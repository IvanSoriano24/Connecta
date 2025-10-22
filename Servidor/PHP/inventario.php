<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'firebase.php';
include 'utils.php';
session_start();

/****************************************/
/****************************************/

/*function obtenerLineas($claveSae, $conexionData)
{
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    if (!$conn) {
        throw new Exception("No pude conectar a la base de datos");
    }
    try {
        $tablaL = "[{$conexionData['nombreBase']}].[dbo].[CLIN" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaI = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaC = "[{$conexionData['nombreBase']}].[dbo].[INVE_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        $sql = "
            SELECT L.*
            FROM $tablaL L
            WHERE EXISTS (
                SELECT 1
                FROM $tablaI I
                INNER JOIN $tablaC C ON C.CVE_PROD = I.CVE_ART
                WHERE I.LIN_PROD = L.CVE_LIN
                  AND (C.CAMPLIB2 != 'N' OR C.CAMPLIB2 IS NULL)
            )";

        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            throw new Exception("Problema al optener las lineas:\n{$errors}");
        }

        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }

        if (!empty($datos)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $datos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se Encontraron lineas.']);
        }
    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        sqlsrv_close($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
*/
function obtenerLineas($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    header('Content-Type: application/json');

    if ($noEmpresa === null || !$firebaseProjectId || !$firebaseApiKey) {
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros: noEmpresa, firebaseProjectId o firebaseApiKey.']);
        return;
    }

    $collection = "CATEGORIAS";
    $baseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
    $urlQuery = "$baseUrl:runQuery?key=$firebaseApiKey";

    $payload = json_encode([
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
    ]);

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload,
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($urlQuery, false, $context);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al consultar Firebase.']);
        return;
    }

    $resultArray = json_decode($response, true);
    if (!is_array($resultArray) || !isset($resultArray[0]['document'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontró documento de categorías para la empresa.']);
        return;
    }

    $doc = $resultArray[0]['document'];
    $fields = $doc['fields'] ?? [];

    $categorias = [];
    if (isset($fields['categorias']['arrayValue']['values']) && is_array($fields['categorias']['arrayValue']['values'])) {
        foreach ($fields['categorias']['arrayValue']['values'] as $v) {
            if (isset($v['stringValue'])) {
                $categorias[] = (string)$v['stringValue'];
            }
        }
    }

    if (empty($categorias)) {
        echo json_encode(['success' => false, 'message' => 'El documento no contiene categorías.']);
        return;
    }

    // Devolver las "líneas" como las categorías obtenidas
    echo json_encode(['success' => true, 'data' => array_values($categorias)]);
}

function obtenerProductosPorLinea($claveSae, $conexionData, $linea){
    $conn = sqlsrv_connect($conexionData['host'], [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ]);
    if (!$conn) {
        throw new Exception("No pude conectar a la base de datos");
    }
    try {
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[LTPD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[INVE_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $sql = "SELECT I.CVE_ART, I.DESCR, I.LIN_PROD, I.EXIST, L.CVE_ART AS ProductoLote, L.LOTE, L.CANTIDAD AS CantidadLote
            FROM $nombreTabla I
            LEFT JOIN $nombreTabla2 L ON L.CVE_ART = I.CVE_ART
            LEFT JOIN $nombreTabla3 C ON C.CVE_PROD = I.CVE_ART
            WHERE I.CTRL_ALM = ? AND (C.CAMPLIB2 = 'N' OR C.CAMPLIB2 IS NOT NULL)";
            //WHERE I.CTRL_ALM = ? AND (C.CAMPLIB2 != 'N' OR C.CAMPLIB2 IS NULL)";
        $param = [$linea];
        //var_dump($linea);
        
        $stmt = sqlsrv_query($conn, $sql, $param);
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
function obtenerProductoGuardado($noEmpresa, $firebaseProjectId, $firebaseApiKey, $linea, $noInventario, $usuarioId, ?int $conteoPreferido = null)
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

    // Mapeo subcolección -> número de conteo (útil para elegir la más reciente)
    $subcolsMap = [
        'lineas' => 1,
        'lineas02' => 2,
        'lineas03' => 3,
        'lineas04' => 4,
        'lineas05' => 5,
        'lineas06' => 6,
    ];

    $candidatos = []; // cada item: ['subcol'=>..., 'conteo'=>int, 'doc'=>array]

    foreach ($subcolsMap as $sc => $numConteo) {
        // Si el caller pide un conteo específico, filtramos desde ya
        if ($conteoPreferido !== null && $numConteo !== (int)$conteoPreferido) {
            continue;
        }

        $url = "$root/INVENTARIO/$invDocId/$sc/$linea?key=$firebaseApiKey";
        $doc = http_get_json($url);
        if (!isset($doc['fields'])) continue;

        // Normalizar idAsignado
        $assigned = [];
        $f = $doc['fields'];
        if (isset($f['idAsignado']['stringValue'])) {
            $assigned[] = (string)$f['idAsignado']['stringValue'];
        } elseif (isset($f['idAsignado']['integerValue'])) {
            $assigned[] = (string)$f['idAsignado']['integerValue'];
        } elseif (!empty($f['idAsignado']['arrayValue']['values'])) {
            foreach ($f['idAsignado']['arrayValue']['values'] as $v) {
                if (isset($v['stringValue'])) $assigned[] = (string)$v['stringValue'];
                if (isset($v['integerValue'])) $assigned[] = (string)$v['integerValue'];
            }
        }

        if (in_array((string)$usuarioId, $assigned, true)) {
            $candidatos[] = ['subcol' => $sc, 'conteo' => $numConteo, 'doc' => $doc];
        }
    }

    // Si no hay candidato (no está asignado en ninguna subcolección)
    if (empty($candidatos)) {
        echo json_encode([
            'success' => true,
            'linea' => (string)$linea,
            'locked' => false,
            'conteo' => $conteoPreferido,   // puede venir null
            'finishedAt' => null,
            'lockedBy' => null,
            'productos' => null,
            'activa' => false,
            'coleccion' => null
        ]);
        return;
    }

    // Elegir candidato:
    // - si vino $conteoPreferido ya lo filtramos arriba
    // - si no vino, tomar el de mayor conteo (el más reciente)
    usort($candidatos, fn($a, $b) => $b['conteo'] <=> $a['conteo']);
    $pick = $candidatos[0];

    $fields = $pick['doc']['fields'];
    $status = isset($fields['status']['booleanValue']) ? (bool)$fields['status']['booleanValue'] : true; // true = editable
    $conteo = isset($fields['conteo']['integerValue']) ? (int)$fields['conteo']['integerValue'] : $pick['conteo'];
    $finishedAt = isset($fields['finishedAt']['timestampValue']) ? (string)$fields['finishedAt']['timestampValue'] : null;
    $lockedBy = isset($fields['lockedBy']['stringValue']) ? (string)$fields['lockedBy']['stringValue'] : null;

    // Campos no producto
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

    // Transformar productos
    $productos = [];
    foreach ($fields as $clave => $valor) {
        if (in_array($clave, $reservados, true)) continue;
        if (empty($valor['arrayValue']['values']) || !is_array($valor['arrayValue']['values'])) continue;

        $lotes = [];
        $sumaTotal = 0;
        foreach ($valor['arrayValue']['values'] as $entry) {
            if (empty($entry['mapValue']['fields'])) continue;
            $f = $entry['mapValue']['fields'];

            $corr = isset($f['corrugados']['integerValue']) ? (int)$f['corrugados']['integerValue'] : 0;
            $sult = isset($f['sueltos']['integerValue']) ? (int)$f['sueltos']['integerValue'] : 0;
            $cxc = isset($f['corrugadosPorCaja']['integerValue']) ? (int)$f['corrugadosPorCaja']['integerValue'] : 0;
            $lote = isset($f['lote']['stringValue']) ? (string)$f['lote']['stringValue'] : '';
            $total = isset($f['total']['integerValue']) ? (int)$f['total']['integerValue'] : ($corr * $cxc + $sult);

            $sumaTotal += $total;
            $lotes[] = [
                'corrugados' => $corr,
                'corrugadosPorCaja' => $cxc,
                'lote' => $lote,
                'total' => $total,
                'sueltos' => $sult,
            ];
        }

        $productos[] = [
            'cve_art' => $clave,
            'conteoTotal' => $sumaTotal,
            'lotes' => $lotes
        ];
    }

    echo json_encode([
        'success' => true,
        'linea' => (string)$linea,
        'locked' => $status,           // true = editable (tu semántica actual)
        'conteo' => $conteo,           // # de conteo real del doc elegido
        'finishedAt' => $finishedAt,
        'lockedBy' => $lockedBy,
        'productos' => $productos,
        'activa' => $status,
        'coleccion' => $pick['subcol']    // p.ej. lineas03
    ]);
}

function noInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    $collection = "FOLIOS";
    $baseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
    $urlQuery = "$baseUrl:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (documento y noEmpresa)
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload,
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($urlQuery, false, $context);

    $noInventario = null;

    if ($response !== false) {
        $resultArray = json_decode($response, true);

        if (isset($resultArray[0]['document'])) {
            // Documento encontrado
            $doc = $resultArray[0]['document'];
            $fields = $doc['fields'];
            $noInventario = $fields['folioSiguiente']['integerValue'] ?? null;
            return (int)$noInventario;
        }
    }

    // -----------------------
    // Si no existe, lo creamos
    // -----------------------
    $newDoc = [
        "fields" => [
            "documento" => ["stringValue" => "inventarioFisico"],
            "folioInicial" => ["integerValue" => 1],
            "folioSiguiente" => ["integerValue" => 1],
            "noEmpresa" => ["integerValue" => (int)$noEmpresa],
        ]
    ];

    $optionsCreate = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($newDoc),
        ]
    ];

    $urlCreate = "$baseUrl/$collection?key=$firebaseApiKey";
    $contextCreate = stream_context_create($optionsCreate);
    $respCreate = @file_get_contents($urlCreate, false, $contextCreate);

    if ($respCreate !== false) {
        // Documento creado con éxito → devolvemos el folio inicial
        return 1;
    }

    return null; // si no se pudo crear
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
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
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
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
        if (isset($v['doubleValue'])) return (float)$v['doubleValue'];
        if (isset($v['stringValue'])) return (string)$v['stringValue'];
        if (isset($v['booleanValue'])) return (bool)$v['booleanValue'];
        if (isset($v['nullValue'])) return null;
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
    $docActive = $pickDoc($respActive);

    $result = [
        "success" => true,
        "foundActive" => false,
        "existsAny" => false,
        "docId" => null,
        "raw" => null // opcional: para depurar, puedes quitarlo en producción
    ];

    if ($docActive) {
        $fields = $docActive['fields'] ?? [];
        $result["noInventario"] = $getField($fields, 'noInventario');
        $result["foundActive"] = true;
        $result["existsAny"] = true; // si hay activo, por ende existe
        $result["docId"] = $getIdFromName($docActive['name']);
        $result["raw"] = null; // o guarda $docActive si quieres
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
    $docAny = $pickDoc($respAny);

    if ($docAny) {
        $fields = $docAny['fields'] ?? [];
        $result["noInventario"] = $getField($fields, 'noInventario');
        $result["existsAny"] = true;
        $result["docId"] = $getIdFromName($docAny['name']);
    }

    return $result;
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'PATCH',
            'content' => json_encode($bodyArr),
            'timeout' => 20
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false ? json_decode($resp, true) : null;
}

function firestore_runQuery($projectId, $apiKey, $structuredQuery)
{
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:runQuery?key=$apiKey";
    $opts = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
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

// Helper: resuelve nombre de subcolección a partir de conteo/subconteo
function subcol_from_conteo(int $conteo, int $subconteo): string
{
    // normaliza
    if ($conteo < 1) $conteo = 1;
    if ($subconteo !== 2) $subconteo = 1; // sólo 1 o 2

    // índice 1-based: (conteo-1)*2 + (1 ó 2)
    $idx = ($conteo - 1) * 2 + $subconteo;

    // mapping: 1->lineas, 2->lineas02, 3->lineas03, …
    return $idx === 1 ? 'lineas' : ('lineas' . str_pad($idx, 2, '0', STR_PAD_LEFT));
}

/*function subcol_from_conteo(int $conteo, int $subconteo): string {
    // conteo 1 => lineas / lineas02
    // conteo 2 => lineas03 / lineas04
    // conteo 3 => lineas05 / lineas06 ...
    if ($conteo <= 1) return ($subconteo === 2) ? 'lineas02' : 'lineas';
    $start = ($conteo - 1) * 2 + 1;            // 2->3, 3->5, ...
    $idx   = ($subconteo === 2) ? $start + 1 : $start;
    return 'lineas' . str_pad($idx, 2, '0', STR_PAD_LEFT);
}*/

// runQuery por noEmpresa + noInventario (usa REST nativo)
function resolveInvDocIdStrict(int $noEmpresa, int $noInv, string $projectId, string $apiKey): ?string {
    $root = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";
    $url  = "$root:runQuery?key=$apiKey";

    $body = [
        "structuredQuery" => [
            "from" => [ ["collectionId" => "INVENTARIO"] ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        ["fieldFilter" => [
                            "field" => ["fieldPath" => "noEmpresa"],
                            "op"    => "EQUAL",
                            "value" => ["integerValue" => $noEmpresa],
                        ]],
                        ["fieldFilter" => [
                            "field" => ["fieldPath" => "noInventario"],
                            "op"    => "EQUAL",
                            "value" => ["integerValue" => $noInv],
                        ]],
                    ]
                ]
            ],
            "limit" => 1
        ]
    ];

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($body, JSON_UNESCAPED_UNICODE)
        ]
    ];
    $res = @file_get_contents($url, false, stream_context_create($opts));
    if ($res === false) return null;
    $rows = json_decode($res, true);
    if (!is_array($rows)) return null;

    foreach ($rows as $r) {
        if (!empty($r['document']['name'])) {
            return basename($r['document']['name']);
        }
    }
    return null;
}

