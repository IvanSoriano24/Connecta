<?php
error_reporting(E_ERROR);                        
include_once "../../sdk2.php";
$datos['modulo']="cancelacion2018"; 
$datos['accion']="consultar";   
$datos["produccion"]="NO";                              
$datos["rfc"] ="LAN7008173R5";
$res = mf_ejecuta_modulo($datos);
print_r($res);