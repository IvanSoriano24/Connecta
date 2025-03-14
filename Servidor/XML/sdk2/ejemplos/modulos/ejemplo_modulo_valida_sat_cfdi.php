<?php
error_reporting(0); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
include "lib/cfdi32_multifacturas.php";
date_default_timezone_set('America/Mexico_City');
include_once "lib/cfdi32_multifacturas.php";

$datos['PAC']['usuario'] = 'DEMO700101XXX';
$datos['PAC']['pass'] = 'DEMO700101XXX';
$datos['PAC']['produccion'] = 'NO';

$datos['modulo'] = 'valida_sat_cfdi';

$datos['factura_xml'] = 'timbrados/cfdi_ejemplo_factura.xml';


$res = cargar_modulo_multifacturas($datos);

print_r($res);
