<?php
error_reporting(E_ALL); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
date_default_timezone_set('America/Mexico_City');

include_once "../../sdk2.php";
$datosHTML['RESPUESTA_UTF8'] = "SI";
$datosHTML['PAC']['usuario'] = "DEMO700101XXX";
$datosHTML['PAC']['pass'] = "DEMO700101XXX";
$datosHTML['PAC']['produccion'] = "NO";
//MODULO MULTIFACTURAS : CONVIERTE UN XML CFDI A HTML
$datosHTML['modulo']="cfdi2html";                                                //NOMBRE MODULO
$datosHTML['rutaxml']='../../timbrados/cfdi_ejemplo_factura4_sin_impuestos.xml';    //RUTA DEL XML CFDI
$datosHTML['titulo']="factura ejemplo";                                          //TITULO DE FACTURA
$datosHTML['tipo']="FACTURA";                                                    //TIPO DE FACTURA VENTA,NOMINA,ARRENDAMIENTO, ETC
$datosHTML['path_logo']="../../timbrados/logo-grande.png";                          //RUTA DE LOGOTIPO DE FACTURA
$datosHTML['notas']="una nota mas y masa";                                       //NOTA IMPRESA EN FACTURA
$datosHTML['color_marco']="#013ADF";                                             //COLOR DEL MARCO DE LA FACTURA
$datosHTML['color_marco_texto']="#FFFFFF";                                       //COLOR DEL TEXTO DEL MARCO DE LA FACTURA
$datosHTML['color_texto']="#0174DF";                                             //COLOR DEL TEXTO EN GENERAL
$datosHTML['fuente_texto']="monospace";                                          //FUENTE DEL TEXTO EN GENERAL
   
  /* echo "<pre>";
print_r($datosHTML);
echo "</pre>";*/
$res = mf_ejecuta_modulo($datosHTML);                                  //FUNCION QUE CARGA EL MODULO cfdi2html
$HTML=$res['html'];                                     //HTML DEL XML           //RESPUESTA DE LA FUNCION CARGAR MODULO
   /*echo "<pre>";
print_r($res);
echo "</pre>";*/
//////////////////////////////////////////////////////////////////////////////
//CONVERTIR EL HTML DEL XML CFDI A PDF
$datosPDF['PAC']['usuario'] = "DEMO700101XXX";
$datosPDF['PAC']['pass'] = "DEMO700101XXX";
$datosPDF['PAC']['produccion'] = "NO";
$datosPDF['modulo']="html2pdf";                                                   //NOMBRE MODULO
$datosPDF['html']="$HTML";                                                        // HTML DE XML CFDI A CONVERTIR A PDF
$datosPDF['archivo_html']="";                                                     // OPCION SI SE TIENE UN ARCHIVO .HTML       
$datosPDF['archivo_pdf']="../../timbrados/cfdi_ejemplo_factura4_sin_impuestos.pdf";
//$datosPDF['archivo_pdf']="RUTA DONDE SE CREARA EL PDF/nombrearhivo.pdf";          //RUTA DONDE SE GUARDARA EL PDF

$res = mf_ejecuta_modulo($datosPDF);                                    //RESPUESTA DE LA FUNCION CARGAR MODULO  
//$res = ___html2pdf($datosPDF);                                    //RESPUESTA DE LA FUNCION CARGAR MODULO

echo "<pre>";
print_r($res);
echo "</pre>";
