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
        "CharacterSet" => "UTF-8", // Aseguramos que todo sea manejado en UTF-8
        "TrustServerCertificate" => true
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
function mostrarPedidos($conexionData, $filtroFecha){
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
            "PWD" => $conexionData['password'],
            "TrustServerCertificate" => true
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
/*function mostrarPedidos($conexionData, $filtroFecha)
{
    $filtroFecha = $_POST['filtroFecha'] ?? 'Todos';

    try {
        // Validar si el número de empresa está definido en la sesión
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }

        // Obtener datos de la sesión
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
        $claveVendedor = $_SESSION['empresa']['claveVendedor'];

        // Validar que el número de empresa sea numérico
        if (!is_numeric($noEmpresa)) {
            echo json_encode(['success' => false, 'message' => 'El número de empresa no es válido']);
            exit;
        }

        // Configuración de conexión
        $conn = sqlsrv_connect($conexionData['host'], [
            "Database" => $conexionData['nombreBase'],
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password']
        ]);

        if (!$conn) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }

        // Definir nombres de las tablas
        $nombreTablaClientes = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTablaVendedores = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

        // **1️⃣ Construir la consulta base**
        $sql = "
            SELECT 
                f.TIP_DOC AS Tipo,
                f.CVE_DOC AS Clave,
                f.CVE_CLPV AS Cliente,
                c.NOMBRE AS Nombre,
                f.STATUS AS Estatus,
                f.FECHAELAB AS FechaElaboracion,
                f.CAN_TOT AS Subtotal,
                f.COM_TOT AS TotalComisiones,
                f.IMPORTE AS ImporteTotal,
                v.NOMBRE AS NombreVendedor
            FROM $nombreTablaPedidos f
            LEFT JOIN $nombreTablaClientes c ON c.CLAVE = f.CVE_CLPV
            LEFT JOIN $nombreTablaVendedores v ON v.CVE_VEND = f.CVE_VEND
            WHERE f.STATUS IN ('E', 'O') ";

        // **2️⃣ Agregar filtro de fecha si es necesario**
        if ($filtroFecha == 'Hoy') {
            $sql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE)";
        } elseif ($filtroFecha == 'Mes') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE())";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE()))";
        }

        // **3️⃣ Definir los parámetros de sp_executesql**
        $paramsSQL = "";
        $params = [];

        if ($tipoUsuario !== 'ADMINISTRADOR') {
            $sql .= " AND f.CVE_VEND = @claveVendedor";
            $paramsSQL .= "@claveVendedor INT";
            $params[] = intval($claveVendedor);
        }

        // **4️⃣ Preparar sp_executesql correctamente**
        $sqlExec = "EXEC sp_executesql N'$sql'";

        if (!empty($paramsSQL)) {
            $sqlExec .= ", N'$paramsSQL'";
            foreach ($params as $param) {
                $sqlExec .= ", ?";
            }
        }

        // **5️⃣ Ejecutar la consulta**
        $stmt = sqlsrv_query($conn, $sqlExec, $params);

        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        // **6️⃣ Recoger resultados**
        $pedidos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                $row[$key] = $value !== null && is_string($value) ? trim(mb_convert_encoding($value, 'UTF-8')) : ($value ?? '');
            }
            $pedidos[] = $row;
        }

        // **7️⃣ Cerrar conexión y devolver resultados**
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        echo json_encode(['success' => !empty($pedidos), 'data' => $pedidos]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}*/
function mostrarPedidoEspecifico($clave, $conexionData)
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
function actualizarPedido($conexionData, $formularioData, $partidasData)
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Extraer los datos del formulario
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);
    $FECHA_DOC = $formularioData['diaAlta'];
    $FECHA_ENT = $formularioData['entrega'];
    $CAN_TOT = 0;
    $IMPORTE = 0;

    foreach ($partidasData as $partida) {
        $CAN_TOT += $partida['cantidad'];
        $IMPORTE += $partida['cantidad'] * $partida['precioUnitario'];
    }

    $CVE_VEND = str_pad($formularioData['claveVendedor'], 5, ' ', STR_PAD_LEFT);
    $IMP_TOT4 = $CAN_TOT * 0.16;
    $DES_TOT = $formularioData['descuento'];
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
        CVE_VEND = ? 
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
        $CVE_DOC
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el pedido', 'errors' => sqlsrv_errors()]));
    }

    // Actualizar las partidas asociadas al pedido
    actualizarPartidas($conexionData, $formularioData, $partidasData);

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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);

    // Iniciar transacción
    sqlsrv_begin_transaction($conn);

    // **1. Ajustar el inventario antes de modificar las partidas**
    $resultadoInventario = actualizarNuevoInventario($conexionData, $formularioData, $partidasData);
    if (!$resultadoInventario['success']) {
        sqlsrv_rollback($conn);
        die(json_encode($resultadoInventario));
    }

    // **2. Obtener partidas existentes para comparar con las nuevas**
    $query = "SELECT CVE_ART, NUM_PAR FROM $nombreTabla WHERE CVE_DOC = ?";
    $stmt = sqlsrv_query($conn, $query, [$CVE_DOC]);
    if ($stmt === false) {
        sqlsrv_rollback($conn);
        die(json_encode(['success' => false, 'message' => 'Error al obtener partidas existentes', 'errors' => sqlsrv_errors()]));
    }

    $partidasExistentes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidasExistentes[$row['CVE_ART']] = $row['NUM_PAR'];
    }
    sqlsrv_free_stmt($stmt);

    // **3. Actualizar o insertar las partidas**
    foreach ($partidasData as $partida) {
        // Extraer los datos de la partida
        $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
        $CVE_DOC = str_pad($CVE_DOC, 10, ' ', STR_PAD_LEFT);
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
        if (isset($partidasExistentes[$CVE_ART])) {
            // Si la partida ya existe, realizar un UPDATE
            $sql = "UPDATE $nombreTabla SET 
                CANT = ?, PREC = ?, IMPU1 = ?, IMPU4 = ?, DESC1 = ?, DESC2 = ?, 
                TOTIMP1 = ?, TOTIMP4 = ?, TOT_PARTIDA = ? 
                WHERE CVE_DOC = ? AND CVE_ART = ?";
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
                $CVE_DOC,
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
        }
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => 'Error al actualizar o insertar una partida', 'errors' => sqlsrv_errors()]));
        }
    }
    // **4. Eliminar partidas que ya no están en la nueva lista**
    $productosNuevos = array_column($partidasData, 'producto');
    $productosAEliminar = array_diff(array_keys($partidasExistentes), $productosNuevos);
    foreach ($productosAEliminar as $productoEliminar) {
        $sql = "DELETE FROM $nombreTabla WHERE CVE_DOC = ? AND CVE_ART = ?";
        $params = [$CVE_DOC, $productoEliminar];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            sqlsrv_rollback($conn);
            die(json_encode(['success' => false, 'message' => 'Error al eliminar una partida', 'errors' => sqlsrv_errors()]));
        }
    }
    // Confirmar transacción
    sqlsrv_commit($conn);
    sqlsrv_close($conn);

    return ['success' => true, 'message' => 'Partidas actualizadas correctamente'];
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
    $nombreTablaInventario = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT);

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
        if (!isset($partidasActuales[$producto])) {
            // Si el producto fue eliminado, agregar la cantidad anterior al inventario
            $sql = "UPDATE $nombreTablaInventario SET EXIST = EXIST + ? WHERE CVE_ART = ?";
            $params = [$cantidadAnterior, $producto];
        } elseif ($partidasActuales[$producto] < $cantidadAnterior) {
            // Si la cantidad fue reducida, agregar la diferencia al inventario
            $diferencia = $cantidadAnterior - $partidasActuales[$producto];
            $sql = "UPDATE $nombreTablaInventario SET EXIST = EXIST + ? WHERE CVE_ART = ?";
            $params = [$diferencia, $producto];
        } elseif ($partidasActuales[$producto] > $cantidadAnterior) {
            // Si la cantidad fue aumentada, restar la diferencia del inventario
            $diferencia = $partidasActuales[$producto] - $cantidadAnterior;
            $sql = "UPDATE $nombreTablaInventario SET EXIST = EXIST - ? WHERE CVE_ART = ?";
            $params = [$diferencia, $producto];
        } else {
            // Si las cantidades son iguales, no se realiza ninguna acción
            continue;
        }

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
            $sql = "UPDATE $nombreTablaInventario SET EXIST = EXIST - ? WHERE CVE_ART = ?";
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

