<?php
require 'firebase.php';
 
session_start(); // Iniciar sesión al inicio del archivo
/*var_dump($_SESSION);
exit;*/
 
// Verifica si el método de la solicitud es GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'];
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
                $noEmpresa = $_POST['noEmpresa'];
                obtenerEmpresa($noEmpresa);
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

function obtenerEmpresa($noEmpresa) {
    // Verifica si el número de empresa fue proporcionado
    if (!empty($noEmpresa)) {
        // Intenta obtener los datos desde Firebase
        $datosCompletos = obtenerDatosEmpresa($noEmpresa);

        if ($datosCompletos) {
            // Si los datos son obtenidos correctamente, guarda la empresa en la sesión
            $_SESSION['empresa'] = $datosCompletos;
            return responderJson(true, 'Datos de la empresa obtenidos correctamente.', $datosCompletos);
        } else {
            return responderJson(false, 'No se encontraron datos para la empresa especificada.');
        }
    } else {
        return responderJson(false, 'El número de empresa no fue proporcionado.');
    }
}

// Función para obtener los datos desde Firebase
function obtenerDatosEmpresa($noEmpresa) {
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    // Configura el contexto de la solicitud para manejar errores y tiempo de espera
    $context = stream_context_create([
        'http' => [
            'timeout' => 10 // Tiempo máximo de espera en segundos
        ]
    ]);

    // Realizar la consulta a Firebase
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false; // Error en la petición
    }

    // Decodifica la respuesta JSON
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false; // No se encontraron documentos
    }

    // Busca los datos de la empresa por noEmpresa
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        if (isset($fields['noEmpresa']['stringValue']) && $fields['noEmpresa']['stringValue'] === $noEmpresa) {
            return [
                'noEmpresa' => $fields['noEmpresa']['stringValue'] ?? null,
                'id' => $fields['id']['stringValue'] ?? null,
                'razonSocial' => $fields['razonSocial']['stringValue'] ?? null,
                'rfc' => $fields['rfc']['stringValue'] ?? null,
                'regimenFiscal' => $fields['regimenFiscal']['stringValue'] ?? null,
                'calle' => $fields['calle']['stringValue'] ?? null,
                'numExterior' => $fields['numExterior']['stringValue'] ?? null,
                'numInterior' => $fields['numInterior']['stringValue'] ?? null,
                'entreCalle' => $fields['entreCalle']['stringValue'] ?? null,
                'colonia' => $fields['colonia']['stringValue'] ?? null,
                'referencia' => $fields['referencia']['stringValue'] ?? null,
                'pais' => $fields['pais']['stringValue'] ?? null,
                'estado' => $fields['estado']['stringValue'] ?? null,
                'municipio' => $fields['municipio']['stringValue'] ?? null,
                'codigoPostal' => $fields['codigoPostal']['stringValue'] ?? null,
                'poblacion' => $fields['poblacion']['stringValue'] ?? null
            ];
        }
    }

    return false; // No se encontró la empresa
}

// Función para responder en formato JSON
function responderJson($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}


// Función para guardar o actualizar empresa
function guardarEmpresa($data) {
    global $firebaseProjectId, $firebaseApiKey;

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


    $fieldsToSave = [
        'id' => ['stringValue' => $data['id']],
        'noEmpresa' => ['stringValue' => $data['noEmpresa']],
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

    // Construir el payload en formato JSON
    $payload = json_encode(['fields' => $fieldsToSave]);

    // Configurar las opciones de la solicitud HTTP
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'PATCH', // PATCH para actualizar el documento existente
            'content' => $payload
        ]
    ];
    
    // Crear el contexto de la solicitud
    $context  = stream_context_create($options);

    try {
        $responseActualizar = file_get_contents($urlActualizar, false, $context);
        if ($responseActualizar === false) {
            throw new Exception('Error al conectar con Firestore para actualizar el documento.');
        }
        
        // Actualiza los datos en la sesión después de guardar en Firebase
        $_SESSION['empresa'] = [
            'id' => $data['id'],
            'noEmpresa' => $data['noEmpresa'],
            'razonSocial' => $data['razonSocial'],
            'rfc' => $data['rfc'],
            'regimenFiscal' => $data['regimenFiscal'],
            'calle' => $data['calle'],
            'numExterior' => $data['numExterior'],
            'numInterior' => $data['numInterior'],
            'entreCalle' => $data['entreCalle'],
            'colonia' => $data['colonia'],
            'referencia' => $data['referencia'],
            'pais' => $data['pais'],
            'estado' => $data['estado'],
            'municipio' => $data['municipio'],
            'codigoPostal' => $data['codigoPostal'],
            'poblacion' => $data['poblacion']
        ];

        echo json_encode(['success' => true, 'message' => 'Documento actualizado correctamente y sesión de empresa actualizada.']);
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