<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
//include 'utils.php';
session_start();

/****************************************/
function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        return ['success' => false, 'message' => 'Error al obtener los datos de Firebase'];
    }
    $documents = json_decode($result, true);
    if (!isset($documents['documents'])) {
        return ['success' => false, 'message' => 'No se encontraron documentos'];
    }
    // Busca el documento donde coincida el campo `noEmpresa`
    foreach ($documents['documents'] as $document) {
        $fields = $document['fields'];
        /*var_dump($fields['noEmpresa']['integerValue']);
        var_dump($noEmpresa);*/
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'nombreBanco'
                    => $fields['nombreBanco']['stringValue'] ?? "",
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
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
        $sql = "SELECT I.CVE_ART, I.DESCR, LIN_PROD, I.EXIST, L.CVE_ART AS ProductoLote, L.LOTE, L.CANTIDAD AS CantidadLote
            FROM $nombreTabla I
            INNER JOIN $nombreTabla2 L ON L.CVE_ART = I.CVE_ART
            WHERE I.LIN_PROD = ?";
        $param = [$linea];
        $stmt   = sqlsrv_query($conn, $sql, $param);
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
        'terminada' => $status
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
function buscarInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey){
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
function getInventarioDocByFolio($noEmpresa, $noInventario, $projectId, $apiKey){
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
function guardarProducto($noEmpresa, $noInventario, $firebaseProjectId, $firebaseApiKey){
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
    // 1) Resolver inventario activo por empresa + folio
    $inv = getInventarioDocByFolioAsignacion((int)$noEmpresa, (int)$noInventario, $projectId, $apiKey);
    if (!$inv || empty($inv['docId'])) {
        return ['success' => false, 'message' => 'Inventario activo no encontrado'];
    }
    $invDocId = $inv['docId'];

    // 2) Construir mapValue para "asignaciones"
    //    Estructura: asignaciones: { "001": {stringValue: "usuarioId"}, "002": {stringValue: "usuarioId2"} }
    $mapFields = [];
    foreach ($asignaciones as $lineaId => $idUsuario) {
        if ($lineaId === '' || $idUsuario === '' || $idUsuario === null) continue;
        $mapFields[(string)$lineaId] = ['stringValue' => (string)$idUsuario];
    }

    // Si quieres sobreescribir COMPLETAMENTE el map con lo enviado (y eliminar no enviados):
    // -> Parchamos con el map que traemos (aunque sea vacío).
    // Si prefieres *fusionar* con lo existente, primero trae el doc, mezcla, y luego parchea.
    $root  = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents";
    $url   = "$root/INVENTARIO/$invDocId?key=$apiKey&updateMask.fieldPaths=asignaciones";

    $body = [
        'fields' => [
            'asignaciones' => [
                'mapValue' => [
                    'fields' => $mapFields  // puede ser []
                ]
            ]
        ]
    ];

    $resp = http_patch_jsonAsignacion($url, $body);
    if (!$resp) {
        return ['success' => false, 'message' => 'No se pudo guardar asignaciones'];
    }

    return ['success' => true];
}
//////////////////////////GUARDAR ASIGNACION/////////////////////////////////////////



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
    case '10': // Guardar asignaciones de líneas -> campo "asignaciones" en INVENTARIO
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

    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
