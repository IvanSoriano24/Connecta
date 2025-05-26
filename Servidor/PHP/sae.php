<?php
require 'firebase.php';
session_start();

// Desactiva errores fatales visibles en producción
/*error_reporting(0)
ini_set('display_errors', 0);*/

function probarConexionSQLServer($host, $usuario, $password, $nombreBase, $claveSae)
{
    try {
        // Establecemos la conexión con la base de datos
        $dsn = "sqlsrv:Server=$host;Database=$nombreBase;TrustServerCertificate=true";
        $conn = new PDO($dsn, $usuario, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Obtenemos las tablas de tipo "BASE TABLE"
        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
        $stmt = $conn->query($sql);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Verificamos que se hayan obtenido tablas
        if (!$tables || count($tables) === 0) {
            return [
                'success' => false,
                'message' => 'No se encontraron tablas en la base de datos'
            ];
        }

        // Seleccionamos una tabla aleatoriamente
        $randomIndex = array_rand($tables);
        $selectedTable = $tables[$randomIndex]['TABLE_NAME'];

        // Extraemos la parte numérica al final del nombre de la tabla
        $numeroTabla = '';
        if (preg_match('/(\d+)$/', $selectedTable, $matches)) {
            $numeroTabla = $matches[1];
        }
        // Retornamos el resultado con la tabla y su número extraído
        return [
            'success' => true,
            'message' => 'Conexión exitosa',
            'tablaSeleccionada' => $selectedTable,
            'numeroTabla' => $numeroTabla
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error de conexión: ' . $e->getMessage()
        ];
    }
}
function probarConexionBanco($host, $usuario, $password, $nombreBase)
{
    try {
        // 1) Conexión PDO a SQL Server
        $dsn = "sqlsrv:Server=$host;Database=$nombreBase;TrustServerCertificate=true";
        $conn = new PDO($dsn, $usuario, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2) Prepared statement: pedimos NUM_REG y NUM_CTA
        $sql  = "SELECT TOP 1 NUM_REG, NUM_CTA 
                 FROM CTAS 
                 WHERE NUM_CTA = :numcta";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':numcta' => 'stp']);

        // 3) fetch() para obtener NUM_REG si existe
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Se encontró al menos un registro
            return [
                'success'    => true,
                'message'    => "La cuenta 'stp' existe en la tabla CTAS.",
                'NUM_REG'    => $row['NUM_REG'],
                'NUM_CTA'    => $row['NUM_CTA']
            ];
        } else {
            // No existe
            return [
                'success' => false,
                'stp' => true,
                'NUM_REG'    => "0",
                'message' => "No se encontró la cuenta 'stp' en la tabla CTAS."
            ];
        }
    } catch (PDOException $e) {
        // Error de conexión o en la consulta
        return [
            'success' => false,
            'message' => 'Error de conexión o consulta: ' . $e->getMessage()
        ];
    }
}

