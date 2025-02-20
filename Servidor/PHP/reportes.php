<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../fpdf/fpdf.php';

//session_start();


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
function obtenerDatosVendedor($clave){
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
function obtenerDatosClienteReporte($conexionData, $clienteId){
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

    // Obtener clave SAE desde la sesión
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT NOMBRE, RFC, CALLE, NUMEXT, NUMINT, COLONIA, MUNICIPIO, ESTADO, PAIS, CODIGO, TELEFONO, EMAILPRED 
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
        'ubicacion' => trim($clienteData['MUNICIPIO'] . ", " . $clienteData['ESTADO'] . ", " . $clienteData['PAIS']),
        'codigoPostal' => trim($clienteData['CODIGO']) ?? 'N/A',
        'telefono' => trim($clienteData['TELEFONO']) ?? 'N/A',
        'email' => trim($clienteData['EMAILPRED']) ?? 'N/A'
    ];
}

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
            $this->Cell(120, 9, iconv("UTF-8", "ISO-8859-1","Email: " . $this->datosVendedor['correo']), 0, 0, 'L');
        }
        // Logo de la empresa
        $this->Image('../../Cliente/SRC/imagen.png', 145, 1, 0, 30); //, '', '', 'PNG'
        $this->Ln(10);

        // Información del Cliente y Empresa en la misma línea
        if ($this->datosClienteReporte && $this->datosEmpresaFire) {
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(32, 100, 210);

            // Cliente - A la Izquierda
            $this->SetX(10); // Inicia desde la izquierda
            $this->Cell(90, 10, iconv("UTF-8", "ISO-8859-1",$this->datosClienteReporte['nombre']), 0, 0, 'L');

            // Empresa - A la Derecha
            $this->SetX(140); // Posiciona la empresa en la parte derecha
            $this->Cell(100, 10, iconv("UTF-8", "ISO-8859-1",strtoupper($this->datosEmpresaFire['razonSocial'])), 0, 0, 'L');

            $this->Ln(10);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);

            // RFC - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1","RFC: " . $this->datosClienteReporte['rfc']), 0, 0, 'L');

            // RFC - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1","RFC: " . $this->datosEmpresaFire['rfc']), 0, 0, 'L');

            $this->Ln(5);

            // Dirección - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1","Dirección: " . $this->datosClienteReporte['direccion'] . ", " . $this->datosClienteReporte['colonia']), 0, 0, 'L');

            // Dirección - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1","Dirección: " . $this->datosEmpresaFire['calle'] . " " . $this->datosEmpresaFire['numExterior'] . ", " . $this->datosEmpresaFire['colonia']), 0, 0, 'L');

            $this->Ln(5);

            // Ubicación - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1",$this->datosClienteReporte['ubicacion']), 0, 0, 'L');

            // Ubicación - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1",$this->datosEmpresaFire['municipio'] . ", " . $this->datosEmpresaFire['estado'] . ", " . $this->datosEmpresaFire['pais']), 0, 0, 'L');

            $this->Ln(5);

            // Código Postal - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1","Código Postal: " . $this->datosClienteReporte['codigoPostal']), 0, 0, 'L');

            // Código Postal - Empresa a la derecha
            $this->SetX(140);
            $this->Cell(100, 9, iconv("UTF-8", "ISO-8859-1","Código Postal: " . $this->datosEmpresaFire['codigoPostal']), 0, 0, 'L');

            $this->Ln(5);

            // Teléfono - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1","Teléfono: " . $this->datosClienteReporte['telefono']), 0, 0, 'L');

            // Empresa: No tiene teléfono, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1","Pedido Nro: " . $this->formularioData['numero']), 0, 0, 'L');

            $this->Ln(5);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            // Email - Cliente a la izquierda
            $this->SetX(10);
            $this->Cell(90, 9, iconv("UTF-8", "ISO-8859-1","Email: " . $this->datosClienteReporte['email']), 0, 0, 'L');

            // Empresa: No tiene email, dejamos espacio en blanco
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 12, iconv("UTF-8", "ISO-8859-1","Fecha de emisión: " . $this->formularioData['diaAlta']), 0, 0, 'L');

            /*// Información del pedido
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(39, 39, 51);
            $this->Cell(100, 7, "Pedido Nro: " . $this->formularioData['numero'], 0, 0, 'L');
            $this->Ln(5);
            $this->SetX(140);
            $this->SetFont('Arial', '', 10);
            $this->Cell(100, 7, "Fecha de emisión: " . $this->formularioData['diaAlta'], 0, 0, 'L');
            */

            $this->Ln(15);
        }

        // **Encabezado de la tabla de partidas**
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(23, 83, 201);
        $this->SetDrawColor(23, 83, 201);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(20, 8, "Clave", 1, 0, 'C', true);
        $this->Cell(90, 8, iconv("UTF-8", "ISO-8859-1","Descripción"), 1, 0, 'C', true);
        $this->Cell(15, 8, "Cant.", 1, 0, 'C', true);
        $this->Cell(30, 8, "Prec. Unitario", 1, 0, 'C', true);
        $this->Cell(40, 8, "Subtotal", 1, 1, 'C', true);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

