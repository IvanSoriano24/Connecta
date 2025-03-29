<?php
date_default_timezone_set('America/Mexico_City');
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
function verificarEstadoPedido($folio, $conexionData, $claveSae)
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
        echo "❌ Error de conexión:\n";
        var_dump(sqlsrv_errors());
        die(json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos',
            'errors' => sqlsrv_errors()
        ]));
    }

    $folio = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($folio, 20, ' ', STR_PAD_LEFT);

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT CVE_DOC, STATUS, TIP_DOC_SIG, DOC_SIG FROM $nombreTabla WHERE CVE_DOC = ?";
    $param = [$CVE_DOC];

    $stmt = sqlsrv_query($conn, $sql, $param);
    if ($stmt === false) {
        echo "❌ Error al ejecutar la consulta:\n";
        var_dump(sqlsrv_errors());
        die(json_encode([
            'success' => false,
            'message' => 'Error al consultar el cliente',
            'errors' => sqlsrv_errors()
        ]));
    }

    $pedido = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$pedido) {
        sqlsrv_close($conn);
        echo "⚠ No se encontraron datos del pedido\n";
        return [
            'success' => false,
            'message' => "No se encontraron datos",
        ];
    }

    $STATUS = $pedido['STATUS'];
    $TIP_DOC_SIG = $pedido['TIP_DOC_SIG'];
    $DOC_SIG = $pedido['DOC_SIG'];

    if ($STATUS !== 'C') {
        if ($TIP_DOC_SIG === 'R' && isset($DOC_SIG)) {
            $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[FACTR" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

            $sql = "SELECT CVE_DOC, STATUS, TIP_DOC_SIG, DOC_SIG, TIP_DOC_ANT, DOC_ANT FROM $nombreTabla2 WHERE CVE_DOC = ?";
            $param = [$DOC_SIG];

            $stmt = sqlsrv_query($conn, $sql, $param);
            if ($stmt === false) {
                echo "❌ Error al consultar la remisión:\n";
                var_dump(sqlsrv_errors());
                die(json_encode([
                    'success' => false,
                    'message' => 'Error al consultar el cliente',
                    'errors' => sqlsrv_errors()
                ]));
            }

            $remision = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (!$remision) {
                sqlsrv_close($conn);
                echo "⚠ No se encontraron datos de la remisión\n";
                return [
                    'success' => false,
                    'message' => "No se encontraron datos",
                ];
            }

            $STATUS_E = $remision['STATUS'];
            $TIP_DOC_SIG_E = $remision['TIP_DOC_SIG'] ?? null;
            $DOC_SIG_E = $remision['DOC_SIG'];
            $TIP_DOC_ANT = $remision['TIP_DOC_ANT'];
            $DOC_ANT = $remision['DOC_ANT'];

            if ($STATUS_E !== 'C') {
                if ($TIP_DOC_SIG_E !== 'F' && !isset($DOC_SIG_E)) {
                    //var_dump($CVE_DOC, $DOC_SIG);
                    return true;
                } elseif ($TIP_DOC_SIG_E === 'F' && isset($DOC_SIG_E)) {
                    //var_dump($CVE_DOC, $DOC_SIG);
                    return false;
                }
            }
        }
    }

    echo "❓ No se cumplieron las condiciones, devolviendo null\n";
    return null; // por si no se cumple ninguna condición
}

function crearFactura($folio, $noEmpresa, $claveSae)
{
    //http://localhost/MDConnecta/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php?cve_doc=18631&noEmpresa=02&claveSae=02
    //$facturaUrl = "https://mdconecta.mdcloud.mx/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php";
    $facturaUrl = "http://localhost/MDConnecta/Servidor/XML/sdk2/ejemplos/cfdi40/ejemplo_factura_basica4.php";

    $data = [
        'cve_doc' => $folio,
        'noEmpresa' => $noEmpresa,
        'claveSae' => $claveSae
    ];
    //var_dump($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $facturaUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $facturaResponse = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error cURL: ' . curl_error($ch);
    }

    curl_close($ch);

    //echo "Respuesta de remision.php: " . $remisionResponse;
    $facturaData = json_decode($facturaResponse, true);
    //echo "Respuesta de decodificada.php: " . $facturaData;
    echo "<div class='container'>
        <div class='title'>Confirmación Exitosa</div>
        <div class='message'>La factura ha sido realizada correctamente.</div>
        <a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>
      </div>";
}

function verificarHora($firebaseProjectId, $firebaseApiKey)
{
    $horaActual = (int) date('Hi'); // Formato "Hi" concatenado como un número entero
    if ($horaActual <= 1455) {
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";
        // Obtener todas las comandas
        $response = @file_get_contents($url);
        if ($response === false) {
            echo "Error al obtener las comandas.\n";
            return;
        }

        $data = json_decode($response, true);
        if (!isset($data['documents'])) {
            echo "No se encontraron comandas.\n";
            return;
        }
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            $status = $fields['status']['stringValue'];
            $folio = $fields['folio']['stringValue'];
            $claveSae = $fields['claveSae']['stringValue'];
            $noEmpresa = $fields['noEmpresa']['stringValue'];

            // Si la comanda está pendiente y es de un día anterior
            if ($status === 'TERMINADA') {
                $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);
                if (!$conexionResult['success']) {
                    echo json_encode($conexionResult);
                    break;
                }
                $conexionData = $conexionResult['data'];
                //Se verifica que el pedido este remitido
                $remitido = verificarEstadoPedido($folio, $conexionData, $claveSae);
                if ($remitido) {
                    //Funcion para crear factura
                    crearFactura($folio, $noEmpresa, $claveSae);
                }
            }
        }
    }
}

verificarHora($firebaseProjectId, $firebaseApiKey);

//$verificado = verificarComandas();
