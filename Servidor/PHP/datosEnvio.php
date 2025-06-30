<?php
require 'firebase.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');
function obtenerFolio($firebaseProjectId, $firebaseApiKey)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS?key=$firebaseApiKey";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    $foliosData = json_decode($response, true);

    if (isset($foliosData['documents'])) {
        foreach ($foliosData['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['documento']['stringValue']) && $fields['documento']['stringValue'] === "datosEnvio") {
                $folioSiguiente = (int)$fields['folioSiguiente']['integerValue'];
                // Extraemos el ID a partir del nombre completo
                // e.g. projects/PROJECT_ID/databases/(default)/documents/FOLIOS/{docId}
                $fullName = $document['name'];
                $parts    = explode('/', $fullName);
                $docId    = end($parts);

                return [
                    'documentId'    => $docId,
                    'folioSiguiente' => $folioSiguiente
                ];
            }
        }
    }
    return 0;
}
function actualizarFolio($firebaseProjectId, $firebaseApiKey, $folio)
{
    $id = $folio['documentId'];
    $nuevoFolio = $folio['folioSiguiente'] + 1;
    $urlActualizacion = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS/$id?updateMask.fieldPaths=folioSiguiente&key=$firebaseApiKey";
    $data = [
        'fields' => [
            'folioSiguiente' => ['integerValue' => $nuevoFolio]
        ]
    ];
    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($urlActualizacion, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'No se Actualizo el Folio']);
    } else {
        //echo json_encode(['success' => true, 'message' => 'Datos de Envio Guardados']);
    }
}
function formatearClaveCliente($clave)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $clave = trim((string) $clave);
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($clave) === 10) {
        return $clave;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    return str_pad($clave, 10, ' ', STR_PAD_LEFT);
}
function validarEstados($clave)
{
    $filePath = "../../Complementos/CAT_ESTADOS.xml";
    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => "El archivo no existe en la ruta: $filePath"]);
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo json_encode(['success' => false, 'message' => "Error al leer el archivo XML en $filePath"]);
        return;
    }

    try {
        $estados = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        return;
    }

    $encontrado = null;
    foreach ($estados->row as $row) {
        if ((string)$row['Clave'] === $clave && (string)$row['Pais'] === 'MEX') {
            $encontrado = [
                'Clave'       => (string)$row['Clave'],
                'Pais'        => (string)$row['Pais'],
                'Descripcion' => (string)$row['Descripcion']
            ];
            break;
        }
    }

    if ($encontrado !== null) {
        return true;
    } else {
        return false;
    }
}
function validarMunicipios($clave, $estado)
{
    $filePath = "../../Complementos/CAT_MUNICIPIO.xml";
    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => "El archivo no existe en la ruta: $filePath"]);
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo json_encode(['success' => false, 'message' => "Error al leer el archivo XML en $filePath"]);
        return;
    }

    try {
        $estados = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        return;
    }

    $encontrado = null;
    foreach ($estados->row as $row) {
        if ((string)$row['Clave'] === $clave && (string)$row['Estado'] === $estado) {
            $encontrado = [
                'Clave'       => (string)$row['Clave'],
                'Estado'        => (string)$row['Estado'],
                'Descripcion' => (string)$row['Descripcion']
            ];
            break;
        }
    }

    if ($encontrado !== null) {
        return true;
    } else {
        return false;
    }
}
// -------------------------------------------------
// 1) Función genérica para guardar UNA fila en Firestore
// -------------------------------------------------
function guardarDocumentoEnvio(array $fila, int $noEmpresa, string $claveSae, string $firebaseProjectId, string $firebaseApiKey)
{
    $folioInfo         = obtenerFolio($firebaseProjectId, $firebaseApiKey);
    $folioSiguiente    = $folioInfo['folioSiguiente'];
    // Mapear los índices de columna de tu Excel a los campos de Firestore:
    // Ajusta aquí los números de columna (0-based) según tu hoja:
    $clienteId      = trim((string)($fila[0] ?? ''));
    $compania    = trim((string)($fila[1] ?? ''));
    $codigoPostal = trim((string)($fila[2] ?? ''));
    $correo       = trim((string)($fila[3] ?? ''));
    $estado       = trim((string)($fila[4] ?? ''));
    $linea1         = trim((string)($fila[5] ?? ''));
    $linea2         = trim((string)($fila[6] ?? ''));
    $municipio         = trim((string)($fila[7] ?? ''));
    $nombreContacto   = trim((string)($fila[8] ?? ''));
    $telefono         = trim((string)($fila[9] ?? ''));
    $tituloEnvio      = trim((string)($fila[10] ?? ''));

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/ENVIOS?key=$firebaseApiKey";
    $clienteId = formatearClaveCliente($clienteId);

    if (validarEstados($estado)) {
        if (validarMunicipios($municipio, $estado)) {
            $bandera = 0;
        } else {
            $bandera = 2;
        }
    } else {
        $bandera = 1;
    }

    if ($bandera === 0) {
        $fields = [
            'claveCliente'   => ['stringValue'  => $clienteId],
            'id'             => ['integerValue' => $folioSiguiente],
            'tituloEnvio'    => ['stringValue'  => $tituloEnvio],
            'nombreContacto' => ['stringValue'  => $nombreContacto],
            'compania'       => ['stringValue'  => $compania],
            'telefonoContacto' => ['stringValue'  => $telefono],
            'correoContacto' => ['stringValue'  => $correo],
            'linea1'         => ['stringValue'  => $linea1],
            'linea2'         => ['stringValue'  => $linea2],
            'codigoPostal'   => ['stringValue'  => $codigoPostal],
            'estado'         => ['stringValue'  => $estado],
            'municipio'      => ['stringValue'  => $municipio],
            'noEmpresa'      => ['integerValue' => $noEmpresa],
            'claveSae'       => ['stringValue'  => $claveSae],
        ];

        $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_SLASHES);

        $opts = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => $payload,
            ]
        ];
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            throw new \Exception("Error al guardar fila con cliente {$clienteId}");
        }
        // 3) Actualizar folio
        actualizarFolio($firebaseProjectId, $firebaseApiKey, $folioInfo);
        return json_decode($resp, true);
    } else {
        throw new \Exception("Error al guardar fila con cliente {$clienteId}");
    }
}