function generarReportePedido($formularioData, $partidasData, $conexionData)
{
    ob_start();

    // Obtener el número de empresa desde la sesión
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $datosEmpresaFire = obtenerDatosEmpresaFire($noEmpresa);

    $clienteId = str_pad(trim($formularioData['cliente']), 10, ' ', STR_PAD_LEFT);
    $datosClienteReporte = obtenerDatosClienteReporte($conexionData, $clienteId);

    $clave = str_pad(trim($formularioData['claveVendedor']), 5, ' ', STR_PAD_LEFT);
    $datosVendedor = obtenerDatosVendedor($clave);

    $claveSae = $_SESSION['empresa']['claveSae']; // Clave de la empresa para la consulta de productos

    $pdf = new PDFPedido($datosEmpresaFire, $datosClienteReporte, $datosVendedor, $formularioData);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(39, 39, 51);

    // Variables para cálculo de totales
    $subtotal = 0;

    // **Agregar filas de la tabla con los datos de $partidasData**
    foreach ($partidasData as $partida) {
        // **Obtener la descripción del producto desde SQL Server**
        $descripcion = obtenerDescripcionProducto($partida['producto'], $conexionData, $claveSae);

        // **Calcular subtotal**
        $monto = $partida['precioUnitario'] * $partida['cantidad'];
        $subtotal += $monto;

        // **Agregar fila de datos**
        $pdf->SetTextColor(39, 39, 51);
        $pdf->Cell(20, 7, $partida['producto'], 0, 0, 'C');
        $pdf->Cell(90, 7, iconv("UTF-8", "ISO-8859-1", $descripcion), 0);
        $pdf->Cell(15, 7, $partida['cantidad'], 0, 0, 'C');
        $pdf->Cell(30, 7, number_format($partida['precioUnitario'], 2), 0, 0, 'C');
        $pdf->Cell(40, 7, number_format($monto, 2), 0, 1, 'R');
    }

    // **Calcular total con IVA (16%)**
    $totalConIVA = $subtotal * 1.16;

    // **Mostrar totales en la factura**
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'IVA (16%):', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalConIVA - $subtotal, 2), 0, 1, 'R');

    $pdf->Cell(155, 7, 'Total MXN:', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($totalConIVA, 2), 0, 1, 'R');

    
    // Generar el nombre del archivo dinámicamente
    $nombreArchivo = "Pedido_" . $formularioData['numero'] . "_" . str_replace(" ", "_", $datosClienteReporte['nombre']) . ".pdf";

    // Limpiar el nombre del archivo de caracteres especiales
    $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '', $nombreArchivo);

    ob_clean();
    // Configurar las cabeceras HTTP para mostrar el nombre correcto
    header('Content-Type: text/html; charset=UTF-8');
    
    header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');

    // Generar el PDF con el nombre personalizado
    $pdf->Output($nombreArchivo, "I");
    exit;
}
