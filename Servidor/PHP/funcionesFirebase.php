<?php
function buscarYEliminar($noPedido)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Normalizar el número de pedido (por si viene con comillas o espacios)
    $noPedido = trim(str_replace('"', '', $noPedido));

    // 1️⃣ Buscar documentos en la colección PEDIDOS_RECHAZO con ese pedido
    $queryUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    $queryPayload = json_encode([
        "structuredQuery" => [
            "from" => [["collectionId" => "PEDIDOS_RECHAZO"]],
            "where" => [
                "fieldFilter" => [
                    "field" => ["fieldPath" => "pedido"],
                    "op" => "EQUAL",
                    "value" => ["stringValue" => $noPedido]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $opts = [
        "http" => [
            "method"  => "POST",
            "header"  => "Content-Type: application/json\r\n",
            "content" => $queryPayload
        ]
    ];

    $context  = stream_context_create($opts);
    $response = @file_get_contents($queryUrl, false, $context);
    $result   = json_decode($response, true);

    if (!is_array($result)) return;

    foreach ($result as $doc) {
        if (isset($doc['document']['name'])) {
            $docPath = $doc['document']['name']; // Ruta completa del documento
            // 2️⃣ Eliminar el documento encontrado
            $deleteUrl = "https://firestore.googleapis.com/v1/$docPath?key=$firebaseApiKey";
            $optsDel = [
                "http" => [
                    "method"  => "DELETE"
                ]
            ];
            @file_get_contents($deleteUrl, false, stream_context_create($optsDel));
        }
    }
}