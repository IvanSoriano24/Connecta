<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
//require_once 'whatsapp.php';
//require_once 'clientes.php';

session_start();

function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey)
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
function obtenerPedidoEspecifico($clave, $conexionData)
{
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Limpiar la clave y convertirla a UTF-8
    $clave = mb_convert_encoding(trim($clave), 'UTF-8');
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
}
// Función para conectar a SQL Server y obtener los datos de clientes
function mostrarPedidos($conexionData, $filtroFecha)
{
    $filtroFecha = $_POST['filtroFecha'] ?? 'Todos';
    //$filtroFecha = "Mes";
    try {
        // Validar si el número de empresa está definido en la sesión
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        // Obtener el número de empresa de la sesión
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        // Validar el formato del número de empresa (asegurarse de que sea numérico)
        if (!is_numeric($noEmpresa)) {
            echo json_encode(['success' => false, 'message' => 'El número de empresa no es válido']);
            exit;
        }
        // Obtener tipo de usuario y clave de vendedor desde la sesión
        $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
        $claveVendedor = $_SESSION['empresa']['claveVendedor'];
        // Configuración de conexión
        $serverName = $conexionData['host'];
        $connectionInfo = [
            "Database" => $conexionData['nombreBase'], // Nombre de la base de datos
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password']
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if ($conn === false) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }
        $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');
        // Construir el nombre de la tabla dinámicamente usando el número de empresa
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
        // Construir la consulta SQL
        if ($tipoUsuario === 'ADMINISTRADOR') {
            // Si el usuario es administrador, mostrar todos los clientes
            $sql = "SELECT 
            TIP_DOC AS Tipo,
                CVE_DOC AS Clave,
                CVE_CLPV AS Cliente,
                (SELECT MAX(NOMBRE) FROM $nombreTabla WHERE $nombreTabla.CLAVE = $nombreTabla2.CVE_CLPV) AS Nombre,
                STATUS AS Estatus,
                FECHAELAB AS FechaElaboracion,
                CAN_TOT AS Subtotal,
                COM_TOT AS TotalComisiones,
                NUM_ALMA AS NumeroAlmacen,
                FORMAENVIO AS FormaEnvio,
                IMPORTE AS ImporteTotal,
                (SELECT MAX(NOMBRE) FROM $nombreTabla3 WHERE $nombreTabla3.CVE_VEND = $nombreTabla2.CVE_VEND) AS NombreVendedor
            FROM $nombreTabla2
            WHERE STATUS IN ('E', 'O')";
            if ($filtroFecha == 'Hoy') {
                // Consulta para el día actual
                $sql .= " AND CAST(FECHAELAB AS DATE) = CAST(GETDATE() AS DATE)";
            } elseif ($filtroFecha == 'Mes') {
                // Consulta para el mes actual
                $sql .= " AND MONTH(FECHAELAB) = MONTH(GETDATE()) AND YEAR(FECHAELAB) = YEAR(GETDATE())";
            } elseif ($filtroFecha == 'Mes Anterior') {
                // Consulta para el mes anterior
                $sql .= " AND MONTH(FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE()))";
            } // Si el filtro es 'Todos', no se agrega ningún filtro adicional
            $stmt = sqlsrv_query($conn, $sql);
        } else {
            // Si el usuario no es administrador, filtrar por el número de vendedor
            $sql = "SELECT 
            TIP_DOC AS Tipo,
                CVE_DOC AS Clave,
                CVE_CLPV AS Cliente,
                (SELECT MAX(NOMBRE) FROM $nombreTabla WHERE $nombreTabla.CLAVE = $nombreTabla2.CVE_CLPV) AS Nombre,
                STATUS AS Estatus,
                FECHAELAB AS FechaElaboracion,
                CAN_TOT AS Subtotal,
                COM_TOT AS TotalComisiones,
                NUM_ALMA AS NumeroAlmacen,
                FORMAENVIO AS FormaEnvio,
                IMPORTE AS ImporteTotal,
                (SELECT MAX(NOMBRE) FROM $nombreTabla3 WHERE $nombreTabla3.CVE_VEND = $nombreTabla2.CVE_VEND) AS NombreVendedor
            FROM $nombreTabla2
            WHERE STATUS IN ('E', 'O') AND CVE_VEND = ?";
            if ($filtroFecha == 'Hoy') {
                // Consulta para el día actual
                $sql .= " AND CAST(FECHAELAB AS DATE) = CAST(GETDATE() AS DATE)";
            } elseif ($filtroFecha == 'Mes') {
                // Consulta para el mes actual
                $sql .= " AND MONTH(FECHAELAB) = MONTH(GETDATE()) AND YEAR(FECHAELAB) = YEAR(GETDATE())";
            } elseif ($filtroFecha == 'Mes Anterior') {
                // Consulta para el mes anterior
                $sql .= " AND MONTH(FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE()))";
            } // Si el filtro es 'Todos', no se agrega ningún filtro adicional
            $params = [intval($claveVendedor)];
            $stmt = sqlsrv_query($conn, $sql, $params);
        }
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }
        // Arreglo para almacenar los datos de clientes
        $clientes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                // Limpiar espacios en blanco solo si el valor no es null
                if ($value !== null && is_string($value)) {
                    $value = trim($value); // Eliminar espacios en blanco al principio y al final

                    // Verificar si el valor no está vacío antes de intentar convertirlo
                    if (!empty($value)) {
                        // Detectar la codificación del valor
                        $encoding = mb_detect_encoding($value, mb_list_encodings(), true);

                        // Si la codificación no se puede detectar o no es UTF-8, convertir la codificación
                        if ($encoding && $encoding !== 'UTF-8') {
                            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                        }
                    }
                } elseif ($value === null) {
                    // Si el valor es null, asignar un valor predeterminado
                    $value = '';
                }
                // Asignar el valor limpio al campo correspondiente
                $row[$key] = $value;
            }
            $clientes[] = $row;
        }
        // Liberar recursos y cerrar la conexión
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        // Retornar los datos en formato JSON
        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron pedidos']);
            exit;
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'data' => $clientes]);
    } catch (Exception $e) {
        // Si hay algún error, devuelves un error en formato JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function mostrarPedidoEspecifico($clave, $conexionData)
{
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
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    // Limpiar la clave y construir el nombre de la tabla
    $clave = mb_convert_encoding(trim($clave), 'UTF-8');
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $tablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

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
     c.[NOMBRE] AS NOMBRE_CLIENTE, c.[TELEFONO] AS TELEFONO_CLIENTE
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
        // Obtener partidas
        $sqlPartidas = "SELECT * FROM $tablaPartidas WHERE [CVE_DOC] = ?";
        $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $params);
        if ($stmtPartidas === false) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener partidas', 'errors' => sqlsrv_errors()]);
            exit;
        }

        $partidas = [];
        while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
            $partidas[] = $row;
        }

        $pedido['partidas'] = $partidas;

        echo json_encode(['success' => true, 'pedido' => $pedido]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
    }
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerDatosCliente($conexionData, $claveCliente)
{
    // Obtener solo la clave del cliente (primera parte antes del espacio)
    $claveArray = explode(' ', $claveCliente, 2); // Limitar a dos elementos
    $clave = $claveArray[0]; // Tomar solo la primera parte

    // Establecer la conexión con SQL Server
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
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
        WHERE CLAVE = $clave
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
function guardarPedido($conexionData, $formularioData, $partidasData)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $claveCliente = $formularioData['cliente'];
    $datosCliente = obtenerDatosCliente($conexionData, $claveCliente);
    if (!$datosCliente) {
        die(json_encode(['success' => false, 'message' => 'No se encontraron datos del cliente']));
    }
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    // Extraer los datos del formulario
    $FOLIO = $formularioData['numero'];
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $FECHA_DOC = $formularioData['diaAlta']; // Fecha del documento
    $FECHA_ENT = $formularioData['entrega'];
    // Sumar los totales de las partidas
    $CAN_TOT = 0; // Inicializar la variable para la cantidad total
    $IMPORTE = 0;
    foreach ($partidasData as $partida) {
        $CAN_TOT += $partida['cantidad']; // Acumula la cantidad total
        $IMPORTE += $partida['cantidad'] * $partida['precioUnitario']; // Suma al importe total
    }
    $CVE_VEND = str_pad($formularioData['claveVendedor'], 5, ' ', STR_PAD_LEFT);
    // Asignación de otros valores del formulario
    $IMP_TOT1 = 0;
    $IMP_TOT2 = 0;
    $IMP_TOT3 = 0;
    $IMP_TOT4 = $CAN_TOT * .16;
    $IMP_TOT5 = 0;
    $IMP_TOT6 = 0;
    $IMP_TOT7 = 0;
    $IMP_TOT8 = 0;
    $DES_TOT = $formularioData['descuento'];
    $DES_FIN = $formularioData['descuento'];
    $CONDICION = $formularioData['condicion'];
    $RFC = $formularioData['rfc'];
    $FECHA_ELAB = $formularioData['diaAlta'];
    $TIP_DOC = $formularioData['factura'];
    $NUM_ALMA = $formularioData['almacen'];
    $FORMAENVIO = 'C';
    $COM_TOT = $formularioData['comision'];
    $DAT_ENVIO = 1; //Telefono
    $CVE_OBS = $datosCliente['CVE_OBS'];
    $CVE_BITA = $datosCliente['CVE_BITA'];
    //$COM_TOT_PORC = $datosCliente['COM_TOT_PORC']; //VENDEDOR
    $METODODEPAGO = $datosCliente['METODODEPAGO'];
    $NUMCTAPAGO = $datosCliente['NUMCTAPAGO'];
    $FORMADEPAGOSAT = $datosCliente['FORMADEPAGOSAT'];
    $USO_CFDI = $datosCliente['USO_CFDI'];
    $REG_FISC = $datosCliente['REG_FISC'];
    $ENLAZADO = 0; ////
    $TIP_DOC_E = 0; ////
    $DES_TOT_PORC = 0; ////
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
            'data' => $claveCliente
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
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    // Iniciar la transacción para las inserciones de las partidas
    sqlsrv_begin_transaction($conn);
    // Iterar sobre las partidas recibidas
    if (isset($partidasData) && is_array($partidasData)) {
        foreach ($partidasData as $partida) {
            // Extraer los datos de la partida
            $CVE_DOC = str_pad($formularioData['numero'], 20, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
            $CVE_ART = $partida['producto']; // Clave del producto
            $CANT = $partida['cantidad']; // Cantidad
            $PREC = $partida['precioUnitario']; // Precio
            $NUM_PAR = $formularioData['numero'];
            // Calcular los impuestos y totales
            $IMPU1 = $partida['ieps']; // Impuesto 1
            $IMPU3 = $partida['isr'];
            //$IMPU1 = 0;
            //$IMPU2 = $partida['impuesto2']; // Impuesto 2
            $IMPU2 = 0;
            $IMPU4 = $partida['iva']; // Impuesto 2
            // Agregar los cálculos para los demás impuestos...
            $PXS = 0;
            $DESC1 = $partida['descuento1'];
            $DESC2 = $partida['descuento2'];
            $COMI = $partida['comision'];
            $NUM_ALMA = $formularioData['almacen'];
            $UNI_VENTA = $partida['unidad'];
            if ($UNI_VENTA === 'No aplica' || $UNI_VENTA === 'SERVICIO' || $UNI_VENTA === 'Servicio') {
                $TIPO_PORD = 'S';
            } else {
                $TIPO_PORD = 'P';
            }
            $TOTIMP1 = $IMPU1 * $CANT * $PREC; // Total impuesto 1
            $TOTIMP2 = $IMPU2 * $CANT * $PREC; // Total impuesto 2
            $TOTIMP4 = $IMPU4 * $CANT * $PREC; // Total impuesto 4
            // Agregar los cálculos para los demás TOTIMP...

            // Calcular el total de la partida (precio * cantidad)
            $TOT_PARTIDA = $PREC * $CANT;

            // Consultar la descripción del producto (si es necesario)
            $DESCR_ART = obtenerDescripcionProducto($CVE_ART, $conexionData, $noEmpresa);

            // Crear la consulta SQL para insertar los datos de la partida
            $sql = "INSERT INTO $nombreTabla
                (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA,
                TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4,
                DESC1, DESC2, DESC3, COMI, APAR,
                ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, TIPO_PROD, CVE_OBS, REG_SERIE, E_LTPD, TIPO_ELEM, 
                NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, UUID,
                VERSION_SINC, DESCR_ART, ID_RELACION, PREC_NETO,
                CVE_PRODSERV, CVE_UNIDAD, IMPU8, IMPU7, IMPU6, IMPU5, IMP5APLA,
                IMP6APLA, TOTIMP8, TOTIMP7, TOTIMP6, TOTIMP5, IMP8APLA, IMP7APLA)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 4, 4, 4, 4,
                ?, ?, 0, ?,
                ?, ?, 0, 0, ?,
                'N', ?, '', 1, ?, ?, 0, 0, 0, 'N',
                0, ?, 'S', 'N', 0, 0, 0, 0, 0, 0, 0,
                0, ?, '', '',
                0, '', '', 0, 0, 0, 0,
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
                $DESCR_ART
            ];
            // Ejecutar la consulta
            $stmt = sqlsrv_query($conn, $sql, $params);
            //var_dump($stmt);
            if ($stmt === false) {
                //var_dump(sqlsrv_errors()); // Muestra los errores específicos
                sqlsrv_rollback($conn);
                die(json_encode(['success' => false, 'message' => 'Error al insertar la partida', 'errors' => sqlsrv_errors()]));
            }
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
function obtenerDescripcionProducto($CVE_ART, $conexionData, $noEmpresa)
{
    // Aquí puedes realizar una consulta para obtener la descripción del producto basado en la clave
    // Asumiendo que la descripción está en una tabla llamada "productos"
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ]);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
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
function actualizarFolio($conexionData)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

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
function actualizarInventario($conexionData, $partidasData)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
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
function obtenerFolioSiguiente($conexionData)
{
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Consulta SQL para obtener el siguiente folio
    $sql = "SELECT (ULT_DOC + 1) AS FolioSiguiente FROM FOLIOSF02 WHERE TIP_DOC = 'P'";
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
function validarCorreoCliente($formularioData, $partidasData, $conexionData)
{
    // Establecer la conexión con SQL Server
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $claveCliente = $formularioData['cliente'];
    $noPedido = $formularioData['numero']; // Número de pedido
    $claveArray = explode(' ', $claveCliente, 2); // Obtener clave del cliente
    $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener MAIL y EMAILPRED
    $sql = "SELECT MAIL, EMAILPRED, NOMBRE FROM $nombreTabla WHERE [CLAVE] = ?";
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

    $correo = trim($clienteData['MAIL']);
    $emailPred = trim($clienteData['EMAILPRED']);
    $clienteNombre = trim($clienteData['NOMBRE']);

    if ($correo === 'S' && !empty($emailPred)) {
        $numeroWhatsApp = '+527773750925';
        enviarCorreo($emailPred, $clienteNombre, $noPedido, $partidasData); // Enviar correo
        error_log("Llamando a enviarWhatsApp con el número $numeroWhatsApp"); // Registro para depuración
        $resultadoWhatsApp = enviarWhatsApp($numeroWhatsApp, $clienteNombre, $noPedido, $partidasData);
        var_dump($resultadoWhatsApp);
    } else {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene un correo electrónico válido registrado.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
// Función para enviar el correo (en desarrollo)
function enviarCorreo($correo, $clienteNombre, $noPedido, $partidasData)
{
    // Crear una instancia de la clase clsMail
    $mail = new clsMail();
    $correo = 'desarrollo01@mdcloud.mx';
    // Título y asunto
    $titulo = 'Notificación de Pedido';
    $asunto = 'Detalles del Pedido #' . $noPedido;

    // URLs para confirmar o rechazar, apuntando a la carpeta del servidor
    $urlBase = "http://localhost/MDConnecta/Servidor/PHP";
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar";
    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar";

    // Construir cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los productos de su pedido <b>$noPedido</b>. Por favor, revíselos y confirme:</p>";

    // Agregar detalles del pedido
    $bodyHTML .= "<table style='border-collapse: collapse; width: 100%;' border='1'>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Total Partida</th>
                        </tr>
                    </thead>
                    <tbody>";
    $total = 0;

    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $totalPartida = $partida['cantidad'] * $partida['precioUnitario'];
        $total += $totalPartida;

        $bodyHTML .= "<tr>
                        <td>$producto</td>
                        <td>$cantidad</td>
                        <td>$" . number_format($totalPartida, 2) . "</td>
                      </tr>";
    }

    $bodyHTML .= "</tbody></table>";
    $bodyHTML .= "<p><b>Total:</b> $" . number_format($total, 2) . "</p>";

    // Agregar botones
    $bodyHTML .= "<p>Confirme su pedido seleccionando una opción:</p>
                  <a href='$urlConfirmar' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar</a>
                  <a href='$urlRechazar' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Rechazar</a>";

    $bodyHTML .= "<p>Saludos cordiales,</p><p>Su equipo de soporte.</p>";

    // Enviar correo
    $resultado = $mail->metEnviar($titulo, $clienteNombre, $correo, $asunto, $bodyHTML);
    // Imprimir el resultado del envío del correo
    echo $resultado;
}
/*function enviarWhatsApp($numero, $nombreCliente, $noPedido, $partidasData)
{
    $url = 'https://graph.facebook.com/v21.0/530466276818765/messages';
$token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD';
 
$nombre = "Sun Arrow";
$data = array(
    "messaging_product" => "whatsapp",
    "recipient_type" => "individual",
    //"to" => "+527773340218",
    "to" => "+527773750925",
    "type" => "template",
    "template" => array(
        "name" => "hello_world",
        "language" => array(
            "code" => "en_US"
        )
    )
);
 
$data_string = json_encode($data);
 
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string))
);
 
$result = curl_exec($curl);
curl_close($curl);
//echo $result;
}*/
function enviarWhatsApp($numero, $nombreCliente, $noPedido, $partidasData)
{
    $url = 'https://graph.facebook.com/v21.0/530466276818765/messages';
    $token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD';

    // Construir el cuerpo del mensaje
    $mensaje = "Estimado/a $nombreCliente,\n";
    $mensaje .= "Detalles de su pedido #$noPedido:\n\n";

    $total = 0;
    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = number_format($partida['precioUnitario'], 2);
        $totalPartida = $partida['cantidad'] * $partida['precioUnitario'];
        $total += $totalPartida;

        $mensaje .= "- $producto: $cantidad unidades (Precio Unitario: $$precioUnitario)\n";
    }

    $mensaje .= "\nTotal del Pedido: $" . number_format($total, 2) . "\n";
    $mensaje .= "\nGracias por su compra. Por favor confirme su pedido.";

    // Datos de la solicitud
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numero, // Número del cliente
        "type" => "text",
        "text" => [
            "body" => $mensaje
        ]
    ];

    $data_string = json_encode($data);

    // Configurar y enviar la solicitud
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ]);

    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_status !== 200) {
        return [
            'success' => false,
            'message' => 'Error al enviar el mensaje por WhatsApp',
            'status' => $http_status,
            'response' => $result
        ];
    }

    return [
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
        'response' => json_decode($result, true)
    ];
}