function guardarProducto($noEmpresa, $noInventario, $firebaseProjectId, $firebaseApiKey) {
    $payload = json_decode($_POST['payload'] ?? 'null', true);
    if (!$payload) {
        echo json_encode(['success' => false, 'message' => 'Payload inválido']);
        exit;
    }

    if (empty($payload['noEmpresa']) && !empty($_SESSION['empresa']['noEmpresa'])) {
        $payload['noEmpresa'] = (string)$_SESSION['empresa']['noEmpresa'];
    }

    foreach (['linea','noInventario','cve_art','conteo','subconteo'] as $k) {
        if (!isset($payload[$k]) || $payload[$k] === '') {
            echo json_encode(['success' => false, 'message' => "Falta $k"]);
            exit;
        }
    }

    // ---- Variables
    $lineaId      = (string)$payload['linea'];
    $folioInv     = (int)$payload['noInventario'];
    $cveArt       = (string)$payload['cve_art'];
    $lotesIn      = is_array($payload['lotes'] ?? null) ? $payload['lotes'] : [];
    $conteoIn     = (int)$payload['conteo'];      // 1,2,3,...
    $subconteoIn  = (int)$payload['subconteo'];   // 1 ó 2
    $usuarioId    = (string)($_SESSION['usuario']['idReal'] ?? '');
    $idInventario = isset($payload['idInventario']) ? (string)$payload['idInventario'] : null;

    if (!$noEmpresa) {
        echo json_encode(['success' => false, 'message' => 'Falta noEmpresa']);
        exit;
    }

    // 1) Resolver docId del inventario **correcto**
    //    - Si viene idInventario → úsalo
    //    - Si no, consulta estricta por noEmpresa + noInventario (runQuery)
    if ($idInventario && $idInventario !== '') {
        $invDocId = $idInventario;
    } else {
        // si ya tienes getInventarioDocByFolio(noEmpresa, folio) que sí filtra por empresa, puedes usarlo.
        $invDocId = resolveInvDocIdStrict((int)$noEmpresa, $folioInv, $firebaseProjectId, $firebaseApiKey);
        if (!$invDocId) {
            echo json_encode(['success' => false, 'message' => 'Inventario no encontrado para esa empresa y folio']);
            exit;
        }
    }

    // 2) Subcolección por conteo/subconteo
    $subcol = subcol_from_conteo($conteoIn, $subconteoIn);

    // 3) URL doc línea
    $root       = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";
    $lineDocUrl = "$root/INVENTARIO/$invDocId/$subcol/$lineaId?key=$firebaseApiKey";

    // (opcional) bloqueo
    $lineDoc = http_get_json($lineDocUrl);
    if (isset($lineDoc['fields']['locked']['booleanValue']) && $lineDoc['fields']['locked']['booleanValue'] === true) {
        echo json_encode(['success' => false, 'message' => 'Línea bloqueada']);
        exit;
    }
    if (isset($lineDoc['fields']['status']['booleanValue']) && $lineDoc['fields']['status']['booleanValue'] === false) {
        echo json_encode(['success' => false, 'message' => 'Línea finalizada (no editable)']);
        exit;
    }

    // 4) Construir valores **NUEVOS** del producto (REEMPLAZO, no append)
    $newValues = [];
    foreach ((array)$lotesIn as $row) {
        $corr    = (int)($row['corrugados'] ?? 0);
        $cxc     = (int)($row['cajasPorCorrugado'] ?? $row['corrugadosPorCaja'] ?? 0);
        $sueltos = (int)($row['sueltos'] ?? 0);

        if (isset($row['totales']) && $row['totales'] !== '') {
            $totalLote = (int)$row['totales'];
        } elseif (isset($row['piezas']) && $row['piezas'] !== '') {
            $totalLote = (int)$row['piezas'];
        } else {
            $totalLote = ($corr * $cxc) + $sueltos;
        }

        $lote = (string)($row['lote'] ?? '');
        $newValues[] = [
            'mapValue' => [
                'fields' => [
                    'corrugados'        => ['integerValue' => $corr],
                    'corrugadosPorCaja' => ['integerValue' => $cxc],
                    'sueltos'           => ['integerValue' => $sueltos],
                    'lote'              => ['stringValue'  => $lote],
                    'total'             => ['integerValue' => (int)$totalLote],
                ]
            ]
        ];
    }

    /*
    // 🔁 ALTERNATIVA: “actualizar por lote” (merge) en vez de reemplazar todo
    // - Lee existentes (si hay)
    $existingValues = [];
    if ($lineDoc && isset($lineDoc['fields'][$cveArt]['arrayValue']['values'])) {
        $existingValues = $lineDoc['fields'][$cveArt]['arrayValue']['values'];
    }

    // - Indexa por 'lote' para sobrescribir si el lote ya existía
    $byLote = [];
    foreach ($existingValues as $v) {
        $f = $v['mapValue']['fields'] ?? [];
        $l = isset($f['lote']['stringValue']) ? trim(strtolower($f['lote']['stringValue'])) : '';
        $byLote[$l] = $v;
    }
    foreach ($newValues as $nv) {
        $f = $nv['mapValue']['fields'] ?? [];
        $l = isset($f['lote']['stringValue']) ? trim(strtolower($f['lote']['stringValue'])) : '';
        $byLote[$l] = $nv; // reemplaza/crea
    }
    // - El valor final para guardar:
    $newValues = array_values($byLote);
    */

    // 5) PATCH (reemplaza el campo del producto)
    $escapedProductField = escape_field_path($cveArt);
    $patchUrl = $lineDocUrl
        . '&updateMask.fieldPaths=' . rawurlencode($escapedProductField)
        . '&updateMask.fieldPaths=status'
        . '&updateMask.fieldPaths=updatedAt'
        . '&updateMask.fieldPaths=lastProduct'
        . '&updateMask.fieldPaths=idAsignado'
        . '&updateMask.fieldPaths=conteo'
        . '&updateMask.fieldPaths=subconteo';

    $payloadFirestore = [
        'fields' => [
            'status'      => ['booleanValue' => true],
            'updatedAt'   => ['timestampValue' => gmdate('c')],
            'lastProduct' => ['stringValue'  => $cveArt],
            'idAsignado'  => ['stringValue'  => $usuarioId],
            'conteo'      => ['integerValue' => $conteoIn],
            'subconteo'   => ['integerValue' => $subconteoIn],
            $cveArt       => ['arrayValue' => ['values' => $newValues]],
        ]
    ];

    $patchResp = http_patch_json($patchUrl, $payloadFirestore);
    if (!$patchResp) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar en Firestore']);
        exit;
    }

    echo json_encode([
        'success'      => true,
        'docPath'      => "INVENTARIO/$invDocId/$subcol/$lineaId",
        'product'      => $cveArt,
        'subcoleccion' => $subcol,
        'conteo'       => $conteoIn,
        'subconteo'    => $subconteoIn,
        'updated'      => true
    ]);
}


