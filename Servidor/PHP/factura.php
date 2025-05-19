<?php
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php'; // Archivo de configuración de Firebase
include 'reportes.php';
session_start();


function obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae, $noEmpresa)
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
function insertarBita($conexionData, $remision, $claveSae, $folioFactura)
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

    // ✅ 4. Formatear las observaciones
    $observaciones = "No.[$folioFactura] $" . number_format($totalPedido, 2);

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
        2
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
    sqlsrv_close($conn);
    return $cveBita;
    /*echo json_encode([
        'success' => true,
        'message' => "BITAXX insertado correctamente con CVE_BITA $cveBita y remisión $folioSiguiente"
    ]);*/
}
function insertarFactf($conexionData, $remision, $folioFactura, $CVE_BITA, $claveSae, $DAT_MOSTR)
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

    $remision = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remision = str_pad($remision, 20, ' ', STR_PAD_LEFT);


    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

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
    $tipDocE = 'F';
    $docAnt = $remision;
    $tipDocAnt = 'R';

    // ✅ 4. Insertar en FACTRXX
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
        $cveDoc,
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
        $pedido['ESCFD'],
        $pedido['AUTORIZA'],
        $pedido['SERIE'],
        $folioFactura,
        $pedido['AUTOANIO'],
        $pedido['DAT_ENVIO'],
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
        $pedido['TIP_FAC'],
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
    sqlsrv_close($conn);
}
function insertarFactf_Clib($conexionData, $folioFactura, $claveSae)
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
    $tablaFactrClib = "[{$conexionData['nombreBase']}].[dbo].[FACTF_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $claveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);


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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTR_CLIBXX insertado correctamente con CVE_DOC $claveDoc"
    ]);*/
}
function obtenerFolio($conexionData, $claveSae)
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

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta SQL para obtener el siguiente folio
    $sql = "SELECT (ULT_DOC + 1) AS FolioSiguiente FROM $nombreTabla WHERE TIP_DOC = 'F' AND SERIE = 'STAND.'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener el siguiente folio
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $folioSiguiente = $row ? $row['FolioSiguiente'] : null;

    actualizarFolio($conexionData, $claveSae);
    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    // Retornar el folio siguiente
    return $folioSiguiente;
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
            WHERE TIP_DOC = 'F' AND SERIE = 'STAND.'";

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
function obtenerRemision($conexionData, $pedidoId, $claveSae)
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
    sqlsrv_close($conn);
    //echo json_encode(['success' => true, 'folio remision' => $folio]);
    // Retornar el folio siguiente
    return $folio;
}
function actualizarAfac($conexionData, $remision, $claveSae)
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
    sqlsrv_close($conn);

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
function actualizarAlerta_Usuario1($conexionData, $claveSae)
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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA_USUARIOX1 actualizada correctamente"
    ]);*/
}
function actualizarAlerta_Usuario2($conexionData, $claveSae)
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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTA_USUARIOX2 actualizada correctamente"
    ]);*/
}
function actualizarAlerta1($conexionData, $claveSae)
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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTAXX1 actualizada correctamente"
    ]);*/
}
function actualizarAlerta2($conexionData, $claveSae)
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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "ALERTAXX2 actualizada correctamente"
    ]);*/
}
function crearCxc($conexionData, $claveSae, $remision, $folioFactura)
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

    //Datos de la remision
    $dataRemision = datosRemision($conexionData, $claveSae, $remision);

    $CVE_DOC = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // Preparar los datos para el INSERT
    $cve_clie   = $dataRemision['CVE_CLPV']; // Clave del cliente
    $CVE_CLIE = formatearClaveCliente($cve_clie);
    $refer      = $CVE_DOC; // Puede generarse o venir del formulario
    $num_cpto   = '1';  // Concepto: ajustar según tu lógica de negocio
    $num_cargo  = 1;    // Número de cargo: un valor de ejemplo
    $no_factura = $CVE_DOC; // Número de factura o pedido
    $docto = $CVE_DOC;   // Puede ser un código de documento, si aplica
    //$IMPORTE = 0;
    $STRCVEVEND = $dataRemision['CVE_VEND'];

    $AFEC_COI = 'A';
    $NUM_MONED = 1;
    $TCAMBIO = 1;
    $TIPO_MOV = 'A'; //Aqui

    $IMPORTE = $dataRemision['IMPORTE'];

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

    sqlsrv_close($conn);

    //echo json_encode(['success' => true, 'no_factura' => $no_factura]); 

    return [
        'factura' => $no_factura,
        'referencia' => $refer,
        'IMPORTE' => $IMPORTE,
        'STRCVEVEND' => $STRCVEVEND,
        'CVE_CLIE' => $CVE_CLIE
    ];
}
function pagarCxc($conexionData, $claveSae, $datosCxC, $folioFactura)
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

    $CVE_DOC = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // Preparar los datos para el INSERT
    $cve_clie   = $datosCxC['CVE_CLIE']; // Clave del cliente
    $CVE_CLIE = formatearClaveCliente($cve_clie);
    $refer      = $CVE_DOC; // Puede generarse o venir del formulario
    $num_cpto   = '22';  // Concepto: ajustar según tu lógica de negocio
    $num_cargo  = 1;    // Número de cargo: un valor de ejemplo
    $no_factura = $CVE_DOC; // Número de factura o pedido
    $docto = $CVE_DOC;   // Puede ser un código de documento, si aplica

    $STRCVEVEND = $datosCxC['STRCVEVEND'];

    $AFEC_COI = 'A';
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
    echo json_encode(['success' => true, 'message' => 'CxC creada y pagada.']);
    return;
}
function insertarDoctoSig($conexionData, $remision, $folioFactura, $claveSae)
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

    // ✅ Formatear los IDs para que sean de 10 caracteres con espacios a la izquierda
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

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
    sqlsrv_close($conn);

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
function actualizarFactr($conexionData, $remision, $folioFactura, $claveSae, $pedidoId)
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

    // Tablas dinámicas
    $tablaFactr = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Formatear los valores para SQL Server
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    $cveDocFactura = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    $cveDocFactura = str_pad($cveDocFactura, 10, ' ', STR_PAD_LEFT);

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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTPXX1 actualizado correctamente para el pedido $pedidoId con remision $cveDocFactura"
    ]);*/
}
function actualizarFactr2($conexionData, $remision, $claveSae, $pedidoId)
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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTPXX2 actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function actualizarFactr3($conexionData, $remision, $claveSae, $pedidoId)
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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "FACTPXX3 actualizado correctamente para el pedido $pedidoId"
    ]);*/
}
function insertarPar_Factr($conexionData, $remision, $folioFactura, $claveSae)
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

    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);

    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

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
                           IMP6APLA, IMP7APLA, IMP8APLA, TOTIMP5, TOTIMP6, TOTIMP7, TOTIMP8 
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

    // ✅ 3. Obtener el `NUM_MOV` de `MINVEXX`
    $sqlNumMov = "SELECT ISNULL(MAX(NUM_MOV), 0) AS NUM_MOV FROM $tablaMovimientos";
    $stmtNumMov = sqlsrv_query($conn, $sqlNumMov);

    if ($stmtNumMov === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener NUM_MOV desde MINVEXX',
            'errors' => sqlsrv_errors()
        ]);
        die();
    }

    $numMovData = sqlsrv_fetch_array($stmtNumMov, SQLSRV_FETCH_ASSOC);
    $numMov = $numMovData['NUM_MOV'];

    // Fecha de sincronización
    $fechaSinc = date('Y-m-d H:i:s');

    // ✅ 4. Insertar cada partida en `PAR_FACTRXX`
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
            TOTIMP6, TOTIMP7, TOTIMP8)
        VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, '',
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?)";

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
            $numMov,
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
            $row['TOTIMP8']
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
        $numMov++;
    }
    sqlsrv_close($conn);
    //echo json_encode(['success' => true, 'folioFactura' => $folioFactura]);
}
function insertarPar_Factf_Clib($conexionData, $remision, $folioFactura, $claveSae)
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
    $remisionId = str_pad($remision, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $remisionId = str_pad($remisionId, 20, ' ', STR_PAD_LEFT);
    // Tablas dinámicas
    $tablaPartidasRemisiones = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaParFactfClib = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $cveDoc = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "PAR_FACTR_CLIB01 insertado correctamente con CVE_DOC $cveDoc y $numPartidas partidas"
    ]);*/
}
function actualizarControl1($conexionData, $claveSae)
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
    sqlsrv_close($conn);

    //echo json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarControl2($conexionData, $claveSae)
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
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    //$noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "UPDATE $nombreTabla SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 32";

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
    sqlsrv_close($conn);

    //echo json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarControl3($conexionData, $claveSae)
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
    sqlsrv_close($conn);

    //echo json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarControl4($conexionData, $claveSae)
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
    sqlsrv_close($conn);

    //echo json_encode(['success' => true, 'message' => 'TBLCONTROL01 actualizado correctamente']);
}
function actualizarInclie2($conexionData, $claveSae, $claveCliente)
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
    sqlsrv_close($conn);

    /*echo json_encode([
        'success' => true,
        'message' => "CLIEXX actualizado correctamente "
    ]);*/
}
function actualizarInclie1($conexionData, $claveSae, $claveCliente)
{
    // 1) Conectar
    $serverName   = $conexionData['host'];
    $connectionInfo = [
        "Database"            => $conexionData['nombreBase'],
        "UID"                 => $conexionData['usuario'],
        "PWD"                 => $conexionData['password'],
        "CharacterSet"        => "UTF-8",
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
    sqlsrv_close($conn);

    // 8) (Opcional) éxito
    /*echo json_encode([
        'success' => true,
        'message' => 'CLIE actualizado correctamente'
    ]);*/
}
function insertatInfoClie($conexionData, $claveSae, $claveCliente)
{
    $serverName   = $conexionData['host'];
    $connectionInfo = [
        "Database"  => $conexionData['nombreBase'],
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
    sqlsrv_close($conn);
    //echo json_encode(['success' => true, 'cliente' => $claveCliente]);

    return $nuevo;
}
function actualizarPar_Factf1($conexionData, $claveSae, $remision, array $enlaces)
{
    // 1) Conectar
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database"             => $conexionData['nombreBase'],
        "UID"                  => $conexionData['usuario'],
        "PWD"                  => $conexionData['password'],
        "CharacterSet"         => "UTF-8",
        "TrustServerCertificate" => true,
    ]);
    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors'  => sqlsrv_errors(),
        ]));
    }

    // 2) Prepara el padding de la remisión igual que en PAR_FACTRxx
    $rem  = str_pad($remision, 10, '0', STR_PAD_LEFT);
    $rem  = str_pad($rem,      20, ' ', STR_PAD_LEFT);

    // 3) Nombre de la tabla PAR_FACTRxx
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
            $rem,
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

    // 6) Cerraremos la conexión y devolvemos éxito
    sqlsrv_close($conn);
    return [
        'success' => true,
        'message' => "Se actualizaron " . count($enlaces) . " partidas con el nuevo E_LTPD.",
    ];
}
function insertarCFDI($conexionData, $claveSae, $folioFactura)
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

    $facturaId = str_pad($folioFactura, 10, '0', STR_PAD_LEFT);
    $facturaId = str_pad($facturaId, 20, ' ', STR_PAD_LEFT);

    $tablaCFDI = "[{$conexionData['nombreBase']}].[dbo].[CFDI" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "INSERT INTO $tablaCFDI
    (TIPO_DOC, CVE_DOC, VERSION, UUID, NO_SERIE, FECHA_CERT, FECHA_CANCELA, XML_DOC, XML_DOC_CANCELA,
    DESGLOCEIMP1, DESGLOCEIMP2, DESGLOCEIMP3, DESGLOCEIMP4)
    values
    (?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?)";
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
function sumarSaldo($conexionData, $claveSae, $pagado){
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
        [SALDO] = [SALDO] + (? * -1)
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
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente para el cliente: $cliente"
    ]);
}
function restarSaldo($conexionData, $claveSae, $pagado){
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
    sqlsrv_close($conn);

    return json_encode([
        'success' => true,
        'message' => "Saldo actualizado correctamente"
    ]);
}
/*******************************************************************************************************/
function validarLotesFactura($conexionData, $claveSae, $remision)
{
    // 1) Conectar
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID"      => $conexionData['usuario'],
        "PWD"      => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

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
    sqlsrv_begin_transaction($conn);
    foreach ($porProducto as $claveArt => $lotesByReg) {
        // reindexamos el array
        $lotes = array_values($lotesByReg);
        $res = insertarEnlaceLTPD($conn, $conexionData, $lotes, $claveSae, $claveArt);
        $todos = array_merge($todos, $res);
    }
    sqlsrv_commit($conn);
    sqlsrv_close($conn);

    return $todos;
}
/****************************************** Funcion Principal ******************************************/
function crearFacturacion($conexionData, $pedidoId, $claveSae, $noEmpresa, $claveCliente, $credito)
{
    global $firebaseProjectId, $firebaseApiKey;

    $folioFactura = obtenerFolio($conexionData, $claveSae);
    $remision = obtenerRemision($conexionData, $pedidoId, $claveSae);
    $CVE_BITA = insertarBita($conexionData, $remision, $claveSae, $folioFactura);

    actualizarAfac($conexionData, $remision, $claveSae);
    //insertarAlerta_Usuario($conexionData, $claveSae); //Verifcar logica
    actualizarAlerta_Usuario1($conexionData, $claveSae);
    actualizarAlerta_Usuario2($conexionData, $claveSae);
    actualizarAlerta1($conexionData, $claveSae);
    actualizarAlerta2($conexionData, $claveSae);

    $datosCxC = crearCxc($conexionData, $claveSae, $remision, $folioFactura); //No manipula saldo
    //sumarSaldo($conexionData, $claveSae, $datosCxC);
    //Pagar solo si elimino anticipo (clientes sin Credito)
    if (!$credito) {
        pagarCxc($conexionData, $claveSae, $datosCxC, $folioFactura);
        //restarSaldo($conexionData, $claveSae, $datosCxC);
    }

    insertarDoctoSig($conexionData, $remision, $folioFactura, $claveSae);

    $DAT_MOSTR = insertatInfoClie($conexionData, $claveSae, $claveCliente); //Error datos: CVE_INFO, POB, CALLE

    insertarFactf($conexionData, $remision, $folioFactura, $CVE_BITA, $claveSae, $DAT_MOSTR);
    insertarFactf_Clib($conexionData, $folioFactura, $claveSae);

    actualizarFactr($conexionData, $remision, $folioFactura, $claveSae, $pedidoId);
    actualizarFactr2($conexionData, $remision, $claveSae, $pedidoId);
    actualizarFactr3($conexionData, $remision, $claveSae, $pedidoId);

    insertarPar_Factr($conexionData, $remision, $folioFactura, $claveSae); //Volver a realizarlo con datos nuevos
    insertarPar_Factf_Clib($conexionData, $remision, $folioFactura, $claveSae);

    $result = validarLotesFactura($conexionData, $claveSae, $remision);

    /*$datos = obtenerDatosPreEnlace($conexionData, $claveSae, $remision);    //No se pudo por falta de datos
    
    foreach ($datos['CVE_ART'] as $producto) {
        $result = insertarEnlaceLTPD($conexionData, $claveSae, $remision, $datos['CVE_ART'], $producto);
    }*/
    //var_dump($result);

    actualizarPar_Factf1($conexionData, $claveSae, $remision, $result);

    actualizarControl1($conexionData, $claveSae);
    actualizarControl2($conexionData, $claveSae);
    actualizarControl3($conexionData, $claveSae);
    actualizarControl4($conexionData, $claveSae);
    actualizarInclie1($conexionData, $claveSae, $claveCliente); //Verificar la logica
    actualizarInclie2($conexionData, $claveSae, $claveCliente);

    insertarCFDI($conexionData, $claveSae, $folioFactura);

    return $folioFactura;
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
        $credito = $_POST['credito'] ?? false;

        //var_dump($credito);
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae, $noEmpresa);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $folioFactura = crearFacturacion($conexionData, $pedidoId, $claveSae, $noEmpresa, $claveCliente, $credito);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'folioFactura1' => $folioFactura]);
        return $folioFactura;
        //return;
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
