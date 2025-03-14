<?php
error_reporting(0);
// <!-- phpDesigner :: Timestamp -->17/06/2016 12:34:32 p. m.<!-- /Timestamp -->
function ___cfdi2html($datos)
{
	//PARA ARCHIVOS INI CON WEB SERVICE
    if($datos['modo']=='INI')
    {
        modo_ini($datos);
        return;
    }
	
	//PARA PHP CON WEB SERVICE
	if($datos['modo']=='WEB')
    {
        modo_webservice($datos);
        return;
    }
    
    
    include "num2letras.php";
    include "imprime.php";
    
    //LEER EL XML PARA GENERAR EL QR
    $xml_datos=nodos_xml($datos['rutaxml']);
	$rfc_emisor=$xml_datos['rfc_emisor'];
    $rfc_receptor=$xml_datos['receptor_rfc'];
    $uuid=$xml_datos['uuid'];
    $monto=$xml_datos['monto'];
    $monto=sprintf("%1.6f",$monto);
    $cadenaqr = "?re=$rfc_emisor&rr=$rfc_receptor&tt=$monto&id=$uuid";
    //ARCHIVO PNG QR
    $archivo_png=str_replace(".xml",".png",$datos['rutaxml']);
    
    if(!file_exists($archivo_png))
    {
        //include_once "../../sdk2.php";
        //include_once "../../lib/modulos/qr/qr.php";
        
        //MODULO MULTIFACTURAS QUE CREA QR PNG DE UN XML CFDI 
        $datosQR['modulo']="qr";
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
    $html=imprime_factura($xml,$titulo,$tipo,$path_logo,$notas,$color_marco,$color_marco_texto,$color_texto,$fuente_texto,$modo);
    
    return array('html'=>$html);   
}


function modo_ini($datos)
{
    include "num2letras.php";
    include "imprime.php";
    
    //LEER EL XML PARA GENERAR EL QR
    $xml_datos=nodos_xml($datos['rutaxml']);
    $rfc_emisor=$xml_datos['rfc_emisor'];
    $rfc_receptor=$xml_datos['receptor_rfc'];
    $uuid=$xml_datos['uuid'];
    $monto=$xml_datos['monto'];
    $monto=sprintf("%1.6f",$monto);
    $cadenaqr = "?re=$rfc_emisor&rr=$rfc_receptor&tt=$monto&id=$uuid";
    //ARCHIVO PNG QR
    $archivo_png=str_replace(".xml",".png",$datos['rutaxml']);
    //5558333457
    if(!file_exists($archivo_png))
    {
        //include_once "../../sdk2.php";
        //include_once "../../lib/modulos/qr/qr.php";
        
        //MODULO MULTIFACTURAS QUE CREA QR PNG DE UN XML CFDI 
        $datosQR['modulo']="qr";
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
    $modo=$datos['modo'];
    $html=imprime_factura($xml,$titulo,$tipo,$path_logo,$notas,$color_marco,$color_marco_texto,$color_texto,$fuente_texto,$modo);
    
    //$ruta_html=str_replace(".xml",".html",$xml);
    //file_put_contents($ruta_html, $html);
    $pdf['html']=base64_encode($html);
    $pdf['formato']="A4"; //defaul
    
    $res = callAPI("POST", "http://apipdf.multifacturas.com/api_pdf.php", $pdf);

    
    $ruta_pdf=str_replace(".xml",".pdf",$xml);
    $data = base64_decode($res);
    file_put_contents($ruta_pdf, $data);

    
    /*otros formatos
    $formato='B10';
    $formato='B9';
    $formato='B8';
    $formato='B7';
    $formato='B6';
    $formato='B5';
    $formato='A9';
    $formato='A8';
    $formato='A7';
    $formato='A6';
    $formato='A5';
    $formato='A4';
    $formato='Letter';
    $formato='Legal';*/
     
}


function modo_webservice($datos)
{
    include "num2letras.php";
    include "imprime.php";

  
    //LEER EL XML PARA GENERAR EL QR
    $xml_datos=nodos_xml($datos['rutaxml']);
	
    $rfc_emisor=$xml_datos['rfc_emisor'];
    $rfc_receptor=$xml_datos['receptor_rfc'];
    $uuid=$xml_datos['uuid'];
    $monto=$xml_datos['monto'];
    $monto=sprintf("%1.6f",$monto);
    $cadenaqr = "?re=$rfc_emisor&rr=$rfc_receptor&tt=$monto&id=$uuid";
    //ARCHIVO PNG QR
    $archivo_png=str_replace(".xml",".png",$datos['rutaxml']);
    //5558333457
    if(!file_exists($archivo_png))
    {
        //include_once "../../sdk2.php";
        //include_once "../../lib/modulos/qr/qr.php";
        
        //MODULO MULTIFACTURAS QUE CREA QR PNG DE UN XML CFDI 
        $datosQR['modulo']="qr";
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
    $modo=$datos['modo'];
    $html=imprime_factura($xml,$titulo,$tipo,$path_logo,$notas,$color_marco,$color_marco_texto,$color_texto,$fuente_texto,$modo);
    
    //$ruta_html=str_replace(".xml",".html",$xml);
    //file_put_contents($ruta_html, $html);
    $pdf['html']=base64_encode($html);
    $pdf['formato']="A4"; //defaul
    
    $res = callAPI("POST", "http://apipdf.multifacturas.com/api_pdf.php", $pdf);

    
    $ruta_pdf=str_replace(".xml",".pdf",$xml);
    $data = base64_decode($res);
    file_put_contents($ruta_pdf, $data);

    
    /*otros formatos
    $formato='B10';
    $formato='B9';
    $formato='B8';
    $formato='B7';
    $formato='B6';
    $formato='B5';
    $formato='A9';
    $formato='A8';
    $formato='A7';
    $formato='A6';
    $formato='A5';
    $formato='A4';
    $formato='Letter';
    $formato='Legal';*/
     
}

function nodos_xml($ruta_xml)
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
       $datos['monto']=(string)$cfdiComprobante['Total'];
    }
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor)
    {
      $datos['receptor_rfc']=(string)$Receptor['Rfc'];
    }

    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor)
    {
      $datos['rfc_emisor']=(string)$Emisor['Rfc'];
    }
    foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante)
    {
       $datos['total']=(string)$cfdiComprobante['Total'];
       $datos['serie']=(string)$cfdiComprobante['Serie'];
       $datos['folio']=(string)$cfdiComprobante['Folio'];
       $datos['fecha_expedicion']=(string)$cfdiComprobante['Fecha'];
    }
	
	$datos = json_decode(json_encode($datos), true);
    return $datos;
}

function callAPI($method, $url, $data){
    $curl = curl_init();
	$options = array(
		CURLOPT_RETURNTRANSFER => true,   // return web page
		CURLOPT_HEADER         => false,  // don't return headers
		CURLOPT_FOLLOWLOCATION => false,   // follow redirects
		CURLOPT_MAXREDIRS      => 1,     // stop after 10 redirects
		CURLOPT_ENCODING       => "",     // handle compressed
		CURLOPT_USERAGENT      => "api-mf", // name of client
		CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
		CURLOPT_CONNECTTIMEOUT => 10,    // time-out on connect
		CURLOPT_TIMEOUT        => 10,    // time-out on response 
	);
	curl_setopt_array($curl, $options);	
    switch ($method){
		case "POST":
			curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		break;
		case "PUT":
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			if ($data)
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
		break;
		default:
			if ($data)
				$url = sprintf("%s?%s", $url, http_build_query($data));
	}
	// OPTIONS:
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	  'APIKEY: 111111111111111111111',
	  'test-test: application/json',
	));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
	$result = curl_exec($curl);

   if(!$result){die("Connection Failure");}
   curl_close($curl);
   return $result;
}

?>