<?php
require 'firebase.php';

session_start();

function obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae)
{
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
        if ($fields['claveSae']['stringValue'] === $claveSae) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function obtenerClienteFireStore()
{
    global $firebaseProjectId, $firebaseApiKey;

    // URL de Firebase para obtener la colección de USUARIOS
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con Firebase.']);
        return;
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success' => true, 'data' => []]); // No hay documentos
        return;
    }

    $clientes = [];
    // Recorrer cada documento y filtrar por tipoUsuario igual a "CLIENTES"
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        if (
            isset($fields['tipoUsuario']['stringValue']) &&
            $fields['tipoUsuario']['stringValue'] === "CLIENTE"  // Filtrar aquí por "CLIENTES"
        ) {
            $cliente = [
                'clave' => $fields['claveUsuario']['stringValue'],
                'nombre' => isset($fields['nombre']['stringValue']) ? $fields['nombre']['stringValue'] : ''
            ];
            $clientes[] = $cliente;
        }
    }

    echo json_encode(['success' => true, 'data' => $clientes]);
}
function obtenerDescuentoCliente($conexionData, $claveSae, $cliente)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $sql = "SELECT 
        DESCUENTO 
    FROM 
        [SAE90Empre02].[dbo].[CLIE02]
    WHERE 
        CLAVE = ?;";

    $params = [$cliente];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }
    $descuentoCliente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    echo json_encode(['success' => true, 'data' => $descuentoCliente]);
}
/*function guardarDescuentos($data, $cliente)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Función para convertir el descuento a entero (como string), eliminando espacios
    $convertirDescuento = function ($desc) {
        $desc = trim($desc);
        return is_numeric($desc) ? strval((int)$desc) : "0";
    };

    // Construir la estructura de campos usando "integerValue" para números
    $fields = [
        'claveUsuario' => ['stringValue' => $cliente],
        'descuentoCliente' => ['integerValue' => $convertirDescuento($data["descuentoCliente"])],
        "descuentosProductos" => [
            "arrayValue" => [
                "values" => array_map(function ($producto) use ($convertirDescuento) {
                    return [
                        "mapValue" => [
                            "fields" => [
                                "clave" => ["stringValue" => $producto["clave"]],
                                "descripcion" => ["stringValue" => $producto["descripcion"]],
                                "descuento" => ["integerValue" => $convertirDescuento($producto["descuento"])],
                            ]
                        ]
                    ];
                }, $data['productos'])
            ]
        ],
    ];

    // Crear el payload
    $payload = json_encode(['fields' => $fields]);

    // URL para comprobar si existe el documento (GET)
    $url_check = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DESCUENTOS/$cliente?key=$firebaseApiKey";
    // URL para crear el documento (POST) con ID personalizado
    $url_create = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DESCUENTOS?documentId=$cliente&key=$firebaseApiKey";

    // Usar get_headers para verificar la existencia del documento
    $headers = @get_headers($url_check, 1);
    $method = "";
    $url_to_use = "";

    if ($headers !== false && strpos($headers[0], '404') !== false) {
        // El documento no existe, se crea con POST
        $method = 'POST';
        $url_to_use = $url_create;
    } else {
        // Si existe o se obtiene otro estado, se actualiza con PUT
        $method = 'PUT';
        $url_to_use = $url_check;
    }

    // Configurar la petición HTTP con el método seleccionado
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => $method,
            'content' => $payload,
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url_to_use, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar los descuentos en Firebase.',
            'payload' => $payload,
            'url' => $url_to_use,
            'error' => $error
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Descuentos guardados correctamente.',
        'response' => $response
    ]);
}*/
function guardarDescuentos($data, $cliente)
{
    global $firebaseProjectId, $firebaseApiKey;
    // Forzar que $cliente sea cadena
    $cliente = "$cliente";

    // Función para convertir el descuento a entero (como string), eliminando espacios
    $convertirDescuento = function ($desc) {
        $desc = trim($desc);
        return is_numeric($desc) ? strval((int)$desc) : "0";
    };

    // Construir la estructura de campos usando "integerValue" para números
    $fields = [
        'claveUsuario' => ['stringValue' => $cliente],
        'descuentoCliente' => ['integerValue' => $convertirDescuento($data["descuentoCliente"])],
        "descuentosProductos" => [
            "arrayValue" => [
                "values" => array_map(function ($producto) use ($convertirDescuento) {
                    return [
                        "mapValue" => [
                            "fields" => [
                                "clave" => ["stringValue" => $producto["clave"]],
                                "descripcion" => ["stringValue" => $producto["descripcion"]],
                                "descuento" => ["integerValue" => $convertirDescuento($producto["descuento"])],
                            ]
                        ]
                    ];
                }, $data['productos'])
            ]
        ],
    ];

    // Crear el payload JSON
    $payload = json_encode(['fields' => $fields]);

    // Construir las URL:
    $clienteId = urlencode($cliente);
    // URL para consultar si existe el documento (GET)
    $url_check = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DESCUENTOS/$clienteId?key=$firebaseApiKey";
    // URL para crear el documento (POST) con ID personalizado
    $url_create = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DESCUENTOS?documentId=$clienteId&key=$firebaseApiKey";

    // Verificar la existencia del documento usando get_headers()
    $headers = @get_headers($url_check, 1);

    $documentoExiste = false;
    if ($headers !== false && isset($headers[0]) && stripos($headers[0], '200') !== false) {
        $documentoExiste = true;
    }

    // Seleccionar método y URL según la existencia del documento
    if ($documentoExiste) {
        $method = 'PATCH';     // Actualizar documento existente
        $url_to_use = $url_check;
    } else {
        $method = 'POST';    // Crear nuevo documento
        $url_to_use = $url_create;
    }

    // Configurar la petición HTTP con el método seleccionado
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => $method,
            'content' => $payload,
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url_to_use, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar los descuentos en Firebase.',
            'payload' => $payload,
            'url' => $url_to_use,
            'error' => $error
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Descuentos guardados correctamente.',
        'response' => $response
    ]);
}
function obtenerProdutosFirebase($cliente)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Asegurarse de que el ID del cliente sea seguro para la URL
    $cliente = urlencode($cliente);

    // Construir la URL para obtener el documento DESCUENTOS del cliente
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DESCUENTOS/$cliente?key=$firebaseApiKey";

    // Realizar la petición a Firebase
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los descuentos desde Firebase.'
        ]);
        return;
    }

    $data = json_decode($response, true);

    // Verificar que existan los productos con descuento
    if (!isset($data['fields']['descuentosProductos']['arrayValue']['values'])) {
        echo json_encode(['success' => true, 'data' => []]); // No hay descuentos registrados
        return;
    }

    $values = $data['fields']['descuentosProductos']['arrayValue']['values'];
    $productos = [];
    foreach ($values as $value) {
        $fields = $value['mapValue']['fields'];
        $productos[] = [
            'clave' => isset($fields['clave']['stringValue']) ? $fields['clave']['stringValue'] : '',
            'descuento' => isset($fields['descuento']['integerValue']) ? $fields['descuento']['integerValue'] : ''
        ];
    }

    echo json_encode(['success' => true, 'data' => $productos]);
}
function obtenerClienteFirebase($cliente)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Sanea el cliente para la URL
    $cliente = urlencode($cliente);

    // Construir la URL para obtener el documento DESCUENTOS del cliente
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DESCUENTOS/$cliente?key=$firebaseApiKey";

    // Realizar la petición a Firebase
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los descuentos desde Firebase.'
        ]);
        return;
    }

    $data = json_decode($response, true);

    // Verifica que el campo descuentoCliente exista en el documento
    if (!isset($data['fields']['descuentoCliente']['integerValue'])) {
        // Si no existe, devuelve 0
        echo json_encode(['success' => true, 'data' => 0]);
        return;
    }

    // El valor viene en integerValue (como string)
    $descuento = $data['fields']['descuentoCliente']['integerValue'];

    echo json_encode(['success' => true, 'data' => $descuento]);
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

        obtenerClienteFireStore();
        break;
    case 2:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $cliente = $_POST['cliente'];
        obtenerDescuentoCliente($conexionData, $claveSae, $cliente);
        break;

    case 3:
        // Recibir y decodificar los datos enviados desde AJAX
        if (isset($_POST['datos'])) {
            $data = json_decode($_POST['datos'], true);
            $cliente = json_decode($_POST['cliente'], true);
            if ($data === null) {
                echo json_encode(['success' => false, 'message' => 'Error al decodificar los datos.']);
                exit();
            }
            guardarDescuentos($data, $cliente);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se enviaron datos.']);
        }
        break;
    case 4:
        // Recibir y decodificar los datos enviados desde AJAX
        $cliente = json_decode($_GET['cliente'], true);
        obtenerProdutosFirebase($cliente);
        break;
    case 5:
        // Recibir y decodificar los datos enviados desde AJAX
        $cliente = json_decode($_GET['cliente'], true);
        obtenerClienteFirebase($cliente);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
