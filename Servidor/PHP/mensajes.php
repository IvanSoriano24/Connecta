<?php
require __DIR__ . '/../../vendor/autoload.php';
use Kreait\Firebase\Factory;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
include 'reportes.php';

function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae)
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
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
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

function validarCorreoCliente($CVE_DOC, $conexionData, $rutaPDF, $claveSae, $folio, $firebaseProjectId, $firebaseApiKey, $pedidoId, $noEmpresa, $vend)
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

    $detallesPedido = obtenerDetalles($firebaseProjectId, $firebaseApiKey, $pedidoId);

    if (!$detallesPedido['success']) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener detalles del pedido.']));
    }

    $pedidoInfo = $detallesPedido['data'];

    $partidasData = $pedidoInfo['productos'];
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $pedidoInfo['enviar']; // Dirección de envío
    $vendedor = $pedidoInfo['vendedor']; // Número de vendedor
    $claveCliente = $pedidoInfo['claveCliente'];

    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $folio; // Número de pedido
    /*$claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
    $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);*/

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener MAIL y EMAILPRED
    $sql = "SELECT MAIL, EMAILPRED, NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
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
        $claveProducto = $partida['producto'];

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

    $fechaElaboracion = $pedidoInfo['diaAlta'];
    $correo = trim($clienteData['MAIL']);
    $emailPred = (is_null($clienteData['EMAILPRED'])) ? "" : trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    // Si hay múltiples correos separados por `;`, tomar solo el primero
    $emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    $numeroWhatsApp = (is_null($clienteData['TELEFONO'])) ? "" : trim($clienteData['TELEFONO']);
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

    if ($emailPred === "") {
        $correoBandera = 1;
    } else {
        $correoBandera = 0;
    }
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

    //var_dump($dataCredito['success']);

    if (($correo === 'S' && isset($emailPred)) || isset($numeroWhatsApp)) {
        // Enviar notificaciones solo si los datos son válidos
        if ($numeroBandera === 0) {
            //$result = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend);
            $result = enviarWhatsAppPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend);
            //var_dump("1: ", $result);
        }
        if ($correoBandera === 0) {
            enviarCorreo($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $vend, $conCredito, $claveCliente);
        }
        // Determinar la respuesta JSON según las notificaciones enviadas
        if ($correoBandera === 0 && $numeroBandera === 0) {
            echo json_encode(['success' => true, 'notificacion' => true, 'message' => 'Pedido Autorizado y notificado por correo y WhatsApp.']);
        } elseif ($correoBandera === 1 && $numeroBandera === 0) {
            echo json_encode(['success' => false, 'telefono' => true, 'message' => 'Pedido Realizado, el Cliente no Tiene un Correo y WhatsApp para notificar.']);
        } elseif ($correoBandera === 0 && $numeroBandera === 1) {
            echo json_encode(['success' => false, 'correo' => true, 'message' => 'Pedido Realizado, el Cliente no Tiene WhatsApp para notifiar pero si Correo.']);
        } else {
            $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
            // Consultar Firebase para obtener los datos del vendedor
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Content-Type: application/json\r\n"
                ]
            ]);

            $response = @file_get_contents($firebaseUrl, false, $context);

            $usuariosData = json_decode($response, true);
            $telefonoVendedor = null;
            $correoVendedor = null;
            $vendedor = formatearClaveVendedor($vend);
            //var_dump($vendedor);
            // Buscar al vendedor por clave
            if (isset($usuariosData['documents'])) {
                foreach ($usuariosData['documents'] as $document) {
                    $fields = $document['fields'];
                    if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
                        if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $vendedor) {
                            if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                                $telefonoVendedor = $fields['telefono']['stringValue'];
                                $correoVendedor = $fields['correo']['stringValue'];
                                break;
                            }
                        }
                    }
                }
            }
            $result = enviarWhatsAppPdf($telefonoVendedor, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend);
            enviarCorreo($correoVendedor, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $vend, $conCredito, $claveCliente);
            //$result = enviarWhatsAppConPlantilla($telefonoVendedor, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend);
            //var_dump("2: ", $result);
            echo json_encode(['success' => true, 'notificacion' => false, 'message' => 'Pedido Autorizado, pero no se pudo notificar al cliente.']);
        }
    } else {
        $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
        // Consultar Firebase para obtener los datos del vendedor
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n"
            ]
        ]);

        $response = @file_get_contents($firebaseUrl, false, $context);

        $usuariosData = json_decode($response, true);
        $telefonoVendedor = null;
        $correoVendedor = null;
        $vendedor = formatearClaveVendedor($vend);
        //var_dump($vendedor);
        // Buscar al vendedor por clave
        if (isset($usuariosData['documents'])) {
            foreach ($usuariosData['documents'] as $document) {
                $fields = $document['fields'];
                if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
                    if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $vendedor) {
                        if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                            $telefonoVendedor = $fields['telefono']['stringValue'];
                            $correoVendedor = $fields['correo']['stringValue'];
                            break;
                        }
                    }
                }
            }
        }
        $result = enviarWhatsAppPdf($telefonoVendedor, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend);
        enviarCorreo($correoVendedor, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $vend, $conCredito, $claveCliente);
        //$result = enviarWhatsAppConPlantilla($telefonoVendedor, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend);
        //var_dump("3: ", $result);
        echo json_encode(['success' => false, 'datos' => false, 'message' => 'El cliente no tiene un correo y telefono válido registrado.']);
        die();
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function enviarWhatsAppPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend)
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload,
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $idFirebasePedido = null;
    $direccion1Contacto = null;

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        // runQuery devuelve un array con un elemento por cada match
        if (isset($resultArray[0]['document'])) {
            $doc = $resultArray[0]['document'];
            // si quieres el ID:
            $parts = explode('/', $doc['name']);
            $idFirebasePedido = end($parts);
            // y para tomar tu campo direccion1Contacto:
            $fields = $doc['fields'];
            $direccion1Contacto = $fields['direccion1Contacto']['stringValue'] ?? null;
        }
    }

    // ✅ Generar URLs dinámicas correctamente
    // ✅ Generar solo el ID del pedido en la URL del botón
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idFirebasePedido);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);

    // ✅ Construir la lista de productos
    $productosStr = "";
    //$lineas = [];
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = $partida['precioUnitario'];

        $totalPartida = $cantidad * $precioUnitario;

        $total += $totalPartida;
        $IMPORTE = $total;

        $productosStr .= "$producto - $cantidad unidades,";
        //$productosStr .= " • {$producto} - {$cantidad} unidades, ";
        //$lineas[] = "• {$producto} - {$cantidad} unidades\nok";

        //$IMPU4 = htmlspecialchars($partida['iva']);
        //$IMPU4 = intval(htmlspecialchars($partida['iva']));
        $IMPU4 = $partida['iva'];

        //$desc1 = htmlspecialchars($partida['descuento']) ?? 0;
        //$desc1 = intval(htmlspecialchars($partida['descuento'] ?? 0));
        $desc1 = isset($partida['descuento'])
            ? (float)$partida['descuento']
            : 0.0;
        $desProcentaje = $desc1 / 100.0;

        $DES = $totalPartida * $desProcentaje;

        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);

        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;


    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    //$rutaPDFW = "https://mdconecta.mdcloud.app/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
    $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";

    //$rutaPDFW = "http://localhost/MDConnecta/Servidor/PHP/pdfs/Pedido" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";

    //$filename = "Pedido_" . urldecode($noPedido) . ".pdf";
    $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
    //$filename = "Pedido_18456.pdf";

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

