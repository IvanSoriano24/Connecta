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
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]);
        exit;
    }

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
        $stmt->bindParam(':claveCliente', $cliente, PDO::PARAM_STR);
        $stmt->execute();
}
function cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae) {
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
function estadoSql($folio, $conexionData, $claveSae){
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
    $CVE_DOC = str_pad($folio, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "UPDATE $nombreTabla SET 
        STATUS = 'E'
        WHERE CVE_DOC = ?";

    $params = [$CVE_DOC];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al actualizar el pedido', 'errors' => sqlsrv_errors()]));
    }

    // Cerrar la conexión
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return ['success' => true, 'message' => 'Status actualizado'];
}

function verificarPedidos($firebaseProjectId, $firebaseApiKey){
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
        $folio = $fields['folio']['stringValue'];
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            break;
        }
        $conexionData = $conexionResult['data'];

        $pagado = verificarPago($conexionData, $cliente, $claveSae);
        $pagado = true;
        if ($pagado) {
            // Convertir a objetos DateTime
            $fechaElaboracionObj = new DateTime($fechaElaboracion);
            $fechaLimiteObj = new DateTime($fechaLimite);

            // Calcular la diferencia
            $diferencia = $fechaElaboracionObj->diff($fechaLimiteObj);
            if ($diferencia->days === 1 && $diferencia->h === 0 && $diferencia->i === 0 && $diferencia->s === 0) {
                if ($status === 'Sin Pagar') {
                    $pagoId = basename($document['name']);
                    cambiarEstadoPago($firebaseProjectId, $firebaseApiKey, $pagoId, $folio, $conexionData, $claveSae);
                }
            }
        }
    }
}


verificarPedidos($firebaseProjectId, $firebaseApiKey);
