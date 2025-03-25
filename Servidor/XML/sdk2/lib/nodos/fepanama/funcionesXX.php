<?php
use Firebase\JWT\JWT;
ini_set("default_socket_timeout", "300");
//
class SimpleXMLExtended extends SimpleXMLElement {
  public function addCDATA($cData) {
    $node = dom_import_simplexml($this);
    $no = $node->ownerDocument;
    $node->appendChild($no->createCDATASection($cData));
  }
}
//


function crea_nodo_simple($arreglo, $clave)
{
    $xml = '';
    if(is_null($arreglo))
    {
        return $xml;
    }
    if(array_key_exists($clave, $arreglo))
    {
        $xml .= "<$clave>" . $arreglo[$clave] . "</$clave>";
    }
    return $xml;
}

function crea_nodo_rama($arreglo, $rama, $hojas=array())
{
    $xml = '';
    if(array_key_exists($rama, $arreglo))
    {
        $xml .= "<$rama>";
        foreach($hojas as $idx => $hoja)
        {
            $xml .= crea_nodo_simple($arreglo[$rama], $hoja);
        }
        $xml .= "</$rama>";
    }
    return $xml;
}

function crea_nodos_numerico($arreglo, $tag)
{
    $xml = '';
    if(array_key_exists($tag, $arreglo))
    {
        foreach($arreglo[$tag] as $idx => $val)
        {
            //$xml .= crea_nodo_simple($nodo, $tag);
            $xml .= "<$tag>$val</$tag>";
        }
    }
    return $xml;
}


/**
 * @param $xml_string string
 * @param $cer_pem string
 * @param $key_pem string
 * @param $password string
 * @throws Exception
 */


/**
 * @param $xml_string string
 * @param $cer_pem string
 * @param $key_pem string
 * @param $password string
 * @throws Exception
 */
