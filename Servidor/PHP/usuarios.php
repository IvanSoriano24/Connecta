<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';

function guardarUsuario($datosUsuario)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Extraer el ID del usuario de los datos proporcionados
    $idUsuario = isset($datosUsuario['idUsuario']) ? $datosUsuario['idUsuario'] : null;

    // Determinar si se trata de una creación (POST) o edición (PATCH)
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS";
    $method = "POST";

    if ($idUsuario) {
        // Si existe el ID del usuario, actualizamos (PATCH)
        $url .= "/$idUsuario?key=$firebaseApiKey";
        $method = "PATCH";
    } else {
        // Si no hay ID, estamos creando un nuevo documento
        $url .= "?key=$firebaseApiKey";
    }

    // Formatear los datos para Firebase (estructura de "fields")
    $fields = [
        'usuario' => ['stringValue' => $datosUsuario['usuario']],
        'nombre' => ['stringValue' => $datosUsuario['nombreUsuario']],
        'apellido' => ['stringValue' => $datosUsuario['apellidosUsuario']],
        'correo' => ['stringValue' => $datosUsuario['correoUsuario']],
        'password' => ['stringValue' => $datosUsuario['contrasenaUsuario']],
        'telefono' => ['stringValue' => $datosUsuario['telefonoUsuario']],
        'tipoUsuario' => ['stringValue' => $datosUsuario['rolUsuario']],
        'descripcionUsuario' => ['stringValue' => $datosUsuario['rolUsuario']],
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
        echo json_encode(['success' => false, 'message' => 'Error al guardar el usuario en Firebase.']);
        return;
    }

    $data = json_decode($response, true);
    if (isset($data['name'])) {
        echo json_encode(['success' => true, 'message' => 'Usuario guardado exitosamente.', 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el usuario.']);
    }
}

function actualizarUsuario($idUsario, $data) {}

function mostrarUsuarios($usuarioLogueado, $usuario)
{
    global $firebaseProjectId, $firebaseApiKey;
    // Validamos si el usuario logueado es administrador
    $esAdministrador = ($usuarioLogueado === 'ADMINISTRADOR'); // Comparar el tipo de usuario
    // Si no es administrador, solo mostramos su propio usuario
    if (!$esAdministrador) {
        // En este caso, podemos buscar al usuario por su nombre en la base de datos
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
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
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            if ($fields['usuario']['stringValue'] === $usuario) { // Aquí buscamos por nombre de usuario
                $usuario = [
                    'id' => str_replace('projects/' . $firebaseProjectId . '/databases/(default)/documents/USUARIOS/', '', $document['name']),
                    'nombreCompleto' => $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue'],
                    'correo' => $fields['correo']['stringValue'] ?? '',
                    'estatus' => $fields['estatus']['stringValue'] ?? '',
                    'rol' => $fields['tipoUsuario']['stringValue'] ?? '',
                    'usuario' => $fields['usuario']['stringValue'] ?? '',
                ];
                break; // Salimos del loop una vez que encontramos el usuario
            }
        }
        if ($usuario === null) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            return;
        }
        $usuarios = [$usuario];  // Asignamos el usuario encontrado a la lis
    } else {
        // Si es administrador, obtenemos todos los usuarios
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
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
        $usuarios = [];
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            $usuarios[] = [
                'id' => str_replace('projects/' . $firebaseProjectId . '/databases/(default)/documents/USUARIOS/', '', $document['name']),
                'nombreCompleto' => $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue'],
                'correo' => $fields['correo']['stringValue'] ?? '',
                'estatus' => $fields['estatus']['stringValue'] ?? '',
                'rol' => $fields['tipoUsuario']['stringValue'] ?? '',
                'usuario' => $fields['usuario']['stringValue'] ?? '',
            ];
        }
    }
    // Devolvemos los usuarios
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $usuarios]);
    // Limpiamos y finalizamos el script sin salida adicional
    exit();
}

