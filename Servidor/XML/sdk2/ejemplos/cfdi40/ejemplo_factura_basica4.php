<?php
require '../../../../PHP/firebase.php';
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
function datosProcuto($CVE_ART)
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
function datosEmpresa()
{
    global $firebaseProjectId, $firebaseApiKey;
    $noEmpresa = '02';

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
//$cve_doc = '          0000018784';
//$cve_doc = '          0000018720';
$cve_doc = '          0000018725';
//No hacer pruebas con 0000018758 en adelante

$pedidoData = datosPedido($cve_doc);
$productosData = datosPartida($cve_doc);
$clienteData = datosCliente($pedidoData['CVE_CLPV']);
$empresaData = datosEmpresa();
// Se especifica la version de CFDi 4.0
$datos['version_cfdi'] = '4.0';
// Ruta del XML Timbrado
$datos['cfdi'] = '../../timbrados/cfdi_' . urlencode($clienteData['NOMBRE']) . '.xml';

// Ruta del XML de Debug
$datos['xml_debug'] = '../../timbrados/xml_' . urlencode($clienteData['NOMBRE']) . '.xml';

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
if(isset($pedidoData['DES_TOT'])){
    $datos['factura']['descuento'] = $pedidoData['DES_TOT'] ?? 0;
}
//$datos['factura']['fecha_expedicion'] = $pedidoData['FECHA_DOC']->format('Y-m-d H:i:s');
$datos['factura']['fecha_expedicion'] = "AUTO";
$datos['factura']['folio'] = $pedidoData['FOLIO'];
$datos['factura']['LugarExpedicion'] = $empresaData['codigoPostal'];
$datos['factura']['metodo_pago'] = $pedidoData['METODODEPAGO'];
if ($pedidoData['METODODEPAGO'] === 'PPD') {
    $datos['factura']['forma_pago'] = '99';
} else {
    $datos['factura']['forma_pago'] = $pedidoData['FORMADEPAGOSAT'];
}
$datos['factura']['moneda'] = 'MXN';
$datos['factura']['serie'] = $pedidoData['SERIE'];
$datos['factura']['subtotal'] = sprintf('%.2f', round($pedidoData['CAN_TOT'], 2));
$datos['factura']['tipocambio'] = $pedidoData['TIPCAMB'];
$datos['factura']['tipocomprobante'] = 'I';
$datos['factura']['total'] = $pedidoData['IMPORTE'];
//$datos['factura']['total'] = sprintf('%.2f', $pedidoData['IMPORTE']);
$datos['factura']['Exportacion'] = '01';

// Datos del Emisor
$datos['emisor']['rfc'] = 'EKU9003173C9'; //RFC DE PRUEBA
$datos['emisor']['nombre'] = 'ESCUELA KEMPER URGATE';  // EMPRESA DE PRUEBA
//$datos['emisor']['RegimenFiscal'] = '626';
$regimenStr = $empresaData['regimenFiscal'];
if (preg_match('/^(\d+)/', $regimenStr, $matches)) {
    $datos['emisor']['RegimenFiscal'] = $matches[1];
} else {
    $datos['emisor']['RegimenFiscal'] = $regimenStr;
}

/*
// Datos del Emisor
$datos['emisor']['rfc'] = $empresaData['rfc']; //RFC DE PRUEBA
$datos['emisor']['nombre'] = $empresaData['razonSocial'];  // EMPRESA DE PRUEBA
$regimenStr = $empresaData['regimenFiscal'];
if (preg_match('/^(\d+)/', $regimenStr, $matches)) {
    $datos['emisor']['RegimenFiscal'] = $matches[1];
} else {
    $datos['emisor']['RegimenFiscal'] = $regimenStr;
}
*/

// Datos del Receptor $clienteData['']
$datos['receptor']['rfc'] = $clienteData['RFC'];
$datos['receptor']['nombre'] = $clienteData['NOMBRE'];
$datos['receptor']['UsoCFDI'] = $clienteData['USO_CFDI'];
$datos['receptor']['DomicilioFiscalReceptor'] = $clienteData['CODIGO'];
$datos['receptor']['RegimenFiscalReceptor'] = $clienteData['REG_FISC'];
// $producto[''] $dataProduc['']
$IMPU = 0;
$DES = 0;
$Sum = 0;
foreach ($productosData as $producto) {
    $dataProduc = datosProcuto($producto['CVE_ART']);
    $concepto = [];
     // Calcular la base imponible restando el descuento, si lo hay
     $baseImpuesto = $producto['TOT_PARTIDA'];
     if (isset($producto['DESC1'])) {
         $precioDes = $producto['TOT_PARTIDA'] * ($producto['DESC1'] / 100);
         $baseImpuesto = $producto['TOT_PARTIDA'] - $precioDes;
     }else{
        $baseImpuesto = $producto['TOT_PARTIDA'];
     }
    $concepto['cantidad'] =  $producto['CANT'];
    $concepto['unidad'] =  $producto['UNI_VENTA'];
    $concepto['ID'] = $producto['CVE_ART'];
    $concepto['descripcion'] =  $dataProduc['DESCR'];
    $concepto['valorunitario'] = $producto['PREC'];
    $concepto['importe'] = sprintf('%.3f', $producto['TOT_PARTIDA']); //'%.2f'
    if(isset($pedidoData['DES_TOT'])){
        //$concepto['Descuento'] = round($precioDes, 2);  //Original
        $concepto['Descuento'] = sprintf('%.3f', $precioDes);
    }
    $concepto['ClaveProdServ'] = $dataProduc['CVE_PRODSERV'];
    $concepto['ClaveUnidad'] = $dataProduc['CVE_UNIDAD'];
    $concepto['ObjetoImp'] = '02';

    $concepto['Impuestos']['Traslados'][0]['Base'] = sprintf('%.3f', $baseImpuesto);
    $concepto['Impuestos']['Traslados'][0]['Impuesto'] = '002';
    $concepto['Impuestos']['Traslados'][0]['TipoFactor'] = 'Tasa';
    $concepto['Impuestos']['Traslados'][0]['TasaOCuota'] = sprintf('%.6f', $producto['IMPU4'] / 100);
    //$concepto['Impuestos']['Traslados'][0]['Importe'] = sprintf('%.2f', round($baseImpuesto * ($producto['IMPU4'] / 100), 2));        //Original
    $concepto['Impuestos']['Traslados'][0]['Importe'] = sprintf('%.3f', ($baseImpuesto * ($producto['IMPU4'] / 100)));
    
    $IMPU = $IMPU + ($baseImpuesto * ($producto['IMPU4'] / 100));    
    $DES = $DES + $precioDes;
    $Sum = $Sum + $precioDes;
    $datos['conceptos'][] = $concepto;
}
// Se agregan los Impuestos
$datos['impuestos']['translados'][0]['Base'] = sprintf('%.2f', $pedidoData['CAN_TOT'] - $DES);
$datos['impuestos']['translados'][0]['impuesto'] = '002';
$datos['impuestos']['translados'][0]['tasa'] = '0.160000';
$datos['impuestos']['translados'][0]['importe'] = sprintf('%.2f',$IMPU); //Original sin sprintf
$datos['impuestos']['translados'][0]['TipoFactor'] = 'Tasa';

//$datos['impuestos']['TotalImpuestosTrasladados'] = round($IMPU, 2);   //Original
$datos['impuestos']['TotalImpuestosTrasladados'] = sprintf('%.2f', $IMPU);

//$datos['factura']['total'] = ($subDescuento + round($IMPU, 2));
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
    echo "<b>[$variable]=</b>$valor<hr>";
}

/*
$datos['conf']['cer'] =base64_encode(file_get_contents($empresa['archivo_cer']));
$datos['conf']['key'] =base64_encode(file_get_contents($empresa['archivo_key']));
base64_encode(file_get_contents($empresa['archivo_cer']));
*/