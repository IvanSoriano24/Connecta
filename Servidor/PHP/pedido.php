<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';

session_start();

function obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae, $noEmpresa)
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
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
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
    $clave = str_pad($clave, 10, ' ', STR_PAD_LEFT);
    return $clave;
}
function mostrarPedidos($conexionData, $claveSae)
{
    try {
        $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
        $clave = $_SESSION['usuario']['claveUsuario'] ?? null;
        $claveUsuario = formatearClaveCliente($clave);
        $serverName = $conexionData['host'];
        $connectionInfo = [
            "Database" => $conexionData['nombreBase'],
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password'],
            "TrustServerCertificate" => true
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if ($conn === false) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }

        // Construir nombres de tablas dinámicamente
        $nombreTabla   = "[{$conexionData['nombreBase']}].[dbo].[CLIE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla2  = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $nombreTabla3  = "[{$conexionData['nombreBase']}].[dbo].[VEND"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Reescribir la consulta evitando duplicados con `DISTINCT`
        $sql = "SELECT DISTINCT 
                    f.TIP_DOC AS Tipo,
                    f.FOLIO AS Clave,
                    f.CVE_CLPV AS Cliente,
                    c.NOMBRE AS Nombre,
                    f.STATUS AS Estatus,
                    f.FECHAELAB AS FechaElaboracion,
                    f.CAN_TOT AS Subtotal,
                    f.COM_TOT AS TotalComisiones,
                    f.IMPORTE AS ImporteTotal,
                    v.NOMBRE AS NombreVendedor
                FROM $nombreTabla2 f
                LEFT JOIN $nombreTabla c ON c.CLAVE = f.CVE_CLPV
                LEFT JOIN $nombreTabla3 v ON v.CVE_VEND = f.CVE_VEND
                WHERE f.STATUS IN ('E', 'O') ";

        // Filtrar por vendedor si el usuario no es administrador
        if ($tipoUsuario === 'CLIENTE') {
            $sql .= " AND f.CVE_CLPV = ? ";
            $params = [$claveUsuario];
        } else {
            $params = [];
        }
        // Ejecutar la consulta
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }

        // Arreglo para almacenar los pedidos evitando duplicados
        $clientes = [];
        $clavesRegistradas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Validar codificación y manejar nulos
            foreach ($row as $key => $value) {
                if ($value !== null && is_string($value)) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $encoding = mb_detect_encoding($value, mb_list_encodings(), true);
                        if ($encoding && $encoding !== 'UTF-8') {
                            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                        }
                    }
                } elseif ($value === null) {
                    $value = '';
                }
                $row[$key] = $value;
            }

            // 🚨 Evitar pedidos duplicados usando CVE_DOC como clave única
            if (!in_array($row['Clave'], $clavesRegistradas)) {
                $clavesRegistradas[] = $row['Clave']; // Registrar la clave para evitar repetición
                $clientes[] = $row;
            }
        }
        usort($clientes, function ($a, $b) {
            return strcmp($a['Clave'], $b['Clave']);
        });
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron pedidos']);
            exit;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'data' => $clientes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function altaPedido($data)
{
    global $firebaseProjectId, $firebaseApiKey;
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/MONTOPEDIDO?key=$firebaseApiKey";
    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'PATCH', // PATCH actualiza o agrega campos
            'content' => json_encode($data),
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response !== false) {
        echo json_encode(['success' => true, 'message' => 'Empresa guardada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la empresa.']);
    }
}
function actualizarPedido($idPedido, $data)
{
    global $firebaseProjectId, $firebaseApiKey;

    // Codificar el ID y la clave de la API para evitar errores de formato
    $idPedido = urlencode($idPedido);
    $firebaseApiKey = urlencode($firebaseApiKey);

    // URL de Firestore para obtener los datos actuales del pedido
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS/$idPedido?key=$firebaseApiKey";

    // Obtener los datos actuales del pedido
    $response = file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del pedido.']);
        return;
    }

    // Decodificar la respuesta de Firestore
    $currentData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Error al decodificar los datos del pedido.']);
        return;
    }

    // Verificar si se encontraron los datos del pedido
    if (isset($currentData['fields'])) {
        // Combinar los datos existentes con los nuevos datos
        $updatedData = $currentData['fields'];

        // Actualizar solo los campos proporcionados en $data
        foreach ($data as $key => $value) {
            $updatedData[$key] = ['stringValue' => $value];
        }

        // Preparar los datos finales para la actualización
        $finalData = [
            'fields' => $updatedData
        ];

        // Configuración para la solicitud PATCH
        $updateUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS/$idPedido?key=$firebaseApiKey";
        $options = [
            'http' => [
                'header'  => "Content-type: application/json",
                'method'  => 'PATCH',
                'content' => json_encode($finalData),
            ]
        ];
        $context  = stream_context_create($options);

        // Ejecutar la solicitud de actualización
        $response = file_get_contents($updateUrl, false, $context);

        // Verificar la respuesta de la actualización
        if ($response !== false) {
            echo json_encode(['success' => true, 'message' => 'Pedido actualizado correctamente.']);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar el pedido.',
                'error' => error_get_last()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
    }
}

