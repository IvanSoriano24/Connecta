<?php
require 'firebase.php';
session_start();

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
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
            // Extrae solo el ID del documento desde la URL
            $documentId = basename($document['name']);  // Esto da solo el ID del documento

            return [
                'success' => true,
                'data' => [
                    'id' => $documentId, // Solo el ID del documento
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'nombreBanco' => $fields['nombreBanco']['stringValue'],
                    'claveSae' => $fields['claveSae']['stringValue'],

                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}

function obtenerCamposUsados(){
    global $firebaseProjectId, $firebaseApiKey;

    $url = "https://firestore.googleapis.com/v1/projects/"
         . "$firebaseProjectId/databases/(default)/documents/PA"
         . "RAMTETROS?key=$firebaseApiKey";
    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success'=>false,'message'=>'Error al obtener los campos.']);
        return;
    }
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success'=>false,'message'=>'No se encontraron documentos.']);
        return;
    }

    $resultado = [];
    foreach ($data['documents'] as $doc) {
        $fields = $doc['fields'];
        // cada clave de $fields (excepto "noEmpresa") es el nombre de la tabla
        foreach ($fields as $tabla => $entry) {
            if ($tabla === 'noEmpresa') continue;
            // si este campo trae un arrayValue ⇒ recorremos sus valores
            if (isset($entry['arrayValue']['values'])) {
                foreach ($entry['arrayValue']['values'] as $elem) {
                    $f = $elem['mapValue']['fields'];
                    $resultado[] = [
                        'tabla'       => $tabla,
                        'campo'       => $f['campo']['stringValue']     ?? '',
                        'descripcion' => $f['descripcion']['stringValue'] ?? '',
                    ];
                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success'=>true,'data'=>$resultado]);
    exit;
}
function obtenerCamposLibres($conexionData)
//function obtenerCamposUsados($conexionData)
{
    $serverName = $conexionData['host'];
    $Database = $conexionData['nombreBase'];
    $UID = $conexionData['usuario'];
    $PWD = $conexionData['password'];


    $dsn = "sqlsrv:Server=$serverName;Database=$Database;TrustServerCertificate=true";
    $conn = new PDO($dsn, $UID, $PWD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME LIKE '%CLIB%'";

    $stmt = $conn->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    usort($tables, function ($a, $b) {
        return strcmp($a['TABLE_NAME'], $b['TABLE_NAME']);
    });

    // Retornamos los usuarios como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $tables]);
}
function obtenerCampos($conexionData, $tabla)
{
    $serverName = $conexionData['host'];
    $database   = $conexionData['nombreBase'];
    $uid        = $conexionData['usuario'];
    $pwd        = $conexionData['password'];

    // Conectamos con PDO
    $dsn = "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true";
    $conn = new PDO($dsn, $uid, $pwd, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Preparamos la consulta con parámetro
    $sql = "
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = :tabla
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':tabla', $tabla, PDO::PARAM_STR);
    $stmt->execute();

    $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //var_dump($campos);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data'    => $campos
    ]);
}

function guardarCamposLibres($noEmpresa, $tabla, $campo, $descripcion, $id, $firebaseProjectId, $firebaseApiKey)
{
    if (empty($id) || $id == null) {
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PARAMTETROS?key=$firebaseApiKey";
        $fields = [
            'noEmpresa' => ['integerValue' => $noEmpresa],
            $tabla => [
                'arrayValue' => [
                    'values' => [
                        [
                            'mapValue' => [
                                'fields' => [
                                    'campo' => ['stringValue' => $campo],
                                    'descripcion' => ['stringValue' => $descripcion]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode(['fields' => $fields])
            ]
        ];

        $context = stream_context_create($options);

        $createResponse = @file_get_contents($url, false, $context);
        if ($createResponse === false) {
            $error = error_get_last();
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear el registro.',
                'error' => $error // Esto mostrará todo el array del error
            ]);
            die();
        }
        $createData = json_decode($createResponse, true);
        if (isset($createData['name'])) {
            //return json_encode(['success' => true, 'message' => 'Nuevo registro creado exitosamente.', 'data' => $createData]);
            echo json_encode(['success' => true, 'message' => 'Nuevo registro creado exitosamente.']);
            die();
        } else {
            //var_dump("No se creo");
            //return json_encode(['success' => false, 'message' => 'No se pudo crear el registro.']);
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el registro.']);
            die();
        }
    } else {
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PARAMTETROS&updateMask.fieldPaths=$tabla&key=$firebaseApiKey";
        $fields = [
            'noEmpresa' => ['stringValue' => $noEmpresa],
            $tabla => [
                'arrayValue' => [
                    'values' => [
                        [
                            'mapValue' => [
                                'fields' => [
                                    'campo' => ['stringValue' => $campo],
                                    'descripcion' => ['stringValue' => $descripcion]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'PATCH',
                'content' => json_encode($fields)
            ]
        ];
        $context = stream_context_create($options);
        $updateResponse = @file_get_contents($url, false, $context);
        if ($updateResponse === false) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el registro en Firebase.']);
            die();
        }
        $updateData = json_decode($updateResponse, true);
        echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente.', 'data' => $updateData]);
        die();
    }
}
function verificarCampoFirebase($campo, $tabla){
    global $firebaseProjectId, $firebaseApiKey;

    $url = "https://firestore.googleapis.com/v1/projects/"
         . "$firebaseProjectId/databases/(default)/documents/PA"
         . "RAMTETROS?key=$firebaseApiKey";
    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success'=>false,'message'=>'Error al obtener los campos.']);
        return;
    }
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success'=>false,'message'=>'No se encontraron documentos.']);
        return;
    }

    $resultado = [];
    foreach ($data['documents'] as $doc) {
        $fields = $doc['fields'];
        // cada clave de $fields (excepto "noEmpresa") es el nombre de la tabla
        foreach ($fields as $tabla => $entry) {
            if ($tabla === 'noEmpresa') continue;
            // si este campo trae un arrayValue ⇒ recorremos sus valores
            if (isset($entry['arrayValue']['values'])) {
                foreach ($entry['arrayValue']['values'] as $elem) {
                    $f = $elem['mapValue']['fields'];
                    if($f['campo']['stringValue'] == $campo){
                        echo json_encode(['success' => true, 'exists' => true]);
                        return;
                    }
                }
            }
        }
    }
    echo json_encode(['success' => true, 'exists' => false]); // Cliente no existe
}

/***********************************************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    $funcion = $_POST['numFuncion'];
    // Asegúrate de recibir los datos en JSON y decodificarlos correctamente
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la petición.']);
    exit();
}
/***********************************************************************************************************/
switch ($funcion) {
    case 1:
        obtenerCamposUsados();
        break;
    case 2:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        obtenerCamposLibres($conexionData);
        break;
    case 3:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $tabla = $_GET['tabla'];
        obtenerCampos($conexionData, $tabla);
        break;
    case 4:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $tabla = $_POST['tabla'];
        $campo = $_POST['campo'];
        $descripcion = $_POST['descripcion'];
        $id = $_POST['id'] ?? null;
        guardarCamposLibres($noEmpresa, $tabla, $campo, $descripcion, $id, $firebaseProjectId, $firebaseApiKey);
        break;
        case 5:
        $campo = $_POST['campo'];
        $tabla = $_POST['tabla'];
        verificarCampoFirebase($campo, $tabla);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        break;
}
