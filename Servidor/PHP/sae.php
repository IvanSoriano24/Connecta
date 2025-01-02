<?php
require 'firebase.php';

function probarConexionSQLServer($host, $usuario, $password, $nombreBase) {
    try {
        $dsn = "sqlsrv:Server=$host;Database=$nombreBase";
        $conn = new PDO($dsn, $usuario, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['success' => true, 'message' => 'Conexión exitosa'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
    }
}

function guardarEnFirebase($data, $firebaseProjectId, $firebaseApiKey) {
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
    // Crear la carga de datos (payload)
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
    // Configuración de la solicitud HTTP
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
    return ['success' => true, 'message' => 'Datos guardados exitosamente en Firebase'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decodificar los datos JSON enviados por fetch
    $input = json_decode(file_get_contents('php://input'), true);

    $action = $input['action'];

    switch ($action) {
        case 'probar': // Probar conexión a SQL Server
            $data = [
                'host' => $input['host'],
                'puerto' => $input['puerto'],
                'usuarioSae' => $input['usuarioSae'],
                'password' => $input['password'],
                'nombreBase' => $input['nombreBase']
            ];
            $resultadoConexion = probarConexionSQLServer($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase']);
            header('Content-Type: application/json'); // Asegurar que se envíe un JSON válido
            echo json_encode($resultadoConexion);
            break;

        case 'guardar': // Guardar datos si la conexión es exitosa
            $data = [
                'host' => $input['host'],
                'puerto' => $input['puerto'],
                'usuarioSae' => $input['usuarioSae'],
                'password' => $input['password'],
                'nombreBase' => $input['nombreBase'],
                'noEmpresa' => $input['noEmpresa']
            ];
            // Probar la conexión primero
            $resultadoConexion = probarConexionSQLServer($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase']);
            if ($resultadoConexion['success']) {
                // Si la conexión es exitosa, guardar en Firebase
                $resultadoGuardar = guardarEnFirebase($data, $firebaseProjectId, $firebaseApiKey);
                echo json_encode($resultadoGuardar);
            } else {
                // Si la conexión falla, devolver el mensaje de error
                echo json_encode(['success' => false, 'message' => $resultadoConexion['message']]);
            }
            break;

        default:
            // Acción no soportada
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
            break;
    }
} else {
    // Respuesta para métodos no soportados
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no soportado']);
}
?>