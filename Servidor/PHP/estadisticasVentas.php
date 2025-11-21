<?php
// Verificar si la sesión ya está iniciada antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'firebase.php';
require 'utils.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['empresa'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

$tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
$claveVendedor = $_SESSION['empresa']['claveUsuario'] ?? '';
$noEmpresa = $_SESSION['empresa']['noEmpresa'];
$claveSae = $_SESSION['empresa']['claveSae'];

// Obtener datos de conexión
$conexionData = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);

if (!$conexionData['success']) {
    echo json_encode(['success' => false, 'message' => $conexionData['message']]);
    exit;
}

$conexionData = $conexionData['data'];

// Conectar a la base de datos
$serverName = $conexionData['host'];
$connectionInfo = [
    "Database" => $conexionData['nombreBase'],
    "UID" => $conexionData['usuario'],
    "PWD" => $conexionData['password'],
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos']);
    exit;
}

// Nombres de tablas
$nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
$nombreTablaCLIB = "[{$conexionData['nombreBase']}].[dbo].[FACTP_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

// Determinar si el usuario es administrador (puede ver todos los vendedores)
$esAdministrador = in_array($tipoUsuario, ['ADMINISTRADOR', 'SUPER-ALMACENISTA', 'ADMIISTRADOR']);

// Formatear clave vendedor solo si NO es administrador
$claveVendedorFormateada = '';
if (!$esAdministrador && $claveVendedor) {
    $claveVendedorFormateada = formatearClaveVendedor($claveVendedor);
}

// Obtener filtros de fecha (pueden venir por POST o GET)
$fechaInicio = null;
$fechaFin = null;

// Leer datos JSON si vienen por POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data && isset($data['fechaInicio']) && isset($data['fechaFin'])) {
    $fechaInicio = $data['fechaInicio'];
    $fechaFin = $data['fechaFin'];
} elseif (isset($_POST['fechaInicio']) && isset($_POST['fechaFin'])) {
    $fechaInicio = $_POST['fechaInicio'];
    $fechaFin = $_POST['fechaFin'];
} elseif (isset($_GET['fechaInicio']) && isset($_GET['fechaFin'])) {
    $fechaInicio = $_GET['fechaInicio'];
    $fechaFin = $_GET['fechaFin'];
}

// Estadísticas de ventas activas
// Usar LEFT JOIN como en ventas.php y filtrar por CAMPLIB3
$sqlActivas = "
    SELECT 
        COUNT(DISTINCT f.CVE_DOC) AS totalPedidos,
        ISNULL(SUM(f.IMPORTE), 0) AS totalImporte
    FROM $nombreTabla2 f
    LEFT JOIN $nombreTablaCLIB clib ON clib.CLAVE_DOC = f.CVE_DOC
    WHERE LTRIM(RTRIM(clib.CAMPLIB3)) = 'A'
";

// Estadísticas de ventas vendidas
$sqlVendidas = "
    SELECT 
        COUNT(DISTINCT f.CVE_DOC) AS totalPedidos,
        ISNULL(SUM(f.IMPORTE), 0) AS totalImporte
    FROM $nombreTabla2 f
    LEFT JOIN $nombreTablaCLIB clib ON clib.CLAVE_DOC = f.CVE_DOC
    WHERE LTRIM(RTRIM(clib.CAMPLIB3)) = 'V'
";

// Aplicar filtro por vendedor SOLO si NO es administrador
// Los administradores ven todos los pedidos de todos los vendedores
$paramsActivas = [];
$paramsVendidas = [];
if (!$esAdministrador && $claveVendedorFormateada) {
    $sqlActivas .= " AND f.CVE_VEND = ?";
    $sqlVendidas .= " AND f.CVE_VEND = ?";
    $paramsActivas[] = $claveVendedorFormateada;
    $paramsVendidas[] = $claveVendedorFormateada;
}

// Aplicar filtro de fecha si se proporcionó
if ($fechaInicio && $fechaFin) {
    $sqlActivas .= " AND CAST(f.FECHAELAB AS DATE) >= ? AND CAST(f.FECHAELAB AS DATE) <= ?";
    $sqlVendidas .= " AND CAST(f.FECHAELAB AS DATE) >= ? AND CAST(f.FECHAELAB AS DATE) <= ?";
    $paramsActivas[] = $fechaInicio;
    $paramsActivas[] = $fechaFin;
    $paramsVendidas[] = $fechaInicio;
    $paramsVendidas[] = $fechaFin;
}

