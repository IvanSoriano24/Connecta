<?php
/**
 * La funcion siempre debe comenzar con tres guiones bajo y el nombre del mismo archivo PHP
 * SIN extension, y recibir una variable; esta variable puede tener el nombre que se desee.
 */

global $__mf_constantes__;
// Se carga nusaop
if(!class_exists('nusoap_client'))
{
    mf_carga_libreria($__mf_constantes__['__MF_LIBS_DIR__']."nusoap/nusoap.php");
}

 
 
function ___cancelacionretencion2022($datos)
{
    $user="";$url="";$passwordPAC="";$uuid="";
    $accion=strtoupper($datos['accion']);
    $certificado = base64_encode(file_get_contents($datos["b64Cer"]));
    $key = base64_encode(file_get_contents($datos["b64Key"]));
    
    
    if($accion !='CONSULTAR')
    {
        if($datos["uuid"] == "")
        {
            $RutaXML = $datos["xml"];    
            $xml = simplexml_load_file($RutaXML); 
            $ns = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('c', $ns['cfdi']);
            $xml->registerXPathNamespace('t', $ns['tfd']);
            foreach ($xml->xpath('//t:TimbreFiscalDigital') as $tfd)
            {  
            $uuid=(string)$tfd['UUID'];   
            } 
        }
        else
        {
            $uuid = $datos["uuid"];
        }
    }
    
    $pac=rand(1, 10);
    //$url_webservice="http://idventa.ddns.net/pac/timbrar_retenciones.php?wsdl";
    $url_webservice="http://pac1.multifacturas.com/pac/timbrar_retenciones.php?wsdl";
    $SOAP_CLIENT=$url_webservice;
    $soapclient = new nusoap_client($SOAP_CLIENT,$esWSDL = true);
    
    
  switch ($accion) {
      //CASO CANCELAR
    case 'CANCELAR':
    
    
        $usuario= $datos['PAC']['usuario'];
        $clave= $datos['PAC']['pass'];
        $produccion=$datos["produccion"];
        $pass_cer=$datos["password"];
        $motivo=$datos["motivo"];
        $folioSustitucion=$datos["folioSustitucion"];
        $rfc_emisor=$datos["rfc"];
        $params = array(
            'usuario_pac' => $usuario,
            'clave_pac' => $clave,
            'rfc_emisor' => $rfc_emisor,
            'cer' => $certificado,
            'key' => $key,
            'pass_key' => $pass_cer,
            'motivo' => $motivo,
            'foliosustitucion' => $folioSustitucion,
            'uuid' => $uuid,
            'produccion' => $produccion
        );

        $respuesta_webservice = $soapclient->call('cancelar_retencion', $params);
        /*echo "<pre>";
        print_r($respuesta_webservice);
        echo "</pre>";*/
        
        
    break;
    /*
    case 'ACEPTAR':
    
        $datos2['PAC']['usuario'] = $datos['PAC']['usuario'];
        $datos2['PAC']['pass'] = $datos['PAC']['pass'];
        $datos2['accion']=$datos['accion'];                                                  
        $datos2["produccion"]=$datos["produccion"];                              
        $datos2["rfc"] =$datos["rfc"];
        $datos2["password"]=$datos["password"];
        $datos2["uuid"]=$uuid;
        $datos2["b64Cer"]=$certificado;
        $datos2["b64Key"]=$key; 
        $parametros_funcion = array('datos' => $datos2);
        $respuesta_webservice = $soapclient->call('aceptarCancelarCfdi', $parametros_funcion);
             break;
     case 'RECHAZAR':
    
        $datos2['PAC']['usuario'] = $datos['PAC']['usuario'];
        $datos2['PAC']['pass'] = $datos['PAC']['pass'];
        $datos2['accion']=$datos['accion'];                                                    
        $datos2["produccion"]=$datos["produccion"];                              
        $datos2["rfc"] =$datos["rfc"];
        $datos2["password"]=$datos["password"];
        $datos2["uuid"]=$uuid;
        $datos2["b64Cer"]=$certificado;
        $datos2["b64Key"]=$key;   
        $parametros_funcion = array('datos' => $datos2);
        $respuesta_webservice = $soapclient->call('aceptarCancelarCfdi', $parametros_funcion);
     break;
     case 'CONSULTAR':
        $datos2['PAC']['usuario'] =$datos['PAC']['usuario'];
        $datos2['PAC']['pass'] = $datos['PAC']['pass']; 
        $datos2['accion']=$datos['accion'];   
        $datos2["produccion"]=$datos["produccion"];                              
        $datos2["rfc"] =$datos["rfc"];
        $parametros_funcion = array('datos' => $datos2);
        $respuesta_webservice = $soapclient->call('consultarCancelarCfdi', $parametros_funcion);
     break;
     case 'CFDIRELACIONADOSXRECEPTOR':
        $datos2['PAC']['usuario'] = $datos['PAC']['usuario'];
        $datos2['PAC']['pass'] = $datos['PAC']['pass'];
        $datos2['accion']=$datos['accion'];                                                    
        $datos2["produccion"]=$datos["produccion"];                              
        $datos2["rfc_receptor"] =$datos["rfc_receptor"];
        $datos2["password"]=$datos["password"];
        $datos2["uuid"]=$uuid;
        $datos2["b64Cer_receptor"]=$certificado;
        $datos2["b64Key_receptor"]=$key;   
        $parametros_funcion = array('datos' => $datos2);
        $respuesta_webservice = $soapclient->call('consultarCfdiRelacionado', $parametros_funcion);
     break;
    */ 
    
    
  }
      
  return $respuesta_webservice;
}