function obtenerClientePedido($claveVendedor, $conexionData, $clienteInput)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
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
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    /*$sql = "SELECT DISTINCT 
                [CLAVE], [NOMBRE], [CALLE], [RFC], [NUMINT], [NUMEXT], [COLONIA], [CODIGO],
                [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS], [TELEFONO], [LISTA_PREC]
            FROM $nombreTabla
            WHERE [CLAVE] = '$clienteClave' OR LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER ('$clienteNombre')
              AND [CVE_VEND] = '$claveVendedor'";*/
    if (preg_match('/[a-zA-Z]/', $clienteInput)) {
        // Búsqueda por nombre
        $sql = "SELECT DISTINCT 
                [CLAVE], [NOMBRE], [CALLE], [RFC], [NUMINT], [NUMEXT], [COLONIA], [CODIGO],
                [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS], [TELEFONO], [LISTA_PREC]
            FROM $nombreTabla
            WHERE LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER ('$clienteNombre') OR [CLAVE] = '$clienteClave'
              AND [CVE_VEND] = '$claveVendedor'";
    } else {
        // Búsqueda por clave
        $sql = "SELECT DISTINCT 
                [CLAVE], [NOMBRE], [CALLE], [RFC], [NUMINT], [NUMEXT], [COLONIA], [CODIGO],
                [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS], [TELEFONO], [LISTA_PREC]
            FROM $nombreTabla
            WHERE [CLAVE] = '$clienteClave' OR LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER ('$clienteNombre')
              AND [CVE_VEND] = '$claveVendedor'";
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

function obtenerProductos($conexionData)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];

    // Intentar conectarse a la base de datos
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL
    $sql = "SELECT TOP (1000) [CVE_ART], [DESCR], [EXIST], [LIN_PROD], [UNI_MED], [CVE_ESQIMPU]
        FROM $nombreTabla";

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

