<?php
require 'firebase.php';
session_start();

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
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
            // Extrae solo el ID del documento desde la URL
            $documentId = basename($document['name']);  // Esto da solo el ID del documento

            return [
                'success' => true,
                'data' => [
                    'id' => $documentId, // Solo el ID del documento
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'nombreBanco' => $fields['nombreBanco']['stringValue'],
                    'claveSae' => $fields['claveSae']['stringValue'],

                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}

function obtenerCamposUsados()
{
    global $firebaseProjectId, $firebaseApiKey;

    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/PA"
        . "RAMTETROS?key=$firebaseApiKey";
    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los campos.']);
        return;
    }
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron documentos.']);
        return;
    }

    $resultado = [];
    foreach ($data['documents'] as $doc) {
        $fields = $doc['fields'];
        // cada clave de $fields (excepto "noEmpresa") es el nombre de la tabla
        foreach ($fields as $tabla => $entry) {
            if ($tabla === 'noEmpresa') continue;
            // si este campo trae un arrayValue ⇒ recorremos sus valores
            if (isset($entry['arrayValue']['values'])) {
                foreach ($entry['arrayValue']['values'] as $elem) {
                    $f = $elem['mapValue']['fields'];
                    if ($tabla != "factura" && $tabla != "pedido" && $tabla != "remision") {
                        $resultado[] = [
                            'id' => basename($doc['name']),
                            'tabla'       => $tabla,
                            'campo'       => $f['campo']['stringValue']     ?? '',
                            'descripcion' => $f['descripcion']['stringValue'] ?? '',
                        ];
                    } else {
                        $resultado[] = [
                            'id' => basename($doc['name']),
                            'tabla'       => $tabla,
                            'serie'       => $f['serie']['stringValue']     ?? '',
                            'tipo' => $f['tipo']['stringValue'] ?? '',
                        ];
                    }
                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $resultado]);
    exit;
}
function mostrarUsuarios()
{
    global $firebaseProjectId, $firebaseApiKey;

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];

    // Validamos si el usuario logueado es administrador

    // Si no es administrador, solo mostramos su propio usuario

    // Si es administrador, obtenemos todos los usuarios
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los usuarios.']);
        return;
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron usuarios.']);
        return;
    }

    $usuarios = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        $empFirebase = (int) $fields['noEmpresa']['integerValue'];
        $empBuscada  = (int) $noEmpresa;
        //var_dump($fields['noEmpresa']['integerValue']);
        if ($empFirebase === $empBuscada) {
            if ($fields['tipoUsuario']['stringValue'] === "ADMINISTRADOR") {
                $usuarios[] = [
                    'id' => str_replace('projects/' . $firebaseProjectId . '/databases/(default)/documents/USUARIOS/', '', $document['name']),
                    'nombreCompleto' => $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue'],
                    'claveUsuario' => $fields['claveUsuario']['stringValue'] ?? '',
                ];
            }
        }
    }

    // Ordenar usuarios alfabéticamente por `nombreCompleto`
    usort($usuarios, function ($a, $b) {
        return strcmp($a['nombreCompleto'], $b['nombreCompleto']);
    });


    // Devolvemos los usuarios ordenados
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $usuarios]);
    exit();
}
function obtenerCamposLibres($conexionData)
{
    $serverName = $conexionData['host'];
    $Database = $conexionData['nombreBase'];
    $UID = $conexionData['usuario'];
    $PWD = $conexionData['password'];


    $dsn = "sqlsrv:Server=$serverName;Database=$Database;TrustServerCertificate=true";
    $conn = new PDO($dsn, $UID, $PWD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME LIKE '%CLIB%'";

    $stmt = $conn->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    usort($tables, function ($a, $b) {
        return strcmp($a['TABLE_NAME'], $b['TABLE_NAME']);
    });

    // Retornamos los usuarios como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $tables]);
}
function obtenerSeries($conexionData, $claveSae, $documento)
{
    $serverName   = $conexionData['host'];
    $Database     = $conexionData['nombreBase'];
    $UID          = $conexionData['usuario'];
    $PWD          = $conexionData['password'];

    try {
        // Conexión a la base de datos
        $dsn = "sqlsrv:Server=$serverName;Database=$Database;TrustServerCertificate=true";
        $conn = new PDO($dsn, $UID, $PWD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Construcción dinámica del nombre de tabla con el formato adecuado
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FOLIOSF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Determinamos el tipo de folio basado en el documento
        switch ($documento) {
            case "factura":
                $folio = "F";
                break;
            case "pedido":
                $folio = "P";
                break;
            case "remision":
                $folio = "R";
                break;
            default:
                throw new Exception("Documento no soportado: '$documento'");
        }

        // Preparar la consulta utilizando parámetros para evitar inyección SQL
        $sql = "SELECT TIP_DOC, SERIE, TIPO FROM $nombreTabla WHERE TIP_DOC = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$folio]);
        $folios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ordenamos el resultado por la columna "SERIE"
        usort($folios, function ($a, $b) {
            return strcmp($a['SERIE'], $b['SERIE']);
        });

        // Enviamos la respuesta en formato JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $folios]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        // En caso de error de conexión o consulta, devolvemos el mensaje de error
        echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        // Otros errores generales se capturan aquí
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
function obtenerCampos($conexionData, $tabla)
{
    $serverName = $conexionData['host'];
    $database   = $conexionData['nombreBase'];
    $uid        = $conexionData['usuario'];
    $pwd        = $conexionData['password'];

    // Conectamos con PDO
    $dsn = "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true";
    $conn = new PDO($dsn, $uid, $pwd, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Preparamos la consulta con parámetro
    $sql = "
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = :tabla
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':tabla', $tabla, PDO::PARAM_STR);
    $stmt->execute();

    $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //var_dump($campos);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data'    => $campos
    ]);
}

function guardarCamposLibres($noEmpresa, $tabla, $campo, $descripcion, $documentId, $firebaseProjectId, $firebaseApiKey)
{
    if (empty($documentId) || $documentId == null) {
        // 1.1) URL de creación (sin documentId)
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PARAMTETROS?key=$firebaseApiKey";

        // 1.2) Payload con el primer campo “$tabla”
        $fields = [
            'noEmpresa' => ['integerValue' => $noEmpresa],
            $tabla      => [
                'arrayValue' => [
                    'values' => [
                        [
                            'mapValue' => [
                                'fields' => [
                                    'campo'       => ['stringValue' => $campo],
                                    'descripcion' => ['stringValue' => $descripcion],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_SLASHES);

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => $payload,
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        $data     = json_decode($response, true);

        if (!empty($data['name'])) {
            // De la respuesta obtendrás:
            // projects/.../documents/PARAMTETROS/{documentId}
            $parts      = explode('/', $data['name']);
            $documentId = end($parts);
            echo json_encode(['success' => true, 'documentId' => $documentId]);
        } else {
            echo json_encode(['success' => false, 'message' => $data]);
        }
    } else {
        $getUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PARAMTETROS/$documentId?key=$firebaseApiKey";
        $current = json_decode(file_get_contents($getUrl), true);

        // Extrae el arrayValue existente para la primera tabla (si existe)
        $existing = $current['fields'][$tabla]['arrayValue']['values'] ?? [];

        // Y luego añades tu nuevo valor:
        $existing[] = [
            'mapValue' => [
                'fields' => [
                    'campo'       => ['stringValue' => $campo],
                    'descripcion' => ['stringValue' => $descripcion],
                ]
            ]
        ];

        // Ahora parcheas SOLO ese campo:
        $patchUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PARAMTETROS/$documentId"
            . "?updateMask.fieldPaths=$tabla&key=$firebaseApiKey";

        $patchFields = [
            $tabla => [
                'arrayValue' => [
                    'values' => $existing
                ]
            ]
        ];

        $payload = json_encode(['fields' => $patchFields], JSON_UNESCAPED_SLASHES);

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'PATCH',
                'content' => $payload,
            ],
        ];

        $context = stream_context_create($options);
        $updateResponse = @file_get_contents($patchUrl, false, $context);
        $result         = json_decode($updateResponse, true);

        if (isset($result['name'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $result]);
        }
    }
}
function verificarCampoFirebase($campo, $tabla){
    global $firebaseProjectId, $firebaseApiKey;

    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/PA"
        . "RAMTETROS?key=$firebaseApiKey";
    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los campos.']);
        return;
    }
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron documentos.']);
        return;
    }

    $resultado = [];
    foreach ($data['documents'] as $doc) {
        $fields = $doc['fields'];
        // cada clave de $fields (excepto "noEmpresa") es el nombre de la tabla
        foreach ($fields as $tabla => $entry) {
            if ($tabla === 'noEmpresa') continue;
            // si este campo trae un arrayValue ⇒ recorremos sus valores
            if (isset($entry['arrayValue']['values'])) {
                foreach ($entry['arrayValue']['values'] as $elem) {
                    $f = $elem['mapValue']['fields'];
                    if ($f['campo']['stringValue'] == $campo) {
                        echo json_encode(['success' => true, 'exists' => true]);
                        return;
                    }
                }
            }
        }
    }
    echo json_encode(['success' => true, 'exists' => false]); // Cliente no existe
}
function obtenerCamposUsadosPorTabla($idDocumento, $tabla)
{
    global $firebaseProjectId, $firebaseApiKey;

    if (empty($tabla)) {
        echo json_encode(['success' => false, 'message' => 'Tabla no proporcionada.']);
        return;
    }

    // 1) URL para leer el documento concreto
    $url = "https://firestore.googleapis.com/v1/projects/"
        . "$firebaseProjectId/databases/(default)/documents/PARAMTETROS/"
        . urlencode($idDocumento)
        . "?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con Firestore.']);
        return;
    }

    $data = json_decode($response, true);
    if (!isset($data['fields'][$tabla]['arrayValue']['values'])) {
        // No existe este campo-tabla en el documento
        echo json_encode(['success' => false, 'message' => "No se encontraron asociaciones para la tabla “$tabla”."]);
        return;
    }

    // 2) Extraer la lista de valores
    $rawValues = $data['fields'][$tabla]['arrayValue']['values'];
    $asociaciones = [];

    foreach ($rawValues as $elem) {
        if (!isset($elem['mapValue']['fields'])) {
            continue;
        }
        $f = $elem['mapValue']['fields'];
        $asociaciones[] = [
            'campo'       => $f['campo']['stringValue']     ?? '',
            'descripcion' => $f['descripcion']['stringValue'] ?? '',
        ];
    }

    // 3) Responder con JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data'    => $asociaciones
    ]);
    exit;
}
function obtenerAdministrador($id)
{
    global $firebaseProjectId, $firebaseApiKey;
    // URL de la API de Firebase para obtener los datos del usuario por ID
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$id?key=$firebaseApiKey";
    // Hacemos la solicitud a Firebase
    $response = @file_get_contents($url);
    // Verificamos si hubo algún error al obtener la respuesta
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del usuario.']);
        return;
    }
    // Decodificamos la respuesta JSON de Firebase
    $data = json_decode($response, true);
    // Verificamos si la respuesta contiene los datos esperados
    if (isset($data['fields'])) {
        // Obtenemos los campos de usuario de la respuesta de Firebase
        $usuario = $data['fields'];
        // Aseguramos que el ID del usuario esté presente en la respuesta
        $usuario['id'] = $id;
        // Limpiamos los campos para que no contengan datos de Firebase
        // Firebase devuelve los campos bajo la clave "fields", necesitamos acceder a los valores reales
        $usuario = array_map(function ($field) {
            return isset($field['stringValue']) ? $field['stringValue'] : null;
        }, $usuario);
        // Respondemos con los datos del usuario en formato JSON
        echo json_encode(['success' => true, 'data' => $usuario]);
    } else {
        // Si no se encuentra el usuario, devolvemos un mensaje de error
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    }
}
function guardarClaveAdministrador($id, $vendedores, $usuario)
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$id?updateMask.fieldPaths=claveUsuario&key=$firebaseApiKey";
    $fields = [
        'claveUsuario' => ['stringValue' => $vendedores], // Guardar la clave correcta
    ];
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => "PATCH",
            'content' => json_encode(['fields' => $fields]),
        ],
    ];
    $context = stream_context_create($options);

    // Realizar la solicitud a Firebase
    $response = @file_get_contents($url, false, $context);

    // Manejar la respuesta
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el usuario en Firebase.']);
        return;
    }
    /************************************************************************************************************/
    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";

    // URL para buscar en la colección EMP_USS
    $urlBuscar = $urlBase . "/EMP_USS?key=$firebaseApiKey";

    // Realizar la consulta para obtener todos los documentos de la colección
    $responseBuscar = file_get_contents($urlBuscar);
    if ($responseBuscar === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener documentos de la colección.']);
        return;
    }

    $dataBuscar = json_decode($responseBuscar, true);

    // Iterar sobre todos los documentos y actualizar aquellos que cumplan con la condición
    if (isset($dataBuscar['documents'])) {
        foreach ($dataBuscar['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
                $documentoId = basename($document['name']); // Extraemos el ID del documento

                // Construir la URL del documento encontrado para actualizarlo
                $urlActualizar = "$urlBase/EMP_USS/$documentoId?key=$firebaseApiKey";

                // Datos de actualización en EMP_USS (solo se actualiza claveSae)
                $payloadEmp = [
                    'fields' => [
                        'claveUsuario' => ['stringValue' => $vendedores],
                    ]
                ];

                // Agregar updateMask para actualizar solo el campo claveSae
                $urlActualizar .= '&updateMask.fieldPaths=claveUsuario';

                $context = stream_context_create([
                    'http' => [
                        'method'  => 'PATCH',
                        'header'  => "Content-Type: application/json\r\n",
                        'content' => json_encode($payloadEmp)
                    ]
                ]);

                $response = @file_get_contents($urlActualizar, false, $context);

                if ($response === false) {
                    $error = error_get_last();
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar EMP_USS.', 'error' => $error['message']]);
                    die();
                }
            }
        }
    }
    echo json_encode(['success' => true, 'message' => 'Clave Guardada Correctamente.']);
}
/***********************************************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    $funcion = $_POST['numFuncion'];
    // Asegúrate de recibir los datos en JSON y decodificarlos correctamente
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la petición.']);
    exit();
}
/***********************************************************************************************************/
switch ($funcion) {
    case 1:
        obtenerCamposUsados();
        break;
    case 2:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        obtenerCamposLibres($conexionData);
        break;
    case 3:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $tabla = $_GET['tabla'];
        obtenerCampos($conexionData, $tabla);
        break;
    case 4:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $tabla = $_POST['tabla'];
        $campo = $_POST['campo'];
        $descripcion = $_POST['descripcion'];
        $id = $_POST['id'] ?? null;
        guardarCamposLibres($noEmpresa, $tabla, $campo, $descripcion, $id, $firebaseProjectId, $firebaseApiKey);
        break;
    case 5:
        $campo = $_POST['campo'];
        $tabla = $_POST['tabla'];
        verificarCampoFirebase($campo, $tabla);
        break;
    case 6:
        $idDocumento = $_GET['idDocumento'] ?? null;
        $tabla = $_GET['tabla'] ?? null;
        obtenerCamposUsadosPorTabla($idDocumento, $tabla);
        break;
    case 7:
        mostrarUsuarios();
        break;
    case 8:
        $id = $_GET['id'];
        obtenerAdministrador($id);
        break;
    case 9:
        $vendedores = $_POST['vendedores'];
        $usuario = $_POST['usuario'];
        $id = $_POST['id'];
        guardarClaveAdministrador($id, $vendedores, $usuario);
        break;
        case 10:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $documento = $_GET['documento'];
        obtenerSeries($conexionData, $claveSae, $documento);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        break;
}
