<?php
error_reporting(E_ALL); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG

date_default_timezone_set('America/Mexico_City');

include_once "../../sdk2.php";

$datos['RESPUESTA_UTF8'] = "SI";

$datos['PAC']['usuario'] = "DEMO700101XXX";
$datos['PAC']['pass'] = "DEMO700101XXX";
$datos['PAC']['produccion'] = "NO";
$datos['modulo']="qr";                                  //NOMBRE DEL MODULO
$datos['archivo_png']="timbrados/qr_defactura.png";     //RUTA DONDE SE GUARDARA EL  QR.PNG
$datos['cadena']="hola hola";                           //CADENA A GUARDAR EN EL QR
$res = mf_ejecuta_modulo($datos);

echo "<pre>";
print_r($res);
echo "</pre>";
?>