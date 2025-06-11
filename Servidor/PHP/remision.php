<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php'; // Archivo de configuración de Firebase
include 'reportes.php';
require_once '../PHPMailer/clsMail.php';


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
                    'nombreBanco' => $fields['nombreBanco']['stringValue'] ?? "",
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function actualizarControl($conexionData, $claveSae)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8", // Aseguramos que todo sea manejado en UTF-8
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

    //$noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "UPDATE $nombreTabla SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 32";

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
    sqlsrv_close($conn);

    //echo json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarFolios($conexionData, $claveSae)
{
    // Establecer conexión con SQL Server
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
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Construcción dinámica de la tabla FOLIOSFXX
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Definir los parámetros de la actualización
    $fechaActual = date('Y-m-d 00:00:00.000'); // Fecha actual
    $tipDoc = 'R';  // Tipo de documento
    $serie = 'STAND.'; // Serie del folio
    $folioDesde = 1;  // FOLIODESDE

    // ✅ Consulta SQL para incrementar `ULT_DOC` en +1
    $sql = "UPDATE $nombreTabla 
            SET ULT_DOC = ULT_DOC + 1, 
                FECH_ULT_DOC = ? 
            WHERE TIP_DOC = ? AND SERIE = ? AND FOLIODESDE = ?";

    // Preparar los parámetros
    $params = [$fechaActual, $tipDoc, $serie, $folioDesde];

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al actualizar FOLIOSF',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => 'FOLIOSF actualizado correctamente (+1 en ULT_DOC)'
    ]);*/
}
function actualizarControl2($conexionData, $claveSae)
{
    // Establecer conexión con SQL Server
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
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Construcción dinámica de la tabla TBLCONTROLXX
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para incrementar ULT_CVE en +1 solo cuando es 0
    $sql = "UPDATE $nombreTabla 
            SET ULT_CVE = ULT_CVE + 1 
            WHERE ID_TABLA = 44";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 44)',
            'errors' => sqlsrv_errors()
        ]);
    }
    // Cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 44, +1 si ULT_CVE = 0)"
    ]);*/
}
function actualizarInve($conexionData, $pedidoId, $claveSae)
{
    // Establecer conexión con SQL Server
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
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // Construcción dinámica de las tablas PAR_FACTRXX e INVEXX
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sqlProductos = "SELECT DISTINCT CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ?";
    $params = [$pedidoId];

    $stmt = sqlsrv_query($conn, $sqlProductos, $params);

    if ($stmt === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
    }

    $sqlUpdate = "UPDATE $tablaInventario SET COSTO_PROM = 0 WHERE CVE_ART = ?";

    // Procesar cada producto del pedido
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        // Ejecutar la actualización para cada producto
        $paramsUpdate = [$cveArt];
        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

        if ($stmtUpdate === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar COSTO_PROM para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }

        sqlsrv_free_stmt($stmtUpdate);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "COSTO_PROM actualizado a 0 para todos los productos del pedido $pedidoId"
    ]);*/
}
function insertarMimve($conexionData, $pedidoId, $claveSae, $cveDoc)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Asegura que el ID del pedido tenga el formato correcto (10 caracteres con espacios a la izquierda)
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);
    // Tablas dinámicas
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaMovimientos = "[{$conexionData['nombreBase']}].[dbo].[MINVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener las partidas del pedido
    $sqlPartidas = "SELECT P.CVE_ART, P.NUM_ALM, P.PREC, P.COST, P.UNI_VENTA, F.CVE_CLPV, F.CVE_VEND, P.CANT
                    FROM $tablaPartidas P
                    INNER JOIN $tablaPedidos F ON P.CVE_DOC = F.CVE_DOC
                    WHERE P.CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // ✅ 2. Obtener valores incrementales para otros campos
    $sqlUltimos = "SELECT 
                    ISNULL(MAX(NUM_MOV), 0) + 1 AS NUM_MOV,
                    ISNULL(MAX(E_LTPD), 0) + 1 AS E_LTPD,
                    ISNULL(MAX(CAST(CVE_FOLIO AS INT)), 0) + 1 AS CVE_FOLIO
                   FROM $tablaMovimientos";

    $stmtUltimos = sqlsrv_query($conn, $sqlUltimos);
    if ($stmtUltimos === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener valores incrementales',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $ultimos = sqlsrv_fetch_array($stmtUltimos, SQLSRV_FETCH_ASSOC);
    $numMov = $ultimos['NUM_MOV'];
    $eLtpd = $ultimos['E_LTPD'];
    $cveFolio = $ultimos['CVE_FOLIO'];
    //$refer = $pedidoId;
    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $refer = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);
    // ✅ 3. Insertar los productos en MINVEXX
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        // Obtener datos del producto en INVEXX
        $sqlProducto = "SELECT EXIST FROM $tablaInventario WHERE CVE_ART = ?";
        $paramsProducto = [$row['CVE_ART']];
        $stmtProducto = sqlsrv_query($conn, $sqlProducto, $paramsProducto);

        if ($stmtProducto === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener datos del producto',
                'errors' => sqlsrv_errors()
            ]);
            die();
        }

        $producto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC);
        $existencia = $producto['EXIST'];

        // Datos para la inserción
        $almacen = $row['NUM_ALM'];
        $cveArt = $row['CVE_ART'];
        $cveCpto = 51; // Concepto de movimiento para la remisión
        $fechaDocu = date('Y-m-d H:i:s');
        $tipoDoc = 'R';
        $claveClpv = $row['CVE_CLPV'];
        $vendedor = $row['CVE_VEND'];
        $cantidad = $row['CANT'];
        $precio = $row['PREC'];
        $costo = $row['COST'];
        $uniVenta = $row['UNI_VENTA'];

        // Insertar en MINVEXX
        $sqlInsert = "INSERT INTO $tablaMovimientos 
            (CVE_ART, ALMACEN, NUM_MOV, CVE_CPTO, FECHA_DOCU, TIPO_DOC, REFER, CLAVE_CLPV, VEND, CANT, 
            CANT_COST, PRECIO, COSTO, REG_SERIE, UNI_VENTA, EXIST_G, EXISTENCIA, FACTOR_CON, 
            FECHAELAB, CVE_FOLIO, SIGNO, COSTEADO, COSTO_PROM_INI, COSTO_PROM_FIN, COSTO_PROM_GRAL, 
            DESDE_INVE, MOV_ENLAZADO) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $paramsInsert = [
            $cveArt,
            $almacen,
            $numMov,
            $cveCpto,
            $fechaDocu,
            $tipoDoc,
            $refer, // ✅ Ahora REFER es el ID del pedido
            $claveClpv,
            $vendedor,
            $cantidad,
            0,
            $precio,
            $costo,
            0,
            $uniVenta,
            $existencia,
            $existencia,
            1,
            $fechaDocu,
            $cveFolio,
            -1,
            'L',
            $costo,
            $costo,
            $costo,
            'N',
            0
        ];

        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
        if ($stmtInsert === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al insertar en MINVEXX para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }

        // ✅ Incrementar solo los valores necesarios
        $numMov++;
        $eLtpd++;

        sqlsrv_free_stmt($stmtProducto);
        sqlsrv_free_stmt($stmtInsert);
    }

    // ✅ 4. Cerrar conexiones
    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_free_stmt($stmtUltimos);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "MINVEXX actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function actualizarInve2($conexionData, $pedidoId, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);
    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener las partidas del pedido con CVE_ART y CANT
    $sqlPartidas = "SELECT CVE_ART, SUM(CANT) AS TOTAL_CANT FROM $tablaPartidas WHERE CVE_DOC = ? GROUP BY CVE_ART";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Fecha actual para `VERSION_SINC`
    $fechaSinc = date('Y-m-d H:i:s');
    // ✅ 2. Actualizar EXIST en INVEXX restando TOTAL_CANT de cada producto
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];
        $cantidad = $row['TOTAL_CANT'];
        $sqlUpdate = "UPDATE $tablaInventario 
                      SET EXIST = EXIST - ?, APART = APART - ?, VERSION_SINC = ?
                      WHERE CVE_ART = ?";
        $paramsUpdate = [$cantidad, $cantidad, $fechaSinc, $cveArt];

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
        if ($stmtUpdate === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar EXIST en INVEXX para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }
        print_r($stmtUpdate);
        sqlsrv_free_stmt($stmtUpdate);
    }

    // ✅ 3. Cerrar conexiones
    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function actualizarInve3($conexionData, $pedidoId, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener los productos del pedido
    $sqlPartidas = "SELECT DISTINCT CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $errores = [];
    // ✅ 2. Intentar actualizar `EDO_PUBL_ML` solo si es 'P' y continuar si hay errores
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        $sqlUpdate = "UPDATE $tablaInventario 
                      SET EDO_PUBL_ML = 'A' 
                      WHERE EDO_PUBL_ML = 'P' AND CVE_ART = ?";
        $paramsUpdate = [$cveArt];

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

        if ($stmtUpdate === false) {
            // Registrar el error, pero NO detener la ejecución
            $errores[] = "Error al actualizar INVEXX para el producto $cveArt: " . json_encode(sqlsrv_errors());
        } else {
            sqlsrv_free_stmt($stmtUpdate);
        }
    }

    // ✅ 3. Cerrar conexiones
    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);*/
}
function actualizarInveClaro($conexionData, $pedidoId, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventarioClaro = "[{$conexionData['nombreBase']}].[dbo].[INVEN_CLARO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Obtener los productos del pedido
    $sqlPartidas = "SELECT DISTINCT CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $errores = [];
    // Intentar actualizar `EDO_PUBL_CS` solo si es 'P' y continuar si hay errores
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        $sqlUpdate = "UPDATE $tablaInventarioClaro 
                      SET EDO_PUBL_CS = 'A' 
                      WHERE EDO_PUBL_CS = 'P' AND CVE_ART = ?";
        $paramsUpdate = [$cveArt];

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

        if ($stmtUpdate === false) {
            $errores[] = "Error al actualizar INVEN_CLARO01 para el producto $cveArt: " . json_encode(sqlsrv_errors());
        } else {
            sqlsrv_free_stmt($stmtUpdate);
        }
    }

    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "INVEN_CLARO01 actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);*/
}
function actualizarInveAmazon($conexionData, $pedidoId, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventarioAmazon = "[{$conexionData['nombreBase']}].[dbo].[INVE_AMAZON" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // Obtener los productos del pedido
    $sqlPartidas = "SELECT DISTINCT CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $errores = [];
    // Intentar actualizar `STATUS_INV`, `SUBMIT_INV_ID` y `EDO_PUBL_AM` y continuar si hay errores
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        $sqlUpdate = "UPDATE $tablaInventarioAmazon 
                      SET STATUS_INV = '', SUBMIT_INV_ID = '', EDO_PUBL_AM = 'A' 
                      WHERE CVE_ART = ?";
        $paramsUpdate = [$cveArt];

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

        if ($stmtUpdate === false) {
            $errores[] = "Error al actualizar INVE_AMAZON01 para el producto $cveArt: " . json_encode(sqlsrv_errors());
        } else {
            sqlsrv_free_stmt($stmtUpdate);
        }
    }

    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "INVE_AMAZON01 actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);*/
}
function actualizarAfac($conexionData, $pedidoId, $claveSae)
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
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);
    // Obtener el total de la venta, impuestos y descuentos del pedido
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sqlPedido = "SELECT CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8, DES_TOT, DES_FIN, COM_TOT, FECHA_DOC 
                  FROM $tablaPedidos 
                  WHERE CVE_DOC = ?";

    $paramsPedido = [$pedidoId];
    $stmtPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);

    if ($stmtPedido === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]));
    }

    $pedido = sqlsrv_fetch_array($stmtPedido, SQLSRV_FETCH_ASSOC);
    if (!$pedido) {
        die(json_encode([
            'success' => false,
            'message' => "No se encontraron datos del pedido $pedidoId"
        ]));
    }

    // 📌 Calcular valores a actualizar
    $totalVenta = $pedido['CAN_TOT']; // 📌 Total de venta
    $totalImpuestos = $pedido['IMP_TOT1'] + $pedido['IMP_TOT2'] + $pedido['IMP_TOT3'] + $pedido['IMP_TOT4'] +
        $pedido['IMP_TOT5'] + $pedido['IMP_TOT6'] + $pedido['IMP_TOT7'] + $pedido['IMP_TOT8']; // 📌 Suma de impuestos
    $totalDescuento = $pedido['DES_TOT']; // 📌 Descuento total
    $descuentoFinal = $pedido['DES_FIN']; // 📌 Descuento final
    $comisiones = $pedido['COM_TOT']; // 📌 Comisiones

    // 📌 Obtener el primer día del mes del pedido para PER_ACUM
    $perAcum = $pedido['FECHA_DOC']->format('Y-m-01 00:00:00');

    // 📌 Actualizar AFACT02
    $tablaAfact = "[{$conexionData['nombreBase']}].[dbo].[AFACT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sqlUpdate = "UPDATE $tablaAfact 
                  SET RVTA_COM = RVTA_COM + ?, 
                      RDESCTO = RDESCTO + ?, 
                      RDES_FIN = RDES_FIN + ?, 
                      RIMP = RIMP + ?, 
                      RCOMI = RCOMI + ? 
                  WHERE PER_ACUM = ?";

    $paramsUpdate = [$totalVenta, $totalDescuento, $descuentoFinal, $totalImpuestos, $comisiones, $perAcum];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al actualizar AFACT02',
            'errors' => sqlsrv_errors()
        ]));
    }

    sqlsrv_free_stmt($stmtPedido);
    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "AFACT02 actualizado correctamente para el período $perAcum"
    ]);*/
}
function actualizarControl3($conexionData, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Construcción dinámica de la tabla TBLCONTROLXX
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Consulta para incrementar ULT_CVE en +1 donde ID_TABLA = 62
    $sql = "UPDATE $nombreTabla 
            SET ULT_CVE = ULT_CVE + 1 
            WHERE ID_TABLA = 62";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 62)',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 62, +1 en ULT_CVE)"
    ]);*/
}
function actualizarControl6($conexionData, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Construcción dinámica de la tabla TBLCONTROLXX
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Consulta para incrementar ULT_CVE en +1 donde ID_TABLA = 58
    $sql = "UPDATE $nombreTabla 
            SET ULT_CVE = ULT_CVE + 1 
            WHERE ID_TABLA = 58";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 58)',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 58, +1 en ULT_CVE)"
    ]);*/
}
function obtenerUltimoDato($conexionData, $claveSae)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8", // Aseguramos que todo sea manejado en UTF-8
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
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
    sqlsrv_close($conn);

    return $CVE_INFO;
}
function gaurdarDatosEnvio($conexionData, $pedidoId, $claveSae)
{
    // Establecer la conexión con SQL Server con UTF-8
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $cve_doc = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);

    $sqlPedido = "SELECT DAT_ENVIO FROM $nombreTabla2 WHERE CVE_DOC = ?";
    $paramsPedido = [$cve_doc];
    $stmPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    $row = sqlsrv_fetch_array($stmPedido, SQLSRV_FETCH_ASSOC);
    $DAT_ENVIO = $row ? $row['DAT_ENVIO'] : null;


    $sqlSelect = "SELECT * FROM $nombreTabla WHERE CVE_INFO = ?";
    $paramsSelect = [$DAT_ENVIO];
    $stmSelect = sqlsrv_query($conn, $sqlSelect, $paramsSelect);
    // Obtener los datos
    $envioData = sqlsrv_fetch_array($stmSelect, SQLSRV_FETCH_ASSOC);


    // Extraer los datos del formulario
    $CVE_INFO = obtenerUltimoDato($conexionData, $claveSae);
    $CVE_INFO = $CVE_INFO + 1;
    $CVE_CONS = "";
    $NOMBRE = $envioData['NOMBRE'];
    $CALLE = $envioData['CALLE'];
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
    $FECHA_ENV = $envioData['FECHA_ENV'];
    $NOMBRE_RECEP = "";
    $NO_RECEP = "";
    $FECHA_RECEP = "";
    //$COLONIA = "";
    $COLONIA = $envioData['COLONIA'];
    $CODIGO = $envioData['CODIGO'];
    $ESTADO = $envioData['ESTADO'];
    $PAIS = "MEXICO";
    $MUNICIPIO = $envioData['MUNICIPIO'];
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
    sqlsrv_free_stmt($stmPedido);
    sqlsrv_free_stmt($stmSelect);
    sqlsrv_close($conn);
    return $CVE_INFO;
}
function insertarBita($conexionData, $pedidoId, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tablas dinámicas
    $tablaFolios = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaBita = "[{$conexionData['nombreBase']}].[dbo].[BITA" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener el `CVE_BITA` incrementado en 1
    $sqlUltimaBita = "SELECT ISNULL(MAX(CVE_BITA), 0) + 1 AS CVE_BITA FROM $tablaBita";
    $stmtUltimaBita = sqlsrv_query($conn, $sqlUltimaBita);

    if ($stmtUltimaBita === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener el último CVE_BITA',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $bitaData = sqlsrv_fetch_array($stmtUltimaBita, SQLSRV_FETCH_ASSOC);
    $cveBita = $bitaData['CVE_BITA'];

    // ✅ 2. Obtener el `CVE_DOC` de la próxima remisión (`ULT_DOC + 1`)
    $sqlFolioSiguiente = "SELECT ISNULL(MAX(ULT_DOC), 0) AS FolioSiguiente FROM $tablaFolios WHERE TIP_DOC = 'R'";
    $stmtFolioSiguiente = sqlsrv_query($conn, $sqlFolioSiguiente);

    if ($stmtFolioSiguiente === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener el próximo número de remisión',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $folioData = sqlsrv_fetch_array($stmtFolioSiguiente, SQLSRV_FETCH_ASSOC);
    $folioSiguiente = $folioData['FolioSiguiente'];
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);
    // ✅ 3. Obtener datos del pedido (`FACTPXX`) para calcular el total
    $sqlPedido = "SELECT CVE_CLPV, CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4 
                  FROM $tablaPedidos WHERE CVE_DOC = ?";
    $paramsPedido = [$pedidoId];

    $stmtPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    if ($stmtPedido === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $pedido = sqlsrv_fetch_array($stmtPedido, SQLSRV_FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron datos del pedido'
        ]);
        die();
    }

    $cveClie = $pedido['CVE_CLPV'];
    $totalPedido = $pedido['CAN_TOT'] + $pedido['IMP_TOT1'] + $pedido['IMP_TOT2'] + $pedido['IMP_TOT3'] + $pedido['IMP_TOT4'];
    $actividad = str_pad(3, 5, ' ', STR_PAD_LEFT);
    // ✅ 4. Formatear las observaciones
    $observaciones = "No.[$folioSiguiente] $" . number_format($totalPedido, 2);

    // ✅ 5. Insertar en `BITA01`
    $sqlInsert = "INSERT INTO $tablaBita 
        (CVE_BITA, CVE_CAMPANIA, STATUS, CVE_CLIE, CVE_USUARIO, NOM_USUARIO, OBSERVACIONES, FECHAHORA, CVE_ACTIVIDAD) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $paramsInsert = [
        $cveBita,
        '_SAE_',
        'F',
        $cveClie,
        1,
        'ADMINISTRADOR',
        $observaciones,
        date('Y-m-d H:i:s'),
        $actividad
    ];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en BITA01 con CVE_BITA $cveBita",
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUltimaBita);
    sqlsrv_free_stmt($stmtFolioSiguiente);
    sqlsrv_free_stmt($stmtPedido);
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_close($conn);
    return $cveBita;
    /*echo json_encode([
        'success' => true,
        'message' => "BITAXX insertado correctamente con CVE_BITA $cveBita y remisión $folioSiguiente"
    ]);*/
}
function insertarFactr($conexionData, $pedidoId, $claveSae, $CVE_BITA, $DAT_ENVIO, $DAT_MOSTR)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // Tablas dinámicas
    $tablaFolios = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaRemisiones = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener el nuevo `CVE_DOC`
    $sqlFolio = "SELECT ISNULL(MAX(ULT_DOC), 0) AS CVE_DOC FROM $tablaFolios WHERE TIP_DOC = 'R'";
    $stmtFolio = sqlsrv_query($conn, $sqlFolio);
    if ($stmtFolio === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener el nuevo CVE_DOC',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $folioData = sqlsrv_fetch_array($stmtFolio, SQLSRV_FETCH_ASSOC);
    $folio = $folioData['CVE_DOC'];
    $cveDoc = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);
    // ✅ 2. Obtener datos del pedido
    $sqlPedido = "SELECT * FROM $tablaPedidos WHERE CVE_DOC = ?";
    $paramsPedido = [$pedidoId];
    $stmtPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    if ($stmtPedido === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $pedido = sqlsrv_fetch_array($stmtPedido, SQLSRV_FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron datos del pedido'
        ]);
        die();
    }

    // ✅ 3. Definir valores constantes y calcular datos
    $fechaDoc = (new DateTime())->format('Y-m-d') . ' 00:00:00.000';
    $tipDoc = 'R';
    $status = 'E';
    $cvePedi = '';  // Vacío según la traza
    $tipDocE = 'F';
    $docAnt = $pedidoId;
    $tipDocAnt = 'P';

    // ✅ 4. Insertar en FACTRXX
    $sqlInsert = "INSERT INTO $tablaRemisiones 
        (TIP_DOC, CVE_DOC, CVE_CLPV, STATUS, DAT_MOSTR, CVE_VEND, CVE_PEDI, FECHA_DOC, FECHA_ENT, FECHA_VEN,
        CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4, DES_TOT, DES_FIN, COM_TOT, CVE_OBS, NUM_ALMA, ACT_CXC,
        ACT_COI, ENLAZADO, NUM_MONED, TIPCAMB, NUM_PAGOS, FECHAELAB, PRIMERPAGO, RFC, CTLPOL, ESCFD, AUTORIZA,
        SERIE, FOLIO, AUTOANIO, DAT_ENVIO, CONTADO, CVE_BITA, BLOQ, TIP_DOC_E, DES_FIN_PORC, DES_TOT_PORC,
        COM_TOT_PORC, IMPORTE, METODODEPAGO, NUMCTAPAGO, DOC_ANT, TIP_DOC_ANT, VERSION_SINC, FORMADEPAGOSAT,
        USO_CFDI, TIP_FAC, REG_FISC, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $paramsInsert = [
        $tipDoc,
        $cveDoc,
        $pedido['CVE_CLPV'],
        $status,
        $DAT_MOSTR,
        $pedido['CVE_VEND'],
        $cvePedi,
        $fechaDoc,
        $fechaDoc,
        $fechaDoc,
        $pedido['CAN_TOT'],
        $pedido['IMP_TOT1'],
        $pedido['IMP_TOT2'],
        $pedido['IMP_TOT3'],
        $pedido['IMP_TOT4'],
        $pedido['DES_TOT'],
        $pedido['DES_FIN'],
        $pedido['COM_TOT'],
        $pedido['CVE_OBS'],
        $pedido['NUM_ALMA'],
        $pedido['ACT_CXC'],
        $pedido['ACT_COI'],
        $pedido['ENLAZADO'],
        $pedido['NUM_MONED'],
        $pedido['TIPCAMB'],
        $pedido['NUM_PAGOS'],
        $pedido['FECHAELAB'],
        $pedido['PRIMERPAGO'],
        $pedido['RFC'],
        $pedido['CTLPOL'],
        $pedido['ESCFD'],
        $pedido['AUTORIZA'],
        $pedido['SERIE'],
        $folio,
        $pedido['AUTOANIO'],
        $DAT_ENVIO,
        $pedido['CONTADO'],
        $CVE_BITA,
        $pedido['BLOQ'],
        $tipDocE,
        $pedido['DES_FIN_PORC'],
        $pedido['DES_TOT_PORC'],
        $pedido['COM_TOT_PORC'],
        $pedido['IMPORTE'],
        $pedido['METODODEPAGO'],
        $pedido['NUMCTAPAGO'],
        $docAnt,
        $tipDocAnt,
        $fechaDoc,
        $pedido['FORMADEPAGOSAT'],
        $pedido['USO_CFDI'],
        $pedido['TIP_FAC'],
        $pedido['REG_FISC'],
        $pedido['IMP_TOT5'],
        $pedido['IMP_TOT6'],
        $pedido['IMP_TOT7'],
        $pedido['IMP_TOT8']
    ];

    if (count($paramsInsert) !== 57) {
        echo json_encode([
            'success' => false,
            'message' => "Error: La cantidad de valores en VALUES no coincide con las columnas en INSERT INTO",
            'expected_columns' => 57,
            'received_values' => count($paramsInsert),
            'values' => $paramsInsert
        ]);
        die();
    }

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en FACTRXX",
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_close($conn);

    return $folio;
    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX insertado correctamente con CVE_DOC $cveDoc"
    ]);*/
}
function insertarFactr_Clib($conexionData, $cveDoc, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tablas dinámicas
    $tablaFactrClib = "[{$conexionData['nombreBase']}].[dbo].[FACTR_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $claveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);


    // ✅ 2. Insertar en `FACTR_CLIB01`
    $sqlInsert = "INSERT INTO $tablaFactrClib (CLAVE_DOC) VALUES (?)";
    $paramsInsert = [$claveDoc];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en FACTR_CLIBXX con CVE_DOC $claveDoc",
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    //sqlsrv_free_stmt($stmtUltimaRemision);
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTR_CLIBXX insertado correctamente con CVE_DOC $claveDoc"
    ]);*/
}
function actualizarInve4($conexionData, $pedidoId, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);
    // Tablas dinámicas
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener las partidas del pedido
    $sqlPartidas = "SELECT P.CVE_ART, P.CANT, (P.PREC * P.CANT) AS TOTAL_PARTIDA, F.CVE_CLPV 
                    FROM $tablaPartidas P
                    INNER JOIN $tablaPedidos F ON P.CVE_DOC = F.CVE_DOC
                    WHERE P.CVE_DOC = ?";
    $paramsPartidas = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $paramsPartidas);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $fechaActual = date('Y-m-d H:i:s');

    // ✅ 2. Actualizar `INVEXX` y `CLIEXX` para cada partida
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];
        $cveClpv = $row['CVE_CLPV'];
        $cantidad = $row['CANT'];
        $totalPartida = $row['TOTAL_PARTIDA'];

        // 🔹 Actualizar `INVEXX`
        $sqlUpdateInve = "UPDATE $tablaInventario 
                          SET VTAS_ANL_C = VTAS_ANL_C + ?, 
                              VTAS_ANL_M = VTAS_ANL_M + ?, 
                              FCH_ULTVTA = ?, 
                              VERSION_SINC = ? 
                          WHERE CVE_ART = ?";
        $paramsUpdateInve = [$cantidad, $totalPartida, $fechaActual, $fechaActual, $cveArt];

        $stmtUpdateInve = sqlsrv_query($conn, $sqlUpdateInve, $paramsUpdateInve);
        if ($stmtUpdateInve === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar INVEXX para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }

        // 🔹 Actualizar `CLIEXX`
        /* $sqlUpdateClie = "UPDATE $tablaClientes
                          SET VTAS_ANL_C = VTAS_ANL_C + ?,
                              VTAS_ANL_M = VTAS_ANL_M + ?,
                              FCH_ULTVTA = ?,
                              VERSION_SINC = ?
                          WHERE CLAVE = ?";
        $paramsUpdateClie = [$cantidad, $totalPartida, $fechaActual, $fechaActual, $cveClpv];

        $stmtUpdateClie = sqlsrv_query($conn, $sqlUpdateClie, $paramsUpdateClie);
        if ($stmtUpdateClie === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar CLIEXX para el cliente $cveClpv",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }*/

        sqlsrv_free_stmt($stmtUpdateInve);
        //sqlsrv_free_stmt($stmtUpdateClie);
    }

    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX y CLIEXX actualizados correctamente para el pedido $pedidoId"
    ]);*/
}
function insertarPar_Factr($conexionData, $pedidoId, $cveDoc, $claveSae, $enlace)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // Tablas dinámicas
    $tablaPartidasPedido = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidasRemision = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaMovimientos = "[{$conexionData['nombreBase']}].[dbo].[MINVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Convertir `$enlace` en un array asociativo con `CVE_ART` como clave
    $enlaceMap = [];
    foreach ($enlace as $lote) {
        $enlaceMap[trim($lote['CVE_ART'])] = $lote['E_LTPD'];
    }

    // ✅ 2. Obtener las partidas del pedido (`PAR_FACTPXX`)
    $sqlPartidas = "SELECT NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, 
                           IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA, TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4, 
                           DESC1, DESC2, DESC3, COMI, APAR, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, 
                           TIPO_PROD, TIPO_ELEM, CVE_OBS, REG_SERIE, E_LTPD, IMPRIMIR, MAN_IEPS, 
                           MTO_PORC, MTO_CUOTA, CVE_ESQ, IMPU5, IMPU6, IMPU7, IMPU8, IMP5APLA, 
                           IMP6APLA, IMP7APLA, IMP8APLA, TOTIMP5, TOTIMP6, TOTIMP7, TOTIMP8 
                    FROM $tablaPartidasPedido WHERE CVE_DOC = ?";
    $paramsPartidas = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $paramsPartidas);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // ✅ 3. Obtener el `NUM_MOV` de `MINVEXX`
    $sqlNumMov = "SELECT ISNULL(MAX(NUM_MOV), 0) AS NUM_MOV FROM $tablaMovimientos";
    $stmtNumMov = sqlsrv_query($conn, $sqlNumMov);

    if ($stmtNumMov === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener NUM_MOV desde MINVEXX',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $numMovData = sqlsrv_fetch_array($stmtNumMov, SQLSRV_FETCH_ASSOC);
    $numMov = $numMovData['NUM_MOV'];

    // Fecha de sincronización
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 4. Insertar cada partida en `PAR_FACTRXX`
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $TOT_PARTIDA = $row['CANT'] * $row['PREC'];

        // **Buscar `E_LTPD` en `$enlaceMap`, si no existe, usar el valor original de `$row['E_LTPD']`**
        $eLtpd = isset($enlaceMap[trim($row['CVE_ART'])]) ? $enlaceMap[trim($row['CVE_ART'])] : $row['E_LTPD'];

        $sqlInsert = "INSERT INTO $tablaPartidasRemision 
            (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, 
            IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA, TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4, DESC1, 
            DESC2, DESC3, COMI, APAR, ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, 
            TIPO_PROD, TIPO_ELEM, CVE_OBS, REG_SERIE, E_LTPD, NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, 
            APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, VERSION_SINC, UUID,
            IMPU5, IMPU6, IMPU7, IMPU8, IMP5APLA, IMP6APLA, IMP7APLA, IMP8APLA, TOTIMP5, 
            TOTIMP6, TOTIMP7, TOTIMP8)
        VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, '',
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?)";

        $paramsInsert = [
            $cveDoc,
            $row['NUM_PAR'],
            $row['CVE_ART'],
            $row['CANT'],
            $row['PXS'],
            $row['PREC'],
            $row['COST'],
            $row['IMPU1'],
            $row['IMPU2'],
            $row['IMPU3'],
            $row['IMPU4'],
            $row['IMP1APLA'],
            $row['IMP2APLA'],
            $row['IMP3APLA'],
            $row['IMP4APLA'],
            $row['TOTIMP1'],
            $row['TOTIMP2'],
            $row['TOTIMP3'],
            $row['TOTIMP4'],
            $row['DESC1'],
            $row['DESC2'],
            $row['DESC3'],
            $row['COMI'],
            $row['APAR'],
            'S',
            $row['NUM_ALM'],
            $row['POLIT_APLI'],
            $row['TIP_CAM'],
            $row['UNI_VENTA'],
            $row['TIPO_PROD'],
            $row['TIPO_ELEM'],
            $row['CVE_OBS'],
            $row['REG_SERIE'],
            $eLtpd,
            $numMov,
            $TOT_PARTIDA,
            $row['IMPRIMIR'],
            $row['MAN_IEPS'],
            1,
            0,
            'C',
            $row['MTO_PORC'],
            $row['MTO_CUOTA'],
            $row['CVE_ESQ'],
            $fechaSinc,
            $row['IMPU5'],
            $row['IMPU6'],
            $row['IMPU7'],
            $row['IMPU8'],
            $row['IMP5APLA'],
            $row['IMP6APLA'],
            $row['IMP7APLA'],
            $row['IMP8APLA'],
            $row['TOTIMP5'],
            $row['TOTIMP6'],
            $row['TOTIMP7'],
            $row['TOTIMP8']
        ];

        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
        if ($stmtInsert === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al Insertar en Par_Factr',
                'errors' => sqlsrv_errors()
            ]);
            die();
        }
        $numMov++;
    }

    sqlsrv_close($conn);
}
function insertarPar_Factr_Clib($conexionData, $pedidoId, $cveDoc, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);
    // Tablas dinámicas
    $tablaPartidasPedido = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactrClib = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // ✅ 2. Contar el número de partidas del pedido en `PAR_FACTPXX`
    $sqlContarPartidas = "SELECT COUNT(*) AS TOTAL_PARTIDAS FROM $tablaPartidasPedido WHERE CVE_DOC = ?";
    $paramsContar = [$pedidoId];

    $stmtContar = sqlsrv_query($conn, $sqlContarPartidas, $paramsContar);
    if ($stmtContar === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al contar las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $partidasData = sqlsrv_fetch_array($stmtContar, SQLSRV_FETCH_ASSOC);
    if (!$partidasData) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron partidas en el pedido'
        ]);
        die();
    }

    $numPartidas = $partidasData['TOTAL_PARTIDAS'];
    // ✅ 3. Insertar en `PAR_FACTR_CLIB01`
    $sqlInsert = "INSERT INTO $tablaParFactrClib (CLAVE_DOC, NUM_PART) VALUES (?, ?)";
    $paramsInsert = [$cveDoc, $numPartidas];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en PAR_FACTR_CLIB01 con CVE_DOC $cveDoc",
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    //sqlsrv_free_stmt($stmtUltimaRemision);
    sqlsrv_free_stmt($stmtContar);
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "PAR_FACTR_CLIB01 insertado correctamente con CVE_DOC $cveDoc y $numPartidas partidas"
    ]);*/
}
function actualizarAlerta_Usuario($conexionData, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tabla dinámica
    $tablaAlertaUsuario = "[{$conexionData['nombreBase']}].[dbo].[ALERTA_USUARIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA_USUARIO01`
    $sqlUpdate = "UPDATE $tablaAlertaUsuario 
                  SET ACTIVA = ? 
                  WHERE ID_USUARIO = ? AND CVE_ALERTA = ?";
    $paramsUpdate = ['S', 1, 4];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTA_USUARIO01',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA_USUARIO01 actualizada correctamente"
    ]);*/
}
function actualizarAlerta($conexionData, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tabla dinámica
    $tablaAlerta = "[{$conexionData['nombreBase']}].[dbo].[ALERTA" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA01`
    $sqlUpdate = "UPDATE $tablaAlerta 
                  SET CANT_DOC = ? 
                  WHERE CVE_ALERTA = ?";
    $paramsUpdate = [0, 4];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTA01',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA01 actualizada correctamente"
    ]);*/
}
function actualizarMulti($conexionData, $pedidoId, $claveSae)
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
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);
    // Construcción dinámica de las tablas
    $tablaMulti = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener los productos y almacenes del pedido
    $sqlProductos = "SELECT CVE_ART, NUM_ALM, SUM(CANT) AS TOTAL_CANT
                     FROM $tablaPartidas 
                     WHERE CVE_DOC = ? 
                     GROUP BY CVE_ART, NUM_ALM";
    $params = [$pedidoId];

    $stmtProductos = sqlsrv_query($conn, $sqlProductos, $params);
    if ($stmtProductos === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]));
    }

    // ✅ 2. Actualizar MULTXX por cada producto
    while ($row = sqlsrv_fetch_array($stmtProductos, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];
        $numAlm = $row['NUM_ALM'];
        $cantidad = $row['TOTAL_CANT']; // Se suma la cantidad total por producto y almacén

        // ✅ Consulta basada en la traza
        $sqlUpdate = "UPDATE $tablaMulti 
                      SET PEND_SURT = 
                      (CASE 
                          WHEN PEND_SURT IS NULL THEN (CASE WHEN ? < 0.0 THEN 0.0 ELSE ? END) 
                          WHEN PEND_SURT + ? < 0.0 THEN 0.0  
                          WHEN PEND_SURT + ? >= 0.0 THEN PEND_SURT + ?  
                          ELSE 0.0 
                      END)
                      WHERE CVE_ART = ? AND CVE_ALM = ?";

        // Parámetros dinámicos
        $paramsUpdate = [
            -$cantidad,
            -$cantidad,
            -$cantidad,
            -$cantidad,
            -$cantidad,
            $cveArt,
            $numAlm
        ];

        // Ejecutar la consulta de actualización
        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

        if ($stmtUpdate === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar MULTXX para el producto $cveArt en almacén $numAlm",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }

        sqlsrv_free_stmt($stmtUpdate);
    }

    sqlsrv_free_stmt($stmtProductos);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "MULTXX actualizado correctamente para los productos del pedido $pedidoId"
    ]);*/
}
function actualizarInve5($conexionData, $pedidoId, $claveSae)
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
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);
    // Construcción dinámica de las tablas
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener los productos del pedido con CVE_ART y CANT
    $sqlProductos = "SELECT CVE_ART, SUM(CANT) AS TOTAL_CANT 
                     FROM $tablaPartidas 
                     WHERE CVE_DOC = ? 
                     GROUP BY CVE_ART";
    $params = [$pedidoId];

    $stmtProductos = sqlsrv_query($conn, $sqlProductos, $params);
    if ($stmtProductos === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]));
    }

    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 2. Actualizar `PEND_SURT` en `INVE01`
    while ($row = sqlsrv_fetch_array($stmtProductos, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];
        $cantidad = $row['TOTAL_CANT'];

        $sqlUpdate = "UPDATE $tablaInventario 
                      SET PEND_SURT = 
                      (CASE 
                          WHEN PEND_SURT + ? < 0 THEN 0         
                          WHEN PEND_SURT + ? >= 0 THEN PEND_SURT + ?  
                          ELSE 0 
                      END),
                      VERSION_SINC = ?
                      WHERE CVE_ART = ?";

        $paramsUpdate = [-$cantidad, -$cantidad, -$cantidad, $fechaSinc, $cveArt];

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
        if ($stmtUpdate === false) {
            die(json_encode([
                'success' => false,
                'message' => "Error al actualizar PEND_SURT en INVEXX para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]));
        }

        sqlsrv_free_stmt($stmtUpdate);
    }

    sqlsrv_free_stmt($stmtProductos);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para los productos del pedido $pedidoId"
    ]);*/
}
function actualizarControl4($conexionData, $claveSae)
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

    // Construcción dinámica de la tabla TBLCONTROLXX
    $tablaControl = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Consulta para incrementar ULT_CVE en +1 donde ID_TABLA = 70
    $sql = "UPDATE $tablaControl 
            SET ULT_CVE = ULT_CVE + 1 
            WHERE ID_TABLA = 70";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 70)',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 70, +1 en ULT_CVE)"
    ]);*/
}
function actualizarControl5($conexionData, $claveSae)
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

    // Construcción dinámica de la tabla TBLCONTROLXX
    $tablaControl = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Consulta para incrementar ULT_CVE en +1 donde ID_TABLA = 67
    $sql = "UPDATE $tablaControl 
            SET ULT_CVE = ULT_CVE + 1 
            WHERE ID_TABLA = 67";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 67)',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 67, +1 en ULT_CVE)"
    ]);*/
}
function actualizarMulti2($conexionData, $pedidoId, $claveSae)
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

    // Construcción dinámica de las tablas MULTXX y PAR_FACTRXX
    $tablaMulti = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // ✅ 1. Obtener los productos y almacenes del pedido
    $sqlProductos = "SELECT CVE_ART, CANT, NUM_ALM FROM $tablaPartidas WHERE CVE_DOC = ?";
    $paramsProductos = [$pedidoId];

    $stmtProductos = sqlsrv_query($conn, $sqlProductos, $paramsProductos);

    if ($stmtProductos === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // ✅ 2. Verificar existencia en MULTXX antes de actualizar
    $sqlExistencia = "SELECT EXIST FROM $tablaMulti WHERE CVE_ART = ? AND CVE_ALM = ?";
    $sqlUpdate = "UPDATE $tablaMulti 
                  SET EXIST = EXIST - ?, 
                      VERSION_SINC = ? 
                  WHERE CVE_ART = ? 
                    AND CVE_ALM = ?";

    $fechaSinc = date('Y-m-d H:i:s'); // Fecha de sincronización actual

    while ($row = sqlsrv_fetch_array($stmtProductos, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];
        $cveAlm = $row['NUM_ALM'];
        $cveCan = $row['CANT'];
        // Obtener la existencia actual
        $paramsExist = [$cveArt, $cveAlm];
        $stmtExist = sqlsrv_query($conn, $sqlExistencia, $paramsExist);

        if ($stmtExist === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al verificar existencia en MULTXX para el producto $cveArt en almacén $cveAlm",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }

        $existencia = sqlsrv_fetch_array($stmtExist, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtExist);

        // Solo actualizar si EXIST <= 0

        if ($existencia && $existencia['EXIST'] >= 0) {

            $paramsUpdate = [$cveCan, $fechaSinc, $cveArt, $cveAlm];

            $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
            if ($stmtUpdate === false) {
                echo json_encode([
                    'success' => false,
                    'message' => "Error al actualizar MULTXX para el producto $cveArt en almacén $cveAlm",
                    'errors' => sqlsrv_errors()
                ]);
                die();
            }
            sqlsrv_free_stmt($stmtUpdate);
        }
    }

    // Cerrar conexiones
    sqlsrv_free_stmt($stmtProductos);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "MULTXX actualizado correctamente para los productos del pedido $pedidoId"
    ]);*/
}
function actualizarPar_Factp($conexionData, $pedidoId, $cveDoc, $claveSae)
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

    // Tablas dinámicas
    $tablaPartidasRemision = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidasPedido = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // ✅ 1. Obtener las partidas de la remisión
    $sqlPartidas = "SELECT NUM_PAR, CVE_ART, CANT FROM $tablaPartidasRemision WHERE CVE_DOC = ?";
    $paramsPartidas = [$cveDoc];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $paramsPartidas);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener las partidas de la remisión',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // ✅ 2. Actualizar `PXS` en `PAR_FACTRXX`
    $sqlUpdate = "UPDATE $tablaPartidasPedido
                  SET PXS = (CASE 
                                WHEN PXS < ? THEN 0 
                                ELSE PXS - ? 
                             END)
                  WHERE CVE_DOC = ? AND NUM_PAR = ? AND CVE_ART = ?";

    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $numPar = $row['NUM_PAR'];
        $cveArt = $row['CVE_ART'];
        $cantidad = $row['CANT'];
        $paramsUpdate = [$cantidad, $cantidad, $pedidoId, $numPar, $cveArt];

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
        if ($stmtUpdate === false) {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar PAR_FACTRXX para el producto $cveArt en el pedido $pedidoId",
                'errors' => sqlsrv_errors()
            ]);
            die();
        }

        sqlsrv_free_stmt($stmtUpdate);
    }

    // Cerrar conexiones
    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "PAR_FACTRXX actualizado correctamente para el pedido $pedidoId y Remision $cveDoc"
    ]);*/
}
function actualizarFactp($conexionData, $pedidoId, $claveSae)
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

    // Tablas dinámicas
    $tablaFactp = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactp = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Formatear el pedidoId (CVE_DOC)
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // Fecha de sincronización
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 1. Consulta para actualizar FACTRXX
    $sqlUpdate = "UPDATE $tablaFactp 
                  SET TIP_DOC_E = ?, 
                      VERSION_SINC = ?, 
                      ENLAZADO = (CASE 
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactp P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactp.CVE_DOC = P.CVE_DOC) = 0 THEN 'T'
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactp P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactp.CVE_DOC = P.CVE_DOC) > 0 THEN 'P' 
                                    ELSE ENLAZADO END)
                  WHERE CVE_DOC = ?";

    $paramsUpdate = ['R', $fechaSinc, $pedidoId, $pedidoId, $pedidoId];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar FACTPXX para el pedido $pedidoId",
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function actualizarFactp2($conexionData, $pedidoId, $cveDocRemision, $claveSae)
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

    // Tablas dinámicas
    $tablaFactp = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Formatear los valores para SQL Server
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    $cveDocRemision = str_pad($cveDocRemision, 10, '0', STR_PAD_LEFT);
    $cveDocRemision = str_pad($cveDocRemision, 20, ' ', STR_PAD_LEFT);

    // ✅ Actualizar DOC_SIG y TIP_DOC_SIG en FACTRXX
    $sqlUpdate = "UPDATE $tablaFactp 
                  SET DOC_SIG = ?, 
                      TIP_DOC_SIG = ? 
                  WHERE CVE_DOC = ?";

    $paramsUpdate = [$cveDocRemision, 'R', $pedidoId];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar FACTPXX para el pedido $pedidoId",
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX actualizado correctamente para el pedido $pedidoId con remision $cveDocRemision"
    ]);*/
}
function actualizarFactp3($conexionData, $pedidoId, $claveSae)
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

    // Tablas dinámicas
    $tablaFactp = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactp = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Formatear el pedidoId para SQL Server
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // ✅ Ejecutar la actualización de `TIP_FAC`
    $sqlUpdate = "UPDATE $tablaFactp 
                  SET TIP_FAC = (
                      CASE 
                          WHEN (SELECT SUM(P.PXS) 
                                FROM $tablaParFactp P 
                                WHERE P.CVE_DOC = ? 
                                AND $tablaFactp.CVE_DOC = P.CVE_DOC) = 0 
                          THEN 'P' 
                          ELSE TIP_FAC 
                      END) 
                  WHERE CVE_DOC = ?";

    $paramsUpdate = [$pedidoId, $pedidoId];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar TIP_FAC en FACTPXX para el pedido $pedidoId",
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function insertarDoctoSig($conexionData, $pedidoId, $cveDoc, $claveSae)
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

    // ✅ Formatear los IDs para que sean de 10 caracteres con espacios a la izquierda
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);
    // Tabla dinámica
    $tablaDoctoSig = "[{$conexionData['nombreBase']}].[dbo].[DOCTOSIGF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Insertar relación: Pedido -> Remisión (S = Sigue)
    $sqlInsert1 = "INSERT INTO $tablaDoctoSig 
        (TIP_DOC, CVE_DOC, ANT_SIG, TIP_DOC_E, CVE_DOC_E, PARTIDA, PART_E, CANT_E) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $params1 = ['P', $pedidoId, 'S', 'R', $cveDoc, 1, 1, 1];

    $stmt1 = sqlsrv_query($conn, $sqlInsert1, $params1);
    if ($stmt1 === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al insertar relación Pedido -> Remisión en DOCTOSIGFXX",
            'errors' => sqlsrv_errors()
        ]));
    }

    // ✅ 2. Insertar relación: Remisión -> Pedido (A = Anterior)
    $sqlInsert2 = "INSERT INTO $tablaDoctoSig 
        (TIP_DOC, CVE_DOC, ANT_SIG, TIP_DOC_E, CVE_DOC_E, PARTIDA, PART_E, CANT_E) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $params2 = ['R', $cveDoc, 'A', 'P', $pedidoId, 1, 1, 1];

    $stmt2 = sqlsrv_query($conn, $sqlInsert2, $params2);
    if ($stmt2 === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al insertar relación Remisión -> Pedido en DOCTOSIGFXX",
            'errors' => sqlsrv_errors()
        ]));
    }

    // ✅ Cerrar conexión
    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "DOCTOSIGFXX insertado correctamente para Pedido $pedidoId y Remisión $cveDoc"
    ]);*/
}
function insertarInfenvio($conexionData, $pedidoId, $cveDoc, $claveSae)
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

    // Tablas dinámicas
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInfenvio = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // 📌 1. Obtener el nuevo `CVE_INFO` (secuencial)
    $sqlUltimoCveInfo = "SELECT ISNULL(MAX(CVE_INFO), 0) + 1 AS NUEVO_CVE_INFO FROM $tablaInfenvio";
    $stmtUltimoCveInfo = sqlsrv_query($conn, $sqlUltimoCveInfo);

    if ($stmtUltimoCveInfo === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener el último CVE_INFO',
            'errors' => sqlsrv_errors()
        ]));
    }

    $rowCveInfo = sqlsrv_fetch_array($stmtUltimoCveInfo, SQLSRV_FETCH_ASSOC);
    $cveInfo = $rowCveInfo['NUEVO_CVE_INFO']; // Nuevo ID secuencial

    // 📌 2. Obtener datos del pedido y del cliente
    $sqlPedido = "SELECT 
                    P.CVE_DOC, P.CVE_CLPV, P.FECHA_ENT, P.NUM_ALMA, 
                    C.NOMBRE, C.CALLE, C.NUMINT, C.NUMEXT, C.LOCALIDAD, 
                    C.ESTADO, C.PAIS, C.MUNICIPIO, C.CODIGO
                  FROM $tablaPedidos P
                  INNER JOIN $tablaClientes C ON P.CVE_CLPV = C.CLAVE
                  WHERE P.CVE_DOC = ?";

    $paramsPedido = [$pedidoId];
    $stmtPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);

    if ($stmtPedido === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]));
    }

    $pedido = sqlsrv_fetch_array($stmtPedido, SQLSRV_FETCH_ASSOC);
    if (!$pedido) {
        die(json_encode([
            'success' => false,
            'message' => 'No se encontraron datos del pedido'
        ]));
    }

    // 📌 3. Definir valores para la inserción
    $fechaEnvio = date('Y-m-d H:i:s'); // Fecha de envío
    $codigoPostal = $pedido['CODIGO']; // Código postal del cliente

    // 📌 4. Insertar en `INFENVIOXX`
    $sqlInsert = "INSERT INTO $tablaInfenvio (
                    CVE_INFO, NOMBRE, CALLE, NUMINT, NUMEXT, POB, ESTADO, PAIS, MUNICIPIO, CODIGO, FECHA_ENV
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $paramsInsert = [
        $cveInfo,
        $pedido['NOMBRE'],
        $pedido['CALLE'],
        $pedido['NUMINT'],
        $pedido['NUMEXT'],
        $pedido['LOCALIDAD'],
        $pedido['ESTADO'],
        $pedido['PAIS'],
        $pedido['MUNICIPIO'],
        $codigoPostal,
        $fechaEnvio
    ];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al insertar en INFENVIOXX',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUltimoCveInfo);
    sqlsrv_free_stmt($stmtPedido);
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "INFENVIOXX insertado correctamente con CVE_INFO $cveInfo para el pedido $pedidoId"
    ]);*/
}


function generarPDFP($conexionData, $cveDoc, $claveSae, $noEmpresa, $vendedor)
{
    generarReporteRemision($conexionData, $cveDoc, $claveSae, $noEmpresa, $vendedor);
}

function actualizarDatosComanda($firebaseProjectId, $firebaseApiKey, $pedidoId, $enlace)
{
    $urlComanda = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    $response = @file_get_contents($urlComanda);
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las comandas.']);
        return;
    }

    $data = json_decode($response, true);
    if (empty($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron comandas.']);
        return;
    }

    foreach ($data['documents'] as $document) {
        $fields = $document['fields'] ?? [];
        if (($fields['folio']['stringValue'] ?? '') !== $pedidoId) {
            continue;
        }

        // Extraer el ID del doc (la parte después de /COMANDA/)
        $pathParts = explode('/', $document['name']);
        $docId = end($pathParts);

        // Traer y modificar el array de productos
        $productos = $fields['productos']['arrayValue']['values'] ?? [];
        foreach ($productos as &$producto) {
            $pf = &$producto['mapValue']['fields'];
            if (($pf['clave']['stringValue'] ?? '') === $enlace['CVE_ART']) {
                // CREA el campo 'lote' (si no existía) o lo SOBREESCRIBE
                $pf['lote'] = ['stringValue' => $enlace['LOTE']];
            }
        }
        unset($producto);

        // Preparamos payload sólo con 'productos' reescrito
        $fieldsToSave = [
            'productos' => [
                'arrayValue' => [
                    'values' => $productos
                ]
            ]
        ];
        $payload = json_encode(['fields' => $fieldsToSave]);

        // URL de PATCH con mask para sólo ese array
        $urlUpdate = "https://firestore.googleapis.com/v1/projects/"
            . "$firebaseProjectId/databases/(default)/documents/COMANDA/"
            . "$docId?updateMask.fieldPaths=productos&key=$firebaseApiKey";

        // Creamos el contexto HTTP
        $ctx = stream_context_create([
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'PATCH',
                'content' => $payload,
            ]
        ]);

        // Un solo file_get_contents
        $result = @file_get_contents($urlUpdate, false, $ctx);
        if ($result === FALSE) {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar la comanda $docId."
            ]);
        } else {
            /*echo json_encode([
                'success' => true,
                'message' => "Comanda $docId actualizada correctamente."
            ]);*/
        }
        return;
    }

    // Si nunca encontramos ningún folio coincidente:
    echo json_encode([
        'success' => false,
        'message' => "Comanda con folio $pedidoId no encontrada."
    ]);
}
function remisionarComanda($firebaseProjectId, $firebaseApiKey, $pedidoId, $folio)
{
    $urlComanda = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    $response = @file_get_contents($urlComanda);
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las comandas.']);
        return;
    }

    $data = json_decode($response, true);
    if (empty($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron comandas.']);
        return;
    }
    $docId = "";

    foreach ($data['documents'] as $document) {
        $fields = $document['fields'] ?? [];
        if (($fields['folio']['stringValue'] ?? '') === $pedidoId) {
            $parts = explode('/', $document['name']);
            $docId = end($parts);
            break;  // ¡rompemos el bucle apenas lo encontremos!
        }
    }
    if (!$docId) {
        echo json_encode([
            'success' => false,
            'message' => "No se encontró comanda con folio $pedidoId."
        ]);
        return;
    }

    /*$fieldsToSave = [
        'fields' => [
            'remision' => ['stringValue' => $folio]
        ]
    ];*/
    $data = [
        'fields' => [
            'remision' => ['stringValue' => $folio]
        ]
    ];

    //$payload = json_encode(['fields' => $fieldsToSave]);

    $urlUpd = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$docId?updateMask.fieldPaths=remision&key=$firebaseApiKey";

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
            //'content' => $data
        ]
    ]);
    //var_dump($context);

    $response = @file_get_contents($urlUpd, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        echo json_encode([
            'success' => false,
            'error' => $error['message']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => "Comanda $docId actualizada con remisión $folio."
        ]);
    }
}
function insertatInfoClie($conexionData, $claveSae, $pedidoId)
{
    $serverName   = $conexionData['host'];
    $connectionInfo = [
        "Database"  => $conexionData['nombreBase'],
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
            'errors'  => sqlsrv_errors()
        ]));
    }
    $tablaClienteInfo = "[{$conexionData['nombreBase']}].[dbo].[INFCLI" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $cve_doc = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);

    $sqlPedido = "SELECT CVE_CLPV FROM $nombreTabla WHERE CVE_DOC = ?";
    $paramsPedido = [$cve_doc];
    $stmPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    $row = sqlsrv_fetch_array($stmPedido, SQLSRV_FETCH_ASSOC);
    $CVE_CLPV = $row ? $row['CVE_CLPV'] : null;


    $dataCliente = obtenerDatosCliente($CVE_CLPV, $conexionData, $claveSae);

    $sqlUltimo = "SELECT ISNULL(MAX(CVE_INFO), 0) + 1 AS NUEVO_CVE FROM $tablaClienteInfo";
    $stmtUlt = sqlsrv_query($conn, $sqlUltimo);
    if ($stmtUlt === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al obtener el último CVE",
            'errors'  => sqlsrv_errors()
        ]));
    }
    $rowUlt = sqlsrv_fetch_array($stmtUlt, SQLSRV_FETCH_ASSOC);
    $nuevo = $rowUlt['NUEVO_CVE'];

    $sql = "INSERT INTO $tablaClienteInfo (CALLE, CODIGO, COLONIA, CRUZAMIENTOS, CRUZAMIENTOS2, CURP,
    CVE_INFO, CVE_PAIS_SAT, CVE_ZONA, ESTADO, MUNICIPIO, NOMBRE, NUMEXT,
    NUMINT, PAIS, POB, REFERDIR, REG_FISC, RFC)
    VALUES(?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?)";
    $params = [
        $dataCliente['CALLE'],
        $dataCliente['CODIGO'],
        $dataCliente['COLONIA'],
        $dataCliente['CRUZAMIENTOS'],
        $dataCliente['CRUZAMIENTOS2'],
        $dataCliente['CURP'],
        $nuevo,
        $dataCliente['CVE_PAIS_SAT'],
        $dataCliente['CVE_ZONA'],
        $dataCliente['ESTADO'],
        $dataCliente['MUNICIPIO'],
        $dataCliente['NOMBRE'],
        $dataCliente['NUMEXT'],
        $dataCliente['NUMINT'],
        $dataCliente['PAIS'],
        $dataCliente['LOCALIDAD'],
        $dataCliente['REFERDIR'],
        $dataCliente['REG_FISC'],
        $dataCliente['RFC']
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al insertar en INFCLI",
            'errors'  => sqlsrv_errors()
        ]));
    }
    sqlsrv_close($conn);
    //echo json_encode(['success' => true, 'cliente' => $claveCliente]);

    return $nuevo;
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
function obtenerDatosCliente($claveCliente, $conexionData, $claveSae)
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

    $clie = formatearClaveCliente($claveCliente);
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
function actualizarStatusPedido($conexionData, $pedidoId, $claveSae)
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

    $cve_doc = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP_CLIB"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sqlUpdate = "UPDATE $nombreTabla 
                  SET CAMPLIB3 = 'V', 
                  WHERE CLAVE_DOC = ?";

    $paramsUpdate = [$cve_doc];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar FACTP_CLIBXX para el pedido $pedidoId",
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);
}
/**************************************************************************************************************/
function crearRemision($conexionData, $pedidoId, $claveSae, $noEmpresa, $vendedor)
{
    global $firebaseProjectId, $firebaseApiKey;


    actualizarMulti($conexionData, $pedidoId, $claveSae);
    actualizarInve5($conexionData, $pedidoId, $claveSae);
    actualizarFolios($conexionData, $claveSae);
    actualizarInve($conexionData, $pedidoId, $claveSae);

    $enlaceLote = validarLotes($conexionData, $pedidoId, $claveSae);
    //var_dump($enlace);

    actualizarInve2($conexionData, $pedidoId, $claveSae);
    actualizarInve3($conexionData, $pedidoId, $claveSae);
    actualizarInveClaro($conexionData, $pedidoId, $claveSae);
    actualizarInveAmazon($conexionData, $pedidoId, $claveSae);
    actualizarMulti2($conexionData, $pedidoId, $claveSae); //No Terminada
    actualizarAfac($conexionData, $pedidoId, $claveSae);

    $CVE_BITA = insertarBita($conexionData, $pedidoId, $claveSae);
    //var_dump("Bita: ", $CVE_BITA);
    actualizarControl3($conexionData, $claveSae);
    $DAT_ENVIO = gaurdarDatosEnvio($conexionData, $pedidoId, $claveSae);
    var_dump("Envio: ", $DAT_ENVIO);
    $DAT_MOSTR = insertatInfoClie($conexionData, $claveSae, $pedidoId);
    //var_dump("Cliente: ", $DAT_MOSTR);

    $folio = insertarFactr($conexionData, $pedidoId, $claveSae, $CVE_BITA, $DAT_ENVIO, $DAT_MOSTR);


    insertarMimve($conexionData, $pedidoId, $claveSae, $folio);
    insertarFactr_Clib($conexionData, $folio, $claveSae);
    actualizarPar_Factp($conexionData, $pedidoId, $folio, $claveSae);
    actualizarInve4($conexionData, $pedidoId, $claveSae);
    insertarPar_Factr($conexionData, $pedidoId, $folio, $claveSae, $enlaceLote);
    actualizarFactp($conexionData, $pedidoId, $claveSae);
    actualizarFactp2($conexionData, $pedidoId, $folio, $claveSae);
    actualizarFactp3($conexionData, $pedidoId, $claveSae);
    insertarDoctoSig($conexionData, $pedidoId, $folio, $claveSae);
    insertarPar_Factr_Clib($conexionData, $pedidoId, $folio, $claveSae);
    //insertarInfenvio($conexionData, $pedidoId, $folio, $claveSae);
    actualizarAlerta_Usuario($conexionData, $claveSae);
    actualizarAlerta($conexionData, $claveSae);
    //actualizarControl($conexionData, $claveSae); //?
    actualizarControl4($conexionData, $claveSae);
    //actualizarControl2($conexionData, $claveSae); //?
    //actualizarControl5($conexionData, $claveSae); //?
    actualizarControl6($conexionData, $claveSae);
    //actualizarControl7($conexionData, $claveSae);

    foreach ($enlaceLote as $enlace) {
        actualizarDatosComanda(
            $firebaseProjectId,
            $firebaseApiKey,
            $pedidoId,
            $enlace
        );
    }

    actualizarStatusPedido($conexionData, $pedidoId, $claveSae);

    //remisionarComanda($firebaseProjectId, $firebaseApiKey, $pedidoId, $folio);

    //$cveDoc = '          0000013314';
    //generarPDFP($conexionData, $cveDoc, $claveSae, $noEmpresa, $vendedor);
    //echo json_encode(['success' => true, 'cveDoc' => $cveDoc]);
    //return $cveDoc;
}
function conectarDB($conexionData)
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

    return $conn;
}
// ✅ 1. Obtener los productos del pedido
function obtenerProductosPedido($conn, $conexionData, $pedidoId, $claveSae)
{
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaProductos = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $params = [str_pad($pedidoId, 20, ' ', STR_PAD_LEFT)];
    $sql = "SELECT P.CVE_ART, P.CANT, I.CON_LOTE
            FROM $tablaPartidas P
            INNER JOIN $tablaProductos I ON P.CVE_ART = I.CVE_ART
            WHERE P.CVE_DOC = ?";

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar los productos del pedido', 'errors' => sqlsrv_errors()]));
    }

    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $productos[] = $row;
    }

    return $productos;
}
// ✅ 2. Obtener los lotes disponibles para un producto
function obtenerLotesDisponibles($conn, $conexionData, $claveProducto, $claveSae)
{
    $tablaLotes = "[{$conexionData['nombreBase']}].[dbo].[LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT REG_LTPD, CANTIDAD, LOTE
            FROM $tablaLotes
            WHERE CVE_ART = ? AND STATUS = 'A' AND CANTIDAD > 0
            ORDER BY FCHCADUC ASC, REG_LTPD ASC";

    $params = [$claveProducto];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => "Error al consultar lotes para el producto $claveProducto", 'errors' => sqlsrv_errors()]));
    }

    $lotes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $lotes[] = $row;
    }

    return $lotes;
}
// ✅ 3. Actualizar los lotes consumidos
function actualizarLotes($conn, $conexionData, $lotesUtilizados, $claveProducto, $claveSae)
{
    $tablaLotes = "[{$conexionData['nombreBase']}].[dbo].[LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    foreach ($lotesUtilizados as $lote) {
        $sql = "UPDATE $tablaLotes 
                SET CANTIDAD = CANTIDAD - ?, 
                    STATUS = CASE WHEN CANTIDAD - ? <= 0 THEN 'B' ELSE 'A' END
                WHERE REG_LTPD = ? AND CVE_ART = ?";

        $params = [$lote['CANTIDAD'], $lote['CANTIDAD'], $lote['REG_LTPD'], $claveProducto];
        $stmt = sqlsrv_query($conn, $sql, $params);

        // 🚀 Verifica si la actualización falló
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => "Error al actualizar lote {$lote['REG_LTPD']} de $claveProducto", 'errors' => sqlsrv_errors()]));
        }

        // 🔍 Depuración: Verificar filas afectadas
        $rowsAffected = sqlsrv_rows_affected($stmt);
    }
}
// ✅ 4. Insertar en ENLACE_LTPD
function insertarEnlaceLTPD($conn, $conexionData, $lotesUtilizados, $claveSae, $claveProducto)
{
    $tablaEnlace = "[{$conexionData['nombreBase']}].[dbo].[ENLACE_LTPD"
        . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // 1) Solo una vez: obtener el próximo E_LTPD
    $sqlUltimoELTPD = "SELECT ISNULL(MAX(E_LTPD), 0) + 1 AS NUEVO_E_LTPD FROM $tablaEnlace";
    $stmtUlt = sqlsrv_query($conn, $sqlUltimoELTPD);
    if ($stmtUlt === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al obtener el último E_LTPD",
            'errors'  => sqlsrv_errors()
        ]));
    }
    $rowUlt = sqlsrv_fetch_array($stmtUlt, SQLSRV_FETCH_ASSOC);
    $nuevoELTPD = $rowUlt['NUEVO_E_LTPD'];

    $enlaceLTPDResultados = [];

    // 2) Inserto cada lote usando el mismo $nuevoELTPD
    foreach ($lotesUtilizados as $lote) {
        $sql = "INSERT INTO $tablaEnlace 
                  (E_LTPD, REG_LTPD, CANTIDAD, PXRS) 
                VALUES (?, ?, ?, ?)";
        $params = [
            $nuevoELTPD,
            $lote['REG_LTPD'],
            $lote['CANTIDAD'],
            $lote['CANTIDAD']
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode([
                'success' => false,
                'message' => "Error al insertar en ENLACE_LTPD",
                'errors'  => sqlsrv_errors()
            ]));
        }

        // Guardamos para devolver
        $enlaceLTPDResultados[] = [
            'E_LTPD'   => $nuevoELTPD,
            'REG_LTPD' => $lote['REG_LTPD'],
            'CANTIDAD' => $lote['CANTIDAD'],
            'PXRS'     => $lote['CANTIDAD'],
            'LOTE'     => $lote['LOTE'],
            'CVE_ART'  => $claveProducto
        ];
    }

    return $enlaceLTPDResultados;
}
// ✅ 5. Función principal `validarLotes`
function validarLotes($conexionData, $pedidoId, $claveSae)
{
    $conn = conectarDB($conexionData);

    $productos = obtenerProductosPedido($conn, $conexionData, $pedidoId, $claveSae);
    $enlaceLTPDResultados = [];


    sqlsrv_begin_transaction($conn);

    foreach ($productos as $producto) {
        if ($producto['CON_LOTE'] != 'S') {
            continue;
        }
        $claveProducto = $producto['CVE_ART'];
        $cantidadRequerida = (float)$producto['CANT'];

        $lotes = obtenerLotesDisponibles($conn, $conexionData, $claveProducto, $claveSae);

        if (empty($lotes)) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => "No se encontraron lotes para el producto $claveProducto"]));
        }

        $lotesUtilizados = [];
        $lotesUsados = "";
        foreach ($lotes as $lote) {
            if ($cantidadRequerida <= 0) break;

            $usarCantidad = min((float)$lote['CANTIDAD'], $cantidadRequerida);
            $cantidadRequerida -= $usarCantidad;
            $lotesUsados .= $lote['LOTE'] . "/";
            $lotesUtilizados[] = [
                'REG_LTPD' => $lote['REG_LTPD'],
                'CANTIDAD' => $usarCantidad,
                'LOTE' => $lotesUsados,
                'CVE_ART' => $claveProducto
            ];
        }

        if ($cantidadRequerida > 0) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => "No hay suficiente stock en lotes para $claveProducto"]));
        }

        actualizarLotes($conn, $conexionData, $lotesUtilizados, $claveProducto, $claveSae);
        /*******************/
        $resultadoPorProducto = insertarEnlaceLTPD($conn, $conexionData, $lotesUtilizados, $claveSae, $claveProducto);
        // Si esa función te devuelve un array de 1 o varios elementos,
        // mézclalos todos en tu array maestro:
        $enlaceLTPDResultados = array_merge(
            $enlaceLTPDResultados,
            (array)$resultadoPorProducto
        );
        //var_dump($enlaceLTPDResultados);
    }
    sqlsrv_commit($conn);
    sqlsrv_close($conn);

    return $enlaceLTPDResultados;
}
function notificarVenderdor($conexionData) {}
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
function mostrarRemisiones($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor, $firebaseProjectId, $firebaseApiKey)
{
    // Recuperar el filtro de fecha enviado o usar 'Todos' por defecto , $filtroVendedor
    $filtroFecha = $_POST['filtroFecha'] ?? 'Todos';
    $estadoPedido = $_POST['estadoPedido'] ?? 'Activos';
    $filtroVendedor = $_POST['filtroVendedor'] ?? '';

    // Parámetros de paginación
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $porPagina = isset($_POST['porPagina']) ? (int)$_POST['porPagina'] : 10;
    $offset = ($pagina - 1) * $porPagina;

    try {
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        if (!is_numeric($noEmpresa)) {
            echo json_encode(['success' => false, 'message' => 'El número de empresa no es válido']);
            exit;
        }

        $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
        $claveVendedor = $_SESSION['empresa']['claveUsuario'] ?? null;
        if ($claveVendedor != null) {
            $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');
        }

        $claveVendedor = formatearClaveVendedor($claveVendedor);

        $serverName = $conexionData['host'];
        $connectionInfo = [
            "Database" => $conexionData['nombreBase'],
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password'],
            "TrustServerCertificate" => true
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if ($conn === false) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }

        // Construir nombres de tablas dinámicamente
        $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla2  = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla3  = "[{$conexionData['nombreBase']}].[dbo].[VEND"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Reescribir la consulta evitando duplicados con `DISTINCT`
        $sql = "
            SELECT DISTINCT 
                f.TIP_DOC              AS Tipo,
                f.CVE_DOC              AS Clave,
                f.CVE_CLPV             AS Cliente,
                c.NOMBRE               AS Nombre,
                f.STATUS               AS Estatus,
                CONVERT(VARCHAR(10), f.FECHAELAB, 105) AS FechaElaboracion,
                f.FECHAELAB            AS FechaOrden,    
                f.CAN_TOT              AS Subtotal,
                f.COM_TOT              AS TotalComisiones,
                f.IMPORTE              AS ImporteTotal,
                f.DOC_SIG              AS DOC_SIG,
                v.NOMBRE               AS NombreVendedor
            FROM $nombreTabla2 f
            LEFT JOIN $nombreTabla  c ON c.CLAVE   = f.CVE_CLPV
            LEFT JOIN $nombreTabla3 v ON v.CVE_VEND= f.CVE_VEND
            ";
        if ($estadoPedido == "Activos" || $estadoPedido == "Vendidos") {
            $sql .= "WHERE f.STATUS IN ('E','O')";
        } else {
            $sql .= "WHERE f.STATUS IN ('C')";
        }

        // Agregar filtros de fecha
        if ($filtroFecha == 'Hoy') {
            $sql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE) ";
        } elseif ($filtroFecha == 'Mes') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE()) ";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE())) ";
        }

        // Filtrar por vendedor si el usuario no es administrador
        /*if ($tipoUsuario !== 'ADMINISTRADOR') {
            $sql .= " AND f.CVE_VEND = ? ";
            $params = [intval($claveVendedor)];
        } else {
            $params = [];
        }*/
        if ($tipoUsuario === 'ADMINISTRADOR') {
            if ($filtroVendedor !== '') {
                $sql      .= " AND f.CVE_VEND = ?";
                $params[]  = $filtroVendedor;
            }
        } else {
            // Usuarios no ADMIN sólo ven sus pedidos
            $sql      .= " AND f.CVE_VEND = ?";
            $params[]  = $claveVendedor;
        }

        // Agregar orden y paginación
        $sql .= " ORDER BY f.FECHAELAB DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY ";
        $params[] = $offset;
        $params[] = $porPagina;

        // Ejecutar la consulta
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        // Arreglo para almacenar los pedidos evitando duplicados
        $clientes = [];
        $clavesRegistradas = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Validar codificación y manejar nulos
            foreach ($row as $key => $value) {
                if ($value !== null && is_string($value)) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $encoding = mb_detect_encoding($value, mb_list_encodings(), true);
                        if ($encoding && $encoding !== 'UTF-8') {
                            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                        }
                    }
                } elseif ($value === null) {
                    $value = '';
                }
                $row[$key] = $value;
            }

            // 🚨 Evitar pedidos duplicados usando CVE_DOC como clave única
            if (!in_array($row['Clave'], $clavesRegistradas)) {
                $clavesRegistradas[] = $row['Clave']; // Registrar la clave para evitar repetición
                $clientes[] = $row;
            }
        }
        /*if($estadoPedido == "Vendidos"){
            $clientes = filtrarPedidosVendidos($clientes);
        }*/
        $countSql  = "
            SELECT COUNT(DISTINCT f.CVE_DOC) AS total
            FROM $nombreTabla2 f
            LEFT JOIN $nombreTabla c ON c.CLAVE    = f.CVE_CLPV
            LEFT JOIN $nombreTabla3 v ON v.CVE_VEND = f.CVE_VEND
        ";
        if ($estadoPedido == "Activos" || $estadoPedido == "Vendidos") {
            $countSql .= "WHERE f.STATUS IN ('E','O')";
        } else {
            $countSql .= "WHERE f.STATUS IN ('C')";
        }
        // Agregar filtros de fecha
        if ($filtroFecha == 'Hoy') {
            $countSql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE) ";
        } elseif ($filtroFecha == 'Mes') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE()) ";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE())) ";
        }
        if ($tipoUsuario === 'ADMINISTRADOR') {
            if ($filtroVendedor !== '') {
                $countSql      .= " AND f.CVE_VEND = ?";
                $params[]  = $filtroVendedor;
            }
        } else {
            // Usuarios no ADMIN sólo ven sus pedidos
            $countSql      .= " AND f.CVE_VEND = ?";
            $params[]  = $claveVendedor;
        }

        $countStmt = sqlsrv_query($conn, $countSql, $params);
        $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total     = (int)$totalRow['total'];
        header('Content-Type: application/json; charset=UTF-8');
        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron pedidos']);
            exit;
        }

        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FALLAS_FACTURA?key=$firebaseApiKey";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos.']);
        } else {
            $data = json_decode($response, true);
            $fallas = [];

            if (isset($data['documents'])) {
                foreach ($data['documents'] as $document) {
                    foreach ($clientes as $remision) {
                        //var_dump($clientes);
                        $fields = $document['fields'];
                        $empFirebase = (int) $fields['noEmpresa']['integerValue'];
                        $empBuscada  = (int) $noEmpresa;
                        if ($empFirebase === $empBuscada) {
                            $folioFirebase = (int) $fields['folio']['stringValue'];
                            $remisionId = $remision['Clave'];
                            //var_dump($fields['folio']['stringValue']);
                            $folioBuscada  = (int) $remisionId;
                            if ($folioFirebase === $folioBuscada) {
                                $fallas[] = [
                                    'id' => basename($document['name']),
                                    'folio' => $fields['folio']['stringValue'],
                                    'claveSae' => $fields['claveSae']['stringValue'],
                                    'noEmpresa' =>  $fields['noEmpresa']['integerValue']
                                ];
                            }
                        }
                    }
                }
            }
        }

        sqlsrv_free_stmt($countStmt);

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        echo json_encode(['success' => true, 'total' => $total, 'data' => $clientes, 'fallas' => $fallas]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function mostrarRemisionEspecifica($clave, $conexionData, $claveSae)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'], // Nombre de la base de datos
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $claveSae = $_SESSION['empresa']['claveSae'];
    // Limpiar la clave y construir el nombre de la tabla
    $clave = mb_convert_encoding(trim($clave), 'UTF-8');
    $clave = str_pad($clave, 10, 0, STR_PAD_LEFT);
    $clave = str_pad($clave, 20, ' ', STR_PAD_LEFT);

    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL con INNER JOIN
    $sql = "SELECT 
     p.[TIP_DOC], p.[CVE_DOC], p.[CVE_CLPV], p.[STATUS], p.[DAT_MOSTR],
     p.[CVE_VEND], p.[CVE_PEDI], p.[FECHA_DOC], p.[FECHA_ENT], p.[FECHA_VEN],
     p.[FECHA_CANCELA], p.[CAN_TOT], p.[IMP_TOT1], p.[IMP_TOT2], p.[IMP_TOT3],
     p.[IMP_TOT4], p.[IMP_TOT5], p.[IMP_TOT6], p.[IMP_TOT7], p.[IMP_TOT8],
     p.[DES_TOT], p.[DES_FIN], p.[COM_TOT], p.[CONDICION], p.[CVE_OBS],
     p.[NUM_ALMA], p.[ACT_CXC], p.[ACT_COI], p.[ENLAZADO], p.[TIP_DOC_E],
     p.[NUM_MONED], p.[TIPCAMB], p.[NUM_PAGOS], p.[FECHAELAB], p.[PRIMERPAGO],
     p.[RFC], p.[CTLPOL], p.[ESCFD], p.[AUTORIZA], p.[SERIE], p.[FOLIO],
     p.[AUTOANIO], p.[DAT_ENVIO], p.[CONTADO], p.[CVE_BITA], p.[BLOQ],
     p.[FORMAENVIO], p.[DES_FIN_PORC], p.[DES_TOT_PORC], p.[IMPORTE],
     p.[COM_TOT_PORC], p.[METODODEPAGO], p.[NUMCTAPAGO], p.[VERSION_SINC],
     p.[FORMADEPAGOSAT], p.[USO_CFDI], p.[TIP_TRASLADO], p.[TIP_FAC],
     p.[REG_FISC],
     c.[NOMBRE] AS NOMBRE_CLIENTE, c.[TELEFONO] AS TELEFONO_CLIENTE, c.[DESCUENTO], c.[CLAVE], c.[CALLE_ENVIO]
 FROM $tablaPedidos p
 INNER JOIN $tablaClientes c ON p.[CVE_CLPV] = c.[CLAVE]
 WHERE p.[CVE_DOC] = ?";
    // Preparar el parámetro
    $params = [$clave];

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Obtener los resultados
    $pedido = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    // Verificar si se encontró el pedido
    if ($pedido) {
        // Convertimos los DateTime a texto "YYYY-MM-DD" o al formato que quieras
        $fechaDoc = $pedido['FECHA_DOC']->format('Y-m-d');
        $fechaEnt = $pedido['FECHA_ENT']->format('Y-m-d');

        header('Content-Type: application/json');
        echo json_encode([
            'success'   => true,
            'pedido'    => array_merge($pedido, [
                'FECHA_DOC' => $fechaDoc,
                'FECHA_ENT' => $fechaEnt
            ])
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerPartidasRemision($conexionData, $clavePedido)
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
        echo json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $clavePedido = str_pad($clavePedido, 20, ' ', STR_PAD_LEFT);
    // Tabla dinámica basada en el número de empresa
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consultar partidas del pedido
    $sql = "SELECT CVE_DOC, NUM_PAR, CVE_ART, CANT, UNI_VENTA, PREC, IMPU1, IMPU4, DESC1, DESC2, TOT_PARTIDA, DESCR_ART, COMI 
            FROM $nombreTabla 
            WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $sql, [$clavePedido]);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error al consultar las partidas del pedido', 'errors' => sqlsrv_errors()]);
        sqlsrv_close($conn);
        exit;
    }
    // Procesar resultados
    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = [
            'NUM_PAR' => $row['NUM_PAR'],
            'DESCR_ART' => $row['DESCR_ART'],
            'CVE_ART' => $row['CVE_ART'],
            'CANT' => $row['CANT'],
            'UNI_VENTA' => $row['UNI_VENTA'],
            'PREC' => $row['PREC'],
            'IMPU1' => $row['IMPU1'],
            'IMPU4' => $row['IMPU4'],
            'DESC1' => $row['DESC1'],
            'DESC2' => $row['DESC2'],
            'COMI' => $row['COMI'],
            'TOT_PARTIDA' => $row['TOT_PARTIDA']
        ];
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    // Responder con las partidas
    echo json_encode(['success' => true, 'partidas' => $partidas]);
}
function obtenerMunicipios($estadoSeleccionado)
{
    $filePath = "../../Complementos/CAT_MUNICIPIO.xml";
    if (!file_exists($filePath)) {
        echo "El archivo no existe en la ruta: $filePath";
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo "Error al leer el archivo XML en $filePath";
        return;
    }

    try {
        $municipios = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return;
    }

    $estado = [];

    // Iterar sobre cada <row>
    foreach ($municipios->row as $row) {
        $Estado = (string)$row['Estado'];
        // Sólo procesamos si País es 'MEX'
        if ($Estado !== $estadoSeleccionado) {
            continue;
        }
        $estado[] = [
            'Clave' => (string)$row['Clave'],
            'Estado' => (string)$row['Estado'],
            'Descripcion' => (string)$row['Descripcion']
        ];
    }
    if (!empty($estado)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($estado, function ($a, $b) {
            return strcmp($a['Clave'] ?? '', $b['Clave'] ?? '');
        });
        echo json_encode(['success' => true, 'data' => $estado]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron ningun municipio.']);
    }
}
function mostrarPedidosFiltrados($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor, $filtroBusqueda, $firebaseProjectId, $firebaseApiKey){
    // Recuperar el filtro de fecha enviado o usar 'Todos' por defecto , $filtroVendedor
    $filtroFecha = $_POST['filtroFecha'] ?? 'Todos';
    $estadoPedido = $_POST['estadoPedido'] ?? 'Activos';
    $filtroVendedor = $_POST['filtroVendedor'] ?? '';

    // Parámetros de paginación
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $porPagina = isset($_POST['porPagina']) ? (int)$_POST['porPagina'] : 10;
    $offset = ($pagina - 1) * $porPagina;

    try {
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        if (!is_numeric($noEmpresa)) {
            echo json_encode(['success' => false, 'message' => 'El número de empresa no es válido']);
            exit;
        }

        $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
        $claveVendedor = $_SESSION['empresa']['claveUsuario'] ?? null;
        if ($claveVendedor != null) {
            $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');
        }

        $claveVendedor = formatearClaveVendedor($claveVendedor);

        $serverName = $conexionData['host'];
        $connectionInfo = [
            "Database" => $conexionData['nombreBase'],
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password'],
            "TrustServerCertificate" => true
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if ($conn === false) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }

        // Construir nombres de tablas dinámicamente
        $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla2  = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla3  = "[{$conexionData['nombreBase']}].[dbo].[VEND"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Reescribir la consulta evitando duplicados con `DISTINCT`
        $sql = "SELECT DISTINCT 
                f.TIP_DOC              AS Tipo,
                f.CVE_DOC              AS Clave,
                f.CVE_CLPV             AS Cliente,
                c.NOMBRE               AS Nombre,
                f.STATUS               AS Estatus,
                CONVERT(VARCHAR(10), f.FECHAELAB, 105) AS FechaElaboracion,
                f.FECHAELAB            AS FechaOrden,    
                f.CAN_TOT              AS Subtotal,
                f.COM_TOT              AS TotalComisiones,
                f.IMPORTE              AS ImporteTotal,
                f.DOC_SIG              AS DOC_SIG,
                v.NOMBRE               AS NombreVendedor
            FROM $nombreTabla2 f
            LEFT JOIN $nombreTabla  c ON c.CLAVE   = f.CVE_CLPV
            LEFT JOIN $nombreTabla3 v ON v.CVE_VEND= f.CVE_VEND
            ";
        if ($estadoPedido == "Activos" || $estadoPedido == "Vendidos") {
            $sql .= "WHERE f.STATUS IN ('E','O')";
        } else {
            $sql .= "WHERE f.STATUS IN ('C')";
        }

        $sql .= " AND (";
        if (preg_match('/[a-zA-Z]/', $filtroBusqueda)) {
            $sql .= "LOWER(LTRIM(RTRIM(f.TIP_DOC))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.CVE_DOC))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.CVE_CLPV))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(c.NOMBRE))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.STATUS))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(CONVERT(VARCHAR(10), f.FECHAELAB, 105)))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.CAN_TOT))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.IMPORTE))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(v.NOMBRE))) LIKE ? ";
        } else {
            $sql .= "f.TIP_DOC LIKE ? 
                OR f.CVE_DOC LIKE ? 
                OR f.CVE_CLPV LIKE ? 
                OR c.NOMBRE LIKE ? 
                OR f.STATUS LIKE ? 
                OR CONVERT(VARCHAR(10), f.FECHAELAB, 105) LIKE ? 
                OR f.CAN_TOT LIKE ? 
                OR f.IMPORTE LIKE ? 
                OR v.NOMBRE LIKE ? ";
        }
        $sql .= ")";
        $likeFilter = '%' . $filtroBusqueda . '%';
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;

        // Agregar filtros de fecha
        if ($filtroFecha == 'Hoy') {
            $sql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE) ";
        } elseif ($filtroFecha == 'Mes') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE()) ";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE())) ";
        }


        if ($tipoUsuario === 'ADMINISTRADOR') {
            if ($filtroVendedor !== '') {
                $sql      .= " AND f.CVE_VEND = ?";
                $params[]  = $filtroVendedor;
            }
        } else {
            // Usuarios no ADMIN sólo ven sus pedidos
            $sql      .= " AND f.CVE_VEND = ?";
            $params[]  = $claveVendedor;
        }

        // Agregar orden y paginación
        $sql .= " ORDER BY f.FECHAELAB DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY ";
        $params[] = $offset;
        $params[] = $porPagina;

        // Ejecutar la consulta
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        $clientes = [];
        $clavesRegistradas = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Validar codificación y manejar nulos
            foreach ($row as $key => $value) {
                if ($value !== null && is_string($value)) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $encoding = mb_detect_encoding($value, mb_list_encodings(), true);
                        if ($encoding && $encoding !== 'UTF-8') {
                            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                        }
                    }
                } elseif ($value === null) {
                    $value = '';
                }
                $row[$key] = $value;
            }

            // 🚨 Evitar pedidos duplicados usando CVE_DOC como clave única
            if (!in_array($row['Clave'], $clavesRegistradas)) {
                $clavesRegistradas[] = $row['Clave']; // Registrar la clave para evitar repetición
                $clientes[] = $row;
            }
        }
        /*if($estadoPedido == "Vendidos"){
            $clientes = filtrarPedidosVendidos($clientes);
        }*/
        $countSql  = "
            SELECT COUNT(DISTINCT f.CVE_DOC) AS total
            FROM $nombreTabla2 f
            LEFT JOIN $nombreTabla c ON c.CLAVE    = f.CVE_CLPV
            LEFT JOIN $nombreTabla3 v ON v.CVE_VEND = f.CVE_VEND
        ";
        if ($estadoPedido == "Activos" || $estadoPedido == "Vendidos") {
            $countSql .= "WHERE f.STATUS IN ('E','O')";
        } else {
            $countSql .= "WHERE f.STATUS IN ('C')";
        }
        // Agregar filtros de fecha
        if ($filtroFecha == 'Hoy') {
            $countSql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE) ";
        } elseif ($filtroFecha == 'Mes') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE()) ";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE())) ";
        }
        if ($tipoUsuario === 'ADMINISTRADOR') {
            if ($filtroVendedor !== '') {
                $countSql      .= " AND f.CVE_VEND = ?";
                $params[]  = $filtroVendedor;
            }
        } else {
            // Usuarios no ADMIN sólo ven sus pedidos
            $countSql      .= " AND f.CVE_VEND = ?";
            $params[]  = $claveVendedor;
        }

        $countStmt = sqlsrv_query($conn, $countSql, $params);
        $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total     = (int)$totalRow['total'];

        
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FALLAS_FACTURA?key=$firebaseApiKey";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos.']);
        } else {
            $data = json_decode($response, true);
            $fallas = [];

            if (isset($data['documents'])) {
                foreach ($data['documents'] as $document) {
                    foreach ($clientes as $remision) {
                        //var_dump($clientes);
                        $fields = $document['fields'];
                        $empFirebase = (int) $fields['noEmpresa']['integerValue'];
                        $empBuscada  = (int) $noEmpresa;
                        if ($empFirebase === $empBuscada) {
                            $folioFirebase = (int) $fields['folio']['stringValue'];
                            $remisionId = $remision['Clave'];
                            //var_dump($fields['folio']['stringValue']);
                            $folioBuscada  = (int) $remisionId;
                            if ($folioFirebase === $folioBuscada) {
                                $fallas[] = [
                                    'id' => basename($document['name']),
                                    'folio' => $fields['folio']['stringValue'],
                                    'claveSae' => $fields['claveSae']['stringValue'],
                                    'noEmpresa' =>  $fields['noEmpresa']['integerValue']
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        sqlsrv_free_stmt($countStmt);

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        header('Content-Type: application/json; charset=UTF-8');
        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron pedidos']);
            exit;
        }
        echo json_encode(['success' => true, 'total' => $total, 'data' => $clientes, 'fallas' => $fallas]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function obtenerEstadoPorClave($claveSeleccionada)
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
                'Clave'       => (string)$row['Clave'],
                'Pais'        => (string)$row['Pais'],
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
/*-------------------------------------------------------------------------------------------------------------------*/
function pedidoFacturado($conexionData, $pedidoID, $claveSae)
{
    //Contruir Conexion
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
        echo json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    //Formatear Clave
    //$clave = str_pad($pedidoID, 10, ' ', STR_PAD_LEFT);
    $pedidoID = str_pad($pedidoID, 20, ' ', STR_PAD_LEFT);
    //Tabla dinamica
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT DOC_SIG, TIP_DOC_SIG FROM $nombreTabla
    WHERE CVE_DOC = ?";

    $params = [$pedidoID];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }
    //Guardamos los resultados
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $DOC_SIG = $row['DOC_SIG'];
        $TIP_DOC_SIG = $row['TIP_DOC_SIG'];
    }
    //Verificamos si el Documento Siguiente no Este Vacio a demas de que Sea una Remision
    if ($DOC_SIG !== NULL && $TIP_DOC_SIG === "F") {
        return true;
    } else if ($DOC_SIG === NULL && $TIP_DOC_SIG !== 'F') {
        return false;
    }
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerFolio($remisionId, $claveSae, $conexionData)
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

    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT CVE_DOC, FOLIO FROM $nombreTabla
    WHERE DOC_SIG = ?";

    $params = [$remisionId];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $FOLIO = $row['FOLIO'];
    }
    return $FOLIO;
}
function obtenerComanda($firebaseProjectId, $firebaseApiKey, $pedidoID, $noEmpresa)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos.']);
    } else {
        $data = json_decode($response, true);
        $comandas = [];

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $status = $fields['status']['stringValue'];
                // Aplicar el filtro de estado si está definido
                if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
                    $empFirebase = (int) $fields['folio']['stringValue'];
                    $empBuscada  = (int) $pedidoID;
                    if ($empFirebase === $empBuscada) {
                        $comandas[] = [
                            'id' => basename($document['name']),
                            'noPedido' => $fields['folio']['stringValue'],
                            'nombreCliente' => $fields['nombreCliente']['stringValue'],
                            'status' =>  $fields['status']['stringValue'],
                            'folio' =>  $fields['folio']['stringValue'],
                            'claveCliente' =>  $fields['claveCliente']['stringValue'],
                            'credito' =>  $fields['credito']['booleanValue']
                        ];
                    }
                }
            }
        }

        return $comandas;
    }
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
function validaciones($folio, $noEmpresa, $claveSae, $conexionData)
{
    global $firebaseProjectId, $firebaseApiKey;
    $errores = [];
    $origen = [];
    $folio = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $folio = str_pad($folio, 20, ' ', STR_PAD_LEFT);
    $pedidoData = datosPedidoValidacion($folio, $claveSae, $conexionData);
    $clienteData = datosCliente($pedidoData['CVE_CLPV'], $claveSae, $conexionData);
    $empresaData = datosEmpresa($noEmpresa, $firebaseProjectId, $firebaseApiKey);
    $locacionArchivos = "../XML/sdk2/certificados/$noEmpresa/";
    $archivoCer = glob($locacionArchivos . "{*.cer,*/*.cer}", GLOB_BRACE);
    $archivoKey = glob($locacionArchivos . "{*.key,*/*.key}", GLOB_BRACE);

    if (empty($archivoCer) || empty($archivoKey)) {
        /*return [
            'success'  => false,
            'message' => "No se encontró el .cer o el .key para la empresa $noEmpresa"
        ];*/
        $errores = [
            'origen' => 'Empresa',
            'message' => 'No se encontró el .cer o el .key para la empresa'  . implode(', ', $noEmpresa)
        ];
        /*echo json_encode([
            'success'  => false,
            'message' => "No se encontró el .cer o el .key para la empresa $noEmpresa"
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
        $errores = [
            'origen' => 'Pedido',
            'message' => 'Faltan datos del pedido: ' . implode(', ', $faltanPedido)
        ];
        //return;
        /*return [
            'success'  => false,
            'message' => 'Faltan datos del pedido: ' . implode(', ', $faltanPedido)
        ];*/
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
        $errores = [
            'origen' => 'Cliente',
            'message' => 'Faltan datos del cliente: ' . implode(', ', $faltan)
        ];
        /*return [
            'success'  => false,
            'message' => 'Faltan datos del cliente: ' . implode(', ', $faltan)
        ];*/
        /*echo json_encode([
            'success'  => false,
            'message' => 'Faltan datos del cliente: ' . implode(', ', $faltan)
        ]);
        return;*/
    }
    if ($clienteData['VAL_RFC'] != 200) {
        $problem = $clienteData['VAL_RFC'];
        /*echo json_encode([
            'success'  => false,
            "message' => 'Cliente no puede timbrar: $problem"
        ]);
        return;*/
        $errores = [
            'origen' => 'Cliente',
            'message' => "Cliente no puede timbrar, status: $problem"
        ];
        /*return [
            'success'  => false,
            "message' => 'Cliente no puede timbrar: $problem"
        ];*/
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
            'message' => 'Faltan datos de la empresa: ' . implode(', ', $faltanEmpre)
        ]);
        return;*/
        $errores = [
            'origen' => 'Empresa',
            'message' => 'Faltan datos de la empresa: ' . implode(', ', $faltanEmpre)
        ];
        /*return [
            'success'  => false,
            'message' => 'Faltan datos de la empresa: ' . implode(', ', $faltanEmpre)
        ];*/
    }
    if (empty($errores)) {
        return ['success' => true];
    } else {
        return [
            'success'  => false,
            'message' => $errores
        ];
    }
}
function facturar($folio, $claveSae, $noEmpresa, $claveCliente, $credito)
{
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
            //var_dump("Factura2: ", $facturaData);
            return $facturaData['folioFactura1'];
            // ✅ La respuesta es un JSON con cveDoc (Pedido procesado correctamente)
        }
    } else {
        //var_dump("No");
        // ❌ No hubo respuesta
        return false;
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
    //var_dump("respuestaCfdi: ", $facturaResponse);
    return $facturaResponse;
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

        $file = '../XML/sdk2/timbrados/cfdi_' . urlencode($clienteData['NOMBRE']) . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura) . '.xml';

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
        //var_dump('success' . false, 'message' . $resultado);
    }
}
function guardarFallas($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $problema, $remisionId, $claveCliente){
    $fechaCreacion = date("Y-m-d H:i:s"); // Fecha y hora actual
    //var_dump($problema);
    // Si me llega un único error en formato asociativo, lo convierto en array de uno:
    if (isset($problema['origen']) && isset($problema['message'])) {
        $problema = [ $problema ];
    }
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FALLAS_FACTURA?key=$firebaseApiKey";
    // Construir el arrayValue de fallas
    $valores = [];
    foreach ($problema as $f) {
        $valores[] = [
            'mapValue' => [
                'fields' => [
                    'origen'  => ['stringValue' => $f['origen']],
                    'message' => ['stringValue' => $f['message']],
                ],
            ],
        ];
    }

    $fields = [
        'folio'     => ['stringValue'  => $remisionId],
        'cliente'   => ['stringValue'  => $claveCliente],
        'claveSae'  => ['stringValue'  => $claveSae],
        'noEmpresa' => ['integerValue' => $noEmpresa],
        'creacion'  => ['stringValue'  => $fechaCreacion],
        'problemas'    => [                  // aquí va el nuevo array
            'arrayValue' => [
                'values' => $valores
            ]
        ],
    ];

    $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_SLASHES);
    $ctx = stream_context_create([
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ]);

    $response = @file_get_contents($url, false, $ctx);
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
        //error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
        //var_dump('success' . false, 'message' . $resultado);
    }
}
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
    $rutaXml = "../XML/sdk2/timbrados/xml_" . urlencode($clienteData['NOMBRE']) . "_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura) . ".xml";
    $rutaQr = "../XML/sdk2/timbrados/cfdi_" . urlencode($clienteData['NOMBRE']) . "_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura) . ".png";
    $rutaCfdi = "../XML/sdk2/timbrados/cfdi_" . urlencode($clienteData['NOMBRE']) . "_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura) . ".xml";

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
    $numeroWhatsApp = trim($clienteData['TELEFONO']);
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
    //$emailPred = $correoVendedor ?? "";
    //$numeroWhatsApp = $telefonoVendedor;
    //var_dump($emailPred);
    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '7773750925';*/
    /*$emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';*/
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123'; // Interzenda*/

    if ($correo === 'S' && !empty($emailPred)) {
        //$rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Factura_" . trim(urlencode($folioFactura)) . ".pdf";
        $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Factura_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura) . ".pdf";
        //$rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Factura_18456.pdf";

        //$rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/". urldecode($rutaPDF);

        //$rutaPDFW = "http://localhost/MDConnecta/Servidor/PHP/pdfs/Factura_" . urldecode($folioFactura) . ".pdf";

        //$filename = "Factura_" . urldecode($folioFactura) . ".pdf";
        $filename = "Factura_" . preg_replace('/[^A-Za-z0-9_\-]/', '', trim(urlencode($folioFactura))) . ".pdf";
        //$filename = "Factura_18456.pdf";

        $resultadoWhatsApp = enviarWhatsAppFactura($numeroWhatsApp, $clienteNombre, $noPactura, $claveSae, $rutaPDFW, $filename);
        //var_dump($resultadoWhatsApp);
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
function crearPdf($folio, $noEmpresa, $claveSae, $conexionData, $folioFactura)
{
    $rutaPDF = generarFactura($folio, $noEmpresa, $claveSae, $conexionData, $folioFactura);
    return $rutaPDF;
}
function facturarRemision($remisionId, $noEmpresa, $claveSae, $conexionData, $firebaseProjectId, $firebaseApiKey)
{
    $pedidoID = obtenerFolio($remisionId, $claveSae, $conexionData);
    //var_dump($pedidoID);
    $datosComanda = obtenerComanda($firebaseProjectId, $firebaseApiKey, $pedidoID, $noEmpresa);

    $folio = $datosComanda[0]['folio'];
    $claveCliente = $datosComanda[0]['claveCliente'];
    $credito = $datosComanda[0]['credito'];
    $docName = $datosComanda[0]['id'];
    $status = $datosComanda[0]['status'];
    if ($status === 'TERMINADA') {
        $respuestaValidaciones = validaciones($folio, $noEmpresa, $claveSae, $conexionData);
        //var_dump($respuestaValidaciones);
        if ($respuestaValidaciones['success']) {
            $folioFactura = facturar($folio, $claveSae, $noEmpresa, $claveCliente, $credito);
            //var_dump("folioFactura: ", $folioFactura);
            actualizarStatus($firebaseProjectId, $firebaseApiKey, $docName);

            $respuestaFactura = json_decode(crearFactura($folio, $noEmpresa, $claveSae, $folioFactura), true);

            //var_dump("Respuesta: ", $respuestaFactura);
            if ($respuestaFactura['success']) {
                $bandera = 1;
                //var_dump("folio: ", $folio);
                //var_dump("folioFactura: ", $folioFactura);
                actualizarCFDI($conexionData, $claveSae, $folioFactura, $bandera);
                $rutaPDF = crearPdf($folio, $noEmpresa, $claveSae, $conexionData, $folioFactura);
                //var_dump("Ruta PDF: ", $rutaPDF);
                validarCorreo($conexionData, $rutaPDF, $claveSae, $folio, $noEmpresa, $folioFactura, $firebaseProjectId, $firebaseApiKey);
                echo json_encode(['success' => true, 'message' => "Remision facturada"]);
            } else {
                enviarCorreoFalla($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $respuestaFactura['Problema'], $folioFactura);
                echo json_encode(['success' => false, 'message' => "Hubo un problema al crear el CFDI, consultar correo"]);
            }
        } else {
            //enviarCorreoFaltaDatos($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $respuestaValidaciones['message']);
            guardarFallas($conexionData, $claveSae, $folio, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $respuestaValidaciones['message'], $remisionId, $claveCliente);
            echo json_encode(['success' => false, 'message' => 'Hubo un error al Facturar']);
        }
    } else {
        echo json_encode([
            'success'  => false,
            'message' => "La Comanda debe de estar Terminada"
        ]);
    }
}
function visualizarFallasFactura($id, $firebaseProjectId, $firebaseApiKey){
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FALLAS_FACTURA/$id?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los detalles de la comanda.']);
    } else {
        $data = json_decode($response, true);
        $fields = $data['fields'];
        $problemas = [];
        foreach ($fields['problemas']['arrayValue']['values'] as $problemas) {
            $problemas[] = [
                'message' => $problemas['mapValue']['fields']['message']['stringValue'],
                'origen' => $problemas['mapValue']['fields']['origen']['stringValue'] ?? "N/A"
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'folio' => $fields['folio']['stringValue'],
                'problemas' => $problemas
            ]
        ]);
    }
}

/*-------------------------------------------------------------------------------------------------------------------*/

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
        $claveSae = $_POST['claveSae'];
        $noEmpresa = $_POST['noEmpresa'];
        $vendedor = $_POST['vendedor'];
        //$noEmpresa = $_POST['noEmpresa'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $pedidoId = $_POST['pedidoId'];

        $cveDoc = crearRemision($conexionData, $pedidoId, $claveSae, $noEmpresa, $vendedor);
        echo json_encode(['success' => true, 'cveDoc' => $cveDoc]);
        break;
    case 2:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $filtroFecha = $_POST['filtroFecha'];
        $estadoPedido = $_POST['estadoPedido'];
        $filtroVendedor = $_POST['filtroVendedor'];
        mostrarRemisiones($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor, $firebaseProjectId, $firebaseApiKey);
        break;
    case 3:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }

        $noEmpresa = $_SESSION['empresa']['noEmpresa'];

        if (!isset($_POST['pedidoID']) || empty($_POST['pedidoID'])) {
            echo json_encode(['success' => false, 'message' => 'No se recibió el ID del pedido']);
            exit;
        }

        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener la conexión',
                'errors' => $conexionResult['errors'] ?? null
            ]);
            exit;
        }
        $conexionData = $conexionResult['data'];
        $clave = $_POST['pedidoID'];

        mostrarRemisionEspecifica($clave, $conexionData, $claveSae);
        break;
    case 4:
        if (isset($_SESSION['empresa']['noEmpresa'])) {
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        } else {
            $noEmpresa = "";
        }
        if (isset($_SESSION['empresa']['claveSae'])) {
            $claveSae = $_SESSION['empresa']['claveSae'];
        } else {
            $claveSae = $_POST['claveSae'];
        }

        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }

        $conexionData = $conexionResult['data'];

        // Validar la acción solicitada
        $accion = isset($_POST['accion']) ? $_POST['accion'] : null;

        if ($accion === 'obtenerFolioSiguiente') {
            // Obtener el siguiente folio
            $folioSiguiente = obtenerFolioSiguiente($conexionData, $claveSae);
            if ($folioSiguiente !== null) {
                echo json_encode(['success' => true, 'folioSiguiente' => $folioSiguiente]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo obtener el siguiente folio']);
            }
        } elseif ($accion === 'obtenerPartidas') {
            // Obtener las partidas de un pedido
            if (!isset($_POST['clavePedido']) || empty($_POST['clavePedido'])) {
                echo json_encode(['success' => false, 'message' => 'No se proporcionó la clave del pedido']);
                exit;
            }
            $clavePedido = $_POST['clavePedido'];
            obtenerPartidasRemision($conexionData, $clavePedido);
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida o no definida']);
        }
        break;
    case 5:
        $estadoSeleccionado = $_POST['estado'];
        obtenerMunicipios($estadoSeleccionado);
        break;
    case 6: //Función para mostrar clientes filtrados por barra de busqueda.
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        $filtroFecha = $_POST['filtroFecha'];
        $estadoPedido = $_POST['estadoPedido'];
        $filtroVendedor = $_POST['filtroVendedor'];
        $filtroBusqueda = $_POST['filtroBusqueda'];
        mostrarPedidosFiltrados($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor, $filtroBusqueda, $firebaseProjectId, $firebaseApiKey);
        break;
    case 7:
        $estadoSeleccionado = $_POST['estadoSeleccionado'];
        obtenerEstadoPorClave($estadoSeleccionado);
        break;
    case 8:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        $pedidoID = $_POST['pedidoID'];
        $verificado = pedidoFacturado($conexionData, $pedidoID, $claveSae);
        if ($verificado) {
            echo json_encode(['success' => true, 'message' => 'Pedido Facturado, no se puede cancelar']);
        } else {
            echo json_encode(['success' => false, 'fail' => true, 'message' => 'Pedido no Facturado, se puede facturar']);
        }
        break;
    case 9:
        $remisionId = $_POST['pedidoID'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        facturarRemision($remisionId, $noEmpresa, $claveSae, $conexionData, $firebaseProjectId, $firebaseApiKey);
        break;
    case 10:
        $id = $_GET['id'];
        visualizarFallasFactura($id, $firebaseProjectId, $firebaseApiKey);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Remision.']);
        break;
}
