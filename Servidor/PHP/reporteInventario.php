<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// === Datos recibidos ===
$datos = json_decode($_POST['datos'] ?? "{}", true);
$logo = $_POST['logo'] ?? "";

// Crear PDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont("helvetica", "", 10);

// === Logo ===
if ($logo) {
    $logoData = preg_replace('#^data:image/\w+;base64,#i', '', $logo);
    $logoContent = base64_decode($logoData);
    $tmpLogo = __DIR__ . "/../../Cliente/SRC/logoInterzenda.png";
    file_put_contents($tmpLogo, $logoContent);
    $pdf->Image($tmpLogo, 170, 10, 30); // arriba derecha
}

// === Encabezado ===
$pdf->SetY(15);
$pdf->SetFont("helvetica", "B", 14);
$pdf->Cell(0, 10, "Resumen Inventario Físico", 0, 1, "C");
$pdf->Ln(2);

$pdf->SetFont("helvetica", "", 10);
$htmlHeader = "
<table cellspacing='0' cellpadding='4'>
<tr>
    <td><b>Línea:</b> " . ($datos['claveLinea'] ?? "-") . "</td>
    <td><b>Fecha inicio:</b> " . ($datos['fechaInicio'] ?? "-") . "</td>
</tr>
<tr>
    <td><b>No. Inventario:</b> " . ($datos['noInventario'] ?? "-") . "</td>
    <td><b>Fecha fin:</b> " . ($datos['fechaFin'] ?? "-") . "</td>
</tr>
<tr>
    <td colspan='2'><b>Realizado por:</b> " . ($datos['usuario'] ?? "—") . "</td>
</tr>
</table>
";
$pdf->writeHTML($htmlHeader, true, false, false, false, "");

// === Tabla ===
$pdf->Ln(3);
$html = '
<table border="1" cellpadding="4">
<thead>
<tr style="background-color:#4B0082; color:#fff; font-weight:bold; text-align:center;">
  <th>Clave</th>
  <th>Artículo</th>
  <th>Lote</th>
  <th>Corrugados</th>
  <th>Cajas</th>
  <th>Total piezas</th>
  <th>Suma total lotes</th>
  <th>Inventario SAE</th>
  <th>Diferencia</th>
</tr>
</thead>
<tbody>
';

$totalLinea = 0;
$totalSae = 0;

foreach (($datos['datos'] ?? []) as $art) {
    $subtotal = 0;
    $rowsLotes = "";

    foreach ($art['lotes'] as $l) {
        $lote = $l['lote'] ?? '';
        $corr = $l['corrugados'] ?? 0;
        $cxc = $l['corrugadosPorCaja'] ?? 0;
        $sueltos = $l['sueltos'] ?? 0;
        $total = $l['total'] ?? (($corr * $cxc) + $sueltos);
        $sae = $art['sae'];

        $rowsLotes .= "<tr>
            <td>{$art['clave']}</td>
            <td>{$art['articulo']}</td>
            <td>{$lote}</td>
            <td align='center'>{$corr}</td>
            <td align='center'>{$cxc}</td>
            <td align='center'>{$total}</td>
            <td align='center'>{$total}</td>
            <td align='center'>{$sae}</td>
            <td align='center'>" . ($total - $sae) . "</td>
        </tr>";

        $subtotal += $total;
    }

    // Filas del producto
    $html .= $rowsLotes;

    // Subtotal producto
    $html .= "<tr style='font-weight:bold; background-color:#f0f0f0;'>
        <td colspan='5' align='right'>Subtotal producto:</td>
        <td align='center'>{$subtotal}</td>
        <td align='center'>{$subtotal}</td>
        <td align='center'>{$art['sae']}</td>
        <td align='center'>" . ($subtotal - $art['sae']) . "</td>
    </tr>";

    $totalLinea += $subtotal;
    $totalSae += $art['sae'];
}

// Totales de la línea
$html .= "<tr style='font-weight:bold; background-color:#dcdcdc;'>
  <td colspan='5' align='right'>TOTALES DE LA LÍNEA:</td>
  <td align='center'>{$totalLinea}</td>
  <td align='center'>{$totalLinea}</td>
  <td align='center'>{$totalSae}</td>
  <td align='center'>" . ($totalLinea - $totalSae) . "</td>
</tr>";

$html .= "</tbody></table>";

$pdf->writeHTML($html, true, false, true, false, "");

// === Salida PDF ===
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="Inventario.pdf"');
$pdf->Output("Inventario.pdf", "I");
exit;