//////////////////////////GUARDAR PRODUCTO/////////////////////////////////////////
function iniciarInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey, $noInventario)
{
    date_default_timezone_set('America/Mexico_City');
    $fechaCreacion = date("Y-m-d H:i:s");

    $fields = [
        'conteo' => ['integerValue' => 1],
        'fechaInicio' => ['stringValue' => $fechaCreacion],
        'noEmpresa' => ['integerValue' => $noEmpresa],
        'noInventario' => ['integerValue' => $noInventario],
        'status' => ['booleanValue' => true],
    ];

    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/INVENTARIO?key=$firebaseApiKey";

    $payload = json_encode(['fields' => $fields]);
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload,
        ]
    ];
    $context = stream_context_create($options);
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
            'docId' => $docId,
            'data' => $data
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
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($body),
                'timeout' => 15,
            ]
        ];
        $ctx = stream_context_create($options);
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
                    'op' => 'EQUAL',
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
            $fullName = $item['document']['name'] ?? '';
            $idDocumento = $fullName ? basename($fullName) : null;

            $inventarios[] = [
                'idDocumento' => $idDocumento, // 🔹 nuevo campo
                'conteo' => isset($f['conteo']['integerValue']) ? (int)$f['conteo']['integerValue'] : null,
                'fechaInicio' => isset($f['fechaInicio']['stringValue']) ? $f['fechaInicio']['stringValue'] : null,
                'noEmpresa' => isset($f['noEmpresa']['integerValue']) ? (int)$f['noEmpresa']['integerValue'] : null,
                'noInventario' => isset($f['noInventario']['integerValue']) ? (int)$f['noInventario']['integerValue'] : null,
                'status' => isset($f['status']['booleanValue']) ? (bool)$f['status']['booleanValue'] : null,
            ];
        }
    }

    //return $inventarios;
    echo json_encode(['success' => true, 'inventarios' => $inventarios]);
}

