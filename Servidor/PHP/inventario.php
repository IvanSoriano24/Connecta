<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
include 'utils.php';
session_start();

/****************************************/
/****************************************/

function obtenerLineas($claveSae, $conexionData)
{
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID"      => $conexionData['usuario'],
        "PWD"      => $conexionData['password'],
        "CharacterSet"         => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    if (!$conn) {
        throw new Exception("No pude conectar a la base de datos");
    }
    try {
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIN" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $sql = "SELECT * FROM $nombreTabla";
        $stmt   = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            throw new Exception("Problema al optener las lineas:\n{$errors}");
        }

        $datos = [];
        // Procesar resultados
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }
        //var_dump($datos);

        if (!empty($datos)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $datos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se Encontraron lineas.']);
        }
    } catch (Exception $e) {
        // Si falla cualquiera, deshacemos TODO:
        sqlsrv_rollback($conn);
        sqlsrv_close($conn);
        //return ['success' => false, 'message' => $e->getMessage()];
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function obtenerProductosPorLinea($claveSae, $conexionData, $linea)
{
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID"      => $conexionData['usuario'],
        "PWD"      => $conexionData['password'],
        "CharacterSet"         => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    if (!$conn) {
        throw new Exception("No pude conectar a la base de datos");
    }
    try {
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[INVE_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $sql = "SELECT I.CVE_ART, I.DESCR, LIN_PROD, I.EXIST, L.CVE_ART AS ProductoLote, L.LOTE, L.CANTIDAD AS CantidadLote
            FROM $nombreTabla I
            INNER JOIN $nombreTabla2 L ON L.CVE_ART = I.CVE_ART
            INNER JOIN $nombreTabla3 C ON C.CVE_PROD = I.CVE_ART
            WHERE I.LIN_PROD = ? AND (C.CAMPLIB2 != 'N' OR C.CAMPLIB2 IS NULL)";
        $param = [$linea];
        $stmt   = sqlsrv_query($conn, $sql, $param);
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            throw new Exception("Problema al optener los productos:\n{$errors}");
        }

        $datos = [];
        // Procesar resultados
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }
        //var_dump($datos);

        if (!empty($datos)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $datos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se Encontraron productos.']);
        }
    } catch (Exception $e) {
        // Si falla cualquiera, deshacemos TODO:
        sqlsrv_rollback($conn);
        sqlsrv_close($conn);
        //return ['success' => false, 'message' => $e->getMessage()];
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function obtenerProductoGuardado($noEmpresa, $firebaseProjectId, $firebaseApiKey, $linea, $noInventario)
{
    // Validaciones rápidas
    if (!$noEmpresa || !$linea || !$noInventario) {
        return ['success' => false, 'message' => 'Parámetros incompletos'];
    }

    // 1) Resolver docId del inventario ACTIVO por empresa + folio
    $inv = getInventarioDocByFolio((int)$noEmpresa, (int)$noInventario, $firebaseProjectId, $firebaseApiKey);
    if (!$inv || empty($inv['docId'])) {
        return ['success' => false, 'message' => 'Inventario activo no encontrado'];
    }
    $invDocId = $inv['docId'];

    // 2) Leer el doc de la línea
    $root      = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
    $lineUrl   = "$root/INVENTARIO/$invDocId/lineas/$linea?key=$firebaseApiKey";
    $lineDoc   = http_get_json($lineUrl);

    // Si no existe la línea aún, devolvemos vacío
    if (!$lineDoc || !isset($lineDoc['fields'])) {
        return [
            'success' => true,
            'linea'   => (string)$linea,
            'locked'  => false,
            'conteo'  => null,
            'productos' => []   // no hay productos guardados
        ];
    }

    $fields = $lineDoc['fields'];

    // 3) Metadata de la línea (si existe)
    $locked     = isset($fields['locked']['booleanValue']) ? (bool)$fields['locked']['booleanValue'] : false;
    $status    = isset($fields['status']['booleanValue']) ? (bool)$fields['status']['booleanValue'] : false;
    $conteo     = isset($fields['conteo']['integerValue']) ? (int)$fields['conteo']['integerValue'] : null;
    $finishedAt = isset($fields['finishedAt']['timestampValue']) ? (string)$fields['finishedAt']['timestampValue'] : null;
    $lockedBy   = isset($fields['lockedBy']['stringValue']) ? (string)$fields['lockedBy']['stringValue'] : null;

    // 4) Campos "reservados" (no son productos)
    $reservados = [
        'locked',
        'conteo',
        'finishedAt',
        'lockedBy',
        'updatedAt',
        'lastProduct',
        'conteoTotal',
        'diferencia',
        'existSistema',
        'descr',
        'linesStatus'
    ];

    // 5) Transformar cada campo de producto (arrayValue de maps) -> PHP array
    $productos = [];

    foreach ($fields as $clave => $valor) {
        if (in_array($clave, $reservados, true)) continue;

        // Debe ser un array de maps (lotes)
        if (!isset($valor['arrayValue']['values']) || !is_array($valor['arrayValue']['values'])) {
            continue; // no es la estructura esperada para producto
        }

        $lotesVals = $valor['arrayValue']['values'];
        $lotes = [];
        $sumaTotal = 0;

        foreach ($lotesVals as $entry) {
            if (!isset($entry['mapValue']['fields'])) continue;
            $f = $entry['mapValue']['fields'];

            $corr   = isset($f['corrugados']['integerValue'])        ? (int)$f['corrugados']['integerValue']        : 0;
            $sul   = isset($f['sueltos']['integerValue'])        ? (int)$f['sueltos']['integerValue']        : 0;
            $cxc    = isset($f['corrugadosPorCaja']['integerValue']) ? (int)$f['corrugadosPorCaja']['integerValue'] : 0;
            $lote   = isset($f['lote']['stringValue'])               ? (string)$f['lote']['stringValue']            : '';
            $total  = isset($f['total']['integerValue'])             ? (int)$f['total']['integerValue']             : ($corr * $cxc);

            $sumaTotal += (int)$total;

            $lotes[] = [
                'corrugados'        => $corr,
                'corrugadosPorCaja' => $cxc,
                'lote'              => $lote,
                'total'             => (int)$total,
                'sueltos' => $sul,
            ];
        }

        // Nota: descr/existSistema si los guardaste a nivel línea son globales, no por producto.
        // Si quisieras guardarlos por producto, tendrías que almacenarlos en otra ruta o map adicional.
        $productos[] = [
            'cve_art'     => $clave,
            'conteoTotal' => $sumaTotal,
            'lotes'       => $lotes
        ];
    }

    // 6) Respuesta
    /*return [
        'success'    => true,
        'linea'      => (string)$linea,
        'locked'     => $locked,
        'conteo'     => $conteo,
        'finishedAt' => $finishedAt,
        'lockedBy'   => $lockedBy,
        'productos'  => $productos
    ];*/
    echo json_encode([
        'success'    => true,
        'linea'      => (string)$linea,
        'locked'     => $locked,
        'conteo'     => $conteo,
        'finishedAt' => $finishedAt,
        'lockedBy'   => $lockedBy,
        'productos'  => $productos,
        'activa' => $status
    ]);
}
function noInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    // Construir la URL para filtrar (usa el campo inventarioFisico y noEmpresa)
    $collection = "FOLIOS";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (idPedido y noEmpresa)
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "documento"],
                                "op" => "EQUAL",
                                "value" => ["stringValue" => 'inventarioFisico']
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $noInventario = null;
    if ($response !== false) {

        $resultArray = json_decode($response, true);
        // runQuery devuelve un array con un elemento por cada match
        if (isset($resultArray[0]['document'])) {
            $doc    = $resultArray[0]['document'];
            // si quieres el ID:
            $parts  = explode('/', $doc['name']);
            $fields = $doc['fields'];
            //var_dump($doc);
            // y para tomar tu campo direccion1Contacto:
            $noInventario = $fields['folioSiguiente']['integerValue'] ?? null;
        }
    }
    return $noInventario;
}
function buscarInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    $collection = "INVENTARIO"; // corregido
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Helpers
    $postJson = function (array $body) use ($url) {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($body),
                'timeout' => 15,
            ]
        ];
        $ctx = stream_context_create($options);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            return null;
        }
        $arr = json_decode($resp, true);
        return is_array($arr) ? $arr : null;
    };

    $pickDoc = function (?array $runQueryResponse) {
        // runQuery devuelve un array, cada elemento puede o no traer 'document'
        if (!$runQueryResponse || !is_array($runQueryResponse)) return null;
        foreach ($runQueryResponse as $row) {
            if (isset($row['document'])) return $row['document'];
        }
        return null;
    };

    $getIdFromName = function (string $name) {
        // name: projects/.../databases/(default)/documents/INVENTARIO/{docId}
        $parts = explode('/', $name);
        return end($parts);
    };

    $getField = function (array $fields, string $key) {
        if (!isset($fields[$key])) return null;
        $v = $fields[$key];
        // soporta tipos comunes
        if (isset($v['integerValue'])) return (int)$v['integerValue'];
        if (isset($v['doubleValue']))  return (float)$v['doubleValue'];
        if (isset($v['stringValue']))  return (string)$v['stringValue'];
        if (isset($v['booleanValue'])) return (bool)$v['booleanValue'];
        if (isset($v['nullValue']))    return null;
        return $v; // fallback
    };

    // ------------------------------
    // A) Buscar inventario ACTIVO
    // ------------------------------
    $queryActive = [
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "status"],
                                "op" => "EQUAL",
                                "value" => ["booleanValue" => true]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ];

    $respActive = $postJson($queryActive);
    $docActive  = $pickDoc($respActive);

    $result = [
        "success"       => true,
        "foundActive"   => false,
        "existsAny"     => false,
        "docId"         => null,
        "raw"           => null // opcional: para depurar, puedes quitarlo en producción
    ];

    if ($docActive) {
        $fields = $docActive['fields'] ?? [];
        $result["noInventario"] = $getField($fields, 'noInventario');
        $result["foundActive"]    = true;
        $result["existsAny"]      = true; // si hay activo, por ende existe
        $result["docId"]          = $getIdFromName($docActive['name']);
        $result["raw"]            = null; // o guarda $docActive si quieres
        //echo json_encode($result);
        return $result;
        //return;
    }

    // ---------------------------------------------
    // B) No hay activo: ¿existe algún inventario?
    // ---------------------------------------------
    $queryAny = [
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "fieldFilter" => [
                    "field" => ["fieldPath" => "noEmpresa"],
                    "op" => "EQUAL",
                    "value" => ["integerValue" => (int)$noEmpresa]
                ]
            ],
            "limit" => 1
        ]
    ];

    $respAny = $postJson($queryAny);
    $docAny  = $pickDoc($respAny);

    if ($docAny) {
        $fields = $docAny['fields'] ?? [];
        $result["noInventario"] = $getField($fields, 'noInventario');
        $result["existsAny"]      = true;
        $result["docId"]          = $getIdFromName($docAny['name']);
    }

    return $result;
    //echo json_encode($result);
}
//////////////////////////GUARDAR PRODUCTO/////////////////////////////////////////
function http_get_json($url)
{
    $resp = @file_get_contents($url);
    if ($resp === false) return null;
    return json_decode($resp, true);
}
function http_patch_json($url, $bodyArr)
{
    $opts = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'PATCH',
            'content' => json_encode($bodyArr),
            'timeout' => 20
        ]
    ];
    $ctx  = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false ? json_decode($resp, true) : null;
}
function firestore_runQuery($projectId, $apiKey, $structuredQuery)
{
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:runQuery?key=$apiKey";
    $opts = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(['structuredQuery' => $structuredQuery]),
            'timeout' => 20
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return null;
    return json_decode($resp, true);
}
function getInventarioDocByFolio($noEmpresa, $noInventario, $projectId, $apiKey)
{
    // Busca inventario ACTIVO por empresa + folio
    $q = [
        "from" => [["collectionId" => "INVENTARIO"]],
        "where" => [
            "compositeFilter" => [
                "op" => "AND",
                "filters" => [
                    ["fieldFilter" => [
                        "field" => ["fieldPath" => "status"],
                        "op" => "EQUAL",
                        "value" => ["booleanValue" => true]
                    ]],
                    ["fieldFilter" => [
                        "field" => ["fieldPath" => "noEmpresa"],
                        "op" => "EQUAL",
                        "value" => ["integerValue" => (int)$noEmpresa]
                    ]],
                    ["fieldFilter" => [
                        "field" => ["fieldPath" => "noInventario"],
                        "op" => "EQUAL",
                        "value" => ["integerValue" => (int)$noInventario]
                    ]]
                ]
            ]
        ],
        "limit" => 1
    ];
    $res = firestore_runQuery($projectId, $apiKey, $q);
    if (!$res || !isset($res[0]['document'])) return null;
    $doc = $res[0]['document'];
    $nameParts = explode('/', $doc['name']);
    $docId = end($nameParts);
    $fields = $doc['fields'] ?? [];
    $conteo = isset($fields['conteo']['integerValue']) ? (int)$fields['conteo']['integerValue'] : null;
    return ['docId' => $docId, 'conteo' => $conteo];
}
function escape_field_path($name)
{
    // Para claves con guiones u otros caracteres (p.ej. "AA-1613") hay que usar backticks en updateMask.fieldPaths
    return '`' . str_replace('`', '\\`', $name) . '`';
}
function guardarProducto($noEmpresa, $noInventario, $firebaseProjectId, $firebaseApiKey)
{
    // 1) Parseo del payload
    $payload = json_decode($_POST['payload'] ?? 'null', true);
    if (!$payload) {
        echo json_encode(['success' => false, 'message' => 'Payload inválido']);
        exit;
    }

    // Respaldos desde sesión si no llegan en payload
    if (empty($payload['noEmpresa']) && !empty($_SESSION['empresa']['noEmpresa'])) {
        $payload['noEmpresa'] = (string)$_SESSION['empresa']['noEmpresa'];
    }

    // 2) Validaciones mínimas
    $req = ['linea', 'noInventario', 'cve_art'];
    foreach ($req as $k) {
        if (!isset($payload[$k]) || $payload[$k] === '') {
            echo json_encode(['success' => false, 'message' => "Falta $k"]);
            exit;
        }
    }
    // noEmpresa opcional si tu inventario se resuelve por sesión:
    //$noEmpresa    = isset($payload['noEmpresa']) && $payload['noEmpresa'] !== '' ? (int)$payload['noEmpresa'] : (int)($_SESSION['empresa']['noEmpresa'] ?? 0);
    $lineaId      = (string)$payload['linea'];
    $folioInv     = (int)$payload['noInventario'];
    $cveArt       = (string)$payload['cve_art'];
    $descr        = (string)($payload['descr'] ?? '');
    $existSistema = (int)($payload['existSistema'] ?? 0);
    $conteoTotal  = (int)($payload['conteoTotal'] ?? 0);
    $diferencia   = (int)($payload['diferencia'] ?? ($conteoTotal - $existSistema));
    $lotesIn      = is_array($payload['lotes'] ?? null) ? $payload['lotes'] : [];
    $tsLocal      = (string)($payload['tsLocal'] ?? gmdate('c'));

    if (!$noEmpresa) {
        echo json_encode(['success' => false, 'message' => 'Falta noEmpresa']);
        exit;
    }

    // 3) Resolver docId de INVENTARIO activo por empresa + folio
    $inv = getInventarioDocByFolio($noEmpresa, $folioInv, $firebaseProjectId, $firebaseApiKey);
    if (!$inv) {
        echo json_encode(['success' => false, 'message' => 'No se encontró inventario activo para ese folio/empresa']);
        exit;
    }
    $invDocId = $inv['docId'];

    // 4) (Opcional) Rechazar si la línea está bloqueada
    $root = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
    $lineDocUrl = "$root/INVENTARIO/$invDocId/lineas/$lineaId?key=$firebaseApiKey";
    $lineDoc    = http_get_json($lineDocUrl);
    if (isset($lineDoc['fields']['locked']['booleanValue']) && $lineDoc['fields']['locked']['booleanValue'] === true) {
        echo json_encode(['success' => false, 'message' => 'Línea bloqueada']);
        exit;
    }

    // 5) Construir el array de maps para ESTE producto (append si ya existe)
    $existing = [];
    if ($lineDoc && isset($lineDoc['fields'][$cveArt]['arrayValue']['values'])) {
        $existing = $lineDoc['fields'][$cveArt]['arrayValue']['values']; // mantener histórico
    }

    // Normalizar lotes del payload → arrayValue.values de maps
    foreach ($lotesIn as $row) {
        $existing[] = [
            'mapValue' => [
                'fields' => [
                    'corrugados'       => ['integerValue' => (int)($row['corrugados'] ?? 0)],
                    'sueltos'       => ['integerValue' => (int)($row['sueltos'] ?? 0)],
                    'corrugadosPorCaja' => ['integerValue' => (int)($row['cajasPorCorrugado'] ?? $row['corrugadosPorCaja'] ?? 0)],
                    'lote'             => ['stringValue'  => (string)($row['lote'] ?? '')],
                    'total'            => ['integerValue' => (int)($row['total'] ?? $row['piezas'] ?? 0)],
                ]
            ]
        ];
    }

    // 6) Preparar PATCH SOLO del campo del producto (y algunos metadatos útiles)
    $escapedProductField = escape_field_path($cveArt); // ej: `AA-1613`
    $patchUrl = $lineDocUrl
        . '&updateMask.fieldPaths=' . rawurlencode($escapedProductField)
        . '&updateMask.fieldPaths=status'
        . '&updateMask.fieldPaths=updatedAt'
        . '&updateMask.fieldPaths=lastProduct'
        . '&updateMask.fieldPaths=conteoTotal'
        . '&updateMask.fieldPaths=diferencia'
        . '&updateMask.fieldPaths=existSistema'
        . '&updateMask.fieldPaths=descr';

    $payloadFirestore = [
        'fields' => [
            'status' => ['booleanValue' => true],
            // Campo por clave de producto → array de maps
            $cveArt => [
                'arrayValue' => ['values' => $existing]
            ],
        ]
    ];

    // 7) Ejecutar PATCH (crea el doc si no existía)
    $patchResp = http_patch_json($patchUrl, $payloadFirestore);
    if (!$patchResp) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar en Firestore']);
        exit;
    }

    echo json_encode([
        'success'  => true,
        'docPath'  => "INVENTARIO/$invDocId/lineas/$lineaId",
        'product'  => $cveArt,
        'updated'  => true
    ]);
}
//////////////////////////GUARDAR PRODUCTO/////////////////////////////////////////
function iniciarInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey, $noInventario)
{
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México
    $fechaCreacion = date("Y-m-d H:i:s"); // Fecha y hora actual
    $fields = [
        'conteo'       => ['integerValue' => 1],
        'fechaInicio'     => ['stringValue' => $fechaCreacion],
        'noEmpresa'  => ['integerValue' => $noEmpresa],
        'noInventario'     => ['integerValue' => 2],
        'status' => ['booleanValue' => true],
    ];

    // Finalmente, enviamos todo a Firestore
    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/INVENTARIO?key=$firebaseApiKey";

    $payload = json_encode(['fields' => $fields]);
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => $error['message']]);
        exit;
    }
}
function mostrarInventarios($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    $collection = "INVENTARIO";
    $url = "https://firestore.googleapis.com/v1/projects/"
        . urlencode($firebaseProjectId)
        . "/databases/(default)/documents:runQuery?key="
        . urlencode($firebaseApiKey);

    // Helper para POST JSON
    $postJson = function (array $body) use ($url) {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($body),
                'timeout' => 15,
            ]
        ];
        $ctx  = stream_context_create($options);
        $resp = @file_get_contents($url, false, $ctx);
        return $resp ? json_decode($resp, true) : null;
    };

    // Construir el body de la consulta
    $body = [
        'structuredQuery' => [
            'from' => [['collectionId' => $collection]],
            'where' => [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'noEmpresa'],
                    'op'    => 'EQUAL',
                    'value' => ['integerValue' => $noEmpresa]
                ]
            ]
        ]
    ];

    $respuesta = $postJson($body);
    if (!is_array($respuesta)) {
        return []; // error o sin datos
    }

    // Extraer campos deseados
    $inventarios = [];
    foreach ($respuesta as $item) {
        if (isset($item['document']['fields'])) {
            $f = $item['document']['fields'];
            $inventarios[] = [
                'conteo'      => isset($f['conteo']['integerValue'])      ? (int)$f['conteo']['integerValue']      : null,
                'fechaInicio' => isset($f['fechaInicio']['stringValue'])   ? $f['fechaInicio']['stringValue']        : null,
                'noEmpresa'   => isset($f['noEmpresa']['integerValue'])    ? (int)$f['noEmpresa']['integerValue']    : null,
                'noInventario' => isset($f['noInventario']['integerValue']) ? (int)$f['noInventario']['integerValue'] : null,
                'status'      => isset($f['status']['booleanValue'])       ? (bool)$f['status']['booleanValue']      : null,
            ];
        }
    }
    //return $inventarios;
    echo json_encode(['succes' => true, 'inventarios' => $inventarios]);
}
function obtenerAlmacenistas($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    $collection = "USUARIOS";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "tipoUsuario"],
                                "op" => "EQUAL",
                                "value" => ["stringValue" => "ALMACENISTA"]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ]
            // ← sin limit para traer todos; si te preocupa volumen, usa un límite alto p.ej. 500
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
            'timeout' => 20
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    $items = [];
    if ($response !== false) {
        $resultArray = json_decode($response, true);
        if (is_array($resultArray)) {
            foreach ($resultArray as $row) {
                if (!isset($row['document'])) continue;
                $doc    = $row['document'];
                $fields = $doc['fields'] ?? [];
                $parts  = explode('/', $doc['name']);
                $id     = end($parts);

                $items[] = [
                    'idUsuario' => $id,
                    'usuario'   => $fields['usuario']['stringValue'] ?? null,
                    'nombre'    => $fields['nombre']['stringValue'] ?? null,
                    // agrega lo que necesites (email, activo, etc.)
                ];
            }
        }
    }
    return $items;
}
//////////////////////////GUARDAR ASIGNACION/////////////////////////////////////////
function http_patch_jsonAsignacion($url, $bodyArr)
{
    $opts = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'PATCH',
            'content' => json_encode($bodyArr),
            'timeout' => 20
        ]
    ];
    $ctx  = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false ? json_decode($resp, true) : null;
}
function firestore_runQueryAsignacion($projectId, $apiKey, $structuredQuery)
{
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:runQuery?key=$apiKey";
    $opts = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(['structuredQuery' => $structuredQuery]),
            'timeout' => 20
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return null;
    return json_decode($resp, true);
}
function getInventarioDocByFolioAsignacion($noEmpresa, $noInventario, $projectId, $apiKey)
{
    $q = [
        "from" => [["collectionId" => "INVENTARIO"]],
        "where" => [
            "compositeFilter" => [
                "op" => "AND",
                "filters" => [
                    ["fieldFilter" => [
                        "field" => ["fieldPath" => "status"],
                        "op" => "EQUAL",
                        "value" => ["booleanValue" => true]
                    ]],
                    ["fieldFilter" => [
                        "field" => ["fieldPath" => "noEmpresa"],
                        "op" => "EQUAL",
                        "value" => ["integerValue" => (int)$noEmpresa]
                    ]],
                    ["fieldFilter" => [
                        "field" => ["fieldPath" => "noInventario"],
                        "op" => "EQUAL",
                        "value" => ["integerValue" => (int)$noInventario]
                    ]]
                ]
            ]
        ],
        "limit" => 1
    ];
    $res = firestore_runQueryAsignacion($projectId, $apiKey, $q);
    if (!$res || !isset($res[0]['document'])) return null;
    $doc = $res[0]['document'];
    $nameParts = explode('/', $doc['name']);
    $docId = end($nameParts);
    return ['docId' => $docId];
}
function guardarAsignaciones($noEmpresa, $noInventario, array $asignaciones, $projectId, $apiKey)
{
    // Helpers mínimos (usa los tuyos si ya existen)
    $root = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";

    // 1) Resolver inventario activo por empresa + folio
    $inv = getInventarioDocByFolioAsignacion((int)$noEmpresa, (int)$noInventario, $projectId, $apiKey);
    if (!$inv || empty($inv['docId'])) {
        return ['success' => false, 'message' => 'Inventario activo no encontrado'];
    }
    $invDocId = $inv['docId'];

    // 2) Construir mapValue para "asignaciones" con arrays
    //    asignaciones: { "001": ["uidA","uidB"], "002": ["uidC"] }
    $mapFields = [];
    foreach ($asignaciones as $lineaId => $uids) {
        if ($lineaId === '' || !is_array($uids)) continue;

        // limitar a 2
        $uids = array_values(array_filter($uids, fn($u) => is_string($u) && $u !== ''));
        $uids = array_slice($uids, 0, 2);

        // arrayValue de stringValue
        $arrVals = array_map(fn($u) => ['stringValue' => $u], $uids);
        $mapFields[(string)$lineaId] = [
            'arrayValue' => ['values' => $arrVals]
        ];
    }

    // 3) PATCH campo "asignaciones" (sobrescribe con lo enviado)
    $urlAsign = "$root/INVENTARIO/$invDocId?key=$apiKey&updateMask.fieldPaths=asignaciones";
    $bodyAsign = [
        'fields' => [
            'asignaciones' => [
                'mapValue' => ['fields' => $mapFields]
            ]
        ]
    ];
    $resp1 = http_patch_jsonAsignacion($urlAsign, $bodyAsign);
    if (!$resp1) {
        return ['success' => false, 'message' => 'No se pudo guardar asignaciones en el documento de inventario'];
    }

    // 4) Espejo por conteo: lineas (1er asignado) y lineas02 (2º asignado)
    foreach ($asignaciones as $lineaId => $uids) {
        $uids = array_values(array_filter($uids, fn($u) => is_string($u) && $u !== ''));
        $uids = array_slice($uids, 0, 2);

        $ts = gmdate('c');

        // 4.1 Primer asignado -> lineas/{lineaId}
        if (isset($uids[0])) {
            $urlL1 = "$root/INVENTARIO/$invDocId/lineas/$lineaId?key=$apiKey&updateMask.fieldPaths=idAsignado&updateMask.fieldPaths=conteo&updateMask.fieldPaths=updatedAt";
            $bodyL1 = [
                'fields' => [
                    'idAsignado' => ['stringValue'  => $uids[0]],
                    'conteo'        => ['integerValue' => 1],
                    'updatedAt'     => ['timestampValue' => $ts]
                ]
            ];
            http_patch_jsonAsignacion($urlL1, $bodyL1); // ignorar error puntual
        }

        // 4.2 Segundo asignado -> lineas02/{lineaId}
        if (isset($uids[1])) {
            $urlL2 = "$root/INVENTARIO/$invDocId/lineas02/$lineaId?key=$apiKey&updateMask.fieldPaths=idAsignado&updateMask.fieldPaths=conteo&updateMask.fieldPaths=updatedAt";
            $bodyL2 = [
                'fields' => [
                    'idAsignado' => ['stringValue'  => $uids[1]],
                    'conteo'        => ['integerValue' => 2],
                    'updatedAt'     => ['timestampValue' => $ts]
                ]
            ];
            http_patch_jsonAsignacion($urlL2, $bodyL2);
        } else {
            // Si no hay segundo, elimina el doc en lineas02 para esta línea (opción recomendada)
            $delUrl = "$root/INVENTARIO/$invDocId/lineas02/$lineaId?key=$apiKey";
            http_delete_simple($delUrl);
            // Alternativa: dejar doc vacío con PATCH si prefieres no borrar
        }
    }

    return ['success' => true];
}
function http_delete_simple($url)
{
    $opts = ['http' => ['method' => 'DELETE', 'timeout' => 20]];
    $ctx  = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false;
}
//////////////////////////GUARDAR ASIGNACION/////////////////////////////////////////
function obtenerEstadoLineas()
{
    //
}
////////////////////////////////////////////////////////////////////////////////////
function sa_project_id(string $saPath): string {
    if (!is_file($saPath)) throw new Exception("No encuentro JSON en $saPath");
    $sa = json_decode(file_get_contents($saPath), true, 512, JSON_THROW_ON_ERROR);
    if (empty($sa['project_id'])) throw new Exception('El JSON no trae project_id');
    return $sa['project_id'];
}
function gcs_bucket_exists(string $bucket, string $accessToken): array {
    // Devuelve [ok(bool), http_code(int), raw(string)]
    $url = "https://storage.googleapis.com/storage/v1/b/{$bucket}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$accessToken],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code === 200, $code, $res ?: ''];
}
function send_json($arr, int $code = 200)
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit; // <- IMPORTANTE: corta aquí
}
function gcsUploadMultipart(string $bucket, string $objectName, string $filePath, string $contentType, string $accessToken, array $extraMetadata = []): array
{
    if (!is_file($filePath)) throw new Exception("Archivo no encontrado: $filePath");

    $meta = [
        'name'        => $objectName,
        'contentType' => $contentType,
    ];
    if ($extraMetadata) $meta['metadata'] = $extraMetadata;

    $boundary  = 'gcs-' . bin2hex(random_bytes(8));
    $fileBytes = file_get_contents($filePath);
    if ($fileBytes === false) throw new Exception("No se pudo leer el archivo: $filePath");

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= json_encode($meta, JSON_UNESCAPED_SLASHES) . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: $contentType\r\n\r\n";
    $body .= $fileBytes . "\r\n";
    $body .= "--$boundary--\r\n";

    $url = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=multipart";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Content-Length: ' . strlen($body),
        ],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $res  = curl_exec($ch);
    if ($res === false) throw new Exception('cURL multipart: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Multipart HTTP $code: $res");

    return json_decode($res, true);
}
function subirImagenesPorProducto(
    string $saPath,
    string $bucketName,
    ?array $filesArray,
    $linea,
    $noInventario,
    $CVE_ART
): array {
    if (!extension_loaded('curl'))    throw new Exception('PHP sin extensión cURL');
    if (!extension_loaded('openssl')) throw new Exception('PHP sin extensión OpenSSL');
    if (!is_file($saPath))            throw new Exception('No encuentro JSON en ' . $saPath);
    if (!$linea)                     throw new Exception('Falta linea');
    if (empty($filesArray) || empty($filesArray['name'])) throw new Exception('No llegaron archivos');

    $token = getAccessTokenFromServiceAccount($saPath);
    $names = (array)$filesArray['name'];
    $tmps  = (array)$filesArray['tmp_name'];
    $sizes = (array)$filesArray['size'];
    $types = (array)$filesArray['type'];

    $subidos = [];
    foreach ($tmps as $i => $tmp) {
        if (!is_uploaded_file($tmp)) continue;

        $origName   = $names[$i] ?? 'archivo';
        $safeName   = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);

        // Detecta MIME simple (PDF o imagen)
        $mime = $types[$i] ?? 'application/octet-stream';
        $ext  = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $mime = 'application/pdf';
        } elseif (in_array($ext, ['jpg', 'jpeg'])) {
            $mime = 'image/jpeg';
        } elseif ($ext === 'png') {
            $mime = 'image/png';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        }

        // Carpeta por tu lógica:
        // Inventario/{noInventario}/{CVE_ART}/PED{numero}/{YYYY}/{mm}/{dd}/ts_rand_nombre.ext
        $ts   = time();
        $rand = bin2hex(random_bytes(4));
        $objectName = "Inventario/{$noInventario}/{$linea}/{$CVE_ART}/{$safeName}";

        $dlTok = uuidv4();

        $objInfo = gcsUploadMultipart(
            $bucketName,
            $objectName,
            $tmp,
            $mime,
            $token,
            ['firebaseStorageDownloadTokens' => $dlTok]
        );

        $downloadURL = "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/" .
            rawurlencode($objectName) . "?alt=media&token={$dlTok}";

        $subidos[] = [
            'name'        => $origName,
            'path'        => $objectName,
            'size'        => $sizes[$i] ?? null,
            'downloadURL' => $downloadURL,
            'gcs'         => $objInfo,
        ];
    }

    return ['success' => true, 'count' => count($subidos), 'files' => $subidos];
}
function b64url($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function getAccessTokenFromServiceAccount(string $saPath, string $scope = 'https://www.googleapis.com/auth/devstorage.read_write'): string
{
    if (!is_file($saPath)) {
        throw new Exception("Service Account JSON no encontrado en $saPath");
    }
    $sa = json_decode(file_get_contents($saPath), true, 512, JSON_THROW_ON_ERROR);
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $now = time();
    $claims = [
        'iss'   => $sa['client_email'],
        'scope' => $scope,
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600
    ];
    $jwtUnsigned = b64url(json_encode($header)) . '.' . b64url(json_encode($claims));

    // Firmar con la clave privada del JSON
    $privateKey = openssl_pkey_get_private($sa['private_key']);
    if (!$privateKey) throw new Exception('No se pudo leer la clave privada del JSON');
    $signature = '';
    if (!openssl_sign($jwtUnsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new Exception('Fallo al firmar JWT');
    }
    //openssl_pkey_free($privateKey);
    $jwt = $jwtUnsigned . '.' . b64url($signature);

    // Intercambiar el JWT por un access_token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    $post = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt
    ]);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('Error cURL token: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Token HTTP $code: $res");
    $json = json_decode($res, true);
    if (empty($json['access_token'])) throw new Exception('No se recibió access_token');
    return $json['access_token'];
}
// Subida RESUMABLE (dos pasos): inicia sesión + sube bytes
function gcsUploadResumable(string $bucket, string $objectName, string $filePath, string $contentType, string $accessToken, array $extraMetadata = []): array
{
    if (!is_file($filePath)) throw new Exception("Archivo no encontrado: $filePath");
    $metadata = array_merge([
        'name' => $objectName,
        'contentType' => $contentType,
    ], $extraMetadata ? ['metadata' => $extraMetadata] : []);

    // 1) Iniciar sesión de subida
    $initUrl = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=resumable";
    $ch = curl_init($initUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: ' . $contentType
        ],
        CURLOPT_POSTFIELDS => json_encode($metadata, JSON_UNESCAPED_SLASHES),
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) throw new Exception('Error cURL init: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Init HTTP $code: $resp");

    // Extraer header Location
    $location = null;
    foreach (explode("\r\n", $resp) as $h) {
        if (stripos($h, 'Location:') === 0) {
            $location = trim(substr($h, 9));
            break;
        }
    }
    if (!$location) throw new Exception('No se recibió header Location para la subida resumible');

    // 2) Subir bytes del archivo
    $fp = fopen($filePath, 'rb');
    $ch = curl_init($location);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: ' . $contentType
        ],
        CURLOPT_INFILE => $fp,
        CURLOPT_INFILESIZE => filesize($filePath),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 0, // sin límite (o ajusta)
    ]);
    $uploadRes = curl_exec($ch);
    if ($uploadRes === false) {
        fclose($fp);
        throw new Exception('Error cURL upload: ' . curl_error($ch));
    }
    $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($uploadCode !== 200 && $uploadCode !== 201) {
        throw new Exception("Upload HTTP $uploadCode: $uploadRes");
    }
    $obj = json_decode($uploadRes, true);
    return $obj ?: ['raw' => $uploadRes];
}
function uuidv4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
function subirImagenes($usuarioId) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 1) Localiza tu service account
        $saPath = __DIR__.'/../../Cliente/keys/firebase-adminsdk.json'; // <-- verifica la ruta

        // 2) Deriva project y bucket desde el SA (evita hardcodear)
        $projectId  = sa_project_id($saPath);
        $bucketName = $projectId . '.firebasestorage.app';           // p.ej. mdconnecta-4aeb4.appspot.com

        // 3) Token OAuth con scope de GCS
        $token = getAccessTokenFromServiceAccount($saPath);

        // 4) Verifica existencia del bucket con el MISMO token (diagnóstico claro)
        [$ok, $code, $raw] = gcs_bucket_exists($bucketName, $token);
        if (!$ok) {
            // 404 => el bucket no existe (o no hay permisos y GCS oculta con 404)
            // 403 => tienes token pero sin permisos sobre ese bucket
            $msg = ($code === 404)
                ? "El bucket {$bucketName} no existe en el proyecto {$projectId}. Abre Firebase Console » Storage y crea/habilita el bucket por primera vez."
                : "No hay acceso al bucket {$bucketName} (HTTP {$code}). Revisa que el SA pertenezca al MISMO proyecto y tenga permisos de Storage Admin.";
            echo json_encode(['success'=>false, 'message'=>$msg, 'http'=>$code, 'raw'=>$raw], JSON_UNESCAPED_UNICODE);
            return;
        }

        // === ENTRADAS ===
        $linea        = $_POST['linea']        ?? null;
        $noInventario = $_POST['noInventario'] ?? null;
        $cve_art      = $_POST['cve_art']      ?? null;
        $tipo         = $_POST['tipo']         ?? null;
        $filesArr     = $_FILES['pdfs'] ?? ($_FILES['pdfs[]'] ?? null);

        if (!$linea)        throw new Exception('Falta "linea"');
        if (!$filesArr || empty($filesArr['name'])) throw new Exception('No llegaron archivos (pdfs[])');

        // El resto de tu lógica: decide carpeta y llama a gcsUploadMultipart(...)
        // Ejemplo (producto):
        if ($tipo === 'producto') {
            if (!$noInventario) throw new Exception('Falta "noInventario"');
            if (!$cve_art)      throw new Exception('Falta "cve_art"');

            $res = subirImagenesPorProducto($saPath, $bucketName, $filesArr, $linea, $noInventario, $cve_art);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            return;
        }

        // Por línea:
        if ($tipo === 'linea') {
            if (!$noInventario) throw new Exception('Falta "noInventario"');
            $res = subirImagenesPorProducto($saPath, $bucketName, $filesArr, $linea, $noInventario, "LINEA-{$linea}");
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            return;
        }

        // Default
        if (!$noInventario) throw new Exception('Falta "noInventario"');
        $res = subirImagenesPorProducto($saPath, $bucketName, $filesArr, $linea, $noInventario, $cve_art ?: "LINEA-{$linea}");
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        return;

    } catch (Throwable $e) {
        echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
        return;
    }
}

