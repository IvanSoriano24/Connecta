<?php
error_reporting(E_ERROR);                        
include_once "../../sdk2.php";
$datos['PAC']['usuario'] = "DEMO700101XXX";
$datos['PAC']['pass'] = "DEMO700101XXX";
$datos['modulo']="cancelacionretencion2022"; 
$datos['accion']="cancelar";                                                  
$datos["produccion"]="NO"; 
//$datos["xml"]="../../timbrados/cfdi_ejemplo_factura.xml";
$datos["uuid"]="e95c803b-47da-433d-aafd-0cf90f3df1d6";
$datos["rfc"] ="EKU9003173C9";
$datos["password"]="12345678a";
$datos["motivo"]="02";
//$datos["folioSustitucion"]="";
$datos["b64Cer"]="../../certificados/EKU9003173C9.cer";
$datos["b64Key"]="../../certificados/EKU9003173C9.key";
/*echo "<pre>";
print_r($datos);
echo "</pre>";*/
$res = mf_ejecuta_modulo($datos);
echo "<pre>";
print_r($res);
echo "</pre>";
/*NOTA: PARA REALIZAR LA CANCELACION SE REQUIERE EL UUID DE LA FACTURA A CANCELAR. 
OPCIONALMENTE PODRA ENVIAR EL XML Y DE AHI SE ESTRAERÁ EL UUID, POR LO CUAL DEBE DE ELGIR UNA DE LAS 2 OPCIONES. ($datos["uuid"] O $datos["xml"])
EN CASO DE QUE POR ERROR SE ENVIEN AMBOS PARAMETROS EL VALOR QUE SERA TOMADO EN CUENTA SERA EL QUE ESTÉ EN EL CAMPO UUID
Y SE IGNORARA LA FACTURA QUE SE ESPECIFIQUE EN EL CAMPO "$datos["xml"]"*/                                                   

