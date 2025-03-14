<?php
/**
 * La funcion siempre debe comenzar con tres guiones bajo y el nombre del mismo archivo PHP
 * SIN extension, y recibir una variable; esta variable puede tener el nombre que se desee.
 */

function ___fe_evento_anulacion($datos)
{
	mf_carga_libreria_nodo('fepanama',  'fepanama', $datos);	
    //LEER LOS DATOS DEL XML DE LA FACTURA QUE GENERARA EL EVENTO
    $ruta_factura_xml=$datos["ruta_xml"];
	
$datos_xml=datos_factura_xml($ruta_factura_xml);
    $tmp_dIdFirma=$datos_xml['dIdFirma'];
    $tmp_dIdFirma=substr($tmp_dIdFirma, 0, 20);  //
    //generar xml del evento de anulacion
    $datos_evAnulaFE_v100['dVerForm']='1.00';
    $datos_evAnulaFE_v100['dIdFirma']="ID".$tmp_dIdFirma;
    $datos_evAnulaFE_v100['iAmb']=$datos_xml['iAmb'];
    $datos_evAnulaFE_v100['dCufe']=$datos_xml['dCufe'];
    $datos_evAnulaFE_v100['dTipContEm']=$datos_xml['dTipContEm'];
    $datos_evAnulaFE_v100['dRucEm']=$datos_xml['dRucEm'];
    $datos_evAnulaFE_v100['dDvEm']=$datos_xml['dDvEm'];
    $datos_evAnulaFE_v100['dMotivoAn']=$datos["dMotivoAn"];//'DATOS MAL CAPTURADOS';
    //GENERAR EL XML SIN FIRMAR DEL EVENTO DE ANULACION
$xml_evAnulaFE_sin_firmar=evAnulaFE_v100($datos_evAnulaFE_v100);
        
    
    //FIRMAR EL XML DEL EVENTO DE ANULACIO CON LOS CERTIFICADOS DEL CLIENTE EMISOR
    $cer_pem = $datos["cer"];//'/var/www/vhosts/cfdi.red/httpdocs/multifacturas_docs/sdk2_desarrollo/certificados/F-8-244-462.cer.pem';
    $key_pem = $datos["cer"];//'/var/www/vhosts/cfdi.red/httpdocs/multifacturas_docs/sdk2_desarrollo/certificados/F-8-244-462.cer.pem';
    $password = $datos["contrasena_cer"];// '28350674';
    //XML DEL EVENTO FIRMADO
$xml_evAnulaFE_firmado=firmar_eventos_xml_panama($xml_evAnulaFE_sin_firmar, $cer_pem, $key_pem, $password);
    
    //datos del emisor de la factura
    $datosE['dTipContEm']=$datos_xml['dTipContEm'];;
    $datosE['dRucEm']=$datos_xml['dRucEm'];;
    $datosE['dDvEm']=$datos_xml['dDvEm'];;
    $datosE['dVerForm']='1.00';
    $datosE['iAmb']=$datos['iAmb'];
    $datosE['dId']=$datos['dId'];
    $xml_evAnulaFE_firmado = str_replace("<?xml version=\"1.0\"?>\n", '', $xml_evAnulaFE_firmado); 
    $datosE['dEvReg']=$xml_evAnulaFE_firmado;   //xml Schema XML 24: evAnulaFE_v1.00.xsd (Evento de Anulación de FE, pag 209
    $datosE['ruta_respuesta']=$datos["ruta_respuesta"];//'../../timbrados/retFeRecepEvento_anulacion.xml';
    $datosE['evento']=$datos["evento"];;
    $datosE['usuario'] =$datos["usuario"];// '844084-1-504061';
    $datosE['pass'] = $datos["pass"];
//por api rest
    if($datos['iAmb']==2)
        $url="https://pruebas.facturacionpanama.com/pac/api_feRecepEvento.php";
    
    if($datos['iAmb']==1)
        $url="https://ws.siteck.mx/pac/api_feRecepEvento.php";

    $res=callAPImf('POST', $url, $datosE,false);

    $array_res_dgi=json_decode($res,true);
    //echo "<pre>";print_r($array_res_dgi);echo "</pre>"; die();
   
    $xml=$array_res_dgi['mf_respuesta'];
	
	
    $ruta_xml=$datos['ruta_respuesta'];
    file_put_contents($ruta_xml,$xml);
    
	$array_res_dgi['xml_evento_anulacion']=base64_encode($array_res_dgi['mf_respuesta']);
    $array_res_dgi['ruta_xml_evento_anulacion']=$ruta_xml;
	
     
    //die();
	$xmlAnulacion = simplexml_load_string($array_res_dgi['mf_respuesta']);
	
	$array_res_dgi['dCodRes']=(string) $xmlAnulacion->gResProc->dCodRes;
	$array_res_dgi['dMsgRes']=(string) $xmlAnulacion->gResProc->dMsgRes;
	$array_res_dgi['dFecProc']=(string) $xmlAnulacion->dFecProc;
	$array_res_dgi['iAmb']=(string) $xmlAnulacion->iAmb;
	unset($array_res_dgi['mf_respuesta']);
 
	return $array_res_dgi;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//ws PAC A DGI feRecepEventoDGI
// Schema XML 24: evAnulaFE_v1.00.xsd (Evento de Anulación de FE)
// pag 209
function evAnulaFE_v100($datos)
{
    $dVerForm ='1.00';// $datos['dVerForm'];
    $dIdFirma = $datos['dIdFirma'];
    $iAmb= $datos['iAmb'];
    $dCufe= $datos['dCufe'];
    $dTipContEm = $datos['dTipContEm'];
    $dRucEm= $datos['dRucEm'];
    $dDvEm = $datos['dDvEm'];
    $dMotivoAn= $datos['dMotivoAn'];
    $Signature= $datos['Signature'];
    
    if(strlen($dRucEm)<18)
    {
        $dRucEm="$dRucEm";
    }
    
    $xml='<rEvAnulaFe xmlns="http://dgi-fep.mef.gob.pa">
            <dVerForm>1.00</dVerForm>
            <gInfProt>
                <dIdFirma>'.$dIdFirma.'</dIdFirma>
                <iAmb>'.$iAmb.'</iAmb>
                <dCufe>'.$dCufe.'</dCufe>
                <gRucEm>
                    <dTipContEm>'.$dTipContEm.'</dTipContEm>
                    <dRucEm>'.$dRucEm.'</dRucEm>
                    <dDvEm>'.$dDvEm.'</dDvEm>
                </gRucEm>
                <dMotivoAn>'.$dMotivoAn.'</dMotivoAn>
            </gInfProt>
            </rEvAnulaFe>';
    return $xml;
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
    //sacar el signature de otra forma
    $string_xml=file_get_contents($ruta_xml);
    list($tmp1,$parte1)=explode("<Signature xmlns",$string_xml);
    list($parte2,$tmp2)=explode("</Signature>",$parte1);
    $Signature = "<Signature xmlns$parte2</Signature>";;
    $datos=array();
    $datos['dIdFirma']=$chFE;
    $datos['iAmb']=$iAmb;
    $datos['dCufe']=$chFE;
    $datos['dTipContEm']=$dTipContEm;
    $datos['dRucEm']=$dRucEm;
    $datos['dDvEm']=$dDvEm;
    $datos['Signature']=$Signature;
    
    return $datos;
}