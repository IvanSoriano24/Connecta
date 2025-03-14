<?php
error_reporting(E_ALL); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
include "../../sdk2.php";
date_default_timezone_set('America/Mexico_City');

$datos['RESPUESTA_UTF8'] = "SI";

$datos['PAC']['usuario'] = "DEMO700101XXX";
$datos['PAC']['pass'] = "DEMO700101XXX";
$datos['PAC']['produccion'] = "NO";

$datos['modulo']="dof";                                  //NOMBRE DEL MODULO
$res = mf_ejecuta_modulo($datos);

echo "<pre>";
print_r($res);
echo "</pre>";
?>