function obtenerDatosCliente($conexionData, $claveCliente)
{
    // Obtener solo la clave del cliente (primera parte antes del espacio)
    $claveArray = explode(' ', $claveCliente, 2); // Limitar a dos elementos
    $clave = $claveArray[0]; // Tomar solo la primera parte
    //    $clave = mb_convert_encoding(trim($clave), 'UTF-8');
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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
    $CVE_DOC = str_pad($CVE_DOC, 10, ' ', STR_PAD_LEFT);
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
    $ENLAZADO = 'O'; ////
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    // Iniciar la transacción para las inserciones de las partidas
    sqlsrv_begin_transaction($conn);
    // Iterar sobre las partidas recibidas
    if (isset($partidasData) && is_array($partidasData)) {
        foreach ($partidasData as $partida) {
            // Extraer los datos de la partida
            $CVE_DOC = str_pad($formularioData['numero'], 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
            $CVE_DOC = str_pad($CVE_DOC, 10, ' ', STR_PAD_LEFT);
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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
        "CharacterSet" => "UTF-8", // Aseguramos que todo sea manejado en UTF-8
        "TrustServerCertificate" => true
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
        "CharacterSet" => "UTF-8"/*,
        "TrustServerCertificate" => true*/
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Extraer 'enviar a' y 'vendedor' del formulario
    $enviarA = $formularioData['enviar']; // Dirección de envío
    $vendedor = $formularioData['vendedor']; // Número de vendedor
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
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
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
    //$emailPred = trim($clienteData['EMAILPRED']);
    $emailPred = 'desarrollo01@mdcloud.mx';
    $clienteNombre = trim($clienteData['NOMBRE']);
    //$numeroWhatsApp = '+527773340218';
    $numeroWhatsApp = '+527773750925';
    //$resultadoWhatsApp = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $partidasData);
    if ($correo === 'S' && !empty($emailPred)) {
        $numeroWhatsApp = '+527773750925';
        enviarCorreo($emailPred, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion); // Enviar correo
        //error_log("Llamando a enviarWhatsApp con el número $numeroWhatsApp"); // Registro para depuración
        $resultadoWhatsApp = enviarWhatsAppConPlantilla($numeroWhatsApp, $clienteNombre, $noPedido, $noEmpresa, $partidasData);
        //echo $resultadoWhatsApp;
    } else {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene un correo electrónico válido registrado.']);
        die();
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
// Función para enviar el correo (en desarrollo)
function enviarCorreo($correo, $clienteNombre, $noPedido, $partidasData, $enviarA, $vendedor, $fechaElaboracion)
{
    // Crear una instancia de la clase clsMail
    $mail = new clsMail();
    $correo = 'desarrollo01@mdcloud.mx';
    // Título y asunto
    $titulo = 'Notificación de Pedido';
    $asunto = 'Detalles del Pedido #' . $noPedido;

    $productosJson = urlencode(json_encode($partidasData));
    // URLs para confirmar o rechazar
    $urlBase = "http://localhost/MDConnecta/Servidor/PHP";
    $urlConfirmar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=confirmar&nombreCliente=" . urlencode($clienteNombre) . "&enviarA=" . urlencode($enviarA) . "&vendedor=" . urlencode($vendedor) . "&productos=$productosJson" . "&fechaElab=" . urlencode($fechaElaboracion);
    $urlRechazar = "$urlBase/confirmarPedido.php?pedidoId=$noPedido&accion=rechazar&nombreCliente=" . urlencode($clienteNombre) . "&vendedor=" . urlencode($vendedor);

    // Construir cuerpo del correo
    $bodyHTML = "<p>Estimado/a <b>$clienteNombre</b>,</p>";
    $bodyHTML .= "<p>Por este medio enviamos los detalles de su pedido <b>$noPedido</b>. Por favor, revíselos y confirme:</p>";
    $bodyHTML .= "<p><b>Fecha y Hora de Elaboración:</b> $fechaElaboracion</p>";
    $bodyHTML .= "<p><b>Dirección de Envío:</b> $enviarA</p>";
    $bodyHTML .= "<p><b>Vendedor:</b> $vendedor</p>";

    // Agregar detalles del pedido
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
    foreach ($partidasData as $partida) {
        $clave = $partida['producto'];
        $descripcion = $partida['descripcion'];
        $cantidad = $partida['cantidad'];
        $totalPartida = $cantidad * $partida['precioUnitario'];
        $total += $totalPartida;

        $bodyHTML .= "<tr>
                        <td>$clave</td>
                        <td>$descripcion</td>
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
    if ($resultado === "Correo enviado exitosamente.") {
        //echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente.']);
    } else {
        error_log("Error al enviar el correo: $resultado");
        echo json_encode(['success' => false, 'message' => 'Hubo un problema al enviar el correo.']);
    }
}
/*function enviarWhatsAppConPlantilla($numero, $clienteNombre, $noPedido, $noEmpresa, $partidasData)
{
    $url = 'https://graph.facebook.com/v21.0/530466276818765/messages';
    $token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD';

    // Calcular el total y construir el texto de los productos
    $productosStr = "";
    $total = 0;

    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = $partida['precioUnitario'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;

        $productosStr .= "$producto - $cantidad units, ";
    }

    // Limpiar el texto de productos
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr)); // Eliminar la última coma

    // Construir URLs dinámicas para los botones
    $urlConfirmar = "https://mdconecta.mdcloud.mx/Servidor/PHP/confirmarPedido?pedidoId=" . urlencode($noPedido) . "&accion=confirmar&noEmpresa=" . urlencode($noEmpresa);
    $urlRechazar = "https://mdconecta.mdcloud.mx/Servidor/PHP/confirmarPedido?pedidoId=" . urlencode($noPedido) . "&accion=rechazar";

    // Crear el cuerpo de la solicitud para la API
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "confirmar_pedido", // Nombre de la plantilla aprobada en inglés
            "language" => ["code" => "es_MX"], // Cambiado a inglés
            "components" => [
                // Parámetro del encabezado
                [
                    "type" => "header",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre] // {{1}} en el encabezado
                    ]
                ],
                // Parámetros del cuerpo
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $noPedido], // {{1}} - Número de Pedido
                        ["type" => "text", "text" => $productosStr], // {{2}} - Lista de Productos
                        ["type" => "text", "text" => "$" . number_format($total, 2)] // {{3}} - Total
                    ]
                ],
                // Parámetro del botón Confirmar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 0,
                    "parameters" => [
                        ["type" => "text", "text" => $urlConfirmar] // {{1}} en el botón Confirmar
                    ]
                ],
                // Parámetro del botón Rechazar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 1,
                    "parameters" => [
                        ["type" => "text", "text" => $urlRechazar] // {{1}} en el botón Rechazar
                    ]
                ]
            ]
        ]
    ];

    $data_string = json_encode($data);

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
    curl_close($curl);

    return $result;
}*/
function enviarWhatsAppConPlantilla($numero, $clienteNombre, $noPedido, $noEmpresa, $partidasData)
{
    $url = 'https://graph.facebook.com/v21.0/530466276818765/messages';
    $token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD';

    // Calcular el total y construir el texto de los productos
    $productosStr = "";
    $total = 0;

    foreach ($partidasData as $partida) {
        $producto = $partida['producto'];
        $cantidad = $partida['cantidad'];
        $precioUnitario = $partida['precioUnitario'];
        $totalPartida = $cantidad * $precioUnitario;
        $total += $totalPartida;

        $productosStr .= "$producto - $cantidad units, ";
    }

    // Limpiar el texto de productos
    $productosStr = trim(preg_replace('/,\s*$/', '', $productosStr)); // Eliminar la última coma

    // Construir URLs dinámicas para los botones
    $urlConfirmar = "https://mdconecta.mdcloud.mx/Servidor/PHP/confirmarPedido?pedidoId=" . urlencode($noPedido) . "&accion=confirmar&noEmpresa=" . urlencode($noEmpresa);
    $urlRechazar = "https://mdconecta.mdcloud.mx/Servidor/PHP/confirmarPedido?pedidoId=" . urlencode($noPedido) . "&accion=rechazar";

    // Crear el cuerpo de la solicitud para la API
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => $numero,
        "type" => "template",
        "template" => [
            "name" => "confirmar_pedido_", // Nombre de la plantilla aprobada en inglés
            "language" => ["code" => "en_US"], // Cambiado a inglés
            "components" => [
                // Parámetro del encabezado
                [
                    "type" => "header",
                    "parameters" => [
                        ["type" => "text", "text" => $clienteNombre] // {{1}} en el encabezado
                    ]
                ],
                // Parámetros del cuerpo
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $noPedido], // {{1}} - Número de Pedido
                        ["type" => "text", "text" => $productosStr], // {{2}} - Lista de Productos
                        ["type" => "text", "text" => "$" . number_format($total, 2)] // {{3}} - Total
                    ]
                ],
                // Parámetro del botón Confirmar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 0,
                    "parameters" => [
                        ["type" => "text", "text" => $urlConfirmar] // {{1}} en el botón Confirmar
                    ]
                ],
                // Parámetro del botón Rechazar
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => 1,
                    "parameters" => [
                        ["type" => "text", "text" => $urlRechazar] // {{1}} en el botón Rechazar
                    ]
                ]
            ]
        ]
    ];

    $data_string = json_encode($data);

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
    curl_close($curl);

    return $result;
}

