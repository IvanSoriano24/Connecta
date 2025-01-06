<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php'; 

function agregarUsuario($data){

}
function actualizarUsuario($idUsario, $data){

}
function mostrarUsuarios($usuarioLogueado, $usuario) {
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
function mostrarUsuario($idUsuario) {
    global $firebaseProjectId, $firebaseApiKey;

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$idUsuario?key=$firebaseApiKey";
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del usuario.']);
        return;
    }

    $data = json_decode($response, true);
    if (isset($data['fields'])) {
        $usuario = $data['fields'];
        $usuario['id'] = $idUsuario;
        echo json_encode(['success' => true, 'data' => $usuario]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    $funcion = $_POST['numFuncion'];
    // Asegúrate de recibir los datos en JSON y decodificarlos correctamente
    if (isset($_POST['usuarioLogueado'])) {
        $usuarioLogueado = json_decode($_POST['usuarioLogueado'], true);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no recibido']);
        exit();
    }
    if (isset($_POST['usuarioLogueado'])) {
        $usuario = json_decode($_POST['usuario'], true);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no recibido']);
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la petición.']);
    exit();
}


switch ($funcion) {
    case 1: 
        $data = [
            /*
            DATOS
            'dato' => $fields['dato']['tipoDato'],
            */
        ];
        agregarUsuario($data);
        break;

    case 2: // Editar pedido
        $idPedido = $_POST['idPedido'];
        $data = [
            /*
            DATOS
            */
        ];
        actualizarUsuario($idUsario, $data);
        break;

    case 3:
        if (isset($_POST['usuarioLogueado'])) {
            $usuarioLogueado = $_POST['usuarioLogueado'];
            $usuario = $_POST['usuario'];
            mostrarUsuarios($usuarioLogueado, $usuario);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó un usuario.']);
            exit();
        }        
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
?>