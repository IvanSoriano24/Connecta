<?php
require 'firebase.php';
require __DIR__ . '/funciones.php';      // carga el autoloader
use PhpCfdi\Credentials\Credential;      // importa la clase

session_start(); // Iniciar sesión al inicio del archivo
/*var_dump($_SESSION);
exit;*/

// Verifica si el método de la solicitud es GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'];
    if ($action === 'get') {
        $usuario = $_GET['usuario'];
        listaEmpresas($usuario); // Llamar a la función para obtener las empresas
    }
}

// Verifica si el método de la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    //var_dump($action);
    if ($action === 'sesion') {
        try {
            if (isset($_POST['id'], $_POST['noEmpresa'], $_POST['razonSocial'])) {
                $id = $_POST['id'];
                $noEmpresa = $_POST['noEmpresa'];
                $razonSocial = $_POST['razonSocial'];
                $claveUsuario = $_POST['claveUsuario'];
                $claveSae = $_POST['claveSae'];
                $contrasena = $_POST['contrasena'];

                // Lógica de sesión
                $_SESSION['empresa'] = [
                    'id' => $id,
                    'noEmpresa' => $noEmpresa,
                    'razonSocial' => $razonSocial,
                    'claveUsuario' => $claveUsuario,
                    'claveSae' => $claveSae,
                    'contrasena' => $contrasena
                ];
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión de empresa guardada correctamente.',
                    'data' => $_SESSION['empresa']
                ]);
            } else if (isset($_POST['ed']) && $_POST['ed'] === '2') {

                $id = $_SESSION['empresa']['id'];
                $noEmpresa = $_SESSION['empresa']['noEmpresa'];
                $razonSocial = $_SESSION['empresa']['razonSocial'];
                $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? 0;
                $claveSae = $_SESSION['empresa']['claveSae'] ?? 0;
                $contrasena = $_SESSION['empresa']['contrasena'] ?? 0;
                //var_dump($noEmpresa);
                obtenerEmpresa($noEmpresa);

                // Lógica de sesión
                $_SESSION['empresa'] = [
                    'id' => $id,
                    'noEmpresa' => $noEmpresa,
                    'razonSocial' => $razonSocial,
                    'claveUsuario' => $claveUsuario,
                    'claveSae' => $claveSae,
                    'contrasena' => $contrasena
                ];
            } else {
                echo json_encode(['success' => false, 'message' => 'Faltan parámetros.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'factura') {
        try {
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
            obtenerCsd($noEmpresa);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'save') {
        try {
            $noEmpresa = obtenerNoEmpresa();
            /*var_dump($noEmpresa['id']+1);
            die;*/
            //noEmpresa = str_pad($_POST['noEmpresa'], 2, '0', STR_PAD_LEFT);
            $id = $noEmpresa['id'] + 1;
            $empre = $noEmpresa['noEmpresa'] + 1;
            $data = [
                'id' => $id,
                'noEmpresa' => $empre,
                'razonSocial' => $_POST['razonSocial'],
                'rfc' => $_POST['rfc'],
                'regimenFiscal' => $_POST['regimenFiscal'],
                'calle' => $_POST['calle'],
                'numExterior' => $_POST['numExterior'],
                'numInterior' => $_POST['numInterior'],
                'entreCalle' => $_POST['entreCalle'],
                'colonia' => $_POST['colonia'],
                'referencia' => $_POST['referencia'],
                'pais' => $_POST['pais'],
                'estado' => $_POST['estado'],
                'municipio' => $_POST['municipio'],
                'codigoPostal' => $_POST['codigoPostal'],
                'poblacion' => $_POST['poblacion']
            ];
            guardarEmpresa($data);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'saveFac') {
        try {
            $idDocumento = $_POST['idDocumentoFac'];
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];;
            $idFat     = $_POST['idFat'];
            $keyPass   = $_POST['keyPassword'];
            $baseDir = "../XML/sdk2/certificados/{$noEmpresa}/";
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }
            $response = ['success' => true];
            $result = probarCsd($keyPass, $baseDir);
            if(!$result){
                echo json_encode(['success' => false, 'message' => "No se pudo abrir los CSD"]);
            }
            // procesar .cer
            if (isset($_FILES['cerFile']) && $_FILES['cerFile']['error'] === UPLOAD_ERR_OK) {
                $dest = $baseDir . basename($_FILES['cerFile']['name']);
                if (!move_uploaded_file($_FILES['cerFile']['tmp_name'], $dest)) {
                    $response = ['success' => false, 'message' => "No pude mover el .cer"];
                }
            } else {
                $response = ['success' => false, 'message' => "Error al subir el .cer"];
            }
            // procesar .key
            if ($response['success'] && isset($_FILES['permFile']) && $_FILES['permFile']['error'] === UPLOAD_ERR_OK) {
                $dest = $baseDir . basename($_FILES['permFile']['name']);
                if (!move_uploaded_file($_FILES['permFile']['tmp_name'], $dest)) {
                    $response = ['success' => false, 'message' => "No pude mover el .key"];
                }
            } else {
                if ($response['success'])
                    $response = ['success' => false, 'message' => "Error al subir el .key"];
            }

            if ($response['success']) {
                // aquí guardas en Firebase o BD la ruta y la contraseña
                guardarDatosFactura([
                    'idFat'      => $idFat,
                    'noEmpresa'  => $noEmpresa,
                    'cerPath'    => $baseDir . $_FILES['cerFile']['name'],
                    'keyPath'    => $baseDir . $_FILES['permFile']['name'],
                    'keyPassword' => $keyPass,
                    'idDocumento' => $idDocumento
                ]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode($response);
            }
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'update') {
        try {
            $data = [
                'id' => $_POST['id'],
                'idDocumento' => $_POST['idDocumento'],
                'noEmpresa' => $_POST['noEmpresa'],
                'razonSocial' => $_POST['razonSocial'],
                'rfc' => $_POST['rfc'],
                'regimenFiscal' => $_POST['regimenFiscal'],
                'calle' => $_POST['calle'],
                'numExterior' => $_POST['numExterior'],
                'numInterior' => $_POST['numInterior'],
                'entreCalle' => $_POST['entreCalle'],
                'colonia' => $_POST['colonia'],
                'referencia' => $_POST['referencia'],
                'pais' => $_POST['pais'],
                'estado' => $_POST['estado'],
                'municipio' => $_POST['municipio'],
                'codigoPostal' => $_POST['codigoPostal'],
                'poblacion' => $_POST['poblacion']
            ];
            actualizarEmpresa($data);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'verificar') {
        try {
            //$noEmpresa = $_POST['noEmpresa'];
            //$noEmpresa = str_pad($_POST['noEmpresa'], 2, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
            $noEmpresa = $_POST['noEmpresa'];
            verificarNoEmpresa($noEmpresa);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'pruebaFac') {
        try {
            $idDocumento = $_POST['idDocumentoFac'];
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];;
            $idFat     = $_POST['idFat'];
            $keyPass   = $_POST['keyPassword'];
            $baseDir = "../XML/sdk2/certificados/{$noEmpresa}/";
            $result = probarCsd($keyPass, $baseDir);
            if(!$result){
                echo json_encode(['success' => false, 'message' => "No se pudo abrir los CSD"]);
            } else{
                echo json_encode(['success' => true, 'message' => "CSD correctos"]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'regimen') {
        try {
            obtenerRegimenes();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
function obtenerNoEmpresa()
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";
    // Configura el contexto de la solicitud para manejar errores y tiempo de espera
    $context = stream_context_create([
        'http' => [
            'timeout' => 10 // Tiempo máximo de espera en segundos
        ]
    ]);
    // Realizar la consulta a Firebase
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false; // Error en la petición
    }

    // Decodifica la respuesta JSON
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false; // No se encontraron documentos
    }

    if (isset($data['documents'])) {
        $empresas = [];
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            // Verificar que el usuario coincida

            $empresas[] = [
                'id' => isset($fields['id']['integerValue']) ? $fields['id']['integerValue'] : "N/A", // Validar id
                'noEmpresa' => isset($fields['noEmpresa']['integerValue']) ? $fields['noEmpresa']['integerValue'] : "No especificado" // Validar noEmpresa
            ];
        }
        if (count($empresas) > 0) {
            usort($empresas, function ($a, $b) {
                return strcmp($a['noEmpresa'], $b['noEmpresa']);
            });

            $ultimoDato = end($empresas); // Obtiene el último dato del arreglo ordenado

            return $ultimoDato; // Retorna solo el último dato
        } else {
            return json_encode(['success' => false, 'message' => 'El usuario no tiene empresas asociadas.']);
        }
    } else {
        return json_encode(['success' => false, 'message' => 'No se encontraron relaciones de empresas para este usuario.']);
    }
}

function obtenerRegimenes()
{
    $filePath = "../../Complementos/CAT_REGIMENFISCAL.xml";
    if (!file_exists($filePath)) {
        echo "El archivo no existe en la ruta: $filePath";
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo "Error al leer el archivo XML en $filePath";
        return;
    }

    try {
        $regimenes = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return;
    }

    $regimen = [];
    // Iterar sobre cada <row>
    foreach ($regimenes->row as $row) {
        //echo "Regimen: " . (string)$row['c_RegimenFiscal'] . " - " . (string)$row['Descripcion'] . "<br/>";
        $regimen[] = [
            'c_RegimenFiscal' => (string)$row['c_RegimenFiscal'],
            'Descripcion' => (string)$row['Descripcion'],
            'Fisica' => (string)$row['Fisica'],
            'Moral' => (string)$row['Moral']
        ];
    }
    if (!empty($regimen)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($regimen, function ($a, $b) {
            return strcmp($a['c_RegimenFiscal'] ?? '', $b['c_RegimenFiscal'] ?? '');
        });


        echo json_encode(['success' => true, 'data' => $regimen]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron ningun regimen.']);
    }
}

function verificarNoEmpresa($noEmpresa)
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    // Configura el contexto de la solicitud para manejar errores y tiempo de espera
    $context = stream_context_create([
        'http' => [
            'timeout' => 10 // Tiempo máximo de espera en segundos
        ]
    ]);

    // Realizar la consulta a Firebase
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false; // Error en la petición
    }

    // Decodifica la respuesta JSON
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false; // No se encontraron documentos
    }
    // Busca los datos de la empresa por noEmpresa
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        $documentName = $document['name']; // Aquí obtienes el nombre completo del documento
        if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
            echo json_encode(['success' => false, 'message' => 'Número de empresa ocupado']);
            return;
        }
    }
    echo json_encode(['success' => true, 'message' => 'Número de empresa válido']);
    return;
}

function obtenerEmpresa($noEmpresa)
{
    // Verifica si el número de empresa fue proporcionado
    if (!empty($noEmpresa)) {
        // Intenta obtener los datos desde Firebase
        $datosCompletos = obtenerDatosEmpresa($noEmpresa);

        if ($datosCompletos) {
            // Si los datos son obtenidos correctamente, guarda la empresa en la sesión
            $_SESSION['empresaInfo'] = $datosCompletos;
            //var_dump($_SESSION['empresa']);
            return responderJson(true, 'Datos de la empresa obtenidos correctamente.', $datosCompletos);
        } else {
            return responderJson(false, 'No se encontraron datos para la empresa especificada.');
        }
    } else {
        return responderJson(false, 'El número de empresa no fue proporcionado.');
    }
}
function obtenerCsd(int $noEmpresa)
{
    $rutaPrincipal = __DIR__ . "/../XML/sdk2/certificados/$noEmpresa/";
    // Buscar .cer y .key en esa carpeta o subcarpetas
    $archivoCer = glob($rutaPrincipal . "{*.cer,*/*.cer}", GLOB_BRACE);
    $archivoKey = glob($rutaPrincipal . "{*.key,*/*.key}", GLOB_BRACE);

    // Si no hay ambos archivos, salimos
    if (empty($archivoCer) || empty($archivoKey)) {
        echo json_encode(['success' => false, 'message' => "No se encontro los archivos"]);
        return false;
    }
    // Obtener solo los nombres de los archivos usando basename
    $archivoCerNames = array_map('basename', $archivoCer);
    $archivoKeyNames = array_map('basename', $archivoKey);
    // Recuperar la contraseña cifrada desde Firestore
    $csd = obtenerContrasenaCSD($noEmpresa);
    if (!$csd || !isset($csd['keyEncValue'], $csd['keyEncIv'])) {
        echo json_encode(['success' => false, 'message' => "No se encontro la contrasena"]);
        return false;
    }

    // Descifrarla
    $password = decryptValue($csd['keyEncValue'], $csd['keyEncIv']);
    $data = [
        'cer' => $archivoCerNames,
        'key' => $archivoKeyNames,
        'pass' => $password
    ];
    echo json_encode(['success' => true, 'data' => $data]);
}

function obtenerContrasenaCSD($noEmpresa)
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    $resp = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 10]]));
    if ($resp === false) return false;

    $data = json_decode($resp, true);
    foreach ($data['documents'] ?? [] as $doc) {
        $f = $doc['fields'] ?? [];
        if (($f['noEmpresa']['integerValue'] ?? null) == $noEmpresa) {
            return [
                'idDocumento' => basename($doc['name']),
                'keyEncValue' => $f['keyEncValue']['stringValue'] ?? null,
                'keyEncIv'    => $f['keyEncIv']['stringValue']    ?? null,
            ];
        }
    }
    return false;
}
// Función para obtener los datos desde Firebase
function obtenerDatosEmpresa($noEmpresa)
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    // Configura el contexto de la solicitud para manejar errores y tiempo de espera
    $context = stream_context_create([
        'http' => [
            'timeout' => 10 // Tiempo máximo de espera en segundos
        ]
    ]);

    // Realizar la consulta a Firebase
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false; // Error en la petición
    }

    // Decodifica la respuesta JSON
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false; // No se encontraron documentos
    }
    // Busca los datos de la empresa por noEmpresa
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        $documentName = $document['name']; // Aquí obtienes el nombre completo del documento
        $documentId = basename($documentName);
        if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
            return [
                'idDocumento' => $documentId,
                'noEmpresa' => $fields['noEmpresa']['integerValue'] ?? null,
                'id' => $fields['id']['integerValue'] ?? null,
                'razonSocial' => $fields['razonSocial']['stringValue'] ?? null,
                'rfc' => $fields['rfc']['stringValue'] ?? null,
                'regimenFiscal' => $fields['regimenFiscal']['stringValue'] ?? null,
                'calle' => $fields['calle']['stringValue'] ?? null,
                'numExterior' => $fields['numExterior']['stringValue'] ?? null,
                'numInterior' => $fields['numInterior']['stringValue'] ?? null,
                'entreCalle' => $fields['entreCalle']['stringValue'] ?? null,
                'colonia' => $fields['colonia']['stringValue'] ?? null,
                'referencia' => $fields['referencia']['stringValue'] ?? null,
                'pais' => $fields['pais']['stringValue'] ?? null,
                'estado' => $fields['estado']['stringValue'] ?? null,
                'municipio' => $fields['municipio']['stringValue'] ?? null,
                'codigoPostal' => $fields['codigoPostal']['stringValue'] ?? null,
                'poblacion' => $fields['poblacion']['stringValue'] ?? null
            ];
        }
    }

    return false; // No se encontró la empresa
}

