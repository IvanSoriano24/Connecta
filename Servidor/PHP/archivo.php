<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
include 'reportes.php';
include 'funcionalidades.php';

//session_start();

function subirImagenArticulo($conexionData){
    // Verifica que se haya enviado al menos un archivo
    if (!isset($_FILES['imagen']) || empty($_FILES['imagen']['name'])) {
        echo json_encode(['success' => false, 'message' => 'No se pudo subir ninguna archivo.']);
        exit;
    }

    // Verifica que se haya enviado la clave del artículo
    if (!isset($_POST['cveArt'])) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionó la clave del artículo.']);
        exit;
    }

    $cveArt = $_POST['cveArt'];
    $imagenes = $_FILES['imagen'];
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app"; // Cambia esto por tu bucket 

    // Subir y procesar cada archivo
    $rutasImagenes = [];
    foreach ($imagenes['tmp_name'] as $index => $tmpName) {
        if ($imagenes['error'][$index] === UPLOAD_ERR_OK) {
            $nombreArchivo = $cveArt . "_" . uniqid() . "_" . basename($imagenes['name'][$index]);
            $rutaFirebase = "imagenes/{$cveArt}/{$nombreArchivo}";
            $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?name=" . urlencode($rutaFirebase);

            // Leer el archivo
            $archivo = file_get_contents($tmpName);

            // Subir el archivo a Firebase Storage
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/octet-stream"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $archivo);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $resultado = json_decode($response, true);

            if (isset($resultado['name'])) {
                $urlPublica = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/{$resultado['name']}?alt=media";
                $rutasImagenes[] = $urlPublica;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir una imagen.', 'response' => $response]);
                exit;
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Imágenes subidas correctamente.', 'imagenes' => $rutasImagenes]);
}

// -----------------------------------------------------------------------------------------------------//
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
        // Obtener conexión
        /*$claveSae = "02";
        $noEmpresa = "02";*/
        $claveSae = "01";
        $noEmpresa = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae); //Aqui
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];
        // Llamar a la función para extraer productos
        subirImagenArticulo($conexionData);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
