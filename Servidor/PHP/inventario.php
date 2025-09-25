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
function obtenerProductoGuardado($noEmpresa, $firebaseProjectId, $firebaseApiKey, $linea, $noInventario, $usuarioId)
{
    if (!$noEmpresa || !$linea || !$noInventario) {
        return ['success' => false, 'message' => 'Parámetros incompletos'];
    }

    // 1) Inventario activo por empresa+folio
    $inv = getInventarioDocByFolio((int)$noEmpresa, (int)$noInventario, $firebaseProjectId, $firebaseApiKey);
    if (!$inv || empty($inv['docId'])) {
        return ['success' => false, 'message' => 'Inventario activo no encontrado'];
    }
    $invDocId = $inv['docId'];

    $root = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
    // 2) Revisar posibles subcolecciones (por si hay 2 asignados / varios conteos)
    $subcols = ['lineas', 'lineas02', 'lineas03', 'lineas04', 'lineas05', 'lineas06'];

    $lineDoc = null;
    $subcolUsada = null;
    foreach ($subcols as $sc) {
        $url = "$root/INVENTARIO/$invDocId/$sc/$linea?key=$firebaseApiKey";
        $doc = http_get_json($url);
        if (!isset($doc['fields'])) continue;

        // Normalizar idAsignado: puede ser string/integer o array de strings
        $assigned = [];
        $f = $doc['fields'];
        if (isset($f['idAsignado']['stringValue'])) {
            $assigned[] = (string)$f['idAsignado']['stringValue'];
        } elseif (isset($f['idAsignado']['integerValue'])) {
            $assigned[] = (string)$f['idAsignado']['integerValue'];
        } elseif (isset($f['idAsignado']['arrayValue']['values']) && is_array($f['idAsignado']['arrayValue']['values'])) {
            foreach ($f['idAsignado']['arrayValue']['values'] as $v) {
                if (isset($v['stringValue']))   $assigned[] = (string)$v['stringValue'];
                if (isset($v['integerValue']))  $assigned[] = (string)$v['integerValue'];
            }
        }

        $match = in_array((string)$usuarioId, $assigned, true);
        if ($match) { // nos quedamos con el doc que pertenece a este usuario
            $lineDoc = $doc;
            $subcolUsada = $sc;
            break;
        }
        // si no hay match, seguimos buscando en la siguiente subcolección
    }

    // Si no encontramos un doc de esa línea asignado a este usuario:
    if (!$lineDoc) {
        echo json_encode([
            'success'    => true,
            'linea'      => (string)$linea,
            'locked'     => false,           // si status=false, bloqueada
            'conteo'     => null,
            'finishedAt' => null,
            'lockedBy'   => null,
            'productos'  => null,
            'activa'     => false,            // para tu UI
            'coleccion'  => $subcolUsada        // útil para depurar
        ]);
        return;
        /*return [
            'success'    => true,
            'linea'      => (string)$linea,
            'locked'     => true,     // bloquear edición
            'conteo'     => null,
            'finishedAt' => null,
            'lockedBy'   => null,
            'productos'  => [],
            'activa'     => false,    // UI en solo lectura
            'coleccion'  => null
        ];*/
    }

    $fields = $lineDoc['fields'];

    // 3) Metadata
    $status     = isset($fields['status']['booleanValue']) ? (bool)$fields['status']['booleanValue'] : true; // true=editable
    $conteo     = isset($fields['conteo']['integerValue']) ? (int)$fields['conteo']['integerValue'] : null;
    $finishedAt = isset($fields['finishedAt']['timestampValue']) ? (string)$fields['finishedAt']['timestampValue'] : null;
    $lockedBy   = isset($fields['lockedBy']['stringValue']) ? (string)$fields['lockedBy']['stringValue'] : null;

    // 4) Campos reservados (no productos)
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
        'linesStatus',
        'idAsignado',
        'status'
    ];

    // 5) Transformar productos
    $productos = [];
    foreach ($fields as $clave => $valor) {
        if (in_array($clave, $reservados, true)) continue;
        if (!isset($valor['arrayValue']['values']) || !is_array($valor['arrayValue']['values'])) continue;

        $lotes = [];
        $sumaTotal = 0;
        foreach ($valor['arrayValue']['values'] as $entry) {
            if (!isset($entry['mapValue']['fields'])) continue;
            $f = $entry['mapValue']['fields'];

            $corr  = isset($f['corrugados']['integerValue'])        ? (int)$f['corrugados']['integerValue']        : 0;
            $sult  = isset($f['sueltos']['integerValue'])           ? (int)$f['sueltos']['integerValue']           : 0;
            $cxc   = isset($f['corrugadosPorCaja']['integerValue']) ? (int)$f['corrugadosPorCaja']['integerValue'] : 0;
            $lote  = isset($f['lote']['stringValue'])               ? (string)$f['lote']['stringValue']            : '';
            $total = isset($f['total']['integerValue'])             ? (int)$f['total']['integerValue']             : ($corr * $cxc + $sult);

            $sumaTotal += $total;
            $lotes[] = [
                'corrugados'        => $corr,
                'corrugadosPorCaja' => $cxc,
                'lote'              => $lote,
                'total'             => $total,
                'sueltos'           => $sult,
            ];
        }

        $productos[] = [
            'cve_art'     => $clave,
            'conteoTotal' => $sumaTotal,
            'lotes'       => $lotes
        ];
    }
    //var_dump($productos);

    // 6) Respuesta
    echo json_encode([
        'success'    => true,
        'linea'      => (string)$linea,
        'locked'     => $status,           // si status=false, bloqueada
        'conteo'     => $conteo,
        'finishedAt' => $finishedAt,
        'lockedBy'   => $lockedBy,
        'productos'  => $productos,
        'activa'     => $status,            // para tu UI
        'coleccion'  => $subcolUsada        // útil para depurar
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
function actualizarInventario($noEmpresa, $firebaseProject, $apiKey)
{
    $collection = "FOLIOS";

    // Construimos la ruta del documento usando runQuery para obtener el nombre completo del doc
    $queryUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProject/databases/(default)/documents:runQuery?key=$apiKey";
    $payloadQuery = json_encode([
        "structuredQuery" => [
            "from" => [["collectionId" => $collection]],
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

    $opts = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payloadQuery,
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($queryUrl, false, $ctx);
    if ($resp === false) return false;

    $arr = json_decode($resp, true);
    if (!isset($arr[0]['document']['name'])) return false;

    // Nombre completo del documento: projects/.../databases/(default)/documents/FOLIOS/{docId}
    $documentName = $arr[0]['document']['name'];

    // Usamos el endpoint commit para aplicar un fieldTransform increment atómico
    $commitUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProject/databases/(default)/documents:commit?key=$apiKey";

    $payloadCommit = json_encode([
        "writes" => [
            [
                "transform" => [
                    "document" => $documentName,
                    "fieldTransforms" => [
                        [
                            "fieldPath" => "folioSiguiente",
                            "increment" => ["integerValue" => "1"]
                        ]
                    ]
                ]
            ]
        ],
        // opcional: returnTransaction puede omitirse
    ]);

    $optsCommit = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payloadCommit,
        ]
    ];
    $ctxCommit = stream_context_create($optsCommit);
    $respCommit = @file_get_contents($commitUrl, false, $ctxCommit);
    if ($respCommit === false) return false;

    $resCommitArr = json_decode($respCommit, true);

    // Si quieres devolver true/false:
    return isset($resCommitArr['writeResults']) ? true : false;
}
function actualizarFolio($firebaseProjectId, $firebaseApiKey, $noEmpresa)
{
    $collection = "FOLIOS";

    // Construimos la ruta del documento usando runQuery para obtener el nombre completo del doc
    $queryUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProject/databases/(default)/documents:runQuery?key=$apiKey";
    $payloadQuery = json_encode([
        "structuredQuery" => [
            "from" => [["collectionId" => $collection]],
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

    $opts = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payloadQuery,
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($queryUrl, false, $ctx);
    if ($resp === false) return false;

    $arr = json_decode($resp, true);
    if (!isset($arr[0]['document']['name'])) return false;

    // Nombre completo del documento: projects/.../databases/(default)/documents/FOLIOS/{docId}
    $documentName = $arr[0]['document']['name'];

    // Usamos el endpoint commit para aplicar un fieldTransform increment atómico
    $commitUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProject/databases/(default)/documents:commit?key=$apiKey";

    $payloadCommit = json_encode([
        "writes" => [
            [
                "transform" => [
                    "document" => $documentName,
                    "fieldTransforms" => [
                        [
                            "fieldPath" => "folioSiguiente",
                            "decrement" => ["integerValue" => "1"]
                        ]
                    ]
                ]
            ]
        ],
        // opcional: returnTransaction puede omitirse
    ]);

    $optsCommit = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payloadCommit,
        ]
    ];
    $ctxCommit = stream_context_create($optsCommit);
    $respCommit = @file_get_contents($commitUrl, false, $ctxCommit);
    if ($respCommit === false) return false;

    $resCommitArr = json_decode($respCommit, true);

    // Si quieres devolver true/false:
    return isset($resCommitArr['writeResults']) ? true : false;
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
    date_default_timezone_set('America/Mexico_City');
    $fechaCreacion = date("Y-m-d H:i:s");

    $fields = [
        'conteo'       => ['integerValue' => 1],
        'fechaInicio'  => ['stringValue' => $fechaCreacion],
        'noEmpresa'    => ['integerValue' => $noEmpresa],
        'noInventario' => ['integerValue' => $noInventario],
        'status'       => ['booleanValue' => true],
    ];

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
        return ['success' => false, 'message' => $error['message']];
    }

    $data = json_decode($response, true);
    if (isset($data['name'])) {
        // Extraer el docId del campo "name"
        $parts = explode('/', $data['name']);
        $docId = end($parts);

        return [
            'success' => true,
            'docId'   => $docId,
            'data'    => $data
        ];
    } else {
        return ['success' => false, 'message' => 'No se pudo obtener ID de inventario'];
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
    echo json_encode(['success' => true, 'inventarios' => $inventarios]);
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
    $root = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";

    // ==== 1) Resolver inventario ====
    $inv = getInventarioDocByFolioAsignacion((int)$noEmpresa, (int)$noInventario, $projectId, $apiKey);
    if (!$inv || empty($inv['docId'])) {
        return ['success' => false, 'message' => 'Inventario activo no encontrado'];
    }
    $invDocId = $inv['docId'];

    // ==== 2) Cargar asignaciones actuales ====
    $invDocUrl = "$root/INVENTARIO/$invDocId?key=$apiKey";
    $invDoc    = http_get_json($invDocUrl);
    $currMap   = []; // lineaId -> [uid0, uid1]
    if (!empty($invDoc['fields']['asignaciones']['mapValue']['fields'])) {
        foreach ($invDoc['fields']['asignaciones']['mapValue']['fields'] as $lin => $arr) {
            $uids = [];
            if (!empty($arr['arrayValue']['values'])) {
                foreach ($arr['arrayValue']['values'] as $v) {
                    if (isset($v['stringValue']) && $v['stringValue'] !== '') {
                        $uids[] = $v['stringValue'];
                    }
                }
            }
            $currMap[$lin] = array_slice($uids, 0, 2);
        }
    }

    // ==== 3) Normalizar nuevas asignaciones (máx 2 por línea) ====
    $newMap = [];
    foreach ($asignaciones as $lineaId => $uids) {
        if ($lineaId === '' || !is_array($uids)) continue;
        $uids = array_values(array_filter($uids, fn($u) => is_string($u) && $u !== ''));
        $newMap[(string)$lineaId] = array_slice($uids, 0, 2);
    }

    // ==== Helpers: detectar si el doc de una subcolección tiene productos guardados ====
    $reservados = [
        'locked','conteo','finishedAt','lockedBy','updatedAt','lastProduct',
        'conteoTotal','diferencia','existSistema','descr','linesStatus','status','idAsignado'
    ];
    $hasProducts = function(array $doc) use ($reservados): bool {
        if (empty($doc['fields']) || !is_array($doc['fields'])) return false;
        foreach ($doc['fields'] as $k => $val) {
            if (in_array($k, $reservados, true)) continue;
            // Producto esperado como arrayValue de maps (lotes)
            if (!empty($val['arrayValue']['values']) && is_array($val['arrayValue']['values'])) {
                if (count($val['arrayValue']['values']) > 0) return true;
            }
        }
        return false;
    };

    // ==== 4) Aplicar cambios por línea/slot respetando reglas ====
    $tsIso   = gmdate('c');
    $errors  = [];
    $finalMap = $currMap; // partimos del estado actual y vamos aplicando cambios válidos

    foreach ($newMap as $lineaId => $desiredUids) {
        $old = $currMap[$lineaId] ?? [];
        // normalizar a 2 slots
        $old   = [$old[0] ?? null,   $old[1] ?? null];
        $want  = [$desiredUids[0] ?? null, $desiredUids[1] ?? null];

        // Recorremos slots 0 (lineas) y 1 (lineas02)
        for ($slot=0; $slot<2; $slot++) {
            $subcol = ($slot === 0) ? 'lineas' : 'lineas02';
            $oldUid = $old[$slot];
            $newUid = $want[$slot];

            // No cambio → nada que hacer
            if ($oldUid === $newUid) continue;

            // Situaciones:
            // A) Quitar asignación (old != null y new == null)
            // B) Reemplazar asignación (old != null y new != null y old != new)
            // C) Añadir asignación (old == null y new != null)

            // Cargar doc del slot para ver si tiene captura
            $docUrl = "$root/INVENTARIO/$invDocId/$subcol/$lineaId?key=$apiKey";
            $doc    = http_get_json($docUrl); // puede no existir (null)

            $tieneProductos = $doc && $hasProducts($doc);

            // A) quitar
            if ($oldUid && !$newUid) {
                if ($tieneProductos) {
                    // ❌ Regla 1: no puedes quitar porque hay productos guardados
                    $errors[] = "No se puede quitar la asignación de $subcol/$lineaId: ya hay productos capturados.";
                    // Mantenemos la asignación antigua
                    $finalMap[$lineaId][$slot] = $oldUid;
                    continue;
                }
                // ✓ Eliminar doc (si existe) y liberar slot
                http_delete_simple($docUrl); // ignorar fallo puntual
                $finalMap[$lineaId][$slot] = null;
                continue;
            }

            // B) reemplazar
            if ($oldUid && $newUid && $oldUid !== $newUid) {
                if ($tieneProductos) {
                    // ❌ No puedes reemplazar si ya hay captura
                    $errors[] = "No se puede cambiar la asignación de $subcol/$lineaId: ya hay productos capturados.";
                    // Mantener antiguo
                    $finalMap[$lineaId][$slot] = $oldUid;
                    continue;
                }
                // ✓ No hay productos: borro doc anterior y creo/actualizo con nuevo asignado
                http_delete_simple($docUrl);
                // crear/patch con nuevo idAsignado + conteo
                $patchUrl = "$root/INVENTARIO/$invDocId/$subcol/$lineaId?key=$apiKey"
                          . "&updateMask.fieldPaths=idAsignado"
                          . "&updateMask.fieldPaths=conteo"
                          . "&updateMask.fieldPaths=updatedAt";
                $body = [
                    'fields' => [
                        'idAsignado'   => ['stringValue' => $newUid],
                        'conteo'       => ['integerValue' => ($slot+1)],
                        'updatedAt'    => ['timestampValue' => $tsIso],
                    ]
                ];
                http_patch_jsonAsignacion($patchUrl, $body);
                $finalMap[$lineaId][$slot] = $newUid;
                continue;
            }

            // C) añadir
            if (!$oldUid && $newUid) {
                // ✓ escribir doc con idAsignado
                $patchUrl = "$root/INVENTARIO/$invDocId/$subcol/$lineaId?key=$apiKey"
                          . "&updateMask.fieldPaths=idAsignado"
                          . "&updateMask.fieldPaths=conteo"
                          . "&updateMask.fieldPaths=updatedAt";
                $body = [
                    'fields' => [
                        'idAsignado'   => ['stringValue' => $newUid],
                        'conteo'       => ['integerValue' => ($slot+1)],
                        'updatedAt'    => ['timestampValue' => $tsIso],
                    ]
                ];
                http_patch_jsonAsignacion($patchUrl, $body);
                $finalMap[$lineaId][$slot] = $newUid;
                continue;
            }
        }

        // Limpia nulls finales del arreglo (deja solo existentes)
        $finalMap[$lineaId] = array_values(array_filter($finalMap[$lineaId] ?? [], fn($u)=>!!$u));
        // Asegura límite de 2
        $finalMap[$lineaId] = array_slice($finalMap[$lineaId], 0, 2);
    }

    // También contempla líneas que estaban antes y no vienen en $newMap:
    // Eso equivale a "quitar ambos"; aplica misma regla.
    foreach ($currMap as $lineaId => $oldUids) {
        if (array_key_exists($lineaId, $newMap)) continue; // ya tratada
        $old = [$oldUids[0] ?? null, $oldUids[1] ?? null];
        $want = [null, null];

        for ($slot=0; $slot<2; $slot++) {
            $subcol = ($slot===0)?'lineas':'lineas02';
            $oldUid = $old[$slot];
            if (!$oldUid) continue;

            $docUrl = "$root/INVENTARIO/$invDocId/$subcol/$lineaId?key=$apiKey";
            $doc    = http_get_json($docUrl);
            $tieneProductos = $doc && $hasProducts($doc);

            if ($tieneProductos) {
                // Mantener asignación existente
                $finalMap[$lineaId][$slot] = $oldUid;
                $errors[] = "No se puede quitar la asignación de $subcol/$lineaId: ya hay productos capturados.";
            } else {
                // Borrar doc y liberar
                http_delete_simple($docUrl);
                if (isset($finalMap[$lineaId])) {
                    unset($finalMap[$lineaId][$slot]);
                }
            }
        }

        // Normaliza restantes para esa línea
        if (isset($finalMap[$lineaId])) {
            $finalMap[$lineaId] = array_values(array_filter($finalMap[$lineaId], fn($u)=>!!$u));
            if (count($finalMap[$lineaId])===0) unset($finalMap[$lineaId]);
        }
    }

    // ==== 5) Escribir asignaciones finales al doc INVENTARIO ====
    $mapFields = [];
    foreach ($finalMap as $lineaId => $uids) {
        $uids = array_values(array_filter($uids, fn($u)=>is_string($u)&&$u!==''));
        $uids = array_slice($uids, 0, 2);
        $arrVals = array_map(fn($u)=>['stringValue'=>$u], $uids);
        $mapFields[(string)$lineaId] = ['arrayValue' => ['values' => $arrVals]];
    }

    $urlAsign  = "$root/INVENTARIO/$invDocId?key=$apiKey&updateMask.fieldPaths=asignaciones";
    $bodyAsign = ['fields' => ['asignaciones' => ['mapValue' => ['fields' => $mapFields]]]];
    $resp1 = http_patch_jsonAsignacion($urlAsign, $bodyAsign);
    if (!$resp1) {
        return ['success' => false, 'message' => 'No se pudo guardar asignaciones en el documento de inventario'];
    }

    // ==== 6) Respuesta ====
    if (!empty($errors)) {
        // No es fallo total: se aplicó lo que se pudo, pero hubo bloqueos
        return [
            'success' => true,
            'warnings' => $errors,
            'aplicado' => $finalMap
        ];
    }

    return ['success' => true, 'aplicado' => $finalMap];
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
function sa_project_id(string $saPath): string
{
    if (!is_file($saPath)) throw new Exception("No encuentro JSON en $saPath");
    $sa = json_decode(file_get_contents($saPath), true, 512, JSON_THROW_ON_ERROR);
    if (empty($sa['project_id'])) throw new Exception('El JSON no trae project_id');
    return $sa['project_id'];
}
function gcs_bucket_exists(string $bucket, string $accessToken): array
{
    // Devuelve [ok(bool), http_code(int), raw(string)]
    $url = "https://storage.googleapis.com/storage/v1/b/{$bucket}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
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
function subirImagenes($usuarioId)
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 1) Localiza tu service account
        $saPath = __DIR__ . '/../../Cliente/keys/firebase-adminsdk.json'; // <-- verifica la ruta

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
            echo json_encode(['success' => false, 'message' => $msg, 'http' => $code, 'raw' => $raw], JSON_UNESCAPED_UNICODE);
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        return;
    }
}
function obtenerLineasAsignadas(int $noEmpresa, int $noInventario, string $projectId, string $apiKey): array
{
    try {
        // 1) Resolver el doc del INVENTARIO (usa tu helper existente)
        if (!function_exists('getInventarioDocByFolioAsignacion') && !function_exists('getInventarioDocByFolio')) {
            return ['success' => false, 'message' => 'No existe helper getInventarioDocByFolio*'];
        }
        $inv = function_exists('getInventarioDocByFolioAsignacion')
            ? getInventarioDocByFolioAsignacion($noEmpresa, $noInventario, $projectId, $apiKey)
            : getInventarioDocByFolio($noEmpresa, $noInventario, $projectId, $apiKey);

        if (!$inv || empty($inv['docId'])) {
            return ['success' => false, 'message' => 'Inventario no encontrado'];
        }
        $invDocId = $inv['docId'];

        // 2) Leer el documento de INVENTARIO para tomar el campo "asignaciones"
        $root  = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";
        $url   = "$root/INVENTARIO/$invDocId?key=$apiKey";
        $doc   = http_get_json($url);

        $asig = [];
        $usuariosToFetch = [];

        if (isset($doc['fields']['asignaciones']['mapValue']['fields'])) {
            $map = $doc['fields']['asignaciones']['mapValue']['fields'];

            foreach ($map as $lineaId => $value) {
                // Soporta formato viejo (string) y nuevo (array)
                $uids = [];

                // stringValue → ["uid"]
                if (isset($value['stringValue'])) {
                    $uid = (string)$value['stringValue'];
                    if ($uid !== '') $uids[] = $uid;
                }

                // arrayValue → ["uid1","uid2"]
                if (isset($value['arrayValue']['values']) && is_array($value['arrayValue']['values'])) {
                    foreach ($value['arrayValue']['values'] as $v) {
                        if (!empty($v['stringValue'])) {
                            $uids[] = (string)$v['stringValue'];
                        }
                    }
                }

                // normaliza (máx. 2 por regla)
                $uids = array_values(array_unique($uids));
                if (count($uids) > 2) $uids = array_slice($uids, 0, 2);

                if (!empty($uids)) {
                    $asig[$lineaId] = $uids;
                    foreach ($uids as $u) $usuariosToFetch[$u] = true;
                }
            }
        }

        // 3) (Opcional) Resuelve nombres de usuarios ya asignados
        $usuarios = [];
        foreach (array_keys($usuariosToFetch) as $uid) {
            $u = leerUsuarioPorId($projectId, $apiKey, $uid); // devuelve ['nombre'=>..., 'usuario'=>...]
            if ($u) $usuarios[$uid] = $u;
        }

        // 4) (Opcional) Si quieres, arma un diccionario de descripciones de líneas:
        //    Si ya tienes catálogo de líneas en algún lado, úsalo; si no, lo dejas vacío.
        $lineasDesc = []; // p.ej. ['001'=>'Línea 001', ...] si lo puedes resolver

        return [
            'success'      => true,
            'noInventario' => $noInventario,
            'asignaciones' => $asig,       // { lineaId: [uid, uid?], ... }
            'usuarios'     => $usuarios,   // { uid: {nombre, usuario}, ... }
            'lineasDesc'   => $lineasDesc,
        ];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
function leerUsuarioPorId(string $projectId, string $apiKey, string $userId): ?array
{
    if ($userId === '') return null;
    $root = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";
    $url  = "$root/USUARIOS/" . rawurlencode($userId) . "?key=$apiKey";

    $doc = http_get_jsonAsignaciones($url);
    if (!isset($doc['fields'])) return null;

    $f = $doc['fields'];
    return [
        'nombre'  => $f['nombre']['stringValue']  ?? '',
        'usuario' => $f['usuario']['stringValue'] ?? '',
        // agrega lo que necesites: tipoUsuario, noEmpresa, etc.
    ];
}
function http_get_jsonAsignaciones(string $url): ?array
{
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 30]]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) return null;
    $j = json_decode($res, true);
    return is_array($j) ? $j : null;
}


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
        $inventario = buscarInventario($noEmpresa, $firebaseProject, $apiKey);
        if ($inventario['success'] && $inventario['foundActive']) {
            echo json_encode([
                'success' => false,
                'message' => 'Hay un inventario activo',
                'docId'   => $inventario['docId']
            ]);
        } else {
            $noInventario = noInventario($noEmpresa, $firebaseProject, $apiKey);

            $nuevoInv = iniciarInventario($noEmpresa, $firebaseProject, $apiKey, $noInventario);
            actualizarInventario($noEmpresa, $firebaseProject, $apiKey);

            if ($nuevoInv['success']) {
                echo json_encode([
                    'success'        => true,
                    'message'        => 'Inventario Iniciado',
                    'newNoInventario' => $noInventario,
                    'idInventario'   => $nuevoInv['docId'] // aquí ya regresa el ID
                ]);
            } else {
                echo json_encode($nuevoInv);
            }
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
        $usuarioId = $_SESSION['usuario']['idReal'];
        obtenerProductoGuardado($noEmpresa, $firebaseProjectId, $firebaseApiKey, $linea, $noInventario, $usuarioId);
        break;
    case 9:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $items = obtenerAlmacenistas($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        echo json_encode(['success' => true, 'data' => $items]);
        break;
    case 10: // Guardar asignaciones de líneas -> campo "asignaciones" en INVENTARIO
        $noEmpresa    = (int)$_SESSION['empresa']['noEmpresa'];
        $payload = json_decode($_POST['payload'] ?? 'null', true);
        //$noInventario = 3;
        $result = buscarInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        $noInventario = $result['noInventario'];
        if (!$noInventario || !$payload || !isset($payload['asignaciones']) || !is_array($payload['asignaciones'])) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            exit;
        }
        $resp = guardarAsignaciones($noEmpresa, $noInventario, $payload['asignaciones'], $firebaseProjectId, $firebaseApiKey);
        echo json_encode($resp);
        break;
    case 11:
        $noInventario = $_GET['noInventario'] ?? null;
        if (!$noInventario) {
            echo json_encode(['success' => false, 'message' => 'Falta noInventario']);
            exit;
        }
        $noEmpresa         = (int)($_SESSION['empresa']['noEmpresa'] ?? 0);

        $res = obtenerLineasAsignadas(
            $noEmpresa,
            (int)$noInventario,
            $firebaseProjectId,
            $firebaseApiKey
        );
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;
    case 12:
        $usuarioId = $_SESSION['usuario']['idReal'];
        subirImagenes($usuarioId);
        break;

    case 20: // Verificar finalización de inventario y crear nuevos conteos
    $idInventario = $_POST['idInventario'] ?? null;
    if (!$idInventario) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID de inventario']);
        exit;
    }

    // Obtener cabecera del inventario
    $invUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey";
    $resInv = @file_get_contents($invUrl);
    $docInv = json_decode($resInv, true);

    if (!isset($docInv['fields'])) {
        echo json_encode(['success' => false, 'message' => 'Inventario no encontrado']);
        exit;
    }

    $conteo = (int)($docInv['fields']['conteo']['integerValue'] ?? 1);

    // Calcular par actual según el conteo
    if ($conteo === 1) {
        $currentPair = ["lineas", "lineas02"];
    } else {
        $start = ($conteo - 1) * 2 + 1; // ej. conteo=2 → 3 y 4
        $currentPair = [
            "lineas" . str_pad($start, 2, "0", STR_PAD_LEFT),
            "lineas" . str_pad($start + 1, 2, "0", STR_PAD_LEFT)
        ];
    }

    // Revisar si todas las del par actual tienen status=false
    $allFalse = true;
    foreach ($currentPair as $subcol) {
        $urlSub = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/$subcol?key=$firebaseApiKey";
        $resSub = @file_get_contents($urlSub);
        $docsSub = json_decode($resSub, true);
        if (!isset($docsSub['documents'])) continue;

        foreach ($docsSub['documents'] as $d) {
            $status = $d['fields']['status']['booleanValue'] ?? true;
            if ($status === true) {
                $allFalse = false;
                break 2;
            }
        }
    }

    if (!$allFalse) {
        echo json_encode(['success' => false, 'message' => 'Aún hay líneas activas en ' . implode(", ", $currentPair)]);
        break;
    }

    // Todas finalizadas → verificar si se deben crear nuevas
    $urlConfig = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONFIG/inventarioFisico?key=$firebaseApiKey";
    $resConfig = @file_get_contents($urlConfig);
    $docConfig = json_decode($resConfig, true);
    $generacion = $docConfig['fields']['generacionConteos']['booleanValue'] ?? false;

    if ($generacion) {
        // Calcular siguiente par
        $nextConteo = $conteo + 1;
        $start = ($nextConteo - 1) * 2 + 1;
        $nextPair = [
            "lineas" . str_pad($start, 2, "0", STR_PAD_LEFT),     // subconteo 1
            "lineas" . str_pad($start + 1, 2, "0", STR_PAD_LEFT)  // subconteo 2
        ];

        // Actualizar conteo en el inventario
        $urlUpdateInv = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey&updateMask.fieldPaths=conteo";

        $payloadUpdate = json_encode([
            "fields" => [
                "conteo" => ["integerValue" => $nextConteo]
            ]
        ]);

        file_get_contents($urlUpdateInv, false, stream_context_create([
            'http' => [
                'method'  => 'PATCH',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payloadUpdate
            ]
        ]));


        // Obtener asignaciones de la cabecera
        $asignaciones = $docInv['fields']['asignaciones']['mapValue']['fields'] ?? [];

        // Crear subcolecciones nuevas con documentos por cada asignación
        foreach ($asignaciones as $docId => $arrUsuarios) {
            $usuarios = $arrUsuarios['arrayValue']['values'] ?? [];

            // Subconteo 1 → primer usuario
            if (isset($usuarios[0]['stringValue'])) {
                $usuario1 = $usuarios[0]['stringValue'];
                $urlCreateDoc = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/{$nextPair[0]}/$docId?key=$firebaseApiKey";

                $payloadDoc = json_encode([
                    "fields" => [
                        "status"     => ["booleanValue" => true],
                        "idAsignado" => ["stringValue"  => $usuario1],
                        "updatedAt"  => ["timestampValue" => gmdate('c')]
                    ]
                ]);

                file_get_contents($urlCreateDoc, false, stream_context_create([
                    'http' => [
                        'method'  => 'PATCH',
                        'header'  => "Content-Type: application/json\r\n",
                        'content' => $payloadDoc
                    ]
                ]));
            }

            // Subconteo 2 → segundo usuario
            if (isset($usuarios[1]['stringValue'])) {
                $usuario2 = $usuarios[1]['stringValue'];
                $urlCreateDoc = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/{$nextPair[1]}/$docId?key=$firebaseApiKey";

                $payloadDoc = json_encode([
                    "fields" => [
                        "status"     => ["booleanValue" => true],
                        "idAsignado" => ["stringValue"  => $usuario2],
                        "updatedAt"  => ["timestampValue" => gmdate('c')]
                    ]
                ]);

                file_get_contents($urlCreateDoc, false, stream_context_create([
                    'http' => [
                        'method'  => 'PATCH',
                        'header'  => "Content-Type: application/json\r\n",
                        'content' => $payloadDoc
                    ]
                ]));
            }
        }

        echo json_encode(['success' => true, 'message' => "Nuevos conteos creados: " . implode(" y ", $nextPair) . " y conteo actualizado a $nextConteo"]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Todas las líneas finalizadas. No se generaron más conteos (generacionConteos = false)']);
    }
    break;


    case 21: // Eliminar inventario
        $noEmpresa    = (int)$_SESSION['empresa']['noEmpresa'];
        $idInventario = $_POST['idInventario'] ?? null;

        if (!$noEmpresa || !$idInventario) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
            exit;
        }

        try {
            // Endpoint Firestore REST API
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey";

            $opts = [
                'http' => [
                    'method' => 'DELETE'
                ]
            ];
            $context = stream_context_create($opts);
            $result  = file_get_contents($url, false, $context);

            if ($http_response_header && strpos($http_response_header[0], "200") !== false) {
                actualizarFolio($firebaseProjectId, $firebaseApiKey, $noEmpresa);
                echo json_encode(["success" => true, "message" => "Inventario eliminado"]);
            } else {
                echo json_encode(["success" => false, "message" => "No se pudo eliminar inventario"]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Error: " . $e->getMessage()
            ]);
        }
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
