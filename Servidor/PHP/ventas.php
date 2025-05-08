<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
include 'reportes.php';

session_start();

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
                    'nombreBanco' => $fields['nombreBanco']['stringValue'],
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
/*function mostrarPedidos($conexionData, $filtroFecha, $filtroVendedor)
{
    // Recuperar filtros enviados
    $filtroFecha    = $_POST['filtroFecha']    ?? 'Todos';
    $filtroVendedor = $_POST['filtroVendedor'] ?? '';

    // Parámetros de paginación
    $pagina   = isset($_POST['pagina'])   ? (int)$_POST['pagina']   : 1;
    $porPagina= isset($_POST['porPagina'])? (int)$_POST['porPagina']: 50;
    $offset   = ($pagina - 1) * $porPagina;

    try {
        //session_start();
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success'=>false,'message'=>'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa    = $_SESSION['empresa']['noEmpresa'];
        $claveSae     = $_SESSION['empresa']['claveSae'];
        $tipoUsuario  = $_SESSION['usuario']['tipoUsuario'];
        $claveVendedor= $_SESSION['empresa']['claveUsuario'] ?? '';

        // Aseguramos encoding de la clave de vendedor
        if ($claveVendedor !== '') {
            $claveVendedor = mb_convert_encoding(trim($claveVendedor),'UTF-8');
        }

        // Conectar
        $conn = sqlsrv_connect($conexionData['host'], [
            "Database"              => $conexionData['nombreBase'],
            "UID"                   => $conexionData['usuario'],
            "PWD"                   => $conexionData['password'],
            "TrustServerCertificate"=> true
        ]);
        if ($conn === false) {
            die(json_encode(['success'=>false,'message'=>'Error al conectar','errors'=>sqlsrv_errors()]));
        }

        // Tablas dinámicas
        $cliTbl = "[{$conexionData['nombreBase']}].[dbo].CLIE".str_pad($claveSae,2,'0',STR_PAD_LEFT);
        $facTbl = "[{$conexionData['nombreBase']}].[dbo].FACTP".str_pad($claveSae,2,'0',STR_PAD_LEFT);
        $venTbl = "[{$conexionData['nombreBase']}].[dbo].VEND".str_pad($claveSae,2,'0',STR_PAD_LEFT);

        // Base de la consulta
        $sql = "
            SELECT 
              f.TIP_DOC   AS Tipo,
              f.CVE_DOC   AS Clave,
              f.CVE_CLPV  AS Cliente,
              c.NOMBRE    AS Nombre,
              f.STATUS    AS Estatus,
              CONVERT(VARCHAR(10),f.FECHAELAB,105) AS FechaElaboracion,
              f.CAN_TOT   AS Subtotal,
              f.IMPORTE   AS ImporteTotal,
              v.NOMBRE    AS NombreVendedor
            FROM $facTbl f
            LEFT JOIN $cliTbl c ON c.CLAVE    = f.CVE_CLPV
            LEFT JOIN $venTbl v ON v.CVE_VEND = f.CVE_VEND
            WHERE f.STATUS IN ('E','O')
        ";

        $params = [];

        // Filtros de fecha
        if ($filtroFecha === 'Hoy') {
            $sql .= " AND CAST(f.FECHAELAB AS DATE)=CAST(GETDATE() AS DATE)";
        } elseif ($filtroFecha === 'Mes') {
            $sql .= " AND MONTH(f.FECHAELAB)=MONTH(GETDATE()) AND YEAR(f.FECHAELAB)=YEAR(GETDATE())";
        } elseif ($filtroFecha === 'Mes Anterior') {
            $sql .= " AND MONTH(f.FECHAELAB)=MONTH(DATEADD(MONTH,-1,GETDATE())) AND YEAR(f.FECHAELAB)=YEAR(DATEADD(MONTH,-1,GETDATE()))";
        }

        // Filtro por vendedor
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

        // Orden y paginación
        $sql .= " ORDER BY f.FECHAELAB DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        $params[] = $offset;
        $params[] = $porPagina;

        // Ejecutar
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success'=>false,'message'=>'Error al ejecutar la consulta','errors'=>sqlsrv_errors()]));
        }

        // Recorrer resultados
        $clientes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Trim y encoding strings
            foreach ($row as $k => $v) {
                
                if (is_string($v)) {
                    $v = trim($v);
                    $enc = mb_detect_encoding($v, mb_list_encodings(), true);
                    if ($enc && $enc !== 'UTF-8') {
                        $v = mb_convert_encoding($v,'UTF-8',$enc);
                    }
                } elseif ($v === null) {
                    $v = '';
                }
                $row[$k] = $v;
            }
            var_dump($row);
            $clientes[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        if (empty($clientes)) {
            echo json_encode(['success'=>false,'message'=>'No se encontraron pedidos']);
            exit;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success'=>true,'data'=>$clientes]);

    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
}*/
function mostrarPedidos($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor)
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
        $nombreTabla2  = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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
        $countStmt = sqlsrv_query($conn, $countSql, $params);
        $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total     = (int)$totalRow['total'];
        sqlsrv_free_stmt($countStmt);

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        header('Content-Type: application/json; charset=UTF-8');
        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron pedidos']);
            exit;
        }
        echo json_encode(['success' => true, 'total' => $total, 'data' => $clientes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function filtrarPedidosVendidos($clientes) {}
function mostrarPedidoEspecifico($clave, $conexionData, $claveSae)
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

    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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
function obtenerPartidasPedido($conexionData, $clavePedido)
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
function actualizarPedido($conexionData, $formularioData, $partidasData, $estatus)
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

    // Actualizar las partidas asociadas al pedido
    actualizarPartidas($conexionData, $formularioData, $partidasData);


    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Extraer los datos del formulario
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $FECHA_DOC = $formularioData['diaAlta'];
    $FECHA_ENT = $formularioData['entrega'];

    $partidasActualizadas = obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae);

    $CAN_TOT = 0;
    $IMPORTE = 0;
    $DES_TOT = 0; // Variable para el importe con descuento
    $descuentoCliente = $formularioData['descuentoCliente']; // Valor del descuento en porcentaje (ejemplo: 10 para 10%)

    foreach ($partidasActualizadas as $partida) {
        $CAN_TOT += $partida['CANT'] * $partida['PREC']; // Sumar cantidades totales
        $IMPORTE += $partida['CANT'] * $partida['PREC']; // Calcular importe total
    }

    // Aplicar descuento
    $IMPORTT = $IMPORTE;
    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasActualizadas as $partida) {
        $precioUnitario = $partida['PREC'];
        $cantidad = $partida['CANT'];
        $IMPU4 = $partida['IMPU4'];
        $desc1 = $partida['DESC1'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**

        $DES = $totalPartida * ($desc1 / 100);
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTT + $IMP_TOT4 - $DES_TOT;
    $CVE_VEND = str_pad($formularioData['claveVendedor'], 5, ' ', STR_PAD_LEFT);
    $CONDICION = $formularioData['condicion'];
    // Crear la consulta SQL para actualizar el pedido
    $sql = "UPDATE $nombreTabla SET 
        FECHA_DOC = ?, 
        FECHA_ENT = ?, 
        CAN_TOT = ?, 
        IMPORTE = ?, 
        IMP_TOT4 = ?, 
        DES_TOT = ?, 
        CONDICION = ?, 
        CVE_VEND = ?,
        STATUS = ? 
        WHERE CVE_DOC = ?";

    $params = [
        $FECHA_DOC,
        $FECHA_ENT,
        $CAN_TOT,
        $IMPORTE,
        $IMP_TOT4,
        $DES_TOT,
        $CONDICION,
        $CVE_VEND,
        $estatus,
        $CVE_DOC
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el pedido', 'errors' => sqlsrv_errors()]));
    }

    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return ['success' => true, 'message' => 'Pedido actualizado correctamente'];
}
function actualizarPartidas($conexionData, $formularioData, $partidasData)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $clave = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($clave, 20, ' ', STR_PAD_LEFT);
    // Iniciar transacción
    sqlsrv_begin_transaction($conn);

    // **1. Ajustar el inventario antes de modificar las partidas**
    $resultadoInventario = actualizarNuevoInventario($conexionData, $formularioData, $partidasData);
    if (!$resultadoInventario['success']) {
        sqlsrv_rollback($conn);
        die(json_encode($resultadoInventario));
    }
    $query = "SELECT CVE_ART, NUM_PAR FROM $nombreTabla WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener partidas existentes',
            'errors' => sqlsrv_errors()
        ]));
    }
    // 🔥 Depuración: Verificar si la consulta devuelve datos
    $partidasExistentes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $claveNormalizada = trim(strtoupper($row['CVE_ART'])); // Eliminar espacios y convertir en mayúsculas
        $partidasExistentes[$claveNormalizada] = $row['NUM_PAR'];
    }

    sqlsrv_free_stmt($stmt);

    $query = "SELECT MAX(NUM_PAR) AS NUM_PAR FROM $nombreTabla WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    $NUM_PAR = ($row && $row['NUM_PAR']) ? $row['NUM_PAR'] + 1 : 1; // Si no hay partidas, empieza desde 1

    // **3. Actualizar o insertar las partidas**
    foreach ($partidasData as $partida) {
        $CVE_UNIDAD = $partida['CVE_UNIDAD'];
        // Extraer los datos de la partida
        $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
        $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
        $CVE_ART = $partida['producto']; // Clave del producto
        $CANT = $partida['cantidad']; // Cantidad
        $PREC = $partida['precioUnitario']; // Precio
        // Calcular los impuestos y totales
        $IMPU1 = $partida['ieps']; // Impuesto 1
        $IMPU3 = $partida['isr'];
        //$IMPU1 = 0;
        //$IMPU2 = $partida['impuesto2']; // Impuesto 2
        $IMPU2 = 0;
        $IMPU4 = $partida['iva']; // Impuesto 2
        // Agregar los cálculos para los demás impuestos...
        $PXS = 0;
        $DESC1 = $partida['descuento'];
        $DESC2 = 0;
        $COMI = $partida['comision'];
        $NUM_ALMA = $formularioData['almacen'];
        $UNI_VENTA = $partida['unidad'];
        if ($UNI_VENTA === 'No aplica' || $UNI_VENTA === 'SERVICIO' || $UNI_VENTA === 'Servicio') {
            $TIPO_PORD = 'S';
        } else {
            $TIPO_PORD = 'P';
        }
        $TOT_PARTIDA = $PREC * $CANT;
        $TOTIMP1 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU1 / 100);
        $TOTIMP2 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU2 / 100);
        $TOTIMP4 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU4 / 100);
        if (isset($partidasExistentes[$CVE_ART])) {
            $NUM_PAR_EXISTENTE = $partidasExistentes[$CVE_ART];
            // Si la partida ya existe, realizar un UPDATE
            $sql = "UPDATE $nombreTabla SET 
                CANT = ?, PREC = ?, IMPU1 = ?, IMPU4 = ?, DESC1 = ?, DESC2 = ?, 
                TOTIMP1 = ?, TOTIMP4 = ?, TOT_PARTIDA = ? 
                WHERE NUM_PAR = ? AND CVE_ART = ?";
            $params = [
                $CANT,
                $PREC,
                $IMPU1,
                $IMPU4,
                $DESC1,
                $DESC2,
                $TOTIMP1,
                $TOTIMP4,
                $TOT_PARTIDA,
                $NUM_PAR_EXISTENTE,
                $CVE_ART
            ];
        } else {
            // Si la partida no existe, realizar un INSERT
            $sql = "INSERT INTO $nombreTabla
                (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA,
                TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4,
                DESC1, DESC2, DESC3, COMI, APAR,
                ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, TIPO_PROD, CVE_OBS, REG_SERIE, E_LTPD, TIPO_ELEM, 
                NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, UUID,
                VERSION_SINC, ID_RELACION, PREC_NETO,
                CVE_PRODSERV, CVE_UNIDAD, IMPU8, IMPU7, IMPU6, IMPU5, IMP5APLA,
                IMP6APLA, TOTIMP8, TOTIMP7, TOTIMP6, TOTIMP5, IMP8APLA, IMP7APLA)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 4, 4, 4, 4,
                ?, ?, 0, ?,
                ?, ?, 0, 0, ?,
                'N', ?, '', 1, ?, ?, 0, 0, 0, 'N',
                0, ?, 'S', 'N', 0, 0, 0, 0, 0, 0, '',
                0, '', '',
                0, ?, '', 0, 0, 0, 0,
                0, 0, 0, 0, 0, 0, 0)";
            $params = [
                $CVE_DOC,
                $NUM_PAR,
                $CVE_ART,
                $CANT,
                $PXS,
                $PREC,
                $IMPU1,
                $IMPU2,
                $IMPU3,
                $IMPU4,
                $TOTIMP1,
                $TOTIMP2,
                $TOTIMP4,
                $DESC1,
                $DESC2,
                $COMI,
                $NUM_ALMA,
                $UNI_VENTA,
                $TIPO_PORD,
                $TOT_PARTIDA,
                $CVE_UNIDAD
            ];
        }
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => 'Error al actualizar o insertar una partida', 'errors' => sqlsrv_errors()]));
        }
        $NUM_PAR++;
    }
    // Confirmar transacción
    sqlsrv_commit($conn);
    sqlsrv_close($conn);

    return ['success' => true, 'message' => 'Partidas actualizadas correctamente'];
}
function obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae)
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

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$CVE_DOC];

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
function actualizarNuevoInventario($conexionData, $formularioData, $partidasData)
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

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // Obtener las partidas anteriores del pedido
    $query = "SELECT CVE_ART, CANT FROM $nombreTablaPartidas WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener partidas anteriores', 'errors' => sqlsrv_errors()]));
    }

    $partidasAnteriores = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidasAnteriores[$row['CVE_ART']] = $row['CANT'];
    }
    sqlsrv_free_stmt($stmt);

    // Crear un array para facilitar la comparación con las nuevas partidas
    $partidasActuales = [];
    foreach ($partidasData as $partida) {
        $partidasActuales[$partida['producto']] = $partida['cantidad'];
    }

    // Ajustar el inventario
    foreach ($partidasAnteriores as $producto => $cantidadAnterior) {

        // Si el producto ya no está en las partidas actuales, fue eliminado
        if (!isset($partidasActuales[$producto])) {

            // Si el producto fue eliminado, agregar la cantidad anterior al inventario
            $sql = "UPDATE $nombreTablaInventario SET APART = APART - ? WHERE CVE_ART = ?";
            $params = [$cantidadAnterior, $producto];
        }
        // Si la cantidad fue reducida, ajustar la diferencia
        elseif ($partidasActuales[$producto] < $cantidadAnterior) {

            // Si la cantidad fue reducida, agregar la diferencia al inventario
            $diferencia = $cantidadAnterior - $partidasActuales[$producto];
            $sql = "UPDATE $nombreTablaInventario SET APART = APART - ? WHERE CVE_ART = ?";
            $params = [$diferencia, $producto];
        }
        // Si la cantidad fue aumentada, restar la diferencia del inventario
        elseif ($partidasActuales[$producto] > $cantidadAnterior) {

            $diferencia = $partidasActuales[$producto] - $cantidadAnterior;
            $sql = "UPDATE $nombreTablaInventario SET APART = APART + ? WHERE CVE_ART = ?";
            $params = [$diferencia, $producto];
        }
        // Si las cantidades son iguales, no se realiza ninguna acción
        else {
            continue;
        }

        // Ejecutar la consulta para actualizar el inventario
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => 'Error al actualizar inventario', 'errors' => sqlsrv_errors()]));
        }
    }

    // Verificar si hay productos nuevos en las partidas actuales
    foreach ($partidasActuales as $producto => $cantidadActual) {
        if (!isset($partidasAnteriores[$producto])) {
            // Si el producto es nuevo, restar la cantidad del inventario
            $sql = "UPDATE $nombreTablaInventario SET APART = APART - ? WHERE CVE_ART = ?";
            $params = [$cantidadActual, $producto];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                sqlsrv_rollback($conn);
                die(json_encode(['success' => false, 'message' => 'Error al agregar nuevo producto al inventario', 'errors' => sqlsrv_errors()]));
            }
        }
    }

    // Confirmar transacción
    sqlsrv_commit($conn);
    sqlsrv_close($conn);

    return ['success' => true, 'message' => 'Inventario actualizado correctamente'];
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
function obtenerDatosCliente($conexionData, $claveCliente, $claveSae)
{
    $clave = formatearClaveCliente($claveCliente);

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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Consulta SQL para obtener los datos del cliente
    $sql = "
        SELECT 
            CVE_OBS,
            CVE_BITA,
            METODODEPAGO, NUMCTAPAGO,
            FORMADEPAGOSAT, USO_CFDI, REG_FISC
        FROM $nombreTabla
        WHERE CLAVE = '$clave'
    ";
    $stmt = sqlsrv_query($conn, $sql, [$clave]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener datos del cliente', 'errors' => sqlsrv_errors()]));
    }

    // Obtener los datos
    $datosCliente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datosCliente;
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
function gaurdarDatosEnvio($conexionData, $clave, $formularioData, $envioData, $claveSae)
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

    $claveCliente = $formularioData['cliente'];
    $datosCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae);
    if (!$datosCliente) {
        die(json_encode(['success' => false, 'message' => 'No se encontraron datos del cliente']));
    }
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";



    // Extraer los datos del formulario
    $CVE_INFO = obtenerUltimoDato($conexionData, $claveSae);
    $CVE_INFO = $CVE_INFO + 1;
    $CVE_CONS = "";
    $NOMBRE = $envioData['nombreContacto'];
    $CALLE = $envioData['direccion1Contacto'];
    $NUMINT = "";
    $NUMEXT = "";
    $CRUZAMIENTOS = "";
    $CRUZAMIENTOS2 = "";
    $POB = "";
    $CURP = "";
    $REFERDIR = "";
    $CVE_ZONA = "";
    $CVE_OBS = "";
    $STRNOGUIA = "";
    $STRMODOENV = "";
    $FECHA_ENV = $envioData['diaAlta'];
    $NOMBRE_RECEP = "";
    $NO_RECEP = "";
    $FECHA_RECEP = "";
    $COLONIA = "";
    $CODIGO = $envioData['codigoContacto'];
    $ESTADO = $envioData['estadoContacto'];
    $PAIS = "MEXICO";
    $MUNICIPIO = $envioData['municipioContacto'];
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
    sqlsrv_close($conn);
    return $CVE_INFO;
}
function guardarPedido($conexionData, $formularioData, $partidasData, $claveSae, $estatus, $DAT_ENVIO)
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
    $claveCliente = $formularioData['cliente'];
    $datosCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae);
    if (!$datosCliente) {
        die(json_encode(['success' => false, 'message' => 'No se encontraron datos del cliente']));
    }
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Extraer los datos del formulario
    $FOLIO = $formularioData['numero'];
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $FECHA_DOC = $formularioData['diaAlta']; // Fecha del documento
    $FECHA_ENT = $formularioData['entrega'];
    // Sumar los totales de las partidas
    $SUBTOTAL = 0;
    $IMPORTE = 0;
    $descuentoCliente = $formularioData['descuentoCliente']; // Valor del descuento en porcentaje (ejemplo: 10 para 10%)

    foreach ($partidasData as $partida) {
        $SUBTOTAL += $partida['cantidad'] * $partida['precioUnitario']; // Sumar cantidades totales
        $IMPORTE += $partida['cantidad'] * $partida['precioUnitario']; // Calcular importe total
    }
    $IMPORTT = $IMPORTE;
    /*foreach ($partidasData as $partida) {
        // Aplicar descuento
        if ($descuentoCliente > 0) { // Verificar que el descuento sea mayor a 0
            $DES_TOT = $IMPORT - ($IMPORT * ($descuentoCliente / 100) * ($partida['descuento'] / 100)); // Aplicar porcentaje de descuento
        } else {
            $DES_TOT = $IMPORT - ($IMPORT * ($partida['descuento'] / 100)); // Si no hay descuento, el total queda igual al importe
        }
    }*/
    //$IMPORTE = $IMPORTE; // Mantener el importe original
    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;
    $CVE_VEND = str_pad($formularioData['claveVendedor'], 5, ' ', STR_PAD_LEFT);
    // Asignación de otros valores del formulario
    $IMP_TOT1 = 0;
    $IMP_TOT2 = 0;
    $IMP_TOT3 = 0;
    $IMP_TOT5 = 0;
    $IMP_TOT6 = 0;
    $IMP_TOT7 = 0;
    $IMP_TOT8 = 0;
    //$DES_FIN = $formularioData['descuentoFin'] || 0;
    $DES_FIN = 0;
    $CONDICION = $formularioData['condicion'] || "";
    $RFC = $formularioData['rfc'];
    $FECHA_ELAB = $formularioData['diaAlta'];
    $TIP_DOC = $formularioData['factura'];
    $NUM_ALMA = $formularioData['almacen'];
    $FORMAENVIO = 'C';
    $COM_TOT = $formularioData['comision'];
    $CVE_OBS = $datosCliente['CVE_OBS'];
    $CVE_BITA = $datosCliente['CVE_BITA'];
    //$COM_TOT_PORC = $datosCliente['COM_TOT_PORC']; //VENDEDOR
    $METODODEPAGO = $datosCliente['METODODEPAGO'];
    $NUMCTAPAGO = $datosCliente['NUMCTAPAGO'];
    $FORMADEPAGOSAT = $datosCliente['FORMADEPAGOSAT'];
    $USO_CFDI = $datosCliente['USO_CFDI'];
    $REG_FISC = $datosCliente['REG_FISC'];
    $ENLAZADO = 'O'; ////
    $TIP_DOC_E = 'O'; ////
    $DES_TOT_PORC = $formularioData['descuentoCliente'];; ////
    $COM_TOT_PORC = 0; ////
    $FECHAELAB = new DateTime("now", new DateTimeZone('America/Mexico_City'));
    $CVE_CLPV = str_pad($claveCliente, 10, ' ', STR_PAD_LEFT);
    $FECHA_CANCELA = "";
    // Crear la consulta SQL para insertar los datos en la base de datos
    $sql = "INSERT INTO $nombreTabla
    (TIP_DOC, CVE_DOC, CVE_CLPV, STATUS, DAT_MOSTR,
    CVE_VEND, CVE_PEDI, FECHA_DOC, FECHA_ENT, FECHA_VEN, CAN_TOT,
    IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8,
    DES_TOT, DES_FIN, COM_TOT, CONDICION, CVE_OBS, NUM_ALMA, ACT_CXC, ACT_COI, ENLAZADO,
    TIP_DOC_E, NUM_MONED, TIPCAMB, NUM_PAGOS, FECHAELAB, PRIMERPAGO, RFC, CTLPOL, ESCFD, AUTORIZA,
    SERIE, FOLIO, AUTOANIO, DAT_ENVIO, CONTADO, CVE_BITA, BLOQ, FORMAENVIO, DES_FIN_PORC, DES_TOT_PORC,
    IMPORTE, COM_TOT_PORC, METODODEPAGO, NUMCTAPAGO,
    VERSION_SINC, FORMADEPAGOSAT, USO_CFDI, TIP_TRASLADO, TIP_FAC, REG_FISC
    ) 
    VALUES 
    ('P', ?, ?, ?, 0, 
    ?, '', ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, 'S', 'N', ?,
    ?, 1, 1, 1, ?, 0, ?, 0, 'N', 1,
    '', ?, '', ?, 'N', ?, 'N', 'C', 0, ?,
    ?, ?, ?, ?,
    '', ?, ?, '', '', ?)";
    // Preparar los parámetros para la consulta
    $params = [
        $CVE_DOC,
        $CVE_CLPV,
        $estatus,
        $CVE_VEND,
        $FECHA_DOC,
        $FECHA_ENT,
        $FECHA_DOC,
        $SUBTOTAL,
        $IMP_TOT1,
        $IMP_TOT2,
        $IMP_TOT3,
        $IMP_TOT4,
        $IMP_TOT5,
        $IMP_TOT6,
        $IMP_TOT7,
        $IMP_TOT8,
        $DES_TOT,
        $DES_FIN,
        $COM_TOT,
        $CONDICION,
        $CVE_OBS,
        $NUM_ALMA,
        $ENLAZADO,
        $TIP_DOC_E,
        $FECHAELAB,
        $RFC,
        $FOLIO,
        $DAT_ENVIO,
        $CVE_BITA,
        $DES_TOT_PORC,
        $IMPORTE,
        $COM_TOT_PORC,
        $METODODEPAGO,
        $NUMCTAPAGO,
        $FORMADEPAGOSAT,
        $USO_CFDI,
        $REG_FISC
    ];
    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al guardar el pedido',
            'sql_error' => sqlsrv_errors() // Captura los errores de SQL Server
        ]));
    } else {
        // echo json_encode(['success' => true, 'message' => 'Pedido guardado con éxito']);
    }
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function guardarPartidas($conexionData, $formularioData, $partidasData)
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
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Iniciar la transacción para las inserciones de las partidas
    sqlsrv_begin_transaction($conn);
    $NUM_PAR = 1;
    // Iterar sobre las partidas recibidas
    if (isset($partidasData) && is_array($partidasData)) {
        foreach ($partidasData as $partida) {
            // Extraer los datos de la partida
            $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
            $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
            $CVE_ART = $partida['producto']; // Clave del producto
            $CANT = $partida['cantidad']; // Cantidad
            $PREC = $partida['precioUnitario']; // Precio
            // Calcular los impuestos y totales
            $IMPU1 = $partida['ieps']; // Impuesto 1
            $IMPU3 = $partida['isr'];
            //$IMPU1 = 0;
            //$IMPU2 = $partida['impuesto2']; // Impuesto 2
            $IMPU2 = 0;
            $IMPU4 = $partida['iva']; // Impuesto 2
            // Agregar los cálculos para los demás impuestos...
            $PXS = 0;
            $DESC1 = $partida['descuento'];
            $DESC2 = 0;
            $COMI = $partida['comision'];
            $CVE_UNIDAD = $partida['CVE_UNIDAD'];
            $NUM_ALMA = $formularioData['almacen'];
            $COSTO_PROM = $partida['COSTO_PROM'];
            $UNI_VENTA = $partida['unidad'];
            if ($UNI_VENTA === 'No aplica' || $UNI_VENTA === 'SERVICIO' || $UNI_VENTA === 'Servicio') {
                $TIPO_PORD = 'S';
            } else {
                $TIPO_PORD = 'P';
            }
            // Calcular el total de la partida (precio * cantidad)
            $TOT_PARTIDA = $PREC * $CANT;
            $TOTIMP1 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU1 / 100);
            $TOTIMP2 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU2 / 100);
            $TOTIMP4 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU4 / 100);
            // Agregar los cálculos para los demás TOTIMP...


            // Consultar la descripción del producto (si es necesario)
            //$DESCR_ART = obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae);

            // Crear la consulta SQL para insertar los datos de la partida
            $sql = "INSERT INTO $nombreTabla
                (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA,
                TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4,
                DESC1, DESC2, DESC3, COMI, APAR,
                ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, TIPO_PROD, CVE_OBS, REG_SERIE, E_LTPD, TIPO_ELEM, 
                NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, UUID,
                VERSION_SINC, ID_RELACION, PREC_NETO,
                CVE_PRODSERV, CVE_UNIDAD, IMPU8, IMPU7, IMPU6, IMPU5, IMP5APLA,
                IMP6APLA, TOTIMP8, TOTIMP7, TOTIMP6, TOTIMP5, IMP8APLA, IMP7APLA)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 4, 4, 4, 4,
                ?, ?, 0, ?,
                ?, ?, 0, 0, ?,
                'N', ?, '', 1, ?, ?, 0, 0, 0, 'N',
                0, ?, 'S', 'N', 0, 0, 0, 0, 0, 0, '',
                0, '', '',
                0, ?, '', 0, 0, 0, 0,
                0, 0, 0, 0, 0, 0, 0)";
            $params = [
                $CVE_DOC,
                $NUM_PAR,
                $CVE_ART,
                $CANT,
                $PXS,
                $PREC,
                $COSTO_PROM,
                $IMPU1,
                $IMPU2,
                $IMPU3,
                $IMPU4,
                $TOTIMP1,
                $TOTIMP2,
                $TOTIMP4,
                $DESC1,
                $DESC2,
                $COMI,
                $NUM_ALMA,
                $UNI_VENTA,
                $TIPO_PORD,
                $TOT_PARTIDA,
                $CVE_UNIDAD
            ];
            // Ejecutar la consulta
            $stmt = sqlsrv_query($conn, $sql, $params);
            //var_dump($stmt);
            if ($stmt === false) {
                //var_dump(sqlsrv_errors()); // Muestra los errores específicos
                sqlsrv_rollback($conn);
                die(json_encode(['success' => false, 'message' => 'Error al insertar la partida', 'errors' => sqlsrv_errors()]));
            }
            $NUM_PAR++;
        }
    } else {
        die(json_encode(['success' => false, 'message' => 'Error: partidasData no es un array válido']));
    }
    //echo json_encode(['success' => true, 'message' => 'Partidas guardadas con éxito']);
    // Confirmar la transacción
    sqlsrv_commit($conn);
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae)
{
    // Aquí puedes realizar una consulta para obtener la descripción del producto basado en la clave
    // Asumiendo que la descripción está en una tabla llamada "productos"
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT DESCR FROM $nombreTabla WHERE CVE_ART = ?";
    $stmt = sqlsrv_query($conn, $sql, [$CVE_ART]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $descripcion = $row ? $row['DESCR'] : '';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $descripcion;
}
function actualizarFolio($conexionData, $claveSae)
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

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // SQL para incrementar el valor de ULT_DOC en 1 donde TIP_DOC es 'P'
    $sql = "UPDATE $nombreTabla
            SET [ULT_DOC] = [ULT_DOC] + 1
            WHERE [TIP_DOC] = 'P'";

    // Ejecutar la consulta SQL
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        // Si la consulta falla, liberar la conexión y retornar el error
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el folio', 'errors' => sqlsrv_errors()]));
    }

    // Verificar cuántas filas se han afectado
    $rowsAffected = sqlsrv_rows_affected($stmt);

    // Liberar el recurso solo si la consulta fue exitosa
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    // Retornar el resultado
    if ($rowsAffected > 0) {
        //echo json_encode(['success' => true, 'message' => 'Folio actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron folios para actualizar']);
    }
}
function actualizarFolioF($conexionData, $claveSae)
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

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // SQL para incrementar el valor de ULT_DOC en 1 donde TIP_DOC es 'P'
    $sql = "UPDATE $nombreTabla
            SET [ULT_DOC] = [ULT_DOC] + 1
            WHERE [TIP_DOC] = 'F' AND [SERIE] = 'STAND.'";

    // Ejecutar la consulta SQL
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        // Si la consulta falla, liberar la conexión y retornar el error
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el folio', 'errors' => sqlsrv_errors()]));
    }

    // Verificar cuántas filas se han afectado
    $rowsAffected = sqlsrv_rows_affected($stmt);

    // Liberar el recurso solo si la consulta fue exitosa
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    // Retornar el resultado
    if ($rowsAffected > 0) {
        //echo json_encode(['success' => true, 'message' => 'Folio actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron folios para actualizar']);
    }
}
function actualizarInventario($conexionData, $partidasData)
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
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['producto'];
        $cantidad = $partida['cantidad'];
        // SQL para actualizar los campos EXIST y PEND_SURT
        $sql = "UPDATE $nombreTabla
            SET    
                [APART] = [APART] + ?   
            WHERE [CVE_ART] = '$CVE_ART'";
        // Preparar la consulta
        $params = array($cantidad, $cantidad);

        // Ejecutar la consulta SQL
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al actualizar el inventario', 'errors' => sqlsrv_errors()]));
        }
        // Verificar cuántas filas se han afectado
        $rowsAffected = sqlsrv_rows_affected($stmt);
        // Retornar el resultado
        if ($rowsAffected > 0) {
            // echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el producto para actualizar']);
        }
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerFolioSiguiente($conexionData, $claveSae)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta SQL para obtener el siguiente folio
    $sql = "SELECT (ULT_DOC + 1) AS FolioSiguiente FROM $nombreTabla WHERE TIP_DOC = 'P'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener el siguiente folio
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $folioSiguiente = $row ? $row['FolioSiguiente'] : null;
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    // Retornar el folio siguiente
    return $folioSiguiente;
}
// Función para validar si el cliente tiene correo
function validarCorreoCliente($formularioData, $partidasData, $conexionData, $rutaPDF, $claveSae, $conCredito)
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
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $formularioData['enviar']; // Dirección de envío
    $vendedor = $formularioData['vendedor']; // Número de vendedor
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $formularioData['numero']; // Número de pedido
    /*$claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
    $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);*/

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener MAIL y EMAILPRED
    $sql = "SELECT MAIL, EMAILPRED, NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [$clave];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['producto'];

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

    $fechaElaboracion = $formularioData['fechaAlta'];
    $correo = trim($clienteData['MAIL']);
    $emailPred = trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    // Si hay múltiples correos separados por `;`, tomar solo el primero
    //$emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    //$emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    //$numeroWhatsApp = trim($clienteData['TELEFONO']);
    $clienteNombre = trim($clienteData['NOMBRE']);
    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';*/
    $emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/ // Interzenda

    if ($emailPred === "") {
        $correoBandera = 1;
    } else {
        $correoBandera = 0;
    }
    if ($numeroWhatsApp === "") {
        $numeroBandera = 1;
    } else {
        $numeroBandera = 0;
    }
    if (($correo === 'S' && isset($emailPred)) || isset($numeroWhatsApp)) {
        // Enviar notificaciones solo si los datos son válidos
        if ($correoBandera === 0) {
            enviarCorreo($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData); // Enviar correo
        }

        if ($numeroBandera === 0) {
            $resultadoWhatsApp = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito);
        }

        // Determinar la respuesta JSON según las notificaciones enviadas
        if ($correoBandera === 0 && $numeroBandera === 0) {
            /*echo json_encode(['success' => true, 'notificacion' => true, 'message' => 'Pedido Autorizado y notificado por correo y WhatsApp.']);
            die();*/
        } elseif ($correoBandera === 1 && $numeroBandera === 0) {
            echo json_encode(['success' => false, 'telefono' => true, 'message' => 'Pedido Realizado y Notificado por WhatsApp.']);
            die();
        } elseif ($correoBandera === 0 && $numeroBandera === 1) {
            echo json_encode(['success' => false, 'correo' => true, 'message' => 'Pedido Realizado y notificado por Correo.']);
            die();
        } else {
            echo json_encode(['success' => false, 'notificacion' => true, 'message' => 'Pedido Realizado, pero no se Pudo Notificar al Cliente.']);
            die();
        }
    } else {
        echo json_encode(['success' => false, 'datos' => true, 'message' => 'El cliente no Tiene un Correo y Telefono Válido Registrado.']);
        die();
    }
    /*******************************************/
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function enviarWhatsAppAutorizacion($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $credito)
{
    // Conectar a SQL Server
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

    // Configuración de la API de WhatsApp
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // Obtener datos del pedido
    $noPedido = $formularioData['numero'];
    $enviarA = $formularioData['enviar'];
    $vendedor = $formularioData['vendedor'];
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $fechaElaboracion = $formularioData['diaAlta'];
    $vendedor = formatearClaveVendedor($vendedor);
    // Obtener datos del cliente desde la base de datos
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE FROM $nombreTabla3 WHERE [CVE_VEND] = ?";
    $stmt = sqlsrv_query($conn, $sql, [$vendedor]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar al vendedor', 'errors' => sqlsrv_errors()]));
    }

    $vendedorData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$vendedorData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }

    // Obtener datos del cliente desde la base de datos
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
    $stmt = sqlsrv_query($conn, $sql, [$clave]);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $vendedor = trim(($vendedorData['NOMBRE']));

    //$clienteNombre = trim($clienteData['NOMBRE']);
    //$numeroTelefono = trim($clienteData['TELEFONO']); // Si no hay teléfono registrado, usa un número por defecto
    //$numero = "7775681612";
    //$numero = "+527772127123"; //InterZenda
    //$numero = "+527773340218";
    //$numero = "+527773750925";
    $numero = '+527775681612';
    // Obtener descripciones de los productos
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['producto'];
        $sqlProducto = "SELECT DESCR FROM $nombreTabla2 WHERE CVE_ART = ?";
        $stmtProducto = sqlsrv_query($conn, $sqlProducto, [$claveProducto]);

        if ($stmtProducto && $rowProducto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC)) {
            $partida['descripcion'] = $rowProducto['DESCR'];
        } else {
            $partida['descripcion'] = 'Descripción no encontrada';
        }

        sqlsrv_free_stmt($stmtProducto);
    }

    // Construcción del mensaje con los detalles del pedido
    $productosStr = "";
    $total = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = $partida['precioUnitario'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $productosStr .= "$producto - $cantidad unidades, ";
    }
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    /*$mensajeProblema1 = "";
    $mensajeProblema2 = "";
    if ($validarSaldo == 1) {
        $mensajeProblema1 = "Saldo Vendido";
    }
    if ($credito == 1) {
        $mensajeProblema2 = "Credito Excedido";
    }

    // Definir el mensaje de problemas del cliente (Saldo vencido)
    $mensajeProblema = urlencode($mensajeProblema1) . urlencode($mensajeProblema2);*/
    $problemas = [];

    if ($validarSaldo == 1) {
        $problemas[] = "• Saldo Vencido";
    }
    if ($credito == 1) {
        $problemas[] = "• Crédito Excedido";
    }

    // Si hay problemas, los une con un espacio
    $mensajeProblema = !empty($problemas) ? implode(" ", $problemas) : "Sin problemas";


    // Construcción del JSON para enviar el mensaje de WhatsApp con plantilla
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "autorizar_pedido",
            "language" => ["code" => "es_MX"],
            "components" => [
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $vendedor], // {{1}} Vendedor
                        ["type" => "text", "text" => $noPedido], // {{2}} Número de pedido
                        ["type" => "text", "text" => $productosStr], // {{3}} Detalles de los productos
                        ["type" => "text", "text" => $mensajeProblema] // {{4}} Problema del cliente
                    ]
                ]
            ]
        ]
    ];

    // Enviar el mensaje de WhatsApp
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

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

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    return $result;
}
function enviarRechazoWhatsApp($numero, $pedidoId, $nombreCliente)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // Crear el cuerpo de la solicitud para la API
    $data = [
        "messaging_product" => "whatsapp",
        "to" => $numero, // Número del vendedor
        "type" => "template",
        "template" => [
            "name" => "rechazar_pedido_", // Nombre de la plantilla aprobada
            "language" => ["code" => "es_MX"], // Idioma de la plantilla
            "components" => [
                // Parámetros del cuerpo de la plantilla
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $nombreCliente], // {{1}}: Nombre del vendedor
                        ["type" => "text", "text" => $pedidoId]  // {{2}}: Número del pedido
                    ]
                ]
            ]
        ]
    ];

    // Convertir los datos a JSON
    $data_string = json_encode($data);

    // Configurar cURL para enviar la solicitud
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ]);

    // Ejecutar la solicitud y cerrar cURL
    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}
