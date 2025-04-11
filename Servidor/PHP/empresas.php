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
    //var_dump($action);
    if ($action === 'sesion') {
        try {
            if (isset($_POST['id'], $_POST['noEmpresa'], $_POST['razonSocial'])) {
                $id = $_POST['id'];
                $noEmpresa = $_POST['noEmpresa'];
                $razonSocial = $_POST['razonSocial'];
                $claveUsuario = $_POST['claveUsuario'];
                $claveSae = $_POST['claveSae'];
                $contrasena = $_POST['contrasena'];
                
                // Lógica de sesión
                $_SESSION['empresa'] = [
                    'id' => $id,
                    'noEmpresa' => $noEmpresa,
                    'razonSocial' => $razonSocial,
                    'claveUsuario' => $claveUsuario,
                    'claveSae' => $claveSae,
                    'contrasena' => $contrasena
                ];
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión de empresa guardada correctamente.',
                    'data' => $_SESSION['empresa']
                ]);
            } else if(isset($_POST['ed']) && $_POST['ed'] === '2'){

                $id = $_SESSION['empresa']['id'];
                $noEmpresa = $_SESSION['empresa']['noEmpresa'];
                $razonSocial = $_SESSION['empresa']['razonSocial'];
                $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? 0;
                $claveSae = $_SESSION['empresa']['claveSae'] ?? 0;
                $contrasena = $_SESSION['empresa']['contrasena'] ?? 0;
                obtenerEmpresa($noEmpresa);

                // Lógica de sesión
                $_SESSION['empresa'] = [
                    'id' => $id,
                    'noEmpresa' => $noEmpresa,
                    'razonSocial' => $razonSocial,
                    'claveUsuario' => $claveUsuario,
                    'claveSae' => $claveSae,
                    'contrasena' => $contrasena
                ];
            }else {
                echo json_encode(['success' => false, 'message' => 'Faltan parámetros.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }elseif($action === 'save'){
        try{
            $noEmpresa = str_pad($_POST['noEmpresa'], 2, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
            $data = [
                'id' => $_POST['id'],
                'noEmpresa' => $noEmpresa,
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
    }elseif($action === 'update'){
        try{
            $data = [
                'id' => $_POST['id'],
                'idDocumento' => $_POST['idDocumento'],
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
            actualizarEmpresa($data);
        }  catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }elseif($action === 'verificar'){
        try {
            //$noEmpresa = $_POST['noEmpresa'];
            $noEmpresa = str_pad($_POST['noEmpresa'], 2, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
            verificarNoEmpresa($noEmpresa);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }elseif($action === 'regimen'){
        try {
            obtenerRegimenes();

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}

function obtenerRegimenes(){
    $filePath = "../../Complementos/CAT_REGIMENFISCAL.xml";
    if (!file_exists($filePath)) {
        echo "El archivo no existe en la ruta: $filePath";
        return;
    }
    
    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo "Error al leer el archivo XML en $filePath";
        return;
    }
    
    try {
        $regimenes = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return;
    }
    
    $regimen = [];
    // Iterar sobre cada <row>
    foreach($regimenes->row as $row){
        //echo "Regimen: " . (string)$row['c_RegimenFiscal'] . " - " . (string)$row['Descripcion'] . "<br/>";
        $regimen[] = [
            'c_RegimenFiscal' => (string)$row['c_RegimenFiscal'],
            'Descripcion' => (string)$row['Descripcion'],
            'Fisica' => (string)$row['Fisica'],
            'Moral' => (string)$row['Moral']
        ];
    }
    if (!empty($regimen)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($regimen, function ($a, $b) {
            return strcmp($a['c_RegimenFiscal'] ?? '', $b['c_RegimenFiscal'] ?? '');
        });


        echo json_encode(['success' => true, 'data' => $regimen]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron ningun regimen.']);
    }
}

function verificarNoEmpresa($noEmpresa){
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
        $documentName = $document['name']; // Aquí obtienes el nombre completo del documento
        if (isset($fields['noEmpresa']['stringValue']) && $fields['noEmpresa']['stringValue'] === $noEmpresa) {
            echo json_encode(['success' => false, 'message' => 'Número de empresa ocupado']);
            return;
        }
    }
    echo json_encode(['success' => true, 'message' => 'Número de empresa válido']);
    return;

}

function obtenerEmpresa($noEmpresa) {
    // Verifica si el número de empresa fue proporcionado
    if (!empty($noEmpresa)) {
        // Intenta obtener los datos desde Firebase
        $datosCompletos = obtenerDatosEmpresa($noEmpresa);

        if ($datosCompletos) {
            // Si los datos son obtenidos correctamente, guarda la empresa en la sesión
            $_SESSION['empresaInfo'] = $datosCompletos;
            //var_dump($_SESSION['empresa']);
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
        $documentName = $document['name']; // Aquí obtienes el nombre completo del documento
        $documentId = basename($documentName);
        if (isset($fields['noEmpresa']['stringValue']) && $fields['noEmpresa']['stringValue'] === $noEmpresa) {
            return [
                'idDocumento' => $documentId,
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

function actualizarEmpresa($data) {
    global $firebaseProjectId, $firebaseApiKey;

    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";

    $documentoId = $data['idDocumento'];

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
            'claveUsuario' => $_SESSION['empresa']['claveUsuario'],
            'claveSae' => $_SESSION['empresa']['claveSae'],
            'contrasena' => $_SESSION['empresa']['contrasena'],
        ];

        echo json_encode(['success' => true, 'message' => 'Documento actualizado correctamente y sesión de empresa actualizada.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Función para guardar o actualizar empresa
function guardarEmpresa($data) {
    global $firebaseProjectId, $firebaseApiKey;

    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

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
            'method'  => 'POST', 
            'content' => $payload
        ]
    ];
    
    // Crear el contexto de la solicitud
    $context  = stream_context_create($options);

    try {
        $response = file_get_contents($urlBase, false, $context);
        if ($response === false) {
            throw new Exception('Error al conectar con Firestore para guardar el documento.');
        }
        echo json_encode(['success' => true, 'message' => 'Documento guardado correctamente.']);
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
        'razonSocial' => $data['razonSocial'],
        'claveSae' => $data['claveSae']
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
                        'razonSocial' => isset($fields['empresa']['stringValue']) ? $fields['empresa']['stringValue'] : "Sin Razón Social", // Validar razonSocial
                        'claveUsuario' => isset($fields['claveUsuario']['stringValue']) ? $fields['claveUsuario']['stringValue'] : "Usuario sin Clave",
                        'claveSae' => isset($fields['claveSae']['stringValue']) ? $fields['claveSae']['stringValue'] : "Usuario sin base asociada",
                        'contrasena' => isset($fields['contrasena']['stringValue']) ? $fields['contrasena']['stringValue'] : ""
                    ];
                }
            }
            if (count($empresas) > 0) {
                // Ordenar por razón social si es necesario
                usort($empresas, function ($a, $b) {
                    return strcmp($a['noEmpresa'], $b['noEmpresa']);
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