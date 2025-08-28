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
                    'nombreBanco' => $fields['nombreBanco']['stringValue'] ?? "",
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
    //return $noInventario;
    echo json_encode(['success' => true, 'noInventario' => $noInventario]);
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
        echo json_encode($result);
        return;
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

    echo json_encode($result);
}
function guardarProducto(){
    $payload = json_decode($_POST['payload'] ?? 'null', true);
    if (!$payload) {
        echo json_encode(['success' => false, 'message' => 'Payload inválido']);
        exit;
    }

    // Validaciones mínimas
    $req = ['linea', 'noInventario', 'noEmpresa', 'cve_art'];
    foreach ($req as $k) {
        if (empty($payload[$k])) {
            echo json_encode(['success' => false, 'message' => "Falta $k"]);
            exit;
        }
    }

    // Guardar en Firestore:
    // - Colección: INVENTARIO_CAPTURA (o subcolección en INVENTARIO/{noInventario}/DETALLE)
    // - Documento: por ejemplo INVENTARIO/{noInventario}/LINEAS/{linea}/PRODUCTOS/{cve_art}
    // - Campos: lo del payload + serverTimestamp

    // ... tu lógica Firestore ...

    echo json_encode(['success' => true, 'docId' => 'AA-1625', 'updated' => true]);
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
        buscarInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        break;
        break;
    case 2:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        noInventario($noEmpresa, $firebaseProjectId, $firebaseApiKey);
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
        $noInventario = 1;
        echo json_encode(['success' => true, 'noInventario' => $noInventario]);
        //guardarProducto();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida Ventas.']);
        //echo json_encode(['success' => false, 'message' => 'No hay funcion.']);
        break;
}
