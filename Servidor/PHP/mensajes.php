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

function comandas($firebaseProjectId, $firebaseApiKey, $filtroStatus){
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos.']);
    } else {
        $data = json_decode($response, true);
        $comandas = [];

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $status = $fields['status']['stringValue'];

                // Aplicar el filtro de estado si está definido
                if ($filtroStatus === '' || $status === $filtroStatus) {
                    $fechaHora = isset($fields['fechaHoraElaboracion']['stringValue']) ? explode(' ', $fields['fechaHoraElaboracion']['stringValue']) : ['', ''];
                    $fecha = $fechaHora[0];
                    $hora = $fechaHora[1];

                    $comandas[] = [
                        'id' => basename($document['name']),
                        'noPedido' => $fields['folio']['stringValue'],
                        'nombreCliente' => $fields['nombreCliente']['stringValue'],
                        'status' => $status,
                        'fecha' => $fecha,
                        'hora' => $hora
                    ];
                }
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
function marcarComandaTerminada($firebaseProjectId, $firebaseApiKey, $comandaId, $numGuia, $enviarHoy) {
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey";

    // Obtener la fecha de envío
    $fechaEnvio = $enviarHoy ? date('Y-m-d') : date('Y-m-d', strtotime('+1 day')); // Hoy o mañana

    // Datos de actualización en Firebase
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'TERMINADA'],
            'fechaEnvio' => ['stringValue' => $fechaEnvio], // Agregar fecha de envío
            'numGuia' => ['stringValue' => $numGuia] // Guardar número de guía
        ]
    ];

    // Agregar `updateMask` para actualizar solo los campos indicados
    $url .= '&updateMask.fieldPaths=status&updateMask.fieldPaths=fechaEnvio&updateMask.fieldPaths=numGuia';

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Error al marcar la comanda como TERMINADA.', 'error' => $error['message']]);
    } else {
        $result = json_decode($response, true);
        echo json_encode(['success' => true, 'message' => 'Comanda marcada como TERMINADA.', 'response' => $result]);
    }
}

function notificaciones($firebaseProjectId, $firebaseApiKey){
    $nuevosMensajes = 0;
    $nuevasComandas = 0;

    // Verificar mensajes nuevos en Firebase
    $mensajesUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/MENSAJES?key=$firebaseApiKey";
    $mensajesResponse = @file_get_contents($mensajesUrl);
    if ($mensajesResponse !== false) {
        $mensajesData = json_decode($mensajesResponse, true);
        foreach ($mensajesData['documents'] as $document) {
            $fields = $document['fields'];
            if ($fields['estado']['stringValue'] === 'Pendiente') {
                $nuevosMensajes++;
            }
        }
    }

    // Verificar comandas pendientes en Firebase
    $comandasUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";
    $comandasResponse = @file_get_contents($comandasUrl);
    if ($comandasResponse !== false) {
        $comandasData = json_decode($comandasResponse, true);
        foreach ($comandasData['documents'] as $document) {
            $fields = $document['fields'];
            if ($fields['status']['stringValue'] === 'Pendiente') {
                $nuevasComandas++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'nuevosMensajes' => $nuevosMensajes,
            'nuevasComandas' => $nuevasComandas
        ]
    ]);
    exit;
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
        $filtroStatus = $_GET['status'] ?? ''; // Obtener el filtro desde la solicitud
        comandas($firebaseProjectId, $firebaseApiKey, $filtroStatus);
        break;
    case 2:
        $comandaId = $_GET['comandaId'];
        obtenerDetallesComanda($firebaseProjectId, $firebaseApiKey, $comandaId);
        break;
    case 3:
        $comandaId = $_POST['comandaId'];
        $numGuia = trim($_POST['numGuia']);
        $enviarHoy = filter_var($_POST['enviarHoy'], FILTER_VALIDATE_BOOLEAN);
        // Validar que el Número de Guía no esté vacío
        if (empty($numGuia)) {
            echo json_encode(['success' => false, 'message' => 'El Número de Guía debe contener exactamente 9 dígitos numéricos.']);
            exit;
        }
        marcarComandaTerminada($firebaseProjectId, $firebaseApiKey, $comandaId, $numGuia, $enviarHoy);
        break;
        case 4:
            notificaciones($firebaseProjectId, $firebaseApiKey);
            break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
