<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Limpiar la clave y convertirla a UTF-8
    $clave = mb_convert_encoding(trim($clave), 'UTF-8');
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    // Crear la consulta SQL con un parámetro
    $sql = "SELECT TOP (1) [CLAVE], [STATUS], [NOMBRE], [RFC], [CALLE], [NUMINT], [NUMEXT], 
        [CRUZAMIENTOS], [COLONIA], [CODIGO], [LOCALIDAD], [MUNICIPIO], [ESTADO], 
        [PAIS], [NACIONALIDAD], [REFERDIR], [TELEFONO], [CLASIFIC], [FAX], [PAG_WEB], 
        [CURP], [CVE_ZONA], [IMPRIR], [MAIL], [SALDO], [TELEFONO] 
    FROM [SAE90Empre02].[dbo].[FACTP02] 
    WHERE CAST(LTRIM(RTRIM([CLAVE])) AS NVARCHAR(MAX)) = CAST(? AS NVARCHAR(MAX))";
    // Preparar el parámetro
    $params = array($clave);
    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $cliente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    // Verificar si encontramos el cliente
    if ($cliente) {
        echo json_encode([
            'success' => true,
            'cliente' => $cliente
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
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
    // Obtener el número de empresa
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    // Extraer los datos del formulario
    $CVE_DOC = $formularioData['numero']; // Número de documento
    $FECHA_DOC = $formularioData['diaAlta']; // Fecha del documento
    $CAN_TOT = $formularioData['cantidadTotal']; // Cantidad total
    $IMP_TOT1 = $formularioData['impuestos1'];
    $IMP_TOT2 = $formularioData['cantidadTotal'];
    $IMP_TOT3 = $formularioData['cantidadTotal'];
    $IMP_TOT4 = $formularioData['cantidadTotal'];
    $IMP_TOT5 = $formularioData['cantidadTotal'];
    $IMP_TOT6 = $formularioData['cantidadTotal'];
    $IMP_TOT7 = $formularioData['cantidadTotal'];
    $IMP_TOT8 = $formularioData['cantidadTotal'];
    $DES_TOT = $formularioData['impuestos1'];
    $DES_FIN = $formularioData['cantidadTotal'];
    $CONDICION = $formularioData['cantidadTotal'];
    $RFC = $formularioData['cantidadTotal'];
    $FECHA_ELAB = $formularioData['cantidadTotal'];
    $TIP_DOC = $formularioData['cantidadTotal'];
    $NUM_ALMA = $formularioData['cantidadTotal'];
    $FORMAENVIO = $formularioData['cantidadTotal'];
    // Crear la consulta SQL para insertar los datos en la base de datos
    $sql = "
        INSERT INTO $nombreTabla (
            [CVE_DOC], [FECHA_DOC], [CAN_TOT], [IMP_TOT1], [IMP_TOT2], [IMP_TOT3], [IMP_TOT4],
            [IMP_TOT5], [IMP_TOT6], [IMP_TOT7], [IMP_TOT8], [DES_TOT], [DES_FIN], [CONDICION], [RFC],
            [TIP_DOC], [FECHA_ELAB], [NUM_ALMA], [FORMAENVIO]
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    // Preparar los parámetros para la consulta
    $params = [
        $CVE_DOC,
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
        $CONDICION,
        $RFC,
        $TIP_DOC,
        $FECHA_ELAB,
        $NUM_ALMA,
        $FORMAENVIO
    ];
    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al guardar el pedido', 'errors' => sqlsrv_errors()]));
    }
    // Si todo salió bien, retornar éxito
    echo json_encode(['success' => true, 'message' => 'Pedido guardado con éxito']);
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
            $CVE_DOC = $formularioData['numero']; // Número de documento
            $CVE_ART = $partida['producto']; // Clave del producto
            $CANT = $partida['cantidad']; // Cantidad
            $PREC = $partida['precioUnitario']; // Precio
            $NUM_PAR = $formularioData['numero'];
            // Calcular los impuestos y totales
            //$IMPU1 = $partida['impuesto1']; // Impuesto 1
            $IMPU1 = 0;
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
            $TIPO_PORD = $partida['unidad'];
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
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 0, ?, 4, 4, 4, 4,
                ?, ?, 0, ?,
                ?, ?, 0, 0, ?,
                'N', ?, '', 1, ?, ?, 0, 0, 0, 'N',
                0, ?, 'S', 'N', 0, 0, 0, 0, 0, 0, 0,
                0, ?, '', '',
                0, '', '', 0, 0, 0, 0,
                0, 0, 0, 0, 0, 0, 0)";
            $params = [
                $CVE_DOC, $NUM_PAR, $CVE_ART, $CANT, $PXS, $PREC, $IMPU1, $IMPU2, $IMPU4,
                $TOTIMP1, $TOTIMP2, $TOTIMP4,
                $DESC1, $DESC2, $COMI, 
                $NUM_ALMA, $UNI_VENTA, $TIPO_PORD,
                $TOT_PARTIDA,
                $DESCR_ART
            ];
            var_dump($params);
            $sqlDepurado = $sql;
            foreach ($params as $param) {
                $sqlDepurado = preg_replace('/\?/', "'" . addslashes($param) . "'", $sqlDepurado, 1);
            }
            var_dump($sqlDepurado);

            // Ejecutar la consulta
            $stmt = sqlsrv_query($conn, $sql, $params);
            var_dump($stmt);
            if ($stmt === false) {
                var_dump(sqlsrv_errors()); // Muestra los errores específicos
                sqlsrv_rollback($conn);
                die(json_encode(['success' => false, 'message' => 'Error al insertar la partida', 'errors' => sqlsrv_errors()]));
            }
        }
    } else {
        die(json_encode(['success' => false, 'message' => 'Error: partidasData no es un array válido']));
    }
    echo json_encode(['success' => true, 'message' => 'Partidas guardadas con éxito']);
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
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el folio', 'errors' => sqlsrv_errors()]));
    }
    // Verificar cuántas filas se han afectado
    $rowsAffected = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    // Retornar el resultado
    if ($rowsAffected > 0) {
        echo json_encode(['success' => true, 'message' => 'Folio actualizado correctamente']);
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
    $CVE_ART = $partida['producto'];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $cantidad = $partidasData['cantidad'];
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    // SQL para actualizar los campos EXIST y PEND_SURT
    $sql = "UPDATE $nombreTabla
            SET 
                [EXIST] = [EXIST] - ?,    
                [PEND_SURT] = [PEND_SURT] + ?   
            WHERE [CVE_ART] = ?";

    // Preparar la consulta
    $params = array($cantidad, $cantidad, $CVE_ART);

    // Ejecutar la consulta SQL
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el inventario', 'errors' => sqlsrv_errors()]));
    }
    // Verificar cuántas filas se han afectado
    $rowsAffected = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    // Retornar el resultado
    if ($rowsAffected > 0) {
        echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el producto para actualizar']);
    }
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

