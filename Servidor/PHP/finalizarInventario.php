<?php
ob_start();

global $firebaseApiKey, $firebaseProjectId;
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/firebase.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;

$idInventario = $_GET['idInventario'] ?? null;
$autorizadoPor = $_GET['autorizadoPor'] ?? 'Usuario desconocido';

if (!$idInventario) {
    echo "Falta ID de inventario.";
    ob_end_flush();
    exit;
}

try {
    // 🔹 Obtener documento Firestore
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey";
    $invDoc = json_decode(file_get_contents($url), true);

    if (!$invDoc) {
        echo "Inventario no encontrado.";
        ob_end_flush();
        exit;
    }

    // 🔹 Actualizar status a false y fechaFin
    $fechaFin = date('Y-m-d H:i:s');
    $body = [
        'fields' => [
            'status' => ['booleanValue' => false],
            'fechaFin' => ['stringValue' => $fechaFin]
        ]
    ];

    $opts = [
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($body)
        ]
    ];

    // 🔹 Ejecutar PATCH a Firestore
    file_get_contents("$url&updateMask.fieldPaths=status&updateMask.fieldPaths=fechaFin", false, stream_context_create($opts));

    // 🔹 Generar PDF
    $nombrePDF = "Inventario_Finalizado_" . date('Ymd_His') . ".pdf";
    $fechaActual = date('d/m/Y H:i:s');
    $logoPath = __DIR__ . "/../../Cliente/SRC/logoInterzenda.png";
    $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : "";

    $html = "
    <html>
    <head><meta charset='UTF-8'>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 12pt; margin: 60px; color: #222; }
        .logo { position: absolute; top: 80px; right: 70px; width: 120px; }
        h1 { text-align: center; color: #1a237e; font-size: 20pt; margin-top: 80px; margin-bottom: 5px; }
        hr { border: 1px solid #1a237e; margin-bottom: 30px; }
        .info { margin-bottom: 60px; line-height: 1.8; }
        .firma { text-align: center; margin-top: 80px; line-height: 1.5; }
        .footer { position: fixed; bottom: 40px; left: 0; width: 100%; text-align: center; font-size: 10pt; color: #777; }
    </style></head>
    <body>
        " . ($logoBase64 ? "<img src='data:image/png;base64,$logoBase64' class='logo'>" : "") . "
        <h1>Inventario Físico Finalizado</h1>
        <hr>
        <div class='info'>
            <p><strong>Fecha de cierre:</strong> $fechaActual</p>
            <p><strong>Autorizado por:</strong> $autorizadoPor</p>
        </div>
        <div class='firma'>
            ______________________________<br>
            $autorizadoPor<br>
            <em>Firma del Responsable</em>
        </div>
        <div class='footer'>
            Documento generado automáticamente — Sistema MDConnecta
        </div>
    </body>
    </html>
    ";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    // 🔹 Devolver el PDF como archivo descargable
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nombrePDF . '"');
    echo $dompdf->output();

    ob_end_flush();
    exit;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