// -------------------------------------------------
// 2) Punto de entrada
// -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_FILES['excel']['error'] ?? 1) === UPLOAD_ERR_OK) {
    session_start();
    $noEmpresa         = (int)$_SESSION['empresa']['noEmpresa'];
    $claveSae          = $_SESSION['empresa']['claveSae'];

    // 1) Leer el Excel
    $tmp = $_FILES['excel']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($tmp);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true); // A,B,C...
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => "No se pudo leer el Excel: " . $e->getMessage()]);
        exit;
    }

    $errores = [];
    $guardados = 0;
    // 2) Iterar desde la fila 2 (suponiendo fila 1 es encabezado)
    $i = 0;
    foreach ($rows as $idx => $fila) {
        if ($idx === 1) continue; // saltar encabezado
        $i++;
        try {
            guardarDocumentoEnvio(
                array_values($fila),
                $noEmpresa,
                $claveSae,
                $firebaseProjectId,
                $firebaseApiKey
            );
            $guardados++;
        } catch (\Exception $ex) {
            $errores[] = "Fila $idx: " . $ex->getMessage();
        }
    }

    // 4) Responder
    if (count($errores) === 0) {
        echo json_encode([
            'success' => true,
            'message' => "Se guardaron $guardados envíos correctamente.",
            'rows'    => $rows      // <— aquí
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Guardado parcial: $guardados filas, " . count($errores) . " errores.",
            'errors'  => $errores,
            'rows'    => $rows      // opcional, si quieres verlas también
        ]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió un Excel válido.']);
    exit;
}
