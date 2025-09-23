<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

//require_once __DIR__ . '/../vendor/autoload.php'; // AJUSTA si tu vendor está en otra ruta
//use Google\Cloud\Storage\StorageClient;

// ====== Helpers OAuth2 y subida a GCS sin librerías externas ======

/**
 * Igual que tu flujo actual (multipart con token de descarga), pero hace las subidas en PARARELO.
 * Usa curl_multi para disparar todas las peticiones a GCS a la vez (o casi a la vez).
 */
function subirPDFsPorNumeroConcurrente(string $saPath, string $bucketName, ?array $filesArray, ?string $numero, int $maxConcurrent = 4, $cve_cliente): array
{
    if (!extension_loaded('curl'))    throw new Exception('PHP sin extensión cURL');
    if (!extension_loaded('openssl')) throw new Exception('PHP sin extensión OpenSSL');
    if (!is_file($saPath))            throw new Exception('No encuentro JSON en ' . $saPath);
    if (!$numero)                     throw new Exception('Falta numero');
    if (empty($filesArray) || empty($filesArray['name'])) throw new Exception('No llegaron archivos');

    // 1) Token una sola vez (se reutiliza para todos)
    $token = getAccessTokenFromServiceAccount($saPath);

    // 2) Prepara bodies multipart (metadata + bytes) por archivo
    $names = (array)$filesArray['name'];
    $tmps  = (array)$filesArray['tmp_name'];
    $sizes = (array)$filesArray['size'];

    $endpoint = "https://storage.googleapis.com/upload/storage/v1/b/{$bucketName}/o?uploadType=multipart";

    $mh = curl_multi_init();
    $handles = []; // map: chId => ['ch'=>, 'dlTok'=>, 'objectName'=>, 'origName'=>, 'size'=>]

    foreach ($tmps as $i => $tmp) {
        if (!is_uploaded_file($tmp)) continue;

        $origName   = $names[$i] ?? 'archivo.pdf';
        $safeName   = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
        //$objectName = "Cliente/OrdenCompra/Folio/{$numero}/{$safeName}";
        $objectName = "Cliente/OrdenCompra/{$cve_cliente}/{$numero}/{$safeName}";
        $dlTok      = uuidv4();

        $meta = [
            'name'        => $objectName,
            'contentType' => 'application/pdf',
            'metadata'    => ['firebaseStorageDownloadTokens' => $dlTok],
        ];

        $boundary  = 'gcs-' . bin2hex(random_bytes(8));
        $fileBytes = file_get_contents($tmp);
        if ($fileBytes === false) throw new Exception("No se pudo leer el archivo: $tmp");

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($meta, JSON_UNESCAPED_SLASHES) . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/pdf\r\n\r\n";
        $body .= $fileBytes . "\r\n";
        $body .= "--$boundary--\r\n";

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: multipart/related; boundary=' . $boundary,
                'Content-Length: ' . strlen($body),
                'Connection: keep-alive',
            ],
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            // Si tu cURL soporta HTTP/2, esto puede ayudar un poco:
            // CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
        ]);

        curl_multi_add_handle($mh, $ch);
        $handles[(int)$ch] = [
            'ch'         => $ch,
            'dlTok'      => $dlTok,
            'objectName' => $objectName,
            'origName'   => $origName,
            'size'       => $sizes[$i] ?? null,
        ];
    }

    // 3) Ejecuta todas (curl_multi)
    // Nota: con 4 PDFs el "maxConcurrent" no es crítico; si quisieras limitarlo,
    // podríamos implementar una cola; para 1-4 no hace falta.
    do {
        $status = curl_multi_exec($mh, $running);
        if ($status > 0) { /* opcional: log de error de multi */
        }
        curl_multi_select($mh, 1.0); // espera a actividad (reduce CPU spin)
    } while ($running && $status === CURLM_OK);

    // 4) Recolecta respuestas
    $subidos = [];
    foreach ($handles as $h) {
        $ch   = $h['ch'];
        $resp = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if ($code !== 200) {
            throw new Exception("Upload HTTP $code: " . $resp);
        }
        $objInfo = json_decode($resp, true) ?: ['raw' => $resp];
        $downloadURL = "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/" .
            rawurlencode($h['objectName']) . "?alt=media&token={$h['dlTok']}";

        $subidos[] = [
            'name'        => $h['origName'],
            'path'        => $h['objectName'],
            'size'        => $h['size'],
            'downloadURL' => $downloadURL,
            'gcs'         => $objInfo,
        ];
    }
    curl_multi_close($mh);

    return ['success' => true, 'count' => count($subidos), 'files' => $subidos];
}

