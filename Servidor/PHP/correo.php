<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require 'firebase.php';


function obtenerCorreos()
{
    global $firebaseProjectId, $firebaseApiKey;

    // URL para obtener los correos desde Firebase
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CORREOS?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los correos.']);
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron correos.']);
        return;
    }

    // Procesamos los datos obtenidos
    $correos = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];

        $correos[] = [
            'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/CORREOS/", '', $document['name']),
            'correo' => $fields['correo']['stringValue'] ?? '',
            'contraseña' => $fields['contraseña']['stringValue'] ?? '',
        ];
    }

    // Ordenamos alfabéticamente por correo (puedes cambiarlo si prefieres otro criterio)
    usort($correos, function ($a, $b) {
        return strcmp($a['correo'], $b['correo']);
    });

    // Retornamos los correos como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $correos]);
    exit();
}
function obtenerCorreoEspecifico($documentId){
    global $firebaseProjectId, $firebaseApiKey;

    if (!$documentId) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID del correo.']);
        exit();
    }

    // URL para obtener el documento específico de la colección CORREOS
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CORREOS/$documentId?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener el correo.']);
        exit();
    }

    $document = json_decode($response, true);

    // Verificamos que se hayan recibido los campos del documento
    if (!isset($document['fields'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontró el correo.']);
        exit();
    }

    $fields = $document['fields'];

    // Procesamos los datos recibidos
    $correo = [
        'id'          => $documentId,
        'correo'      => $fields['correo']['stringValue'] ?? '',
        'contrasena'  => $fields['contrasena']['stringValue'] ?? '',
        'claveUsuario'  => $fields['claveUsuario']['stringValue'] ?? '',
        'usuario'  => $fields['usuario']['stringValue'] ?? '',
    ];

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $correo]);
    exit();
}
function editarCorreo($correo, $contrasena, $documentId) {
    global $firebaseProjectId, $firebaseApiKey;

    if (!$documentId) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID del documento.']);
        return;
    }

    // Preparamos los campos a actualizar sin modificar los que no se envían
    $fields = [];
    if (!empty($correo)) {
        $fields['correo'] = ['stringValue' => $correo];
    }
    if (!empty($contrasena)) {
        $fields['contrasena'] = ['stringValue' => $contrasena];
    }

    if (empty($fields)) {
        echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar.']);
        return;
    }

    // Construir la cadena updateMask de forma fija, sin usar arrays intermedios
    if (!empty($correo) && !empty($contrasena)) {
        $updateMaskParam = "updateMask.fieldPaths=correo&updateMask.fieldPaths=" . urlencode("contrasena");
    } elseif (!empty($correo)) {
        $updateMaskParam = "updateMask.fieldPaths=correo";
    } elseif (!empty($contrasena)) {
        $updateMaskParam = "updateMask.fieldPaths=" . urlencode("contrasena");
    }

    // Construir la URL de actualización similar a la función activarUsuario
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CORREOS/$documentId?" 
           . $updateMaskParam . "&key=$firebaseApiKey";

    $body = json_encode(['fields' => $fields]);

    $options = [
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $body,
        ],
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el correo. ' . $error['message']
        ]);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'Correo actualizado correctamente.']);
}
function eliminarCorreo($documentId)
{
    global $firebaseProjectId, $firebaseApiKey;

    if (!$documentId) {
        return ['success' => false, 'message' => 'Falta el ID del documento para eliminar el correo.'];
    }

    // Construir la URL para eliminar el documento
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CORREOS/$documentId?key=$firebaseApiKey";

    // Configuración para la solicitud DELETE a Firebase
    $options = [
        'http' => [
            'method' => 'DELETE',
            'header' => "Content-Type: application/json\r\n",
        ],
    ];

    // Realizamos la solicitud a Firebase
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        return ['success' => false, 'message' => 'Error al eliminar el correo.'];
    }

    return ['success' => true, 'message' => 'Correo eliminado correctamente.'];
}
// Función para guardar correo en Firebase
function guardarCorreo($correo, $contraseña, $claveUsuario, $usuario)
{
    // Configuración de Firebase
    global $firebaseProjectId, $firebaseApiKey;

    $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CORREOS?key=$firebaseApiKey";

    // Estructura de los datos a enviar a Firebase
    $requestData = [
        "fields" => [
            "correo" => ["stringValue" => $correo],
            "contrasena" => ["stringValue" => $contraseña],
            "claveUsuario" => ["stringValue" => $claveUsuario],
            "usuario" => ["stringValue" => $usuario],
        ]
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/json\r\n",
            'content' => json_encode($requestData),
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($firebaseUrl, false, $context);

    // Verificar si la solicitud fue exitosa
    return $response !== FALSE;
}
function actualizarEmpUss($correo, $contraseña, $claveUsuario, $usuario)
{
    global $firebaseProjectId, $firebaseApiKey;

    // 1. Consulta para buscar el documento en EMP_USS que coincida con claveUsuario y usuario.
    $queryUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Construimos la consulta estructurada.
    $query = [
        "structuredQuery" => [
            "from" => [
                ["collectionId" => "EMP_USS"]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "claveUsuario"],
                                "op" => "EQUAL",
                                "value" => ["stringValue" => $claveUsuario]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "usuario"],
                                "op" => "EQUAL",
                                "value" => ["stringValue" => $usuario]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($query)
        ]
    ];

    $context  = stream_context_create($options);
    $queryResponse = file_get_contents($queryUrl, false, $context);

    if ($queryResponse === FALSE) {
        return false;
    }

    // La respuesta es una secuencia de resultados (en JSON) donde buscamos el documento.
    $results = json_decode($queryResponse, true);
    $documentName = null;
    foreach ($results as $result) {
        if (isset($result['document'])) {
            $documentName = $result['document']['name'];
            break;
        }
    }

    // Si no se encontró el documento, se retorna error.
    if (!$documentName) {
        return false;
    }

    // 2. Extraemos el ID del documento.
    // El formato del nombre es: projects/{projectId}/databases/(default)/documents/EMP_USS/{docId}
    $parts = explode('/', $documentName);
    $docId = end($parts);

    // 3. Actualizamos el documento en EMP_USS usando el ID obtenido.
    $updateUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS/$docId?updateMask.fieldPaths=correo&updateMask.fieldPaths=contrasena&key=$firebaseApiKey";

    $updateData = [
        "fields" => [
            "contrasena" => ["stringValue" => $contraseña],
        ]
    ];

    $updateOptions = [
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($updateData)
        ]
    ];

    $updateContext  = stream_context_create($updateOptions);
    $updateResponse = file_get_contents($updateUrl, false, $updateContext);

    return $updateResponse !== FALSE;
}

