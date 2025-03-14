<?php
//ejemplo factura cfdi 4.0
// Se desactivan los mensajes de debug
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
//error_reporting(E_ALL);

// Se especifica la zona horaria
date_default_timezone_set('America/Mexico_City');

// Se incluye el SDK
require_once '../../sdk2.php';
function datosCliente($clie)
{
    $serverName = "34.29.174.237";
    $connectionInfo = [
        "Database" => 'mdc_sae01',
        "UID" => 'sa',
        "PWD" => 'Green2580a.',
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $claveSae = '01';
    $nombreTabla   = "[mdc_sae01].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CLAVE = ?";
    $params = [$clie];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($clienteData) {
        return $clienteData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function datosPedido($cve_doc)
{
    $serverName = "34.29.174.237";
    $connectionInfo = [
        "Database" => 'mdc_sae01',
        "UID" => 'sa',
        "PWD" => 'Green2580a.',
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $claveSae = '01';
    $nombreTabla  = "[mdc_sae01].[dbo].[FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$cve_doc];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    // Obtener los resultados
    $pedidoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($pedidoData) {
        return $pedidoData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function datosPartida($cve_doc)
{
    $serverName = "34.29.174.237";
    $connectionInfo = [
        "Database" => 'mdc_sae01',
        "UID" => 'sa',
        "PWD" => 'Green2580a.',
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $claveSae = '01';
    $nombreTabla  = "[mdc_sae01].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$cve_doc];

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
    sqlsrv_close($conn);
}
function datosProcuto($CVE_ART){
    $serverName = "34.29.174.237";
    $connectionInfo = [
        "Database" => 'mdc_sae01',
        "UID" => 'sa',
        "PWD" => 'Green2580a.',
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $claveSae = '01';
    $nombreTabla  = "[mdc_sae01].[dbo].[INVE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_ART = ?";
    $params = [$CVE_ART];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $productoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($productoData) {
        return $productoData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

$cve_doc = '          0000018625';

$pedidoData = datosPedido($cve_doc);
$productosData = datosPartida($cve_doc);

$clienteData = datosCliente($pedidoData['CVE_CLPV']);
// Se especifica la version de CFDi 4.0
$datos['version_cfdi'] = '4.0';
// Ruta del XML Timbrado
$datos['cfdi'] = '../../timbrados/cfdi_ejemplo_factura4.xml';

// Ruta del XML de Debug
$datos['xml_debug'] = '../../timbrados/ejemplo.xml';

// Credenciales de Timbrado
$datos['PAC']['usuario'] = 'DEMO700101XXX';
$datos['PAC']['pass'] = 'DEMO700101XXX';
$datos['PAC']['produccion'] = 'NO';

// Rutas y clave de los CSD
$datos['conf']['cer'] = '../../certificados/EKU9003173C9.cer';
$datos['conf']['key'] = '../../certificados/EKU9003173C9.key';
$datos['conf']['pass'] = '12345678a';

// Datos de la Factura || $pedidoData['']
$datos['factura']['condicionesDePago'] = $pedidoData['CONDICION'];
$datos['factura']['descuento'] = $pedidoData['DES_TOT'];
$datos['factura']['fecha_expedicion'] = $pedidoData['FECHA_DOC']->format('Y-m-d H:i:s');
$datos['factura']['folio'] = $pedidoData['FOLIO'];
$datos['factura']['forma_pago'] = $pedidoData['FORMADEPAGOSAT'];
$datos['factura']['LugarExpedicion'] = '45079';
$datos['factura']['metodo_pago'] = $pedidoData['METODODEPAGO'];
$datos['factura']['moneda'] = 'MXN';
$datos['factura']['serie'] = $pedidoData['SERIE'];
$datos['factura']['subtotal'] = $pedidoData['CANT_TOT'];
$datos['factura']['tipocambio'] = $pedidoData['TIPCAMB'];
$datos['factura']['tipocomprobante'] = 'I';
$datos['factura']['total'] = $pedidoData['IMPORTE'];
$datos['factura']['Exportacion'] = '01';

// Datos del Emisor
$datos['emisor']['rfc'] = 'LALDS2345'; //RFC DE PRUEBA
$datos['emisor']['nombre'] = 'SUN ARROW';  // EMPRESA DE PRUEBA
$datos['emisor']['RegimenFiscal'] = '626';

// Datos del Receptor $clienteData['']
$datos['receptor']['rfc'] = $clienteData['RFC'];
$datos['receptor']['nombre'] = $clienteData['NOMBRE'];
$datos['receptor']['UsoCFDI'] = $clienteData['USO_CFDI'];
$datos['receptor']['DomicilioFiscalReceptor'] = '65000';
$datos['receptor']['RegimenFiscalReceptor'] = $clienteData['REG_FISC'];

foreach ($productosData as $producto) {
    $dataProduc = datosProcuto($producto['CVE_ART']);
    
    $concepto = [];
    $concepto['cantidad'] =  $producto['CANT'];
    $concepto['unidad'] =  $producto['UNI_VENTA'];
    $concepto['ID'] = $producto['CVE_ART'];
    $concepto['descripcion'] =  $producto['DESCR_ART'];
    $concepto['valorunitario'] = $producto['PREC'];
    $concepto['importe'] = $producto['TOT_PARTIDA'];
    $concepto['ClaveProdServ'] = $dataProduc['CVE_PRODSERV'];
    $concepto['ClaveUnidad'] = $dataProduc['CVE_UNIDAD'];
    $concepto['ObjetoImp'] = '02';

    $concepto['Impuestos']['Traslados'][0]['Base'] = $producto['TOT_PARTIDA'];
    $concepto['Impuestos']['Traslados'][0]['Impuesto'] = '002';
    $concepto['Impuestos']['Traslados'][0]['TipoFactor'] = 'Tasa';
    $concepto['Impuestos']['Traslados'][0]['TasaOCuota'] = $producto['IMPU4'];
    $concepto['Impuestos']['Traslados'][0]['Importe'] = $producto['TOTIMP4'];

    $datos['conceptos'][] = $concepto;
}

// Se agregan los Impuestos
$datos['impuestos']['translados'][0]['Base'] = $pedidoData['CAN_TOT'];
$datos['impuestos']['translados'][0]['impuesto'] = '002';
$datos['impuestos']['translados'][0]['tasa'] = '0.160000';
$datos['impuestos']['translados'][0]['importe'] = $pedidoData['IMPORTE'];
$datos['impuestos']['translados'][0]['TipoFactor'] = 'Tasa';

$datos['impuestos']['TotalImpuestosTrasladados'] = $pedidoData['IMPORTE'];

//echo "<pre>";print_r($datos);echo "</pre>";
$res = mf_genera_cfdi4($datos);
//$res = mf_default($datos);
//var_dump($res);
///////////    MOSTRAR RESULTADOS DEL ARRAY $res   ///////////

echo "<h1>Respuesta Generar XML y Timbrado</h1>";
foreach ($res as $variable => $valor) {
    echo "<hr>";
    $valor = htmlentities($valor);
    $valor = str_replace('&lt;br/&gt;', '<br/>', $valor);
    //echo "<b>[$variable]=</b>$valor<hr>";
}
