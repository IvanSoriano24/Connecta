<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
include 'reportes.php';
include 'utils.php';

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
    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "template",
        "template" => [
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
function validarSaldo($conexionData, $clave, $claveSae, $conn)
{
    try {
        // Montamos los nombres de tabla dinámicos
        $db         = $conexionData['nombreBase'];
        $s          = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaCuenM = "[$db].[dbo].[CUEN_M{$s}]";
        $tablaCuenD = "[$db].[dbo].[CUEN_DET{$s}]";
        $tablaClie  = "[$db].[dbo].[CLIE{$s}]";

        // Consulta que agrupa cada factura y sólo devuelve filas con saldo > 0 (redondeado a 2 decimales)
        $sql = "
            SELECT TOP 1 1
            FROM $tablaCuenM CUENM
            INNER JOIN $tablaClie CLIENTES
              ON CLIENTES.CLAVE = CUENM.CVE_CLIE
            LEFT JOIN $tablaCuenD CUEND
              ON CUEND.CVE_CLIE  = CUENM.CVE_CLIE
             AND CUEND.REFER     = CUENM.REFER
             AND CUEND.NUM_CARGO = CUENM.NUM_CARGO
            WHERE
              CUENM.FECHA_VENC     < CAST(GETDATE() AS DATE)
              AND CLIENTES.STATUS <> 'B'
              AND CLIENTES.CLAVE   = ?
              AND CUENM.REFER NOT LIKE '%NC%'
            GROUP BY
              CUENM.CVE_CLIE,
              CUENM.REFER,
              CUENM.NUM_CARGO,
              CUENM.IMPORTE
            HAVING
              ROUND(
                CUENM.IMPORTE
                - COALESCE(SUM(CUEND.IMPORTE), 0),
                2
              ) > 0
        ";

        // Ejecutamos la consulta
        $params = [$clave];
        $stmt   = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            throw new Exception("Error al verificar saldo vencido:\n{$errors}");
        }

        // Si devuelve alguna fila => al menos una factura vencida con saldo pendiente
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        sqlsrv_free_stmt($stmt);

        return ($row !== null) ? 1 : 0;
    } catch (Exception $e) {
        // Manejo de errores
        error_log("validarSaldo error: " . $e->getMessage());
        return -1;
    }
}
function formatearDato($dato)
{
    if (is_string($dato)) {
        return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
    }
    if (is_array($dato)) {
        // Para arreglos anidados, se llama recursivamente
        return formatearFormulario($dato);
    }
    // Si es otro tipo (número, boolean, etc.), se devuelve tal cual.
    return $dato;
}
function formatearFormulario($formulario)
{
    foreach ($formulario as $clave => $valor) {
        $formulario[$clave] = formatearDato($valor);
    }
    return $formulario;
}
function formatearPartidas($partidas)
{
    foreach ($partidas as $indice => $partida) {
        if (is_array($partida)) {
            foreach ($partida as $clave => $valor) {
                $partidas[$indice][$clave] = formatearDato($valor);
            }
        } else {
            $partidas[$indice] = formatearDato($partida);
        }
    }
    return $partidas;
}
function validarCreditos($conexionData, $clienteId, $conn)
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
    }
}
function validarExistenciasEdicionPedido($pedidoId, $conexionData, $claveSae, $partidasNuevas, $conn)
{
    if ($conn === false) {
        return ['success' => false, 'message' => 'No se pudo conectar', 'errors' => sqlsrv_errors()];
    }

    // 2) Mapa de cantidades nuevas por CVE_ART
    $nuevasMap = [];
    foreach ($partidasNuevas as $np) {
        $nuevasMap[$np['producto']] = (float)$np['cantidad'];
    }

    // 3) Leer partidas VIEJAS
    $tablaPart = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"
        . str_pad($claveSae, 2, '0', STR_PAD_LEFT) . "]";
    $cveDoc20 = str_pad(str_pad($pedidoId, 10, '0', STR_PAD_LEFT), 20, ' ', STR_PAD_LEFT);

    $sqlPart = "SELECT CVE_ART, CANT FROM $tablaPart WHERE CVE_DOC = ?";
    $stmtPart = sqlsrv_query($conn, $sqlPart, [$cveDoc20]);
    if ($stmtPart === false) {
        sqlsrv_close($conn);
        return ['success' => false, 'message' => 'Error al leer partidas', 'errors' => sqlsrv_errors()];
    }
    $viejasMap = [];
    while ($r = sqlsrv_fetch_array($stmtPart, SQLSRV_FETCH_ASSOC)) {
        $viejasMap[$r['CVE_ART']] = (float)$r['CANT'];
    }
    sqlsrv_free_stmt($stmtPart);

    // 4) Preparar consulta de inventario
    $tablaInv = "[{$conexionData['nombreBase']}].[dbo].[INVE"
        . str_pad($claveSae, 2, '0', STR_PAD_LEFT) . "]";
    $sqlInv = "SELECT COALESCE([EXIST],0) AS EXIST, COALESCE([APART],0) AS APART
               FROM $tablaInv WHERE CVE_ART = ?";

    $sinStock = [];
    $conStock = [];

    // 5) Para cada artículo viejo, calcular delta y disponibilidad
    foreach ($viejasMap as $cveArt => $cantVieja) {
        $cantNueva = $nuevasMap[$cveArt] ?? 0.0;
        // leer inventario
        $stmtInv = sqlsrv_query($conn, $sqlInv, [$cveArt]);
        if ($stmtInv === false) {
            sqlsrv_close($conn);
            return ['success' => false, 'message' => 'Error al verificar inventario', 'errors' => sqlsrv_errors()];
        }
        $row = sqlsrv_fetch_array($stmtInv, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtInv);

        $existencias = (float)$row['EXIST'];
        $ap = (float)$row['APART'];

        // delta>0: liberamos; delta<0: apartamos adicional
        $delta   = $cantVieja - $cantNueva;
        $apNuevo = max(0, $ap - $delta);
        $disponible = $existencias - $apNuevo;

        $info = [
            'producto'    => $cveArt,
            'existencias' => $existencias,
            'apartados'   => $apNuevo,
            'disponible'  => $disponible
        ];
        if ($disponible >= 0) {
            $productosConExistencia[] = $info;
        } else {
            $productosSinExistencia[] = $info;
        }
    }

    if (!empty($productosSinExistencia)) {
        return [
            'success'               => false,
            'exist'                 => true,
            'message'               => 'No hay suficientes existencias para algunos productos',
            'productosSinExistencia' => $productosSinExistencia ?? []
        ];
    } else {
        return [
            'success'                => true,
            'message'                => 'Existencias verificadas correctamente',
            'productosConExistencia' => $productosConExistencia ?? []
        ];
    }
}
function validarCreditoCliente($conexionData, $clienteId, $totalPedido, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT LIMCRED, SALDO FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [str_pad($clienteId, 10, ' ', STR_PAD_LEFT)];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$clienteData) {
        sqlsrv_close($conn);
        return [
            'success' => false,
            'saldoActual' => null,
            'limiteCredito' => null
        ];
    }

    $limiteCredito = (float)$clienteData['LIMCRED'];
    $saldoActual = (float)$clienteData['SALDO'];
    $puedeContinuar = ($saldoActual + $totalPedido) <= $limiteCredito;

    sqlsrv_free_stmt($stmt);

    // Devolver el resultado y los datos relevantes
    return [
        'success' => $puedeContinuar,
        'saldoActual' => $saldoActual,
        'limiteCredito' => $limiteCredito
    ];
}
function obtenerUltimoDato($conexionData, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "
        SELECT TOP 1 [CVE_INFO] 
        FROM $nombreTabla
        ORDER BY [CVE_INFO] DESC
    ";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $CVE_INFO = $row ? $row['CVE_INFO'] : null;
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);

    return $CVE_INFO;
}
function gaurdarDatosEnvio($conexionData, $clave, $formularioData, $envioData, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $claveCliente = $formularioData['cliente'];
    /*$datosCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $conn);
    if (!$datosCliente) {
        die(json_encode(['success' => false, 'message' => 'No se encontraron datos del cliente']));
    }*/
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Extraer los datos del formulario
    $CVE_INFO = obtenerUltimoDato($conexionData, $claveSae, $conn);
    $CVE_INFO = $CVE_INFO + 1;
    $CVE_CONS = "";
    $NOMBRE = $envioData['nombreContacto'];
    $CALLE = $envioData['direccion1Contacto'];
    $NUMINT = "";
    $NUMEXT = "S/N";
    $CRUZAMIENTOS = "";
    $CRUZAMIENTOS2 = "";
    $POB = "";
    $CURP = "";
    $REFERDIR = "";
    $CVE_ZONA = "";
    $CVE_OBS = "";
    $STRNOGUIA = "";
    $STRMODOENV = "";
    $FECHA_ENV = $envioData['diaAlta'];
    $NOMBRE_RECEP = "";
    $NO_RECEP = "";
    $FECHA_RECEP = "";
    //$COLONIA = "";
    $COLONIA = $envioData['direccion2Contacto'];
    $CODIGO = $envioData['codigoContacto'];
    $ESTADO = $envioData['estadoContacto'];
    $PAIS = "MEXICO";
    $MUNICIPIO = $envioData['municipioContacto'];
    $PAQUETERIA = "";
    $CVE_PED_TIEND = "";
    $F_ENTREGA = "";
    $R_FACTURA = "";
    $R_EVIDENCIA = "";
    $ID_GUIA = "";
    $FAC_ENV = "";
    $GUIA_ENV = "";
    $REG_FISC = "";
    $CVE_PAIS_SAT = "";
    $FEEDDOCUMENT_GUIA = "";
    // Crear la consulta SQL para insertar los datos en la base de datos
    $sql = "INSERT INTO $nombreTabla
    (CVE_INFO, CVE_CONS, NOMBRE, CALLE, NUMINT, NUMEXT,
    CRUZAMIENTOS, CRUZAMIENTOS2, POB, CURP, REFERDIR, CVE_ZONA, CVE_OBS,
    STRNOGUIA, STRMODOENV, FECHA_ENV, NOMBRE_RECEP, NO_RECEP,
    FECHA_RECEP, COLONIA, CODIGO, ESTADO, PAIS, MUNICIPIO,
    PAQUETERIA, CVE_PED_TIEND, F_ENTREGA, R_FACTURA, R_EVIDENCIA,
    ID_GUIA, FAC_ENV, GUIA_ENV, REG_FISC,
    CVE_PAIS_SAT, FEEDDOCUMENT_GUIA)
    VALUES 
    (?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?)";
    // Preparar los parámetros para la consulta
    $params = [
        $CVE_INFO,
        $CVE_CONS,
        $NOMBRE,
        $CALLE,
        $NUMINT,
        $NUMEXT,
        $CRUZAMIENTOS,
        $CRUZAMIENTOS2,
        $POB,
        $CURP,
        $REFERDIR,
        $CVE_ZONA,
        $CVE_OBS,
        $STRNOGUIA,
        $STRMODOENV,
        $FECHA_ENV,
        $NOMBRE_RECEP,
        $NO_RECEP,
        $FECHA_RECEP,
        $COLONIA,
        $CODIGO,
        $ESTADO,
        $PAIS,
        $MUNICIPIO,
        $PAQUETERIA,
        $CVE_PED_TIEND,
        $F_ENTREGA,
        $R_FACTURA,
        $R_EVIDENCIA,
        $ID_GUIA,
        $FAC_ENV,
        $GUIA_ENV,
        $REG_FISC,
        $CVE_PAIS_SAT,
        $FEEDDOCUMENT_GUIA
    ];
    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al guardar los datos de envio',
            'sql_error' => sqlsrv_errors() // Captura los errores de SQL Server
        ]));
    }
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    return $CVE_INFO;
}
function actualizarControl2($conexionData, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    //$noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "UPDATE $nombreTabla SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 70";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL01',
            'errors' => sqlsrv_errors()
        ]));
    }
    // Cerrar conexión
    sqlsrv_free_stmt($stmt);

    //echo json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarPedido($conexionData, $formularioData, $partidasData, $estatus, $DAT_ENVIO, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Actualizar las partidas asociadas al pedido
    actualizarPartidas($conexionData, $formularioData, $partidasData, $conn);


    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Extraer los datos del formulario
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $FECHA_DOC = $formularioData['diaAlta'];
    $FECHA_ENT = $formularioData['entrega'];

    $partidasActualizadas = obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae, $conn);

    $CAN_TOT = 0;
    $IMPORTE = 0;
    $DES_TOT = 0; // Variable para el importe con descuento
    $descuentoCliente = $formularioData['descuentoCliente']; // Valor del descuento en porcentaje (ejemplo: 10 para 10%)

    foreach ($partidasActualizadas as $partida) {
        $CAN_TOT += $partida['CANT'] * $partida['PREC']; // Sumar cantidades totales
        $IMPORTE += $partida['CANT'] * $partida['PREC']; // Calcular importe total
    }

    // Aplicar descuento
    $IMPORTT = $IMPORTE;
    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasActualizadas as $partida) {
        $precioUnitario = $partida['PREC'];
        $cantidad = $partida['CANT'];
        $IMPU4 = $partida['IMPU4'];
        $desc1 = $partida['DESC1'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**

        $DES = $totalPartida * ($desc1 / 100);
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTT + $IMP_TOT4 - $DES_TOT;
    //$IMPORTE = number_format($IMPORTE, 2);
    $IMPORTE = round(($IMPORTE), 2);
    $CVE_VEND = str_pad($formularioData['claveVendedor'], 5, ' ', STR_PAD_LEFT);
    $CONDICION = $formularioData['condicion'];
    // Crear la consulta SQL para actualizar el pedido
    $sql = "UPDATE $nombreTabla SET 
        FECHA_DOC = ?, 
        FECHA_ENT = ?, 
        CAN_TOT = ?, 
        IMPORTE = ?, 
        IMP_TOT4 = ?, 
        DES_TOT = ?, 
        CONDICION = ?, 
        CVE_VEND = ?,
        DAT_ENVIO = ?,
        STATUS = ? 
        WHERE CVE_DOC = ?";
    $params = [
        $FECHA_DOC,
        $FECHA_ENT,
        $CAN_TOT,
        $IMPORTE,
        $IMP_TOT4,
        $DES_TOT,
        $CONDICION,
        $CVE_VEND,
        $DAT_ENVIO,
        $estatus,
        $CVE_DOC
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el pedido', 'errors' => sqlsrv_errors()]));
    }

    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);

    return ['success' => true, 'message' => 'Pedido actualizado correctamente'];
}
function actualizarPartidas($conexionData, $formularioData, $partidasData, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $clave = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($clave, 20, ' ', STR_PAD_LEFT);
    // Iniciar transacción
    sqlsrv_begin_transaction($conn);

    // **1. Ajustar el inventario antes de modificar las partidas**
    $resultadoInventario = actualizarNuevoInventario($conexionData, $formularioData, $partidasData, $conn);
    if (!$resultadoInventario['success']) {
        sqlsrv_rollback($conn);
        die(json_encode($resultadoInventario));
    }
    $query = "SELECT CVE_ART, NUM_PAR FROM $nombreTabla WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener partidas existentes',
            'errors' => sqlsrv_errors()
        ]));
    }
    // 🔥 Depuración: Verificar si la consulta devuelve datos
    $partidasExistentes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $claveNormalizada = trim(strtoupper($row['CVE_ART'])); // Eliminar espacios y convertir en mayúsculas
        $partidasExistentes[$claveNormalizada] = $row['NUM_PAR'];
    }

    sqlsrv_free_stmt($stmt);

    $query = "SELECT MAX(NUM_PAR) AS NUM_PAR FROM $nombreTabla WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    $NUM_PAR = ($row && $row['NUM_PAR']) ? $row['NUM_PAR'] + 1 : 1; // Si no hay partidas, empieza desde 1

    // **3. Actualizar o insertar las partidas**
    foreach ($partidasData as $partida) {
        $CVE_UNIDAD = $partida['CVE_UNIDAD'];
        $CVE_PRODSERV = $partida['CVE_PRODSERV'];
        // Extraer los datos de la partida
        $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
        $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
        $CVE_ART = $partida['producto']; // Clave del producto
        $CANT = $partida['cantidad']; // Cantidad
        $COSTO_PROM = $partida['COSTO_PROM'];
        $IMPU1 = $partida['ieps']; // Impuesto 1
        if ($IMPU1 != 0) {
            $IMP1APLA = 0;
        } else {
            $IMP1APLA = 6;
        }
        $PREC = $partida['precioUnitario']; // Precio
        // Calcular los impuestos y totales
        $IMPU1 = $partida['ieps']; // Impuesto 1
        $IMPU3 = $partida['isr'];
        //$IMPU1 = 0;
        //$IMPU2 = $partida['impuesto2']; // Impuesto 2
        $IMPU2 = 0;
        $IMPU4 = $partida['iva']; // Impuesto 2
        // Agregar los cálculos para los demás impuestos...
        $DESC1 = $partida['descuento'];
        $DESC2 = 0;
        $COMI = $partida['comision'];
        $CVE_ESQIMPU = $formularioData['CVE_ESQIMPU'];
        $NUM_ALMA = $formularioData['almacen'];
        $UNI_VENTA = $partida['unidad'];
        if ($UNI_VENTA === 'No aplica' || $UNI_VENTA === 'SERVICIO' || $UNI_VENTA === 'Servicio') {
            $TIPO_PORD = 'S';
        } else {
            $TIPO_PORD = 'P';
        }
        $TOT_PARTIDA = $PREC * $CANT;
        $TOTIMP1 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU1 / 100);
        $TOTIMP2 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU2 / 100);
        $TOTIMP4 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU4 / 100);
        if (isset($partidasExistentes[$CVE_ART])) {
            $NUM_PAR_EXISTENTE = $partidasExistentes[$CVE_ART];
            // Si la partida ya existe, realizar un UPDATE
            $sql = "UPDATE $nombreTabla SET 
                CANT = ?, PXS = ?, PREC = ?, IMPU1 = ?, IMPU4 = ?, DESC1 = ?, DESC2 = ?, 
                TOTIMP1 = ?, TOTIMP4 = ?, TOT_PARTIDA = ? WHERE NUM_PAR = ? AND CVE_ART = ? AND CVE_DOC = ?";
            $params = [
                $CANT,
                $CANT,
                $PREC,
                $IMPU1,
                $IMPU4,
                $DESC1,
                $DESC2,
                $TOTIMP1,
                $TOTIMP4,
                $TOT_PARTIDA,
                $NUM_PAR_EXISTENTE,
                $CVE_ART,
                $CVE_DOC
            ];
        } else {
            // Si la partida no existe, realizar un INSERT
            $sql = "INSERT INTO $nombreTabla
                (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA,
                TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4,
                DESC1, DESC2, DESC3, COMI, APAR,
                ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, TIPO_PROD, CVE_OBS, REG_SERIE, E_LTPD, TIPO_ELEM, 
                NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, UUID,
                VERSION_SINC, ID_RELACION, PREC_NETO,
                CVE_PRODSERV, CVE_UNIDAD, IMPU8, IMPU7, IMPU6, IMPU5, IMP5APLA,
                IMP6APLA, TOTIMP8, TOTIMP7, TOTIMP6, TOTIMP5, IMP8APLA, IMP7APLA)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 6, 6, 0,
                ?, ?, 0, ?,
                ?, ?, 0, 0, ?,
                'N', ?, '', 1, ?, ?, 0, 0, 0, 'N',
                0, ?, 'S', 'N', 1, 0, 'C', 0, 0, ?, '',
                0, '', '',
                ?, ?, '', 0, 0, 0, 6,
                6, 0, 0, 0, 0, 6, 6)";

            $params = [
                $CVE_DOC,
                $NUM_PAR,
                $CVE_ART,
                $CANT,
                $CANT,
                $PREC,
                $COSTO_PROM,
                $IMPU1,
                $IMPU2,
                $IMPU3,
                $IMPU4,
                $IMP1APLA,
                $TOTIMP1,
                $TOTIMP2,
                $TOTIMP4,
                $DESC1,
                $DESC2,
                //$COMI, Comision estaba en el lugar de cantidad
                $CANT,
                $NUM_ALMA,
                $UNI_VENTA,
                $TIPO_PORD,
                $TOT_PARTIDA,
                $CVE_ESQIMPU,
                $CVE_PRODSERV,
                $CVE_UNIDAD
            ];
        }
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => 'Error al actualizar o insertar una partida', 'errors' => sqlsrv_errors()]));
        }
        $NUM_PAR++;
    }

    return ['success' => true, 'message' => 'Partidas actualizadas correctamente'];
}
function obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

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
function actualizarNuevoInventario($conexionData, $formularioData, $partidasData, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // Obtener las partidas anteriores del pedido
    $query = "SELECT CVE_ART, CANT FROM $nombreTablaPartidas WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener partidas anteriores', 'errors' => sqlsrv_errors()]));
    }

    $partidasAnteriores = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidasAnteriores[$row['CVE_ART']] = $row['CANT'];
    }
    sqlsrv_free_stmt($stmt);

    // Crear un array para facilitar la comparación con las nuevas partidas
    $partidasActuales = [];
    foreach ($partidasData as $partida) {
        $partidasActuales[$partida['producto']] = $partida['cantidad'];
    }

    // Ajustar el inventario
    foreach ($partidasAnteriores as $producto => $cantidadAnterior) {

        // Si el producto ya no está en las partidas actuales, fue eliminado
        if (!isset($partidasActuales[$producto])) {

            // Si el producto fue eliminado, agregar la cantidad anterior al inventario
            $sql = "UPDATE $nombreTablaInventario SET APART = APART - ? WHERE CVE_ART = ?";
            $params = [$cantidadAnterior, $producto];
        }
        // Si la cantidad fue reducida, ajustar la diferencia
        elseif ($partidasActuales[$producto] < $cantidadAnterior) {

            // Si la cantidad fue reducida, agregar la diferencia al inventario
            $diferencia = $cantidadAnterior - $partidasActuales[$producto];
            $sql = "UPDATE $nombreTablaInventario SET APART = APART - ? WHERE CVE_ART = ?";
            $params = [$diferencia, $producto];
        }
        // Si la cantidad fue aumentada, restar la diferencia del inventario
        elseif ($partidasActuales[$producto] > $cantidadAnterior) {

            $diferencia = $partidasActuales[$producto] - $cantidadAnterior;
            $sql = "UPDATE $nombreTablaInventario SET APART = APART + ? WHERE CVE_ART = ?";
            $params = [$diferencia, $producto];
        }
        // Si las cantidades son iguales, no se realiza ninguna acción
        else {
            continue;
        }

        // Ejecutar la consulta para actualizar el inventario
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => 'Error al actualizar inventario', 'errors' => sqlsrv_errors()]));
        }
    }

    // Verificar si hay productos nuevos en las partidas actuales
    foreach ($partidasActuales as $producto => $cantidadActual) {
        if (!isset($partidasAnteriores[$producto])) {
            // Si el producto es nuevo, restar la cantidad del inventario
            $sql = "UPDATE $nombreTablaInventario SET APART = APART - ? WHERE CVE_ART = ?";
            $params = [$cantidadActual, $producto];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                sqlsrv_rollback($conn);
                die(json_encode(['success' => false, 'message' => 'Error al agregar nuevo producto al inventario', 'errors' => sqlsrv_errors()]));
            }
        }
    }
    return ['success' => true, 'message' => 'Inventario actualizado correctamente'];
}
function generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $FOLIO, $conn)
{
    $rutaPDF = generarReportePedido($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $FOLIO, $conn);
    return $rutaPDF;
}
function actualizarDatosPedido($envioData, $FOLIO, $noEmpresa, $observaciones)
{
    global $firebaseProjectId, $firebaseApiKey;

    $idDocumento = $envioData['idDocumento']; // ← ID del documento a actualizar

    $fields = [
        'idPedido'            => ['integerValue' => (int)$FOLIO],
        'noEmpresa'           => ['integerValue' => (int)$noEmpresa],
        'nombreContacto'      => ['stringValue'  => $envioData['nombreContacto']],
        'companiaContacto'    => ['stringValue'  => $envioData['compañiaContacto']],
        'telefonoContacto'    => ['stringValue'  => $envioData['telefonoContacto']],
        'correoContacto'      => ['stringValue'  => $envioData['correoContacto']],
        'direccion1Contacto'  => ['stringValue'  => $envioData['direccion1Contacto']],
        'direccion2Contacto'  => ['stringValue'  => $envioData['direccion2Contacto']],
        'codigoContacto'      => ['stringValue'  => $envioData['codigoContacto']],
        'estadoContacto'      => ['stringValue'  => $envioData['estadoContacto']],
        'municipioContacto'   => ['stringValue'  => $envioData['municipioContacto']],
        //Datos adicionales
        'observaciones' => ['stringValue' => $observaciones ?? ""]
    ];

    // URL de actualización con ID del documento
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DATOS_PEDIDO/$idDocumento?key=$firebaseApiKey";

    $payload = json_encode(['fields' => $fields]);
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'PATCH', // ← PATCH en lugar de POST
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $error['message']]);
        exit;
    }

    // Extrae el nombre/id del documento creado en Firestore
    $resData = json_decode($response, true);
    if (isset($resData['name'])) {
        // El id es la última parte de la URL del campo "name"
        $parts = explode('/', $resData['name']);
        return end($parts);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo obtener el id del documento']);
        exit;
    }
    //echo json_encode(['success' => true, 'message' => 'Datos de envío actualizados']);
}
function validarCorreoClienteActualizacion($formularioData, $conexionData, $rutaPDF, $claveSae, $conCredito, $id, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $formularioData['enviar']; // Dirección de envío
    $vendedor = $formularioData['vendedor']; // Número de vendedor
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $formularioData['numero']; // Número de pedido
    /*$claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
     $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);*/

    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $partidasData = obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae, $conn);

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
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

    $fechaElaboracion = $formularioData['fechaAlta'];
    $correo = trim($clienteData['MAIL']);
    $emailPred = (is_null($clienteData['EMAILPRED'])) ? "" : trim($clienteData['EMAILPRED']); // Obtener el string completo de correos

    // Si hay múltiples correos separados por `;`, tomar solo el primero
    $emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    //$numeroWhatsApp = trim($clienteData['TELEFONO']);
    $numeroWhatsApp = (is_null($clienteData['TELEFONO'])) ? "" : trim($clienteData['TELEFONO']);
    $clienteNombre = trim($clienteData['NOMBRE']);
    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';*/

    $claveCliente = $clave;
    /*$emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';*/
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/ // Interzenda

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
    if (($correo === 'S' && isset($emailPred)) || isset($numeroWhatsApp)) {
        // Enviar notificaciones solo si los datos son válidos
        //if ($formularioData['enviarCorreo']) {
        if ($correoBandera === 0) {
            enviarCorreoActualizacion($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData, $id, $conn); // Enviar correo
        }
        /*} else {
            $correoBandera = 1;
        }*/
        //if ($formularioData['enviarWhats']) {
        if ($numeroBandera === 0) {
            $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            //$filename = "Pedido_" . urldecode($noPedido) . ".pdf";
            $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            //$filename = "Pedido_18456.pdf";
            //$resultadoWhatsApp = enviarWhatsAppConPlantillaActualizacion($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $id);
            $resultadoWhatsApp = enviarWhatsAppConPlantillaPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $id, $rutaPDFW, $filename);
        }
        /*}else {
            $numeroBandera = 1;
        }*/
        //Respuestas

        // Determinar la respuesta JSON según las notificaciones enviadas
        if ($correoBandera === 0 && $numeroBandera === 0) {
            echo json_encode([
                'success' => true,
                'message' => 'El pedido fue actualizado correctamente.',
            ]);
            sqlsrv_commit($conn);
            sqlsrv_close($conn);
            exit();
        } elseif ($correoBandera === 1 && $numeroBandera === 0) {
            echo json_encode(['success' => false, 'telefono' => true, 'message' => 'Pedido Realizado, el Cliente no tiene Correo para Notificar pero si WhatsApp.']);
            sqlsrv_commit($conn);
            sqlsrv_close($conn);
            exit();
        } elseif ($correoBandera === 0 && $numeroBandera === 1) {
            echo json_encode(['success' => false, 'correo' => true, 'message' => 'Pedido Realizado, el Cliente no Tiene WhatsApp para notifiar pero si Correo.']);
            sqlsrv_commit($conn);
            sqlsrv_close($conn);
            exit();
        } else {
            $emailPred = $_SESSION['usuario']['correo'];
            $numeroWhatsApp = $_SESSION['usuario']['telefono'];
            enviarCorreoActualizacion($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData, $id, $conn); // Enviar correo
            $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            //$filename = "Pedido_" . urldecode($noPedido) . ".pdf";
            $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            //$filename = "Pedido_18456.pdf";
            //$resultadoWhatsApp = enviarWhatsAppConPlantillaActualizacion($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $id);
            $resultadoWhatsApp = enviarWhatsAppConPlantillaPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $id, $rutaPDFW, $filename);
            echo json_encode(['success' => false, 'notificacion' => true, 'message' => 'Pedido Realizado, el Cliente no Tiene un Correo y WhatsApp para notificar.']);
            sqlsrv_commit($conn);
            sqlsrv_close($conn);
            exit();
        }
    } else {
        $emailPred = $_SESSION['usuario']['correo'];
        $numeroWhatsApp = $_SESSION['usuario']['telefono'];
        enviarCorreoActualizacion($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData, $id, $conn); // Enviar correo
        $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
        //$filename = "Pedido_" . urldecode($noPedido) . ".pdf";
        $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
        //$filename = "Pedido_18456.pdf";
        //$resultadoWhatsApp = enviarWhatsAppConPlantillaActualizacion($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $id);
        $resultadoWhatsApp = enviarWhatsAppConPlantillaPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $id, $rutaPDFW, $filename);
        echo json_encode(['success' => false, 'datos' => true, 'message' => 'Pedido Realizado, el Cliente no Tiene un Correo y WhatsApp para notificar.']);
        sqlsrv_commit($conn);
        sqlsrv_close($conn);
        exit();
    }
    sqlsrv_free_stmt($stmt);
}
function enviarCorreoActualizacion($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData, $idEnvios, $conn)
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
    //$correoRemitente = null;
    //$contraseñaRemitente = null;
    // Definir el correo de destino (puedes cambiarlo si es necesario)
    $correoDestino = $correo;

    // Obtener el nombre de la empresa desde la sesión
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'Empresa Desconocida';
    $vendedor = obtenerNombreVendedor($vendedor, $conexionData, $claveSae, $conn);
    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // URL base del servidor
    $urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "http://localhost/MDConnecta/Servidor/PHP";
    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito)  . "&idEnvios=" . urlencode($idEnvios);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa);

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
        $clave = $partida['CVE_ART'];
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = $partida['CANT'];
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

    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total a Pagar:</b> $" . number_format($IMPORTE, 2) . "</p>";

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
function enviarWhatsAppConPlantillaActualizacion($numero, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idEnvios)
{
    //$url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    //$token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // ✅ Verifica que los valores no estén vacíos
    if (empty($noPedido) || empty($claveSae)) {
        error_log("Error: noPedido o noEmpresa están vacíos.");
        return false;
    }
    $productosJson = urlencode(json_encode($partidasData));
    // ✅ Generar URLs dinámicas correctamente
    // ✅ Generar solo el ID del pedido en la URL del botón
    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idEnvios);
    //$urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae); // Solo pasamos el número de pedido  
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);


    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $clave = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];
        $totalPartida = $cantidad * $partida['PREC'];
        $total += $totalPartida;
        $IMPORTE = $total;
        $productosStr .= "$clave - $cantidad unidades, ";

        $IMPU4 = $partida['IMPU4'];
        $desc1 = $partida['DESC1'] ?? 0;
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
function obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae, $conn)
{
    // Aquí puedes realizar una consulta para obtener la descripción del producto basado en la clave
    // Asumiendo que la descripción está en una tabla llamada "productos"
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT DESCR FROM $nombreTabla WHERE CVE_ART = ?";
    $stmt = sqlsrv_query($conn, $sql, [$CVE_ART]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $descripcion = $row ? $row['DESCR'] : '';

    sqlsrv_free_stmt($stmt);

    return $descripcion;
}
function guardarPedidoActualizado($formularioData, $conexionData, $claveSae, $noEmpresa, $partidasData, $idEnvios, $conn)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Validar que se cuente con los datos mínimos requeridos
    if (empty($formularioData['numero']) || empty($formularioData['cliente'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos del pedido.']);
        return;
    }
    $SUBTOTAL = 0;
    $IMPORTE = 0;
    $descuentoCliente = $formularioData['descuentoCliente']; // Valor del descuento en porcentaje (ejemplo: 10 para 10%)
    foreach ($partidasData as $partida) {
        $SUBTOTAL += $partida['cantidad'] * $partida['precioUnitario']; // Sumar cantidades totales
        $IMPORTE += $partida['cantidad'] * $partida['precioUnitario']; // Calcular importe total
    }
    $IMPORTT = $IMPORTE;
    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;
    foreach ($partidasData as &$partida) {  // 🔹 Pasar por referencia para modificar el array
        $CVE_ART = $partida['producto'];
        $partida['descripcion'] = obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae, $conn);
    }
    // Preparar los campos que se guardarán en Firebase
    $fields = [
        'folio'       => ['stringValue' => $formularioData['numero'] ?? ''],
        'cliente'     => ['stringValue' => $formularioData['cliente'] ?? ''],
        'ordenCompra' => ['stringValue' => $formularioData['ordenCompra'] ?? ''],
        'enviar'      => ['stringValue' => $formularioData['enviar'] ?? ''],
        'vendedor'    => ['stringValue' => $formularioData['vendedor'] ?? ''],
        'diaAlta'     => ['stringValue' => $formularioData['fechaAlta'] ?? ''],
        'partidas'    => [
            'arrayValue' => ['values' => array_map(function ($p) {
                return [
                    'mapValue' => ['fields' => [
                        'cantidad'       => ['stringValue' => $p['cantidad'] ?? ''],
                        'producto'       => ['stringValue' => $p['producto'] ?? ''],
                        'unidad'         => ['stringValue' => $p['unidad'] ?? ''],
                        'descuento'      => ['stringValue' => $p['descuento'] ?? ''],
                        'ieps'           => ['stringValue' => $p['ieps'] ?? ''],
                        'impuesto2'      => ['stringValue' => $p['impuesto2'] ?? ''],
                        'isr'            => ['stringValue' => $p['isr'] ?? ''],
                        'iva'            => ['stringValue' => $p['iva'] ?? ''],
                        'comision'       => ['stringValue' => $p['comision'] ?? ''],
                        'precioUnitario' => ['stringValue' => $p['precioUnitario'] ?? ''],
                        'subtotal'       => ['stringValue' => $p['subtotal'] ?? ''],
                        'descripcion'    => ['stringValue' => $p['descripcion'] ?? ''],
                    ]]
                ];
            }, $partidasData)]
        ],
        'importe'    => ['doubleValue' => $IMPORTE ?? ''],
        'claveSae'   => ['stringValue' => $claveSae ?? ''],
        'noEmpresa'  => ['integerValue' => (int)$noEmpresa ?? ''],
        'status'     => ['stringValue' => 'Sin Autorizar'],
        'idEnvios' => ['stringValue' => $idEnvios ?? ''],
    ];

    /****/
    // Construir la URL para filtrar (usa el campo idPedido y noEmpresa)
    $collection = "PEDIDOS_AUTORIZAR";
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
                                "field" => ["fieldPath" => "folio"],
                                "op" => "EQUAL",
                                "value" => ["stringValue" => $formularioData['numero']]
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
    $idFirebasePedido = "";

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        if (isset($resultArray[0]['document']['name'])) {
            $name = $resultArray[0]['document']['name']; // p.ej. projects/proj/databases/(default)/documents/DATOS_PEDIDO/{id}
            $parts = explode('/', $name);
            $idFirebasePedido = end($parts); // <--- ESTE ES EL ID DEL DOCUMENTO CREADO EN FIREBASE
        }
    }
    /****/
    if ($idFirebasePedido === "") {
        // 🔎 Confirmar con runQuery que no exista
        $urlCheck = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";
        $payloadCheck = json_encode([
            "structuredQuery" => [
                "from" => [["collectionId" => $collection]],
                "where" => [
                    "compositeFilter" => [
                        "op" => "AND",
                        "filters" => [
                            [
                                "fieldFilter" => [
                                    "field" => ["fieldPath" => "folio"],
                                    "op" => "EQUAL",
                                    "value" => ["stringValue" => $formularioData['numero']]
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

        $optionsCheck = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => $payloadCheck,
            ]
        ];
        $contextCheck  = stream_context_create($optionsCheck);
        $responseCheck = @file_get_contents($urlCheck, false, $contextCheck);

        $resultCheck = $responseCheck ? json_decode($responseCheck, true) : [];
        if (isset($resultCheck[0]['document']['name'])) {
            // Si lo encuentra en el segundo runQuery, obtenemos el ID
            $parts = explode('/', $resultCheck[0]['document']['name']);
            $idFirebasePedido = end($parts);
        }

        if ($idFirebasePedido === "") {
            // 🚀 Crear porque no existe
            $url = "https://firestore.googleapis.com/v1/projects/"
                . "$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR?key=$firebaseApiKey";

            $method = "POST";
        } else {
            // ⚡ Ya existe → Actualizar
            $url = "https://firestore.googleapis.com/v1/projects/"
                . "$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR/$idFirebasePedido?key=$firebaseApiKey";

            $method = "PATCH";
        }
    } else {
        // ✅ Ya teníamos el ID → Confirmar con runQuery por seguridad
        $urlCheck = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";
        $payloadCheck = json_encode([
            "structuredQuery" => [
                "from" => [["collectionId" => $collection]],
                "where" => [
                    "compositeFilter" => [
                        "op" => "AND",
                        "filters" => [
                            [
                                "fieldFilter" => [
                                    "field" => ["fieldPath" => "folio"],
                                    "op" => "EQUAL",
                                    "value" => ["stringValue" => $formularioData['numero']]
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

        $optionsCheck = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => $payloadCheck,
            ]
        ];
        $contextCheck  = stream_context_create($optionsCheck);
        $responseCheck = @file_get_contents($urlCheck, false, $contextCheck);

        $resultCheck = $responseCheck ? json_decode($responseCheck, true) : [];
        if (isset($resultCheck[0]['document']['name'])) {
            $parts = explode('/', $resultCheck[0]['document']['name']);
            $idFirebasePedido = end($parts);
        }

        // ⚡ Forzar a PATCH porque sí existe
        $url = "https://firestore.googleapis.com/v1/projects/"
            . "$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR/$idFirebasePedido?key=$firebaseApiKey";

        $method = "PATCH";
    }

    // Enviar datos a Firestore (crear o actualizar)
    $payload = json_encode(['fields' => $fields]);
    file_put_contents("debug_payload.json", $payload);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => $method,
            'content' => $payload,
        ]
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => $error['message']]);
        exit;
    }
}
function enviarWhatsAppActualizado($formularioData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $credito, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $partidasData = obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae, $conn);

    // Configuración de la API de WhatsApp
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // Obtener datos del pedido
    $noPedido = $formularioData['numero'];
    $enviarA = $formularioData['enviar'];
    $vendedor = $formularioData['vendedor'];
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $fechaElaboracion = $formularioData['diaAlta'];
    $vendedor = formatearClaveVendedor($vendedor);
    // Obtener datos del cliente desde la base de datos
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE FROM $nombreTabla3 WHERE [CVE_VEND] = ?";
    $stmt = sqlsrv_query($conn, $sql, [$vendedor]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar al vendedor', 'errors' => sqlsrv_errors()]));
    }

    $vendedorData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$vendedorData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }

    // Obtener datos del cliente desde la base de datos
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
    $stmt = sqlsrv_query($conn, $sql, [$clave]);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $vendedor = trim(($vendedorData['NOMBRE']));

    //$clienteNombre = trim($clienteData['NOMBRE']);
    //$numeroTelefono = trim($clienteData['TELEFONO']); // Si no hay teléfono registrado, usa un número por defecto
    //$numero = "+527772127123"; //InterZenda AutorizaTelefono
    $numero = "+527773750925";
    //$numero = "+527773340218";
    //$numero = $_SESSION['usuario']['telefono'];
    // Obtener descripciones de los productos
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['CVE_ART'];
        $sqlProducto = "SELECT DESCR FROM $nombreTabla2 WHERE CVE_ART = ?";
        $stmtProducto = sqlsrv_query($conn, $sqlProducto, [$claveProducto]);

        if ($stmtProducto && $rowProducto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC)) {
            $partida['descripcion'] = $rowProducto['DESCR'];
        } else {
            $partida['descripcion'] = 'Descripción no encontrada';
        }

        sqlsrv_free_stmt($stmtProducto);
    }

    // Construcción del mensaje con los detalles del pedido
    $productosStr = "";
    $total = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];
        $precioUnitario = $partida['PREC'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $productosStr .= "$producto - $cantidad unidades, ";
    }
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    /*$mensajeProblema1 = "";
    $mensajeProblema2 = "";
    if ($validarSaldo == 1) {
        $mensajeProblema1 = "Saldo Vendido";
    }
    if ($credito == 1) {
        $mensajeProblema2 = "Credito Excedido";
    }

    // Definir el mensaje de problemas del cliente (Saldo vencido)
    $mensajeProblema = urlencode($mensajeProblema1) . urlencode($mensajeProblema2);*/
    $problemas = [];

    if ($validarSaldo == 1) {
        $problemas[] = "• Saldo Vencido";
    }
    if ($credito == 1) {
        $problemas[] = "• Crédito Excedido";
    }

    // Si hay problemas, los une con un espacio
    $mensajeProblema = !empty($problemas) ? implode(" ", $problemas) : "Sin problemas";


    // Construcción del JSON para enviar el mensaje de WhatsApp con plantilla
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "autorizar_pedido",
            "language" => ["code" => "es_MX"],
            "components" => [
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $vendedor], // {{1}} Vendedor
                        ["type" => "text", "text" => $noPedido], // {{2}} Número de pedido
                        ["type" => "text", "text" => $productosStr], // {{3}} Detalles de los productos
                        ["type" => "text", "text" => $mensajeProblema] // {{4}} Problema del cliente
                    ]
                ]
            ]
        ]
    ];

    // Enviar el mensaje de WhatsApp
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

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

    sqlsrv_free_stmt($stmt);
    return $result;
}


