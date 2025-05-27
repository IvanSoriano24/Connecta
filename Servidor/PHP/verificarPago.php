<?php
require 'firebase.php';

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
    // Busca el documento donde coincida el campo `claveSae`
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
function verificarPago($conexionData, $cliente, $claveSae, $folio)
{
    // 1) Conectar
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID"      => $conexionData['usuario'],
        "PWD"      => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors'  => sqlsrv_errors()
        ]));
    }

    // 2) Normalizar cliente y folio
    $cliente = str_pad($cliente, 10, ' ', STR_PAD_LEFT);
    $folio10 = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $cveDoc  = str_pad($folio10, 20, ' ', STR_PAD_LEFT);

    // 3) Tablas dinámicas
    $tablaPed  = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, '0', STR_PAD_LEFT) . "]";
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
            'errors'  => sqlsrv_errors()
        ]));
    }
    $row1 = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC);
    if (!$row1) {
        sqlsrv_close($conn);
        return ['success' => false, 'message' => 'Pedido no encontrado'];
    }
    $importePedido = (float)$row1['IMPORTE'];

    // 5) Recuperar el único anticipo (NUM_CPTO='9' y REFER=CVE_DOC)
    $sql2 = "SELECT TOP 1 
                IMPORTE   AS importeAnticipo,
                REFER,
                NO_FACTURA
             FROM $tablaCuen
             WHERE CVE_CLIE = ?
               AND NUM_CPTO = '9'";
    $stmt2 = sqlsrv_query($conn, $sql2, [$cliente]);
    if ($stmt2 === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar el anticipo',
            'errors'  => sqlsrv_errors()
        ]));
    }
    $row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);

    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_close($conn);

    $importePagado = (float)$row2['importeAnticipo'] ?? 0;
    $saldo   = $importePedido - $importePagado;
    $pagada  = $importePagado >= $importePedido;

    // 6) Si no hay anticipo, devolvemos pagada=false
    if (!$row2) {
        return [
            'success'       => true,
            'importePedido' => $importePedido,
            'importePagado' => $importePagado,
            'saldo'         => $saldo,
            'pagada'        => false
        ];
    }

    return [
        'success'        => true,
        'importePedido'  => $importePedido,
        'importePagado'  => $importePagado,
        'saldo'          => $saldo,
        'pagada'         => $pagada,
        'REFER'          => trim($row2['REFER']),
        'NO_FACTURA'     => trim($row2['NO_FACTURA'])
    ];
}
function cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS/$pagoId?updateMask.fieldPaths=status&updateMask.fieldPaths=buscar&key=$firebaseApiKey";

    $data = [
        'fields' => [
            'status' => ['stringValue' => 'Pagada'],
            'buscar' => ['booleanValue' => false]
        ]
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo "Error al actualizar la comanda $pagoId.\n";
    } else {
        //echo "Comanda $pagoId actualizada a 'Pagada'.\n";
        estadoSql($folio, $conexionData, $claveSae);
    }
}
function estadoSql($folio, $conexionData, $claveSae)
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
        echo "DEBUG: Error al conectar en estadoSql:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "UPDATE $nombreTabla SET 
        STATUS = 'E'
        WHERE CVE_DOC = ?";

    $stmt = sqlsrv_query($conn, $sql, [$CVE_DOC]);

    if ($stmt === false) {
        echo "DEBUG: Error al actualizar el pedido:\n";
        var_dump(sqlsrv_errors());
        exit;
    }

    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    return ['success' => true, 'message' => 'Status actualizado'];
}
function crearRemision($folio, $claveSae, $noEmpresa, $vendedor)
{
    $remisionUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/remision.php";
    //$remisionUrl = 'http://localhost/MDConnecta/Servidor/PHP/remision.php';
    $data = [
        'numFuncion' => 1,
        'pedidoId' => $folio,
        'claveSae' => $claveSae,
        'noEmpresa' => $noEmpresa,
        'vendedor' => $vendedor
    ];

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
    //echo "Respuesta de decodificada.php: " . $remisionData;
    //$cveDoc = trim($remisionData['cveDoc']);
    // Verificar si la respuesta es un PDF
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
}
function eliminarCxc($conexionData, $claveSae, $cliente, $pagado)
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
    } catch (Exception $e) {
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
    $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT [CVE_ART], [CANT] FROM $nombreTabla
        WHERE [CVE_DOC] = ?";
    $params = [$CVE_DOC];
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
    $tablaCuenD = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
function crearComanda($folio, $claveSae, $noEmpresa, $vendedor, $fechaElaboracion, $conexionData, $firebaseProjectId, $firebaseApiKey)
{
    date_default_timezone_set('America/Mexico_City');

    $pedidoData = datosPedido($folio, $claveSae, $conexionData);
    $productosData = datosPartida($folio, $claveSae, $conexionData);
    $clienteData = datosCliente($pedidoData['CVE_CLPV'], $claveSae, $conexionData);
    $enviarA = datoEnvio($pedidoData['DAT_ENVIO'], $claveSae, $conexionData);
    $nombreVendedor = vendedorNom($conexionData, $vendedor, $claveSae);
    $horaActual = (int) date('H'); // Hora actual en formato 24 horas (e.g., 13 para 1:00 PM)
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
                                    "cantidad" => ["integerValue" => (int) $productosData["CANT"]],
                                ]
                            ]
                        ];
                    }, $productosData)
                ]
            ],
            "vendedor" => ["stringValue" => $nombreVendedor],
            "status" => ["stringValue" => $estadoComanda], // Establecer estado según la hora
            "claveSae" => ["stringValue" => $claveSae],
            "noEmpresa" => ["integerValue" => $noEmpresa],
            "pagada" => ["booleanValue" => true],
            "credito" => ["booleanValue" => false],
            "facturado" => ["booleanValue" => false]
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
        echo "<div class='container'>
                        <div class='title'>Error al Conectarse</div>
                        <div class='message'>No se pudo conectar a Firebase: " . $error['message'] . "</div>
                      </div>";
    } else {
        echo "<div class='container'>
                            <div class='title'>Confirmación Exitosa</div>
                            <div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>
                          </div>";
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[INVE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
    $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[VEND"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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
    $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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
function restarSaldo($conexionData, $claveSae, $pagado, $cliente){
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
    $imp = -6380.0;
    $cliente = formatearClaveCliente($cliente);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "UPDATE $nombreTabla SET
        [SALDO] = [SALDO] + (? * -1)
        WHERE CLAVE = ?";

    //$params = [$pagado['importePagado'], $cliente];
    $params = [$imp, $cliente];
    var_dump($sql);
    var_dump($params);

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

    /*return json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente para el cliente: $cliente"
    ]);*/
    echo json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente para el cliente: $cliente"
    ]);
}
function verificarPedidos($firebaseProjectId, $firebaseApiKey)
{
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
        if ($status === 'Sin Pagar') {
            //$cliente = "878";
            if ($buscar) {
                $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
                if ($conexionResult['success']) {
                    $conexionData = $conexionResult['data'];
                    $fechaPago = obtenerFecha($conexionData, $cliente, $claveSae);
                    $fechaLimiteObj = new DateTime($fechaLimite);
                    if ($fechaPago <= $fechaLimiteObj) {
                        $pagado = verificarPago($conexionData, $cliente, $claveSae, $folio);
                        $pagado['pagada'] = true;
                        if ($pagado['pagada']) {
                            /*var_dump($pagado);
                            die();*/
                            $pagoId = basename($document['name']);
                            //echo "DEBUG: Pago encontrado, actualizando estado para pagoId: $pagoId, folio: $folio\n"; // Depuración
                            //cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae);
                            //var_dump($pagado);
                            //eliminarCxc($conexionData, $claveSae, $cliente, $pagado);
                            //restarSaldo($conexionData, $claveSae, $pagado, $cliente);
                            var_dump($cliente);
                            //crearComanda($folio, $claveSae, $noEmpresa, $vendedor, $fechaElaboracion, $conexionData, $firebaseProjectId, $firebaseApiKey);
                            //crearRemision($folio, $claveSae, $noEmpresa, $vendedor);
                            //Remision y Demas
                        }
                    } else if ($fechaPago > $fechaLimiteObj) {
                        liberarExistencias($conexionData, $folio, $claveSae);
                        //Notificar
                    }
                }
            }
        }
    }
}

verificarPedidos($firebaseProjectId, $firebaseApiKey);