// Función para obtener un usuario específico
function mostrarUsuario($idUsuario)
{
    global $firebaseProjectId, $firebaseApiKey;
    // URL de la API de Firebase para obtener los datos del usuario por ID
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$idUsuario?key=$firebaseApiKey";
    // Hacemos la solicitud a Firebase
    $response = @file_get_contents($url);
    // Verificamos si hubo algún error al obtener la respuesta
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del usuario.']);
        return;
    }
    // Decodificamos la respuesta JSON de Firebase
    $data = json_decode($response, true);
    // Verificamos si la respuesta contiene los datos esperados
    if (isset($data['fields'])) {
        // Obtenemos los campos de usuario de la respuesta de Firebase
        $usuario = $data['fields'];
        // Aseguramos que el ID del usuario esté presente en la respuesta
        $usuario['id'] = $idUsuario;
        // Limpiamos los campos para que no contengan datos de Firebase
        // Firebase devuelve los campos bajo la clave "fields", necesitamos acceder a los valores reales
        $usuario = array_map(function ($field) {
            return isset($field['stringValue']) ? $field['stringValue'] : null;
        }, $usuario);
        // Respondemos con los datos del usuario en formato JSON
        echo json_encode(['success' => true, 'data' => $usuario]);
    } else {
        // Si no se encuentra el usuario, devolvemos un mensaje de error
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    }
}

