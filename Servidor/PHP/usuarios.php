<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require 'firebase.php';

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
        if ($fields['noEmpresa']['stringValue'] === $noEmpresa) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue']
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function guardarUsuario($datosUsuario)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Extraer el ID del usuario de los datos proporcionados
    $idUsuario = isset($datosUsuario['idUsuario']) ? $datosUsuario['idUsuario'] : null;

    // Determinar si se trata de una creación (POST) o edición (PATCH)
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS";
    $method = "POST";

    if ($idUsuario) {
        // Si existe el ID del usuario, actualizamos (PATCH)
        $url .= "/$idUsuario?key=$firebaseApiKey";
        $method = "PATCH";
    } else {
        // Si no hay ID, estamos creando un nuevo documento
        $url .= "?key=$firebaseApiKey";
    }

    // Determinar el estado según el tipo de usuario
    $status = ($datosUsuario['rolUsuario'] === "CLIENTE") ? "Activo" : "Bloqueado";

    // Determinar qué clave guardar según el tipo de usuario
    $clave = ($datosUsuario['rolUsuario'] === "CLIENTE") ? $datosUsuario['claveCliente'] : $datosUsuario['claveVendedor'];

    // Formatear los datos para Firebase (estructura de "fields")
    $fields = [
        'usuario' => ['stringValue' => $datosUsuario['usuario']],
        'nombre' => ['stringValue' => $datosUsuario['nombreUsuario']],
        'apellido' => ['stringValue' => $datosUsuario['apellidosUsuario']],
        'correo' => ['stringValue' => $datosUsuario['correoUsuario']],
        'password' => ['stringValue' => $datosUsuario['contrasenaUsuario']],
        'telefono' => ['stringValue' => $datosUsuario['telefonoUsuario']],
        'tipoUsuario' => ['stringValue' => $datosUsuario['rolUsuario']],
        'descripcionUsuario' => ['stringValue' => $datosUsuario['rolUsuario']],
        'status' => ['stringValue' => $status], // Se asigna según la condición
        'claveUsuario' => ['stringValue' => $clave], // Guardar la clave correcta
    ];

    // Preparar la solicitud
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => $method,
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

    $data = json_decode($response, true);
    if (isset($data['name'])) {
        echo json_encode(['success' => true, 'message' => 'Usuario guardado exitosamente.', 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el usuario.']);
    }
}

function actualizarUsuario($idUsario, $data) {}

function mostrarUsuarios($usuarioLogueado, $usuario)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Validamos si el usuario logueado es administrador
    $esAdministrador = ($usuarioLogueado === 'ADMINISTRADOR'); // Comparar el tipo de usuario

    // Si no es administrador, solo mostramos su propio usuario
    if (!$esAdministrador) {
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

        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            if ($fields['usuario']['stringValue'] === $usuario) {
                $usuario = [
                    'id' => str_replace('projects/' . $firebaseProjectId . '/databases/(default)/documents/USUARIOS/', '', $document['name']),
                    'nombreCompleto' => $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue'],
                    'correo' => $fields['correo']['stringValue'] ?? '',
                    'estatus' => $fields['estatus']['stringValue'] ?? '',
                    'rol' => $fields['tipoUsuario']['stringValue'] ?? '',
                    'usuario' => $fields['usuario']['stringValue'] ?? '',
                    'status' => $fields['status']['stringValue'] ?? '',
                ];
                break; // Salimos del loop una vez que encontramos el usuario
            }
        }

        if ($usuario === null) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            return;
        }

        $usuarios = [$usuario];  // Asignamos el usuario encontrado a la lista
    } else {
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
            $usuarios[] = [
                'id' => str_replace('projects/' . $firebaseProjectId . '/databases/(default)/documents/USUARIOS/', '', $document['name']),
                'nombreCompleto' => $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue'],
                'correo' => $fields['correo']['stringValue'] ?? '',
                'estatus' => $fields['estatus']['stringValue'] ?? '',
                'rol' => $fields['tipoUsuario']['stringValue'] ?? '',
                'usuario' => $fields['usuario']['stringValue'] ?? '',
                'status' => $fields['status']['stringValue'] ?? '',
            ];
        }

        // Ordenar usuarios alfabéticamente por `nombreCompleto`
        usort($usuarios, function ($a, $b) {
            return strcmp($a['nombreCompleto'], $b['nombreCompleto']);
        });
    }

    // Devolvemos los usuarios ordenados
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $usuarios]);
    exit();
}

