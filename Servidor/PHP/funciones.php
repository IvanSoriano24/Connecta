<?php

function rechazoWhatsApp()
{
    echo "<div class='container'>
            <div class='title'>Pedido Rechazado</div>
            <div class='message'>Se ha rechazado el pedido.</div>
            <a href='../../Cliente/index.php' class='button'>Volver al inicio</a>
        </div>";
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
<?php
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    // Si es una solicitud POST, asignamos el valor de numFuncion
    $funcion = $_POST['numFuncion'];
    //var_dump($funcion);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    // Si es una solicitud GET, asignamos el valor de numFuncion
    $funcion = $_GET['numFuncion'];
    //var_dump($funcion);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la peticion.']);
    exit;
}

switch ($funcion) {
    case 1:
        rechazoWhatsApp();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Función no válida.']);
        break;
}
?>