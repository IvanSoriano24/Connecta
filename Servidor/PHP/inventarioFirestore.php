<?php
global $firebaseApiKey, $firebaseProjectId;
require_once "firebase.php";
session_start();
header("Content-Type: application/json");

if (!function_exists('subcols_for_conteo')) {
function subcols_for_conteo(int $n): array {
    if ($n <= 1) return ['lineas', 'lineas02']; // conteo 1
    $start = ($n - 1) * 2 + 1;                  // 2→3 y 4; 3→5 y 6; etc.
    return [
        'lineas' . str_pad($start,     2, '0', STR_PAD_LEFT),
        'lineas' . str_pad($start + 1, 2, '0', STR_PAD_LEFT),
    ];
}
}
function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey)
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

/*HELPERS COMPARATIVA*/
// --- helpers (ponlos una sola vez en el archivo) ---
function _norm_assigned_ids(array $fields): array {
    $out = [];
    if (isset($fields['idAsignado']['stringValue']))  $out[] = (string)$fields['idAsignado']['stringValue'];
    if (isset($fields['idAsignado']['integerValue'])) $out[] = (string)$fields['idAsignado']['integerValue'];
    if (!empty($fields['idAsignado']['arrayValue']['values'])) {
        foreach ($fields['idAsignado']['arrayValue']['values'] as $v) {
            if (isset($v['stringValue']))  $out[] = (string)$v['stringValue'];
            if (isset($v['integerValue'])) $out[] = (string)$v['integerValue'];
        }
    }
    return array_values(array_unique(array_filter($out, fn($s)=>$s!=='')));
}
function _get_user_name(string $root, string $apiKey, ?string $uid): ?string {
    if (!$uid) return null;
    $url = "$root/USUARIOS/".rawurlencode($uid)."?key=$apiKey";
    $doc = http_get_json($url);
    return $doc['fields']['nombre']['stringValue'] ?? $uid;
}

/**
 * Convierte un campo de tipo arrayValue de Firestore a un array PHP de strings
 */