function enviarCorreo($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $vend, $conCredito, $claveCliente)
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload,
        ]
    ];

    $context = stream_context_create($options);
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
    if ($correoRemitente == "" || $contraseñaRemitente == "") {
        $correoRemitente = "";
        $contraseñaRemitente = "";
    }
    /*$correoRemitente = "";
    $contraseñaRemitente = "";*/
    // Definir el correo de destino (puedes cambiarlo si es necesario)
    $correoDestino = $correo;

    // Obtener el nombre de la empresa desde la sesión
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'Empresa Desconocida';

    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // Convertir productos a JSON para la URL
    $productosJson = urlencode(json_encode($partidasData));

    // URL base del servidor
    $urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "https://mdconecta.mdcloud.app/Servidor/PHP";
    //$urlBase = "http://localhost/MDConnecta/Servidor/PHP";

    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vend) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idFirebasePedido);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vend) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los detalles de su pedido <b>$noPedido</b>. Por favor, revíselos, confirme el pedido y despues páguelo:</p>";
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
        $clave = htmlspecialchars($partida['producto']);
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = htmlspecialchars($partida['cantidad']);
        $totalPartida = $cantidad * $partida['precioUnitario'];
        $total += $totalPartida;
        $IMPORTE = $total;

        $bodyHTML .= "<tr>
                        <td style='text-align: center;'>$clave</td>
                        <td>$descripcion</td>
                        <td style='text-align: right;'>$cantidad</td>
                        <td style='text-align: right;'>$" . number_format($totalPartida, 2) . "</td>
                      </tr>";

        //$IMPU4 = htmlspecialchars($partida['iva']);
        //$IMPU4 = intval(htmlspecialchars($partida['iva']));
        $IMPU4 = $partida['iva'];
        //$desc1 = htmlspecialchars($partida['descuento']) ?? 0;
        //$desc1 = intval(htmlspecialchars($partida['descuento'] ?? 0));
        $desc1 = isset($partida['descuento'])
            ? (float)$partida['descuento']
            : 0.0;
        $desProcentaje = $desc1 / 100.0;
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b><h2>Total:</h2></b> $" . number_format($IMPORTE, 2) . "</p>";

    // Botones para confirmar o rechazar el pedido
    $bodyHTML .= "<p>Confirme su pedido seleccionando una opción:</p>
                  <a href='$urlConfirmar' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar</a>
                  <a href='$urlRechazar' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Rechazar</a>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente);

    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
}

function obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $claveVendedor)
{
    $clave = formatearClaveCliente($claveCliente);
    $claveVend = formatearClaveVendedor($claveVendedor);

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

    // **1️⃣ Obtener datos del Cliente**
    $nombreTablaCliente = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sqlCliente = "SELECT NOMBRE FROM $nombreTablaCliente WHERE CLAVE = ?";
    $stmtCliente = sqlsrv_query($conn, $sqlCliente, [$clave]);
    //var_dump($clave);
    if ($stmtCliente === false) {
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al obtener datos del cliente', 'errors' => sqlsrv_errors()]));
    }

    $datosCliente = sqlsrv_fetch_array($stmtCliente, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmtCliente); // ✅ Liberar el recurso después de usarlo

    // **2️⃣ Obtener datos del Vendedor**
    $nombreTablaVendedor = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sqlVendedor = "SELECT NOMBRE FROM $nombreTablaVendedor WHERE CVE_VEND = ?";
    $stmtVendedor = sqlsrv_query($conn, $sqlVendedor, [$claveVend]);
    if ($stmtVendedor === false) {
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al obtener datos del vendedor', 'errors' => sqlsrv_errors()]));
    }

    $datosVendedor = sqlsrv_fetch_array($stmtVendedor, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmtVendedor); // ✅ Liberar el recurso después de usarlo

    // **3️⃣ Unir los datos en un solo array**
    $resultado = [
        'cliente' => $datosCliente ? $datosCliente['NOMBRE'] : 'No encontrado',
        'vendedor' => $datosVendedor ? $datosVendedor['NOMBRE'] : 'No encontrado'
    ];

    sqlsrv_close($conn); // ✅ Cerrar conexión

    return $resultado;
}

function formatearClaveCliente($clave)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $clave = trim((string)$clave);
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($clave) === 10) {
        return $clave;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    return $clave;
}

function formatearClaveVendedor($clave)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $clave = trim((string)$clave);
    $clave = str_pad($clave, 5, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($clave) === 5) {
        return $clave;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $clave = str_pad($clave, 5, ' ', STR_PAD_LEFT);
    return $clave;
}

function comandas($firebaseProjectId, $firebaseApiKey, $filtroStatus)
{
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    
    $collection = "COMANDA";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId"
        . "/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    $filters = [];

    // Filtro de status solo si no es cadena vacía
    if (trim($filtroStatus) !== "") {
        $filters[] = [
            "fieldFilter" => [
                "field" => ["fieldPath" => "status"],
                "op" => "EQUAL",
                "value" => ["stringValue" => $filtroStatus]
            ]
        ];
    }

    // Filtro obligatorio de noEmpresa
    $filters[] = [
        "fieldFilter" => [
            "field" => ["fieldPath" => "noEmpresa"],
            "op" => "EQUAL",
            "value" => ["integerValue" => (int)$noEmpresa]
        ]
    ];

    // Decidir si usar simple o compositeFilter
    if (count($filters) === 1) {
        $where = $filters[0];
    } else {
        $where = [
            "compositeFilter" => [
                "op" => "AND",
                "filters" => $filters
            ]
        ];
    }

    // Opcional: ordenar por folio descendente en la propia consulta
    $structuredQuery = [
        "from" => [["collectionId" => $collection]],
        "where" => $where
    ];

    $payload = json_encode(["structuredQuery" => $structuredQuery]);

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo conectar a la base de datos.'
        ]);
        exit;
    }

    $resultArray = json_decode($response, true);
    $comandas = [];

    foreach ($resultArray as $item) {
        if (!isset($item['document'])) {
            continue;
        }
        $doc = $item['document'];
        $fields = $doc['fields'];

        $fechaHoraStr = $fields['fechaHoraElaboracion']['stringValue'] ?? "";
        list($fecha, $hora) = array_pad(explode(" ", $fechaHoraStr), 2, ["", "00:00:00"]);

        // Obtener vendedor
        $vendedor = $fields['vendedor']['stringValue'] ?? "";

        // Determinar tipo de pago: Anticipado si pagada=true, Crédito si credito=true
        $pagada = $fields['pagada']['booleanValue'] ?? false;
        $credito = $fields['credito']['booleanValue'] ?? false;
        
        $tipoPago = "No definido";
        if ($pagada) {
            $tipoPago = "Anticipado";
        } elseif ($credito) {
            $tipoPago = "Crédito";
        }

        $comandas[] = [
            'id' => basename($doc['name']),
            'noPedido' => $fields['folio']['stringValue'] ?? "",
            'nombreCliente' => $fields['nombreCliente']['stringValue'] ?? "",
            'status' => $fields['status']['stringValue'] ?? "",
            'fecha' => $fecha,
            'hora' => $hora,
            'vendedor' => $vendedor,
            'tipoPago' => $tipoPago
        ];
    }

    // aquí haces el orden
    usort($comandas, function ($a, $b) {
        return (int)$b['noPedido'] <=> (int)$a['noPedido'];
    });

    echo json_encode([
        'success' => true,
        'data' => $comandas
    ]);

}

