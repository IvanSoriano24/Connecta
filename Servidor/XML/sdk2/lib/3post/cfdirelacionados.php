<?php
/*
$datos['rutaxml']="PAGO-P526-ASESORIA-ESPECIALIZADA-EN-DESARROLLOS-ERP.xml";
$res=___cfdirelacionados($datos);
echo "<pre>";
print_r($res);
echo "</pre>";
*/

function ini_cfdirelacionados($datos)
{
    
}

function mf_cfdirelacionados($datos)
{
   global $__mf_constantes__;
   $ruta_cfdirelacionados=$__mf_constantes__['__MF_SDK_DIR__']."cfdi_relacionados/";
   mkdir($ruta_cfdirelacionados, 0777, true);
   $xml_datos=leer_xml($datos['cfdi']);
   $cfdiRelacionados=$xml_datos['cfdiRelacionados'];
   $TipoDeComprobante=$xml_datos['TipoDeComprobante'];
   $serie=$xml_datos['Serie'];
   $folio=$xml_datos['Folio'];
   $uuid=$xml_datos['uuid'];
   $nombre_archivo="$serie$folio"."_".$uuid;
   $ruta_archivo=$ruta_cfdirelacionados.$nombre_archivo;
   $contenido_archivo=$cfdiRelacionados."\n";
   file_put_contents($ruta_archivo,$contenido_archivo);
   /*
   echo "<pre>";
   print_r($xml_datos);
   echo "</pre>";
   */
}

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
        $tipoDeComprobante=(String)$cfdiComprobante['TipoDeComprobante'];
        $serie=(String)$cfdiComprobante['Serie'];
        $folio=(String)$cfdiComprobante['Folio'];
        $datos['Serie']=$serie;
        $datos['Folio']=$folio;
        $datos['TipoDeComprobante']=$tipoDeComprobante;
    }
    
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:CfdiRelacionados//cfdi:CfdiRelacionado') as $CfdiRelacionado)
    {
        $UUID_relacionado=(String)$CfdiRelacionado['UUID'];
        $CfdiRelacionados.=$UUID_relacionado.":";
       
    }
    
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//pago10:Pagos//pago10:Pago//pago10:DoctoRelacionado') as $DoctoRelacionado)
    {
        $IdDocumento=(String)$DoctoRelacionado['IdDocumento'];
        $CfdiRelacionados.=$IdDocumento.":";
    }
    $datos['cfdiRelacionados']=$CfdiRelacionados;
    
    return $datos;
}