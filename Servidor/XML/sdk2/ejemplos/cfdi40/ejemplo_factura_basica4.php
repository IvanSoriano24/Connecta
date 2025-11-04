<?php
// require_once '../../../../PHP/firebase.php';
//ejemplo factura cfdi 4.0
// Se desactivan los mensajes de debug
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
//error_reporting(E_ALL);

// Se especifica la zona horaria
date_default_timezone_set('America/Mexico_City');

// Se incluye el SDK
require_once __DIR__ . '/../../sdk2.php';

function datosClienteC($clie, $claveSae, $conexionData, $conn)
{
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
        echo json_encode(['success' => false, 'message' => "Cliente no encontrado $clie"]);
    }
    sqlsrv_free_stmt($stmt);
}
function datosPedidoC($cve_doc, $claveSae, $conexionData, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[FACTF"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
        echo json_encode(['success' => false, 'message' => "Pedido/Factura no encontrado $cve_doc"]);
    }
    sqlsrv_free_stmt($stmt);
}
function datosPartidaC($cve_doc, $claveSae, $conexionData, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
}
function datosProcuto($CVE_ART, $claveSae, $conexionData, $conn)
{
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
}
function datosEmpresaC($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    // Endpoint de runQuery
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Filtrar por noEmpresa == $noEmpresa
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => "EMPRESAS"]
            ],
            "where" => [
                "fieldFilter" => [
                    "field" => ["fieldPath" => "noEmpresa"],
                    "op"    => "EQUAL",
                    "value" => ["integerValue" => (int)$noEmpresa]
                ]
            ],
            "limit" => 1
        ]
    ], JSON_UNESCAPED_SLASHES);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false; // Error en la petición
    }

    $rows = json_decode($response, true);
    if (!is_array($rows)) {
        return false; // Respuesta inesperada
    }

    foreach ($rows as $row) {
        if (!isset($row['document']['fields'])) continue;

        $fields = $row['document']['fields'];

        return [
            'noEmpresa'     => isset($fields['noEmpresa']['integerValue']) ? (int)$fields['noEmpresa']['integerValue'] : null,
            'id'            => $fields['id']['stringValue'] ?? null,
            'razonSocial'   => $fields['razonSocial']['stringValue'] ?? null,
            'rfc'           => $fields['rfc']['stringValue'] ?? null,
            'regimenFiscal' => $fields['regimenFiscal']['stringValue'] ?? null,
            'calle'         => $fields['calle']['stringValue'] ?? null,
            'numExterior'   => $fields['numExterior']['stringValue'] ?? null,
            'numInterior'   => $fields['numInterior']['stringValue'] ?? null,
            'entreCalle'    => $fields['entreCalle']['stringValue'] ?? null,
            'colonia'       => $fields['colonia']['stringValue'] ?? null,
            'referencia'    => $fields['referencia']['stringValue'] ?? null,
            'pais'          => $fields['pais']['stringValue'] ?? null,
            'estado'        => $fields['estado']['stringValue'] ?? null,
            'municipio'     => $fields['municipio']['stringValue'] ?? null,
            'codigoPostal'  => $fields['codigoPostal']['stringValue'] ?? null,
            'poblacion'     => $fields['poblacion']['stringValue'] ?? null,
            'keyEncValue'   => $fields['keyEncValue']['stringValue'] ?? null,
            'keyEncIv'      => $fields['keyEncIv']['stringValue'] ?? null,
        ];
    }

    return false; // No se encontró la empresa
}
function decryptValue(string $b64Cipher, string $b64Iv): string
{
    $key = FIREBASE_CRYPT_KEY;
    $iv = base64_decode($b64Iv);
    $cipher = base64_decode($b64Cipher);
    return openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}
