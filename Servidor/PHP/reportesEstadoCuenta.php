<?php
// Cargar TCPDF ANTES que cualquier otro archivo para evitar conflictos
// Intentar cargar TCPDF desde Composer primero
if (file_exists(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
} 
// Si no está en Composer, usar la versión local existente en el proyecto
/*elseif (file_exists(__DIR__ . '/../XML/sdk2/lib/modulos/html2pdf_fe_ticket/tcpdf_min/tcpdf.php')) {
    require_once __DIR__ . '/../XML/sdk2/lib/modulos/html2pdf_fe_ticket/tcpdf_min/tcpdf.php';
}*/

// Cargar autoload después de TCPDF
require_once __DIR__ . '/../../vendor/autoload.php';

// Verificar que TCPDF esté disponible
if (!class_exists('TCPDF')) {
    die('Error: TCPDF no está disponible. Por favor, ejecute: composer install');
}

require_once 'firebase.php';
require_once '../PHPMailer/clsMail.php';
require_once 'reportesGeneral.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir constantes de TCPDF si no están definidas
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P'); // Portrait
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'LETTER');
}

// Obtener parámetros
$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';
$cliente = $_GET['cliente'] ?? $_POST['cliente'] ?? '';
$fechaInicio = $_GET['fechaInicio'] ?? $_POST['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? $_POST['fechaFin'] ?? '';
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

if (empty($tipo) || empty($cliente)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    exit;
}

// Obtener conexión
if (!isset($_SESSION['empresa']['noEmpresa'])) {
    echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
    exit;
}

$noEmpresa = $_SESSION['empresa']['noEmpresa'];
$claveSae = $_SESSION['empresa']['claveSae'];
$conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);

if (!$conexionResult['success']) {
    echo json_encode($conexionResult);
    exit;
}

$conexionData = $conexionResult['data'];
$filtroCliente = formatearClaveCliente($cliente);

// Obtener datos del cliente
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
    echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
    exit;
}

$prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
$tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
$tablaClieClib = "[{$conexionData['nombreBase']}].[dbo].[CLIE_CLIB$prefijo]";

$sqlCliente = "SELECT NOMBRE, EMAILPRED, TELEFONO, SALDO, LIMCRED FROM $tablaClientes WHERE CLAVE = ?";
$paramsCliente = [$filtroCliente];
$stmtCliente = sqlsrv_query($conn, $sqlCliente, $paramsCliente);

if ($stmtCliente === false) {
    sqlsrv_close($conn);
    echo json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]);
    exit;
}

$clienteData = sqlsrv_fetch_array($stmtCliente, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmtCliente);

$limiteCreditoCliente = isset($clienteData['LIMCRED']) ? (float)$clienteData['LIMCRED'] : 0.0;
$saldoCliente = isset($clienteData['SALDO']) ? (float)$clienteData['SALDO'] : 0.0;

$cuentaSTP = '';
if ($clienteData) {
    $sqlCuentaSTP = "SELECT CAMPLIB10 FROM $tablaClieClib WHERE CVE_CLIE = ?";
    $paramsCuentaSTP = [$filtroCliente];
    $stmtCuentaSTP = sqlsrv_query($conn, $sqlCuentaSTP, $paramsCuentaSTP);
    if ($stmtCuentaSTP !== false) {
        $cuentaSTPData = sqlsrv_fetch_array($stmtCuentaSTP, SQLSRV_FETCH_ASSOC);
        $cuentaSTP = trim($cuentaSTPData['CAMPLIB10'] ?? '');
        sqlsrv_free_stmt($stmtCuentaSTP);
    }
}

sqlsrv_close($conn);

if (!$clienteData) {
    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    exit;
}

$clienteNombre = trim($clienteData['NOMBRE'] ?? 'Cliente');
$emailCliente = trim($clienteData['EMAILPRED'] ?? '');
$telefonoCliente = trim($clienteData['TELEFONO'] ?? '');

// Si hay múltiples correos separados por `;`, tomar solo el primero
if (!empty($emailCliente)) {
    $emailArray = explode(';', $emailCliente);
    $emailCliente = trim($emailArray[0]);
}