function obtenerDetallesComanda($firebaseProjectId, $firebaseApiKey, $comandaId)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los detalles de la comanda.']);
    } else {
        $data = json_decode($response, true);
        $fields = $data['fields'];
        $productos = [];

        // Obtener el folio del pedido para buscar lotes en BD si no están en Firebase
        $folioPedido = $fields['folio']['stringValue'] ?? '';
        $claveSae = $_SESSION['empresa']['claveSae'] ?? '';
        $noEmpresa = $_SESSION['empresa']['noEmpresa'] ?? '';
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conn = null;
        if ($conexionResult['success']) {
            $conexionData = $conexionResult['data'];
            $serverName = $conexionData['host'];
            $connectionInfo = [
                "Database" => $conexionData['nombreBase'],
                "UID" => $conexionData['usuario'],
                "PWD" => $conexionData['password'],
                "CharacterSet" => "UTF-8",
                "TrustServerCertificate" => true
            ];
            $conn = @sqlsrv_connect($serverName, $connectionInfo);
        }

        if (isset($fields['productos']['arrayValue']['values'])) {
            foreach ($fields['productos']['arrayValue']['values'] as $p) {
                // Obtener el lote, verificar múltiples formatos posibles
                $lote = '';
                if (isset($p['mapValue']['fields']['lote'])) {
                    if (isset($p['mapValue']['fields']['lote']['stringValue'])) {
                        $lote = trim($p['mapValue']['fields']['lote']['stringValue']);
                    } elseif (isset($p['mapValue']['fields']['lote']['integerValue'])) {
                        $lote = (string)$p['mapValue']['fields']['lote']['integerValue'];
                    } elseif (isset($p['mapValue']['fields']['lote']['doubleValue'])) {
                        $lote = (string)$p['mapValue']['fields']['lote']['doubleValue'];
                    }
                }
                
                // Si el lote está vacío, "N/A" o "NA", intentar obtenerlo desde LTPD a través de ENLACE_LTPD
                // Nota: Los lotes se asignan cuando se procesa la remisión. Si no están aquí,
                // significa que la comanda aún no ha sido procesada o el producto no tiene control de lotes.
                if (empty($lote) || $lote === 'N/A' || $lote === 'NA') {
                    $claveProducto = $p['mapValue']['fields']['clave']['stringValue'] ?? '';
                    if (!empty($claveProducto) && $conn !== false && !empty($folioPedido)) {
                        // Intentar obtener el lote desde LTPD usando ENLACE_LTPD y PAR_FACTP
                        $tablaEnlace = "[{$conexionData['nombreBase']}].[dbo].[ENLACE_LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
                        $tablaLotes = "[{$conexionData['nombreBase']}].[dbo].[LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
                        $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
                        $pedidoFormat = str_pad($folioPedido, 10, '0', STR_PAD_LEFT);
                        $pedidoFormat = str_pad($pedidoFormat, 20, ' ', STR_PAD_LEFT);
                        
                        // Buscar el lote a través de la relación: PAR_FACTP -> ENLACE_LTPD -> LTPD
                        $sql = "SELECT TOP 1 L.LOTE 
                                FROM $tablaEnlace E
                                INNER JOIN $tablaLotes L ON L.REG_LTPD = E.REG_LTPD AND L.CVE_ART = E.CVE_ART
                                INNER JOIN $tablaPartidas P ON P.CVE_ART = ?
                                WHERE P.CVE_DOC = ? AND P.CVE_ART = ?
                                ORDER BY E.E_LTPD DESC";
                        $params = [$claveProducto, $pedidoFormat, $claveProducto];
                        $stmt = @sqlsrv_query($conn, $sql, $params);
                        
                        if ($stmt !== false) {
                            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                            if ($row && isset($row['LOTE']) && !empty(trim($row['LOTE']))) {
                                $lote = trim((string)$row['LOTE']);
                            }
                            sqlsrv_free_stmt($stmt);
                        }
                    }
                }
                
                // Si después de todo sigue vacío, dejarlo vacío
                if (empty($lote) || $lote === 'N/A' || $lote === 'NA') {
                    $lote = '';
                }
                
                $productos[] = [
                    'cantidad'     => $p['mapValue']['fields']['cantidad']['integerValue'] ?? 0,
                    'clave'        => $p['mapValue']['fields']['clave']['stringValue'] ?? '',
                    'descripcion'  => $p['mapValue']['fields']['descripcion']['stringValue'] ?? '',
                    'lote'         => $lote,
                    'checked'      => $p['mapValue']['fields']['checked']['booleanValue'] ?? false
                ];
            }
        }
        
        // Cerrar conexión si se abrió
        if ($conn !== false && $conn !== null) {
            sqlsrv_close($conn);
        }

        $envioData = [];
        if (isset($fields['envio']['mapValue']['fields'])) {
            $e = $fields['envio']['mapValue']['fields'];
            $envioData = [
                'codigoContacto'    => $e['codigoContacto']['stringValue'] ?? '',
                'companiaContacto'  => $e['companiaContacto']['stringValue'] ?? '',
                'correoContacto'    => $e['correoContacto']['stringValue'] ?? '',
                'direccion1Contacto'=> $e['direccion1Contacto']['stringValue'] ?? '',
                'direccion2Contacto'=> $e['direccion2Contacto']['stringValue'] ?? '',
                'estadoContacto'    => $e['estadoContacto']['stringValue'] ?? '',
                'idPedido'          => $e['idPedido']['integerValue'] ?? 0,
                'municipioContacto' => $e['municipioContacto']['stringValue'] ?? '',
                'noEmpresa'         => $e['noEmpresa']['integerValue'] ?? 0,
                'nombreContacto'    => $e['nombreContacto']['stringValue'] ?? '',
                'telefonoContacto'  => $e['telefonoContacto']['stringValue'] ?? '',
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id'            => $comandaId,
                'noPedido'      => $fields['folio']['stringValue'] ?? '',
                'nombreCliente' => $fields['nombreCliente']['stringValue'] ?? '',
                'status'        => $fields['status']['stringValue'] ?? '',
                'fechaEnvio'    => $fields['fechaEnvio']['stringValue'] ?? '',
                'fecha'         => explode(' ', $fields['fechaHoraElaboracion']['stringValue'])[0] ?? '',
                'hora'          => explode(' ', $fields['fechaHoraElaboracion']['stringValue'])[1] ?? '00:00:00',
                'numGuia'       => $fields['numGuia']['stringValue'] ?? '',
                'productos'     => $productos,
                'envioData'     => $envioData,
                'activada'      => $fields['activada']['booleanValue'] ?? false,
                'observaciones' => $fields['observaciones']['stringValue'] ?? ''
            ]
        ]);
    }
}

