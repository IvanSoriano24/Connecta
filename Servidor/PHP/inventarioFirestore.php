<?php
require_once "firebase.php";
session_start();
header("Content-Type: application/json");

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

// Función auxiliar para GET a Firestore
function http_get_json($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    if (curl_errno($ch)) {
        return null;
    }
    curl_close($ch);
    return json_decode($res, true);
}

// Función auxiliar para PATCH/POST a Firestore
function http_post_json($url, $data, $method = "PATCH")
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $res = curl_exec($ch);
    if (curl_errno($ch)) {
        return null;
    }
    curl_close($ch);
    return json_decode($res, true);
}


function conteo_to_subcol(int $conteo): string
{
    return $conteo <= 1 ? 'lineas' : ('lineas' . str_pad((string)$conteo, 2, '0', STR_PAD_LEFT));
}

function detectar_subcol_por_asignacion(string $root, string $invDocId, string $lineaId, string $usuarioId, string $apiKey): ?string
{
    // Lee doc INVENTARIO para ver `asignaciones`
    $invUrl = "$root/INVENTARIO/$invDocId?key=$apiKey";
    $invDoc = http_get_json($invUrl);
    if (!isset($invDoc['fields']['asignaciones']['mapValue']['fields'][$lineaId])) return null;

    $arr = $invDoc['fields']['asignaciones']['mapValue']['fields'][$lineaId];
    if (!isset($arr['arrayValue']['values']) || !is_array($arr['arrayValue']['values'])) return null;

    $uids = array_map(function ($v) {
        return $v['stringValue'] ?? null;
    }, $arr['arrayValue']['values']);
    $idx = array_search($usuarioId, $uids, true);
    if ($idx === false) return null;

    // posición 0 => conteo 1 => lineas
    $conteo = $idx + 1;
    //var_dump("Asignacion: ", $conteo);
    return conteo_to_subcol($conteo);
}

function detectar_subcol_por_existencia(string $root, string $invDocId, string $lineaId, string $usuarioId, string $apiKey): ?string
{
    $subcols = ['lineas', 'lineas02', 'lineas03', 'lineas04', 'lineas05', 'lineas06'];
    foreach ($subcols as $sc) {
        $url = "$root/INVENTARIO/$invDocId/$sc/$lineaId?key=$apiKey";
        $doc = http_get_json($url);
        if (!isset($doc['fields'])) continue;
        $f = $doc['fields'];
        // admite idAsignado guardado como string o integerValue
        $actual = $f['idAsignado']['stringValue'] ?? (string)($f['idAsignado']['integerValue'] ?? '');
        if ($actual !== '' && (string)$usuarioId === (string)$actual) {
            //var_dump("Existencia: ", $sc);
            return $sc;
        }
    }

    return null;
}

