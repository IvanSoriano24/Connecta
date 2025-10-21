<?php
global $firebaseApiKey, $firebaseProjectId;
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/firebase.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;

$idInventario = $_GET['idInventario'] ?? null;
$autorizadoPor = $_GET['autorizadoPor'] ?? 'Usuario desconocido';

if (!$idInventario) {
    die("Falta ID de inventario.");
}

try {
    // Obtener documento Firestore actual
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey";
    $invDoc = json_decode(file_get_contents($url), true);

    if (!$invDoc) {
        die("Inventario no encontrado.");
    }

    // Cambiar status a false y agregar fechaFin
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

    // Actualizamos ambos campos en el mismo PATCH
    file_get_contents("$url&updateMask.fieldPaths=status&updateMask.fieldPaths=fechaFin", false, stream_context_create($opts));


    // 🔹 Generar PDF
    $nombrePDF = "Inventario_Finalizado_" . date('Ymd_His') . ".pdf";
    $fechaActual = date('d/m/Y H:i:s');
    $logoPath = __DIR__ . "/../../Cliente/SRC/logoInterzenda.png";


    // Convierte el logo a base64 para incrustarlo en el PDF
    $logoBase64 = "";
    if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
    }

    $html = "
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 12pt;
            margin: 60px;
            color: #222;
        }

        /* === Logo alineado arriba a la derecha === */
        .logo {
            position: absolute;
            top: 80px;
            right: 70px;
            width: 120px;
        }

        /* === Encabezado y título === */
        h1 {
            text-align: center;
            color: #1a237e;
            font-size: 20pt;
            margin-top: 80px;
            margin-bottom: 5px;
        }

        hr {
            border: 1px solid #1a237e;
            margin-bottom: 30px;
        }

        .info {
            margin-bottom: 60px;
            line-height: 1.8;
        }

        .info p {
            margin: 5px 0;
        }

        .info strong {
            color: #000;
        }

        .firma {
            text-align: center;
            margin-top: 80px;
            line-height: 1.5;
        }

        .footer {
            position: fixed;
            bottom: 40px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
            color: #777;
        }
    </style>
</head>
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


    // Generar PDF y enviarlo al navegador
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $dompdf->stream($nombrePDF, ["Attachment" => true]);

    exit;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
