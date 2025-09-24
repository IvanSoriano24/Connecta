<?php
// ===== Utilidades comunes =====
function send_json($arr, int $code = 200)
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}
function b64url($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getAccessTokenFromServiceAccount(string $saPath, string $scope = 'https://www.googleapis.com/auth/devstorage.read_only'): string
{
    if (!is_file($saPath)) throw new Exception("Service Account JSON no encontrado: $saPath");
    $sa   = json_decode(file_get_contents($saPath), true, 512, JSON_THROW_ON_ERROR);
    $hdr  = ['alg' => 'RS256', 'typ' => 'JWT'];
    $now  = time();
    $clm  = [
        'iss'   => $sa['client_email'],
        'scope' => $scope,
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600
    ];
    $unsigned = b64url(json_encode($hdr)) . '.' . b64url(json_encode($clm));
    $pkey = openssl_pkey_get_private($sa['private_key']);
    if (!$pkey) throw new Exception('No se pudo leer la private_key');
    $sig = '';
    openssl_sign($unsigned, $sig, $pkey, OPENSSL_ALGO_SHA256);
    openssl_pkey_free($pkey);
    $jwt = $unsigned . '.' . b64url($sig);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('cURL token: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Token HTTP $code: $res");
    $j = json_decode($res, true);
    if (empty($j['access_token'])) throw new Exception('Sin access_token');
    return $j['access_token'];
}

// Lista objetos bajo un prefijo (con paginación)
function gcs_list_objects(string $bucket, string $prefix, string $token): array
{
    $items = [];
    $pageToken = null;
    do {
        $url = "https://storage.googleapis.com/storage/v1/b/" . rawurlencode($bucket) . "/o?prefix=" . rawurlencode($prefix) . "&fields=items(name,size,updated),nextPageToken";
        if ($pageToken) $url .= '&pageToken=' . urlencode($pageToken);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60
        ]);
        $res = curl_exec($ch);
        if ($res === false) throw new Exception('cURL list: ' . curl_error($ch));
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) throw new Exception("List HTTP $code: $res");
        $json = json_decode($res, true) ?: [];
        if (!empty($json['items'])) $items = array_merge($items, $json['items']);
        $pageToken = $json['nextPageToken'] ?? null;
    } while ($pageToken);
    return $items;
}

// Descarga un objeto completo (bytes) vía alt=media
function gcs_download_bytes(string $bucket, string $objectName, string $token): string
{
    $url = "https://storage.googleapis.com/storage/v1/b/" . rawurlencode($bucket) . "/o/" . rawurlencode($objectName) . "?alt=media";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 0
    ]);
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('cURL get: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Get HTTP $code (obj: $objectName)");
    return $res;
}

// ======= TUS FUNCIONES =======
function buscarArchivos($noInv)
{
    $bucket     = 'mdconnecta-4aeb4.firebasestorage.app'; // ⬅️ mismo que usas en subidas
    $saPath     = __DIR__ . '/../../Cliente/keys/firebase-adminsdk.json';
    $prefix     = "Inventario/{$noInv}/";                 // árbol que tú estás usando
    $token      = getAccessTokenFromServiceAccount($saPath, 'https://www.googleapis.com/auth/devstorage.read_only');
    $objetos    = gcs_list_objects($bucket, $prefix, $token);
    return ['success' => true, 'count' => count($objetos), 'items' => $objetos];
}

function descargarArchivos($noInv)
{
    $bucket     = 'mdconnecta-4aeb4.firebasestorage.app'; // ⬅️ mismo bucket
    $saPath     = __DIR__ . '/../../Cliente/keys/firebase-adminsdk.json';
    $prefix     = "Inventario/{$noInv}/";
    $token      = getAccessTokenFromServiceAccount($saPath, 'https://www.googleapis.com/auth/devstorage.read_only');

    $items = gcs_list_objects($bucket, $prefix, $token);
    if (empty($items)) {
        // no imprimas ZIP vacío
        send_json(['success' => false, 'message' => 'No hay archivos para este inventario.'], 404);
    }

    // Crea ZIP temporal
    $tmpZip = tempnam(sys_get_temp_dir(), 'invzip_');
    $zip = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
        send_json(['success' => false, 'message' => 'No se pudo crear ZIP temporal'], 500);
    }

    foreach ($items as $it) {
        $name = $it['name'] ?? '';
        if ($name === '' || substr($name, -1) === '/') continue; // salta "carpetas"
        // ruta relativa dentro del zip (quita el prefijo "Inventario/{noInv}/")
        $rel = ltrim(substr($name, strlen($prefix)), '/');
        if ($rel === '') $rel = basename($name);

        // descarga bytes y añade al zip
        $bytes = gcs_download_bytes($bucket, $name, $token);
        $zip->addFromString($rel, $bytes);
    }
    $zip->close();

    // Stream de descarga
    $filename = "Inventario_{$noInv}_evidencia.zip";
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpZip));
    header('Cache-Control: no-store');
    readfile($tmpZip);
    @unlink($tmpZip);
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
    case 1: { // streamear ZIP (GET o POST)
        $noInv = $_REQUEST['noInv'] ?? null; // acepta GET o POST
        if (!$noInv) {
            // si quieres, puedes mandar un pequeño JSON de error
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Falta noInv']);
            exit;
        }
        // Esta función debe ENVIAR el ZIP y hacer exit por dentro
        descargarArchivos($noInv);
        // No pongas ningún echo aquí; descargarArchivos ya terminó la respuesta
        exit;
    }

    case 2: { // sólo verificar si hay archivos (JSON)
        $noInv = $_POST['noInv'] ?? $_GET['noInv'] ?? null;
        if (!$noInv) {
            echo json_encode(['success' => false, 'message' => 'Falta noInv']);
            exit;
        }
        $found = buscarArchivos($noInv);
        $count = is_array($found) ? (int)($found['count'] ?? 0) : (int)$found;
        echo json_encode(['success' => $count > 0, 'count' => $count]);
        exit;
    }

    default: {
        echo json_encode(['success' => false, 'message' => 'Función no válida']);
        exit;
    }
}

