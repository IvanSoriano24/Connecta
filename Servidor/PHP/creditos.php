<?php
require 'firebase.php';

session_start();

function obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae)
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
        if ($fields['claveSae']['stringValue'] === $claveSae) {
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

function obtenerCredito($conexionData, $claveUsuario, $claveSae)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT CLAVE,
        SALDO, LIMCRED
    FROM 
        $nombreTabla
    WHERE 
        CLAVE = ?;";

    $params = [$claveUsuario];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
    }
    $creditos = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    echo json_encode(['success' => true, 'data' => $creditos]);
}
function buscarSujerencias($conexionData, $claveUsuario, $claveSae)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $claveCliente = formatearClaveCliente($claveUsuario);
    $nombreTabla1 = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla4 = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "
            WITH ultimos AS (
        SELECT TOP (25)
            p.CVE_DOC,
            p.CVE_ART, 
            p.CANT,
            c.LISTA_PREC,
            MAX(p.PREC) AS PREC,
            SUM(p.CANT) AS TOTAL_COMPRADO, 
            MAX(i.DESCR) AS DESCR, 
            MAX(i.EXIST) AS EXIST, 
            MAX(i.LIN_PROD) AS LIN_PROD, 
            MAX(i.UNI_MED) AS UNI_MED, 
            MAX(i.APART) AS APART,
            MAX(i.CVE_ESQIMPU) AS CVE_ESQIMPU,
            MAX(i.CVE_UNIDAD) AS CVE_UNIDAD,
            MAX(f.FECHA_DOC) AS ULTIMA_COMPRA
        FROM $nombreTabla1 p
        INNER JOIN $nombreTabla2 f ON p.CVE_DOC = f.CVE_DOC
        INNER JOIN $nombreTabla3 i ON p.CVE_ART = i.CVE_ART
        INNER JOIN $nombreTabla4 c ON f.CVE_CLPV = c.CLAVE
        WHERE f.CVE_CLPV = ?
        AND i.EXIST > 0
        GROUP BY p.CVE_ART, p.CANT, p.CVE_DOC, c.LISTA_PREC
        ORDER BY ULTIMA_COMPRA DESC
    )
    SELECT *
    FROM ultimos
    ORDER BY NEWID();
    ";

    $params = [$claveCliente]; // Parámetros para filtrar por LISTA_PREC y cliente
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // 📌 Obtener todas las imágenes de Firebase en un solo lote
    /*$firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenesPorArticulo = listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket);*/

    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        /*$cveArt = $row['CVE_ART'];

        // 📌 Asignar las imágenes correspondientes al producto
        $row['IMAGEN_ML'] = $imagenesPorArticulo[$cveArt] ?? []; // Si no hay imágenes, asignar un array vacío*/

        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'sinDatos' => true, 'message' => 'No se encontraron productos.']);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function validarSaldo($conexionData, $clave, $claveSae)
{
    // Establecer los datos de conexión
    $serverName = $conexionData['host'];
    $database = $conexionData['nombreBase'];
    $username = $conexionData['usuario'];
    $password = $conexionData['password'];

    try {
        // Conectar usando PDO con TrustServerCertificate habilitado
        $dsn = "sqlsrv:server=$serverName;Database=$database;TrustServerCertificate=true";
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $clave = formatearClaveCliente($clave);
        $tablaCuenM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaCuenD = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaMon = "[{$conexionData['nombreBase']}].[dbo].[MONED" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        // Consulta SQL para verificar saldo vencido
        $sql = "
            SELECT TOP 1 1 
            FROM $tablaCuenM CUENM  
            LEFT JOIN $tablaClie CLIENTES ON CLIENTES.CLAVE = CUENM.CVE_CLIE
            LEFT JOIN $tablaCuenD CUEN ON CUEN.CVE_CLIE = CUENM.CVE_CLIE
                AND CUEN.REFER = CUENM.REFER
                AND CUEN.NUM_CARGO = CUENM.NUM_CARGO
            LEFT JOIN $tablaMon MON ON CUENM.NUM_MONED = MON.NUM_MONED    
            WHERE CUENM.FECHA_VENC < GETDATE()  -- Solo cuentas vencidas
                AND CLIENTES.STATUS <> 'B'     -- Excluir clientes inactivos
                AND CLIENTES.CLAVE = :claveCliente
                AND (
                    ISNULL((SELECT SUM(IMPORTE) FROM $tablaCuenD WHERE CVE_CLIE = CUENM.CVE_CLIE AND REFER = CUENM.REFER AND NUM_CARGO = CUENM.NUM_CARGO), 0) = 0
                    OR ISNULL((SELECT SUM(IMPORTE) FROM $tablaCuenD WHERE CVE_CLIE = CUENM.CVE_CLIE AND REFER = CUENM.REFER AND NUM_CARGO = CUENM.NUM_CARGO), 0) < CUENM.IMPORTE
                )
        ";

        // Preparar y ejecutar la consulta
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':claveCliente', $clave, PDO::PARAM_STR);
        $stmt->execute();

        // Si encuentra al menos un registro con estado 'VENCIDA' o 'VENCIDA PARCIAL', retorna 1
        return $stmt->fetchColumn() ? 1 : 0;
    } catch (PDOException $e) {
        // Manejo de errores
        echo "Error de conexión: " . $e->getMessage();
        return -1; // Código de error
    }
}

