<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../fpdf/fpdf.php';
session_start();

// Función para obtener los datos de la empresa desde Firebase
function obtenerDatosEmpresaFire($noEmpresa){
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";

    // Configurar la solicitud HTTP
    $context = stream_context_create([
        'http' => [
            'timeout' => 10 // Tiempo de espera
        ]
    ]);

    // Obtener los datos desde Firebase
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false; // Error en la solicitud
    }

    // Decodificar la respuesta JSON
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false; // No hay documentos
    }

    // Buscar la empresa en Firebase
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
            return [
                'noEmpresa' => $fields['noEmpresa']['integerValue'] ?? null,
                'razonSocial' => $fields['razonSocial']['stringValue'] ?? null,
                'rfc' => $fields['rfc']['stringValue'] ?? null,
                'calle' => $fields['calle']['stringValue'] ?? null,
                'numExterior' => $fields['numExterior']['stringValue'] ?? null,
                'numInterior' => $fields['numInterior']['stringValue'] ?? null,
                'colonia' => $fields['colonia']['stringValue'] ?? null,
                'municipio' => $fields['municipio']['stringValue'] ?? null,
                'estado' => $fields['estado']['stringValue'] ?? null,
                'pais' => $fields['pais']['stringValue'] ?? null,
                'codigoPostal' => $fields['codigoPostal']['stringValue'] ?? null,
                'regimenFiscal' => $fields['regimenFiscal']['stringValue'] ?? null
            ];
        }
    }

    return false; // No se encontró la empresa
}
function obtenerDatosVendedor($clave)
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";

    // Configurar la solicitud HTTP
    $context = stream_context_create([
        'http' => [
            'timeout' => 10
        ]
    ]);

    // Obtener los datos desde Firebase
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }

    // Decodificar la respuesta JSON
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false;
    }

    // Buscar el vendedor por claveUsuario
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        if (isset($fields['claveUsuario']['stringValue']) && trim($fields['claveUsuario']['stringValue']) === trim($clave)) {
            return [
                'nombre' => $fields['nombre']['stringValue'] ?? 'Desconocido',
                'telefono' => $fields['telefono']['stringValue'] ?? 'Sin Telefono',
                'correo' => $fields['correo']['stringValue'] ?? 'Sin Correo'
            ];
        }
    }

    return false;
}
function obtenerDatosClienteReporte($conexionData, $clienteId, $claveSae, $conn){
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a SQL Server', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT NOMBRE, RFC, CALLE, NUMEXT, NUMINT, COLONIA, MUNICIPIO, ESTADO, PAIS, CODIGO, TELEFONO, EMAILPRED, DESCUENTO, REG_FISC
            FROM $nombreTabla WHERE [CLAVE] = ?";

    $params = [$clienteId];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    //sqlsrv_close($conn);

    if (!$clienteData) {
        return null; // Cliente no encontrado
    }

    return [
        'nombre' => trim($clienteData['NOMBRE']) ?? 'Desconocido',
        //'rfc' => trim($clienteData['RFC']) ?? 'N/A',
        'rfc' => $clienteData['RFC'] ?? 'N/A',
        'direccion' => trim($clienteData['CALLE'] . " " . ($clienteData['NUMEXT'] ?? '') . " " . ($clienteData['NUMINT'] ?? '')),
        //'colonia' => trim($clienteData['COLONIA']) ?? 'N/A',
        'colonia' => $clienteData['COLONIA'] ?? 'N/A',
        'ubicacion' => trim($clienteData['MUNICIPIO'] . ", " . $clienteData['ESTADO'] . ", " . $clienteData['PAIS']) ?? 'N/A',
        //'codigoPostal' => trim($clienteData['CODIGO']) ?? 'N/A',
        'codigoPostal' => $clienteData['CODIGO'] ?? 'N/A',
        'telefono' => $clienteData['TELEFONO'] ?? 'N/A',
        'email' => $clienteData['EMAILPRED'] ?? 'N/A',
        'DESCUENTO' => $clienteData['DESCUENTO'] ?? 0,
        'REG_FISC' => $clienteData['REG_FISC'] ?? 0
    ];
}
function obtenerDatosClienteReporteE($conexionData, $clienteId, $claveSae){
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar a SQL Server', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT NOMBRE, RFC, CALLE, NUMEXT, NUMINT, COLONIA, MUNICIPIO, ESTADO, PAIS, CODIGO, TELEFONO, EMAILPRED, DESCUENTO, REG_FISC
            FROM $nombreTabla WHERE [CLAVE] = ?";

    $params = [$clienteId];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    //sqlsrv_close($conn);

    if (!$clienteData) {
        return null; // Cliente no encontrado
    }

    return [
        'nombre' => trim($clienteData['NOMBRE']) ?? 'Desconocido',
        //'rfc' => trim($clienteData['RFC']) ?? 'N/A',
        'rfc' => $clienteData['RFC'] ?? 'N/A',
        'direccion' => trim($clienteData['CALLE'] . " " . ($clienteData['NUMEXT'] ?? '') . " " . ($clienteData['NUMINT'] ?? '')),
        //'colonia' => trim($clienteData['COLONIA']) ?? 'N/A',
        'colonia' => $clienteData['COLONIA'] ?? 'N/A',
        'ubicacion' => trim($clienteData['MUNICIPIO'] . ", " . $clienteData['ESTADO'] . ", " . $clienteData['PAIS']) ?? 'N/A',
        //'codigoPostal' => trim($clienteData['CODIGO']) ?? 'N/A',
        'codigoPostal' => $clienteData['CODIGO'] ?? 'N/A',
        'telefono' => $clienteData['TELEFONO'] ?? 'N/A',
        'email' => $clienteData['EMAILPRED'] ?? 'N/A',
        'DESCUENTO' => $clienteData['DESCUENTO'] ?? 0,
        'REG_FISC' => $clienteData['REG_FISC'] ?? 0
    ];
}
/****************************************************************************************************************/
function obtenerDatosRemisiones($cveDoc, $conexionData, $claveSae)
{
    // Configuración de conexión a SQL Server
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Construcción del nombre de la tabla con clave SAE
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener los datos de la remisión
    $sql = "SELECT CVE_CLPV, CVE_VEND, FECHA_DOC, FOLIO, DES_TOT
            FROM $nombreTabla 
            WHERE CVE_DOC = ?";

    $params = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar la remisión', 'errors' => sqlsrv_errors()]));
    }

    $datosRemision = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);

    if (!$datosRemision) {
        return null; // Si no encuentra la remisión, retorna null
    }

    return [
        'CVE_CLPV' => trim($datosRemision['CVE_CLPV']),
        'CVE_VEND' => trim($datosRemision['CVE_VEND']),
        'FECHA_DOC' => $datosRemision['FECHA_DOC']->format('Y-m-d'),
        'FOLIO' => (float) $datosRemision['FOLIO'],
        'DES_TOT' => (float) $datosRemision['DES_TOT']
    ];
}
function obtenerDatosPartidasRemisiones($cveDoc, $conexionData, $claveSae)
{
    // Configuración de conexión a SQL Server
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Construcción del nombre de la tabla con clave SAE
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener las partidas de la remisión
    $sql = "SELECT CVE_ART, CANT, PREC, TOT_PARTIDA, IMPU1, IMPU2, IMPU3, IMPU4, IMPU5, IMPU6, IMPU7, IMPU8, DESC1, DESC2
            FROM $nombreTabla 
            WHERE CVE_DOC = ?";

    $params = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar las partidas de la remisión', 'errors' => sqlsrv_errors()]));
    }

    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = [
            'CVE_ART' => trim($row['CVE_ART']),
            'CANT' => (float) $row['CANT'],
            'PREC' => (float) $row['PREC'],
            'TOT_PARTIDA' => (float) $row['TOT_PARTIDA'],
            'IMPU1' => (float) $row['IMPU1'],
            'IMPU2' => (float) $row['IMPU2'],
            'IMPU3' => (float) $row['IMPU3'],
            'IMPU4' => (float) $row['IMPU4'],
            'IMPU5' => (float) $row['IMPU5'],
            'IMPU6' => (float) $row['IMPU6'],
            'IMPU7' => (float) $row['IMPU7'],
            'IMPU8' => (float) $row['IMPU8'],
            'DESC1' => (float) $row['DESC1'],
            'DESC2' => (float) $row['DESC2']
        ];
    }

    sqlsrv_close($conn);

    return $partidas;
}
function obtenerDescripcionProductoRemision($CVE_ART, $conexionData, $claveSae)
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
/****************************************************************************************************************/
function obtenerDatosPedido($cveDoc, $conexionData, $claveSae)
{
    // Configuración de conexión a SQL Server
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar a la base de datos',
            'errors'  => sqlsrv_errors()
        ]));
    }

    // Construcción del nombre de la tabla con clave SAE
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP"
        . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta: convertimos FECHA_DOC a dd-mm-yyyy (estilo 105)
    $sql = "
        SELECT 
          CVE_CLPV,
          CVE_VEND,
          CONVERT(VARCHAR(10), FECHA_DOC, 105) AS FECHA_DOC,
          FOLIO,
          DES_TOT
        FROM $nombreTabla
        WHERE CVE_DOC = ?
    ";

    $params = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar la remisión',
            'errors'  => sqlsrv_errors()
        ]));
    }

    $datosPedidoAuto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);

    if (!$datosPedidoAuto) {
        return null; // Si no encuentra la remisión
    }

    return [
        'CVE_CLPV'  => trim($datosPedidoAuto['CVE_CLPV']),
        'CVE_VEND'  => trim($datosPedidoAuto['CVE_VEND']),
        // FECHA_DOC ya viene en formato "dd-mm-yyyy"
        'FECHA_DOC' => $datosPedidoAuto['FECHA_DOC'],  
        'FOLIO'     => (float) $datosPedidoAuto['FOLIO'],
        'DES_TOT'   => (float) $datosPedidoAuto['DES_TOT']
    ];
}
function obtenerDatosPartidasPedido($cveDoc, $conexionData, $claveSae)
{
    // Configuración de conexión a SQL Server
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Construcción del nombre de la tabla con clave SAE
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener las partidas de la remisión
    $sql = "SELECT CVE_ART, CANT, PREC, TOT_PARTIDA, IMPU1, IMPU2, IMPU3, IMPU4, IMPU5, IMPU6, IMPU7, IMPU8, DESC1, DESC2, UNI_VENTA, CVE_UNIDAD
            FROM $nombreTabla 
            WHERE CVE_DOC = ?";

    $params = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar las partidas de la remisión', 'errors' => sqlsrv_errors()]));
    }

    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = [
            'CVE_ART' => trim($row['CVE_ART']),
            'CANT' => (float) $row['CANT'],
            'PREC' => (float) $row['PREC'],
            'TOT_PARTIDA' => (float) $row['TOT_PARTIDA'],
            'IMPU1' => (float) $row['IMPU1'],
            'IMPU2' => (float) $row['IMPU2'],
            'IMPU3' => (float) $row['IMPU3'],
            'IMPU4' => (float) $row['IMPU4'],
            'IMPU5' => (float) $row['IMPU5'],
            'IMPU6' => (float) $row['IMPU6'],
            'IMPU7' => (float) $row['IMPU7'],
            'IMPU8' => (float) $row['IMPU8'],
            'DESC1' => (float) $row['DESC1'],
            'DESC2' => (float) $row['DESC2'],
            'UNI_VENTA' => $row['UNI_VENTA'],
            'CVE_UNIDAD' => $row['CVE_UNIDAD']
        ];
    }

    sqlsrv_close($conn);

    return $partidas;
}
function obtenerDescripcionProductoPedidoAutoriza($CVE_ART, $conexionData, $claveSae, $conn){
    if (!is_resource($conn)) {
        die(json_encode([
            'success' => false,
            'message' => 'La conexión no es un recurso válido.'
        ]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT DESCR, CVE_PRODSERV FROM $nombreTabla WHERE CVE_ART = ?";
    $stmt = sqlsrv_query($conn, $sql, [$CVE_ART]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $descripcion = $row ? $row['DESCR'] : '';
    $CVE_PRODSERV = $row ? $row['CVE_PRODSERV'] : '';

    sqlsrv_free_stmt($stmt);

    return [
        "DESCR" => $descripcion,
        "CVE_PRODSERV" => $CVE_PRODSERV
    ];
}
function obtenerDescripcionProductoPedidoAutorizaE($CVE_ART, $conexionData, $claveSae){
    // Configuración de conexión a SQL Server
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

    if (!is_resource($conn)) {
        die(json_encode([
            'success' => false,
            'message' => 'La conexión no es un recurso válido.'
        ]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT DESCR, CVE_PRODSERV FROM $nombreTabla WHERE CVE_ART = ?";
    $stmt = sqlsrv_query($conn, $sql, [$CVE_ART]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $descripcion = $row ? $row['DESCR'] : '';
    $CVE_PRODSERV = $row ? $row['CVE_PRODSERV'] : '';

    sqlsrv_free_stmt($stmt);

    return [
        "DESCR" => $descripcion,
        "CVE_PRODSERV" => $CVE_PRODSERV
    ];
}
/****************************************************************************************************************/
function obtenerDatosFactura($cveDoc, $conexionData, $claveSae)
{
    // Configuración de conexión a SQL Server
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

    if ($conn === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar a la base de datos',
            'errors'  => sqlsrv_errors()
        ]));
    }

    // Construcción del nombre de la tabla con clave SAE
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTF"
        . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta: convertimos FECHA_DOC a dd-mm-yyyy (estilo 105)
    $sql = "
        SELECT 
          CVE_CLPV,
          CVE_VEND,
          CONVERT(VARCHAR(10), FECHA_DOC, 105) AS FECHA_DOC,
          FOLIO,
          DES_TOT
        FROM $nombreTabla
        WHERE CVE_DOC = ?
    ";

    $params = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar la remisión',
            'errors'  => sqlsrv_errors()
        ]));
    }

    $datosPedidoAuto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);

    if (!$datosPedidoAuto) {
        return null; // Si no encuentra la remisión
    }

    return [
        'CVE_CLPV'  => trim($datosPedidoAuto['CVE_CLPV']),
        'CVE_VEND'  => trim($datosPedidoAuto['CVE_VEND']),
        // FECHA_DOC ya viene en formato "dd-mm-yyyy"
        'FECHA_DOC' => $datosPedidoAuto['FECHA_DOC'],  
        'FOLIO'     => (float) $datosPedidoAuto['FOLIO'],
        'DES_TOT'   => (float) $datosPedidoAuto['DES_TOT']
    ];
}
function obtenerDatosPartidasFactura($cveDoc, $conexionData, $claveSae)
{
    // Configuración de conexión a SQL Server
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Construcción del nombre de la tabla con clave SAE
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener las partidas de la remisión
    $sql = "SELECT CVE_ART, CANT, PREC, TOT_PARTIDA, IMPU1, IMPU2, IMPU3, IMPU4, IMPU5, IMPU6, IMPU7, IMPU8, DESC1, DESC2, UNI_VENTA, CVE_UNIDAD
            FROM $nombreTabla 
            WHERE CVE_DOC = ?";

    $params = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar las partidas de la remisión', 'errors' => sqlsrv_errors()]));
    }

    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = [
            'CVE_ART' => trim($row['CVE_ART']),
            'CANT' => (float) $row['CANT'],
            'PREC' => (float) $row['PREC'],
            'TOT_PARTIDA' => (float) $row['TOT_PARTIDA'],
            'IMPU1' => (float) $row['IMPU1'],
            'IMPU2' => (float) $row['IMPU2'],
            'IMPU3' => (float) $row['IMPU3'],
            'IMPU4' => (float) $row['IMPU4'],
            'IMPU5' => (float) $row['IMPU5'],
            'IMPU6' => (float) $row['IMPU6'],
            'IMPU7' => (float) $row['IMPU7'],
            'IMPU8' => (float) $row['IMPU8'],
            'DESC1' => (float) $row['DESC1'],
            'DESC2' => (float) $row['DESC2'],
            'UNI_VENTA' => $row['UNI_VENTA'],
            'CVE_UNIDAD' => $row['CVE_UNIDAD']
        ];
    }

    sqlsrv_close($conn);

    return $partidas;
}
/****************************************************************************************************************/

