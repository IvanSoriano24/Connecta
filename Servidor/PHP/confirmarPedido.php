<?php
date_default_timezone_set('America/Mexico_City');
require 'firebase.php'; // Archivo de configuración de Firebase
//session_start();
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
function obtenerProductos($pedidoId, $conexionData, $claveSae){
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
    $CVE_DOC = str_pad($pedidoId, 10, '0', STR_PAD_LEFT); // Asegura que tenga 10 dígitos con ceros a la izquierda
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    $params = [$CVE_DOC];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }

    $partidas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = $row;
    }
    return $partidas;
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerDescripcion($producto, $conexionData, $claveSae){
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


    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[INVE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_ART = ?";
    $params = [$producto];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $productoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($productoData) {
        return $productoData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

if (isset($_GET['pedidoId']) && isset($_GET['accion'])) {
    $pedidoId = $_GET['pedidoId'];
    $accion = $_GET['accion'];
    $nombreCliente = urldecode($_GET['nombreCliente'] ?? 'Desconocido');
    $enviarA = urldecode($_GET['enviarA'] ?? 'No especificado');
    $vendedor = urldecode($_GET['vendedor'] ?? 'Sin vendedor');
    $claveSae = $_GET['claveSae'];
    $noEmpresa = $_GET['noEmpresa'];
    $clave = $_GET['clave'] ?? "";
    $conCredito = $_GET['credito'] ?? "";
    $fechaElaboracion = urldecode($_GET['fechaElab'] ?? 'Sin fecha');
    // Obtener fecha y hora actual si no está incluida en los parámetros
    $resultado = verificarExistencia($firebaseProjectId, $firebaseApiKey, $pedidoId);
    if ($resultado) {
        echo "<div class='container'>
            <div class='title'>Solicitud Inválida</div>
            <div class='message'>Este pedido ya fue aceptado.</div>
            <a href='/index.php' class='button'>Volver al inicio</a>
          </div>";
    } else {
        if ($accion === 'confirmar') {

            if ($conCredito === 'S') {
                $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey);
                if (!$conexionResult['success']) {
                    echo json_encode($conexionResult);
                    die();
                }
                $conexionData = $conexionResult['data'];

                // Obtener la hora actual
                $horaActual = (int) date('H'); // Hora actual en formato 24 horas (e.g., 13 para 1:00 PM)
                // Determinar el estado según la hora
                $estadoComanda = $horaActual >= 13 ? "Pendiente" : "Abierta"; // "Pendiente" después de 1:00 PM
                $producto = obtenerProductos($pedidoId, $conexionData, $claveSae);
                // Preparar datos para Firebase
                $comanda = [
                    "fields" => [
                        "idComanda" => ["stringValue" => uniqid()],
                        "folio" => ["stringValue" => $pedidoId],
                        "nombreCliente" => ["stringValue" => $nombreCliente],
                        "enviarA" => ["stringValue" => $enviarA],
                        "fechaHoraElaboracion" => ["stringValue" => $fechaElaboracion],
                        "productos" => [
                            "arrayValue" => [
                                "values" => array_map(function ($producto) use ($conexionData, $claveSae) {
                                    $productoData = obtenerDescripcion($producto["CVE_ART"], $conexionData, $claveSae);
                                    return [
                                        "mapValue" => [
                                            "fields" => [
                                                "clave" => ["stringValue" => $producto["CVE_ART"]],
                                                "descripcion" => ["stringValue" => $productoData["DESCR"]],
                                                "cantidad" => ["integerValue" => (int) $producto["CANT"]],
                                            ]
                                        ]
                                    ];
                                }, $producto)
                            ]
                        ],
                        "vendedor" => ["stringValue" => $vendedor],
                        "status" => ["stringValue" => $estadoComanda], // Establecer estado según la hora
                        "claveSae" => ["stringValue" => $claveSae],
                        "noEmpresa" => ["stringValue" => $noEmpresa]
                    ]
                ];

                // URL de Firebase
                $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

                // Enviar los datos a Firebase
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\n",
                        'content' => json_encode($comanda)
                    ]
                ]);

                $response = @file_get_contents($url, false, $context);

                if ($response === false) {
                    $error = error_get_last();
                    echo "<div class='container'>
                        <div class='title'>Error al Conectarse</div>
                        <div class='message'>No se pudo conectar a Firebase: " . $error['message'] . "</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
                } else {
                    $result = json_decode($response, true);
                    if (isset($result['name'])) {
                        $remisionUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/remision.php";
                        //$remisionUrl = 'http://localhost/MDConnecta/Servidor/PHP/remision.php';

                        $data = [
                            'numFuncion' => 1,
                            'pedidoId' => $pedidoId,
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

                        //echo "Respuesta de remision.php: " . $remisionResponse;
                        $remisionData = json_decode($remisionResponse, true);
                        //echo "Respuesta de decodificada.php: " . $remisionData;
                        //$cveDoc = trim($remisionData['cveDoc']);

                        // Verificar si la respuesta es un PDF
                        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                        if (strpos($contentType, 'application/pdf') !== false) {
                            // Guardar el PDF localmente o redireccionar
                            file_put_contents("remision.pdf", $remisionResponse);
                            echo "<script>window.open('remision.pdf', '_blank');</script>";
                        }
                        echo "<div class='container'>
                            <div class='title'>Confirmación Exitosa</div>
                            <div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>
                            <a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>
                          </div>";
                    } else {
                        echo "<div class='container'>
                            <div class='title'>Error al Registrar</div>
                            <div class='message'>Hubo un problema al registrar los datos en Firebase.</div>
                            <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                          </div>";
                    }
                }
            } else {
                //Actualizar status para buscar pago
                $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS?key=$firebaseApiKey";

                $response = @file_get_contents($url);
                if ($response === false) {
                    echo "Error al obtener los ...\n";
                    return;
                }
                $data = json_decode($response, true);
                if (!isset($data['documents'])) {
                    echo "No se encontraron ...\n";
                    return;
                }

                // Recorrer todas las comandas y verificar si el folio ya está en la base de datos
                foreach ($data['documents'] as $document) {
                    $fields = $document['fields'];
                    if (isset($fields['folio']['stringValue']) && $fields['folio']['stringValue'] === $pedidoId) {
                        $pagoId = basename($document['name']);
                        $status = $fields['status'];
                        $buscar = $fields['buscar'];
                    }
                }
                if ($buscar['booleanValue']) {
                    echo "<div class='container'>
                        <div class='title'>Pedido aceptado</div>
                        <div class='message'>El pedido fue aceptado y esperando el pago.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
                } else if ($status['stringValue'] === 'Pagada') {
                    echo "<div class='container'>
                        <div class='title'>Pedido pagado</div>
                        <div class='message'>El pedido ya fue pagado.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
                } else {
                    $urlActualizacion = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS/$pagoId?updateMask.fieldPaths=buscar&key=$firebaseApiKey";
                    $data = [
                        'fields' => [
                            'buscar' => ['booleanValue' => true]
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
                        echo "<div class='container'>
                        <div class='title'>Error al actualizar el Pago</div>
                        <div class='message'>No se pudo actualizar la información.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
                    } else {
                        echo "<div class='container'>
                            <div class='title'>Confirmación Exitosa</div>
                            <div class='message'>El pedido ha sido confirmado y tiene 24 horas para pagarlo.</div>
                            <a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>
                          </div>";
                    }
                }
            }
        } elseif ($accion === 'rechazar') {
            $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
            // Consultar Firebase para obtener los datos del vendedor
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Content-Type: application/json\r\n"
                ]
            ]);

            $response = @file_get_contents($firebaseUrl, false, $context);

            $usuariosData = json_decode($response, true);
            $telefonoVendedor = null;

            // Buscar al vendedor por clave
            if (isset($usuariosData['documents'])) {
                foreach ($usuariosData['documents'] as $document) {
                    $fields = $document['fields'];
                    if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
                        if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $vendedor) {
                            if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                                $telefonoVendedor = $fields['telefono']['stringValue'];
                                break;
                            }
                        }
                    }
                }
            }
            //$telefonoVendedor = '+527772127123'; // Interzenda
            $telefonoVendedor = '+527773750925';
            if (!$telefonoVendedor) {
                /*echo "<div class='container'>
                        <div class='title'>Error al Encontrar Vendedor</div>
                        <div class='message'>No se encontró el número de teléfono del vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";*/
                exit;
            }
            // Enviar mensaje de WhatsApp
            $resultadoWhatsApp = enviarWhatsApp($telefonoVendedor, $pedidoId, $nombreCliente);
            if ($resultadoWhatsApp) {
                echo "<div class='container'>
                        <div class='title'>Pedido Rechazado</div>
                        <div class='message'>El pedido $pedidoId fue rechazado correctamente y se notificó al vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>
                      </div>";
            } else {
                echo "<div class='container'>
                        <div class='title'>Error al Notificar</div>
                        <div class='message'>El pedido fue rechazado, pero no se pudo notificar al vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
            }
        } else {
            echo "<div class='container'>
                    <div class='title'>Acción no válida</div>
                    <div class='message'>No se reconoció la acción solicitada.</div>
                    <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                  </div>";
        }
    }
} else {
    echo "<div class='container'>
            <div class='title'>Solicitud Inválida</div>
            <div class='message'>No se enviaron los parámetros necesarios para continuar.</div>
            <a href='/index.php' class='button'>Volver al inicio</a>
          </div>";
}