// Función para responder en formato JSON
function responderJson($success, $message, $data = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function actualizarEmpresa($data)
{
    global $firebaseProjectId, $firebaseApiKey;

    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";

    $documentoId = $data['idDocumento'];

    // Construir la URL del documento encontrado para actualizarlo
    $urlActualizar = "$urlBase/EMPRESAS/$documentoId?key=$firebaseApiKey";


    $fieldsToSave = [
        'id' => ['stringValue' => $data['id']],
        'noEmpresa' => ['integerValue' => $data['noEmpresa']],
        'razonSocial' => ['stringValue' => $data['razonSocial']],
        'rfc' => ['stringValue' => $data['rfc']],
        'regimenFiscal' => ['stringValue' => $data['regimenFiscal']],
        'calle' => ['stringValue' => $data['calle']],
        'numExterior' => ['stringValue' => $data['numExterior']],
        'numInterior' => ['stringValue' => $data['numInterior']],
        'entreCalle' => ['stringValue' => $data['entreCalle']],
        'colonia' => ['stringValue' => $data['colonia']],
        'referencia' => ['stringValue' => $data['referencia']],
        'pais' => ['stringValue' => $data['pais']],
        'estado' => ['stringValue' => $data['estado']],
        'municipio' => ['stringValue' => $data['municipio']],
        'codigoPostal' => ['stringValue' => $data['codigoPostal']],
        'poblacion' => ['stringValue' => $data['poblacion']]
    ];

    // Construir el payload en formato JSON
    $payload = json_encode(['fields' => $fieldsToSave]);

    // Configurar las opciones de la solicitud HTTP
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'PATCH', // PATCH para actualizar el documento existente
            'content' => $payload
        ]
    ];

    // Crear el contexto de la solicitud
    $context  = stream_context_create($options);

    try {
        $responseActualizar = file_get_contents($urlActualizar, false, $context);
        if ($responseActualizar === false) {
            throw new Exception('Error al conectar con Firestore para actualizar el documento.');
        }

        // Actualiza los datos en la sesión después de guardar en Firebase
        $_SESSION['empresa'] = [
            'id' => $data['id'],
            'noEmpresa' => $data['noEmpresa'],
            'razonSocial' => $data['razonSocial'],
            'claveUsuario' => $_SESSION['empresa']['claveUsuario'],
            'claveSae' => $_SESSION['empresa']['claveSae'],
            'contrasena' => $_SESSION['empresa']['contrasena'],
        ];

        echo json_encode(['success' => true, 'message' => 'Documento actualizado correctamente y sesión de empresa actualizada.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Función para guardar o actualizar empresa
function guardarEmpresa($data)
{
    global $firebaseProjectId, $firebaseApiKey;

    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    $fieldsToSave = [
        'id' => ['integerValue' => $data['id']],
        'noEmpresa' => ['integerValue' => $data['noEmpresa']],
        'razonSocial' => ['stringValue' => $data['razonSocial']],
        'rfc' => ['stringValue' => $data['rfc']],
        'regimenFiscal' => ['stringValue' => $data['regimenFiscal']],
        'calle' => ['stringValue' => $data['calle']],
        'numExterior' => ['stringValue' => $data['numExterior']],
        'numInterior' => ['stringValue' => $data['numInterior']],
        'entreCalle' => ['stringValue' => $data['entreCalle']],
        'colonia' => ['stringValue' => $data['colonia']],
        'referencia' => ['stringValue' => $data['referencia']],
        'pais' => ['stringValue' => $data['pais']],
        'estado' => ['stringValue' => $data['estado']],
        'municipio' => ['stringValue' => $data['municipio']],
        'codigoPostal' => ['stringValue' => $data['codigoPostal']],
        'poblacion' => ['stringValue' => $data['poblacion']]
    ];

    // Construir el payload en formato JSON
    $payload = json_encode(['fields' => $fieldsToSave]);

    // Configurar las opciones de la solicitud HTTP
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload
        ]
    ];

    // Crear el contexto de la solicitud
    $context  = stream_context_create($options);

    try {
        $response = file_get_contents($urlBase, false, $context);
        if ($response === false) {
            throw new Exception('Error al conectar con Firestore para guardar el documento.');
        }
        echo json_encode(['success' => true, 'message' => 'Documento guardado correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
function probarCsd($keyPass, $baseDir)
{
    //$fechaActual = date();
    $cerFile    = $baseDir . basename($_FILES['cerFile']['name']);
    $keyFile    = $baseDir . basename($_FILES['permFile']['name']);
    $passPhrase = $keyPass;

    try {
        // Abrimos los archivos .cer y .key
        $credential  = Credential::openFiles($cerFile, $keyFile, $passPhrase);
        $certificate = $credential->certificate();

        // Obtenemos fechas de vigencia como DateTimeImmutable
        /*$notBefore = $certificate->notBefore(); // inicio de vigencia
        $notAfter  = $certificate->notAfter();  // fin de vigencia
        $now       = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        // ¿Está actualmente vigente?
        $isValid = ($notBefore <= $now && $notAfter >= $now);*/

        return true;
    } catch (\Exception $e) {
        // Cualquier error (contraseña, archivos mal formados, etc.)
        return false;
    }
}
function guardarDatosFactura($data)
{
    global $firebaseProjectId, $firebaseApiKey;
    $idDocumento = $data['idDocumento'];
    // 1) Cifrar la contraseña
    $enc = encryptValue($data['keyPassword']);
    // $enc = ['value' => '<base64-ciphertext>', 'iv' => '<base64-iv>']

    // 2) Construir payload solo con los dos campos que queremos crear/actualizar
    $fields = [
        'keyEncValue' => ['stringValue' => $enc['value']],
        'keyEncIv'    => ['stringValue' => $enc['iv']]
    ];
    $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_SLASHES);

    // 3) PATCH con updateMask para que Firestore solo toque esos dos campos
    $docPath = "projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS/$idDocumento";
    $url = "https://firestore.googleapis.com/v1/$docPath"
        . "?updateMask.fieldPaths=keyEncValue"
        . "&updateMask.fieldPaths=keyEncIv"
        . "&key=$firebaseApiKey";

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 10
        ]
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        $err = error_get_last();
        throw new \RuntimeException("Firebase API error: " . ($err['message'] ?? 'unknown'));
    }

    $obj = json_decode($resp, true);
    return [
        'success'      => true,
        'documentName' => $obj['name'] ?? null
    ];
}
function sesionEmpresa($data)
{
    // Guardar la empresa en la sesión
    $_SESSION['empresa'] = [
        'id' => $data['id'],
        'noEmpresa' => $data['noEmpresa'],
        'razonSocial' => $data['razonSocial'],
        'claveSae' => $data['claveSae']
    ];

    //header('Content-Type: application/json');

    // Responder al cliente con los datos guardados
    echo json_encode([
        'success' => true,
        'message' => 'Sesión de empresa guardada correctamente.',
        'data' => $_SESSION['empresa']
    ]);
}
// Función para eliminar empresa
function eliminarEmpresa()
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'DELETE',
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response !== false) {
        echo json_encode(['success' => true, 'message' => 'Empresa eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la empresa.']);
    }
}