// Función para obtener un usuario específico
function mostrarUsuario($idUsuario)
{
    global $firebaseProjectId, $firebaseApiKey;
    // URL de la API de Firebase para obtener los datos del usuario por ID
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$idUsuario?key=$firebaseApiKey";
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
        $usuario['id'] = $idUsuario;
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

/*function optenerEmpresas()
{
    // Configuración de Firebase
    global $firebaseProjectId, $firebaseApiKey;
    $urlEmpUs = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";
    // Realizar la solicitud a Firebase
    $responseEmpUs = file_get_contents($urlEmpUs);
    if ($responseEmpUs !== false) {
        $dataEmpUs = json_decode($responseEmpUs, true);
        if (isset($dataEmpUs['documents'])) {
            $empresas = [];
            foreach ($dataEmpUs['documents'] as $document) {
                $fields = $document['fields'];

                // Agregar todas las empresas sin filtrar por usuario
                $empresas[] = [
                    'id' => isset($fields['id']['stringValue']) ? $fields['id']['stringValue'] : "N/A",
                    'noEmpresa' => isset($fields['noEmpresa']['stringValue']) ? $fields['noEmpresa']['stringValue'] : "No especificado",
                    'razonSocial' => isset($fields['razonSocial']['stringValue']) ? $fields['razonSocial']['stringValue'] : "Sin Razón Social"
                ];
            }
            // Verificar si se encontraron empresas
            if (count($empresas) > 0) {
                // Ordenar por razón social
                usort($empresas, function ($a, $b) {
                    return strcmp($a['razonSocial'], $b['razonSocial']);
                });

                // Devolver las empresas como respuesta JSON
                echo json_encode(['success' => true, 'data' => $empresas]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontraron empresas.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron datos de empresas en Firebase.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos de empresas.']);
    }
}*/
function obtenerEmpresasNoAsociadas()
{
    global $firebaseProjectId, $firebaseApiKey;

    $usuario = $_GET['usuarioId'] ?? null;
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no proporcionado.']);
        return;
    }

    // Obtener todas las empresas
    $urlEmpresas = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMPRESAS?key=$firebaseApiKey";
    $responseEmpresas = @file_get_contents($urlEmpresas);

    if ($responseEmpresas === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las empresas.']);
        return;
    }

    $dataEmpresas = json_decode($responseEmpresas, true);
    if (!isset($dataEmpresas['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron empresas.']);
        return;
    }

    // Obtener las asociaciones del usuario
    $urlAsociaciones = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";
    $responseAsociaciones = @file_get_contents($urlAsociaciones);

    if ($responseAsociaciones === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las asociaciones.']);
        return;
    }

    $dataAsociaciones = json_decode($responseAsociaciones, true);
    $empresasAsociadas = [];

    if (isset($dataAsociaciones['documents'])) {
        foreach ($dataAsociaciones['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
                $empresasAsociadas[] = $fields['noEmpresa']['stringValue']; // Guardar el `noEmpresa` asociado
            }
        }
    }

    // Filtrar empresas no asociadas
    $empresasDisponibles = [];
    foreach ($dataEmpresas['documents'] as $document) {
        $fields = $document['fields'];
        $noEmpresa = $fields['noEmpresa']['stringValue'] ?? "No especificado";

        if (!in_array($noEmpresa, $empresasAsociadas)) {
            $empresasDisponibles[] = [
                'id' => $fields['id']['stringValue'] ?? "N/A",
                'noEmpresa' => $noEmpresa,
                'razonSocial' => $fields['razonSocial']['stringValue'] ?? "Sin Razón Social"
            ];
        }
    }

    // Ordenar las empresas por Razón Social
    usort($empresasDisponibles, function ($a, $b) {
        return strcmp($a['razonSocial'], $b['razonSocial']);
    });

    echo json_encode(['success' => true, 'data' => $empresasDisponibles]);
    exit();
}

