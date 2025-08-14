<?php
set_time_limit(0);
require 'firebase.php'; // Archivo con las credenciales de Firebase

function actualizarComandasPendientes($firebaseProjectId, $firebaseApiKey) {
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    // Obtener todas las comandas
    $response = @file_get_contents($url);
    if ($response === false) {
        echo "Error al obtener las comandas.\n";
        return;
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo "No se encontraron comandas.\n";
        return;
    }

    $fechaHoy = date('Y-m-d'); // Fecha actual
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        $status = $fields['status']['stringValue'];
        $fechaElaboracion = explode(' ', $fields['fechaHoraElaboracion']['stringValue'])[0]; // Fecha sin hora

        // Si la comanda está pendiente y es de un día anterior
        if ($status === 'Pendiente' && $fechaElaboracion < $fechaHoy) {
            $comandaId = basename($document['name']);
            cambiarEstadoComanda($firebaseProjectId, $firebaseApiKey, $comandaId);
        }
    }
}

function cambiarEstadoComanda($firebaseProjectId, $firebaseApiKey, $comandaId) {
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA/$comandaId?updateMask.fieldPaths=status&key=$firebaseApiKey";

    $data = [
        'fields' => [
            'status' => ['stringValue' => 'Abierta']
        ]
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        echo "Error al actualizar la comanda $comandaId.\n";
    } else {
        echo "Comanda $comandaId actualizada a 'Abierta'.\n";
    }
}

// Ejecutar la función
actualizarComandasPendientes($firebaseProjectId, $firebaseApiKey);

/***********************************************************************************/
/*
<?php
set_time_limit(0);
require 'firebase.php';   // Define $firebaseProjectId y $firebaseApiKey

function actualizarComandasPendientes(string $firebaseProjectId, string $firebaseApiKey, string $noEmpresa = null)
{
    $fechaHoy = date('Y-m-d'); 
    $urlRun   = "https://firestore.googleapis.com/v1/projects/"
              . "$firebaseProjectId/databases/(default)/documents:runQuery"
              . "?key=$firebaseApiKey";

    // Construir el filtro para runQuery
    $whereNode = buildPendingQueryFilters($fechaHoy, $noEmpresa);

    $payload = json_encode([
        'structuredQuery' => [
            'from'  => [['collectionId' => 'COMANDA']],
            'where' => $whereNode,
            // Opcional: ordenar por folio/fecha
            'orderBy' => [[
                'field'     => ['fieldPath' => 'fechaHoraElaboracion'],
                'direction' => 'ASCENDING'
            ]]
        ]
    ]);

    // Enviar runQuery
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload
        ]
    ]);

    $raw = @file_get_contents($urlRun, false, $ctx);
    if ($raw === false) {
        echo "[ERROR] No se pudo ejecutar runQuery.\n";
        return;
    }

    $matches = json_decode($raw, true);

    // Recorrer los documentos que coinciden con el filtro
    foreach ($matches as $item) {
        if (!isset($item['document'])) {
            continue;
        }

        $doc      = $item['document'];
        $comandaId = basename($doc['name']);
        echo "[INFO] Reabriendo comanda ID=$comandaId\n";
        cambiarEstadoComanda($firebaseProjectId, $firebaseApiKey, $comandaId);
    }
}

function cambiarEstadoComanda(string $firebaseProjectId, string $firebaseApiKey, string $comandaId)
{
    $urlPatch = "https://firestore.googleapis.com/v1/projects/"
              . "$firebaseProjectId/databases/(default)/documents/COMANDA/"
              . "$comandaId"
              . "?updateMask.fieldPaths=status&key=$firebaseApiKey";

    $body = json_encode([
        'fields' => [
            'status' => ['stringValue' => 'Abierta']
        ]
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $body
        ]
    ]);

    $result = @file_get_contents($urlPatch, false, $ctx);
    if ($result === false) {
        echo "[ERROR] Falló al actualizar comanda $comandaId\n";
    } else {
        echo "[OK] Comanda $comandaId ahora es 'Abierta'\n";
    }
}

// Ejecutar todo (puedes pasarle $noEmpresa o dejarlo null si no aplica)
actualizarComandasPendientes($firebaseProjectId, $firebaseApiKey, $noEmpresa ?? null);

*/