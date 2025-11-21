<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
include 'reportes.php';
include 'utils.php';
include 'bitacora.php';


function validarSaldo($conexionData, $clave, $claveSae, $conn)
{
    try {
        // Montamos los nombres de tabla dinámicos
        $db         = $conexionData['nombreBase'];
        $s          = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        $tablaCuenM = "[$db].[dbo].[CUEN_M{$s}]";
        $tablaCuenD = "[$db].[dbo].[CUEN_DET{$s}]";
        $tablaClie  = "[$db].[dbo].[CLIE{$s}]";

        // Consulta que agrupa cada factura y sólo devuelve filas con saldo > 0 (redondeado a 2 decimales)
        $sql = "
            SELECT TOP 1 1
            FROM $tablaCuenM CUENM
            INNER JOIN $tablaClie CLIENTES
              ON CLIENTES.CLAVE = CUENM.CVE_CLIE
            LEFT JOIN $tablaCuenD CUEND
              ON CUEND.CVE_CLIE  = CUENM.CVE_CLIE
             AND CUEND.REFER     = CUENM.REFER
             AND CUEND.NUM_CARGO = CUENM.NUM_CARGO
            WHERE
              CUENM.FECHA_VENC     < CAST(GETDATE() AS DATE)
              AND CLIENTES.STATUS <> 'B'
              AND CLIENTES.CLAVE   = ?
              AND CUENM.REFER NOT LIKE '%NC%'
            GROUP BY
              CUENM.CVE_CLIE,
              CUENM.REFER,
              CUENM.NUM_CARGO,
              CUENM.IMPORTE
            HAVING
              ROUND(
                CUENM.IMPORTE
                - COALESCE(SUM(CUEND.IMPORTE), 0),
                2
              ) > 0
        ";

        // Ejecutamos la consulta
        $params = [$clave];
        $stmt   = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            throw new Exception("Error al verificar saldo vencido:\n{$errors}");
        }

        // Si devuelve alguna fila => al menos una factura vencida con saldo pendiente
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        sqlsrv_free_stmt($stmt);

        return ($row !== null) ? 1 : 0;
    } catch (Exception $e) {
        // Manejo de errores
        error_log("validarSaldo error: " . $e->getMessage());
        return -1;
    }
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
function validarCreditoCliente($conexionData, $clienteId, $totalPedido, $claveSae, $conn)
{
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

    sqlsrv_free_stmt($stmt);

    // Devolver el resultado y los datos relevantes
    return [
        'success' => $puedeContinuar,
        'saldoActual' => $saldoActual,
        'limiteCredito' => $limiteCredito
    ];
}
function gaurdarDatosEnvio($conexionData, $clave, $formularioData, $envioData, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $claveCliente = $formularioData['cliente'];
    /*$datosCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $conn);
    if (!$datosCliente) {
        die(json_encode(['success' => false, 'message' => 'No se encontraron datos del cliente']));
    }*/
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Extraer los datos del formulario
    $CVE_INFO = obtenerUltimoDato($conexionData, $claveSae, $conn);
    $CVE_INFO = $CVE_INFO + 1;
    $CVE_CONS = "";
    $NOMBRE = $envioData['nombreContacto'];
    $CALLE = $envioData['direccion1Contacto'];
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
    $FECHA_ENV = $envioData['diaAlta'];
    $NOMBRE_RECEP = "";
    $NO_RECEP = "";
    $FECHA_RECEP = "";
    //$COLONIA = "";
    $COLONIA = $envioData['direccion2Contacto'];
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
    return $CVE_INFO;
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
function actualizarControl2($conexionData, $claveSae, $conn)
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

    $sql = "UPDATE $nombreTabla SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 70";

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
function guardarPedido($conexionData, $formularioData, $partidasData, $claveSae, $estatus, $DAT_ENVIO, $conn, $conCredito)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $claveCliente = $formularioData['cliente'];
    $datosCliente = obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $conn);
    if (!$datosCliente) {
        die(json_encode(['success' => false, 'message' => 'No se encontraron datos del cliente']));
    }
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Extraer los datos del formulario
    $FOLIO = obtenerFolioSiguientePedido($conexionData, $claveSae, $conn);
    $CVE_DOC = str_pad($FOLIO, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
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
    $IMP_TOT1 = 0;
    $IMP_T1 = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $IMPU1 = $partida['ieps'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;

        $IMP_T1 = ($totalPartida - $DES) * ($IMPU1 / 100);
        $IMP_TOT1 += $IMP_T1;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    $CVE_VEND = str_pad($formularioData['claveVendedor'], 5, ' ', STR_PAD_LEFT);
    // Asignación de otros valores del formulario
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
    $CVE_OBS = 0;
    //$IMPORTE = number_format($IMPORTE, 2);
    // Toma el valor o 0 si no existe, lo casteas a float, y luego redondeas a 2 decimales
    $IMPORTE = round(($IMPORTE), 2);

    $CVE_BITA = $datosCliente['CVE_BITA'];
    //$COM_TOT_PORC = $datosCliente['COM_TOT_PORC']; //VENDEDOR
    if ($conCredito === 'S') {
        $METODODEPAGO = "PPD";
        $FORMADEPAGOSAT = 99;
    } else {
        $METODODEPAGO = "PUE";
        //$FORMADEPAGOSAT = 03;
        $FORMADEPAGOSAT = $datosCliente['FORMADEPAGOSAT'] ?? 03;
    }

    $NUMCTAPAGO = $datosCliente['NUMCTAPAGO'] ?? "";
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
    }
    // Determinar el valor de CAMPLIB3 en base al estatus
    $valorCampoLib3 = ($estatus === 'E') ? 'A' : (($estatus === 'C') ? 'C' : '');

    // Tabla de campos libres del pedido
    $nombreTablaCamposLibres = "[{$conexionData['nombreBase']}].[dbo].[FACTP_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta para insertar o actualizar CAMPLIB3
    // Primero intentamos un UPDATE, si no existe el registro se puede hacer un INSERT (opcional según la lógica del sistema)
    $sqlCamposLibres = "
    IF EXISTS (SELECT 1 FROM $nombreTablaCamposLibres WHERE CLAVE_DOC = ?)
        UPDATE $nombreTablaCamposLibres SET CAMPLIB3 = ? WHERE CLAVE_DOC = ?
    ELSE
        INSERT INTO $nombreTablaCamposLibres (CLAVE_DOC, CAMPLIB3) VALUES (?, ?)
    ";

    $paramsCamposLibres = [
        $CVE_DOC,
        $valorCampoLib3,
        $CVE_DOC, // Para UPDATE
        $CVE_DOC,
        $valorCampoLib3           // Para INSERT
    ];

    $stmtCamposLibres = sqlsrv_query($conn, $sqlCamposLibres, $paramsCamposLibres);

    if ($stmtCamposLibres === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al guardar CAMPLIB3 en FACTP_CLIB',
            'sql_error' => sqlsrv_errors()
        ]));
    }
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    return $FOLIO;
}
function obtenerFolioSiguientePedido($conexionData, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta SQL para obtener el siguiente folio
    $sql = "SELECT (ULT_DOC + 1) AS FolioSiguiente FROM $nombreTabla WHERE TIP_DOC = 'P'";
    $stmt = sqlsrv_query($conn, $sql);

    // SQL para incrementar el valor de ULT_DOC en 1 donde TIP_DOC es 'P'
    $sql2 = "UPDATE $nombreTabla SET [ULT_DOC] = [ULT_DOC] + 1 WHERE [TIP_DOC] = 'P'";

    // Ejecutar la consulta SQL
    $stmt2 = sqlsrv_query($conn, $sql2);

    if ($stmt === false) {
        // Si la consulta falla, liberar la conexión y retornar el error
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el folio', 'errors' => sqlsrv_errors()]));
    }
    if ($stmt2 === false) {
        // Si la consulta falla, liberar la conexión y retornar el error
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el folio', 'errors' => sqlsrv_errors()]));
    }

    // Verificar cuántas filas se han afectado
    $rowsAffected = sqlsrv_rows_affected($stmt);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    // Obtener el siguiente folio
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $folioSiguiente = $row ? $row['FolioSiguiente'] : null;
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    // Retornar el folio siguiente
    return $folioSiguiente;
}
function guardarPartidas($conexionData, $formularioData, $partidasData, $claveSae, $conn, $FOLIO)
{
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
            $CVE_DOC = str_pad($FOLIO, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
            $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
            $CVE_ART = $partida['producto']; // Clave del producto
            $CANT = $partida['cantidad']; // Cantidad
            $PREC = $partida['precioUnitario']; // Precio
            // Calcular los impuestos y totales
            $IMPU1 = $partida['ieps']; // Impuesto 1
            $IMPU3 = $partida['isr'];
            if ($IMPU1 != 0) {
                $IMP1APLA = 0;
            } else {
                $IMP1APLA = 6;
            }
            //$IMPU1 = 0;
            //$IMPU2 = $partida['impuesto2']; // Impuesto 2
            $IMPU2 = 0;
            $IMPU4 = $partida['iva']; // Impuesto 2
            // Agregar los cálculos para los demás impuestos...
            $DESC1 = $partida['descuento'];
            $DESC2 = 0;
            //$COMI = $partida['comision'];
            $CVE_UNIDAD = $partida['CVE_UNIDAD'];
            $CVE_PRODSERV = $partida['CVE_PRODSERV'];
            $NUM_ALMA = $formularioData['almacen'];
            $CVE_ESQIMPU = $formularioData['CVE_ESQIMPU'];
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 6, 6, 0,
                ?, ?, 0, ?,
                ?, ?, 0, 0, ?,
                'N', ?, '', 1, ?, ?, 0, 0, 0, 'N',
                0, ?, 'S', 'N', 1, 0, 'C', 0, 0, ?, '',
                0, '', '',
                ?, ?, '', 0, 0, 0, 6,
                6, 0, 0, 0, 0, 6, 6)";
            $params = [
                $CVE_DOC,
                $NUM_PAR,
                $CVE_ART,
                $CANT,
                $CANT,
                $PREC,
                $COSTO_PROM,
                $IMPU1,
                $IMPU2,
                $IMPU3,
                $IMPU4,
                $IMP1APLA,
                $TOTIMP1,
                $TOTIMP2,
                $TOTIMP4,
                $DESC1,
                $DESC2,
                //$COMI,
                $CANT,
                $NUM_ALMA,
                $UNI_VENTA,
                $TIPO_PORD,
                $TOT_PARTIDA,
                $CVE_ESQIMPU,
                $CVE_PRODSERV,
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

            // AQUÍ AGREGA EL INSERT EN PAR_FACTP_CLIBxx
            $nombreTablaCamposLibresPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
            $sqlInsertParClib = "INSERT INTO $nombreTablaCamposLibresPartidas (CLAVE_DOC, NUM_PART) VALUES (?, ?)";
            $paramsParClib = [$CVE_DOC, $NUM_PAR];
            $stmtClib = sqlsrv_query($conn, $sqlInsertParClib, $paramsParClib);

            if ($stmtClib === false) {
                sqlsrv_rollback($conn);
                die(json_encode(['success' => false, 'message' => 'Error al insertar en PAR_FACTP_CLIB', 'errors' => sqlsrv_errors()]));
            }

            $NUM_PAR++;
        }
    } else {
        die(json_encode(['success' => false, 'message' => 'Error: partidasData no es un array válido']));
    }
    //echo json_encode(['success' => true, 'message' => 'Partidas guardadas con éxito']);
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
}
function actualizarInventario($conexionData, $partidasData, $conn)
{
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['producto'];
        $cantidad = $partida['cantidad'];
        //$cantidad = "uno";
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
}
function actualizarApartados($conexionData, $partidasData, $conn)
{
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['producto'];
        //$cantidad = "uno";
        // SQL para actualizar los campos EXIST y PEND_SURT
        $sqlSelect = "SELECT CVE_ART, APART FROM $nombreTabla WHERE CVE_ART = '$CVE_ART' AND APART < 0";
        $stmtSelect = sqlsrv_query($conn, $sqlSelect);
        if ($stmtSelect === false) {
            continue;
        } else {
            $sql = "UPDATE $nombreTabla
            SET    
                [APART] = 0  
            WHERE [CVE_ART] = '$CVE_ART' AND [APART] < 0";
            // Preparar la consulta
            // Ejecutar la consulta SQL
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                die(json_encode(['success' => false, 'message' => 'Error al actualizar el inventario', 'errors' => sqlsrv_errors()]));
            }
            // Verificar cuántas filas se han afectado
            $rowsAffected = sqlsrv_rows_affected($stmt);
            // Retornar el resultado
            /*if ($rowsAffected > 0) {
            // echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró el producto para actualizar']);
            }*/
        }
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmtSelect);
}
function guardarDatosPedido($envioData, $FOLIO, $noEmpresa, $formularioData)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Construir los fields de Firestore con el formato correcto
    $fields = [
        'idPedido'            => ['integerValue' => (int)$FOLIO],
        'noEmpresa'           => ['integerValue' => (int)$noEmpresa],
        'nombreContacto'      => ['stringValue'  => $envioData['nombreContacto']],
        'companiaContacto'    => ['stringValue'  => $envioData['compañiaContacto']],
        'telefonoContacto'    => ['stringValue'  => $envioData['telefonoContacto']],
        'correoContacto'      => ['stringValue'  => $envioData['correoContacto']],
        'direccion1Contacto'  => ['stringValue'  => $envioData['direccion1Contacto']],
        'direccion2Contacto'  => ['stringValue'  => $envioData['direccion2Contacto']],
        'codigoContacto'      => ['stringValue'  => $envioData['codigoContacto']],
        'estadoContacto'      => ['stringValue'  => $envioData['estadoContacto']],
        'municipioContacto'   => ['stringValue'  => $envioData['municipioContacto']],
        //Datos adicionales
        'observaciones' => ['stringValue' => $formularioData['observaciones'] ?? ""]/*,
        'enviarWhat' => ['booleanValue' => $formularioData['enviarWhats'] ?? ""],
        'enviarCorreo' => ['booleanValue' => $formularioData['enviarCorreo'] ?? ""]*/
    ];

    // Prepara la URL de la colección
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DATOS_PEDIDO?key=$firebaseApiKey";

    $payload = json_encode(['fields' => $fields]);
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => $error['message']]);
        exit;
    }

    // Extrae el nombre/id del documento creado en Firestore
    $resData = json_decode($response, true);
    if (isset($resData['name'])) {
        // El id es la última parte de la URL del campo "name"
        $parts = explode('/', $resData['name']);
        return end($parts);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo obtener el id del documento']);
        exit;
    }
}
function generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $FOLIO, $conn)
{
    $rutaPDF = generarReportePedido($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $FOLIO, $conn);
    return $rutaPDF;
}
/**Envio de Confirmacion***/
function validarCorreoCliente($formularioData, $partidasData, $conexionData, $rutaPDF, $claveSae, $conCredito, $conn, $noPedido, $idEnvios, $flagCorreo = false, $flagWhats = false)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $formularioData['enviar']; // Dirección de envío
    $vendedor = $formularioData['vendedor']; // Número de vendedor
    $claveCliente = $formularioData['cliente'];
    $clave = formatearClaveCliente($claveCliente);
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
    $emailPred = (is_null($clienteData['EMAILPRED'])) ? "" : trim($clienteData['EMAILPRED']); // Obtener el string completo de correos
    $claveCliente = $clave;

    // Si hay múltiples correos separados por `;`, tomar solo el primero
    $emailPredArray = explode(';', $emailPred); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    //$numeroWhatsApp = trim($clienteData['TELEFONO']);
    $numeroWhatsApp = (is_null($clienteData['TELEFONO'])) ? "" : trim($clienteData['TELEFONO']);
    $clienteNombre = trim($clienteData['NOMBRE']);
    /*$emailPred = 'desarrollo01@mdcloud.mx';
    $numeroWhatsApp = '+527773750925';*/
    /*$emailPred = 'marcos.luna@mdcloud.mx';
    $numeroWhatsApp = '+527775681612';*/
    /*$emailPred = 'amartinez@grupointerzenda.com';
    $numeroWhatsApp = '+527772127123';*/ // Interzenda

    /*$emailPred = $_SESSION['usuario']['correo'];
    $numeroWhatsApp = $_SESSION['usuario']['telefono'];*/

    if (empty($emailPred) || !filter_var($emailPred, FILTER_VALIDATE_EMAIL)) {
        $correoBandera = 1;
    } else {
        $correoBandera = 0;
    }
    if (empty($numeroWhatsApp) || !preg_match('/^\d{10,15}$/', $numeroWhatsApp)) {
        $numeroBandera = 1;
    } else {
        $numeroBandera = 0;
    }
    // =======================================================