/**
 * Sube un archivo a Firebase Storage (GCS) con uploadType=multipart.
 * Ideal para PDFs <= ~5MB. No requiere SDK de Google.
 */
function gcsUploadMultipart(string $bucket, string $objectName, string $filePath, string $contentType, string $accessToken, array $extraMetadata = []): array
{
    if (!is_file($filePath)) throw new Exception("Archivo no encontrado: $filePath");

    $meta = [
        'name'        => $objectName,
        'contentType' => $contentType,
    ];
    if ($extraMetadata) $meta['metadata'] = $extraMetadata;

    $boundary  = 'gcs-' . bin2hex(random_bytes(8));
    $fileBytes = file_get_contents($filePath);
    if ($fileBytes === false) throw new Exception("No se pudo leer el archivo: $filePath");

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= json_encode($meta, JSON_UNESCAPED_SLASHES) . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: $contentType\r\n\r\n";
    $body .= $fileBytes . "\r\n";
    $body .= "--$boundary--\r\n";

    $url = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=multipart";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Content-Length: ' . strlen($body),
        ],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $res  = curl_exec($ch);
    if ($res === false) throw new Exception('cURL multipart: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Multipart HTTP $code: $res");

    return json_decode($res, true);
}

/**
 * Valida, obtiene token y sube TODOS los PDFs de $_FILES['pdfs'].
 * Se ajusta a tu lógica: carpeta por **numero** (no pedidoId).
 * Retorna: ['success'=>true, 'count'=>N, 'files'=>[ {name,path,size,downloadURL,gcs} ]]
 */
