<?php
error_reporting(E_ERROR);                        
include_once "../../sdk2.php";
$datos['modulo']="cancelacion2018"; 
$datos['accion']="rechazar";                                                     
$datos["produccion"]="NO";                              
$datos["rfc"] ="XIA190128J61";
$datos["password"]="12345678a";
$datos["uuid"]="25d57a90-77cc-4fe2-acf6-67a3c2f2508d";
$datos["xml"]="../../timbrados/cfdi_ejemplo_factura.xml";
$datos["b64Cer"]="Certificados/XIA190128J61.cer";
$datos["b64Key"]="Certificados/XIA190128J61.key";
$res = mf_ejecuta_modulo($datos);
print_r($res);