function enviarWhatsApp($numero, $pedidoId, $nombreCliente)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // Crear el cuerpo de la solicitud para la API

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $numero, // Número del vendedor
        "type" => "template",
        "template" => [
            "name" => "rechazar_pedido", // Nombre de la plantilla aprobada
            "language" => ["code" => "es_MX"], // Idioma de la plantilla
            "components" => [
                // Parámetros del cuerpo de la plantilla
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $nombreCliente], // {{1}}: Nombre del vendedor
                        ["type" => "text", "text" => $pedidoId]  // {{2}}: Número del pedido
                    ]
                ]
            ]
        ]
    ];

    // Convertir los datos a JSON
    $data_string = json_encode($data);

    // Configurar cURL para enviar la solicitud
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ]);

    // Ejecutar la solicitud y cerrar cURL
    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}

function verificarExistencia($firebaseProjectId, $firebaseApiKey, $pedidoId)
{
    // URL para obtener todas las comandas en Firebase
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

    // Obtener la lista de comandas
    $response = @file_get_contents($url);
    if ($response === false) {
        return false; // Si hay un error en la conexión, asumimos que no existe
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        return false; // Si no hay documentos en COMANDA, el pedido no existe
    }

    // Recorrer todas las comandas y verificar si el folio ya está en la base de datos
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'];
        if (isset($fields['folio']['stringValue']) && $fields['folio']['stringValue'] === $pedidoId) {
            return true; // Pedido encontrado en Firebase
        }
    }

    return false; // No se encontró el pedido en la colección
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pedido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #212529;
        }

        .container {
            text-align: center;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .message {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            margin-top: 10px;
            background-color: #007bff;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
</body>

</html>