function obtenerUsuarios(){
    global $firebaseProjectId, $firebaseApiKey;

    // URL para obtener usuarios desde Firebase
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
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

    // Procesamos los datos obtenidos
    $usuarios = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];

        // Omitimos los usuarios con tipoUsuario 'CLIENTE'
        if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === 'CLIENTE') {
            continue;
        }

        $usuarios[] = [
            'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/", '', $document['name']),
            'nombre' => isset($fields['nombre']['stringValue'], $fields['apellido']['stringValue'])
                ? $fields['nombre']['stringValue'] . ' ' . $fields['apellido']['stringValue']
                : 'Nombre desconocido',
            'correo' => $fields['correo']['stringValue'] ?? '',
            'estatus' => $fields['estatus']['stringValue'] ?? '',
            'rol' => $fields['tipoUsuario']['stringValue'] ?? '',
            'usuario' => $fields['usuario']['stringValue'] ?? '',
            'claveVendedor' => $fields['claveVendedor']['stringValue'] ?? '',
        ];
    }

    // Ordenamos alfabéticamente por nombre
    usort($usuarios, function ($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });

    // Retornamos los usuarios como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $usuarios]);
    exit();
}
function guardarAsociacion()
{
    global $firebaseProjectId, $firebaseApiKey;

    $empresa = $_POST['empresa'] ?? null;
    $id = $_POST['id'] ?? null;
    $noEmpresa = $_POST['noEmpresa'] ?? null;
    $usuario = $_POST['usuario'] ?? null;
    $claveVendedor = $_POST['claveVendedor'] ?? null;


    if (!$empresa || !$id || !$noEmpresa || !$usuario) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para guardar la asociación.']);
        return;
    }

    // Verificar si ya existe la asociación
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al verificar las asociaciones existentes.']);
        return;
    }

    $data = json_decode($response, true);

    if (isset($data['documents'])) {
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];

            if (
                isset($fields['usuario']['stringValue'], $fields['id']['stringValue']) &&
                $fields['usuario']['stringValue'] === $usuario &&
                $fields['id']['stringValue'] === $id
            ) {
                echo json_encode(['success' => false, 'message' => 'La asociación ya existe.']);
                return;
            }
        }
    }

    // Si no existe, guardar la nueva asociación
    $fields = [
        'empresa' => ['stringValue' => $empresa],
        'id' => ['stringValue' => $id],
        'noEmpresa' => ['stringValue' => $noEmpresa],
        'usuario' => ['stringValue' => $usuario],
        'claveVendedor' => ['stringValue' => $claveVendedor],
    ];

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode(['fields' => $fields]),
        ],
    ];
    $context = stream_context_create($options);

    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la asociación en Firebase.']);
        return;
    }

    // Obtener el ID del documento del usuario
    $urlUsuarios = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
    $responseUsuarios = @file_get_contents($urlUsuarios);

    if ($responseUsuarios === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los usuarios para actualizar el estado.']);
        return;
    }

    $usuariosData = json_decode($responseUsuarios, true);
    $usuarioId = null;

    if (isset($usuariosData['documents'])) {
        foreach ($usuariosData['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
                $usuarioId = str_replace("projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/", '', $document['name']);
                break;
            }
        }
    }

    if (!$usuarioId) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado para actualizar el estado.']);
        return;
    }

    // Actualizar solo el campo `status` del usuario a `Activo` usando `updateMask`
    $urlUsuario = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$usuarioId?updateMask.fieldPaths=status&key=$firebaseApiKey";
    $fieldsUsuario = [
        'status' => ['stringValue' => 'Activo'],
    ];

    $optionsUsuario = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'PATCH',
            'content' => json_encode(['fields' => $fieldsUsuario]),
        ],
    ];
    $contextUsuario = stream_context_create($optionsUsuario);

    $responseUsuario = @file_get_contents($urlUsuario, false, $contextUsuario);

    if ($responseUsuario === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del usuario.']);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'Asociación guardada y estado del usuario actualizado a Activo.']);
    exit();
}
function obtenerAsociaciones()
{
    global $firebaseProjectId, $firebaseApiKey;

    $usuario = $_GET['usuarioId'] ?? null; // Se espera el campo `usuario`
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no proporcionado.']);
        return;
    }

    // URL para obtener las asociaciones desde la colección EMP_USS
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las asociaciones.']);
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron asociaciones.']);
        return;
    }

    // Filtrar las asociaciones por el campo `usuario` y agregar el `id` del documento
    $asociaciones = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];

        if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
            $asociaciones[] = [
                'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/EMP_USS/", '', $document['name']),
                'razonSocial' => $fields['empresa']['stringValue'],
                'noEmpresa' => $fields['noEmpresa']['stringValue'],
            ];
        }
    }

    // Retornar las asociaciones como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $asociaciones]);
    exit();
}
function eliminarAsociacion()
{
    global $firebaseProjectId, $firebaseApiKey;

    $idAsociacion = $_POST['id'] ?? null;
    $usuario = $_POST['usuario'] ?? null;

    if (!$idAsociacion) {
        echo json_encode(['success' => false, 'message' => 'ID de la asociación no proporcionado.']);
        return;
    }

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no proporcionado.']);
        return;
    }

    // 1. Eliminar el documento de la asociación
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS/$idAsociacion?key=$firebaseApiKey";
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'DELETE',
        ],
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la asociación.']);
        return;
    }

    // 2. Verificar si el usuario tiene más asociaciones
    $urlAsociaciones = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";
    $query = [
        'structuredQuery' => [
            'from' => [['collectionId' => 'EMP_USS']],
            'where' => [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'usuario'],
                    'op' => 'EQUAL',
                    'value' => ['stringValue' => $usuario],
                ],
            ],
        ],
    ];
    $optionsQuery = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($query),
        ],
    ];
    $contextQuery = stream_context_create($optionsQuery);
    $responseQuery = @file_get_contents($urlAsociaciones, false, $contextQuery);

    if ($responseQuery === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al verificar las asociaciones del usuario.']);
        return;
    }

    $dataQuery = json_decode($responseQuery, true);

    $tieneAsociaciones = false;
    foreach ($dataQuery as $document) {
        if (isset($document['document'])) {
            $tieneAsociaciones = true;
            break;
        }
    }

    // 3. Si no tiene asociaciones, actualizar su estado a "Bloqueado"
    if (!$tieneAsociaciones) {
        // Obtener el ID del documento del usuario
        $urlUsuarios = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
        $responseUsuarios = @file_get_contents($urlUsuarios);

        if ($responseUsuarios === FALSE) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener el usuario para actualizar su estado.']);
            return;
        }

        $usuariosData = json_decode($responseUsuarios, true);
        $usuarioId = null;

        if (isset($usuariosData['documents'])) {
            foreach ($usuariosData['documents'] as $document) {
                $fields = $document['fields'];
                if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
                    $usuarioId = str_replace("projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/", '', $document['name']);
                    break;
                }
            }
        }

        if (!$usuarioId) {
            echo json_encode(['success' => false, 'message' => 'No se encontró el documento del usuario.']);
            return;
        }

        // Actualizar el estado del usuario a "Bloqueado"
        $urlUsuario = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$usuarioId?updateMask.fieldPaths=status&key=$firebaseApiKey";
        $fieldsUsuario = [
            'status' => ['stringValue' => 'Bloqueado'],
        ];
        $optionsUsuario = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'PATCH',
                'content' => json_encode(['fields' => $fieldsUsuario]),
            ],
        ];
        $contextUsuario = stream_context_create($optionsUsuario);
        $responseUsuario = @file_get_contents($urlUsuario, false, $contextUsuario);

        if ($responseUsuario === FALSE) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del usuario.']);
            return;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Asociación eliminada exitosamente.']);
    exit();
}
function obtenerAsociacionesUsuarios()
{
    global $firebaseProjectId, $firebaseApiKey;

    $usuario = $_GET['usuarioId'] ?? null;

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no proporcionado.']);
        return;
    }

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/EMP_USS?key=$firebaseApiKey";

    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener las asociaciones.']);
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['documents'])) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron asociaciones.']);
        return;
    }

    $asociaciones = [];
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];

        if (isset($fields['usuario']['stringValue']) && $fields['usuario']['stringValue'] === $usuario) {
            $asociaciones[] = [
                'id' => str_replace("projects/$firebaseProjectId/databases/(default)/documents/EMP_USS/", '', $document['name']),
                'razonSocial' => $fields['empresa']['stringValue'],
                'noEmpresa' => $fields['noEmpresa']['stringValue'],
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $asociaciones]);
    exit();
}
function bajaUsuario()
{
    global $firebaseProjectId, $firebaseApiKey;

    $usuarioId = $_POST['usuarioId'] ?? null;

    if (!$usuarioId) {
        echo json_encode(['success' => false, 'message' => 'ID del usuario no proporcionado.']);
        return;
    }

    // Obtener el documento del usuario para verificar su estado actual
    $urlUsuario = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$usuarioId?key=$firebaseApiKey";

    $responseUsuario = @file_get_contents($urlUsuario);

    if ($responseUsuario === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del usuario.']);
        return;
    }

    $usuarioData = json_decode($responseUsuario, true);

    // Verificar si el usuario tiene el campo `status` y si ya está en "Baja"
    if (isset($usuarioData['fields']['status']['stringValue']) && $usuarioData['fields']['status']['stringValue'] === 'Baja') {
        echo json_encode(['success' => false, 'message' => 'El usuario ya está dado de baja.']);
        return;
    }

    // Actualizar el campo `status` del usuario a `Baja`
    $urlUsuarioUpdate = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$usuarioId?updateMask.fieldPaths=status&key=$firebaseApiKey";
    $fieldsUsuario = [
        'status' => ['stringValue' => 'Baja'], // Actualiza solo el campo `status`
    ];

    $optionsUsuario = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'PATCH',
            'content' => json_encode(['fields' => $fieldsUsuario]),
        ],
    ];
    $contextUsuario = stream_context_create($optionsUsuario);

    $responseUsuarioUpdate = @file_get_contents($urlUsuarioUpdate, false, $contextUsuario);

    if ($responseUsuarioUpdate === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del usuario.']);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'El usuario ha sido dado de baja exitosamente.']);
    exit();
}
function activarUsuario()
{
    global $firebaseProjectId, $firebaseApiKey;

    $usuarioId = $_POST['usuarioId'] ?? null;

    if (!$usuarioId) {
        echo json_encode(['success' => false, 'message' => 'ID del usuario no proporcionado.']);
        return;
    }

    // URL para actualizar el campo `status` del usuario
    $urlUsuarioUpdate = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS/$usuarioId?updateMask.fieldPaths=status&key=$firebaseApiKey";
    $fieldsUsuario = [
        'status' => ['stringValue' => 'Activo'], // Actualiza el campo `status` a 'Activo'
    ];

    $optionsUsuario = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'PATCH',
            'content' => json_encode(['fields' => $fieldsUsuario]),
        ],
    ];
    $contextUsuario = stream_context_create($optionsUsuario);

    $responseUsuarioUpdate = @file_get_contents($urlUsuarioUpdate, false, $contextUsuario);

    if ($responseUsuarioUpdate === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del usuario.']);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'El usuario ha sido activado exitosamente.']);
    exit();
}

