<?php
require_once "firebase.php";
require_once "utils.php";

header("Content-Type: application/json; charset=UTF-8");

// Recibir parámetros del frontend
$noInventario = isset($_POST['noInventario']) ? (int)$_POST['noInventario'] : null;
$idDocumento  = $_POST['idDocumento'] ?? null;
$conteo       = isset($_POST['conteo']) ? (int)$_POST['conteo'] : 1;

if (!$idDocumento) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del documento de inventario']);
    exit;
}

try {
    $firebaseBase = "https://firestore.googleapis.com/v1";
    $dbPath       = "projects/{$firebaseProjectId}/databases/(default)";
    $apiKey       = "?key=" . urlencode($firebaseApiKey);

    // ======================================================
    // 🔹 1. OBTENER DOCUMENTO PRINCIPAL DIRECTAMENTE POR ID
    // ======================================================
    $urlCabecera = "$firebaseBase/$dbPath/documents/INVENTARIO/$idDocumento$apiKey";
    $respCabecera = @file_get_contents($urlCabecera);

    if ($respCabecera === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo obtener la cabecera del inventario']);
        exit;
    }

    $jsonCab = json_decode($respCabecera, true);
    if (!isset($jsonCab['fields'])) {
        echo json_encode(['success' => false, 'message' => 'Inventario no encontrado o sin campos válidos']);
        exit;
    }

    $fields = $jsonCab['fields'];
    $noEmpresa = isset($fields['noEmpresa']['integerValue']) ? (int)$fields['noEmpresa']['integerValue'] : 0;

    // ======================================================
    // 🔹 2. LEER SUBCOLECCIÓN SEGÚN EL CONTEO
    // ======================================================
    $subcollection = "lineas" . ($conteo > 1 ? sprintf("%02d", $conteo) : "");
    $urlSubcol = "$firebaseBase/$dbPath/documents/INVENTARIO/$idDocumento/$subcollection$apiKey";
    $respSubcol = @file_get_contents($urlSubcol);

    $datosLineas = [];

    if ($respSubcol !== false) {
        $jsonSubcol = json_decode($respSubcol, true);

        if (isset($jsonSubcol['error'])) {
            echo json_encode(['success' => false, 'message' => "Error Firestore: {$jsonSubcol['error']['message']}"]);
            exit;
        }

        if (isset($jsonSubcol['documents']) && is_array($jsonSubcol['documents'])) {
            foreach ($jsonSubcol['documents'] as $docCat) {
                $categoria = basename($docCat['name']); // AD, JD, etc.
                $campos = $docCat['fields'] ?? [];

                foreach ($campos as $clave => $arrayValue) {
                    if (!isset($arrayValue['arrayValue']['values'])) continue;

                    foreach ($arrayValue['arrayValue']['values'] as $registro) {
                        $r = $registro['mapValue']['fields'] ?? [];
                        $datosLineas[] = [
                            'categoria'         => $categoria,
                            'clave'             => $clave,
                            'lote'              => $r['lote']['stringValue'] ?? '',
                            'corrugados'        => (int)($r['corrugados']['integerValue'] ?? 0),
                            'corrugadosPorCaja' => (int)($r['corrugadosPorCaja']['integerValue'] ?? 0),
                            'sueltos'           => (int)($r['sueltos']['integerValue'] ?? 0),
                            'total'             => (int)($r['total']['integerValue'] ?? 0)
                        ];
                    }
                }
            }
        }
    }

    // ======================================================
    // 🔹 3. OBTENER DATOS DE SAE (filtrados por claves Firestore)
    // ======================================================
    $datosSAE = [];
    $clavesFirestore = array_unique(array_column($datosLineas, 'clave')); // todas las claves de Firestore

    if (!empty($clavesFirestore)) {
        // Obtener todos los productos de SAE
        $datosSAEall = obtenerDatosSAE($noEmpresa, $firebaseProjectId, $firebaseApiKey);

        // Filtrar solo los que existen en Firestore
        $datosSAE = array_filter($datosSAEall, function ($item) use ($clavesFirestore) {
            return in_array($item['clave'], $clavesFirestore);
        });

        // Reindexar el array (opcional para evitar huecos)
        $datosSAE = array_values($datosSAE);
    }

    // ======================================================
    // 🔹 4. RESPUESTA FINAL
    // ======================================================
    echo json_encode([
        'success' => true,
        'datos' => [
            'cabecera' => $fields,
            'lineas'   => $datosLineas,
            'sae'      => $datosSAE
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}