function obtenerUsuarios()
{
    global $firebaseProjectId, $firebaseApiKey;

    // URL para obtener usuarios desde Firebase
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los usuarios.']);
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron usuarios.']);
        return;
    }

    // Procesamos los datos obtenidos
    $usuarios = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];

        // Omitimos los usuarios con tipoUsuario 'CLIENTE'
        if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === 'CLIENTE') {
            continue;
        }

        $usuarios[] = [
            'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/", '', $document['name']),
            'nombre' => isset($fields['nombre']['stringValue'], $fields['apellido']['stringValue'])
                ? $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue']
                : 'Nombre desconocido',
            'correo' => $fields['correo']['stringValue'] ?? '',
            'usuario' => $fields['usuario']['stringValue'] ?? '',
            'tipoUsuario' => $fields['tipoUsuario']['stringValue'] ?? '',
            'claveUsuario' => $fields['claveUsuario']['stringValue'] ?? '',
        ];
    }

    // Ordenamos alfabéticamente por nombre
    usort($usuarios, function ($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });

    // Retornamos los usuarios como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $usuarios]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    $funcion = $_POST['numFuncion'];
    // Asegúrate de recibir los datos en JSON y decodificarlos correctamente
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la petición.']);
    exit();
}
switch ($funcion) {
    case 1:
        obtenerCorreos();
        break;
    case 2:
        $documentId = $_GET['documentId'];
        obtenerCorreoEspecifico($documentId);
        break;
    case 3: // Editar correo
        case 3: // Editar correo
            // Obtener los datos del correo a editar
            $correo = $_POST['correo'];
            $contraseña = $_POST['contrasena']; // Asegúrate de que el nombre del parámetro coincida con el que envías desde JS
            $documentId = $_POST['documentId'];
            $claveUsuario = $_POST['claveUsuario'];
            $usuario = $_POST['usuario'];
        
            // Llamamos a la función para actualizar el correo.
            editarCorreo($correo, $contraseña, $documentId);
            actualizarEmpUss($correo, $contraseña, $claveUsuario, $usuario);
            break;
    case 4: // Eliminar correo
        // Obtener el correo a eliminar
        $documentId = $_POST['documentId'];

        // Lógica para eliminar el correo de la base de datos
        $resultado = eliminarCorreo($documentId);

        // Se envía la respuesta una sola vez
        echo json_encode($resultado);
        break;
    case 5: // Guardar correo (función 5)
        // Obtener los datos del correo desde el POST
        $correo = $_POST['correo'];
        $contraseña = $_POST['contraseña'];

        $claveUsuario = $_POST['claveUsuario'];
        $usuario = $_POST['usuario'];

        // Validar que los datos no estén vacíos
        if (empty($correo) || empty($contraseña)) {
            echo json_encode(['success' => false, 'message' => 'Correo o contraseña vacíos.']);
            exit();
        }

        // Guardar en la colección CORREOS
        $resultadoCorreo = guardarCorreo($correo, $contraseña, $claveUsuario, $usuario);

        // Actualizar el documento correspondiente en la colección EMP_USS
        $resultadoEmp = actualizarEmpUss($correo, $contraseña, $claveUsuario, $usuario);

        if ($resultadoCorreo && $resultadoEmp) {
            echo json_encode(['success' => true, 'message' => 'Correo guardado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el correo.']);
        }
        break;
    case 6:
        obtenerUsuarios();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
