<?php
error_reporting(0); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
include "lib/cfdi32_multifacturas.php";
date_default_timezone_set('America/Mexico_City');
include_once "lib/cfdi32_multifacturas.php";

$datos['RESPUESTA_UTF8'] = 'SI';
//$datos['cfdi'] = 'timbrados/modulo_sellar.xml';
$datos['cfdi'] = 'isotech/A929_timbrado.xml';
//$datos['rutaxml'] = 'sin_timbrar_ejemplo_factura_cheque.xml';
$datos['rutaxml'] = 'isotech/A929.xml';
$datos['conf']['cer'] = 'pruebas/XIA190128J61.cer';
$datos['conf']['key'] = 'pruebas/XIA190128J61.key';
$datos['conf']['pass'] = '12345678a';
$datos['PAC']['usuario'] = 'DEMO700101XXX';
$datos['PAC']['pass'] = 'DEMO700101XXX';
$datos['PAC']['produccion'] = 'NO';
$datos['modulo'] = 'sellarytimbrar';

$res = cargar_modulo_multifacturas($datos);

echo "<h1>Respuesta Generar XML y Timbrado</h1>";
foreach($res AS $variable=>$valor)
{
    $valor=htmlentities($valor);
    $valor=str_replace('&lt;br/&gt;','<br/>',$valor);
    echo "<b>[$variable]=</b>$valor<hr>";
}
