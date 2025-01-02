<?php
require 'firebase.php';
 
session_start(); // Iniciar sesión al inicio del archivo
/*var_dump($_SESSION);
exit;*/
 
// Verifica si el método de la solicitud es GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'];
    var_dump($action);
    if ($action === 'get') {
        $usuario = $_GET['usuario'];
        listaEmpresas($usuario); // Llamar a la función para obtener las empresas
    }
}
 
// Verifica si el método de la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'sesion') {
        try {
            if (isset($_POST['id'], $_POST['noEmpresa'], $_POST['razonSocial'])) {
                $id = $_POST['id'];
                $noEmpresa = $_POST['noEmpresa'];
                $razonSocial = $_POST['razonSocial'];
                // Lógica de sesión
                $_SESSION['empresa'] = [
                    'id' => $id,
                    'noEmpresa' => $noEmpresa,
                    'razonSocial' => $razonSocial
                ];
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión de empresa guardada correctamente.',
                    'data' => $_SESSION['empresa']
                ]);
            } else if(isset($_POST['ed']) && $_POST['ed'] === '2'){
                obtenerEmpresa();
            }else {
                echo json_encode(['success' => false, 'message' => 'Faltan parámetros.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }elseif($action === 'save'){
        try{
            $data = [
                'id' => $_POST['id'],
                'noEmpresa' => $_POST['noEmpresa'],
                'razonSocial' => $_POST['razonSocial'],
                'rfc' => $_POST['rfc'],
                'regimenFiscal' => $_POST['regimenFiscal'],
                'calle' => $_POST['calle'],
                'numExterior' => $_POST['numExterior'],
                'numInterior' => $_POST['numInterior'],
                'entreCalle' => $_POST['entreCalle'],
                'colonia' => $_POST['colonia'],
                'referencia' => $_POST['referencia'],
                'pais' => $_POST['pais'],
                'estado' => $_POST['estado'],
                'municipio' => $_POST['municipio'],
                'codigoPostal' => $_POST['codigoPostal'],
                'poblacion' => $_POST['poblacion']
            ];
            guardarEmpresa($data);
        }  catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}

function obtenerEmpresa() {
    // Verifica si la empresa está en la sesión
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa'];
        // Si faltan datos en la sesión, busca los datos en la base de datos
        if (empty($empresa['rfc']) || empty($empresa['regimenFiscal']) || empty($empresa['calle']) || empty($empresa['numExterior']) || empty($empresa['numInterior'])
        || empty($empresa['entreCalle']) || empty($empresa['colonia']) || empty($empresa['referencia']) || empty($empresa['pais']) || empty($empresa['estado'])
        || empty($empresa['municipio']) || empty($empresa['codigoPostal']) || empty($empresa['poblacion'])) {
            $datosCompletos = obtenerDatosEmpresa($empresa['noEmpresa']);
            if ($datosCompletos) {
                $empresa = array_merge($empresa, $datosCompletos);
                $_SESSION['empresa'] = $empresa; // Actualiza la sesión con los datos completos
            }
        }
        echo json_encode([
            'success' => true,
            'data' => $empresa
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la empresa en la sesión.'
        ]);
    }
}
// Ejemplo de función para obtener los datos desde Firebase
function obtenerDatosEmpresa($noEmpresa) {
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";
    // Realizar la consulta a Firebase para obtener los datos
    $response = file_get_contents($url);
    if ($response === false) {
        return false; // Error en la petición
    }
    
    $data = json_decode($response, true);
    $empresaData = null;

    if (isset($data['documents'])) {
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['noEmpresa']['stringValue']) && $fields['noEmpresa']['stringValue'] === $noEmpresa) {
                $empresaData = [
                    'rfc' => $fields['rfc']['stringValue'],
                    'regimenFiscal' => $fields['regimenFiscal']['stringValue'],
                    'calle' => $fields['calle']['stringValue'],
                    'numExterior' => $fields['numExterior']['stringValue'],
                    'numInterior' => $fields['numInterior']['stringValue'],
                    'entreCalle' => $fields['entreCalle']['stringValue'],
                    'colonia' => $fields['colonia']['stringValue'],
                    'referencia' => $fields['referencia']['stringValue'],
                    'pais' => $fields['pais']['stringValue'],
                    'estado' => $fields['estado']['stringValue'],
                    'municipio' => $fields['municipio']['stringValue'],
                    'codigoPostal' => $fields['codigoPostal']['stringValue'],
                    'poblacion' => $fields['poblacion']['stringValue']
                ];
                break; 
            }
        }
    }
    return $empresaData;
}


