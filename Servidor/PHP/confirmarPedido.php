<?php
require 'firebase.php'; // Archivo de configuración de Firebase
session_start();

if (isset($_GET['pedidoId']) && isset($_GET['accion'])) {
    $pedidoId = $_GET['pedidoId'];
    $accion = $_GET['accion'];
    $nombreCliente = urldecode($_GET['nombreCliente'] ?? 'Desconocido');
    $enviarA = urldecode($_GET['enviarA'] ?? 'No especificado');
    $vendedor = urldecode($_GET['vendedor'] ?? 'Sin vendedor');
    $productosJson = urldecode($_GET['productos'] ?? '[]');
    $productos = json_decode($productosJson, true);

    if ($accion === 'confirmar') {
        // Preparar datos para Firebase
        $comanda = [
            "fields" => [
                "idComanda" => ["stringValue" => uniqid()],
                "folio" => ["stringValue" => $pedidoId],
                "nombreCliente" => ["stringValue" => $nombreCliente],
                "enviarA" => ["stringValue" => $enviarA],
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
                "status" => ["stringValue" => "Abierta"]
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
        echo "<div class='container'>
                <div class='title'>Pedido Rechazado</div>
                <div class='message'>El pedido $pedidoId fue rechazado correctamente.</div>
                <a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>
              </div>";
    } else {
        echo "<div class='container'>
                <div class='title'>Acción no válida</div>
                <div class='message'>No se reconoció la acción solicitada.</div>
                <a href='/Cliente/altaPedido.php' class='button'>Volver</a>
              </div>";
    }
} else {
    echo "<div class='container'>
            <div class='title'>Solicitud Inválida</div>
            <div class='message'>No se enviaron los parámetros necesarios para continuar.</div>
            <a href='/index.php' class='button'>Volver al inicio</a>
          </div>";
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