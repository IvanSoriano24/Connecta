<?php
error_reporting(0); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
date_default_timezone_set('America/Mexico_City');
include_once "lib/cfdi32_multifacturas.php";

$datos['RESPUESTA_UTF8'] = "SI";

$datos['PAC']['usuario'] = "DEMO700101XXX";
$datos['PAC']['pass'] = "DEMO700101XXX";
$datos['PAC']['produccion'] = "NO";

$datos['SDK']['ruta'] = "C:\\multifacturas_sdk";

$datos['modulo'] = 'cancelacion';
$datos['cer'] = 'pruebas/aaa010101aaa.cer';
$datos['key'] = 'pruebas/aaa010101aaa.key';
$datos['pass'] = '12345678a';
$datos['rfc'] = 'AAA010101AAA';
$datos['xml'] = 'VENTA-A-PUBLICO-EN-GENERAL-.xml';

$res = cargar_modulo_multifacturas($datos);

