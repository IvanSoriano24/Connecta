<?php
/**
 * La funcion siempre debe comenzar con tres guiones bajo y el nombre del mismo archivo PHP
 * SIN extension, y recibir una variable; esta variable puede tener el nombre que se desee.
 */
 //error_reporting(0);
//include_once "../../sdk2.php";
function ___fe_consultar_fe($datos)
{
    
    //version de php
    global $__mf_phpversion__;
	$__mf_phpversion__ = mf_phpversion();
    $version_php=($__mf_phpversion__ * 10);
   
    include("../../../lib/nodos/fepanama/funcionesXX.php");
    //include("../../../lib/nodos/fepanama/funciones$version_php.php");
    //por api rest
    //$url="http://pruebas.facturacionpanama.com/pac/api_feConsFE.php";
    
    
    if($datos['iAmb']==2)
        $url="http://pruebas.facturacionpanama.com/pac/api_feConsFE.php";
    
    if($datos['iAmb']==1)
        $url="https://ws.siteck.mx/pac/api_feConsFE.php";
    
    
    $res=callAPImf('POST', $url, $datos,false);
	//mf_agrega_global('respuesta_ws', $res);
	//echo "<pre>";print_r(json_decode($res,true));echo "</pre>"; die();
    //return json_decode($res,true);
    
    $array_res_dgi=json_decode($res,true);
    //echo "<pre>";print_r($array_res_dgi);echo "</pre>"; 
    
    $xml=$array_res_dgi['mf_respuesta'];
    $ruta_xml=$datos['ruta_respuesta'];
    file_put_contents($ruta_xml,$xml);
    
    $array_res_dgi['mf_xml_retfeConsFE']=$ruta_xml;
     
    //die();

	return $array_res_dgi;
    
}