// -----------------------------------------------------------------------------------------------------//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    // Si es una solicitud POST, asignamos el valor de numFuncion
    $funtion = $_POST['numFuncion'];
    //var_dump($funcion);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    // Si es una solicitud GET, asignamos el valor de numFuncion
    $funtion = $_GET['numFuncion'];
    //var_dump($funcion);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la peticion.']);
    exit;
}
switch ($funtion) {
    case 1:
        $formularioData = json_decode($_POST['formulario'], true); // Datos del formulario desde JS
        $csrf_token  = $_SESSION['csrf_token'];
        $csrf_token_form = $formularioData['token'];
        if ($csrf_token === $csrf_token_form) {
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
            $claveSae = $_SESSION['empresa']['claveSae'];
            $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);

            if (!$conexionResult['success']) {
                echo json_encode($conexionResult);
                break;
            }
            $envioData = json_decode($_POST['envio'], true);
            $partidasData = json_decode($_POST['partidas'], true); // Datos de las partidas desde JS
            $conexionData = $conexionResult['data'];

            // Formatear los datos
            $formularioData = formatearFormulario($formularioData);
            $partidasData = formatearPartidas($partidasData);
            $conn = sqlsrv_connect($conexionData['host'], [
                "Database" => $conexionData['nombreBase'],
                "UID"      => $conexionData['usuario'],
                "PWD"      => $conexionData['password'],
                "CharacterSet"         => "UTF-8",
                "TrustServerCertificate" => true
            ]);
            if (!$conn) {
                throw new Exception("No pude conectar a la base de datos");
            }
            // Inicio transacción:
            sqlsrv_begin_transaction($conn);
            try {

                $clienteId = $formularioData['cliente'];
                $clave = formatearClaveCliente($clienteId);
                $dataCredito = json_decode(validarCreditos($conexionData, $clave, $conn), true);

                if ($dataCredito['success']) {
                    $conCredito = $dataCredito['conCredito'];
                } else {
                    $conCredito = "N";
                }
                $pedidoId = $formularioData['numero'];
                $resultadoValidacion = validarExistenciasEdicionPedido($pedidoId, $conexionData, $claveSae, $partidasData, $conn);
                /*var_dump($resultadoValidacion);
                die();*/
                //$resultadoValidacion = validarExistencias($conexionData, $partidasData, $claveSae);
                if ($resultadoValidacion['success']) {

                    // Calcular el total del pedido
                    $totalPedido = calcularTotalPedido($partidasData);

                    // Validar crédito del cliente
                    $validacionCredito = validarCreditoCliente($conexionData, $clave, $totalPedido, $claveSae, $conn);

                    if ($validacionCredito['success']) {
                        $credito = '0';
                    } else {
                        $credito = '1';
                    }

                    $validarSaldo = validarSaldo($conexionData, $clave, $claveSae, $conn);


                    /*if ($validarSaldo == 0 && $credito == 0) {
                        $estatus = "E";
                    } else if ($validarSaldo == 1 || $credito == 1) {
                        $estatus = "C";
                    }*/
                    $estatus = "E";
                    /*$estatus = "E";
                    $validarSaldo = 0;
                    $credito = 0;*/
                    // Lógica para edición de pedido
                    $DAT_ENVIO = gaurdarDatosEnvio($conexionData, $clave, $formularioData, $envioData, $claveSae, $conn); //ROLLBACK
                    actualizarControl2($conexionData, $claveSae, $conn); //ROLLBACK

                    $resultadoActualizacion = actualizarPedido($conexionData, $formularioData, $partidasData, $estatus, $DAT_ENVIO, $conn); //ROLLBACK

                    if ($resultadoActualizacion['success']) {
                        //actualizarDatoEnvio($DAT_ENVIO, $claveSae, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $envioData);
                        if ($validarSaldo === 0 && $credito == 0) {
                            $rutaPDF = generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $formularioData['numero'], $conn);
                            $id = actualizarDatosPedido($envioData, $formularioData['numero'], $noEmpresa, $formularioData['observaciones']);
                            validarCorreoClienteActualizacion($formularioData, $conexionData, $rutaPDF, $claveSae, $conCredito, $id, $conn);
                            exit();
                        } else {
                            //actualizarDatoEnvio($DAT_ENVIO, $claveSae, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $envioData);
                            $id = actualizarDatosPedido($envioData, $formularioData['numero'], $noEmpresa, $formularioData['observaciones'], $conn);
                            if ($conCredito == "S") {
                                guardarPedidoActualizado($formularioData, $conexionData, $claveSae, $noEmpresa, $partidasData, $id, $conn);
                                $resultado = enviarWhatsAppActualizado($formularioData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $conCredito, $conn);
                            } else {
                                //var_dump("Si");
                                $rutaPDF = generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $formularioData['numero'], $conn);
                                validarCorreoClienteActualizacion($formularioData, $conexionData, $rutaPDF, $claveSae, $conCredito, $id, $conn);
                            }
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => false,
                                'autorizacion' => true,
                                'message' => 'El pedido se completó pero debe ser autorizado.',
                            ]);
                            sqlsrv_commit($conn);
                            sqlsrv_close($conn);
                            exit();
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'No se pudo actualizar el pedido.',
                        ]);
                    }
                } else {
                    // Error de existencias
                    echo json_encode([
                        'success' => false,
                        'exist' => true,
                        'message' => $resultadoValidacion['message'],
                        'productosSinExistencia' => $resultadoValidacion['productosSinExistencia'],
                    ]);
                }
            } catch (Exception $e) {
                // Si falla cualquiera, deshacemos TODO:
                sqlsrv_rollback($conn);
                sqlsrv_close($conn);
                //return ['success' => false, 'message' => $e->getMessage()];
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
