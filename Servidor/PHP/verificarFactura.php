<?php
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

    $nombreTabla  = "[SAE90Empre02].[dbo].[FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
        echo json_encode(['success' => false, 'message' => "Pedido no encontrado $cve_doc"]);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerProductos($cveDoc, $conexionData, $claveSae)
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

    $nombreTabla  = "[SAE90Empre02].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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

    $nombreTabla   = "[SAE90Empre02].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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

    $nombreTabla   = "[SAE90Empre02].[dbo].[VEND"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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

function crearFactura($folio, $noEmpresa, $claveSae)
{
    //http://localhost/MDConnecta/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php?cve_doc=18631&noEmpresa=02&claveSae=02
    //$facturaUrl = "https://mdconecta.mdcloud.mx/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php";
    $facturaUrl = "http://localhost/MDConnecta/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php";

    $data = [
        'cve_doc' => $folio,
        'noEmpresa' => $noEmpresa,
        'claveSae' => $claveSae
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
    if($facturaResponse){
        echo "<div class='container'>
        <div class='title'>Confirmación Exitosa</div>
        <div class='message'>La factura ha sido realizada correctamente.</div>
        <a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>
      </div>";
    } else{
        echo "<div class='container'>
        <div class='title'>Error Exitosa</div>
        <div class='message'>Hubo un error.</div>
        <a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>
      </div>";
    }
}

function crearPdf($folio, $noEmpresa, $claveSae, $conexionData)
{
    $rutaPDF = generarFactura($folio, $noEmpresa, $claveSae, $conexionData);
    return $rutaPDF;
}

function validarCorreo($conexionData, $rutaPDF, $claveSae, $folio, $noEmpresa)
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

    $cveDoc = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    $formularioData = obtenerPedido($cveDoc, $conexionData, $claveSae);
    $partidasData = obtenerProductos($cveDoc, $conexionData, $claveSae);
    $clienteData = obtenerCliente($formularioData['CVE_CLPV'], $conexionData, $claveSae);
    $vendedorData = obtenerVendedor($formularioData['CVE_VEND'], $conexionData, $claveSae);
    $empresaData = obtenerEmpresa($noEmpresa);
    $titulo = $empresaData['razonSocial'];
    $enviarA = $clienteData['CALLE']; // Dirección de envío
    $vendedor = $vendedorData['NOMBRE']; // Número de vendedor
    $noPedido = $formularioData['FOLIO']; // Número de pedido
    $rutaXml = "../XML/sdk2/timbrados/xml_" . urlencode($clienteData['NOMBRE']) . "_" . urlencode($formularioData['FOLIO']) . ".xml";
    $rutaQr = "../XML/sdk2/timbrados/cfdi_" . urlencode($clienteData['NOMBRE']) . "_" . urlencode($formularioData['FOLIO']) . ".png";
    $rutaCfdi = "../XML/sdk2/timbrados/cfdi_" . urlencode($clienteData['NOMBRE']) . "_" . urlencode($formularioData['FOLIO']) . ".xml";

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
    $emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';
    /*$emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';*/
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/
    if ($correo === 'S' && !empty($emailPred)) {
        /*$resultadoWhatsApp = enviarWhatsAppFactura($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave);
        var_dump($resultadoWhatsApp);*/
        $pdfCaption = "Factura de su pedido";
        $pdfFilename = "Factura_" . urlencode($folio) . ".pdf";
        $filename = 'Factura';
        //http://localhost/MDConnecta/Servidor
        //$pdfUrl = "pdfs/Factura_" .urlencode($folio) . ".pdf";
        $pdfUrl = __DIR__ . "\pdfs/Factura_" . urlencode($folio) . ".pdf";
        //$pdfUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/";
        //$responsePDF = enviarDocumentoWhatsApp($numeroWhatsApp, $pdfUrl, $pdfCaption, $pdfFilename);
        //$responsePDF =  enviarDocumentoWhatsAppConMediaID($numeroWhatsApp, $pdfUrl, $pdfCaption, $filename);

        enviarCorreo($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $titulo, $rutaCfdi, $rutaXml, $rutaQr); // Enviar correo
    } else {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene un correo electrónico válido registrado.']);
        die();
    }
    sqlsrv_close($conn);
}
function enviarDocumentoWhatsAppConMediaID($numeroWhatsApp, $pdfUrl, $caption, $filename)
{
    /*var_dump($numeroWhatsApp);
    var_dump($pdfUrl);
    var_dump($caption);
    var_dump($filename);
    $mimeType = 'application/pdf';*/
    
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    /*$response = uploadMediaToWhatsApp($token, $pdfUrl, $mimeType);
    $uploadData = json_decode($response, true);
    $mediaId = isset($uploadData['id']) ? $uploadData['id'] : null;
    var_dump($mediaId);
    // Crear el payload para enviar un documento usando media_id
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "document",
        "document" => [
            "id" => $mediaId ,       // Usar el media_id obtenido al subir el archivo
            "caption" => $caption,  // Texto descriptivo (opcional)
            "filename" => $filename // Nombre del archivo a mostrar en WhatsApp
        ]
    ];*/
    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "text",
        "text" => [
            "preview_url" => false,
            "body" => "text-message-content"
        ]
    ];

    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

    // Inicializar cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    }
    curl_close($ch);

    if (isset($error_msg)) {
        return "Error: " . $error_msg;
    }
    return $response;
}
function uploadMediaToWhatsApp($accessToken, $filePath, $mimeType) {
    // Construir la URL de la API
    $url = "https://graph.facebook.com/v21.0/509608132246667/media";
    
    // Preparar el archivo con curl_file_create
    if (!file_exists($filePath)) {
        return "Error: el archivo no existe en la ruta especificada.";
    }
    $cfile = curl_file_create($filePath, $mimeType, basename($filePath));
    
    // Los campos que se enviarán en la solicitud POST
    $fields = [
        'messaging_product' => 'whatsapp',
        'file' => $cfile
    ];
    
    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    // Se envía el array $fields y curl se encargará de enviar multipart/form-data
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken"
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return "Error: " . $error_msg;
    }
    
    curl_close($ch);
    return $response;
}
function enviarDocumentoWhatsApp($numeroWhatsApp, $mediaLink, $caption, $filename)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    // Crear el payload para enviar un documento
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "document",
        "document" => [
            "link" => $mediaLink,        // URL pública del archivo o link subido a la API
            "filename" => $filename      // Nombre del archivo a mostrar en WhatsApp
        ]
    ];
    var_dump($data);
    // ✅ Verificar JSON antes de enviarlo
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

    // ✅ Revisar si el JSON contiene `messaging_product`
    if (!isset($data['messaging_product'])) {
        error_log("ERROR: 'messaging_product' no está en la solicitud.");
        return false;
    }

    // Inicializar cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    }
    curl_close($ch);

    if (isset($error_msg)) {
        return "Error: " . $error_msg;
    }
    return $response;
}
function enviarCorreo($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $titulo, $rutaCfdi, $rutaXml, $rutaQr)
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
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // Convertir productos a JSON para la URL
    $productosJson = urlencode(json_encode($partidasData));

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos su factura <b>$noPedido</b>.</p>";
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
    foreach ($partidasData as $partida) {
        $clave = htmlspecialchars($partida['CVE_ART']);
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = htmlspecialchars($partida['CANT']);
        $totalPartida = $cantidad * $partida['PREC'];
        $total += $totalPartida;

        $bodyHTML .= "<tr>
                        <td>$clave</td>
                        <td>$descripcion</td>
                        <td>$cantidad</td>
                        <td>$" . number_format($totalPartida, 2) . "</td>
                      </tr>";
    }

    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total:</b> $" . number_format($total, 2) . "</p>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $rutaCfdi, $correoRemitente, $contraseñaRemitente, $rutaXml, $rutaQr);

    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
}
function enviarWhatsAppFactura($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // ✅ Verifica que los valores no estén vacíos
    if (empty($noPedido) || empty($claveSae)) {
        error_log("Error: noPedido o noEmpresa están vacíos.");
        return false;
    }

    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "template",
        "template" => [
            "name" => "factura_pedido", // 📌 Nombre EXACTO en Meta Business Manager
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
                        ["type" => "text", "text" => $noPedido]
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



function verificarHora($firebaseProjectId, $firebaseApiKey)
{
    $horaActual = (int) date('Hi'); // Formato "Hi" concatenado como un número entero
    if ($horaActual <= 1455) {
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";
        // Obtener todas las comandas
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
            $fields = $document['fields'];
            $status = $fields['status']['stringValue'];
            $folio = $fields['folio']['stringValue'];
            $claveSae = $fields['claveSae']['stringValue'];
            $noEmpresa = $fields['noEmpresa']['stringValue'];

            // Si la comanda está pendiente y es de un día anterior
            if ($status === 'TERMINADA') {
                $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);
                if (!$conexionResult['success']) {
                    echo json_encode($conexionResult);
                    break;
                }
                $conexionData = $conexionResult['data'];
                //Se verifica que el pedido este remitido
                $remitido = verificarEstadoPedido($folio, $conexionData, $claveSae);
                if ($remitido) {
                    $folio = "18490";
                    //$folio = "18456";
                    //Funcion para crear factura
                    crearFactura($folio, $noEmpresa, $claveSae);
                    $rutaPDF = crearPdf($folio, $noEmpresa, $claveSae, $conexionData);
                    validarCorreo($conexionData, $rutaPDF, $claveSae, $folio, $noEmpresa);
                    die();
                }
            }
        }
    }
}

verificarHora($firebaseProjectId, $firebaseApiKey);

//$verificado = verificarComandas();