function obtenerClientePedido($clave, $conexionData, $cliente)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8" // Aseguramos que todo sea manejado en UTF-8
    ];
    // Intentar conectarse a la base de datos
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Limpiar la entrada del cliente y clave, y convertirla a UTF-8
    $cliente = mb_convert_encoding(trim($cliente), 'UTF-8');
    $clave = mb_convert_encoding(trim($clave), 'UTF-8');

    // Agregar % a la entrada del cliente para búsqueda parcial
    $cliente = '%' . $cliente . '%';
    // Consulta SQL 
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT DISTINCT 
            [CLAVE], [NOMBRE], [CALLE],[RFC], [NUMINT], [NUMEXT], [COLONIA],[CODIGO], 
            [LOCALIDAD], [MUNICIPIO], [ESTADO], [PAIS],[TELEFONO], [LISTA_PREC]
        FROM $nombreTabla 
        WHERE LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER('$cliente') 
          AND [CVE_VEND] = $clave";

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados de la consulta
    $clientes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $clientes[] = $row; // Almacenar cada cliente en el array
    }
    // Verificar si se encontraron clientes y devolver la respuesta
    if (count($clientes) > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cliente' => $clientes
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron clientes.']);
    }
    // Liberar recursos y cerrar la conexión
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
        echo json_encode(['success' => true, 'precio' => (float)$precio]);
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
    $sql = "SELECT IMPUESTO1, IMPUESTO2, IMPUESTO4 FROM $nombreTabla WHERE CVE_ESQIMPU = ?";
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
            'IMPUESTO1' => (float)$row['IMPUESTO1'],
            'IMPUESTO2' => (float)$row['IMPUESTO2'],
            'IMPUESTO4' => (float)$row['IMPUESTO4']
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
    case 2: // 
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
        $clave = $_POST['pedidoID'];
        mostrarPedidoEspecifico($clave, $conexionData, $noEmpresa);
        break;
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
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $folioSiguiente = obtenerFolioSiguiente($conexionData);
        if ($folioSiguiente !== null) {
            echo json_encode(['success' => true, 'folioSiguiente' => $folioSiguiente]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo obtener el siguiente folio']);
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
        // Mostrar los clientes usando los datos de conexión obtenidos
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
        $formularioData = json_decode($_POST['formulario'], true); // Clave "formulario" enviada desde JS
        $partidasData = json_decode($_POST['partidas'], true); // Clave "partidas" enviada desde JS        
        $conexionData = $conexionResult['data'];
        // Mostrar los clientes usando los datos de conexión obtenidos
        //guardarPedido($conexionData, $formularioData, $partidasData);
        guardarPartidas($conexionData, $formularioData, $partidasData);
        //actualizarFolio($conexionData);
        //actualizarInventario($conexionData, $partidasData);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
