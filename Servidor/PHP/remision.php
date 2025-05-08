<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php'; // Archivo de configuración de Firebase
include 'reportes.php';
session_start();

function obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae)
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
        if ($fields['claveSae']['stringValue'] === $claveSae) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue']
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

    // Construcción dinámica de las tablas PAR_FACTPXX e INVEXX
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
        3
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
function insertarFactr($conexionData, $pedidoId, $claveSae, $CVE_BITA)
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
    $cveDoc = $folioData['CVE_DOC'];
    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
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
    $datMostr = 0;
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
        $datMostr,
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
        $cveDoc,
        $pedido['AUTOANIO'],
        $pedido['DAT_ENVIO'],
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

    return $cveDoc;
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

    // Construcción dinámica de las tablas MULTXX y PAR_FACTPXX
    $tablaMulti = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    // ✅ 1. Obtener los productos y almacenes del pedido
    $sqlProductos = "SELECT DISTINCT CVE_ART, NUM_ALM FROM $tablaPartidas WHERE CVE_DOC = ?";
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
                  SET EXIST = ?, 
                      VERSION_SINC = ? 
                  WHERE CVE_ART = ? 
                    AND CVE_ALM = ?";

    $fechaSinc = date('Y-m-d H:i:s'); // Fecha de sincronización actual

    while ($row = sqlsrv_fetch_array($stmtProductos, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];
        $cveAlm = $row['NUM_ALM'];

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
        if ($existencia && $existencia['EXIST'] <= 0) {
            $paramsUpdate = [0, $fechaSinc, $cveArt, $cveAlm];

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

    // ✅ 2. Actualizar `PXS` en `PAR_FACTPXX`
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
                'message' => "Error al actualizar PAR_FACTPXX para el producto $cveArt en el pedido $pedidoId",
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
        'message' => "PAR_FACTPXX actualizado correctamente para el pedido $pedidoId y Remision $cveDoc"
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

    // ✅ 1. Consulta para actualizar FACTPXX
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
        'message' => "FACTPXX actualizado correctamente para el pedido $pedidoId"
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
    /*$cveDocRemision = str_pad($cveDocRemision, 10, '0', STR_PAD_LEFT);
    $cveDocRemision = str_pad($cveDocRemision, 10, ' ', STR_PAD_LEFT);*/

    // ✅ Actualizar DOC_SIG y TIP_DOC_SIG en FACTPXX
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
        'message' => "FACTPXX actualizado correctamente para el pedido $pedidoId con remision $cveDocRemision"
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
        'message' => "FACTPXX actualizado correctamente para el pedido $pedidoId"
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

function actualizarDatosComanda($firebaseProjectId, $firebaseApiKey, $pedidoId, $enlace) {
    $urlComanda = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    $response = @file_get_contents($urlComanda);
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las comandas.']);
        return;
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron comandas.']);
        return;
    }

    // Buscar la comanda con el folio igual al pedidoId
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        if (isset($fields['folio']['stringValue']) && $fields['folio']['stringValue'] === $pedidoId) {
            $id = str_replace("projects/$firebaseProjectId/databases/(default)/documents/COMANDA/", '', $document['name']);

            // Obtener los productos
            $productos = $fields['productos']['arrayValue']['values'] ?? [];

            // Actualizar el producto que coincide con CVE_ART
            foreach ($productos as &$producto) {
                $productoFields = &$producto['mapValue']['fields'];
                if (isset($productoFields['clave']['stringValue']) && $productoFields['clave']['stringValue'] === $enlace['CVE_ART']) {
                    $productoFields['lote'] = ["stringValue" => $enlace['LOTE']];
                }
            }

            // Preparar el payload para actualizar
            $fieldsToSave = [
                "productos" => [
                    "arrayValue" => [
                        "values" => $productos
                    ]
                ]
            ];

            $payload = json_encode(['fields' => $fieldsToSave]);

            $urlUpdate = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$id?updateMask.fieldPaths=productos&key=$firebaseApiKey";

            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'PATCH',
                    'content' => $payload
                ]
            ];

            $result = @file_get_contents($urlUpdate, false, $options);
            if ($result === FALSE) {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la comanda.']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Comanda actualizada correctamente.']);
            }
            return;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Comanda con el pedidoId no encontrada.']);
}

