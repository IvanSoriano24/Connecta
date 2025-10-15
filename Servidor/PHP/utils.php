<?php
/**********************************************FUNCIONES*************************************************/
function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae){
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
        /*var_dump($fields['noEmpresa']['integerValue']);
        var_dump($noEmpresa);*/
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'nombreBanco' => $fields['nombreBanco']['stringValue'] ?? "",
                    'claveSae' => $fields['claveSae']['stringValue'],
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function formatearClaveCliente($clave){
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
function validarExistencias($conexionData, $partidasData, $claveSae, $conn){
    // Establecer la conexión con SQL Server con UTF-8
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Inicializar listas de productos
    $productosSinExistencia = [];
    $productosConExistencia = [];

    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['producto'];
        $cantidad = $partida['cantidad'];

        // Consultar existencias reales considerando apartados
        $sqlCheck = "SELECT 
                        COALESCE(M.[EXIST], 0) AS EXIST, 
                        COALESCE(I.[APART], 0) AS APART, 
                        (COALESCE(M.[EXIST], 0) - COALESCE(I.[APART], 0)) AS DISPONIBLE 
                     FROM $nombreTabla I
                     INNER JOIN $nombreTabla2 M ON M.CVE_ART = I.CVE_ART
                     WHERE I.[CVE_ART] = ? AND M.CVE_ALM = 1";
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$CVE_ART]);

        if ($stmtCheck === false) {
            sqlsrv_close($conn);
            die(json_encode(['success' => false, 'message' => 'Error al verificar existencias', 'errors' => sqlsrv_errors()]));
        }

        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $existencias = (float)$row['EXIST'];
            $apartados = (float)$row['APART'];
            $disponible = (float)$row['DISPONIBLE'];
            /*var_dump($existencias);
            var_dump($apartados);
            var_dump($disponible);*/
            if ($disponible >= $cantidad) {
                $productosConExistencia[] = [
                    'producto' => $CVE_ART,
                    'existencias' => $existencias,
                    'apartados' => $apartados,
                    'disponible' => $disponible
                ];
            } else {
                $productosSinExistencia[] = [
                    'producto' => $CVE_ART,
                    'existencias' => $existencias,
                    'apartados' => $apartados,
                    'disponible' => $disponible
                ];
            }
        } else {
            $productosSinExistencia[] = [
                'producto' => $CVE_ART,
                'existencias' => 0,
                'apartados' => 0,
                'disponible' => 0
            ];
        }
        sqlsrv_free_stmt($stmtCheck);
    }

    // Responder con el estado de las existencias
    if (!empty($productosSinExistencia)) {
        return [
            'success' => false,
            'exist' => true,
            'message' => 'No hay suficientes existencias para algunos productos',
            'productosSinExistencia' => $productosSinExistencia
        ];
    } else {
        return [
            'success' => true,
            'message' => 'Existencias verificadas correctamente',
            'productosConExistencia' => $productosConExistencia
        ];
    }
}
function obtenerDatosCliente($conexionData, $claveCliente, $claveSae, $conn){
    $clave = formatearClaveCliente($claveCliente);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }
    // Consulta SQL para obtener los datos del cliente
    $sql = "
        SELECT 
            *
        FROM $nombreTabla
        WHERE CLAVE = '$clave'
    ";
    $stmt = sqlsrv_query($conn, $sql, [$clave]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener datos del cliente', 'errors' => sqlsrv_errors()]));
    }

    // Obtener los datos
    $datosCliente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    // Liberar recursos y cerrar la conexión
    sqlsrv_free_stmt($stmt);
    return $datosCliente;
}
function calcularTotalPedido($partidasData){
    $SUBTOTAL = 0;
    $IMPORTE = 0;
    foreach ($partidasData as $partida) {
        $SUBTOTAL += $partida['cantidad'] * $partida['precioUnitario']; // Sumar cantidades totales
        $IMPORTE += $partida['cantidad'] * $partida['precioUnitario']; // Calcular importe total
    }
    $IMPORTT = $IMPORTE;
    $DES_TOT = 0; // Inicializar el total con descuento
    $DES = 0;
    $totalDescuentos = 0; // Inicializar acumulador de descuentos
    $IMP_TOT4 = 0;
    $IMP_T4 = 0;
    foreach ($partidasData as $partida) {
        $precioUnitario = $partida['precioUnitario'];
        $cantidad = $partida['cantidad'];
        $IMPU4 = $partida['iva'];
        $desc1 = $partida['descuento'] ?? 0; // Primer descuento
        $totalPartida = $precioUnitario * $cantidad;
        // **Aplicar los descuentos en cascada**
        $desProcentaje = ($desc1 / 100);
        $DES = $totalPartida * $desProcentaje;
        $DES_TOT += $DES;

        $IMP_T4 = ($totalPartida - $DES) * ($IMPU4 / 100);
        $IMP_TOT4 += $IMP_T4;
    }
    $IMPORTE = $IMPORTE + $IMP_TOT4 - $DES_TOT;
    return $IMPORTE;
}
function formatearClaveVendedor($vendedor){
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $vendedor = trim((string) $vendedor);
    $vendedor = str_pad($vendedor, 5, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 10 caracteres, devolverla tal cual
    if (strlen($vendedor) === 5) {
        return $vendedor;
    }

    // Si es menor a 10 caracteres, rellenar con espacios a la izquierda
    $vendedor = str_pad($vendedor, 5, ' ', STR_PAD_LEFT);
    return $vendedor;
}
function obtenerNombreVendedor($vendedor, $conexionData, $claveSae, $conn){
    $vendedor = formatearClaveVendedor($vendedor);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $sql = "SELECT NOMBRE FROM $nombreTabla WHERE CVE_VEND = ?";
    $stmt = sqlsrv_query($conn, $sql, [$vendedor]);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al obtener la descripción del producto', 'errors' => sqlsrv_errors()]));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $nombre = $row ? $row['NOMBRE'] : '';

    sqlsrv_free_stmt($stmt);

    return $nombre;
}

/**********************************************
 * FUNCIÓN: obtenerDatosSAE
 * Lee datos de inventario del sistema Aspel SAE
 **********************************************/
function obtenerDatosSAE($noEmpresa, $firebaseProjectId, $firebaseApiKey)
{
    // Obtener conexión a SAE desde Firestore (colección CONEXIONES)
    $conexion = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, "");

    if (!$conexion['success']) {
        return []; // No hay conexión configurada
    }

    $cfg = $conexion['data'];
    $host = $cfg['host'];
    $puerto = $cfg['puerto'];
    $usuario = $cfg['usuario'];
    $password = $cfg['password'];
    $nombreBase = $cfg['nombreBase'];

    // Conexión a SQL Server (Aspel SAE)
    $connectionInfo = [
        "Database" => $nombreBase,
        "UID" => $usuario,
        "PWD" => $password,
        "CharacterSet" => "UTF-8"
    ];

    $conn = @sqlsrv_connect($host . "," . $puerto, $connectionInfo);
    if (!$conn) {
        return []; // Evitar detener todo si no se conecta
    }

    $datos = [];
    $query = "SELECT TOP 100 CLAVE, DESCRIPCION, EXIST, COSTO, (EXIST * COSTO) AS TOTAL FROM INVE01 ORDER BY CLAVE ASC";
    $stmt = sqlsrv_query($conn, $query);

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = [
                'clave' => utf8_encode($row['CLAVE']),
                'descripcion' => utf8_encode($row['DESCRIPCION']),
                'existencia' => (float)$row['EXIST'],
                'costo' => (float)$row['COSTO'],
                'total' => (float)$row['TOTAL']
            ];
        }
    }

    sqlsrv_close($conn);
    return $datos;
}

/**********************************************FUNCIONES*************************************************/