function marcarComandaTerminada($firebaseProjectId, $firebaseApiKey, $comandaId, $numGuia, $enviarHoy)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey";

    $fechaEnvio = $enviarHoy ? date('Y-m-d') : date('Y-m-d', strtotime('+1 day')); // Hoy o mañana

    // Datos de actualización en Firebase
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'TERMINADA'],
            'fechaEnvio' => ['stringValue' => $fechaEnvio], // Agregar fecha de envío
            'numGuia' => ['stringValue' => strip_tags($numGuia)] // Guardar número de guía
        ]
    ];

    // Agregar `updateMask` para actualizar solo los campos indicados
    $url .= '&updateMask.fieldPaths=status&updateMask.fieldPaths=fechaEnvio&updateMask.fieldPaths=numGuia';

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Error al marcar la comanda como TERMINADA.', 'error' => $error['message']]);
    } else {
        $result = json_decode($response, true);
        echo json_encode(['success' => true, 'message' => 'Comanda marcada como TERMINADA.', 'response' => $result, 'data' => $data]);
    }
}

function activarComanda($firebaseProjectId, $firebaseApiKey, $comandaId)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey";

    // Datos de actualización en Firebase
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'Abierta'],
            'activada' => ['booleanValue' => true]
        ]
    ];

    // Agregar `updateMask` para actualizar solo los campos indicados
    $url .= '&updateMask.fieldPaths=status&updateMask.fieldPaths=activada';

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Error al activar la comanda.', 'error' => $error['message']]);
    } else {
        $result = json_decode($response, true);
        echo json_encode(['success' => true, 'message' => 'Comanda activada.', 'response' => $result, 'data' => $data]);
    }
}

function notificaciones($firebaseProjectId, $firebaseApiKey)
{
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $nuevosPedidos = 0;
    $nuevasComandas = 0;
    // Verificar pedidos nuevos en Firebase
    $pedidosUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR?key=$firebaseApiKey";
    $pedidosResponse = @file_get_contents($pedidosUrl);
    if ($pedidosResponse !== false) {
        $pedidosData = json_decode($pedidosResponse, true);
        foreach ($pedidosData['documents'] as $document) {
            $fields = $document['fields'];
            if ($fields['status']['stringValue'] === 'Sin Autorizar') {
                $nuevosPedidos++;
            }
        }
    }
    // Verificar comandas pendientes en Firebase
    $comandasUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";
    $comandasResponse = @file_get_contents($comandasUrl);
    if ($comandasResponse !== false) {
        $comandasData = json_decode($comandasResponse, true);
        foreach ($comandasData['documents'] as $document) {
            $fields = $document['fields'];
            if ($fields['status']['stringValue'] === 'Abierta') {
                $nuevasComandas++;
            }
        }
    }

    if ($tipoUsuario === "ADMINISTRADOR") {
        $nuevasComandas = 0;
    } else if ($tipoUsuario === "ALMACENISTA") {
        $nuevosPedidos = 0;
    } else {
        $nuevasComandas = 0;
        $nuevosPedidos = 0;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'nuevosPedidos' => $nuevosPedidos,
            'nuevasComandas' => $nuevasComandas
        ]
    ]);
    exit;
}