// Función para guardar o actualizar empresa
function guardarEmpresa($data) {
    global $firebaseProjectId, $firebaseApiKey;
 
    // URL base para Firestore
    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
 
    // URL para buscar en la colección EMPRESAS
    $urlBuscar = $urlBase . "/EMPRESAS?key=$firebaseApiKey";
 
    // Realizar la consulta para obtener todos los documentos de la colección
    $responseBuscar = file_get_contents($urlBuscar);
    if ($responseBuscar === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener documentos de la colección.']);
        return;
    }
 
    $dataBuscar = json_decode($responseBuscar, true);
 
    // Buscar el documento que tenga el campo noEmpresa igual al valor recibido
    $documentoId = null;
    if (isset($dataBuscar['documents'])) {
        foreach ($dataBuscar['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['noEmpresa']['stringValue']) && $fields['noEmpresa']['stringValue'] === $data['noEmpresa']) {
                $documentoId = basename($document['name']); // Extraemos el ID del documento
                break;
            }
        }
    }
 
    // Si no se encuentra el documento, devolver un error
    if ($documentoId === null) {
        echo json_encode(['success' => false, 'message' => 'No se encontró un documento con el noEmpresa proporcionado.']);
        return;
    }
 
    // Construir la URL del documento encontrado para actualizarlo
    $urlActualizar = "$urlBase/EMPRESAS/$documentoId?key=$firebaseApiKey";
 
    // Los campos que deseas actualizar/agregar
    $fieldsToSave = [
        'razonSocial' => ['stringValue' => $data['razonSocial']],
        'rfc' => ['stringValue' => $data['rfc']],
        'regimenFiscal' => ['stringValue' => $data['regimenFiscal']],
        'calle' => ['stringValue' => $data['calle']],
        'numExterior' => ['stringValue' => $data['numExterior']],
        'numInterior' => ['stringValue' => $data['numInterior']],
        'entreCalle' => ['stringValue' => $data['entreCalle']],
        'colonia' => ['stringValue' => $data['colonia']],
        'referencia' => ['stringValue' => $data['referencia']],
        'pais' => ['stringValue' => $data['pais']],
        'estado' => ['stringValue' => $data['estado']],
        'municipio' => ['stringValue' => $data['municipio']],
        'codigoPostal' => ['stringValue' => $data['codigoPostal']],
        'poblacion' => ['stringValue' => $data['poblacion']]
    ];
 
    $payload = json_encode(['fields' => $fieldsToSave]);
 
    // Configurar la solicitud HTTP para actualizar
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'PATCH', // PATCH para actualizar el documento existente
            'content' => $payload
        ]
    ];
 
    $context = stream_context_create($options);
 
    // Realizar la solicitud para actualizar el documento
    try {
        $responseActualizar = file_get_contents($urlActualizar, false, $context);
        if ($responseActualizar === false) {
            throw new Exception('Error al conectar con Firestore para actualizar el documento.');
        }
        echo json_encode(['success' => true, 'message' => 'Documento actualizado correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
 
function sesionEmpresa($data)
{
    // Guardar la empresa en la sesión
    $_SESSION['empresa'] = [
        'id' => $data['id'],
        'noEmpresa' => $data['noEmpresa'],
        'razonSocial' => $data['razonSocial']
    ];
 
    //header('Content-Type: application/json');
 
    // Responder al cliente con los datos guardados
    echo json_encode([
        'success' => true,
        'message' => 'Sesión de empresa guardada correctamente.',
        'data' => $_SESSION['empresa']
    ]);
}
// Función para eliminar empresa
function eliminarEmpresa(){
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
function listaEmpresas($nombreUsuario) {
    global $firebaseProjectId, $firebaseApiKey;
    $urlEmpUs = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";
    $responseEmpUs = file_get_contents($urlEmpUs);
 
    if ($responseEmpUs !== false) {
        $dataEmpUs = json_decode($responseEmpUs, true);
        if (isset($dataEmpUs['documents'])) {
            $empresas = [];
            foreach ($dataEmpUs['documents'] as $document) {
                $fields = $document['fields'];
 
                // Verificar que el usuario coincida
                if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $nombreUsuario) {
                    $empresas[] = [
                        'id' => isset($fields['id']['stringValue']) ? $fields['id']['stringValue'] : "N/A", // Validar id
                        'noEmpresa' => isset($fields['noEmpresa']['stringValue']) ? $fields['noEmpresa']['stringValue'] : "No especificado", // Validar noEmpresa
                        'razonSocial' => isset($fields['empresa']['stringValue']) ? $fields['empresa']['stringValue'] : "Sin Razón Social" // Validar razonSocial
                    ];
                }
            }
 
            if (count($empresas) > 0) {
                // Ordenar por razón social si es necesario
                usort($empresas, function ($a, $b) {
                    return strcmp($a['razonSocial'], $b['razonSocial']);
                });
 
                $_SESSION['empresaSelect'] = [
                    'usuario' => $nombreUsuario,
                    'empresas' => $empresas
                ];
                echo json_encode(['success' => true, 'data' => $empresas]);
            } else {
                echo json_encode(['success' => false, 'message' => 'El usuario no tiene empresas asociadas.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron relaciones de empresas para este usuario.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las relaciones de empresas del usuario.']);
    }
}
 
?>