function subirPDFsPorNumero(string $saPath, string $bucketName, ?array $filesArray, ?string $numero, $cve_cliente): array
{
    if (!extension_loaded('curl'))    throw new Exception('PHP sin extensión cURL');
    if (!extension_loaded('openssl')) throw new Exception('PHP sin extensión OpenSSL');
    if (!is_file($saPath))            throw new Exception('No encuentro JSON en ' . $saPath);
    if (!$numero)                     throw new Exception('Falta numero');
    if (empty($filesArray) || empty($filesArray['name'])) throw new Exception('No llegaron archivos');

    // Tu helper existente para token
    $token = getAccessTokenFromServiceAccount($saPath);

    $names = (array)$filesArray['name'];
    $tmps  = (array)$filesArray['tmp_name'];
    $sizes = (array)$filesArray['size'];

    $subidos = [];
    foreach ($tmps as $i => $tmp) {
        if (!is_uploaded_file($tmp)) continue;

        $origName   = $names[$i] ?? 'archivo.pdf';
        $safeName   = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);

        // 👇 Directorio según tu lógica (usa "numero")
        //$objectName = "Cliente/OrdenCompra/Folio/{$numero}/{$safeName}";
        $objectName = "Cliente/OrdenCompra/{$cve_cliente}/{$numero}/{$safeName}";

        // Token para URL pública estilo Firebase
        $dlTok = uuidv4();

        // Subida MULTIPART (simple y segura para ≤5MB)
        $objInfo = gcsUploadMultipart(
            $bucketName,
            $objectName,
            $tmp,
            'application/pdf',
            $token,
            ['firebaseStorageDownloadTokens' => $dlTok]
        );

        $downloadURL = "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/" .
            rawurlencode($objectName) . "?alt=media&token={$dlTok}";

        $subidos[] = [
            'name'        => $origName,
            'path'        => $objectName,
            'size'        => $sizes[$i] ?? null,
            'downloadURL' => $downloadURL,
            'gcs'         => $objInfo,
        ];
    }

    return ['success' => true, 'count' => count($subidos), 'files' => $subidos];
}
function b64url($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function getAccessTokenFromServiceAccount(string $saPath, string $scope = 'https://www.googleapis.com/auth/devstorage.read_write'): string
{
    if (!is_file($saPath)) {
        throw new Exception("Service Account JSON no encontrado en $saPath");
    }
    $sa = json_decode(file_get_contents($saPath), true, 512, JSON_THROW_ON_ERROR);
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $now = time();
    $claims = [
        'iss'   => $sa['client_email'],
        'scope' => $scope,
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600
    ];
    $jwtUnsigned = b64url(json_encode($header)) . '.' . b64url(json_encode($claims));

    // Firmar con la clave privada del JSON
    $privateKey = openssl_pkey_get_private($sa['private_key']);
    if (!$privateKey) throw new Exception('No se pudo leer la clave privada del JSON');
    $signature = '';
    if (!openssl_sign($jwtUnsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new Exception('Fallo al firmar JWT');
    }
    openssl_pkey_free($privateKey);
    $jwt = $jwtUnsigned . '.' . b64url($signature);

    // Intercambiar el JWT por un access_token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    $post = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt
    ]);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('Error cURL token: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Token HTTP $code: $res");
    $json = json_decode($res, true);
    if (empty($json['access_token'])) throw new Exception('No se recibió access_token');
    return $json['access_token'];
}
// Subida RESUMABLE (dos pasos): inicia sesión + sube bytes
function gcsUploadResumable(string $bucket, string $objectName, string $filePath, string $contentType, string $accessToken, array $extraMetadata = []): array
{
    if (!is_file($filePath)) throw new Exception("Archivo no encontrado: $filePath");
    $metadata = array_merge([
        'name' => $objectName,
        'contentType' => $contentType,
    ], $extraMetadata ? ['metadata' => $extraMetadata] : []);

    // 1) Iniciar sesión de subida
    $initUrl = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=resumable";
    $ch = curl_init($initUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: ' . $contentType
        ],
        CURLOPT_POSTFIELDS => json_encode($metadata, JSON_UNESCAPED_SLASHES),
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) throw new Exception('Error cURL init: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Init HTTP $code: $resp");

    // Extraer header Location
    $location = null;
    foreach (explode("\r\n", $resp) as $h) {
        if (stripos($h, 'Location:') === 0) {
            $location = trim(substr($h, 9));
            break;
        }
    }
    if (!$location) throw new Exception('No se recibió header Location para la subida resumible');

    // 2) Subir bytes del archivo
    $fp = fopen($filePath, 'rb');
    $ch = curl_init($location);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: ' . $contentType
        ],
        CURLOPT_INFILE => $fp,
        CURLOPT_INFILESIZE => filesize($filePath),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 0, // sin límite (o ajusta)
    ]);
    $uploadRes = curl_exec($ch);
    if ($uploadRes === false) {
        fclose($fp);
        throw new Exception('Error cURL upload: ' . curl_error($ch));
    }
    $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($uploadCode !== 200 && $uploadCode !== 201) {
        throw new Exception("Upload HTTP $uploadCode: $uploadRes");
    }
    $obj = json_decode($uploadRes, true);
    return $obj ?: ['raw' => $uploadRes];
}
function uuidv4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    $numFuncion = isset($_POST['numFuncion']) ? (int)$_POST['numFuncion'] : 0;


    switch ($numFuncion) {
        case 33: { // Subir PDFs desde el modal
                // 1) Limpia cualquier salida anterior y obliga JSON
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json; charset=utf-8');
                // (opcional, recomendado) no mostrar notices/warnings en la respuesta
                ini_set('display_errors', '0');
                ini_set('log_errors', '1');
                error_reporting(E_ALL);

                // 2) TU CONFIG
                //$saPath     = __DIR__ . '../../Cliente/JS/keys/firebase-adminsdk.json';
                $saPath = __DIR__.'/../../Cliente/keys/firebase-adminsdk.json'; // <-- verifica la ruta
                $bucketName = 'mdconnecta-4aeb4.firebasestorage.app';

                try {
                    // 3) ENTRADA SEGÚN TU LÓGICA
                    $numero   = $_POST['numero'] ?? null;                  // tu front manda "numero"
                    $cve_cliente   = $_POST['cliente'] ?? null;
                    $filesArr = $_FILES['pdfs'] ?? ($_FILES['pdfs[]'] ?? null);

                    // 4) LLAMADA A TU FUNCIÓN
                    $res = subirPDFsPorNumero($saPath, $bucketName, $filesArr, $numero, $cve_cliente);

                    // 5) RESPUESTA JSON + corte duro
                    http_response_code(200);
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit; // 👈 evita que algo más se imprima después
                } catch (Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit; // 👈 igual aquí
                }

                try {
                    // Tu front manda "numero"
                    $numero   = $_POST['numero'] ?? null;
                    $cve_cliente   = $_POST['cliente'] ?? null;
                    $filesArr = $_FILES['pdfs'] ?? ($_FILES['pdfs[]'] ?? null);

                    // ⬇️ ÚNICO CAMBIO: usa la versión concurrente
                    $res = subirPDFsPorNumeroConcurrente($saPath, $bucketName, $filesArr, $numero, 4, $cve_cliente);

                    http_response_code(200);
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                } catch (Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }

                // --- Prechequeos básicos (sin meter ruido) ---
                if (!extension_loaded('curl')) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'PHP sin extensión cURL']);
                    break;
                }
                if (!extension_loaded('openssl')) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'PHP sin extensión OpenSSL']);
                    break;
                }
                if (!is_file($saPath)) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'No encuentro JSON en ' . $saPath]);
                    break;
                }

                // --- Entrada obligatoria ---
                $numero = $_POST['numero'] ?? null; // el front debe mandar 'numero'
                if (!$numero) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Falta numero']);
                    break;
                }

                // Nombre del campo de archivos: debe llegar como $_FILES['pdfs'] (front usa 'pdfs[]')
                $filesKey = 'pdfs';
                if (empty($_FILES[$filesKey]['name'])) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'No llegaron archivos']);
                    break;
                }

                try {
                    // 1) Token OAuth2 con tu JSON (helpers ya pegados arriba)
                    $token = getAccessTokenFromServiceAccount($saPath);

                    // 2) Subir cada PDF por subida "resumable"
                    $subidos = [];
                    $names = (array)$_FILES[$filesKey]['name'];
                    $tmps  = (array)$_FILES[$filesKey]['tmp_name'];
                    $sizes = (array)$_FILES[$filesKey]['size'];

                    foreach ($tmps as $i => $tmp) {
                        if (!is_uploaded_file($tmp)) continue;

                        $origName   = $names[$i] ?? 'archivo.pdf';
                        $safeName   = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
                        $objectName = "Cliente/OrdenCompra/{$cve_cliente}/{$numero}/{$safeName}";

                        // (Opcional) token de descarga al estilo Firebase
                        $dlTok = uuidv4();

                        // Subida
                        $objInfo = gcsUploadResumable(
                            $bucketName,
                            $objectName,
                            $tmp,
                            'application/pdf',
                            $token,
                            ['firebaseStorageDownloadTokens' => $dlTok]
                        );

                        // URL de descarga (opcional)
                        $downloadURL = "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/" .
                            rawurlencode($objectName) . "?alt=media&token={$dlTok}";

                        $subidos[] = [
                            'name'        => $origName,
                            'path'        => $objectName,
                            'size'        => $sizes[$i] ?? null,
                            'downloadURL' => $downloadURL,
                            'gcs'         => $objInfo,
                        ];
                    }

                    // 3) Respuesta estándar para el front
                    http_response_code(200);
                    echo json_encode(['success' => true, 'count' => count($subidos), 'files' => $subidos], JSON_UNESCAPED_UNICODE);
                    break;
                } catch (Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    break;
                }
            }

            // Verifica que se haya enviado al menos un archivo
            //if (!isset($_FILES['numero']) || empty($_FILES['imagen']['name'])) {
            //  echo json_encode(['success' => false, 'message' => 'No se pudo subir ninguna imagen.']);
            // exit;
            //}

            if (!isset($_FILES['pdfs']) || empty($_FILES['pdfs']['name'])) {
                echo json_encode(['success' => false, 'message' => 'No se recibieron archivos (pdfs[]).']);
                exit;
            }

            $numero  = $_POST['numero']  ?? null;   // opcional
            $noEmpresa = $_POST['noEmpresa'] ?? null;   // opcional
            $claveSae  = $_POST['claveSae']  ?? null;   // opcional

            $maxFiles = 4;
            $maxSize  = 50 * 1024 * 1024; // 5 MB

            $names = (array)($_FILES['pdfs']['name'] ?? []);
            $tmps  = (array)($_FILES['pdfs']['tmp_name'] ?? []);
            $errs  = (array)($_FILES['pdfs']['error'] ?? []);
            $sizes = (array)($_FILES['pdfs']['size'] ?? []);
            $types = (array)($_FILES['pdfs']['type'] ?? []);

            if (count($names) > $maxFiles) {
                echo json_encode(['success' => false, 'message' => "Máximo {$maxFiles} archivos."]);
                exit;
            }


            //$projectId  = 'mdconnecta-4aeb4';
            //$bucketName = $projectId . '.appspot.com';
            //$bucketName = 'mdconnecta-4aeb4.firebasestorage.app';
            //$storage = new StorageClient(['projectId' => $projectId]);
            //$bucket  = $storage->bucket($bucketName);
            $projectId  = 'mdconnecta-4aeb4';
            $bucketName = 'mdconnecta-4aeb4.firebasestorage.app';

            $storage = new Google\Cloud\Storage\StorageClient([
                'projectId'   => 'mdconnecta-4aeb4',
                'keyFilePath' => __DIR__ . 'C:/xampp/htdocs/MDConnecta/Cliente/keys/firebase-adminsdk.json', // <-- aquí
            ]);

            $bucket = $storage->bucket($bucketName);

            $base = ['Clientes'];
            if (!empty($noEmpresa)) $base[] = 'EMP' . preg_replace('/\D+/', '', (string)$noEmpresa);
            if (!empty($claveSae))  $base[] = 'SAE' . preg_replace('/\D+/', '', (string)$claveSae);
            $base[] = !empty($numero) ? ('PED' . preg_replace('/\D+/', '', (string)$numero)) : 'sin_pedido';
            $base[] = date('Y');
            $base[] = date('m');
            $base[] = date('d');
            $basePath = implode('/', $base);

            $uploaded = [];
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

            for ($i = 0; $i < count($names); $i++) {
                $name = $names[$i];
                $tmp  = $tmps[$i];
                $err  = $errs[$i];
                $size = $sizes[$i];

                if ($err !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("Error al subir '{$name}' (código {$err}).");
                }
                if ($size > $maxSize) {
                    throw new RuntimeException("El archivo '{$name}' excede 50MB.");
                }

                $mime = $types[$i] ?? 'application/pdf';
                if ($finfo) {
                    $detected = finfo_file($finfo, $tmp);
                    if ($detected) $mime = $detected;
                }
                if (stripos($mime, 'pdf') === false) {
                    throw new RuntimeException("El archivo '{$name}' no es PDF (MIME: {$mime}).");
                }

                $baseName = pathinfo($name, PATHINFO_FILENAME);
                $baseName = iconv('UTF-8', 'ASCII//TRANSLIT', $baseName);
                $baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $baseName) ?: 'archivo';

                $uniq = bin2hex(random_bytes(4));
                $objectName = "{$basePath}/" . time() . "_{$uniq}_{$baseName}.pdf";

                $token = bin2hex(random_bytes(16));

                $bucket->upload(
                    fopen($tmp, 'r'),
                    [
                        'name' => $objectName,
                        'metadata' => [
                            'contentType' => 'application/pdf',
                            'metadata' => ['firebaseStorageDownloadTokens' => $token]
                        ]
                    ]
                );

                $encoded = rawurlencode($objectName);
                // Puedes usar el dominio googleapis o el de firebasestorage.app
                $url = "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/{$encoded}?alt=media&token={$token}";
                // $url = "https://mdconnecta-4aeb4.firebasestorage.app/v0/b/{$bucketName}/o/{$encoded}?alt=media&token={$token}";

                $uploaded[] = [
                    'name' => $name,
                    'path' => $objectName,
                    'url'  => $url,
                    'size' => $size
                ];
            }
            if ($finfo) finfo_close($finfo);

            echo json_encode([
                'success'   => true,
                'message'   => 'PDF(s) subidos correctamente.',
                'numero'  => $numero,
                'noEmpresa' => $noEmpresa,
                'claveSae'  => $claveSae,
                'files'     => $uploaded
            ]);
            exit;
    }
    //Acaba
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}


