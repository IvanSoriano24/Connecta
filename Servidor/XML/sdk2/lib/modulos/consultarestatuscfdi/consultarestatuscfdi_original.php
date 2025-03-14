<?php
// <!-- phpDesigner :: Timestamp [08/11/2016 12:47:55 p. m.] -->
//  REVISA QUE LA FACTURA ESTE CANCELADA Y LA VUELVE A CANCELAR EN CASO DE FALLA
error_reporting(E_ALL);
//include "../../nusoap/nusoap.php";
global $__mf_constantes__;
// Se carga nusaop
if(!class_exists('nusoap_client'))
{
    mf_carga_libreria($__mf_constantes__['__MF_LIBS_DIR__']."nusoap/nusoap.php");
}

 
function ___consultarestatuscfdi($datos)
{
    if(!file_exists($datos['factura_xml']))
    {  
        $ruta=$datos['factura_xml'];
        $estatus["consulta"]["CodigoEstatus"]="el archivo xml en ruta $ruta NO EXISTE, VERIFICAR RUTA O PERMISOS DE CARPETAS DONDE ESTA ALMACENADO EL ARCHIVO XML";
        $estatus["consulta"]["Estado"]="el archivo xml en ruta $ruta NO EXISTE, VERIFICAR RUTA O PERMISOS DE CARPETAS DONDE ESTA ALMACENADO EL ARCHIVO XML";
	    return $estatus;
	}
    
    $xml_datos=leer_xml($datos['factura_xml']);
    $rfc_emisor=$xml_datos['rfc_emisor'];
    $rfc_receptor=$xml_datos['receptor_rfc'];
    $uuid=$xml_datos['uuid'];
    $monto=$xml_datos['monto'];
    $cadenaqr = "?re=$rfc_emisor&rr=$rfc_receptor&tt=$monto&id=$uuid";
    
    //$cadenaqr_formato="<![CDATA[$cadenaqr]]>";
    $rfc_emisor=trim($rfc_emisor);
    $rfc_receptor=trim($rfc_receptor);
    $importe=trim($monto);
    $uuid=trim($uuid);
    //
    $rfc_emisor=strtoupper($rfc_emisor);
    $rfc_receptor=strtoupper($rfc_receptor);
    $importe=strtoupper($importe);
    $uuid=strtoupper($uuid);
    
    //PRIMERO BUSCAR EN EL WS DEL SAT
    $url = "https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc";
    $soapclient = new nusoap_client($url,$esWSDL=true);
    $soapclient->soap_defencoding = 'UTF-8'; 
    $soapclient->decode_utf8 = false;
    $impo = $importe;
    $impo=sprintf("%.6f", $impo);
    $impo = str_pad($impo,17,"0",STR_PAD_LEFT);
    $factura = "?re=$rfc_emisor&rr=$rfc_receptor&tt=$impo&id=$uuid";
    $prm = array('expresionImpresa'=>$factura);
    $buscar=$soapclient->call('Consulta',$prm);
    /*
    echo "<pre>";
    print_r($buscar);
    echo "</pre>";
    */
    //SI NO ENCUENTRA RESPUESTA, BUSCAR EN EL WS DEL PAC
    if(!isset($buscar['ConsultaResult']['Estado']))
    {
        unset($buscar);
        $url = "https://consultaqrfacturaelectronicatest.sw.com.mx/ConsultaCFDIService.svc?singleWsdl";
        $soapclient = new nusoap_client($url,$esWSDL=true);
        $soapclient->soap_defencoding = 'UTF-8'; 
        $soapclient->decode_utf8 = false;
        $impo = $importe;
        $impo=sprintf("%.6f", $impo);
        $impo = str_pad($impo,17,"0",STR_PAD_LEFT);
        $factura = "?re=$rfc_emisor&rr=$rfc_receptor&tt=$impo&id=$uuid";
        $prm = array('expresionImpresa'=>$factura);
        $buscar=$soapclient->call('Consulta',$prm);
        
    }
    
    //AHORA SI LEER RESPUESTA 
    if(isset($buscar['ConsultaResult']['Estado']))
    {
		$estatus["consulta"]["CodigoEstatus"]=$buscar['ConsultaResult']['CodigoEstatus'];
        $estatus["consulta"]["EsCancelable"]=$buscar['ConsultaResult']['EsCancelable'];
        $estatus["consulta"]["Estado"]=$buscar['ConsultaResult']['Estado'];
        $estatus["consulta"]["EstatusCancelacion"]=$buscar['ConsultaResult']['EstatusCancelacion'];
        
        return $estatus;
        
	}
	else
	{  
	   $estatus["consulta"]["CodigoEstatus"]='Desconocido: talvez el servicio del sat esta sin servicio o saturado, intentalo mas tarde';
       $estatus["consulta"]["Estado"]='Desconocido: talvez el servicio del sat esta sin servicio o saturado, intentalo mas tarde';
       return $estatus;
	}

    

}
//////////////////////////////////////////////////////////////////////////////////////////////////////   
function leer_xml($ruta_xml)
{
    ### LEER EL XML ##############################
    $xml = simplexml_load_file($ruta_xml);
    $ns = $xml->getNamespaces(true);
    $xml->registerXPathNamespace('c', $ns['cfdi']);
    $xml->registerXPathNamespace('t', $ns['tfd']);
    foreach ($xml->xpath('//t:TimbreFiscalDigital') as $tfd)
    {
       $datos['uuid']=(string)$tfd['UUID'];
    }
    foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante)
    {
       $datos['monto']=$cfdiComprobante['Total'];
    }
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor)
    {
      $datos['receptor_rfc']=$Receptor['Rfc'];
    }

    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor)
    {
      $datos['rfc_emisor']=$Emisor['Rfc'];
    }
    foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante)
    {
       $datos['total']=$cfdiComprobante['total'];
       $datos['serie']=$cfdiComprobante['serie'];
       $datos['folio']=$cfdiComprobante['folio'];
       $datos['fecha_expedicion']=$cfdiComprobante['fecha'];
    }
    return $datos;
}
//////////////////////////////////////////////////////////////////////////////////////////////////////   

?>
