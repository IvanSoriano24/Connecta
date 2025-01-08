<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
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

// Función para conectar a SQL Server y obtener los datos de clientes
function mostrarPedidos($conexionData){
    try {
        //session_start();
        // Validar si el número de empresa está definido en la sesión
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        // Obtener el número de empresa de la sesión
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        // Validar el formato del número de empresa (asegurarse de que sea numérico)
        if (!is_numeric($noEmpresa)) {
            echo json_encode(['success' => false, 'message' => 'El número de empresa no es válido']);
            exit;
        }
        // Obtener tipo de usuario y clave de vendedor desde la sesión
        $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
        $claveVendedor = $_SESSION['empresa']['claveVendedor'];
        // Configuración de conexión
        $serverName = $conexionData['host'];
        $connectionInfo = [
            "Database" => $conexionData['nombreBase'], // Nombre de la base de datos
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password']
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if ($conn === false) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }
        $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');
        // Construir el nombre de la tabla dinámicamente usando el número de empresa
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($noEmpresa, 2, "0", STR_PAD_LEFT) . "]";
        // Construir la consulta SQL
        if ($tipoUsuario === 'ADMINISTRADOR') {
            // Si el usuario es administrador, mostrar todos los clientes
            $sql = "SELECT 
            TIP_DOC AS Tipo,
                CVE_DOC AS Clave,
                CVE_CLPV AS Cliente,
                (SELECT MAX(NOMBRE) FROM CLIE02 WHERE CLIE02.CLAVE = FACTP02.CVE_CLPV) AS Nombre,
                STATUS AS Estatus,
                CVE_PEDI AS SuPedido,
                FECHAELAB AS FechaElaboracion,
                CAN_TOT AS Subtotal,
                COM_TOT AS TotalComisiones,
                NUM_ALMA AS NumeroAlmacen,
                FORMAENVIO AS FormaEnvio,
                IMPORTE AS ImporteTotal,
                (SELECT MAX(NOMBRE) FROM VEND02 WHERE VEND02.CVE_VEND = FACTP02.CVE_VEND) AS NombreVendedor
            FROM $nombreTabla
            WHERE STATUS IN ('E', 'O')";
            $stmt = sqlsrv_query($conn, $sql);
        } else {
            // Si el usuario no es administrador, filtrar por el número de vendedor
            $sql = "SELECT 
            TIP_DOC AS Tipo,
                CVE_DOC AS Clave,
                CVE_CLPV AS Cliente,
                (SELECT MAX(NOMBRE) FROM CLIE02 WHERE CLIE02.CLAVE = FACTP02.CVE_CLPV) AS Nombre,
                STATUS AS Estatus,
                CVE_PEDI AS SuPedido,
                FECHAELAB AS FechaElaboracion,
                CAN_TOT AS Subtotal,
                COM_TOT AS TotalComisiones,
                NUM_ALMA AS NumeroAlmacen,
                FORMAENVIO AS FormaEnvio,
                IMPORTE AS ImporteTotal,
                (SELECT MAX(NOMBRE) FROM VEND02 WHERE VEND02.CVE_VEND = FACTP02.CVE_VEND) AS NombreVendedor
            FROM $nombreTabla
            WHERE STATUS IN ('E', 'O') AND CVE_VEND = ?;";
            $params = [intval($claveVendedor)]; 
            $stmt = sqlsrv_query($conn, $sql, $params);
        }
        //var_dump($conn);
        //var_dump($sql);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
        }
        // Arreglo para almacenar los datos de clientes
        $clientes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                // Limpiar espacios en blanco solo si el valor no es null
                if ($value !== null && is_string($value)) {
                    $value = trim($value); // Eliminar espacios en blanco al principio y al final

                    // Verificar si el valor no está vacío antes de intentar convertirlo
                    if (!empty($value)) {
                        // Detectar la codificación del valor
                        $encoding = mb_detect_encoding($value, mb_list_encodings(), true);

                        // Si la codificación no se puede detectar o no es UTF-8, convertir la codificación
                        if ($encoding && $encoding !== 'UTF-8') {
                            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                        }
                    }
                } elseif ($value === null) {
                    // Si el valor es null, asignar un valor predeterminado
                    $value = '';
                }

                // Asignar el valor limpio al campo correspondiente
                $row[$key] = $value;
            }
            $clientes[] = $row;
        }
        // Liberar recursos y cerrar la conexión
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        // Retornar los datos en formato JSON
        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron clientes']);
            exit;
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'data' => $clientes]);
    } catch (Exception $e) {
        // Si hay algún error, devuelves un error en formato JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function mostrarPedidoEspecifico($clave, $conexionData)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8" // Aseguramos que todo sea manejado en UTF-8
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Limpiar la clave y convertirla a UTF-8
    $clave = mb_convert_encoding(trim($clave), 'UTF-8');

    // Crear la consulta SQL con un parámetro
    $sql = "SELECT TOP (1) [CLAVE], [STATUS], [NOMBRE], [RFC], [CALLE], [NUMINT], [NUMEXT], 
                    [CRUZAMIENTOS], [COLONIA], [CODIGO], [LOCALIDAD], [MUNICIPIO], [ESTADO], 
                    [PAIS], [NACIONALIDAD], [REFERDIR], [TELEFONO], [CLASIFIC], [FAX], [PAG_WEB], 
                    [CURP], [CVE_ZONA], [IMPRIR], [MAIL], [SALDO], [TELEFONO] 
            FROM [SAE90Empre02].[dbo].[FACTP02] 
            WHERE CAST(LTRIM(RTRIM([CLAVE])) AS NVARCHAR(MAX)) = CAST(? AS NVARCHAR(MAX))";

    // Preparar el parámetro
    $params = array($clave);

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    // Obtener los resultados
    $cliente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    // Verificar si encontramos el cliente
    if ($cliente) {
        echo json_encode([
            'success' => true,
            'cliente' => $cliente
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }

    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}


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
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        mostrarPedidos($conexionData);
        break;
    case 2: // 
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
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $clave = $_GET['clave'];
        mostrarPedidoEspecifico($clave, $conexionData, $noEmpresa);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