function enviarWhatsAppConPlantilla($numero, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';

    //$token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD'; // 📌 Reemplázalo con un token válido
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // ✅ Verifica que los valores no estén vacíos
    if (empty($noPedido) || empty($claveSae)) {
        error_log("Error: noPedido o noEmpresa están vacíos.");
        return false;
    }
    $productosJson = urlencode(json_encode($partidasData));
    // ✅ Generar URLs dinámicas correctamente
    // ✅ Generar solo el ID del pedido en la URL del botón
    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&credito=" . urlencode($conCredito);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave); // Solo pasamos el número de pedido


    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = $partida['precioUnitario'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $IMPORTE = $total;
        $productosStr .= "$producto - $cantidad unidades, ";

        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0;
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    // ✅ Datos para WhatsApp API con botones de Confirmar y Rechazar
    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "confirmar_pedido", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
            "components" => [
                [
                    "type" => "header",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre] // 📌 Encabezado dinámico
                    ]
                ],
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $noPedido], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $productosStr], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($IMPORTE, 2)] // 📌 Precio total
                    ]
                ],
                // ✅ Botón Confirmar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 0,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlConfirmar] // 📌 URL dinámica
                    ]
                ],
                // ✅ Botón Rechazar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 1,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlRechazar] // 📌 URL dinámica
                    ]
                ]
            ]
        ]
    ];

    // ✅ Verificar JSON antes de enviarlo
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

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
function enviarCorreo($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData)
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
    $vendedor = obtenerNombreVendedor($vendedor, $conexionData, $claveSae);
    // Obtener el nombre de la empresa desde la sesión
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'Empresa Desconocida';

    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // Convertir productos a JSON para la URL
    $productosJson = urlencode(json_encode($partidasData));

    // URL base del servidor
    $urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "http://localhost/MDConnecta/Servidor/PHP";
    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&credito=" . urlencode($conCredito);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa);

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los detalles de su pedido <b>$noPedido</b>. Por favor, revíselos y confirme:</p>";
    $bodyHTML .= "<p><b>Fecha y Hora de Elaboración:</b> $fechaElaboracion</p>";
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
        $clave = htmlspecialchars($partida['producto']);
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = htmlspecialchars($partida['cantidad']);
        $totalPartida = $cantidad * $partida['precioUnitario'];
        $total += $totalPartida;
        $IMPORTE = $total;

        $bodyHTML .= "<tr>
                        <td style='text-align: center;'>$clave</td>
                        <td>$descripcion</td>
                        <td style='text-align: right;'>$cantidad</td>
                        <td style='text-align: right;'>$" . number_format($totalPartida, 2) . "</td>
                      </tr>";

        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0;
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // `
    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total:</b> $" . number_format($IMPORTE, 2) . "</p>";

    // Botones para confirmar o rechazar el pedido
    $bodyHTML .= "<p>Confirme su pedido seleccionando una opción:</p>
                  <a href='$urlConfirmar' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar</a>
                  <a href='$urlRechazar' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Rechazar</a>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente);

    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
}
function obtenerClientePedido($claveVendedor, $conexionData, $clienteInput, $claveSae)
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

    $clienteInput = mb_convert_encoding(trim($clienteInput), 'UTF-8');
    $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');

    // Manejo de espacios para la clave
    $clienteClave = str_pad($clienteInput, 10, " ", STR_PAD_LEFT);
    $clienteNombre = '%' . $clienteInput . '%';
    $claveVendedor = str_pad($claveVendedor, 5, " ", STR_PAD_LEFT);

    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];

    // Construir la consulta SQL
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    if ($tipoUsuario === "ADMINISTRADOR") {
        if (preg_match('/[a-zA-Z]/', $clienteInput)) {
            // Búsqueda por nombre
            $sql = "SELECT DISTINCT
                    [CLAVE], [NOMBRE], [CALLE_ENVIO] AS CALLE, [RFC], [NUMINT], [NUMEXT], [COLONIA], [CODIGO],
                    [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS], [TELEFONO], [LISTA_PREC], [DESCUENTO], [CVE_VEND]
                FROM $nombreTabla
                WHERE LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER ('$clienteNombre') AND [STATUS] = 'A'";
        } else {
            // Búsqueda por clave
            $sql = "SELECT DISTINCT
                    [CLAVE], [NOMBRE], [CALLE_ENVIO] AS CALLE, [RFC], [NUMINT], [NUMEXT], [COLONIA], [CODIGO],
                    [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS], [TELEFONO], [LISTA_PREC], [DESCUENTO], [CVE_VEND]
                FROM $nombreTabla
                WHERE [CLAVE] = '$clienteClave' AND [STATUS] = 'A'";
        }
    } else {
        if (preg_match('/[a-zA-Z]/', $clienteInput)) {
            // Búsqueda por nombre
            $sql = "SELECT DISTINCT
                    [CLAVE], [NOMBRE], [CALLE_ENVIO] AS CALLE, [RFC], [NUMINT], [NUMEXT], [COLONIA], [CODIGO],
                    [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS], [TELEFONO], [LISTA_PREC], [DESCUENTO], [CVE_VEND]
                FROM $nombreTabla
                WHERE LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER ('$clienteNombre') AND [CVE_VEND] = '$claveVendedor' AND [STATUS] = 'A'";
        } else {
            // Búsqueda por clave
            $sql = "SELECT DISTINCT
                    [CLAVE], [NOMBRE], [CALLE_ENVIO] AS CALLE, [RFC], [NUMINT], [NUMEXT], [COLONIA], [CODIGO],
                    [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS], [TELEFONO], [LISTA_PREC], [DESCUENTO], [CVE_VEND]
                FROM $nombreTabla
                WHERE [CLAVE] = '$clienteClave' AND [CVE_VEND] = '$claveVendedor' AND [STATUS] = 'A'";
        }
    }
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }
    $clientes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $clientes[] = $row;
    }

    if (count($clientes) > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cliente' => $clientes
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron clientes.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerProductoPedido($claveVendedor, $conexionData, $clienteInput)
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

    $clienteInput = mb_convert_encoding(trim($clienteInput), 'UTF-8');
    $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');

    // Manejo de espacios para la clave
    $clienteClave = str_pad($clienteInput, 10, " ", STR_PAD_LEFT);
    $clienteNombre = '%' . $clienteInput . '%';

    // Construir la consulta SQL
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Definir la consulta SQL asegurando la búsqueda insensible a mayúsculas y manejando el guion `-`
    $sql = "SELECT DISTINCT [CVE_ART], [DESCR], [EXIST], [LIN_PROD], [UNI_MED], [CVE_ESQIMPU], [CVE_UNIDAD], [COSTO_PROM], [UUID]
        FROM $nombreTabla
        WHERE [EXIST] > 0 
        AND (LOWER(LTRIM(RTRIM([DESCR]))) LIKE LOWER(?) 
        OR LOWER(LTRIM(RTRIM([CVE_ART]))) LIKE LOWER(?))";

    // Agregar `%` al parámetro de búsqueda para permitir coincidencias parciales
    $parametros = ["%$clienteInput%", "%$clienteInput%"];

    // Ejecutar la consulta SQL
    $stmt = sqlsrv_query($conn, $sql, $parametros);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al buscar productos', 'errors' => sqlsrv_errors()]));
    }

    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'productos' => $productos
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerProductos($conexionData)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    // Intentar conectarse a la base de datos
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL
    $sql = "SELECT TOP (1000) [CVE_ART], [DESCR], [EXIST], [LIN_PROD], [UNI_MED], [CVE_ESQIMPU], [CVE_UNIDAD], [COSTO_PROM], [UUID]
        FROM $nombreTabla WHERE [EXIST] > 0";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }

    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
    }

    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerPrecioProducto($conexionData, $claveProducto, $listaPrecioCliente, $claveSae)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];
    // Intentar conectarse a la base de datos
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Usar la lista de precios del cliente o un valor predeterminado
    $listaPrecio = $listaPrecioCliente ? intval($listaPrecioCliente) : 1;
    $claveProducto = mb_convert_encoding(trim($claveProducto), 'UTF-8');
    //$claveProducto = "'". $claveProducto . "'";
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PRECIO_X_PROD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT [PRECIO] 
            FROM $nombreTabla
            WHERE [CVE_ART] = ? AND [CVE_PRECIO] = ?";
    $params = [$claveProducto, $listaPrecio];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }
    $precio = null;
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $precio = $row['PRECIO'];
    }
    header('Content-Type: application/json');
    if ($precio !== null) {
        echo json_encode(['success' => true, 'precio' => (float) $precio]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el precio del producto.']);
    }
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerImpuesto($conexionData, $cveEsqImpu, $claveSae)
{
    //ob_start(); // Inicia el buffer de salida para evitar texto adicional
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
        header('Content-Type: application/json; charset=utf-8');
        //ob_end_clean();
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $cveEsqImpu = mb_convert_encoding(trim($cveEsqImpu), 'UTF-8');
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[IMPU" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT IMPUESTO1, IMPUESTO2, IMPUESTO3, IMPUESTO4 FROM $nombreTabla WHERE CVE_ESQIMPU = ?";
    $params = [$cveEsqImpu];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        header('Content-Type: application/json; charset=utf-8');
        //ob_end_clean();
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }

    $impuestos = null;
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $impuestos = [
            'IMPUESTO1' => (float) $row['IMPUESTO1'],
            'IMPUESTO2' => (float) $row['IMPUESTO2'],
            'IMPUESTO4' => (float) $row['IMPUESTO4']
        ];
    }

    header('Content-Type: application/json; charset=utf-8');
    //ob_end_clean(); // Limpia cualquier salida antes de enviar la respuesta

    if ($impuestos !== null) {
        echo json_encode(['success' => true, 'impuestos' => $impuestos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron impuestos para la clave especificada.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function validarExistencias($conexionData, $partidasData, $claveSae)
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Inicializar listas de productos
    $productosSinExistencia = [];
    $productosConExistencia = [];

    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['producto'];
        $cantidad = $partida['cantidad'];

        // Consultar existencias reales considerando apartados
        $sqlCheck = "SELECT 
                        COALESCE([EXIST], 0) AS EXIST, 
                        COALESCE([APART], 0) AS APART, 
                        (COALESCE([EXIST], 0) - COALESCE([APART], 0)) AS DISPONIBLE 
                     FROM $nombreTabla 
                     WHERE [CVE_ART] = ?";
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$CVE_ART]);

        if ($stmtCheck === false) {
            sqlsrv_close($conn);
            die(json_encode(['success' => false, 'message' => 'Error al verificar existencias', 'errors' => sqlsrv_errors()]));
        }

        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $existencias = (float)$row['EXIST'];
            $apartados = (float)$row['APART'];
            $disponible = (float)$row['DISPONIBLE'];
            /*var_dump($existencias);
            var_dump($apartados);
            var_dump($disponible);*/
            if ($disponible >= $cantidad) {
                $productosConExistencia[] = [
                    'producto' => $CVE_ART,
                    'existencias' => $existencias,
                    'apartados' => $apartados,
                    'disponible' => $disponible
                ];
            } else {
                $productosSinExistencia[] = [
                    'producto' => $CVE_ART,
                    'existencias' => $existencias,
                    'apartados' => $apartados,
                    'disponible' => $disponible
                ];
            }
        } else {
            $productosSinExistencia[] = [
                'producto' => $CVE_ART,
                'existencias' => 0,
                'apartados' => 0,
                'disponible' => 0
            ];
        }
        sqlsrv_free_stmt($stmtCheck);
    }

    sqlsrv_close($conn);

    // Responder con el estado de las existencias
    if (!empty($productosSinExistencia)) {
        return [
            'success' => false,
            'exist' => true,
            'message' => 'No hay suficientes existencias para algunos productos',
            'productosSinExistencia' => $productosSinExistencia
        ];
    }

    return [
        'success' => true,
        'message' => 'Existencias verificadas correctamente',
        'productosConExistencia' => $productosConExistencia
    ];
}
function calcularTotalPedido($partidasData)
{
    $total = 0;
    foreach ($partidasData as $partida) {
        $total += $partida['cantidad'] * $partida['precioUnitario'];
    }
    return $total;
}
function validarCreditoCliente($conexionData, $clienteId, $totalPedido, $claveSae)
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT LIMCRED, SALDO FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [str_pad($clienteId, 10, ' ', STR_PAD_LEFT)];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$clienteData) {
        sqlsrv_close($conn);
        return [
            'success' => false,
            'saldoActual' => null,
            'limiteCredito' => null
        ];
    }

    $limiteCredito = (float)$clienteData['LIMCRED'];
    $saldoActual = (float)$clienteData['SALDO'];
    $puedeContinuar = ($saldoActual + $totalPedido) <= $limiteCredito;

    sqlsrv_close($conn);

    // Devolver el resultado y los datos relevantes
    return [
        'success' => $puedeContinuar,
        'saldoActual' => $saldoActual,
        'limiteCredito' => $limiteCredito
    ];
}
function eliminarPartida($conexionData, $clavePedido, $numPar)
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
    $clavePedido = str_pad($clavePedido, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $clavePedido = str_pad($clavePedido, 20, ' ', STR_PAD_LEFT);
    // Nombre de las tablas dinámico basado en la empresa
    $claveSae = $_SESSION['empresa']['claveSae'];
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // 🚨 1️⃣ Obtener datos de la partida antes de eliminarla
    $sqlDatos = "SELECT CANT, CVE_ART FROM $tablaPartidas WHERE CVE_DOC = ? AND NUM_PAR = ?";
    $stmtDatos = sqlsrv_query($conn, $sqlDatos, [$clavePedido, $numPar]);

    if ($stmtDatos === false || !($row = sqlsrv_fetch_array($stmtDatos, SQLSRV_FETCH_ASSOC))) {
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al obtener datos de la partida', 'errors' => sqlsrv_errors()]));
    }

    $cantidad = $row['CANT']; // Cantidad de la partida
    $cveArt = $row['CVE_ART']; // Clave del artículo

    sqlsrv_free_stmt($stmtDatos); // Liberar la consulta anterior

    // 🚨 2️⃣ Restar los apartados en inventario antes de eliminar la partida
    $sqlActualizarInve = "UPDATE $tablaInve SET APART = APART - ? WHERE CVE_ART = ?";
    $stmtActualizarInve = sqlsrv_query($conn, $sqlActualizarInve, [$cantidad, $cveArt]);

    if ($stmtActualizarInve === false) {
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al actualizar apartados en inventario', 'errors' => sqlsrv_errors()]));
    }

    sqlsrv_free_stmt($stmtActualizarInve); // Liberar la consulta anterior

    // 🚨 3️⃣ Eliminar la partida
    $sqlEliminar = "DELETE FROM $tablaPartidas WHERE CVE_DOC = ? AND NUM_PAR = ?";
    $stmtEliminar = sqlsrv_query($conn, $sqlEliminar, [$clavePedido, $numPar]);

    if ($stmtEliminar === false) {
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al eliminar la partida', 'errors' => sqlsrv_errors()]));
    }

    $filasAfectadas = sqlsrv_rows_affected($stmtEliminar);
    sqlsrv_free_stmt($stmtEliminar);
    sqlsrv_close($conn);

    // 🚨 4️⃣ Responder según el resultado
    if ($filasAfectadas > 0) {
        echo json_encode(['success' => true, 'message' => 'Partida eliminada correctamente y apartados ajustados.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la partida especificada.']);
    }
}
function pedidoRemitido($conexionData, $pedidoID, $claveSae)
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
    //$clave = str_pad($pedidoID, 10, ' ', STR_PAD_LEFT);
    $pedidoID = str_pad($pedidoID, 20, ' ', STR_PAD_LEFT);

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT DOC_SIG, TIP_DOC_SIG FROM $nombreTabla
    WHERE CVE_DOC = ?";

    $params = [$pedidoID];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $DOC_SIG = $row['DOC_SIG'];
        $TIP_DOC_SIG = $row['TIP_DOC_SIG'];
    }

    if ($DOC_SIG !== NULL && $TIP_DOC_SIG === "R") {
        return true;
    } else if ($DOC_SIG === NULL && $TIP_DOC_SIG !== 'R') {
        return false;
    }
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function eliminarPedido($conexionData, $pedidoID, $claveSae)
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
    //$clave = str_pad($pedidoID, 10, ' ', STR_PAD_LEFT);
    $pedidoID = str_pad($pedidoID, 20, ' ', STR_PAD_LEFT);
    // Nombre de la tabla dinámico basado en la empresa
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Actualizar el estatus del pedido
    $query = "UPDATE $nombreTabla SET STATUS = 'C' WHERE CVE_DOC = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$pedidoID]);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta', 'errors' => sqlsrv_errors()]);
        exit;
    }

    if (sqlsrv_execute($stmt)) {
        echo json_encode(['success' => true, 'pedido' => $pedidoID]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estatus del pedido', 'errors' => sqlsrv_errors()]);
    }

    sqlsrv_close($conn);
}
function liberarExistencias($conexionData, $folio, $claveSae)
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
        var_dump(sqlsrv_errors());
        exit;
    }
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT [CVE_ART], [CANT] FROM $nombreTabla
        WHERE [CVE_DOC] = '$CVE_DOC'";
    //$params = [$CVE_DOC];
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        echo "DEBUG: Error al actualizar el pedido:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = $row;
    }
    $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    foreach ($partidas as $partida) {
        $CVE_ART = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];
        // SQL para actualizar los campos EXIST y PEND_SURT
        $sql = "UPDATE $tablaInve
            SET    
                [APART] = [APART] - ?   
            WHERE [CVE_ART] = '$CVE_ART'";
        // Preparar la consulta
        $params = array($cantidad, $cantidad);
        // Ejecutar la consulta SQL
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al actualizar el inventario', 'errors' => sqlsrv_errors()]));
        }
        // Verificar cuántas filas se han afectado
        $rowsAffected = sqlsrv_rows_affected($stmt);
        // Retornar el resultado
        if ($rowsAffected > 0) {
            // echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el producto para actualizar']);
        }
    }
}