// Obtener datos del reporte
$reportes = [];
$tituloReporte = '';

switch ($tipo) {
    case 'general':
        $reportes = obtenerDatosEstadoCuentaGeneral($conexionData, $fechaInicio, $fechaFin, $filtroCliente);
        $tituloReporte = 'Estado de Cuenta';
        break;
    case 'detallado':
        $reportes = obtenerDatosEstadoCuentaDetalle($conexionData, $fechaInicio, $fechaFin, $filtroCliente);
        $tituloReporte = 'Estado de Cuenta';
        break;
    case 'cobranza':
        $reportes = obtenerDatosCobranza($conexionData, $fechaInicio, $fechaFin, $filtroCliente);
        $tituloReporte = 'Estado de Cuenta';
        break;
    case 'facturasnopagadas':
        $reportes = obtenerDatosFacturasNoPagadas($conexionData, $filtroCliente);
        $tituloReporte = 'Estado de Cuenta';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Tipo de reporte no válido']);
        exit;
}

// Si no hay datos y la acción es descargar, generar PDF vacío
// Para otras acciones (whatsapp, correo), devolver error si no hay datos
if (empty($reportes)) {
    if ($accion === 'descargar') {
        // Generar PDF vacío con mensaje
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('MDConnecta');
        $pdf->SetAuthor('MDConnecta');
        $pdf->SetTitle($tituloReporte);
        $pdf->SetSubject($tituloReporte);
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $tituloReporte, 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Cliente: ' . htmlspecialchars($clienteNombre), 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'No se encontraron datos para el reporte.', 0, 1, 'C');
        
        ob_end_clean();
        header('Content-Type: application/pdf');
        $nombreArchivo = $tituloReporte . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $cliente) . '_' . date('YmdHis') . '.pdf';
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        $pdf->Output($nombreArchivo, 'D');
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron datos para el reporte']);
        exit;
    }
}

// Función para obtener datos del estado de cuenta general
function obtenerDatosEstadoCuentaGeneral($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente) {
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
            return [];
        }

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaCuenM    = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M$prefijo]";
        $tablaCuenDet  = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET$prefijo]";
        $tablaConcepto = "[{$conexionData['nombreBase']}].[dbo].[CONC$prefijo]";

        $where = "WHERE CUENM.FECHA_VENC < CAST(GETDATE() AS DATE) AND C.STATUS <> 'B'";
        $params = [];

        if (!empty($filtroCliente)) {
            $where .= " AND C.CLAVE = ?";
            $params[] = $filtroCliente;
        }

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
            sqlsrv_close($conn);
            return [];
        }

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
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
            $reportes[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $reportes;
    } catch (Exception $e) {
        return [];
    }
}

// Función para obtener datos del estado de cuenta detallado
function obtenerDatosEstadoCuentaDetalle($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente) {
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
            return [];
        }

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaCuenM    = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M$prefijo]";
        $tablaCuenDet  = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET$prefijo]";
        $tablaConcepto = "[{$conexionData['nombreBase']}].[dbo].[CONC$prefijo]";

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

        $sql = "$sqlCargos UNION ALL $sqlAbonos ORDER BY DOCUMENTO, FECHA_APLICACION, TIPO_REGISTRO";
        $allParams = array_merge($paramsCargos, $paramsAbonos);

        $stmt = sqlsrv_query($conn, $sql, $allParams);
        if ($stmt === false) {
            sqlsrv_close($conn);
            return [];
        }

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

        // Agrupar y calcular saldos
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
        return $resultado;
    } catch (Exception $e) {
        return [];
    }
}

// Función para obtener datos de facturas no pagadas
function obtenerDatosFacturasNoPagadas($conexionData, $filtroCliente) {
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
            return [];
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
            sqlsrv_close($conn);
            return [];
        }

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
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

        return $reportes;
    } catch (Exception $e) {
        return [];
    }
}