class PDFPedido extends FPDF
{
    private $datosEmpresaFire;
    private $datosClienteReporte;
    private $datosVendedor;
    private $formularioData;
    private $ordenCompra;
    private $emailPred;
    private $FOLIO;

    function __construct($datosEmpresaFire, $datosClienteReporte, $datosVendedor, $formularioData, $ordenCompra, $emailPred, $FOLIO)
    {
        parent::__construct();
        $this->datosEmpresaFire = $datosEmpresaFire;
        $this->datosClienteReporte = $datosClienteReporte;
        $this->datosVendedor = $datosVendedor;
        $this->formularioData = $formularioData;
        $this->ordenCompra = $ordenCompra;
        $this->emailPred = $emailPred;
        $this->FOLIO = $FOLIO;
    }

    function Header()
    {
        // Información del vendedor
        if ($this->datosVendedor) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(32, 100, 210);
            $this->Cell(120, 10, "Vendedor: " . $this->datosVendedor['nombre'], 0, 0, 'L');
            $this->Ln(5);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(120, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $this->datosVendedor['telefono']), 0, 0, 'L');
            $this->Ln(5);
            $this->Cell(120, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->datosVendedor['correo']), 0, 0, 'L');
        }
        // Logo de la empresa
        $this->Image('../../Cliente/SRC/imagen.png', 145, 1, 0, 30); //, '', '', 'PNG'
        $this->Ln(10);

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(32, 100, 210);
        $this->Cell(120, 10, "PEDIDO", 0, 0, 'L');
        $this->Ln(10);

        // Información del Cliente y Empresa en la misma línea
        if ($this->datosClienteReporte && $this->datosEmpresaFire) {
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(32, 100, 210);

            // Cliente - A la Izquierda
            $this->SetX(10); // Inicia desde la izquierda
            $this->Cell(90, 10, iconv("UTF-8", "ISO-8859-1", $this->datosClienteReporte['nombre']), 0, 0, 'L');

            // Empresa - A la Derecha
            $this->SetX(140); // Posiciona la empresa en la parte derecha
            $this->Cell(100, 10, iconv("UTF-8", "ISO-8859-1", strtoupper($this->datosEmpresaFire['razonSocial'])), 0, 0, 'L');

            $this->Ln(10);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);

            // RFC - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosClienteReporte['rfc']), 0, 0, 'L');

            // RFC - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosEmpresaFire['rfc']), 0, 0, 'L');

            $this->Ln(5);

            // Dirección - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosClienteReporte['direccion'] . ", " . $this->datosClienteReporte['colonia']), 0, 0, 'L');

            // Dirección - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosEmpresaFire['calle'] . " " . $this->datosEmpresaFire['numExterior'] . ", " . $this->datosEmpresaFire['colonia']), 0, 0, 'L');

            $this->Ln(5);

            // Ubicación - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", $this->datosClienteReporte['ubicacion']), 0, 0, 'L');

            // Ubicación - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", $this->datosEmpresaFire['municipio'] . ", " . $this->datosEmpresaFire['estado'] . ", " . $this->datosEmpresaFire['pais']), 0, 0, 'L');

            $this->Ln(5);

            // Código Postal - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosClienteReporte['codigoPostal']), 0, 0, 'L');

            // Código Postal - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosEmpresaFire['codigoPostal']), 0, 0, 'L');

            $this->Ln(5);

            // Teléfono - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $this->datosClienteReporte['telefono']), 0, 0, 'L');

            // Empresa: No tiene teléfono, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Pedido Nro: " . $this->FOLIO), 0, 0, 'L');

            $this->Ln(5);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            // Email - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->emailPred), 0, 0, 'L');

            // Empresa: No tiene email, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Fecha de emisión: " . $this->formularioData['diaAlta']), 0, 1, 'L');
            $this->Ln(5);

            if(isset($this->ordenCompra)){
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Oden Compra: " . $this->ordenCompra), 0, 0, 'L');
            }
            $this->Ln(15);
        }

        // **Encabezado de la tabla de partidas**
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(23, 83, 201);
        $this->SetDrawColor(23, 83, 201);
        $this->SetTextColor(255, 255, 255);
        /*$this->Cell(20, 8, "Clave", 1, 0, 'C', true);
        $this->Cell(70, 8, iconv("UTF-8", "ISO-8859-1", "Descripción"), 1, 0, 'C', true);
        $this->Cell(15, 8, "Cant.", 1, 0, 'C', true);
        $this->Cell(20, 8, "Precio", 1, 0, 'C', true);
        $this->Cell(20, 8, "Descuento", 1, 0, 'C', true);
        $this->Cell(20, 8, "Impuestos", 1, 0, 'C', true);
        $this->Cell(30, 8, "Subtotal", 1, 1, 'C', true);*/
        $this->Cell(10, 8, "Cant.", 1, 0, 'C', true);
        $this->Cell(28, 8, "Unidad", 1, 0, 'C', true);
        $this->Cell(15, 8, "Clave SAT", 1, 0, 'C', true); //c
        $this->Cell(15, 8, "Clave", 1, 0, 'C', true);
        $this->Cell(65, 8, iconv("UTF-8", "ISO-8859-1", "Descripción"), 1, 0, 'C', true);
        $this->Cell(18, 8, "IVA", 1, 0, 'C', true);
        $this->Cell(18, 8, "Precio", 1, 0, 'C', true);
        $this->Cell(22, 8, "Subtotal", 1, 1, 'C', true);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}
