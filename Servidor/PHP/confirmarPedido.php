<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'firebase.php';
require_once '../PHPMailer/clsMail.php';
//require_once 'clientes.php';

session_start();


if (isset($_GET['pedidoId']) && isset($_GET['accion'])) {
    $pedidoId = $_GET['pedidoId'];
    $accion = $_GET['accion'];

    // Variables para el mensaje y el estilo según la acción
    $titulo = "";
    $mensaje = "";
    $color = "";

    if ($accion === 'confirmar') {
        $titulo = "Pedido Confirmado";
        $mensaje = "Gracias por confirmar su pedido. Estamos procesando su solicitud.";
        $color = "#28a745"; // Verde
        // Lógica para confirmar el pedido (actualización en la base de datos)
    } elseif ($accion === 'rechazar') {
        $titulo = "Pedido Rechazado";
        $mensaje = "Lamentamos que haya decidido rechazar su pedido. Si hay algo que podamos mejorar, háganoslo saber.";
        $color = "#dc3545"; // Rojo
        // Lógica para rechazar el pedido (actualización en la base de datos)
    } else {
        $titulo = "Acción No Válida";
        $mensaje = "La acción solicitada no es válida. Por favor, contacte con soporte.";
        $color = "#ffc107"; // Amarillo
    }
} else {
    $titulo = "Solicitud Inválida";
    $mensaje = "No se recibieron los datos necesarios para procesar su solicitud.";
    $color = "#6c757d"; // Gris
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
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
            color: <?php echo $color; ?>;
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
    <div class="container">
        <div class="title"><?php echo $titulo; ?></div>
        <div class="message"><?php echo $mensaje; ?></div>
        <a href="http://localhost/MDConnecta/Cliente/altaPedido.php" class="button">Regresar al Inicio</a>
    </div>
</body>
</html>