function obtenerAlmacenistas($noEmpresa, $firebaseProjectId, $firebaseApiKey){
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => $payload,
            'timeout' => 20
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    $items = [];
    if ($response !== false) {
        $resultArray = json_decode($response, true);
        if (is_array($resultArray)) {
            foreach ($resultArray as $row) {
                if (!isset($row['document'])) continue;
                $doc = $row['document'];
                $fields = $doc['fields'] ?? [];
                $parts = explode('/', $doc['name']);
                $id = end($parts);

                $items[] = [
                    'idUsuario' => $id,
                    'usuario' => $fields['usuario']['stringValue'] ?? null,
                    'nombre' => $fields['nombre']['stringValue'] ?? null,
                    'apellido' => $fields['apellido']['stringValue'] ?? null,
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
            'header' => "Content-Type: application/json\r\n",
            'method' => 'PATCH',
            'content' => json_encode($bodyArr),
            'timeout' => 20
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false ? json_decode($resp, true) : null;
}

function firestore_runQueryAsignacion($projectId, $apiKey, $structuredQuery)
{
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:runQuery?key=$apiKey";
    $opts = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
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
///////////////
// Helper: par de subcolecciones para el conteo N
function subcols_for_conteo(int $n): array {
    if ($n <= 1) return ['lineas','lineas02'];       // conteo 1
    $start = ($n - 1) * 2 + 1;                       // 2→3,4; 3→5,6; ...
    return [
        'lineas' . str_pad($start,     2, '0', STR_PAD_LEFT),
        'lineas' . str_pad($start + 1, 2, '0', STR_PAD_LEFT),
    ];
}

function guardarAsignaciones(
    $noEmpresa,
    $noInventario,
    array $asignaciones,
    $projectId,
    $apiKey,
    ?int $conteoActual = null
) {
    $root = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";
    $errors = [];

    // ==== 1️⃣ Resolver inventario ====
    $invQueryUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:runQuery?key=$apiKey";

    $queryBody = [
        "structuredQuery" => [
            "from" => [["collectionId" => "INVENTARIO"]],
            "where" => [
                "fieldFilter" => [
                    "field" => ["fieldPath" => "noInventario"],
                    "op" => "EQUAL",
                    "value" => ["integerValue" => (int)$noInventario] // 👈 forzamos número
                ]
            ],
            "limit" => 1
        ]
    ];

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($queryBody)
        ]
    ]);

    $result = @file_get_contents($invQueryUrl, false, $context);

    if ($result === false) {
        error_log("[guardarAsignaciones] Error al consultar Firestore: $invQueryUrl");
        return ['success' => false, 'message' => 'Error al conectar con Firestore'];
    }

    $data = json_decode($result, true);

    if (!is_array($data)) {
        error_log("[guardarAsignaciones] Respuesta no válida Firestore: $result");
        return ['success' => false, 'message' => 'Respuesta no válida desde Firestore'];
    }

    $inv = null;
    foreach ($data as $row) {
        if (isset($row['document'])) {
            $inv = $row['document'];
            break;
        }
    }

    if (!$inv) {
        error_log("[guardarAsignaciones] No se encontró inventario con noInventario=$noInventario. Respuesta: $result");
        return ['success' => false, 'message' => 'Inventario activo no encontrado'];
    }

    $invDocId = basename($inv['name']); // 👈 obtenemos el ID real del doc


    // ==== 2️⃣ Traer cabecera ====
    $invDocUrl = "$root/INVENTARIO/$invDocId?key=$apiKey";
    $invDoc = json_decode(@file_get_contents($invDocUrl), true);
    if ($conteoActual === null) {
        $conteoActual = (int)($invDoc['fields']['conteo']['integerValue'] ?? 1);
        if ($conteoActual < 1) $conteoActual = 1;
    }

    // ==== 3️⃣ Subcolecciones ====
    $subcols_for_conteo = function ($conteo) {
        $map = [
            1 => ['lineas', 'lineas02'],
            2 => ['lineas03', 'lineas04'],
            3 => ['lineas05', 'lineas06'],
            4 => ['lineas07', 'lineas08']
        ];
        return $map[$conteo] ?? ['lineas', 'lineas02'];
    };
    [$subA, $subB] = $subcols_for_conteo($conteoActual);

    // ==== 4️⃣ Cargar asignaciones actuales ====
    $currMap = [];
    if (!empty($invDoc['fields']['asignaciones']['mapValue']['fields'])) {
        foreach ($invDoc['fields']['asignaciones']['mapValue']['fields'] as $lin => $arr) {
            $uids = [];
            if (!empty($arr['arrayValue']['values'])) {
                foreach ($arr['arrayValue']['values'] as $v) {
                    $uids[] = $v['stringValue'] ?? null;
                }
            }
            $currMap[$lin] = array_pad($uids, 2, null);
        }
    }

    // ==== 5️⃣ Normalizar nuevas asignaciones ====
    $newMap = [];
    foreach ($asignaciones as $lineaId => $uids) {
        if ($lineaId === '' || !is_array($uids)) continue;
        $uids = array_slice(array_values($uids), 0, 2);
        $newMap[(string)$lineaId] = $uids;
    }

    // ==== 6️⃣ Aplicar cambios Firestore ====
    $tsIso = gmdate('c');
    $finalMap = [];
    $reservados = ['locked','conteo','subconteo','finishedAt','lockedBy','updatedAt','lastProduct','conteoTotal','diferencia','existSistema','descr','linesStatus','status','idAsignado'];
    $hasProducts = function ($doc) use ($reservados) {
        if (empty($doc['fields']) || !is_array($doc['fields'])) return false;
        foreach ($doc['fields'] as $k => $val) {
            if (in_array($k, $reservados, true)) continue;
            if (!empty($val['arrayValue']['values'])) return true;
        }
        return false;
    };

    foreach ($newMap as $lineaId => $desiredUids) {
        $old  = $currMap[$lineaId] ?? [null, null];
        $want = [$desiredUids[0] ?? null, $desiredUids[1] ?? null];
        $finalMap[$lineaId] = $want;

        for ($slot = 0; $slot < 2; $slot++) {
            $subcol = $slot === 0 ? $subA : $subB;
            $oldUid = $old[$slot];
            $newUid = $want[$slot];

            if ($oldUid === $newUid) continue;

            $docUrl = "$root/INVENTARIO/$invDocId/$subcol/$lineaId?key=$apiKey";
            $doc = json_decode(@file_get_contents($docUrl), true);
            $tieneProductos = $doc && $hasProducts($doc);

            if ($oldUid && !$newUid && $tieneProductos) {
                $errors[] = "No se puede quitar la asignación de $subcol/$lineaId: ya hay productos capturados.";
                continue;
            }

            if ($newUid) {
                $patchUrl = "$docUrl&updateMask.fieldPaths=idAsignado&updateMask.fieldPaths=conteo&updateMask.fieldPaths=subconteo&updateMask.fieldPaths=updatedAt";
                $body = [
                    'fields' => [
                        'idAsignado' => ['stringValue' => $newUid],
                        'conteo'     => ['integerValue' => $conteoActual],
                        'subconteo'  => ['integerValue' => $slot + 1],
                        'updatedAt'  => ['timestampValue' => $tsIso],
                    ]
                ];
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'PATCH',
                        'header' => "Content-Type: application/json\r\n",
                        'content' => json_encode($body)
                    ]
                ]);
                @file_get_contents($patchUrl, false, $ctx);
            }
        }
    }

    // ==== 7️⃣ Fusionar y persistir mapa asignaciones ====
    $invActual = json_decode(@file_get_contents($invDocUrl), true);
    $existentes = [];

    if (!empty($invActual['fields']['asignaciones']['mapValue']['fields'])) {
        foreach ($invActual['fields']['asignaciones']['mapValue']['fields'] as $lin => $arr) {
            $uids = [];
            if (!empty($arr['arrayValue']['values'])) {
                foreach ($arr['arrayValue']['values'] as $v) {
                    $uids[] = $v['stringValue'] ?? null;
                }
            }
            $existentes[$lin] = array_pad($uids, 2, null);
        }
    }