class PDFRemision extends FPDF
{
    private $datosEmpresaFireRemision;
    private $datosClienteRemision;
    private $datosVendedorRemision;
    private $datosRemisiones;
    private $emailPred;

    function __construct($datosEmpresaFireRemision, $datosClienteRemision, $datosVendedorRemision, $datosRemisiones, $emailPred)
    {
        parent::__construct();
        $this->datosEmpresaFireRemision = $datosEmpresaFireRemision;
        $this->datosClienteRemision = $datosClienteRemision;
        $this->datosVendedorRemision = $datosVendedorRemision;
        $this->datosRemisiones = $datosRemisiones;
        $this->emailPred = $emailPred;
    }

    function Header()
    {
        // Información del vendedor
        if ($this->datosVendedorRemision) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(32, 100, 210);
            $this->Cell(120, 10, "Vendedor: " . $this->datosVendedorRemision['nombre'], 0, 0, 'L');
            $this->Ln(5);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(120, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $this->datosVendedorRemision['telefono']), 0, 0, 'L');
            $this->Ln(5);
            $this->Cell(120, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->datosVendedorRemision['correo']), 0, 0, 'L');
        }
        // Logo de la empresa
        $this->Image('../../Cliente/SRC/imagen.png', 145, 1, 0, 30); //, '', '', 'PNG'
        $this->Ln(10);

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(32, 100, 210);
        $this->Cell(120, 10, "REMISION", 0, 0, 'L');
        $this->Ln(10);

        // Información del Cliente y Empresa en la misma línea
        if ($this->datosClienteRemision && $this->datosEmpresaFireRemision) {
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(32, 100, 210);

            // Cliente - A la Izquierda
            $this->SetX(10); // Inicia desde la izquierda
            $this->Cell(90, 10, iconv("UTF-8", "ISO-8859-1", $this->datosClienteRemision['nombre']), 0, 0, 'L');

            // Empresa - A la Derecha
            $this->SetX(140); // Posiciona la empresa en la parte derecha
            $this->Cell(100, 10, iconv("UTF-8", "ISO-8859-1", strtoupper($this->datosEmpresaFireRemision['razonSocial'])), 0, 0, 'L');

            $this->Ln(10);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);

            // RFC - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosClienteRemision['rfc']), 0, 0, 'L');

            // RFC - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosEmpresaFireRemision['rfc']), 0, 0, 'L');

            $this->Ln(5);

            // Dirección - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosClienteRemision['direccion'] . ", " . $this->datosClienteRemision['colonia']), 0, 0, 'L');

            // Dirección - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosEmpresaFireRemision['calle'] . " " . $this->datosEmpresaFireRemision['numExterior'] . ", " . $this->datosEmpresaFireRemision['colonia']), 0, 0, 'L');

            $this->Ln(5);

            // Ubicación - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", $this->datosClienteRemision['ubicacion']), 0, 0, 'L');

            // Ubicación - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", $this->datosEmpresaFireRemision['municipio'] . ", " . $this->datosEmpresaFireRemision['estado'] . ", " . $this->datosEmpresaFireRemision['pais']), 0, 0, 'L');

            $this->Ln(5);

            // Código Postal - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosClienteRemision['codigoPostal']), 0, 0, 'L');

            // Código Postal - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosEmpresaFireRemision['codigoPostal']), 0, 0, 'L');

            $this->Ln(5);

            // Teléfono - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $this->datosClienteRemision['telefono']), 0, 0, 'L');

            // Empresa: No tiene teléfono, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Remision Nro: " . $this->datosRemisiones['FOLIO']), 0, 0, 'L');

            $this->Ln(5);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            // Email - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->emailPred), 0, 0, 'L');

            // Empresa: No tiene email, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Fecha de emisión: " . $this->datosRemisiones['FECHA_DOC']), 0, 0, 'L');
            $this->Ln(15);
        }

        // **Encabezado de la tabla de partidas**
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(23, 83, 201);
        $this->SetDrawColor(23, 83, 201);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(20, 8, "Clave", 1, 0, 'C', true);
        $this->Cell(70, 8, iconv("UTF-8", "ISO-8859-1", "Descripción"), 1, 0, 'C', true);
        $this->Cell(15, 8, "Cant.", 1, 0, 'C', true);
        $this->Cell(20, 8, "Precio", 1, 0, 'C', true);
        $this->Cell(20, 8, "Descuento", 1, 0, 'C', true);
        $this->Cell(20, 8, "Impuestos", 1, 0, 'C', true);
        $this->Cell(30, 8, "Subtotal", 1, 1, 'C', true);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}
class PDFPedidoAutoriza extends FPDF
{
    private $datosEmpresaPedidoAutoriza;
    private $datosClientePedidoAutoriza;
    private $datosVendedorPedidoAutoriza;
    private $datosPedidoAutoriza;
    private $emailPred;

