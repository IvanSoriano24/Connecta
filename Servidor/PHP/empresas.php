<?php
require 'firebase.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'];
    if ($action === 'get') {
        listaEmpresas(); // Llamar a la función para obtener las empresas
    }elseif ($action === 'obtenerEmpresa') {
        obtenerEmpresa(); // Llamar a la función para obtener una empresa específica
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    switch ($action) {
        case 'save': // Crear o actualizar empresa
            $data = [
                'id' => $_POST['id'],
                'noEmpresa' => $_POST['noEmpresa'],
                'razonSocial' => $_POST['razonSocial']
            ];
            guardarEmpresa($data);
            break;

        case 'delete': // Eliminar empresa
            eliminarEmpresa();
            break;
        /*case 'guardarEmpresa':
            guardarDatosEmpresa();*/
    }
}/*

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'];

    if ($action === 'get') {
        obtenerEmpresa();
    }
}
*/

// Función para guardar o actualizar empresa
function guardarEmpresa($data)
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";
    // Verificar si existe un ID fijo (opcionalmente podrías generar IDs automáticos)
    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'PATCH', // PATCH actualiza o agrega campos
            'content' => json_encode($data),
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response !== false) {
        echo json_encode(['success' => true, 'message' => 'Empresa guardada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la empresa.']);
    }
}

// Función para eliminar empresa
function eliminarEmpresa()
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'DELETE',
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response !== false) {
        echo json_encode(['success' => true, 'message' => 'Empresa eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la empresa.']);
    }
}

// Función para obtener datos de la empresa
function listaEmpresas()
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    // Realizar la petición GET
    $response = file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        // Verificar si existen documentos
        if (isset($data['documents'])) {
            $empresas = [];

            // Recorrer los documentos y extraer sus datos
            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $empresas[] = [
                    'id' => $fields['id']['integerValue'] ?? null,
                    'noEmpresa' => $fields['noEmpresa']['stringValue'] ?? '',
                    'razonSocial' => $fields['razonSocial']['stringValue'] ?? ''
                ];
            }
            echo json_encode(['success' => true, 'data' => $empresas]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron empresas.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las empresas.']);
    }
    //guardarDatosEmpresa();
}
// Optener datos para su edicion

function obtenerEmpresa()
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";
    // Realizar la petición GET
    $response = file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        // Verificar si existen documentos
        if (isset($data['documents'])) {
            $empresas = [];

            // Recorrer los documentos y extraer sus datos
            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $empresas[] = [
                    'id' => $fields['id']['integerValue'],
                    'noEmpresa' => $fields['noEmpresa']['stringValue'],
                    'razonSocial' => $fields['razonSocial']['stringValue']
                ];
            }
            // Devolver los datos procesados
            echo json_encode(['success' => true, 'data' => $empresas]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron empresas.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las empresas.']);
    }
}
function guardarDatosEmpresa()
{
    session_start();
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['empresaId'])) {
        $_SESSION['empresa'] = [
            'id' => $data['empresaId'],
            'noEmpresa' => $data['noEmpresa'],
            'razonSocial' => $data['razonSocial']
        ];

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se recibió el ID de la empresa.']);
    }
}

// Llamar a la función para obtener las empresas
//$empresas = obtenerEmpresas();
