<?php
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