    function __construct($datosEmpresaPedidoAutoriza, $datosClientePedidoAutoriza, $datosVendedorPedidoAutoriza, $datosPedidoAutoriza, $emailPred)
    {
        parent::__construct();
        $this->datosEmpresaPedidoAutoriza = $datosEmpresaPedidoAutoriza;
        $this->datosClientePedidoAutoriza = $datosClientePedidoAutoriza;
        $this->datosVendedorPedidoAutoriza = $datosVendedorPedidoAutoriza;
        $this->datosPedidoAutoriza = $datosPedidoAutoriza;
        $this->emailPred = $emailPred;
    }
    function Header()
    {
        // Información del vendedor
        if ($this->datosVendedorPedidoAutoriza) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(32, 100, 210);
            $this->Cell(120, 10, "Vendedor: " . $this->datosVendedorPedidoAutoriza['nombre'], 0, 0, 'L');
            $this->Ln(5);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(120, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $this->datosVendedorPedidoAutoriza['telefono']), 0, 0, 'L');
            $this->Ln(5);
            $this->Cell(120, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->datosVendedorPedidoAutoriza['correo']), 0, 0, 'L');
        }
        // Logo de la empresa
        $this->Image('../../Cliente/SRC/imagen.png', 145, 1, 0, 30); //, '', '', 'PNG'
        $this->Ln(10);

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(32, 100, 210);
        $this->Cell(120, 10, "PEDIDO", 0, 0, 'L');
        $this->Ln(10);

        // Información del Cliente y Empresa en la misma línea
        if ($this->datosClientePedidoAutoriza && $this->datosEmpresaPedidoAutoriza) {
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(32, 100, 210);

            // Cliente - A la Izquierda
            $this->SetX(10); // Inicia desde la izquierda
            $this->Cell(90, 10, iconv("UTF-8", "ISO-8859-1", $this->datosClientePedidoAutoriza['nombre']), 0, 0, 'L');

            // Empresa - A la Derecha
            $this->SetX(140); // Posiciona la empresa en la parte derecha
            $this->Cell(100, 10, iconv("UTF-8", "ISO-8859-1", strtoupper($this->datosEmpresaPedidoAutoriza['razonSocial'])), 0, 0, 'L');

            $this->Ln(10);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);

            // RFC - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosClientePedidoAutoriza['rfc']), 0, 0, 'L');

            // RFC - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosEmpresaPedidoAutoriza['rfc']), 0, 0, 'L');

            $this->Ln(5);

            // Dirección - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosClientePedidoAutoriza['direccion'] . ", " . $this->datosClientePedidoAutoriza['colonia']), 0, 0, 'L');

            // Dirección - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosEmpresaPedidoAutoriza['calle'] . " " . $this->datosEmpresaPedidoAutoriza['numExterior'] . ", " . $this->datosEmpresaPedidoAutoriza['colonia']), 0, 0, 'L');

            $this->Ln(5);

            // Ubicación - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", $this->datosClientePedidoAutoriza['ubicacion']), 0, 0, 'L');

            // Ubicación - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", $this->datosEmpresaPedidoAutoriza['municipio'] . ", " . $this->datosEmpresaPedidoAutoriza['estado'] . ", " . $this->datosEmpresaPedidoAutoriza['pais']), 0, 0, 'L');

            $this->Ln(5);

            // Código Postal - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosClientePedidoAutoriza['codigoPostal']), 0, 0, 'L');

            // Código Postal - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosEmpresaPedidoAutoriza['codigoPostal']), 0, 0, 'L');

            $this->Ln(5);

            // Teléfono - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $this->datosClientePedidoAutoriza['telefono']), 0, 0, 'L');

            // Empresa: No tiene teléfono, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Pedido Nro: " . $this->datosPedidoAutoriza['FOLIO']), 0, 0, 'L');

            $this->Ln(5);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            // Email - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->emailPred), 0, 0, 'L');

            // Empresa: No tiene email, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Fecha de emisión: " . $this->datosPedidoAutoriza['FECHA_DOC']), 0, 0, 'L');
            $this->Ln(15);
        }

        // **Encabezado de la tabla de partidas**
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(23, 83, 201);
        $this->SetDrawColor(23, 83, 201);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(10, 8, "Cant.", 1, 0, 'C', true);
        $this->Cell(28, 8, "Unidad", 1, 0, 'C', true);
        $this->Cell(15, 8, "Clave SAT", 1, 0, 'C', true); 
        $this->Cell(15, 8, "Clave", 1, 0, 'C', true);
        $this->Cell(60, 8, iconv("UTF-8", "ISO-8859-1", "Descripción"), 1, 0, 'C', true);
        $this->Cell(18, 8, "IVA", 1, 0, 'C', true);
        $this->Cell(18, 8, "Precio", 1, 0, 'C', true);
        $this->Cell(22, 8, "Subtotal", 1, 1, 'C', true);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}
class PDFFactura extends FPDF
{
    private function clipText($text, $maxWidth)
    {
        while ($this->GetStringWidth($text) > $maxWidth && strlen($text) > 0) {
            $text = substr($text, 0, -1);
        }
        return $text;
    }

    function imprimirDatosFiscales(
        $noCertificado,
        $sello,
        $MetodoPago,
        $FormaPago,
        $LugarExpedicion,
        $TipoDeComprobante,
        $Moneda,
        $SelloSAT,
        $SelloCFD,
        $RfcProvCertif,
        $qrFile,
        $fecha
    ) {
        // Nos posicionamos en una ubicación fija desde el final de la página (por ejemplo, 50 mm desde el fondo)
        //$this->SetY(-150);
        // Opcional: dibujar una línea para separar la sección fiscal
        $this->SetDrawColor(150, 150, 150);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);

        $cellWidth = 110;
        $yInicio = $this->GetY();
        // Filas con los datos fiscales
        $this->SetFont('Arial', '', 7);

        $this->Cell(30, 5, "No. Certificado", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($noCertificado, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "Sello", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($sello, $cellWidth), 1, 1, 'L');


        $this->Cell(30, 5, "Metodo de Pago", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($MetodoPago, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "Forma de Pago", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($FormaPago, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "Lugar Expedicion", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($LugarExpedicion, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "Tipo Comprobante", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($TipoDeComprobante, $cellWidth), 1, 1, 'L');


        $this->Cell(30, 5, "Moneda", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($Moneda, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "SelloSAT (Timbre)", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($SelloSAT, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "SelloCFD", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($SelloCFD, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "RfcProvCertif", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($RfcProvCertif, $cellWidth), 1, 1, 'L');

        $this->Cell(30, 5, "Fecha Timbrado", 1, 0, 'L');
        $this->Cell($cellWidth, 5, $this->clipText($fecha, $cellWidth), 1, 1, 'L');

        // Si se proporciona el QR y existe, se inserta en el encabezado, por ejemplo a la izquierda o a la derecha del texto
        if ($qrFile && file_exists($qrFile)) {
            // Ejemplo: ubicamos el QR en la esquina superior derecha, antes del logo de la empresa
            $this->Image($qrFile, 155, $yInicio, 50, 50);
        }
    }

    private $datosEmpresaPedidoAutoriza;
    private $datosClientePedidoAutoriza;
    private $datosVendedorPedidoAutoriza;
    private $datosPedidoAutoriza;
    private $emailPred;
    private $regimen;
    private $fechaEmision;

    function __construct(
        $datosEmpresaPedidoAutoriza,
        $datosClientePedidoAutoriza,
        $datosVendedorPedidoAutoriza,
        $datosPedidoAutoriza,
        $emailPred,
        $regimen,
        $fechaEmision
    ) {
        parent::__construct();
        $this->datosEmpresaPedidoAutoriza = $datosEmpresaPedidoAutoriza;
        $this->datosClientePedidoAutoriza = $datosClientePedidoAutoriza;
        $this->datosVendedorPedidoAutoriza = $datosVendedorPedidoAutoriza;
        $this->datosPedidoAutoriza = $datosPedidoAutoriza;
        $this->emailPred = $emailPred;
        $this->regimen = $regimen;
        $this->fechaEmision = $fechaEmision;
    }
    function Header()
    {
        if ($this->PageNo() == 1) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(32, 100, 210);
            $this->Cell(20, 12, iconv("UTF-8", "ISO-8859-1", "Factura# : "), 0, 0, 'L');
            $this->SetTextColor(39, 39, 51);
            $this->Cell(20, 12, iconv("UTF-8", "ISO-8859-1", $this->datosPedidoAutoriza['FOLIO']), 0, 0, 'L');
            
            $this->Ln(4);

            $this->SetTextColor(32, 100, 210);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(40, 12, iconv("UTF-8", "ISO-8859-1", "Fecha de emisión: "), 0, 0, 'L');
            $this->SetTextColor(39, 39, 51);
            $this->Cell(20, 12, iconv("UTF-8", "ISO-8859-1", $this->fechaEmision), 0, 0, 'L');
            $this->Ln(4);

            // Logo de la empresa
            $this->Image('../../Cliente/SRC/imagen.png', 145, 1, 0, 30); //, '', '', 'PNG'
            $this->Ln(10);

            $this->SetFont('Arial', 'B', 16);
            $this->SetTextColor(32, 100, 210);
            $this->Cell(120, 10, "FACTURA", 0, 0, 'L');
            $this->Ln(10);

            // Información del Cliente y Empresa en la misma línea
            if ($this->datosClientePedidoAutoriza && $this->datosEmpresaPedidoAutoriza) {
                $this->SetFont('Arial', 'B', 14);
                $this->SetTextColor(32, 100, 210);

                $this->SetX(10); // Inicia desde la izquierda
                $this->Cell(90, 10, iconv("UTF-8", "ISO-8859-1", "Datos del Cliente"), 0, 0, 'L');
                $this->SetX(140); // Posiciona la empresa en la parte derecha
                $this->Cell(100, 10, iconv("UTF-8", "ISO-8859-1", "Datos del Emisor"), 0, 0, 'L');
                $this->Ln(5);

                // Cliente - A la Izquierda
                $this->SetX(10); // Inicia desde la izquierda
                $this->Cell(90, 10, iconv("UTF-8", "ISO-8859-1", $this->datosClientePedidoAutoriza['nombre']), 0, 0, 'L');

                // Empresa - A la Derecha
                $this->SetX(140); // Posiciona la empresa en la parte derecha
                $this->Cell(100, 10, iconv("UTF-8", "ISO-8859-1", strtoupper($this->datosEmpresaPedidoAutoriza['razonSocial'])), 0, 0, 'L');

                $this->Ln(10);

                $this->SetFont('Arial', '', 10);
                $this->SetTextColor(39, 39, 51);

                // RFC - Cliente a la izquierda
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosClientePedidoAutoriza['rfc']), 0, 0, 'L');

                // RFC - Empresa a la derecha
                $this->SetX(140);
                $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "RFC: " . $this->datosEmpresaPedidoAutoriza['rfc']), 0, 0, 'L');

                $this->Ln(5);

                // Dirección - Cliente a la izquierda
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosClientePedidoAutoriza['direccion'] . ", " . $this->datosClientePedidoAutoriza['colonia']), 0, 0, 'L');

                // Dirección - Empresa a la derecha
                $this->SetX(140);
                $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Dirección: " . $this->datosEmpresaPedidoAutoriza['calle'] . " " . $this->datosEmpresaPedidoAutoriza['numExterior'] . ", " . $this->datosEmpresaPedidoAutoriza['colonia']), 0, 0, 'L');

                $this->Ln(5);

                // Ubicación - Cliente a la izquierda
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", $this->datosClientePedidoAutoriza['ubicacion']), 0, 0, 'L');

                // Ubicación - Empresa a la derecha
                $this->SetX(140);
                $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", $this->datosEmpresaPedidoAutoriza['municipio'] . ", " . $this->datosEmpresaPedidoAutoriza['estado'] . ", " . $this->datosEmpresaPedidoAutoriza['pais']), 0, 0, 'L');

                $this->Ln(5);

                // Código Postal - Cliente a la izquierda
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosClientePedidoAutoriza['codigoPostal']), 0, 0, 'L');