// BLOQUE PRINCIPAL DE ENVÍO CON CHEQUEO DE FLAGS
// =======================================================
    if ($correo === 'S' || isset($numeroWhatsApp)) {

        // 🔹 Bandera real según checkboxes
        $puedeEnviarCorreo = ($correoBandera === 0) && ($flagCorreo === true || $flagCorreo === "true" || $flagCorreo === 1 || $flagCorreo === "1");
        $puedeEnviarWhats  = ($numeroBandera === 0) && ($flagWhats === true || $flagWhats === "true" || $flagWhats === 1 || $flagWhats === "1");

        // 🔸 Enviar correo si aplica
        if ($puedeEnviarCorreo) {
            enviarCorreo(
                $emailPred,
                $clienteNombre,
                $noPedido,
                $partidasData,
                $enviarA,
                $vendedor,
                $fechaElaboracion,
                $claveSae,
                $noEmpresa,
                $clave,
                $rutaPDF,
                $conCredito,
                $conexionData,
                $idEnvios,
                $conn,
                $claveCliente
            );
        }

        // 🔸 Enviar WhatsApp si aplica
        if ($puedeEnviarWhats) {
            $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
            $resultadoWhatsApp = enviarWhatsAppConPlantillaPdf(
                $numeroWhatsApp,
                $clienteNombre,
                $noPedido,
                $claveSae,
                $partidasData,
                $enviarA,
                $vendedor,
                $fechaElaboracion,
                $noEmpresa,
                $clave,
                $conCredito,
                $claveCliente,
                $idEnvios,
                $rutaPDFW,
                $filename
            );
        }

        // 🔹 Respuesta según resultado
        if ($puedeEnviarCorreo && $puedeEnviarWhats) {
            echo json_encode([
                'success' => true,
                'message' => "Pedido completado y enviado por correo a $emailPred y por WhatsApp a $numeroWhatsApp.",
                'correo' => $emailPred,
                'whats' => $numeroWhatsApp,
            ]);
        } elseif ($puedeEnviarCorreo && !$puedeEnviarWhats) {
            echo json_encode([
                'success' => true,
                'soloCorreo' => true,
                'message' => "Pedido completado y enviado solo por correo a $emailPred.",
                'correo' => $emailPred,
            ]);
        } elseif (!$puedeEnviarCorreo && $puedeEnviarWhats) {
            echo json_encode([
                'success' => true,
                'soloWhats' => true,
                'message' => "Pedido completado y enviado solo por WhatsApp a $numeroWhatsApp.",
                'whats' => $numeroWhatsApp,
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'sinEnvio' => true,
                'message' => "Pedido completado pero no se enviaron notificaciones al cliente.",
            ]);
        }

        $camposModulo = [
            'quienCreo' => $_SESSION['usuario']['nombre'],
            'pedidoID' => $noPedido,
            'clienteID' => $claveCliente,
            'productos' => $partidasData,
        ];
        agregarBitacora($_SESSION['empresa']['claveUsuario'], "PEDIDOS", "Pedido Con Credito", $noEmpresa, $camposModulo);
        
        sqlsrv_commit($conn);
        sqlsrv_close($conn);
        exit();
    } else {
        // 🚨 Caso sin correo ni WhatsApp válidos
        $emailPred = $_SESSION['usuario']['correo'];
        $numeroWhatsApp = $_SESSION['usuario']['telefono'];

        enviarCorreo(
            $emailPred,
            $clienteNombre,
            $noPedido,
            $partidasData,
            $enviarA,
            $vendedor,
            $fechaElaboracion,
            $claveSae,
            $noEmpresa,
            $clave,
            $rutaPDF,
            $conCredito,
            $conexionData,
            $idEnvios,
            $conn,
            $claveCliente
        );

        $rutaPDFW = "https://mdconecta.mdcloud.mx/Servidor/PHP/pdfs/Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
        $filename = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $noPedido) . ".pdf";
        $resultadoWhatsApp = enviarWhatsAppConPlantillaPdf(
            $numeroWhatsApp,
            $clienteNombre,
            $noPedido,
            $claveSae,
            $partidasData,
            $enviarA,
            $vendedor,
            $fechaElaboracion,
            $noEmpresa,
            $clave,
            $conCredito,
            $claveCliente,
            $idEnvios,
            $rutaPDFW,
            $filename
        );

        echo json_encode([
            'success' => false,
            'notificacion' => true,
            'message' => 'Pedido realizado, pero el cliente no tiene correo ni WhatsApp válidos. Se notificó al vendedor.',
        ]);

        $camposModulo = [
            'quienCreo' => $_SESSION['usuario']['nombre'],
            'pedidoID' => $noPedido,
            'clienteID' => $claveCliente,
            'productos' => $partidasData,
        ];
        agregarBitacora($_SESSION['empresa']['claveUsuario'], "PEDIDOS", "Pedido Con Credito", $noEmpresa, $camposModulo);

        sqlsrv_commit($conn);
        sqlsrv_close($conn);
        exit();
    }

    /*******************************************/
}
function enviarCorreo($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $claveSae, $noEmpresa, $clave, $rutaPDF, $conCredito, $conexionData, $idEnvios, $conn, $claveCliente)
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
    $vendedor = obtenerNombreVendedor($vendedor, $conexionData, $claveSae, $conn);
    // Obtener el nombre de la empresa desde la sesión
    $titulo = isset($_SESSION['empresa']['razonSocial']) ? $_SESSION['empresa']['razonSocial'] : 'Empresa Desconocida';

    // Asunto del correo
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // Convertir productos a JSON para la URL
    $productosJson = urlencode(json_encode($partidasData));

    // URL base del servidor
    //$urlBase = "https://mdconecta.mdcloud.mx/Servidor/PHP";
    //$urlBase = "https://mdconecta.mdcloud.app/Servidor/PHP";
    $urlBase = "http://localhost/NewMDConnecta/Servidor/PHP";
    // URLs para confirmar o rechazar el pedido
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&idEnvios=" . urlencode($idEnvios) . "&claveCliente=" . urlencode($claveCliente);

    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&claveCliente=" . urlencode($claveCliente);

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
        //echo json_encode(['success' => false, 'message' => $resultado]);
        //die();
        throw new Exception("Error al enviar el correo");
    }
}
function enviarWhatsAppConPlantillaPdf($numeroWhatsApp, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idEnvios, $rutaPDFW, $filename)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Construir la URL para filtrar (usa el campo idPedido y noEmpresa)
    $collection = "DATOS_PEDIDO";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (idPedido y noEmpresa)
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "idPedido"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noPedido]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $idFirebasePedido = null;
    $direccion1Contacto = null;

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        // runQuery devuelve un array con un elemento por cada match
        if (isset($resultArray[0]['document'])) {
            $doc    = $resultArray[0]['document'];
            // si quieres el ID:
            $parts  = explode('/', $doc['name']);
            $idFirebasePedido = end($parts);
            // y para tomar tu campo direccion1Contacto:
            $fields = $doc['fields'];
            $direccion1Contacto = $fields['direccion1Contacto']['stringValue'] ?? null;
        }
    }

    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idFirebasePedido);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);

    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'] ?? $partida['CVE_ART'];
        $cantidad = $partida['cantidad'] ?? $partida['CANT'];
        $precioUnitario = $partida['precioUnitario'] ?? $partida['PREC'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $IMPORTE = $total;
        $productosStr .= "$producto - $cantidad unidades, ";

        $IMPU4 = $partida['iva'] ?? $partida['IMPU4'];
        $desc1 = $partida['descuento'] ?? $partida['DESC1'];

        $desProcentaje = ($desc1 / 100);

        $DES = $totalPartida * $desProcentaje;

        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);

        $IMP_TOT4 += $IMP_T4;
    }

    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;

    // ✅ Eliminar la última coma y espacios
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr));

    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $numeroWhatsApp,
        "type" => "template",
        "template" => [
            //"name" => "new_confirmar_pedido_pdf", // 📌 Nombre EXACTO en Meta Business Manager
            "name" => "confirmar_pedido_pdf", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
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
                        ["type" => "text", "text" => $clienteNombre], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $noPedido], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $productosStr], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($IMPORTE, 2)], // 📌 Lista de productos
                        ["type" => "text", "text" => $direccion1Contacto], // 📌 Lista de productos
                        ["type" => "text", "text" => "$" . number_format($DES_TOT, 2)], // 📌 Precio total
                        ["type" => "text", "text" => "$" . number_format($IMP_TOT4, 2)], // 📌 Lista de productos
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
function enviarWhatsAppConPlantilla($numero, $clienteNombre, $noPedido, $claveSae, $partidasData, $enviarA, $vendedor, $fechaElaboracion, $noEmpresa, $clave, $conCredito, $claveCliente, $idEnvios)
{
    //$url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    //$token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $urlConfirmar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&noEmpresa=" . urlencode($noEmpresa) . "&clave=" . urlencode($clave) . "&conCredito=" . urlencode($conCredito) . "&claveCliente=" . urlencode($claveCliente) . "&idEnvios=" . urlencode($idEnvios);
    $urlRechazar = urlencode($noPedido) . "&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor) . "&fechaElab=" . urlencode($fechaElaboracion) . "&claveSae=" . urlencode($claveSae) . "&clave=" . urlencode($clave) . "&noEmpresa=" . urlencode($noEmpresa);

    // ✅ Construir la lista de productos
    $productosStr = "";
    $total = 0;
    $DES_TOT = 0;
    $IMPORTE = 0;
    $IMP_TOT4 = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'] ?? $partida['CVE_ART'];
        $cantidad = $partida['cantidad'] ?? $partida['CANT'];
        $precioUnitario = $partida['precioUnitario'] ?? $partida['PREC'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;
        $IMPORTE = $total;
        $productosStr .= "$producto - $cantidad unidades, ";

        $IMPU4 = $partida['iva'] ?? $partida['IMPU4'];
        $desc1 = $partida['descuento'] ?? $partida['DESC1'];

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
function guardarPedidoAutorizado($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $conn, $FOLIO, $idEnvios)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Validar que se cuente con los datos mínimos requeridos
    if (empty($FOLIO) || empty($formularioData['cliente'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos del pedido.']);
        return;
    }
    $FOLIO = (string)$FOLIO;
    // Agregar la descripción del producto a cada partida
    $SUBTOTAL = 0;
    $IMPORTE = 0;
    $descuentoCliente = $formularioData['descuentoCliente']; // Valor del descuento en porcentaje (ejemplo: 10 para 10%)
    foreach ($partidasData as $partida) {
        $SUBTOTAL += $partida['cantidad'] * $partida['precioUnitario']; // Sumar cantidades totales
        $IMPORTE += $partida['cantidad'] * $partida['precioUnitario']; // Calcular importe total
    }
    $IMPORTT = $IMPORTE;
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
    foreach ($partidasData as &$partida) {  // 🔹 Pasar por referencia para modificar el array
        $CVE_ART = $partida['producto'];
        $partida['descripcion'] = obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae, $conn);
    }
    // Preparar los campos que se guardarán en Firebase
    $fields = [
        'folio'       => ['stringValue' => (string)$FOLIO],
        'cliente'     => ['stringValue' => $formularioData['cliente']],
        'ordenCompra' => ['stringValue' => $formularioData['ordenCompra']],
        'enviar'      => ['stringValue' => $formularioData['enviar'] ?? ''],
        'vendedor'    => ['stringValue' => $formularioData['vendedor'] ?? ''],
        'diaAlta'     => ['stringValue' => $formularioData['fechaAlta'] ?? ''],
        'partidas'    => [
            'arrayValue' => ['values' => array_map(function ($p) {
                return [
                    'mapValue' => ['fields' => [
                        'cantidad'       => ['stringValue' => $p['cantidad']],
                        'producto'       => ['stringValue' => $p['producto']],
                        'unidad'         => ['stringValue' => $p['unidad']],
                        'descuento'      => ['stringValue' => $p['descuento']],
                        'ieps'           => ['stringValue' => $p['ieps']],
                        'impuesto2'      => ['stringValue' => $p['impuesto2']],
                        'isr'            => ['stringValue' => $p['isr']],
                        'iva'            => ['stringValue' => $p['iva']],
                        'comision'       => ['stringValue' => $p['comision']],
                        'precioUnitario' => ['stringValue' => $p['precioUnitario']],
                        'subtotal'       => ['stringValue' => $p['subtotal']],
                        'descripcion'    => ['stringValue' => $p['descripcion']],
                    ]]
                ];
            }, $partidasData)]
        ],
        'importe'    => ['doubleValue' => $IMPORTE],
        'claveSae'   => ['stringValue' => $claveSae],
        'noEmpresa'  => ['integerValue' => $noEmpresa],
        'status'     => ['stringValue' => 'Sin Autorizar'],
        'idEnvios' => ['stringValue' => $idEnvios],
    ];

    // Finalmente, enviamos todo a Firestore
    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/PEDIDOS_AUTORIZAR?key=$firebaseApiKey";

    $payload = json_encode(['fields' => $fields]);
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => $error['message']]);
        exit;
    }
    //echo json_encode(['success' => true, 'message' => 'Pedido guardado y en espera de ser autorizado.']);
    //exit;
}
function obtenerDescripcionProducto($CVE_ART, $conexionData, $claveSae, $conn)
{
    // Aquí puedes realizar una consulta para obtener la descripción del producto basado en la clave
    // Asumiendo que la descripción está en una tabla llamada "productos"
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT DESCR FROM $nombreTabla WHERE CVE_ART = ?";
    $stmt = sqlsrv_query($conn, $sql, [$CVE_ART]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $descripcion = $row ? $row['DESCR'] : '';

    sqlsrv_free_stmt($stmt);

    return $descripcion;
}
function enviarWhatsAppAutorizacion($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $credito, $conn, $FOLIO)
{

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Configuración de la API de WhatsApp
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // Obtener datos del pedido
    $noPedido = $FOLIO;
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
        //echo json_encode(['success' => false, 'message' => 'El vendedor no tiene datos registrados.']);
        //sqlsrv_close($conn);
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
        //echo json_encode(['success' => false, 'message' => 'El cliente no tiene datos registrados.']);
        //sqlsrv_close($conn);
        return;
    }
    $vendedor = trim(($vendedorData['NOMBRE']));

    //$clienteNombre = trim($clienteData['NOMBRE']);
    //$numero = trim($clienteData['TELEFONO']); // Si no hay teléfono registrado, usa un número por defecto
    //$numero = "+527772127123"; //InterZenda AutorizaTelefono
    $numero = "+527773750925";
    //$_SESSION['usuario']['telefono'];
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
    return $result;
}
// -----------------------------------------------------------------------------------------------------//
// Verificar si la sesión ya está iniciada antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        $formularioData = json_decode($_POST['formulario'], true); // Datos del formulario desde JS
        $flagWhats = $formularioData['enviarWhats'] ?? false;
        $flagCorreo = $formularioData['enviarCorreo'] ?? false;

        $csrf_token  = $_SESSION['csrf_token'];
        $csrf_token_form = $formularioData['token'];
        if ($csrf_token === $csrf_token_form) {
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

            // Formatear los datos
            $formularioData = formatearFormulario($formularioData);
            $partidasData = formatearPartidas($partidasData);
            $conn = sqlsrv_connect($conexionData['host'], [
                "Database" => $conexionData['nombreBase'],
                "UID"      => $conexionData['usuario'],
                "PWD"      => $conexionData['password'],
                "CharacterSet"         => "UTF-8",
                "TrustServerCertificate" => true
            ]);
            if (!$conn) {
                throw new Exception("No pude conectar a la base de datos");
            }
            // Inicio transacción:
            sqlsrv_begin_transaction($conn);
            try {
                $resultadoValidacion = validarExistencias($conexionData, $partidasData, $claveSae, $conn);
                if ($resultadoValidacion['success']) {
                    $totalPedido = calcularTotalPedido($partidasData);
                    $clienteId = $formularioData['cliente'];
                    $clave = formatearClaveCliente($clienteId);
                    ///Inicio

                    // Validar crédito del cliente
                    $validacionCredito = validarCreditoCliente($conexionData, $clave, $totalPedido, $claveSae, $conn);
                    if ($validacionCredito['success']) {
                        $credito = '0';
                    } else {
                        $credito = '1';
                    }
                    $validarSaldo = validarSaldo($conexionData, $clave, $claveSae, $conn);
                    //$validarSaldo = 1;
                    $estatus = "E";
                    /*$estatus = "E";
                            $validarSaldo = 0;
                            $credito = 0;*/
                    $conCredito = $formularioData['conCredito'];
                    $DAT_ENVIO = gaurdarDatosEnvio($conexionData, $clave, $formularioData, $envioData, $claveSae, $conn); //ROLLBACK
                    actualizarControl2($conexionData, $claveSae, $conn); //ROLLBACK
                    $FOLIO = guardarPedido($conexionData, $formularioData, $partidasData, $claveSae, $estatus, $DAT_ENVIO, $conn, $conCredito); //ROLLBACK
                    //guardarPedidoClib($conexionData, $formularioData, $partidasData, $claveSae, $estatus, $DAT_ENVIO);
                    //actualizarFolio($conexionData, $claveSae, $conn); //ROLLBACK
                    //actualizarDatoEnvio($DAT_ENVIO, $claveSae, $noEmpresa, $firebaseProjectId, $firebaseApiKey, $envioData); //ROLLBACK
                    guardarPartidas($conexionData, $formularioData, $partidasData, $claveSae, $conn, $FOLIO); //ROLLBACK
                    actualizarApartados($conexionData, $partidasData, $conn);
                    actualizarInventario($conexionData, $partidasData, $conn); //ROLLBACK
                    if ($validarSaldo == 0 && $credito == 0) {
                        $idEnvios = guardarDatosPedido($envioData, $FOLIO, $noEmpresa, $formularioData);
                        $rutaPDF = generarPDFP($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $FOLIO, $conn);
                        validarCorreoCliente($formularioData, $partidasData, $conexionData, $rutaPDF, $claveSae, $conCredito, $conn, $FOLIO, $idEnvios, $flagCorreo, $flagWhats);


                        sqlsrv_commit($conn);
                        sqlsrv_close($conn);
                        exit();
                    } else {
                        $idEnvios = guardarDatosPedido($envioData, $FOLIO, $noEmpresa, $formularioData);
                        guardarPedidoAutorizado($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $conn, $FOLIO, $idEnvios);
                        $resultado = enviarWhatsAppAutorizacion($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $validarSaldo, $credito, $conn, $FOLIO);
                        header('Content-Type: application/json; charset=UTF-8');
                        echo json_encode([
                            'success' => false,
                            'autorizacion' => true,
                            'message' => 'El pedido se completó pero debe ser autorizado.',
                        ]);

                        $camposModulo = [
                            'quienCreo' => $_SESSION['usuario']['nombre'],
                            'pedidoID' => $FOLIO,
                            'clienteID' => $clienteId,
                            'productos' => $partidasData,
                        ];
                        agregarBitacora($_SESSION['empresa']['claveUsuario'] ?? $_SESSION['usuario']['usuario'], "PEDIDOS", "Pedido Autorizado", $noEmpresa, $camposModulo);

                        sqlsrv_commit($conn);
                        sqlsrv_close($conn);
                        exit();
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
            } catch (Exception $e) {
                // Si falla cualquiera, deshacemos TODO:
                sqlsrv_rollback($conn);
                sqlsrv_close($conn);
                //return ['success' => false, 'message' => $e->getMessage()];
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
