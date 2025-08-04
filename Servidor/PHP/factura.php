<?php
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php'; // Archivo de configuración de Firebase
include 'reportes.php';

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
                    'claveSae' => $fields['claveSae']['stringValue']
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function obtenerDatosCliente($claveCliente, $conexionData, $claveSae)
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

    $clie = formatearClaveCliente($claveCliente);
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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
function datosRemision($conexionData, $claveSae, $remision)
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

    $cve_doc = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);


    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[FACTR"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$cve_doc];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    // Obtener los resultados
    $remisionData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($remisionData) {
        return $remisionData;
    } else {
        echo json_encode(['success' => false, 'message' => "Pedido no encontrado $cve_doc"]);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
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
function insertarBita($conexionData, $remision, $claveSae, $folioFactura, $conn)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tablas dinámicas
    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaBita = "[{$conexionData['nombreBase']}].[dbo].[BITA" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Obtener el `CVE_BITA` incrementado en 1
    $sqlUltimaBita = "SELECT ISNULL(MAX(CVE_BITA), 0) + 1 AS CVE_BITA FROM $tablaBita";
    $stmtUltimaBita = sqlsrv_query($conn, $sqlUltimaBita);

    if ($stmtUltimaBita === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener el último CVE_BITA',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $bitaData = sqlsrv_fetch_array($stmtUltimaBita, SQLSRV_FETCH_ASSOC);
    $cveBita = $bitaData['CVE_BITA'];

    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);
    // ✅ 3. Obtener datos del pedido (`FACTPXX`) para calcular el total
    $sqlRemision = "SELECT CVE_CLPV, CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4 
                  FROM $tablaPedidos WHERE CVE_DOC = ?";
    $paramsPedido = [$remisionId];

    $stmtRemision = sqlsrv_query($conn, $sqlRemision, $paramsPedido);
    if ($stmtRemision === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $remisionn = sqlsrv_fetch_array($stmtRemision, SQLSRV_FETCH_ASSOC);
    if (!$remisionn) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron datos del remision'
        ]);
        die();
    }

    $cveClie = $remisionn['CVE_CLPV'];
    $totalPedido = $remisionn['CAN_TOT'] + $remisionn['IMP_TOT1'] + $remisionn['IMP_TOT2'] + $remisionn['IMP_TOT3'] + $remisionn['IMP_TOT4'];
    
    /*$folioFactura = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $folioFactura = urldecode($folioFactura) . urldecode($SERIE);
    $folioFactura = str_pad($folioFactura, 20, ' ', STR_PAD_LEFT);*/

    // ✅ 4. Formatear las observaciones
    $observaciones = "No.[$folioFactura] $" . number_format($totalPedido, 2);
    $actividad = str_pad(2, 5, ' ', STR_PAD_LEFT);
    // ✅ 5. Insertar en `BITA01`
    $sqlInsert = "INSERT INTO $tablaBita 
        (CVE_BITA, CVE_CAMPANIA, STATUS, CVE_CLIE, CVE_USUARIO, NOM_USUARIO, OBSERVACIONES, FECHAHORA, CVE_ACTIVIDAD) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $paramsInsert = [
        $cveBita,
        '_SAE_',
        'F',
        $cveClie,
        1,
        'ADMINISTRADOR',
        $observaciones,
        date('Y-m-d H:i:s'),
        $actividad
    ];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en BITA01 con CVE_BITA $cveBita",
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUltimaBita);
    sqlsrv_free_stmt($stmtRemision);
    sqlsrv_free_stmt($stmtInsert);
    return $cveBita;
    /*echo json_encode([
        'success' => true,
        'message' => "BITAXX insertado correctamente con CVE_BITA $cveBita y remisión $folioSiguiente"
    ]);*/
}
function insertarFactf($conexionData, $remision, $folioUnido, $CVE_BITA, $claveSae, $DAT_MOSTR, $folioFactura, $SERIE, $DAT_ENVIO, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    $remision = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remision = str_pad($remision, 20, ' ', STR_PAD_LEFT);


    /*$cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);*/

    $tablaFacturas = "[{$conexionData['nombreBase']}].[dbo].[FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaRemisiones = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 2. Obtener datos del pedido
    $sqlPedido = "SELECT * FROM $tablaRemisiones WHERE CVE_DOC = ?";
    $paramsPedido = [$remision];
    $stmtPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    if ($stmtPedido === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $pedido = sqlsrv_fetch_array($stmtPedido, SQLSRV_FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron datos del pedido'
        ]);
        die();
    }

    $fechaDoc = (new DateTime())->format('Y-m-d') . ' 00:00:00.000';
    $tipDoc = 'F';
    $status = 'E';
    $cvePedi = '';  // Vacío según la traza
    $tipDocE = 'R'; //Documento Enlazado
    $docAnt = $remision;
    $tipDocAnt = 'R';

    // ✅ 4. Insertar en FACTFXX
    $sqlInsert = "INSERT INTO $tablaFacturas 
        (TIP_DOC, CVE_DOC, CVE_CLPV, STATUS, DAT_MOSTR, CVE_VEND, CVE_PEDI, FECHA_DOC, FECHA_ENT, FECHA_VEN,
        CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4, DES_TOT, DES_FIN, COM_TOT, CVE_OBS, NUM_ALMA, ACT_CXC,
        ACT_COI, ENLAZADO, NUM_MONED, TIPCAMB, NUM_PAGOS, FECHAELAB, PRIMERPAGO, RFC, CTLPOL, ESCFD, AUTORIZA,
        SERIE, FOLIO, AUTOANIO, DAT_ENVIO, CONTADO, CVE_BITA, BLOQ, TIP_DOC_E, DES_FIN_PORC, DES_TOT_PORC,
        COM_TOT_PORC, IMPORTE, METODODEPAGO, NUMCTAPAGO, DOC_ANT, TIP_DOC_ANT, VERSION_SINC, FORMADEPAGOSAT,
        USO_CFDI, TIP_FAC, REG_FISC, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $paramsInsert = [
        $tipDoc,
        $folioUnido,
        $pedido['CVE_CLPV'],
        $status,
        $DAT_MOSTR,
        $pedido['CVE_VEND'],
        $cvePedi,
        $fechaDoc,
        $fechaDoc,
        $fechaDoc,
        $pedido['CAN_TOT'],
        $pedido['IMP_TOT1'],
        $pedido['IMP_TOT2'],
        $pedido['IMP_TOT3'],
        $pedido['IMP_TOT4'],
        $pedido['DES_TOT'],
        $pedido['DES_FIN'],
        $pedido['COM_TOT'],
        $pedido['CVE_OBS'],
        $pedido['NUM_ALMA'],
        $pedido['ACT_CXC'],
        $pedido['ACT_COI'],
        $pedido['ENLAZADO'],
        $pedido['NUM_MONED'],
        $pedido['TIPCAMB'],
        $pedido['NUM_PAGOS'],
        $pedido['FECHAELAB'],
        $pedido['PRIMERPAGO'],
        $pedido['RFC'],
        $pedido['CTLPOL'],
        'T',
        0,
        $SERIE,
        $folioFactura,
        $pedido['AUTOANIO'],
        $DAT_ENVIO,
        $pedido['CONTADO'],
        $CVE_BITA,
        $pedido['BLOQ'],
        $tipDocE,
        $pedido['DES_FIN_PORC'],
        $pedido['DES_TOT_PORC'],
        $pedido['COM_TOT_PORC'],
        $pedido['IMPORTE'],
        $pedido['METODODEPAGO'],
        $pedido['NUMCTAPAGO'],
        $docAnt,
        $tipDocAnt,
        $fechaDoc,
        $pedido['FORMADEPAGOSAT'],
        $pedido['USO_CFDI'],
        'F',
        $pedido['REG_FISC'],
        $pedido['IMP_TOT5'],
        $pedido['IMP_TOT6'],
        $pedido['IMP_TOT7'],
        $pedido['IMP_TOT8']
    ];

    if (count($paramsInsert) !== 57) {
        echo json_encode([
            'success' => false,
            'message' => "Error: La cantidad de valores en VALUES no coincide con las columnas en INSERT INTO",
            'expected_columns' => 57,
            'received_values' => count($paramsInsert),
            'values' => $paramsInsert
        ]);
        die();
    }

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en FACTRXX",
            'errors' => sqlsrv_errors()
        ]);
        die();
    } else {
        /*echo json_encode([
            'success' => true,
            'message' => "FACTF insertado correctamente con "
        ]);*/
    }

    //echo json_encode(['success' => true, 'folioFactura' => $folioFactura]);
}
function insertarFactf_Clib($conexionData, $folioFactura, $claveSae, $conn)
{
    if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tablas dinámicas
    $tablaFactrClib = "[{$conexionData['nombreBase']}].[dbo].[FACTF_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $claveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    //$claveDoc = str_pad($claveDoc, 20, ' ', STR_PAD_LEFT);


    // ✅ 2. Insertar en `FACTR_CLIB01`
    $sqlInsert = "INSERT INTO $tablaFactrClib (CLAVE_DOC) VALUES (?)";
    $paramsInsert = [$claveDoc];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en FACTR_CLIBXX con CVE_DOC $claveDoc",
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    //sqlsrv_free_stmt($stmtUltimaRemision);
    sqlsrv_free_stmt($stmtInsert);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTF_CLIBXX insertado correctamente con CVE_DOC $claveDoc"
    ]);*/
}
function obtenerFolio($conexionData, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta SQL para obtener el siguiente folio
    $sql = "SELECT (ULT_DOC + 1) AS FolioSiguiente, SERIE FROM $nombreTabla WHERE TIP_DOC = 'F' AND SERIE = 'MD'";
    //$sql = "SELECT (ULT_DOC + 1) AS FolioSiguiente, SERIE FROM $nombreTabla WHERE TIP_DOC = 'F' AND SERIE = 'AV'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener el siguiente folio
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $folioSiguiente = $row ? $row['FolioSiguiente'] : null;
    $SERIE = $row ? $row['SERIE'] : null;

    actualizarFolio($conexionData, $claveSae, $conn);
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    // Retornar el folio siguiente
    
    //return $folioSiguiente;
    return [
        'folioSiguiente' => $folioSiguiente,
        'serie' => $SERIE
    ];
}
function actualizarFolio($conexionData, $claveSae, $conn)
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
            WHERE TIP_DOC = 'F' AND SERIE = 'MD'";

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

    // Retornar el resultado
    if ($rowsAffected > 0) {
        //echo json_encode(['success' => true, 'message' => 'Folio actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron folios para actualizar']);
    }
}
function obtenerRemision($conexionData, $pedidoId, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    $folioAnterior = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $folioAnterior = str_pad($folioAnterior, 20, ' ', STR_PAD_LEFT);

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT FOLIO FROM $nombreTabla WHERE TIP_DOC = 'R' AND DOC_ANT = ?";
    $params = [$folioAnterior];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $folio = $row ? $row['FOLIO'] : null;
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    //echo json_encode(['success' => true, 'folio remision' => $folio]);
    // Retornar el folio siguiente
    return $folio;
}
function actualizarAfac($conexionData, $remision, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }
    $remision = str_pad($remision, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $remision = str_pad($remision, 20, ' ', STR_PAD_LEFT);
    // Obtener el total de la venta, impuestos y descuentos del pedido
    $tablaRemision = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sqlRemision = "SELECT CAN_TOT, IMP_TOT1, IMP_TOT2, IMP_TOT3, IMP_TOT4, IMP_TOT5, IMP_TOT6, IMP_TOT7, IMP_TOT8, DES_TOT, DES_FIN, COM_TOT, FECHA_DOC 
                  FROM $tablaRemision 
                  WHERE CVE_DOC = ?";

    $paramsPedido = [$remision];
    $stmtRemision = sqlsrv_query($conn, $sqlRemision, $paramsPedido);

    if ($stmtRemision === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al obtener los datos del pedido',
            'errors' => sqlsrv_errors()
        ]));
    }

    $remisions = sqlsrv_fetch_array($stmtRemision, SQLSRV_FETCH_ASSOC);
    if (!$remisions) {
        die(json_encode([
            'success' => false,
            'message' => "No se encontraron datos del pedido $remision"
        ]));
    }

    // 📌 Calcular valores a actualizar
    $totalVenta = $remisions['CAN_TOT']; // 📌 Total de venta
    $totalImpuestos = $remisions['IMP_TOT1'] + $remisions['IMP_TOT2'] + $remisions['IMP_TOT3'] + $remisions['IMP_TOT4'] +
        $remisions['IMP_TOT5'] + $remisions['IMP_TOT6'] + $remisions['IMP_TOT7'] + $remisions['IMP_TOT8']; // 📌 Suma de impuestos
    $totalDescuento = $remisions['DES_TOT']; // 📌 Descuento total
    $descuentoFinal = $remisions['DES_FIN']; // 📌 Descuento final
    $comisiones = $remisions['COM_TOT']; // 📌 Comisiones

    // 📌 Obtener el primer día del mes de la remision para PER_ACUM
    $perAcum = $remisions['FECHA_DOC']->format('Y-m-01 00:00:00');

    // 📌 Actualizar AFACT02
    $tablaAfact = "[{$conexionData['nombreBase']}].[dbo].[AFACT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sqlUpdate = "UPDATE $tablaAfact 
                  SET RVTA_COM = RVTA_COM + ?, 
                      RDESCTO = RDESCTO + ?, 
                      RDES_FIN = RDES_FIN + ?, 
                      RIMP = RIMP + ?, 
                      RCOMI = RCOMI + ? 
                  WHERE PER_ACUM = ?";

    $paramsUpdate = [$totalVenta, $totalDescuento, $descuentoFinal, $totalImpuestos, $comisiones, $perAcum];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al actualizar AFACT02',
            'errors' => sqlsrv_errors()
        ]));
    }

    sqlsrv_free_stmt($stmtRemision);
    sqlsrv_free_stmt($stmtUpdate);
    /*echo json_encode([
        'success' => true,
        'message' => "AFACT02 actualizado correctamente para el período $perAcum"
    ]);*/
}
function insertarAlerta_Usuario($conexionData, $claveSae)
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
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tabla dinámica
    $tablaAlertaUsuario = "[{$conexionData['nombreBase']}].[dbo].[ALERTA_USUARIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "INSERT INTO $tablaAlertaUsuario
    (CVE_ALERTA,ID_USUARIO,ACTIVA) VALUES (?, ?, ?)";

    $params = [5, 0, "N"];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al Insertar ALERTA_USUARIOXX',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA_USUARIOXX creada correctamente"
    ]);*/
}
function actualizarAlerta_Usuario1($conexionData, $claveSae, $conn)
{
   if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tabla dinámica
    $tablaAlertaUsuario = "[{$conexionData['nombreBase']}].[dbo].[ALERTA_USUARIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA_USUARIO01`
    $sqlUpdate = "UPDATE $tablaAlertaUsuario 
                  SET ACTIVA = ? 
                  WHERE ID_USUARIO = ? AND CVE_ALERTA = ?";
    $paramsUpdate = ['S', 1, 4];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTA_USUARIOX1',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_free_stmt($stmtUpdate);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA_USUARIOX1 actualizada correctamente"
    ]);*/
}
function actualizarAlerta_Usuario2($conexionData, $claveSae, $conn)
{
   if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tabla dinámica
    $tablaAlertaUsuario = "[{$conexionData['nombreBase']}].[dbo].[ALERTA_USUARIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA_USUARIO01`
    $sqlUpdate = "UPDATE $tablaAlertaUsuario 
                  SET ACTIVA = ? 
                  WHERE ID_USUARIO = ? AND CVE_ALERTA = ?";
    $paramsUpdate = ['N', 1, 1];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTA_USUARIOX2',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_free_stmt($stmtUpdate);

    /*echo json_encode([
         'success' => true,
         'message' => "ALERTA_USUARIOX2 actualizada correctamente"
     ]);*/
}
function actualizarAlerta1($conexionData, $claveSae, $conn)
{
 if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tabla dinámica
    $tablaAlerta = "[{$conexionData['nombreBase']}].[dbo].[ALERTA" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA01`
    $sqlUpdate = "UPDATE $tablaAlerta 
                  SET CANT_DOC = ? 
                  WHERE CVE_ALERTA = ?";
    $paramsUpdate = [0, 4];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTAXX1',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_free_stmt($stmtUpdate);
    /*echo json_encode([
        'success' => true,
        'message' => "ALERTAXX1 actualizada correctamente"
    ]);*/
}
function actualizarAlerta2($conexionData, $claveSae, $conn)
{
   if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Tabla dinámica
    $tablaAlerta = "[{$conexionData['nombreBase']}].[dbo].[ALERTA" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ Actualizar `ALERTA01`
    $sqlUpdate = "UPDATE $tablaAlerta 
                  SET CANT_DOC = ? 
                  WHERE CVE_ALERTA = ?";
    $paramsUpdate = [0, 1];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar ALERTAXX2',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    sqlsrv_free_stmt($stmtUpdate);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTAXX2 actualizada correctamente"
    ]);*/
}
function crearCxc($conexionData, $claveSae, $remision, $folioFactura, $conn)
{
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }
    $tablaCunetM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    //Datos de la remision
    $dataRemision = datosRemision($conexionData, $claveSae, $remision);
    /*$folioFactura = urldecode($folioFactura) . urldecode($SERIE);
    $CVE_DOC = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);*/

    // Preparar los datos para el INSERT
    $cve_clie   = $dataRemision['CVE_CLPV']; // Clave del cliente
    $CVE_CLIE = formatearClaveCliente($cve_clie);
    $data = obtenerDatosCliente($CVE_CLIE, $conexionData, $claveSae);
    $diasCredito = $data['DIASCRED'];
    $refer      = $folioFactura; // Puede generarse o venir del formulario
    $num_cpto   = '1';  // Concepto: ajustar según tu lógica de negocio
    $num_cargo  = 1;    // Número de cargo: un valor de ejemplo
    $no_factura = $folioFactura; // Número de factura o pedido
    $docto = $folioFactura;   // Puede ser un código de documento, si aplica
    //$IMPORTE = 0;
    $STRCVEVEND = $dataRemision['CVE_VEND'];

    $AFEC_COI = '';
    $NUM_MONED = 1;
    $TCAMBIO = 1;
    $TIPO_MOV = 'A'; //Aqui

    $IMPORTE = $dataRemision['IMPORTE'];

    $fecha_apli = date("Y-m-d 00:00:00.000");         // Fecha de aplicación: ahora
    $fecha_venc = date("Y-m-d 00:00:00.000", strtotime($fecha_apli . ' + ' . $diasCredito . ' day')); // Vencimiento a 24 horas
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
                    UUID,
                    VERSION_SINC,
                    USUARIOGL,
                    FECHAELAB,
                    IMPMON_EXT,
                    SIGNO,
                    STRCVEVEND
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, 0, ?, ?, ?, ?)";

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

    //echo json_encode(['success' => true, 'no_facturaCuenM' => $no_factura]);

    return [
        'factura' => $no_factura,
        'referencia' => $refer,
        'IMPORTE' => $IMPORTE,
        'STRCVEVEND' => $STRCVEVEND,
        'CVE_CLIE' => $CVE_CLIE
    ];
}
function pagarCxc($conexionData, $claveSae, $datosCxC, $folioFactura, $remision, $conn){
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }
    $tablaCunetDet = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    /*$CVE_DOC = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $folioFactura = urldecode($folioFactura) . urldecode($SERIE);
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);*/

    // Preparar los datos para el INSERT
    $cve_clie   = $datosCxC['CVE_CLIE']; // Clave del cliente
    $CVE_CLIE = formatearClaveCliente($cve_clie);
    $refer      = $datosCxC['referencia']; // Puede generarse o venir del formulario
    $num_cpto   = '22';  // Concepto: ajustar según tu lógica de negocio
    $num_cargo  = 1;    // Número de cargo: un valor de ejemplo
    $no_factura = $datosCxC['factura']; // Número de factura o pedido
    $docto = $datosCxC['factura'];   // Puede ser un código de documento, si aplica

    $STRCVEVEND = $datosCxC['STRCVEVEND'];

    $AFEC_COI = '';
    $NUM_MONED = 1;
    $TCAMBIO = 1;
    $TIPO_MOV = 'A'; //Aqui



    $IMPORTE = $datosCxC['IMPORTE'];

    $fecha_apli = date("Y-m-d 00:00:00.000");         // Fecha de aplicación: ahora
    $fecha_venc = date("Y-m-d 00:00:00.000", strtotime($fecha_apli . ' + 1 day')); // Vencimiento a 24 horas
    $status     = 'A';  // Estado inicial, por ejemplo
    $USUARIO    = '0';
    $IMPMON_EXT = $IMPORTE;
    $SIGNO = 1;

    // 1) Lista exacta de columnas
    $cols = [
        'CVE_CLIE',
        'REFER',
        'NUM_CPTO',
        'NUM_CARGO',
        'NO_FACTURA',
        'DOCTO',
        'IMPORTE',
        'FECHA_APLI',
        'FECHA_VENC',
        'USUARIO',
        'AFEC_COI',
        'NUM_MONED',
        'TCAMBIO',
        'TIPO_MOV',
        'IMPMON_EXT',
        'SIGNO',
        'ID_MOV',
        'NO_PARTIDA'
    ];

    // 2) Armamos el SQL con tanto "?" como columnas
    $columnList    = implode(", ", $cols);
    $placeholders  = implode(", ", array_fill(0, count($cols), "?"));
    $sql = "INSERT INTO $tablaCunetDet ($columnList) VALUES ($placeholders)";

    // 3) Preparamos los parámetros EN EL MISMO ORDEN
    $params = [
        $CVE_CLIE,        // CVE_CLIE
        $refer,         // REFER
        '22',             // NUM_CPTO
        1,                // NUM_CARGO
        $no_factura,         // NO_FACTURA
        $docto,         // DOCTO
        $datosCxC['IMPORTE'], // IMPORTE
        $fecha_apli,      // FECHA_APLI
        $fecha_venc,      // FECHA_VENC
        '0',              // USUARIO
        '',              // AFEC_COI
        1,                // NUM_MONED
        1,                // TCAMBIO
        'A',              // TIPO_MOV
        $datosCxC['IMPORTE'], // IMPMON_EXT
        -1,                 // SIGNO
        1,
        1
    ];

    // 4) Ejecutar
    $stmt = sqlsrv_query($conn, $sql, $params);
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
    /*echo json_encode(['success' => true, 'message' => 'CxC creada y pagada.']);*/
    return;
}
function insertarDoctoSig($conexionData, $remision, $folioFactura, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    // ✅ Formatear los IDs para que sean de 10 caracteres con espacios a la izquierda
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    //$cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // Tabla dinámica
    $tablaDoctoSig = "[{$conexionData['nombreBase']}].[dbo].[DOCTOSIGF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 1. Insertar relación: Remisión -> Factura (S = Sigue)
    $sqlInsert1 = "INSERT INTO $tablaDoctoSig 
        (TIP_DOC, CVE_DOC, ANT_SIG, TIP_DOC_E, CVE_DOC_E, PARTIDA, PART_E, CANT_E) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $params1 = ['R', $remisionId, 'S', 'F', $cveDoc, 1, 1, 1];

    $stmt1 = sqlsrv_query($conn, $sqlInsert1, $params1);
    if ($stmt1 === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al insertar relación Pedido -> Remisión en DOCTOSIGFXX",
            'errors' => sqlsrv_errors()
        ]));
    }

    // ✅ 2. Insertar relación: Factura -> Remision (A = Anterior)
    $sqlInsert2 = "INSERT INTO $tablaDoctoSig 
        (TIP_DOC, CVE_DOC, ANT_SIG, TIP_DOC_E, CVE_DOC_E, PARTIDA, PART_E, CANT_E) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $params2 = ['F', $cveDoc, 'A', 'R', $remisionId, 1, 1, 1];

    $stmt2 = sqlsrv_query($conn, $sqlInsert2, $params2);
    if ($stmt2 === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al insertar relación Remisión -> Pedido en DOCTOSIGFXX",
            'errors' => sqlsrv_errors()
        ]));
    }

    // ✅ Cerrar conexión
    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);

    /*echo json_encode([
        'success' => true,
        'message' => "DOCTOSIGFXX insertado correctamente para Remisión $remision y Factura $folioFactura"
    ]);*/
}
function obtenerDatosPreEnlace($conexionData, $claveSae, $remision)
{
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID"      => $conexionData['usuario'],
        "PWD"      => $conexionData['password'],
        "CharacterSet"         => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors'  => sqlsrv_errors()
        ]));
    }

    // Formatear remisión (20 chars, ceros y espacios a la izquierda)
    $remision = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $rem = str_pad($remision, 20, ' ', STR_PAD_LEFT);

    $tablaPar = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR"
        . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaEnl = "[{$conexionData['nombreBase']}].[dbo].[ENLACE_LTPD"
        . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Aquí corregimos el SELECT: faltaba la coma y el AS
    $sql = "
      SELECT 
        P.[CVE_DOC]   AS CVE_DOC,
        P.[CVE_ART]   AS CVE_ART,
        P.[E_LTPD]    AS E_LTPD_ANT,    -- el enlace viejo si lo hubiera
        E.[REG_LTPD]  AS REG_LTPD,
        E.[CANTIDAD]  AS CANTIDAD,
        E.[PXRS]       AS PXRS
      FROM $tablaPar P
      INNER JOIN $tablaEnl E
        ON P.[E_LTPD] = E.[E_LTPD]
      WHERE P.[CVE_DOC] = ?
    ";
    $stmt = sqlsrv_query($conn, $sql, [$rem]);
    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al obtener las partidas",
            'errors'  => sqlsrv_errors()
        ]));
    }

    $datos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Asegúrate de castear bien si lo necesitas
        $datos[] = [
            'CVE_DOC'   => trim($row['CVE_DOC']),
            'CVE_ART'   => trim($row['CVE_ART']),
            'E_LTPD_ANT'  => (int) $row['E_LTPD_ANT'],
            'REG_LTPD'  => (int) $row['REG_LTPD'],
            'CANTIDAD'  => (float)$row['CANTIDAD'],
            'PXRS'       => (float)$row['PXRS']
        ];
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    //echo json_encode(['success' => true, 'datos' => $datos]);
    return $datos;
}
function insertarEnlaceLTPD($conn, $conexionData, array $lotesUtilizados, string $claveSae, string $claveProducto)
{
    $tablaEnlace = "[{$conexionData['nombreBase']}].[dbo].[ENLACE_LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // 1) Sólo una vez: obtener el próximo E_LTPD
    $sql = "SELECT ISNULL(MAX(E_LTPD),0)+1 AS NUEVO_E_LTPD FROM $tablaEnlace";
    $st  = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC);
    $nuevoELTPD = (int)$row['NUEVO_E_LTPD'];
    sqlsrv_free_stmt($st);

    // 2) Insertar cada lote con ese mismo E_LTPD
    $insert = "
    INSERT INTO $tablaEnlace (E_LTPD, REG_LTPD, CANTIDAD, PXRS)
    VALUES (?, ?, ?, ?)
  ";
    $resultados = [];
    foreach ($lotesUtilizados as $lote) {
        $params = [
            $nuevoELTPD,
            $lote['REG_LTPD'],
            $lote['CANTIDAD'],
            $lote['PXRS']
        ];
        $ok = sqlsrv_query($conn, $insert, $params);
        if (!$ok) {
            throw new Exception("Error al insertar lote {$lote['nuevoELTPD']}: " . print_r(sqlsrv_errors(), 1));
        }
        $resultados[] = [
            'E_LTPD'   => $nuevoELTPD,
            'REG_LTPD' => $lote['REG_LTPD'],
            'CANTIDAD' => $lote['CANTIDAD'],
            'PXRS'     => $lote['PXRS'],
            'CVE_ART'  => $claveProducto
        ];
    }

    return $resultados;
}
function actualizarFactr($conexionData, $remision, $folioFactura, $claveSae, $pedidoId, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Tablas dinámicas
    $tablaFactr = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Formatear los valores para SQL Server
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    $cveDocFactura = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    //$cveDocFactura = str_pad($cveDocFactura, 20, ' ', STR_PAD_LEFT);

    // ✅ Actualizar DOC_SIG y TIP_DOC_SIG en FACTPXX
    $sqlUpdate = "UPDATE $tablaFactr 
                  SET DOC_SIG = ?, 
                      TIP_DOC_SIG = ? 
                  WHERE CVE_DOC = ?";

    $paramsUpdate = [$cveDocFactura, 'F', $remisionId];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar FACTPXX para el pedido $remisionId",
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTPXX1 actualizado correctamente para el pedido $pedidoId con remision $cveDocFactura"
    ]);*/
}
function actualizarFactr2($conexionData, $remision, $claveSae, $pedidoId, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Tablas dinámicas
    $tablaFactr = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactr = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Formatear el pedidoId para SQL Server
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    // ✅ Ejecutar la actualización de `TIP_FAC`
    $sqlUpdate = "UPDATE $tablaFactr 
                  SET TIP_FAC = (
                      CASE 
                          WHEN (SELECT SUM(P.PXS) 
                                FROM $tablaParFactr P 
                                WHERE P.CVE_DOC = ? 
                                AND $tablaFactr.CVE_DOC = P.CVE_DOC) = 0 
                          THEN 'R' 
                          ELSE TIP_FAC 
                      END) 
                  WHERE CVE_DOC = ?";

    $paramsUpdate = [$remisionId, $remisionId];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar TIP_FAC en FACTPXX para el pedido $remisionId",
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTPXX2 actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function actualizarFactr3($conexionData, $remision, $claveSae, $pedidoId, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Tablas dinámicas
    $tablaFactr = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactr = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Formatear el pedidoId (CVE_DOC)
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    // Fecha de sincronización
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 1. Consulta para actualizar FACTPXX
    $sqlUpdate = "UPDATE $tablaFactr 
                  SET TIP_DOC_E = ?, 
                      VERSION_SINC = ?, 
                      ENLAZADO = (CASE 
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactr P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactr.CVE_DOC = P.CVE_DOC) = 0 THEN 'T'
                                    WHEN (SELECT SUM(P.PXS) FROM $tablaParFactr P 
                                          WHERE P.CVE_DOC = ? AND $tablaFactr.CVE_DOC = P.CVE_DOC) > 0 THEN 'P' 
                                    ELSE ENLAZADO END)
                  WHERE CVE_DOC = ?";

    $paramsUpdate = ['R', $fechaSinc, $remisionId, $remisionId, $remisionId];

    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
    if ($stmtUpdate === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al actualizar FACTPXX para el pedido $remisionId",
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmtUpdate);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTPXX3 actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function insertarPar_Factr($conexionData, $remision, $folioFactura, $claveSae, $conn)
{
    if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    //$cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // Tablas dinámicas
    $tablaPartidasRemision = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidasFactura = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaMovimientos = "[{$conexionData['nombreBase']}].[dbo].[MINVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // ✅ 2. Obtener las partidas del pedido (`PAR_FACTPXX`)
    $sqlPartidas = "SELECT NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, 
                           IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA, TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4, 
                           DESC1, DESC2, DESC3, COMI, APAR, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, 
                           TIPO_PROD, TIPO_ELEM, CVE_OBS, REG_SERIE, E_LTPD, IMPRIMIR, MAN_IEPS, 
                           MTO_PORC, MTO_CUOTA, CVE_ESQ, IMPU5, IMPU6, IMPU7, IMPU8, IMP5APLA, 
                           IMP6APLA, IMP7APLA, IMP8APLA, TOTIMP5, TOTIMP6, TOTIMP7, TOTIMP8,
                           PREC_NETO, CVE_PRODSERV, CVE_UNIDAD, E_LTPD
                    FROM $tablaPartidasRemision WHERE CVE_DOC = ?";
    $paramsPartidas = [$remisionId];

    $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $paramsPartidas);
    if ($stmtPartidas === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Fecha de sincronización
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 4. Insertar cada partida en `PAR_FACTFXX`
    while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
        $TOT_PARTIDA = $row['CANT'] * $row['PREC'];

        // **Buscar `E_LTPD` en `$enlaceMap`, si no existe, usar el valor original de `$row['E_LTPD']`**
        //$eLtpd = isset($enlaceMap[trim($row['CVE_ART'])]) ? $enlaceMap[trim($row['CVE_ART'])] : $row['E_LTPD'];

        $sqlInsert = "INSERT INTO $tablaPartidasFactura 
            (CVE_DOC, NUM_PAR, CVE_ART, CANT, PXS, PREC, COST, IMPU1, IMPU2, IMPU3, IMPU4, 
            IMP1APLA, IMP2APLA, IMP3APLA, IMP4APLA, TOTIMP1, TOTIMP2, TOTIMP3, TOTIMP4, DESC1, 
            DESC2, DESC3, COMI, APAR, ACT_INV, NUM_ALM, POLIT_APLI, TIP_CAM, UNI_VENTA, 
            TIPO_PROD, TIPO_ELEM, CVE_OBS, REG_SERIE, NUM_MOV, TOT_PARTIDA, IMPRIMIR, MAN_IEPS, 
            APL_MAN_IMP, CUOTA_IEPS, APL_MAN_IEPS, MTO_PORC, MTO_CUOTA, CVE_ESQ, VERSION_SINC, UUID,
            IMPU5, IMPU6, IMPU7, IMPU8, IMP5APLA, IMP6APLA, IMP7APLA, IMP8APLA, TOTIMP5, 
            TOTIMP6, TOTIMP7, TOTIMP8,
            PREC_NETO, CVE_PRODSERV, CVE_UNIDAD)
        VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, '',
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?)";

        $paramsInsert = [
            $cveDoc,
            $row['NUM_PAR'],
            $row['CVE_ART'],
            $row['CANT'],
            $row['PXS'],
            $row['PREC'],
            $row['COST'],
            $row['IMPU1'],
            $row['IMPU2'],
            $row['IMPU3'],
            $row['IMPU4'],
            $row['IMP1APLA'],
            $row['IMP2APLA'],
            $row['IMP3APLA'],
            $row['IMP4APLA'],
            $row['TOTIMP1'],
            $row['TOTIMP2'],
            $row['TOTIMP3'],
            $row['TOTIMP4'],
            $row['DESC1'],
            $row['DESC2'],
            $row['DESC3'],
            $row['COMI'],
            $row['APAR'],
            'S',
            $row['NUM_ALM'],
            $row['POLIT_APLI'],
            $row['TIP_CAM'],
            $row['UNI_VENTA'],
            $row['TIPO_PROD'],
            $row['TIPO_ELEM'],
            $row['CVE_OBS'],
            $row['REG_SERIE'],
            $row['NUM_MOV'],
            $TOT_PARTIDA,
            $row['IMPRIMIR'],
            $row['MAN_IEPS'],
            1,
            0,
            'C',
            $row['MTO_PORC'],
            $row['MTO_CUOTA'],
            $row['CVE_ESQ'],
            $fechaSinc,
            $row['IMPU5'],
            $row['IMPU6'],
            $row['IMPU7'],
            $row['IMPU8'],
            $row['IMP5APLA'],
            $row['IMP6APLA'],
            $row['IMP7APLA'],
            $row['IMP8APLA'],
            $row['TOTIMP5'],
            $row['TOTIMP6'],
            $row['TOTIMP7'],
            $row['TOTIMP8'],
            0,
            $row['CVE_PRODSERV'],
            $row['CVE_UNIDAD']
        ];

        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
        if ($stmtInsert === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al Insertar en Par_Factr',
                'errors' => sqlsrv_errors()
            ]);
            die();
        }
    }
    //echo json_encode(['success' => true, 'folioFactura' => $folioFactura]);
}
function insertarPar_Factf_Clib($conexionData, $remision, $folioFactura, $claveSae, $conn)
{
    if ($conn === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);
    // Tablas dinámicas
    $tablaPartidasRemisiones = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactfClib = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    //$cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // ✅ 2. Contar el número de partidas del pedido en `PAR_FACTPXX`
    $sqlContarPartidas = "SELECT COUNT(*) AS TOTAL_PARTIDAS FROM $tablaPartidasRemisiones WHERE CVE_DOC = ?";
    $paramsContar = [$remisionId];

    $stmtContar = sqlsrv_query($conn, $sqlContarPartidas, $paramsContar);
    if ($stmtContar === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al contar las partidas del pedido',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $partidasData = sqlsrv_fetch_array($stmtContar, SQLSRV_FETCH_ASSOC);
    if (!$partidasData) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron partidas en el pedido'
        ]);
        die();
    }

    $numPartidas = $partidasData['TOTAL_PARTIDAS'];
    // ✅ 3. Insertar en `PAR_FACTF_CLIB0X`
    $sqlInsert = "INSERT INTO $tablaParFactfClib (CLAVE_DOC, NUM_PART) VALUES (?, ?)";
    $paramsInsert = [$cveDoc, $numPartidas];

    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        echo json_encode([
            'success' => false,
            'message' => "Error al insertar en PAR_FACTR_CLIB01 con CVE_DOC $cveDoc",
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    // Cerrar conexión
    //sqlsrv_free_stmt($stmtUltimaRemision);
    sqlsrv_free_stmt($stmtContar);
    sqlsrv_free_stmt($stmtInsert);

    /*echo json_encode([
        'success' => true,
        'message' => "PAR_FACTF_CLIB01 insertado correctamente con CVE_DOC $cveDoc y $numPartidas partidas"
    ]);*/
}
function actualizarControl1($conexionData, $claveSae, $conn)
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

    $sql = "UPDATE $nombreTabla SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 62";

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

    $sql = "UPDATE $nombreTabla SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 58";

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
function actualizarControl3($conexionData, $claveSae, $conn)
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
function actualizarControl4($conexionData, $claveSae, $conn)
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

    $sql = "UPDATE $nombreTabla SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 67";

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
function actualizarInclie2($conexionData, $claveSae, $claveCliente, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    $clave = formatearClaveCliente($claveCliente);

    // Construcción dinámica de la tabla CLIEXX
    $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "UPDATE $tablaClie 
            SET VENTAS = VENTAS + 1 
            WHERE CLAVE = ?";
    $params = [$clave];

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al actualizar CLIE',
            'errors' => sqlsrv_errors()
        ]));
    }

    // Cerrar conexión
    sqlsrv_free_stmt($stmt);

    /*echo json_encode([
        'success' => true,
        'message' => "CLIEXX actualizado correctamente "
    ]);*/
}
function actualizarInclie1($conexionData, $claveSae, $claveCliente, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors'  => sqlsrv_errors()
        ]));
    }

    // 2) Preparar variables (igual que tus @P1…@P7)
    $incrementoSaldo   = 1.16;                              // @P1
    $fechaComparacion  = '2025-05-13 00:00:00';             // @P2
    $fechaUltCom       = '2025-05-13 00:00:00';             // @P3
    $ultVentad         = 'PRUEBA0000000001';                // @P4
    $ultCompm          = 1.16;                              // @P5
    $versionSinc       = '2025-05-13 18:23:48.850';         // @P6
    $claveCliente      = str_pad('1', 10, " ", STR_PAD_LEFT); // @P7 — igual que formatearClaveCliente

    // 3) Nombre dinámico de la tabla CLIExx
    $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // 4) Armar el UPDATE con CASE para FCH_ULTCOM
    $sql = "
    UPDATE $tablaClie
        SET SALDO       = SALDO + ?,
          FCH_ULTCOM  = CASE 
                           WHEN FCH_ULTCOM IS NULL
                             OR FCH_ULTCOM < ? 
                           THEN ? 
                           ELSE FCH_ULTCOM
                         END,
          ULT_VENTAD  = ?,
          ULT_COMPM   = ?,
          VERSION_SINC= ?
    WHERE CLAVE = ?
    ";

    // 5) Mapear los parámetros en el mismo orden
    $params = [
        $incrementoSaldo,   // ?
        $fechaComparacion,  // ?
        $fechaUltCom,       // ?
        $ultVentad,         // ?
        $ultCompm,          // ?
        $versionSinc,       // ?
        $claveCliente       // ?
    ];

    // 6) Ejecutar
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al ejecutar UPDATE CLIE',
            'errors'  => sqlsrv_errors()
        ]));
    }

    // 7) Cerrar
    sqlsrv_free_stmt($stmt);

    // 8) (Opcional) éxito
    /*echo json_encode([
        'success' => true,
        'message' => 'CLIE actualizado correctamente'
    ]);*/
}
function insertatInfoClie($conexionData, $claveSae, $claveCliente, $conn){
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors'  => sqlsrv_errors()
        ]));
    }
    $dataCliente = obtenerDatosCliente($claveCliente, $conexionData, $claveSae);
    $tablaClienteInfo = "[{$conexionData['nombreBase']}].[dbo].[INFCLI" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sqlUltimo = "SELECT ISNULL(MAX(CVE_INFO), 0) + 1 AS NUEVO_CVE FROM $tablaClienteInfo";
    $stmtUlt = sqlsrv_query($conn, $sqlUltimo);
    if ($stmtUlt === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al obtener el último CVE",
            'errors'  => sqlsrv_errors()
        ]));
    }
    $rowUlt = sqlsrv_fetch_array($stmtUlt, SQLSRV_FETCH_ASSOC);
    $nuevo = $rowUlt['NUEVO_CVE'];

    $sql = "INSERT INTO $tablaClienteInfo (CALLE, CODIGO, COLONIA, CRUZAMIENTOS, CRUZAMIENTOS2, CURP,
    CVE_INFO, CVE_PAIS_SAT, CVE_ZONA, ESTADO, MUNICIPIO, NOMBRE, NUMEXT,
    NUMINT, PAIS, POB, REFERDIR, REG_FISC, RFC)
    VALUES(?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?)";
    $params = [
        $dataCliente['CALLE'],
        $dataCliente['CODIGO'],
        $dataCliente['COLONIA'],
        $dataCliente['CRUZAMIENTOS'],
        $dataCliente['CRUZAMIENTOS2'],
        $dataCliente['CURP'],
        $nuevo,
        $dataCliente['CVE_PAIS_SAT'],
        $dataCliente['CVE_ZONA'],
        $dataCliente['ESTADO'],
        $dataCliente['MUNICIPIO'],
        $dataCliente['NOMBRE'],
        $dataCliente['NUMEXT'],
        $dataCliente['NUMINT'],
        $dataCliente['PAIS'],
        $dataCliente['LOCALIDAD'],
        $dataCliente['REFERDIR'],
        $dataCliente['REG_FISC'],
        $dataCliente['RFC']
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => "Error al insertar en INFCLI",
            'errors'  => sqlsrv_errors()
        ]));
    }
    //echo json_encode(['success' => true, 'cliente' => $claveCliente]);

    return $nuevo;
}
function actualizarPar_Factf1($conexionData, $claveSae, $folioUnido, array $enlaces, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors'  => sqlsrv_errors(),
        ]));
    }

    // 2) Prepara el padding de la remisión igual que en PAR_FACTFxx
    /*$rem  = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $rem  = str_pad($rem,      20, ' ', STR_PAD_LEFT);*/

    // 3) Nombre de la tabla PAR_FACTFxx
    $tablaPar = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF"
        . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // 4) Preparamos el UPDATE parametrizado
    $sql = "
        UPDATE $tablaPar
        SET    E_LTPD = ?
        WHERE  CVE_DOC = ?
          AND  CVE_ART = ?
    ";

    // 5) Ejecutamos uno por uno
    foreach ($enlaces as $enlace) {
        $params = [
            $enlace['E_LTPD'],
            $folioUnido,
            $enlace['CVE_ART'],
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            // Si falla, hacemos rollback manual y cortamos
            sqlsrv_close($conn);
            die(json_encode([
                'success' => false,
                'message' => "Error al actualizar PAR_FACTR para {$enlace['CVE_ART']}",
                'errors'  => sqlsrv_errors(),
            ]));
        }
        sqlsrv_free_stmt($stmt);
    }

    return [
        'success' => true,
        'message' => "Se actualizaron " . count($enlaces) . " partidas con el nuevo E_LTPD.",
    ];
}
function insertarCFDI($conexionData, $claveSae, $folioFactura, $conn)
{
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    $facturaId = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    //$facturaId = str_pad($facturaId, 20, ' ', STR_PAD_LEFT);

    $tablaCFDI = "[{$conexionData['nombreBase']}].[dbo].[CFDI" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "INSERT INTO $tablaCFDI
    (TIPO_DOC, CVE_DOC, VERSION, UUID, NO_SERIE, FECHA_CERT, FECHA_CANCELA, XML_DOC, XML_DOC_CANCELA,
    DESGLOCEIMP1, DESGLOCEIMP2, DESGLOCEIMP3, DESGLOCEIMP4, EN_TABLERO)
    values
    (?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?)";
    $params = [
        "F",
        $facturaId,
        "1.1",
        '',
        '',
        '',
        '',
        NULL,
        NULL,
        'N',
        'N',
        'N',
        'S',
        'S'
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        // Si falla, hacemos rollback manual y cortamos
        sqlsrv_close($conn);
        die(json_encode([
            'success' => false,
            'message' => "Error al insertar",
            'errors'  => sqlsrv_errors(),
        ]));
    }
    sqlsrv_free_stmt($stmt);
    //echo json_encode(['success' => true, 'facturaId' => $facturaId]);
}
function sumarSaldo($conexionData, $claveSae, $pagado, $conn){
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
        [SALDO] = [SALDO] + (? * 1)
        WHERE CLAVE = ?";

    $params = [$pagado['IMPORTE'], $pagado['CVE_CLIE' ]];

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
    $cliente = $pagado['CVE_CLIE' ];
    return json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente para el cliente: $cliente"
    ]);
}
function restarSaldo($conexionData, $claveSae, $pagado, $conn)
{
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
        [SALDO] = [SALDO] - (? * -1)
        WHERE CLAVE = ?";

    $params = [$pagado['importe'], $pagado['CLIENTE']];

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

    return json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente"
    ]);
}
/*******************************************************************************************************/
function validarLotesFactura($conexionData, $claveSae, $remision, $conn)
{
    // 2) Obtengo todos los lotes de la remisión
    $rows = obtenerDatosPreEnlace($conexionData, $claveSae, $remision);

    // 3) Agrupo y desduplico por producto y por REG_LTPD
    $porProducto = [];
    foreach ($rows as $r) {
        $art = trim($r['CVE_ART']);
        $reg = $r['REG_LTPD'];
        // usamos REG_LTPD como clave para evitar duplicados
        $porProducto[$art][$reg] = [
            'REG_LTPD' => $r['REG_LTPD'],
            'CANTIDAD' => $r['CANTIDAD'],
            'PXRS'     => $r['PXRS']
        ];
    }

    // 4) Por cada producto, insertamos sus lotes (ahora sin duplicados)
    $todos = [];
    foreach ($porProducto as $claveArt => $lotesByReg) {
        // reindexamos el array
        $lotes = array_values($lotesByReg);
        $res = insertarEnlaceLTPD($conn, $conexionData, $lotes, $claveSae, $claveArt);
        $todos = array_merge($todos, $res);
    }
    sqlsrv_commit($conn);

    return $todos;
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
function gaurdarDatosEnvio($conexionData, $pedidoId, $claveSae, $conn)
{
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $cve_doc = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cve_doc = str_pad($cve_doc, 20, ' ', STR_PAD_LEFT);

    $sqlPedido = "SELECT DAT_ENVIO FROM $nombreTabla2 WHERE CVE_DOC = ?";
    $paramsPedido = [$cve_doc];
    $stmPedido = sqlsrv_query($conn, $sqlPedido, $paramsPedido);
    $row = sqlsrv_fetch_array($stmPedido, SQLSRV_FETCH_ASSOC);
    $DAT_ENVIO = $row ? $row['DAT_ENVIO'] : null;


    $sqlSelect = "SELECT * FROM $nombreTabla WHERE CVE_INFO = ?";
    $paramsSelect = [$DAT_ENVIO];
    $stmSelect = sqlsrv_query($conn, $sqlSelect, $paramsSelect);
    // Obtener los datos
    $envioData = sqlsrv_fetch_array($stmSelect, SQLSRV_FETCH_ASSOC);


    // Extraer los datos del formulario
    $CVE_INFO = obtenerUltimoDato($conexionData, $claveSae);
    $CVE_INFO = $CVE_INFO + 1;
    $CVE_CONS = "";
    $NOMBRE = $envioData['NOMBRE'];
    $CALLE = $envioData['CALLE'];
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
    $FECHA_ENV = $envioData['FECHA_ENV'];
    $NOMBRE_RECEP = "";
    $NO_RECEP = "";
    $FECHA_RECEP = "";
    //$COLONIA = "";
    $COLONIA = $envioData['COLONIA'];
    $CODIGO = $envioData['CODIGO'];
    $ESTADO = $envioData['ESTADO'];
    $PAIS = "MEXICO";
    $MUNICIPIO = $envioData['MUNICIPIO'];
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
    sqlsrv_free_stmt($stmPedido);
    sqlsrv_free_stmt($stmSelect);
    return $CVE_INFO;
}
/****************************************** Funcion Principal ******************************************/
function crearFacturacion($conexionData, $pedidoId, $claveSae, $noEmpresa, $claveCliente, $credito, $conn)
{
    global $firebaseProjectId, $firebaseApiKey;
    /* 
    'folioSiguiente' => $folioSiguiente,
    'serie' => $SERIE
    */
    $datFactura = obtenerFolio($conexionData, $claveSae, $conn);
    $folioFactura = $datFactura['folioSiguiente'];
    $SERIE = $datFactura['serie'];
    $folioFormateado = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $folioUnido = urldecode($SERIE) . urldecode($folioFormateado);
    //$folioUnido = str_pad($folioUnido, 20, ' ', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda

    $remision = obtenerRemision($conexionData, $pedidoId, $claveSae, $conn);
    $CVE_BITA = insertarBita($conexionData, $remision, $claveSae, $folioUnido, $conn);

    actualizarAfac($conexionData, $remision, $claveSae, $conn);
    //insertarAlerta_Usuario($conexionData, $claveSae); //Verifcar logica
    actualizarAlerta_Usuario1($conexionData, $claveSae, $conn);
    actualizarAlerta_Usuario2($conexionData, $claveSae, $conn);
    actualizarAlerta1($conexionData, $claveSae, $conn);
    actualizarAlerta2($conexionData, $claveSae, $conn);

    $datosCxC = crearCxc($conexionData, $claveSae, $remision, $folioUnido, $conn); //No manipula saldo
    sumarSaldo($conexionData, $claveSae, $datosCxC, $conn);
    //Pagar solo si elimino anticipo (clientes sin Credito)
    if (!$credito) {
        pagarCxc($conexionData, $claveSae, $datosCxC, $folioUnido, $remision, $conn);
        restarSaldo($conexionData, $claveSae, $datosCxC, $conn);
    }

    insertarDoctoSig($conexionData, $remision, $folioUnido, $claveSae, $conn);

    $DAT_MOSTR = insertatInfoClie($conexionData, $claveSae, $claveCliente, $conn);
    actualizarControl2($conexionData, $claveSae, $conn); //ROLLBACK
    $DAT_ENVIO = gaurdarDatosEnvio($conexionData, $remision, $claveSae, $conn);
    actualizarControl3($conexionData, $claveSae, $conn);

    insertarFactf($conexionData, $remision, $folioUnido, $CVE_BITA, $claveSae, $DAT_MOSTR, $folioFactura, $SERIE, $DAT_ENVIO, $conn);
    insertarFactf_Clib($conexionData, $folioUnido, $claveSae, $conn);

    actualizarFactr($conexionData, $remision, $folioUnido, $claveSae, $pedidoId, $conn);
    actualizarFactr2($conexionData, $remision, $claveSae, $pedidoId, $conn, $conn);
    actualizarFactr3($conexionData, $remision, $claveSae, $pedidoId, $conn, $conn);

    insertarPar_Factr($conexionData, $remision, $folioUnido, $claveSae, $conn); //Volver a realizarlo con datos nuevos
    insertarPar_Factf_Clib($conexionData, $remision, $folioUnido, $claveSae, $conn);

    $result = validarLotesFactura($conexionData, $claveSae, $remision, $conn);
    actualizarControl4($conexionData, $claveSae, $conn);

    /*$datos = obtenerDatosPreEnlace($conexionData, $claveSae, $remision);    //No se pudo por falta de datos
    
    foreach ($datos['CVE_ART'] as $producto) {
        $result = insertarEnlaceLTPD($conexionData, $claveSae, $remision, $datos['CVE_ART'], $producto);
    }*/
    //var_dump($result);

    actualizarPar_Factf1($conexionData, $claveSae, $folioUnido, $result, $conn);

    actualizarControl1($conexionData, $claveSae, $conn);
    actualizarInclie1($conexionData, $claveSae, $claveCliente, $conn); //Verificar la logica
    actualizarInclie2($conexionData, $claveSae, $claveCliente, $conn);

    insertarCFDI($conexionData, $claveSae, $folioUnido, $conn);

    return $folioUnido;
    /*return [
        'folio' => $folioFactura,
        'serie' => $SERIE,
        'folioUnico' => $folioUnido
    ];*/
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
function mostrarRemisiones($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor) {
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
        $nombreTabla2  = "[{$conexionData['nombreBase']}].[dbo].[FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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
        if ($estadoPedido == "Activos" || $estadoPedido == "Vendidos") {
            $countSql .= "WHERE f.STATUS IN ('E','O')";
        } else {
            $countSql .= "WHERE f.STATUS IN ('C')";
        }
        // Agregar filtros de fecha
        if ($filtroFecha == 'Hoy') {
            $countSql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE) ";
        } elseif ($filtroFecha == 'Mes') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE()) ";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE())) ";
        }
        if ($tipoUsuario === 'ADMINISTRADOR') {
            if ($filtroVendedor !== '') {
                $countSql      .= " AND f.CVE_VEND = ?";
                $params[]  = $filtroVendedor;
            }
        } else {
            // Usuarios no ADMIN sólo ven sus pedidos
            $countSql      .= " AND f.CVE_VEND = ?";
            $params[]  = $claveVendedor;
        }

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
function mostrarRemisionEspecifica($clave, $conexionData, $claveSae) {
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
    //$clave = str_pad($clave, 20, ' ', STR_PAD_LEFT);

    $tablaPedidos = "[{$conexionData['nombreBase']}].[dbo].[FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaPartidas = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
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
function obtenerPartidasRemision($conexionData, $clavePedido)
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
    //$clavePedido = str_pad($clavePedido, 20, ' ', STR_PAD_LEFT);
    // Tabla dinámica basada en el número de empresa
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

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

    $estado = [];

    // Iterar sobre cada <row>
    foreach ($municipios->row as $row) {
        $Estado = (string)$row['Estado'];
        // Sólo procesamos si País es 'MEX'
        if ($Estado !== $estadoSeleccionado) {
            continue;
        }
        $estado[] = [
            'Clave' => (string)$row['Clave'],
            'Estado' => (string)$row['Estado'],
            'Descripcion' => (string)$row['Descripcion']
        ];
    }
    if (!empty($estado)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($estado, function ($a, $b) {
            return strcmp($a['Clave'] ?? '', $b['Clave'] ?? '');
        });
        echo json_encode(['success' => true, 'data' => $estado]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron ningun municipio.']);
    }
}
function mostrarPedidosFiltrados($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor, $filtroBusqueda)
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
        $nombreTabla2  = "[{$conexionData['nombreBase']}].[dbo].[FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla3  = "[{$conexionData['nombreBase']}].[dbo].[VEND"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Reescribir la consulta evitando duplicados con `DISTINCT`
        $sql = "SELECT DISTINCT 
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

        $sql .= " AND (";
        if (preg_match('/[a-zA-Z]/', $filtroBusqueda)) {
            $sql .= "LOWER(LTRIM(RTRIM(f.TIP_DOC))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.CVE_DOC))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.CVE_CLPV))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(c.NOMBRE))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.STATUS))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(CONVERT(VARCHAR(10), f.FECHAELAB, 105)))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.CAN_TOT))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(f.IMPORTE))) LIKE ? 
                OR LOWER(LTRIM(RTRIM(v.NOMBRE))) LIKE ? ";
        } else {
            $sql .= "f.TIP_DOC LIKE ? 
                OR f.CVE_DOC LIKE ? 
                OR f.CVE_CLPV LIKE ? 
                OR c.NOMBRE LIKE ? 
                OR f.STATUS LIKE ? 
                OR CONVERT(VARCHAR(10), f.FECHAELAB, 105) LIKE ? 
                OR f.CAN_TOT LIKE ? 
                OR f.IMPORTE LIKE ? 
                OR v.NOMBRE LIKE ? ";
        }
        $sql .= ")";
        $likeFilter = '%' . $filtroBusqueda . '%';
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;
        $params[] = $likeFilter;

        // Agregar filtros de fecha
        if ($filtroFecha == 'Hoy') {
            $sql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE) ";
        } elseif ($filtroFecha == 'Mes') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE()) ";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $sql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE())) ";
        }


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
        if ($estadoPedido == "Activos" || $estadoPedido == "Vendidos") {
            $countSql .= "WHERE f.STATUS IN ('E','O')";
        } else {
            $countSql .= "WHERE f.STATUS IN ('C')";
        }
        // Agregar filtros de fecha
        if ($filtroFecha == 'Hoy') {
            $countSql .= " AND CAST(f.FECHAELAB AS DATE) = CAST(GETDATE() AS DATE) ";
        } elseif ($filtroFecha == 'Mes') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(GETDATE()) AND YEAR(f.FECHAELAB) = YEAR(GETDATE()) ";
        } elseif ($filtroFecha == 'Mes Anterior') {
            $countSql .= " AND MONTH(f.FECHAELAB) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(f.FECHAELAB) = YEAR(DATEADD(MONTH, -1, GETDATE())) ";
        }
        if ($tipoUsuario === 'ADMINISTRADOR') {
            if ($filtroVendedor !== '') {
                $countSql      .= " AND f.CVE_VEND = ?";
                $params[]  = $filtroVendedor;
            }
        } else {
            // Usuarios no ADMIN sólo ven sus pedidos
            $countSql      .= " AND f.CVE_VEND = ?";
            $params[]  = $claveVendedor;
        }

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
function obtenerEstadoPorClave($claveSeleccionada)
{
    $filePath = "../../Complementos/CAT_ESTADOS.xml";

    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => "El archivo no existe en la ruta: $filePath"]);
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo json_encode(['success' => false, 'message' => "Error al leer el archivo XML en $filePath"]);
        return;
    }

    try {
        $estados = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        return;
    }

    $encontrado = null;
    foreach ($estados->row as $row) {
        if ((string)$row['Clave'] === $claveSeleccionada && (string)$row['Pais'] === 'MEX') {
            $encontrado = [
                'Clave'       => (string)$row['Clave'],
                'Pais'        => (string)$row['Pais'],
                'Descripcion' => (string)$row['Descripcion']
            ];
            break;
        }
    }

    if ($encontrado !== null) {
        echo json_encode(['success' => true, 'data' => $encontrado]);
    } else {
        echo json_encode(['success' => false, 'message' => "No se encontró el estado con clave $claveSeleccionada"]);
    }
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
        $claveSae = $_POST['claveSae'];
        $noEmpresa = $_POST['noEmpresa'];
        $pedidoId = $_POST['pedidoId'];
        $claveCliente = $_POST['claveCliente'];
        $claveCliente = $_POST['claveCliente'];
        $credito = $_POST['credito'] ?? false;
        $conn = $_POST['conn'];
        $conexionData = $_POST['conexionData'];

        $folio = crearFacturacion($conexionData, $pedidoId, $claveSae, $noEmpresa, $claveCliente, $credito, $conn);
        header('Content-Type: application/json');
        //die( json_encode(['success' => true, 'folioFactura1' => $folioFactura]));
        echo json_encode(['success' => true, 'folioFactura1' => $folio]);
        //return $folioUnido;
        break;
    case 2:
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
        mostrarRemisiones($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor);
        break;
    case 3:
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

        mostrarRemisionEspecifica($clave, $conexionData, $claveSae);
        break;
    case 4:
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
            obtenerPartidasRemision($conexionData, $clavePedido);
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida o no definida']);
        }
        break;
    case 5:
        $estadoSeleccionado = $_POST['estado'];
        obtenerMunicipios($estadoSeleccionado);
        break;
    case 6: //Función para mostrar clientes filtrados por barra de busqueda.
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
        $filtroFecha = $_POST['filtroFecha'];
        $estadoPedido = $_POST['estadoPedido'];
        $filtroVendedor = $_POST['filtroVendedor'];
        $filtroBusqueda = $_POST['filtroBusqueda'];
        mostrarPedidosFiltrados($conexionData, $filtroFecha, $estadoPedido, $filtroVendedor, $filtroBusqueda);
        break;
    case 7:
        $estadoSeleccionado = $_POST['estadoSeleccionado'];
        obtenerEstadoPorClave($estadoSeleccionado);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        break;
}