// Función para obtener datos de cobranza
function obtenerDatosCobranza($conexionData, $filtroFechaInicio, $filtroFechaFin, $filtroCliente) {
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
            return [];
        }

        $prefijo = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE$prefijo]";
        $tablaCuenM    = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M$prefijo]";
        $tablaCuenDet  = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET$prefijo]";
        $tablaConcepto = "[{$conexionData['nombreBase']}].[dbo].[CONC$prefijo]";
        $tablaMonedas  = "[{$conexionData['nombreBase']}].[dbo].[MONED$prefijo]";

        $where = "WHERE CUENM.STATUS <> 'B'";
        $params = [];

        if (!empty($filtroCliente)) {
            $where .= " AND C.CLAVE = ?";
            $params[] = $filtroCliente;
        }

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
            sqlsrv_close($conn);
            return [];
        }

        $reportes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['SALDO'] < 0.1) {
                continue;
            }
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
        return $reportes;
    } catch (Exception $e) {
        return [];
    }
}

// Función para generar PDF
function generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo, $cuentaSTP, $limiteCreditoCliente, $saldoCliente) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('MDConnecta');
    $pdf->SetAuthor('MDConnecta');
    $pdf->SetTitle($tituloReporte);
    $pdf->SetSubject($tituloReporte);
    $pdf->SetMargins(10, 20, 10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);

    // Encabezado con logotipo e información principal
    $logoPath = realpath(__DIR__ . '/../../Cliente/SRC/logoInterzenda.PNG');
    $margins = $pdf->getMargins();
    $logoX = $margins['left'];
    $logoY = 16;
    $logoWidth = 42;
    $logoBottomY = $logoY;

    if ($logoPath && file_exists($logoPath)) {
        $logoPath = str_replace('\\', '/', $logoPath);
        $pdf->Image($logoPath, $logoX, $logoY, $logoWidth, 0, '', '', '', false, 300);
        $logoBottomY = $pdf->GetY();
    }

    $logoDisponible = $logoPath && file_exists($logoPath);
    $usableWidth = $pdf->getPageWidth() - $margins['left'] - $margins['right'];

    // Replicar desplazamiento del bloque de crédito dentro de la tarjeta (50% columna izquierda + 2% de separación + padding lateral)
    $cardPaddingX = 14 * 0.352777778; // 14px en mm aprox. (ajustado para PDF)
    $innerCardWidth = max($usableWidth - ($cardPaddingX * 2), 10);
    $creditColumnStart = $margins['left'] + $cardPaddingX + ($innerCardWidth * 0.52);
    $creditRightEdge = $margins['left'] + $usableWidth - $cardPaddingX;
    $headerStartX = $logoDisponible ? max($logoX + $logoWidth + 8, $creditColumnStart) : $creditColumnStart;
    $headerWidth = max($creditRightEdge - $headerStartX, 60);

    $pdf->SetXY($headerStartX, $logoY);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(63, 43, 140);
    $pdf->Cell($headerWidth, 9, htmlspecialchars($tituloReporte), 0, 2, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(119, 119, 119);
    $pdf->Cell($headerWidth, 5, 'Fecha de generación: ' . date('Y-m-d H:i:s'), 0, 2, 'L');

    $pdf->SetTextColor(0, 0, 0);

    $limiteCreditoTexto = '$' . number_format($limiteCreditoCliente, 2);
    $saldoClienteTexto = '$' . number_format($saldoCliente, 2);
    $cuentaSTPTexto = $cuentaSTP !== '' ? htmlspecialchars($cuentaSTP) : 'No registrada';
    $headerBottomY = max($logoY + ($logoWidth * 0.5), $pdf->GetY());
    $pdf->SetY($headerBottomY + 12);

    $resumenHtml = "
        <table cellspacing='0' cellpadding='0' style='width:100%; border-collapse:collapse; font-size:9px;'>
            <tr>
                <td style='background-color:#f4f1ff; border-radius:8px; border:0.6px solid #ded8ff; padding:10px 14px;'>
                    <table cellspacing='0' cellpadding='0' style='width:100%; font-size:9px; border-collapse:collapse;'>
                        <tr>
                            <td style='width:50%; vertical-align:top;'>
                                <table cellspacing='0' cellpadding='0' style='width:100%; font-size:9px;'>
                                    <tr>
                                        <td style='font-weight:bold; color:#3f2b8c;'>Cliente</td>
                                    </tr>
                                    <tr>
                                        <td style='padding:4px 0 10px 0; color:#333;'>" . htmlspecialchars($clienteNombre) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='font-weight:bold; color:#3f2b8c;'>Cuenta STP</td>
                                    </tr>
                                    <tr>
                                        <td style='padding-top:4px; color:#333;'>$cuentaSTPTexto</td>
                                    </tr>
                                </table>
                            </td>
                            <td style='width:2%;'></td>
                            <td style='width:48%; vertical-align:top; text-align:right;'>
                                <table cellspacing='0' cellpadding='0' style='width:100%; font-size:9px;'>
                                    <tr>
                                        <td style='font-weight:bold; color:#3f2b8c; text-align:right;'>Límite de crédito</td>
                                    </tr>
                                    <tr>
                                        <td style='padding:4px 0 10px 0; color:#333; text-align:right;'>$limiteCreditoTexto</td>
                                    </tr>
                                    <tr>
                                        <td style='font-weight:bold; color:#3f2b8c; text-align:right;'>Saldo del cliente</td>
                                    </tr>
                                    <tr>
                                        <td style='padding-top:4px; color:#333; text-align:right;'>$saldoClienteTexto</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    ";
    $pdf->writeHTML($resumenHtml, false, false, false, false, '');
    $pdf->Ln(6);

    // Tabla de datos estilizada
    $tableStart = '<table cellpadding="6" cellspacing="0" style="width:100%; font-size:8px; border-collapse:collapse; border-spacing:0;">';
    $headerRowStyle = "background-color:#3f2b8c; color:#ffffff; text-transform:uppercase; letter-spacing:0.4px;";
    $headerCellStyle = "padding:7px 6px; text-align:center; border:none;";
    $bodyCellStyle = "padding:6px 5px; border:none; border-bottom:0.35px solid #ecebf7; color:#333333;";
    $bodyCellRightStyle = "padding:6px 5px; border:none; border-bottom:0.35px solid #ecebf7; text-align:right; color:#333333;";
    $totalRowBaseStyle = "padding:9px 6px; background-color:#ece7ff; border:none; border-top:0.6px solid #c6bffb;";
    $totalLabelStyle = $totalRowBaseStyle . " text-align:right; font-weight:bold; color:#3f2b8c;";
    $totalValueStyle = $totalRowBaseStyle . " text-align:right; font-weight:bold; color:#3f2b8c;";
    $totalEmptyStyle = $totalRowBaseStyle;

    if ($tipo === 'cobranza') {
        $html = $tableStart;
        $html .= '<thead><tr style="' . $headerRowStyle . '">';
        $html .= '<th style="' . $headerCellStyle . '">Clave</th><th style="' . $headerCellStyle . '">Nombre</th><th style="' . $headerCellStyle . '">Teléfono</th><th style="' . $headerCellStyle . '">Tipo</th><th style="' . $headerCellStyle . '">Concepto</th>';
        $html .= '<th style="' . $headerCellStyle . '">Documento</th><th style="' . $headerCellStyle . '">Núm.</th><th style="' . $headerCellStyle . '">F. Aplicación</th><th style="' . $headerCellStyle . '">F. Vencimiento</th>';
        $html .= '<th style="' . $headerCellStyle . '">Cargos</th><th style="' . $headerCellStyle . '">Abonos</th>';
        $html .= '<th style="' . $headerCellStyle . '">Saldo</th><th style="' . $headerCellStyle . '">Moneda</th>';
        $html .= '</tr></thead><tbody>';
    } else if ($tipo === 'detallado') {
        $html = $tableStart;
        $html .= '<thead><tr style="' . $headerRowStyle . '">';
        $html .= '<th style="' . $headerCellStyle . '">Clave</th><th style="' . $headerCellStyle . '">Tipo</th><th style="' . $headerCellStyle . '">Concepto</th><th style="' . $headerCellStyle . '">Documento</th><th style="' . $headerCellStyle . '">Núm.</th>';
        $html .= '<th style="' . $headerCellStyle . '">F. Aplicación</th><th style="' . $headerCellStyle . '">F. Vencimiento</th>';
        $html .= '<th style="' . $headerCellStyle . '">Cargo</th><th style="' . $headerCellStyle . '">Abono</th>';
        $html .= '<th style="' . $headerCellStyle . '">Saldo</th>';
        $html .= '</tr></thead><tbody>';
    } else if ($tipo === 'facturasnopagadas') {
        $html = $tableStart;
        $html .= '<thead><tr style="' . $headerRowStyle . '">';
        $html .= '<th style="' . $headerCellStyle . '">Factura</th>';
        $html .= '<th style="' . $headerCellStyle . '">F. Aplicación</th><th style="' . $headerCellStyle . '">F. Vencimiento</th>';
        $html .= '<th style="' . $headerCellStyle . '">Cargos</th><th style="' . $headerCellStyle . '">Abonos</th>';
        $html .= '<th style="' . $headerCellStyle . '">Saldos</th><th style="' . $headerCellStyle . '">Moneda</th><th style="' . $headerCellStyle . '">Estado</th>';
        $html .= '</tr></thead><tbody>';
    } else {
        $html = $tableStart;
        $html .= '<thead><tr style="' . $headerRowStyle . '">';
        $html .= '<th style="' . $headerCellStyle . '">Clave</th><th style="' . $headerCellStyle . '">Tipo</th><th style="' . $headerCellStyle . '">Concepto</th><th style="' . $headerCellStyle . '">Documento</th><th style="' . $headerCellStyle . '">Núm.</th>';
        $html .= '<th style="' . $headerCellStyle . '">F. Aplicación</th><th style="' . $headerCellStyle . '">F. Vencimiento</th>';
        $html .= '<th style="' . $headerCellStyle . '">Cargos</th><th style="' . $headerCellStyle . '">Abonos</th>';
        $html .= '<th style="' . $headerCellStyle . '">Saldo</th>';
        $html .= '</tr></thead><tbody>';
    }

    $totalCargos = 0;
    $totalAbonos = 0;
    $totalSaldo = 0;

    foreach ($reportes as $reporte) {
        if ($tipo === 'cobranza') {
            $cargos = $reporte['CARGOS'] ?? 0;
            $abonos = $reporte['ABONOS'] ?? 0;
            $saldo = $reporte['SALDO'] ?? 0;
            $totalCargos += $cargos;
            $totalAbonos += $abonos;
            $totalSaldo += $saldo;

            $html .= '<tr>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['CLAVE'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['NOMBRE_CLIENTE'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['TELEFONO_CLIENTE'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['TIPO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['CONCEPTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['DOCUMENTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['NUM'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_APLICACION'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_VENCIMIENTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($cargos, 2) . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($abonos, 2) . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($saldo, 2) . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['MONEDA'] ?? '') . '</td>';
            $html .= '</tr>';
        } else if ($tipo === 'detallado') {
            $cargo = $reporte['CARGO'] ?? '';
            $abono = $reporte['ABONO'] ?? '';
            $saldo = $reporte['SALDO'] ?? '';
            
            if ($cargo !== '') {
                $totalCargos += $cargo;
            }
            if ($abono !== '') {
                $totalAbonos += $abono;
            }
            if ($saldo !== '') {
                $totalSaldo = $saldo; // El saldo se acumula
            }

            $html .= '<tr>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['CLAVE'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['TIPO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['CONCEPTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['DOCUMENTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['NUM'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_APLICACION'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_VENCIMIENTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">' . ($cargo !== '' ? '$' . number_format($cargo, 2) : '') . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">' . ($abono !== '' ? '$' . number_format($abono, 2) : '') . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">' . ($saldo !== '' ? '$' . number_format($saldo, 2) : '') . '</td>';
            $html .= '</tr>';
        } else if ($tipo === 'facturasnopagadas') {
            $montoOriginal = $reporte['MONTO_ORIGINAL'] ?? 0;
            $montoPagado = $reporte['MONTO_PAGADO'] ?? 0;
            $saldoRestante = $reporte['SALDO_RESTANTE'] ?? 0;
            $totalCargos += $montoOriginal;
            $totalAbonos += $montoPagado;
            $totalSaldo += $saldoRestante;
            
            $estadoColor = '';
            if (($reporte['ESTADO_CUENTA'] ?? '') === 'VENCIDA') {
                $estadoColor = 'color:#d32f2f; font-weight:bold;';
            } else if (($reporte['ESTADO_CUENTA'] ?? '') === 'PENDIENTE') {
                $estadoColor = 'color:#f57c00; font-weight:bold;';
            }

            $html .= '<tr>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FACTURA'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_APLICACION'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_VENCIMIENTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($montoOriginal, 2) . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($montoPagado, 2) . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($saldoRestante, 2) . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['MONEDA'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . ' ' . $estadoColor . '">' . htmlspecialchars($reporte['ESTADO_CUENTA'] ?? '') . '</td>';
            $html .= '</tr>';
        } else {
            $cargos = $reporte['CARGOS'] ?? 0;
            $abonos = $reporte['ABONOS'] ?? 0;
            $saldo = $reporte['SALDO'] ?? 0;
            $totalCargos += $cargos;
            $totalAbonos += $abonos;
            $totalSaldo += $saldo;

            $html .= '<tr>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['CLAVE'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['TIPO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['CONCEPTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['DOCUMENTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['NUM'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_APLICACION'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellStyle . '">' . htmlspecialchars($reporte['FECHA_VENCIMIENTO'] ?? '') . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($cargos, 2) . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($abonos, 2) . '</td>';
            $html .= '<td style="' . $bodyCellRightStyle . '">$' . number_format($saldo, 2) . '</td>';
            $html .= '</tr>';
        }
    }

    // Totales
    if ($tipo === 'cobranza') {
        $html .= '<tr>';
        $html .= '<td colspan="9" style="' . $totalLabelStyle . '">TOTALES</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalCargos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalAbonos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalSaldo, 2) . '</td>';
        $html .= '<td style="' . $totalEmptyStyle . '"></td>';
        $html .= '</tr>';
    } else if ($tipo === 'detallado') {
        $html .= '<tr>';
        $html .= '<td colspan="7" style="' . $totalLabelStyle . '">TOTALES</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalCargos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalAbonos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalSaldo, 2) . '</td>';
        $html .= '</tr>';
    } else if ($tipo === 'facturasnopagadas') {
        $html .= '<tr>';
        $html .= '<td colspan="3" style="' . $totalLabelStyle . '">TOTALES</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalCargos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalAbonos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalSaldo, 2) . '</td>';
        $html .= '<td style="' . $totalEmptyStyle . '"></td><td style="' . $totalEmptyStyle . '"></td>';
        $html .= '</tr>';
    } else {
        $html .= '<tr>';
        $html .= '<td colspan="7" style="' . $totalLabelStyle . '">TOTALES</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalCargos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalAbonos, 2) . '</td>';
        $html .= '<td style="' . $totalValueStyle . '">$' . number_format($totalSaldo, 2) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    return $pdf;
}

// Procesar según la acción
if ($accion === 'descargar') {
    $pdf = generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo, $cuentaSTP, $limiteCreditoCliente, $saldoCliente);
    
    ob_end_clean();
    header('Content-Type: application/pdf');
    $nombreArchivo = $tituloReporte . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $cliente) . '_' . date('YmdHis') . '.pdf';
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    $pdf->Output($nombreArchivo, 'D');
    exit;
} else if ($accion === 'whatsapp') {
    // Generar PDF y guardarlo
    $pdf = generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo, $cuentaSTP, $limiteCreditoCliente, $saldoCliente);
    $nombreArchivo = $tituloReporte . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $cliente) . '_' . date('YmdHis') . '.pdf';
    $rutaPDF = __DIR__ . '/pdfs/' . $nombreArchivo;
    
    // Asegurar que el directorio existe
    if (!file_exists(__DIR__ . '/pdfs/')) {
        mkdir(__DIR__ . '/pdfs/', 0777, true);
    }
    
    $pdf->Output($rutaPDF, 'F');
    
    // URL pública del PDF
    $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/" . $nombreArchivo;
    
    if (empty($telefonoCliente) || !preg_match('/^\d{10,15}$/', $telefonoCliente)) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene un número de teléfono válido']);
        exit;
    }
    
    // Enviar por WhatsApp
    $resultado = enviarWhatsAppReporte($telefonoCliente, $clienteNombre, $tituloReporte, $rutaPDFW, $nombreArchivo);
    
    if (str_contains($resultado, "error") || str_contains($resultado, "Error")) {
        echo json_encode(['success' => false, 'message' => 'Error al enviar por WhatsApp: ' . $resultado]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Reporte enviado por WhatsApp correctamente']);
    }
    exit;
} else if ($accion === 'correo') {
    // Generar PDF y guardarlo
    $pdf = generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo, $cuentaSTP, $limiteCreditoCliente, $saldoCliente);
    $nombreArchivo = $tituloReporte . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $cliente) . '_' . date('YmdHis') . '.pdf';
    $rutaPDF = __DIR__ . '/pdfs/' . $nombreArchivo;
    
    // Asegurar que el directorio existe
    if (!file_exists(__DIR__ . '/pdfs/')) {
        mkdir(__DIR__ . '/pdfs/', 0777, true);
    }
    
    $pdf->Output($rutaPDF, 'F');
    
    if (empty($emailCliente) || !filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene un correo electrónico válido']);
        exit;
    }
    
    // Enviar por correo
    $resultado = enviarCorreoReporte($emailCliente, $clienteNombre, $tituloReporte, $rutaPDF, $fechaInicio, $fechaFin);
    
    if ($resultado === "Correo enviado exitosamente.") {
        echo json_encode(['success' => true, 'message' => 'Reporte enviado por correo correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al enviar por correo: ' . $resultado]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

// Función para enviar WhatsApp
function enviarWhatsAppReporte($numeroWhatsApp, $clienteNombre, $tituloReporte, $rutaPDFW, $filename) {
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "template",
        "template" => [
            "name" => "reporte_estado_cuenta",
            "language" => ["code" => "es_MX"],
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
                        ["type" => "text", "text" => $clienteNombre],
                        ["type" => "text", "text" => $tituloReporte]
                    ]
                ]
            ]
        ]
    ];

    $data_string = json_encode($data);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return "OK";
    } else {
        return "error: " . $response;
    }
}

