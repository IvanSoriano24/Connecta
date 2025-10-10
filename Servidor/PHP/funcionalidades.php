<?php


function actualizarMulti($conexionData, $pedidoId, $claveSae, $conn)
{

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
        $numAlm = 1;
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

    /*echo json_encode([
        'success' => true,
        'message' => "MULTXX actualizado correctamente para los productos del pedido $pedidoId"
    ]);*/
}
function actualizarInve5($conexionData, $pedidoId, $claveSae, $conn)
{
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

    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para los productos del pedido $pedidoId"
    ]);*/
}
function actualizarFolios($conexionData, $claveSae, $conn)
{
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

    /*echo json_encode([
        'success' => true,
        'message' => 'FOLIOSF actualizado correctamente (+1 en ULT_DOC)'
    ]);*/
}
function actualizarInve($conexionData, $pedidoId, $claveSae, $conn)
{
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

    /*echo json_encode([
        'success' => true,
        'message' => "COSTO_PROM actualizado a 0 para todos los productos del pedido $pedidoId"
    ]);*/
}
function validarLotes($conexionData, $pedidoId, $claveSae, $conn)
{

    $productos = obtenerProductosPedido($conn, $conexionData, $pedidoId, $claveSae);
    $enlaceLTPDResultados = [];

    foreach ($productos as $producto) {
        if ($producto['CON_LOTE'] != 'S') {
            continue;
        }
        $claveProducto = $producto['CVE_ART'];
        $cantidadRequerida = (float)$producto['CANT'];

        $lotes = obtenerLotesDisponibles($conn, $conexionData, $claveProducto, $claveSae);

        if (empty($lotes)) {
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
            die(json_encode(['success' => false, 'message' => "No hay suficiente stock en lotes para $claveProducto"]));
        }

        actualizarLotes($conn, $conexionData, $lotesUtilizados, $claveProducto, $claveSae);
        /*******************/
        $resultadoPorProducto = insertarEnlaceLTPD($conn, $conexionData, $lotesUtilizados, $claveSae, $claveProducto);
        actualizarControl7($conexionData, $claveSae, $conn);
        // Si esa función te devuelve un array de 1 o varios elementos,
        // mézclalos todos en tu array maestro:
        $enlaceLTPDResultados = array_merge(
            $enlaceLTPDResultados,
            (array)$resultadoPorProducto
        );
        //var_dump($enlaceLTPDResultados);
    }


    return $enlaceLTPDResultados;
}
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
    sqlsrv_free_stmt($stmt);
    return $productos;
}
function obtenerLotesDisponibles($conn, $conexionData, $claveProducto, $claveSae)
{
    $tablaLotes = "[{$conexionData['nombreBase']}].[dbo].[LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT REG_LTPD, CANTIDAD, LOTE
            FROM $tablaLotes
            WHERE CVE_ART = ? AND STATUS = 'A' AND CVE_ALM = 1 AND CANTIDAD > 0
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
    sqlsrv_free_stmt($stmt);
    return $lotes;
}
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
        sqlsrv_free_stmt($stmt);
    }
}
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
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_free_stmt($stmtUlt);

    return $enlaceLTPDResultados;
}
function actualizarControl7($conexionData, $claveSae, $conn)
{
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

    // ✅ Consulta para incrementar ULT_CVE en +1 donde ID_TABLA = 67
    $sql = "UPDATE $nombreTabla 
            SET ULT_CVE = ULT_CVE + 1 
            WHERE ID_TABLA = 67";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 67)',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);

    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 67, +1 en ULT_CVE)"
    ]);*/
}
function actualizarInve2($conexionData, $pedidoId, $claveSae, $conn)
{
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
        //print_r($stmtUpdate);
        sqlsrv_free_stmt($stmtUpdate);
    }

    // ✅ 3. Cerrar conexiones
    sqlsrv_free_stmt($stmtPartidas);
    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function actualizarInve3($conexionData, $pedidoId, $claveSae, $conn)
{
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

    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);*/
}
function actualizarInveClaro($conexionData, $pedidoId, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "INVEN_CLARO01 actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);*/
}
function actualizarInveAmazon($conexionData, $pedidoId, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "INVE_AMAZON01 actualizado correctamente para el pedido $pedidoId",
        'errors' => $errores
    ]);*/
}
function actualizarAfac($conexionData, $pedidoId, $claveSae, $conn)
{
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

    /*echo json_encode([
        'success' => true,
        'message' => "AFACT02 actualizado correctamente para el período $perAcum"
    ]);*/
}
function actualizarControl3($conexionData, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 62, +1 en ULT_CVE)"
    ]);*/
}
function actualizarControl6($conexionData, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 58, +1 en ULT_CVE)"
    ]);*/
}
function actualizarMulti2($conexionData, $pedidoId, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "MULTXX actualizado correctamente para los productos del pedido $pedidoId"
    ]);*/
}
function actualizarPar_Factp($conexionData, $pedidoId, $cveDoc, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "PAR_FACTRXX actualizado correctamente para el pedido $pedidoId y Remision $cveDoc"
    ]);*/
}
function actualizarFactp($conexionData, $pedidoId, $claveSae, $conn)
{
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
    /*$sqlUpdate = "UPDATE $tablaFactp 
                  SET TIP_DOC_E = ?, 
                      VERSION_SINC = ?, 
                      ENLAZADO = (CASE 
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactp P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactp.CVE_DOC = P.CVE_DOC) = 0 THEN 'T'
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactp P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactp.CVE_DOC = P.CVE_DOC) > 0 THEN 'P' 
                                    ELSE ENLAZADO END)
                  WHERE CVE_DOC = ?";*/
    $sqlUpdate = "UPDATE $tablaFactp 
                  SET TIP_DOC_E = ?, 
                      VERSION_SINC = ?, 
                      ENLAZADO = (CASE 
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactp P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactp.CVE_DOC = P.CVE_DOC) = 0 THEN 'P'
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactp P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactp.CVE_DOC = P.CVE_DOC) > 0 THEN 'T' 
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
    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function actualizarFactp2($conexionData, $pedidoId, $cveDocRemision, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX actualizado correctamente para el pedido $pedidoId con remision $cveDocRemision"
    ]);*/
}
function actualizarFactp3($conexionData, $pedidoId, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function insertarDoctoSig($conexionData, $pedidoId, $cveDoc, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "DOCTOSIGFXX insertado correctamente para Pedido $pedidoId y Remisión $cveDoc"
    ]);*/
}
function insertarInfenvio($conexionData, $pedidoId, $cveDoc, $claveSae, $conn)
{
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


    /*echo json_encode([
        'success' => true,
        'message' => "INFENVIOXX insertado correctamente con CVE_INFO $cveInfo para el pedido $pedidoId"
    ]);*/
}
function insertarBitaR($conexionData, $pedidoId, $claveSae, $conn)
{
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
    return $cveBita;
    /*echo json_encode([
        'success' => true,
        'message' => "BITAXX insertado correctamente con CVE_BITA $cveBita y remisión $folioSiguiente"
    ]);*/
}
function insertarFactr($conexionData, $pedidoId, $claveSae, $CVE_BITA, $DAT_ENVIO, $DAT_MOSTR, $conn)
{
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
    $tipDocE = 'P';
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
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
        'O',
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
        'R',
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
    return $folio;
    /*echo json_encode([
        'success' => true,
        'message' => "FACTRXX insertado correctamente con CVE_DOC $cveDoc"
    ]);*/
}
function insertarFactr_Clib($conexionData, $cveDoc, $claveSae, $conn)
{
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
    $CAMPLIB3 = 'A';


    // ✅ 2. Insertar en `FACTR_CLIB01`
    $sqlInsert = "INSERT INTO $tablaFactrClib (CLAVE_DOC, CAMPLIB3) VALUES (?,?)";
    $paramsInsert = [$claveDoc, $CAMPLIB3];

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
    /*echo json_encode([
        'success' => true,
        'message' => "FACTR_CLIBXX insertado correctamente con CVE_DOC $claveDoc"
    ]);*/
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
function gaurdarDatosEnvioR($conexionData, $pedidoId, $claveSae, $conn)
{
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
    $CVE_INFO = obtenerUltimoDato($conexionData, $claveSae, $conn);
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
    $COLONIA = "";
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
    return $CVE_INFO;
}
function insertatInfoClie($conexionData, $claveSae, $pedidoId, $conn)
{
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
    //var_dump($CVE_CLPV);
    $dataCliente = obtenerDatosClienteR($CVE_CLPV, $conexionData, $claveSae, $conn);

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
    //echo json_encode(['success' => true, 'cliente' => $claveCliente]);

    return $nuevo;
}
function formatearClaveCliente($clave)
{
    //var_dump("Clave: ", $clave);
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $clave = trim($clave);
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($clave) === 10) {
        return $clave;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    return $clave;
}
function obtenerDatosClienteR($claveCliente, $conexionData, $claveSae, $conn)
{
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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

    //return $datosCliente;
}
function insertarMimve($conexionData, $pedidoId, $claveSae, $cveDoc, $enlace, $conn)
{
    if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $enlaceMap = [];
    foreach ($enlace as $lote) {
        $enlaceMap[trim($lote['CVE_ART'])] = $lote['E_LTPD'];
    }

    // Asegura que el ID del pedido tenga el formato correcto (10 caracteres con espacios a la izquierda)
    $pedidoId = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $pedidoId = str_pad($pedidoId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);
    // Tablas dinámicas
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInventario = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaMovimientos = "[{$conexionData['nombreBase']}].[dbo].[MINVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaControlMovimientos = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
                    ULT_CVE + 1 AS NUM_MOV
                   FROM $tablaControlMovimientos WHERE ID_TABLA = 44";

    $stmtUltimos = sqlsrv_query($conn, $sqlUltimos);
    if ($stmtUltimos === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los movimientos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $ultimos = sqlsrv_fetch_array($stmtUltimos, SQLSRV_FETCH_ASSOC);
    $numMov = $ultimos['NUM_MOV'];
    /*var_dump($numMov);
    die();*/
    /**********************************************************************/
    $sqlFolio = "SELECT 
                    ULT_CVE + 1 AS CVE_FOLIO
                   FROM $tablaControlMovimientos WHERE ID_TABLA = 32";

    $stmtFolio = sqlsrv_query($conn, $sqlFolio);
    if ($stmtFolio === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener cve_folio',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $folio = sqlsrv_fetch_array($stmtFolio, SQLSRV_FETCH_ASSOC);
    $cveFolio = $folio['CVE_FOLIO'];
    $cantMov = 0;
    //$refer = $pedidoId;
    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $refer = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);
    $movPorProducto = [];
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
        $fechaDocu = date('Y-m-d');
        $FECHAELAB = date('Y-m-d H:i:s');

        $tipoDoc = 'R';
        $claveClpv = $row['CVE_CLPV'];
        $vendedor = $row['CVE_VEND'];
        $cantidad = $row['CANT'];
        $precio = $row['PREC'];
        $costo = $row['COST'];
        $uniVenta = $row['UNI_VENTA'];
        $eLtpd = isset($enlaceMap[trim($row['CVE_ART'])]) ? $enlaceMap[trim($row['CVE_ART'])] : $row['E_LTPD'];
        // Insertar en MINVEXX
        $sqlInsert = "INSERT INTO $tablaMovimientos 
            (CVE_ART, ALMACEN, NUM_MOV, CVE_CPTO, FECHA_DOCU, TIPO_DOC, REFER, CLAVE_CLPV, VEND, CANT, 
            CANT_COST, PRECIO, COSTO, REG_SERIE, UNI_VENTA, EXIST_G, EXISTENCIA, FACTOR_CON, 
            FECHAELAB, CVE_FOLIO, SIGNO, COSTEADO, COSTO_PROM_INI, COSTO_PROM_FIN, COSTO_PROM_GRAL, 
            DESDE_INVE, MOV_ENLAZADO, E_LTPD) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
            $FECHAELAB,
            $cveFolio,
            -1,
            'S',
            $costo,
            $costo,
            $costo,
            'N',
            0,
            $eLtpd
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

        $numMovActual = $numMov;
        $movPorProducto[$cveArt]['NUM_MOV']   = $numMovActual;
        $movPorProducto[$cveArt]['CVE_ART']  = $cveArt;

        // ✅ Incrementar solo los valores necesarios
        $numMov++;
        $cantMov++;

        sqlsrv_free_stmt($stmtProducto);
        sqlsrv_free_stmt($stmtInsert);
    }

    // ✅ 4. Cerrar conexiones
    sqlsrv_free_stmt($stmtPartidas);
    sqlsrv_free_stmt($stmtUltimos);
    sqlsrv_free_stmt($stmtFolio);

    return [
        'porProducto'      => array_values($movPorProducto),
        'totalMovimientos' => $cantMov
    ];
}
function actualizarControl($conexionData, $claveSae, $conn)
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

    //echo json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarControl2R($conexionData, $claveSae, $movimientos, $conn)
{
    if ($conn === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
    }
    $totalMovimientos = $movimientos['totalMovimientos'];
    // Construcción dinámica de la tabla TBLCONTROLXX
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para incrementar ULT_CVE en +1 solo cuando es 0
    $sql = "UPDATE $nombreTabla 
            SET ULT_CVE = ULT_CVE + ?
            WHERE ID_TABLA = 44";

    $param = [$totalMovimientos];
    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $param);

    if ($stmt === false) {
        return json_encode([
            'success' => false,
            'message' => 'Error al actualizar TBLCONTROL (ID_TABLA = 44)',
            'errors' => sqlsrv_errors()
        ]);
    }
    // Cerrar conexión
    sqlsrv_free_stmt($stmt);

    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 44, Movimientos $totalMovimientos.)"
    ]);*/
}
function actualizarInve4($conexionData, $pedidoId, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "INVEXX y CLIEXX actualizados correctamente para el pedido $pedidoId"
    ]);*/
}
function insertarPar_Factr($conexionData, $pedidoId, $cveDoc, $claveSae, $enlace, $movimientos, $conn)
{
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
    $cveDoc = trim($cveDoc);
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

    $movimientosMap = [];
    foreach ($movimientos['porProducto'] as $item) {
        $clave = trim($item['CVE_ART']);
        $movimientosMap[$clave] = $item['NUM_MOV'];
    }

    // ✅ 2. Obtener las partidas del pedido (`PAR_FACTPXX`)
    $sqlPartidas = "SELECT * FROM $tablaPartidasPedido WHERE CVE_DOC = ?";
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

    // Fecha de sincronización
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 4. Insertar cada partida en `PAR_FACTRXX`
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $TOT_PARTIDA = $row['CANT'] * $row['PREC'];
        $cveArtKey    = trim($row['CVE_ART']);
        // **Buscar `E_LTPD` en `$enlaceMap`, si no existe, usar el valor original de `$row['E_LTPD']`**
        $eLtpd = isset($enlaceMap[trim($row['CVE_ART'])]) ? $enlaceMap[trim($row['CVE_ART'])] : $row['E_LTPD'];
        $numMovPartida = $movimientosMap[$row['CVE_ART']] ?? null;
        if ($numMovPartida === null) {
            // Opcional: manejar el caso que no exista movimiento
            throw new Exception("No existe NUM_MOV para el artículo $cveArtKey");
        }
        $sqlInsert = "INSERT INTO $tablaPartidasRemision 
            (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, 
            IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA, TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4, DESC1, 
            DESC2, DESC3, COMI, APAR, ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, 
            TIPO_PROD, TIPO_ELEM, CVE_OBS, REG_SERIE, E_LTPD, NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, 
            APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, VERSION_SINC, UUID,
            IMPU5, IMPU6, IMPU7, IMPU8, IMP5APLA, IMP6APLA, IMP7APLA, IMP8APLA, TOTIMP5, 
            TOTIMP6, TOTIMP7, TOTIMP8,
            CVE_PRODSERV, CVE_UNIDAD, PREC_NETO)
        VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, '',
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
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
            0,
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
            $numMovPartida,
            $row['TOT_PARTIDA'],
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
            $row['TOTIMP8'],
            $row['CVE_PRODSERV'],
            $row['CVE_UNIDAD'],
            0
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
    }
    sqlsrv_free_stmt($stmtInsert);
    sqlsrv_free_stmt($stmtPartidas);
}
function insertarPar_Factr_Clib($conexionData, $pedidoId, $cveDoc, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "PAR_FACTR_CLIB01 insertado correctamente con CVE_DOC $cveDoc y $numPartidas partidas"
    ]);*/
}
function actualizarAlerta_Usuario($conexionData, $claveSae, $conn)
{
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

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA_USUARIO01 actualizada correctamente"
    ]);*/
}
function actualizarAlerta($conexionData, $claveSae, $conn)
{
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

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA01 actualizada correctamente"
    ]);*/
}
function actualizarControl4($conexionData, $claveSae, $conn)
{
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
    /*echo json_encode([
        'success' => true,
        'message' => "TBLCONTROL actualizado correctamente (ID_TABLA = 70, +1 en ULT_CVE)"
    ]);*/
}
function actualizarStatusPedido($conexionData, $pedidoId, $claveSae, $conn, $logFile)
{
    $error = error_get_last();
    $msg = sprintf(
        "[%s] INFO: Actualizando estus del pedido → %s\n",
        date('Y-m-d H:i:s'),
        json_encode($error, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $cve_doc = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP_CLIB"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sqlUpdate = "UPDATE $nombreTabla 
                  SET CAMPLIB3 = 'V' 
                  WHERE CLAVE_DOC = ?";

    $paramsUpdate = [$cve_doc];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        $error = sqlsrv_errors();
        $msg = sprintf(
            "[%s] ERROR: Error al actualizar FACTP_CLIBXX para el pedido $pedidoId → %s\n",
            date('Y-m-d H:i:s'),
            json_encode($error, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar FACTP_CLIBXX para el pedido $pedidoId",
            'errors' => sqlsrv_errors()
        ]));
    }
    $error = sqlsrv_errors();
    $msg = sprintf(
        "[%s] SUCCES: Estado del pedido $pedidoId actualizado → %s\n",
        date('Y-m-d H:i:s'),
        json_encode($error, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);
}
function actualizarDatosComanda($firebaseProjectId, $firebaseApiKey, $pedidoId, $enlace)
{
    $baseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
    $runQueryUrl = $baseUrl . ":runQuery?key=$firebaseApiKey";

    // Construir body para runQuery que filtre por campo folio == $pedidoId
    $body = [
        "structuredQuery" => [
            "from" => [
                ["collectionId" => "COMANDA"]
            ],
            "where" => [
                "fieldFilter" => [
                    "field" => ["fieldPath" => "folio"],
                    "op" => "EQUAL",
                    "value" => ["stringValue" => (string)$pedidoId]
                ]
            ],
            "limit" => 1
        ]
    ];
    $ctxRun = stream_context_create([
        "http" => [
            "header"  => "Content-Type: application/json\r\n",
            "method"  => "POST",
            "content" => json_encode($body),
            "ignore_errors" => true
        ]
    ]);

    $resp = @file_get_contents($runQueryUrl, false, $ctxRun);
    if ($resp === false) {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar runQuery para buscar la comanda.']);
        return;
    }

    $rows = json_decode($resp, true);
    if (empty($rows) || !is_array($rows)) {
        echo json_encode(['success' => false, 'message' => 'Respuesta vacía o inválida de runQuery.']);
        return;
    }

    // Buscar el primer documento devuelto por runQuery
    $document = null;
    foreach ($rows as $entry) {
        if (isset($entry['document'])) {
            $document = $entry['document'];
            break;
        }
    }

    if ($document === null) {
        //echo json_encode(['success' => false, 'message' => "Comanda con folio $pedidoId no encontrada."]);
        return;
    }

    // Extraer docId desde document.name (la parte después de /COMANDA/)
    $name = $document['name'] ?? '';
    $pathParts = explode('/', $name);
    $docId = end($pathParts);
    if (empty($docId)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo extraer el ID del documento.']);
        return;
    }

    // Obtener productos y aplicar modificación del campo lote según $enlace
    $fields = $document['fields'] ?? [];
    $productos = $fields['productos']['arrayValue']['values'] ?? [];
    foreach ($productos as &$producto) {
        if (!isset($producto['mapValue']['fields'])) continue;
        $pf = &$producto['mapValue']['fields'];
        if (($pf['clave']['stringValue'] ?? '') === $enlace['CVE_ART']) {
            $pf['lote'] = ['stringValue' => (string)$enlace['LOTE']];
        }
    }
    unset($producto);

    // Preparar payload para PATCH actualizando solo products
    $fieldsToSave = [
        'productos' => [
            'arrayValue' => [
                'values' => $productos
            ]
        ]
    ];
    $payload = json_encode(['fields' => $fieldsToSave]);

    $urlUpdate = $baseUrl . "/COMANDA/$docId?updateMask.fieldPaths=productos&key=$firebaseApiKey";
    $ctxUpdate = stream_context_create([
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'PATCH',
            'content' => $payload,
            'ignore_errors' => true
        ]
    ]);

    $result = @file_get_contents($urlUpdate, false, $ctxUpdate);
    if ($result === FALSE) {
        //echo json_encode(['success' => false, 'message' => "Error al actualizar la comanda $docId."]);
        return;
    }

    //echo json_encode(['success' => true, 'message' => "Comanda $docId actualizada correctamente."]);
}

