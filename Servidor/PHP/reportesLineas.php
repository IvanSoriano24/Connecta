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
if (!function_exists('formatearClaveVendedor')) {
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
            $where .= " AND c.CLAVE LIKE ?";
            $params[] = '%' . $filtroCliente . '%';
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
function mostrarProductosLineas($conexionData, $filtroFecha, $filtroCliente, $pagina, $porPagina, $lineaSeleccionada)
{
    $offset = ($pagina - 1) * $porPagina;

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
            $where .= " AND c.CLAVE LIKE ?";
            $params[] = '%' . $filtroCliente . '%';
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
        ORDER BY CVE_ART
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY;
        ";

        // Parámetros de la consulta
        $params = [$lineaSeleccionada];
        if (!empty($filtroCliente)) $params[] = '%' . $filtroCliente . '%';
        if (!empty($filtroFecha)) $params[] = $filtroFecha;
        // Otra vez para BaseProductos
        $params[] = $lineaSeleccionada;
        $params[] = $offset;
        $params[] = $porPagina;

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
        $pagina = (int) ($_POST['pagina'] ?? 1);
        $porPagina = (int) ($_POST['porPagina'] ?? 10);
        $lineaSeleccionada = $_POST['lineaSeleccionada'] ?? '';

        mostrarProductosLineas($conexionData, $filtroFecha, $filtroCliente, $pagina, $porPagina, $lineaSeleccionada);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}