function cfdi($cve_doc, $noEmpresa, $claveSae, $facturaID, $conn, $conexionData, $firebaseProjectId, $firebaseApiKey) {
    if (empty($facturaID)) {
        header('Content-Type: application/json');
        return json_encode([
            'success'  => false,
            'Problema' => "Factura no Valida $facturaID"
        ]);
    }

    /*$facturaID = str_pad($factura, 10, '0', STR_PAD_LEFT);
    $facturaID = str_pad($facturaID, 20, ' ', STR_PAD_LEFT);*/
    $serverName = $conexionData['host'];
    $pedidoData = datosPedidoC($facturaID, $claveSae, $conexionData, $conn);
    $productosData = datosPartidaC($facturaID, $claveSae, $conexionData, $conn);
    $clienteData = datosClienteC($pedidoData['CVE_CLPV'], $claveSae, $conexionData, $conn);
    $empresaData = datosEmpresaC($noEmpresa, $firebaseProjectId, $firebaseApiKey);
    //var_dump($empresaData);
    $password = decryptValue($empresaData['keyEncValue'], $empresaData['keyEncIv']);
    // Se especifica la version de CFDi 4.0
    $datos['version_cfdi'] = '4.0';
    // Ruta del XML Timbrado
    $datos['cfdi'] = __DIR__ . '/../../timbrados/cfdi_' . urlencode($clienteData['NOMBRE']) . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $facturaID) . '.xml';
    //var_dump($datos['cfdi']);

    // Ruta del XML de Debug
    $datos['xml_debug'] = __DIR__ . '/../../timbrados/xml_' . urlencode($clienteData['NOMBRE']) . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $facturaID) . '.xml';
    //$datos['xml_debug']='../../timbrados/sin_timbrar_ejemplo_factura4.xml';


    // Credenciales de Timbrado
    $datos['PAC']['usuario'] = $empresaData['rfc'];
    $datos['PAC']['pass'] = $empresaData['rfc'];
    //$datos['PAC']['produccion'] = 'SI';
    $datos['PAC']['produccion'] = 'NO';

    // Credenciales de Timbrado
    /*$datos['PAC']['usuario'] = 'DEMO700101XXX';
    $datos['PAC']['pass'] = 'DEMO700101XXX';
    $datos['PAC']['produccion'] = 'NO';*/

    // Rutas y clave de los CSD
    /*$datos['conf']['cer'] = '../../certificados/escuela/EKU9003173C9.cer';
    $datos['conf']['key'] = '../../certificados/escuela/EKU9003173C9.key';
    $datos['conf']['pass'] = '12345678a';*/
    /*$datos['conf']['cer'] = '../../certificados/2/00001000000513872236.cer';
    $datos['conf']['key'] = '../../certificados/2/CSD_unidad_LUHM920412GU2_20220708_132000.key';
    $datos['conf']['pass'] = 'CUSAr279';*/
    $locacionArchivos = __DIR__ . "/../../certificados/$noEmpresa/";

    // glob devuelve un array, así que tomamos sólo el primer elemento
    $archivoCerArray = glob($locacionArchivos . "{*.cer,*/*.cer}", GLOB_BRACE);
    $archivoKeyArray = glob($locacionArchivos . "{*.key,*/*.key}", GLOB_BRACE);

    $cerPath = $archivoCerArray[0] ?? null;
    $keyPath = $archivoKeyArray[0] ?? null;

    $datos['conf']['cer']  = $cerPath;
    $datos['conf']['key']  = $keyPath;
    $datos['conf']['pass'] = $password;

    // Datos de la Factura || $pedidoData['']
    $datos['factura']['condicionesDePago'] = $pedidoData['CONDICION'];
    if (isset($pedidoData['DES_TOT'])) {
        $datos['factura']['descuento'] = sprintf('%.2f', $pedidoData['DES_TOT'] ?? 0);
    }
    //$datos['factura']['fecha_expedicion'] = $pedidoData['FECHA_DOC']->format('Y-m-d H:i:s');
    $datos['factura']['fecha_expedicion'] = "AUTO";
    $datos['factura']['folio'] = trim($pedidoData['FOLIO']);
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
    /*$datos['emisor']['rfc'] = 'EKU9003173C9'; //RFC DE PRUEBA
    $datos['emisor']['nombre'] = 'ESCUELA KEMPER URGATE';  // EMPRESA DE PRUEBA
    $datos['emisor']['RegimenFiscal'] = '626';*/
    /*$datos['emisor']['rfc'] = 'LUHM920412GU2'; //RFC DE PRUEBA
    $datos['emisor']['nombre'] = 'MARCOS LUNA HERNANDEZ';  // EMPRESA DE PRUEBA
    $datos['emisor']['RegimenFiscal'] = '612';
    $regimenStr = $empresaData['regimenFiscal'];
    if (preg_match('/^(\d+)/', $regimenStr, $matches)) {
        $datos['emisor']['RegimenFiscal'] = $matches[1];
    } else {
        $datos['emisor']['RegimenFiscal'] = $regimenStr;
    }*/

    // Datos del Emisor
    $datos['emisor']['rfc'] = trim($empresaData['rfc']);
    $datos['emisor']['nombre'] = trim($empresaData['razonSocial']);
    $regimenStr = $empresaData['regimenFiscal'];
    if (preg_match('/^(\d+)/', $regimenStr, $matches)) {
        $datos['emisor']['RegimenFiscal'] = trim($matches[1]);
    } else {
        $datos['emisor']['RegimenFiscal'] = trim($regimenStr);
    }

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
    $IEPS = 0;
    $porIEPS = 0;
    foreach ($productosData as $producto) {
        $dataProduc = datosProcuto($producto['CVE_ART'], $claveSae, $conexionData, $conn);
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

        if ($producto['IMPU1'] != 0) {
            $concepto['Impuestos']['Traslados'][1]['Base'] = sprintf('%.3f', $baseImpuesto);
            $concepto['Impuestos']['Traslados'][1]['Impuesto'] = '003';
            $concepto['Impuestos']['Traslados'][1]['TipoFactor'] = 'Cuota';
            $concepto['Impuestos']['Traslados'][1]['TasaOCuota'] = sprintf('%.6f', $producto['IMPU1'] / 100);
            //$concepto['Impuestos']['Traslados'][1]['Importe'] = sprintf('%.2f', round($baseImpuesto * ($producto['IMPU4'] / 100), 2));        //Original
            $concepto['Impuestos']['Traslados'][1]['Importe'] = sprintf('%.3f', ($baseImpuesto * ($producto['IMPU1'] / 100)));

            $IEPS = $IEPS + ($baseImpuesto * ($producto['IMPU1'] / 100));
            $porIEPS = $producto['IMPU1'];
        }
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

    if ($producto['IMPU1'] != 0) {
        $datos['impuestos']['translados'][1]['Base'] = sprintf('%.2f', $pedidoData['CAN_TOT'] - $DES);
        $datos['impuestos']['translados'][1]['impuesto'] = '002';
        $datos['impuestos']['translados'][1]['tasa'] = sprintf('%.2f', $porIEPS);
        $datos['impuestos']['translados'][1]['importe'] = sprintf('%.2f', $IEPS); //Original sin sprintf
        $datos['impuestos']['translados'][1]['TipoFactor'] = 'Tasa';
    }

    //$datos['impuestos']['TotalImpuestosTrasladados'] = round($IMPU, 2);   //Original
    $datos['impuestos']['TotalImpuestosTrasladados'] = sprintf('%.2f', $IMPU + $IEPS);

    /*echo "<pre>";
    print_r($datos);
    echo "</pre>";*/
    $res = mf_genera_cfdi4($datos);
    //var_dump($res);
    /*$res = mf_default($datos);
    var_dump($res);*/

    if (isset($res['codigo_mf_numero']) && $res['codigo_mf_numero'] == 0) {
        header('Content-Type: application/json');
        return json_encode([
            "success" => true
        ]);
    } else {
        header('Content-Type: application/json');
        return json_encode([
            "success" => false,
            "Problema" => $res['mensaje_original_pac_json']
            //"Problema" => $res['codigo_mf_texto']
        ]);
    }
//    ///////////    MOSTRAR RESULTADOS DEL ARRAY $res   ///////////
//    echo "<h1>Respuesta Generar XML y Timbrado</h1>";
//    foreach ($res as $variable => $valor) {
//        echo "<hr>";
//        $valor = htmlentities($valor);
//        $valor = str_replace('&lt;br/&gt;', '<br/>', $valor);
//        echo "<b>[$variable]=</b>$valor<hr>";
//}
}
//http://localhost/MDConnecta/Servidor/PHPverificarFactura.php
//http://localhost/MDConnecta/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php?cve_doc=18631&noEmpresa=02&claveSae=02
$cve_doc = $_POST['cve_doc'];
$noEmpresa = $_POST['noEmpresa'];
$claveSae = $_POST['claveSae'];
$factura = $_POST['factura'];

/*$cve_doc = 19;
$noEmpresa = 3;
$claveSae = 03;
$factura = 26;*/

/*$cve_doc = 19097;
$noEmpresa = 2;
$claveSae = 02;
$factura = 18979;*/
/*
 * cfdi($cve_doc, $noEmpresa, $claveSae, $factura);
$datos['conf']['cer'] =base64_encode(file_get_contents($empresa['archivo_cer']));
$datos['conf']['key'] =base64_encode(file_get_contents($empresa['archivo_key']));
base64_encode(file_get_contents($empresa['archivo_cer']));
*/