                // Código Postal - Empresa a la derecha
                $this->SetX(140);
                $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Código Postal: " . $this->datosEmpresaPedidoAutoriza['codigoPostal']), 0, 0, 'L');

                $this->Ln(5);

                // Regimen
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Regimen Fiscal: " . $this->datosClientePedidoAutoriza['REG_FISC']), 0, 0, 'L');

                // Regimen
                $this->SetX(140);
                $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1", "Regimen Fiscal: " . $this->regimen), 0, 0, 'L');
                $this->Ln(5);

                // Teléfono - Cliente a la izquierda
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $this->datosClientePedidoAutoriza['telefono']), 0, 0, 'L');
                $this->Ln(5);

                $this->SetFont('Arial', '', 10);
                $this->SetTextColor(39, 39, 51);
                // Email - Cliente a la izquierda
                $this->SetX(10);
                $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->emailPred), 0, 0, 'L');
                $this->Ln(15);
            }

            // **Encabezado de la tabla de partidas**
            $this->SetFont('Arial', 'B', 8);
            $this->SetFillColor(23, 83, 201);
            $this->SetDrawColor(23, 83, 201);
            $this->SetTextColor(255, 255, 255);
            /*$this->Cell(20, 8, "Clave", 1, 0, 'C', true);
            $this->Cell(70, 8, iconv("UTF-8", "ISO-8859-1", "Descripción"), 1, 0, 'C', true);
            $this->Cell(15, 8, "Cant.", 1, 0, 'C', true);
            $this->Cell(20, 8, "Precio", 1, 0, 'C', true);
            $this->Cell(20, 8, "Descuento", 1, 0, 'C', true);
            $this->Cell(20, 8, "Impuestos", 1, 0, 'C', true);
            $this->Cell(30, 8, "Subtotal", 1, 1, 'C', true);*/
            $this->Cell(10, 8, "Cant.", 1, 0, 'C', true);
            $this->Cell(28, 8, "Unidad", 1, 0, 'C', true);
            $this->Cell(15, 8, "Clave SAT", 1, 0, 'C', true);
            $this->Cell(15, 8, "Clave", 1, 0, 'C', true);
            $this->Cell(60, 8, iconv("UTF-8", "ISO-8859-1", "Descripción"), 1, 0, 'C', true);
            $this->Cell(18, 8, "IVA", 1, 0, 'C', true);
            $this->Cell(18, 8, "Precio", 1, 0, 'C', true);
            $this->Cell(22, 8, "Subtotal", 1, 1, 'C', true);
        }
    }

    function Footer()
    {
        // Posicionar a 1.5 cm del fondo
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        // Imprime "Página X de Y" centrado
        $this->Cell(1, 10, "Este documento es una representacion impresa de un CFDI", 0, 0, 'L');
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
}

