<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
session_start();

function obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa)
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
                    'claveSae' => $fields['claveSae']['stringValue']
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

        $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
        $porPagina = isset($_POST['porPagina']) ? (int)$_POST['porPagina'] : 10;
        $offset = ($pagina - 1) * $porPagina;

        // Construir el nombre de la tabla dinámicamente usando el número de empresa
        $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        // Construir la consulta SQL
        if ($tipoUsuario === 'ADMINISTRADOR') {
            // ADMIN: no hay filtro de vendedor
            // Inicializamos el array de params
            $params = [];

            // Montamos el SQL SIN punto y coma al final
            $sql = "
            SELECT
                CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
            FROM $nombreTabla
            WHERE STATUS = 'A'
            ORDER BY CLAVE ASC
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
            ";

            // Ahora sí agregamos offset y limit a $params
            $params[] = $offset;
            $params[] = $porPagina;

            // Ejecutamos pasando $params
            $stmt = sqlsrv_query($conn, $sql, $params);
        } else {
            // USUARIO normal: filtramos por vendedor
            $params = [intval($claveUsuario)];

            $sql = "
            SELECT
                CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
            FROM $nombreTabla
            WHERE STATUS = 'A'
                AND CVE_VEND = ?
            ORDER BY CLAVE ASC
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
            ";

            // Agregamos offset y limit
            $params[] = $offset;
            $params[] = $porPagina;

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
        $countSql  = "
            SELECT COUNT(DISTINCT CLAVE) AS total
            FROM $nombreTabla WHERE STATUS = 'A'
        ";
        $countStmt = sqlsrv_query($conn, $countSql);
        $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total     = (int)$totalRow['total'];
        sqlsrv_free_stmt($countStmt);
        // Liberar recursos y cerrar la conexión
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        // Retornar los datos en formato JSON
        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron clientes']);
            exit;
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'total' => $total, 'data' => $clientes]);
    } catch (Exception $e) {
        // Si hay algún error, devuelves un error en formato JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function mostrarClientesVendedor($conexionData, $claveSae)
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
        $tablaFacturas = "[{$conexionData['nombreBase']}].[dbo].[FACTF" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Construir la consulta SQL
        if ($tipoUsuario === 'ADMINISTRADOR') {
            // ADMIN: no hay filtro de vendedor
            // Inicializamos el array de params
            $params = [];

            // Montamos el SQL SIN punto y coma al final
            $sql = "
            SELECT
                CLIE.CLAVE,
                CLIE.NOMBRE,
                CLIE.RFC,
                CLIE.CALLE_ENVIO AS CALLE,
                CLIE.TELEFONO,
                CLIE.SALDO,
                CLIE.VAL_RFC AS EstadoDatosTimbrado,
                CLIE.NOMBRECOMERCIAL,
                CLIE.DESCUENTO,
                ISNULL(f.PrimerAnoCompra, '') AS PrimerAnoCompra
            FROM $nombreTabla CLIE
            LEFT JOIN (
                SELECT CVE_CLPV, MIN(YEAR(FECHA_DOC)) AS PrimerAnoCompra
                FROM $tablaFacturas
                WHERE STATUS = 'E'
                GROUP BY CVE_CLPV
            ) AS f ON f.CVE_CLPV = CLIE.CLAVE
            WHERE CLIE.STATUS = 'A'
            ORDER BY CLIE.CLAVE ASC
            ";

            // Ejecutamos pasando $params
            $stmt = sqlsrv_query($conn, $sql, $params);
        } else {
            // USUARIO normal: filtramos por vendedor
            $params = [intval($claveUsuario)];

            $sql = "
            SELECT
                CLIE.CLAVE,
                CLIE.NOMBRE,
                CLIE.RFC,
                CLIE.CALLE_ENVIO AS CALLE,
                CLIE.TELEFONO,
                CLIE.SALDO,
                CLIE.VAL_RFC AS EstadoDatosTimbrado,
                CLIE.NOMBRECOMERCIAL,
                CLIE.DESCUENTO,
                ISNULL(f.PrimerAnoCompra, '') AS PrimerAnoCompra
            FROM $nombreTabla CLIE
            LEFT JOIN (
                SELECT CVE_CLPV, MIN(YEAR(FECHA_DOC)) AS PrimerAnoCompra
                FROM $tablaFacturas
                WHERE STATUS = 'E'
                GROUP BY CVE_CLPV
            ) AS f ON f.CVE_CLPV = CLIE.CLAVE
            WHERE CLIE.STATUS = 'A'
                AND CLIE.CVE_VEND = ?
            ORDER BY CLIE.CLAVE ASC
            ";

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
        $countSql  = "
            SELECT COUNT(DISTINCT CLAVE) AS total
            FROM $nombreTabla WHERE STATUS = 'A'
        ";
        $countStmt = sqlsrv_query($conn, $countSql);
        $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total     = (int)$totalRow['total'];
        sqlsrv_free_stmt($countStmt);
        // Liberar recursos y cerrar la conexión
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        // Retornar los datos en formato JSON
        if (empty($clientes)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron clientes']);
            exit;
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'total' => $total, 'data' => $clientes]);
    } catch (Exception $e) {
        // Si hay algún error, devuelves un error en formato JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
function mostrarClienteBusqueda($claveVendedor, $conexionData, $clienteInput, $claveSae)
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

    $clienteInput = mb_convert_encoding(trim($clienteInput), 'UTF-8');
    $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');

    // Manejo de espacios para la clave
    $clienteClave = str_pad($clienteInput, 10, " ", STR_PAD_LEFT);
    $clienteNombre = '%' . $clienteInput . '%';
    $claveVendedor = str_pad($claveVendedor, 5, " ", STR_PAD_LEFT);

    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];

    // Construir la consulta SQL
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    if ($tipoUsuario === "ADMINISTRADOR") {
        if (preg_match('/[a-zA-Z]/', $clienteInput)) {
            // Búsqueda por nombre
            $sql = "SELECT DISTINCT
                CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
            FROM $nombreTabla
                WHERE LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER ('$clienteNombre') AND [STATUS] = 'A'";
        } else {
            // Búsqueda por clave
            $sql = "SELECT DISTINCT
                CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
            FROM $nombreTabla
            WHERE [CLAVE] = '$clienteClave' AND [STATUS] = 'A'";
        }
    } else {
        if (preg_match('/[a-zA-Z]/', $clienteInput)) {
            // Búsqueda por nombre
            $sql = "SELECT DISTINCT
                    CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
                FROM $nombreTabla
                WHERE LOWER(LTRIM(RTRIM([NOMBRE]))) LIKE LOWER ('$clienteNombre') AND [CVE_VEND] = '$claveVendedor' AND [STATUS] = 'A'";
        } else {
            // Búsqueda por clave
            $sql = "SELECT DISTINCT
                    CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
                FROM $nombreTabla
                WHERE [CLAVE] = '$clienteClave' AND [CVE_VEND] = '$claveVendedor' AND [STATUS] = 'A'";
        }
    }
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error en la consulta', 'errors' => sqlsrv_errors()]));
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
    /*$countSql  = "
            SELECT COUNT(DISTINCT CLAVE) AS total
            FROM $nombreTabla WHERE STATUS = 'A'
        ";
    $countStmt = sqlsrv_query($conn, $countSql);
    $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
    $total     = (int)$totalRow['total'];
    sqlsrv_free_stmt($countStmt);*/
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    // Retornar los datos en formato JSON
    if (empty($clientes)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron clientes']);
        exit;
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true,  'data' => $clientes]); //'total' => $total,
}
function mostrarClientesPedidos($conexionData, $claveSae)
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
            // ADMIN: no hay filtro de vendedor
            // Inicializamos el array de params
            $params = [];

            // Montamos el SQL SIN punto y coma al final
            $sql = "
            SELECT
                CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
            FROM $nombreTabla
            WHERE STATUS = 'A'
            ORDER BY CLAVE ASC
            ";

            // Ejecutamos pasando $params
            $stmt = sqlsrv_query($conn, $sql);
        } else {
            // USUARIO normal: filtramos por vendedor
            $params = [intval($claveUsuario)];

            $sql = "
            SELECT
                CLAVE,
                NOMBRE,
                RFC,
                CALLE_ENVIO AS CALLE,
                TELEFONO,
                SALDO,
                VAL_RFC AS EstadoDatosTimbrado,
                NOMBRECOMERCIAL,
                DESCUENTO
            FROM $nombreTabla
            WHERE STATUS = 'A'
                AND CVE_VEND = ?
            ORDER BY CLAVE ASC
            ";
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
    $nombreTabla3 = "[{$conexionData['nombreBase']}].[dbo].[CLIE_CLIB" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Crear la consulta SQL con un parámetro
    $sql = "SELECT TOP (1) C.[CLAVE], C.[STATUS], C.[NOMBRE], C.[RFC], C.[CALLE], C.[NUMINT], C.[NUMEXT], 
                    C.[CRUZAMIENTOS], C.[COLONIA], C.[CODIGO], C.[LOCALIDAD], C.[MUNICIPIO], C.[ESTADO], 
                    C.[PAIS], C.[NACIONALIDAD], C.[REFERDIR], C.[TELEFONO], C.[CLASIFIC], C.[FAX], C.[PAG_WEB], 
                    C.[CURP], C.[CVE_ZONA], C.[IMPRIR], C.[MAIL], C.[SALDO], C.[TELEFONO],
                    L.[CAMPLIB9] AS CON_CREDITO, C.[DIAREV], C.[DIAPAGO], C.[DIASCRED], C.[DIAREV], C.[METODODEPAGO], C.[LISTA_PREC], C.[DESCUENTO], C.[CVE_VEND],
                    V.[NOMBRE] AS NombreVendedor, C.[LIMCRED], C.[NUMCTAPAGO], C.[DIAREV], C.[DIAPAGO]
            FROM $nombreTabla C 
            INNER JOIN $nombreTabla3 L ON C.CLAVE = L.CVE_CLIE
            INNER JOIN $nombreTabla2 V ON C.CVE_VEND = V.CVE_VEND
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
        $sql = "SELECT CAMPLIB9 FROM $nombreTabla WHERE CVE_CLIE = ?";
        //$sql = "SELECT CAMPLIB8 FROM $nombreTabla WHERE CVE_CLIE = ?";
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
        $conCredito = trim($clienteData['CAMPLIB9'] ?? "");
        //$conCredito = trim($clienteData['CAMPLIB8'] ?? "");

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
function obtenerDatosEnvio($firebaseProjectId, $firebaseApiKey, $claveUsuario)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/ENVIOS?key=$firebaseApiKey";

    // Configura el contexto de la solicitud para manejar errores y tiempo de espera
    $context = stream_context_create([
        'http' => [
            'timeout' => 10 // Tiempo máximo de espera en segundos
        ]
    ]);

    // Realizar la consulta a Firebase
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false; // Error en la petición
    }

    // Decodifica la respuesta JSON
    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false; // No se encontraron documentos
    }

    $datos = [];
    // Busca los datos de la empresa por noEmpresa
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        $documentName = $document['name']; // Aquí obtienes el nombre completo del documento
        $documentId = basename($documentName);
        if (isset($fields['claveCliente']['stringValue']) && $fields['claveCliente']['stringValue'] === $claveUsuario) {
            $datos[] = [
                'idDocumento' => $documentId,
                'id' => $fields['id']['integerValue'] ?? null,
                'tituloEnvio' => $fields['tituloEnvio']['stringValue'] ?? null,
            ];
        }
    }

    if (!empty($datos)) {
        // Ordenar los vendedores por nombre alfabéticamente
        usort($datos, function ($a, $b) {
            return strcmp($a['tituloEnvio'], $b['tituloEnvio']);
        });

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $datos]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se Encontraron Datos de Envio.']);
    }
}
function obtenerDatosCliente($claveVendedor, $conexionData, $filtroBusqueda, $claveSae)
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

    $claveVendedor = mb_convert_encoding(trim($claveVendedor), 'UTF-8');
    $claveVendedor = str_pad($claveVendedor, 5, " ", STR_PAD_LEFT);

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    if ($tipoUsuario === "ADMINISTRADOR") {
        if (preg_match('/[a-zA-Z]/', $filtroBusqueda)) {
            $sql = "SELECT CLAVE, NOMBRE, RFC, CALLE, TELEFONO, NUMEXT, VAL_RFC AS EstadoDatosTimbrado, NUMINT, COLONIA, CODIGO, LOCALIDAD, PAIS, NOMBRECOMERCIAL, LISTA_PREC 
                FROM $nombreTabla
                WHERE 
                LOWER(LTRIM(RTRIM(CLAVE))) LIKE ? OR
                LOWER(LTRIM(RTRIM(NOMBRE))) LIKE ? OR
                LOWER(LTRIM(RTRIM(NOMBRECOMERCIAL))) LIKE ? OR
                LOWER(LTRIM(RTRIM(CALLE))) LIKE ? AND [STATUS] = 'A'
                ORDER BY CLAVE ASC;";
        } else {
            $sql = "SELECT CLAVE, NOMBRE, RFC, CALLE, TELEFONO, NUMEXT, VAL_RFC AS EstadoDatosTimbrado, NUMINT, COLONIA, CODIGO, LOCALIDAD, PAIS, NOMBRECOMERCIAL, LISTA_PREC 
                FROM $nombreTabla
                WHERE 
                CLAVE LIKE ? OR
                NOMBRE LIKE ? OR
                NOMBRECOMERCIAL LIKE ? OR
                CALLE LIKE ? AND [STATUS] = 'A'
                ORDER BY CLAVE ASC;";
        }
    } else {
        if (preg_match('/[a-zA-Z]/', $filtroBusqueda)) {
            $sql = "SELECT CLAVE, NOMBRE, RFC, CALLE, TELEFONO, NUMEXT, VAL_RFC AS EstadoDatosTimbrado, NUMINT, COLONIA, CODIGO, LOCALIDAD, PAIS, NOMBRECOMERCIAL, LISTA_PREC 
                FROM $nombreTabla
                WHERE 
                LOWER(LTRIM(RTRIM(CLAVE))) LIKE ? OR
                LOWER(LTRIM(RTRIM(NOMBRE))) LIKE ? OR
                LOWER(LTRIM(RTRIM(NOMBRECOMERCIAL))) LIKE ? OR
                LOWER(LTRIM(RTRIM(CALLE))) LIKE ? AND [CVE_VEND] = '$claveVendedor' AND [STATUS] = 'A'
                ORDER BY CLAVE ASC;";
        } else {
            $sql = "SELECT CLAVE, NOMBRE, RFC, CALLE, TELEFONO, NUMEXT, VAL_RFC AS EstadoDatosTimbrado, NUMINT, COLONIA, CODIGO, LOCALIDAD, PAIS, NOMBRECOMERCIAL, LISTA_PREC 
                FROM $nombreTabla
                WHERE 
                CLAVE LIKE ? OR
                NOMBRE LIKE ? OR
                NOMBRECOMERCIAL LIKE ? OR
                CALLE LIKE ? AND [CVE_VEND] = '$claveVendedor' AND [STATUS] = 'A'
                ORDER BY CLAVE ASC;";
        }
    }

    $likeFilter = '%' . $filtroBusqueda . '%';
    $params = [$likeFilter, $likeFilter, $likeFilter, $likeFilter];
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
    $countSql  = "
            SELECT COUNT(DISTINCT CLAVE) AS total
            FROM $nombreTabla WHERE STATUS = 'A'
        ";
    $countStmt = sqlsrv_query($conn, $countSql);
    $totalRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
    $total     = (int)$totalRow['total'];

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    if (empty($clientes)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron clientes']);
        exit;
    }

    header('Content-Type: application/json; charset=UTF-8');
    //return $clientes;
    echo json_encode(['success' => true, 'total' => $total, 'data' => $clientes]);
}
function obtenerFolio($firebaseProjectId, $firebaseApiKey)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS?key=$firebaseApiKey";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    $foliosData = json_decode($response, true);

    if (isset($foliosData['documents'])) {
        foreach ($foliosData['documents'] as $document) {
            $fields = $document['fields'];
            if (isset($fields['documento']['stringValue']) && $fields['documento']['stringValue'] === "datosEnvio") {
                $folioSiguiente = (int)$fields['folioSiguiente']['integerValue'];
                // Extraemos el ID a partir del nombre completo
                // e.g. projects/PROJECT_ID/databases/(default)/documents/FOLIOS/{docId}
                $fullName = $document['name'];
                $parts    = explode('/', $fullName);
                $docId    = end($parts);

                return [
                    'documentId'    => $docId,
                    'folioSiguiente' => $folioSiguiente
                ];
            }
        }
    }
    return 0;
}
function guardarDatosEnvio($datosEnvio, $firebaseProjectId, $firebaseApiKey, $folio)
{
    $urlBase = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/ENVIOS?key=$firebaseApiKey";

    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    $claveSae = $_SESSION['empresa']['claveSae'];
    $cliente = formatearClaveCliente($datosEnvio['clienteId']);
    $fieldsToSave = [
        'claveCliente' => ['stringValue' => $cliente],
        'id' => ['integerValue' => $folio['folioSiguiente']],
        'tituloEnvio' => ['stringValue' => $datosEnvio['tituloEnvio']],
        'nombreContacto' => ['stringValue' => $datosEnvio['nombreContacto']],
        'compania' => ['stringValue' => $datosEnvio['compania']],
        'telefonoContacto' => ['stringValue' => $datosEnvio['telefonoContacto']],
        'correoContacto' => ['stringValue' => $datosEnvio['correoContacto']],
        'linea1' => ['stringValue' => $datosEnvio['linea1']],
        'linea2' => ['stringValue' => $datosEnvio['linea2']],
        'codigoPostal' => ['stringValue' => $datosEnvio['codigoPostal']],
        'estado' => ['stringValue' => $datosEnvio['estado']],
        'municipio' => ['stringValue' => $datosEnvio['municipio']],
        'noEmpresa' => ['integerValue' => $noEmpresa],
        'claveSae' => ['stringValue' => $claveSae]
    ];
    // Construir el payload en formato JSON
    $payload = json_encode(['fields' => $fieldsToSave]);

    // Configurar las opciones de la solicitud HTTP
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload
        ]
    ];

    // Crear el contexto de la solicitud
    $context  = stream_context_create($options);

    try {
        $response = file_get_contents($urlBase, false, $context);
        if ($response === false) {
            throw new Exception('Error al conectar con Firestore para guardar el documento.');
        }
        //echo json_encode(['success' => true, 'message' => 'Documento guardado correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
function actualizarFolio($firebaseProjectId, $firebaseApiKey, $folio)
{
    $id = $folio['documentId'];
    $nuevoFolio = $folio['folioSiguiente'] + 1;
    $urlActualizacion = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS/$id?updateMask.fieldPaths=folioSiguiente&key=$firebaseApiKey";
    $data = [
        'fields' => [
            'folioSiguiente' => ['integerValue' => $nuevoFolio]
        ]
    ];
    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($urlActualizacion, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'No se Actualizo el Folio']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Datos de Envio Guardados']);
    }
}
function llenarDatosEnvio($id, $firebaseProjectId, $firebaseApiKey)
{
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/ENVIOS/$id?key=$firebaseApiKey";

    $response = @file_get_contents($url);

    if ($response === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del usuario.']);
        return;
    }

    $envioData = json_decode($response, true);
    echo json_encode(['success' => true, 'data' => $envioData]);
    //var_dump($envioData['name']);
    /*$datosEnvio = [];
    foreach ($envioData as $document) {
        $documentName = $document['name']; 
        $documentId = basename($documentName);
        if (isset($document['noEmpresa']['integerValue']) && $document['noEmpresa']['integerValue'] === $noEmpresa) {
            $datosEnvio [] = [
                'idDocumento' => $documentId,
                'noEmpresa' => $document['noEmpresa']['integerValue'] ?? null,
                'id' => $document['id']['integerValue'] ?? null,
                'tituloEnvio' => $document['tituloEnvio']['stringValue'] ?? null,
                'nombreContacto' => $document['nombreContacto']['stringValue'] ?? null,
                'compania' => $document['compania']['stringValue'] ?? null,
                'telefonoContacto' => $document['telefonoContacto']['stringValue'] ?? null,
                'correoContacto' => $document['correoContacto']['stringValue'] ?? null,
                'linea1' => $document['linea1']['stringValue'] ?? null,
                'linea2' => $document['linea2']['stringValue'] ?? null,
                'codigoPostal' => $document['codigoPostal']['stringValue'] ?? null,
                'estado' => $document['estado']['stringValue'] ?? null,
                'municipio' => $document['municipio']['stringValue'] ?? null,
                'claveSae' => $document['claveSae']['stringValue'] ?? null,
                'claveCliente' => $document['claveCliente']['stringValue'] ?? null
            ];
        }
    }*/
    //var_dump($envioData);
}
function actualizarDatosEnvio($id, $contacto, $firebaseProjectId, $firebaseApiKey)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/ENVIOS/$id?updateMask.fieldPaths=nombreContacto&key=$firebaseApiKey";

    $data = [
        'fields' => [
            'nombreContacto' => ['stringValue' => $contacto]
        ]
    ];
    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'No se Actualizo el Nombre del Contacto']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Datos de Envio Guardados']);
    }
}
function obtenerDatosEnvioEditar($firebaseProjectId, $firebaseApiKey, $pedidoID, $conexionData, $noEmpresa, $claveSae)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $CVE_DOC = str_pad($pedidoID, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    // Construir la consulta SQL
    $sql = "SELECT DAT_ENVIO FROM $nombreTabla WHERE CVE_DOC = ?";
    //$sql = "SELECT CAMPLIB8 FROM $nombreTabla WHERE CVE_CLIE = ?";
    $params = [$CVE_DOC];
    $stmt = sqlsrv_query($conn, $sql, $params);

    // Verificar si hubo errores al ejecutar la consulta
    if ($stmt === false) {
        throw new Exception('Error al ejecutar la consulta.');
    }

    // Obtener los datos del cliente
    $envioData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$envioData) {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
        exit;
    }
    //var_dump($clienteData);
    // Limpiar y preparar los datos para la respuesta
    $DAT_ENVIO = trim($envioData['DAT_ENVIO'] ?? "");
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $sqlEnvio = "SELECT * FROM $nombreTabla2 WHERE CVE_INFO = ?";
    $params2 = [$DAT_ENVIO];
    $stmt2 = sqlsrv_query($conn, $sqlEnvio, $params2);
    if ($stmt2 === false) {
        throw new Exception('Error al ejecutar la consulta.');
    }

    // Obtener los datos del cliente
    $datos = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
    
    if (!empty($datos)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $datos]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se Encontraron Datos de Envio.']);
    }
}
function obtenerDatosEnvioVisualizar($firebaseProjectId, $firebaseApiKey, $pedidoID, $conexionData, $noEmpresa, $claveSae)
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
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }
    $claveSae = $_SESSION['empresa']['claveSae'];
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INFENVIO" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    // Construir la consulta SQL
    $sql = "SELECT * FROM $nombreTabla WHERE CVE_INFO = ?";
    //$sql = "SELECT CAMPLIB8 FROM $nombreTabla WHERE CVE_CLIE = ?";
    $params = [$pedidoID];
    $stmt = sqlsrv_query($conn, $sql, $params);

    // Verificar si hubo errores al ejecutar la consulta
    if ($stmt === false) {
        throw new Exception('Error al ejecutar la consulta.');
    }

    // Obtener los datos del cliente
    $envioData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);


    if (!empty($envioData)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $envioData]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se Encontraron Datos de Envio.']);
    }
}