function _arr_string_from_field($field)
{
    if (!isset($field['arrayValue']['values'])) {
        return [];
    }

    $values = $field['arrayValue']['values'];
    $result = [];

    foreach ($values as $v) {
        if (isset($v['stringValue'])) {
            $result[] = $v['stringValue'];
        }
    }

    return $result;
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
        $payload = json_decode(file_get_contents("php://input"), true);

        if (
            !$payload
            || !isset($payload["noInventario"], $payload["claveLinea"], $payload["articulos"], $payload["subconteo"], $payload["conteo"])
        ) {
            echo json_encode(["success" => false, "message" => "Datos incompletos"]);
            exit;
        }

        $noInv       = (int)$payload["noInventario"];
        $claveLinea  = (string)$payload["claveLinea"];
        $articulosIn = $payload["articulos"];
        $status      = !isset($payload["status"]) || (bool)$payload["status"]; // true → editable
        $subconteoIn = (int)$payload["subconteo"];
        $conteo      = (int)$payload["conteo"];

        $usuarioId = (string)($_SESSION['usuario']['idReal'] ?? '');

        // 🔹 1) Buscar inventario por folio
        $invDocId = $payload["idInventario"] ?? null;
        if (!$invDocId) {
            echo json_encode(["success" => false, "message" => "ID de inventario no especificado"]);
            exit;
        }

        // Obtener datos del inventario actual
        $invUrl = "$root/INVENTARIO/$invDocId?key=$firebaseApiKey";
        $inventarioActivo = http_get_json($invUrl);
        if (!$inventarioActivo || !isset($inventarioActivo["fields"])) {
            echo json_encode(["success" => false, "message" => "Inventario no encontrado"]);
            exit;
        }


        // 🔹 2) Determinar subcolección en base a conteo + subconteo
        function getSubcol($conteo, $subconteo)
        {
            if ($conteo === 1) {
                return ($subconteo === 1) ? "lineas" : "lineas02";
            } else {
                $start = ($conteo - 1) * 2 + ($subconteo === 1 ? 1 : 2);
                return "lineas" . str_pad($start, 2, "0", STR_PAD_LEFT);
            }
        }
        $subcol = getSubcol($conteo, $subconteoIn);

        // 🔹 3) Construir body para Firestore
        $data = [
            "fields" => [
                "idAsignado" => ["stringValue" => $usuarioId],
                "status"     => ["booleanValue" => (bool)$status],
                "updatedAt"  => ["timestampValue" => gmdate('c')],
            ]
        ];

        foreach ($articulosIn as $cveArt => $lotesFront) {
            $arrLotes = [];

            foreach ((array)$lotesFront as $l) {
                $corr    = (int)($l["corrugados"] ?? 0);
                $cxc     = (int)($l["corrugadosPorCaja"] ?? 0);
                $sueltos = (int)($l["sueltos"] ?? 0);

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

            $data["fields"][$cveArt] = ["arrayValue" => ["values" => $arrLotes]];
        }

        // 🔹 4) Guardar en la subcolección detectada
        $url  = "$root/INVENTARIO/$invDocId/$subcol/$claveLinea?key=$firebaseApiKey";
        $resp = http_post_json($url, $data, "PATCH");

        if ($resp) {
            // ---------------------------
            // 🔹 5) Comparar con subcolección par y actualizar productosDiferentes
            // ---------------------------
            function getParSubcol($subcol) {
                if ($subcol === "lineas") return "lineas02";
                if ($subcol === "lineas02") return "lineas";

                if (preg_match('/^lineas(\d{2})$/', $subcol, $m)) {
                    $num = (int)$m[1];
                    $parNum = ($num % 2 === 1) ? $num + 1 : $num - 1;
                    return "lineas" . str_pad($parNum, 2, "0", STR_PAD_LEFT);
                }

                return null;
            }

            $parSubcol = getParSubcol($subcol);
            if ($parSubcol) {
                $parUrl = "$root/INVENTARIO/$invDocId/$parSubcol/$claveLinea?key=$firebaseApiKey";
                $parDoc = http_get_json($parUrl);

                if ($parDoc && isset($parDoc["fields"])) {
                    // Recuperar array actual de productosDiferentes
                    $invFields = $inventarioActivo["fields"] ?? [];
                    $currentDif = [];
                    if (isset($invFields["productosDiferentes"]["arrayValue"]["values"])) {
                        foreach ($invFields["productosDiferentes"]["arrayValue"]["values"] as $val) {
                            if (isset($val["stringValue"])) {
                                $currentDif[] = $val["stringValue"];
                            }
                        }
                    }

                    // Recorremos artículos y actualizamos lista
                    foreach ($articulosIn as $cveArt => $lotesFront) {
                        $totGuardado = 0;
                        foreach ($lotesFront as $l) {
                            $corr    = (int)($l["corrugados"] ?? 0);
                            $cxc     = (int)($l["corrugadosPorCaja"] ?? 0);
                            $sueltos = (int)($l["sueltos"] ?? 0);

                            if (isset($l["totales"]) && $l["totales"] !== '') {
                                $totGuardado += (int)$l["totales"];
                            } elseif (isset($l["piezas"]) && $l["piezas"] !== '') {
                                $totGuardado += (int)$l["piezas"];
                            } else {
                                $totGuardado += ($corr * $cxc) + $sueltos;
                            }
                        }

                        $totPar = 0;
                        if (isset($parDoc["fields"][$cveArt]["arrayValue"]["values"])) {
                            foreach ($parDoc["fields"][$cveArt]["arrayValue"]["values"] as $lot) {
                                $totPar += (int)($lot["mapValue"]["fields"]["total"]["integerValue"] ?? 0);
                            }
                        }

                        if ($totPar !== $totGuardado) {
                            if (!in_array($cveArt, $currentDif)) {
                                $currentDif[] = $cveArt;
                            }
                        } else {
                            // 🔹 Coinciden los subconteos → comparar con SAE
                            $noEmpresa = $_SESSION['empresa']['noEmpresa'] ?? null;
                            $claveSae = $_SESSION['empresa']['claveSae'] ?? null;

                            if ($noEmpresa && $claveSae) {
                                // Obtener conexión al SAE
                                $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
                                $conexionData = $conexionResult['data'];

                                // Abrir conexión SQL Server
                                $conn = sqlsrv_connect($conexionData['host'], [
                                    "Database" => $conexionData['nombreBase'],
                                    "UID" => $conexionData['usuario'],
                                    "PWD" => $conexionData['password'],
                                    "CharacterSet" => "UTF-8",
                                    "TrustServerCertificate" => true
                                ]);

                                if ($conn) {
                                    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
                                    $sql = "SELECT EXIST FROM $nombreTabla WHERE CVE_ART = ?";
                                    $param = [$cveArt];
                                    $stmt = sqlsrv_query($conn, $sql, $param);

                                    if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                                        $existenciaSAE = (float)$row['EXIST'];
                                    } else {
                                        $existenciaSAE = null;
                                    }
                                    sqlsrv_close($conn);

                                    // 🔸 Comparar totales Firestore vs SAE
                                    if ($existenciaSAE !== null && $existenciaSAE != $totGuardado) {
                                        if (!in_array($cveArt, $currentDif)) {
                                            $currentDif[] = $cveArt;
                                        }
                                    } elseif ($existenciaSAE !== null && $existenciaSAE == $totGuardado) {
                                        // Coincide con SAE → eliminar si estaba
                                        $currentDif = array_values(array_filter($currentDif, fn($x) => $x !== $cveArt));
                                    }
                                } else {
                                    error_log("❌ No se pudo conectar a la base de datos SAE para comparar $cveArt");
                                }
                            }

                        }
                    }

                    // Actualizar SOLO productosDiferentes
                    $updateBody = [
                        "fields" => [
                            "productosDiferentes" => [
                                "arrayValue" => [
                                    "values" => array_map(fn($cve) => ["stringValue" => $cve], $currentDif)
                                ]
                            ]
                        ]
                    ];
                    $cabUrl = "$root/INVENTARIO/$invDocId?updateMask.fieldPaths=productosDiferentes&key=$firebaseApiKey";
                    http_post_json($cabUrl, $updateBody, "PATCH");
                }
            }

            echo json_encode([
                "success"       => true,
                "message"       => "Línea guardada correctamente",
                "subcoleccion"  => $subcol,
                "claveLinea"    => $claveLinea,
                "status"        => $status,
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al guardar la línea"]);
        }
        break;

    case "obtenerInventarioActivo":
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];

        $invUrl  = "$root/INVENTARIO?key=$firebaseApiKey";
        $invDocs = http_get_json($invUrl);

        if (!isset($invDocs["documents"])) {
            echo json_encode(["success" => false, "message" => "No hay inventarios"]);
            exit;
        }

        $inventarioActivo = null;
        foreach ($invDocs["documents"] as $doc) {
            $fields = $doc["fields"];

            // 🔹 Validar que sea de la empresa en sesión y que esté activo
            if (
                isset($fields["status"]["booleanValue"]) &&
                $fields["status"]["booleanValue"] === true &&
                isset($fields["noEmpresa"]["integerValue"]) &&
                (int)$fields["noEmpresa"]["integerValue"] === (int)$noEmpresa
            ) {
                $inventarioActivo = $doc;
                break;
            }
        }

        if (!$inventarioActivo) {
            echo json_encode(["success" => false, "message" => "Inventario activo no encontrado para la empresa"]);
            exit;
        }

        $fields = $inventarioActivo["fields"];
        $noInv  = (int)($fields["noInventario"]["integerValue"] ?? 0);
        $fechaI = $fields["fechaInicio"]["stringValue"] ?? null; // guardas fecha como string
        $fechaF = $fields["fechaFin"]["stringValue"] ?? null;    // puede no existir

        echo json_encode([
            "success"      => true,
            "noInventario" => $noInv,
            "fechaInicio"  => $fechaI,
            "fechaFin"     => $fechaF
        ]);
        break;

    case "obtenerLineas":
        $tipoUsuario = $_SESSION['usuario']["tipoUsuario"] ?? null;
        $usuarioId   = $_SESSION['usuario']["idReal"] ?? null;
        $noEmpresa   = $_SESSION['empresa']['noEmpresa'] ?? null;

        $invUrl  = "$root/INVENTARIO?key=$firebaseApiKey";
        $invDocs = http_get_json($invUrl);

        if (!isset($invDocs["documents"])) {
            echo json_encode(["success" => false, "message" => "No hay inventarios"]);
            exit;
        }

        // 🔹 Buscar inventario activo de la empresa en sesión
        $inventarioActivo = null;
        foreach ($invDocs["documents"] as $doc) {
            $fields = $doc["fields"] ?? [];

            $status     = $fields["status"]["booleanValue"] ?? false;
            $empresaDoc = isset($fields["noEmpresa"]["integerValue"]) ? (int)$fields["noEmpresa"]["integerValue"] : null;

            if ($status === true && $empresaDoc === (int)$noEmpresa) {
                $inventarioActivo = $doc;
                break;
            }
        }

        if (!$inventarioActivo) {
            echo json_encode(["success" => false, "message" => "Inventario activo no encontrado para la empresa"]);
            exit;
        }

        $invDocId  = basename($inventarioActivo["name"]);
        $fields    = $inventarioActivo["fields"];
        $asignadas = [];

        // Obtener productos diferentes para filtrar líneas activas
        $productosDif = _arr_string_from_field($fields["productosDiferentes"] ?? []);

        // Extraer prefijos activos (AD, JD, etc.) desde productosDiferentes
        $prefijosActivos = [];
        foreach ($productosDif as $p) {
            if (preg_match('/^([A-Z]+)/i', $p, $m)) {
                $prefijosActivos[] = strtoupper($m[1]);
            }
        }
        $prefijosActivos = array_unique($prefijosActivos);


        // Tomar conteo máximo del inventario
        $maxConteo = isset($fields["conteo"]["integerValue"])
            ? (int)$fields["conteo"]["integerValue"]
            : 1;

        $subcolecciones = [];

        // Generar todas las subcolecciones desde conteo 1 hasta el actual
        for ($c = 1; $c <= $maxConteo; $c++) {
            if ($c === 1) {
                $subcolecciones[] = ["nombre" => "lineas", "conteo" => $c, "subconteo" => 1];
                $subcolecciones[] = ["nombre" => "lineas02", "conteo" => $c, "subconteo" => 2];
            } else {
                $start = ($c - 1) * 2 + 1;
                $subcolecciones[] = ["nombre" => "lineas" . str_pad($start, 2, "0", STR_PAD_LEFT), "conteo" => $c, "subconteo" => 1];
                $subcolecciones[] = ["nombre" => "lineas" . str_pad($start + 1, 2, "0", STR_PAD_LEFT), "conteo" => $c, "subconteo" => 2];
            }
        }

        // Acceso a asignaciones
        $asignaciones = $fields["asignaciones"]["mapValue"]["fields"] ?? [];

        foreach ($subcolecciones as $subcol) {
            $lineasUrl  = "$root/INVENTARIO/$invDocId/{$subcol['nombre']}?key=$firebaseApiKey";
            $lineasDocs = http_get_json($lineasUrl);

            if (!isset($lineasDocs["documents"])) continue;

            foreach ($lineasDocs["documents"] as $doc) {
                $docId  = basename($doc["name"]);
                $status = $doc["fields"]["status"]["booleanValue"] ?? null;

                // Saltar si el prefijo del docId (AD, JD, etc.) ya no está activo
                if (!empty($prefijosActivos) && !in_array(strtoupper($docId), $prefijosActivos)) {
                    continue;
                }


                // Leer asignaciones para esta línea
                $usuariosAsignados = [];
                if (isset($asignaciones[$docId]["arrayValue"]["values"])) {
                    foreach ($asignaciones[$docId]["arrayValue"]["values"] as $val) {
                        if (isset($val["stringValue"])) {
                            $usuariosAsignados[] = $val["stringValue"];
                        } elseif (isset($val["nullValue"])) {
                            $usuariosAsignados[] = null;
                        } else {
                            $usuariosAsignados[] = null; // fallback seguro
                        }
                    }
                }


                foreach ($usuariosAsignados as $idx => $usuarioAsignado) {
                    $registro = [
                        "CVE_LIN"   => $docId,
                        "coleccion" => $subcol['nombre'],
                        "conteo"    => $subcol['conteo'],
                        "subconteo" => $idx + 1, // posición = subconteo
                        "status"    => $status,
                        "asignadoA" => $usuarioAsignado
                    ];

                    if ($tipoUsuario === "SUPER-ALMACENISTA" || $usuarioAsignado === $usuarioId) {
                        $asignadas[] = $registro;
                    }
                }
            }
        }

        $asignadas = array_values(array_unique($asignadas, SORT_REGULAR));
        echo json_encode(["success" => true, "lineas" => $asignadas]);
        break;

    case 'obtenerLineaConteos': {
            $noInv      = (int)($_GET['noInventario'] ?? 0);
            $conteo     = (int)($_GET['conteo'] ?? 1);      // ← usa el conteo que te mandan (default 1)
            $claveLinea = (string)($_GET['claveLinea'] ?? '');
            if (!$noInv || !$claveLinea) {
                echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
                exit;
            }

            // 1) localizar inventario por folio
            $invUrl  = "$root/INVENTARIO?key=$firebaseApiKey";
            $invDocs = http_get_json($invUrl);
            $invDocId = null;
            if (!empty($invDocs['documents'])) {
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

            // 2) Elegir el par según el CONTEO solicitado (sin recorrer todos)
            [$subA, $subB] = subcols_for_conteo($conteo);

            $uA  = "$root/INVENTARIO/$invDocId/$subA/$claveLinea?key=$firebaseApiKey";
            $uB  = "$root/INVENTARIO/$invDocId/$subB/$claveLinea?key=$firebaseApiKey";
            $docA = http_get_json($uA);
            $docB = http_get_json($uB);

            // Si quisieras fallback automático al par anterior cuando no hay nada en este conteo,
            // descomenta este bloque:
            /*
        if ((empty($docA['fields']) && empty($docB['fields'])) && $conteo > 1) {
            [$subA, $subB] = subcols_for_conteo($conteo - 1);
            $uA  = "$root/INVENTARIO/$invDocId/$subA/$claveLinea?key=$firebaseApiKey";
            $uB  = "$root/INVENTARIO/$invDocId/$subB/$claveLinea?key=$firebaseApiKey";
            $docA = http_get_json($uA);
            $docB = http_get_json($uB);
        }
        */

            // 3) Resolver usuarios (idAsignado) → nombre
            $user1 = null;
            $user2 = null;
            if (!empty($docA['fields'])) {
                $ids  = _norm_assigned_ids($docA['fields']);
                $uid  = $ids[0] ?? null;
                $user1 = $uid ? ['id' => $uid, 'name' => _get_user_name($root, $firebaseApiKey, $uid)] : null;
            }
            if (!empty($docB['fields'])) {
                $ids  = _norm_assigned_ids($docB['fields']);
                $uid  = $ids[0] ?? null;
                $user2 = $uid ? ['id' => $uid, 'name' => _get_user_name($root, $firebaseApiKey, $uid)] : null;
            }

            echo json_encode([
                'success'   => true,
                'conteoIdx' => $conteo,                    // ← ahora refleja el conteo pedido
                'subs'      => ['a' => $subA, 'b' => $subB],
                'conteo1'   => (!empty($docA['fields']) ? $docA : null),
                'conteo2'   => (!empty($docB['fields']) ? $docB : null),
                'user1'     => $user1,
                'user2'     => $user2
            ]);
            exit;
        }

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
        $claveLinea   = $_GET['claveLinea'] ?? null;
        $conteo       = isset($_GET['conteo']) ? (int)$_GET['conteo'] : null;
        $subconteo    = isset($_GET['subconteo']) ? (int)$_GET['subconteo'] : null;

        if (!$idInventario || !$claveLinea || !$conteo || !$subconteo) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
            exit;
        }

        // 🔹 Calcular nombre de subcolección
        function getSubcol($conteo, $subconteo)
        {
            if ($conteo === 1) {
                return ($subconteo === 1) ? "lineas" : "lineas02";
            } else {
                $start = ($conteo - 1) * 2 + ($subconteo === 1 ? 1 : 2);
                return "lineas" . str_pad($start, 2, "0", STR_PAD_LEFT);
            }
        }
        $subcol = getSubcol($conteo, $subconteo);

        // 🔹 Consultar documento en Firestore
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario/$subcol/$claveLinea?key=$firebaseApiKey";
        $res = @file_get_contents($url);
        $doc = $res ? json_decode($res, true) : null;

        $finalizada = false;
        if ($doc && isset($doc['fields']['status']['booleanValue'])) {
            $finalizada = ($doc['fields']['status']['booleanValue'] === false);
        }

        echo json_encode(['success' => true, 'finalizada' => $finalizada]);
        break;

    case "obtenerProductosDiferentes":
        $invUrl = "$root/INVENTARIO?key=$firebaseApiKey";
        $invDocs = http_get_json($invUrl);

        if (!isset($invDocs["documents"])) {
            echo json_encode(["success" => false, "message" => "No hay inventarios"]);
            exit;
        }

        // Buscar inventario activo
        $inventarioActivo = null;
        foreach ($invDocs["documents"] as $doc) {
            if (
                isset($doc["fields"]["status"]["booleanValue"]) &&
                $doc["fields"]["status"]["booleanValue"] === true
            ) {
                $inventarioActivo = $doc;
                break;
            }
        }

        if (!$inventarioActivo) {
            echo json_encode(["success" => false, "message" => "Inventario activo no encontrado"]);
            exit;
        }

        $fields = $inventarioActivo["fields"] ?? [];
        $productosDiferentes = [];

        if (isset($fields["productosDiferentes"]["arrayValue"]["values"])) {
            foreach ($fields["productosDiferentes"]["arrayValue"]["values"] as $val) {
                if (isset($val["stringValue"])) {
                    $productosDiferentes[] = $val["stringValue"];
                }
            }
        }

        $noInventario = isset($fields["noInventario"]["integerValue"])
            ? (int)$fields["noInventario"]["integerValue"]
            : null;

        echo json_encode([
            "success" => true,
            "noInventario" => $noInventario,
            "productosDiferentes" => $productosDiferentes
        ]);
        break;

    case "obtenerConteoActual":
        $idInventario = $_GET['idInventario'] ?? null;
        if (!$idInventario) {
            echo json_encode(["success" => false, "message" => "Falta ID"]);
            exit;
        }

        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/INVENTARIO/$idInventario?key=$firebaseApiKey";
        $res = file_get_contents($url);
        $doc = json_decode($res, true);

        $conteo = (int)($doc['fields']['conteo']['integerValue'] ?? 0);
        echo json_encode(["success" => true, "conteo" => $conteo]);
        break;


    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}