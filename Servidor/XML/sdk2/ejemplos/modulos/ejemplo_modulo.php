<?php
error_reporting(0); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
include "lib/cfdi32_multifacturas.php";
date_default_timezone_set('America/Mexico_City');
include_once "lib/cfdi32_multifacturas.php";

$datos['RESPUESTA_UTF8'] = "SI";

$datos['PAC']['usuario'] = "DEMO700101XXX";
$datos['PAC']['pass'] = "DEMO700101XXX";
$datos['PAC']['produccion'] = "NO";

$datos['SDK']['ruta'] = "C:\\multifacturas_sdk";

$datos['modulo'] = "ejemplo";
$datos['servicios'] = "SKYPE";

$datos['num1'] = "2";
$datos['num2'] = "3";

$res = cargar_modulo_multifacturas($datos);

print_r($res);