function cancelarPedido($idPedido)
{
    global $firebaseProjectId, $firebaseApiKey;
    // Codificar el ID y la clave de la API para evitar errores de formato
    $idPedido = urlencode($idPedido);
    $firebaseApiKey = urlencode($firebaseApiKey);
    // URL de Firestore para obtener los datos actuales del pedido
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS/$idPedido?key=$firebaseApiKey";
    // Obtener los datos actuales del pedido
    $response = file_get_contents($url);
    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del pedido.']);
        return;
    }
    // Decodificar la respuesta de Firestore
    $currentData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Error al decodificar los datos del pedido.']);
        return;
    }
    // Verificar si se encontraron los datos del pedido
    if (isset($currentData['fields'])) {
        // Combinar los datos existentes con el nuevo estado
        $updatedData = $currentData['fields'];
        // Actualizar el campo "estado" a "CANCELADO"
        $updatedData['estado'] = ['stringValue' => 'CANCELADO'];
        // Preparar los datos finales para la actualización
        $finalData = [
            'fields' => $updatedData
        ];
        // Configuración para la solicitud PATCH
        $updateUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS/$idPedido?key=$firebaseApiKey";
        $options = [
            'http' => [
                'header'  => "Content-type: application/json",
                'method'  => 'PATCH',
                'content' => json_encode($finalData),
            ]
        ];
        $context  = stream_context_create($options);
        // Ejecutar la solicitud de actualización
        $response = file_get_contents($updateUrl, false, $context);
        // Verificar la respuesta de la actualización
        if ($response !== false) {
            echo json_encode(['success' => true, 'message' => 'Pedido cancelado correctamente.']);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al cancelar el pedido.',
                'error' => error_get_last()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
    }
}
function obtenerPedido($idPedido, $conexionData, $claveSae)
{
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $pedido = str_pad($idPedido, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($pedido, 20, ' ', STR_PAD_LEFT);

    /*$nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2  = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT p.CVE_DOC, p.CVE_ART, p.CANT, p.TOT_PARTIDA, f.IMPORTE
    FROM $nombreTabla p LEFT JOIN $nombreTabla2 f ON f.CVE_DOC = p.CVE_DOC WHERE p.CVE_DOC = ?";*/

    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[INVE"   . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT p.CVE_DOC, p.CVE_ART, p.CANT, p.TOT_PARTIDA, f.CAN_TOT, i.DESCR
        FROM $nombreTabla p 
        LEFT JOIN $nombreTabla2 f ON f.CVE_DOC = p.CVE_DOC 
        LEFT JOIN $nombreTabla3 i ON p.CVE_ART = i.CVE_ART
        WHERE p.CVE_DOC = ?";

    $param = [$CVE_DOC];

    $stmt = sqlsrv_query($conn, $sql, $param);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    // 📌 Obtener todas las imágenes de Firebase en un solo lote
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenesPorArticulo = listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket);

    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cveArt = $row['CVE_ART'];

        // 📌 Asignar las imágenes correspondientes al producto
        $row['IMAGEN_ML'] = $imagenesPorArticulo[$cveArt] ?? []; // Si no hay imágenes, asignar un array vacío

        // 📌 Validar si el precio es null, asignar un precio predeterminado
        $row['PRECIO'] = $row['PRECIO'] ?? 0.00;

        $partidas[] = $row;
    }

    if (count($partidas) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'partidas' => $partidas]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron partidas.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket)
{
    // Asegurar que el prefijo termine con '/'
    $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?prefix=imagenes/";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // Depuración de la respuesta de Firebase
    //var_dump($data);

    $imagenesPorArticulo = [];
    if (isset($data['items'])) {
        foreach ($data['items'] as $item) {
            $name = $item['name']; // Ejemplo: "imagenes/AB-2PAM/imagen1.jpg"
            $parts = explode('/', $name);

            if (count($parts) >= 2) {
                $cveArt = $parts[1]; // "AB-2PAM"
                $imagenesPorArticulo[$cveArt][] = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/" . rawurlencode($name) . "?alt=media";
            }
        }
    }

    return $imagenesPorArticulo;
}


// Verificar si la solicitud es POST o GET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    // Si es una solicitud POST, asignamos el valor de numFuncion
    $funcion = $_POST['numFuncion'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    // Si es una solicitud GET, asignamos el valor de numFuncion
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la peticion.']);
    //break;
}
switch ($funcion) {
    case 1:
        $data = [
            /*
            DATOS
            'dato' => $fields['dato']['tipoDato'],
            */];
        altaPedido($data);
        break;

    case 2: // Editar pedido
        $idPedido = $_POST['idPedido'];
        $data = [
            /*
            DATOS
            */];
        actualizarPedido($idPedido, $data);
        break;

    case 3: // Cancelar pedido
        $idPedido = $_POST['idPedido'];
        /*$data = [
            'fields' => [
                'estado' => ['stringValue' => 'CANCELADO']
            ]
        ]; */
        cancelarPedido($idPedido);
        break;

    case 4: // Obtener pedidos
        //$claveSae = "02";
        $claveSae = "01";
        $noEmpresa = 01;
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae, $noEmpresa);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        mostrarPedidos($conexionData, $claveSae);
        break;

    case 5:
        $idPedido = $_GET['idPedido'];
        //$claveSae = "02";
        $claveSae = "01";
        $noEmpresa = "01";
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae, $noEmpresa);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        //$idPedido = "FYOcALZA6k4v2UpXv6Ln";
        obtenerPedido($idPedido, $conexionData, $claveSae);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        break;
}
