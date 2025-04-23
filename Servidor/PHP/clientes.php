<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
session_start();

function obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey)
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
                    'nombreBase' => $fields['nombreBase']['stringValue']
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}

// Función para conectar a SQL Server y obtener los datos de clientes
function mostrarClientes($conexionData, $claveSae)
{
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
        $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? null;

        // Configuración de conexión
        $serverName = $conexionData['host'];
        $connectionInfo = [
            "Database" => $conexionData['nombreBase'], // Nombre de la base de datos
            "UID" => $conexionData['usuario'],
            "PWD" => $conexionData['password'],
            "TrustServerCertificate" => true
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if ($conn === false) {
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }
        if ($claveUsuario != null) {
            $claveUsuario = mb_convert_encoding(trim($claveUsuario), 'UTF-8');
        }

        // Construir el nombre de la tabla dinámicamente usando el número de empresa
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        // Construir la consulta SQL
        if ($tipoUsuario === 'ADMINISTRADOR') {
            // Si el usuario es administrador, mostrar todos los clientes
            $sql = "SELECT 
                        CLAVE,  
                        NOMBRE, 
                        RFC,
                        CALLE_ENVIO AS CALLE, 
                        TELEFONO, 
                        SALDO, 
                        VAL_RFC AS EstadoDatosTimbrado, 
                        NOMBRECOMERCIAL,
                        DESCUENTO 
                    FROM 
                        $nombreTabla
                    WHERE 
                        STATUS = 'A';";
            $stmt = sqlsrv_query($conn, $sql);
        } else {
            // Si el usuario no es administrador, filtrar por el número de vendedor
            $sql = "SELECT 
                        CLAVE,  
                        NOMBRE, 
                        RFC,
                        CALLE_ENVIO AS CALLE, 
                        TELEFONO, 
                        SALDO, 
                        VAL_RFC AS EstadoDatosTimbrado, 
                        NOMBRECOMERCIAL,
                        DESCUENTO
                    FROM 
                        $nombreTabla
                    WHERE 
                        STATUS = 'A' AND CVE_VEND = ?;";
            $params = [intval($claveUsuario)]; // Si CVE_VEND es un número
            $stmt = sqlsrv_query($conn, $sql, $params);
        }
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

function mostrarClienteEspecifico($clave, $conexionData)
{
    // Establecer la conexión con SQL Server con UTF-8
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "TrustServerCertificate" => true,
        "CharacterSet" => "UTF-8" // Aseguramos que todo sea manejado en UTF-8
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Crear la consulta SQL con un parámetro
    $sql = "SELECT TOP (1) [CLAVE], [STATUS], [NOMBRE], [RFC], [CALLE], [NUMINT], [NUMEXT], 
                    [CRUZAMIENTOS], [COLONIA], [CODIGO], [LOCALIDAD], [MUNICIPIO], [ESTADO], 
                    [PAIS], [NACIONALIDAD], [REFERDIR], [TELEFONO], [CLASIFIC], [FAX], [PAG_WEB], 
                    [CURP], [CVE_ZONA], [IMPRIR], [MAIL], [SALDO], [TELEFONO],
                    [CON_CREDITO], [DIAREV], [DIAPAGO], [DIASCRED], [DIAREV], [METODODEPAGO], [LISTA_PREC], [DESCUENTO], [CVE_VEND]
            FROM $nombreTabla 
            WHERE [CLAVE] = ?";

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
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado', 'errors' => sqlsrv_errors()]);
    }

    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function validarCreditos($conexionData, $clienteId)
{
    // Validar si el ID del cliente está proporcionado
    if (!$clienteId) {
        echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
        exit;
    }

    try {
        // Configuración de conexión
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
            die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
        }
        $claveSae = $_SESSION['empresa']['claveSae'];
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Construir la consulta SQL
        //$sql = "SELECT CAMPLIB7 FROM $nombreTabla WHERE CVE_CLIE = ?";
        $sql = "SELECT CAMPLIB8 FROM $nombreTabla WHERE CVE_CLIE = ?";
        $params = [$clienteId];
        $stmt = sqlsrv_query($conn, $sql, $params);

        // Verificar si hubo errores al ejecutar la consulta
        if ($stmt === false) {
            throw new Exception('Error al ejecutar la consulta.');
        }

        // Obtener los datos del cliente
        $clienteData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$clienteData) {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
            exit;
        }
        //var_dump($clienteData);
        // Limpiar y preparar los datos para la respuesta
        //$conCredito = trim($clienteData['CAMPLIB7'] ?? "");
        $conCredito = trim($clienteData['CAMPLIB8'] ?? "");

        // Enviar respuesta con los datos del cliente
        echo json_encode([
            'success' => true,
            'conCredito' => $conCredito
        ]);
    } catch (Exception $e) {
        // Manejo de errores
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        // Liberar recursos y cerrar la conexión
        if (isset($stmt)) {
            sqlsrv_free_stmt($stmt);
        }
        if (isset($conn)) {
            sqlsrv_close($conn);
        }
    }
}
function calcularTotalPedido($partidasData)
{
    $total = 0;
    foreach ($partidasData as $partida) {
        $total += $partida['cantidad'] * $partida['precioUnitario'];
    }
    return $total;
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
    return str_pad($clave, 10, ' ', STR_PAD_LEFT);
}
function obtenerDatosCliente($conexionData, $claveUsuario, $claveSae)
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
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT 
        CLAVE,  
        NOMBRE, 
        RFC,
        CALLE, 
        TELEFONO, 
        NUMEXT, 
        NUMINT,
        COLONIA,
        CODIGO,
        LOCALIDAD,
        PAIS,
        NOMBRECOMERCIAL,
        LISTA_PREC 
    FROM 
        $nombreTabla
    WHERE 
        CLAVE = ?;";  // ✅ Eliminé el 'AND' incorrecto

    $params = [$claveUsuario]; // ✅ No conviertas a entero si CLAVE puede ser alfanumérica

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    $clientes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        foreach ($row as $key => $value) {
            // Limpiar y convertir datos a UTF-8 si es necesario
            if ($value !== null && is_string($value)) {
                $value = trim($value);
                if (!empty($value)) {
                    $encoding = mb_detect_encoding($value, mb_list_encodings(), true);
                    if ($encoding && $encoding !== 'UTF-8') {
                        $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                    }
                }
            } elseif ($value === null) {
                $value = ''; // Valor predeterminado si es null
            }
            $row[$key] = $value;
        }
        $clientes[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    if (empty($clientes)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron clientes']);
        exit;
    }

    header('Content-Type: application/json; charset=UTF-8');
    //return $clientes;
    echo json_encode(['success' => true, 'data' => $clientes]);
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
        $csrf_token_form = $_POST['token'];
        $csrf_token  = $_SESSION['csrf_token'];
        if ($csrf_token === $csrf_token_form) {
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
            $claveSae = $_SESSION['empresa']['claveSae'];
            $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);
            if (!$conexionResult['success']) {
                echo json_encode($conexionResult);
                break;
            }
            // Mostrar los clientes usando los datos de conexión obtenidos
            $conexionData = $conexionResult['data'];
            mostrarClientes($conexionData, $claveSae);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    case 2: // 
        $csrf_token_form = $_GET['token'];
        $csrf_token  = $_SESSION['csrf_token'];
        if ($csrf_token === $csrf_token_form) {
            if (!isset($_SESSION['empresa']['noEmpresa'])) {
                echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
                exit;
            }
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
            $claveSae = $_SESSION['empresa']['claveSae'];
            $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);

            if (!$conexionResult['success']) {
                echo json_encode($conexionResult);
                break;
            }
            // Mostrar los clientes usando los datos de conexión obtenidos
            $conexionData = $conexionResult['data'];
            $clave = $_GET['clave'];
            $clave = formatearClaveCliente($clave);
            mostrarClienteEspecifico($clave, $conexionData, $noEmpresa);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    case 3:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);

        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $clie = $_GET['clienteId'];
        $clave = formatearClaveCliente($clie);
        validarCreditos($conexionData, $clave);
        break;
    case 4:
        $noEmpresa = "";
        //$claveSae = "02";
        $claveSae = "01";
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);

        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $clave = $_SESSION['usuario']['claveUsuario'];
        $claveUsuario = formatearClaveCliente($clave);
        obtenerDatosCliente($conexionData, $claveUsuario, $claveSae);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