//--------------Funcion Mostrar Articulos----------------------------------------------------------------
function listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket)
{
    // Asegurar que el prefijo termine con '/'
    $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?prefix=imagenes/";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // Depuración de la respuesta de Firebase
    //var_dump($data);

    $imagenesPorArticulo = [];
    if (isset($data['items'])) {
        foreach ($data['items'] as $item) {
            $name = $item['name']; // Ejemplo: "imagenes/AB-2PAM/imagen1.jpg"
            $parts = explode('/', $name);

            if (count($parts) >= 2) {
                $cveArt = $parts[1]; // "AB-2PAM"
                $imagenesPorArticulo[$cveArt][] = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/" . rawurlencode($name) . "?alt=media";
            }
        }
    }

    return $imagenesPorArticulo;
}
function extraerProductos($conexionData, $claveSae)
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
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta directa a la tabla fija INVE02
    $sql = "
        SELECT 
            [CVE_ART], 
            [DESCR], 
            [EXIST], 
            [LIN_PROD], 
            [UNI_MED],
            [APART]
        FROM $nombreTabla WHERE [EXIST] > 0
    ";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Obtener todas las imágenes de Firebase en un solo lote
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenesPorArticulo = listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket);
    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        // Asignar las imágenes correspondientes al producto
        $row['IMAGEN_ML'] = $imagenesPorArticulo[$cveArt] ?? []; // Si no hay imágenes, asignar un array vacío

        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function extraerProductosE($conexionData, $claveSae, $listaPrecioCliente)
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
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
    if ($tipoUsuario === "ADMINISTRADOR") {
        $claveCliente = 3;
    } else {
        $claveCliente = $_SESSION['usuario']['claveUsuario']; // Clave del cliente
        $claveCliente = formatearClaveCliente($claveCliente);
    }
    $nombreTabla1 = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla4 = "[{$conexionData['nombreBase']}].[dbo].[PRECIO_X_PROD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // 📌 Consulta para obtener los productos más vendidos del cliente con su precio
    $sql = "
        SELECT DISTINCT TOP (6)
            p.CVE_ART, 
            i.DESCR, 
            SUM(p.CANT) AS TOTAL_COMPRADO, 
            i.EXIST, 
            i.LIN_PROD, 
            i.UNI_MED, 
            i.APART,
            i.CVE_ESQIMPU,
            i.CVE_UNIDAD,
            pr.PRECIO -- 📌 Se une el precio del producto
        FROM $nombreTabla1 p
        INNER JOIN $nombreTabla2 f ON p.CVE_DOC = f.CVE_DOC
        INNER JOIN $nombreTabla3 i ON p.CVE_ART = i.CVE_ART
        LEFT JOIN $nombreTabla4 pr 
            ON i.CVE_ART = pr.CVE_ART 
            AND pr.CVE_PRECIO = ?  -- 📌 Se filtra por LISTA_PREC
        WHERE f.CVE_CLPV = ?
        AND i.EXIST > 0 -- 📌 Filtro para productos con existencias mayores a 0
        GROUP BY p.CVE_ART, i.DESCR, i.EXIST, i.LIN_PROD, i.UNI_MED, i.APART, i.CVE_ESQIMPU, i.CVE_UNIDAD, pr.PRECIO
        ORDER BY TOTAL_COMPRADO DESC
    ";

    $params = [$listaPrecioCliente, $claveCliente]; // Parámetros para filtrar por LISTA_PREC y cliente
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // 📌 Obtener todas las imágenes de Firebase en un solo lote
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenesPorArticulo = listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket);

    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        // 📌 Asignar las imágenes correspondientes al producto
        $row['IMAGEN_ML'] = $imagenesPorArticulo[$cveArt] ?? []; // Si no hay imágenes, asignar un array vacío

        // 📌 Validar si el precio es null, asignar un precio predeterminado
        $row['PRECIO'] = $row['PRECIO'] ?? 0.00;

        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'sinDatos' => true, 'message' => 'No se encontraron productos.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function extraerProductosImagenes($conexionData, $claveSae)
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
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
    if ($tipoUsuario === "ADMINISTRADOR") {
        $claveCliente = 3;
    } else {
        $claveCliente = $_SESSION['usuario']['claveUsuario']; // Clave del cliente
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // 📌 Consulta para obtener los productos más vendidos del cliente con su precio
    $sql = "
        SELECT 
        i.[CVE_ART], 
        i.[DESCR], 
        i.[EXIST], 
        i.[LIN_PROD], 
        i.[UNI_MED],
        i.[APART],
        i.[CVE_ESQIMPU],
        i.[CVE_UNIDAD]
    FROM $nombreTabla i
    WHERE i.[EXIST] > 0
    ";

    $params = [$claveCliente]; // Parámetros para filtrar por LISTA_PREC y cliente
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // 📌 Obtener todas las imágenes de Firebase en un solo lote
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenesPorArticulo = listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket);

    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        // 📌 Asignar las imágenes correspondientes al producto
        $row['IMAGEN_ML'] = $imagenesPorArticulo[$cveArt] ?? []; // Si no hay imágenes, asignar un array vacío

        // 📌 Validar si el precio es null, asignar un precio predeterminado
        $row['PRECIO'] = $row['PRECIO'] ?? 0.00;

        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function extraerProductosCategoria($conexionData, $claveSae, $listaPrecioCliente)
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
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $nombreTabla1 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[PRECIO_X_PROD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta para obtener los productos más vendidos del cliente
    $sql = "
    SELECT 
        i.[CVE_ART], 
        i.[DESCR], 
        i.[EXIST], 
        i.[LIN_PROD], 
        i.[UNI_MED],
        i.[APART],
        i.[CVE_ESQIMPU],
        i.[CVE_UNIDAD],
        i.[COSTO_PROM],
        p.[PRECIO] -- Se une el precio del producto
    FROM $nombreTabla1 i
    LEFT JOIN $nombreTabla2 p 
        ON i.[CVE_ART] = p.[CVE_ART] 
        AND p.[CVE_PRECIO] = ? WHERE i.[EXIST] > 0";

    $params = [$listaPrecioCliente]; // Parámetro para filtrar por cliente
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Obtener todas las imágenes de Firebase en un solo lote
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenesPorArticulo = listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket);
    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        // Asignar las imágenes correspondientes al producto
        $row['IMAGEN_ML'] = $imagenesPorArticulo[$cveArt] ?? []; // Si no hay imágenes, asignar un array vacío

        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function listarImagenesDesdeFirebase($cveArt, $firebaseStorageBucket)
{
    $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?prefix=" . rawurlencode("imagenes/{$cveArt}/");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    $imagenes = [];
    if (isset($data['items'])) {
        foreach ($data['items'] as $item) {
            $imagenes[] = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/" . rawurlencode($item['name']) . "?alt=media";
        }
    }

    return $imagenes;
}
function extraerProducto($conexionData, $claveSae)
{
    if (!isset($_GET['cveArt'])) {
        echo json_encode(['success' => false, 'message' => 'Clave del artículo no proporcionada.']);
        return;
    }

    $cveArt = $_GET['cveArt']; // Clave del artículo proporcionada en la solicitud

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

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta específica para el producto
    $sql = "SELECT 
                [CVE_ART], 
                [DESCR], 
                [EXIST], 
                [LIN_PROD], 
                [UNI_MED]
            FROM $nombreTabla
            WHERE [CVE_ART] = ?";
    $params = [$cveArt];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    $producto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$producto) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        return;
    }

    // Obtener imágenes desde Firebase Storage
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenes = listarImagenesDesdeFirebase($cveArt, $firebaseStorageBucket);

    // Preparar respuesta
    $producto['IMAGENES'] = $imagenes;

    echo json_encode(['success' => true, 'producto' => $producto]);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function subirImagenArticulo($conexionData)
{
    // Verifica que se haya enviado al menos un archivo
    if (!isset($_FILES['imagen']) || empty($_FILES['imagen']['name'])) {
        echo json_encode(['success' => false, 'message' => 'No se pudo subir ninguna imagen.']);
        exit;
    }

    // Verifica que se haya enviado la clave del artículo
    if (!isset($_POST['cveArt'])) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionó la clave del artículo.']);
        exit;
    }

    $cveArt = $_POST['cveArt'];
    $imagenes = $_FILES['imagen'];
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app"; // Cambia esto por tu bucket

    // Subir y procesar cada archivo
    $rutasImagenes = [];
    foreach ($imagenes['tmp_name'] as $index => $tmpName) {
        if ($imagenes['error'][$index] === UPLOAD_ERR_OK) {
            $nombreArchivo = $cveArt . "_" . uniqid() . "_" . basename($imagenes['name'][$index]);
            $rutaFirebase = "imagenes/{$cveArt}/{$nombreArchivo}";
            $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?name=" . urlencode($rutaFirebase);

            // Leer el archivo
            $archivo = file_get_contents($tmpName);

            // Subir el archivo a Firebase Storage
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/octet-stream"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $archivo);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $resultado = json_decode($response, true);

            if (isset($resultado['name'])) {
                $urlPublica = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/{$resultado['name']}?alt=media";
                $rutasImagenes[] = $urlPublica;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir una imagen.', 'response' => $response]);
                exit;
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Imágenes subidas correctamente.', 'imagenes' => $rutasImagenes]);
}
function eliminarImagen($conexionData)
{
    if (!isset($_POST['cveArt']) || !isset($_POST['imageUrl'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        return;
    }

    $cveArt = $_POST['cveArt'];
    $imageUrl = $_POST['imageUrl'];

    // Extraer el `filePath` desde la URL
    $parsedUrl = parse_url($imageUrl);

    if (!isset($parsedUrl['query']) && !isset($parsedUrl['path'])) {
        echo json_encode(['success' => false, 'message' => 'No se pudo obtener la ruta del archivo.']);
        return;
    }

    // Intentar extraer 'name' de la query
    $filePath = null;
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
        $filePath = $queryParams['name'] ?? null;
    }

    // Si no se pudo extraer 'name', limpiar la ruta directamente desde 'path'
    if (!$filePath && isset($parsedUrl['path'])) {
        $filePath = preg_replace('#^/v0/b/[^/]+/o/#', '', urldecode($parsedUrl['path']));
    }

    // Validar el `filePath`
    if (!$filePath || strpos($filePath, 'imagenes/') !== 0) {
        echo json_encode(['success' => false, 'message' => 'El filePath generado es inválido.']);
        return;
    }

    // Construir la URL del archivo en Firebase Storage
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app"; // Bucket correcto
    $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/" . rawurlencode($filePath);

    // Realizar la solicitud DELETE
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Validar la respuesta
    if ($httpCode === 204) {
        echo json_encode(['success' => true, 'message' => 'Imagen eliminada correctamente.']);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar la imagen.',
            'response' => $response,
            'httpCode' => $httpCode
        ]);
    }
}

function generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa)
{
    $rutaPDF = generarReportePedido($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa);
    return $rutaPDF;
}
// -----------------------------------------------------------------------------------------------------//
function guardarPedidoEcomers($conexionData, $formularioData, $partidasData, $claveSae)
{
    /// Establecer la conexión con SQL Server con UTF-8
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
    $claveCliente = $formularioData['cliente'];
    $datosCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae);
    if (!$datosCliente) {
        die(json_encode(['success' => false, 'message' => 'No se encontraron datos del cliente']));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Extraer los datos del formulario
    $FOLIO = $formularioData['numero'];
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $FECHA_DOC = $formularioData['diaAlta']; // Fecha del documento
    $FECHA_ENT = $formularioData['entrega'];
    // Sumar los totales de las partidas
    $CAN_TOT = 0;
    $IMPORTE = 0;
    $DES_TOT = 0; // Variable para el importe con descuento
    $descuentoCliente = $formularioData['descuentoCliente']; // Valor del descuento en porcentaje (ejemplo: 10 para 10%)

    foreach ($partidasData as $partida) {
        $CAN_TOT += $partida['cantidad'] * $partida['precioUnitario']; // Sumar cantidades totales
        $IMPORTE += $partida['cantidad'] * $partida['precioUnitario']; // Calcular importe total
    }

    $DES_TOT = 0; // Inicializar el total con descuento
    $IMPORTT = $IMPORTE;
    $DES = 0;
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**

        $DES = $totalPartida * ($desc1 / 100);
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }

    $CVE_VEND = str_pad($formularioData['claveVendedor'], 5, ' ', STR_PAD_LEFT);
    // Asignación de otros valores del formulario
    $IMP_TOT1 = 0;
    $IMP_TOT2 = 0;
    $IMP_TOT3 = 0;
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;
    $IMP_TOT5 = 0;
    $IMP_TOT6 = 0;
    $IMP_TOT7 = 0;
    $IMP_TOT8 = 0;
    $DES_FIN = 0;
    $CONDICION = $formularioData['condicion'];
    $RFC = $formularioData['rfc'];
    $FECHA_ELAB = $formularioData['diaAlta'];
    //$TIP_DOC = $formularioData['factura'];
    $NUM_ALMA = $formularioData['almacen'];
    $FORMAENVIO = 'C';
    $COM_TOT = $formularioData['comision'];
    $DAT_ENVIO = 1;
    $CVE_OBS = $datosCliente['CVE_OBS'];
    $CVE_BITA = $datosCliente['CVE_BITA'];
    //$COM_TOT_PORC = $datosCliente['COM_TOT_PORC']; //VENDEDOR
    $METODODEPAGO = $datosCliente['METODODEPAGO'];
    $NUMCTAPAGO = $datosCliente['NUMCTAPAGO'];
    $FORMADEPAGOSAT = $datosCliente['FORMADEPAGOSAT'];
    $USO_CFDI = $datosCliente['USO_CFDI'];
    $REG_FISC = $datosCliente['REG_FISC'];
    $ENLAZADO = 'O'; ////
    $TIP_DOC_E = 0; ////
    $DES_TOT_PORC = $formularioData['descuentoCliente'];; ////
    $COM_TOT_PORC = 0; ////
    $FECHAELAB = new DateTime("now", new DateTimeZone('America/Mexico_City'));
    $claveArray = explode(' ', $claveCliente, 2); // Limitar a dos elementos
    $clave = $claveArray[0];
    $CVE_CLPV = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    // Crear la consulta SQL para insertar los datos en la base de datos
    $sql = "INSERT INTO $nombreTabla
    (TIP_DOC, CVE_DOC, CVE_CLPV, STATUS, DAT_MOSTR,
    CVE_VEND, CVE_PEDI, FECHA_DOC, FECHA_ENT, FECHA_VEN, FECHA_CANCELA, CAN_TOT,
    IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8,
    DES_TOT, DES_FIN, COM_TOT, CONDICION, CVE_OBS, NUM_ALMA, ACT_CXC, ACT_COI, ENLAZADO,
    TIP_DOC_E, NUM_MONED, TIPCAMB, NUM_PAGOS, FECHAELAB, PRIMERPAGO, RFC, CTLPOL, ESCFD, AUTORIZA,
    SERIE, FOLIO, AUTOANIO, DAT_ENVIO, CONTADO, CVE_BITA, BLOQ, FORMAENVIO, DES_FIN_PORC, DES_TOT_PORC,
    IMPORTE, COM_TOT_PORC, METODODEPAGO, NUMCTAPAGO,
    VERSION_SINC, FORMADEPAGOSAT, USO_CFDI, TIP_TRASLADO, TIP_FAC, REG_FISC
    ) 
    VALUES 
    ('P', ?, ?, 'E', 0, 
    ?, '', ?, ?, ?, '', ?,
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, 'S', 'N', ?,
    ?, 1, 1, 1, ?, 0, ?, 0, 'N', 1,
    '', ?, '', ?, 'N', ?, 'N', 'C', 0, ?,
    ?, ?, ?, ?,
    '', ?, ?, '', '', ?)";
    // Preparar los parámetros para la consulta
    $params = [
        $CVE_DOC,
        $CVE_CLPV,
        $CVE_VEND,
        $FECHA_DOC,
        $FECHA_ENT,
        $FECHA_DOC,
        $CAN_TOT,
        $IMP_TOT1,
        $IMP_TOT2,
        $IMP_TOT3,
        $IMP_TOT4,
        $IMP_TOT5,
        $IMP_TOT6,
        $IMP_TOT7,
        $IMP_TOT8,
        $DES_TOT,
        $DES_FIN,
        $COM_TOT,
        $CONDICION,
        $CVE_OBS,
        $NUM_ALMA,
        $ENLAZADO,
        $TIP_DOC_E,
        $FECHAELAB,
        $RFC,
        $FOLIO,
        $DAT_ENVIO,
        $CVE_BITA,
        $DES_TOT_PORC,
        $IMPORTE,
        $COM_TOT_PORC,
        $METODODEPAGO,
        $NUMCTAPAGO,
        $FORMADEPAGOSAT,
        $USO_CFDI,
        $REG_FISC
    ];
    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al guardar el pedido',
            'sql_error' => sqlsrv_errors() // Captura los errores de SQL Server
        ]));
    } else {
        // echo json_encode(['success' => true, 'message' => 'Pedido guardado con éxito']);
    }
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function guardarPartidasEcomers($conexionData, $formularioData, $partidasData, $claveSae)
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

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Iniciar la transacción
    sqlsrv_begin_transaction($conn);

    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // **Obtener el último `NUM_PAR` de la base de datos**
    $query = "SELECT MAX(NUM_PAR) AS NUM_PAR FROM $nombreTabla WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    $NUM_PAR = ($row && $row['NUM_PAR']) ? $row['NUM_PAR'] + 1 : 1; // Si no hay partidas, empieza desde 1

    if (isset($partidasData) && is_array($partidasData)) {
        foreach ($partidasData as $partida) {
            $CVE_ART = $partida['producto'];
            $CANT = $partida['cantidad'];
            $PREC = $partida['precioUnitario'];
            $IMPU1 = $partida['ieps'];
            $IMPU3 = $partida['isr'];
            $IMPU2 = 0;
            $IMPU4 = $partida['iva'];
            $PXS = 0;
            $DESC1 = $partida['descuento'];
            $DESC2 = $partida['descuento2'];
            $COMI = $partida['comision'];
            $CVE_UNIDAD = $partida['CVE_UNIDAD'];
            $COSTO_PROM = $partida['COSTO_PROM'];
            $NUM_ALMA = $formularioData['almacen'];
            $UNI_VENTA = $partida['unidad'];
            $TIPO_PORD = ($UNI_VENTA === 'No aplica' || $UNI_VENTA === 'SERVICIO' || $UNI_VENTA === 'Servicio') ? 'S' : 'P';
            $TOT_PARTIDA = $PREC * $CANT;
            $TOTIMP1 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU1 / 100);
            $TOTIMP2 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU2 / 100);
            $TOTIMP4 = ($TOT_PARTIDA - ($TOT_PARTIDA * ($DESC1 / 100))) * ($IMPU4 / 100);

            // **Obtener la descripción del producto**

            // **INSERTAR PARTIDA**
            $sql = "INSERT INTO $nombreTabla
                (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA,
                TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4,
                DESC1, DESC2, DESC3, COMI, APAR,
                ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, TIPO_PROD, CVE_OBS, REG_SERIE, E_LTPD, TIPO_ELEM, 
                NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, UUID,
                VERSION_SINC, ID_RELACION, PREC_NETO,
                CVE_PRODSERV, CVE_UNIDAD, IMPU8, IMPU7, IMPU6, IMPU5, IMP5APLA,
                IMP6APLA, TOTIMP8, TOTIMP7, TOTIMP6, TOTIMP5, IMP8APLA, IMP7APLA)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 4, 4, 4, 4,
                ?, ?, 0, ?,
                ?, ?, 0, 0, ?,
                'N', ?, '', 1, ?, ?, 0, 0, 0, 'N',
                0, ?, 'S', 'N', 0, 0, 0, 0, 0, 0, '',
                0, '', '',
                0, ?, '', 0, 0, 0, 0,
                0, 0, 0, 0, 0, 0, 0)";

            $params = [
                $CVE_DOC,
                $NUM_PAR,
                $CVE_ART,
                $CANT,
                $PXS,
                $PREC,
                $COSTO_PROM,
                $IMPU1,
                $IMPU2,
                $IMPU3,
                $IMPU4,
                $TOTIMP1,
                $TOTIMP2,
                $TOTIMP4,
                $DESC1,
                $DESC2,
                $COMI,
                $NUM_ALMA,
                $UNI_VENTA,
                $TIPO_PORD,
                $TOT_PARTIDA,
                $CVE_UNIDAD
            ];

            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                var_dump(sqlsrv_errors()); // Muestra errores específicos
                sqlsrv_rollback($conn);
                die(json_encode(['success' => false, 'message' => 'Error al insertar la partida', 'errors' => sqlsrv_errors()]));
            }

            $NUM_PAR++; // **Incrementar NUM_PAR después de cada inserción**
        }
    } else {
        die(json_encode(['success' => false, 'message' => 'Error: partidasData no es un array válido']));
    }

    // Confirmar la transacción
    sqlsrv_commit($conn);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function actualizarInventarioEcomers($conexionData, $partidasData, $claveSae)
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['producto'];
        $cantidad = $partida['cantidad'];
        // SQL para actualizar los campos EXIST y PEND_SURT
        $sql = "UPDATE $nombreTabla
            SET    
                [APART] = [APART] + ?   
            WHERE [CVE_ART] = '$CVE_ART'";
        // Preparar la consulta
        $params = array($cantidad, $cantidad);

        // Ejecutar la consulta SQL
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al actualizar el inventario', 'errors' => sqlsrv_errors()]));
        }
        // Verificar cuántas filas se han afectado
        $rowsAffected = sqlsrv_rows_affected($stmt);
        // Retornar el resultado
        if ($rowsAffected > 0) {
            // echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el producto para actualizar']);
        }
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function remision($conexionData, $formularioData, $partidasData, $claveSae, $noEmpresa)
{
    $numFuncion = '1';
    $pedidoId = $formularioData['numero'];
    $vendedor = $formularioData['claveVendedor'];

    // URL del servidor donde se ejecutará la remisión
    $remisionUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/remision.php";
    //$remisionUrl = 'http://localhost/MDConnecta/Servidor/PHP/remision.php';

    // Datos a enviar a la API de remisión
    $data = [
        'numFuncion' => $numFuncion,
        'pedidoId' => $pedidoId,
        'claveSae' => $claveSae,
        'noEmpresa' => $noEmpresa,
        'vendedor' => $vendedor
    ];

    // Inicializa cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remisionUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    // Ejecutar la petición y capturar la respuesta
    $remisionResponse = curl_exec($ch);

    // Verificar errores en cURL
    if (curl_errno($ch)) {
        echo 'Error cURL: ' . curl_error($ch);
        curl_close($ch);
        return;
    }

    // Obtener tipo de contenido antes de cerrar cURL
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($remisionResponse) {
        // Intenta decodificar como JSON
        $remisionData = json_decode($remisionResponse, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($remisionData['cveDoc'])) {
            // ✅ La respuesta es un JSON con cveDoc (Pedido procesado correctamente)
            echo "<div class='container'>
                    <div class='title'>Confirmación Exitosa</div>
                    <div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>
                    <a href='/Menu.php' class='button'>Regresar al inicio</a>
                  </div>";
        } elseif (strpos($contentType, 'application/pdf') !== false) {
            // ✅ La respuesta es un PDF (Guardar y abrir)
            $pdfPath = "remision.pdf";
            file_put_contents($pdfPath, $remisionResponse);
            echo "<script>window.open('$pdfPath', '_blank');</script>";
        } else {
        }
    } else {
        // ❌ No hubo respuesta
        echo "<div class='container error'>
                <div class='title'>Confirmación Fallida</div>
                <div class='message'>El pedido falló. No se recibió respuesta del servidor.</div>
                <a href='/Menu.php' class='button'>Regresar al inicio</a>
              </div>";
    }
}
function validarCorreoClienteEcomers($formularioData, $partidasData, $conexionData, $rutaPDF, $claveSae, $noEmpresa)
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
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $formularioData['enviar']; // Dirección de envío
    $vendedor = $formularioData['vendedor']; // Número de vendedor
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $formularioData['numero']; // Número de pedido
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener MAIL y EMAILPRED
    $sql = "SELECT MAIL, EMAILPRED, NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [$clave];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['producto'];

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

    $fechaElaboracion = $formularioData['diaAlta'];
    $correo = trim($clienteData['MAIL']);
    $emailPred = trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    // Si hay múltiples correos separados por `;`, tomar solo el primero
    //$emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    //$emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    //$numeroWhatsApp = trim($clienteData['TELEFONO']);

    $clienteNombre = trim($clienteData['NOMBRE']);

    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';*/
    $emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/ // Interzenda

    if ($correo === 'S' && !empty($emailPred)) {
        enviarCorreoEcomers($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conexionData); // Enviar correo
        $conCredito = "S";
        $resultadoWhatsApp = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito);
    } else {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene un correo electrónico válido registrado.']);
        die();
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function enviarCorreoEcomers($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conexionData)
{
    // Crear una instancia de la clase clsMail
    $mail = new clsMail();

    // Definir el remitente (si no está definido, se usa uno por defecto)
    $correoRemitente = $_SESSION['usuario']['correo'] ?? "";
    $contraseñaRemitente = $_SESSION['empresa']['contrasena'] ?? "";

    if ($correoRemitente == "" || $contraseñaRemitente == "") {
        $correoRemitente = "";
        $contraseñaRemitente = "";
    }

    // Definir el correo de destino (puedes cambiarlo si es necesario)
    $correoDestino = $correo;

    $vendedor = obtenerNombreVendedor($vendedor, $conexionData, $claveSae);
    // Obtener el nombre de la empresa desde la sesión
    $titulo = 'Sun Arrow';

    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // Convertir productos a JSON para la URL
    $productosJson = urlencode(json_encode($partidasData));

    // URL base del servidor
    $urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "http://localhost/MDConnecta/Servidor/PHP";

    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae);

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los detalles de su pedido <b>$noPedido</b>. Por favor, revíselos y confirme:</p>";
    $bodyHTML .= "<p><b>Fecha y Hora de Elaboración:</b> $fechaElaboracion</p>";
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
        $clave = htmlspecialchars($partida['producto']);
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = htmlspecialchars($partida['cantidad']);
        $totalPartida = $cantidad * $partida['precioUnitario'];
        $total += $totalPartida;
        $IMPORTE = $total;

        $bodyHTML .= "<tr>
                        <td style='text-align: center;'>$clave</td>
                        <td>$descripcion</td>
                        <td style='text-align: right;'>$cantidad</td>
                        <td style='text-align: right;'>$" . number_format($totalPartida, 2) . "</td>
                      </tr>";

        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0;
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total:</b> $" . number_format($IMPORTE, 2) . "</p>";

    // Botones para confirmar o rechazar el pedido
    $bodyHTML .= "<p>Confirme su pedido seleccionando una opción:</p>
                  <a href='$urlConfirmar' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar</a>
                  <a href='$urlRechazar' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Rechazar</a>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente);

    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
}
function validarSaldo($conexionData, $clave, $claveSae)
{
    // Establecer los datos de conexión
    $serverName = $conexionData['host'];
    $database = $conexionData['nombreBase'];
    $username = $conexionData['usuario'];
    $password = $conexionData['password'];

    try {
        // Conectar usando PDO con TrustServerCertificate habilitado
        $dsn = "sqlsrv:server=$serverName;Database=$database;TrustServerCertificate=true";
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $tablaCuenM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaCuenD = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaMon = "[{$conexionData['nombreBase']}].[dbo].[MONED" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        // Consulta SQL para verificar saldo vencido
        $sql = "
            SELECT TOP 1 1 
            FROM $tablaCuenM CUENM  
            LEFT JOIN $tablaClie CLIENTES ON CLIENTES.CLAVE = CUENM.CVE_CLIE
            LEFT JOIN $tablaCuenD CUEN ON CUEN.CVE_CLIE = CUENM.CVE_CLIE
                AND CUEN.REFER = CUENM.REFER
                AND CUEN.NUM_CARGO = CUENM.NUM_CARGO
            LEFT JOIN $tablaMon MON ON CUENM.NUM_MONED = MON.NUM_MONED    
            WHERE CUENM.FECHA_VENC < GETDATE()  -- Solo cuentas vencidas
                AND CLIENTES.STATUS <> 'B'     -- Excluir clientes inactivos
                AND CLIENTES.CLAVE = :claveCliente
                AND (
                    ISNULL((SELECT SUM(IMPORTE) FROM $tablaCuenD WHERE CVE_CLIE = CUENM.CVE_CLIE AND REFER = CUENM.REFER AND NUM_CARGO = CUENM.NUM_CARGO), 0) = 0
                    OR ISNULL((SELECT SUM(IMPORTE) FROM $tablaCuenD WHERE CVE_CLIE = CUENM.CVE_CLIE AND REFER = CUENM.REFER AND NUM_CARGO = CUENM.NUM_CARGO), 0) < CUENM.IMPORTE
                )
        ";

        // Preparar y ejecutar la consulta
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':claveCliente', $clave, PDO::PARAM_STR);
        $stmt->execute();

        // Si encuentra al menos un registro con estado 'VENCIDA' o 'VENCIDA PARCIAL', retorna 1
        return $stmt->fetchColumn() ? 1 : 0;
    } catch (PDOException $e) {
        // Manejo de errores
        echo "Error de conexión: " . $e->getMessage();
        return -1; // Código de error
    }
}
function guardarPedidoAutorizado($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Validar que se cuente con los datos mínimos requeridos
    if (empty($formularioData['numero']) || empty($formularioData['cliente'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos del pedido.']);
        return;
    }
    // Agregar la descripción del producto a cada partida
    foreach ($partidasData as &$partida) {  // 🔹 Pasar por referencia para modificar el array
        $CVE_ART = $partida['producto'];
        $partida['descripcion'] = obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae);
    }
    // Preparar los campos que se guardarán en Firebase
    $fields = [
        'folio'     => ['stringValue' => $formularioData['numero']],
        'cliente'    => ['stringValue' => $formularioData['cliente']],
        'enviar'     => ['stringValue' => isset($formularioData['enviar']) ? $formularioData['enviar'] : ''],
        'vendedor'   => ['stringValue' => isset($formularioData['vendedor']) ? $formularioData['vendedor'] : ''],
        'diaAlta'    => ['stringValue' => isset($formularioData['fechaAlta']) ? $formularioData['fechaAlta'] : ''],
        //'partidas'   => ['stringValue' => json_encode($partidasData)], // Guardamos las partidas como JSON
        "partidas" => [
            "arrayValue" => [
                "values" => array_map(function ($partidasData) {
                    return [
                        "mapValue" => [
                            "fields" => [
                                "cantidad" => ["stringValue" => $partidasData["cantidad"]],
                                "producto" => ["stringValue" => $partidasData["producto"]],
                                "unidad" => ["stringValue" => $partidasData["unidad"]],
                                "descuento" => ["stringValue" => $partidasData["descuento"]],
                                "ieps" => ["stringValue" => $partidasData["ieps"]],
                                "impuesto2" => ["stringValue" => $partidasData["impuesto2"]],
                                "isr" => ["stringValue" => $partidasData["isr"]],
                                "iva" => ["stringValue" => $partidasData["iva"]],
                                "comision" => ["stringValue" => $partidasData["comision"]],
                                "precioUnitario" => ["stringValue" => $partidasData["precioUnitario"]],
                                "subtotal" => ["stringValue" => $partidasData["subtotal"]],
                                "descripcion" => ["stringValue" => $partidasData["descripcion"]],
                            ]
                        ]
                    ];
                }, $partidasData)
            ]
        ],
        'claveSae'   => ['stringValue' => $claveSae],
        'noEmpresa'  => ['integerValue' => $noEmpresa],
        'status' => ['stringValue' => 'Sin Autorizar']
    ];

    // URL para crear un nuevo documento en la colección PEDIDOS_AUTORIZAR
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR?key=$firebaseApiKey";

    // Crear el payload a enviar
    $payload = json_encode(['fields' => $fields]);

    // Configurar las opciones HTTP para el POST
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ],
    ];
    $context = stream_context_create($options);

    // Ejecutar la petición a Firebase
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el pedido autorizado en Firebase.']);
        return;
    }

    //echo json_encode(['success' => true, 'message' => 'Pedido guardado y en espera de ser autorizado.']);
}
function buscarAnticipo($conexionData, $formularioData, $claveSae, $totalPedido)
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

    $cliente = $formularioData['cliente'];
    $cliente = formatearClaveCliente($cliente);

    $tablaCunetM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaCunetDet = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT M.REFER, M.NUM_CPTO, M.IMPORTE, M.NO_FACTURA, M.DOCTO, 
        CONVERT(VARCHAR(10), M.FECHA_VENC, 120) AS fecha
        FROM $tablaCunetM M 
        INNER JOIN $tablaCunetDet D 
        ON M.REFER = D.REFER 
            AND M.NO_FACTURA = D.NO_FACTURA
        WHERE M.CVE_CLIE = ? 
        AND M.NUM_CPTO = '9'";
    $params = [$cliente];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar el cliente Anticipo',
            'errors' => sqlsrv_errors()
        ]));
    }
    $clienteCxC = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$clienteCxC) {
        sqlsrv_close($conn);
        return [
            'success' => false,
            'sinFondo' => true,
        ];
    }

    $REFER = $clienteCxC['REFER'];
    $IMPORTE = $clienteCxC['IMPORTE'];
    $NO_FACTURA = $clienteCxC['NO_FACTURA'];
    $fechaVencimiento = $clienteCxC['fecha'];
    $DOCTO = $clienteCxC['DOCTO'];

    // Asegurarse de que $fechaVencimiento es un objeto DateTime.
    /*if (!($fechaVencimiento instanceof DateTime)) {
        // Asumimos que el formato en la BD es "Y-m-d H:i:s"
        $fechaVencimiento = DateTime::createFromFormat("Y-m-d", $fechaVencimiento);
    }*/

    // Obtener la fecha actual
    $fechaActual = new DateTime();

    // Comparar solo la parte de la fecha (Y-m-d) para evitar diferencias por hora o zona
    $fechaActualStr = $fechaActual->format('Y-m-d');
    /*var_dump($fechaActualStr);
    var_dump($fechaVencimiento);*/

    /*if ($fechaActualStr > $fechaVencimiento) {
        sqlsrv_close($conn);
        return [
            'success' => false,
            'fechaVencimiento' => true,
            'sinFondo' => false,
            'anticipoVencimiento' => true,
            'message' => 'El anticipo tiene una fecha futura y no puede ser utilizado'
        ];
        //die();
    }*/
    $puedeContinuar = ($totalPedido) <= $IMPORTE;
    if ($puedeContinuar) {
        $fondo = false;
    } else {
        $fondo = true;
    }

    /*$fechaVencimiento = false;
    $puedeContinuar = true;*/
    sqlsrv_close($conn);

    // Devolver el resultado y los datos relevantes
    return [
        'success' => $puedeContinuar,
        'sinFondo' => $fondo,
        'IMPORTE' => $IMPORTE,
        'subTotal' => $totalPedido,
        'Vencimiento' => $fechaVencimiento,
        'Referencia' => $REFER,
        'NO_FACTURA' => $NO_FACTURA,
        'DOCTO' => $DOCTO
    ];
}
function guardarPago($conexionData, $formularioData, $partidasData, $claveSae, $noEmpresa)
{
    global $firebaseProjectId, $firebaseApiKey;

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

    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México
    $vendedor = $formularioData['vendedor'];
    $fechaCreacion = date("Y-m-d H:i:s"); // Fecha y hora actual
    $fechaLimite = date("Y-m-d H:i:s", strtotime($fechaCreacion . ' + 1 day')); // Suma 24 horas


    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS?key=$firebaseApiKey";
    $fields = [
        'folio'     => ['stringValue' => $formularioData['numero']],
        'cliente'    => ['stringValue' => $formularioData['cliente']],
        'claveSae'   => ['stringValue' => $claveSae],
        'noEmpresa'  => ['integerValue' => $noEmpresa],
        'status' => ['stringValue' => 'Sin Pagar'],
        'creacion' => ['stringValue' => $fechaCreacion],
        'limite' => ['stringValue' => $fechaLimite],
        'vendedor' => ['stringValue' => $vendedor],
        'buscar' => ['booleanValue' => false]
    ];

    $payload = json_encode(['fields' => $fields]);

    // Configurar las opciones HTTP para el POST
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ],
    ];
    $context = stream_context_create($options);

    // Ejecutar la petición a Firebase
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el pedido autorizado en Firebase.']);
        return;
    }
}
function nuevoFolio($conexionData, $claveSae)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta SQL para obtener el siguiente folio
    $sql = "SELECT (ULT_DOC + 1) AS FolioSiguiente FROM $nombreTabla WHERE TIP_DOC = 'F' AND [SERIE] = 'STAND.'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener el siguiente folio
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $folioSiguiente = $row ? $row['FolioSiguiente'] : null;
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    // Retornar el folio siguiente
    return $folioSiguiente;
}
function generarCuentaPorCobrar($conexionData, $formularioData, $claveSae, $partidasData)
{
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México

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

    $folio = nuevoFolio($conexionData, $claveSae);
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    $tablaCunetM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    //FALTA FACTURA

    // Preparar los datos para el INSERT
    $cve_clie   = $formularioData['cliente']; // Clave del cliente
    $CVE_CLIE = formatearClaveCliente($cve_clie);
    $refer      = $CVE_DOC; // Puede generarse o venir del formulario
    $num_cpto   = '9';  // Concepto: ajustar según tu lógica de negocio
    $num_cargo  = 1;    // Número de cargo: un valor de ejemplo
    $no_factura = $CVE_DOC; // Número de factura o pedido
    $docto = $CVE_DOC;   // Puede ser un código de documento, si aplica
    $IMPORTE = 0;
    $STRCVEVEND = $formularioData['claveVendedor'];



    $AFEC_COI = 'A';
    $NUM_MONED = 1;
    $TCAMBIO = 1;
    $TIPO_MOV = 'A'; //Aqui

    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    $total = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        $total += $totalPartida;
        $IMPORTE = $total;
        // **Aplicar los descuentos en cascada**
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    $fecha_apli = date("Y-m-d 00:00:00.000");         // Fecha de aplicación: ahora
    $fecha_venc = date("Y-m-d 00:00:00.000", strtotime($fecha_apli . ' + 1 day')); // Vencimiento a 24 horas
    $status     = 'A';  // Estado inicial, por ejemplo
    $USUARIO    = '0';
    $IMPMON_EXT = $IMPORTE;
    $SIGNO = 1;
    // Preparar el query INSERT (ajusta los campos según la estructura real de tu tabla)
    $query = "INSERT INTO $tablaCunetM (
        CVE_CLIE, 
        REFER, 
        NUM_CPTO, 
        NUM_CARGO, 
        NO_FACTURA, 
        DOCTO, 
        IMPORTE, 
        FECHA_APLI, 
        FECHA_VENC,
        STATUS,
        USUARIO,
        AFEC_COI,
        NUM_MONED,
        TCAMBIO,
        TIPO_MOV,
        FECHA_ENTREGA,
        IMPMON_EXT,
        UUID,
        VERSION_SINC,
        USUARIOGL,
        FECHAELAB,
        SIGNO,
        STRCVEVEND
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, 0, ?, ?, ?)";

    $params = [
        $CVE_CLIE,
        $refer,
        $num_cpto,
        $num_cargo,
        $no_factura,
        $docto,
        $IMPORTE,
        $fecha_apli,
        $fecha_venc,
        $status,
        $USUARIO,
        $AFEC_COI,
        $NUM_MONED,
        $TCAMBIO,
        $TIPO_MOV,
        $fecha_apli,
        $IMPMON_EXT,
        $fecha_apli,
        $fecha_apli,
        $SIGNO,
        $STRCVEVEND
    ];

    $stmt = sqlsrv_query($conn, $query, $params);
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        sqlsrv_close($conn);
        return [
            'success' => false,
            'message' => 'Error al insertar la cuenta por cobrar',
            'errors' => $errors
        ];
    }

    sqlsrv_close($conn);
    return $no_factura;
}
function eliminarCxc($conexionData, $anticipo, $claveSae)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    // Conectar a la base de datos
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Construir los nombres de las tablas
    $tablaCunetM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaCunetDet = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    /*$referencia = $formularioData['referencia'] ?? '000001'; // Puede generarse o venir del formulario
    $factura = $formularioData['numero']; // Número de factura o pedido*/
    /*$referencia = '000001';
    $factura = '18784';*/
    $referencia = $anticipo['Referencia'];
    $factura = $anticipo['NO_FACTURA'];
    // Iniciar una transacción
    sqlsrv_begin_transaction($conn);

    try {
        // Eliminar de la tabla CUEN_M
        $sqlCunetM = "DELETE FROM $tablaCunetM WHERE [REFER] = ? AND [NO_FACTURA] = ?";
        //$params = [$anticipo['Referencia'], $anticipo['NO_FACTURA']];
        $params = [$referencia, $factura];
        $stmtCunetM = sqlsrv_prepare($conn, $sqlCunetM, $params);
        if ($stmtCunetM === false) {
            throw new Exception('Error al preparar la consulta para $tablaCunetM: ' . print_r(sqlsrv_errors(), true));
        }
        if (!sqlsrv_execute($stmtCunetM)) {
            throw new Exception('Error al ejecutar la consulta para $tablaCunetM: ' . print_r(sqlsrv_errors(), true));
        }

        // Eliminar de la tabla CUEN_Det
        /*$sqlCunetDet = "DELETE FROM $tablaCunetDet WHERE [REFER] = ? AND [NO_FACTURA] = ?";
        $stmtCunetDet = sqlsrv_prepare($conn, $sqlCunetDet, $params);*/
        /*if ($stmtCunetDet === false) {
            throw new Exception('Error al preparar la consulta para $tablaCunetDet: ' . print_r(sqlsrv_errors(), true));
        }
        if (!sqlsrv_execute($stmtCunetDet)) {
            throw new Exception('Error al ejecutar la consulta para $tablaCunetDet: ' . print_r(sqlsrv_errors(), true));
        }*/

        // Confirmar la transacción
        sqlsrv_commit($conn);
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        sqlsrv_rollback($conn);
        die(json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]));
    }

    // Liberar recursos y cerrar conexión
    if (isset($stmtCunetM)) sqlsrv_free_stmt($stmtCunetM);
    //if (isset($stmtCunetDet)) sqlsrv_free_stmt($stmtCunetDet);
    sqlsrv_close($conn);
}
function eliminarCxCBanco($conexionData, $anticipo, $claveSae)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    // Conectar a la base de datos
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Construir los nombres de las tablas
    $tablaCunetM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaCunetDet = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    /*$referencia = $formularioData['referencia'] ?? '000001'; // Puede generarse o venir del formulario
    $factura = $formularioData['numero']; // Número de factura o pedido*/
    /*$referencia = '000001';
    $factura = '18784';*/
    $referencia = $anticipo['Referencia'];
    $factura = $anticipo['NO_FACTURA'];
    // Iniciar una transacción
    sqlsrv_begin_transaction($conn);

    try {
        // Eliminar de la tabla CUEN_M
        $sqlCunetM = "DELETE FROM $tablaCunetM WHERE [REFER] = ? AND [NO_FACTURA] = ?";
        //$params = [$anticipo['Referencia'], $anticipo['NO_FACTURA']];
        $params = [$referencia, $factura];
        $stmtCunetM = sqlsrv_prepare($conn, $sqlCunetM, $params);
        if ($stmtCunetM === false) {
            throw new Exception('Error al preparar la consulta para $tablaCunetM: ' . print_r(sqlsrv_errors(), true));
        }
        if (!sqlsrv_execute($stmtCunetM)) {
            throw new Exception('Error al ejecutar la consulta para $tablaCunetM: ' . print_r(sqlsrv_errors(), true));
        }

        // Eliminar de la tabla CUEN_Det
        /*$sqlCunetDet = "DELETE FROM $tablaCunetDet WHERE [REFER] = ? AND [NO_FACTURA] = ?";
        $stmtCunetDet = sqlsrv_prepare($conn, $sqlCunetDet, $params);*/
        /*if ($stmtCunetDet === false) {
            throw new Exception('Error al preparar la consulta para $tablaCunetDet: ' . print_r(sqlsrv_errors(), true));
        }
        if (!sqlsrv_execute($stmtCunetDet)) {
            throw new Exception('Error al ejecutar la consulta para $tablaCunetDet: ' . print_r(sqlsrv_errors(), true));
        }*/

        // Confirmar la transacción
        sqlsrv_commit($conn);
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        sqlsrv_rollback($conn);
        die(json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]));
    }

    // Liberar recursos y cerrar conexión
    if (isset($stmtCunetM)) sqlsrv_free_stmt($stmtCunetM);
    //if (isset($stmtCunetDet)) sqlsrv_free_stmt($stmtCunetDet);
    sqlsrv_close($conn);
}
function crearCxc($conexionData, $claveSae, $formularioData, $partidasData)
{
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México

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
    $tablaCunetM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    //FALTA FACTURA
    $folio = nuevoFolio($conexionData, $claveSae);
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // Preparar los datos para el INSERT
    $cve_clie   = $formularioData['cliente']; // Clave del cliente
    $CVE_CLIE = formatearClaveCliente($cve_clie);
    $refer      = $CVE_DOC; // Puede generarse o venir del formulario
    $num_cpto   = '1';  // Concepto: ajustar según tu lógica de negocio
    $num_cargo  = 1;    // Número de cargo: un valor de ejemplo
    $no_factura = $CVE_DOC; // Número de factura o pedido
    $docto = $CVE_DOC;   // Puede ser un código de documento, si aplica
    $IMPORTE = 0;
    $STRCVEVEND = $formularioData['claveVendedor'];

    $AFEC_COI = 'A';
    $NUM_MONED = 1;
    $TCAMBIO = 1;
    $TIPO_MOV = 'A'; //Aqui

    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    $fecha_apli = date("Y-m-d 00:00:00.000");         // Fecha de aplicación: ahora
    $fecha_venc = date("Y-m-d 00:00:00.000", strtotime($fecha_apli . ' + 1 day')); // Vencimiento a 24 horas
    $status     = 'A';  // Estado inicial, por ejemplo
    $USUARIO    = '0';
    $IMPMON_EXT = $IMPORTE;
    $SIGNO = 1;


    // Preparar el query INSERT (ajusta los campos según la estructura real de tu tabla)
    $query = "INSERT INTO $tablaCunetM (
                    CVE_CLIE, 
                    REFER, 
                    NUM_CPTO, 
                    NUM_CARGO, 
                    NO_FACTURA, 
                    DOCTO, 
                    IMPORTE, 
                    FECHA_APLI, 
                    FECHA_VENC,
                    STATUS,
                    USUARIO,
                    AFEC_COI,
                    NUM_MONED,
                    TCAMBIO,
                    TIPO_MOV,
                    FECHA_ENTREGA,
                    IMPMON_EXT,
                    UUID,
                    VERSION_SINC,
                    USUARIOGL,
                    FECHAELAB,
                    IMPMON_EXT,
                    SIGNO,
                    STRCVEVEND
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, 0, ?, ?, ?, ?)";

    $params = [
        $CVE_CLIE,
        $refer,
        $num_cpto,
        $num_cargo,
        $no_factura,
        $docto,
        $IMPORTE,
        $fecha_apli,
        $fecha_venc,
        $status,
        $USUARIO,
        $AFEC_COI,
        $NUM_MONED,
        $TCAMBIO,
        $TIPO_MOV,
        $fecha_apli,
        $IMPORTE,
        $fecha_apli,
        $fecha_apli,
        $IMPMON_EXT,
        $SIGNO,
        $STRCVEVEND
    ];

    $stmt = sqlsrv_query($conn, $query, $params);
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        sqlsrv_close($conn);
        return [
            'success' => false,
            'message' => 'Error al insertar la cuenta por cobrar',
            'errors' => $errors
        ];
    }

    sqlsrv_close($conn);
    return [
        'factura' => $no_factura,
        'referencia' => $refer,
        'importe' => $IMPORTE
    ];
}
function pagarCxc($conexionData, $claveSae, $datosCxC, $formularioData, $partidasData)
{
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México

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
    $tablaCunetDet = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $folio = nuevoFolio($conexionData, $claveSae);
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // Preparar los datos para el INSERT
    $cve_clie   = $formularioData['cliente']; // Clave del cliente
    $CVE_CLIE = formatearClaveCliente($cve_clie);
    $refer      = $CVE_DOC; // Puede generarse o venir del formulario
    $num_cpto   = '22';  // Concepto: ajustar según tu lógica de negocio
    $num_cargo  = 1;    // Número de cargo: un valor de ejemplo
    $no_factura = $CVE_DOC; // Número de factura o pedido
    $docto = $CVE_DOC;   // Puede ser un código de documento, si aplica
    $IMPORTE = 0;
    $STRCVEVEND = $formularioData['claveVendedor'];

    $AFEC_COI = 'A';
    $NUM_MONED = 1;
    $TCAMBIO = 1;
    $TIPO_MOV = 'A'; //Aqui

    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    $fecha_apli = date("Y-m-d 00:00:00.000");         // Fecha de aplicación: ahora
    $fecha_venc = date("Y-m-d 00:00:00.000", strtotime($fecha_apli . ' + 1 day')); // Vencimiento a 24 horas
    $status     = 'A';  // Estado inicial, por ejemplo
    $USUARIO    = '0';
    $IMPMON_EXT = $IMPORTE;
    $SIGNO = 1;

    // Preparar el query INSERT (ajusta los campos según la estructura real de tu tabla)
    $query = "INSERT INTO $tablaCunetDet (
                    CVE_CLIE, 
                    REFER, 
                    NUM_CPTO, 
                    NUM_CARGO, 
                    NO_FACTURA, 
                    DOCTO, 
                    IMPORTE, 
                    FECHA_APLI, 
                    FECHA_VENC,
                    STATUS,
                    USUARIO,
                    AFEC_COI,
                    NUM_MONED,
                    TCAMBIO,
                    TIPO_MOV,
                    FECHA_ENTREGA,
                    IMPMON_EXT,
                    UUID,
                    VERSION_SINC,
                    USUARIOGL,
                    FECHAELAB,
                    IMPMON_EXT,
                    SIGNO,
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, 0, ?, ?, ?, ?)";

    $params = [
        $CVE_CLIE,
        $refer,
        $num_cpto,
        $num_cargo,
        $no_factura,
        $docto,
        $IMPORTE,
        $fecha_apli,
        $fecha_venc,
        $status,
        $USUARIO,
        $AFEC_COI,
        $NUM_MONED,
        $TCAMBIO,
        $TIPO_MOV,
        $fecha_apli,
        $IMPORTE,
        $fecha_apli,
        $fecha_apli,
        $IMPMON_EXT,
        $SIGNO,
        $STRCVEVEND
    ];
    //var_dump("de salida");
    $stmt = sqlsrv_query($conn, $query, $params);
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        var_dump($errors);
        sqlsrv_close($conn);
        return [
            'success' => false,
            'message' => 'Error al insertar en CUEN_DET',
            'errors' => $errors
        ];
    }
    sqlsrv_close($conn);
    //echo json_encode(['success' => true, 'message' => 'CxC creada y pagada.']);
    return;
}
function validarCorreoClienteConfirmacion($formularioData, $partidasData, $conexionData, $rutaPDF, $conCredito)
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
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $formularioData['enviar']; // Dirección de envío
    $vendedor = $formularioData['vendedor']; // Número de vendedor
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $formularioData['numero']; // Número de pedido
    /*$claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
    $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);*/

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener MAIL y EMAILPRED
    $sql = "SELECT MAIL, EMAILPRED, NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [$clave];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['producto'];

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

    $fechaElaboracion = $formularioData['fechaAlta'];
    $correo = trim($clienteData['MAIL']);
    $emailPred = trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    // Si hay múltiples correos separados por `;`, tomar solo el primero
    //$emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    //$emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    //$numeroWhatsApp = trim($clienteData['TELEFONO']);

    $clienteNombre = trim($clienteData['NOMBRE']);
    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';*/
    $emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/ // Interzenda

    if ($emailPred === "") {
        $correoBandera = 1;
    } else {
        $correoBandera = 0;
    }
    if ($numeroWhatsApp === "") {
        $numeroBandera = 1;
    } else {
        $numeroBandera = 0;
    }
    if (($correo === 'S' && isset($emailPred)) || isset($numeroWhatsApp)) {
        // Enviar notificaciones solo si los datos son válidos
        if ($correoBandera === 0) {
            enviarCorreoConfirmacion($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData); // Enviar correo
        }

        if ($numeroBandera === 0) {
            $resultadoWhatsApp = enviarWhatsAppConPlantillaConfirmacion($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito);
        }

        // Determinar la respuesta JSON según las notificaciones enviadas
        if ($correoBandera === 0 && $numeroBandera === 0) {
            /*echo json_encode(['success' => true, 'notificacion' => true, 'message' => 'Pedido Autorizado y notificado por correo y WhatsApp.']);
            die();*/
        } elseif ($correoBandera === 1 && $numeroBandera === 0) {
            echo json_encode(['success' => false, 'telefono' => true, 'message' => 'Pedido Realizado y Notificado por WhatsApp.']);
            die();
        } elseif ($correoBandera === 0 && $numeroBandera === 1) {
            echo json_encode(['success' => false, 'correo' => true, 'message' => 'Pedido Realizado y notificado por Correo.']);
            die();
        } else {
            echo json_encode(['success' => false, 'notificacion' => true, 'message' => 'Pedido Realizado, pero no se Pudo Notificar al Cliente.']);
            die();
        }
    } else {
        echo json_encode(['success' => false, 'datos' => true, 'message' => 'El cliente no Tiene un Correo y Telefono Válido Registrado.']);
        die();
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function enviarWhatsAppConPlantillaConfirmacion($numero, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';

    //$token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD'; // 📌 Reemplázalo con un token válido
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // ✅ Verifica que los valores no estén vacíos
    if (empty($noPedido) || empty($claveSae)) {
        error_log("Error: noPedido o noEmpresa están vacíos.");
        return false;
    }
    $productosJson = urlencode(json_encode($partidasData));
    // ✅ Generar URLs dinámicas correctamente
    // ✅ Generar solo el ID del pedido en la URL del botón
    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&credito=" . urlencode($conCredito);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave); // Solo pasamos el número de pedido


    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = $partida['precioUnitario'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $IMPORTE = $total;

        $productosStr .= "$producto - $cantidad unidades, ";

        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0;
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    // ✅ Datos para WhatsApp API con botones de Confirmar y Rechazar
    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "confirmar_pedido", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
            "components" => [
                [
                    "type" => "header",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre] // 📌 Encabezado dinámico
                    ]
                ],
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $noPedido], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $productosStr], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($IMPORTE, 2)] // 📌 Precio total
                    ]
                ],
                // ✅ Botón Confirmar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 0,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlConfirmar] // 📌 URL dinámica
                    ]
                ],
                // ✅ Botón Rechazar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 1,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlRechazar] // 📌 URL dinámica
                    ]
                ]
            ]
        ]
    ];

    // ✅ Verificar JSON antes de enviarlo
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

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
function enviarCorreoConfirmacion($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData)
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
    //$correoRemitente = null;
    //$contraseñaRemitente = null;
    // Definir el correo de destino (puedes cambiarlo si es necesario)
    $correoDestino = $correo;

    // Obtener el nombre de la empresa desde la sesión
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'Empresa Desconocida';

    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // Convertir productos a JSON para la URL
    $productosJson = urlencode(json_encode($partidasData));
    $vendedor = obtenerNombreVendedor($vendedor, $conexionData, $claveSae);
    // URL base del servidor
    $urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "http://localhost/MDConnecta/Servidor/PHP";

    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&credito=" . urlencode($conCredito);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae);

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los detalles de su pedido <b>$noPedido</b>. Por favor, revíselos y confirme:</p>";
    $bodyHTML .= "<p><b>Fecha y Hora de Elaboración:</b> $fechaElaboracion</p>";
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
        $clave = htmlspecialchars($partida['producto']);
        $descripcion = htmlspecialchars($partida['descripcion']);
        $cantidad = htmlspecialchars($partida['cantidad']);
        $totalPartida = $cantidad * $partida['precioUnitario'];
        $total += $totalPartida;
        $IMPORTE = $total;

        $bodyHTML .= "<tr>
                        <td style='text-align: center;'>$clave</td>
                        <td>$descripcion</td>
                        <td style='text-align: right;'>$cantidad</td>
                        <td style='text-align: right;'>$" . number_format($totalPartida, 2) . "</td>
                      </tr>";

        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0;
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total:</b> $" . number_format($IMPORTE, 2) . "</p>";

    // Botones para confirmar o rechazar el pedido
    $bodyHTML .= "<p>Confirme su pedido seleccionando una opción:</p>
                  <a href='$urlConfirmar' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar</a>
                  <a href='$urlRechazar' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Rechazar</a>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente);

    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
}
function restarSaldo($conexionData, $claveSae, $datosCxC, $claveCliente)
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
    //$importe = '1250.75';
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "UPDATE $nombreTabla SET
        [SALDO] = [SALDO] - ?
        WHERE CLAVE = ?";

    $params = [$datosCxC['importe'], $claveCliente];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al actualizar el saldo',
            'errors' => sqlsrv_errors()
        ]));
    }

    // ✅ Confirmar la transacción si es necesario (solo si se usa `BEGIN TRANSACTION`)
    // sqlsrv_commit($conn);

    // ✅ Liberar memoria y cerrar conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente para el cliente $claveCliente"
    ]);
}

