<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$logDir  = __DIR__ . '/logs';
$logFile = $logDir . '/enviosConfirmacion.log';

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
include 'reportes.php';

function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae, $logFile){
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
        $error = error_get_last();
        $msg = sprintf(
            "[%s] INFO: No se encontro una conexion → %s\n",
            date('Y-m-d H:i:s'),
            json_encode($error, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        return ['success' => false, 'message' => 'No se encontraron documentos'];
    }
    // Busca el documento donde coincida el campo `noEmpresa`
    foreach ($documents['documents'] as $document) {
        $fields = $document['fields'];
        /*var_dump($fields['noEmpresa']['integerValue']);
        var_dump($noEmpresa);*/
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'nombreBanco' => $fields['nombreBanco']['stringValue'] ?? "",
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function verificarStatusPedido($pedidoID, $firebaseProjectId, $firebaseApiKey, $noEmpresa, $logFile)
{
    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents:runQuery"
        . "?key=$firebaseApiKey";

    // Filtros combinados
    $whereNode = [
        'compositeFilter' => [
            'op' => 'AND',
            'filters' => [
                [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'folio'],
                        'op'    => 'EQUAL',
                        'value' => ['stringValue' => (string)$pedidoID]
                    ]
                ],
                [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'noEmpresa'],
                        'op'    => 'EQUAL',
                        'value' => ['integerValue' => (int)$noEmpresa]
                    ]
                ]
            ]
        ]
    ];

    $payload = json_encode([
        'structuredQuery' => [
            'from'  => [['collectionId' => 'PEDIDOS_AUTORIZAR']],
            'where' => $whereNode,
            'limit' => 1 // Solo necesitamos un resultado
        ]
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload
        ]
    ]);

    $resp = @file_get_contents($url, false, $ctx);

    // Si falla la consulta, asumimos "true" (no está pendiente)
    if ($resp === false) {
        $error = error_get_last();
        $msg = sprintf(
            "[%s] INFO: No se encontro el pedido en 'PEDIDOS_AUTORIZAR' → %s\n",
            date('Y-m-d H:i:s'),
            json_encode($error, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        return true;
    }

    $results = json_decode($resp, true);

    foreach ($results as $item) {
        if (!isset($item['document'])) {
            continue;
        }

        $fields = $item['document']['fields'] ?? [];
        $status = $fields['status']['stringValue'] ?? '';
        $folio = $fields['folio']['stringValue'] ?? '';
        // Si el status es "Sin Autorizar" → false, de lo contrario → true
        return $status !== 'Sin Autorizar';
    }
    //var_dump("No");
    // Si no hay documento que coincida, devolvemos true
    return true;
}
function enviarConfirmacionCorreo($pedidoID, $noEmpresa, $claveSae, $conexionData, $correosManuales = '')
{

    // Capturar los correos enviados desde el frontend
    $correosManuales = $_POST['correos'] ?? '';

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

    $detallesPedido = obtenerPedido($pedidoID, $conn, $claveSae, $conexionData);
    //var_dump($detallesPedido);

    $partidasData = obtenerPartidas($pedidoID, $conn, $claveSae, $conexionData);
    // Extraer 'enviar a' y 'vendedor' del formulario
    $envios = obtenerDireccion($detallesPedido['DAT_ENVIO'], $conn, $claveSae, $conexionData); // Dirección de envío
    $enviarA = $envios['CALLE'];
    $vendedor = $detallesPedido['CVE_VEND']; // Número de vendedor
    $claveCliente = $detallesPedido['CVE_CLPV'];

    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $detallesPedido['FOLIO']; // Número de pedido
    /*$claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
    $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);*/

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener solo el NOMBRE (eliminamos MAIL y EMAILPRED)
    $sql = "SELECT NOMBRE FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [$clave];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['CVE_ART'];

        // Consulta SQL para obtener la descripción del producto
        $sqlProducto = "SELECT DESCR FROM $nombreTabla2 WHERE CVE_ART = ?";
        $stmtProducto = sqlsrv_query($conn, $sqlProducto, [$claveProducto]);

        if ($stmtProducto && $rowProducto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC)) {
            $partida['descripcion'] = $rowProducto['DESCR'];
        } else {
            $partida['descripcion'] = 'Descripción no encontrada'; // Manejo de error
        }

        sqlsrv_free_stmt($stmtProducto);
    }

    $fecha = $detallesPedido['FECHAELAB'];
    $fechaElaboracion = $fecha->format('Y-m-d');
    //$correo = trim($clienteData['MAIL']);
    //$emailPred = (is_null($clienteData['EMAILPRED'])) ? "" : trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    // Si hay múltiples correos separados por `;`, tomar solo el primero
    //$emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    //$emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    $clienteNombre = trim($clienteData['NOMBRE']);
    $claveCliente = $clave;
    global $firebaseProjectId, $firebaseApiKey;

    // Construir la URL para filtrar (usa el campo idPedido y noEmpresa)
    $collection = "DATOS_PEDIDO";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (idPedido y noEmpresa)
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "idPedido"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noPedido]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $idFirebasePedido = null;
    $direccion1Contacto = null;
    //$envioCorreo = false;

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        // runQuery devuelve un array con un elemento por cada match
        if (isset($resultArray[0]['document'])) {
            $doc    = $resultArray[0]['document'];
            // si quieres el ID:
            $parts  = explode('/', $doc['name']);
            $idFirebasePedido = end($parts);
            // y para tomar tu campo direccion1Contacto:
            $fields = $doc['fields'];
            $direccion1Contacto = $fields['direccion1Contacto']['stringValue'] ?? null;
            //$envioCorreo = $fields['enviarCorreo']['booleanValue'] ?? false;
        }
    }
    /*if(!$envioCorreo){
        echo json_encode(['success' => false, 'notificacion' => true, 'message' => 'No se le notifico al cliente debido a la restriccion del envio de confirmacion por este medio.']);
        die();
    }*/
    $rutaPDF = descargarPedido($conexionData, $claveSae, $noEmpresa, $pedidoID);
    $dataCredito = validarCreditos($conexionData, $clave);

    $credito = json_decode($dataCredito, true);
    if ($credito['success']) {
        if ($credito['conCredito'] === 'S') {
            $conCredito = "S";
        } else {
            $conCredito = "N";
        }
    }
    if (!empty($correosManuales)) {
        $correosArray = explode(';', $correosManuales);
        $correosEnviados = [];
        $correosInvalidos = [];

        foreach ($correosArray as $correo) {
            $correo = trim($correo);
            if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                // Enviar correo a cada dirección válida
                enviarCorreoPedido($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData, $claveCliente);
                $correosEnviados[] = $correo;
            } else {
                $correosInvalidos[] = $correo;
            }
        }

        if (!empty($correosEnviados)) {
            echo json_encode([
                'success' => true,
                'message' => 'Pedido enviado correctamente a: ' . implode(', ', $correosEnviados),
                'correosEnviados' => $correosEnviados,
                'correosInvalidos' => $correosInvalidos
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo enviar el pedido. Todos los correos proporcionados son inválidos: ' . implode(', ', $correosInvalidos)
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se proporcionaron direcciones de correo electrónico.'
        ]);
    }

    sqlsrv_close($conn);
}
function descargarPedido($conexionData, $claveSae, $noEmpresa, $pedidoID)
{
    $rutaPDF = descargarPedidoPdf($conexionData, $claveSae, $noEmpresa, $pedidoID);
    return $rutaPDF;
}
function obtenerPedido($pedidoId, $conn, $claveSae, $conexionData)
{

    $CVE_DOC = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT * FROM $tablaPedidos
    WHERE CVE_DOC = ?";
    $params = [$CVE_DOC];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    // Obtener los resultados
    $pedidoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($pedidoData) {
        return $pedidoData;
    } else {
        echo json_encode(['success' => false, 'message' => "Pedido no encontrado $cveDoc"]);
    }
    sqlsrv_free_stmt($stmt);
}
function obtenerDireccion($DAT_ENVIO, $conn, $claveSae, $conexionData)
{

    $tablaEnvios = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql    = "SELECT CVE_INFO, NOMBRE, CALLE
               FROM {$tablaEnvios}
               WHERE CVE_INFO = ?";
    $params = [$DAT_ENVIO];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        // Error grave: abortamos y enviamos detalles
        $errs = sqlsrv_errors();
        throw new Exception("Error al ejecutar la consulta en {$tablaEnvios}: " . print_r($errs, true));
    }

    // 3) Obtenemos el primer (y único) resultado
    $fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    // 4) Liberamos recursos
    sqlsrv_free_stmt($stmt);

    // 5) Retornamos la fila o null si no existe
    return $fila ?: null;
}
function obtenerPartidas($pedidoId, $conn, $claveSae, $conexionData)
{

    $CVE_DOC = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$CVE_DOC];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = $row;
    }
    return $partidas;
    sqlsrv_free_stmt($stmt);
}
function formatearClaveCliente($clave)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $clave = trim((string) $clave);
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($clave) === 10) {
        return $clave;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    return $clave;
}
function formatearClaveVendedor($vendedor)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $vendedor = trim((string) $vendedor);
    $vendedor = str_pad($vendedor, 5, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($vendedor) === 5) {
        return $vendedor;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $vendedor = str_pad($vendedor, 5, ' ', STR_PAD_LEFT);
    return $vendedor;
}
function obtenerNombreVendedor($vendedor, $conexionData, $claveSae)
{

    $vendedor = formatearClaveVendedor($vendedor);

    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE FROM $nombreTabla WHERE CVE_VEND = ?";
    $stmt = sqlsrv_query($conn, $sql, [$vendedor]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $nombre = $row ? $row['NOMBRE'] : '';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $nombre;
}
function validarCreditos($conexionData, $clienteId)
{
    // Validar si el ID del cliente está proporcionado
    if (!$clienteId) {
        echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
        exit;
    }

    try {
        // Configuración de conexión
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
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }
        $claveSae = $_SESSION['empresa']['claveSae'];
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Construir la consulta SQL
        $sql = "SELECT CAMPLIB9 FROM $nombreTabla WHERE CVE_CLIE = ?";
        //$sql = "SELECT CAMPLIB8 FROM $nombreTabla WHERE CVE_CLIE = ?";
        $params = [$clienteId];
        $stmt = sqlsrv_query($conn, $sql, $params);

        // Verificar si hubo errores al ejecutar la consulta
        if ($stmt === false) {
            throw new Exception('Error al ejecutar la consulta.');
        }

        // Obtener los datos del cliente
        $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$clienteData) {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
            exit;
        }
        //var_dump($clienteData);
        // Limpiar y preparar los datos para la respuesta
        $conCredito = trim($clienteData['CAMPLIB9'] ?? "");
        //$conCredito = trim($clienteData['CAMPLIB8'] ?? "");

        // Enviar respuesta con los datos del cliente
        return json_encode([
            'success' => true,
            'conCredito' => $conCredito
        ]);
    } catch (Exception $e) {
        // Manejo de errores
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        // Liberar recursos y cerrar la conexión
        if (isset($stmt)) {
            sqlsrv_free_stmt($stmt);
        }
        if (isset($conn)) {
            sqlsrv_close($conn);
        }
    }
}
function enviarCorreoPedido($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData, $claveCliente)
{
    // Obtener el id de Firestore del pedido buscado
    global $firebaseProjectId, $firebaseApiKey;

    // Construir la URL para filtrar (usa el campo idPedido y noEmpresa)
    $collection = "DATOS_PEDIDO";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (idPedido y noEmpresa)
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "idPedido"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noPedido]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $idFirebasePedido = null;

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        if (isset($resultArray[0]['document']['name'])) {
            $name = $resultArray[0]['document']['name']; // p.ej. projects/proj/databases/(default)/documents/DATOS_PEDIDO/{id}
            $parts = explode('/', $name);
            $idFirebasePedido = end($parts); // <--- ESTE ES EL ID DEL DOCUMENTO CREADO EN FIREBASE
        }
    }

    // Crear una instancia de la clase clsMail
    $mail = new clsMail();

    // Definir el remitente (si no está definido, se usa uno por defecto)
    $correoRemitente = $_SESSION['usuario']['correo'] ?? "";
    $contraseñaRemitente = $_SESSION['empresa']['contrasena'] ?? "";

    if ($correoRemitente === "" || $contraseñaRemitente === "") {
        $correoRemitente = "";
        $contraseñaRemitente = "";
    }

    $correoDestino = $correo;
    //$vendedor = obtenerNombreVendedor($vendedor, $conexionData, $claveSae);
    // Obtener el nombre de la empresa desde la sesión
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'Empresa Desconocida';

    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // URL base del servidor
    $urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "https://mdconecta.mdcloud.app/Servidor/PHP";
    //$urlBase = "http://localhost/MDConnecta/Servidor/PHP";
    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&idEnvios=" . urlencode($idFirebasePedido) . "&claveCliente=" . urlencode($claveCliente);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&claveCliente=" . urlencode($claveCliente);

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los detalles de su pedido <b>$noPedido</b>. Por favor, revíselos y confirme:</p>";
    $bodyHTML .= "<p><b>Fecha y Hora de Elaboración:</b> $fechaElaboracion</p>";
    $bodyHTML .= "<p><b>Dirección de Envío:</b> $enviarA</p>";
    $bodyHTML .= "<p><b>Vendedor:</b> $vendedor</p>";

    // Agregar tabla con detalles del pedido
    $bodyHTML .= "<table style='border-collapse: collapse; width: 100%;' border='1'>
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Total Partida</th>
                        </tr>
                    </thead>
                    <tbody>";

    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $clave = htmlspecialchars($partida['CVE_ART']);
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = htmlspecialchars($partida['CANT']);
        $totalPartida = $cantidad * $partida['PREC'];
        $total += $totalPartida;
        $IMPORTE = $total;

        $bodyHTML .= "<tr>
                        <td style='text-align: center;'>$clave</td>
                        <td>$descripcion</td>
                        <td style='text-align: right;'>$cantidad</td>
                        <td style='text-align: right;'>$" . number_format($totalPartida, 2) . "</td>
                      </tr>";

        $IMPU4 = $partida['IMPU4'];
        $desc1 = $partida['DESC1'] ?? 0;
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // `
    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total:</b> $" . number_format($IMPORTE, 2) . "</p>";

    // Botones para confirmar o rechazar el pedido
    $bodyHTML .= "<p>Confirme su pedido seleccionando una opción:</p>
                  <a href='$urlConfirmar' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar</a>
                  <a href='$urlRechazar' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Rechazar</a>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente);
    //var_dump($resultado);
    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        //echo json_encode(['success' => false, 'message' => $resultado]);
        throw new Exception("Error al enviar el correo");
    }
}
function enviarConfirmacionWhats($pedidoID, $noEmpresa, $claveSae, $conexionData, $numeroWhatsApp)
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

    $detallesPedido = obtenerPedido($pedidoID, $conn, $claveSae, $conexionData);
    //var_dump($detallesPedido);

    $partidasData = obtenerPartidas($pedidoID, $conn, $claveSae, $conexionData);
    // Extraer 'enviar a' y 'vendedor' del formulario
    $envios = obtenerDireccion($detallesPedido['DAT_ENVIO'], $conn, $claveSae, $conexionData); // Dirección de envío
    $enviarA = $envios['CALLE'];
    $vendedor = $detallesPedido['CVE_VEND']; // Número de vendedor
    $claveCliente = $detallesPedido['CVE_CLPV'];

    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $detallesPedido['FOLIO']; // Número de pedido
    /*$claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
    $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);*/

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener MAIL y EMAILPRED
    $sql = "SELECT NOMBRE FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [$clave];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['CVE_ART'];

        // Consulta SQL para obtener la descripción del producto
        $sqlProducto = "SELECT DESCR FROM $nombreTabla2 WHERE CVE_ART = ?";
        $stmtProducto = sqlsrv_query($conn, $sqlProducto, [$claveProducto]);

        if ($stmtProducto && $rowProducto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC)) {
            $partida['descripcion'] = $rowProducto['DESCR'];
        } else {
            $partida['descripcion'] = 'Descripción no encontrada'; // Manejo de error
        }

        sqlsrv_free_stmt($stmtProducto);
    }

    $fecha = $detallesPedido['FECHAELAB'];
    $fechaElaboracion = $fecha->format('Y-m-d');
    $clienteNombre = trim($clienteData['NOMBRE']);

    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';*/
    $claveCliente = $clave;
    /*$emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';*/
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/ // Interzenda
    //$emailPred = "";
    //$numeroWhatsApp = "";
    /*$emailPred = $_SESSION['usuario']['correo'];
    $numeroWhatsApp = $_SESSION['usuario']['telefono'];*/
    /*$emailPred = 'ivan.soriano@mdcloud.mx';
    $numeroWhatsApp = '+527773340218';*/
    // Obtener el id de Firestore del pedido buscado
    global $firebaseProjectId, $firebaseApiKey;

    // Construir la URL para filtrar (usa el campo idPedido y noEmpresa)
    $collection = "DATOS_PEDIDO";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (idPedido y noEmpresa)
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "idPedido"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noPedido]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $idFirebasePedido = null;
    $direccion1Contacto = null;
    //$envioWhats = false;

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        // runQuery devuelve un array con un elemento por cada match
        if (isset($resultArray[0]['document'])) {
            $doc    = $resultArray[0]['document'];
            // si quieres el ID:
            $parts  = explode('/', $doc['name']);
            $idFirebasePedido = end($parts);
            // y para tomar tu campo direccion1Contacto:
            $fields = $doc['fields'];
            $direccion1Contacto = $fields['direccion1Contacto']['stringValue'] ?? null;
            //$envioWhats = $fields['enviarWhat']['booleanValue'] ?? false;
        }
    }
    /*if(!$envioWhats){
        echo json_encode(['success' => false, 'notificacion' => true, 'message' => 'No se le notifico al cliente debido a la restriccion del envio de confirmacion por este medio.']);
        die();
    }*/
    $rutaPDF = descargarPedido($conexionData, $claveSae, $noEmpresa, $pedidoID);

    if ($numeroWhatsApp === "") {
        $numeroBandera = 1;
    } else {
        $numeroBandera = 0;
    }

    $dataCredito = validarCreditos($conexionData, $clave);
    $credito = json_decode($dataCredito, true);
    if ($credito['success']) {
        if ($credito['conCredito'] === 'S') {
            $conCredito = "S";
        } else {
            $conCredito = "N";
        }
    }
    if ($numeroWhatsApp === "") {
        $numeroBandera = 1;
    } else {
        $numeroBandera = 0;
    }
    if (isset($numeroWhatsApp)) {
        if ($numeroBandera === 0) {
            //$rutaPDFW = "https://mdconecta.mdcloud.app/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
        $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";

            //$resultadoWhatsApp = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idFirebasePedido);
            /*var_dump($resultadoWhatsApp);
            die();*/
            $resultadoWhatsApp = enviarWhatsAppConPlantillaPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idFirebasePedido, $rutaPDFW, $filename, $direccion1Contacto);
            //var_dump($resultadoWhatsApp);
            if (str_contains($resultadoWhatsApp, "error")) {
                throw new Exception("Problema al enviar mensaje de WhatsApp");
                //echo json_encode(['success' => false, 'message' => 'Problema al enviar mensaje de WhatsApp.', 'error' => $resultadoWhatsApp]);
            }
        }

        // Determinar la respuesta JSON según las notificaciones enviadas
        if ($numeroBandera === 0) {
            /// Respuesta de éxito
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => true,
                'autorizacion' => false,
                'message' => 'El pedido se envio correctamente por WhatsApp.',
            ]);
        } else {
            $numeroWhatsApp = $_SESSION['usuario']['telefono'];
            //$rutaPDFW = "https://mdconecta.mdcloud.app/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
        $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";

            //$resultadoWhatsApp = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idFirebasePedido);
            //var_dump($resultadoWhatsApp);
            $resultadoWhatsApp = enviarWhatsAppConPlantillaPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idFirebasePedido, $rutaPDFW, $filename, $direccion1Contacto);
            //$resultadoWhatsApp = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idFirebasePedido);
            if (str_contains($resultadoWhatsApp, "error")) {
                throw new Exception("Problema al enviar mensaje de WhatsApp");
                //echo json_encode(['success' => false, 'message' => 'Problema al enviar mensaje de WhatsApp.', 'error' => $resultadoWhatsApp]);
            }
            echo json_encode(['success' => false, 'notificacion' => true, 'message' => 'Pedido Enviado, el Cliente no Tiene un Correo y WhatsApp para notificar.']);
            //die();
        }
    } else {
        echo json_encode(['success' => false, 'datos' => true, 'message' => 'El cliente no Tiene un Correo y WhatsApp Válido Registrado.']);
        //die();
    }
}
function enviarWhatsAppConPlantilla($numero, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idEnvios)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idEnvios);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);

    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'] ?? $partida['CVE_ART'];
        $cantidad = $partida['cantidad'] ?? $partida['CANT'];
        $precioUnitario = $partida['precioUnitario'] ?? $partida['PREC'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $IMPORTE = $total;
        $productosStr .= "$producto - $cantidad unidades, ";

        $IMPU4 = $partida['iva'] ?? $partida['IMPU4'];
        $desc1 = $partida['descuento'] ?? $partida['DESC1'];

        $desProcentaje = ($desc1 / 100);

        $DES = $totalPartida * $desProcentaje;

        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);

        $IMP_TOT4 += $IMP_T4;
    }

    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    // ✅ Datos para WhatsApp API con botones de Confirmar y Rechazar
    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "confirmar_pedido", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
            "components" => [
                [
                    "type" => "header",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre] // 📌 Encabezado dinámico
                    ]
                ],
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $noPedido], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $productosStr], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($IMPORTE, 2)] // 📌 Precio total
                    ]
                ],
                // ✅ Botón Confirmar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 0,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlConfirmar] // 📌 URL dinámica
                    ]
                ],
                // ✅ Botón Rechazar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 1,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlRechazar] // 📌 URL dinámica
                    ]
                ]
            ]
        ]
    ];

    // ✅ Verificar JSON antes de enviarlo
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

    // ✅ Revisar si el JSON contiene `messaging_product`
    if (!isset($data['messaging_product'])) {
        error_log("ERROR: 'messaging_product' no está en la solicitud.");
        return false;
    }

    // ✅ Enviar solicitud a WhatsApp API con headers correctos
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
    ]);

    $result = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    error_log("WhatsApp Response: " . $result);
    error_log("HTTP Status Code: " . $http_code);

    return $result;
}
function enviarWhatsAppConPlantillaPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idEnvios, $rutaPDFW, $filename)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Construir la URL para filtrar (usa el campo idPedido y noEmpresa)
    $collection = "DATOS_PEDIDO";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (idPedido y noEmpresa)
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "idPedido"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noPedido]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $idFirebasePedido = null;
    $direccion1Contacto = null;

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        // runQuery devuelve un array con un elemento por cada match
        if (isset($resultArray[0]['document'])) {
            $doc    = $resultArray[0]['document'];
            // si quieres el ID:
            $parts  = explode('/', $doc['name']);
            $idFirebasePedido = end($parts);
            // y para tomar tu campo direccion1Contacto:
            $fields = $doc['fields'];
            $direccion1Contacto = $fields['direccion1Contacto']['stringValue'] ?? null;
        }
    }

    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    //"Pedido.php?accion=confirmar&pedidoId=" . 
    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idFirebasePedido);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);

    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'] ?? $partida['CVE_ART'];
        $cantidad = $partida['cantidad'] ?? $partida['CANT'];
        $precioUnitario = $partida['precioUnitario'] ?? $partida['PREC'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $IMPORTE = $total;
        $productosStr .= "$producto - $cantidad unidades, ";

        $IMPU4 = $partida['iva'] ?? $partida['IMPU4'];
        $desc1 = $partida['descuento'] ?? $partida['DESC1'];

        $desProcentaje = ($desc1 / 100);

        $DES = $totalPartida * $desProcentaje;

        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);

        $IMP_TOT4 += $IMP_T4;
    }

    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "template",
        "template" => [
            //"name" => "new_confirmar_pedido_pdf", // 📌 Nombre EXACTO en Meta Business Manager
            "name" => "confirmar_pedido_pdf", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
            "components" => [
                [
                    "type" => "header",
                    "parameters" => [
                        [
                            "type" => "document",
                            "document" => [
                                "link" => $rutaPDFW,
                                "filename" => $filename
                            ]
                        ]
                    ]

                ],
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $noPedido], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $productosStr], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($IMPORTE, 2)], // 📌 Lista de productos
                        ["type" => "text", "text" => $direccion1Contacto], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($DES_TOT, 2)], // 📌 Precio total
                        ["type" => "text", "text" => "$" . number_format($IMP_TOT4, 2)], // 📌 Lista de productos
                    ]
                ],
                // ✅ Botón Confirmar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 0,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlConfirmar] // 📌 URL dinámica
                    ]
                ],
                // ✅ Botón Rechazar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 1,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlRechazar] // 📌 URL dinámica
                    ]
                ]
            ]
        ]
    ];
    // ✅ Verificar JSON antes de enviarlo
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

    // ✅ Revisar si el JSON contiene `messaging_product`
    if (!isset($data['messaging_product'])) {
        error_log("ERROR: 'messaging_product' no está en la solicitud.");
        return false;
    }

    // ✅ Enviar solicitud a WhatsApp API con headers correctos
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
    ]);

    $result = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    error_log("WhatsApp Response: " . $result);
    error_log("HTTP Status Code: " . $http_code);

    return $result;
}



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
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae  = $_SESSION['empresa']['claveSae'];

        // Obtener correos del POST
        $correosManuales = $_POST['correos'] ?? '';

        // Obtenemos la conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae, $logFile);
        if (!$conexionResult['success']) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "Error al conectar a Firebase";
            exit;
        }
        $conexionData = $conexionResult['data'];

        $pedidoID = $_POST['pedidoID'];
        $pedidoID = trim($pedidoID); // Elimina espacios en blanco al inicio y al final
        //var_dump("Pedido: ", $pedidoID);

        $pedidoIDFormato = ltrim($pedidoID, '0'); // Ahora sí elimina los ceros iniciales
        //var_dump("Pedido Formateado", $pedidoIDFormato);

        $pedidoAutorisado = verificarStatusPedido($pedidoIDFormato, $firebaseProjectId, $firebaseApiKey, $noEmpresa, $conexionData, $logFile);
        //die();
        if ($pedidoAutorisado) {
            //enviarConfirmacion($pedidoID, $noEmpresa, $claveSae, $conexionData);
            enviarConfirmacionCorreo($pedidoID, $noEmpresa, $claveSae, $conexionData);
            //echo json_encode(['success' => true, 'message' => 'Pedido Autorizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Este pedido aun no ha sido autorizado.']);
        }
        break;
    case 2:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae  = $_SESSION['empresa']['claveSae'];
        // Obtenemos la conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae, $logFile);
        if (!$conexionResult['success']) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "Error al conectar a Firebase";
            exit;
        }
        $conexionData = $conexionResult['data'];
        $telefono = $_POST['telefono'];
        $pedidoID = $_POST['pedidoID'];
        $pedidoID = trim($pedidoID); // Elimina espacios en blanco al inicio y al final
        //var_dump("Pedido: ", $pedidoID);

        $pedidoIDFormato = ltrim($pedidoID, '0'); // Ahora sí elimina los ceros iniciales
        //var_dump("Pedido Formateado", $pedidoIDFormato);

        $pedidoAutorisado = verificarStatusPedido($pedidoIDFormato, $firebaseProjectId, $firebaseApiKey, $noEmpresa, $conexionData, $logFile);
        if ($pedidoAutorisado) {
            //enviarConfirmacion($pedidoID, $noEmpresa, $claveSae, $conexionData);
            enviarConfirmacionWhats($pedidoID, $noEmpresa, $claveSae, $conexionData, $telefono);
            //echo json_encode(['success' => true, 'message' => 'Pedido Autorizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Este pedido aun no ha sido autorizado.']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