function pedidos($firebaseProjectId, $firebaseApiKey, $filtroStatus, $conexionData)
{
    /*
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR?key=$firebaseApiKey";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos.']);
        return;
    }

    $data = json_decode($response, true);
    $pedidos = [];
    $claveSae = $_SESSION['empresa']['claveSae'];
    if (isset($data['documents'])) {
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            if ($fields['claveSae']['stringValue'] === $claveSae) {
                $status = $fields['status']['stringValue'] ?? 'Desconocido';

                // Validaciones necesarias
                //$claveSae = $fields['claveSae']['stringValue'] ?? '';
                $noEmpresa = $fields['noEmpresa']['integerValue'] ?? '';
                $claveCliente = $fields['cliente']['stringValue'] ?? '';
                $claveVendedor = $fields['vendedor']['stringValue'] ?? '';

                // Aplicar el filtro de estado si está definido
                if ($filtroStatus === '' || $status === $filtroStatus) {
                    // Extraer partidas y calcular total
                    $totalPedido = 0;
                    $partidas = $fields['partidas']['arrayValue']['values'] ?? [];

                    foreach ($partidas as $partida) {
                        if (isset($partida['mapValue']['fields']['subtotal']['stringValue'])) {
                            $subtotal = floatval($partida['mapValue']['fields']['subtotal']['stringValue']);
                            $totalPedido += $subtotal;
                        }
                    }

                    // Obtener datos del cliente
                    $dataCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $claveVendedor);
                    $clienteNombre = $dataCliente['cliente'] ?? 'Cliente Desconocido';
                    $vendedorNombre = $dataCliente['vendedor'] ?? 'Vendedor Desconocido';


                    // Formatear datos correctamente
                    $pedidos[] = [
                        'id' => basename($document['name']),
                        'folio' => $fields['folio']['stringValue'] ?? 'N/A',
                        'cliente' => $clienteNombre,
                        'enviar' => $fields['enviar']['stringValue'] ?? 'N/A',
                        'vendedor' => $vendedorNombre,
                        'diaAlta' => $fields['diaAlta']['stringValue'] ?? 'N/A',
                        'claveSae' => $fields['claveSae']['stringValue'] ?? 'N/A',
                        'noEmpresa' => $noEmpresa,
                        'status' => $status,
                        'totalPedido' => number_format(floatval($fields['importe']['doubleValue']), 2, '.', '') ?? "0.0", // 🔹 Total formateado con 2 decimales
                    ];
                }
            } else {
            }
        }
        // Ordenar los pedidos por totalPedido de manera descendente
        usort($pedidos, function ($a, $b) {
            return $b['folio'] <=> $a['folio'];
        });
    }

    echo json_encode(['success' => true, 'data' => $pedidos]);*/
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $collection = "PEDIDOS_AUTORIZAR";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId"
        . "/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    $filters = [];

    // Filtro de status solo si no es cadena vacía
    if (trim($filtroStatus) !== "") {
        $filters[] = [
            "fieldFilter" => [
                "field" => ["fieldPath" => "status"],
                "op" => "EQUAL",
                "value" => ["stringValue" => $filtroStatus]
            ]
        ];
    }

    // Filtro obligatorio de noEmpresa
    $filters[] = [
        "fieldFilter" => [
            "field" => ["fieldPath" => "noEmpresa"],
            "op" => "EQUAL",
            "value" => ["integerValue" => (int)$noEmpresa]
        ]
    ];

    // Decidir si usar simple o compositeFilter
    if (count($filters) === 1) {
        $where = $filters[0];
    } else {
        $where = [
            "compositeFilter" => [
                "op" => "AND",
                "filters" => $filters
            ]
        ];
    }

    // Opcional: ordenar por folio descendente en la propia consulta
    $structuredQuery = [
        "from" => [["collectionId" => $collection]],
        "where" => $where
    ];

    $payload = json_encode(["structuredQuery" => $structuredQuery]);

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo conectar a la base de datos.'
        ]);
        exit;
    }

    $resultArray = json_decode($response, true);
    $pedidos = [];

    foreach ($resultArray as $item) {
        if (!isset($item['document'])) {
            continue;
        }
        $doc = $item['document'];
        $fields = $doc['fields'];

        $fechaHoraStr = $fields['fechaHoraElaboracion']['stringValue'] ?? "";
        list($fecha, $hora) = array_pad(explode(" ", $fechaHoraStr), 2, ["", "00:00:00"]);

        /*$pedidos[] = [
            'id'            => basename($doc['name']),
            'noPedido'      => $fields['folio']['stringValue']     ?? "",
            'nombreCliente' => $fields['nombreCliente']['stringValue'] ?? "",
            'status'        => $fields['status']['stringValue']    ?? "",
            'fecha'         => $fecha,
            'hora'          => $hora
        ];*/
        $claveSae = $fields['claveSae']['stringValue'] ?? '';
        $claveCliente = $fields['cliente']['stringValue'] ?? '';
        $claveVendedor = $fields['vendedor']['stringValue'] ?? '';
        $status = $fields['status']['stringValue'] ?? 'Desconocido';
        // Obtener datos del cliente
        $dataCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $claveVendedor);
        $clienteNombre = $dataCliente['cliente'] ?? 'Cliente Desconocido';
        $vendedorNombre = $dataCliente['vendedor'] ?? 'Vendedor Desconocido';
        $pedidos[] = [
            'id' => basename($doc['name']),
            'folio' => $fields['folio']['stringValue'] ?? 'N/A',
            'cliente' => $clienteNombre,
            'enviar' => $fields['enviar']['stringValue'] ?? 'N/A',
            'vendedor' => $vendedorNombre,
            'diaAlta' => $fields['diaAlta']['stringValue'] ?? 'N/A',
            'claveSae' => $fields['claveSae']['stringValue'] ?? 'N/A',
            'noEmpresa' => $noEmpresa,
            'status' => $status,
            'totalPedido' => number_format(floatval($fields['importe']['doubleValue']), 2, '.', '') ?? "0.0", // 🔹 Total formateado con 2 decimales
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $pedidos
    ]);
}

function obtenerDetallesPedido($firebaseProjectId, $firebaseApiKey, $pedidoId)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR/$pedidoId?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los detalles de la comanda.']);
    } else {
        $data = json_decode($response, true);
        $fields = $data['fields'];
        $productos = [];
        foreach ($fields['partidas']['arrayValue']['values'] as $producto) {
            // Extraer partidas y calcular total
            $totalPedido = 0;
            $partidas = isset($fields['partidas']['arrayValue']['values']) ? $fields['partidas']['arrayValue']['values'] : [];

            foreach ($partidas as $partida) {
                if (isset($partida['mapValue']['fields']['subtotal']['stringValue'])) {
                    $totalPedido += floatval($partida['mapValue']['fields']['subtotal']['stringValue']);
                }
            }


            $productos[] = [
                'producto' => $producto['mapValue']['fields']['producto']['stringValue'],
                'descripcion' => $producto['mapValue']['fields']['descripcion']['stringValue'],
                'cantidad' => $producto['mapValue']['fields']['cantidad']['stringValue'],
                'subtotal' => number_format($producto['mapValue']['fields']['subtotal']['stringValue'], 2, '.', '')
            ];
        }
        $claveSae = $fields['claveSae']['stringValue'];
        $noEmpresa = $fields['noEmpresa']['integerValue'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        $claveCliente = $fields['cliente']['stringValue'];
        $claveVendedor = $fields['vendedor']['stringValue'];

        $datas = obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $claveVendedor);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $pedidoId,
                'folio' => $fields['folio']['stringValue'],
                'cliente' => $datas['cliente'] ?? "",
                'status' => $fields['status']['stringValue'],
                'diaAlta' => $fields['diaAlta']['stringValue'] ?? "",
                'vendedor' => $claveVendedor ?? "",
                'productos' => $productos,
                'noEmpresa' => $noEmpresa ?? "",
                'claveSae' => $claveSae ?? ""
            ]
        ]);
    }
}