function formatearDato($dato)
{
    if (is_string($dato)) {
        return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
    }
    if (is_array($dato)) {
        // Para arreglos anidados, se llama recursivamente
        return formatearFormulario($dato);
    }
    // Si es otro tipo (número, boolean, etc.), se devuelve tal cual.
    return $dato;
}
function formatearFormulario($formulario)
{
    foreach ($formulario as $clave => $valor) {
        $formulario[$clave] = formatearDato($valor);
    }
    return $formulario;
}
function formatearPartidas($partidas)
{
    foreach ($partidas as $indice => $partida) {
        if (is_array($partida)) {
            foreach ($partida as $clave => $valor) {
                $partidas[$indice][$clave] = formatearDato($valor);
            }
        } else {
            $partidas[$indice] = formatearDato($partida);
        }
    }
    return $partidas;
}
function validarCreditos($conexionData, $clienteId)
{
    // Validar si el ID del cliente está proporcionado
    if (!$clienteId) {
        echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
        exit;
    }

    try {
        // Configuración de conexión
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
        $claveSae = $_SESSION['empresa']['claveSae'];
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Construir la consulta SQL
        $sql = "SELECT CAMPLIB7 FROM $nombreTabla WHERE CVE_CLIE = ?";
        //$sql = "SELECT CAMPLIB8 FROM $nombreTabla WHERE CVE_CLIE = ?";
        $params = [$clienteId];
        $stmt = sqlsrv_query($conn, $sql, $params);

        // Verificar si hubo errores al ejecutar la consulta
        if ($stmt === false) {
            throw new Exception('Error al ejecutar la consulta.');
        }

        // Obtener los datos del cliente
        $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$clienteData) {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
            exit;
        }
        //var_dump($clienteData);
        // Limpiar y preparar los datos para la respuesta
        $conCredito = trim($clienteData['CAMPLIB7'] ?? "");
        //$conCredito = trim($clienteData['CAMPLIB8'] ?? "");

        // Enviar respuesta con los datos del cliente
        return json_encode([
            'success' => true,
            'conCredito' => $conCredito
        ]);
    } catch (Exception $e) {
        // Manejo de errores
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        // Liberar recursos y cerrar la conexión
        if (isset($stmt)) {
            sqlsrv_free_stmt($stmt);
        }
        if (isset($conn)) {
            sqlsrv_close($conn);
        }
    }
}
function validarCorreoClienteActualizacion($formularioData, $conexionData, $rutaPDF, $claveSae, $conCredito)
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
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $formularioData['enviar']; // Dirección de envío
    $vendedor = $formularioData['vendedor']; // Número de vendedor
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $noPedido = $formularioData['numero']; // Número de pedido
    /*$claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
     $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);*/

    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $partidasData = obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae);

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener MAIL y EMAILPRED
    $sql = "SELECT MAIL, EMAILPRED, NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
    $params = [$clave];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
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

    $fechaElaboracion = $formularioData['fechaAlta'];
    $correo = trim($clienteData['MAIL']);
    //$emailPred = trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    // Si hay múltiples correos separados por `;`, tomar solo el primero
    //$emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    //$emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    //$numeroWhatsApp = trim($clienteData['TELEFONO']);
    $clienteNombre = trim($clienteData['NOMBRE']);
    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';*/
    $emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/ // Interzenda


    if ($emailPred === "") {
        $correoBandera = 1;
    } else {
        $correoBandera = 0;
    }
    if ($numeroWhatsApp === "") {
        $numeroBandera = 1;
    } else {
        $numeroBandera = 0;
    }
    if (($correo === 'S' && isset($emailPred)) || isset($numeroWhatsApp)) {
        // Enviar notificaciones solo si los datos son válidos
        if ($correoBandera === 0) {
            enviarCorreoActualizacion($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData); // Enviar correo
        }

        if ($numeroBandera === 0) {
            $resultadoWhatsApp = enviarWhatsAppConPlantillaConfirmacion($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito);
        }

        // Determinar la respuesta JSON según las notificaciones enviadas
        if ($correoBandera === 0 && $numeroBandera === 0) {
            /*echo json_encode(['success' => true, 'notificacion' => true, 'message' => 'Pedido Autorizado y notificado por correo y WhatsApp.']);
            die();*/
        } elseif ($correoBandera === 1 && $numeroBandera === 0) {
            echo json_encode(['success' => false, 'telefono' => true, 'message' => 'Pedido Realizado y Notificado por WhatsApp.']);
            die();
        } elseif ($correoBandera === 0 && $numeroBandera === 1) {
            echo json_encode(['success' => false, 'correo' => true, 'message' => 'Pedido Realizado y notificado por Correo.']);
            die();
        } else {
            echo json_encode(['success' => false, 'notificacion' => true, 'message' => 'Pedido Realizado, pero no se Pudo Notificar al Cliente.']);
            die();
        }
    } else {
        echo json_encode(['success' => false, 'datos' => true, 'message' => 'El cliente no Tiene un Correo y Telefono Válido Registrado.']);
        die();
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function enviarWhatsAppConPlantillaActualizacion($numero, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';

    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // ✅ Verifica que los valores no estén vacíos
    if (empty($noPedido) || empty($claveSae)) {
        error_log("Error: noPedido o noEmpresa están vacíos.");
        return false;
    }
    $productosJson = urlencode(json_encode($partidasData));
    // ✅ Generar URLs dinámicas correctamente
    // ✅ Generar solo el ID del pedido en la URL del botón
    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&credito=" . urlencode($conCredito);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave); // Solo pasamos el número de pedido


    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $clave = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];
        $totalPartida = $cantidad * $partida['PREC'];
        $total += $totalPartida;
        $IMPORTE = $total;
        $productosStr .= "$clave - $cantidad unidades, ";

        $IMPU4 = $partida['IMPU4'];
        $desc1 = $partida['DESC1'] ?? 0;
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;
        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    // ✅ Datos para WhatsApp API con botones de Confirmar y Rechazar
    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "confirmar_pedido", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
            "components" => [
                [
                    "type" => "header",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre] // 📌 Encabezado dinámico
                    ]
                ],
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $noPedido], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $productosStr], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($IMPORTE, 2)] // 📌 Precio total
                    ]
                ],
                // ✅ Botón Confirmar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 0,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlConfirmar] // 📌 URL dinámica
                    ]
                ],
                // ✅ Botón Rechazar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 1,
                    "parameters" => [
                        ["type" => "payload", "payload" => $urlRechazar] // 📌 URL dinámica
                    ]
                ]
            ]
        ]
    ];

    // ✅ Verificar JSON antes de enviarlo
    $data_string = json_encode($data, JSON_PRETTY_PRINT);

    error_log("WhatsApp JSON: " . $data_string);

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
function enviarCorreoActualizacion($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData)
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
    //$correoRemitente = null;
    //$contraseñaRemitente = null;
    // Definir el correo de destino (puedes cambiarlo si es necesario)
    $correoDestino = $correo;

    // Obtener el nombre de la empresa desde la sesión
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'Empresa Desconocida';
    $vendedor = obtenerNombreVendedor($vendedor, $conexionData, $claveSae);
    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // URL base del servidor
    $urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "http://localhost/MDConnecta/Servidor/PHP";
    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&credito=" . urlencode($conCredito);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa);

    // Construcción del cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los detalles de su pedido <b>$noPedido</b>. Por favor, revíselos y confirme:</p>";
    $bodyHTML .= "<p><b>Fecha y Hora de Elaboración:</b> $fechaElaboracion</p>";
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
                        <td style='text-align: center;'>$clave</td>
                        <td>$descripcion</td>
                        <td style='text-align: right;'>$cantidad</td>
                        <td style='text-align: right;'>$" . number_format($totalPartida, 2) . "</td>
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
    $bodyHTML .= "<p><b>Total a Pagar:</b> $" . number_format($IMPORTE, 2) . "</p>";

    // Botones para confirmar o rechazar el pedido
    $bodyHTML .= "<p>Confirme su pedido seleccionando una opción:</p>
                  <a href='$urlConfirmar' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar</a>
                  <a href='$urlRechazar' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Rechazar</a>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar el correo con el remitente dinámico
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correoDestino, $asunto, $bodyHTML, $rutaPDF, $correoRemitente, $contraseñaRemitente);

    if ($resultado === "Correo enviado exitosamente.") {
        // En caso de éxito, puedes registrar logs o realizar alguna otra acción
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
}
function guardarPedidoActualizado($formularioData, $conexionData, $claveSae, $noEmpresa)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Validar que se cuente con los datos mínimos requeridos
    if (empty($formularioData['numero']) || empty($formularioData['cliente'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos del pedido.']);
        return;
    }

    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $partidasData = obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae);

    // Agregar la descripción del producto a cada partida
    foreach ($partidasData as &$partida) {  // 🔹 Pasar por referencia para modificar el array
        $CVE_ART = $partida['CVE_ART'];
        $partida['descripcion'] = obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae);
    }
    // Preparar los campos que se guardarán en Firebase
    $fields = [
        'folio'     => ['stringValue' => $formularioData['numero']],
        'cliente'    => ['stringValue' => $formularioData['cliente']],
        'enviar'     => ['stringValue' => isset($formularioData['enviar']) ? $formularioData['enviar'] : ''],
        'vendedor'   => ['stringValue' => isset($formularioData['vendedor']) ? $formularioData['vendedor'] : ''],
        'diaAlta'    => ['stringValue' => isset($formularioData['fechaAlta']) ? $formularioData['fechaAlta'] : ''],
        //'partidas'   => ['stringValue' => json_encode($partidasData)], // Guardamos las partidas como JSON
        "partidas" => [
            "arrayValue" => [
                "values" => array_map(function ($partidasData) {
                    return [
                        "mapValue" => [
                            "fields" => [
                                "cantidad" => ["stringValue" => $partidasData["CANT"]],
                                "producto" => ["stringValue" => $partidasData["CVE_ART"]],
                                "unidad" => ["stringValue" => $partidasData["UNI_VENTA"]],
                                "descuento" => ["stringValue" => $partidasData["DESC1"]],
                                "ieps" => ["stringValue" => $partidasData["IMPU1"]],
                                "impuesto2" => ["stringValue" => $partidasData["IMPU2"]],
                                "isr" => ["stringValue" => $partidasData["IMPU3"]],
                                "iva" => ["stringValue" => $partidasData["IMPU4"]],
                                "comision" => ["stringValue" => $partidasData["COMI"]],
                                "precioUnitario" => ["stringValue" => $partidasData["PREC"]],
                                "subtotal" => ["stringValue" => $partidasData["TOT_PARTIDA"]],
                                "descripcion" => ["stringValue" => $partidasData["descripcion"]],
                            ]
                        ]
                    ];
                }, $partidasData)
            ]
        ],
        'claveSae'   => ['stringValue' => $claveSae],
        'noEmpresa'  => ['integerValue' => $noEmpresa],
        'status' => ['stringValue' => 'Sin Autorizar']
    ];

    // URL para crear un nuevo documento en la colección PEDIDOS_AUTORIZAR
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR?key=$firebaseApiKey";

    // Crear el payload a enviar
    $payload = json_encode(['fields' => $fields]);

    // Configurar las opciones HTTP para el POST
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ],
    ];
    $context = stream_context_create($options);

    // Ejecutar la petición a Firebase
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el pedido autorizado en Firebase.']);
        return;
    }

    //echo json_encode(['success' => true, 'message' => 'Pedido guardado y en espera de ser autorizado.']);
}
function enviarWhatsAppActualizado($formularioData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $credito)
{
    // Conectar a SQL Server
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

    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $partidasData = obtenerPartidasActualizadas($CVE_DOC, $conexionData, $claveSae);

    // Configuración de la API de WhatsApp
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // Obtener datos del pedido
    $noPedido = $formularioData['numero'];
    $enviarA = $formularioData['enviar'];
    $vendedor = $formularioData['vendedor'];
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
    $fechaElaboracion = $formularioData['diaAlta'];
    $vendedor = formatearClaveVendedor($vendedor);
    // Obtener datos del cliente desde la base de datos
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE FROM $nombreTabla3 WHERE [CVE_VEND] = ?";
    $stmt = sqlsrv_query($conn, $sql, [$vendedor]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar al vendedor', 'errors' => sqlsrv_errors()]));
    }

    $vendedorData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$vendedorData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }

    // Obtener datos del cliente desde la base de datos
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE, TELEFONO FROM $nombreTabla WHERE [CLAVE] = ?";
    $stmt = sqlsrv_query($conn, $sql, [$clave]);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$clienteData) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        sqlsrv_close($conn);
        return;
    }
    $vendedor = trim(($vendedorData['NOMBRE']));

    //$clienteNombre = trim($clienteData['NOMBRE']);
    //$numeroTelefono = trim($clienteData['TELEFONO']); // Si no hay teléfono registrado, usa un número por defecto
    //$numero = "7775681612";
    //$numero = "+527772127123"; //InterZenda
    //$numero = "+527773340218";
    //$numero = "+527773750925";
    $numero = '+527775681612';
    // Obtener descripciones de los productos
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    foreach ($partidasData as &$partida) {
        $claveProducto = $partida['CVE_ART'];
        $sqlProducto = "SELECT DESCR FROM $nombreTabla2 WHERE CVE_ART = ?";
        $stmtProducto = sqlsrv_query($conn, $sqlProducto, [$claveProducto]);

        if ($stmtProducto && $rowProducto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC)) {
            $partida['descripcion'] = $rowProducto['DESCR'];
        } else {
            $partida['descripcion'] = 'Descripción no encontrada';
        }

        sqlsrv_free_stmt($stmtProducto);
    }

    // Construcción del mensaje con los detalles del pedido
    $productosStr = "";
    $total = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];
        $precioUnitario = $partida['PREC'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $productosStr .= "$producto - $cantidad unidades, ";
    }
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    /*$mensajeProblema1 = "";
    $mensajeProblema2 = "";
    if ($validarSaldo == 1) {
        $mensajeProblema1 = "Saldo Vendido";
    }
    if ($credito == 1) {
        $mensajeProblema2 = "Credito Excedido";
    }

    // Definir el mensaje de problemas del cliente (Saldo vencido)
    $mensajeProblema = urlencode($mensajeProblema1) . urlencode($mensajeProblema2);*/
    $problemas = [];

    if ($validarSaldo == 1) {
        $problemas[] = "• Saldo Vencido";
    }
    if ($credito == 1) {
        $problemas[] = "• Crédito Excedido";
    }

    // Si hay problemas, los une con un espacio
    $mensajeProblema = !empty($problemas) ? implode(" ", $problemas) : "Sin problemas";


    // Construcción del JSON para enviar el mensaje de WhatsApp con plantilla
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "autorizar_pedido",
            "language" => ["code" => "es_MX"],
            "components" => [
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $vendedor], // {{1}} Vendedor
                        ["type" => "text", "text" => $noPedido], // {{2}} Número de pedido
                        ["type" => "text", "text" => $productosStr], // {{3}} Detalles de los productos
                        ["type" => "text", "text" => $mensajeProblema] // {{4}} Problema del cliente
                    ]
                ]
            ]
        ]
    ];

    // Enviar el mensaje de WhatsApp
    $data_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log("WhatsApp JSON: " . $data_string);

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

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    return $result;
}

