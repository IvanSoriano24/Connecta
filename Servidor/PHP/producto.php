<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
//require_once 'whatsapp.php';
//require_once 'clientes.php';

session_start();

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
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
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

function obtenerDetalleProducto($conexionData, $cveArt)
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
        exit();
    }

    //$sql = "SELECT [CVE_ART], [DESCR], [EXIST], [LIN_PROD], [UNI_MED] FROM [{$conexionData['nombreBase']}].[dbo].[INVE02] WHERE [CVE_ART] = ?";
    $sql = "SELECT [CVE_ART], [DESCR], [EXIST], [LIN_PROD], [UNI_MED] FROM [{$conexionData['nombreBase']}].[dbo].[INVE01] WHERE [CVE_ART] = ?";
    $stmt = sqlsrv_query($conn, $sql, [$cveArt]);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit();
    }

    $producto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);

    if (!$producto) {
        echo json_encode(['success' => false, 'message' => "Producto con clave {$cveArt} no encontrado."]);
        exit();
    }

    // Obtener imágenes del producto desde Firebase Storage
    //$firebaseStorageBucket = "mdconnecta-4aeb4.appspot.com";
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app"; // Cambia esto por tu bucket
    $url = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o?prefix=" . rawurlencode("imagenes/{$cveArt}/");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $imagenesData = json_decode($response, true);
    $imagenes = [];

    if (isset($imagenesData['items'])) {
        foreach ($imagenesData['items'] as $item) {
            $imagenes[] = "https://firebasestorage.googleapis.com/v0/b/{$firebaseStorageBucket}/o/" . rawurlencode($item['name']) . "?alt=media";
        }
    }

    echo json_encode([
        'success' => true,
        'producto' => $producto,
        'imagenes' => $imagenes
    ], JSON_PRETTY_PRINT);
}

function obtenerProductosFiltrados($conexionData, $filtroBusqueda, $claveSae)
{
    // Parámetros de paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $porPagina = isset($_GET['porPagina']) ? (int)$_GET['porPagina'] : 10;
    $offset = ($pagina - 1) * $porPagina;


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

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Consulta directa a la tabla fija INVE02

    /*if (preg_match('/[a-zA-Z]/', $filtroBusqueda)) {
        $sql = "SELECT f.[CVE_ART], f.[DESCR], m.[EXIST], f.[LIN_PROD], f.[UNI_MED], f.[APART] 
            FROM {$nombreTabla} AS f
            INNER JOIN $nombreTabla2 AS m ON m.[CVE_ART] = f.[CVE_ART]
            WHERE 
            f.[EXIST] > 0 AND m.[CVE_ALM] = 1 AND
            LOWER(LTRIM(RTRIM(CVE_ART))) LIKE ? OR
            LOWER(LTRIM(RTRIM(DESCR))) LIKE ? OR
            LOWER(LTRIM(RTRIM(EXIST))) LIKE ? AND [STATUS] = 'A'
            ORDER BY f.CVE_ART ASC 
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY;";
    } else {
        $sql = "SELECT f.[CVE_ART], f.[DESCR], m.[EXIST], f.[LIN_PROD], f.[UNI_MED], f.[APART] 
            FROM {$nombreTabla} AS f
            INNER JOIN $nombreTabla2 AS m ON m.[CVE_ART] = f.[CVE_ART]
            WHERE 
            f.[EXIST] > 0 AND m.[CVE_ALM] = 1 AND
            CVE_ART LIKE ? OR
            DESCR LIKE ? OR
            EXIST LIKE ? AND [STATUS] = 'A'
            ORDER BY f.CVE_ART ASC 
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY;";
    }*/
    if (preg_match('%[A-Za-z]%', $filtroBusqueda) > 0) {

        $sql =  "SELECT
        f.[CVE_ART],
        f.[DESCR],
        m.[EXIST],
        f.[LIN_PROD],
        f.[UNI_MED],
        f.[APART]
    FROM {$nombreTabla} AS f
    INNER JOIN {$nombreTabla2} AS m
        ON m.[CVE_ART] = f.[CVE_ART]
    WHERE 
        f.[EXIST]     > 0
        AND m.[CVE_ALM] = 1
        AND (
            LOWER(LTRIM(RTRIM(f.[CVE_ART]))) LIKE ?
            OR LOWER(LTRIM(RTRIM(f.[DESCR])))   LIKE ?
            OR LOWER(LTRIM(RTRIM(CONVERT(VARCHAR(50), m.[EXIST])))) LIKE ?
        )
        AND f.[STATUS] = 'A'
    ORDER BY f.[CVE_ART] ASC  
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    } else {

        $sql = "SELECT
        f.[CVE_ART],
        f.[DESCR],
        m.[EXIST],
        f.[LIN_PROD],
        f.[UNI_MED],
        f.[APART]
    FROM {$nombreTabla} AS f
    INNER JOIN {$nombreTabla2} AS m
        ON m.[CVE_ART] = f.[CVE_ART]
    WHERE 
        f.[EXIST]     > 0
        AND m.[CVE_ALM] = 1
        AND (
            f.[CVE_ART] LIKE ?
            OR f.[DESCR]   LIKE ?
            OR CONVERT(VARCHAR(50), m.[EXIST]) LIKE ?
        )
        AND f.[STATUS] = 'A'
    ORDER BY f.[CVE_ART] ASC  
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    }


    $likeFilter = '%' . $filtroBusqueda . '%';
    $params = [$likeFilter, $likeFilter, $likeFilter, $offset, $porPagina];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $productos[] = $row;
    }

    $countSql  = "SELECT COUNT(f.CVE_ART) AS total
        FROM $nombreTabla f
        WHERE f.[EXIST] > 0";
    $countStmt = sqlsrv_query($conn, $countSql);
    $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
    $total = (int)$totalRow['total'];

    sqlsrv_free_stmt($countStmt);
    sqlsrv_close($conn);
    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'total' => $total, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    // Si es una solicitud POST, asignamos el valor de numFuncion
    $funcion = $_POST['numFuncion'];
    //var_dump($funcion);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    // Si es una solicitud GET, asignamos el valor de numFuncion
    $funcion = $_GET['numFuncion'];
    //var_dump($funcion);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la peticion.']);
    exit;
}

switch ($funcion) {
    case 1:
        /*if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }*/

        //$noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $noEmpresa = "02";
        //$noEmpresa = "01";

        /*if (!isset($_POST['pedidoID']) || empty($_POST['pedidoID'])) {
            echo json_encode(['success' => false, 'message' => 'No se recibió el ID del pedido']);
            exit;
        }*/

        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener la conexión',
                'errors' => $conexionResult['errors'] ?? null
            ]);
            exit;
        }
        $conexionData = $conexionResult['data'];
        $cveArt = $_GET['cveArt'];
        obtenerDetalleProducto($conexionData, $cveArt);
        break;
    case 2:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey);

        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $filtroBusqueda = $_POST['searchText'];
        obtenerProductosFiltrados($conexionData, $filtroBusqueda, $claveSae);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        break;
}
