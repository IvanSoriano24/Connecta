<?php

require 'firebase.php';

function obtenerConexion($firebaseProjectId, $firebaseApiKey)
{
    $noEmpresa = "02";
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
    return ['success' => false, 'message' => 'No hay una conexion al sistema'];
}

function login($funcion) {
    try {
        $tipUsuario = '';
        session_start();

        $firebaseApiKey = "AIzaSyCh8BFeIi4JcAAe-aW8Z2odIqdytw-wnDA";
        $firebaseProjectId = "mdconnecta-4aeb4";
        $privateKeyPath = "/ruta/a/tu/archivo-privado.json"; // Ruta al archivo de clave privada de Firebase
        $clientEmail = "tu-email@proyecto.iam.gserviceaccount.com"; // Email del proyecto Firebase

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $usuario = $_POST['usuario'];
            $password = $_POST['password']; 

            // URL del API REST de Firebase Firestore
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
            // Realizar la solicitud GET para obtener los usuarios
            $response = file_get_contents($url);

            if ($response === false) {
                die("Error al conectarse con Firebase.");
            }

            // Decodificar la respuesta JSON
            $data = json_decode($response, true);
            // Validar las credenciales
            $usuarioValido = false;

            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $usuarioFirebase = $fields['usuario']['stringValue'];
                $passwordFirebase = $fields['password']['stringValue'];
                $statusFirebase = $fields['status']['stringValue']; // Obtener el campo `status`

                // Validar credenciales y estatus
                if ($usuarioFirebase === $usuario && $passwordFirebase === $password) {
                    if ($statusFirebase === 'Bloqueado') {
                        header("Location: /index.php?error=2");
                        exit();
                    }

                    if ($statusFirebase === 'Baja') {
                        header("Location: /index.php?error=3");
                        exit();
                    }

                    // Si las credenciales coinciden y el usuario está activo, guardar datos en sesión
                    $_SESSION['usuario'] = [
                        'id' => $document['name'], // ID del documento
                        'apellido' => $fields['apellido']['stringValue'],
                        'correo' => $fields['correo']['stringValue'],
                        'descripcionUsuario' => $fields['descripcionUsuario']['stringValue'],
                        'nombre' => $fields['nombre']['stringValue'],
                        'password' => $passwordFirebase,
                        'telefono' => $fields['telefono']['stringValue'],
                        'tipoUsuario' => $fields['tipoUsuario']['stringValue'],
                        'usuario' => $usuarioFirebase,
                        'status' => $statusFirebase,
                        'claveUsuario' => $fields['claveUsuario']['stringValue']
                    ];
                    $tipUsuario = $_SESSION['usuario']['tipoUsuario'];

                    // Generar token JWT para Firebase Storage
                    $usuarioUid = $document['name']; // UID del usuario (ID del documento en Firestore)
                   /* $tokenFirebase = generarTokenFirebase($usuarioUid, $privateKeyPath, $clientEmail);
                    $_SESSION['firebase_token'] = $tokenFirebase; // Guardar el token en la sesión*/

                    $usuarioValido = true;
                    break;
                }
            }

            // Redirigir según el resultado de la validación
            if ($usuarioValido) {
                if ($tipUsuario == 'CLIENTE') {
                    $resultadoConexion = obtenerConexion($firebaseProjectId, $firebaseApiKey);
                    if (!$resultadoConexion['success']) {
                        cerrarSesion();
                        echo json_encode($resultadoConexion);
                        header("Location: ../../Cliente/index.php?error=4");
                        exit();
                    }
                    header("Location: /Menu.php");
                    exit();
                }
                header("Location: /Dashboard.php");
                exit();
            } else {
                header("Location: /index.php?error=1");
                exit();
            }
        }
    } catch (Exception $e) {
        echo $e->getMessage(); // En caso de fallo, se muestra el error
    }
}
function generarTokenFirebase($usuarioUid, $privateKeyPath, $clientEmail) {
    $now = time();
    $exp = $now + (60 * 60); // Token válido por 1 hora

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $payload = json_encode([
        'iss' => $clientEmail, // Emisor
        'sub' => $clientEmail, // Usuario autenticado (generalmente admin de Firebase)
        'aud' => 'https://firebasestorage.googleapis.com/',
        'iat' => $now,
        'exp' => $exp,
        'uid' => $usuarioUid // UID del usuario autenticado
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $unsignedToken = $base64UrlHeader . '.' . $base64UrlPayload;

    $privateKey = file_get_contents($privateKeyPath);
    openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $unsignedToken . '.' . $base64UrlSignature;
}

function cerrarSesion() {
    session_start();
    session_unset();
    session_destroy();
    echo "Sesión Finalizada";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcion = $_POST["numFuncion"] ?? null;
    switch ($funcion) {
        case 1:
             login($funcion); // Ejemplo, no incluido aquí
            break;
        case 2:
            cerrarSesion();
            break;
        default:
            echo "Función no válida.";
            break;
    }
} else {
    echo "Método no permitido.";
}

$funcion = $_POST["numFuncion"];
switch ($funcion) {
    case 1:
        login($funcion);
        break;
    case 2:
        cerrarSesion();
        break;
}
?>