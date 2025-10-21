<?php

/**
 * Actualiza la lista de productos diferentes en Firestore según las asignaciones actuales.
 */
function actualizarProductosDiferentesDesdeSQL(
    int $noEmpresa,
    array $lineasNuevas,
    string $idInventario,
    string $firebaseProjectId,
    string $firebaseApiKey
): array {
    try {
        // ================================================================
        // 1️⃣ Obtener documento actual del inventario
        // ================================================================
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey";
        $invDoc = @file_get_contents($url);
        if ($invDoc === false) {
            throw new Exception("Inventario no encontrado o error de conexión.");
        }
        $invData = json_decode($invDoc, true);
        $fields = $invData['fields'] ?? [];

        // ================================================================
        // 2️⃣ Obtener conexión SQL real (desde colección CONEXIONES)
        // ================================================================
        $pdo = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$pdo instanceof PDO) {
            throw new Exception("No se pudo obtener conexión válida a SQL Server.");
        }

        // ================================================================
        // 3️⃣ Obtener clave SAE para construir nombres de tablas dinámicas
        // ================================================================
        $claveSae = null;
        $urlQuery = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";
        $query = [
            "structuredQuery" => [
                "from" => [["collectionId" => "CONEXIONES"]],
                "where" => [
                    "fieldFilter" => [
                        "field" => ["fieldPath" => "noEmpresa"],
                        "op" => "EQUAL",
                        "value" => ["integerValue" => $noEmpresa]
                    ]
                ],
                "limit" => 1
            ]
        ];

        $opts = [
            "http" => [
                "method"  => "POST",
                "header"  => "Content-Type: application/json\r\n",
                "content" => json_encode($query)
            ]
        ];
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($urlQuery, false, $ctx);
        $arr = json_decode($resp, true);
        foreach ($arr as $row) {
            if (isset($row['document'])) {
                $fieldsConn = $row['document']['fields'] ?? [];
                $claveSae = $fieldsConn['claveSae']['stringValue'] ?? null;
                break;
            }
        }

        if (!$claveSae) {
            throw new Exception("No se encontró la clave SAE para la empresa $noEmpresa.");
        }

        // Construcción dinámica de tablas
        $tablaInve = "INVE" . $claveSae;         // ej. INVE02
        $tablaInveClib = "INVE_CLIB" . $claveSae; // ej. INVE_CLIB02

        // ================================================================
        // 4️⃣ Buscar los CVE_ART por cada categoría asignada (CTRL_ALM)
        // ================================================================
        $productosDiferentes = [];

        foreach ($lineasNuevas as $categoria) {
            // A) Traer todos los artículos de esa categoría
            $sqlArt = "SELECT CVE_ART FROM $tablaInve WHERE CTRL_ALM = :categoria";
            $stmtArt = $pdo->prepare($sqlArt);
            $stmtArt->execute([':categoria' => $categoria]);
            $cveArtList = $stmtArt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($cveArtList)) {
                continue; // nada para esta categoría
            }

            // B) Buscar en INVE_CLIBxx los productos con CAMPLIB2 = 'N'
            $inQuery = implode(",", array_map(fn($v) => "'$v'", $cveArtList));
            $sqlProd = "SELECT CVE_PROD FROM $tablaInveClib WHERE CVE_PROD IN ($inQuery) AND CAMPLIB2 = 'N'";
            $stmtProd = $pdo->query($sqlProd);
            $cveProdList = $stmtProd->fetchAll(PDO::FETCH_COLUMN);

            // C) Agregar al arreglo final
            $productosDiferentes = array_merge($productosDiferentes, $cveProdList);
        }

        // Quitar duplicados
        $productosDiferentes = array_values(array_unique($productosDiferentes));

        // ================================================================
        // 5️⃣ Actualizar campo productosDiferentes en Firestore
        // ================================================================
        $root = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
        $patchUrl = "$root/INVENTARIO/$idInventario?key=$firebaseApiKey&updateMask.fieldPaths=productosDiferentes";

        $arrVals = [];
        foreach ($productosDiferentes as $p) {
            $arrVals[] = ['stringValue' => $p];
        }
        $body = ['fields' => ['productosDiferentes' => ['arrayValue' => ['values' => $arrVals]]]];

        $context = stream_context_create([
            'http' => [
                'method'  => 'PATCH',
                'header'  => "Content-Type: application/json\r\n",
                'content' => json_encode($body)
            ]
        ]);
        $resp = @file_get_contents($patchUrl, false, $context);
        if ($resp === false) {
            throw new Exception("Error al actualizar productosDiferentes en Firestore.");
        }

        return [
            'success' => true,
            'message' => 'Productos diferentes actualizados correctamente.',
            'productosDiferentes' => $productosDiferentes
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
