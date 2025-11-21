<?php
session_start();
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

// Estadísticas de ventas activas (todas, sin filtro de fecha)
// Usar LEFT JOIN como en ventas.php y filtrar por CAMPLIB3
$sqlActivas = "
    SELECT 
        COUNT(DISTINCT f.CVE_DOC) AS totalPedidos,
        ISNULL(SUM(f.IMPORTE), 0) AS totalImporte
    FROM $nombreTabla2 f
    LEFT JOIN $nombreTablaCLIB clib ON clib.CLAVE_DOC = f.CVE_DOC
    WHERE LTRIM(RTRIM(clib.CAMPLIB3)) = 'A'
";

// Estadísticas de ventas vendidas (todas, sin filtro de fecha)
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
    $paramsActivas = [$claveVendedorFormateada];
    $paramsVendidas = [$claveVendedorFormateada];
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

sqlsrv_close($conn);

// Preparar respuesta con información de debug (solo en desarrollo)
$response = [
    'success' => true,
    'data' => [
        'activas' => $activas,
        'vendidas' => $vendidas,
        'totalDineroVendido' => $vendidas['totalImporte']
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

