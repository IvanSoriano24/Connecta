<?php
set_time_limit(0);
date_default_timezone_set('America/Mexico_City');
require 'firebase.php';
include 'reportes.php';
require_once '../PHPMailer/clsMail.php';

function obtenerPedido($cveDoc, $conexionData, $claveSae)
{
    // Establecer la conexión con SQL Server
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

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[FACTF"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$cveDoc];

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
    sqlsrv_close($conn);
}
function obtenerProductos($cveDoc, $conexionData, $claveSae){
    // Establecer la conexión con SQL Server
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

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$cveDoc];

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
function obtenerCliente($clave, $conexionData, $claveSae)
{
    // Establecer la conexión con SQL Server
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

    $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CLAVE = ?";
    $params = [$clave];

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
function obtenerVendedor($clave, $conexionData, $claveSae)
{
    // Establecer la conexión con SQL Server
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

    $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[VEND"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_VEND = ?";
    $params = [$clave];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $vendData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($vendData) {
        return $vendData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Vendedor no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
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
function obtenerEmpresa($noEmpresa)
{
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
        if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
            return [
                'noEmpresa' => $fields['noEmpresa']['integerValue'] ?? null,
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

function obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa)
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
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
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
function verificarEstadoPedido($folio, $conexionData, $claveSae)
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
        echo "❌ Error de conexión:\n";
        var_dump(sqlsrv_errors());
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    $folio = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($folio, 20, ' ', STR_PAD_LEFT);

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT CVE_DOC, STATUS, TIP_DOC_SIG, DOC_SIG FROM $nombreTabla WHERE CVE_DOC = ?";
    $param = [$CVE_DOC];

    $stmt = sqlsrv_query($conn, $sql, $param);
    if ($stmt === false) {
        echo "❌ Error al ejecutar la consulta:\n";
        var_dump(sqlsrv_errors());
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar el cliente',
            'errors' => sqlsrv_errors()
        ]));
    }

    $pedido = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$pedido) {
        sqlsrv_close($conn);
        echo "⚠ No se encontraron datos del pedido\n";
        return [
            'success' => false,
            'message' => "No se encontraron datos",
        ];
    }

    $STATUS = $pedido['STATUS'];
    $TIP_DOC_SIG = $pedido['TIP_DOC_SIG'];
    $DOC_SIG = $pedido['DOC_SIG'];

    if ($STATUS !== 'C') {
        if ($TIP_DOC_SIG === 'R' && isset($DOC_SIG)) {
            $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

            $sql = "SELECT CVE_DOC, STATUS, TIP_DOC_SIG, DOC_SIG, TIP_DOC_ANT, DOC_ANT FROM $nombreTabla2 WHERE CVE_DOC = ?";
            $param = [$DOC_SIG];

            $stmt = sqlsrv_query($conn, $sql, $param);
            if ($stmt === false) {
                echo "❌ Error al consultar la remisión:\n";
                var_dump(sqlsrv_errors());
                die(json_encode([
                    'success' => false,
                    'message' => 'Error al consultar el cliente',
                    'errors' => sqlsrv_errors()
                ]));
            }

            $remision = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (!$remision) {
                sqlsrv_close($conn);
                echo "⚠ No se encontraron datos de la remisión\n";
                return [
                    'success' => false,
                    'message' => "No se encontraron datos",
                ];
            }

            $STATUS_E = $remision['STATUS'];
            $TIP_DOC_SIG_E = $remision['TIP_DOC_SIG'] ?? null;
            $DOC_SIG_E = $remision['DOC_SIG'];
            $TIP_DOC_ANT = $remision['TIP_DOC_ANT'];
            $DOC_ANT = $remision['DOC_ANT'];

            if ($STATUS_E !== 'C') {
                if ($TIP_DOC_SIG_E !== 'F' && !isset($DOC_SIG_E)) {
                    //var_dump($CVE_DOC, $DOC_SIG);
                    return true;
                } elseif ($TIP_DOC_SIG_E === 'F' && isset($DOC_SIG_E)) {
                    //var_dump($CVE_DOC, $DOC_SIG);
                    return false;
                }
            }
        }
    }

    echo "❓ No se cumplieron las condiciones, devolviendo null\n";
    return null; // por si no se cumple ninguna condición
}

function crearFactura($folio, $noEmpresa, $claveSae, $folioFactura)
{
    $facturaUrl = "https://mdconecta.mdcloud.mx/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php";
    //$facturaUrl = "http://localhost/MDConnecta/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php";

    $data = [
        'cve_doc' => $folio,
        'noEmpresa' => $noEmpresa,
        'claveSae' => $claveSae,
        'factura' => $folioFactura
    ];
    //var_dump($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $facturaUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $facturaResponse = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error cURL: ' . curl_error($ch);
    }
    curl_close($ch);
    var_dump("respuestaCfdi: ", $facturaResponse);
    return $facturaResponse;
}

