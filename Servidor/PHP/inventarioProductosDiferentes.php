<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

header('Content-Type: application/json');

// =========================================
// 🔹 Parámetros del cuerpo (POST JSON)
// =========================================
$data = json_decode(file_get_contents("php://input"), true);
$idInventario = $data['idInventario'] ?? null;
$autorizadoPor = $data['autorizadoPor'] ?? 'Usuario desconocido';

// =========================================
// 🔹 Configuración Firebase
// =========================================
$firebaseProjectId = 'mdadmin-77777'; // ✅ tu project ID real
$firebaseApiKey = 'AIzaSyDjh9....';   // ✅ tu API Key real

if (!$idInventario) {
    echo json_encode(['success' => false, 'message' => 'Falta ID de inventario.']);
    exit;
}

try {
    // =========================================
    // 1️⃣ Obtener documento actual del inventario
    // =========================================
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey";
    $invDoc = @file_get_contents($url);
    if ($invDoc === false) {
        throw new Exception("Inventario no encontrado o error de conexión.");
    }
    $invData = json_decode($invDoc, true);

    // =========================================
    // 2️⃣ Extraer campos relevantes del inventario
    // =========================================
    $fields = $invData['fields'] ?? [];
    $noInventario = $fields['noInventario']['integerValue'] ?? '—';
    $noEmpresa = $fields['noEmpresa']['integerValue'] ?? '—';
    $fechaInicio = $fields['fechaInicio']['stringValue'] ?? '—';
    $conteo = $fields['conteo']['integerValue'] ?? '—';
    $status = $fields['status']['booleanValue'] ?? true;
    $productosDiferentes = [];
    if (!empty($fields['productosDiferentes']['arrayValue']['values'])) {
        foreach ($fields['productosDiferentes']['arrayValue']['values'] as $p) {
            if (isset($p['stringValue'])) $productosDiferentes[] = $p['stringValue'];
        }
    }

    // =========================================
    // 3️⃣ Actualizar status a false (finalizado)
    // =========================================
    $patchUrl = "$url&updateMask.fieldPaths=status";
    $body = ['fields' => ['status' => ['booleanValue' => false]]];
    $context = stream_context_create([
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($body)
        ]
    ]);
    $resp = @file_get_contents($patchUrl, false, $context);
    if ($resp === false) {
        throw new Exception("No se pudo actualizar el estado del inventario.");
    }

    // =========================================
    // 4️⃣ Generar PDF de cierre
    // =========================================
    $nombrePDF = "Inventario_Finalizado_" . date('Ymd_His') . ".pdf";
    $pdfDir = __DIR__ . "/../PDF";
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0777, true);
    }
    $pdfPath = "$pdfDir/$nombrePDF";

    $html = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: DejaVu Sans, sans-serif; margin: 40px; font-size: 12px; }
            h1 { text-align: center; color: #2E2E2E; }
            h2 { color: #4E4E4E; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; }
            .firma { margin-top: 80px; text-align: center; }
            .footer { text-align: center; font-size: 10px; color: gray; margin-top: 50px; }
        </style>
    </head>
    <body>
        <h1>INVENTARIO FINALIZADO</h1>
        <hr>
        <h2>Detalles del Inventario</h2>
        <table>
            <tr><th>ID Inventario</th><td>$idInventario</td></tr>
            <tr><th>Número Inventario</th><td>$noInventario</td></tr>
            <tr><th>Empresa</th><td>$noEmpresa</td></tr>
            <tr><th>Fecha de Inicio</th><td>$fechaInicio</td></tr>
            <tr><th>Conteo Actual</th><td>$conteo</td></tr>
            <tr><th>Autorizado Por</th><td>$autorizadoPor</td></tr>
            <tr><th>Fecha de Cierre</th><td>" . date('Y-m-d H:i:s') . "</td></tr>
        </table>
        <br>
        <h2>Productos Diferentes</h2>
        <table>
            <tr><th>#</th><th>Clave Producto</th></tr>";

    foreach ($productosDiferentes as $i => $clave) {
        $html .= "<tr><td>" . ($i + 1) . "</td><td>$clave</td></tr>";
    }

    if (empty($productosDiferentes)) {
        $html .= "<tr><td colspan='2' style='text-align:center;'>Sin registros de diferencias.</td></tr>";
    }

    $html .= "
        </table>
        <div class='firma'>
            _______________________________<br>
            <strong>$autorizadoPor</strong><br>
            Autorización de cierre
        </div>
        <div class='footer'>
            MDAdmin — Sistema de Inventario — Generado automáticamente el " . date('d/m/Y H:i') . "
        </div>
    </body>
    </html>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    file_put_contents($pdfPath, $dompdf->output());

    // =========================================
    // 5️⃣ Respuesta JSON
    // =========================================
    echo json_encode([
        'success' => true,
        'message' => 'Inventario cerrado correctamente',
        'nombrePDF' => $nombrePDF,
        'status' => false,
        'productosDiferentes' => $productosDiferentes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
