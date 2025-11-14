<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida.',
    ]);
    exit;
}

require_once 'firebase.php';
require_once 'bitacora.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Solicitud inválida.',
    ]);
    exit;
}

$modulo = strtoupper(trim($input['modulo'] ?? ''));
$accionFiltro = strtoupper(trim($input['accion'] ?? ''));

$accionesPermitidas = [
    'PEDIDOS' => [
        'CREACION' => ['Pedido Anticipado', 'Pedido Con Credito'],
        'EDICION'  => ['Edicion de Pedido'],
    ],
    'CLIENTES' => [],
    'FACTURAS' => [],
];

if ($modulo === '' || !isset($accionesPermitidas[$modulo])) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'El módulo seleccionado no es válido.',
    ]);
    exit;
}

if ($accionFiltro === '' || !isset($accionesPermitidas[$modulo][$accionFiltro])) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'La acción seleccionada no es válida para este módulo.',
    ]);
    exit;
}

$acciones = $accionesPermitidas[$modulo][$accionFiltro];

if (empty($acciones)) {
    echo json_encode([
        'success' => true,
        'data' => [],
    ]);
    exit;
}

try {
    $registros = consultarBitacoraPorFiltros($modulo, $acciones);
    echo json_encode([
        'success' => true,
        'data' => $registros,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo obtener la bitácora.',
        'error' => $e->getMessage(),
    ]);
}

function consultarBitacoraPorFiltros(string $modulo, array $acciones, int $limite = 50): array
{
    global $firebaseProjectId, $firebaseApiKey;

    $accionValues = [];
    foreach ($acciones as $accion) {
        $accionValues[] = ['stringValue' => $accion];
    }

    $query = [
        'structuredQuery' => [
            'from' => [
                ['collectionId' => 'BITACORA_GENERAL'],
            ],
            'where' => [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => [
                        [
                            'fieldFilter' => [
                                'field' => ['fieldPath' => 'modulo'],
                                'op' => 'EQUAL',
                                'value' => ['stringValue' => $modulo],
                            ],
                        ],
                        [
                            'fieldFilter' => [
                                'field' => ['fieldPath' => 'accion'],
                                'op' => count($accionValues) > 1 ? 'IN' : 'EQUAL',
                                'value' => count($accionValues) > 1
                                    ? ['arrayValue' => ['values' => $accionValues]]
                                    : $accionValues[0],
                            ],
                        ],
                    ],
                ],
            ],
            'orderBy' => [
                [
                    'field' => ['fieldPath' => 'creacion'],
                    'direction' => 'DESCENDING',
                ],
            ],
            'limit' => $limite,
        ],
    ];

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($query),
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        throw new Exception($error['message'] ?? 'Error de comunicación con Firestore');
    }

    $statusLine = $http_response_header[0] ?? '';
    if (strpos($statusLine, '200') === false) {
        throw new Exception($response);
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [];
    }

    $registros = [];
    foreach ($data as $entrada) {
        if (!isset($entrada['document'])) {
            continue;
        }

        $documento = $entrada['document'];
        $fields = $documento['fields'] ?? [];
        if (empty($fields)) {
            continue;
        }

        $parsed = [];
        foreach ($fields as $campo => $valor) {
            $parsed[$campo] = firestoreValueToPhp($valor);
        }

        $camposModulo = $parsed['camposModulo'] ?? [];
        if (is_string($camposModulo['productos'] ?? null)) {
            $productos = json_decode($camposModulo['productos'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $camposModulo['productos'] = $productos;
            }
        }

        if (is_string($camposModulo['cambiosProductos'] ?? null)) {
            $cambios = json_decode($camposModulo['cambiosProductos'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $camposModulo['cambiosProductos'] = $cambios;
            }
        }

        $idDocumento = obtenerIdDocumentoFirestore($documento['name'] ?? '');

        $registros[] = [
            'id' => $idDocumento,
            'modulo' => $parsed['modulo'] ?? '',
            'accion' => $parsed['accion'] ?? '',
            'usuario' => $parsed['usuario'] ?? '',
            'num' => $parsed['num'] ?? null,
            'noEmpresa' => $parsed['noEmpresa'] ?? null,
            'creacion' => $parsed['creacion'] ?? '',
            'camposModulo' => $camposModulo,
        ];
    }

    return $registros;
}

function firestoreValueToPhp(array $value)
{
    if (isset($value['stringValue'])) {
        return $value['stringValue'];
    }
    if (isset($value['integerValue'])) {
        return (int)$value['integerValue'];
    }
    if (isset($value['doubleValue'])) {
        return (float)$value['doubleValue'];
    }
    if (isset($value['booleanValue'])) {
        return (bool)$value['booleanValue'];
    }
    if (isset($value['timestampValue'])) {
        return $value['timestampValue'];
    }
    if (isset($value['mapValue'])) {
        $mapFields = $value['mapValue']['fields'] ?? [];
        $result = [];
        foreach ($mapFields as $k => $v) {
            $result[$k] = firestoreValueToPhp($v);
        }
        return $result;
    }
    if (isset($value['arrayValue'])) {
        $values = $value['arrayValue']['values'] ?? [];
        return array_map('firestoreValueToPhp', $values);
    }
    if (array_key_exists('nullValue', $value)) {
        return null;
    }
    return null;
}

function obtenerIdDocumentoFirestore(string $name): string
{
    if ($name === '') {
        return '';
    }
    $parts = explode('/', $name);
    return end($parts);
}

