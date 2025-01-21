<?php
$url = 'https://graph.facebook.com/v21.0/530466276818765/messages';
$token = 'EAAQbK4YCPPcBOwTkPW9uIomHqNTxkx1A209njQk5EZANwrZBQ3pSjIBEJepVYAe5N8A0gPFqF3pN3Ad2dvfSitZCrtNiZA5IbYEpcyGjSRZCpMsU8UQwK1YWb2UPzqfnYQXBc3zHz2nIfbJ2WJm56zkJvUo5x6R8eVk1mEMyKs4FFYZA4nuf97NLzuH6ulTZBNtTgZDZD';
 
$nombre = "Sun Arrow";
$data = array(
    "messaging_product" => "whatsapp",
    "recipient_type" => "individual",
    //"to" => "+527773340218",
    "to" => "+527773750925",
    "type" => "template",
    "template" => array(
        "name" => "hello_world",
        "language" => array(
            "code" => "en_US"
        )
    )
);
 
$data_string = json_encode($data);
 
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string))
);
 
$result = curl_exec($curl);
curl_close($curl);
echo $result;
 
?>