function obtenerDetalles($firebaseProjectId, $firebaseApiKey, $pedidoId)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR/$pedidoId?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        return ['success' => false, 'message' => 'Error al obtener los detalles de la comanda.'];
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['fields'])) {
        return ['success' => false, 'message' => 'Respuesta inválida de Firebase.'];
    }

    $fields = $data['fields'];
    $productos = [];
    $totalPedido = 0;

    if (isset($fields['partidas']['arrayValue']['values'])) {
        foreach ($fields['partidas']['arrayValue']['values'] as $producto) {
            if (isset($producto['mapValue']['fields']['subtotal']['stringValue'])) {
                $totalPedido += floatval($producto['mapValue']['fields']['subtotal']['stringValue']);
            }

            $productos[] = [
                'producto' => $producto['mapValue']['fields']['producto']['stringValue'] ?? '',
                'descripcion' => $producto['mapValue']['fields']['descripcion']['stringValue'] ?? '',
                'iva' => $producto['mapValue']['fields']['iva']['stringValue'] ?? '',
                'ieps' => $producto['mapValue']['fields']['ieps']['stringValue'] ?? '',
                'isr' => $producto['mapValue']['fields']['isr']['stringValue'] ?? '',
                'impuesto2' => $producto['mapValue']['fields']['impuesto2']['stringValue'] ?? '',
                'descuento' => $producto['mapValue']['fields']['descuento']['stringValue'] ?? 'descuento',
                'cantidad' => $producto['mapValue']['fields']['cantidad']['stringValue'] ?? '',
                'precioUnitario' => $producto['mapValue']['fields']['precioUnitario']['stringValue'] ?? '',
                'subtotal' => $producto['mapValue']['fields']['subtotal']['stringValue'] ?? ''
            ];
        }
    }

    $claveSae = $fields['claveSae']['stringValue'] ?? '';
    $noEmpresa = $fields['noEmpresa']['integerValue'] ?? '';

    $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
    $conexionData = $conexionResult['data'];

    $claveCliente = $fields['cliente']['stringValue'] ?? '';
    $claveVendedor = $fields['vendedor']['stringValue'] ?? '';
    $datosCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $claveVendedor);

    return [
        'success' => true,
        'data' => [
            'id' => $pedidoId,
            'folio' => $fields['folio']['stringValue'] ?? '',
            'claveCliente' => $fields['cliente']['stringValue'] ?? '',
            'cliente' => $datosCliente['cliente'] ?? '',
            'status' => $fields['status']['stringValue'] ?? '',
            'enviar' => $fields['enviar']['stringValue'] ?? '',
            'diaAlta' => $fields['diaAlta']['stringValue'] ?? '',
            'vendedor' => $datosCliente['vendedor'] ?? '',
            'productos' => $productos,
            'noEmpresa' => $datosCliente['noEmpresa'] ?? '',
            'claveSae' => $datosCliente['claveSae'] ?? ''
        ]
    ];
}

function pedidoAutorizado($firebaseProjectId, $firebaseApiKey, $pedidoId, $folio, $claveSae, $noEmpresa, $vend, $conexionData)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR/$pedidoId?key=$firebaseApiKey";

    // Obtener la fecha de envío


    // Datos de actualización en Firebase
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'Autorizado'],
        ]
    ];

    // Agregar `updateMask` para actualizar solo los campos indicados
    $url .= '&updateMask.fieldPaths=status';

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Error al Autorizar el pedido.', 'error' => $error['message']]);
    } else {
        $result = json_decode($response, true);
        //actualizarEstadoPedido($folio, $conexionData, $claveSae);
        $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
        $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
        $rutaPDF = generarPDFP($CVE_DOC, $conexionData, $claveSae, $noEmpresa, $vend, $folio);
        validarCorreoCliente($CVE_DOC, $conexionData, $rutaPDF, $claveSae, $folio, $firebaseProjectId, $firebaseApiKey, $pedidoId, $noEmpresa, $vend);
        //echo json_encode(['success' => true, 'message' => 'Pedido Autorizado.']);
    }
}

function generarPDFP($CVE_DOC, $conexionData, $claveSae, $noEmpresa, $vend, $folio)
{

    $rutaPDF = generarReportePedidoAutorizado($conexionData, $CVE_DOC, $claveSae, $noEmpresa, $vend, $folio);
    return $rutaPDF;
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

function enviarWhatsAppConPlantilla($numero, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $vend)
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload,
        ]
    ];

    $context = stream_context_create($options);
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
    //var_dump($idFirebasePedido);
    //$url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    //$token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    // ✅ Generar URLs dinámicas correctamente
    // ✅ Generar solo el ID del pedido en la URL del botón
    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idFirebasePedido);
    //$urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae); // Solo pasamos el número de pedido  
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vend) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);

    // ✅ Construir la lista de productos
    $productosStr = "";
    //$lineas = [];
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = $partida['precioUnitario'];

        $totalPartida = $cantidad * $precioUnitario;

        $total += $totalPartida;
        $IMPORTE = $total;

        $productosStr .= "$producto - $cantidad unidades,";
        //$productosStr .= " • {$producto} - {$cantidad} unidades, ";
        //$lineas[] = "• {$producto} - {$cantidad} unidades\nok";

        //$IMPU4 = htmlspecialchars($partida['iva']);
        //$IMPU4 = intval(htmlspecialchars($partida['iva']));
        $IMPU4 = $partida['iva'];

        //$desc1 = htmlspecialchars($partida['descuento']) ?? 0;
        //$desc1 = intval(htmlspecialchars($partida['descuento'] ?? 0));
        $desc1 = isset($partida['descuento'])
            ? (float)$partida['descuento']
            : 0.0;
        $desProcentaje = $desc1 / 100.0;

        $DES = $totalPartida * $desProcentaje;

        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);

        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));
    //$productosStr = implode($lineas);
    //$productosStr = implode("\n", $lineas);

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

function pedidoRechazado($vendedor, $nombreCliente, $folio, $firebaseProjectId, $firebaseApiKey, $pedidoId, $claveSae, $conexionData, $noEmpresa)
{
    $urlFire = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
    $response = @file_get_contents($urlFire);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los usuarios.']);
        return;
    }

    $dataUsuarios = json_decode($response, true);

    // Validación corregida
    if (!isset($dataUsuarios['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron usuarios.']);
        return;
    }

    $telefonoVendedor = null; // Inicializar como null en caso de que no se encuentre
    $vendedor = formatearClaveVendedor($vendedor);
    foreach ($dataUsuarios['documents'] as $document) {
        $fields = $document['fields'];
        if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
            if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $vendedor) {
                if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                    $telefonoVendedor = $fields['telefono']['stringValue'];
                    break;
                }
            }
        }
    }

    // Si no se encuentra el vendedor, asignar un valor por defecto
    $numero = $telefonoVendedor ?? '';
    //$numero = '+527772127123'; // Interzenda
    //$numero = '+527773750925';
    //$numero = '+527775681612';

    $urlUsuario = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR/$pedidoId?key=$firebaseApiKey";

    // Obtener la fecha de envío


    // Datos de actualización en Firebase
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'Rechazado'],
        ]
    ];

    // Agregar `updateMask` para actualizar solo los campos indicados
    $urlUsuario .= '&updateMask.fieldPaths=status';

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($urlUsuario, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Error al Autorizar el pedido.', 'error' => $error['message']]);
    } else {
        $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
        $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
        // Crear el cuerpo de la solicitud para la API
        $data = [
            "messaging_product" => "whatsapp",
            "to" => $numero, // Número del vendedor
            "type" => "template",
            "template" => [
                "name" => "rechazar_pedido_autorizado", // Nombre de la plantilla aprobada
                "language" => ["code" => "es_MX"], // Idioma de la plantilla
                "components" => [
                    // Parámetros del cuerpo de la plantilla
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombreCliente], // {{1}}: Nombre del vendedor
                            ["type" => "text", "text" => $folio]  // {{2}}: Número del pedido
                        ]
                    ]
                ]
            ]
        ];

        // Convertir los datos a JSON
        $data_string = json_encode($data);

        // Configurar cURL para enviar la solicitud
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ]);

        // Ejecutar la solicitud y cerrar cURL
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}