function obtenerPrecioProducto($conexionData, $claveProducto, $listaPrecioCliente, $noEmpresa)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PRECIO_X_PROD" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
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

function obtenerImpuesto($conexionData, $cveEsqImpu, $noEmpresa)
{
    ob_start(); // Inicia el buffer de salida para evitar texto adicional

    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];

    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        header('Content-Type: application/json; charset=utf-8');
        ob_end_clean();
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $cveEsqImpu = mb_convert_encoding(trim($cveEsqImpu), 'UTF-8');
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[IMPU" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT IMPUESTO1, IMPUESTO2, IMPUESTO3, IMPUESTO4 FROM $nombreTabla WHERE CVE_ESQIMPU = ?";
    $params = [$cveEsqImpu];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        header('Content-Type: application/json; charset=utf-8');
        ob_end_clean();
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
    ob_end_clean(); // Limpia cualquier salida antes de enviar la respuesta

    if ($impuestos !== null) {
        echo json_encode(['success' => true, 'impuestos' => $impuestos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron impuestos para la clave especificada.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function validarExistencias($conexionData, $partidasData)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
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
function validarCreditoCliente($conexionData, $clienteId, $totalPedido)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $sql = "SELECT LIMCRED, SALDO FROM [SAE90Empre02].[dbo].[CLIE02] WHERE [CLAVE] = ?";
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

function obtenerPartidasPedido($conexionData, $clavePedido)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Tabla dinámica basada en el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Consultar partidas del pedido
    $sql = "SELECT CVE_DOC, NUM_PAR, CVE_ART, CANT, PREC, IMPU1, IMPU4, DESC1, DESC2, TOT_PARTIDA, DESCR_ART, COMI 
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
            'CVE_ART' => $row['CVE_ART'],
            'CANT' => $row['CANT'],
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
function eliminarPartida($conexionData, $clavePedido, $numPar)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Nombre de la tabla dinámico basado en la empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta para eliminar la partida
    $sql = "DELETE FROM $nombreTabla WHERE CVE_DOC = ? AND NUM_PAR = ?";
    $stmt = sqlsrv_query($conn, $sql, [$clavePedido, $numPar]);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la partida', 'errors' => sqlsrv_errors()]);
        sqlsrv_close($conn);
        exit;
    }

    $filasAfectadas = sqlsrv_rows_affected($stmt);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    if ($filasAfectadas > 0) {
        echo json_encode(['success' => true, 'message' => 'Partida eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la partida especificada.']);
    }
}

function eliminarPedido($conexionData, $pedidoID) {
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8"
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    $clave = str_pad($pedidoID, 10, ' ', STR_PAD_LEFT);
    // Nombre de la tabla dinámico basado en la empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Actualizar el estatus del pedido
    $query = "UPDATE $nombreTabla SET STATUS = 'C' WHERE CVE_DOC = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$clave]);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta', 'errors' => sqlsrv_errors()]);
        exit;
    }

    if (sqlsrv_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estatus del pedido', 'errors' => sqlsrv_errors()]);
    }

    sqlsrv_close($conn);
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

//var_dump($funcion);
switch ($funcion) {
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
        $filtroFecha = $_POST['filtroFecha'];
        mostrarPedidos($conexionData, $filtroFecha);
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

        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
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
        mostrarPedidoEspecifico($clave, $conexionData, $noEmpresa);
        break;
        //Nuevo       
    case 3:
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

        $conexionData = $conexionResult['data'];

        // Validar la acción solicitada
        $accion = isset($_POST['accion']) ? $_POST['accion'] : null;

        if ($accion === 'obtenerFolioSiguiente') {
            // Obtener el siguiente folio
            $folioSiguiente = obtenerFolioSiguiente($conexionData);
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
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $clave = $_POST['clave'];
        $cliente = $_POST['cliente'];
        obtenerClientePedido($clave, $conexionData, $cliente);
        break;
    case 5:
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
        obtenerProductos($conexionData);
        break;
    case 6:
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
        $claveProducto = $_GET['claveProducto'];
        $listaPrecioCliente = $_GET['listaPrecioCliente'];
        obtenerPrecioProducto($conexionData, $claveProducto, $listaPrecioCliente, $noEmpresa);
        break;
    case 7:
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
        $conexionData = $conexionResult['data'];
        $cveEsqImpu = $_POST['cveEsqImpu'];
        obtenerImpuesto($conexionData, $cveEsqImpu, $noEmpresa);
        break;

    case 8:
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

        $formularioData = json_decode($_POST['formulario'], true); // Datos del formulario desde JS
        $partidasData = json_decode($_POST['partidas'], true); // Datos de las partidas desde JS
        $conexionData = $conexionResult['data'];

        // Validar existencias antes de realizar cualquier otra operación
        $resultadoValidacion = validarExistencias($conexionData, $partidasData);

        if ($resultadoValidacion['success']) {
            // Calcular el total del pedido
            $totalPedido = calcularTotalPedido($partidasData);
            $clienteId = $formularioData['cliente'];
            $claveArray = explode(' ', $clienteId, 2); // Obtener clave del cliente
            $clave = str_pad($claveArray[0], 10, ' ', STR_PAD_LEFT);

            // Validar crédito del cliente
            $validacionCredito = validarCreditoCliente($conexionData, $clave, $totalPedido);

            if ($validacionCredito['success']) {
                // Si la validación de crédito es exitosa, proceder con las demás operaciones
                //guardarPedido($conexionData, $formularioData, $partidasData);
                //guardarPartidas($conexionData, $formularioData, $partidasData);
                //actualizarFolio($conexionData);
                //actualizarInventario($conexionData, $partidasData);
                validarCorreoCliente($formularioData, $partidasData, $conexionData);

                // Respuesta de éxito al frontend
                echo json_encode([
                    'success' => true,
                    'message' => 'El pedido se completó correctamente.'
                ]);
            } else {
                // Si no pasa la validación de crédito, detener el proceso
                echo json_encode([
                    'success' => false,
                    'credit' => true,
                    'message' => 'Limite de Credito.',
                    'saldoActual' => $validacionCredito['saldoActual'],
                    'limiteCredito' => $validacionCredito['limiteCredito']
                ]);
            }
        } else {
            // Si no hay existencias, retornar detalles al frontend
            echo json_encode([
                'success' => false,
                'exist' => true,
                'message' => $resultadoValidacion['message'],
                'productosSinExistencia' => $resultadoValidacion['productosSinExistencia']
            ]);
        }
        break;
    case 9:
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

        $conexionData = $conexionResult['data'];

        if (!isset($_POST['clavePedido']) || empty($_POST['clavePedido'])) {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó la clave del pedido']);
            exit;
        }

        if (!isset($_POST['numPar']) || empty($_POST['numPar'])) {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó el número de partida']);
            exit;
        }

        $clavePedido = $_POST['clavePedido'];
        $numPar = $_POST['numPar'];

        eliminarPartida($conexionData, $clavePedido, $numPar);
        break;
    case 10:
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
        $conexionData = $conexionResult['data'];
        $pedidoID = $_POST['pedidoID'];
        eliminarPedido($conexionData, $pedidoID);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