function obtenerClientePedido($claveVendedor, $conexionData, $clienteInput)
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
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

function eliminarPedido($conexionData, $pedidoID)
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

//--------------Funcion Mostrar Articulos----------------------------------------------------------------

// function extraerProductos($conexionData)
// {
//     $serverName = $conexionData['host'];
//     $connectionInfo = [
//         "Database" => $conexionData['nombreBase'],
//         "UID" => $conexionData['usuario'],
//         "PWD" => $conexionData['password'],
//         "CharacterSet" => "UTF-8"
//     ];

//     $conn = sqlsrv_connect($serverName, $connectionInfo);
//     if ($conn === false) {
//         echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
//         exit;
//     }

//     $noEmpresa = $_SESSION['empresa']['noEmpresa'];
//     $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

//     $sql = "SELECT TOP (1000) [CVE_ART], [DESCR], [EXIST], [LIN_PROD], [UNI_MED], [CVE_ESQIMPU], [IMAGEN_URL]
//         FROM $nombreTabla";

//     $stmt = sqlsrv_query($conn, $sql);

//     if ($stmt === false) {
//         echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
//         exit;
//     }

//     $productos = [];
//     while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
//         $productos[] = $row;
//     }

//     if (count($productos) > 0) {
//         header('Content-Type: application/json');
//         echo json_encode(['success' => true, 'productos' => $productos]);
//     } else {
//         echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
//     }

