<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';

function agregarUsuario($data) {}
function actualizarUsuario($idUsario, $data) {}
function mostrarUsuarios($usuarioLogueado, $usuario){
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
        $usuario = array_map(function($field) {
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
            */];
        agregarUsuario($data);
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
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}