<?php
error_reporting(E_ALL); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
include "lib/cfdi32_multifacturas.php";
date_default_timezone_set('America/Mexico_City');
include_once "lib/cfdi32_multifacturas.php";

$datos['RESPUESTA_UTF8'] = "SI";

$datos['PAC']['usuario'] = "DEMO700101XXX";
$datos['PAC']['pass'] = "DEMO700101XXX";
$datos['PAC']['produccion'] = "NO";


$datos['modulo'] = "codigopostal";
$datos['CP'] = "35027";

$res = mf_ejecuta_modulo($datos);

echo "<pre>";
print_r($res);
echo "</pre>";