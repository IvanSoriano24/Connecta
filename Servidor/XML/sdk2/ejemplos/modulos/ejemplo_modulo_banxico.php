<?php
//error_reporting(0);
date_default_timezone_set('America/Mexico_City');
// NO OLVIDAR ESTE INCLUDE
include_once "../../sdk2.php";
$datos['PAC']['usuario'] = 'DEMO700101XXX';
$datos['PAC']['pass'] = 'DEMO700101XXX';
$datos['PAC']['produccion'] = 'NO'; //   [SI|NO]

$datos['modulo'] = 'banxico';

$res = mf_ejecuta_modulo($datos);

// Se muestran los resultados
print_r($res);