function liberarExistencias($conexionData, $folio, $claveSae)
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
        var_dump(sqlsrv_errors());
        exit;
    }
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT [CVE_ART], [CANT] FROM $nombreTabla
        WHERE [CVE_DOC] = '$CVE_DOC'";
    //$params = [$CVE_DOC];
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        echo "DEBUG: Error al actualizar el pedido:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $partidas = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = $row;
    }
    $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidas as $partida) {
        $CVE_ART = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];
        // SQL para actualizar los campos EXIST y PEND_SURT
        $sql = "UPDATE $tablaInve
            SET    
                [APART] = [APART] - ?   
            WHERE [CVE_ART] = '$CVE_ART'";
        // Preparar la consulta
        $params = array($cantidad, $cantidad);
        // Ejecutar la consulta SQL
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al actualizar el inventario', 'errors' => sqlsrv_errors()]));
        }
        // Verificar cuántas filas se han afectado
        $rowsAffected = sqlsrv_rows_affected($stmt);
        // Retornar el resultado
        if ($rowsAffected > 0) {
            // echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el producto para actualizar']);
        }
    }
}

function obtenerEstadoComanda($claveSeleccionada)
{
    $filePath = "../../Complementos/CAT_ESTADOS.xml";

    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => "El archivo no existe en la ruta: $filePath"]);
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo json_encode(['success' => false, 'message' => "Error al leer el archivo XML en $filePath"]);
        return;
    }

    try {
        $estados = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        return;
    }

    $encontrado = null;
    foreach ($estados->row as $row) {
        if ((string)$row['Clave'] === $claveSeleccionada && (string)$row['Pais'] === 'MEX') {
            $encontrado = [
                'Clave' => (string)$row['Clave'],
                'Pais' => (string)$row['Pais'],
                'Descripcion' => (string)$row['Descripcion']
            ];
            break;
        }
    }

    if ($encontrado !== null) {
        echo json_encode(['success' => true, 'data' => $encontrado]);
    } else {
        echo json_encode(['success' => false, 'message' => "No se encontró el estado con clave $claveSeleccionada"]);
    }
}

function verificarRemision($noPedido, $conexionData, $noEmpresa, $claveSae)
{
    // 1) Conectar
    $connInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($conexionData['host'], $connInfo);
    if ($conn === false) {
        return ['success' => false, 'message' => 'Error DB: ' . print_r(sqlsrv_errors(), true)];
    }

    // 2) Formatear pedido
    $CVE_PED = str_pad($noPedido, 10, '0', STR_PAD_LEFT);
    $CVE_PED = str_pad($CVE_PED, 20, ' ', STR_PAD_LEFT);

    // 3) Tablas dinámicas
    $db = $conexionData['nombreBase'];
    $s = str_pad($claveSae, 2, '0', STR_PAD_LEFT);
    $tblP = "[$db].[dbo].[FACTP{$s}]";
    $tblR = "[$db].[dbo].[FACTR{$s}]";

    // 4) Consulta
    $sql = "
      SELECT 
        R.STATUS       AS statusRemision,
        P.CVE_DOC      AS pedidoDoc,
        R.CVE_DOC      AS remisionDoc
      FROM $tblP P
      INNER JOIN $tblR R
        ON R.DOC_ANT = P.CVE_DOC
      WHERE P.CVE_DOC = ?
    ";
    $params = [$CVE_PED];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Error Query: ' . print_r(sqlsrv_errors(), true)];
    }

    // 5) Obtener resultado
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Interpretar status
        $st = $row['statusRemision'];
        switch ($st) {
            case 'E': // emitida
            case 'O': // abierta
                $interpret = 'ACTIVA';
                break;
            case 'C': // cancelada
                $interpret = 'CANCELADA';
                break;
            default:
                $interpret = 'DESCONOCIDO';
        }
        return [
            'success' => true,
            'data' => [
                'statusCode' => $st,
                'statusText' => $interpret,
                'pedidoDoc' => trim($row['pedidoDoc']),
                'remisionDoc' => trim($row['remisionDoc'])
            ]
        ];
    } else {
        // No hay remisión vinculada
        return [
            'success' => true,
            'data' => [
                'statusCode' => null,
                'statusText' => 'SIN REMISIÓN'
            ]
        ];
    }
}

function cancelarComanda($firebaseProjectId, $firebaseApiKey, $comandaId, $mensajes)
{
    if (!$comandaId) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID de la comanda.']);
        return;
    }

    // URL base del documento con updateMask para solo el campo status
    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/COMANDA/"
        . "$comandaId?updateMask.fieldPaths=status&key=$firebaseApiKey";

    // Solo vamos a cambiar el status
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'CANCELADO']
        ]
    ];

    $opts = [
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data),
            'timeout' => 10
        ]
    ];

    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);

    if ($resp === false) {
        $err = error_get_last();
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con Firestore.',
            'error' => $err['message']
        ]);
    } else {
        $result = json_decode($resp, true);
        // si quieres puedes revisar $result['status'] o similar
        if ($mensajes == 0) {
            return json_encode([
                'success' => true,
                'message' => 'Comanda marcada como CANCELADA.',
                'response' => $result
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Comanda marcada como CANCELADA.',
                'response' => $result
            ]);
        }
    }
}

