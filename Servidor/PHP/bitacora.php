<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';

/**
 * Obtiene el documento de folios asociado al módulo indicado.
 *
 * Espera encontrar en la colección FOLIOS un documento con el campo
 * `documento` igual al nombre suministrado (por defecto BITACORA_GENERAL).
 *
 * @param string $documento Nombre del documento/módulo dentro de FOLIOS.
 * @return array|null       Arreglo con `documentId` y `folioSiguiente`, o null si no se encuentra.
 */
function obtenerFolioFirestore(string $documento = 'bitacora'): ?array
{
    global $firebaseProjectId, $firebaseApiKey;

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";
    $payload = json_encode([
        'structuredQuery' => [
            'from' => [
                ['collectionId' => 'FOLIOS']
            ],
            'where' => [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'documento'],
                    'op'    => 'EQUAL',
                    'value' => ['stringValue' => $documento]
                ]
            ],
            'limit' => 1
        ]
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $result = json_decode($response, true);
    if (!is_array($result)) {
        return null;
    }

    foreach ($result as $entry) {
        if (!isset($entry['document']['name'], $entry['document']['fields'])) {
            continue;
        }

        $fields = $entry['document']['fields'];
        $folioSiguiente = (int)($fields['folioSiguiente']['integerValue'] ?? 0);
        $fullName = $entry['document']['name'];
        $parts = explode('/', $fullName);
        $docId = end($parts);

        if (!empty($docId)) {
            return [
                'documentId'     => $docId,
                'folioSiguiente' => $folioSiguiente,
            ];
        }
    }

    return null;
}

/**
 * Incrementa en 1 el folio del documento proporcionado.
 *
 * @param array $folioData Debe contener `documentId` y `folioSiguiente`.
 * @return bool            TRUE si la actualización fue exitosa.
 */