/****************************************/
function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        return ['success' => false, 'message' => 'Error al obtener los datos de Firebase'];
    }
    $documents = json_decode($result, true);
    if (!isset($documents['documents'])) {
        return ['success' => false, 'message' => 'No se encontraron documentos'];
    }
    // Busca el documento donde coincida el campo `noEmpresa`
    foreach ($documents['documents'] as $document) {
        $fields = $document['fields'];
        /*var_dump($fields['noEmpresa']['integerValue']);
        var_dump($noEmpresa);*/
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {  //Cada empresa tiene su propia conexion 
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'nombreBanco'
                    => $fields['nombreBanco']['stringValue'] ?? "",
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
/****************************************/


function obtenerLineas($claveSae, $conexionData) // sirve para armar la conexion y el numero de base sql server
{
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID"      => $conexionData['usuario'],
        "PWD"      => $conexionData['password'],
        "CharacterSet"         => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    if (!$conn) {
        throw new Exception("No pude conectar a la base de datos");
    }
    try {
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIN" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $sql = "SELECT * FROM $nombreTabla";
        $stmt   = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            throw new Exception("Problema al optener las lineas:\n{$errors}");
        }

        $datos = [];
        // Procesar resultados
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }
        //var_dump($datos);

        if (!empty($datos)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $datos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se Encontraron lineas.']);
        }
    } catch (Exception $e) {
        // Si falla cualquiera, deshacemos TODO:
        sqlsrv_rollback($conn);
        sqlsrv_close($conn);
        //return ['success' => false, 'message' => $e->getMessage()];
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}


function subirImagenArticulo($conexionData)
{
    // Verifica que se haya enviado al menos un archivo
    if (!isset($_FILES['cliente']) || empty($_FILES['cliente']['name'])) {  //files el nombre de mi variable que mande
        echo json_encode(['success' => false, 'message' => 'No se pudo subir ningun archivo.']);
        exit;
    }

    // Verifica que se haya enviado la clave del artículo
    if (!isset($_POST['cliente'])) {  // cveArt                              //se puede poner mejor que se llame la clave del cliente
        echo json_encode(['success' => false, 'message' => 'No se proporcionó la clave del artículo.']);
        exit;
    }

    $claveCliente = $_POST['cliente'];
    $cveArt = $_POST['cveArt'];
    $imagenes = $_FILES['imagen'];
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app"; // Cambia esto por tu bucket


    // Subir y procesar cada archivo
    $rutasImagenes = [];
    foreach ($imagenes['tmp_name'] as $index => $tmpName) {
        if ($imagenes['error'][$index] === UPLOAD_ERR_OK) {
            $nombreArchivo = $claveCliente . "_" . uniqid() . "_" . basename($imagenes['name'][$index]);
            $rutaFirebase = "Cliente/{$claveCliente}/{$nombreArchivo}";
            $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?name=" . urlencode($rutaFirebase);

            // Leer el archivo
            $archivo = file_get_contents($tmpName);

            // Subir el archivo a Firebase Storage
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/octet-stream"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $archivo);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $resultado = json_decode($response, true);

            if (isset($resultado['name'])) {
                $urlPublica = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/{$resultado['name']}?alt=media";
                $rutasImagenes[] = $urlPublica;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir una achivo.', 'response' => $response]);
                exit;
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Imágenes subidas correctamente.', 'imagenes' => $rutasImagenes]);
}


$archivo = file_get_contents($tmpName);

// Subir el archivo a Firebase Storage
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/octet-stream"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $archivo);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$resultado = json_decode($response, true);

if (isset($resultado['name'])) {
    $urlPublica = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/{$resultado['name']}?alt=media";
    $rutasImagenes[] = $urlPublica;
} else {
    echo json_encode(['success' => false, 'message' => 'Error al subir una imagen.', 'response' => $response]);
    exit;
}



// -----------------------------------------------------------------------------------------------------//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    // Si es una solicitud POST, asignamos el valor de numFuncion
    $funcion = $_POST['numFuncion'];
    //var_dump($funcion);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    // Si es una solicitud GET, asignamos el valor de numFuncion
    $funcion = $_GET['numFuncion'];
    //var_dump($funcion);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la peticion.']);
    exit;
}


switch ($funcion) {

    case 4:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        $linea = $_GET['linea'];
        obtenerProductosPorLinea($claveSae, $conexionData, $linea);
        break;

    case 14:
        // Obtener conexión
        /*$claveSae = "02";
        $noEmpresa = "02";*/
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        //$claveSae = "01";
        //$noEmpresa = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae); //Aqui
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }

        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];

        // Llamar a la función para extraer productos
        subirImagenArticulo($conexionData);
        break;


    case 31: { // Subir PDFs de Orden de Compra a Firebase Storage
            header('Content-Type: application/json; charset=utf-8');

            try {
                // ----------- Entradas esperadas -----------
                $numero  = $_POST['numero']  ?? null;   // opcional (alta nueva puede venir null)
                $noEmpresa = $_POST['noEmpresa'] ?? null;   // opcional
                $claveSae  = $_POST['claveSae']  ?? null;   // opcional

                if (!isset($_FILES['pdfs'])) {
                    echo json_encode(['success' => false, 'message' => 'No se recibieron archivos (pdfs[]).']);
                    break;
                }

                // ----------- Validaciones básicas -----------
                $maxFiles  = 4;
                $maxSize   = 5 * 1024 * 1024; // 5 MB
                $filesInfo = $_FILES['pdfs'];

                // Normalizar a arreglo
                $names = is_array($filesInfo['name']) ? $filesInfo['name'] : [$filesInfo['name']];
                $tmps  = is_array($filesInfo['tmp_name']) ? $filesInfo['tmp_name'] : [$filesInfo['tmp_name']];
                $errs  = is_array($filesInfo['error']) ? $filesInfo['error'] : [$filesInfo['error']];
                $sizes = is_array($filesInfo['size']) ? $filesInfo['size'] : [$filesInfo['size']];
                $types = is_array($filesInfo['type']) ? $filesInfo['type'] : [$filesInfo['type']];

                if (count($names) > $maxFiles) {
                    echo json_encode(['success' => false, 'message' => "Solo se permite subir hasta {$maxFiles} PDFs por solicitud."]);
                    break;
                }

                // ----------- Config Firebase / Storage -----------
                // Proyecto/bucket por default en Firebase:
                $projectId  = 'mdconnecta-4aeb4';
                //$bucketName = $projectId . '.appspot.com';
                //$bucketName = 'mdconnecta-4aeb4.firebasestorage.app'; // <-- ESTE es el bucket, no el dominio .app
                $bucketName = 'mdconnecta-4aeb4.firebasestorage.app';
                // Si tu app ya carga el autoload y credenciales en otro punto, no dupliques.
                // use Google\Cloud\Storage\StorageClient;  // Asegúrate de tener el "use" al inicio del archivo
                $storage = new Google\Cloud\Storage\StorageClient([
                    'projectId' => $projectId,
                    // Si usas variable de entorno GOOGLE_APPLICATION_CREDENTIALS, no necesitas keyFilePath
                    // 'keyFilePath' => '/ruta/a/service-account.json',
                ]);

                $serviceAccountPath = __DIR__ . '/../secrets/mdconnecta-4aeb4-service-account.json'; // AJUSTA
                if (!file_exists($serviceAccountPath)) {
                    echo json_encode(['success' => false, 'message' => 'Service account JSON no encontrado']);
                    exit;
                }

                $storage = new Google\Cloud\Storage\StorageClient([
                    'keyFilePath' => $serviceAccountPath,
                ]);
                $bucket = $storage->bucket($bucketName);

                // Comprobación clara (permiso + existencia)
                if (!$bucket->exists()) {
                    echo json_encode(['success' => false, 'message' => "Bucket '$bucketName' no existe o no es accesible con estas credenciales"]);
                    exit;
                }

                $bucket = $storage->bucket($bucketName);

                // Construir ruta base en el bucket
                $basePathParts = ['Clientes'];
                if (!empty($noEmpresa)) $basePathParts[] = 'EMP' . preg_replace('/\D+/', '', (string)$noEmpresa);
                if (!empty($claveSae))  $basePathParts[] = 'SAE' . preg_replace('/\D+/', '', (string)$claveSae);
                $basePathParts[] = !empty($numero) ? ('PED' . preg_replace('/\D+/', '', (string)$numero)) : 'sin_pedido';
                // Fecha para segmentar
                $basePathParts[] = date('Y');
                $basePathParts[] = date('m');
                $basePathParts[] = date('d');
                $basePath = implode('/', $basePathParts);

                // ----------- Subida de cada archivo -----------
                $uploaded = [];
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

                for ($i = 0; $i < count($names); $i++) {
                    $name = $names[$i];
                    $tmp  = $tmps[$i];
                    $err  = $errs[$i];
                    $size = $sizes[$i];
                    $type = $types[$i];

                    if ($err !== UPLOAD_ERR_OK) {
                        throw new RuntimeException("Error al subir el archivo '{$name}' (código {$err}).");
                    }

                    if ($size > $maxSize) {
                        throw new RuntimeException("El archivo '{$name}' excede el tamaño máximo de 5MB.");
                    }

                    // Validar que realmente sea PDF
                    $mime = $type;
                    if ($finfo) {
                        $detected = finfo_file($finfo, $tmp);
                        if ($detected) $mime = $detected;
                    }
                    if (stripos($mime, 'pdf') === false) {
                        throw new RuntimeException("El archivo '{$name}' no parece ser un PDF (MIME: {$mime}).");
                    }

                    // Sanitizar nombre base
                    $basename = pathinfo($name, PATHINFO_FILENAME);
                    $basename = iconv('UTF-8', 'ASCII//TRANSLIT', $basename);
                    $basename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $basename);
                    if ($basename === '' || $basename === null) $basename = 'archivo';

                    // Nombre final único en el bucket
                    $uniq = bin2hex(random_bytes(4)); // 8 hex
                    $destObject = "{$basePath}/" . time() . "_{$uniq}_{$basename}.pdf";

                    // Generar token para descarga pública (Firebase quirk)
                    $token = bin2hex(random_bytes(16));

                    // Subir
                    $object = $bucket->upload(
                        fopen($tmp, 'r'),
                        [
                            'name' => $destObject,
                            'metadata' => [
                                'contentType' => 'application/pdf',
                                // Importante: para obtener URL pública con token en Firebase
                                'metadata' => [
                                    'firebaseStorageDownloadTokens' => $token
                                ]
                            ]
                        ]
                    );

                    // URL de descarga directa (puedes usar también el dominio firebasestorage.app)
                    $encodedPath = rawurlencode($destObject);
                    $downloadUrl = "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/{$encodedPath}?alt=media&token={$token}";
                    // Alternativa con dominio app:
                    // $downloadUrl = "https://mdconnecta-4aeb4.firebasestorage.app/v0/b/{$bucketName}/o/{$encodedPath}?alt=media&token={$token}";

                    $uploaded[] = [
                        'name' => $name,
                        'path' => $destObject,
                        'url'  => $downloadUrl,
                        'size' => $size
                    ];
                }

                if ($finfo) finfo_close($finfo);

                echo json_encode([
                    'success' => true,
                    'message' => 'PDF(s) subidos correctamente.',
                    'numero' => $numero,
                    'noEmpresa' => $noEmpresa,
                    'claveSae' => $claveSae,
                    'files' => $uploaded
                ]);
            } catch (Throwable $e) {
                // Log opcional: error_log('[case31 subir PDFs] ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }

            break;
        }
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
