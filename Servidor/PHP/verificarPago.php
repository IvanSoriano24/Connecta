<?php
//$logFile = '/var/log/confirmarPedido.log';
// al inicio de tu script PHP
$logDir  = __DIR__ . '/logs';
$logFile = $logDir . '/verificarPago.log';
require 'firebase.php';

//Funcion para obtener los datos de conexion
function obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa)
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
function validarRemision($conexionData, $folio, $claveSae, $logFile)
{
    // 1) Conectar
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
    $err = sqlsrv_errors();
    $msg = sprintf(
        "[%s] Succes: Buscando si el pedido: $folio tiene remision %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    $folio10 = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($folio10, 20, ' ', STR_PAD_LEFT);

    // 3) Tablas dinámicas
    $tablaPed = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, '0', STR_PAD_LEFT) . "]";

    $sql = "SELECT TIP_DOC_SIG, DOC_SIG FROM $tablaPed WHERE CVE_DOC = ?";
    $param = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $param);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$row) {
        sqlsrv_close($conn);
        $err = sqlsrv_errors();
        $msg = sprintf(
            "[%s] ERROR: Pedido: $folio no encontrado %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        return ['success' => false, 'message' => 'Pedido no encontrado'];
    }
    $TIP_DOC_SIG = $row['TIP_DOC_SIG'] ?? "";
    $DOC_SIG = $row['DOC_SIG'] ?? NULL;
    $err = sqlsrv_errors();
    $msg = sprintf(
        "[%s] Succes: Se encontro los siguientes datos, tipo de documento: $TIP_DOC_SIG, documento: $DOC_SIG %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);

    if ($TIP_DOC_SIG === 'R' || ($DOC_SIG != NULL || !empty($DOC_SIG))) {
        $err = sqlsrv_errors();
        $msg = sprintf(
            "[%s] INFO: Se encontro una remision para el pedido: $folio %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        return true;
    } else if ($TIP_DOC_SIG != 'R' || $DOC_SIG == NULL) {
        $err = sqlsrv_errors();
        $msg = sprintf(
            "[%s] INFO: No se encontro una remision para el pedido: $folio %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        return false;
    }
}
function verificarPago($conexionData, $cliente, $claveSae, $folio, $logFile)
{
    // 1) Conectar
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

    // 2) Normalizar cliente y folio
    $cliente = str_pad($cliente, 10, ' ', STR_PAD_LEFT);
    $folio10 = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($folio10, 20, ' ', STR_PAD_LEFT);

    // 3) Tablas dinámicas
    $tablaPed = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, '0', STR_PAD_LEFT) . "]";
    $tablaCuen = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, '0', STR_PAD_LEFT) . "]";

    // 4) Obtener importe del pedido
    $sql1 = "SELECT IMPORTE
             FROM $tablaPed
             WHERE CVE_DOC = ?";
    $stmt1 = sqlsrv_query($conn, $sql1, [$cveDoc]);
    if ($stmt1 === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar el pedido',
            'errors' => sqlsrv_errors()
        ]));
    }
    $row1 = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC);
    if (!$row1) {
        sqlsrv_close($conn);
        return ['success' => false, 'message' => 'Pedido no encontrado'];
    }
    $importePedido = (float)$row1['IMPORTE'];
    var_dump("importePedido: ", $importePedido);

    // 5) Recuperar el único anticipo (NUM_CPTO='9' y REFER=CVE_DOC)
    $sql2 = "SELECT TOP 1 
                IMPORTE   AS importeAnticipo,
                REFER,
                NO_FACTURA
             FROM $tablaCuen
             WHERE CVE_CLIE = ?
               AND NUM_CPTO = '9'
               ORDER BY FECHA_APLI DESC";
    $stmt2 = sqlsrv_query($conn, $sql2, [$cliente]);
    if ($stmt2 === false) {
        $err = sqlsrv_errors();
        $msg = sprintf(
            "[%s] ERROR: Error al consultar el anticipo del cliente $cliente %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar el anticipo',
            'errors' => sqlsrv_errors()
        ]));
    }
    $row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);

    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_close($conn);

    $importePagado = (float)$row2['importeAnticipo'] ?? 0;
    // Toma el valor o 0 si no existe, lo casteas a float, y luego redondeas a 2 decimales
    //$importePagado = round((float)($row2['importeAnticipo'] ?? 0), 2);

    var_dump("importePagado: ", $importePagado);
    $saldo = $importePedido - $importePagado;
    var_dump("importePedido: ", $importePedido);
    var_dump("saldo: ", $saldo);
    //$importePagado = 2668.3108799999995;
    $pagada = $importePagado >= $importePedido;
    var_dump("pagada: ", $pagada);

    $err = error_get_last();
    $msg = sprintf(
        "[%s] Succes: Pedido: $folio con importe $importePedido, con una cuenta de $importePagado  %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    // 6) Si no hay anticipo, devolvemos pagada=false
    if (!$row2) {
        return [
            'success' => true,
            'importePedido' => $importePedido,
            'importePagado' => $importePagado,
            'saldo' => $saldo,
            'pagada' => false
        ];
    }

    return [
        'success' => true,
        'importePedido' => $importePedido,
        'importePagado' => $importePagado,
        'saldo' => $saldo,
        'pagada' => $pagada,
        'REFER' => trim($row2['REFER']),
        'NO_FACTURA' => trim($row2['NO_FACTURA'])
    ];
}
function cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae, $logFile)
{
    //Construir la URL con el id del documento y los updateMask para solo actualizar los campos requeridos
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS/$pagoId?updateMask.fieldPaths=status&updateMask.fieldPaths=buscar&key=$firebaseApiKey";

    //Estructurar los datos
    $data = [
        'fields' => [
            'status' => ['stringValue' => 'Pagada'],
            'buscar' => ['booleanValue' => false]
        ]
    ];
    //Crear la conexion
    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);
    //Realizar la conexion
    $response = @file_get_contents($url, false, $context);

    //Verificar la respuesta
    if ($response === false) {
        $err = error_get_last();
        $msg = sprintf(
            "[%s] ERROR: Pago: $folio no cambio de estatus %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo "Error al actualizar la comanda $pagoId.\n";
    } else {
        $err = error_get_last();
        $msg = sprintf(
            "[%s] Succes: Pago: $folio cambio de estatus %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
    }
}
function cambiarEstadoPagoVencido($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae, $logFile, $resultado)
{
    //Construir la URL con el id del documento y los updateMask para solo actualizar los campos requeridos
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS/$pagoId?updateMask.fieldPaths=status&updateMask.fieldPaths=buscar&key=$firebaseApiKey";

    //Estructurar los datos
    $data = [
        'fields' => [
            'status' => ['stringValue' => $resultado],
            'buscar' => ['booleanValue' => false]
        ]
    ];
    //Crear la conexion
    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);
    //Realizar la conexion
    $response = @file_get_contents($url, false, $context);

    //Verificar la respuesta
    if ($response === false) {
        echo "Error al actualizar la comanda $pagoId.\n";
    } else {
        //echo "Comanda $pagoId actualizada a 'Pagada'.\n";
        //Cambiar el estado en base de datos SAE
    }
}
function crearRemision($folio, $claveSae, $noEmpresa, $vendedor, $logFile)
{
    $err = error_get_last();
    $msg = sprintf(
        "[%s] Succes: Empezando la remision %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    //Construir la conexion
    $remisionUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/remision.php";
    //$remisionUrl = 'http://localhost/MDConnecta/Servidor/PHP/remision.php';

    //Estructurar los datos nesesarios
    $data = [
        'numFuncion' => 1,
        'pedidoId' => $folio,
        'claveSae' => $claveSae,
        'noEmpresa' => $noEmpresa,
        'vendedor' => $vendedor
    ];

    //Realizar la peticion
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remisionUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $remisionResponse = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error cURL: ' . curl_error($ch);
    }
    curl_close($ch);
    echo "Respuesta de remision.php: " . $remisionResponse;
    $remisionData = json_decode($remisionResponse, true);
    echo "Respuesta de decodificada.php: " . $remisionData;
    //$cveDoc = trim($remisionData['cveDoc']);
    // Verificar si la respuesta es un PDF
    $err = error_get_last();
    $msg = sprintf(
        "[%s] Succes: Respuesta de la remision $remisionData, mas informacion revisar logs de la remision %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
}
function eliminarCxc($conexionData, $claveSae, $cliente, $pagado, $logFile)
{
    // Configuración de conexión
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    echo "DEBUG: Conectando a la base de datos...\n";
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }
    echo "DEBUG: Conexión exitosa.\n";

    // Asegurar que el cliente tenga 10 caracteres (rellenados con espacios)
    $cliente = str_pad($cliente, 10, ' ', STR_PAD_LEFT);
    $NO_FACTURA = $pagado['NO_FACTURA'];
    $REFER = $pagado['REFER'];

    // Construir dinámicamente los nombres de las tablas
    $tablaCunetM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaCunetDet = "[{$conexionData['nombreBase']}].[dbo].[CUEN_Det" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Iniciar una transacción
    sqlsrv_begin_transaction($conn);
    $err = error_get_last();
    $msg = sprintf(
        "[%s] Succes: Iniciando aliminacion de pago %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);

    try {
        // Eliminar de la tabla CUEN_M
        $sqlCunetM = "DELETE FROM $tablaCunetM WHERE [CVE_CLIE] = ? AND [REFER] = ? AND [NO_FACTURA] = ? ";
        $params = [$cliente, $REFER, $NO_FACTURA];
        $stmtCunetM = sqlsrv_prepare($conn, $sqlCunetM, $params);
        if ($stmtCunetM === false) {
            throw new Exception('Error al preparar la consulta para ' . $tablaCunetM . ': ' . print_r(sqlsrv_errors(), true));
        }

        if (!sqlsrv_execute($stmtCunetM)) {
            throw new Exception('Error al ejecutar la consulta para ' . $tablaCunetM . ': ' . print_r(sqlsrv_errors(), true));
        }
        // Confirmar la transacción
        sqlsrv_commit($conn);
        $err = error_get_last();
        $msg = sprintf(
            "[%s] Succes: CXC eliminada %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
    } catch (Exception $e) {
        $err = error_get_last();
        $mensaje = $e->getMessage();
        $msg = sprintf(
            "[%s] Error: Hubo un error al eliminar la CXC: $mensaje pagado %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        // Revertir la transacción en caso de error
        sqlsrv_rollback($conn);
        die(json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]));
    }

    // Liberar recursos y cerrar conexión
    if (isset($stmtCunetM)) {
        sqlsrv_free_stmt($stmtCunetM);
    }
    sqlsrv_close($conn);
}
function liberarExistencias($conexionData, $folio, $claveSae, $logFile)
{

    //Crear conexion a based de datos
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
    $err = sqlsrv_errors();
    $msg = sprintf(
        "[%s] Succes: Liberando existencias del pedido: $folio %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    //Crear tablas dinamicas
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    //Crear consulta para obtener los productos y la cantidad de estos
    $sql = "SELECT [CVE_ART], [CANT] FROM $nombreTabla
        WHERE [CVE_DOC] = ?";
    $params = [$CVE_DOC];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $err = sqlsrv_errors();
        $msg = sprintf(
            "[%s] ERROR: rror al obtener los datos para liberar existencias %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo "DEBUG: Error al obtener los datos para liberar existencias:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = $row;
    }

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
            $err = sqlsrv_errors();
            $msg = sprintf(
                "[%s] ERROR: Error al liberar las existencias %s\n",
                date('Y-m-d H:i:s'),
                json_encode($err, JSON_UNESCAPED_UNICODE)
            );
            error_log($msg, 3, $logFile);
            die(json_encode(['success' => false, 'message' => 'Error al actualizar el inventario', 'errors' => sqlsrv_errors()]));
        }
        // Verificar cuántas filas se han afectado
        $rowsAffected = sqlsrv_rows_affected($stmt);
        // Retornar el resultado
        if ($rowsAffected > 0) {
            $err = sqlsrv_errors();
            $msg = sprintf(
                "[%s] Succes: Existencias liberadas exitosamente %s\n",
                date('Y-m-d H:i:s'),
                json_encode($err, JSON_UNESCAPED_UNICODE)
            );
            error_log($msg, 3, $logFile);
            echo json_encode(['success' => true, 'message' => 'Inventario actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el producto para actualizar']);
            $err = sqlsrv_errors();
            $msg = sprintf(
                "[%s] ERROR: No se encontro producto para liberar %s\n",
                date('Y-m-d H:i:s'),
                json_encode($err, JSON_UNESCAPED_UNICODE)
            );
            error_log($msg, 3, $logFile);
        }
    }
}
function cancelarPedido($conexionData, $pedidoID, $claveSae, $logFile)
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
    $err = sqlsrv_errors();
    $msg = sprintf(
        "[%s] Succes: Cancelando pedido $pedidoID %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    $pedidoID = str_pad($pedidoID, 10, '0', STR_PAD_LEFT);
    $pedidoID = str_pad($pedidoID, 20, ' ', STR_PAD_LEFT);

    $tablaPedidos   = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPedidosCLIB = "[{$conexionData['nombreBase']}].[dbo].[FACTP_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Iniciar transacción
    sqlsrv_begin_transaction($conn);

    try {
        // 1. Actualizar STATUS en FACTP##
        $query1 = "UPDATE $tablaPedidos SET STATUS = 'C' WHERE CVE_DOC = ?";
        $stmt1 = sqlsrv_prepare($conn, $query1, [$pedidoID]);
        if (!$stmt1 || !sqlsrv_execute($stmt1)) {
            throw new Exception('Error al cancelar el pedido en FACTP', 1);
        }

        // 2. Actualizar CAMPLIB3 en FACTP_CLIB##
        $query2 = "UPDATE $tablaPedidosCLIB SET CAMPLIB3 = 'C' WHERE CLAVE_DOC = ?";
        $stmt2 = sqlsrv_prepare($conn, $query2, [$pedidoID]);
        if (!$stmt2 || !sqlsrv_execute($stmt2)) {
            throw new Exception('Error al actualizar CAMPLIB3 en FACTP_CLIB', 2);
        }

        sqlsrv_commit($conn);
        echo json_encode(['success' => true, 'pedido' => $pedidoID]);
        $err = sqlsrv_errors();
        $msg = sprintf(
            "[%s] Succes: Pedido $pedidoID cancelado %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        $err = $e->getMessage();
        $msg = sprintf(
            "[%s] ERROR: Error al cancelar el  pedido $pedidoID %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => sqlsrv_errors()
        ]);
    } finally {
        sqlsrv_close($conn);
    }
}
function obtenerFecha($conexionData, $cliente, $claveSae)
{
    date_default_timezone_set('America/Mexico_City');
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $cliente = str_pad($cliente, 10, ' ', STR_PAD_LEFT);
    // Construir dinámicamente los nombres de las tablas
    $tablaCuenD = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT FECHAELAB FROM $tablaCuenD WHERE CVE_CLIE = ? AND NUM_CPTO = '9'";
    $params = [$cliente];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $fechaPago = $row ? $row['FECHAELAB'] : '';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $fechaPago;
}

