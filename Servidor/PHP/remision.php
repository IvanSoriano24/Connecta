<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php'; // Archivo de configuración de Firebase
session_start();

function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey){
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
        if ($fields['noEmpresa']['stringValue'] === $noEmpresa) {
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
function actualizarControl($conexionData) {
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8" // Aseguramos que todo sea manejado en UTF-8
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        die(json_encode([
            'success' => false, 
            'message' => 'Error al conectar con la base de datos', 
            'errors' => sqlsrv_errors()
        ]));
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

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

    return json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarFolios($conexionData) {
    // Validar que la empresa está definida en la sesión
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];

    // Establecer conexión con SQL Server
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Definir los parámetros de la actualización
    $fechaActual = date('Y-m-d H:i:s'); // Fecha actual
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

    return json_encode([
        'success' => true,
        'message' => 'FOLIOSF actualizado correctamente (+1 en ULT_DOC)'
    ]);
}
function actualizarControl2($conexionData) {
    // Validar que la empresa está definida en la sesión
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];

    // Establecer conexión con SQL Server
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

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

    return json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 44, +1 si ULT_CVE = 0)"
    ]);
}
function actualizarInve($conexionData, $pedidoId) {
    // Validar que la empresa está definida en la sesión
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];

    // Establecer conexión con SQL Server
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Construcción dinámica de las tablas PAR_FACTPXX e INVE01
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE01]";

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
            return json_encode([
                'success' => false,
                'message' => "Error al actualizar COSTO_PROM para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
        }

        sqlsrv_free_stmt($stmtUpdate);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "COSTO_PROM actualizado a 0 para todos los productos del pedido $pedidoId"
    ]);
}
function insertarNimve($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaMovimientos = "[{$conexionData['nombreBase']}].[dbo].[MINVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener las partidas del pedido
    $sqlPartidas = "SELECT P.CVE_ART, P.NUM_ALM, P.PREC, P.COST, P.UNI_VENTA, F.CVE_CLPV, F.CVE_VEND, P.CANT
                    FROM $tablaPartidas P
                    INNER JOIN FACTP01 F ON P.CVE_DOC = F.CVE_DOC
                    WHERE P.CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
    }

    // ✅ 2. Obtener valores incrementales
    $sqlUltimos = "SELECT 
                    ISNULL(MAX(NUM_MOV), 0) + 1 AS NUM_MOV,
                    ISNULL(MAX(REFER), 0) + 1 AS REFER,
                    ISNULL(MAX(E_LTPD), 0) + 1 AS E_LTPD,
                    ISNULL(MAX(CVE_FOLIO), 0) + 1 AS CVE_FOLIO
                   FROM $tablaMovimientos";

    $stmtUltimos = sqlsrv_query($conn, $sqlUltimos);
    if ($stmtUltimos === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener valores incrementales',
            'errors' => sqlsrv_errors()
        ]);
    }

    $ultimos = sqlsrv_fetch_array($stmtUltimos, SQLSRV_FETCH_ASSOC);
    $numMov = $ultimos['NUM_MOV'];
    $refer = $ultimos['REFER'];
    $eLtpd = $ultimos['E_LTPD'];
    $cveFolio = $ultimos['CVE_FOLIO'];

    // ✅ 3. Insertar los productos en MINVEXX
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        // Obtener datos del producto en INVEXX
        $sqlProducto = "SELECT EXIST FROM $tablaInventario WHERE CVE_ART = ?";
        $paramsProducto = [$row['CVE_ART']];
        $stmtProducto = sqlsrv_query($conn, $sqlProducto, $paramsProducto);

        if ($stmtProducto === false) {
            return json_encode([
                'success' => false,
                'message' => 'Error al obtener datos del producto',
                'errors' => sqlsrv_errors()
            ]);
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
            CANT_COST, PRECIO, COSTO, REG_SERIE, UNI_VENTA, E_LTPD, EXIST_G, EXISTENCIA, FACTOR_CON, 
            FECHAELAB, CVE_FOLIO, SIGNO, COSTEADO, COSTO_PROM_INI, COSTO_PROM_FIN, COSTO_PROM_GRAL, 
            DESDE_INVE, MOV_ENLAZADO) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $paramsInsert = [
            $cveArt, $almacen, $numMov, $cveCpto, $fechaDocu, $tipoDoc, $refer, $claveClpv, $vendedor,
            $cantidad, 0, $precio, $costo, 0, $uniVenta, $eLtpd, $existencia, $existencia, 1, $fechaDocu, 
            $cveFolio, -1, 'L', $costo, $costo, $costo, 'N', 0
        ];

        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
        if ($stmtInsert === false) {
            return json_encode([
                'success' => false,
                'message' => "Error al insertar en MINVEXX para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
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

    return json_encode([
        'success' => true,
        'message' => "MINVEXX actualizado correctamente para el pedido $pedidoId"
    ]);
}
function actualizarInve2($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener las partidas del pedido con CVE_ART y CANT
    $sqlPartidas = "SELECT CVE_ART, SUM(CANT) AS TOTAL_CANT FROM $tablaPartidas WHERE CVE_DOC = ? GROUP BY CVE_ART";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Fecha actual para `VERSION_SINC`
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 2. Actualizar EXIST en INVEXX restando TOTAL_CANT de cada producto
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];
        $cantidad = $row['TOTAL_CANT'];

        $sqlUpdate = "UPDATE $tablaInventario 
                      SET EXIST = EXIST - ?, VERSION_SINC = ?
                      WHERE CVE_ART = ?";
        $paramsUpdate = [$cantidad, $fechaSinc, $cveArt];

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
        if ($stmtUpdate === false) {
            return json_encode([
                'success' => false,
                'message' => "Error al actualizar EXIST en INVEXX para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
        }

        sqlsrv_free_stmt($stmtUpdate);
    }

    // ✅ 3. Cerrar conexiones
    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para el pedido $pedidoId"
    ]);
}
function actualizarInve3($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener los productos del pedido
    $sqlPartidas = "SELECT DISTINCT CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
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

    return json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);
}
function actualizarInveClaro($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventarioClaro = "[{$conexionData['nombreBase']}].[dbo].[INVEN_CLARO" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Obtener los productos del pedido
    $sqlPartidas = "SELECT DISTINCT CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
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

    return json_encode([
        'success' => true,
        'message' => "INVEN_CLARO01 actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);
}
function actualizarInveAmazon($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventarioAmazon = "[{$conexionData['nombreBase']}].[dbo].[INVE_AMAZON" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Obtener los productos del pedido
    $sqlPartidas = "SELECT DISTINCT CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ?";
    $params = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
    if ($stmtPartidas === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener productos del pedido',
            'errors' => sqlsrv_errors()
        ]);
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

    return json_encode([
        'success' => true,
        'message' => "INVE_AMAZON01 actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);
}
function actualizarAfac($conexionData){
    /*
    exec sp_executesql N'UPDATE AFACT01 SET RVTA_COM =RVTA_COM +  @P1 ,RDESCTO =RDESCTO +  @P2 ,
    RDES_FIN =RDES_FIN +  @P3 ,RIMP =RIMP +  @P4 ,RCOMI =RCOMI +  @P5  WHERE PER_ACUM =  @P6',
    N'@P1 float,@P2 float,@P3 float,@P4 float,@P5 float,@P6 datetime',100,0,0,16,0,'2025-01-01 00:00:00'
    */
}
function actualizarControl3($conexionData) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Consulta para incrementar ULT_CVE en +1 donde ID_TABLA = 62
    $sql = "UPDATE $nombreTabla 
            SET ULT_CVE = ULT_CVE + 1 
            WHERE ID_TABLA = 62";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 62)',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 62, +1 en ULT_CVE)"
    ]);
}
function insertarBita($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaFolios = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaBita = "[{$conexionData['nombreBase']}].[dbo].[BITA" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener el `CVE_BITA` incrementado en 1
    $sqlUltimaBita = "SELECT ISNULL(MAX(CVE_BITA), 0) + 1 AS CVE_BITA FROM $tablaBita";
    $stmtUltimaBita = sqlsrv_query($conn, $sqlUltimaBita);

    if ($stmtUltimaBita === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener el último CVE_BITA',
            'errors' => sqlsrv_errors()
        ]);
    }

    $bitaData = sqlsrv_fetch_array($stmtUltimaBita, SQLSRV_FETCH_ASSOC);
    $cveBita = $bitaData['CVE_BITA'];

    // ✅ 2. Obtener el `CVE_DOC` de la próxima remisión (`ULT_DOC + 1`)
    $sqlFolioSiguiente = "SELECT ISNULL(MAX(ULT_DOC), 0) + 1 AS FolioSiguiente FROM $tablaFolios WHERE TIP_DOC = 'R'";
    $stmtFolioSiguiente = sqlsrv_query($conn, $sqlFolioSiguiente);

    if ($stmtFolioSiguiente === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener el próximo número de remisión',
            'errors' => sqlsrv_errors()
        ]);
    }

    $folioData = sqlsrv_fetch_array($stmtFolioSiguiente, SQLSRV_FETCH_ASSOC);
    $folioSiguiente = $folioData['FolioSiguiente'];

    // ✅ 3. Obtener datos del pedido (`FACTPXX`) para calcular el total
    $sqlPedido = "SELECT CVE_CLPV, CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4 
                  FROM $tablaPedidos WHERE CVE_DOC = ?";
    $paramsPedido = [$pedidoId];

    $stmtPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    if ($stmtPedido === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]);
    }

    $pedido = sqlsrv_fetch_array($stmtPedido, SQLSRV_FETCH_ASSOC);
    if (!$pedido) {
        return json_encode([
            'success' => false,
            'message' => 'No se encontraron datos del pedido'
        ]);
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
        $cveBita, '_SAE_', 'F', $cveClie, 1, 'ADMINISTRADOR', $observaciones, date('Y-m-d H:i:s'), 3
    ];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        return json_encode([
            'success' => false,
            'message' => "Error al insertar en BITA01 con CVE_BITA $cveBita",
            'errors' => sqlsrv_errors()
        ]);
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUltimaBita);
    sqlsrv_free_stmt($stmtFolioSiguiente);
    sqlsrv_free_stmt($stmtPedido);
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "BITA01 insertado correctamente con CVE_BITA $cveBita y remisión $folioSiguiente"
    ]);
}
function insertarFactr($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaFolios = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaRemisiones = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener el nuevo `CVE_DOC` desde `FOLIOSFXX`
    $sqlFolio = "SELECT ISNULL(MAX(ULT_DOC), 0) + 1 AS CVE_DOC FROM $tablaFolios WHERE TIP_DOC = 'R'";
    $stmtFolio = sqlsrv_query($conn, $sqlFolio);

    if ($stmtFolio === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener el nuevo CVE_DOC',
            'errors' => sqlsrv_errors()
        ]);
    }

    $folioData = sqlsrv_fetch_array($stmtFolio, SQLSRV_FETCH_ASSOC);
    $cveDoc = $folioData['CVE_DOC'];

    // ✅ 2. Obtener los datos del pedido (`FACTPXX`)
    $sqlPedido = "SELECT CVE_CLPV, CVE_VEND, CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4,
                         DES_TOT, DES_FIN, COM_TOT, CVE_OBS, NUM_ALMA, ACT_CXC, ACT_COI, ENLAZADO,
                         NUM_MONED, TIPCAMB, NUM_PAGOS, FECHAELAB, PRIMERPAGO, RFC, CTLPOL, ESCFD,
                         AUTORIZA, SERIE, AUTOANIO, DAT_ENVIO, CONTADO, CVE_BITA, BLOQ, DES_FIN_PORC,
                         DES_TOT_PORC, COM_TOT_PORC, IMPORTE, METODODEPAGO, NUMCTAPAGO, FORMADEPAGOSAT,
                         USO_CFDI, TIP_FAC, REG_FISC, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8
                  FROM $tablaPedidos WHERE CVE_DOC = ?";
    $paramsPedido = [$pedidoId];

    $stmtPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    if ($stmtPedido === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]);
    }

    $pedido = sqlsrv_fetch_array($stmtPedido, SQLSRV_FETCH_ASSOC);
    if (!$pedido) {
        return json_encode([
            'success' => false,
            'message' => 'No se encontraron datos del pedido'
        ]);
    }

    // ✅ 3. Definir valores constantes y calcular datos
    $fechaDoc = date('Y-m-d H:i:s');
    $versionSinc = $fechaDoc;
    $tipDoc = 'R';
    $status = 'O';
    $datMostr = 0;
    $cvePedi = '';  // Vacío según la traza
    $tipDocE = 'P';
    $docAnt = $pedidoId;
    $tipDocAnt = 'F';
    $uuid = NULL;  // No se pone

    // ✅ 4. Insertar en FACTRXX
    $sqlInsert = "INSERT INTO $tablaRemisiones 
        (TIP_DOC, CVE_DOC, CVE_CLPV, STATUS, DAT_MOSTR, CVE_VEND, CVE_PEDI, FECHA_DOC, FECHA_ENT, FECHA_VEN,
        CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4, DES_TOT, DES_FIN, COM_TOT, CVE_OBS, NUM_ALMA, ACT_CXC,
        ACT_COI, ENLAZADO, NUM_MONED, TIPCAMB, NUM_PAGOS, FECHAELAB, PRIMERPAGO, RFC, CTLPOL, ESCFD, AUTORIZA,
        SERIE, FOLIO, AUTOANIO, DAT_ENVIO, CONTADO, CVE_BITA, BLOQ, TIP_DOC_E, DES_FIN_PORC, DES_TOT_PORC,
        COM_TOT_PORC, IMPORTE, METODODEPAGO, NUMCTAPAGO, DOC_ANT, TIP_DOC_ANT, UUID, VERSION_SINC, FORMADEPAGOSAT,
        USO_CFDI, TIP_FAC, REG_FISC, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $paramsInsert = [
        $tipDoc, $cveDoc, $pedido['CVE_CLPV'], $status, $datMostr, $pedido['CVE_VEND'], $cvePedi, 
        $fechaDoc, $fechaDoc, $fechaDoc, $pedido['CAN_TOT'], $pedido['IMP_TOT1'], $pedido['IMP_TOT2'], 
        $pedido['IMP_TOT3'], $pedido['IMP_TOT4'], $pedido['DES_TOT'], $pedido['DES_FIN'], $pedido['COM_TOT'], 
        $pedido['CVE_OBS'], $pedido['NUM_ALMA'], $pedido['ACT_CXC'], $pedido['ACT_COI'], $pedido['ENLAZADO'], 
        $pedido['NUM_MONED'], $pedido['TIPCAMB'], $pedido['NUM_PAGOS'], $pedido['FECHAELAB'], 
        $pedido['PRIMERPAGO'], $pedido['RFC'], $pedido['CTLPOL'], $pedido['ESCFD'], $pedido['AUTORIZA'], 
        $pedido['SERIE'], $cveDoc, $pedido['AUTOANIO'], $pedido['DAT_ENVIO'], $pedido['CONTADO'], 
        $pedido['CVE_BITA'], $pedido['BLOQ'], $tipDocE, $pedido['DES_FIN_PORC'], $pedido['DES_TOT_PORC'], 
        $pedido['COM_TOT_PORC'], $pedido['IMPORTE'], $pedido['METODODEPAGO'], $pedido['NUMCTAPAGO'], 
        $docAnt, $tipDocAnt, $uuid, $versionSinc, $pedido['FORMADEPAGOSAT'], $pedido['USO_CFDI'], 
        $pedido['TIP_FAC'], $pedido['REG_FISC'], $pedido['IMP_TOT5'], $pedido['IMP_TOT6'], 
        $pedido['IMP_TOT7'], $pedido['IMP_TOT8']
    ];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        return json_encode([
            'success' => false,
            'message' => "Error al insertar en FACTRXX",
            'errors' => sqlsrv_errors()
        ]);
    }

    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "FACTRXX insertado correctamente con CVE_DOC $cveDoc"
    ]);
}
function insertarFactr_Clib($conexionData) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaRemisiones = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaFactrClib = "[{$conexionData['nombreBase']}].[dbo].[FACTR_CLIB" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener el `CVE_DOC` de la última remisión insertada en `FACTRXX`
    $sqlUltimaRemision = "SELECT TOP 1 CVE_DOC FROM $tablaRemisiones ORDER BY CVE_DOC DESC";
    $stmtUltimaRemision = sqlsrv_query($conn, $sqlUltimaRemision);

    if ($stmtUltimaRemision === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener la última clave de remisión',
            'errors' => sqlsrv_errors()
        ]);
    }

    $remisionData = sqlsrv_fetch_array($stmtUltimaRemision, SQLSRV_FETCH_ASSOC);
    if (!$remisionData) {
        return json_encode([
            'success' => false,
            'message' => 'No se encontró ninguna remisión en FACTRXX'
        ]);
    }

    $claveDoc = $remisionData['CVE_DOC'];

    // ✅ 2. Insertar en `FACTR_CLIB01`
    $sqlInsert = "INSERT INTO $tablaFactrClib (CLAVE_DOC) VALUES (?)";
    $paramsInsert = [$claveDoc];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        return json_encode([
            'success' => false,
            'message' => "Error al insertar en FACTR_CLIB01 con CVE_DOC $claveDoc",
            'errors' => sqlsrv_errors()
        ]);
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUltimaRemision);
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "FACTR_CLIB01 insertado correctamente con CVE_DOC $claveDoc"
    ]);
}
function actualizarInve4($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener las partidas del pedido
    $sqlPartidas = "SELECT P.CVE_ART, P.CANT, (P.PREC * P.CANT) AS TOTAL_PARTIDA, F.CVE_CLPV 
                    FROM $tablaPartidas P
                    INNER JOIN $tablaPedidos F ON P.CVE_DOC = F.CVE_DOC
                    WHERE P.CVE_DOC = ?";
    $paramsPartidas = [$pedidoId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $paramsPartidas);
    if ($stmtPartidas === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
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
            return json_encode([
                'success' => false,
                'message' => "Error al actualizar INVEXX para el producto $cveArt",
                'errors' => sqlsrv_errors()
            ]);
        }

        // 🔹 Actualizar `CLIEXX`
        $sqlUpdateClie = "UPDATE $tablaClientes 
                          SET VTAS_ANL_C = VTAS_ANL_C + ?, 
                              VTAS_ANL_M = VTAS_ANL_M + ?, 
                              FCH_ULTVTA = ?, 
                              VERSION_SINC = ? 
                          WHERE CLAVE = ?";
        $paramsUpdateClie = [$cantidad, $totalPartida, $fechaActual, $fechaActual, $cveClpv];

        $stmtUpdateClie = sqlsrv_query($conn, $sqlUpdateClie, $paramsUpdateClie);
        if ($stmtUpdateClie === false) {
            return json_encode([
                'success' => false,
                'message' => "Error al actualizar CLIEXX para el cliente $cveClpv",
                'errors' => sqlsrv_errors()
            ]);
        }

        sqlsrv_free_stmt($stmtUpdateInve);
        sqlsrv_free_stmt($stmtUpdateClie);
    }

    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "INVEXX y CLIEXX actualizados correctamente para el pedido $pedidoId"
    ]);
}
function insertarPar_Factr($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaRemisiones = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidasPedido = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidasRemision = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaMovimientos = "[{$conexionData['nombreBase']}].[dbo].[MINVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener el `CVE_DOC` de la última remisión generada en `FACTRXX`
    $sqlUltimaRemision = "SELECT TOP 1 CVE_DOC FROM $tablaRemisiones ORDER BY CVE_DOC DESC";
    $stmtUltimaRemision = sqlsrv_query($conn, $sqlUltimaRemision);

    if ($stmtUltimaRemision === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener la última clave de remisión',
            'errors' => sqlsrv_errors()
        ]);
    }

    $remisionData = sqlsrv_fetch_array($stmtUltimaRemision, SQLSRV_FETCH_ASSOC);
    if (!$remisionData) {
        return json_encode([
            'success' => false,
            'message' => 'No se encontró ninguna remisión en FACTRXX'
        ]);
    }

    $cveDoc = $remisionData['CVE_DOC'];

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
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
    }

    // ✅ 3. Obtener el `NUM_MOV` de `MINVEXX`
    $sqlNumMov = "SELECT ISNULL(MAX(NUM_MOV), 0) AS NUM_MOV FROM $tablaMovimientos";
    $stmtNumMov = sqlsrv_query($conn, $sqlNumMov);
    
    if ($stmtNumMov === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener NUM_MOV desde MINVEXX',
            'errors' => sqlsrv_errors()
        ]);
    }

    $numMovData = sqlsrv_fetch_array($stmtNumMov, SQLSRV_FETCH_ASSOC);
    $numMov = $numMovData['NUM_MOV'];

    // Fecha de sincronización
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 4. Insertar cada partida en `PAR_FACTRXX`
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $sqlInsert = "INSERT INTO $tablaPartidasRemision 
            (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, IMP1APLA, 
            IMP2APLA, IMP3APLA, IMP4APLA, TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4, DESC1, DESC2, DESC3, COMI, 
            APAR, ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, TIPO_PROD, TIPO_ELEM, CVE_OBS, REG_SERIE, 
            E_LTPD, NUM_MOV, IMPRIMIR, MAN_IEPS, APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, 
            MTO_CUOTA, CVE_ESQ, VERSION_SINC, IMPU5, IMPU6, IMPU7, IMPU8, IMP5APLA, IMP6APLA, IMP7APLA, 
            IMP8APLA, TOTIMP5, TOTIMP6, TOTIMP7, TOTIMP8) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'S', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 'C', ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $paramsInsert = [
            $cveDoc, $row['NUM_PAR'], $row['CVE_ART'], $row['CANT'], $row['PXS'], $row['PREC'], $row['COST'], 
            $row['IMPU1'], $row['IMPU2'], $row['IMPU3'], $row['IMPU4'], $row['IMP1APLA'], $row['IMP2APLA'], 
            $row['IMP3APLA'], $row['IMP4APLA'], $row['TOTIMP1'], $row['TOTIMP2'], $row['TOTIMP3'], $row['TOTIMP4'], 
            $row['DESC1'], $row['DESC2'], $row['DESC3'], $row['COMI'], $row['APAR'], $row['NUM_ALM'], 
            $row['POLIT_APLI'], $row['TIP_CAM'], $row['UNI_VENTA'], $row['TIPO_PROD'], $row['TIPO_ELEM'], 
            $row['CVE_OBS'], $row['REG_SERIE'], $row['E_LTPD'], $numMov, $row['IMPRIMIR'], $row['MAN_IEPS'], 
            $row['MTO_PORC'], $row['MTO_CUOTA'], $fechaSinc, $row['IMPU5'], $row['IMPU6'], $row['IMPU7'], 
            $row['IMPU8'], $row['IMP5APLA'], $row['IMP6APLA'], $row['IMP7APLA'], $row['IMP8APLA'], 
            $row['TOTIMP5'], $row['TOTIMP6'], $row['TOTIMP7'], $row['TOTIMP8']
        ];

        sqlsrv_query($conn, $sqlInsert, $paramsInsert);
        $numMov++;
    }

    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "PAR_FACTRXX insertado correctamente para la remisión $cveDoc"
    ]);
}
function insertarPar_Factr_Clib($conexionData, $pedidoId) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tablas dinámicas
    $tablaRemisiones = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidasPedido = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactrClib = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR_CLIB" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener `CVE_DOC` de la última remisión en `FACTRXX`
    $sqlUltimaRemision = "SELECT TOP 1 CVE_DOC FROM $tablaRemisiones ORDER BY CVE_DOC DESC";
    $stmtUltimaRemision = sqlsrv_query($conn, $sqlUltimaRemision);

    if ($stmtUltimaRemision === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener la última clave de remisión',
            'errors' => sqlsrv_errors()
        ]);
    }

    $remisionData = sqlsrv_fetch_array($stmtUltimaRemision, SQLSRV_FETCH_ASSOC);
    if (!$remisionData) {
        return json_encode([
            'success' => false,
            'message' => 'No se encontró ninguna remisión en FACTRXX'
        ]);
    }

    $cveDoc = $remisionData['CVE_DOC'];

    // ✅ 2. Contar el número de partidas del pedido en `PAR_FACTPXX`
    $sqlContarPartidas = "SELECT COUNT(*) AS TOTAL_PARTIDAS FROM $tablaPartidasPedido WHERE CVE_DOC = ?";
    $paramsContar = [$pedidoId];

    $stmtContar = sqlsrv_query($conn, $sqlContarPartidas, $paramsContar);
    if ($stmtContar === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al contar las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
    }

    $partidasData = sqlsrv_fetch_array($stmtContar, SQLSRV_FETCH_ASSOC);
    if (!$partidasData) {
        return json_encode([
            'success' => false,
            'message' => 'No se encontraron partidas en el pedido'
        ]);
    }

    $numPartidas = $partidasData['TOTAL_PARTIDAS'];

    // ✅ 3. Insertar en `PAR_FACTR_CLIB01`
    $sqlInsert = "INSERT INTO $tablaParFactrClib (CLAVE_DOC, NUM_PART) VALUES (?, ?)";
    $paramsInsert = [$cveDoc, $numPartidas];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        return json_encode([
            'success' => false,
            'message' => "Error al insertar en PAR_FACTR_CLIB01 con CVE_DOC $cveDoc",
            'errors' => sqlsrv_errors()
        ]);
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUltimaRemision);
    sqlsrv_free_stmt($stmtContar);
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "PAR_FACTR_CLIB01 insertado correctamente con CVE_DOC $cveDoc y $numPartidas partidas"
    ]);
}
function actualizarAlerta_Usuario($conexionData) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tabla dinámica
    $tablaAlertaUsuario = "[{$conexionData['nombreBase']}].[dbo].[ALERTA_USUARIO" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA_USUARIO01`
    $sqlUpdate = "UPDATE $tablaAlertaUsuario 
                  SET ACTIVA = ? 
                  WHERE ID_USUARIO = ? AND CVE_ALERTA = ?";
    $paramsUpdate = ['S', 1, 4];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTA_USUARIO01',
            'errors' => sqlsrv_errors()
        ]);
    }

    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "ALERTA_USUARIO01 actualizada correctamente"
    ]);
}
function actualizarAlerta($conexionData) {
    if (!isset($_SESSION['empresa']['noEmpresa'])) {
        return json_encode([
            'success' => false,
            'message' => 'No se ha definido la empresa en la sesión'
        ]);
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }

    // Tabla dinámica
    $tablaAlerta = "[{$conexionData['nombreBase']}].[dbo].[ALERTA" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA01`
    $sqlUpdate = "UPDATE $tablaAlerta 
                  SET CANT_DOC = ? 
                  WHERE CVE_ALERTA = ?";
    $paramsUpdate = [0, 4];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTA01',
            'errors' => sqlsrv_errors()
        ]);
    }

    sqlsrv_free_stmt($stmtUpdate);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "ALERTA01 actualizada correctamente"
    ]);
}

function crearRemision($conexionData, $pedidoId){
    actualizarControl($conexionData);
    actualizarFolios($conexionData);
    actualizarControl2($conexionData);
    actualizarInve($conexionData, $pedidoId);
    insertarNimve($conexionData, $pedidoId);
    actualizarInve2($conexionData, $pedidoId);
    actualizarInve3($conexionData, $pedidoId);
    actualizarInveClaro($conexionData, $pedidoId);
    actualizarInveAmazon($conexionData, $pedidoId);
    actualizarAfac($conexionData);
    actualizarControl3($conexionData);
    insertarBita($conexionData, $pedidoId);
    insertarFactr($conexionData, $pedidoId);
    insertarFactr_Clib($conexionData);
    actualizarInve4($conexionData, $pedidoId);
    insertarPar_Factr($conexionData, $pedidoId);
    insertarPar_Factr_Clib($conexionData, $pedidoId);
    actualizarAlerta_Usuario($conexionData);
    actualizarAlerta($conexionData);
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

switch ($funcion){
    case 1:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $pedidoId = $_POST['pedidoId'];
        crearRemision($conexionData, $pedidoId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}