function generarReportePedido($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $FOLIO, $conn){
    $datosEmpresaFire = obtenerDatosEmpresaFire($noEmpresa);

    $clienteId = str_pad(trim($formularioData['cliente']), 10, ' ', STR_PAD_LEFT);
    $datosClienteReporte = obtenerDatosClienteReporte($conexionData, $clienteId, $claveSae, $conn);

    $emailPredArray = explode(';', $datosClienteReporte['email']); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]);

    $clave = str_pad(trim($formularioData['claveVendedor']), 5, ' ', STR_PAD_LEFT);
    $datosVendedor = obtenerDatosVendedor($clave);
    $ordenCompra = $formularioData['ordenCompra'];
    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    $pdf = new PDFPedido($datosEmpresaFire, $datosClienteReporte, $datosVendedor, $formularioData, $ordenCompra, $emailPred, $FOLIO);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    foreach ($partidasData as $partida) {
        $productosData = obtenerDescripcionProductoPedidoAutoriza($partida['producto'], $conexionData, $claveSae, $conn);

        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $desc1 = $partida['descuento'] ?? 0;
        //$descTotal = intval($formularioData['descuentoCliente'] ?? 0);
        //$descuentos = $desc1  + $descTotal;

        $ieps = intval($partida['ieps'] ?? 0);
        $impuesto2 = intval($partida['impuesto2'] ?? 0);
        $isr = intval($partida['isr'] ?? 0);
        $iva = intval($partida['iva'] ?? 0);
        $impuestos = $ieps + $impuesto2 + $isr + $iva;

        $subtotalPartida = $precioUnitario * $cantidad;

        $descuentoPartida = $subtotalPartida * (($desc1 / 100));

        $totalDescuentos += $descuentoPartida;
        $subtotal += $subtotalPartida;

        $impuestoPartida = ($subtotalPartida - $descuentoPartida) * ($impuestos / 100);
        $totalImpuestos += $impuestoPartida;

        $pdf->SetTextColor(39, 39, 51);
        /*$pdf->Cell(20, 7, $partida['producto'], 0, 0, 'C');
        $pdf->Cell(70, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 0);
        $pdf->Cell(15, 7, $cantidad, 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($precioUnitario, 2), 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($desc1, 2) . "%", 1, 0, 'C');
        $pdf->Cell(20, 7, number_format($impuestos, 2) . "%", 0, 0, 'C');
        $pdf->Cell(30, 7, number_format($subtotalPartida, 2), 0, 1, 'R');*/
        $pdf->Cell(8, 7, $cantidad, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(28, 7, $partida['unidad'] . " " . $partida['CVE_UNIDAD'], 0, 0, 'C');
        $pdf->Cell(15, 7, $productosData['CVE_PRODSERV'], 0, 0, 'C');
        $pdf->Cell(15, 7, $partida['producto'], 0, 0, 'C');
        //$pdf->SetFont('Arial', '', 6);
        $pdf->Cell(65, 7, iconv("UTF-8", "ISO-8859-1", $productosData['DESCR']), 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(18, 7,"$" . number_format($impuestoPartida, 2), 0, 0, 'R');
        $pdf->Cell(18, 7,"$" . number_format($precioUnitario, 2), 0, 0, 'R');
        $pdf->Cell(22, 7,"$" . number_format($subtotalPartida, 2), 0, 1, 'R');
    }

    // Calcular totales
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $total = $subtotalConDescuento + $totalImpuestos;

    //$total = $subtotal - $subtotalConDescuento + $totalImpuestos;

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Importe:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Descuento:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalDescuentos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotalConDescuento, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'IVA:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalImpuestos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($total, 2), 0, 1, 'R');

    // **Generar el nombre del archivo correctamente**
    $nombreArchivo = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $FOLIO) . ".pdf";
    $rutaArchivo = __DIR__ . "/pdfs/" . $nombreArchivo;

    // **Asegurar que la carpeta `pdfs/` exista**
    if (!is_dir(__DIR__ . "/pdfs")) {
        mkdir(__DIR__ . "/pdfs", 0777, true);
    }

    // **Guardar el PDF en el servidor**
    $pdf->Output($rutaArchivo, "F");

    return $rutaArchivo;
}
function generarReportePedidoE($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa, $FOLIO){
    $datosEmpresaFire = obtenerDatosEmpresaFire($noEmpresa);

    $clienteId = str_pad(trim($formularioData['cliente']), 10, ' ', STR_PAD_LEFT);
    $datosClienteReporte = obtenerDatosClienteReporteE($conexionData, $clienteId, $claveSae);

    $emailPredArray = explode(';', $datosClienteReporte['email']); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]);

    $clave = str_pad(trim($formularioData['claveVendedor']), 5, ' ', STR_PAD_LEFT);
    $datosVendedor = obtenerDatosVendedor($clave);
    $ordenCompra = $formularioData['ordenCompra'];
    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    $pdf = new PDFPedido($datosEmpresaFire, $datosClienteReporte, $datosVendedor, $formularioData, $ordenCompra, $emailPred, $FOLIO);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    foreach ($partidasData as $partida) {
        $productosData = obtenerDescripcionProductoPedidoAutorizaE($partida['producto'], $conexionData, $claveSae);

        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $desc1 = $partida['descuento'] ?? 0;
        //$descTotal = intval($formularioData['descuentoCliente'] ?? 0);
        //$descuentos = $desc1  + $descTotal;

        $ieps = intval($partida['ieps'] ?? 0);
        $impuesto2 = intval($partida['impuesto2'] ?? 0);
        $isr = intval($partida['isr'] ?? 0);
        $iva = intval($partida['iva'] ?? 0);
        $impuestos = $ieps + $impuesto2 + $isr + $iva;

        $subtotalPartida = $precioUnitario * $cantidad;

        $descuentoPartida = $subtotalPartida * (($desc1 / 100));

        $totalDescuentos += $descuentoPartida;
        $subtotal += $subtotalPartida;

        $impuestoPartida = ($subtotalPartida - $descuentoPartida) * ($impuestos / 100);
        $totalImpuestos += $impuestoPartida;

        $pdf->SetTextColor(39, 39, 51);
        /*$pdf->Cell(20, 7, $partida['producto'], 0, 0, 'C');
        $pdf->Cell(70, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 0);
        $pdf->Cell(15, 7, $cantidad, 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($precioUnitario, 2), 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($desc1, 2) . "%", 1, 0, 'C');
        $pdf->Cell(20, 7, number_format($impuestos, 2) . "%", 0, 0, 'C');
        $pdf->Cell(30, 7, number_format($subtotalPartida, 2), 0, 1, 'R');*/
        $pdf->Cell(8, 7, $cantidad, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(28, 7, $partida['unidad'] . " " . $partida['CVE_UNIDAD'], 0, 0, 'C');
        $pdf->Cell(15, 7, $productosData['CVE_PRODSERV'], 0, 0, 'C');
        $pdf->Cell(15, 7, $partida['producto'], 0, 0, 'C');
        //$pdf->SetFont('Arial', '', 6);
        $pdf->Cell(65, 7, iconv("UTF-8", "ISO-8859-1", $productosData['DESCR']), 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(18, 7,"$" . number_format($impuestoPartida, 2), 0, 0, 'R');
        $pdf->Cell(18, 7,"$" . number_format($precioUnitario, 2), 0, 0, 'R');
        $pdf->Cell(22, 7,"$" . number_format($subtotalPartida, 2), 0, 1, 'R');
    }

    // Calcular totales
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $total = $subtotalConDescuento + $totalImpuestos;

    //$total = $subtotal - $subtotalConDescuento + $totalImpuestos;

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Importe:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Descuento:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalDescuentos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotalConDescuento, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'IVA:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalImpuestos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($total, 2), 0, 1, 'R');

    // **Generar el nombre del archivo correctamente**
    $nombreArchivo = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $FOLIO) . ".pdf";
    $rutaArchivo = __DIR__ . "/pdfs/" . $nombreArchivo;

    // **Asegurar que la carpeta `pdfs/` exista**
    if (!is_dir(__DIR__ . "/pdfs")) {
        mkdir(__DIR__ . "/pdfs", 0777, true);
    }

    // **Guardar el PDF en el servidor**
    $pdf->Output($rutaArchivo, "F");

    return $rutaArchivo;
}
function generarReportePedidoAutorizado($conexionData, $CVE_DOC, $claveSae, $noEmpresa, $vendedor, $folio)
{
    $vendedor = trim($vendedor);
    // Obtener los datos de la empresa
    $datosEmpresaPedidoAutoriza = obtenerDatosEmpresaFire($noEmpresa);
    $datosPedidoAutoriza = obtenerDatosPedido($CVE_DOC, $conexionData, $claveSae);
    $datosPartidasPedido = obtenerDatosPartidasPedido($CVE_DOC, $conexionData, $claveSae);
    
    // Obtener datos del cliente
    $clienteId = str_pad(trim($datosPedidoAutoriza['CVE_CLPV']), 10, ' ', STR_PAD_LEFT);
    //var_dump($clienteId);
    $datosClientePedidoAutoriza = obtenerDatosClienteReporteE($conexionData, $clienteId, $claveSae);
    //var_dump($datosClientePedidoAutoriza);
    $emailPredArray = explode(';', $datosClientePedidoAutoriza['email']); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    // Obtener datos del vendedor
    $clave = str_pad(trim($vendedor), 5, ' ', STR_PAD_LEFT);
    $datosVendedorPedidoAutoriza = obtenerDatosVendedor($clave);

    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    // Crear el PDF
    $pdf = new PDFPedidoAutoriza($datosEmpresaPedidoAutoriza, $datosClientePedidoAutoriza, $datosVendedorPedidoAutoriza, $datosPedidoAutoriza, $emailPred);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    // **Agregar filas de la tabla con los datos de la remisión**
    foreach ($datosPartidasPedido as $partida) {
        // **Obtener la descripción del producto desde SQL Server**
        $productosData = obtenerDescripcionProductoPedidoAutorizaE($partida['CVE_ART'], $conexionData, $claveSae);

        // **Cálculos**
        $precioUnitario = $partida['PREC'];
        $cantidad = $partida['CANT'];
        $desc1 = $partida['DESC1'] ?? 0;
        $desc2 = $partida['DESC2'] ?? 0;
        //$descTotal = $datosClientePedidoAutoriza['DESCUENTO'];
        $descuentos = $desc1 + $desc2;
        // Sumar todos los impuestos
        $impuestos = ($partida['IMPU1'] + $partida['IMPU2'] + $partida['IMPU3'] + $partida['IMPU4'] +
            $partida['IMPU5'] + $partida['IMPU6'] + $partida['IMPU7'] + $partida['IMPU8']) ?? 0;

        $subtotalPartida = $precioUnitario * $cantidad;
        // **Aplicar descuentos**
        $descuentoPartida  = $subtotalPartida * (($desc1 / 100));
        // **Sumar totales**
        $subtotal += $subtotalPartida;
        $totalDescuentos += $descuentoPartida;

        $impuestoPartida = ($subtotalPartida - $descuentoPartida) * ($impuestos / 100);
        $totalImpuestos += $impuestoPartida;

        // **Agregar fila de datos**
        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(8, 7, $cantidad, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(28, 7, $partida['UNI_VENTA'] . " " . $partida['CVE_UNIDAD'], 0, 0, 'C');
        $pdf->Cell(15, 7, $productosData['CVE_PRODSERV'], 0, 0, 'C');
        $pdf->Cell(15, 7, $partida['CVE_ART'], 0, 0, 'C');
        //$pdf->SetFont('Arial', '', 6);
        $pdf->Cell(60, 7, iconv("UTF-8", "ISO-8859-1", $productosData['DESCR']), 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(18, 7,"$" . number_format($impuestoPartida, 2), 0, 0, 'R');
        $pdf->Cell(18, 7,"$" . number_format($precioUnitario, 2), 0, 0, 'R');
        $pdf->Cell(22, 7,"$" . number_format($subtotalPartida, 2), 0, 1, 'R');
    }
    // **Calcular totales**
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $total = $subtotalConDescuento + $totalImpuestos;

    // **Mostrar totales en la factura**
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Importe:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Descuento:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalDescuentos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotalConDescuento, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'IVA:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalImpuestos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($total, 2), 0, 1, 'R');

    // **Generar el nombre del archivo correctamente**
    $nombreArchivo = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folio) . ".pdf";
    $rutaArchivo = __DIR__ . "/pdfs/" . $nombreArchivo;

    // **Asegurar que la carpeta `pdfs/` exista**
    if (!is_dir(__DIR__ . "/pdfs")) {
        mkdir(__DIR__ . "/pdfs", 0777, true);
    }

    // **Guardar el PDF en el servidor**
    $pdf->Output($rutaArchivo, "F");

    return $rutaArchivo;
}
function generarReporteRemision($conexionData, $cveDoc, $claveSae, $noEmpresa, $vendedor)
{

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // Obtener los datos de la empresa
    $datosEmpresaFireRemision = obtenerDatosEmpresaFire($noEmpresa);
    $datosRemisiones = obtenerDatosRemisiones($cveDoc, $conexionData, $claveSae);
    $datosPartidasRemisiones = obtenerDatosPartidasRemisiones($cveDoc, $conexionData, $claveSae);

    // Obtener datos del cliente
    $clienteId = str_pad(trim($datosRemisiones['CVE_CLPV']), 10, ' ', STR_PAD_LEFT);
    $datosClienteRemision = obtenerDatosClienteReporteE($conexionData, $clienteId, $claveSae);

    $emailPredArray = explode(';', $datosClienteRemision['email']); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]);

    // Obtener datos del vendedor
    $clave = str_pad(trim($vendedor), 5, ' ', STR_PAD_LEFT);
    $datosVendedorRemision = obtenerDatosVendedor($clave);

    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    // Crear el PDF
    $pdf = new PDFRemision($datosEmpresaFireRemision, $datosClienteRemision, $datosVendedorRemision, $datosRemisiones, $emailPred);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    // **Agregar filas de la tabla con los datos de la remisión**
    foreach ($datosPartidasRemisiones as $partida) {
        // **Obtener la descripción del producto desde SQL Server**
        $descripcion = obtenerDescripcionProductoRemision($partida['CVE_ART'], $conexionData, $claveSae);

        // **Cálculos**
        $precioUnitario = $partida['PREC'];
        $cantidad = $partida['CANT'];
        $desc1 = $partida['DESC1'] ?? 0;
        $desc2 = $partida['DESC2'] ?? 0;
        //$descTotal = $datosClienteRemision['DESCUENTO'];
        $descuentos = $desc1 + $desc2;
        // Sumar todos los impuestos
        $impuestos = ($partida['IMPU1'] + $partida['IMPU2'] + $partida['IMPU3'] + $partida['IMPU4'] +
            $partida['IMPU5'] + $partida['IMPU6'] + $partida['IMPU7'] + $partida['IMPU8']) ?? 0;

        $subtotalPartida = $precioUnitario * $cantidad;
        // **Aplicar descuentos**
        $descuentoPartida = $subtotalPartida * (($desc1 / 100));


        // **Sumar totales**
        $totalDescuentos += $descuentoPartida;
        $subtotal += $subtotalPartida;

        $impuestoPartida = ($subtotalPartida - $descuentoPartida) * ($impuestos / 100);
        $totalImpuestos += $impuestoPartida;

        // **Agregar fila de datos**
        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(20, 7, $partida['CVE_ART'], 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(70, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 0);
        $pdf->Cell(15, 7, $cantidad, 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($precioUnitario, 2), 0, 0, 'C');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(20, 7, number_format($descuentos, 2) . "%", 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($impuestos, 2) . "%", 0, 0, 'C');
        $pdf->Cell(30, 7, number_format($subtotalPartida, 2), 0, 1, 'R');
    }

    // **Calcular totales**
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $totalFinal = $subtotalConDescuento + $totalImpuestos;

    // **Mostrar totales en la factura**
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Importe:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Descuento:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalDescuentos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotalConDescuento, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'IVA:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalImpuestos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalFinal, 2), 0, 1, 'R');

    // **Generar el nombre del archivo**
    $nombreArchivo = "Remision_" . $datosRemisiones['FOLIO'] . ".pdf";
    $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '', $nombreArchivo);

    // **Limpiar cualquier salida previa antes de enviar el PDF**
    ob_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');

    // **Generar el PDF**
    $pdf->Output("I");
}
function generarFactura($folio, $noEmpresa, $claveSae, $conexionData, $folioFactura){
    $CVE_DOC = str_pad($folioFactura, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    //$CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);

    // Obtener los datos de la empresa
    $datosEmpresaPedidoAutoriza = obtenerDatosEmpresaFire($noEmpresa);
    $datosPedidoAutoriza = obtenerDatosFactura($CVE_DOC, $conexionData, $claveSae);
    $datosPartidasPedido = obtenerDatosPartidasFactura($CVE_DOC, $conexionData, $claveSae);

    // Obtener datos del cliente
    $clienteId = str_pad(trim($datosPedidoAutoriza['CVE_CLPV']), 10, ' ', STR_PAD_LEFT);
    //var_dump($clienteId);
    $datosClientePedidoAutoriza = obtenerDatosClienteReporteE($conexionData, $clienteId, $claveSae);
    //var_dump($datosClientePedidoAutoriza);
    $emailPredArray = explode(';', $datosClientePedidoAutoriza['email']); // Divide los correos por `;`
    $emailPred = trim($emailPredArray[0]); // Obtiene solo el primer correo y elimina espacios extra
    // Obtener datos del vendedor CVE_VEND
    $clave = str_pad(trim($datosPedidoAutoriza['CVE_VEND']), 5, ' ', STR_PAD_LEFT);
    $datosVendedorPedidoAutoriza = obtenerDatosVendedor($clave);

    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    $regimenStr = $datosEmpresaPedidoAutoriza['regimenFiscal'];
    if (preg_match('/^(\d+)/', $regimenStr, $matches)) {
        $regimen = $matches[1];
    } else {
        $regimen = $regimenStr;
    }

    // Ruta de los archivos (ajusta la ruta según corresponda)
    $nombreArchivoBase = "cfdi_" . urlencode($datosClientePedidoAutoriza['nombre']) . "_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura);
    $xmlFile = "../XML/sdk2/timbrados/" . $nombreArchivoBase . ".xml";
    $qrFile = "../XML/sdk2/timbrados/" . $nombreArchivoBase . ".png";

    // Extraer datos fiscales desde el XML 
    if (file_exists($xmlFile)) {
        $xml = simplexml_load_file($xmlFile);
        $namespaces = $xml->getNamespaces(true);

        // Registrar el namespace "cfdi" si existe
        if (isset($namespaces['cfdi'])) {
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi']);
        }
        // Registrar el namespace "tfd" (para TimbreFiscalDigital)
        if (isset($namespaces['tfd'])) {
            $xml->registerXPathNamespace('tfd', $namespaces['tfd']);
        } else {
            // En algunos XML el namespace "tfd" podría venir dentro de cfdi:Complemento
            // Puedes intentar extraerlo así:
            $complemento = $xml->xpath('//cfdi:Complemento');
            if ($complemento && isset($complemento[0])) {
                $tfdNamespaces = $complemento[0]->getNamespaces(true);
                if (isset($tfdNamespaces['tfd'])) {
                    $xml->registerXPathNamespace('tfd', $tfdNamespaces['tfd']);
                }
            }
        }
        // Extraer datos fiscales desde el nodo principal
        $noCertificado = (string)$xml['NoCertificado'];
        $sello = (string)$xml['Sello'];
        $fechaEmision = (string)$xml['Fecha'];
        $MetodoPago = (string)$xml['MetodoPago'];
        $FormaPago = (string)$xml['FormaPago'];
        $LugarExpedicion = (string)$xml['LugarExpedicion'];
        $Moneda = (string)$xml['Moneda'];
        $TipoDeComprobante = (string)$xml['TipoDeComprobante'];
        $fecha = new DateTime($fechaEmision);  // Convertir a un objeto DateTime
        $fechaEmision = $fecha->format('Y-m-d');

        // Extraer "FechaTimbrado" usando XPath para llegar al nodo TimbreFiscalDigital
        $timbres = $xml->xpath('//tfd:TimbreFiscalDigital');
        $fechaTimbrado = "";
        if ($timbres && isset($timbres[0])) {
            $fechaTimbrado = (string)$timbres[0]['FechaTimbrado'];
            $SelloSAT = (string)$timbres[0]['SelloSAT'];
            $SelloCFD = (string)$timbres[0]['SelloCFD'];
            $RfcProvCertif = (string)$timbres[0]['RfcProvCertif'];
        }
        $fecha = $fechaTimbrado; // Ahora $fecha tiene el valor correcto.

        $selloSat = (string)$xml['SelloSAT']; // Si fuese atributo de cfdi:Comprobante
    }

    // Crear el PDF
    $pdf = new PDFFactura(
        $datosEmpresaPedidoAutoriza,
        $datosClientePedidoAutoriza,
        $datosVendedorPedidoAutoriza,
        $datosPedidoAutoriza,
        $emailPred,
        $regimen,
        $fechaEmision
    );
    $pdf->AddPage();
    //$pdf->SetFont('Arial', '', 9);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(39, 39, 51);

    // **Agregar filas de la tabla con los datos de la remisión**
    foreach ($datosPartidasPedido as $partida) {
        // **Obtener la descripción del producto desde SQL Server**
        $productosData = obtenerDescripcionProductoPedidoAutorizaE($partida['CVE_ART'], $conexionData, $claveSae);

        // **Cálculos**
        $precioUnitario = $partida['PREC'];
        $cantidad = $partida['CANT'];
        $desc1 = $partida['DESC1'] ?? 0;
        $desc2 = $partida['DESC2'] ?? 0;
        //$descTotal = $datosClientePedidoAutoriza['DESCUENTO'];
        $descuentos = $desc1 + $desc2;
        // Sumar todos los impuestos
        $impuestos = ($partida['IMPU1'] + $partida['IMPU2'] + $partida['IMPU3'] + $partida['IMPU4'] +
            $partida['IMPU5'] + $partida['IMPU6'] + $partida['IMPU7'] + $partida['IMPU8']) ?? 0;

        $subtotalPartida = $precioUnitario * $cantidad;
        // **Aplicar descuentos**
        $descuentoPartida  = $subtotalPartida * (($desc1 / 100));
        // **Sumar totales**
        $subtotal += $subtotalPartida;
        $totalDescuentos += $descuentoPartida;

        $impuestoPartida = ($subtotalPartida - $descuentoPartida) * ($impuestos / 100);
        $totalImpuestos += $impuestoPartida;

        // **Agregar fila de datos**
        $pdf->SetTextColor(39, 39, 51);
        /*$pdf->Cell(20, 7, $partida['CVE_ART'], 0, 0, 'C');
        $pdf->Cell(70, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 0);
        $pdf->Cell(15, 7, $cantidad, 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($precioUnitario, 2), 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($descuentos, 2) . "%", 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($impuestos, 2) . "%", 0, 0, 'C');
        $pdf->Cell(30, 7, number_format($subtotalPartida, 2), 0, 1, 'R');*/
        $pdf->Cell(8, 7, $cantidad, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(28, 7, $partida['UNI_VENTA'] . " " . $partida['CVE_UNIDAD'], 0, 0, 'C'); //Unidad
        $pdf->Cell(15, 7, $productosData['CVE_PRODSERV'], 0, 0, 'C'); //Clave SAT
        $pdf->Cell(15, 7, $partida['CVE_ART'], 0, 0, 'C');
        $pdf->Cell(65, 7, iconv("UTF-8", "ISO-8859-1", $productosData['DESCR']), 0);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(18, 7,"$" . number_format($impuestoPartida, 2), 0, 0, 'R');
        $pdf->Cell(18, 7, "$" . number_format($precioUnitario, 2), 0, 0, 'R');
        $pdf->Cell(22, 7, "$" . number_format($subtotalPartida, 2), 0, 1, 'R');
    }

    // **Calcular totales**
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $total = $subtotalConDescuento + $totalImpuestos;

    // **Mostrar totales en la factura**
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Importe:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Descuento:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalDescuentos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotalConDescuento, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'IVA:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalImpuestos, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($total, 2), 0, 1, 'R');

    $totalLetraFormatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    $totalEnLetras = $totalLetraFormatter->format($total);
    // Si deseas que el resultado aparezca en mayúsculas, puedes convertirlo con strtoupper()
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 7, strtoupper($totalEnLetras), 0, 1, 'R');

    $pdf->Ln(3);

    // Llamar a la función para imprimir los datos fiscales en la última página
    $pdf->imprimirDatosFiscales(
        $noCertificado,
        $sello,
        $MetodoPago,
        $FormaPago,
        $LugarExpedicion,
        $TipoDeComprobante,
        $Moneda,
        $SelloSAT,
        $SelloCFD,
        $RfcProvCertif,
        $qrFile,
        $fecha
    );

    $pdf->AliasNbPages();
    /********************************************************************************************************************/

    // **Generar el nombre del archivo correctamente**
    $nombreArchivo = "Factura_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $folioFactura) . ".pdf";
    $rutaArchivo = __DIR__ . "/pdfs/" . $nombreArchivo;

    // **Asegurar que la carpeta `pdfs/` exista**
    if (!is_dir(__DIR__ . "/pdfs")) {
        mkdir(__DIR__ . "/pdfs", 0777, true);
    }

    // **Guardar el PDF en el servidor**
    $pdf->Output($rutaArchivo, "F");

    return $rutaArchivo;
    /********************************************************************************************************************/
}
function generarPDFPedido($conexionData, $claveSae, $noEmpresa, $folio) {
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    // a) Obtener datos del pedido, cliente, empresa, partidas, etc.
    $datosEmpresa    = obtenerDatosEmpresaFire($noEmpresa);
    $datosPedido     = obtenerDatosPedido($CVE_DOC, $conexionData, $claveSae);
    $datosPartidas   = obtenerDatosPartidasPedido($CVE_DOC, $conexionData, $claveSae);
    $clienteId       = str_pad(trim($datosPedido['CVE_CLPV']), 10, ' ', STR_PAD_LEFT);
    $datosCliente    = obtenerDatosClienteReporteE($conexionData, $clienteId, $claveSae);
    // b) Creamos el PDF con tu clase:
    $pdf = new PDFPedidoAutoriza(
        $datosEmpresa,
        $datosCliente,
        obtenerDatosVendedor(str_pad(trim($datosPedido['CVE_VEND']), 5, ' ', STR_PAD_LEFT)),
        $datosPedido,
        // aquí podrías pasar otros datos, ej. primer correo:
        explode(';', $datosCliente['email'])[0]
    );
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    // c) Iteramos partidas y escribimos celdas:
    $subtotal       = 0;
    $totalImpuestos = 0;
    $totalDescuentos= 0;
    foreach ($datosPartidas as $partida) {
        $productosData   = obtenerDescripcionProductoPedidoAutorizaE($partida['CVE_ART'], $conexionData, $claveSae);
        $precioUnitario  = $partida['PREC'];
        $cantidad        = $partida['CANT'];
        $desc1           = $partida['DESC1'] ?? 0;
        $descuentoPartida= $precioUnitario * $cantidad * ($desc1 / 100);
        $impuestos       = ($partida['IMPU1'] + $partida['IMPU2'] + $partida['IMPU3'] + $partida['IMPU4']
                            + $partida['IMPU5'] + $partida['IMPU6'] + $partida['IMPU7'] + $partida['IMPU8']) ?? 0;
        $subtotalPartida = $precioUnitario * $cantidad;
        $subtotal       += $subtotalPartida;
        $totalDescuentos+= $descuentoPartida;
        $impuestoPartida = ($subtotalPartida - $descuentoPartida) * ($impuestos / 100);
        $totalImpuestos += $impuestoPartida;
        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(8, 7, $cantidad, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(28, 7, $partida['UNI_VENTA'] . " " . $partida['CVE_UNIDAD'], 0, 0, 'C');
        $pdf->Cell(15, 7, $productosData['CVE_PRODSERV'], 0, 0, 'C');
        $pdf->Cell(15, 7, $partida['CVE_ART'], 0, 0, 'C');
        $pdf->Cell(60, 7, iconv("UTF-8", "ISO-8859-1", $productosData['DESCR']), 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(18, 7, "$" . number_format($impuestoPartida, 2), 0, 0, 'R');
        $pdf->Cell(18, 7, "$" . number_format($precioUnitario, 2), 0, 0, 'R');
        $pdf->Cell(22, 7, "$" . number_format($subtotalPartida, 2), 0, 1, 'R');
    }
    // d) Totales:
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $total                = $subtotalConDescuento + $totalImpuestos;
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Importe:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'Descuento:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalDescuentos, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotalConDescuento, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'IVA:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalImpuestos, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($total, 2), 0, 1, 'R');
    
    //Limpiar cualquier buffer que quedara (importante para que no se 'pegue' nada al PDF)
    if (ob_get_length()) {
        ob_end_clean();
    }

    //Construir un nombre *estrictamente* libre de espacios o "_" al final
    $folioLimpio = trim($folio);                   // quita espacios 
    $folioLimpio = trim($folioLimpio, " _");       // quita guiones bajos y espacios al final/inicio
    $nombreArchivo = "Pedido_" . $folioLimpio . ".pdf";

    //Mandamos headers limpios: Content-Type y Content-Disposition
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

    //Volcar el PDF directamente al navegador:
    $pdf->Output("I");
    exit;
}
function descargarPedidoPdf($conexionData, $claveSae, $noEmpresa, $folio) {
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    // a) Obtener datos del pedido, cliente, empresa, partidas, etc.
    $datosEmpresa    = obtenerDatosEmpresaFire($noEmpresa);
    $datosPedido     = obtenerDatosPedido($CVE_DOC, $conexionData, $claveSae);
    $datosPartidas   = obtenerDatosPartidasPedido($CVE_DOC, $conexionData, $claveSae);
    $clienteId       = str_pad(trim($datosPedido['CVE_CLPV']), 10, ' ', STR_PAD_LEFT);
    $datosCliente    = obtenerDatosClienteReporteE($conexionData, $clienteId, $claveSae);
    // b) Creamos el PDF con tu clase:
    $pdf = new PDFPedidoAutoriza(
        $datosEmpresa,
        $datosCliente,
        obtenerDatosVendedor(str_pad(trim($datosPedido['CVE_VEND']), 5, ' ', STR_PAD_LEFT)),
        $datosPedido,
        // aquí podrías pasar otros datos, ej. primer correo:
        explode(';', $datosCliente['email'])[0]
    );
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    // c) Iteramos partidas y escribimos celdas:
    $subtotal       = 0;
    $totalImpuestos = 0;
    $totalDescuentos= 0;
    foreach ($datosPartidas as $partida) {
        $productosData   = obtenerDescripcionProductoPedidoAutorizaE($partida['CVE_ART'], $conexionData, $claveSae);
        $precioUnitario  = $partida['PREC'];
        $cantidad        = $partida['CANT'];
        $desc1           = $partida['DESC1'] ?? 0;
        $descuentoPartida= $precioUnitario * $cantidad * ($desc1 / 100);
        $impuestos       = ($partida['IMPU1'] + $partida['IMPU2'] + $partida['IMPU3'] + $partida['IMPU4']
                            + $partida['IMPU5'] + $partida['IMPU6'] + $partida['IMPU7'] + $partida['IMPU8']) ?? 0;
        $subtotalPartida = $precioUnitario * $cantidad;
        $subtotal       += $subtotalPartida;
        $totalDescuentos+= $descuentoPartida;
        $impuestoPartida = ($subtotalPartida - $descuentoPartida) * ($impuestos / 100);
        $totalImpuestos += $impuestoPartida;
        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(8, 7, $cantidad, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(28, 7, $partida['UNI_VENTA'] . " " . $partida['CVE_UNIDAD'], 0, 0, 'C');
        $pdf->Cell(15, 7, $productosData['CVE_PRODSERV'], 0, 0, 'C');
        $pdf->Cell(15, 7, $partida['CVE_ART'], 0, 0, 'C');
        $pdf->Cell(60, 7, iconv("UTF-8", "ISO-8859-1", $productosData['DESCR']), 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(18, 7, "$" . number_format($impuestoPartida, 2), 0, 0, 'R');
        $pdf->Cell(18, 7, "$" . number_format($precioUnitario, 2), 0, 0, 'R');
        $pdf->Cell(22, 7, "$" . number_format($subtotalPartida, 2), 0, 1, 'R');
    }
    // d) Totales:
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $total                = $subtotalConDescuento + $totalImpuestos;
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Importe:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'Descuento:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalDescuentos, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotalConDescuento, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'IVA:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalImpuestos, 2), 0, 1, 'R');
    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($total, 2), 0, 1, 'R');
    
    // **Generar el nombre del archivo correctamente**
    $nombreArchivo = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $datosPedido ['FOLIO']) . ".pdf";
    $rutaArchivo = __DIR__ . "/pdfs/" . $nombreArchivo;

    // **Asegurar que la carpeta `pdfs/` exista**
    if (!is_dir(__DIR__ . "/pdfs")) {
        mkdir(__DIR__ . "/pdfs", 0777, true);
    }

    // **Guardar el PDF en el servidor**
    $pdf->Output($rutaArchivo, "F");

    return $rutaArchivo;
}