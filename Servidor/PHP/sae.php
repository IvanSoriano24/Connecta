<?php
require 'firebase.php';

function probarConexion($data){
    // Verificar si los datos están llegando correctamente
    if (isset($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase'])) {
        // Conectar a la base de datos (simulado aquí)
        $conexionExitosa = true; // Esto es solo para ejemplo; aquí pondrías tu lógica real.

        if ($conexionExitosa) {
            // Si la conexión es exitosa
            return ['success' => true, 'message' => 'Conexión exitosa'];
        } else {
            // Si falla la conexión
            return ['success' => false, 'message' => 'No se pudo conectar a la base de datos'];
        }
    } else {
        return ['success' => false, 'message' => 'Faltan datos'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decodificar los datos JSON enviados por fetch
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'probar') {
        $data = [
            'host' => $input['host'],
            'usuarioSae' => $input['usuarioSae'],
            'password' => $input['password'],
            'nombreBase' => $input['nombreBase']
        ];

        // Probar la conexión y devolver el resultado en JSON
        $result = probarConexion($data);
        header('Content-Type: application/json'); // Asegurar que se envíe un JSON válido
        echo json_encode($result);
    } else {
        // Respuesta para acciones no válidas
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} else {
    // Respuesta para métodos no soportados
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no soportado']);
}
?>