// Función para obtener datos de la empresa
function listaEmpresas($nombreUsuario)
{
    global $firebaseProjectId, $firebaseApiKey;
    $urlEmpUs = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";
    $responseEmpUs = file_get_contents($urlEmpUs);
    if ($responseEmpUs !== false) {
        $dataEmpUs = json_decode($responseEmpUs, true);
        if (isset($dataEmpUs['documents'])) {
            $empresas = [];
            foreach ($dataEmpUs['documents'] as $document) {
                $fields = $document['fields'];
                // Verificar que el usuario coincida
                if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $nombreUsuario) {
                    $empresas[] = [
                        'id' => isset($fields['id']['integerValue']) ? $fields['id']['integerValue'] : "N/A", // Validar id
                        'noEmpresa' => isset($fields['noEmpresa']['integerValue']) ? $fields['noEmpresa']['integerValue'] : "No especificado", // Validar noEmpresa
                        'razonSocial' => isset($fields['empresa']['stringValue']) ? $fields['empresa']['stringValue'] : "Sin Razón Social", // Validar razonSocial
                        'claveUsuario' => isset($fields['claveUsuario']['stringValue']) ? $fields['claveUsuario']['stringValue'] : "Usuario sin Clave",
                        'claveSae' => isset($fields['claveSae']['stringValue']) ? $fields['claveSae']['stringValue'] : "Usuario sin base asociada",
                        'contrasena' => isset($fields['contrasena']['stringValue']) ? $fields['contrasena']['stringValue'] : ""
                    ];
                }
            }

            if (count($empresas) > 0) {
                // Ordenar por razón social si es necesario
                usort($empresas, function ($a, $b) {
                    return strcmp($a['noEmpresa'], $b['noEmpresa']);
                });
                $_SESSION['empresaSelect'] = [
                    'usuario' => $nombreUsuario,
                    'empresas' => $empresas
                ];
                echo json_encode(['success' => true, 'data' => $empresas]);
            } else {
                echo json_encode(['success' => false, 'message' => 'El usuario no tiene empresas asociadas.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron relaciones de empresas para este usuario.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las relaciones de empresas del usuario.']);
    }
}
function encryptValue(string $plaintext): array
{
    $key = FIREBASE_CRYPT_KEY;
    $ivLen = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($ivLen);
    $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return [
        'iv' => base64_encode($iv),
        'value' => base64_encode($ciphertext)
    ];
}

function decryptValue(string $b64Cipher, string $b64Iv): string
{
    $key = FIREBASE_CRYPT_KEY;
    $iv = base64_decode($b64Iv);
    $cipher = base64_decode($b64Cipher);
    return openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}
