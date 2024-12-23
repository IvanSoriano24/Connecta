<?php
require 'firebase.php';

function probarConexion($data){
    // Verificar si los datos están llegando correctamente
    if (isset($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase'])) {
        // Imprimir los datos recibidos para asegurarse de que estén correctos
        echo "Datos recibidos: <br>";
        echo "Host: " . $data['host'] . "<br>";
        echo "Usuario: " . $data['usuarioSae'] . "<br>";
        echo "Password: " . $data['password'] . "<br>";
        echo "Nombre Base: " . $data['nombreBase'] . "<br>";
    } else {
        echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    if ($action == 'probar') {
        error_log(print_r($_POST, true));  // Esto imprimirá los datos en el log del servidor
        $data = [
            'host' => $_POST['host'],
            'usuarioSae' => $_POST['usuarioSae'],
            'password' => $_POST['password'],
            'nombreBase' => $_POST['nombreBase']
        ];
        echo json_encode(['success' => true, 'message' => 'Conexión exitosas']);
        probarConexion($data);  // Llamar a la función para obtener las empresas
    }else{
        echo json_encode(['success' => true, 'message' => 'Conexión exitosa']);
    }
}
?>