function extraerProductosSujerencias($conexionData, $claveSae, $listaPrecioCliente, $CVE_ART ){
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
    $nombreTabla1 = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[PRECIO_X_PROD" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta para obtener los productos más vendidos del cliente
    $sql = "
    SELECT 
        i.[CVE_ART], 
        i.[DESCR], 
        i.[EXIST], 
        i.[LIN_PROD], 
        i.[UNI_MED],
        i.[APART],
        i.[CVE_ESQIMPU],
        i.[CVE_UNIDAD],
        i.[COSTO_PROM],
        p.[PRECIO] -- Se une el precio del producto
    FROM $nombreTabla1 i
    LEFT JOIN $nombreTabla2 p 
        ON i.[CVE_ART] = p.[CVE_ART] 
        AND p.[CVE_PRECIO] = ? WHERE i.[EXIST] > 0";

    $params = [$listaPrecioCliente]; // Parámetro para filtrar por cliente
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL', 'errors' => sqlsrv_errors()]);
        exit;
    }

    // Obtener todas las imágenes de Firebase en un solo lote
    $firebaseStorageBucket = "mdconnecta-4aeb4.firebasestorage.app";
    $imagenesPorArticulo = listarTodasLasImagenesDesdeFirebase($firebaseStorageBucket);
    $productos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Asignar las imágenes correspondientes al producto
        $row['IMAGEN_ML'] = $imagenesPorArticulo[$CVE_ART] ?? []; // Si no hay imágenes, asignar un array vacío

        $productos[] = $row;
    }

    if (count($productos) > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron productos.']);
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
        $claveSae = '02';
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $claveUsuario = $_SESSION['usuario']['claveUsuario'];
        obtenerCredito($conexionData, $claveUsuario, $claveSae);
        break;
    case 2:
        $claveSae = '02';
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $claveUsuario = $_SESSION['usuario']['claveUsuario'];
        /*$validarSaldo = validarSaldo($conexionData, $claveUsuario, $claveSae);
        if ($validarSaldo === 0) {*/
        buscarSujerencias($conexionData, $claveUsuario, $claveSae);
        /*} else {
            echo json_encode([
                'success' => false,
                'saldo' => true,
                'message' => 'Tienes Saldo Vencido.',
            ]);
        }*/
        break;
    case 3:
        $noEmpresa = "02";
        $claveSae = "02";
        $conexionResult = obtenerConexion($firebaseProjectId, $firebaseApiKey, $claveSae);
        $conexionData = $conexionResult['data'];
        $listaPrecioCliente = $_GET['listaPrecioCliente'];
        $articulos = $_GET['articulos'];
        extraerProductosSujerencias($conexionData, $claveSae, $listaPrecioCliente, $articulos );
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