// Función para enviar correo
function enviarCorreoReporte($correo, $clienteNombre, $tituloReporte, $rutaPDF, $fechaInicio, $fechaFin) {
    $mail = new clsMail();
    
    $correoRemitente = $_SESSION['usuario']['correo'] ?? "";
    $contraseñaRemitente = $_SESSION['empresa']['contrasena'] ?? "";
    
    if ($correoRemitente === "" || $contraseñaRemitente === "") {
        $correoRemitente = "";
        $contraseñaRemitente = "";
    }
    
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'MDConnecta';
    $asunto = $tituloReporte . ' - ' . $clienteNombre;
    
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio le enviamos su <b>$tituloReporte</b>.</p>";
    
    if (!empty($fechaInicio) || !empty($fechaFin)) {
        $bodyHTML .= "<p><b>Rango de fechas:</b> " . 
            (!empty($fechaInicio) ? htmlspecialchars($fechaInicio) : 'Desde inicio') . 
            " - " . 
            (!empty($fechaFin) ? htmlspecialchars($fechaFin) : 'Hasta hoy') . 
            "</p>";
    }
    
    $bodyHTML .= "<p>El documento PDF se encuentra adjunto a este correo.</p>";
    $bodyHTML .= "<p>Saludos cordiales,<br>Su equipo de soporte.</p>";
    
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correo, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente);
    
    return $resultado;
}
?>



