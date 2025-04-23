<?php
date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
//$claveSae = "02";
$claveSae = "01";
global $claveSae;

session_start();

function validarArticuloVisto($usuario, $CVE_ART) {
    global $firebaseProjectId, $firebaseApiKey;
    
    // URL para obtener todos los documentos de la colección TBLCONTROL
    $url = "https://firestore.googleapis.com/v1/projects/{$firebaseProjectId}/databases/(default)/documents/TBLCONTROL?key={$firebaseApiKey}";
    
    $response = @file_get_contents($url);
    if ($response === false) {
        error_log("Error al obtener los documentos de TBLCONTROL.");
        return false;
    }
    
    $responseData = json_decode($response, true);
    //var_dump($responseData['documents']);
    // Obtener la fecha de hoy
    $today = date("Y-m-d");
    
    // Recorrer cada documento
    foreach ($responseData['documents'] as $doc) {
        // $doc es cada documento; verificamos que tenga 'fields'
        if (isset($doc['fields'])) {
            $fields = $doc['fields'];
            // Verificar que el usuario coincida
            if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
                // Validar que el documento sea del día de hoy
                if (isset($fields['fecha']['stringValue']) && $fields['fecha']['stringValue'] !== $today) {
                    continue; // no es del día de hoy
                }
                // Verificar el array de artículos
                if (isset($fields['articulos']['arrayValue']['values']) && is_array($fields['articulos']['arrayValue']['values'])) {
                    foreach ($fields['articulos']['arrayValue']['values'] as $articulo) {
                        if (isset($articulo['mapValue']['fields']['clave']['stringValue']) && 
                            $articulo['mapValue']['fields']['clave']['stringValue'] === $CVE_ART) {
                            // Se encontró el registro para este artículo
                            return $doc['name']; // Retorna la ruta completa del documento
                        }
                    }
                }
            }
        }
    }
    return false;
}
function guardarArticuloVisto($usuario, $CVE_ART) {
    global $firebaseProjectId, $firebaseApiKey;
    $today = date("Y-m-d");
    $hora = time();
    $incremento = 1;
    
    // Primero, verificar si ya existe un registro para el usuario y artículo hoy
    $docName = validarArticuloVisto($usuario, $CVE_ART);
    /*var_dump($docName);
    die();*/
    if ($docName !== false) {
        // Actualización: obtener el documento existente sin updateMask (GET)
        $docUrl = "https://firestore.googleapis.com/v1/$docName?key=$firebaseApiKey";
        $existingResponse = @file_get_contents($docUrl);
        if ($existingResponse === false) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener el documento existente.']);
            die();
        }
        $docData = json_decode($existingResponse, true);
        $currentVisto = 0;
        if (isset($docData['fields']['articulos']['arrayValue']['values'][0]['mapValue']['fields']['vecesVisto']['integerValue'])) {
            $currentVisto = (int)$docData['fields']['articulos']['arrayValue']['values'][0]['mapValue']['fields']['vecesVisto']['integerValue'];
        }
        $newVisto = $currentVisto + $incremento;
        
        // Payload para actualizar (PATCH) el campo "articulos"
        $updatePayload = [
            "fields" => [
                "articulos" => [
                    "arrayValue" => [
                        "values" => [
                            [
                                "mapValue" => [
                                    "fields" => [
                                        "clave" => ["stringValue" => $CVE_ART],
                                        "ultimaHora" => ["stringValue" => (string)$hora],
                                        "vecesVisto" => ["integerValue" => $newVisto]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // Para actualizar, se usa PATCH con updateMask sobre "articulos"
        $updateUrl = "https://firestore.googleapis.com/v1/{$docName}?key={$firebaseApiKey}&updateMask.fieldPaths=articulos";
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'PATCH',
                'content' => json_encode($updatePayload)
            ]
        ];
        $context = stream_context_create($options);
        $updateResponse = @file_get_contents($updateUrl, false, $context);
        if ($updateResponse === false) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el registro en Firebase.']);
            die();
        }
        $updateData = json_decode($updateResponse, true);
        echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente.', 'data' => $updateData]);
        die();
    } else {
        // No existe: crear un nuevo documento usando POST
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/TBLCONTROL?key=$firebaseApiKey";

        $fields = [
            'tipo' => ['stringValue' => "Articulo"],
            'fecha' => ['stringValue' => $today],
            'articulos' => [
                'arrayValue' => [
                    'values' => [
                        [
                            'mapValue' => [
                                'fields' => [
                                    'clave' => ['stringValue' => $CVE_ART],
                                    'ultimaHora' => ['stringValue' => (string)$hora],
                                    'vecesVisto' => ['integerValue' => $incremento]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'usuario' => ['stringValue' => $usuario]
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
                'message' => 'Error al crear el registro en Firebase.',
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
        $usuario = $_SESSION['usuario']['usuario'];
        $CVE_ART = $_GET['CVE_ART'];
        guardarArticuloVisto($usuario, $CVE_ART);
        break;
    case 2:

        guardaTiempoPagina($usuario, $pagina, $tiempo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
