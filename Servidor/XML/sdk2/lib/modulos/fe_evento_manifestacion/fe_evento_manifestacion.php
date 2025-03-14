<?php
/**
 * La funcion siempre debe comenzar con tres guiones bajo y el nombre del mismo archivo PHP
 * SIN extension, y recibir una variable; esta variable puede tener el nombre que se desee.
 */
 //error_reporting(0);
//include_once "../../sdk2.php";
function ___fe_evento_manifestacion($datos)
{
    //version de php
    global $__mf_phpversion__;
	$__mf_phpversion__ = mf_phpversion();
    $version_php=($__mf_phpversion__ * 10);
    //include("../../../lib/nodos/fepanama/funcionesXX.php");
    //include("../../../lib/nodos/fepanama/funcionesXX.php");
    include("../../../lib/nodos/fepanama/funciones$version_php.php");   
    //LEER LOS DATOS DEL XML DE LA FACTURA QUE GENERARA EL EVENTO
    $ruta_factura_xml=$datos["ruta_xml"];
    $datos_xml=datos_factura_xml($ruta_factura_xml);
    $datos_evManifRecep_v100['dVerForm']=$datos["dVerForm"];
    $tmp_dIdFirma=$datos_xml['dIdFirma'];
    $tmp_dIdFirma=substr($tmp_dIdFirma, 0, 20);  // bcd
    $datos_evManifRecep_v100['dIdFirma']="ID".$tmp_dIdFirma;;
    $datos_evManifRecep_v100['iAmb']=$datos_xml['iAmb'];;
    $datos_evManifRecep_v100['dCufe']=$datos_xml['dCufe'];
    $datos_evManifRecep_v100['dRucRec']=$datos_xml['dRucRec'];
    //$datos_evManifRecep_v100['dManifRecep']='1001';  //Confirmación de los Datos de la Operación
    $datos_evManifRecep_v100['dManifRecep']=$datos["dManifRecep"];//'1002';  //Confirmación de la Operación 
    //$datos_evManifRecep_v100['dManifRecep']='1003';  //Confirmación de la Transacción
    //$datos_evManifRecep_v100['dManifRecep']='1004';  //Cancelación del Negocio
    //$datos_evManifRecep_v100['dManifRecep']='1005';  //Desconocimiento de la Operación
    $datos_evManifRecep_v100['dMotManif']=$datos["dMotManif"];//'Manifestacion onfirmación de los Datos de la Operación';
    //SE CREA EL  XML DEL EVENTO SIN FIRMAR
    $xml_evManifRecep_sin_firmar = evManifRecep_v100($datos_evManifRecep_v100);
    //FIRMAR EL XML DEL EVENTO DE ANULACIO CON LOS CERTIFICADOS DEL CLIENTE EMISOR
    $cer_pem = $datos["ruta_cer"];//'/var/www/vhosts/cfdi.red/httpdocs/multifacturas_docs/sdk2_desarrollo/certificados/F-8-244-462.cer.pem';
    $key_pem = $datos["ruta_key"];//'/var/www/vhosts/cfdi.red/httpdocs/multifacturas_docs/sdk2_desarrollo/certificados/F-8-244-462.cer.pem';
    $password = $datos["contrasena_cer"];// '28350674';
    //XML DEL EVENTO FIRMADO
    $xml_evManifRecep_firmado=firmar_eventos_xml_panama($xml_evManifRecep_sin_firmar, $cer_pem, $key_pem, $password);
    
    //datos del emisor de la factura
    $datosE['dTipContEm']=$datos_xml['dTipContEm'];;
    $datosE['dRucEm']=$datos_xml['dRucEm'];;
    $datosE['dDvEm']=$datos_xml['dDvEm'];;
    $datosE['dVerForm']=$datos["dVerForm"];
    $datosE['iAmb']=$datos["iAmb"];
    $datosE['dId']=$datos["dId"];
    $xml_evManifRecep_firmado = str_replace("<?xml version=\"1.0\"?>\n", '', $xml_evManifRecep_firmado); 
    $datosE['dEvReg']=$xml_evManifRecep_firmado;   //xml Schema XML 24: evAnulaFE_v1.00.xsd (Evento de Anulación de FE, pag 209
    $datosE['ruta_respuesta']=$datos["ruta_respuesta"];//'../../timbrados/retFeRecepEvento_manifestacion.xml';
    $datosE['evento']=$datos["evento"];//'evManifRecep';
    $datosE['usuario'] = $datos["usuario"];//'844084-1-504061';
    $datosE['pass'] = $datos["pass"];//'pruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebapruebaprueba';

    if($datos['iAmb']==2)
        $url="http://pruebas.facturacionpanama.com/pac/api_feRecepEvento.php";
    
    if($datos['iAmb']==1)
        $url="http://ws.siteck.mx/pac/api_feRecepEvento.php";

	$res=callAPImf('POST', $url, $datosE,false);
	//mf_agrega_global('respuesta_ws', $res);
    $array_res_dgi=json_decode($res,true);
    //echo "<pre>";print_r($array_res_dgi);echo "</pre>"; die();
    
    $xml=$array_res_dgi['mf_respuesta'];
    $ruta_xml=$datos['ruta_respuesta'];
    file_put_contents($ruta_xml,$xml);
    
    $array_res_dgi['mf_xml_retFeRecepEvento_manifestacion']=$ruta_xml;
     
    //die();

	return $array_res_dgi;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//ws PAC A DGI feRecepEventoDGI
/*
    Schema XML 25: evManifRecep_v1.00.xsd (Evento de Manifestación del Receptor)
    Pag. 210
*/
function evManifRecep_v100($datos)
{
    $dVerForm = $datos['dVerForm'];
    $dIdFirma = $datos['dIdFirma'];
    $iAmb= $datos['iAmb'];
    $dCufe= $datos['dCufe'];
    $dRucRec = $datos['dRucRec'];
    $dManifRecep= $datos['dManifRecep'];
    $dMotManif = $datos['dMotManif'];
    $xml_evManifRecep_v100='<rEvManifRecep xmlns="http://dgi-fep.mef.gob.pa">
            <dVerForm>1.00</dVerForm>
            <gInfProt>
                <dIdFirma>'.$dIdFirma.'</dIdFirma>
                <iAmb>'.$iAmb.'</iAmb>
                <dCufe>'.$dCufe.'</dCufe>
                <dRucRec>'.$dRucRec.'</dRucRec>
                <dManifRecep>'.$dManifRecep.'</dManifRecep>
                <dMotManif>'.$dMotManif.'</dMotManif>
            </gInfProt>
            </rEvManifRecep>';
    return $xml_evManifRecep_v100;
}

//leer firma de xml
function datos_factura_xml($ruta_xml)
{
    
    $xmlF = simplexml_load_file($ruta_xml);
    $iAmb = $xmlF->gDGen->iAmb;
    $chFE = $xmlF->dId;
    $dTipContEm = $xmlF->gDGen->gEmis->gRucEmi->dTipoRuc;
    $dRucEm = $xmlF->gDGen->gEmis->gRucEmi->dRuc;
    $dDvEm= $xmlF->gDGen->gEmis->gRucEmi->dDV;
    
    $dRucRec= $xmlF->gDGen->gDatRec->gRucRec->dRuc;
    //sacar el signature de otra forma
    $string_xml=file_get_contents($ruta_xml);
    list($tmp1,$parte1)=explode("<Signature xmlns",$string_xml);
    list($parte2,$tmp2)=explode("</Signature>",$parte1);
    $Signature = "<Signature xmlns$parte2</Signature>";;
    
    $datos['dIdFirma']=$chFE;
    $datos['iAmb']=$iAmb;
    $datos['dCufe']=$chFE;
    $datos['dTipContEm']=$dTipContEm;
    $datos['dRucEm']=$dRucEm;
    $datos['dDvEm']=$dDvEm;
    $datos['dRucRec']=$dRucRec;
    $datos['Signature']=$Signature;
    
    return $datos;
}