function crearPdf($folio, $noEmpresa, $claveSae, $conexionData, $folioFactura)
{
    $rutaPDF = generarFactura($folio, $noEmpresa, $claveSae, $conexionData, $folioFactura);
    return $rutaPDF;
}
function validarCorreo($conexionData, $rutaPDF, $claveSae, $folio, $noEmpresa, $folioFactura, $firebaseProjectId, $firebaseApiKey)
{

    // Establecer la conexión con SQL Server
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

    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    $formularioData = obtenerPedido($cveDoc, $conexionData, $claveSae);
    $partidasData = obtenerProductos($cveDoc, $conexionData, $claveSae);
    $clienteData = obtenerCliente($formularioData['CVE_CLPV'], $conexionData, $claveSae);
    $vendedorData = obtenerVendedor($formularioData['CVE_VEND'], $conexionData, $claveSae);
    $CVE_VEND = $formularioData['CVE_VEND'];
    $CVE_VEND = formatearClaveVendedor($CVE_VEND);
    $empresaData = obtenerEmpresa($noEmpresa);
    $titulo = $empresaData['razonSocial'];
    $enviarA = $clienteData['CALLE']; // Dirección de envío
    $vendedor = $vendedorData['NOMBRE']; // Número de vendedor
    $noPactura = $folioFactura; // Número de pedido
    $rutaXml = "../XML/sdk2/timbrados/xml_" . urlencode($clienteData['NOMBRE']) . "_" . urlencode($folioFactura) . ".xml";
    $rutaQr = "../XML/sdk2/timbrados/cfdi_" . urlencode($clienteData['NOMBRE']) . "_" . urlencode($folioFactura) . ".png";
    $rutaCfdi = "../XML/sdk2/timbrados/cfdi_" . urlencode($clienteData['NOMBRE']) . "_" . urlencode($folioFactura) . ".xml";

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

    $fechaElaboracion = $formularioData['FECHAELAB'];
    $correo = trim($clienteData['MAIL']);
    $emailPred = trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    // Si hay múltiples correos separados por `;`, tomar solo el primero
    //$emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    //$emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    //$numeroWhatsApp = trim($clienteData['TELEFONO']);
    $clienteNombre = trim($clienteData['NOMBRE']);
    $clave = trim($clienteData['CLAVE']);

    /******************************************/
    $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
    // Consultar Firebase para obtener los datos del vendedor
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($firebaseUrl, false, $context);
    if ($response === false) {
        echo "<div class='container'>
                        <div class='title'>Error al Obtener Información</div>
                        <div class='message'>No se pudo obtener la información del vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
        exit;
    }

    $usuariosData = json_decode($response, true);

    //var_dump($usuariosData);
    $telefonoVendedor = "";
    $correoVendedor = "";
    $nombreVendedor = "";
    //var_dump($CVE_VEND);
    // Buscar al vendedor por clave
    if (isset($usuariosData['documents'])) {
        foreach ($usuariosData['documents'] as $document) {
            $fields = $document['fields'];
            //var_dump($document['fields']);
            if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
                if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $CVE_VEND) {
                    if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                        $telefonoVendedor = $fields['telefono']['stringValue'];
                        $correoVendedor = $fields['correo']['stringValue'] ?? "";
                        $nombreVendedor = $fields['nombre']['stringValue'];
                        break;
                    }
                }
            }
        }
    }
    /******************************************/
    $emailPred = $correoVendedor ?? "";
    $numeroWhatsApp = $telefonoVendedor;
    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '7773750925';*/
    /*$emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';*/
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123'; // Interzenda*/

    if ($correo === 'S' && !empty($emailPred)) {
        $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Factura_" . trim(urlencode($folioFactura)) . ".pdf";
        //$rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Factura_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura) . ".pdf";
        //$rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Factura_18456.pdf";

        //$rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/". urldecode($rutaPDF);
        
        //$rutaPDFW = "http://localhost/MDConnecta/Servidor/PHP/pdfs/Factura_" . urldecode($folioFactura) . ".pdf";
        
        //$filename = "Factura_" . urldecode($folioFactura) . ".pdf";
        $filename = "Factura_" . preg_replace('/[^A-Za-z0-9_\-]/', '', trim(urlencode($folioFactura))) . ".pdf";
        //$filename = "Factura_18456.pdf";

        $resultadoWhatsApp = enviarWhatsAppFactura($numeroWhatsApp, $clienteNombre, $noPactura, $claveSae, $rutaPDFW, $filename);
        var_dump($resultadoWhatsApp);
        enviarCorreo($emailPred, $clienteNombre, $noPactura, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $titulo, $rutaCfdi, $rutaXml, $rutaQr); // Enviar correo
    } else {
        echo json_encode(['success' => false, 'message' => 'El vendedor no tiene un correo electrónico válido registrado.']);
        die();
    }
    sqlsrv_close($conn);
}
function enviarCorreo($correo, $clienteNombre, $noPactura, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $titulo, $rutaCfdi, $rutaXml, $rutaQr)
{
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

    // Asunto del correo
    $asunto = 'Detalles de la Factura #' . $noPactura;

    // Convertir productos a JSON para la URL
    $productosJson = urlencode(json_encode($partidasData));

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos su factura <b>$noPactura</b>.</p>";
    $bodyHTML .= "<p><b>Fecha y Hora de Elaboración:</b> " . $fechaElaboracion->format('Y-m-d H:i:s') . "</p>";
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
        
        $clave = $partida['CVE_ART'];
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = $partida['CANT'];
        $totalPartida = $cantidad * $partida['PREC'];
        $total += $totalPartida;
        $IMPORTE = $total;

        $bodyHTML .= "<tr>
                        <td>$clave</td>
                        <td>$descripcion</td>
                        <td>$cantidad</td>
                        <td>$" . number_format($totalPartida, 2) . "</td>
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

    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total:</b> $" . number_format($IMPORTE, 2) . "</p>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente, $rutaXml, $rutaQr, $rutaCfdi);

    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
}
function enviarWhatsAppFactura($numeroWhatsApp, $clienteNombre, $noPactura, $claveSae, $rutaPDF, $filename)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // ✅ Verifica que los valores no estén vacíos
    if (empty($noPactura) || empty($claveSae)) {
        error_log("Error: noPedido o noEmpresa están vacíos.");
        return false;
    }
    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "template",
        "template" => [
            "name" => "pedido_factura", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
            "components" => [
                [
                    "type" => "header",
                    "parameters" => [
                        [
                            "type" => "document",
                            "document" => [
                                "link" => $rutaPDF,
                                "filename" => $filename
                            ]
                        ]
                    ]

                ],
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre],
                        ["type" => "text", "text" => $noPactura]
                    ]
                ]
            ]
        ]
    ];
    // ✅ Verificar JSON antes de enviarlo
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);;

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
function enviarCorreoFaltaDatos($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $problema)
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

    $cveDoc = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    $fechaActual = date("Y-m-d H:i:s");

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT CVE_VEND, CVE_CLPV FROM $nombreTabla
        WHERE CVE_DOC = ?";

    $stmt = sqlsrv_query($conn, $sql, [$cveDoc]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $CVE_VEND = $row ? $row['CVE_VEND'] : "";
    $CVE_CLPV = $row ? $row['CVE_CLPV'] : "";

    $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
    // Consultar Firebase para obtener los datos del vendedor
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($firebaseUrl, false, $context);
    if ($response === false) {
        echo "<div class='container'>
                        <div class='title'>Error al Obtener Información</div>
                        <div class='message'>No se pudo obtener la información del vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
        exit;
    }

    $usuariosData = json_decode($response, true);

    //var_dump($usuariosData);
    $telefonoVendedor = "";
    $nombreVendedor = "";
    // Buscar al vendedor por clave
    if (isset($usuariosData['documents'])) {
        foreach ($usuariosData['documents'] as $document) {
            $fields = $document['fields'];
            //var_dump($document['fields']);
            if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
                if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $CVE_VEND) {
                    if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                        $telefonoVendedor = $fields['telefono']['stringValue'];
                        $correoVendedor = $fields['correo']['stringValue'];
                        $nombreVendedor = $fields['nombre']['stringValue'];
                        break;
                    }
                }
            }
        }
    }

    $mail = new clsMail();
    //$correoVendedor = "amartinez@grupointerzenda.com"; //Interzenda
    //$correoVendedor = 'marcos.luna@mdcloud.mx';
    //$correoVendedor = "desarrollo01@mdcloud.mx";
    $titulo = "MDConnecta";
    // Definir el remitente (si no está definido, se usa uno por defecto)
    $correoRemitente = $_SESSION['usuario']['correo'] ?? "";
    $contraseñaRemitente = $_SESSION['empresa']['contrasena'] ?? "";

    if ($correoRemitente === "" || $contraseñaRemitente === "") {
        $correoRemitente = "";
        $contraseñaRemitente = "";
    }

    $correoDestino = $correoVendedor;

    // Asunto del correo
    $asunto = 'Problemas con la factura #' . $folio;

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$nombreVendedor</b>,</p>";
    $bodyHTML .= "<p>Se le notifica que hubo un problema al realizar la factura del pedido: <b>$folio</b>.</p>";
    $bodyHTML .= "<p><b>Fecha de Reporte:</b> " . $fechaActual . "</p>";
    $bodyHTML .= "<p><b>Problema:</b> " . $problema . "</p>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviarErrorDatos($titulo, $nombreVendedor, $correoDestino, $asunto, $bodyHTML, $correoRemitente, $contraseñaRemitente);

    if ($resultado === "Correo enviado exitosamente.") {
        //var_dump('success' . true, 'message' . $resultado);
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
        var_dump('success' . false, 'message' . $resultado);
    }
}
function enviarCorreoFalla($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $problema, $folioFactura)
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

    $cveDoc = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    $fechaActual = date("Y-m-d H:i:s");

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT CVE_VEND, CVE_CLPV FROM $nombreTabla
        WHERE CVE_DOC = ?";

    $stmt = sqlsrv_query($conn, $sql, [$cveDoc]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $CVE_VEND = $row ? $row['CVE_VEND'] : "";
    $CVE_CLPV = $row ? $row['CVE_CLPV'] : "";

    $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
    // Consultar Firebase para obtener los datos del vendedor
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($firebaseUrl, false, $context);
    if ($response === false) {
        echo "<div class='container'>
                        <div class='title'>Error al Obtener Información</div>
                        <div class='message'>No se pudo obtener la información del vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
        exit;
    }

    $usuariosData = json_decode($response, true);

    //var_dump($usuariosData);
    $telefonoVendedor = "";
    $nombreVendedor = "";
    // Buscar al vendedor por clave
    if (isset($usuariosData['documents'])) {
        foreach ($usuariosData['documents'] as $document) {
            $fields = $document['fields'];
            //var_dump($document['fields']);
            if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
                if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $CVE_VEND) {
                    if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                        $telefonoVendedor = $fields['telefono']['stringValue'];
                        $correoVendedor = $fields['correo']['stringValue'];
                        $nombreVendedor = $fields['nombre']['stringValue'];
                        break;
                    }
                }
            }
        }
    }

    $mail = new clsMail();
    //$correoVendedor = "amartinez@grupointerzenda.com"; //Interzenda
    //$correoVendedor = 'marcos.luna@mdcloud.mx';
    //$correoVendedor = "desarrollo01@mdcloud.mx";
    $clienteData = obtenerCliente($CVE_CLPV, $conexionData, $claveSae);
    $rutaXml = "../XML/sdk2/timbrados/xml_" . urlencode($clienteData['NOMBRE']) . "_" . urlencode($folioFactura) . ".xml";
    $rutaError = "../XML/sdk2/tmp/ultimo_error_respuesta.txt";
    $titulo = "MDConnecta";
    // Definir el remitente (si no está definido, se usa uno por defecto)
    $correoRemitente = $_SESSION['usuario']['correo'] ?? "";
    $contraseñaRemitente = $_SESSION['empresa']['contrasena'] ?? "";

    if ($correoRemitente === "" || $contraseñaRemitente === "") {
        $correoRemitente = "";
        $contraseñaRemitente = "";
    }

    $correoDestino = $correoVendedor;

    // Asunto del correo
    $asunto = 'Problemas con la factura #' . $folioFactura;

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$nombreVendedor</b>,</p>";
    $bodyHTML .= "<p>Se le notifica que hubo un problema al realizar la factura: <b>$folioFactura</b>.</p>";
    $bodyHTML .= "<p><b>Fecha de Reporte:</b> " . $fechaActual . "</p>";
    $bodyHTML .= "<p><b>Problema:</b> " . $problema . "</p>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviarError($titulo, $nombreVendedor, $correoDestino, $asunto, $bodyHTML, $correoRemitente, $contraseñaRemitente, $rutaXml, $rutaError);

    if ($resultado === "Correo enviado exitosamente.") {
        //var_dump('success' . true, 'message' . $resultado);
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
        var_dump('success' . false, 'message' . $resultado);
    }
}

