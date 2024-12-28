<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';

function altaPedido($data){
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/MONTOPEDIDO?key=$firebaseApiKey";
    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'PATCH', // PATCH actualiza o agrega campos
            'content' => json_encode($data),
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response !== false) {
        echo json_encode(['success' => true, 'message' => 'Empresa guardada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la empresa.']);
    }
}

function obtenerPedidos() {
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS?key=$firebaseApiKey";

    $response = file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        $pedidos = [];
        if (isset($data['documents'])) {
            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $pedidos[] = [
                    'id' => str_replace('projects/' . $firebaseProjectId . '/databases/(default)/documents/PEDIDOS/', '', $document['name']),
                    'cliente' => $fields['cliente']['stringValue'] ?? '',
                    'total' => $fields['total']['stringValue'] ?? '',
                    'fecha' => $fields['fecha']['stringValue'] ?? ''
                ];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $pedidos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los pedidos.']);
    }
}


function actualizarPedido($idPedido, $data) {
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/MONTOPEDIDO/$idPedido?key=$firebaseApiKey";

    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'PATCH',
            'content' => json_encode($data),
        ]
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);  // Usamos el "@" para evitar el warning si hay error
    if ($response !== false) {
        echo json_encode(['success' => true, 'message' => 'Pedido actualizado correctamente.']);
    } else {
        // Si la respuesta es false, significa que hubo un error al realizar la solicitud
        $error = error_get_last(); // Obtener el último error ocurrido
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el pedido.',
            'error' => $error
        ]);
    }
}

function obtenerPedido($idPedido) {
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS/$idPedido?key=$firebaseApiKey";
    $response = file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);

        // Verificar si se encontró el pedido
        if (isset($data['fields'])) {
            $pedido = $data['fields'];
            $pedido['id'] = $idPedido; // Asignamos el idPedido manualmente
            return $pedido;
        } else {
            return null; // Si no se encuentra el pedido
        }
    } else {
        return null; // Si hubo un error en la petición
    }
}

//http://localhost/MDConnecta/Servidor/PHP/pedido.php?numFuncion=5?idPedido=FYOcALZA6k4v2UpXv6Ln

// Verificar si la solicitud es POST o GET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    // Si es una solicitud POST, asignamos el valor de numFuncion
    $funcion = $_POST['numFuncion'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    // Si es una solicitud GET, asignamos el valor de numFuncion
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la peticion.']);
    //break;
}
switch ($funcion) {
    case 1: 
        $data = [
            /*
            DATOS
            'dato' => $fields['dato']['tipoDato'],
            */
        ];
        altaPedido($data);
        break;

    case 2: // Editar pedido
        $idPedido = $_POST['idPedido'];
        $data = [
            /*
            DATOS
            */
        ];
        actualizarPedido($idPedido, $data);
        break;

    case 3: // Cancelar pedido
        $idPedido = $_POST['idPedido'];
        $data = [
            'estado' => ['stringValue' => 'Cancelado'],
        ];
        actualizarPedido($idPedido, $data);
        break;

    case 4: // Obtener pedidos
       // $data = [
            /*
            ID del cliente
            */
        //];
        obtenerPedidos();
        break;
    
    case 5:
        $idPedido = $_GET['idPedido'];
        obtenerPedido($idPedido);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}

?>