// 🔹 Fusionar (actualizar o agregar nuevas)
    foreach ($finalMap as $lineaId => $uids) {
        $existentes[$lineaId] = $uids;
    }

// 🔹 Reconstruir cuerpo del patch
    $mapFields = [];
    foreach ($existentes as $lineaId => $uids) {
        $vals = [];
        foreach ($uids as $u) {
            $vals[] = $u ? ['stringValue' => $u] : ['nullValue' => null];
        }
        $vals = array_pad($vals, 2, ['nullValue' => null]);
        $mapFields[$lineaId] = ['arrayValue' => ['values' => $vals]];
    }

// 🔹 Enviar solo el mapa fusionado (sin borrar lo anterior)
    $urlAsign = "$root/INVENTARIO/$invDocId?key=$apiKey&updateMask.fieldPaths=asignaciones";
    $bodyAsign = ['fields' => ['asignaciones' => ['mapValue' => ['fields' => $mapFields]]]];
    $ctx = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($bodyAsign)
        ]
    ]);
    @file_get_contents($urlAsign, false, $ctx);


    // ==== 8️⃣ Actualizar productosDiferentes si conteo >= 2 ====
    if ($conteoActual >= 2) {
        try {
            // 🔹 Obtener datos de conexión desde Firestore
            $connUrl = "$root/CONEXIONES?key=$apiKey";
            $data = json_decode(@file_get_contents($connUrl), true);
            $conexion = null;
            foreach ($data['documents'] ?? [] as $doc) {
                $fields = $doc['fields'];
                if ($fields['noEmpresa']['integerValue'] == $noEmpresa) {
                    $conexion = [
                        'host' => $fields['host']['stringValue'],
                        'puerto' => $fields['puerto']['stringValue'],
                        'usuario' => $fields['usuario']['stringValue'],
                        'password' => $fields['password']['stringValue'],
                        'nombreBase' => $fields['nombreBase']['stringValue'],
                        'claveSae' => $fields['claveSae']['stringValue'],
                    ];
                    break;
                }
            }

            if (!$conexion) throw new Exception("No se encontró conexión SQL para empresa $noEmpresa");

            $server = $conexion['host'] . ',' . $conexion['puerto'];
            $dsn = "sqlsrv:Server=$server;Database=" . $conexion['nombreBase'] . ";TrustServerCertificate=true";
            $pdo = new PDO($dsn, $conexion['usuario'], $conexion['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // 🔹 Buscar productos diferentes
            $productosDiferentes = [];
            $claveSae = $conexion['claveSae'];
            $tablaInve = "INVE" . $claveSae;
            $tablaClib = "INVE_CLIB" . $claveSae;

            foreach (array_keys($finalMap) as $categoria) {
                $sql1 = "SELECT CVE_ART FROM $tablaInve WHERE CTRL_ALM = :cat";
                $stmt1 = $pdo->prepare($sql1);
                $stmt1->execute([':cat' => $categoria]);
                $arts = $stmt1->fetchAll(PDO::FETCH_COLUMN);

                if (!$arts) continue;
                $in = implode(",", array_map(fn($a) => "'$a'", $arts));
                $sql2 = "SELECT CVE_PROD FROM $tablaClib WHERE CVE_PROD IN ($in) AND CAMPLIB2 = 'N'";
                $rows = $pdo->query($sql2)->fetchAll(PDO::FETCH_COLUMN);
                $productosDiferentes = array_merge($productosDiferentes, $rows);
            }

            $productosDiferentes = array_values(array_unique($productosDiferentes));

            // 🔹 Actualizar Firestore
            // 🔹 Obtener los productosDiferentes actuales
            $invActual = json_decode(@file_get_contents($invDocUrl), true);
            $existentes = [];
            if (!empty($invActual['fields']['productosDiferentes']['arrayValue']['values'])) {
                foreach ($invActual['fields']['productosDiferentes']['arrayValue']['values'] as $v) {
                    if (isset($v['stringValue'])) {
                        $existentes[] = $v['stringValue'];
                    }
                }
            }

            // 🔹 Fusionar sin duplicar
            $todos = array_unique(array_merge($existentes, $productosDiferentes));

            // 🔹 Actualizar en Firestore solo si hay nuevos
            if (count($todos) > count($existentes)) {
                $patchUrl = "$root/INVENTARIO/$invDocId?key=$apiKey&updateMask.fieldPaths=productosDiferentes";
                $arrVals = array_map(fn($p) => ['stringValue' => $p], $todos);
                $body = ['fields' => ['productosDiferentes' => ['arrayValue' => ['values' => $arrVals]]]];
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'PATCH',
                        'header' => "Content-Type: application/json\r\n",
                        'content' => json_encode($body)
                    ]
                ]);
                @file_get_contents($patchUrl, false, $ctx);
            }


        } catch (Exception $e) {
            $errors[] = "Advertencia: no se pudieron actualizar productos diferentes (" . $e->getMessage() . ")";
        }
    }

    return !empty($errors)
        ? ['success' => true, 'warnings' => $errors, 'aplicado' => $finalMap, 'conteo' => $conteoActual]
        : ['success' => true, 'aplicado' => $finalMap, 'conteo' => $conteoActual];
}


