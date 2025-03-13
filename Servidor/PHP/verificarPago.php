<?php
require 'firebase.php';

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
    // Busca el documento donde coincida el campo `claveSae`
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
                    'claveSae' => $fields['claveSae']['stringValue']
                ]
            ];
        }
    }
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}

function verificarPago($conexionData, $cliente, $claveSae)
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
        echo "DEBUG: Error al conectar con la base de datos:\n";
        var_dump(sqlsrv_errors());
        exit;
    }

    // Construir dinámicamente los nombres de las tablas
    $tablaCuenM = "[{$conexionData['nombreBase']}].[dbo].[CUEN_M" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaCuenD = "[{$conexionData['nombreBase']}].[dbo].[CUEN_DET" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaClie  = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    // Consulta: se busca la CxC correspondiente al cliente y a la factura,
    // y se evalúa si el total abonado es mayor o igual al importe original.
    $sql = "
        SELECT TOP 1 
            CASE 
              WHEN ISNULL((SELECT SUM(IMPORTE) 
                           FROM $tablaCuenD 
                           WHERE CVE_CLIE = CUENM.CVE_CLIE 
                             AND REFER = CUENM.REFER 
                             AND NUM_CARGO = CUENM.NUM_CARGO), 0) >= CUENM.IMPORTE
              THEN 1 ELSE 0 
            END AS PAGADA
        FROM $tablaCuenM CUENM
        INNER JOIN $tablaClie CLIENTES ON CLIENTES.CLAVE = CUENM.CVE_CLIE
        WHERE CLIENTES.STATUS <> 'B'
          AND CLIENTES.CLAVE = ?
          AND CUENM.NO_FACTURA = ?
    ";

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, [$cliente, $factura]);
    if ($stmt === false) {
        echo "DEBUG: Error en la consulta:\n";
        var_dump(sqlsrv_errors());
        exit;
    }

    $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    echo "DEBUG: Resultado de la consulta:\n";
    var_dump($result); // Depuración

    sqlsrv_close($conn);

    // Si no se encontró registro, se puede considerar que no está pagada.
    if (!$result) {
        echo "DEBUG: No se encontró registro para la factura $factura y cliente $cliente\n";
        return false;
    }

    // Retorna true si PAGADA es 1, es decir, si el total abonado es mayor o igual que el importe original.
    echo "DEBUG: Valor de PAGADA: " . $result['PAGADA'] . "\n"; // Depuración
    return $result['PAGADA'] == 1;
}

function cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS/$pagoId?updateMask.fieldPaths=status&key=$firebaseApiKey";

    $data = [
        'fields' => [
            'status' => ['stringValue' => 'Pagada']
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
        echo "Error al actualizar la comanda $pagoId.\n";
    } else {
        //echo "Comanda $pagoId actualizada a 'Pagada'.\n";
        estadoSql($folio, $conexionData, $claveSae);
    }
}

function estadoSql($folio, $conexionData, $claveSae)
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
        echo "DEBUG: Error al conectar en estadoSql:\n";
        var_dump(sqlsrv_errors());
        exit;
    }
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "UPDATE $nombreTabla SET 
        STATUS = 'E'
        WHERE CVE_DOC = ?";

    echo "DEBUG: Query de actualización de estadoSql:\n$sql\n"; // Depuración
    echo "DEBUG: Parámetros para estadoSql: ";
    var_dump([$CVE_DOC]); // Depuración

    $stmt = sqlsrv_query($conn, $sql, [$CVE_DOC]);

    if ($stmt === false) {
        echo "DEBUG: Error al actualizar el pedido:\n";
        var_dump(sqlsrv_errors());
        exit;
    }

    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "DEBUG: Estado actualizado para CVE_DOC: $CVE_DOC\n"; // Depuración
    return ['success' => true, 'message' => 'Status actualizado'];
}

function crearRemision($folio, $claveSae, $noEmpresa, $vendedor)
{
    $remisionUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/remision.php";
    //$remisionUrl = 'http://localhost/MDConnecta/Servidor/PHP/remision.php';
    $data = [
        'numFuncion' => 1,
        'pedidoId' => $folio,
        'claveSae' => $claveSae,
        'noEmpresa' => $noEmpresa,
        'vendedor' => $vendedor
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remisionUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $remisionResponse = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error cURL: ' . curl_error($ch);
    }

    curl_close($ch);

    echo "Respuesta de remision.php: " . $remisionResponse;
    $remisionData = json_decode($remisionResponse, true);
    //echo "Respuesta de decodificada.php: " . $remisionData;
    //$cveDoc = trim($remisionData['cveDoc']);

    // Verificar si la respuesta es un PDF
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
}

function verificarPedidos($firebaseProjectId, $firebaseApiKey)
{

    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS?key=$firebaseApiKey";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo "Error al obtener los pagos.\n";
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['documents'])) {
        echo "No se encontraron pagos.\n";
        return;
    }
    $fechaHoy = date('Y-m-d');
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        $status = $fields['status']['stringValue'];
        $fechaElaboracion = $fields['creacion']['stringValue'];
        $fechaLimite = $fields['limite']['stringValue'];
        $cliente = $fields['cliente']['stringValue'];
        $claveSae = $fields['claveSae']['stringValue'];
        $noEmpresa = $fields['noEmpresa']['stringValue'];
        $folio = $fields['folio']['stringValue'];
        $buscar = $fields['buscar']['stringValue'];
        $vendedor = $fields['vendedor']['stringValue'];

        if ($buscar) {

            $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);

            if (!$conexionResult['success']) {
                echo json_encode($conexionResult);
                break;
            }
            $conexionData = $conexionResult['data'];

            $pagado = verificarPago($conexionData, $cliente, $claveSae);
            /*echo "DEBUG: Resultado de verificarPago: ";
            var_dump($pagado); // Depuración*/
            //$pagado = true;
            if ($pagado) {
                date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México
                $fechaHoy = date("Y-m-d H:i:s");
                // Convertir a objetos DateTime
                $fechaHoyObj = new DateTime($fechaHoy);
                $fechaLimiteObj = new DateTime($fechaLimite);
                // Calcular la diferencia
                /*$diferencia = $fechaHoyObj->diff($fechaLimiteObj);
                echo "DEBUG: Diferencia entre elaboración y límite:\n";
                var_dump($diferencia); // Depuración*/
                //exit();
                //if ($diferencia->days == 1 && $diferencia->h === 0 && $diferencia->i === 0 && $diferencia->s === 0) {
                if ($fechaHoyObj <= $fechaLimiteObj) {
                    if ($status === 'Sin Pagar') {
                        $pagoId = basename($document['name']);
                        //echo "DEBUG: Pago encontrado, actualizando estado para pagoId: $pagoId, folio: $folio\n"; // Depuración
                        cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae);
                        crearRemision($folio, $claveSae, $noEmpresa, $vendedor);
                        //Remision y Demas
                    }
                }
            }
        }
    }
}

verificarPedidos($firebaseProjectId, $firebaseApiKey);