/*function subirImagenes($usuarioId){

    // Verifica que se haya enviado al menos un archivo
    if (!isset($_FILES['imagen']) || empty($_FILES['imagen']['name'])) {
        echo json_encode(['success' => false, 'message' => 'No se pudo subir ninguna imagen.']);
        exit;
    }

    // Verifica que se haya enviado la clave del artículo
    if (!isset($_POST['cveArt'])) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionó la clave del artículo.']);
        exit;
    }

    $cveArt = $_POST['cveArt'];
    $imagenes = $_FILES['imagen'];
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app"; // Cambia esto por tu bucket 

    // Subir y procesar cada archivo
    $rutasImagenes = [];
    foreach ($imagenes['tmp_name'] as $index => $tmpName) {
        if ($imagenes['error'][$index] === UPLOAD_ERR_OK) {
            $nombreArchivo = $cveArt . "_" . uniqid() . "_" . basename($imagenes['name'][$index]);
            $rutaFirebase = "imagenes/{$cveArt}/{$nombreArchivo}";
            $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?name=" . urlencode($rutaFirebase);

            // Leer el archivo
            $archivo = file_get_contents($tmpName);

            // Subir el archivo a Firebase Storage
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/octet-stream"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $archivo);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $resultado = json_decode($response, true);

            if (isset($resultado['name'])) {
                $urlPublica = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/{$resultado['name']}?alt=media";
                $rutasImagenes[] = $urlPublica;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir una imagen.', 'response' => $response]);
                exit;
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Imágenes subidas correctamente.', 'imagenes' => $rutasImagenes]);
}*/