function obtenerVendedor($conexionData, $noEmpresa)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Construcción del nombre de la tabla VEND basado en la empresa
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta SQL para obtener vendedores activos (STATUS = 'A')
    $sql = "
        SELECT 
            CVE_VEND AS clave, 
            NOMBRE AS nombre
        FROM $nombreTabla
        WHERE STATUS = 'A'
    ";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    $vendedores = [];
    $vendedoresUnicos = []; // Array asociativo para evitar duplicados

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $clave = $row['clave'];
        if (!isset($vendedoresUnicos[$clave])) {
            $vendedoresUnicos[$clave] = $row['nombre'];
            $vendedores[] = [
                'clave' => $clave,
                'nombre' => $row['nombre']
            ];
        }
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    if (!empty($vendedores)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($vendedores, function ($a, $b) {
            return strcmp($a['nombre'], $b['nombre']);
        });

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $vendedores]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron vendedores activos.']);
    }
}
function obtenerDatosVendedor($conexionData, $noEmpresa, $claveVendedor)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // **Formatear la clave antes de la consulta**
    $claveVendedor = str_pad($claveVendedor, 5, " ", STR_PAD_LEFT);

    $sql = "SELECT CVE_VEND AS clave, NOMBRE AS nombre FROM $nombreTabla WHERE STATUS = 'A' AND CVE_VEND = ?";

    $params = [$claveVendedor];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    $vendedor = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    if ($vendedor) {
        echo json_encode(['success' => true, 'data' => $vendedor]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el vendedor.']);
    }
}
function obtenerClientes($conexionData, $noEmpresa)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }
    //$nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[VEND02]";
    $sql = "
    SELECT 
        CLAVE AS clave, 
        NOMBRE AS nombre,
        EMAILPRED AS correo,    -- Asegúrate de que existe este campo en la BD
        TELEFONO AS telefono -- Asegúrate de que existe este campo en la BD
    FROM [SAE90Empre02].[dbo].[CLIE02]
    WHERE STATUS = 'A' AND CLASIFIC LIKE '%E%' -- Cambiar la clasificacion a E
    ";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    $clientes = [];
    $clientesUnicos = []; // Array asociativo para evitar duplicados

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $clave = $row['clave'];
        if (!isset($clientesUnicos[$clave])) {
            $clientesUnicos[$clave] = $row['nombre'];
            $clientes[] = [
                'clave' => $clave,
                'nombre' => $row['nombre'],
                'correo' => $row['correo'] ?? '',  // Evita error si el campo es NULL
                'telefono' => $row['telefono'] ?? '' // Evita error si el campo es NULL
            ];
        }
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    header('Content-Type: application/json');
    if (!empty($clientes)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($clientes, function ($a, $b) {
            return strcmp($a['nombre'] ?? '', $b['nombre'] ?? '');
        });


        echo json_encode(['success' => true, 'data' => $clientes]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron clientes activos.']);
    }
}
function verificarClienteFirebase($claveCliente) {
    global $firebaseProjectId, $firebaseApiKey;

    // URL de Firebase para obtener la colección de USUARIOS
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";

    // Realizamos la solicitud a Firebase
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con Firebase.']);
        return;
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        echo json_encode(['success' => true, 'exists' => false]); // No hay usuarios registrados aún
        return;
    }

    // Recorrer la colección para verificar si existe el cliente con la misma clave
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        if (
            isset($fields['tipoUsuario']['stringValue']) &&
            isset($fields['claveUsuario']['stringValue']) &&
            $fields['tipoUsuario']['stringValue'] === "CLIENTE" &&
            $fields['claveUsuario']['stringValue'] === $claveCliente
        ) {
            echo json_encode(['success' => true, 'exists' => true]); // El cliente ya existe
            return;
        }
    }
    echo json_encode(['success' => true, 'exists' => false]); // Cliente no existe
}
function obtenerDatosCliente($conexionData, $noEmpresa, $claveCliente)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];

    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Tabla de clientes
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta para obtener los datos del cliente
    $sql = "SELECT CLAVE AS clave, NOMBRE AS nombre, EMAILPRED AS correo, TELEFONO AS telefono 
            FROM $nombreTabla 
            WHERE STATUS = 'A' AND CLAVE = ?";
    
    $params = [$claveCliente];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    $cliente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    if ($cliente) {
        echo json_encode(['success' => true, 'data' => $cliente]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el cliente.']);
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    $funcion = $_POST['numFuncion'];
    // Asegúrate de recibir los datos en JSON y decodificarlos correctamente
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la petición.']);
    exit();
}

switch ($funcion) {
    case 1:
        $datosUsuario = [
            'idUsuario' => isset($_POST['idUsuario']) ? $_POST['idUsuario'] : null,
            'usuario' => $_POST['usuario'],
            'nombreUsuario' => $_POST['nombreUsuario'],
            'apellidosUsuario' => $_POST['apellidosUsuario'],
            'correoUsuario' => $_POST['correoUsuario'],
            'contrasenaUsuario' => $_POST['contrasenaUsuario'],
            'telefonoUsuario' => $_POST['telefonoUsuario'],
            'rolUsuario' => $_POST['rolUsuario'],
            'claveVendedor' => $_POST['claveVendedor'],
            'claveCliente' => $_POST['claveCliente'],
        ];
        // Guardar los datos en Firebase o la base de datos
        guardarUsuario($datosUsuario);
        break;

    case 2: // Editar pedido
        $idPedido = $_POST['idPedido'];
        $data = [
            /*
            DATOS
            */];
        actualizarUsuario($idUsario, $data);
        break;

    case 3:
        if (isset($_POST['usuarioLogueado'])) {
            $usuarioLogueado = $_POST['usuarioLogueado'];
            $usuario = $_POST['usuario'];
            mostrarUsuarios($usuarioLogueado, $usuario);
            break;
        } else {
            echo json_encode(['success' => false, 'message' => 'No se proporcionó un usuario.']);
            exit();
        }
    case 4:
        //optenerEmpresas();
        obtenerEmpresasNoAsociadas();
        break;
    case 5:
        $id = $_GET['id'];
        mostrarUsuario($id);
        exit();
    case 6:
        obtenerUsuarios();
        break;
    case 7:
        guardarAsociacion();
        break;
    case 8:
        obtenerAsociaciones();
        break;
    case 9:
        eliminarAsociacion();
        break;
    case 10:
        obtenerAsociacionesUsuarios();
        break;
    case 11:
        bajaUsuario();
        break;
    case 12:
        activarUsuario();
        break;
    case 13:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        obtenerVendedor($conexionData, $noEmpresa);
        break;
    case 14:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        $claveVendedor = $_GET['claveVendedor'];
        obtenerDatosVendedor($conexionData, $noEmpresa, $claveVendedor);
        break;
    case 15:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = "02";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        obtenerClientes($conexionData, $noEmpresa);
        break;
    case 16:
        $claveCliente = $_POST['claveCliente'];
        verificarClienteFirebase($claveCliente);
        break;
    case 17:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = "02";
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];
        $claveCliente = $_GET['claveCliente'];
        obtenerDatosCliente($conexionData, $noEmpresa, $claveCliente);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
