<?php
/**
 * La funcion siempre debe comenzar con tres guiones bajo y el nombre del mismo archivo PHP
 * SIN extension, y recibir una variable; esta variable puede tener el nombre que se desee.
 */

function ___fe_consultar_lote_facturas($datos)
{
    //version de php
    global $__mf_phpversion__;
	$__mf_phpversion__ = mf_phpversion();
    $version_php=($__mf_phpversion__ * 10);
    //include("../../../lib/nodos/fepanama/funcionesXX.php");
    include("../../../lib/nodos/fepanama/funciones$version_php.php");
    //por api rest	
    $url="http://pruebas.facturacionpanama.com/pac/api_feResultLoteFE.php";
	//$url="http://144.217.229.55/pac/api_feRecepFE_lote.php";
	//$url="http://56.cfdi.red/panama/pac/api_feRecepFE_lote.php";
    //$url="http://55.cfdi.red/panama/pac/api_feRecepFE_lote.php";
	$res=callAPImf('POST', $url, $datos,false);
	//mf_agrega_global('respuesta_ws', $res);
    $array_res_dgi=json_decode($res,true);
    //echo "<pre>";print_r($array_res_dgi);echo "</pre>"; die();
    
    $xml=$array_res_dgi['mf_respuesta'];
    $ruta_xml=$datos['ruta_respuesta'];
    
    file_put_contents($ruta_xml,$xml);
    
    $array_res_dgi['mf_xml_feResultLoteFE']=$ruta_xml;
     
    //die();

	return $array_res_dgi;
    
}