function facturar($folio, $claveSae, $noEmpresa)
{
    $numFuncion = '1';
    $pedidoId = $folio;

    // URL del servidor donde se ejecutará la remisión
    $facturanUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/factura.php";
    //$facturanUrl = 'http://localhost/MDConnecta/Servidor/PHP/factura.php';

    // Datos a enviar a la API de remisión
    $data = [
        'numFuncion' => $numFuncion,
        'pedidoId' => $pedidoId,
        'claveSae' => $claveSae,
        'noEmpresa' => $noEmpresa,
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

    if ($facturaResponse) {
        // Intenta decodificar como JSON
        //$facturaData = json_decode($facturaResponse, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($facturaResponse)) {
            return $facturaResponse;
            // ✅ La respuesta es un JSON con cveDoc (Pedido procesado correctamente)
            echo "<div class='container'>
                    <div class='title'>Confirmación Exitosa</div>
                    <div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>
                    <a href='/Menu.php' class='button'>Regresar al inicio</a>
                  </div>";
        }
    } else {
        // ❌ No hubo respuesta
        echo "<div class='container error'>
                <div class='title'>Confirmación Fallida</div>
                <div class='message'>El pedido falló. No se recibió respuesta del servidor.</div>
                <a href='/Menu.php' class='button'>Regresar al inicio</a>
              </div>";
    }
}
function obtenerNombreVendedor($vendedor, $conexionData, $claveSae)
{

    $vendedor = formatearClaveVendedor($vendedor);

    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE FROM $nombreTabla WHERE CVE_VEND = ?";
    $stmt = sqlsrv_query($conn, $sql, [$vendedor]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $nombre = $row ? $row['NOMBRE'] : '';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $nombre;
}
function obtenerNombreCliente($cliente, $conexionData, $claveSae)
{

    $cliente = formatearClaveCliente($cliente);

    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE FROM $nombreTabla WHERE CLAVE = ?";
    $stmt = sqlsrv_query($conn, $sql, [$cliente]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al Obtener el Nombre del Cliente', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $nombre = $row ? $row['NOMBRE'] : '';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $nombre;
}
function comanda($formularioData, $partidasData, $claveSae, $noEmpresa, $conexionData, $firebaseProjectId, $firebaseApiKey)
{
    date_default_timezone_set('America/Mexico_City');

    $horaActual = (int) date('H'); // Hora actual en formato 24 horas (e.g., 13 para 1:00 PM)
    // Determinar el estado según la hora
    $estadoComanda = $horaActual >= 13 ? "Pendiente" : "Abierta"; // "Pendiente" después de 1:00 PM
    //$estadoComanda = $horaActual >= 15 ? "Pendiente" : "Abierta"; // "Pendiente" después de 3:00 PM
    $producto = obtenerProductosComanda($formularioData['numero'], $conexionData, $claveSae);
    $vendedor = obtenerNombreVendedor($formularioData['claveVendedor'], $conexionData, $claveSae);
    $nombreCliente = obtenerNombreCliente($formularioData['cliente'], $conexionData, $claveSae);
    // Preparar datos para Firebase
    $comanda = [
        "fields" => [
            "idComanda" => ["stringValue" => uniqid()],
            "folio" => ["stringValue" => $formularioData['numero']],
            "nombreCliente" => ["stringValue" => $nombreCliente],
            "enviarA" => ["stringValue" => $formularioData['enviar']],
            "fechaHoraElaboracion" => ["stringValue" => $formularioData['diaAlta']],
            "productos" => [
                "arrayValue" => [
                    "values" => array_map(function ($producto) use ($conexionData, $claveSae) {
                        $productoData = obtenerDescripcionComanda($producto["CVE_ART"], $conexionData, $claveSae);
                        return [
                            "mapValue" => [
                                "fields" => [
                                    "clave" => ["stringValue" => $producto["CVE_ART"]],
                                    "descripcion" => ["stringValue" => $productoData["DESCR"]],
                                    "cantidad" => ["integerValue" => (int) $producto["CANT"]],
                                ]
                            ]
                        ];
                    }, $producto)
                ]
            ],
            "vendedor" => ["stringValue" => $vendedor],
            "status" => ["stringValue" => $estadoComanda], // Establecer estado según la hora
            "claveSae" => ["stringValue" => $claveSae],
            "noEmpresa" => ["stringValue" => $noEmpresa],
            "pagada" => ["booleanValue" => true]
        ]
    ];

    // URL de Firebase
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    // Enviar los datos a Firebase
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($comanda)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
}
function obtenerProductosComanda($pedidoId, $conexionData, $claveSae)
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
    $CVE_DOC = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$CVE_DOC];

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
function obtenerDescripcionComanda($producto, $conexionData, $claveSae)
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


    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[INVE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_ART = ?";
    $params = [$producto];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $productoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($productoData) {
        return $productoData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function obtenerEstados()
{
    $filePath = "../../Complementos/CAT_ESTADOS.xml";
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
        $estados = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return;
    }

    $estado = [];
    // Iterar sobre cada <row>
    foreach ($estados->row as $row) {
        $pais = (string)$row['Pais'];
        // Sólo procesamos si País es 'MEX'
        if ($pais !== 'MEX') {
            continue;
        }
        $estado[] = [
            'Clave'       => (string)$row['Clave'],
            'Pais'        => $pais,
            'Descripcion' => (string)$row['Descripcion']
        ];
    }

    if (!empty($estado)) {
        // Opcional: ordenar por Descripción alfabéticamente
        usort($estado, function ($a, $b) {
            return strcmp($a['Descripcion'] ?? '', $b['Descripcion'] ?? '');
        });

        echo json_encode(['success' => true, 'data' => $estado]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron registros para País MEX.']);
    }
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

    $municipio = [];

    // Iterar sobre cada <row>
    foreach ($municipios->row as $row) {
        $Estado = (string)$row['Estado'];
        // Sólo procesamos si País es 'MEX'
        if ($Estado !== $estadoSeleccionado) {
            continue;
        }
        $municipio[] = [
            'Clave' => (string)$row['Clave'],
            'Estado' => (string)$row['Estado'],
            'Descripcion' => (string)$row['Descripcion']
        ];
    }
    if (!empty($municipio)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($municipio, function ($a, $b) {
            return strcmp($a['Clave'] ?? '', $b['Clave'] ?? '');
        });


        echo json_encode(['success' => true, 'data' => $municipio]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron ningun regimen.']);
    }
}


// -----------------------------------------------------------------------------------------------------//
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
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $filtroFecha = $_POST['filtroFecha'];
        $estadoPedido = $_POST['estadoPedido'];
        $filtroVendedor = $_POST['filtroVendedor'];
        mostrarPedidos($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor);
        break;
    case 2:

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

        mostrarPedidoEspecifico($clave, $conexionData, $claveSae);

        break;
    case 3:
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
            obtenerPartidasPedido($conexionData, $clavePedido);
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida o no definida']);
        }
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
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $clave = $_POST['clave'];
        $cliente = $_POST['cliente'];
        obtenerClientePedido($clave, $conexionData, $cliente, $claveSae);
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
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        obtenerProductos($conexionData);
        break;
    case 6:
        if (isset($_SESSION['empresa']['noEmpresa'])) {
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        } else {
            $noEmpresa = "";
        }
        if (isset($_SESSION['empresa']['noEmpresa'])) {
            $claveSae = $_SESSION['empresa']['claveSae'];
        } else {
            //$claveSae = "02";
            $claveSae = "01";
        }
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $claveProducto = $_GET['claveProducto'];
        $listaPrecioCliente = $_GET['listaPrecioCliente'];
        obtenerPrecioProducto($conexionData, $claveProducto, $listaPrecioCliente, $claveSae);
        break;
    case 7:
        if (isset($_SESSION['empresa']['noEmpresa'])) {
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        } else {
            $noEmpresa = "";
        }

        if (isset($_SESSION['empresa']['noEmpresa'])) {
            $claveSae = $_SESSION['empresa']['claveSae'];
        } else {
            //$claveSae = "02";
            $claveSae = "01";
        }
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        $cveEsqImpu = $_POST['cveEsqImpu'];
        obtenerImpuesto($conexionData, $cveEsqImpu, $claveSae);
        break;
    case 8:
        $formularioData = json_decode($_POST['formulario'], true); // Datos del formulario desde JS
        $csrf_token  = $_SESSION['csrf_token'];
        $csrf_token_form = $formularioData['token'];
        if ($csrf_token === $csrf_token_form) {
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
            $envioData = json_decode($_POST['envio'], true);
            $partidasData = json_decode($_POST['partidas'], true); // Datos de las partidas desde JS
            $conexionData = $conexionResult['data'];
            $tipoOperacion = $formularioData['tipoOperacion']; // 'alta' o 'editar'

            // Formatear los datos
            $formularioData = formatearFormulario($formularioData);
            $partidasData = formatearPartidas($partidasData);
            if ($tipoOperacion === 'alta') {
                // Lógica para alta de pedido
                $resultadoValidacion = validarExistencias($conexionData, $partidasData, $claveSae);
                if ($resultadoValidacion['success']) {

                    $conCredito = $formularioData['conCredito'];
                    // Calcular el total del pedido
                    $totalPedido = calcularTotalPedido($partidasData);
                    $clienteId = $formularioData['cliente'];
                    $clave = formatearClaveCliente($clienteId);

                    $DAT_ENVIO = gaurdarDatosEnvio($conexionData, $clave, $formularioData, $envioData, $claveSae);

                    if ($conCredito === 'S') {
                        // Validar crédito del cliente
                        $validacionCredito = validarCreditoCliente($conexionData, $clave, $totalPedido, $claveSae);

                        if ($validacionCredito['success']) {
                            $credito = '0';
                        } else {
                            $credito = '1';
                        }

                        $validarSaldo = validarSaldo($conexionData, $clave, $claveSae);

                        if ($validarSaldo == 0 && $credito == 0) {
                            $estatus = "E";
                        } else if ($validarSaldo == 1 || $credito == 1) {
                            $estatus = "C";
                        }
                        $estatus = "E";
                        $validarSaldo = 0;
                        $credito = 0;
                        guardarPedido($conexionData, $formularioData, $partidasData, $claveSae, $estatus, $DAT_ENVIO);
                        guardarPartidas($conexionData, $formularioData, $partidasData, $claveSae);
                        actualizarFolio($conexionData, $claveSae);
                        actualizarInventario($conexionData, $partidasData);
                        if ($validarSaldo == 0 && $credito == 0) {
                            $rutaPDF = generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa);
                            validarCorreoCliente($formularioData, $partidasData, $conexionData, $rutaPDF, $claveSae, $conCredito);
                            // Respuesta de éxito
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => true,
                                'autorizacion' => false,
                                'message' => 'El pedido se completó correctamente.',
                            ]);
                            exit();
                        } else {
                            guardarPedidoAutorizado($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa);
                            $resultado = enviarWhatsAppAutorizacion($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $credito);
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => false,
                                'autorizacion' => true,
                                'message' => 'El pedido se completó pero debe ser autorizado.',
                            ]);
                        }
                    } else {
                        $anticipo = buscarAnticipo($conexionData, $formularioData, $claveSae, $totalPedido);

                        /*$anticipo = [
                            'success' => $true,
                            'sinFondo' => $fondo,
                            'IMPORTE' => $IMPORTE,
                            'subTotal' => $totalPedido,
                            'Vencimiento' => $fechaVencimiento,
                            'Referencia' => $REFER,
                            'NO_FACTURA' => $NO_FACTURA
                        ];*/
                        //$anticipo['success'] = true;
                        if ($anticipo['success']) {
                            //Funcion para eliminar anticipo
                            $estatus = 'E';
                            guardarPedido($conexionData, $formularioData, $partidasData, $claveSae, $estatus, $DAT_ENVIO);
                            guardarPartidas($conexionData, $formularioData, $partidasData, $claveSae);
                            actualizarInventario($conexionData, $partidasData);
                            actualizarFolio($conexionData, $claveSae);
                            eliminarCxc($conexionData, $anticipo, $claveSae);
                            remision($conexionData, $formularioData, $partidasData, $claveSae, $noEmpresa);
                            comanda($formularioData, $partidasData, $claveSae, $noEmpresa, $conexionData, $firebaseProjectId, $firebaseApiKey);
                            /*$datosCxC = crearCxc($conexionData, $claveSae, $formularioData, $partidasData);
                            pagarCxc($conexionData, $claveSae, $datosCxC, $formularioData, $partidasData);
                            restarSaldo($conexionData, $claveSae, $datosCxC, $clave);*/
                            eliminarCxCBanco($anticipo, $claveSae, $formularioData);
                            //actualizarFolioF($conexionData, $claveSae);
                            // Respuesta de éxito
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => true,
                                'message' => 'El pedido se completó correctamente.',
                            ]);
                            exit();
                        } elseif ($anticipo['sinFondo']) {
                            //No tiene fondos
                            $estatus = 'C';
                            guardarPedido($conexionData, $formularioData, $partidasData, $claveSae, $estatus, $DAT_ENVIO);
                            guardarPartidas($conexionData, $formularioData, $partidasData, $claveSae);
                            actualizarInventario($conexionData, $partidasData);
                            $rutaPDF = generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa);
                            validarCorreoClienteConfirmacion($formularioData, $partidasData, $conexionData, $rutaPDF, $claveSae, $conCredito);
                            guardarPago($conexionData, $formularioData, $partidasData, $claveSae, $noEmpresa);
                            $fac = generarCuentaPorCobrar($conexionData, $formularioData, $claveSae, $partidasData);
                            actualizarFolio($conexionData, $claveSae);
                            actualizarFolioF($conexionData, $claveSae);
                            // Respuesta de éxito
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => false,
                                'cxc' => true,
                                'Factura' => $fac,
                                'message' => 'El pedido tiene 24 Horas para liquidarse.',
                            ]);
                            exit();
                        } elseif ($anticipo['fechaVencimiento']) {
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => false,
                                'message' => 'Anticipo vencido.',
                            ]);
                            exit();
                        } else {
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => false,
                                'message' => 'Hubo un error inesperado.',
                            ]);
                            exit();
                        }
                        //exit();
                    }
                } else {
                    // Error de existencias
                    echo json_encode([
                        'success' => false,
                        'exist' => true,
                        'message' => $resultadoValidacion['message'],
                        'productosSinExistencia' => $resultadoValidacion['productosSinExistencia'],
                    ]);
                }
                exit(); //borar
            } elseif ($tipoOperacion === 'editar') {

                $clienteId = $formularioData['cliente'];
                $clave = formatearClaveCliente($clienteId);
                $dataCredito = json_decode(validarCreditos($conexionData, $clave), true);

                if ($dataCredito['success']) {
                    $conCredito = "S";
                } else {
                    $conCredito = "N";
                }
                $resultadoValidacion = validarExistencias($conexionData, $partidasData, $claveSae);

                if ($resultadoValidacion['success']) {
                    // Calcular el total del pedido
                    $totalPedido = calcularTotalPedido($partidasData);

                    // Validar crédito del cliente
                    $validacionCredito = validarCreditoCliente($conexionData, $clave, $totalPedido, $claveSae);

                    if ($validacionCredito['success']) {
                        $credito = '0';
                    } else {
                        $credito = '1';
                    }

                    $validarSaldo = validarSaldo($conexionData, $clave, $claveSae);

                    if ($validarSaldo == 0 && $credito == 0) {
                        $estatus = "E";
                    } else if ($validarSaldo == 1 || $credito == 1) {
                        $estatus = "C";
                    }

                    /*$estatus = "E";
                    $validarSaldo = 0;
                    $credito = 0;*/

                    // Lógica para edición de pedido
                    $resultadoActualizacion = actualizarPedido($conexionData, $formularioData, $partidasData, $estatus);

                    if ($resultadoActualizacion['success']) {
                        if ($validarSaldo === 0 && $credito == 0) {
                            $rutaPDF = generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa);
                            validarCorreoClienteActualizacion($formularioData, $conexionData, $rutaPDF, $claveSae, $conCredito);
                            echo json_encode([
                                'success' => true,
                                'message' => 'El pedido fue actualizado correctamente.',
                            ]);
                            exit();
                        } else {
                            guardarPedidoActualizado($formularioData, $conexionData, $claveSae, $noEmpresa);
                            $resultado = enviarWhatsAppActualizado($formularioData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $conCredito);
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode([
                                'success' => false,
                                'autorizacion' => true,
                                'message' => 'El pedido se completó pero debe ser autorizado.',
                            ]);
                            exit();
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'No se pudo actualizar el pedido.',
                        ]);
                    }
                } else {
                    // Error de existencias
                    echo json_encode([
                        'success' => false,
                        'exist' => true,
                        'message' => $resultadoValidacion['message'],
                        'productosSinExistencia' => $resultadoValidacion['productosSinExistencia'],
                    ]);
                }
                //exit(); //borar
            } else {
                // Operación desconocida
                echo json_encode([
                    'success' => false,
                    'message' => 'Operación no reconocida.',
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    case 9:
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
        $clavePedido = $_POST['clavePedido'];
        $numPar = $_POST['numPar'];
        if (!isset($_POST['clavePedido']) || empty($_POST['clavePedido'])) {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó la clave del pedido']);
            exit;
        }

        if (!isset($_POST['numPar']) || empty($_POST['numPar'])) {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó el número de partida']);
            exit;
        }
        eliminarPartida($conexionData, $clavePedido, $numPar);
        break;
    case 10:
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
        $verificado = pedidoRemitido($conexionData, $pedidoID, $claveSae);
        eliminarPedido($conexionData, $pedidoID, $claveSae);
        liberarExistencias($conexionData, $pedidoID, $claveSae);
        break;
    case 11:
        /*$csrf_token  = $_SESSION['csrf_token'];
        $csrf_token_form = $_GET['token'];
        if ($csrf_token === $csrf_token_form) {*/
        // Empresa por defecto (puedes cambiar este valor según tus necesidades)
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];

        // Obtener conexión
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];

        // Llamar a la función para extraer productos
        extraerProductos($conexionData, $claveSae); //Aqui
        /*} else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }*/
        break;
    case 12:
        $codigoProducto = isset($_GET['codigoProducto']) ? $_GET['codigoProducto'] : null;

        if (!$codigoProducto) {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó un código de producto.']);
            exit;
        }

        // Depurar el código de producto
        error_log("Código de producto recibido: $codigoProducto");

        $sql = "SELECT [CVE_ART], [DESCR], [EXIST], [LIN_PROD], [UNI_MED], 
                               ISNULL([IMAGEN_ML], 'ruta/imagen_por_defecto.png') AS IMAGEN_ML
                        FROM $nombreTabla
                        WHERE CVE_ART = ?";
        $params = [$codigoProducto];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            error_log("Error en la consulta SQL: " . print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL.', 'errors' => sqlsrv_errors()]);
            exit;
        }

        $producto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($producto) {
            // Depuración del producto encontrado
            error_log("Producto encontrado: " . print_r($producto, true));
            echo json_encode(['success' => true, 'producto' => $producto]);
        } else {
            error_log("Producto no encontrado.");
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        }

        sqlsrv_free_stmt($stmt);
        break;
    case 13:
        // Obtener conexión
        //$claveSae = "02";
        $claveSae = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae); //Aqui
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }

        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];
        eliminarImagen($conexionData);
        // Llamar a la función para extraer productos
        //mostrarArticulosParaImagenes($conexionData);
        break;
    case 14:
        // Obtener conexión
        /*$claveSae = "02";
        $noEmpresa = "02";*/
        $claveSae = "01";
        $noEmpresa = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae); //Aqui
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }

        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];

        // Llamar a la función para extraer productos
        subirImagenArticulo($conexionData);
        break;
    case 15:
        // Obtener conexión
        //$claveSae = "02";
        $claveSae = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }

        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];

        // Llamar a la función para extraer productos
        extraerProducto($conexionData, $claveSae);
        break;
    case 16:
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
        $clave = $_POST['clave'];
        $producto = $_POST['producto'];
        obtenerProductoPedido($clave, $conexionData, $producto, $claveSae);
        break;
    case 17:
        /*$noEmpresa = "02";
        $claveSae = "02";*/
        $noEmpresa = "01";
        $claveSae = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        $listaPrecioCliente = $_GET['listaPrecioCliente'];
        extraerProductosE($conexionData, $claveSae, $listaPrecioCliente);
        break;
    case 18:
        /*$noEmpresa = "02";
        $claveSae = "02";*/
        $noEmpresa = "01";
        $claveSae = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        $listaPrecioCliente = $_GET['listaPrecioCliente'];
        extraerProductosCategoria($conexionData, $claveSae, $listaPrecioCliente);
        break;
    case 19:
        /*$claveSae = "02";
        $noEmpresa = "02";*/
        $noEmpresa = "01";
        $claveSae = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        // 📌 Convertir `formularioData` en un array asociativo
        $formularioData = [];
        foreach ($_POST['formularioData'] as $key => $value) {
            $formularioData[$key] = trim($value);
        }
        /*var_dump($_POST['partidasData']);
        exit();*/
        // 📌 Convertir `partidasData` en un array de partidas
        $partidasData = [];
        foreach ($_POST['partidasData'] as $index => $partida) {
            $partidaArray = [];
            foreach ($partida as $key => $value) {
                $partidaArray[$key] = trim($value);
            }
            $partidasData[] = $partidaArray;
        }

        $totalPedido = calcularTotalPedido($partidasData);
        $clienteId = $formularioData['cliente'];
        $clave = formatearClaveCliente($clienteId);
        // Validar crédito del cliente

        $resultadoValidacion = validarExistencias($conexionData, $partidasData, $claveSae);
        if ($resultadoValidacion['success']) {
            $validacionCredito = validarCreditoCliente($conexionData, $clave, $totalPedido, $claveSae);
            if ($validacionCredito['success']) {
                $validarSaldo = validarSaldo($conexionData, $clave, $claveSae);
                if ($validarSaldo === 0) {
                    guardarPedidoEcomers($conexionData, $formularioData, $partidasData, $claveSae);
                    guardarPartidasEcomers($conexionData, $formularioData, $partidasData, $claveSae);
                    actualizarInventarioEcomers($conexionData, $partidasData, $claveSae);
                    actualizarFolio($conexionData, $claveSae);
                    $rutaPDF = generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa);
                    validarCorreoClienteEcomers($formularioData, $partidasData, $conexionData, $rutaPDF, $claveSae, $noEmpresa);
                    //remision($conexionData, $formularioData, $partidasData, $claveSae, $noEmpresa);
                    // 📌 Respuesta en caso de éxito sin PDF
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode([
                        'success' => true,
                        'message' => 'El pedido se completó correctamente.',
                    ]);
                    exit;
                } else {
                    echo json_encode([
                        'success' => false,
                        'saldo' => true,
                        'message' => 'Tienes Saldo vencido.',
                    ]);
                }
            } else {
                // Error de crédito
                echo json_encode([
                    'success' => false,
                    'credit' => true,
                    'message' => 'Límite de crédito excedido.',
                    'saldoActual' => $validacionCredito['saldoActual'],
                    'limiteCredito' => $validacionCredito['limiteCredito'],
                ]);
            }
        } else {
            // Error de existencias
            echo json_encode([
                'success' => false,
                'exist' => true,
                'message' => $resultadoValidacion['message'],
                'productosSinExistencia' => $resultadoValidacion['productosSinExistencia'],
            ]);
        }
        break;
    case 20:
        $noEmpresa = "";
        //$claveSae = "02";
        $claveSae = "01";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        extraerProductosImagenes($conexionData, $claveSae);
        break;
    case 21:
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
        $verificado = pedidoRemitido($conexionData, $pedidoID, $claveSae);
        if ($verificado) {
            echo json_encode(['success' => true, 'message' => 'Pedido Remitido, no se puede cancelar']);
        } else {
            echo json_encode(['success' => false, 'fail' => true, 'message' => 'Pedido no Remitido, se puede cancelar']);
        }
        break;
    case 22:
        obtenerEstados();
        break;
    case 23:
        $estadoSeleccionado = $_POST['estado'];
        obtenerMunicipios($estadoSeleccionado);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