function actualizarFolioFirestore(array $folioData): bool
{
    global $firebaseProjectId, $firebaseApiKey;

    if (!isset($folioData['documentId'], $folioData['folioSiguiente'])) {
        return false;
    }

    $nuevoFolio = (int)$folioData['folioSiguiente'] + 1;
    $documentId = $folioData['documentId'];

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS/$documentId?updateMask.fieldPaths=folioSiguiente&key=$firebaseApiKey";
    $payload = json_encode([
        'fields' => [
            'folioSiguiente' => ['integerValue' => $nuevoFolio],
        ],
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'PATCH',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    return $response !== false;
}

/**
 * Convierte un arreglo asociativo en el formato de Firestore (mapValue).
 *
 * @param array $data Datos a convertir.
 * @return array      Representación `mapValue` para Firestore.
 */
function convertirArrayAFirestoreMap(array $data): array
{
    $fields = [];
    foreach ($data as $key => $value) {
        $fields[$key] = convertirValorAFirestore($value);
    }
    return $fields;
}

/**
 * Convierte un valor PHP al formato correspondiente de Firestore.
 *
 * @param mixed $value Valor a transformar.
 * @return array       Representación Firestore.
 */
function convertirValorAFirestore($value): array
{
    if (is_array($value)) {
        $esAsociativo = array_keys($value) !== range(0, count($value) - 1);
        if ($esAsociativo) {
            return [
                'mapValue' => [
                    'fields' => convertirArrayAFirestoreMap($value),
                ],
            ];
        }

        $values = [];
        foreach ($value as $item) {
            $values[] = convertirValorAFirestore($item);
        }

        return [
            'arrayValue' => [
                'values' => $values,
            ],
        ];
    }

    if ($value instanceof DateTimeInterface) {
        return ['timestampValue' => $value->format(DATE_ATOM)];
    }

    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        return ['integerValue' => (int)$value];
    }

    if (is_float($value)) {
        return ['doubleValue' => $value];
    }

    if (is_bool($value)) {
        return ['booleanValue' => $value];
    }

    if ($value === null) {
        return ['nullValue' => null];
    }

    return ['stringValue' => (string)$value];
}

/**
 * Registra un movimiento en la colección BITACORA_GENERAL de Firestore.
 *
 * @param string $usuario      Identificador del usuario.
 * @param string $modulo       Nombre del módulo que genera la bitácora.
 * @param string $accion       Acción realizada.
 * @param int    $noEmpresa    Número de empresa.
 * @param array  $camposModulo Datos adicionales específicos del módulo.
 * @param string $documentoFolio Nombre del documento en la colección FOLIOS (opcional).
 *
 * @return array Resultado de la operación (`success` => bool, `message` => string).
 */
function agregarBitacora(
    string $usuario,
    string $modulo,
    string $accion,
    int $noEmpresa,
    array $camposModulo = [],
    string $documentoFolio = 'bitacora'
): array {
    global $firebaseProjectId, $firebaseApiKey;

    $folioData = obtenerFolioFirestore($documentoFolio);
    if ($folioData === null) {
        return [
            'success' => false,
            'message' => 'No fue posible obtener el folio para la bitácora.',
        ];
    }

    $num = (int)$folioData['folioSiguiente'];
    $timestamp = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format(DATE_ATOM);

    $fields = [
        'accion'      => ['stringValue' => $accion],
        'usuario'     => ['stringValue' => $usuario],
        'num'         => ['integerValue' => $num],
        'noEmpresa'   => ['integerValue' => $noEmpresa],
        'modulo'      => ['stringValue' => $modulo],
        'creacion'    => ['timestampValue' => $timestamp],
        'camposModulo'=> [
            'mapValue' => [
                'fields' => convertirArrayAFirestoreMap($camposModulo),
            ],
        ],
    ];
    //var_dump($fields);
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/BITACORA_GENERAL?key=$firebaseApiKey";
    $payload = json_encode(['fields' => $fields]);

    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => $payload,
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    $esExito = strpos($statusLine, '200') !== false;

    if ($response === false || !$esExito) {
        $error = $response !== false ? $response : (error_get_last()['message'] ?? null);
        return [
            'success' => false,
            'message' => 'No se pudo registrar la bitácora en Firestore.',
            'error'   => $error,
        ];
    }

    // Intentar incrementar el folio; si falla no se considera fatal para el registro creado.
    actualizarFolioFirestore($folioData);

    return [
        'success' => true,
        'message' => 'Bitácora registrada correctamente.',
        'num'     => $num,
    ];
}

/**
 * Registra específicamente la edición de un pedido con el detalle de productos.
 */
function guardarBitacoraEdicionPedido(
    string $usuarioClave,
    string $nombreResponsable,
    int $noEmpresa,
    string $pedidoID,
    string $clienteID,
    array $productos,
    array $cambiosProductos = []
): array {
    $productosBitacora = resumirProductosParaBitacora($productos);
    $resumenCambios = normalizarCambiosProductosBitacora($cambiosProductos);

    $camposModulo = [
        'quienCreo' => $nombreResponsable,
        'pedidoID' => $pedidoID,
        'clienteID' => $clienteID,
        'productos' => json_encode($productosBitacora, JSON_UNESCAPED_UNICODE),
        'cambiosProductos' => json_encode($resumenCambios, JSON_UNESCAPED_UNICODE),
    ];

    return agregarBitacora(
        $usuarioClave,
        'PEDIDOS',
        'Edicion de Pedido',
        $noEmpresa,
        $camposModulo
    );
}

function resumirProductosParaBitacora(array $productos): array
{
    $resumen = [];
    foreach ($productos as $producto) {
        $clave = $producto['producto'] ?? $producto['CVE_ART'] ?? '';
        $cantidad = $producto['cantidad'] ?? $producto['CANT'] ?? 0;
        $precio = $producto['precioUnitario'] ?? $producto['PREC'] ?? 0;
        $descripcion = $producto['descripcion'] ?? ($producto['DESCR'] ?? '');

        $resumen[] = [
            'producto' => (string)$clave,
            'cantidad' => (float)$cantidad,
            'precio' => (float)$precio,
            'descripcion' => (string)$descripcion,
        ];
    }
    return $resumen;
}

function normalizarCambiosProductosBitacora(array $cambios): array
{
    $tipos = ['agregados', 'editados', 'eliminados'];
    $resultado = [
        'agregados' => [],
        'editados' => [],
        'eliminados' => [],
    ];

    foreach ($tipos as $tipo) {
        if (!isset($cambios[$tipo]) || !is_array($cambios[$tipo])) {
            continue;
        }

        foreach ($cambios[$tipo] as $item) {
            $producto = (string)($item['producto'] ?? '');

            if ($tipo === 'editados') {
                $resultado[$tipo][] = [
                    'producto' => $producto,
                    'cantidadAnterior' => (float)($item['cantidadAnterior'] ?? 0),
                    'cantidadActual' => (float)($item['cantidadActual'] ?? 0),
                    'diferencia' => (float)($item['diferencia'] ?? 0),
                ];
            } elseif ($tipo === 'agregados') {
                $resultado[$tipo][] = [
                    'producto' => $producto,
                    'cantidad' => (float)($item['cantidadActual'] ?? $item['cantidad'] ?? 0),
                ];
            } else { // eliminados
                $resultado[$tipo][] = [
                    'producto' => $producto,
                    'cantidad' => (float)($item['cantidadAnterior'] ?? $item['cantidad'] ?? 0),
                ];
            }
        }
    }

    return $resultado;
}