function obtenerProductos($articulos, $conexionData, $claveSae)
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
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $data = [];
        foreach ($articulos as $art) {
            $sql = "SELECT CVE_ART, DESCR, EXIST
            FROM $nombreTabla
            WHERE CVE_ART = ?";
            $param = [$art['cve_art']];
            $stmt = sqlsrv_query($conn, $sql, $param);
            if ($stmt === false) {
                $errors = print_r(sqlsrv_errors(), true);
                throw new Exception("Problema al optener los productos:\n{$errors}");
            }
            // Procesar resultados
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
        }

        //var_dump($datos);

        if (!empty($data)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
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

// Verificar sesión
if (!isset($_SESSION['usuario']['idReal'])) {
    echo json_encode(["success" => false, "message" => "No hay sesión activa"]);
    exit;
}
$usuarioId = $_SESSION['usuario']['idReal'];

$accion = $_GET['accion'] ?? null;

$root = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";

switch ($accion) {

    // Guardar todos los artículos de una línea (autoguardado/finalizar)
    case "guardarLinea":
    // $root y $firebaseApiKey ya definidos en tu archivo
    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload || !isset($payload["noInventario"], $payload["claveLinea"], $payload["articulos"])) {
        echo json_encode(["success" => false, "message" => "Datos incompletos"]);
        exit;
    }

    $noInv       = (int)$payload["noInventario"];
    $claveLinea  = (string)$payload["claveLinea"];
    $articulosIn = $payload["articulos"]; // { CVE_ART: [ {lote,corrugados,corrugadosPorCaja,sueltos,piezas,totales}, ... ], ... }
    $status      = isset($payload["status"]) ? (bool)$payload["status"] : true; // true=editable, false=finalizada
    $conteoIn    = isset($payload["conteo"]) ? (int)$payload["conteo"] : null;

    // Usuario actual (quien guarda la línea)
    $usuarioId = (string)($_SESSION['usuario']['idReal'] ?? '');

    // 1) Buscar inventario por folio
    $invUrl  = "$root/INVENTARIO?key=$firebaseApiKey";
    $invDocs = http_get_json($invUrl);

    $invDocId = null;
    if (isset($invDocs["documents"])) {
        foreach ($invDocs["documents"] as $doc) {
            $fields = $doc["fields"] ?? [];
            if (isset($fields["noInventario"]["integerValue"]) && (int)$fields["noInventario"]["integerValue"] === $noInv) {
                $invDocId = basename($doc["name"]);
                break;
            }
        }
    }
    if (!$invDocId) {
        echo json_encode(["success" => false, "message" => "Inventario no encontrado"]);
        exit;
    }

    // 2) Determinar subcolección destino
    if ($conteoIn !== null && $conteoIn > 0) {
        $subcol = conteo_to_subcol($conteoIn);
    } else {
        $subcol = detectar_subcol_por_asignacion($root, $invDocId, $claveLinea, $usuarioId, $firebaseApiKey)
              ?? detectar_subcol_por_existencia($root, $invDocId, $claveLinea, $usuarioId, $firebaseApiKey)
              ?? 'lineas';
    }

    // 3) Construir body para Firestore
    //    Se guarda un campo por producto (CVE_ART) como arrayValue de lotes (mapValue)
    $data = [
        "fields" => [
            "idAsignado" => ["stringValue" => $usuarioId],
            "status"     => ["booleanValue" => (bool)$status],
            "updatedAt"  => ["timestampValue" => gmdate('c')],
        ]
    ];

    foreach ($articulosIn as $cveArt => $lotesFront) {
        $arrLotes = [];

        // Lotes que vienen del front (cada uno con: lote, corrugados, corrugadosPorCaja, sueltos, piezas, totales)
        foreach ((array)$lotesFront as $l) {
            $corr   = (int)($l["corrugados"] ?? 0);
            $cxc    = (int)($l["corrugadosPorCaja"] ?? 0);
            $sueltos= (int)($l["sueltos"] ?? 0);

            // prioridad total: totales -> piezas -> cálculo
            if (isset($l["totales"]) && $l["totales"] !== '') {
                $totalLote = (int)$l["totales"];
            } elseif (isset($l["piezas"]) && $l["piezas"] !== '') {
                $totalLote = (int)$l["piezas"];
            } else {
                $totalLote = ($corr * $cxc) + $sueltos;
            }

            $lote = (string)($l["lote"] ?? "");

            $arrLotes[] = [
                "mapValue" => [
                    "fields" => [
                        "corrugados"        => ["integerValue" => $corr],
                        "corrugadosPorCaja" => ["integerValue" => $cxc],
                        "sueltos"           => ["integerValue" => $sueltos],
                        "lote"              => ["stringValue"  => $lote],
                        "total"             => ["integerValue" => (int)$totalLote],
                    ]
                ]
            ];
        }

        // Campo del producto en Firestore
        $data["fields"][$cveArt] = ["arrayValue" => ["values" => $arrLotes]];
    }

    // 4) Guardar en la subcolección detectada
    $url  = "$root/INVENTARIO/$invDocId/$subcol/$claveLinea?key=$firebaseApiKey";
    $resp = http_post_json($url, $data, "PATCH");

    if ($resp) {
        echo json_encode([
            "success"       => true,
            "message"       => "Línea guardada correctamente",
            "subcoleccion"  => $subcol,
            "claveLinea"    => $claveLinea
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al guardar la línea"]);
    }
    break;

    case "obtenerInventarioActivo":
        $invUrl = "$root/INVENTARIO?key=$firebaseApiKey";
        $invDocs = http_get_json($invUrl);

        if (!isset($invDocs["documents"])) {
            echo json_encode(["success" => false, "message" => "No hay inventarios"]);
            exit;
        }

        $inventarioActivo = null;
        foreach ($invDocs["documents"] as $doc) {
            if (isset($doc["fields"]["status"]["booleanValue"]) && $doc["fields"]["status"]["booleanValue"] === true) {
                $inventarioActivo = $doc;
                break;
            }
        }

        if (!$inventarioActivo) {
            echo json_encode(["success" => false, "message" => "Inventario activo no encontrado"]);
            exit;
        }

        $fields = $inventarioActivo["fields"];
        $noInventario = isset($fields["noInventario"]["integerValue"]) ? (int)$fields["noInventario"]["integerValue"] : null;
        $fechaInicio = isset($fields["fechaInicio"]["stringValue"]) ? $fields["fechaInicio"]["stringValue"] : null;

        echo json_encode([
            "success" => true,
            "noInventario" => $noInventario,
            "fechaInicio" => $fechaInicio
        ]);
        break;

    case "obtenerLineas":
        $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
        $usuarioId = $_SESSION['usuario']["idReal"]; // asegúrate que este campo existe en tu sesión
        $invUrl = "$root/INVENTARIO?key=$firebaseApiKey";
        $invDocs = http_get_json($invUrl);

        if (!isset($invDocs["documents"])) {
            echo json_encode(["success" => false, "message" => "No hay inventarios"]);
            exit;
        }

        $inventarioActivo = null;
        foreach ($invDocs["documents"] as $doc) {
            if (isset($doc["fields"]["status"]["booleanValue"]) && $doc["fields"]["status"]["booleanValue"] === true) {
                $inventarioActivo = $doc;
                break;
            }
        }

        if (!$inventarioActivo) {
            echo json_encode(["success" => false, "message" => "Inventario activo no encontrado"]);
            exit;
        }

        $invDocId = basename($inventarioActivo["name"]);
        $asignadas = [];

        // 🔹 recorrer todas las posibles subcolecciones (lineas, lineas02, lineas03, ...)
        $subcolecciones = ["lineas", "lineas02", "lineas03", "lineas04", "lineas05", "lineas06"];
        // 🔹 Tomar el campo "conteo" del documento padre
        $conteo = isset($inventarioActivo["fields"]["conteo"]["integerValue"])
            ? (int)$inventarioActivo["fields"]["conteo"]["integerValue"]
            : 1;

        // recorrer solo las subcolecciones que existen (lineas, lineas02)
        $subcolecciones = ["lineas", "lineas02"];

        foreach ($subcolecciones as $subcol) {
            $lineasUrl = "$root/INVENTARIO/$invDocId/$subcol?key=$firebaseApiKey";
            $lineasDocs = http_get_json($lineasUrl);

            if (!isset($lineasDocs["documents"])) continue;

            // calcular conteo (directo)
            if ($subcol === "lineas") {
                $conteo = 1;
            } else {
                $num = (int)filter_var($subcol, FILTER_SANITIZE_NUMBER_INT);
                $conteo = $num > 0 ? $num : 1;
            }

            // calcular subconteo (pares = mismo número)
            $subconteo = ($conteo === 1) ? 1 : ceil($conteo / 2);
            foreach ($lineasDocs["documents"] as $doc) {
                $fields = $doc["fields"];

                // ✅ Si es SUPER-ALMACENISTA, no filtramos
                if ($tipoUsuario === "SUPER-ALMACENISTA" || (isset($fields["idAsignado"]["stringValue"]) && $fields["idAsignado"]["stringValue"] == $usuarioId) || (isset($fields["idAsignado"]["integerValue"]) && (string)$fields["idAsignado"]["integerValue"] == (string)$usuarioId)) {
                    $asignadas[] = [
                        "CVE_LIN" => basename($doc["name"]),
                        "coleccion" => $subcol,
                        "conteo" => $conteo,
                        "subconteo" => $subconteo
                    ];
                }
            }
        }

        echo json_encode(["success" => true, "lineas" => $asignadas]);
        break;

    case 'obtenerLineaConteos':
        $noInv = (int)($_GET['noInventario'] ?? 0);
        $claveLinea = (string)($_GET['claveLinea'] ?? '');
        if (!$noInv || !$claveLinea) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            exit;
        }

        // 1) localizar inventario por folio
        $invUrl = "$root/INVENTARIO?key=$firebaseApiKey";
        $invDocs = http_get_json($invUrl);
        $invDocId = null;
        if (isset($invDocs['documents'])) {
            foreach ($invDocs['documents'] as $doc) {
                $fields = $doc['fields'] ?? [];
                if ((int)($fields['noInventario']['integerValue'] ?? 0) === $noInv) {
                    $invDocId = basename($doc['name']);
                    break;
                }
            }
        }
        if (!$invDocId) {
            echo json_encode(['success' => false, 'message' => 'Inventario no encontrado']);
            exit;
        }

        // 2) traer ambos docs
        $url1 = "$root/INVENTARIO/$invDocId/lineas/$claveLinea?key=$firebaseApiKey";
        $url2 = "$root/INVENTARIO/$invDocId/lineas02/$claveLinea?key=$firebaseApiKey";

        $doc1 = http_get_json($url1);
        $doc2 = http_get_json($url2);

        // si no existen, devolver success true pero vacíos
        echo json_encode([
            'success' => true,
            'conteo1' => $doc1 && isset($doc1['fields']) ? $doc1 : null,
            'conteo2' => $doc2 && isset($doc2['fields']) ? $doc2 : null
        ]);
        exit;

    case 'obtenerInventario':
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        if (isset($_GET['articulos'])) {
            $articulos = $_GET['articulos'];
            obtenerProductos($articulos, $conexionData, $claveSae);
        } else {
            echo json_encode(["success" => false, "message" => "Faltan Articulos"]);
        }
        break;

    case "verificarLinea":
        $idInventario = $_GET['idInventario'] ?? null;
        $claveLinea = $_GET['claveLinea'] ?? null;
        $subcol = $_GET['subcol'] ?? null;

        if (!$idInventario || !$claveLinea || !$subcol) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
            exit;
        }

        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/$subcol/$claveLinea?key=$firebaseApiKey";
        $res = @file_get_contents($url);
        $doc = $res ? json_decode($res, true) : null;

        $finalizada = false;
        if ($doc && isset($doc['fields']['status']['booleanValue'])) {
            $finalizada = ($doc['fields']['status']['booleanValue'] === false);
        }

        echo json_encode(['success' => true, 'finalizada' => $finalizada]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}