// -----------------------------------------------------------------------------------------------------//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    // Si es una solicitud POST, asignamos el valor de numFuncion
    $funcion = $_POST['numFuncion'];
    //var_dump($funcion);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    // Si es una solicitud GET, asignamos el valor de numFuncion
    $funcion = $_GET['numFuncion'];
    //var_dump($funcion);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la peticion.']);
    exit;
}
switch ($funcion) {
    case 1:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $result = buscarInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        echo json_encode($result);
        break;
    case 2:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $noInventario = noInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        echo json_encode(['success' => true, 'noInventario' => $noInventario]);
        break;
    case 3:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        obtenerLineas($claveSae, $conexionData);
        break;
    case 4:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        $linea = $_GET['linea'];
        obtenerProductosPorLinea($claveSae, $conexionData, $linea);
        break;
    case 5:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $noInventario = noInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        //echo json_encode(['success' => true, 'noInventario' => $noInventario]);
        guardarProducto($noEmpresa, $noInventario, $firebaseProjectId, $firebaseApiKey);
        break;
    case 6:
        $noEmpresa       = $_SESSION['empresa']['noEmpresa'];
        $firebaseProject = $firebaseProjectId;
        $apiKey          = $firebaseApiKey;

        // Guardamos el array de respuesta en una variable
        $inventario = buscarInventario($noEmpresa, $firebaseProject, $apiKey);

        // Accedemos a las propiedades del array
        if ($inventario['success'] && $inventario['foundActive']) {
            echo json_encode([
                'success' => false,
                'message' => 'Hay un inventario activo',
                'docId'   => $inventario['docId']
            ]);
        } else {
            // No había activo, iniciamos uno nuevo
            $noInventario = noInventario($noEmpresa, $firebaseProject, $apiKey);
            iniciarInventario($noEmpresa, $firebaseProject, $apiKey, $noInventario);

            echo json_encode([
                'success' => true,
                'message' => 'Inventario Iniciado',
                'newNoInventario' => $noInventario
            ]);
        }
        break;
    case 7:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        mostrarInventarios($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        break;
    case 8:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $linea = $_GET['linea'];
        $noInventario = $_GET['noInventario'];
        obtenerProductoGuardado($noEmpresa, $firebaseProjectId, $firebaseApiKey, $linea, $noInventario);
        break;
    case 9:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $items = obtenerAlmacenistas($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        echo json_encode(['success' => true, 'data' => $items]);
        break;
    case 10: // Guardar asignaciones de líneas -> campo "asignaciones" en INVENTARIO
        $noEmpresa    = (int)$_SESSION['empresa']['noEmpresa'];
        $payload = json_decode($_POST['payload'] ?? 'null', true);
        $noInventario = 1;
        if (!$noInventario || !$payload || !isset($payload['asignaciones']) || !is_array($payload['asignaciones'])) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            exit;
        }
        $resp = guardarAsignaciones($noEmpresa, $noInventario, $payload['asignaciones'], $firebaseProjectId, $firebaseApiKey);
        echo json_encode($resp);
        break;
    case 11:
        obtenerEstadoLineas();
        break;
    case 12:
        $usuarioId = $_SESSION['usuario']['idReal'];
        subirImagenes($usuarioId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