// Ejecutar consulta de activas
$stmtActivas = sqlsrv_query($conn, $sqlActivas, $paramsActivas);
$activas = ['totalPedidos' => 0, 'totalImporte' => 0];
if ($stmtActivas) {
    $row = sqlsrv_fetch_array($stmtActivas, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $activas = [
            'totalPedidos' => (int)$row['totalPedidos'],
            'totalImporte' => (float)$row['totalImporte']
        ];
    }
    sqlsrv_free_stmt($stmtActivas);
} else {
    // Log error y devolver información de debug
    $errors = sqlsrv_errors();
    $debugInfo = [
        'sql' => $sqlActivas,
        'params' => $paramsActivas,
        'errors' => $errors
    ];
    error_log("Error en consulta activas: " . json_encode($debugInfo));
}

// Ejecutar consulta de vendidas
$stmtVendidas = sqlsrv_query($conn, $sqlVendidas, $paramsVendidas);
$vendidas = ['totalPedidos' => 0, 'totalImporte' => 0];
if ($stmtVendidas) {
    $row = sqlsrv_fetch_array($stmtVendidas, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $vendidas = [
            'totalPedidos' => (int)$row['totalPedidos'],
            'totalImporte' => (float)$row['totalImporte']
        ];
    }
    sqlsrv_free_stmt($stmtVendidas);
} else {
    // Log error y devolver información de debug
    $errors = sqlsrv_errors();
    $debugInfo = [
        'sql' => $sqlVendidas,
        'params' => $paramsVendidas,
        'errors' => $errors
    ];
    error_log("Error en consulta vendidas: " . json_encode($debugInfo));
}

// Nombres de tablas adicionales
$nombreTablaVEND = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
$nombreTablaCLIE = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
$nombreTablaPAR = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
$nombreTablaINVE = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

// Consulta para obtener el vendedor con mayores ventas
$sqlVendedorTop = "
    SELECT TOP 1
        v.CVE_VEND,
        v.NOMBRE,
        COUNT(DISTINCT f.CVE_DOC) AS totalVentas,
        ISNULL(SUM(f.IMPORTE), 0) AS totalImporte
    FROM $nombreTabla2 f
    LEFT JOIN $nombreTablaCLIB clib ON clib.CLAVE_DOC = f.CVE_DOC
    LEFT JOIN $nombreTablaVEND v ON v.CVE_VEND = f.CVE_VEND
    WHERE LTRIM(RTRIM(clib.CAMPLIB3)) = 'V'
";

// Consulta para obtener el cliente con más compras
$sqlClienteTop = "
    SELECT TOP 1
        c.CLAVE,
        c.NOMBRE,
        COUNT(DISTINCT f.CVE_DOC) AS totalCompras,
        ISNULL(SUM(f.IMPORTE), 0) AS totalImporte
    FROM $nombreTabla2 f
    LEFT JOIN $nombreTablaCLIB clib ON clib.CLAVE_DOC = f.CVE_DOC
    LEFT JOIN $nombreTablaCLIE c ON c.CLAVE = f.CVE_CLPV
    WHERE LTRIM(RTRIM(clib.CAMPLIB3)) = 'V'
";

// Consulta para obtener el producto más vendido
$sqlProductoTop = "
    SELECT TOP 1
        i.CVE_ART,
        i.DESCR,
        SUM(p.CANT) AS totalCantidad,
        COUNT(DISTINCT p.CVE_DOC) AS totalPedidos
    FROM $nombreTablaPAR p
    INNER JOIN $nombreTabla2 f ON p.CVE_DOC = f.CVE_DOC
    LEFT JOIN $nombreTablaCLIB clib ON clib.CLAVE_DOC = f.CVE_DOC
    LEFT JOIN $nombreTablaINVE i ON i.CVE_ART = p.CVE_ART
    WHERE LTRIM(RTRIM(clib.CAMPLIB3)) = 'V'
";

// Aplicar filtro por vendedor si NO es administrador
$paramsVendedorTop = [];
$paramsClienteTop = [];
$paramsProductoTop = [];
if (!$esAdministrador && $claveVendedorFormateada) {
    $sqlVendedorTop .= " AND f.CVE_VEND = ?";
    $sqlClienteTop .= " AND f.CVE_VEND = ?";
    $sqlProductoTop .= " AND f.CVE_VEND = ?";
    $paramsVendedorTop[] = $claveVendedorFormateada;
    $paramsClienteTop[] = $claveVendedorFormateada;
    $paramsProductoTop[] = $claveVendedorFormateada;
}

