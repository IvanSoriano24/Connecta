<?php
require 'firebase.php';

// Desactiva errores fatales visibles en producción
/*error_reporting(0)
ini_set('display_errors', 0);*/

function probarConexionSQLServer($host, $usuario, $password, $nombreBase)
{
    try {
        $dsn = "sqlsrv:Server=$host;Database=$nombreBase;TrustServerCertificate=true";
        $conn = new PDO($dsn, $usuario, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['success' => true, 'message' => 'Conexión exitosa'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
    }
}

function guardarConexion($data, $firebaseProjectId, $firebaseApiKey, $idDocumento){
    // Si el idDocumento es nulo, creamos un nuevo documento
    if ($idDocumento === null) {
        // URL para crear un nuevo documento
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
        $payload = [
            'fields' => [
                'host' => ['stringValue' => $data['host']],
                'puerto' => ['stringValue' => $data['puerto']],
                'usuario' => ['stringValue' => $data['usuarioSae']],
                'password' => ['stringValue' => $data['password']],
                'nombreBase' => ['stringValue' => $data['nombreBase']],
                'noEmpresa' => ['stringValue' => $data['noEmpresa']],
            ],
        ];
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($payload),
            ],
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            return ['success' => false, 'message' => 'Error al guardar en Firebase'];
        }
        return ['success' => true, 'message' => 'Datos guardados exitosamente en Firebase', 'firebaseResponse' => json_decode($result, true)];
    } else {
        // Si el idDocumento no es nulo, buscamos si existe ese documento para actualizarlo
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES/$idDocumento?key=$firebaseApiKey";
        // Hacemos la solicitud GET para obtener el documento
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n"
            ]
        ]);
        $result = file_get_contents($url, false, $context);
        // Verifica si la respuesta es válida
        if ($result === FALSE) {
            return ['success' => false, 'message' => 'Error al obtener el documento de Firebase'];
        }
        $document = json_decode($result, true);
        // Si el documento existe, actualizamos los datos
        if (isset($document['name'])) {
            // Preparamos los datos de actualización
            $payload = [
                'fields' => [
                    'host' => ['stringValue' => $data['host']],
                    'puerto' => ['stringValue' => $data['puerto']],
                    'usuario' => ['stringValue' => $data['usuarioSae']],
                    'password' => ['stringValue' => $data['password']],
                    'nombreBase' => ['stringValue' => $data['nombreBase']],
                    'noEmpresa' => ['stringValue' => $data['noEmpresa']],
                ],
            ];
            // Hacemos la solicitud PATCH para actualizar el documento
            $options = [
                'http' => [
                    'header' => "Content-Type: application/json\r\n",
                    'method' => 'PATCH',
                    'content' => json_encode($payload),
                ],
            ];
            $context = stream_context_create($options);
            $updateResult = file_get_contents($url, false, $context);
            // Si la actualización falla
            if ($updateResult === FALSE) {
                return ['success' => false, 'message' => 'Error al actualizar el documento en Firebase'];
            }
            return ['success' => true, 'message' => 'Documento actualizado exitosamente'];
        } else {
            return ['success' => false, 'message' => 'No se encontró el documento con el ID especificado'];
        }
    }
}

function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey) {
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
        if ($fields['noEmpresa']['stringValue'] === $noEmpresa) {
            // Extrae solo el ID del documento desde la URL
            $documentId = basename($document['name']);  // Esto da solo el ID del documento

            return [
                'success' => true,
                'data' => [
                    'id' => $documentId, // Solo el ID del documento
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'], 
                    'usuarioSae' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue']
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        echo json_encode(['success' => false, 'message' => 'Datos no válidos en la solicitud']);
        exit;
    }
    $action = $input['action'];
    switch ($action) {
        case 'probar':
            $data = [
                'host' => $input['host'],
                'puerto' => $input['puerto'],
                'usuarioSae' => $input['usuarioSae'],
                'password' => $input['password'],
                'nombreBase' => $input['nombreBase']
            ];
            $resultadoConexion = probarConexionSQLServer($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase']);
            echo json_encode($resultadoConexion);
            break;

        case 'guardar':
            $data = [
                'host' => $input['host'],
                'puerto' => $input['puerto'],
                'usuarioSae' => $input['usuarioSae'],
                'password' => $input['password'],
                'nombreBase' => $input['nombreBase'],
                'noEmpresa' => $input['noEmpresa']
            ];
            $idDocumento = $input['idDocumento'];
            $resultadoConexion = probarConexionSQLServer($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase']);
            if ($resultadoConexion['success']) {
                $resultadoGuardar = guardarConexion($data, $firebaseProjectId, $firebaseApiKey, $idDocumento);
                echo json_encode($resultadoGuardar);
            } else {
                echo json_encode(['success' => false, 'message' => $resultadoConexion['message']]);
            }
            break;

        case 'mostrar':
            $noEmpresa = $input['noEmpresa'];
            $resultado = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
            echo json_encode($resultado);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
            break;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no soportado']);
}
?>