//     sqlsrv_free_stmt($stmt);
//     sqlsrv_close($conn);
// }

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
function extraerProductos($conexionData)
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

    // Consulta directa a la tabla fija INVE02
    $sql = "
        SELECT 
            [CVE_ART], 
            [DESCR], 
            [EXIST], 
            [LIN_PROD], 
            [UNI_MED],
            [APART]
        FROM [SAE90Empre02].[dbo].[INVE02]
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
function extraerProducto($conexionData)
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

    // Asume la empresa predeterminada
    $noEmpresa = '02';
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

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
// -----------------------------------------------------------------------------------------------------


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
        /*if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }*/

        //$noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $noEmpresa = "02";
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

        $tipoOperacion = $formularioData['tipoOperacion']; // 'alta' o 'editar'
        if ($tipoOperacion === 'alta') {
            // Lógica para alta de pedido
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
                    guardarPedido($conexionData, $formularioData, $partidasData);
                    guardarPartidas($conexionData, $formularioData, $partidasData);
                    actualizarFolio($conexionData);
                    actualizarInventario($conexionData, $partidasData);
                    validarCorreoCliente($formularioData, $partidasData, $conexionData);
                    // Respuesta de éxito
                    echo json_encode([
                        'success' => true,
                        'message' => 'El pedido se completó correctamente.',
                    ]);
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
        } elseif ($tipoOperacion === 'editar') {
            // Lógica para edición de pedido
            $resultadoActualizacion = actualizarPedido($conexionData, $formularioData, $partidasData);

            if ($resultadoActualizacion['success']) {
                actualizarInventario($conexionData, $partidasData);

                echo json_encode([
                    'success' => true,
                    'message' => 'El pedido fue actualizado correctamente.',
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo actualizar el pedido.',
                ]);
            }
        } else {
            // Operación desconocida
            echo json_encode([
                'success' => false,
                'message' => 'Operación no reconocida.',
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
    case 11:
        // Empresa por defecto (puedes cambiar este valor según tus necesidades)
        $noEmpresa = '02';

        // Obtener conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }

        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];

        // Llamar a la función para extraer productos
        extraerProductos($conexionData);
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
        // Empresa por defecto (puedes cambiar este valor según tus necesidades)
        $noEmpresa = '02';

        // Obtener conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
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
        // Empresa por defecto (puedes cambiar este valor según tus necesidades)
        $noEmpresa = '02';

        // Obtener conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
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
        // Empresa por defecto (puedes cambiar este valor según tus necesidades)
        $noEmpresa = '02';

        // Obtener conexión
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }

        // Obtener los datos de conexión
        $conexionData = $conexionResult['data'];

        // Llamar a la función para extraer productos
        extraerProducto($conexionData);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
