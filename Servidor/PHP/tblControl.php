<?php
date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
$claveSae = "02";
global $claveSae;

session_start();

function guardarArticuloVisto($usuario, $CVE_ART)
{
    global $firebaseProjectId, $firebaseApiKey;
    $visto = 1;
    // Determinar si se trata de una creación (POST) o edición (PATCH)
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/TBLCONTROL?key=$firebaseApiKey";
    $method = "POST";
    $hora = time();
    // Formatear los datos para Firebase (estructura de "fields")
    $fields = [
        'tipo' => ['stringValue' => "Articulo"],
        "articulos" => [
                            "arrayValue" => [
                                "values" => {
                                    return [
                                        "mapValue" => [
                                            "fields" => [
                                                "clave" => ["stringValue" => $CVE_ART],
                                                "hora" => ["stringValue" => (string)$hora],
                                                "vecesVisto" => ["integerValue" => $visto],
                                            ]
                                        ]
                                    ];
                                }
                            ]
                        ],
        'usuario' => ['stringValue' => $usuario]
    ];
    ///HACER UN ARRAY PARA LOS ARTICULOS
    // Preparar la solicitud
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => $method,
            'content' => json_encode(['fields' => $fields]),
        ],
    ];
    $context = stream_context_create($options);

    // Realizar la solicitud a Firebase
    $response = @file_get_contents($url, false, $context);

    // Manejar la respuesta
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar evento en Firebase.']);
        return;
    }

    $data = json_decode($response, true);
    if (isset($data['name'])) {
        echo json_encode(['success' => true, 'message' => 'Evento guardado exitosamente.', 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el evento.']);
    }
}
function guardaTiempoPagina($usuario, $pagina, $tiempo)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Determinar si se trata de una creación (POST) o edición (PATCH)
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/TBLCONTROL?key=$firebaseApiKey";
    $method = "POST";
    $hora = time();
    // Formatear los datos para Firebase (estructura de "fields")
    $fields = [
        'tipo' => ['stringValue' => "Pagina"],
        'detalle' => ['stringValue' => "El tiempo total en la pagina $pagina fue de $tiempo"],
        'fecha' => ['stringValue' => (string)$hora],
        'usuario' => ['stringValue' => $usuario]
    ];

    // Preparar la solicitud
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => $method,
            'content' => json_encode(['fields' => $fields]),
        ],
    ];
    $context = stream_context_create($options);

    // Realizar la solicitud a Firebase
    $response = @file_get_contents($url, false, $context);

    // Manejar la respuesta
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar evento en Firebase.']);
        return;
    }

    $data = json_decode($response, true);
    if (isset($data['name'])) {
        echo json_encode(['success' => true, 'message' => 'Evento guardado exitosamente.', 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el evento.']);
    }
}


function obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae)
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
        if ($fields['claveSae']['stringValue'] === $claveSae) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}

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

        guardarBotonPresionado($usuario, $idBoton, $pagina);
        break;
    case 2:

        guardaTiempoPagina($usuario, $pagina, $tiempo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
