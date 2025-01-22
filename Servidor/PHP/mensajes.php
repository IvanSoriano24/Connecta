<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';

session_start();


function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey)
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
        if ($fields['noEmpresa']['stringValue'] === $noEmpresa) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue']
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}

function comandas($firebaseProjectId, $firebaseApiKey)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos. Verifica la URL o las reglas de seguridad de Firebase.']);
    } else {
        $data = json_decode($response, true);
        $comandas = [];
        if (isset($data['documents'])) {
            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $fechaHora = isset($fields['fechaHoraElaboracion']['stringValue']) ? explode(' ', $fields['fechaHoraElaboracion']['stringValue']) : ['', ''];
                $fecha = $fechaHora[0]; // Fecha
                $hora = $fechaHora[1]; // Hora

                $comandas[] = [
                    'id' => basename($document['name']),
                    'noPedido' => $fields['folio']['stringValue'],
                    'nombreCliente' => $fields['nombreCliente']['stringValue'],
                    'status' => $fields['status']['stringValue'],
                    'fecha' => $fecha,
                    'hora' => $hora
                ];
            }
        }
        echo json_encode(['success' => true, 'data' => $comandas]);
    }
}
function obtenerDetallesComanda($firebaseProjectId, $firebaseApiKey, $comandaId)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los detalles de la comanda.']);
    } else {
        $data = json_decode($response, true);
        $fields = $data['fields'];
        $productos = [];
        foreach ($fields['productos']['arrayValue']['values'] as $producto) {
            $productos[] = [
                'cantidad' => $producto['mapValue']['fields']['cantidad']['integerValue'],
                'clave' => $producto['mapValue']['fields']['clave']['stringValue'],
                'descripcion' => $producto['mapValue']['fields']['descripcion']['stringValue']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $comandaId,
                'noPedido' => $fields['folio']['stringValue'],
                'nombreCliente' => $fields['nombreCliente']['stringValue'],
                'status' => $fields['status']['stringValue'],
                'fecha' => explode(' ', $fields['fechaHoraElaboracion']['stringValue'])[0],
                'hora' => explode(' ', $fields['fechaHoraElaboracion']['stringValue'])[1],
                'productos' => $productos
            ]
        ]);
    }
}
function marcarComandaTerminada($firebaseProjectId, $firebaseApiKey, $comandaId)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey";
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'TERMINADA']
        ]
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al marcar la comanda como TERMINADA.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Comanda marcada como TERMINADA.']);
    }
}

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
    case 1:
        comandas($firebaseProjectId, $firebaseApiKey);
        break;
    case 2:
        $comandaId = $_GET['comandaId'];
        obtenerDetallesComanda($firebaseProjectId, $firebaseApiKey, $comandaId);
        break;
    case 3:
        marcarComandaTerminada($firebaseProjectId, $firebaseApiKey, $comandaId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
