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
function mostrarClientes($conexionData){
    try {
        $serverName = $conexionData['host']; // Ejemplo: "187.188.133.4,35"
        $connectionInfo = [
            "Database" => $conexionData['nombreBase'],
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password']
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if ($conn === false) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos']));
        }
        // Consulta SQL para obtener los datos
        $sql = "SELECT 
            CLAVE,  
            NOMBRE, 
            CALLE, 
            TELEFONO, 
            SALDO, 
            VAL_RFC AS EstadoDatosTimbrado, 
            NOMBRECOMERCIAL 
        FROM 
            [SAE90Empre01].[dbo].[CLIE01];";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta']));
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
            die();
        }

        //var_dump($response);
        //$response = ['success' => true, 'data' => $clientes];
        //var_dump($clientes);
        // Comprobación para posibles errores de codificación JSON
        // Verificar errores de codificación JSON antes de enviar
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([
                'success' => false,
                'message' => 'Error de codificación JSON: ' . json_last_error_msg()
            ]);
            die();
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'data' => $clientes]);
        //exit();
    } catch (Exception $e) {
        // Si hay algún error, devuelves un error en formato JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function mostrarClienteEspecifico($clave, $conexionData){
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
        die(print_r(sqlsrv_errors(), true)); // Verifica la conexión
    }
    // Limpiar la clave y convertirla a UTF-8
    $clave = mb_convert_encoding(trim($clave), 'UTF-8');
    $clave = str_pad($clave, 10, ' '); // Agregar espacios si es necesario para cumplir con VARCHAR(10)

    // Crear la consulta SQL con un parámetro
    $sql = "SELECT TOP (1) [CLAVE], [STATUS], [NOMBRE], [RFC], [CALLE], [NUMINT], [NUMEXT], 
                    [CRUZAMIENTOS], [COLONIA], [CODIGO], [LOCALIDAD], [MUNICIPIO], [ESTADO], 
                    [PAIS], [NACIONALIDAD], [REFERDIR], [TELEFONO], [CLASIFIC], [FAX], [PAG_WEB], 
                    [CURP], [CVE_ZONA], [IMPRIR], [MAIL], [SALDO], [TELEFONO] 
            FROM [SAE90Empre01].[dbo].[CLIE01] 
            WHERE LTRIM(RTRIM([CLAVE])) = ?";
    // Preparar el parámetro
    $params = array($clave);
    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true)); // Verifica si hay errores en la consulta
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
        mostrarClientes($conexionData);
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
        mostrarClienteEspecifico($clave, $conexionData);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
