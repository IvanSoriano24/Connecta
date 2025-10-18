<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$idInventario = $data['idInventario'] ?? null;
$autorizadoPor = $data['autorizadoPor'] ?? 'Usuario desconocido';

if (!$idInventario) {
    echo json_encode(['success' => false, 'message' => 'Falta ID de inventario.']);
    exit;
}

try {
    // 🔹 Obtener documento Firestore actual
    $url = "https://firestore.googleapis.com/v1/projects/tu_proyecto/databases/(default)/documents/INVENTARIO/$idInventario?key=TU_API_KEY";
    $invDoc = json_decode(file_get_contents($url), true);

    if (!$invDoc) {
        echo json_encode(['success' => false, 'message' => 'Inventario no encontrado.']);
        exit;
    }

    // 🔹 Cambiar status a false
    $body = [
        'fields' => ['status' => ['booleanValue' => false]]
    ];

    $opts = [
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($body)
        ]
    ];
    file_get_contents("$url&updateMask.fieldPaths=status", false, stream_context_create($opts));

    // 🔹 Generar PDF simple de cierre
    $nombrePDF = "Inventario_Finalizado_" . date('Ymd_His') . ".pdf";
    $pdfPath = __DIR__ . "/../PDF/$nombrePDF";

    $html = "
        <h2 style='text-align:center;'>Inventario Finalizado</h2>
        <hr>
        <p><strong>Inventario:</strong> $idInventario</p>
        <p><strong>Fecha de cierre:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Autorizado por:</strong> $autorizadoPor</p>
        <br><br>
        <p style='text-align:center;'>______________________________<br>Firma del Responsable</p>
    ";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    file_put_contents($pdfPath, $dompdf->output());

    echo json_encode([
        'success' => true,
        'nombrePDF' => $nombrePDF
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