function firmar_xml_panama($xml_string, $cer_pem, $key_pem, $password,$codigoQR)
{
	if($cer_pem=='')
		$cer_pem=$key_pem;
	if($key_pem=='')
		$key_pem=$cer_pem;
  // echo "<br>xml_string:".$xml_string;
  //echo $xml_string=file_get_contents($xml_string);
    // Constantes a usar
    
    $xml_string = utf8_encode($xml_string);
    
    global $__mf_constantes__;

    // Respuesta
    $respuesta_funcion = array('abortar' => false);

    // Se carga la libreria xmlsec
    mf_carga_libreria($__mf_constantes__['__MF_LIBS_DIR__'] . 'xmlsec/vendor/autoload.php');

    try {
        // Se carga el xml
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML("<?xml version=\"1.0\"  encoding=\"UTF-8\"?>$xml_string");

        // Objeto XMLSecurityDSig (prefijo)
        $objDSig = new RobRichards\XMLSecLibs\XMLSecurityDSig('');

        // Se canoniza el xml (c14n)
        $objDSig->setCanonicalMethod(RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N);
        
        // Transformaciones para panama
        $transformaciones_xml = array(
            'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
            'http://www.w3.org/2001/10/xml-exc-c14n#'
        );

        // Tipo de sello SHA-256
        $objDSig->addReference(
            $doc,
            RobRichards\XMLSecLibs\XMLSecurityDSig::SHA256,
            $transformaciones_xml,
            array('force_uri'=>true)
        );

        // Create a new (private) Security key
        $objKey = new RobRichards\XMLSecLibs\XMLSecurityKey(RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        //If key has a passphrase, set it using
        //$objKey->passphrase = '<passphrase>';
        $objKey->passphrase = $password;

        // Load the private key
        $objKey->loadKey($key_pem, TRUE);

        // Sign the XML file
        $objDSig->sign($objKey);

        // Add the associated public key to the signature
        $objDSig->add509Cert(file_get_contents($cer_pem), true, false, array('issuerSerial' => false, 'subjectName' => true));

        // Append the signature to the XML
        $objDSig->appendSignature($doc->documentElement);

        // Se regresa el xml firmado
        $xml_firmado=$doc->saveXML();
        //file_put_contents("/var/www/vhosts/cfdi.red/httpdocs/multifacturas_docs/sdk2_desarrollo/tmp/xml_firmado_ultimo.xml",$xml_firmado);
//echo $xml_firmado;die();
        //generar el nodo "gNoFirm" y su hijo "dQRCode" de la firma
        mf_carga_libreria($__mf_constantes__['__MF_LIBS_DIR__'] . 'jwt/vendor/autoload.php');
        $xmlF = simplexml_load_string($xml_firmado);
        $iAmb = $xmlF->gDGen->iAmb;
        $chFE = $xmlF->dId;
        $DigestValue = $xmlF->Signature->SignedInfo->Reference->DigestValue;
        
        //url de verificacion qr produccion
        $urlqr="https://dgi-fep.mef.gob.pa/Consultas/FacturasPorQR?";  //produccion
        if($iAmb==2)
        {
			
            //url de verificacion qr en pruebas
            $urlqr="https://dgi-fep-test.mef.gob.pa:40001/Consultas/FacturasPorQR?";  //pruebas
        }

//$codigoQR='CEA4A5457603B609E05349D1950A8972CEA4A5457604B609E05349D1950A8972CEA4A5457605B609E05349D1950A8972CEA4A5457606B609E05349D1950A8972';
        $key = $codigoQR;
        $payload = array(
            "chFE" => (string) $chFE,
            "iAmb" => (string) $iAmb,
            "digestValue" => (string) $DigestValue
        );


        $jwt = JWT::encode($payload, $key,'HS256');

//echo "<pre>";print_r($payload);echo "</pre>";die();
        //$decoded = JWT::decode($jwt, $key, array('HS256'));
        //print_r($decoded);
        $firma_qr=$urlqr."chFE=".$chFE."&iAmb=".$iAmb."&digestValue=".$DigestValue."&jwt=".$jwt; 
		//$firma_qr="<![CDATA[".$firma_qr."]]>";
		//$firma_qr="<![CDATA[$firma_qr]]>";
		//$firma_qr=trim($firma_qr);
//echo $firma_qr;die();        
        //AGRGAR EL NODO DE LA FIRMA QR AL XML FIRMADO TIPO CDATA
        $xml_tmp = new SimpleXMLExtended($xml_firmado);
        $gNoFirm = $xml_tmp->addChild('gNoFirm');
        // Agregando CDATA:
        $dQRCode = $gNoFirm->addChild('dQRCode');
        $dQRCode->addCDATA("$firma_qr");
        
        $xml_firmado = $xml_tmp->asXML();
        $xml_firmado=trim($xml_firmado);
        $xml_firmado = preg_replace("/[\r\n|\n|\r]+/", PHP_EOL, $xml_firmado);

        $respuesta_funcion['firma_qr']= $firma_qr;
        $respuesta_funcion['xml_firmado'] = $xml_firmado;

        
        return $respuesta_funcion;
    }
    catch (Exception $ex)
    {
		//echo "ssssssssssssss";
		//print_r($ex);
/*		
        $respuesta_funcion['abortar'] = true;
        $respuesta_funcion['respuesta']['codigo_mf_numero'] = 7;
        $respuesta_funcion['respuesta']['codigo_mf_texto'] = 'ERROR AL GENERAR FIRMA';
*/
        return $respuesta_funcion;
    }
}


///////////////////////////////////////////////

/**
 * certificado_pem()
 *
 * @param mixed $datos
 * @return
 */
if(!function_exists('mf_certificado_pem'))
{
    function mf_certificado_pem($datos)
    {
//aki generar certificados

    }
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function cufe($datos)
{
    $cufe = '';

    // [<ID del Campo>-<Longitud>]
    // Tipo de Documento [B06-2]
    $cufe .= $datos['rFE']['gDGen']['iDoc'];

    // Tipo de Contribuyente [B3011-1]
    $cufe .= $datos['rFE']['gDGen']['gEmis']['gRucEmi']['dTipoRuc'];

    // RUC del Emisor [B3012-20]
    $cufe .= formato_ruc_cufe($datos['rFE']['gDGen']['gEmis']['gRucEmi']['dRuc']);

    // DV de RUC [B3013-3]
    $cufe .= '-' . $datos['rFE']['gDGen']['gEmis']['gRucEmi']['dDV'];

    // Codigo de la Sucursal [B303-3]
    $cufe .= $datos['rFE']['gDGen']['gEmis']['dSucEm'];

    // Fecha de Emision [B10-8]
    $cufe .= str_replace('-', '', substr($datos['rFE']['gDGen']['dFechaEm'], 0, 10));

    // Numero de Factura [B07-9]
    $cufe .= $datos['rFE']['gDGen']['dNroDF'];

    // Punto de Facturacion [B08-3]
    $cufe .= $datos['rFE']['gDGen']['dPtoFacDF'];

    // Tipo de Emision [B03-2]
    $cufe .= $datos['rFE']['gDGen']['iTpEmis'];

    // Ambiente de Destino [B02-1]
    $cufe .= $datos['rFE']['gDGen']['iAmb'];

    // Secuencia de Seguridad [B09-9]
    $cufe .= $datos['rFE']['gDGen']['dSeg'];

    // Digito Verificados
    $cufe .= dv_cufe($cufe);

    // Se regresa el CUFE
    return $cufe;
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function calcula_dv_cufe($cufe)
{
    // Se cambian las letras por numeros en el cufe
    $cufe =  formato_ruc_cufe($cufe);
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function formato_ruc_cufe($ruc,$ELIMINAR_LETRAS=null)
{
    // Caracteres a omitir en la copia
    $caracteres_a_evitar = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

    // Ruc numerico
    $ruc_numerico = '';

    // Se cambian los caracteres por numeros
    for($i = 0; $i < strlen($ruc); $i++)
    {
        if(in_array($ruc[$i], $caracteres_a_evitar))
        {
            $ruc_numerico .= $ruc[$i];
        }
        else
        {
            $digitos_ascii = str_split(strval(ord($ruc[$i])));
            $posicion = count($digitos_ascii) - 1;
            $digito = $digitos_ascii[$posicion];
            $ruc_numerico .= $digito;
        }
    }

    // Se agregan los ceros
    //$ruc_numerico = str_pad($ruc, 20, '0', STR_PAD_LEFT); //antes
    
    if($ELIMINAR_LETRAS=='si'){
        $ruc_numerico = str_pad($ruc_numerico, 20, '0', STR_PAD_LEFT); //carlos
    }else{
        $ruc_numerico = str_pad($ruc, 20, '0', STR_PAD_LEFT); //antes
    }
        
    
    // Se regresa el ruc numerico
    return $ruc_numerico;
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function dv_cufe($cufe)
{
    // Se invierte el CUFE
    $cufe_inv = str_replace('-', '', strrev($cufe));
    // Se eliminan las letras
    $cufe_inv = formato_ruc_cufe($cufe,"si");
    // Suma de los digitos
    $suma_digitos = 0;
    for($i = 0; $i < strlen($cufe_inv); $i++)
    {
        // Multiplicador (2, 1, 2, 1, 2, 1.....)
         $multiplicador = (($i % 2) == 0) ? 2 : 1;

        // Se multiplica el digito por el multiplicador
        $resultado = $multiplicador * $cufe_inv[$i];

        // Se obtienen los digitos del resultado
        $digitos = str_split($resultado);

        // Se suman los digitos
        $suma_digitos += array_sum($digitos);
    }

    // Se calcula el modulo
    $modulo = $suma_digitos % 10;

    // Se calcula el digito verificados
    return ($modulo > 0) ? 10 - $modulo : 0;
}

////

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   function mf_valida_xsd_fp($xmlfile, $xsdfile, $omitir='NO')
    {
        global $__mf_debug_trace__;

        $respuesta_funcion = array(
            'abortar' => false,
            'respuesta' => array()
        );

        // Se omite la la validacion
        if($omitir=='SI')
        {
            return $respuesta_funcion;
        }

        // Se valida que exista el XML y XSD
//        if(file_exists($xmlfile) && file_exists($xsdfile))
        if($xmlfile!='' && file_exists($xsdfile))
        {

            // Se valida que exista la clase DOMDocumetn
            if(!class_exists('DOMDocument'))
            {
                $respuesta_funcion['abortar'] = true;
                $respuesta_funcion['respuesta']['codigo_mf_numero'] = 7;
                $respuesta_funcion['respuesta']['codigo_mf_texto'] = 'ERROR NO SE ENCONTRO DOMDOCUMENT';
            }
            else
            {

                // Se lee el XML
                //$xml = utf8_encode(file_get_contents($xmlfile));
				//$xml=$xmlfile;
				$xml=mf_recupera_global('xml_firmado');
				
//echo htmlentities($xml);

                // Se carga el XML
                $dom = new DOMDocument();

                $dom->loadXML($xml);

                // Se eliminan los errores anteriores
                libxml_clear_errors();

                // Se activa el manejo de errores
                libxml_use_internal_errors(true);

                // Se valida el XML
                if(file_exists($xsdfile))
                {
                    $dom->schemaValidate($xsdfile);
                }

                // Se recuperan los errores
                $errors = libxml_get_errors();

                // Se agregan los errores al debug
                $__mf_debug_trace__['xsd'] = $errors;

                // Se recorren los errores
                $error_txt = '';
                foreach($errors as $idx => $error)
                {
                    /*
                     * 1 => Warning
                     * 2 => Error
                     * 3 => Error Fatal
                     */
                    if($error->level > 1)
                    {
                        $tmp = $error->message;
                        $aux = explode('{', $tmp, 2);
                        $error_txt .= $aux[count($aux) - 1] /*. "<br/>"*/;
                    }
                }
 
                // Se da formato a los errores
                if($error_txt != '')
                {
                    $error_txt=str_replace('is required but missing',' es obligatorio',$error_txt);
                    $error_txt=str_replace('is not a valid value of the local atomic type',' no es un valor valido tipo',$error_txt);
                    $error_txt=str_replace('The value',' El valor',$error_txt);
                    $error_txt=str_replace('has a length of',' cantidad de caracteres',$error_txt);
                    $error_txt=str_replace('this underruns the allowed minimum length of',' cantidad minima de caracteres',$error_txt);
                    $error_txt=str_replace('Missing child element(s)',' Se esperaban elementos capturados ',$error_txt);
                    $error_txt=str_replace('is not accepted by the pattern',' unicamente se aceptan los caracteres',$error_txt);
                    $error_txt=str_replace('is not a valid value of the atomic type',' no es un valor valido ',$error_txt);
                    $error_txt=str_replace('this exceeds the allowed maximum length of',' excede la cantidad maxima que es ',$error_txt);
                    $error_txt=str_replace('is not an element of the set','no es un elemento valido de ',$error_txt);
                    $aux=explode(". Expected is ( {http:/",$error_txt);
                    $error_txt=$aux[0];
                    $aux=explode("'{http",$error_txt);
                    $error_txt=$aux[0];
                    $error_txt=str_replace('The attribute',' El campo',$error_txt);
                    $error_txt=str_replace('attribute',' campo',$error_txt);
                    $error_txt= str_replace("[facet 'minLength']  El",'',$error_txt);
                    $error_txt= str_replace("[facet 'enumeration']  El",'',$error_txt);
                    $error_txt= str_replace("[facet 'pattern']  El",'',$error_txt);
                    $error_txt= str_replace("'ND'","'CAMPO VACIO'",$error_txt);
                    $error_txt="ERROR EN DATOS CAPTURADOS: $error_txt";

                    $respuesta_funcion['abortar'] = true;
                    $respuesta_funcion['respuesta']['codigo_mf_numero'] = 7;
                    $respuesta_funcion['respuesta']['codigo_mf_texto'] = $error_txt;
                }
            }
        }
        return $respuesta_funcion;
    }
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//TIMBRADO
function feRecepFE_v100($xml_sin_firmar,$datos)
{
//echo $xml_sin_firmar;	die();

	$resp_firma = firmar_xml_panama($xml_sin_firmar, $datos['conf']['cer'], $datos['conf']['key'], $datos['conf']['pass'],$datos['PAC']['QR']);

        $xml_firmado = $resp_firma['xml_firmado'];
        // Se respalda el XML firmado
        mf_agrega_global('xml_firmado', $xml_firmado);
//aki error documento no firmado
//echo $xml_firmado;	die();
        // Se calcula el CUFE
        $cufe = cufe($datos);
        // Se agrega el CUFE en las variables globales
        mf_agrega_global('cufe', $cufe);
	$xml_firmado = $resp_firma['xml_firmado'];
	mf_agrega_global('xml_firmado', $xml_firmado);
	
//por api rest	
	mf_agrega_global('xml_firmado', $xml_firmado);
    $dVerForm =$datos['rFE']['dVerForm']; 
    $iAmb=$datos['rFE']['gDGen']['iAmb'];
	if($iAmb==1)
	{
		//produccion
		$url="https://ws.siteck.mx/pac//api_feRecepFE.php";		
	}
	else
	{
		//pruebas
		$url="https://pruebas.siteck.mx/pac/api_feRecepFE.php";		
	}
	
	if($datos['url']!='')
	{
		$url=$datos['url'];
	}

	$dId=$datos['dId'];
	$dId=intval($dId);
	if($dId==0)
		$dId=rand(1111111111,9999999999);
	$data2['dId']=$dId;
//echo $xml_firmado;
	$data2['xFe']=base64_encode($xml_firmado);
	$data2['ruc']=$datos['PAC']['usuario'];
	$data2['clave']=$datos['PAC']['pass'];
	
//echo "<pre>";print_r($data2);echo "</pre>"; 


	$iTpEmis=$datos['rFE']['gDGen']['iTpEmis'];

	if($iTpEmis=='02')
	{
		//modo, pre-contingencia
		$xmldata = simplexml_load_string($xml_string);
		$CUFE=$xmldata->dId;
        $iAmb = (string)$xmlF->gDGen->iAmb;
		$RUC=(string)$xmlF->gDGen->gEmis->gRucEmi->dRuc;
		$QR=(string)$xmlF->gNoFirm->dQRCode;

		$res="﻿{\"dVerForm\":\"1.00\",\"iAmb\":\"$iAmb\",\"dVerApl\":null,\"xProtFe\":\"PENDIENTE DE ENVIAR AL PAC\",\"codigo\":\"0260\",\"codigo_txt\":\"0260\",\"codigo_advertencias\":\"\",\"saldo\":999999,\"dProtAut\":\"$dProtAut\",\"dFecProc\":\"$dFecProc\",\"QR\":\"$QR\",\"dId\":\"$dId\",\"CUFE\":\"$CUFE\"}";
		
	}
	else
	{
		
		$res=callAPImf('POST', $url, $data2,false);
		
	}


	mf_agrega_global('respuesta_ws', $res);


///////////  ALMACENA XML Respuesta
	$ruta_xml=$datos['xml'];
	$ruta_xml_sinfirmar=str_replace('.xml','_sinfirmar.xml',$ruta_xml);
	$ruta_xml_sinfirmar=str_replace('.XML','_sinfirmar.xml',$ruta_xml_sinfirmar);
	$ruta_xml_au=str_replace('.xml','_au.xml',$ruta_xml);
	$ruta_xml_au=str_replace('.XML','_au.xml',$ruta_xml_au);
	$ruta_xml_error=str_replace('.xml','_error.xml',$ruta_xml);
	$ruta_xml_error=str_replace('.XML','_error.xml',$ruta_xml_error);

//mash 2023-06-20
	$res_json=json_decode($res,true, JSON_INVALID_UTF8_IGNORE);
//echo "<pre>res_json ";print_r($res_json);echo "</pre>"; //die();
//echo "<pre>";print_r($res_json);echo "</pre>"; //die();
	$saldo=$res_json['saldo'];
	mf_agrega_global('xml_firmado', '');
	if($res_json['iAmb']==1)
	{
		//produccion
		if($res_json['codigo']=='0260')
		{
			//OK
			mf_agrega_global('xml_firmado', $xml_firmado);
			file_put_contents($ruta_xml,$xml_firmado);
			file_put_contents($ruta_xml_au,base64_decode($res_json['AU']));
		}
		else
		{
			//ERROR
			if($res_json['saldo']>0)
			{
				file_put_contents($ruta_xml_error,$xml_firmado);
			}
		}
		
	}
	else
	{
		//pruebas
		if($res_json['codigo']=='0260')
		{
			//OK
			mf_agrega_global('xml_firmado', $xml_firmado);
			file_put_contents($ruta_xml,$xml_firmado);
//			file_put_contents($ruta_xml_sinfirmar,$xml_sin_firmar);
			file_put_contents($ruta_xml_au,base64_decode($res_json['AU']));
		}
		else
		{
			//ERROR
			file_put_contents($ruta_xml_error,$xml_firmado);
			file_put_contents($ruta_xml_sinfirmar,$xml_sin_firmar);
		}
	}

	return $res_json;
}
//

//




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////   CURL
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//https://weichie.com/blog/curl-api-calls-with-php/

function callAPImf($method, $url, $data=null,$cache=false)
{
    
    
/*
	if($_SERVER["SERVER_ADDR"]=='144.217.229.55')//ip ws pruebas
	{
		//$url=str_replace('//55.cfdi.red/panelpa','//144.217.229.55/panel',$url);
		$url=str_replace('//55.cfdi.red/panelpa','//144.217.229.54/panel',$url);
	}
	*/
	if($cache!=false)
	{
		$tag_cache="callAPI_$method$url".md5(json_encode($data2));
		$valor=cache_lee($tag_cache);
		if($valor!='')
			return $valor;
		
	}
	
    if($data2!=null)
		$data=$data2;
        
	$curl = curl_init();
	$options = array(
		CURLOPT_RETURNTRANSFER => true,   // return web page
		CURLOPT_HEADER         => false,  // don't return headers
		CURLOPT_FOLLOWLOCATION => false,   // follow redirects
		CURLOPT_MAXREDIRS      => 2,     // stop after 10 redirects
		CURLOPT_ENCODING       => "",     // handle compressed
//		CURLOPT_USERAGENT      => "api-mf", // name of client
		CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
		CURLOPT_CONNECTTIMEOUT => 90,    // time-out on connect
		CURLOPT_TIMEOUT        => 90    // time-out on response
	);
    
    curl_setopt($curl, CURLOPT_TIMEOUT,90); // 500 seconds CARLOS
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);	
	curl_setopt_array($curl, $options);	
    switch ($method){
		case "POST":
			curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
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
    //print_r($data);
    //echo $url;
	curl_setopt($curl, CURLOPT_URL, $url);
	

	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	// EXECUTE:
    $result = curl_exec($curl);
    

	if(!$result)
	{
		
        //return 'ERROR CURL';
        $error['error']="ERROR sdk1000 CURL: ".curl_error($curl);;
        $info = curl_getinfo($curl);
        $info = var_export($info,true);
        return json_encode($error);
        //return 'ERROR CURL';
	}
	curl_close($curl);

	if($cache!=false)
		return cache_guarda("$tag_cache",trim($result));
	else
		return trim($result);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
function  ruta__($arr,$padre='/')
{
	foreach($arr AS $llave => $valor)
	{
		//is_object(
		if(is_array($valor))
		{
			echo ruta($arr[$llave],"$padre$llave/");
			
		}
		else
		{
			echo "$padre$llave=$valor <br/>";			
		}
	}
	
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////////////



//////////////////////////////////////////////////////////////////////////////////////////////////////////


//FORMA LOS XML DE LOS EVENTOS DE FACTURAS
function firmar_eventos_xml_panama($xml_string, $cer_pem, $key_pem, $password)
{
    
    if($cer_pem=='')
		$cer_pem=$key_pem;
	if($key_pem=='')
		$key_pem=$cer_pem;
    
  // echo "<br>xml_string:".$xml_string;
  //echo $xml_string=file_get_contents($xml_string);
    // Constantes a usar
    global $__mf_constantes__;

    // Respuesta
    $respuesta_funcion = array('abortar' => false);

    // Se carga la libreria xmlsec
	
    $ruta = __DIR__.'/';
    $ruta=str_replace('\\','/',$ruta);
	global $_kit_ruta_;

    include "$_kit_ruta_/lib/xmlsec/vendor/robrichards/xmlseclibs/xmlseclibs.php";
    //mf_carga_libreria($__mf_constantes__['__MF_LIBS_DIR__'] . 'xmlsec/vendor/autoload.php');

    try {
        // Se carga el xml
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML($xml_string);

        // Objeto XMLSecurityDSig (prefijo)
        $objDSig = new RobRichards\XMLSecLibs\XMLSecurityDSig('');

        // Se canoniza el xml (c14n)
        $objDSig->setCanonicalMethod(RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N);
        
        // Transformaciones para panama
        $transformaciones_xml = array(
            'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
            'http://www.w3.org/2001/10/xml-exc-c14n#'
        );
        
        // Tipo de sello SHA-256
        $objDSig->addReference(
            $doc,
            RobRichards\XMLSecLibs\XMLSecurityDSig::SHA256,
            $transformaciones_xml,
            array('force_uri'=>true)
        );
        
        // Create a new (private) Security key
        $objKey = new RobRichards\XMLSecLibs\XMLSecurityKey(RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        //If key has a passphrase, set it using
        //$objKey->passphrase = '<passphrase>';
        $objKey->passphrase = $password;

        // Load the private key
        $objKey->loadKey($key_pem, TRUE);

        // Sign the XML file
        $objDSig->sign($objKey);

        // Add the associated public key to the signature
        $objDSig->add509Cert(file_get_contents($cer_pem), true, false, array('issuerSerial' => false, 'subjectName' => true));

        // Append the signature to the XML
        $objDSig->appendSignature($doc->documentElement);
        // Append the signature to the Message XML element

        // Se regresa el xml firmado
        $xml_firmado=$doc->saveXML();
        //file_put_contents("/var/www/vhosts/cfdi.red/httpdocs/multifacturas_docs/sdk2_desarrollo/tmp/xml_firmado_ultimo.xml",$xml_firmado);
      
    }
    catch (Exception $ex)
    {

        return $respuesta_funcion;
    }
    
    return $xml_firmado;
}


///PROVICIONAL MIENTRAS SE SOLUCIONA EL WS REAL  29/12/21
function feConsFE($datos)
{
    $dVerForm=$datos['dVerForm'];
    $dId=$datos['dId'];
    $iAmb=$datos['iAmb'];
    $dCUFE=$datos['dCUFE'];  
    $certificado_autenticacion=$datos['cer'];
    $password_autenticacion=$datos['cer_pass'];
    /* PARAMETROS DF LA FUNCION */
    $feConsFE = array('feDatosMsg' => array('rEnviConsFe' => array('dVerForm' => $dVerForm,
                                                                        'dId' => $dId,
                                                                        'iAmb' => $iAmb,'dCufe' => $dCUFE)
                                            )
                    );
global $_kit_ruta_;

    include "$_kit_ruta_/lib/nusoap/nusoap.php";
    $wsdl="https://dgi-fepws-test.mef.gob.pa:40010/FepWcfService/feConsFE.svc?wsdl";
    //certificado del pac
    //$certificado_autenticacion="F-8-229-2724.cer.pem";
    //$password_autenticacion="524596785";
    
    //certificado del emisor
    //$certificado_autenticacion="certificado_kit.cer";
    //$password_autenticacion="84665c168490988d33d4b1ded2f5edf5b4957784498";
    
    try {
        $opciones = array(
           "local_cert" => $certificado_autenticacion, "passphrase" => $password_autenticacion,
           "soap_version" => SOAP_1_1, "encoding" => "UTF-8",
           "location" => "https://dgi-fepws-test.mef.gob.pa:40010/FepWcfService/feConsFE.svc",
           'trace' => true);
    
        $cliente = new SoapClient($wsdl, $opciones);
        /** headers **/
        $headers=array();
        $headers[] = new SoapHeader("http://dgi-fep.mef.gob.pa/wsdl/FeRecepFE", 'feHeaderMsg', array('dVerForm' => 1.00));
        $cliente->__setSoapHeaders($headers);
    
        /* MUESTRA LAS FUNCIONES DISPONIBLES PARA ESTE WS*/
        /*$funciones = $cliente->__getFunctions();
        echo "<br>FUNCIONES DISPONIBLES DEL WS <br>";
        echo "<pre>";
        print_r($funciones);
        echo "</pre>";*/
        
        /*
        echo "<br>PARAMETROS DEL METODO feConsFE<br>";
        echo "<pre>";
        print_r($feConsFE);
        echo "</pre>";
        */
        $dgi_respuesta=  $cliente->__soapCall('feConsFE',$feConsFE);
        
        /*echo "<pre>";
        print_r($dgi_respuesta);
        echo "</pre>";*/
    
        /* MUESTRA EL XML SOAP ENVOLOPED QUE SE ENVIO COMO PETICION AL WS*/
        /*$debug =  $cliente->__getLastRequest();
        echo "<br>DEBUG ENVELOPED<BR> FeCodSegQR: <pre>" . htmlentities($debug) . "</pre> <br>";
        echo "<br>DEBUG ENVELOPED<BR> FeCodSegQR: <pre>" . $debug . "</pre> <br>";*/
        //$respuesta_funcion = $dgi_respuesta;
        //$respuesta_funcion=json_encode($respuesta_funcion);
        $respuesta_funcion = json_decode(json_encode($dgi_respuesta), true);
        
        /*echo "<br>RESPUESTA DEL WS<br>";
        echo "<pre>";
        print_r($respuesta_funcion);
        echo "</pre>";*/
    }
    catch(Exception $e) {
       //$respuesta_funcion = $e->getMessage();
        $respuesta_funcion['mf_error_codigo']=999;
        $respuesta_funcion['mf_error_texto']="ERROR DE CONEXION A DGI";
        $respuesta_funcion['mf_error_conexion']=$e->getMessage();
    }
    
    
    return $respuesta_funcion;
}


