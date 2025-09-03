<?php
require_once "firebase.php";
session_start();
header("Content-Type: application/json");

// Función auxiliar para GET a Firestore
function http_get_json($url) {
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
function http_post_json($url, $data, $method = "PATCH") {
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

        if (!$payload || !isset($payload["noInventario"], $payload["claveLinea"], $payload["articulos"])) {
            echo json_encode(["success" => false, "message" => "Datos incompletos"]);
            exit;
        }

        $noInv      = (int)$payload["noInventario"];
        $claveLinea = $payload["claveLinea"];
        $articulos  = $payload["articulos"];
        $status     = $payload["status"] ?? true; // true=editable (autoguardado), false=finalizada

        // 1) Buscar inventario con ese folio
        $invUrl = "$root/INVENTARIO?key=$firebaseApiKey";
        $invDocs = http_get_json($invUrl);

        $invDocId = null;
        if (isset($invDocs["documents"])) {
            foreach ($invDocs["documents"] as $doc) {
                $fields = $doc["fields"];
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

        // 2) Construir body para Firestore
        $data = [
            "fields" => [
                "idAsignado" => ["stringValue" => $usuarioId],
                "status"       => ["booleanValue" => (bool)$status],
            ]
        ];

        foreach ($articulos as $cveArt => $lotes) {
            $arrLotes = [];
            foreach ($lotes as $l) {
                $arrLotes[] = [
                    "mapValue" => [
                        "fields" => [
                            "corrugados"        => ["integerValue" => (int)$l["corrugados"]],
                            "corrugadosPorCaja" => ["integerValue" => (int)$l["corrugadosPorCaja"]],
                            "lote"              => ["stringValue" => (string)$l["lote"]],
                            "total"             => ["integerValue" => (int)$l["total"]],
                        ]
                    ]
                ];
            }
            $data["fields"][$cveArt] = ["arrayValue" => ["values" => $arrLotes]];
        }

        // 3) Guardar en Firestore (PATCH)
        $url = "$root/INVENTARIO/$invDocId/lineas/$claveLinea?key=$firebaseApiKey";
        $resp = http_post_json($url, $data, "PATCH");

        if ($resp) {
            echo json_encode(["success" => true, "message" => "Línea guardada correctamente"]);
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
        $fechaInicio  = isset($fields["fechaInicio"]["stringValue"]) ? $fields["fechaInicio"]["stringValue"] : null;

        echo json_encode([
            "success"      => true,
            "noInventario" => $noInventario,
            "fechaInicio"  => $fechaInicio
        ]);
        break;


    case "obtenerLineas":
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

        foreach ($subcolecciones as $subcol) {
            $lineasUrl = "$root/INVENTARIO/$invDocId/$subcol?key=$firebaseApiKey";
            $lineasDocs = http_get_json($lineasUrl);

            if (!isset($lineasDocs["documents"])) continue;

            // calcular conteo (directo)
            if ($subcol === "lineas") {
                $conteo = 1;
            } else {
                $num = (int) filter_var($subcol, FILTER_SANITIZE_NUMBER_INT);
                $conteo = $num > 0 ? $num : 1;
            }

            // calcular subconteo (pares = mismo número)
            $subconteo = ($conteo === 1) ? 1 : ceil($conteo / 2);


            foreach ($lineasDocs["documents"] as $doc) {
                $fields = $doc["fields"];
                if (
                    (isset($fields["idAsignado"]["stringValue"]) && $fields["idAsignado"]["stringValue"] == $usuarioId) ||
                    (isset($fields["idAsignado"]["integerValue"]) && (string)$fields["idAsignado"]["integerValue"] == (string)$usuarioId)
                ) {
                    $asignadas[] = [
                        "CVE_LIN"   => basename($doc["name"]),
                        "coleccion" => $subcol,
                        "conteo" => $conteo,
                        "subconteo" => $subconteo
                    ];
                }
            }
        }

        echo json_encode(["success" => true, "lineas" => $asignadas]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}