function guardarConexion($data, $firebaseProjectId, $firebaseApiKey, $idDocumento, $resultadoConexion, $resultadoBanco)
{
    // Si el idDocumento es nulo, creamos un nuevo documento
    if ($idDocumento === null) {

        $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";

        // URL para buscar en la colección EMPRESAS
        $urlBuscar = $urlBase . "/EMP_USS?key=$firebaseApiKey";

        // Realizar la consulta para obtener todos los documentos de la colección
        $responseBuscar = file_get_contents($urlBuscar);
        if ($responseBuscar === false) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener documentos de la colección.']);
            return;
        }

        $dataBuscar = json_decode($responseBuscar, true);

        // Buscar el documento que tenga el campo noEmpresa igual al valor recibido
        $documentoId = null;
        if (isset($dataBuscar['documents'])) {
            foreach ($dataBuscar['documents'] as $document) {
                $fields = $document['fields'];
                if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $data['noEmpresa']) {
                    $documentoId = basename($document['name']); // Extraemos el ID del documento
                    break;
                }
            }
        }
        // Construir la URL del documento encontrado para actualizarlo
        $urlActualizar = "$urlBase/EMP_USS/$documentoId?key=$firebaseApiKey";

        // Obtener la fecha de envío


        // Datos de actualización en Firebase
        $claveSae = $resultadoConexion['numeroTabla'];
        $claveSae = $claveSae = str_pad($claveSae, 2, "0", STR_PAD_LEFT);
        // Datos de actualización en Firebase
        $data = [
            'fields' => [
                'claveSae' => ['stringValue' => $claveSae],
            ]
        ];

        // Agregar `updateMask` para actualizar solo los campos indicados
        $urlActualizar .= '&updateMask.fieldPaths=claveSae';

        $context = stream_context_create([
            'http' => [
                'method' => 'PATCH',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($data)
            ]
        ]);

        $response = @file_get_contents($urlActualizar, false, $context);

        // URL para crear un nuevo documento
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
        $payload = [
            'fields' => [
                'host' => ['stringValue' => $data['host']],
                'puerto' => ['stringValue' => $data['puerto']],
                'usuario' => ['stringValue' => $data['usuarioSae']],
                'password' => ['stringValue' => $data['password']],
                'nombreBase' => ['stringValue' => $data['nombreBase']],
                'nombreBanco' => ['stringValue' => $data['nombreBanco']],
                'noEmpresa' => ['integerValue' => $data['noEmpresa']],
                'claveSae' => ['stringValue' => $claveSae],
                'numCuneta' => ['stringValue' => $resultadoBanco['NUM_REG']],
            ],
        ];
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($payload),
            ],
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            return ['success' => false, 'message' => 'Error al guardar en Firebase'];
        }
        $_SESSION['empresa'] = [
            'claveSae' => $claveSae
        ];
        echo ['success' => true, 'message' => 'Datos guardados exitosamente en Firebase', 'firebaseResponse' => json_decode($result, true)];
    } else {

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

        // Guardamos la información original en otra variable para usarla más adelante
        $originalData = $data;

        // Formatear la claveSae (agrega ceros a la izquierda)
        $claveSae = str_pad($resultadoConexion['numeroTabla'], 2, "0", STR_PAD_LEFT);

        // Iterar sobre todos los documentos y actualizar aquellos que cumplan con la condición
        if (isset($dataBuscar['documents'])) {
            foreach ($dataBuscar['documents'] as $document) {
                $fields = $document['fields'];
                if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $data['noEmpresa']) {
                    $documentoId = basename($document['name']); // Extraemos el ID del documento

                    // Construir la URL del documento encontrado para actualizarlo
                    $urlActualizar = "$urlBase/EMP_USS/$documentoId?key=$firebaseApiKey";

                    // Datos de actualización en EMP_USS (solo se actualiza claveSae)
                    $payloadEmp = [
                        'fields' => [
                            'claveSae' => ['stringValue' => $claveSae],
                        ]
                    ];

                    // Agregar updateMask para actualizar solo el campo claveSae
                    $urlActualizar .= '&updateMask.fieldPaths=claveSae';

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

        // Actualizar el documento en la colección CONEXIONES
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES/$idDocumento?key=$firebaseApiKey";

        // Hacemos la solicitud GET para obtener el documento de CONEXIONES
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n"
            ]
        ]);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener el documento de Firebase en CONEXIONES']);
            return;
        }
        $document = json_decode($result, true);

        // Si el documento existe, se actualizan los datos usando la información original
        if (isset($document['name'])) {
            $payloadCon = [
                'fields' => [
                    'host'       => ['stringValue' => $originalData['host']],
                    'puerto'     => ['stringValue' => $originalData['puerto']],
                    'usuario'    => ['stringValue' => $originalData['usuarioSae']],
                    'password'   => ['stringValue' => $originalData['password']],
                    'nombreBase' => ['stringValue' => $originalData['nombreBase']],
                    'nombreBanco' => ['stringValue' => $originalData['nombreBanco']],
                    'noEmpresa'  => ['integerValue' => $originalData['noEmpresa']],
                    'claveSae'   => ['stringValue' => $claveSae],
                    'numCuneta' => ['stringValue' => $resultadoBanco['NUM_REG']],
                ],
            ];

            $options = [
                'http' => [
                    'header' => "Content-Type: application/json\r\n",
                    'method' => 'PATCH',
                    'content' => json_encode($payloadCon),
                ],
            ];
            $context = stream_context_create($options);
            $updateResult = file_get_contents($url, false, $context);

            if ($updateResult === FALSE) {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el documento en Firebase CONEXIONES']);
                return;
            }

            $_SESSION['empresa']['claveSae'] = $claveSae;

            echo json_encode(['success' => true, 'message' => 'Documento actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el documento con el ID especificado en CONEXIONES']);
        }
    }
}
function guardarConexionNew($data, $firebaseProjectId, $firebaseApiKey, $resultadoConexion, $resultadoBanco)
{
    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents";

    // URL para buscar en la colección EMP_USS
    $urlBuscar = $urlBase . "/EMP_USS?key=$firebaseApiKey";

    // Realizar la consulta para obtener todos los documentos de la colección
    $responseBuscar = file_get_contents($urlBuscar);
    if ($responseBuscar === false) {
        return ['success' => false, 'message' => 'Error al obtener documentos de la colección.'];
    }
    $dataBuscar = json_decode($responseBuscar, true);

    // Buscar el documento que tenga el campo noEmpresa igual al valor recibido
    $documentoId = null;
    if (isset($dataBuscar['documents'])) {
        foreach ($dataBuscar['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $data['noEmpresa']) {
                $documentoId = basename($document['name']); // Extraemos el ID del documento
                break;
            }
        }
    }

    // Si se encontró un documento, se actualiza el campo 'claveSae'
    if ($documentoId !== null) {
        $urlActualizar = "$urlBase/EMP_USS/$documentoId?key=$firebaseApiKey&updateMask.fieldPaths=claveSae";
        $updatePayload = [
            'fields' => [
                'claveSae' => ['stringValue' => $resultadoConexion['numeroTabla']]
            ]
        ];
        $updateOptions = [
            'http' => [
                'method' => 'PATCH',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($updatePayload)
            ]
        ];
        $updateContext = stream_context_create($updateOptions);
        $updateResponse = @file_get_contents($urlActualizar, false, $updateContext);
        // Se puede manejar el error o continuar según lo requiera la lógica
        if ($updateResponse === false) {
            // Opcionalmente podrías retornar o registrar el error
        }
    }

    // Crear un nuevo documento en la colección CONEXIONES
    $urlCrear = "$urlBase/CONEXIONES?key=$firebaseApiKey";
    $payload = [
        'fields' => [
            'host'       => ['stringValue' => $data['host']],
            'puerto'     => ['stringValue' => $data['puerto']],
            'usuario'    => ['stringValue' => $data['usuarioSae']],
            'password'   => ['stringValue' => $data['password']],
            'nombreBase' => ['stringValue' => $data['nombreBase']],
            'noEmpresa'  => ['integerValue' => $data['noEmpresa']],
            'claveSae'   => ['stringValue' => $resultadoConexion['numeroTabla']],
        ],
    ];

    $createOptions = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($payload),
        ],
    ];

    $createContext = stream_context_create($createOptions);
    $createResponse = file_get_contents($urlCrear, false, $createContext);
    if ($createResponse === false) {
        return ['success' => false, 'message' => 'Error al guardar en Firebase'];
    }

    // Decodificar la respuesta y extraer el ID del nuevo documento
    $firebaseResponse = json_decode($createResponse, true);
    if (isset($firebaseResponse['name'])) {
        $nameParts = explode("/", $firebaseResponse['name']);
        $documentIdNuevo = end($nameParts); // Se extrae el último elemento que es el ID
    } else {
        return ['success' => false, 'message' => 'No se pudo obtener el ID del documento'];
    }

    return [
        'success'    => true,
        'message'    => 'Datos guardados exitosamente en Firebase',
        'idDocumento' => $documentIdNuevo
    ];
}

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
                    'usuarioSae' => $fields['usuario']['stringValue'],
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

function verificarConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa)
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
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos de Firebase']);
        return;
    }

    $documents = json_decode($result, true);
    if (!isset($documents['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron conexiones en Firebase']);
        return;
    }

    // Buscar si existe un documento con el mismo `noEmpresa`
    foreach ($documents['documents'] as $document) {
        $fields = $document['fields'];

        if (isset($fields['claveSae']) && $fields['claveSae']['stringValue'] === $claveSae && isset($fields['noEmpresa']) && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
            echo json_encode(['success' => true, 'tieneConexion' => true]);
            return;
        }
    }

    echo json_encode(['success' => true, 'tieneConexion' => false]);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        echo json_encode(['success' => false, 'message' => 'Datos no válidos en la solicitud']);
        exit;
    }
    $action = $input['action'];
    switch ($action) {
        case 'verificar':
            // Decodificar el JSON recibido
            $input = json_decode(file_get_contents('php://input'), true);

            if (isset($input['claveSae'])) {
                $claveSae = $input['claveSae'];
                $noEmpresa = $input['noEmpresa'];
                verificarConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se recibió el número de empresa']);
            }
            break;
        case 'probar':
            $data = [
                'host' => $input['host'],
                'puerto' => $input['puerto'],
                'usuarioSae' => $input['usuarioSae'],
                'password' => $input['password'],
                'nombreBase' => $input['nombreBase'],
                'nombreBanco' => $input['nombreBanco'] ?? "",
                'claveSae' => $input['claveSae']
            ];
            $resultadoConexion = probarConexionSQLServer($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase'], $data['claveSae']);
            if ($resultadoConexion['success']) {
                $noTabla = $resultadoConexion['numeroTabla'];
                if (isset($data['nombreBanco'])) {
                    $resultadoBanco = probarConexionBanco($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBanco'], $data['claveSae']);
                    if ($resultadoBanco['success']) {
                        $noCuenta = $resultadoBanco['NUM_REG'];
                        $message = $resultadoBanco['message'];
                        /*$resultado = [
                        'success' => true,
                        'numeroTabla' => $noTabla
                    ];*/
                        //var_dump($noCuenta);
                        echo json_encode(['success' => true, 'numeroTabla' => $noTabla, 'noCuenta' => $noCuenta, 'message' => $message]);
                        return;
                        //echo json_encode($resultado);
                    } else {
                        $noCuenta = $resultadoBanco['NUM_REG'] || 0;
                        $message = $resultadoBanco['message'];
                        echo json_encode(['success' => false, 'stp' => $resultadoBanco['stp'], 'numeroTabla' => $noTabla, 'noCuenta' => $noCuenta, 'message' => $resultadoBanco['message']]);
                        //echo json_encode(['success' => false, 'stp' => $resultadoBanco['stp'], 'numeroTabla' => $noTabla, 'message' => $resultadoBanco['message']]);
                        return;
                    }
                } else {
                    $message = "Conexion realizada sin Banco";
                    echo json_encode(['success' => true, 'numeroTabla' => $noTabla, 'noCuenta' => 0, 'message' => $message]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => $resultadoConexion['message']]);
            }
            break;

        case 'guardar':
            $data = [
                'host' => $input['host'],
                'puerto' => $input['puerto'],
                'usuarioSae' => $input['usuarioSae'],
                'password' => $input['password'],
                'nombreBase' => $input['nombreBase'],
                'noEmpresa' => $input['noEmpresa'],
                'nombreBanco' => $input['nombreBanco'],
                'claveSae' => $input['claveSae']
            ];
            $csrf_token_form = $input['token'];
            $csrf_token  = $_SESSION['csrf_token'];
            if ($csrf_token === $csrf_token_form) {
                $idDocumento = $input['idDocumento'];
                $idDocumento = trim($idDocumento);
                $resultadoConexion = probarConexionSQLServer($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase'], $data['claveSae']);
                if ($resultadoConexion['success']) {
                    $resultadoBanco = probarConexionBanco($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBanco'], $data['claveSae']);
                    if ($resultadoConexion['success']) {
                        ob_clean();
                        $resultadoGuardar = guardarConexion($data, $firebaseProjectId, $firebaseApiKey, $idDocumento, $resultadoConexion, $resultadoBanco);
                        //echo json_encode($resultadoGuardar);
                        return;
                    } else {
                        echo json_encode(['success' => false, 'message' => $resultadoConexion['message']]);
                        return;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => $resultadoConexion['message']]);
                    return;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error en la sesion']);
                return;
            }
            break;
        case 'guardarNew':
            $data = [
                'host' => $input['host'],
                'puerto' => $input['puerto'],
                'usuarioSae' => $input['usuarioSae'],
                'password' => $input['password'],
                'nombreBase' => $input['nombreBase'],
                'nombreBanco' => $input['nombreBanco'] ?? "",
                'noEmpresa' => $input['noEmpresa'],
                'claveSae' => $input['claveSae']
            ];
            /*$csrf_token_form = $input['token'];
            $csrf_token  = $_SESSION['csrf_token'];
            if ($csrf_token === $csrf_token_form) {*/
                $resultadoConexion = probarConexionSQLServer($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBase'], $data['claveSae']);
                if ($resultadoConexion['success']) {
                    if (isset($data['nombreBanco'])) {
                        $resultadoBanco = probarConexionBanco($data['host'], $data['usuarioSae'], $data['password'], $data['nombreBanco'], $data['claveSae']);
                        if ($resultadoBanco['success']) {
                            $resultadoGuardar = guardarConexionNew($data, $firebaseProjectId, $firebaseApiKey, $resultadoConexion, $resultadoBanco);
                            echo json_encode($resultadoGuardar);
                        } else {
                            echo json_encode(['success' => false, 'message' => $resultadoBanco['message']]);
                        }
                    } else {
                        $message = "Conexion realizada sin Banco";
                    echo json_encode(['success' => true, 'numeroTabla' => $noTabla, 'noCuenta' => 0, 'message' => $message]);
                    return;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => $resultadoConexion['message']]);
                }
            /*} else {
                echo json_encode(['success' => false, 'message' => 'Error en la sesion']);
                return;
            }*/
            break;

        case 'mostrar':
            $noEmpresa = $input['noEmpresa'];
            $claveSae = $input['claveSae'];
            $resultado = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);
            echo json_encode($resultado);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
            break;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no soportado']);
}