function facturar($folio, $claveSae, $noEmpresa, $claveCliente, $credito){
    $numFuncion = '1';
    $pedidoId = $folio;

    // URL del servidor donde se ejecutará la remisión
    $facturanUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/factura.php";
    //$facturanUrl = 'http://localhost/MDConnecta/Servidor/PHP/factura.php';

    // Datos a enviar a la API de remisión
    // En tu JS/PHP cliente:
    $data = [
        'numFuncion'   => $numFuncion,
        'pedidoId'     => $pedidoId,
        'claveSae'     => $claveSae,
        'noEmpresa'    => $noEmpresa,
        'claveCliente' => $claveCliente,
        'credito'      => $credito
    ];


    // Inicializa cURL

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $facturanUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    // Ejecutar la petición y capturar la respuesta
    $facturaResponse = curl_exec($ch);

    // Verificar errores en cURL
    if (curl_errno($ch)) {
        echo 'Error cURL: ' . curl_error($ch);
        curl_close($ch);
        return;
    }

    // Obtener tipo de contenido antes de cerrar cURL
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    //var_dump($facturaResponse);
    if ($facturaResponse) {
        // Intenta decodificar como JSON
        $facturaData = json_decode($facturaResponse, true);
        //var_dump("Factura1: ", $facturaResponse);
        if (json_last_error() === JSON_ERROR_NONE && isset($facturaData)) {
            var_dump("Factura2: ", $facturaData);
            return $facturaData['folioFactura1'];
            // ✅ La respuesta es un JSON con cveDoc (Pedido procesado correctamente)
        }
    } else {
        var_dump("No");
        // ❌ No hubo respuesta
        return false;
    }
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
        echo json_encode(['success' => false, 'message' => "Pedido no encontrado $cve_doc"]);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function actualizarCFDI($conexionData, $claveSae, $folioFactura, $bandera)
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
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }
    if ($bandera == 1) {
        $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
        $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

        $pedidoData = datosPedido($cveDoc, $claveSae, $conexionData);
        $clienteData = datosCliente($pedidoData['CVE_CLPV'], $claveSae, $conexionData);

        $file = '../XML/sdk2/timbrados/cfdi_' . urlencode($clienteData['NOMBRE']) . '_' . urlencode($folioFactura) . '.xml';

        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            $ns   = $xml->getDocNamespaces(true);
            if ($xml !== false) {
                // 1) Entra al nodo cfdi:Comprobante
                $cfdi = $xml->children($ns['cfdi']);

                // 2) Dentro de Comprobante, al Complemento
                $complemento = $cfdi->Complemento;

                // 3) Dentro de Complemento, al namespace tfd
                $tfd = $complemento->children($ns['tfd'])->TimbreFiscalDigital;

                // 4) Ahora sí sacas atributos
                $version   = (string) $xml['Version'];
                $uuid      = (string) $tfd->attributes()->UUID;
                $noSerie   = (string) $tfd->attributes()->NoCertificadoSAT;
                $fechaCert = (string) $tfd->attributes()->FechaTimbrado;

                $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CFDI" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
                $sql = "UPDATE $nombreTabla SET 
                    
                    UUID = ?,
                    NO_SERIE = ?,
                    FECHA_CERT = ?,
                    FECHA_CANCELA = '',
                    XML_DOC = ?,
                    PENDIENTE = 'N',
                    CVE_USUARIO = 0
                    WHERE CVE_DOC = ?";

                $params = [
                    //VERSION = ?, $version,
                    $uuid,
                    $noSerie,
                    $fechaCert,
                    file_get_contents($file),
                    $cveDoc
                ];
                $stmt = sqlsrv_query($conn, $sql, $params);
                if ($stmt === false) {
                    die(json_encode(['success' => false, 'message' => 'Error al actualizar el CFDI', 'errors' => sqlsrv_errors()]));
                }
            } else {
                die(json_encode(['success' => false, 'message' => 'No se encontro ningun archivo', 'errors' => sqlsrv_errors()]));
            }
        } else {
            die(json_encode(['success' => false, 'message' => 'No se encontro ningun archivo', 'errors' => sqlsrv_errors()]));
        }
    }
}
function actualizarStatus($firebaseProjectId, $firebaseApiKey, $documentName, $value = true)
{
    // Extraer el ID de documento (la parte después de /COMANDA/)
    $parts = explode('/', $documentName);
    $docId = end($parts);

    // URL de PATCH con máscara solo en facturado
    $url = sprintf(
        'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents/COMANDA/%s?updateMask.fieldPaths=facturado&key=%s',
        $firebaseProjectId,
        $docId,
        $firebaseApiKey
    );

    // Payload con solo el campo facturado
    $payload = json_encode([
        'fields' => [
            'facturado' => ['booleanValue' => $value]
        ]
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            // opcional: 'timeout' => 10,
        ]
    ]);

    $res = @file_get_contents($url, false, $ctx);
    return $res !== false;
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
        echo json_encode(['success' => false, 'message' => "Cliente no encontrado $clie"]);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function datosPedidoValidacion($cve_doc, $claveSae, $conexionData)
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
        echo json_encode(['success' => false, 'message' => "Pedido/Factura no encontrado $cve_doc"]);
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
        $empFirebase = (int) $fields['noEmpresa']['integerValue'];
        $empBuscada  = (int) $noEmpresa;
        if ($empFirebase === $empBuscada) {
            return [
                'noEmpresa' => $fields['noEmpresa']['integerValue'] ?? null,
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
                'poblacion' => $fields['poblacion']['stringValue'] ?? null,
                'keyEncValue' => $fields['keyEncValue']['stringValue'] ?? null,
                'keyEncIv' => $fields['keyEncIv']['stringValue'] ?? null
            ];
        }
    }

    return false; // No se encontró la empresa
}
function validaciones($folio, $noEmpresa, $claveSae)
{
    global $firebaseProjectId, $firebaseApiKey;

    $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
    if (!$conexionResult['success']) {
        echo json_encode($conexionResult);
        die();
    }
    $conexionData = $conexionResult['data'];
    $folio = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $folio = str_pad($folio, 20, ' ', STR_PAD_LEFT);
    $pedidoData = datosPedidoValidacion($folio, $claveSae, $conexionData);
    $clienteData = datosCliente($pedidoData['CVE_CLPV'], $claveSae, $conexionData);
    $empresaData = datosEmpresa($noEmpresa, $firebaseProjectId, $firebaseApiKey);
    $locacionArchivos = "../XML/sdk2/certificados/$noEmpresa/";
    $archivoCer = glob($locacionArchivos . "{*.cer,*/*.cer}", GLOB_BRACE);
    $archivoKey = glob($locacionArchivos . "{*.key,*/*.key}", GLOB_BRACE);

    if (empty($archivoCer) || empty($archivoKey)) {
        return [
            'success'  => false,
            'Problema' => "No se encontró el .cer o el .key para la empresa $noEmpresa"
        ];
        /*echo json_encode([
            'success'  => false,
            'Problema' => "No se encontró el .cer o el .key para la empresa $noEmpresa"
        ]);
        return;*/
    }
    $requeridosPedido = ['FORMADEPAGOSAT', 'METODODEPAGO', 'USO_CFDI', 'REG_FISC'];
    $faltanPedido = [];
    foreach ($requeridosPedido as $campo) {
        if (empty($clienteData[$campo])) {
            $faltanPedido[] = $campo;
        }
    }
    if (!empty($faltanPedido)) {
        header('Content-Type: application/json');
        return [
            'success'  => false,
            'Problema' => 'Faltan datos del pedido: ' . implode(', ', $faltanPedido)
        ];
    }
    $requeridos = ['RFC', 'NOMBRE', 'USO_CFDI', 'CODIGO', 'REG_FISC'];
    $faltan = [];
    foreach ($requeridos as $campo) {
        if (empty($clienteData[$campo])) {
            $faltan[] = $campo;
        }
    }
    if (!empty($faltan)) {
        header('Content-Type: application/json');
        return [
            'success'  => false,
            'Problema' => 'Faltan datos del cliente: ' . implode(', ', $faltan)
        ];
        /*echo json_encode([
            'success'  => false,
            'Problema' => 'Faltan datos del cliente: ' . implode(', ', $faltan)
        ]);
        return;*/
    }
    if ($clienteData['VAL_RFC'] != 200) {
        $problem = $clienteData['VAL_RFC'];
        /*echo json_encode([
            'success'  => false,
            "Problema' => 'Cliente no puede timbrar: $problem"
        ]);
        return;*/
        return [
            'success'  => false,
            "Problema' => 'Cliente no puede timbrar: $problem"
        ];
    }
    $requeridosEmpre = ['rfc', 'razonSocial', 'regimenFiscal', 'codigoPostal', 'keyEncValue', 'keyEncIv'];
    $faltanEmpre = [];
    foreach ($requeridosEmpre as $campo) {
        if (empty($empresaData[$campo])) {
            $faltanEmpre[] = $campo;
        }
    }
    if (!empty($faltanEmpre)) {
        header('Content-Type: application/json');
        /*echo json_encode([
            'success'  => false,
            'Problema' => 'Faltan datos de la empresa: ' . implode(', ', $faltanEmpre)
        ]);
        return;*/
        return [
            'success'  => false,
            'Problema' => 'Faltan datos de la empresa: ' . implode(', ', $faltanEmpre)
        ];
    }
    return ['success' => true];
}
function verificarHora($firebaseProjectId, $firebaseApiKey)
{
    $horaActual = (int) date('Hi'); // Formato "Hi" concatenado como un número entero
    if ($horaActual <= 1855) { //1455
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";
        //Obtener todas las comandas
        $response = @file_get_contents($url);
        if ($response === false) {
            echo "Error al obtener las comandas.\n";
            return;
        }
        $data = json_decode($response, true);
        if (!isset($data['documents'])) {
            echo "No se encontraron comandas.\n";
            return;
        }
        foreach ($data['documents'] as $document) {
            $fields    = $document['fields'];
            $docName   = $document['name'];
            $status = $fields['status']['stringValue'];
            $folio = $fields['folio']['stringValue'];
            $claveCliente = $fields['claveCliente']['stringValue'];
            $facturado = $fields['facturado']['booleanValue'];
            $credito = $fields['credito']['booleanValue'];
            $claveSae = $fields['claveSae']['stringValue'];
            $noEmpresa = $fields['noEmpresa']['integerValue'];
            $pagada = $fields['pagada']['booleanValue'];

            // Si la comanda está pendiente y es de un día anterior
            if ($status === 'TERMINADA') {
                $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
                if (!$conexionResult['success']) {
                    echo json_encode($conexionResult);
                    break;
                }
                $conexionData = $conexionResult['data'];
                if (!$facturado) {
                    //Funcion para crear factura
                    if ($pagada) {
                        $respuestaValidaciones = validaciones($folio, $noEmpresa, $claveSae);
                        //var_dump($respuestaValidaciones);
                        if ($respuestaValidaciones['success']) {
                            //$folioFactura = $folioFactura['folioFactura1'];
                            //$folioFactura = json_decode(facturar($folio, $claveSae, $noEmpresa, $claveCliente, $credito), true);
                            $folioFactura = facturar($folio, $claveSae, $noEmpresa, $claveCliente, $credito);
                            var_dump("folioFactura: ", $folioFactura);
                            //$folioFactura = 26;
                            //var_dump("folioFactura: ", $folioFactura);
                            
                            actualizarStatus($firebaseProjectId, $firebaseApiKey, $docName);
                            
                            $respuestaFactura = json_decode(crearFactura($folio, $noEmpresa, $claveSae, $folioFactura), true);
                            
                            //var_dump("Respuesta: ", $respuestaFactura);
                            if ($respuestaFactura['success']) {
                                $bandera = 1;
                                var_dump("folio: ", $folio);
                                var_dump("folioFactura: ", $folioFactura);
                                actualizarCFDI($conexionData, $claveSae, $folioFactura, $bandera);
                                $rutaPDF = crearPdf($folio, $noEmpresa, $claveSae, $conexionData, $folioFactura);
                                var_dump("Ruta PDF: ", $rutaPDF);
                                validarCorreo($conexionData, $rutaPDF, $claveSae, $folio, $noEmpresa, $folioFactura, $firebaseProjectId, $firebaseApiKey);
                            } else {
                                enviarCorreoFalla($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $respuestaFactura['Problema'], $folioFactura);
                            }
                        } else {
                            enviarCorreoFaltaDatos($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $respuestaValidaciones['Problema']);
                        }
                    }
                }
            }
        }
    }
}

verificarHora($firebaseProjectId, $firebaseApiKey);