function crearRemision($conexionData, $pedidoId, $claveSae, $noEmpresa, $vendedor)
{
    global $firebaseProjectId, $firebaseApiKey;

    /*actualizarControl($conexionData, $claveSae);
    actualizarMulti($conexionData, $pedidoId, $claveSae);
    actualizarInve5($conexionData, $pedidoId, $claveSae);
    actualizarFolios($conexionData, $claveSae);
    actualizarControl4($conexionData, $claveSae);
    actualizarControl2($conexionData, $claveSae);
    actualizarControl5($conexionData, $claveSae);
    actualizarInve($conexionData, $pedidoId, $claveSae);*/

    $enlace = validarLotes($conexionData, $pedidoId, $claveSae);

    /*actualizarInve2($conexionData, $pedidoId, $claveSae);
    actualizarInve3($conexionData, $pedidoId, $claveSae);
    actualizarInveClaro($conexionData, $pedidoId, $claveSae);
    actualizarInveAmazon($conexionData, $pedidoId, $claveSae);
    actualizarMulti2($conexionData, $pedidoId, $claveSae); //No Terminada
    actualizarAfac($conexionData, $pedidoId, $claveSae);
    actualizarControl3($conexionData, $claveSae);
    $CVE_BITA = insertarBita($conexionData, $pedidoId, $claveSae);
    $cveDoc = insertarFactr($conexionData, $pedidoId, $claveSae, $CVE_BITA);
    insertarMimve($conexionData, $pedidoId, $claveSae, $cveDoc);
    insertarFactr_Clib($conexionData, $cveDoc, $claveSae);
    actualizarPar_Factp($conexionData, $pedidoId, $cveDoc, $claveSae);
    actualizarInve4($conexionData, $pedidoId, $claveSae);
    insertarPar_Factr($conexionData, $pedidoId, $cveDoc, $claveSae, $enlace);
    actualizarFactp($conexionData, $pedidoId, $claveSae);
    actualizarFactp2($conexionData, $pedidoId, $cveDoc, $claveSae);
    actualizarFactp3($conexionData, $pedidoId, $claveSae);
    insertarDoctoSig($conexionData, $pedidoId, $cveDoc, $claveSae);
    insertarPar_Factr_Clib($conexionData, $pedidoId, $cveDoc, $claveSae);
    insertarInfenvio($conexionData, $pedidoId, $cveDoc, $claveSae);
    actualizarAlerta_Usuario($conexionData, $claveSae);
    actualizarAlerta($conexionData, $claveSae);*/

    var_dump($enlace);

    actualizarDatosComanda($firebaseProjectId, $firebaseApiKey, $pedidoId, $enlace);

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
            WHERE CVE_ART = ? AND STATUS = 'A'
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
    $tablaEnlace = "[{$conexionData['nombreBase']}].[dbo].[ENLACE_LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $enlaceLTPDResultados = [];

    foreach ($lotesUtilizados as $lote) {
        // 🔹 Obtener el último E_LTPD y sumarle 1
        $sqlUltimoELTPD = "SELECT ISNULL(MAX(E_LTPD), 0) + 1 AS NUEVO_E_LTPD FROM $tablaEnlace";
        $stmtUltimoELTPD = sqlsrv_query($conn, $sqlUltimoELTPD);

        if ($stmtUltimoELTPD === false) {
            die(json_encode(['success' => false, 'message' => "Error al obtener el último E_LTPD", 'errors' => sqlsrv_errors()]));
        }

        $rowUltimoELTPD = sqlsrv_fetch_array($stmtUltimoELTPD, SQLSRV_FETCH_ASSOC);
        $nuevoELTPD = $rowUltimoELTPD['NUEVO_E_LTPD'];

        // 🔹 Insertar en ENLACE_LTPD
        $sql = "INSERT INTO $tablaEnlace (E_LTPD, REG_LTPD, CANTIDAD, PXRS) VALUES (?, ?, ?, ?)";
        $params = [$nuevoELTPD, $lote['REG_LTPD'], $lote['CANTIDAD'], $lote['CANTIDAD']];

        $stmt = sqlsrv_query($conn, $sql, $params);

        // 🚀 Verifica si la inserción falló
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => "Error al insertar en ENLACE_LTPD", 'errors' => sqlsrv_errors()]));
        }

        // 🔹 Guardar el resultado para debugging
        $enlaceLTPDResultados[] = [
            'E_LTPD' => $nuevoELTPD,
            'REG_LTPD' => $lote['REG_LTPD'],
            'CANTIDAD' => $lote['CANTIDAD'],
            'PXRS' => $lote['CANTIDAD'],
            'LOTE' => $lote['LOTE'],
            'CVE_ART' => $claveProducto
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
        foreach ($lotes as $lote) {
            if ($cantidadRequerida <= 0) break;

            $usarCantidad = min((float)$lote['CANTIDAD'], $cantidadRequerida);
            $cantidadRequerida -= $usarCantidad;

            $lotesUtilizados[] = [
                'REG_LTPD' => $lote['REG_LTPD'],
                'CANTIDAD' => $usarCantidad,
                'LOTE' => $lote['LOTE'],
                'CVE_ART' => $claveProducto
            ];
        }

        if ($cantidadRequerida > 0) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => "No hay suficiente stock en lotes para $claveProducto"]));
        }

        actualizarLotes($conn, $conexionData, $lotesUtilizados, $claveProducto, $claveSae);
        $enlaceLTPDResultados = insertarEnlaceLTPD($conn, $conexionData, $lotesUtilizados, $claveSae, $claveProducto);
        var_dump($enlaceLTPDResultados);
    }

    sqlsrv_commit($conn);
    sqlsrv_close($conn);

    return $enlaceLTPDResultados;
}
function notificarVenderdor($conexionData) {}
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
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $pedidoId = $_POST['pedidoId'];

        $cveDoc = crearRemision($conexionData, $pedidoId, $claveSae, $noEmpresa, $vendedor);
        //echo json_encode(['success' => true, 'cveDoc' => $cveDoc]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