function http_delete_simple($url)
{
    $opts = ['http' => ['method' => 'DELETE', 'timeout' => 20]];
    $ctx = stream_context_create($opts);
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
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
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
        'name' => $objectName,
        'contentType' => $contentType,
    ];
    if ($extraMetadata) $meta['metadata'] = $extraMetadata;

    $boundary = 'gcs-' . bin2hex(random_bytes(8));
    $fileBytes = file_get_contents($filePath);
    if ($fileBytes === false) throw new Exception("No se pudo leer el archivo: $filePath");

    $body = "--$boundary\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= json_encode($meta, JSON_UNESCAPED_SLASHES) . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: $contentType\r\n\r\n";
    $body .= $fileBytes . "\r\n";
    $body .= "--$boundary--\r\n";

    $url = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=multipart";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Content-Length: ' . strlen($body),
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $res = curl_exec($ch);
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
): array
{
    if (!extension_loaded('curl')) throw new Exception('PHP sin extensión cURL');
    if (!extension_loaded('openssl')) throw new Exception('PHP sin extensión OpenSSL');
    if (!is_file($saPath)) throw new Exception('No encuentro JSON en ' . $saPath);
    if (!$linea) throw new Exception('Falta linea');
    if (empty($filesArray) || empty($filesArray['name'])) throw new Exception('No llegaron archivos');

    $token = getAccessTokenFromServiceAccount($saPath);
    $names = (array)$filesArray['name'];
    $tmps = (array)$filesArray['tmp_name'];
    $sizes = (array)$filesArray['size'];
    $types = (array)$filesArray['type'];

    $subidos = [];
    foreach ($tmps as $i => $tmp) {
        if (!is_uploaded_file($tmp)) continue;

        $origName = $names[$i] ?? 'archivo';
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);

        // Detecta MIME simple (PDF o imagen)
        $mime = $types[$i] ?? 'application/octet-stream';
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
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
        $ts = time();
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
            'name' => $origName,
            'path' => $objectName,
            'size' => $sizes[$i] ?? null,
            'downloadURL' => $downloadURL,
            'gcs' => $objInfo,
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
        'iss' => $sa['client_email'],
        'scope' => $scope,
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
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
        'assertion' => $jwt
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
        $projectId = sa_project_id($saPath);
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
        $linea = $_POST['linea'] ?? null;
        $noInventario = $_POST['noInventario'] ?? null;
        $cve_art = $_POST['cve_art'] ?? null;
        $tipo = $_POST['tipo'] ?? null;
        $filesArr = $_FILES['pdfs'] ?? ($_FILES['pdfs[]'] ?? null);

        if (!$linea) throw new Exception('Falta "linea"');
        if (!$filesArr || empty($filesArr['name'])) throw new Exception('No llegaron archivos (pdfs[])');

        // El resto de tu lógica: decide carpeta y llama a gcsUploadMultipart(...)
        // Ejemplo (producto):
        if ($tipo === 'producto') {
            if (!$noInventario) throw new Exception('Falta "noInventario"');
            if (!$cve_art) throw new Exception('Falta "cve_art"');

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
        $root = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";
        $url = "$root/INVENTARIO/$invDocId?key=$apiKey";
        $doc = http_get_json($url);

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
            'success' => true,
            'noInventario' => $noInventario,
            'asignaciones' => $asig,       // { lineaId: [uid, uid?], ... }
            'usuarios' => $usuarios,   // { uid: {nombre, usuario}, ... }
            'lineasDesc' => $lineasDesc,
        ];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function leerUsuarioPorId(string $projectId, string $apiKey, string $userId): ?array
{
    if ($userId === '') return null;
    $root = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";
    $url = "$root/USUARIOS/" . rawurlencode($userId) . "?key=$apiKey";

    $doc = http_get_jsonAsignaciones($url);
    if (!isset($doc['fields'])) return null;

    $f = $doc['fields'];
    return [
        'nombre' => $f['nombre']['stringValue'] ?? '',
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
function _arr_string_from_field(?array $field): array
{
    // field tipo arrayValue.values de stringValue -> array de strings
    $out = [];
    if (!empty($field['arrayValue']['values'])) {
        foreach ($field['arrayValue']['values'] as $v) {
            if (isset($v['stringValue'])) $out[] = (string)$v['stringValue'];
        }
    }
    return $out;
}
function _arrayValue_strings(array $arr): array
{
    return ['arrayValue' => ['values' => array_map(fn($s) => ['stringValue' => (string)$s], array_values(array_unique($arr)))]];
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
        //obtenerLineas($claveSae, $conexionData);
        obtenerLineas($noEmpresa, $firebaseProjectId, $firebaseApiKey);
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
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $firebaseProject = $firebaseProjectId;
        $apiKey = $firebaseApiKey;
        $inventario = buscarInventario($noEmpresa, $firebaseProject, $apiKey);
        if ($inventario['success'] && $inventario['foundActive']) {
            echo json_encode([
                'success' => false,
                'message' => 'Hay un inventario activo',
                'docId' => $inventario['docId']
            ]);
        } else {
            $noInventario = noInventario($noEmpresa, $firebaseProject, $apiKey);

            $nuevoInv = iniciarInventario($noEmpresa, $firebaseProject, $apiKey, $noInventario);
            actualizarInventario($noEmpresa, $firebaseProject, $apiKey);

            if ($nuevoInv['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Inventario Iniciado',
                    'newNoInventario' => $noInventario,
                    'idInventario' => $nuevoInv['docId'] // aquí ya regresa el ID
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
        $noEmpresa = (int)$_SESSION['empresa']['noEmpresa'];
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
        $noEmpresa = (int)($_SESSION['empresa']['noEmpresa'] ?? 0);

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

        // Validar que el conteo actual del inventario coincida con el solicitado
        $conteoSolicitado = (int)($_POST['conteo'] ?? 0);
        if ($conteoSolicitado !== $conteo) {
            echo json_encode([
                'success' => false,
                'message' => "El conteo solicitado ($conteoSolicitado) no coincide con el conteo actual del inventario ($conteo). No se generará un nuevo conteo."
            ]);
            exit;
        }

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
            echo json_encode(['success' => false, 'message' => 'No se generó un nuevo conteo']);
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
                    'method' => 'PATCH',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $payloadUpdate
                ]
            ]));


            // Obtener asignaciones y productos diferentes
            $asignaciones = $docInv['fields']['asignaciones']['mapValue']['fields'] ?? [];
            $productosDif = _arr_string_from_field($docInv['fields']['productosDiferentes'] ?? []);

// 1️⃣ Extraer prefijos activos de productosDiferentes
            $prefijosActivos = [];
            foreach ($productosDif as $p) {
                if (preg_match('/^([A-Z]+)/i', $p, $m)) {
                    $prefijosActivos[] = strtoupper($m[1]);
                }
            }
            $prefijosActivos = array_unique($prefijosActivos);

// 2️⃣ Crear subcolecciones nuevas con documentos filtrados
            foreach ($asignaciones as $docId => $arrUsuarios) {

                // Saltar si el prefijo (AD, JD, etc.) ya no tiene productos en productosDiferentes
                if (!in_array(strtoupper($docId), $prefijosActivos)) {
                    continue; // ⛔ No crear documento para este grupo
                }

                $usuarios = $arrUsuarios['arrayValue']['values'] ?? [];

                // 1) Junta resueltosSAE del par actual (si existen en cualquiera de los 2 docs)
                $resueltos = [];
                foreach ($currentPair as $subcolActual) {
                    $urlActual = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/$subcolActual/$docId?key=$firebaseApiKey";
                    $docAct = @json_decode(@file_get_contents($urlActual), true);
                    if (!isset($docAct['fields'])) continue;

                    foreach (['resueltosSAE', 'igualesSAE', 'coincidentesSAE'] as $k) {
                        if (isset($docAct['fields'][$k])) {
                            $resueltos = array_merge($resueltos, _arr_string_from_field($docAct['fields'][$k]));
                        }
                    }
                }
                $resueltos = array_values(array_unique($resueltos)); // 🔒 únicos

                $tsIso = gmdate('c');

                // Subconteo 1 (nextPair[0]) ← primer usuario
                if (isset($usuarios[0]['stringValue'])) {
                    $usuario1 = $usuarios[0]['stringValue'];

                    $urlCreateDoc = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/{$nextPair[0]}/$docId?key=$firebaseApiKey";
                    $payloadDoc = [
                        "fields" => [
                            "status"     => ["booleanValue" => true],
                            "idAsignado" => ["stringValue"  => $usuario1],
                            "updatedAt"  => ["timestampValue" => $tsIso],
                        ]
                    ];
                    if (!empty($resueltos)) {
                        $payloadDoc["fields"]["omitidos"] = _arrayValue_strings($resueltos);
                    }

                    file_get_contents($urlCreateDoc, false, stream_context_create([
                        'http' => [
                            'method'  => 'PATCH',
                            'header'  => "Content-Type: application/json\r\n",
                            'content' => json_encode($payloadDoc)
                        ]
                    ]));
                }

                // Subconteo 2 (nextPair[1]) ← segundo usuario
                if (isset($usuarios[1]['stringValue'])) {
                    $usuario2 = $usuarios[1]['stringValue'];

                    $urlCreateDoc = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/{$nextPair[1]}/$docId?key=$firebaseApiKey";
                    $payloadDoc = [
                        "fields" => [
                            "status"     => ["booleanValue" => true],
                            "idAsignado" => ["stringValue"  => $usuario2],
                            "updatedAt"  => ["timestampValue" => $tsIso],
                        ]
                    ];
                    if (!empty($resueltos)) {
                        $payloadDoc["fields"]["omitidos"] = _arrayValue_strings($resueltos);
                    }

                    file_get_contents($urlCreateDoc, false, stream_context_create([
                        'http' => [
                            'method'  => 'PATCH',
                            'header'  => "Content-Type: application/json\r\n",
                            'content' => json_encode($payloadDoc)
                        ]
                    ]));
                }
            }

            echo json_encode(['success' => true, 'message' => "Nuevos conteos creados"]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Todas las líneas finalizadas. No se generaron más conteos (generacionConteos = false)']);
        }
        break;


    case 21: // Eliminar inventario
        $noEmpresa = (int)$_SESSION['empresa']['noEmpresa'];
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
            $result = file_get_contents($url, false, $context);

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


    case 22: // Obtener configuración de inventario físico
        try {
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONFIG/inventarioFisico?key=$firebaseApiKey";
            $result = file_get_contents($url);
            $doc = json_decode($result, true);

            if (isset($doc["fields"])) {
                echo json_encode([
                    "success" => true,
                    "data" => [
                        "generacionConteos" => $doc["fields"]["generacionConteos"]["booleanValue"] ?? false,
                        "guardadoAutomatico" => $doc["fields"]["guardadoAutomatico"]["booleanValue"] ?? false
                    ]
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Documento no encontrado"]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Error: " . $e->getMessage()
            ]);
        }
        break;

    case 23: // Actualizar configuración de inventario físico
        $campo = $_POST["campo"] ?? null;
        $valor = ($_POST["valor"] ?? "false") === "true";

        if (!$campo) {
            echo json_encode(["success" => false, "message" => "Falta el campo a actualizar"]);
            exit;
        }

        try {
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONFIG/inventarioFisico?key=$firebaseApiKey&updateMask.fieldPaths=$campo";

            $data = [
                "fields" => [
                    $campo => [ "booleanValue" => $valor ]
                ]
            ];

            $opts = [
                "http" => [
                    "method" => "PATCH",
                    "header" => "Content-Type: application/json",
                    "content" => json_encode($data)
                ]
            ];
            $context = stream_context_create($opts);
            $result = file_get_contents($url, false, $context);

            if ($http_response_header && strpos($http_response_header[0], "200") !== false) {
                echo json_encode(["success" => true, "message" => "Campo $campo actualizado"]);
            } else {
                echo json_encode(["success" => false, "message" => "No se pudo actualizar"]);
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