// Aplicar filtro de fecha si se proporcionó
if ($fechaInicio && $fechaFin) {
    $sqlVendedorTop .= " AND CAST(f.FECHAELAB AS DATE) >= ? AND CAST(f.FECHAELAB AS DATE) <= ?";
    $sqlClienteTop .= " AND CAST(f.FECHAELAB AS DATE) >= ? AND CAST(f.FECHAELAB AS DATE) <= ?";
    $sqlProductoTop .= " AND CAST(f.FECHAELAB AS DATE) >= ? AND CAST(f.FECHAELAB AS DATE) <= ?";
    $paramsVendedorTop[] = $fechaInicio;
    $paramsVendedorTop[] = $fechaFin;
    $paramsClienteTop[] = $fechaInicio;
    $paramsClienteTop[] = $fechaFin;
    $paramsProductoTop[] = $fechaInicio;
    $paramsProductoTop[] = $fechaFin;
}

$sqlVendedorTop .= " GROUP BY v.CVE_VEND, v.NOMBRE ORDER BY totalVentas DESC, totalImporte DESC";
$sqlClienteTop .= " GROUP BY c.CLAVE, c.NOMBRE ORDER BY totalCompras DESC, totalImporte DESC";
$sqlProductoTop .= " GROUP BY i.CVE_ART, i.DESCR ORDER BY totalCantidad DESC";

// Ejecutar consulta de vendedor top
$vendedorTop = ['nombre' => '-', 'ventas' => 0];
$stmtVendedorTop = sqlsrv_query($conn, $sqlVendedorTop, $paramsVendedorTop);
if ($stmtVendedorTop) {
    $row = sqlsrv_fetch_array($stmtVendedorTop, SQLSRV_FETCH_ASSOC);
    if ($row && $row['NOMBRE']) {
        $vendedorTop = [
            'nombre' => trim($row['NOMBRE']),
            'ventas' => (int)$row['totalVentas']
        ];
    }
    sqlsrv_free_stmt($stmtVendedorTop);
}

// Ejecutar consulta de cliente top
$clienteTop = ['nombre' => '-', 'compras' => 0];
$stmtClienteTop = sqlsrv_query($conn, $sqlClienteTop, $paramsClienteTop);
if ($stmtClienteTop) {
    $row = sqlsrv_fetch_array($stmtClienteTop, SQLSRV_FETCH_ASSOC);
    if ($row && $row['NOMBRE']) {
        $clienteTop = [
            'nombre' => trim($row['NOMBRE']),
            'compras' => (int)$row['totalCompras']
        ];
    }
    sqlsrv_free_stmt($stmtClienteTop);
}

// Ejecutar consulta de producto top
$productoTop = ['nombre' => '-', 'cantidad' => 0];
$stmtProductoTop = sqlsrv_query($conn, $sqlProductoTop, $paramsProductoTop);
if ($stmtProductoTop) {
    $row = sqlsrv_fetch_array($stmtProductoTop, SQLSRV_FETCH_ASSOC);
    if ($row && $row['DESCR']) {
        $productoTop = [
            'nombre' => trim($row['DESCR']),
            'cantidad' => (float)$row['totalCantidad']
        ];
    }
    sqlsrv_free_stmt($stmtProductoTop);
}

sqlsrv_close($conn);

// Preparar respuesta con información de debug (solo en desarrollo)
$response = [
    'success' => true,
    'data' => [
        'activas' => $activas,
        'vendidas' => $vendidas,
        'totalDineroVendido' => $vendidas['totalImporte'],
        'vendedorTop' => $vendedorTop,
        'clienteTop' => $clienteTop,
        'productoTop' => $productoTop
    ]
];

// Agregar información de debug si hay errores o si está en modo debug
if (isset($_GET['debug']) || $activas['totalPedidos'] == 0 && $vendidas['totalPedidos'] == 0) {
    $response['debug'] = [
        'tipoUsuario' => $tipoUsuario,
        'esAdministrador' => $esAdministrador,
        'claveVendedor' => $claveVendedor,
        'claveVendedorFormateada' => $claveVendedorFormateada,
        'claveSae' => $claveSae,
        'noEmpresa' => $noEmpresa,
        'tablaFACTP' => $nombreTabla2,
        'tablaFACTP_CLIB' => $nombreTablaCLIB,
        'sqlActivas' => $sqlActivas,
        'sqlVendidas' => $sqlVendidas,
        'paramsActivas' => $paramsActivas,
        'paramsVendidas' => $paramsVendidas
    ];
}

echo json_encode($response);
?>

