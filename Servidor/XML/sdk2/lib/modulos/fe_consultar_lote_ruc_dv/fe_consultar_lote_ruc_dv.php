<?php
/**
 * La funcion siempre debe comenzar con tres guiones bajo y el nombre del mismo archivo PHP
 * SIN extension, y recibir una variable; esta variable puede tener el nombre que se desee.
 */
 //error_reporting(0);
//include_once "../../sdk2.php";
function ___fe_consultar_lote_ruc_dv($datos)
{
    //version de php
    global $__mf_phpversion__;
	$__mf_phpversion__ = mf_phpversion();
    $version_php=($__mf_phpversion__ * 10);
    //include("../../lib/nodos/fepanama/funciones$version_php.php");
    include("../../../lib/nodos/fepanama/funcionesXX.php");
    //include("../../../lib/nodos/fepanama/funcionesXX.php");
    //include("../../../lib/nodos/fepanama/funciones$version_php.php");
    //por api rest
    if($datos['iAmb']==2)
        $url="http://pruebas.facturacionpanama.com/pac/api_feConsLoteRucDV.php";
    
    if($datos['iAmb']==1)
        $url="https://ws.siteck.mx/pac/api_feConsLoteRucDV.php";
    
    $res=callAPImf('POST', $url, $datos,false);
    $array_res_dgi=json_decode($res,true);
    //echo "<pre>";print_r($array_res_dgi);echo "</pre>"; 
    
    $xml=$array_res_dgi['mf_respuesta'];
    $ruta_xml=$datos['ruta_respuesta'];
    file_put_contents($ruta_xml,$xml);
    
    $array_res_dgi['mf_xml_retfeConsRucDV']=$ruta_xml;
     
    //die();

	return $array_res_dgi;
    
}