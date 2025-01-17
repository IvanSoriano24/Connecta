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

    if ($accion === 'confirmar') {
        // Lógica para confirmar el pedido
        echo "Pedido $pedidoId confirmado.";
        // Aquí puedes actualizar el estado del pedido en la base de datos
    } elseif ($accion === 'rechazar') {
        // Lógica para rechazar el pedido
        echo "Pedido $pedidoId rechazado.";
        // Aquí puedes actualizar el estado del pedido en la base de datos
    } else {
        echo "Acción no válida.";
    }
} else {
    echo "Solicitud inválida.";
}
?>