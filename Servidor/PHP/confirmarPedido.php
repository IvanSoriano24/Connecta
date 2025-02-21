<?php
date_default_timezone_set('America/Mexico_City');
require 'firebase.php'; // Archivo de configuración de Firebase
//session_start();

if (isset($_GET['pedidoId']) && isset($_GET['accion'])) {
    $pedidoId = $_GET['pedidoId'];
    $accion = $_GET['accion'];
    $nombreCliente = urldecode($_GET['nombreCliente'] ?? 'Desconocido');
    $enviarA = urldecode($_GET['enviarA'] ?? 'No especificado');
    $vendedor = urldecode($_GET['vendedor'] ?? 'Sin vendedor');
    $productosJson = urldecode($_GET['productos'] ?? '[]');
    $productos = json_decode($productosJson, true);
    $fechaElaboracion = urldecode($_GET['fechaElab'] ?? 'Sin fecha');
    $claveSae = $_GET['claveSae'];
    $noEmpresa = $_GET['noEmpresa'];
    $clave = $_GET['clave'];
    // Obtener fecha y hora actual si no está incluida en los parámetros
    $resultado = verificarExistencia($firebaseProjectId, $firebaseApiKey, $pedidoId);
    if ($resultado) {
        echo "<div class='container'>
            <div class='title'>Solicitud Inválida</div>
            <div class='message'>Este pedido ya fue aceptado/cancelado.</div>
            <a href='/index.php' class='button'>Volver al inicio</a>
          </div>";
    } else {
        if ($accion === 'confirmar') {
            // Obtener la hora actual
            $horaActual = (int) date('H'); // Hora actual en formato 24 horas (e.g., 13 para 1:00 PM)
            // Determinar el estado según la hora
            $estadoComanda = $horaActual >= 13 ? "Pendiente" : "Abierta"; // "Pendiente" después de 1:00 PM

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
                            "values" => array_map(function ($producto) {
                                return [
                                    "mapValue" => [
                                        "fields" => [
                                            "clave" => ["stringValue" => $producto["producto"]],
                                            "descripcion" => ["stringValue" => $producto["descripcion"]],
                                            "cantidad" => ["integerValue" => (int) $producto["cantidad"]],
                                        ]
                                    ]
                                ];
                            }, $productos)
                        ]
                    ],
                    "vendedor" => ["stringValue" => $vendedor],
                    "status" => ["stringValue" => $estadoComanda] // Establecer estado según la hora
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
                    //$remisionUrl = "remision.php";
                    //$remisionUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/remision.php";
                    $remisionUrl = 'http://localhost/MDConnecta/Servidor/PHP/remision.php';

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
                    echo "Respuesta de decodificada.php: " . $remisionData;
                    $cveDoc = trim($remisionData['cveDoc']);
                    //var_dump($cveDoc);
                    // Verificar si la respuesta es un PDF
                    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                    if (strpos($contentType, 'application/pdf') !== false) {
                        // Guardar el PDF localmente o redireccionar
                        file_put_contents("remision_$cveDoc.pdf", $remisionResponse);
                        echo "<script>window.open('remision_$cveDoc.pdf', '_blank');</script>";
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
            if ($response === false) {
                echo "<div class='container'>
                        <div class='title'>Error al Obtener Información</div>
                        <div class='message'>No se pudo obtener la información del vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
                exit;
            }

            $usuariosData = json_decode($response, true);
            $telefonoVendedor = null;

            // Buscar al vendedor por clave
            if (isset($usuariosData['documents'])) {
                foreach ($usuariosData['documents'] as $document) {
                    $fields = $document['fields'];
                    if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $vendedor) {
                        $telefonoVendedor = $fields['telefono']['stringValue'];
                        break;
                    }
                }
            }

            if (!$telefonoVendedor) {
                echo "<div class='container'>
                        <div class='title'>Error al Encontrar Vendedor</div>
                        <div class='message'>No se encontró el número de teléfono del vendedor.</div>
                        <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
                      </div>";
                exit;
            }
            // Enviar mensaje de WhatsApp
            $resultadoWhatsApp = enviarWhatsApp($telefonoVendedor, $pedidoId, $nombreCliente);
            if ($resultadoWhatsApp) {

                $estadoComanda = "Cancelada"; // "Pendiente" después de 1:00 PM

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
                                "values" => array_map(function ($producto) {
                                    return [
                                        "mapValue" => [
                                            "fields" => [
                                                "clave" => ["stringValue" => $producto["producto"]],
                                                "descripcion" => ["stringValue" => $producto["descripcion"]],
                                                "cantidad" => ["integerValue" => (int) $producto["cantidad"]],
                                            ]
                                        ]
                                    ];
                                }, $productos)
                            ]
                        ],
                        "vendedor" => ["stringValue" => $vendedor],
                        "status" => ["stringValue" => $estadoComanda] // Establecer estado según la hora
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
    $url = 'https://graph.facebook.com/v21.0/530466276818765/messages';
    $token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD';
    // Crear el cuerpo de la solicitud para la API
    $data = [
        "messaging_product" => "whatsapp",
        "to" => $numero, // Número del vendedor
        "type" => "template",
        "template" => [
            "name" => "rechazar_pedido_", // Nombre de la plantilla aprobada
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