//function crearRemision($conexionData, $pedidoId, $claveSae, $noEmpresa, $vendedor,  $conn){
function crearRemision($conexionData, $pedidoId, $claveSae, $noEmpresa, $vendedor, $logFile)
{
    global $firebaseProjectId, $firebaseApiKey;
    $error = error_get_last();
    $msg = sprintf(
        "[%s] INFO: Inicio de la Remision %s\n",
        date('Y-m-d H:i:s'),
        json_encode($error, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    if (!$conn) {
        $error = error_get_last();
        $msg = sprintf(
            "[%s] INFO: Realizando Remision → %s\n",
            date('Y-m-d H:i:s'),
            json_encode($error, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        throw new Exception("No pude conectar a la base de datos");
    }

    actualizarMulti($conexionData, $pedidoId, $claveSae, $conn);
    actualizarInve5($conexionData, $pedidoId, $claveSae, $conn);
    actualizarFolios($conexionData, $claveSae, $conn);
    actualizarInve($conexionData, $pedidoId, $claveSae, $conn);

    $enlaceLote = validarLotes($conexionData, $pedidoId, $claveSae, $conn);
    //var_dump($enlace);

    actualizarInve2($conexionData, $pedidoId, $claveSae, $conn);
    actualizarInve3($conexionData, $pedidoId, $claveSae, $conn);
    actualizarInveClaro($conexionData, $pedidoId, $claveSae, $conn);
    actualizarInveAmazon($conexionData, $pedidoId, $claveSae, $conn);
    actualizarMulti2($conexionData, $pedidoId, $claveSae, $conn);

    actualizarAfac($conexionData, $pedidoId, $claveSae, $conn);

    $CVE_BITA = insertarBitaR($conexionData, $pedidoId, $claveSae, $conn);
    //var_dump("Bita: ", $CVE_BITA);
    actualizarControl3($conexionData, $claveSae, $conn);
    $DAT_ENVIO = gaurdarDatosEnvioR($conexionData, $pedidoId, $claveSae, $conn);
    //actualizamos
    //var_dump("Envio: ", $DAT_ENVIO);
    $DAT_MOSTR = insertatInfoClie($conexionData, $claveSae, $pedidoId, $conn);
    //var_dump("Cliente: ", $DAT_MOSTR);

    $folio = insertarFactr($conexionData, $pedidoId, $claveSae, $CVE_BITA, $DAT_ENVIO, $DAT_MOSTR, $conn);


    $movimientos = insertarMimve($conexionData, $pedidoId, $claveSae, $folio, $enlaceLote, $conn);
    actualizarControl($conexionData, $claveSae, $conn); //?
    actualizarControl2R($conexionData, $claveSae, $movimientos, $conn); //?
    insertarFactr_Clib($conexionData, $folio, $claveSae, $conn);
    actualizarPar_Factp($conexionData, $pedidoId, $folio, $claveSae, $conn);
    actualizarInve4($conexionData, $pedidoId, $claveSae, $conn);
    insertarPar_Factr($conexionData, $pedidoId, $folio, $claveSae, $enlaceLote, $movimientos, $conn);
    actualizarFactp($conexionData, $pedidoId, $claveSae, $conn);
    actualizarFactp2($conexionData, $pedidoId, $folio, $claveSae, $conn);
    actualizarFactp3($conexionData, $pedidoId, $claveSae, $conn);
    insertarDoctoSig($conexionData, $pedidoId, $folio, $claveSae, $conn);
    insertarPar_Factr_Clib($conexionData, $pedidoId, $folio, $claveSae, $conn);
    //insertarInfenvio($conexionData, $pedidoId, $folio, $claveSae, $conn);
    actualizarAlerta_Usuario($conexionData, $claveSae, $conn);
    actualizarAlerta($conexionData, $claveSae, $conn);

    actualizarControl4($conexionData, $claveSae, $conn);

    //actualizarControl5($conexionData, $claveSae); //?
    actualizarControl6($conexionData, $claveSae, $conn);

    foreach ($enlaceLote as $enlace) {
        actualizarDatosComanda(
            $firebaseProjectId,
            $firebaseApiKey,
            $pedidoId,
            $enlace,
            $logFile
        );
    }

    actualizarStatusPedido($conexionData, $pedidoId, $claveSae, $conn, $logFile);
}