function verificarComandas($conexionData, $noEmpresa, $claveSae, $firebaseProjectId, $firebaseApiKey)
{
    //Variable para evitar los mensajes de las funciones
    $mensajes = 0;
    // 1) Traer todas las comandas
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";
    $resp = @file_get_contents($url);
    if ($resp === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo conectar a Firestore.']);
        return;
    }
    $docs = json_decode($resp, true)['documents'] ?? [];
    $comandas = [];
    foreach ($docs as $doc) {
        $fields = $doc['fields'];
        if ((int)$fields['noEmpresa']['integerValue'] !== (int)$noEmpresa) continue;
        $comandas[] = [
            'id' => basename($doc['name']),
            'noPedido' => $fields['folio']['stringValue'],
            'status' => $fields['status']['stringValue']
        ];
    }

    $canceladas = [];
    $noCanceladas = [];
    // 2) Para cada comanda aún no CANCELADA, chequeamos remisión
    foreach ($comandas as $c) {
        if ($c['status'] === 'CANCELADO') {
            $canceladas[] = $c;
            continue;
        }
        $rem = verificarRemision($c['noPedido'], $conexionData, $noEmpresa, $claveSae);
        if (!$rem['success']) {
            // si hubo error DB, lo consideramos “no cancelada” 
            $noCanceladas[] = $c;
            continue;
        }
        $code = $rem['data']['statusCode'];
        if ($code === 'C') {
            // remisión cancelada → cancelamos la comanda
            cancelarComanda($firebaseProjectId, $firebaseApiKey, $c['id'], $mensajes);
            $canceladas[] = $c;
        } else {
            $noCanceladas[] = $c;
        }
    }

    // 3) Emitimos JSON con ambos listados
    echo json_encode([
        'success' => true,
        'canceladas' => $canceladas,
        'noCanceladas' => $noCanceladas
    ]);
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
        $filtroStatus = $_GET['status'] ?? ''; // Obtener el filtro desde la solicitud
        comandas($firebaseProjectId, $firebaseApiKey, $filtroStatus);
        break;
    case 2:
        $comandaId = $_GET['comandaId'];
        obtenerDetallesComanda($firebaseProjectId, $firebaseApiKey, $comandaId);
        break;
    case 3:
        $csrf_token = $_SESSION['csrf_token'];
        $csrf_token_form = $_POST['token'];
        if ($csrf_token === $csrf_token_form) {
            $comandaId = $_POST['comandaId'];
            $numGuia = trim($_POST['numGuia']);
            $enviarHoy = filter_var($_POST['enviarHoy'], FILTER_VALIDATE_BOOLEAN);
            // Validar que el Número de Guía no esté vacío
            if (empty($numGuia)) {
                echo json_encode(['success' => false, 'message' => 'El Número de Guía debe contener exactamente 9 dígitos numéricos.']);
                exit;
            }
            marcarComandaTerminada($firebaseProjectId, $firebaseApiKey, $comandaId, $numGuia, $enviarHoy);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    case 4:
        notificaciones($firebaseProjectId, $firebaseApiKey);
        break;
    case 5:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        // Obtener datos de conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $filtroStatus = $_GET['status'] ?? ''; // Obtener el filtro desde la solicitud
        pedidos($firebaseProjectId, $firebaseApiKey, $filtroStatus, $conexionData);
        break;
    case 6:
        $pedidoId = $_GET['pedidoId'];
        obtenerDetallesPedido($firebaseProjectId, $firebaseApiKey, $pedidoId);
        break;
    case 7:
        $csrf_token = $_SESSION['csrf_token'];
        $csrf_token_form = $_POST['token'];
        if ($csrf_token === $csrf_token_form) {
            $noEmpresa = trim($_POST['noEmpresa']);
            $claveSae = trim($_POST['claveSae']);
            $pedidoId = $_POST['pedidoId'];
            $folio = trim($_POST['folio']);
            $vend = trim($_POST['vendedor']);
            $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
            $conexionData = $conexionResult['data'];

            pedidoAutorizado($firebaseProjectId, $firebaseApiKey, $pedidoId, $folio, $claveSae, $noEmpresa, $vend, $conexionData);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    case 8:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        // Obtener datos de conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $vendedor = $_GET['vendedor'];
        $nombreCliente = $_GET['cliente'];
        $folio = $_GET['folio'];
        $pedidoId = $_GET['pedidoId'];
        $result = pedidoRechazado($vendedor, $nombreCliente, $folio, $firebaseProjectId, $firebaseApiKey, $pedidoId, $claveSae, $conexionData, $noEmpresa);
        //liberarExistencias($conexionData, $folio, $claveSae);
        echo json_encode(['success' => true, 'message' => 'Pedido Rechazado.']);
        break;
    case 9:
        $comandaId = $_GET['comandaId'];
        activarComanda($firebaseProjectId, $firebaseApiKey, $comandaId);
        break;
    case 10:
        $estadoSeleccionado = $_POST['estadoSeleccionado'];
        obtenerEstadoComanda($estadoSeleccionado);
        break;
    case 11:
        $noPedido = $_GET['noPedido'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        //var_dump($noPedido);
        $remision = verificarRemision($noPedido, $conexionData, $noEmpresa, $claveSae);
        header('Content-Type: application/json');
        echo json_encode($remision);
        break;
    case 12:
        $comandaId = $_GET['comandaId'];
        $mensajes = 1;
        cancelarComanda($firebaseProjectId, $firebaseApiKey, $comandaId, $mensajes);
        break;
    case 13:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        verificarComandas($conexionData, $noEmpresa, $claveSae, $firebaseProjectId, $firebaseApiKey);
        break;

        // Función para el autoguardadod e los checks de las comandas:
    case "14": // Autoguardado de checkbox producto
        $comandaId = $_POST['comandaId'] ?? null;
        $index     = $_POST['index'] ?? null;
        $checked   = $_POST['checked'] ?? null;

        if (!$comandaId || $index === null || $checked === null) {
            echo json_encode(["success" => false, "message" => "Datos incompletos"]);
            exit;
        }

        try {
            // 1) Obtener documento actual de la comanda
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey";
            $doc = @file_get_contents($url);

            if ($doc === false) {
                echo json_encode(["success" => false, "message" => "No se pudo obtener la comanda"]);
                exit;
            }

            $docData = json_decode($doc, true);
            if (!isset($docData['fields']['productos'])) {
                echo json_encode(["success" => false, "message" => "Comanda sin productos"]);
                exit;
            }

            // 2) Reconstruir productos como array normal
            $productos = [];
            foreach ($docData['fields']['productos']['arrayValue']['values'] as $p) {
                $producto = [];
                foreach ($p['mapValue']['fields'] as $k => $v) {
                    // Convertir tipos Firestore a valores PHP
                    if (isset($v['stringValue'])) $producto[$k] = $v['stringValue'];
                    if (isset($v['integerValue'])) $producto[$k] = (int)$v['integerValue'];
                    if (isset($v['booleanValue'])) $producto[$k] = (bool)$v['booleanValue'];
                }
                $productos[] = $producto;
            }

            // 3) Actualizar el producto específico
            $productos[$index]['checked'] = ($checked === "true");

            // 4) Preparar productos en formato Firestore
            $productosFirestore = ['arrayValue' => ['values' => []]];
            foreach ($productos as $p) {
                $map = ['fields' => []];
                foreach ($p as $k => $v) {
                    if (is_string($v)) $map['fields'][$k] = ['stringValue' => $v];
                    if (is_int($v))    $map['fields'][$k] = ['integerValue' => $v];
                    if (is_bool($v))   $map['fields'][$k] = ['booleanValue' => $v];
                }
                $productosFirestore['arrayValue']['values'][] = ['mapValue' => $map];
            }

            // 5) Hacer PATCH a Firestore
            $updateUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?key=$firebaseApiKey&updateMask.fieldPaths=productos";
            $payload = json_encode([
                "fields" => [
                    "productos" => $productosFirestore
                ]
            ]);

            $opts = [
                'http' => [
                    'method'  => 'PATCH',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => $payload
                ]
            ];
            $context = stream_context_create($opts);
            $result  = @file_get_contents($updateUrl, false, $context);

            if ($result === false) {
                echo json_encode(["success" => false, "message" => "Error al actualizar la comanda"]);
                exit;
            }

            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        break;
}