function optenerEmpresas(){
    // Configuración de Firebase
    global $firebaseProjectId, $firebaseApiKey;
    $urlEmpUs = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";
    // Realizar la solicitud a Firebase
    $responseEmpUs = file_get_contents($urlEmpUs);
    if ($responseEmpUs !== false) {
        $dataEmpUs = json_decode($responseEmpUs, true);
        if (isset($dataEmpUs['documents'])) {
            $empresas = [];
            foreach ($dataEmpUs['documents'] as $document) {
                $fields = $document['fields'];

                // Agregar todas las empresas sin filtrar por usuario
                $empresas[] = [
                    'id' => isset($fields['id']['stringValue']) ? $fields['id']['stringValue'] : "N/A",
                    'noEmpresa' => isset($fields['noEmpresa']['stringValue']) ? $fields['noEmpresa']['stringValue'] : "No especificado",
                    'razonSocial' => isset($fields['razonSocial']['stringValue']) ? $fields['razonSocial']['stringValue'] : "Sin Razón Social"
                ];
            }
            // Verificar si se encontraron empresas
            if (count($empresas) > 0) {
                // Ordenar por razón social
                usort($empresas, function ($a, $b) {
                    return strcmp($a['razonSocial'], $b['razonSocial']);
                });

                // Devolver las empresas como respuesta JSON
                echo json_encode(['success' => true, 'data' => $empresas]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontraron empresas.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron datos de empresas en Firebase.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos de empresas.']);
    }
}
function obtenerUsuarios() {
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
        $usuarios[] = [
            'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/", '', $document['name']),
            'nombre' => isset($fields['nombre']['stringValue'], $fields['apellido']['stringValue']) 
                        ? $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue'] 
                        : 'Nombre desconocido',
            'correo' => $fields['correo']['stringValue'] ?? '',
            'estatus' => $fields['estatus']['stringValue'] ?? '',
            'rol' => $fields['tipoUsuario']['stringValue'] ?? '',
            'usuario' => $fields['usuario']['stringValue'] ?? '',
        ];
    }

    // Retornamos los usuarios como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $usuarios]);
    exit();
}
function guardarAsociacion() {
    global $firebaseProjectId, $firebaseApiKey;

    $empresa = $_POST['empresa'] ?? null;
    $id = $_POST['id'] ?? null;
    $noEmpresa = $_POST['noEmpresa'] ?? null;
    $usuario = $_POST['usuario'] ?? null;

    if (!$empresa || !$id || !$noEmpresa || !$usuario) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para guardar la asociación.']);
        return;
    }

    // Verificar si ya existe la asociación
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";

    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al verificar las asociaciones existentes.']);
        return;
    }

    $data = json_decode($response, true);

    if (isset($data['documents'])) {
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];

            if (
                isset($fields['usuario']['stringValue'], $fields['id']['stringValue']) &&
                $fields['usuario']['stringValue'] === $usuario &&
                $fields['id']['stringValue'] === $id
            ) {
                echo json_encode(['success' => false, 'message' => 'La asociación ya existe.']);
                return;
            }
        }
    }

    // Si no existe, guardar la nueva asociación
    $fields = [
        'empresa' => ['stringValue' => $empresa],
        'id' => ['stringValue' => $id],
        'noEmpresa' => ['stringValue' => $noEmpresa],
        'usuario' => ['stringValue' => $usuario],
    ];

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode(['fields' => $fields]),
        ],
    ];
    $context = stream_context_create($options);

    $response = @file_get_contents($url, false, $context);

    if ($response !== FALSE) {
        echo json_encode(['success' => true, 'message' => 'Asociación guardada exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la asociación en Firebase.']);
    }
    exit();
}
function obtenerAsociaciones() {
    global $firebaseProjectId, $firebaseApiKey;

    $usuario = $_GET['usuarioId'] ?? null; // Se espera el campo `usuario`

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no proporcionado.']);
        return;
    }

    // URL para obtener las asociaciones desde la colección EMP_USS
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las asociaciones.']);
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron asociaciones.']);
        return;
    }

    // Filtrar las asociaciones por el campo `usuario` y agregar el `id` del documento
    $asociaciones = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];

        if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
            $asociaciones[] = [
                'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/EMP_USS/", '', $document['name']),
                'razonSocial' => $fields['empresa']['stringValue'],
                'noEmpresa' => $fields['noEmpresa']['stringValue'],
            ];
        }
    }

    // Retornar las asociaciones como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $asociaciones]);
    exit();
}
function eliminarAsociacion() {
    global $firebaseProjectId, $firebaseApiKey;

    $idAsociacion = $_POST['id'] ?? null;

    if (!$idAsociacion) {
        echo json_encode(['success' => false, 'message' => 'ID de la asociación no proporcionado.']);
        return;
    }

    // URL para eliminar el documento en Firestore
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS/$idAsociacion?key=$firebaseApiKey";

    // Configuración de la solicitud
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'DELETE',
        ],
    ];
    $context = stream_context_create($options);

    // Realizar la solicitud a Firebase
    $response = @file_get_contents($url, false, $context);

    if ($response !== FALSE) {
        echo json_encode(['success' => true, 'message' => 'Asociación eliminada exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la asociación.']);
    }
    exit();
}
function obtenerAsociacionesUsuarios() {
    global $firebaseProjectId, $firebaseApiKey;

    $usuario = $_GET['usuarioId'] ?? null;

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no proporcionado.']);
        return;
    }

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";

    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las asociaciones.']);
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron asociaciones.']);
        return;
    }

    $asociaciones = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];

        if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
            $asociaciones[] = [
                'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/EMP_USS/", '', $document['name']),
                'razonSocial' => $fields['empresa']['stringValue'],
                'noEmpresa' => $fields['noEmpresa']['stringValue'],
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $asociaciones]);
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
        $datosUsuario = [
            'idUsuario' => isset($_POST['idUsuario']) ? $_POST['idUsuario'] : null,
            'usuario' => $_POST['usuario'],
            'nombreUsuario' => $_POST['nombreUsuario'],
            'apellidosUsuario' => $_POST['apellidosUsuario'],
            'correoUsuario' => $_POST['correoUsuario'],
            'contrasenaUsuario' => $_POST['contrasenaUsuario'],
            'telefonoUsuario' => $_POST['telefonoUsuario'],
            'rolUsuario' => $_POST['rolUsuario'],
        ];
        // Guardar los datos en Firebase o la base de datos
        guardarUsuario($datosUsuario);
        break;

    case 2: // Editar pedido
        $idPedido = $_POST['idPedido'];
        $data = [
            /*
            DATOS
            */];
        actualizarUsuario($idUsario, $data);
        break;

    case 3:
        if (isset($_POST['usuarioLogueado'])) {
            $usuarioLogueado = $_POST['usuarioLogueado'];
            $usuario = $_POST['usuario'];
            mostrarUsuarios($usuarioLogueado, $usuario);
            break;
        } else {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó un usuario.']);
            exit();
        }
    case 4:
        optenerEmpresas();
        break;
    case 5:
        $id = $_GET['id'];
        mostrarUsuario($id);
        exit();
    case 6:
        obtenerUsuarios();
        break;
    case 7:
        guardarAsociacion();
        break;
    case 8:
        obtenerAsociaciones();
        break;
    case 9:
        eliminarAsociacion();
        break;
        case 10:
            obtenerAsociacionesUsuarios();
            break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