function crearComanda($idEnvios, $folio, $claveSae, $noEmpresa, $vendedor, $fechaElaboracion, $conexionData, $firebaseProjectId, $firebaseApiKey, $logFile)
{
    $err = error_get_last();
    $msg = sprintf(
        "[%s] Succes: Iniciando la creacion de la comanda %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    date_default_timezone_set('America/Mexico_City');

    $pedidoData = datosPedido($folio, $claveSae, $conexionData);
    $productosData = datosPartida($folio, $claveSae, $conexionData);
    $envioData = datosEnvioNuevo($idEnvios, $firebaseProjectId, $firebaseApiKey);
    $clienteData = datosCliente($pedidoData['CVE_CLPV'], $claveSae, $conexionData);
    $enviarA = datoEnvio($pedidoData['DAT_ENVIO'], $claveSae, $conexionData);
    //actualizarControl2($conexionData, $claveSae);

    $nombreVendedor = vendedorNom($conexionData, $vendedor, $claveSae);
    $horaActual = (int)date('H'); // Hora actual en formato 24 horas (e.g., 13 para 1:00 PM)
    // Determinar el estado según la hora
    $estadoComanda = $horaActual >= 13 ? "Pendiente" : "Abierta"; // "Pendiente" después de 1:00 PM
    // Preparar datos para Firebase
    $comanda = [
        "fields" => [
            "idComanda" => ["stringValue" => uniqid()],
            "folio" => ["stringValue" => $folio],
            "nombreCliente" => ["stringValue" => $clienteData['NOMBRE']],
            "claveCliente" => ["stringValue" => $clienteData['CLAVE']],
            "enviarA" => ["stringValue" => $enviarA],
            "fechaHoraElaboracion" => ["stringValue" => $fechaElaboracion],
            "productos" => [
                "arrayValue" => [
                    "values" => array_map(function ($productosData) use ($claveSae, $conexionData) { // 🔹 Aquí añadimos use()
                        $dataProduc = datosProcuto($productosData['CVE_ART'], $claveSae, $conexionData);
                        return [
                            "mapValue" => [
                                "fields" => [
                                    "clave" => ["stringValue" => $productosData["CVE_ART"]],
                                    "descripcion" => ["stringValue" => $dataProduc["DESCR"]],
                                    "cantidad" => ["integerValue" => (int)$productosData["CANT"]],
                                ]
                            ]
                        ];
                    }, $productosData)
                ]
            ],
            "envio" => [
                'mapValue' => ['fields' => [
                    'codigoContacto' => ['stringValue' => $envioData['codigoContacto']],
                    'companiaContacto' => ['stringValue' => $envioData['companiaContacto']],
                    'correoContacto' => ['stringValue' => $envioData['correoContacto']],
                    'direccion1Contacto' => ['stringValue' => $envioData['direccion1Contacto']],
                    'direccion2Contacto' => ['stringValue' => $envioData['direccion2Contacto']],
                    'estadoContacto' => ['stringValue' => $envioData['estadoContacto']],
                    'idPedido' => ['integerValue' => $envioData['idPedido']],
                    'municipioContacto' => ['stringValue' => $envioData['municipioContacto']],
                    'noEmpresa' => ['integerValue' => $envioData['noEmpresa']],
                    'nombreContacto' => ['stringValue' => $envioData['nombreContacto']],
                    'telefonoContacto' => ['stringValue' => $envioData['telefonoContacto']],
                ]]
            ],
            "vendedor" => ["stringValue" => $nombreVendedor],
            "status" => ["stringValue" => $estadoComanda], // Establecer estado según la hora
            "claveSae" => ["stringValue" => $claveSae],
            "noEmpresa" => ["integerValue" => $noEmpresa],
            "pagada" => ["booleanValue" => true],
            "credito" => ["booleanValue" => false],
            "facturado" => ["booleanValue" => false],
            "observaciones" => ["stringValue" => $envioData['observaciones'] ?? ""]
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

    if ($response === false) {

        $error = error_get_last();
        $msg = sprintf(
            "[%s] ERROR: Error al crear la comanda %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo "<div class='container'>
                        <div class='title'>Error al Conectarse</div>
                        <div class='message'>No se pudo conectar a Firebase: " . $error['message'] . "</div>
                      </div>";
    } else {
        $err = error_get_last();
        $msg = sprintf(
            "[%s] Succes: Se creo la comanda del pedido $folio %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo "<div class='container'>
                            <div class='title'>Confirmación Exitosa</div>
                            <div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>
                          </div>";
    }
}
function eliminarDocumentoDatosPedido($firebaseProjectId, $firebaseApiKey, $idEnvios, $logFile)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DATOS_PEDIDO/$idEnvios?key=$firebaseApiKey";

    $options = [
        'http' => [
            'method' => 'DELETE',
            'header' => "Content-Type: application/json\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    // Verificar si fue exitoso o no
    if ($result === false) {
        $error = error_get_last();
        $msg = sprintf(
            "[%s] ERROR: No se elimino el documento de los datos del pedido %s\n",
            date('Y-m-d H:i:s'),
            json_encode($error, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo "<div class='container'>
                <div class='title'>Error al eliminar</div>
                <div class='message'>No se pudo eliminar el documento DATOS_PEDIDO/$idEnvios: {$error['message']}</div>
              </div>";
        return false;
    } else {
        $err = error_get_last();
        $msg = sprintf(
            "[%s] Succes: Se elimino el documento de los datos del pedido %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo "<div class='container'>
                <div class='title'>Documento Eliminado</div>
                <div class='message'>Se eliminó correctamente DATOS_PEDIDO/$idEnvios</div>
              </div>";
        return true;
    }
}
function datosCliente($clie, $claveSae, $conexionData)
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CLAVE = ?";
    $params = [$clie];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($clienteData) {
        return $clienteData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function datosPedido($cve_doc, $claveSae, $conexionData)
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $cve_doc = str_pad($cve_doc, 10, '0', STR_PAD_LEFT);
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$cve_doc];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    // Obtener los resultados
    $pedidoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($pedidoData) {
        return $pedidoData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function datosPartida($cve_doc, $claveSae, $conexionData)
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $cve_doc = str_pad($cve_doc, 10, '0', STR_PAD_LEFT);
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);
    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$cve_doc];

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
function datosProcuto($CVE_ART, $claveSae, $conexionData)
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_ART = ?";
    $params = [$CVE_ART];

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

use Google\Cloud\Firestore\FirestoreClient;

function datosEnvioNuevo($idEnvios, $firebaseProjectId, $firebaseApiKey)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DATOS_PEDIDO/$idEnvios?key=$firebaseApiKey";

    $json = @file_get_contents($url);
    $response = json_decode($json, true);

    if (!isset($response['fields'])) {
        echo "<div class='container'>
                <div class='title'>Error</div>
                <div class='message'>No se encontró el documento con ID: $idEnvios en la colección DATOS_PEDIDO.</div>
              </div>";
        return [];
    }

    $fields = $response['fields'];

    return [
        'codigoContacto' => $fields['codigoContacto']['stringValue'] ?? '',
        'companiaContacto' => $fields['companiaContacto']['stringValue'] ?? '',
        'correoContacto' => $fields['correoContacto']['stringValue'] ?? '',
        'direccion1Contacto' => $fields['direccion1Contacto']['stringValue'] ?? '',
        'direccion2Contacto' => $fields['direccion2Contacto']['stringValue'] ?? '',
        'estadoContacto' => $fields['estadoContacto']['stringValue'] ?? '',
        'idPedido' => $fields['idPedido']['integerValue'] ?? 0,
        'municipioContacto' => $fields['municipioContacto']['stringValue'] ?? '',
        'noEmpresa' => $fields['noEmpresa']['integerValue'] ?? 0,
        'nombreContacto' => $fields['nombreContacto']['stringValue'] ?? '',
        'telefonoContacto' => $fields['telefonoContacto']['stringValue'] ?? '',
        'observaciones' => $fields['observaciones']['stringValue'] ?? ''
    ];
}
function vendedorNom($conexionData, $vendedor, $claveSae)
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $CLAVE = str_pad($vendedor, 5, ' ', STR_PAD_LEFT);
    $sql = "SELECT NOMBRE FROM $nombreTabla WHERE CVE_VEND = ?";
    $params = [$CLAVE];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $nombreVendedor = $row ? $row['NOMBRE'] : '';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $nombreVendedor;
}
function formatearClaveCliente($clave)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $clave = trim((string)$clave);
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($clave) === 10) {
        return $clave;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    return $clave;
}

function datoEnvio($idEnvio, $claveSae, $conexionData)
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT CALLE FROM $nombreTabla WHERE CVE_INFO = ?";
    $params = [$idEnvio];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $calleEnvio = $row ? $row['CALLE'] : '';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $calleEnvio;
}

function restarSaldo($conexionData, $claveSae, $pagado, $cliente, $logFile)
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
    //$imp = -6380.0;
    $err = error_get_last();
    $msg = sprintf(
        "[%s] Succes: Iniciando resta de saldo %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    $cliente = formatearClaveCliente($cliente);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "UPDATE $nombreTabla SET
        [SALDO] = [SALDO] - (? * -1)
        WHERE CLAVE = ?";

    $params = [$pagado['importePagado'], $cliente];
    //$params = [$imp, $cliente];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $err = error_get_last();
        $msg = sprintf(
            "[%s] Succes: No se actualizo el saldo del cliente $cliente %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
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

    /*return json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente para el cliente: $cliente"
    ]);*/
    echo json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente para el cliente: $cliente"
    ]);
    $err = error_get_last();
    $msg = sprintf(
        "[%s] Succes: Saldo actualizado correctamente del cliente $cliente %s\n",
        date('Y-m-d H:i:s'),
        json_encode($err, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
}
function verificarExistencias($pedidoId, $conexionData, $claveSae, $logFile)
{
    //Creamos la conexion
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    //Verificamos que la conexion sea exitosa
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Inicializar listas de productos
    $productosSinExistencia = [];
    $productosConExistencia = [];

    $partidasData = obtenerProductos($pedidoId, $conexionData, $claveSae);

    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];

        // Consultar existencias reales considerando apartados
        $sqlCheck = "SELECT 
                        COALESCE(M.[EXIST], 0) AS EXIST, 
                        COALESCE(I.[APART], 0) AS APART, 
                        (COALESCE(M.[EXIST], 0) - COALESCE(I.[APART], 0)) AS DISPONIBLE 
                     FROM $nombreTabla I
                     INNER JOIN $nombreTabla2 M ON M.[CVE_ART] = I.CVE_ART
                     WHERE I.[CVE_ART] = ? AND M.[CVE_ALM] = 1";
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
                // opcional: también loguear los que sí tienen existencia
                $msg = sprintf(
                    "[%s] Info: Pedido %s verificadas existencias correctamente → %s\n",
                    date('Y-m-d H:i:s'),
                    $pedidoId,
                    json_encode($productosConExistencia, JSON_UNESCAPED_UNICODE)
                );
                error_log($msg, 3, $logFile);
                $productosConExistencia[] = [
                    'producto' => $CVE_ART,
                    'existencias' => $existencias,
                    'apartados' => $apartados,
                    'disponible' => $disponible
                ];
            } else {
                $msg = sprintf(
                    "[%s] Advertencia: Pedido %s sin existencias → %s\n",
                    date('Y-m-d H:i:s'),
                    $pedidoId,
                    json_encode($productosSinExistencia, JSON_UNESCAPED_UNICODE)
                );
                // escribe en el log (flag 3 = append a fichero)
                error_log($msg, 3, $logFile);
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
    } else {
        return [
            'success' => true,
            'message' => 'Existencias verificadas correctamente',
            'productosConExistencia' => $productosConExistencia
        ];
    }
}
function verificarPedidos($firebaseProjectId, $firebaseApiKey, $logFile)
{
    //Obtener los pagos
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo "Error al obtener los pagos.\n";
        return;
    }
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo "No se encontraron pagos.\n";
        return;
    }
    $fechaHoy = date('Y-m-d');
    foreach ($data['documents'] as $document) {
        //Declarar los datos as usar
        $fields = $document['fields'];
        $status = $fields['status']['stringValue'];
        $fechaElaboracion = $fields['creacion']['stringValue'];
        $fechaLimite = $fields['limite']['stringValue'];
        $cliente = $fields['cliente']['stringValue'];
        $claveSae = $fields['claveSae']['stringValue'];
        $noEmpresa = $fields['noEmpresa']['integerValue'];
        $folio = $fields['folio']['stringValue'];
        $buscar = $fields['buscar']['booleanValue'];
        $vendedor = $fields['vendedor']['stringValue'];
        $idEnvios = $fields['idEnvios']['stringValue'];
        $pagoId = basename($document['name']);
        //Filtrar pagos que aun no esten pagados
        if ($status === 'Sin Pagar') {
            //$cliente = "878";
            //Filtrar pagos que ya han sido confirmados
            if ($buscar) {
                //var_dump("Si");
                //Obtener los datos de conexion
                $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
                if ($conexionResult['success']) {
                    $conexionData = $conexionResult['data'];
                    $fechaPago = obtenerFecha($conexionData, $cliente, $claveSae);
                    $fechaLimiteObj = new DateTime($fechaLimite);
                    $err = error_get_last();
                    $msg = sprintf(
                        "[%s] Succes: Buscando pago del pedido: $folio %s\n",
                        date('Y-m-d H:i:s'),
                        json_encode($err, JSON_UNESCAPED_UNICODE)
                    );
                    error_log($msg, 3, $logFile);
                    var_dump("folio: ", $folio);
                    //Validar que no hayan pasado las 24horas
                    /*$resultadoValidacion = validarRemision($conexionData, $folio, $claveSae, $logFile);
                    if (!$resultadoValidacion) {*/
                        if ($fechaPago <= $fechaLimiteObj) {
                            $err = error_get_last();
                            $msg = sprintf(
                                "[%s] Succes: Pedido: $folio en tiempo %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($err, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            //Verificar si se realizo el pago
                            $pagado = verificarPago($conexionData, $cliente, $claveSae, $folio, $logFile);

                            //$pagado['pagada'] = true;
                            if ($pagado['pagada']) {
                                var_dump($pagado);

                                $err = error_get_last();
                                $msg = sprintf(
                                    "[%s] Succes: Pedido: $folio pagado %s\n",
                                    date('Y-m-d H:i:s'),
                                    json_encode($err, JSON_UNESCAPED_UNICODE)
                                );
                                error_log($msg, 3, $logFile);
                                //die();

                                //echo "DEBUG: Pago encontrado, actualizando estado para pagoId: $pagoId, folio: $folio\n"; // Depuración
                                cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae, $logFile);
                                //var_dump($pagado);
                                eliminarCxc($conexionData, $claveSae, $cliente, $pagado, $logFile);
                                restarSaldo($conexionData, $claveSae, $pagado, $cliente, $logFile);
                                //var_dump($cliente);
                                //Inicia validacion
                                //$exsitencias = verificarExistencias($pedidoId, $conexionData, $claveSae, $logFile);
                                //if ($exsitencias['success']) {
                                crearComanda($idEnvios, $folio, $claveSae, $noEmpresa, $vendedor, $fechaElaboracion, $conexionData, $firebaseProjectId, $firebaseApiKey, $logFile);

                                crearRemision($folio, $claveSae, $noEmpresa, $vendedor, $logFile);
                                // Eliminar el documento de DATOS_PEDIDO
                                eliminarDocumentoDatosPedido($firebaseProjectId, $firebaseApiKey, $idEnvios, $logFile);
                                //Termina validacion
                            } else {
                                $err = error_get_last();
                                $msg = sprintf(
                                    "[%s] Succes: Pedido: $folio no pagado %s\n",
                                    date('Y-m-d H:i:s'),
                                    json_encode($err, JSON_UNESCAPED_UNICODE)
                                );
                                error_log($msg, 3, $logFile);
                            }
                        } else if ($fechaPago > $fechaLimiteObj) {
                            $resultado = 'Vencida';
                            cambiarEstadoPagoVencido($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae, $logFile, $resultado);
                            $err = error_get_last();
                            $msg = sprintf(
                                "[%s] ERROR:El pago del pedido: $folio se vencio %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($err, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            //Si ya pasaron, liberar existencias
                            liberarExistencias($conexionData, $folio, $claveSae, $logFile);
                            cancelarPedido($conexionData, $folio, $claveSae, $logFile);
                            //Notificar
                        }
                    /*} else {
                        var_dump("Pedido remisionado: ", $folio);
                        $msg = sprintf(
                            "[%s] INFO:El pedido: $folio tiene una remision %s\n",
                            date('Y-m-d H:i:s'),
                            json_encode($err, JSON_UNESCAPED_UNICODE)
                        );
                        error_log($msg, 3, $logFile);
                        $resultado = 'Pagada';
                        cambiarEstadoPagoVencido($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae, $logFile, $resultado);
                    }*/
                }
            }
        }
    }
}

//Funcion primaria para validar si se realizo el pago
verificarPedidos($firebaseProjectId, $firebaseApiKey, $logFile);
