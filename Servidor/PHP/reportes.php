<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../fpdf/fpdf.php';

//session_start();


// Función para obtener los datos de la empresa desde Firebase
function obtenerDatosEmpresaFire($noEmpresa)
{
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
        if (isset($fields['noEmpresa']['stringValue']) && $fields['noEmpresa']['stringValue'] === $noEmpresa) {
            return [
                'noEmpresa' => $fields['noEmpresa']['stringValue'] ?? null,
                'razonSocial' => $fields['razonSocial']['stringValue'] ?? null,
                'rfc' => $fields['rfc']['stringValue'] ?? null,
                'calle' => $fields['calle']['stringValue'] ?? null,
                'numExterior' => $fields['numExterior']['stringValue'] ?? null,
                'numInterior' => $fields['numInterior']['stringValue'] ?? null,
                'colonia' => $fields['colonia']['stringValue'] ?? null,
                'municipio' => $fields['municipio']['stringValue'] ?? null,
                'estado' => $fields['estado']['stringValue'] ?? null,
                'pais' => $fields['pais']['stringValue'] ?? null,
                'codigoPostal' => $fields['codigoPostal']['stringValue'] ?? null
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
function obtenerDatosClienteReporte($conexionData, $clienteId, $claveSae){
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

    $sql = "SELECT NOMBRE, RFC, CALLE, NUMEXT, NUMINT, COLONIA, MUNICIPIO, ESTADO, PAIS, CODIGO, TELEFONO, EMAILPRED, DESCUENTO 
            FROM $nombreTabla WHERE [CLAVE] = ?";

    $params = [$clienteId];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar el cliente', 'errors' => sqlsrv_errors()]));
    }

    $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);

    if (!$clienteData) {
        return null; // Cliente no encontrado
    }

    return [
        'nombre' => trim($clienteData['NOMBRE']) ?? 'Desconocido',
        'rfc' => trim($clienteData['RFC']) ?? 'N/A',
        'direccion' => trim($clienteData['CALLE'] . " " . ($clienteData['NUMEXT'] ?? '') . " " . ($clienteData['NUMINT'] ?? '')),
        'colonia' => trim($clienteData['COLONIA']) ?? 'N/A',
        'ubicacion' => trim($clienteData['MUNICIPIO'] . ", " . $clienteData['ESTADO'] . ", " . $clienteData['PAIS']) ?? 'N/A',
        'codigoPostal' => trim($clienteData['CODIGO']) ?? 'N/A',
        'telefono' => $clienteData['TELEFONO'] ?? 'N/A',
        'email' => $clienteData['EMAILPRED'] ?? 'N/A',
        'DESCUENTO' => $clienteData['DESCUENTO'] ?? 0
    ];
}
/****************************************************************************************************************/
function obtenerDatosRemisiones($cveDoc, $conexionData, $claveSae){
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
function obtenerDatosPartidasRemisiones($cveDoc, $conexionData, $claveSae){
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
function obtenerDescripcionProductoRemision($CVE_ART, $conexionData, $claveSae){
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
function obtenerDatosPedido($cveDoc, $conexionData, $claveSae){
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener los datos de la remisión
    $sql = "SELECT CVE_CLPV, CVE_VEND, FECHA_DOC, FOLIO, DES_TOT
            FROM $nombreTabla 
            WHERE CVE_DOC = ?";

    $params = [$cveDoc];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al consultar la remisión', 'errors' => sqlsrv_errors()]));
    }

    $datosPedidoAuto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);

    if (!$datosPedidoAuto) {
        return null; // Si no encuentra la remisión, retorna null
    }

    return [
        'CVE_CLPV' => trim($datosPedidoAuto['CVE_CLPV']),
        'CVE_VEND' => trim($datosPedidoAuto['CVE_VEND']),
        'FECHA_DOC' => $datosPedidoAuto['FECHA_DOC']->format('Y-m-d'),
        'FOLIO' => (float) $datosPedidoAuto['FOLIO'],
        'DES_TOT' => (float) $datosPedidoAuto['DES_TOT']
    ];
}
function obtenerDatosPartidasPedido($cveDoc, $conexionData, $claveSae){
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
function obtenerDescripcionProductoPedidoAutoriza($CVE_ART, $conexionData, $claveSae){
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

class PDFPedido extends FPDF
{
    private $datosEmpresaFire;
    private $datosClienteReporte;
    private $datosVendedor;
    private $formularioData;

    function __construct($datosEmpresaFire, $datosClienteReporte, $datosVendedor, $formularioData)
    {
        parent::__construct();
        $this->datosEmpresaFire = $datosEmpresaFire;
        $this->datosClienteReporte = $datosClienteReporte;
        $this->datosVendedor = $datosVendedor;
        $this->formularioData = $formularioData;
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
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Pedido Nro: " . $this->formularioData['numero']), 0, 0, 'L');

            $this->Ln(5);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            // Email - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->datosClienteReporte['email']), 0, 0, 'L');

            // Empresa: No tiene email, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Fecha de emisión: " . $this->formularioData['diaAlta']), 0, 0, 'L');

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
class PDFRemision extends FPDF
{
    private $datosEmpresaFireRemision;
    private $datosClienteRemision;
    private $datosVendedorRemision;
    private $datosRemisiones;

    function __construct($datosEmpresaFireRemision, $datosClienteRemision, $datosVendedorRemision, $datosRemisiones)
    {
        parent::__construct();
        $this->datosEmpresaFireRemision = $datosEmpresaFireRemision;
        $this->datosClienteRemision = $datosClienteRemision;
        $this->datosVendedorRemision = $datosVendedorRemision;
        $this->datosRemisiones = $datosRemisiones;
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
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->datosClienteRemision['email']), 0, 0, 'L');

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

    function __construct($datosEmpresaPedidoAutoriza, $datosClientePedidoAutoriza, $datosVendedorPedidoAutoriza, $datosPedidoAutoriza)
    {
        parent::__construct();
        $this->datosEmpresaPedidoAutoriza = $datosEmpresaPedidoAutoriza;
        $this->datosClientePedidoAutoriza = $datosClientePedidoAutoriza;
        $this->datosVendedorPedidoAutoriza = $datosVendedorPedidoAutoriza;
        $this->datosPedidoAutoriza = $datosPedidoAutoriza;
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
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1", "Remision Nro: " . $this->datosPedidoAutoriza['FOLIO']), 0, 0, 'L');

            $this->Ln(5);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            // Email - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->datosClientePedidoAutoriza['email']), 0, 0, 'L');

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

function generarReportePedido($formularioData, $partidasData, $conexionData, $claveSae, $noEmpresa){
    $datosEmpresaFire = obtenerDatosEmpresaFire($noEmpresa);

    $clienteId = str_pad(trim($formularioData['cliente']), 10, ' ', STR_PAD_LEFT);
    $datosClienteReporte = obtenerDatosClienteReporte($conexionData, $clienteId, $claveSae);

    $clave = str_pad(trim($formularioData['claveVendedor']), 5, ' ', STR_PAD_LEFT);
    $datosVendedor = obtenerDatosVendedor($clave);

    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    $pdf = new PDFPedido($datosEmpresaFire, $datosClienteReporte, $datosVendedor, $formularioData);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    foreach ($partidasData as $partida) {
        $descripcion = obtenerDescripcionProducto($partida['producto'], $conexionData, $claveSae);

        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $desc1 = $partida['descuento'] ?? 0;
        $descTotal = intval($formularioData['descuentoCliente'] ?? 0);
        $descuentos = $desc1  + $descTotal;

        $ieps = intval($partida['ieps'] ?? 0);
        $impuesto2 = intval($partida['impuesto2'] ?? 0);
        $isr = intval($partida['isr'] ?? 0);
        $iva = intval($partida['iva'] ?? 0);
        $impuestos = $ieps + $impuesto2 + $isr + $iva;

        $precioConDescuento = $precioUnitario * (1 - ($desc1 / 100)) * (1 - ($descTotal / 100));
        $subtotalPartida = $precioConDescuento * $cantidad;

        $subtotal += $subtotalPartida;
        $totalDescuentos += ($precioUnitario - $precioConDescuento) * $cantidad;
        $totalImpuestos += ($subtotalPartida * ($impuestos / 100));

        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(20, 7, $partida['producto'], 1, 0, 'C');
        $pdf->Cell(70, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 1);
        $pdf->Cell(15, 7, $cantidad, 1, 0, 'C');
        $pdf->Cell(20, 7, number_format($precioUnitario, 2), 1, 0, 'C');
        $pdf->Cell(20, 7, number_format($descuentos, 2) . "%", 1, 0, 'C');
        $pdf->Cell(20, 7, number_format($impuestos, 2) . "%", 1, 0, 'C');
        $pdf->Cell(30, 7, number_format($subtotalPartida, 2), 1, 1, 'R');
    }

    // Calcular totales
    $subtotalConDescuento = $subtotal - $totalDescuentos;
    $totalFinal = $subtotalConDescuento + $totalImpuestos;

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

    // **Generar el nombre del archivo correctamente**
    $nombreArchivo = "Pedido_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $formularioData['numero']) . ".pdf";
    $rutaArchivo = __DIR__ . "/pdfs/" . $nombreArchivo;

    // **Asegurar que la carpeta `pdfs/` exista**
    if (!is_dir(__DIR__ . "/pdfs")) {
        mkdir(__DIR__ . "/pdfs", 0777, true);
    }

    // **Guardar el PDF en el servidor**
    $pdf->Output($rutaArchivo, "F");

    return $rutaArchivo;
}
function generarReportePedidoAutorizado($conexionData, $CVE_DOC, $claveSae, $noEmpresa, $vendedor, $folio){
    $vendedor = trim($vendedor);
    // Obtener los datos de la empresa
    $datosEmpresaPedidoAutoriza = obtenerDatosEmpresaFire($noEmpresa);
    $datosPedidoAutoriza = obtenerDatosPedido($CVE_DOC, $conexionData, $claveSae);
    $datosPartidasPedido = obtenerDatosPartidasPedido($CVE_DOC, $conexionData, $claveSae);

    // Obtener datos del cliente
    $clienteId = str_pad(trim($datosPedidoAutoriza['CVE_CLPV']), 10, ' ', STR_PAD_LEFT);
    $datosClientePedidoAutoriza = obtenerDatosClienteReporte($conexionData, $clienteId, $claveSae);

    // Obtener datos del vendedor
    $clave = str_pad(trim($vendedor), 5, ' ', STR_PAD_LEFT);
    $datosVendedorPedidoAutoriza = obtenerDatosVendedor($clave);

    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    // Crear el PDF
    $pdf = new PDFPedidoAutoriza($datosEmpresaPedidoAutoriza, $datosClientePedidoAutoriza, $datosVendedorPedidoAutoriza, $datosPedidoAutoriza);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    // **Agregar filas de la tabla con los datos de la remisión**
    foreach ($datosPartidasPedido as $partida) {
        // **Obtener la descripción del producto desde SQL Server**
        $descripcion = obtenerDescripcionProductoPedidoAutoriza($partida['CVE_ART'], $conexionData, $claveSae);

        // **Cálculos**
        $precioUnitario = $partida['PREC'];
        $cantidad = $partida['CANT'];
        $desc1 = $partida['DESC1'] ?? 0;
        $desc2 = $partida['DESC2'] ?? 0;
        $descTotal = $datosClientePedidoAutoriza['DESCUENTO'];
        $descuentos = $desc1 + $desc2 + $descTotal;
        // Sumar todos los impuestos
        $impuestos = ($partida['IMPU1'] + $partida['IMPU2'] + $partida['IMPU3'] + $partida['IMPU4'] +
            $partida['IMPU5'] + $partida['IMPU6'] + $partida['IMPU7'] + $partida['IMPU8']) ?? 0;

        // **Aplicar descuentos**
        $precioConDescuento = $precioUnitario * (1 - ($desc1 / 100)) * (1 - ($desc2 / 100)) * (1 - ($descTotal / 100));
        $subtotalPartida = $precioUnitario * $cantidad;

        // **Sumar totales**
        $subtotal += $subtotalPartida;
        $totalDescuentos += ($precioUnitario - $precioConDescuento) * $cantidad;
        $totalImpuestos += ($subtotalPartida * ($impuestos / 100));

        // **Agregar fila de datos**
        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(20, 7, $partida['CVE_ART'], 0, 0, 'C');
        $pdf->Cell(70, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 0);
        $pdf->Cell(15, 7, $cantidad, 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($precioUnitario, 2), 0, 0, 'C');
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
function generarReporteRemision($conexionData, $cveDoc, $claveSae, $noEmpresa, $vendedor){

    $cveDoc = str_pad($cveDoc, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $cveDoc = str_pad($cveDoc, 20, ' ', STR_PAD_LEFT);

    // Obtener los datos de la empresa
    $datosEmpresaFireRemision = obtenerDatosEmpresaFire($noEmpresa);
    $datosRemisiones = obtenerDatosRemisiones($cveDoc, $conexionData, $claveSae);
    $datosPartidasRemisiones = obtenerDatosPartidasRemisiones($cveDoc, $conexionData, $claveSae);

    // Obtener datos del cliente
    $clienteId = str_pad(trim($datosRemisiones['CVE_CLPV']), 10, ' ', STR_PAD_LEFT);
    $datosClienteRemision = obtenerDatosClienteReporte($conexionData, $clienteId, $claveSae);

    // Obtener datos del vendedor
    $clave = str_pad(trim($vendedor), 5, ' ', STR_PAD_LEFT);
    $datosVendedorRemision = obtenerDatosVendedor($clave);

    // Variables para cálculo de totales
    $subtotal = 0;
    $totalImpuestos = 0;
    $totalDescuentos = 0;

    // Crear el PDF
    $pdf = new PDFRemision($datosEmpresaFireRemision, $datosClienteRemision, $datosVendedorRemision, $datosRemisiones);
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
        $descTotal = $datosClienteRemision['DESCUENTO'];
        $descuentos = $desc1 + $desc2 + $descTotal;
        // Sumar todos los impuestos
        $impuestos = ($partida['IMPU1'] + $partida['IMPU2'] + $partida['IMPU3'] + $partida['IMPU4'] +
            $partida['IMPU5'] + $partida['IMPU6'] + $partida['IMPU7'] + $partida['IMPU8']) ?? 0;

        // **Aplicar descuentos**
        $precioConDescuento = $precioUnitario * (1 - ($desc1 / 100)) * (1 - ($desc2 / 100)) * (1 - ($descTotal / 100));
        $subtotalPartida = $precioConDescuento * $cantidad;

        // **Sumar totales**
        $subtotal += $subtotalPartida;
        $totalDescuentos += ($precioUnitario - $precioConDescuento) * $cantidad;
        $totalImpuestos += ($subtotalPartida * ($impuestos / 100));

        // **Agregar fila de datos**
        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(20, 7, $partida['CVE_ART'], 0, 0, 'C');
        $pdf->Cell(70, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 0);
        $pdf->Cell(15, 7, $cantidad, 0, 0, 'C');
        $pdf->Cell(20, 7, number_format($precioUnitario, 2), 0, 0, 'C');
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
