<?php
require_once 'firebase.php'; // Archivo de configuración de Firebase
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('obtenerConexion')) {
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
                        'claveSae' => $fields['claveSae']['stringValue']
                    ]
                ];
            }
        }
        return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
    }
}
function formatearClaveVendedor($vendedor)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $vendedor = trim((string)$vendedor);
    $vendedor = str_pad($vendedor, 5, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($vendedor) === 5) {
        return $vendedor;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $vendedor = str_pad($vendedor, 5, ' ', STR_PAD_LEFT);
    return $vendedor;
}
function formatearClaveCliente($clave)
{
    // Convierte a string, quita espacios, luego pad a la izquierda a 10
    return str_pad(trim((string)$clave), 10, ' ', STR_PAD_LEFT);
}

function mostrarLineasReporte($conexionData, $filtroFecha, $filtroCliente)
{
    try {
        $claveSae = $_SESSION['empresa']['claveSae'];

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

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaFacturas = "[{$conexionData['nombreBase']}].[dbo].[FACTF$prefijo]";
        $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF$prefijo]";
        $tablaProductos = "[{$conexionData['nombreBase']}].[dbo].[INVE$prefijo]";
        $tablaLineas = "[{$conexionData['nombreBase']}].[dbo].[CLIN$prefijo]";

        $params = [];
        $where = "WHERE f.STATUS != 'C'";

        if (!empty($filtroCliente)) {
            $where .= " AND c.CLAVE = ?";
            $params[] = $filtroCliente;
        }

        if (!empty($filtroFecha)) {
            $where .= " AND YEAR(f.FECHA_DOC) = ?";
            $params[] = $filtroFecha;
        }

        $sql = "
        WITH VentasPorLinea AS (
            SELECT 
                l.CVE_LIN,
                l.DESC_LIN,
                MONTH(f.FECHA_DOC) AS Mes,
                SUM(p.CANT) AS CantidadVendida
            FROM $tablaLineas l
            JOIN $tablaProductos i ON i.LIN_PROD = l.CVE_LIN
            JOIN $tablaPartidas p ON p.CVE_ART = i.CVE_ART
            JOIN $tablaFacturas f ON f.CVE_DOC = p.CVE_DOC
            JOIN $tablaClientes c ON c.CLAVE = f.CVE_CLPV
            $where
            GROUP BY l.CVE_LIN, l.DESC_LIN, MONTH(f.FECHA_DOC)
        ),
        LineasMeses AS (
            SELECT l.CVE_LIN, l.DESC_LIN, Meses.Mes
            FROM $tablaLineas l
            CROSS JOIN (VALUES 
                (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12)
            ) AS Meses(Mes)
        ),
        Totales AS (
            SELECT 
                lm.CVE_LIN,
                lm.DESC_LIN,
                lm.Mes,
                ISNULL(v.CantidadVendida, 0) AS CantidadVendida
            FROM LineasMeses lm
            LEFT JOIN VentasPorLinea v ON v.CVE_LIN = lm.CVE_LIN AND v.Mes = lm.Mes
        )
        SELECT * FROM (
            SELECT 
                t.CVE_LIN,
                t.DESC_LIN,
                t.Mes,
                t.CantidadVendida
            FROM Totales t
        ) AS fuente
        PIVOT (
            SUM(CantidadVendida)
            FOR Mes IN ([1],[2],[3],[4],[5],[6],[7],[8],[9],[10],[11],[12])
        ) AS pvt
        ORDER BY CVE_LIN";

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                $row[$key] = is_string($value) ? utf8_encode(trim($value)) : ($value ?? 0);
            }
            $reportes[] = $row;
        }

        $countSql = "SELECT COUNT(*) AS total FROM $tablaLineas";
        $countStmt = sqlsrv_query($conn, $countSql);
        $totalRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total = (int) $totalRow['total'];

        sqlsrv_free_stmt($stmt);
        sqlsrv_free_stmt($countStmt);
        sqlsrv_close($conn);

        echo json_encode(['success' => true, 'data' => $reportes, 'total' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function mostrarProductosLineas($conexionData, $filtroFecha, $filtroCliente, $lineaSeleccionada)
{
    try {
        $claveSae = $_SESSION['empresa']['claveSae'];

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

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaFacturas = "[{$conexionData['nombreBase']}].[dbo].[FACTF$prefijo]";
        $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF$prefijo]";
        $tablaProductos = "[{$conexionData['nombreBase']}].[dbo].[INVE$prefijo]";
        $tablaLineas = "[{$conexionData['nombreBase']}].[dbo].[CLIN$prefijo]";

        $params = [$lineaSeleccionada];
        $where = "WHERE f.STATUS != 'C'";

        if (!empty($filtroCliente)) {
            $where .= " AND c.CLAVE = ?";
            $params[] = $filtroCliente;
        }

        if (!empty($filtroFecha)) {
            $where .= " AND YEAR(f.FECHA_DOC) = ?";
            $params[] = $filtroFecha;
        }

        $sql = "
        WITH VentasPorProducto AS (
            SELECT 
                i.CVE_ART,
                i.DESCR,
                i.LIN_PROD,
                l.DESC_LIN,
                MONTH(f.FECHA_DOC) AS Mes,
                SUM(p.CANT) AS CantidadVendida
            FROM $tablaProductos i
            JOIN $tablaLineas l ON i.LIN_PROD = l.CVE_LIN
            JOIN $tablaPartidas p ON p.CVE_ART = i.CVE_ART
            JOIN $tablaFacturas f ON f.CVE_DOC = p.CVE_DOC
            JOIN $tablaClientes c ON c.CLAVE = f.CVE_CLPV
            WHERE f.STATUS != 'C' AND i.LIN_PROD = ?
            " . (!empty($filtroCliente) ? " AND c.CLAVE LIKE ?" : "") . "
            " . (!empty($filtroFecha) ? " AND YEAR(f.FECHA_DOC) = ?" : "") . "
            GROUP BY i.CVE_ART, i.DESCR, i.LIN_PROD, l.DESC_LIN, MONTH(f.FECHA_DOC)
        ),
        Meses AS (
            SELECT 1 AS Mes UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
            SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL
            SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
        ),
        BaseProductos AS (
            SELECT 
                i.CVE_ART,
                i.DESCR,
                i.LIN_PROD,
                l.DESC_LIN,
                m.Mes
            FROM $tablaProductos i
            JOIN $tablaLineas l ON i.LIN_PROD = l.CVE_LIN
            CROSS JOIN Meses m
            WHERE i.LIN_PROD = ?
        ),
        ProductosConVentas AS (
            SELECT 
                b.CVE_ART,
                b.DESCR,
                b.LIN_PROD,
                b.DESC_LIN,
                b.Mes,
                ISNULL(v.CantidadVendida, 0) AS CantidadVendida
            FROM BaseProductos b
            LEFT JOIN VentasPorProducto v 
                ON b.CVE_ART = v.CVE_ART AND b.Mes = v.Mes
        )
        SELECT * FROM (
            SELECT 
                CVE_ART,
                DESCR,
                LIN_PROD,
                DESC_LIN,
                Mes,
                CantidadVendida
            FROM ProductosConVentas
        ) AS fuente
        PIVOT (
            SUM(CantidadVendida)
            FOR Mes IN ([1],[2],[3],[4],[5],[6],[7],[8],[9],[10],[11],[12])
        ) AS pvt
        ORDER BY CVE_ART;
        ";

        // Parámetros de la consulta
        $params = [$lineaSeleccionada];
        if (!empty($filtroCliente)) $params[] = '%' . $filtroCliente . '%';
        if (!empty($filtroFecha)) $params[] = $filtroFecha;
        // Otra vez para BaseProductos
        $params[] = $lineaSeleccionada;

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        $productos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                $row[$key] = is_string($value) ? utf8_encode(trim($value)) : ($value ?? 0);
            }
            $productos[] = $row;
        }

        // Conteo total de productos en la línea
        $countSql = "SELECT COUNT(*) AS total FROM $tablaProductos WHERE LIN_PROD = ?";
        $countStmt = sqlsrv_query($conn, $countSql, [$lineaSeleccionada]);
        $totalRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total = (int) $totalRow['total'];

        sqlsrv_free_stmt($stmt);
        sqlsrv_free_stmt($countStmt);

        $sqlResumen = "
        WITH Meses AS (
            SELECT 1 AS Mes UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
            SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL
            SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
        ),
        LineaSeleccionada AS (
            SELECT 
                l.CVE_LIN,
                l.DESC_LIN
            FROM $tablaLineas l
            WHERE l.CVE_LIN = ?
        ),
        VentasMes AS (
            SELECT 
                l.CVE_LIN,
                l.DESC_LIN,
                MONTH(f.FECHA_DOC) AS Mes,
                SUM(p.CANT) AS CantidadVendida
            FROM $tablaLineas l
            JOIN $tablaProductos i ON i.LIN_PROD = l.CVE_LIN
            JOIN $tablaPartidas p ON p.CVE_ART = i.CVE_ART
            JOIN $tablaFacturas f ON f.CVE_DOC = p.CVE_DOC
            JOIN $tablaClientes c ON c.CLAVE = f.CVE_CLPV
            WHERE f.STATUS != 'C' AND i.LIN_PROD = ?
            " . (!empty($filtroCliente) ? " AND c.CLAVE LIKE ?" : "") . "
            " . (!empty($filtroFecha) ? " AND YEAR(f.FECHA_DOC) = ?" : "") . "
            GROUP BY l.CVE_LIN, l.DESC_LIN, MONTH(f.FECHA_DOC)
        ),
        ResumenConMeses AS (
            SELECT 
                ls.CVE_LIN,
                ls.DESC_LIN,
                m.Mes,
                ISNULL(vm.CantidadVendida, 0) AS CantidadVendida
            FROM LineaSeleccionada ls
            CROSS JOIN Meses m
            LEFT JOIN VentasMes vm ON vm.CVE_LIN = ls.CVE_LIN AND vm.Mes = m.Mes
        )
        SELECT * FROM (
            SELECT 
                CVE_LIN,
                DESC_LIN,
                Mes,
                CantidadVendida
            FROM ResumenConMeses
        ) AS fuente
        PIVOT (
            SUM(CantidadVendida)
            FOR Mes IN ([1],[2],[3],[4],[5],[6],[7],[8],[9],[10],[11],[12])
        ) AS pvt;
        ";

        $resumenParams = [$lineaSeleccionada, $lineaSeleccionada];
        if (!empty($filtroCliente)) $resumenParams[] = '%' . $filtroCliente . '%';
        if (!empty($filtroFecha)) $resumenParams[] = $filtroFecha;

        $resumenStmt = sqlsrv_query($conn, $sqlResumen, $resumenParams);
        if ($resumenStmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar totalVentasSql', 'errors' => sqlsrv_errors()]));
        }

        $resumenRow = sqlsrv_fetch_array($resumenStmt, SQLSRV_FETCH_ASSOC);
        if ($resumenRow) {
            foreach ($resumenRow as $key => $value) {
                $resumenRow[$key] = is_string($value) ? utf8_encode(trim($value)) : ($value ?? 0);
            }

            $totalLinea = 0;
            for ($i = 1; $i <= 12; $i++) {
                $totalLinea += (int) ($resumenRow[$i] ?? 0);
            }

            $resumen = [
                'descripcionLinea' => $resumenRow['DESC_LIN'],
                'totalLinea' => $totalLinea
            ];
            for ($i = 1; $i <= 12; $i++) {
                $resumen["mes_$i"] = (int) ($resumenRow[$i] ?? 0);
            }
        }

        sqlsrv_free_stmt($resumenStmt);
        sqlsrv_close($conn);

        echo json_encode([
            'success' => true,
            'data' => $productos,
            'total' => $total,
            'resumen' => $resumen ?? null,
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function obtenerEstadoCuentaGeneral($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente)
{
    try {
        $claveSae = $_SESSION['empresa']['claveSae'];

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

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaCuenM    = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M$prefijo]";
        $tablaCuenDet  = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET$prefijo]";
        $tablaConcepto = "[{$conexionData['nombreBase']}].[dbo].[CONC$prefijo]";

        $where = "
        WHERE
            CUENM.FECHA_VENC < CAST(GETDATE() AS DATE)
            AND C.STATUS <> 'B'
        ";
        $params = [];

        if (!empty($filtroCliente)) {
            $where .= " AND C.CLAVE = ?";
            $params[] = $filtroCliente;
        }

        // ------ AGREGAR FILTRO DE FECHAS ------
        // Fechas en formato yyyy-mm-dd
        if (!empty($filtroFechaInicio) && !empty($filtroFechaFin)) {
            $where .= " AND CUENM.FECHA_APLI BETWEEN ? AND ?";
            $params[] = $filtroFechaInicio;
            $params[] = $filtroFechaFin;
        } else if (!empty($filtroFechaInicio)) {
            $where .= " AND CUENM.FECHA_APLI >= ?";
            $params[] = $filtroFechaInicio;
        } else if (!empty($filtroFechaFin)) {
            $where .= " AND CUENM.FECHA_APLI <= ?";
            $params[] = $filtroFechaFin;
        }

        $sql = "
            SELECT
                C.CLAVE AS CLAVE,
                CONC.NUM_CPTO AS TIPO,
                CONC.DESCR AS CONCEPTO,
                CUENM.REFER AS DOCUMENTO,
                CUENM.NO_FACTURA AS NUM,
                CUENM.FECHA_APLI AS FECHA_APLICACION,
                CUENM.FECHA_VENC AS FECHA_VENCIMIENTO,
                CUENM.IMPORTE AS CARGOS,
                COALESCE(SUM(CUEN.IMPORTE), 0) AS ABONOS,
                (CUENM.IMPORTE - COALESCE(SUM(CUEN.IMPORTE), 0)) AS SALDO
            FROM $tablaCuenM CUENM  
            LEFT JOIN $tablaClientes C ON C.CLAVE = CUENM.CVE_CLIE
            LEFT JOIN $tablaCuenDet CUEN ON CUEN.CVE_CLIE = CUENM.CVE_CLIE
                AND CUEN.REFER = CUENM.REFER
                AND CUEN.NUM_CARGO = CUENM.NUM_CARGO
            LEFT JOIN $tablaConcepto CONC ON CUENM.NUM_CPTO = CONC.NUM_CPTO
            $where
            GROUP BY
                C.CLAVE,
                CONC.NUM_CPTO,
                CONC.DESCR,
                CUENM.REFER,
                CUENM.NO_FACTURA,
                CUENM.FECHA_APLI,
                CUENM.FECHA_VENC,
                CUENM.IMPORTE
            ORDER BY
                CUENM.FECHA_VENC ASC
        ";

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatea fechas SQLSRV
            foreach (['FECHA_APLICACION', 'FECHA_VENCIMIENTO'] as $fechaCampo) {
                if (isset($row[$fechaCampo]) && $row[$fechaCampo] instanceof DateTime) {
                    $row[$fechaCampo] = $row[$fechaCampo]->format('Y-m-d');
                } elseif (isset($row[$fechaCampo]) && is_array($row[$fechaCampo]) && isset($row[$fechaCampo]['date'])) {
                    // (por si tienes objetos como en PDO)
                    $row[$fechaCampo] = substr($row[$fechaCampo]['date'], 0, 10);
                }
            }

            $row['CARGOS'] = (float)$row['CARGOS'];
            $row['ABONOS'] = (float)$row['ABONOS'];
            $row['SALDO']  = (float)$row['SALDO'];

            foreach ($row as $k => $v) {
                if (is_string($v)) $row[$k] = utf8_encode(trim($v));
            }
            $reportes[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        echo json_encode(['success' => true, 'data' => $reportes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function obtenerEstadoCuentaDetalle($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente)
{
    try {
        $claveSae = $_SESSION['empresa']['claveSae'];

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

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaCuenM    = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M$prefijo]";
        $tablaCuenDet  = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET$prefijo]";
        $tablaConcepto = "[{$conexionData['nombreBase']}].[dbo].[CONC$prefijo]";

        // --- Armado de WHERE y parámetros
        $paramsCargos = [];
        $whereCargos = "WHERE CUENM.STATUS <> 'C'";
        if (!empty($filtroCliente)) {
            $whereCargos .= " AND C.CLAVE = ?";
            $paramsCargos[] = $filtroCliente;
        }
        if (!empty($filtroFechaInicio)) {
            $whereCargos .= " AND CUENM.FECHA_APLI >= ?";
            $paramsCargos[] = $filtroFechaInicio;
        }
        if (!empty($filtroFechaFin)) {
            $whereCargos .= " AND CUENM.FECHA_APLI <= ?";
            $paramsCargos[] = $filtroFechaFin;
        }

        $paramsAbonos = [];
        $whereAbonos = "WHERE 1=1";
        if (!empty($filtroCliente)) {
            $whereAbonos .= " AND C.CLAVE = ?";
            $paramsAbonos[] = $filtroCliente;
        }
        if (!empty($filtroFechaInicio)) {
            $whereAbonos .= " AND CUEN.FECHA_APLI >= ?";
            $paramsAbonos[] = $filtroFechaInicio;
        }
        if (!empty($filtroFechaFin)) {
            $whereAbonos .= " AND CUEN.FECHA_APLI <= ?";
            $paramsAbonos[] = $filtroFechaFin;
        }

        // --- SELECTs
        $sqlCargos = "
            SELECT
                C.CLAVE AS CLAVE,
                CONC.NUM_CPTO AS TIPO,
                CONC.DESCR AS CONCEPTO,
                CUENM.REFER AS DOCUMENTO,
                CUENM.NO_FACTURA AS NUM,
                CUENM.FECHA_APLI AS FECHA_APLICACION,
                CUENM.FECHA_VENC AS FECHA_VENCIMIENTO,
                CUENM.IMPORTE AS CARGO,
                NULL AS ABONO,
                NULL AS SALDO,
                'CARGO' AS TIPO_REGISTRO
            FROM $tablaCuenM CUENM
            LEFT JOIN $tablaClientes C ON C.CLAVE = CUENM.CVE_CLIE
            LEFT JOIN $tablaConcepto CONC ON CUENM.NUM_CPTO = CONC.NUM_CPTO
            $whereCargos
        ";

        $sqlAbonos = "
            SELECT
                C.CLAVE AS CLAVE,
                CONC.NUM_CPTO AS TIPO,
                CONC.DESCR AS CONCEPTO,
                CUEN.REFER AS DOCUMENTO,
                CUEN.NO_FACTURA AS NUM,
                CUEN.FECHA_APLI AS FECHA_APLICACION,
                CUEN.FECHA_VENC AS FECHA_VENCIMIENTO,
                NULL AS CARGO,
                CUEN.IMPORTE AS ABONO,
                NULL AS SALDO,
                'ABONO' AS TIPO_REGISTRO
            FROM $tablaCuenDet CUEN
            LEFT JOIN $tablaClientes C ON C.CLAVE = CUEN.CVE_CLIE
            LEFT JOIN $tablaConcepto CONC ON CUEN.NUM_CPTO = CONC.NUM_CPTO
            $whereAbonos
        ";

        // --- Ejecución y combinación de params
        $sql = "
            $sqlCargos
            UNION ALL
            $sqlAbonos
            ORDER BY DOCUMENTO, FECHA_APLICACION, TIPO_REGISTRO
        ";
        $allParams = array_merge($paramsCargos, $paramsAbonos);

        $stmt = sqlsrv_query($conn, $sql, $allParams);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        // ... Resto igual ...

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach (['FECHA_APLICACION', 'FECHA_VENCIMIENTO'] as $fechaCampo) {
                if (isset($row[$fechaCampo]) && $row[$fechaCampo] instanceof DateTime) {
                    $row[$fechaCampo] = $row[$fechaCampo]->format('Y-m-d');
                } elseif (isset($row[$fechaCampo]) && is_array($row[$fechaCampo]) && isset($row[$fechaCampo]['date'])) {
                    $row[$fechaCampo] = substr($row[$fechaCampo]['date'], 0, 10);
                }
            }
            foreach ($row as $k => $v) {
                if (is_string($v)) $row[$k] = utf8_encode(trim($v));
                if (in_array($k, ['CARGO','ABONO','SALDO']) && $v !== null) $row[$k] = floatval($v);
            }
            $reportes[] = $row;
        }

        // ... El mismo agrupado y acumulado ...
        $agrupados = [];
        foreach ($reportes as $mov) {
            $doc = $mov['DOCUMENTO'];
            if (!isset($agrupados[$doc])) $agrupados[$doc] = [];
            $agrupados[$doc][] = $mov;
        }

        $resultado = [];
        foreach ($agrupados as $doc => $movs) {
            $saldo = 0;
            foreach ($movs as $i => $mov) {
                if ($mov['CARGO'] !== null) {
                    $saldo += $mov['CARGO'];
                    $resultado[] = [
                        'CLAVE' => $mov['CLAVE'],
                        'TIPO' => $mov['TIPO'],
                        'CONCEPTO' => $mov['CONCEPTO'],
                        'DOCUMENTO' => $mov['DOCUMENTO'],
                        'NUM' => $mov['NUM'],
                        'FECHA_APLICACION' => $mov['FECHA_APLICACION'],
                        'FECHA_VENCIMIENTO' => $mov['FECHA_VENCIMIENTO'],
                        'CARGO' => $mov['CARGO'],
                        'ABONO' => '',
                        'SALDO' => '',
                    ];
                } else if ($mov['ABONO'] !== null) {
                    $saldo -= $mov['ABONO'];
                    $resultado[] = [
                        'CLAVE' => '',
                        'TIPO' => $mov['TIPO'],
                        'CONCEPTO' => $mov['CONCEPTO'],
                        'DOCUMENTO' => $mov['DOCUMENTO'],
                        'NUM' => $mov['NUM'],
                        'FECHA_APLICACION' => $mov['FECHA_APLICACION'],
                        'FECHA_VENCIMIENTO' => $mov['FECHA_VENCIMIENTO'],
                        'CARGO' => '',
                        'ABONO' => $mov['ABONO'],
                        'SALDO' => $saldo,
                    ];
                }
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        echo json_encode(['success' => true, 'data' => $resultado]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerCobranzaDetalle($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente, $filtroVendedor)
{
    try {
        $claveSae = $_SESSION['empresa']['claveSae'];

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

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaCuenM    = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M$prefijo]";
        $tablaCuenDet  = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET$prefijo]";
        $tablaConcepto = "[{$conexionData['nombreBase']}].[dbo].[CONC$prefijo]";
        $tablaMonedas  = "[{$conexionData['nombreBase']}].[dbo].[MONED$prefijo]";

        $where = "WHERE CUENM.STATUS <> 'B'";
        $params = [];

        // --- Filtro cliente primero ---
        if (!empty($filtroCliente)) {
            $where .= " AND C.CLAVE = ?";
            $params[] = $filtroCliente;
        } else if (!empty($filtroVendedor)) {
            $where .= " AND C.CVE_VEND = ?";
            $params[] = $filtroVendedor;
        }
        // Si ninguno, no agregues nada (trae todos)

        // --- Filtros de rango de fechas ---
        if (!empty($filtroFechaInicio) && !empty($filtroFechaFin)) {
            $where .= " AND CUENM.FECHA_APLI BETWEEN ? AND ?";
            $params[] = $filtroFechaInicio;
            $params[] = $filtroFechaFin;
        } else if (!empty($filtroFechaInicio)) {
            $where .= " AND CUENM.FECHA_APLI >= ?";
            $params[] = $filtroFechaInicio;
        } else if (!empty($filtroFechaFin)) {
            $where .= " AND CUENM.FECHA_APLI <= ?";
            $params[] = $filtroFechaFin;
        }

        // --- Armamos la consulta ---
        $sql = "
            SELECT
                C.CLAVE AS CLAVE,
                C.NOMBRE AS NOMBRE_CLIENTE,
                C.TELEFONO AS TELEFONO_CLIENTE,
                CONC.NUM_CPTO AS TIPO,
                CONC.DESCR AS CONCEPTO,
                CUENM.REFER AS DOCUMENTO,
                CUENM.NO_FACTURA AS NUM,
                CUENM.FECHA_APLI AS FECHA_APLICACION,
                CUENM.FECHA_VENC AS FECHA_VENCIMIENTO,
                CUENM.IMPORTE AS CARGOS,
                COALESCE(SUM(CUEN.IMPORTE), 0) AS ABONOS,
                (CUENM.IMPORTE - COALESCE(SUM(CUEN.IMPORTE), 0)) AS SALDO,
                MON.DESCR AS MONEDA
            FROM $tablaCuenM CUENM  
            LEFT JOIN $tablaClientes C ON C.CLAVE = CUENM.CVE_CLIE
            LEFT JOIN $tablaCuenDet CUEN ON CUEN.CVE_CLIE = CUENM.CVE_CLIE
                AND CUEN.REFER = CUENM.REFER
                AND CUEN.NUM_CARGO = CUENM.NUM_CARGO
            LEFT JOIN $tablaConcepto CONC ON CUENM.NUM_CPTO = CONC.NUM_CPTO
            LEFT JOIN $tablaMonedas MON ON CUENM.NUM_MONED = MON.NUM_MONED
            $where
            GROUP BY
                C.CLAVE,
                C.NOMBRE,
                C.TELEFONO,
                CONC.NUM_CPTO,
                CONC.DESCR,
                CUENM.REFER,
                CUENM.NO_FACTURA,
                CUENM.FECHA_APLI,
                CUENM.FECHA_VENC,
                CUENM.IMPORTE,
                MON.DESCR
            HAVING ABS(CUENM.IMPORTE - COALESCE(SUM(CUEN.IMPORTE), 0)) >= 0.1
            ORDER BY
                CUENM.FECHA_VENC ASC
        ";

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Ignora saldos negativos
            if ($row['SALDO'] < 0.1) {
                continue;
            }
            // Formatea fechas SQLSRV
            foreach (['FECHA_APLICACION', 'FECHA_VENCIMIENTO'] as $fechaCampo) {
                if (isset($row[$fechaCampo]) && $row[$fechaCampo] instanceof DateTime) {
                    $row[$fechaCampo] = $row[$fechaCampo]->format('Y-m-d');
                } elseif (isset($row[$fechaCampo]) && is_array($row[$fechaCampo]) && isset($row[$fechaCampo]['date'])) {
                    $row[$fechaCampo] = substr($row[$fechaCampo]['date'], 0, 10);
                }
            }

            $row['CARGOS'] = (float)$row['CARGOS'];
            $row['ABONOS'] = (float)$row['ABONOS'];
            $row['SALDO']  = (float)$row['SALDO'];

            foreach ($row as $k => $v) {
                if (is_string($v)) $row[$k] = utf8_encode(trim($v));
            }
            // Si es filtro vendedor, agrega datos de cliente siempre.
            $reportes[] = [
                'CLAVE'    => $row['CLAVE'],
                'NOMBRE_CLIENTE' => $row['NOMBRE_CLIENTE'],
                'TELEFONO_CLIENTE' => $row['TELEFONO_CLIENTE'],
                'TIPO'     => $row['TIPO'],
                'CONCEPTO' => $row['CONCEPTO'],
                'DOCUMENTO'=> $row['DOCUMENTO'],
                'NUM'      => $row['NUM'],
                'FECHA_APLICACION' => $row['FECHA_APLICACION'],
                'FECHA_VENCIMIENTO' => $row['FECHA_VENCIMIENTO'],
                'CARGOS'   => $row['CARGOS'],
                'ABONOS'   => $row['ABONOS'],
                'SALDO'    => $row['SALDO'],
                'MONEDA'   => $row['MONEDA']
            ];
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        if (count($reportes) > 0) {
            echo json_encode([
                'success' => true,
                'data' => $reportes
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No se encontraron datos.',
                'debug' => [
                    'filtroFechaInicio' => $filtroFechaInicio,
                    'filtroFechaFin'    => $filtroFechaFin,
                    'filtroCliente'     => $filtroCliente,
                    'filtroVendedor'    => $filtroVendedor,
                    'sql'               => $sql,
                    'params'            => $params
                ]
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerFacturasNoPagadas($conexionData, $filtroCliente)
{
    try {
        $claveSae = $_SESSION['empresa']['claveSae'];

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

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaCuenM    = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M$prefijo]";
        $tablaCuenDet  = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET$prefijo]";
        $tablaMonedas  = "[{$conexionData['nombreBase']}].[dbo].[MONED$prefijo]";

        $docWidth = 20;
        $decimals = 0;

        $whereCliente = "";
        $params = [];
        if (!empty($filtroCliente)) {
            $whereCliente = "AND CLI.CLAVE = ?";
            $params[] = $filtroCliente;
        }

        $sql = "
        WITH M AS (
          SELECT
              CLI.CLAVE    AS CLAVE_CLIENTE,
              CLI.NOMBRE   AS NOMBRE_CLIENTE,
              M0.REFER     AS REFERENCIA,
              RIGHT(REPLICATE('0', $docWidth) + RTRIM(LTRIM(M0.REFER)), $docWidth) AS DOC_KEY,
              M0.NO_FACTURA AS FACTURA,
              M0.NUM_CPTO   AS CONCEPTO,
              M0.FECHA_VENC AS FECHA_VENCIMIENTO,
              M0.FECHA_APLI AS FECHA_APLICACION,
              CAST(CASE WHEN M0.SIGNO = -1 THEN M0.IMPORTE * -1 ELSE M0.IMPORTE END AS DECIMAL(18,6)) AS MONTO_ORIGINAL_RAW,
              MON.DESCR AS MONEDA
          FROM $tablaCuenM AS M0
          INNER JOIN $tablaClientes  AS CLI ON CLI.CLAVE = M0.CVE_CLIE
          LEFT  JOIN $tablaMonedas AS MON ON MON.NUM_MONED = M0.NUM_MONED
          WHERE
              CLI.STATUS <> 'B'
              $whereCliente
              AND M0.NUM_CPTO NOT IN (8,9,12)
        ),
        D AS (
          SELECT
              D0.CVE_CLIE,
              RIGHT(REPLICATE('0', $docWidth) + RTRIM(LTRIM(D0.REFER)), $docWidth) AS DOC_KEY,
              CAST(SUM(CASE 
                          WHEN D0.SIGNO = -1 AND D0.NUM_CPTO <> 16 
                          THEN D0.IMPORTE * D0.SIGNO 
                          ELSE 0 
                       END) AS DECIMAL(18,6)) AS SUMA_DETALLE_RAW
          FROM $tablaCuenDet AS D0
          GROUP BY
              D0.CVE_CLIE,
              RIGHT(REPLICATE('0', $docWidth) + RTRIM(LTRIM(D0.REFER)), $docWidth)
        ),
        S AS (
          SELECT
              M.CLAVE_CLIENTE,
              M.NOMBRE_CLIENTE,
              M.REFERENCIA,
              M.FACTURA,
              M.CONCEPTO,
              M.FECHA_VENCIMIENTO,
              M.FECHA_APLICACION,
              ROUND(M.MONTO_ORIGINAL_RAW, $decimals)                        AS MONTO_ORIGINAL_RED,
              ROUND(COALESCE(-D.SUMA_DETALLE_RAW, 0), $decimals)            AS MONTO_PAGADO_RED,
              ROUND(M.MONTO_ORIGINAL_RAW + COALESCE(D.SUMA_DETALLE_RAW,0), $decimals) AS SALDO_RESTANTE_RED,
              M.MONEDA
          FROM M
          LEFT JOIN D
            ON D.CVE_CLIE = M.CLAVE_CLIENTE
           AND D.DOC_KEY  = M.DOC_KEY
        )
        SELECT
            CLAVE_CLIENTE AS CLAVE,
            NOMBRE_CLIENTE AS NOMBRE_CLIENTE,
            REFERENCIA AS REFERENCIA,
            FACTURA AS FACTURA,
            CONCEPTO AS CONCEPTO,
            FECHA_VENCIMIENTO AS FECHA_VENCIMIENTO,
            FECHA_APLICACION AS FECHA_APLICACION,
            CONVERT(DECIMAL(18,2), MONTO_ORIGINAL_RED)  AS MONTO_ORIGINAL,
            CONVERT(DECIMAL(18,2), MONTO_PAGADO_RED)    AS MONTO_PAGADO,
            CONVERT(DECIMAL(18,2), SALDO_RESTANTE_RED)  AS SALDO_RESTANTE,
            MONEDA AS MONEDA,
            CASE
              WHEN SALDO_RESTANTE_RED > 0 AND FECHA_VENCIMIENTO < CAST(GETDATE() AS DATE) THEN 'VENCIDA'
              WHEN SALDO_RESTANTE_RED > 0 THEN 'PENDIENTE'
              ELSE 'PAGADA'
            END AS ESTADO_CUENTA
        FROM S
        WHERE SALDO_RESTANTE_RED > 0
        ORDER BY FECHA_VENCIMIENTO ASC
        ";

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatea fechas SQLSRV
            foreach (['FECHA_APLICACION', 'FECHA_VENCIMIENTO'] as $fechaCampo) {
                if (isset($row[$fechaCampo]) && $row[$fechaCampo] instanceof DateTime) {
                    $row[$fechaCampo] = $row[$fechaCampo]->format('Y-m-d');
                } elseif (isset($row[$fechaCampo]) && is_array($row[$fechaCampo]) && isset($row[$fechaCampo]['date'])) {
                    $row[$fechaCampo] = substr($row[$fechaCampo]['date'], 0, 10);
                }
            }

            $row['MONTO_ORIGINAL'] = (float)$row['MONTO_ORIGINAL'];
            $row['MONTO_PAGADO'] = (float)$row['MONTO_PAGADO'];
            $row['SALDO_RESTANTE'] = (float)$row['SALDO_RESTANTE'];

            foreach ($row as $k => $v) {
                if (is_string($v)) $row[$k] = utf8_encode(trim($v));
            }
            $reportes[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        echo json_encode(['success' => true, 'data' => $reportes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
        $filtroFecha = $_POST['filtroFecha'] ?? 'Todos';
        $filtroCliente = $_POST['filtroCliente'] ?? '';
        $filtroCliente = formatearClaveCliente($filtroCliente);

        mostrarLineasReporte($conexionData, $filtroFecha, $filtroCliente);
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

        $conexionData = $conexionResult['data'];
        $filtroFecha = $_POST['filtroFecha'] ?? 'Todos';
        $filtroCliente = $_POST['filtroCliente'] ?? '';
        $lineaSeleccionada = $_POST['lineaSeleccionada'] ?? '';
        $filtroCliente = formatearClaveCliente($filtroCliente);

        mostrarProductosLineas($conexionData, $filtroFecha, $filtroCliente, $lineaSeleccionada);
        break;
    case 3:
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
        $filtroCliente = $_POST['filtroCliente'] ?? '';
        $filtroFechaInicio = $_POST['filtroFechaInicio'] ?? '';
        $filtroFechaFin = $_POST['filtroFechaFin'] ?? '';

        // SOLO AQUÍ haces el formateo SI filtroCliente no viene vacío
        $filtroCliente = formatearClaveCliente($filtroCliente);

        obtenerEstadoCuentaGeneral($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente);
        break;
    case 4:
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
        $filtroVendedor = $_POST['filtroVendedor'] ?? '';
        $filtroCliente = $_POST['filtroCliente'] ?? '';
        $filtroFechaInicio = $_POST['filtroFechaInicio'] ?? '';
        $filtroFechaFin = $_POST['filtroFechaFin'] ?? '';
        if (!empty($filtroCliente)) {
            $filtroCliente = formatearClaveCliente(trim($filtroCliente));
        }
        if (!empty($filtroVendedor)) {
            $filtroVendedor = formatearClaveVendedor(trim($filtroVendedor));
        }

        obtenerCobranzaDetalle($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente, $filtroVendedor);
        break;
    case 5:
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
        $filtroCliente = $_POST['filtroCliente'] ?? '';
        $filtroFechaInicio = $_POST['filtroFechaInicio'] ?? '';
        $filtroFechaFin = $_POST['filtroFechaFin'] ?? '';

        // SOLO AQUÍ haces el formateo SI filtroCliente no viene vacío
        $filtroCliente = formatearClaveCliente($filtroCliente);

        obtenerEstadoCuentaDetalle($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente);
        break;
    case 6:
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
        $filtroCliente = $_POST['filtroCliente'] ?? '';

        // SOLO AQUÍ haces el formateo SI filtroCliente no viene vacío
        if (!empty($filtroCliente)) {
            $filtroCliente = formatearClaveCliente($filtroCliente);
        }

        obtenerFacturasNoPagadas($conexionData, $filtroCliente);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}