function obtenerDatosClienteE($conexionData, $claveUsuario, $claveSae)
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
    echo json_encode(['success' => true, 'data' => $clientes]);
    exit();
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
            $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
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
            $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);

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
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);

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
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $claveVendedor = $_SESSION['usuario']['claveUsuario'];
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);

        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $filtroBusqueda = $_POST['searchText'];
        obtenerDatosCliente($claveVendedor, $conexionData, $filtroBusqueda, $claveSae);
        break;
    case 5:
        //$noEmpresa = "01";
        $noEmpresa = "2";
        //$claveSae = "02";
        $claveSae = "02";
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);

        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $clave = $_GET["clave"];
        $claveUsuario = formatearClaveCliente($clave);
        obtenerDatosEnvio($firebaseProjectId, $firebaseApiKey, $claveUsuario);
        break;
    case 6:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $datosEnvio = [
            'clienteId'        => $_POST['clienteId']         ?? '',
            'tituloEnvio'      => $_POST['tituloEnvio']       ?? '',
            'nombreContacto'   => $_POST['nombreContacto']    ?? '',
            'compania'         => $_POST['compañia']          ?? '',
            'telefonoContacto' => $_POST['telefonoContacto']  ?? '',
            'correoContacto'   => $_POST['correoContacto']    ?? '',
            'linea1'           => $_POST['linea1Contacto']    ?? '',
            'linea2'           => $_POST['linea2Contacto']    ?? '',
            'codigoPostal'     => $_POST['codigoContacto']    ?? '',
            'estado'           => $_POST['estadoContacto']    ?? '',
            'municipio'        => $_POST['municipioContacto'] ?? '',
        ];
        $folio = obtenerFolio($firebaseProjectId, $firebaseApiKey);
        guardarDatosEnvio($datosEnvio, $firebaseProjectId, $firebaseApiKey, $folio);
        actualizarFolio($firebaseProjectId, $firebaseApiKey, $folio);
        break;
    case 7:
        $id = $_POST["idDocumento"];
        llenarDatosEnvio($id, $firebaseProjectId, $firebaseApiKey);
        break;
    case 8:
        $id = $_POST["idDocumento"];
        $contacto = $_POST["nombreContacto"];
        actualizarDatosEnvio($id, $contacto, $firebaseProjectId, $firebaseApiKey);
        break;
    case 9:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        $csrf_token_form = $_POST['token'];
        $csrf_token  = $_SESSION['csrf_token'];
        if ($csrf_token === $csrf_token_form) {
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
            $claveSae = $_SESSION['empresa']['claveSae'];
            $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
            if (!$conexionResult['success']) {
                echo json_encode($conexionResult);
                break;
            }
            // Mostrar los clientes usando los datos de conexión obtenidos
            $conexionData = $conexionResult['data'];
            mostrarClientesPedidos($conexionData, $claveSae);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }
        break;
    case 10:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);

        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $pedidoID = $_POST["pedidoID"];
        obtenerDatosEnvioEditar($firebaseProjectId, $firebaseApiKey, $pedidoID, $conexionData, $noEmpresa, $claveSae);
        break;
    case 11:
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);

        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $envioID = $_POST["envioID"];
        obtenerDatosEnvioVisualizar($firebaseProjectId, $firebaseApiKey, $envioID, $conexionData, $noEmpresa, $claveSae);
    case 12:
        if (!isset($_SESSION['empresa']['noEmpresa'])) {
            echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión']);
            exit;
        }
        /*$csrf_token_form = $_POST['token'];
        $csrf_token  = $_SESSION['csrf_token'];
        if ($csrf_token === $csrf_token_form) {*/
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveSae = $_SESSION['empresa']['claveSae'];
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        mostrarClientesVendedor($conexionData, $claveSae);
        /*} else {
            echo json_encode([
                'success' => false,
                'message' => 'Error en la sesion.',
            ]);
        }*/
        break;
    case 13:
        $noEmpresa = "2";
        $claveSae = "02";
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        // Mostrar los clientes usando los datos de conexión obtenidos
        $conexionData = $conexionResult['data'];
        $tipoUsuario = $_SESSION['usuario']['tipoUsuario'];
        if ($tipoUsuario === "ADMINISTRADOR") {
            $claveUsuario = 3;
        } else {
            $claveUsuario = $_SESSION['usuario']['claveUsuario']; // Clave del cliente
            $claveUsuario = formatearClaveCliente($claveUsuario);
        }
        obtenerDatosClienteE($conexionData, $claveUsuario, $claveSae);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Funcion no valida.']);
        break;
}
