<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'firebase.php';
require_once '../PHPMailer/clsMail.php';
require_once 'reportesGeneral.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

$sqlCliente = "SELECT NOMBRE, EMAILPRED, TELEFONO FROM $tablaClientes WHERE CLAVE = ?";
$paramsCliente = [$filtroCliente];
$stmtCliente = sqlsrv_query($conn, $sqlCliente, $paramsCliente);

if ($stmtCliente === false) {
    sqlsrv_close($conn);
    echo json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]);
    exit;
}

$clienteData = sqlsrv_fetch_array($stmtCliente, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmtCliente);
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
        $tituloReporte = 'Estado de Cuenta General';
        break;
    case 'detallado':
        $reportes = obtenerDatosEstadoCuentaDetalle($conexionData, $fechaInicio, $fechaFin, $filtroCliente);
        $tituloReporte = 'Estado de Cuenta Detallado';
        break;
    case 'cobranza':
        $reportes = obtenerDatosCobranza($conexionData, $fechaInicio, $fechaFin, $filtroCliente);
        $tituloReporte = 'Cobranza';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Tipo de reporte no válido']);
        exit;
}

if (empty($reportes)) {
    echo json_encode(['success' => false, 'message' => 'No se encontraron datos para el reporte']);
    exit;
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
function generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('MDConnecta');
    $pdf->SetAuthor('MDConnecta');
    $pdf->SetTitle($tituloReporte);
    $pdf->SetSubject($tituloReporte);
    $pdf->SetMargins(10, 20, 10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);

    // Encabezado
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $tituloReporte, 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica', '', 10);
    $htmlHeader = "<table cellspacing='0' cellpadding='2'>";
    $htmlHeader .= "<tr><td><b>Cliente:</b> " . htmlspecialchars($clienteNombre) . "</td></tr>";
    if (!empty($fechaInicio) || !empty($fechaFin)) {
        $htmlHeader .= "<tr><td><b>Rango de fechas:</b> " . 
            (!empty($fechaInicio) ? htmlspecialchars($fechaInicio) : 'Desde inicio') . 
            " - " . 
            (!empty($fechaFin) ? htmlspecialchars($fechaFin) : 'Hasta hoy') . 
            "</td></tr>";
    }
    $htmlHeader .= "<tr><td><b>Fecha de generación:</b> " . date('Y-m-d H:i:s') . "</td></tr>";
    $htmlHeader .= "</table>";
    $pdf->writeHTML($htmlHeader, true, false, false, false, '');
    $pdf->Ln(5);

    // Tabla de datos
    if ($tipo === 'cobranza') {
        $html = '<table border="1" cellpadding="4" style="font-size:8px;">';
        $html .= '<thead><tr style="background-color:#4B0082; color:#fff; font-weight:bold; text-align:center;">';
        $html .= '<th>Clave</th><th>Nombre</th><th>Teléfono</th><th>Tipo</th><th>Concepto</th>';
        $html .= '<th>Documento</th><th>Núm.</th><th>F. Aplicación</th><th>F. Vencimiento</th>';
        $html .= '<th style="text-align:right;">Cargos</th><th style="text-align:right;">Abonos</th>';
        $html .= '<th style="text-align:right;">Saldo</th><th>Moneda</th>';
        $html .= '</tr></thead><tbody>';
    } else if ($tipo === 'detallado') {
        $html = '<table border="1" cellpadding="4" style="font-size:8px;">';
        $html .= '<thead><tr style="background-color:#4B0082; color:#fff; font-weight:bold; text-align:center;">';
        $html .= '<th>Clave</th><th>Tipo</th><th>Concepto</th><th>Documento</th><th>Núm.</th>';
        $html .= '<th>F. Aplicación</th><th>F. Vencimiento</th>';
        $html .= '<th style="text-align:right;">Cargo</th><th style="text-align:right;">Abono</th>';
        $html .= '<th style="text-align:right;">Saldo</th>';
        $html .= '</tr></thead><tbody>';
    } else {
        $html = '<table border="1" cellpadding="4" style="font-size:8px;">';
        $html .= '<thead><tr style="background-color:#4B0082; color:#fff; font-weight:bold; text-align:center;">';
        $html .= '<th>Clave</th><th>Tipo</th><th>Concepto</th><th>Documento</th><th>Núm.</th>';
        $html .= '<th>F. Aplicación</th><th>F. Vencimiento</th>';
        $html .= '<th style="text-align:right;">Cargos</th><th style="text-align:right;">Abonos</th>';
        $html .= '<th style="text-align:right;">Saldo</th>';
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
            $html .= '<td>' . htmlspecialchars($reporte['CLAVE'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['NOMBRE_CLIENTE'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['TELEFONO_CLIENTE'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['TIPO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['CONCEPTO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['DOCUMENTO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['NUM'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['FECHA_APLICACION'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['FECHA_VENCIMIENTO'] ?? '') . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($cargos, 2) . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($abonos, 2) . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($saldo, 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['MONEDA'] ?? '') . '</td>';
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
            $html .= '<td>' . htmlspecialchars($reporte['CLAVE'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['TIPO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['CONCEPTO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['DOCUMENTO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['NUM'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['FECHA_APLICACION'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['FECHA_VENCIMIENTO'] ?? '') . '</td>';
            $html .= '<td style="text-align:right;">' . ($cargo !== '' ? '$' . number_format($cargo, 2) : '') . '</td>';
            $html .= '<td style="text-align:right;">' . ($abono !== '' ? '$' . number_format($abono, 2) : '') . '</td>';
            $html .= '<td style="text-align:right;">' . ($saldo !== '' ? '$' . number_format($saldo, 2) : '') . '</td>';
            $html .= '</tr>';
        } else {
            $cargos = $reporte['CARGOS'] ?? 0;
            $abonos = $reporte['ABONOS'] ?? 0;
            $saldo = $reporte['SALDO'] ?? 0;
            $totalCargos += $cargos;
            $totalAbonos += $abonos;
            $totalSaldo += $saldo;

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($reporte['CLAVE'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['TIPO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['CONCEPTO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['DOCUMENTO'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['NUM'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['FECHA_APLICACION'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($reporte['FECHA_VENCIMIENTO'] ?? '') . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($cargos, 2) . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($abonos, 2) . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($saldo, 2) . '</td>';
            $html .= '</tr>';
        }
    }

    // Totales
    if ($tipo === 'cobranza') {
        $html .= '<tr style="font-weight:bold; background-color:#dcdcdc;">';
        $html .= '<td colspan="9" style="text-align:right;"><b>TOTALES:</b></td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalCargos, 2) . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalAbonos, 2) . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalSaldo, 2) . '</td>';
        $html .= '<td></td>';
        $html .= '</tr>';
    } else if ($tipo === 'detallado') {
        $html .= '<tr style="font-weight:bold; background-color:#dcdcdc;">';
        $html .= '<td colspan="7" style="text-align:right;"><b>TOTALES:</b></td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalCargos, 2) . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalAbonos, 2) . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalSaldo, 2) . '</td>';
        $html .= '</tr>';
    } else {
        $html .= '<tr style="font-weight:bold; background-color:#dcdcdc;">';
        $html .= '<td colspan="7" style="text-align:right;"><b>TOTALES:</b></td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalCargos, 2) . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalAbonos, 2) . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($totalSaldo, 2) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    return $pdf;
}

// Procesar según la acción
if ($accion === 'descargar') {
    $pdf = generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo);
    
    ob_end_clean();
    header('Content-Type: application/pdf');
    $nombreArchivo = $tituloReporte . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $cliente) . '_' . date('YmdHis') . '.pdf';
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    $pdf->Output($nombreArchivo, 'D');
    exit;
} else if ($accion === 'whatsapp') {
    // Generar PDF y guardarlo
    $pdf = generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo);
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
    $pdf = generarPDF($reportes, $tituloReporte, $clienteNombre, $fechaInicio, $fechaFin, $tipo);
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

