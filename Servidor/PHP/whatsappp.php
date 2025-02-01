<?php
function enviarMensajeWhatsApp($telefono, $mensaje)
{
    $url = 'https://graph.facebook.com/v17.0/586289881215812/messages'; // Cambia con tu ID de teléfono
    $token = 'EAASuzJy6mxQBOyGkNdJqFxh88sihU1n5xxPf91Fa17xZBaJRpKsYxCAzyJbrSArKUvR1C3fhobcxr0MuAufqZBqLHfXnVA4b9TQQOviBsZA87joWbBz2IiBgAJ4Bmph35YPbT5JZCuZBucO74WYqLpvSc4e1flihZBhOWmi8JZAIhUSXeljZCs6d8A9cU2xRbSx9BNZApTWdlqBanCVw9RZA3S4YENzLMZD'; // Tu token de acceso de la API de WhatsApp Business
    
    // Datos para el mensaje
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $telefono,  // Número al que deseas enviar el mensaje
        'text' => [
            'body' => $mensaje
        ]
    ];

    // Configuración de la solicitud cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    }
    curl_close($ch);

    return $response;
}

function enviarConfirmacion($telefono, $mensaje)
{
    $url = 'https://graph.facebook.com/v17.0/586289881215812/messages'; // Cambia con tu ID de teléfono
    $token = 'EAASuzJy6mxQBOyGkNdJqFxh88sihU1n5xxPf91Fa17xZBaJRpKsYxCAzyJbrSArKUvR1C3fhobcxr0MuAufqZBqLHfXnVA4b9TQQOviBsZA87joWbBz2IiBgAJ4Bmph35YPbT5JZCuZBucO74WYqLpvSc4e1flihZBhOWmi8JZAIhUSXeljZCs6d8A9cU2xRbSx9BNZApTWdlqBanCVw9RZA3S4YENzLMZD'; // Token de acceso

    // Datos del mensaje
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $telefono,
        'text' => [
            'body' => $mensaje
        ]
    ];

    // Configuración de la solicitud cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    }
    curl_close($ch);

    return $response;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numeroPrueba = '+1 555-170-2812';  // El número de prueba
$miNumero = '527773750925';  // Tu número real en formato internacional
$mensaje = "¡Hola! Este es un mensaje de prueba desde la API de WhatsApp.";

$response = enviarMensajeWhatsApp($miNumero, $mensaje);
echo $response;  // Puedes revisar la respuesta para confirmar que el mensaje fue enviado correctamente

}
