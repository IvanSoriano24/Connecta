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
$folioFiltro = trim($input['folio'] ?? '');
$fechaInicio = trim($input['fechaInicio'] ?? '');
$fechaFin = trim($input['fechaFin'] ?? '');

$accionesPermitidas = [
    'PEDIDOS' => [
        'TODAS'       => ['Pedido Anticipado', 'Pedido Con Credito', 'Edicion de Pedido', 'Cancelacion de Pedido', 'Envio de Confirmacion', 'Pedido Autorizado', 'Pedido Rechazado'],
        'CREACION'    => ['Pedido Anticipado', 'Pedido Con Credito'],
        'EDICION'     => ['Edicion de Pedido'],
        'CANCELACION' => ['Cancelacion de Pedido'],
        'ENVIO DE CONFIRMACION' => ['Envio de Confirmacion'],
        'CONFIRMACION DE PEDIDO' => ['CONFIRMACION'],
        'AUTORIZACION DE PEDIDO' => ['Pedido Autorizado', 'Pedido Rechazado'],
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
    // Si es confirmación de pedido, consultar la colección BITACORA
    if (in_array('CONFIRMACION', $acciones)) {
        $registros = consultarBitacoraConfirmacion($folioFiltro, $fechaInicio, $fechaFin);
    } else {
        $registros = consultarBitacoraPorFiltros($modulo, $acciones, $folioFiltro, $fechaInicio, $fechaFin);
    }
    
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

function consultarBitacoraPorFiltros(string $modulo, array $acciones, string $folio = '', string $fechaInicio = '', string $fechaFin = '', int $limite = 50): array
{
    global $firebaseProjectId, $firebaseApiKey;

    $accionValues = [];
    foreach ($acciones as $accion) {
        $accionValues[] = ['stringValue' => $accion];
    }

    $filters = [
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
    ];

    if ($folio !== '' && $modulo === 'PEDIDOS') {
        $valorFolio = ctype_digit($folio)
            ? ['integerValue' => (int)$folio]
            : ['stringValue' => $folio];
        
        // Para acciones de autorización, buscar en folioPedido también
        // Usamos OR para buscar en ambos campos
        $filters[] = [
            'compositeFilter' => [
                'op' => 'OR',
                'filters' => [
                    [
                        'fieldFilter' => [
                            'field' => ['fieldPath' => 'camposModulo.pedidoID'],
                            'op' => 'EQUAL',
                            'value' => $valorFolio,
                        ],
                    ],
                    [
                        'fieldFilter' => [
                            'field' => ['fieldPath' => 'camposModulo.folioPedido'],
                            'op' => 'EQUAL',
                            'value' => $valorFolio,
                        ],
                    ],
                ],
            ],
        ];
    }

    $zona = new DateTimeZone('America/Mexico_City');
    if ($fechaInicio !== '') {
        $inicio = DateTime::createFromFormat('Y-m-d H:i:s', $fechaInicio . ' 00:00:00', $zona);
        if ($inicio) {
            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'creacion'],
                    'op' => 'GREATER_THAN_OR_EQUAL',
                    'value' => ['timestampValue' => $inicio->format(DateTime::ATOM)],
                ],
            ];
        }
    }

    if ($fechaFin !== '') {
        $fin = DateTime::createFromFormat('Y-m-d H:i:s', $fechaFin . ' 23:59:59', $zona);
        if ($fin) {
            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'creacion'],
                    'op' => 'LESS_THAN_OR_EQUAL',
                    'value' => ['timestampValue' => $fin->format(DateTime::ATOM)],
                ],
            ];
        }
    }

    $query = [
        'structuredQuery' => [
            'from' => [
                ['collectionId' => 'BITACORA_GENERAL'],
            ],
            'where' => [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => $filters,
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

        // Para acciones de autorización, el folio puede estar en folioPedido
        if (isset($camposModulo['folioPedido']) && !isset($camposModulo['pedidoID'])) {
            $camposModulo['pedidoID'] = $camposModulo['folioPedido'];
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

function consultarBitacoraConfirmacion(string $folio = '', string $fechaInicio = '', string $fechaFin = '', int $limite = 50): array
{
    global $firebaseProjectId, $firebaseApiKey;
    
    $noEmpresa = $_SESSION['empresa']['noEmpresa'] ?? null;
    if ($noEmpresa === null) {
        return [];
    }

    $filters = [
        [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'noEmpresa'],
                'op' => 'EQUAL',
                'value' => ['integerValue' => (int)$noEmpresa],
            ],
        ],
    ];

    // Filtro por folio de pedido
    if ($folio !== '') {
        $valorFolio = ctype_digit($folio)
            ? ['integerValue' => (int)$folio]
            : ['stringValue' => $folio];
        $filters[] = [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'pedido'],
                'op' => 'EQUAL',
                'value' => $valorFolio,
            ],
        ];
    }

    // Filtro por fecha inicio
    if ($fechaInicio !== '') {
        $zona = new DateTimeZone('America/Mexico_City');
        $inicio = DateTime::createFromFormat('Y-m-d H:i:s', $fechaInicio . ' 00:00:00', $zona);
        if ($inicio) {
            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'fechaCreacion'],
                    'op' => 'GREATER_THAN_OR_EQUAL',
                    'value' => ['stringValue' => $inicio->format('Y-m-d H:i:s')],
                ],
            ];
        }
    }

    // Filtro por fecha fin
    if ($fechaFin !== '') {
        $zona = new DateTimeZone('America/Mexico_City');
        $fin = DateTime::createFromFormat('Y-m-d H:i:s', $fechaFin . ' 23:59:59', $zona);
        if ($fin) {
            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'fechaCreacion'],
                    'op' => 'LESS_THAN_OR_EQUAL',
                    'value' => ['stringValue' => $fin->format('Y-m-d H:i:s')],
                ],
            ];
        }
    }

    $query = [
        'structuredQuery' => [
            'from' => [
                ['collectionId' => 'BITACORA'],
            ],
            'where' => [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => $filters,
                ],
            ],
            'orderBy' => [
                [
                    'field' => ['fieldPath' => 'fechaCreacion'],
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

        $idDocumento = obtenerIdDocumentoFirestore($documento['name'] ?? '');

        // Mapear el tipo de confirmación
        $accionOriginal = strtolower($parsed['accion'] ?? '');
        $tipoConfirmacion = '';
        
        switch ($accionOriginal) {
            case 'aceptado':
                $tipoConfirmacion = 'Aceptado';
                break;
            case 'anticipo':
                $tipoConfirmacion = 'Anticipo';
                break;
            case 'sin existencias':
                $tipoConfirmacion = 'Sin Existencias';
                break;
            default:
                $tipoConfirmacion = ucfirst($parsed['accion'] ?? 'Confirmación');
        }

        $registros[] = [
            'id' => $idDocumento,
            'modulo' => 'PEDIDOS',
            'accion' => 'Confirmación de Pedido',
            'usuario' => 'Cliente', // No hay usuario en BITACORA, se asume que fue el cliente
            'num' => null,
            'noEmpresa' => $parsed['noEmpresa'] ?? null,
            'creacion' => $parsed['fechaCreacion'] ?? '',
            'camposModulo' => [
                'pedidoID' => $parsed['pedido'] ?? '',
                'clienteID' => $parsed['claveCliente'] ?? '',
                'tipoConfirmacion' => $tipoConfirmacion,
                'fechaCreacion' => $parsed['fechaCreacion'] ?? '',
            ],
        ];
    }

    return $registros;
}

