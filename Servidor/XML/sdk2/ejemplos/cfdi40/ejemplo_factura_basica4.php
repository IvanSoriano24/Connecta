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
function obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey)
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
    // Busca el documento donde coincida el campo `claveSae`
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
                    'claveSae' => $fields['claveSae']['stringValue']
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function datosCliente($clie, $claveSae, $conexionData)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
function datosPedido($cve_doc, $claveSae, $conexionData)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
        echo json_encode(['success' => false, 'message' => "Pedido no encontrado $cve_doc"]);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function datosPartida($cve_doc, $claveSae, $conexionData)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
function datosProcuto($CVE_ART, $claveSae, $conexionData)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }


    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[INVE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
function datosEmpresa($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{

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
function cfdi($cve_doc, $noEmpresa, $claveSae)
{
    global $firebaseProjectId, $firebaseApiKey;

    $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);
    if (!$conexionResult['success']) {
        echo json_encode($conexionResult);
        die();
    }
    $conexionData = $conexionResult['data'];
    $cve_doc = str_pad($cve_doc, 10, '0', STR_PAD_LEFT);
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);

    $pedidoData = datosPedido($cve_doc, $claveSae, $conexionData);
    $productosData = datosPartida($cve_doc, $claveSae, $conexionData);
    $clienteData = datosCliente($pedidoData['CVE_CLPV'], $claveSae, $conexionData);
    $empresaData = datosEmpresa($noEmpresa, $firebaseProjectId, $firebaseApiKey);

    // Se especifica la version de CFDi 4.0
    $datos['version_cfdi'] = '4.0';
    // Ruta del XML Timbrado
    $datos['cfdi'] = '../../timbrados/cfdi_' . urlencode($clienteData['NOMBRE']) . '_' . urlencode($pedidoData['FOLIO']) .  '.xml';

    // Ruta del XML de Debug
    $datos['xml_debug'] = '../../timbrados/xml_' . urlencode($clienteData['NOMBRE']) . '_' . urlencode($pedidoData['FOLIO']) .  '.xml';

    /*
    // Credenciales de Timbrado
    $datos['PAC']['usuario'] = $empresaData['rfc'];
    $datos['PAC']['pass'] = $empresaData['rfc'];
    $datos['PAC']['produccion'] = 'SI';
    */
    // Credenciales de Timbrado
    $datos['PAC']['usuario'] = 'DEMO700101XXX';
    $datos['PAC']['pass'] = 'DEMO700101XXX';
    $datos['PAC']['produccion'] = 'NO';

    // Rutas y clave de los CSD
    $datos['conf']['cer'] = '../../certificados/EKU9003173C9.cer';
    $datos['conf']['key'] = '../../certificados/EKU9003173C9.key';
    $datos['conf']['pass'] = '12345678a';
    /*$datos['conf']['cer'] = '../../certificadosM/00001000000513872236.cer';
    $datos['conf']['key'] = '../../certificadosM/CSD_unidad_LUHM920412GU2_20220708_132000.key';
    $datos['conf']['pass'] = 'CUSAr279';*/

    // Datos de la Factura || $pedidoData['']
    $datos['factura']['condicionesDePago'] = $pedidoData['CONDICION'];
    if (isset($pedidoData['DES_TOT'])) {
        $datos['factura']['descuento'] = sprintf('%.2f', $pedidoData['DES_TOT'] ?? 0);
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
    //$datos['factura']['subtotal'] = sprintf('%.2f', round($pedidoData['CAN_TOT'], 2));    //Original
    $datos['factura']['subtotal'] = sprintf('%.2f', $pedidoData['CAN_TOT']);
    $datos['factura']['tipocambio'] = $pedidoData['TIPCAMB'];
    $datos['factura']['tipocomprobante'] = 'I';
    $datos['factura']['total'] = sprintf('%.2f', $pedidoData['IMPORTE']);
    //$datos['factura']['total'] = sprintf('%.2f', $pedidoData['IMPORTE']);
    $datos['factura']['Exportacion'] = '01';

    // Datos del Emisor
    $datos['emisor']['rfc'] = 'EKU9003173C9'; //RFC DE PRUEBA
    $datos['emisor']['nombre'] = 'ESCUELA KEMPER URGATE';  // EMPRESA DE PRUEBA
    $datos['emisor']['RegimenFiscal'] = '626';
    /*$datos['emisor']['rfc'] = 'LUHM920412GU2'; //RFC DE PRUEBA
    $datos['emisor']['nombre'] = 'MARCOS LUNA HERNANDEZ';  // EMPRESA DE PRUEBA
    $datos['emisor']['RegimenFiscal'] = '612';*/
    /*$regimenStr = $empresaData['regimenFiscal'];
    if (preg_match('/^(\d+)/', $regimenStr, $matches)) {
        $datos['emisor']['RegimenFiscal'] = $matches[1];
    } else {
        $datos['emisor']['RegimenFiscal'] = $regimenStr;
    }*/
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
        $dataProduc = datosProcuto($producto['CVE_ART'], $claveSae, $conexionData);
        $concepto = [];
        // Calcular la base imponible restando el descuento, si lo hay
        $baseImpuesto = $producto['TOT_PARTIDA'];
        if (isset($producto['DESC1'])) {
            $precioDes = $producto['TOT_PARTIDA'] * ($producto['DESC1'] / 100);
            $baseImpuesto = $producto['TOT_PARTIDA'] - $precioDes;
        } else {
            $baseImpuesto = $producto['TOT_PARTIDA'];
        }
        $concepto['cantidad'] =  $producto['CANT'];
        $concepto['unidad'] =  $producto['UNI_VENTA'];
        $concepto['ID'] = $producto['CVE_ART'];
        $concepto['descripcion'] =  $dataProduc['DESCR'];
        $concepto['valorunitario'] = $producto['PREC'];
        $concepto['importe'] = sprintf('%.3f', $producto['TOT_PARTIDA']); //'%.2f'
        if (isset($pedidoData['DES_TOT'])) {
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
    $datos['impuestos']['translados'][0]['importe'] = sprintf('%.2f', $IMPU); //Original sin sprintf
    $datos['impuestos']['translados'][0]['TipoFactor'] = 'Tasa';

    //$datos['impuestos']['TotalImpuestosTrasladados'] = round($IMPU, 2);   //Original
    $datos['impuestos']['TotalImpuestosTrasladados'] = sprintf('%.2f', $IMPU);

    //echo "<pre>";print_r($datos);echo "</pre>";
    $res = mf_genera_cfdi4($datos);
    //var_dump($res);
    /*$res = mf_default($datos);
    var_dump($res);*/

    if (isset($res['codigo_mf_numero']) && $res['codigo_mf_numero'] == 0) {
        header('Content-Type: application/json');
        echo json_encode([
            "Succes" => true
        ]);
        return;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            "Succes" => false,
            "Problema" => $res['mensaje_original_pac_json']
            //"Problema" => $res['codigo_mf_texto']
        ]);
        return;
    }
    ///////////    MOSTRAR RESULTADOS DEL ARRAY $res   ///////////
    echo "<h1>Respuesta Generar XML y Timbrado</h1>";
    foreach ($res as $variable => $valor) {
        echo "<hr>";
        $valor = htmlentities($valor);
        $valor = str_replace('&lt;br/&gt;', '<br/>', $valor);
        echo "<b>[$variable]=</b>$valor<hr>";
    }
    //return true;
}
//http://localhost/MDConnecta/Servidor/PHPverificarFactura.php
//http://localhost/MDConnecta/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php?cve_doc=18631&noEmpresa=02&claveSae=02
/*$cve_doc = $_POST['cve_doc'];
$noEmpresa = $_POST['noEmpresa'];
$claveSae = $_POST['claveSae'];*/
$cve_doc = '18633';
$noEmpresa = '02';
$claveSae = '02';
cfdi($cve_doc, $noEmpresa, $claveSae);
/*
$datos['conf']['cer'] =base64_encode(file_get_contents($empresa['archivo_cer']));
$datos['conf']['key'] =base64_encode(file_get_contents($empresa['archivo_key']));
base64_encode(file_get_contents($empresa['archivo_cer']));
*/