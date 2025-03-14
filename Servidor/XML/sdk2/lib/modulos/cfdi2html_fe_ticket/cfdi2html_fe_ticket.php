<?php
//error_reporting(E_ALL);
// <!-- phpDesigner :: Timestamp -->17/06/2016 12:34:32 p. m.<!-- /Timestamp -->
function ___cfdi2html_fe_ticket($datos)
{
    include "num2letras.php";
    include "imprime.php";
    
    //LEER EL XML PARA GENERAR EL QR
 
    $a=$datos['rutaxml'];
    $xmlF = simplexml_load_file($a);
    $cadenaqr = $xmlF->gNoFirm->dQRCode;
           //ARCHIVO PNG QR
    $archivo_png=str_replace(".xml",".png",$a);
    
    if(!file_exists($archivo_png))
    {
        //include_once "../../sdk2.php";
        //include_once "../../lib/modulos/qr/qr.php";
        
        //MODULO MULTIFACTURAS QUE CREA QR PNG DE UN XML CFDI 
        $datosQR['modulo']="qr_fe";
        $datosQR['PAC']['usuario'] = "DEMO700101XXX";
        $datosQR['PAC']['pass'] = "DEMO700101XXX";
        $datosQR['PAC']['produccion'] = "NO";
        $datosQR['cadena']=$cadenaqr;
        $datosQR['archivo_png']=$archivo_png;
        $res = mf_ejecuta_modulo($datosQR);
        //$res = ___qr($datosQR);
        
        $archivo_png = $res['archivo_png'];
        
    }
    //
    
    $xml=$datos['rutaxml'];
    $titulo=$datos['titulo'];
    $tipo=$datos['tipo'];
    $path_logo=$datos['path_logo'];
    $notas=$datos['notas'];
    $color_marco=$datos['color_marco'];
    $color_marco_texto=$datos['color_marco_texto'];
    $color_texto=$datos['color_texto'];
    $fuente_texto=$datos['fuente_texto'];
    $html=imprime_factura($xml,$titulo,$tipo,$path_logo,$notas,$color_marco,$color_marco_texto,$color_texto,$fuente_texto);
    return array('html'=>$html);   
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
       $datos['monto']=$cfdiComprobante['total'];
    }
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor)
    {
      $datos['receptor_rfc']=$Receptor['rfc'];
    }

    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor)
    {
      $datos['rfc_emisor']=$Emisor['rfc'];
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

?>