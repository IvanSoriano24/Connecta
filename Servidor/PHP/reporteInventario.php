<?php
require __DIR__ . '/../fpdf/fpdf.php';


class PDFInventario extends FPDF {
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$datosPost = json_decode($_POST['datos'], true);
$logoBase64 = $_POST['logo'] ?? null;

// === Procesar logo ===
if ($logoBase64) {
    $logoData = explode(',', $logoBase64);
    $logoContent = base64_decode($logoData[1]);
    $logoPath = __DIR__ . "/tmp_logo.png";
    file_put_contents($logoPath, $logoContent);
} else {
    $logoPath = __DIR__ . "/SRC/imagen-small.png";
}

$pdf = new PDFInventario('P','mm','Letter');
$pdf->AliasNbPages();
$pdf->AddPage();

// Logo
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 30);
}
$pdf->Ln(20);

// Encabezado
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,utf8_decode("Resumen Inventario Físico"),0,1,'C');
$pdf->Ln(4);

// Datos generales
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,8,"Linea: " . utf8_decode($datosPost['claveLinea']),0,0,'L');
$pdf->Cell(70,8,"No. Inventario: " . $datosPost['noInventario'],0,1,'L');
$pdf->Cell(100,8,"Realizado por: " . utf8_decode($datosPost['usuario'] ?? "—"),0,1,'L');
$pdf->Cell(50,8,"Fecha inicio: " . ($datosPost['fechaInicio'] ?? ""),0,0,'L');
$pdf->Cell(70,8,"Fecha fin: " . ($datosPost['fechaFin'] ?? ""),0,1,'L');
$pdf->Ln(4);

// Cabecera de tabla
$pdf->SetFont('Arial','B',8);
$pdf->Cell(25,8,"Clave",1,0,'C');
$pdf->Cell(50,8,"Articulo",1,0,'C');
$pdf->Cell(25,8,"Lote",1,0,'C');
$pdf->Cell(20,8,"Corrugados",1,0,'C');
$pdf->Cell(20,8,"Cajas",1,0,'C');
$pdf->Cell(20,8,"Total",1,0,'C');
$pdf->Cell(25,8,"Inventario SAE",1,0,'C');
$pdf->Cell(25,8,"Diferencia",1,1,'C');

$pdf->SetFont('Arial','',8);

// Totales
$totalLinea = 0;
$totalSAE = 0;
$totalDif = 0;

foreach ($datosPost['datos'] as $producto) {
    $clave = $producto['clave'];
    $articulo = utf8_decode($producto['articulo']);
    $sae = $producto['sae'];
    $subtotalProducto = 0;

    foreach ($producto['lotes'] as $lote) {
        $corr = $lote['corrugados'];
        $cajas = $lote['corrugadosPorCaja'];
        $total = $lote['total'];
        $loteClave = $lote['lote'];

        $pdf->Cell(25,8,$clave,1);
        $pdf->Cell(50,8,$articulo,1);
        $pdf->Cell(25,8,$loteClave,1);
        $pdf->Cell(20,8,$corr,1,0,'C');
        $pdf->Cell(20,8,$cajas,1,0,'C');
        $pdf->Cell(20,8,$total,1,0,'C');
        $pdf->Cell(25,8,$sae,1,0,'C');
        $pdf->Cell(25,8,($total - $sae),1,1,'C');

        $subtotalProducto += $total;
    }

    // Subtotal por producto
    $pdf->SetFont('Arial','B',8);
    $pdf->Cell(120,8,"Subtotal producto:",1);
    $pdf->Cell(20,8,$subtotalProducto,1,0,'C');
    $pdf->Cell(25,8,$sae,1,0,'C');
    $pdf->Cell(25,8,($subtotalProducto - $sae),1,1,'C');
    $pdf->SetFont('Arial','',8);

    $totalLinea += $subtotalProducto;
    $totalSAE   += $sae;
    $totalDif   += ($subtotalProducto - $sae);
}

// Totales finales
$pdf->Ln(4);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(120,8,"TOTALES DE LA LINEA:",1);
$pdf->Cell(20,8,$totalLinea,1,0,'C');
$pdf->Cell(25,8,$totalSAE,1,0,'C');
$pdf->Cell(25,8,$totalDif,1,1,'C');

// === Salida limpia ===
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="inventario.pdf"');
$pdf->Output('I', 'inventario.pdf');
exit;
