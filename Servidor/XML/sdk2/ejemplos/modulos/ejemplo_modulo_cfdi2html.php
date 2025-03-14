<?php
error_reporting(0); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
date_default_timezone_set('America/Mexico_City');

include_once "../../sdk2.php";

$datos['RESPUESTA_UTF8'] = "SI";
$datos['PAC']['usuario'] = "DEMO700101XXX";    
$datos['PAC']['pass'] = "DEMO700101XXX";
$datos['PAC']['produccion'] = "NO";
$datos['modulo']="cfdi2html";
//$datos['modo']="WEB";                                                      // MODO WEB SERVICE
$datos['rutaxml']='../../timbrados/cfdi_ejemplo_factura4.xml';   			 //RUTA DEL XML CFDI
$datos['titulo']="factura ejemplo";                                          //TITULO DE FACTURA
$datos['tipo']="FACTURA";                                                    //TIPO DE FACTURA VENTA,NOMINA,ARRENDAMIENTO, ETC
$datos['path_logo']="timbrados/LOGOAEDESADECV.jpg";                          //RUTA DE LOGOTIPO DE FACTURA
$datos['notas']="una nota mas y masa";                                       //NOTA IMPRESA EN FACTURA
$datos['color_marco']="#F7FE2E";                                             //COLOR DEL MARCO DE LA FACTURA
$datos['color_marco_texto']="#0174DF";                                       //COLOR DEL TEXTO DEL MARCO DE LA FACTURA
$datos['color_texto']="#0174DF";                                             //COLOR DEL TEXTO EN GENERAL
$datos['fuente_texto']="monospace";                                          //FUENTE DEL TEXTO EN GENERAL

$res = mf_ejecuta_modulo($datos);                                  //FUNCION QUE CARGA EL MODULO cfdi2html

/*echo "<pre>";
print_r($res);
echo "</pre>";*/


echo $res['html'];                                                           //RESPUESTA DE LA FUNCION CARGAR MODULO