<?php
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
date_default_timezone_set('America/Panama');
require_once '../../sdk2.php';
//$datos['dVerForm']='1.00';
$datos['iAmb']='1';
$datos['dId']='2';
$datos['evento']='evAnulaFE';
$datos["ruta_xml"]="../../timbrados/fe_ejemplo_factura.xml";
$datos["dMotivoAn"]="primeras pruebas de facturacion en producccion";
$datos['usuario'] = '155704603-2-2021';
$datos['pass'] = 'hs7omTfb44qyEqfY8UTUxE6SgimLugRHvakBXX0clnoijJplDjA7Wf9qk8wd8OIsETsLHo1KwKW9313DgkXo8zuUeHX4FUNF1T9C';
$datos['ruta_respuesta']='../../../timbrados/retFeRecepEvento_anulacion.xml';
$datos["cer"]='/var/www/vhosts/cfdi.red/httpdocs/multifacturas_docs/sdk2_desarrollo/certificados/certificado_kit_siteck.cer';
$datos["contrasena_cer"]= '51458e4a9ff8f6212a1c9a45c0c2203e9d1b4437103';
$datos['modulo']="fe_evento_anulacion";




$res = mf_ejecuta_modulo($datos);

/*
$datospost['json']=json_encode($datos);

$datospost['modo']='JSON';

$url="https://ws.siteck.mx/api/anulacion.php";

$res=callAPImf('POST', $url, $datospost);
 */

echo "<pre>";print_r($res);echo "</pre>";


