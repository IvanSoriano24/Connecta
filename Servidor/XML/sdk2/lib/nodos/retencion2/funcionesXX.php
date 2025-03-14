<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Timbra una retencion
 * @param $pac integer
 * @param $usuario string
 * @param $clave string
 * @param $xml string
 * @return mixed
 */
if(!function_exists('mf_timbrar_retencion'))
{
    function mf_timbrar_retencion($pac, $usuario, $clave, $xml,$produccion)
    {
        global $__mf_constantes__;
        global $__mf_modo_local__;
        global $__mf_servidor_local__;

        if(file_exists($xml) === true)
        {
            $xml = file_get_contents($xml);
        }

        // URL Web Service retenciones
        if($__mf_modo_local__ == true)
        {
			$urlws = "http://$__mf_servidor_local__/pac/timbrar_retenciones.php?wsdl";
        }
        else
        {
			if($pac == 3)
				$pac=4;
		   $urlws = "http://$pac.multifacturas.com/pac/timbrar_retenciones.php?wsdl";
           //echo  $urlws = "http://pac4.multifacturas.com/pac/timbrar_retenciones.php?wsdl";
        }

        mf_carga_libreria($__mf_constantes__['__MF_LIBS_DIR__'] . 'nusoap/nusoap.php');

		$cliente2 = new nusoap_client($urlws,$esWSDL = false);
        $params = array(
            'rfc' => $usuario,
            'clave' => $clave,
            'xml' => base64_encode(utf8_encode($xml)),
            'produccion' => $produccion
        );

        $params['xml'] =base64_decode(utf8_decode($params['xml']));
        $res = $cliente2->call('retencion', $params);
        
/*
echo "<pre>xx--";
//print_r($params);
print_r($res);
echo "</pre>";
//die();
*/
       // por compatibilidad se agrego el base64
        $res['cfdi'] = base64_encode($res['cfdi']);


        if($res['codigo_mf_numero'] != 0)
        {
            $res['abortar'] = true;
        }
        else
        {
            $res['abortar'] = false;
            $res['cfdi'] = base64_decode($res['cfdi']);
        }
        return $res;
    }
}

/////////////////////////////////////////////////////////////////////////////////
/**
 * _cfdi_almacena_error_()
 *
 * @return
 */
if(!function_exists('_cfdi_almacena_error_')) 
{
    function _cfdi_almacena_error_()
    {
        global $cfd_sin_timbrar;
        global $__mf_constantes__;
        $cfd_sin_timbrar=$cfd_sin_timbrar;
        @mkdir($__mf_constantes__['__MF_SDK_TMP__']);
        @chmod($__mf_constantes__['__MF_SDK_TMP__'],0777);
        $file_target = $__mf_constantes__['__MF_SDK_TMP__'].'ultimo_error.xml';

        @unlink($file_target);
        if (file_exists($file_target)) {
            @chmod($file_target, 0777);
        } // add write permission
        if (($wh = fopen($file_target, 'wb')) === false) {
            return "ERROR ESCRITURA EN  $file_target";
        } // error messages.
        if (fwrite($wh, $cfd_sin_timbrar) === false) {
            fclose($wh);
            return "ERROR ESCRITURA EN  $file_target";
        }
        fclose($wh);
        @chmod($file_target, 0777);
    }
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

        global $__mf_constantes__;

        $respuesta_funcion = array('abortar' => false);


        $cer=$datos['conf']['cer'];
        $key=$datos['conf']['key'];
        $pass=$datos['conf']['pass'];

//    $externo=$datos['modo_externo'];
//1.11
        $externo='SI';


        $cer=str_replace('\\','/',$cer);
        $key=str_replace('\\','/',$key);

        @unlink("$cer.pem");
        @unlink("$key.pem");

        $cer=str_replace('.pem','',$cer);
        $key=str_replace('.pem','',$key);
        $cer=str_replace('.PEM','',$cer);
        $key=str_replace('.PEM','',$key);



//auto deteccion de excencion .pem
        $pos = strpos(strtolower($cer), ".pem");
        if ($pos !== false)
        {
            $cerpem=$cer;
        }
        else
        {
            $cerpem="$cer.pem";
        }

        $pos = strpos(strtolower($key), ".pem");
        if ($pos !== false)
        {
            $keypem=$key;
        }
        else
        {
            $keypem="$key.pem";
        }

        if($externo=='SI')
        {
            if(!class_exists('nusoap_client'))
            {
                $ruta_nusoap = $__mf_constantes__['__MF_LIBS_DIR__']."nusoap/nusoap.php";
                mf_carga_libreria($ruta_nusoap);
            }

            if(!class_exists('nusoap_client'))
            {
                $respuesta_funcion['abortar'] = true;
                $respuesta_funcion['respuesta'] = array(
                    'codigo_mf_numero' => 7,
                    'codigo_mf_texto' => 'SE REQUIERE LIBRERIA nusoap'
                );
                return $respuesta_funcion;
            }

            if(file_exists($cer) == false)
            {
                $respuesta_funcion['abortar'] = true;
                $respuesta_funcion['respuesta'] = array(
                    'codigo_mf_numero' => 7,
                    'codigo_mf_texto' => 'NO SE PUDO LEER CERTIFICADO'
                );
                return $respuesta_funcion;
            }

            if(file_exists($key) == false)
            {
                $respuesta_funcion['abortar'] = true;
                $respuesta_funcion['respuesta'] = array(
                    'codigo_mf_numero' => 7,
                    'codigo_mf_texto' => 'NO SE PUDO LEER LLAVE PRIVADA'
                );
                return $respuesta_funcion;
            }

            $cer_base64=base64_encode(file_get_contents($cer));
            $key_base64=base64_encode(file_get_contents($key));
            $pass_base64=base64_encode($datos['conf']['pass']);



            global $__mf_modo_local__;

            if($__mf_modo_local__ == true)
            {
                global $__mf_servidor_local__;
                $soapclient = new nusoap_client("http://$__mf_servidor_local__/pac/?wsdl",$esWSDL = true);
            }
            else
            {

                $pac=rand(1,10);
                $url2="http://pac$pac.multifacturas.com/pac/?wsdl";
                $soapclient = new nusoap_client($url2,$esWSDL = true);
            }
            /*
                    global $multi_produccion;
                    if($multi_produccion==true)
                    {
                        $soapclient = new nusoap_client("http://pac$pac.multifacturas.com/pac/?wsdl",$esWSDL = true);
                    }
                    else
                    {
                        global $__mf_servidor_local__;
                        $soapclient = new nusoap_client("http://$__mf_servidor_local__/pac/?wsdl",$esWSDL = true);
                    }
            */
            $tim = array('cer_base64' => $cer_base64, 'key_base64' => $key_base64,'pass_base64' => $pass_base64);
            $soap_timbrado = $soapclient->call('generar_pem', $tim);
            $res=$soap_timbrado;
            if(array_key_exists('codigo_mf_numero', $res) && $res['codigo_mf_numero'] != 0)
            {
                $respuesta_funcion['abortar'] = true;
                $respuesta_funcion['respuesta'] = array(
                    'codigo_mf_numero' => $res['codigo_mf_numero'],
                    'codigo_mf_texto' => 'ERROR CONVIRTIENDO CERTIFICADOS, REVISE CLAVE'
                );
                return $respuesta_funcion;
            }

            $cer_pem= base64_decode($res['cer_pem']);
            $key_pem= base64_decode($res['key_pem']);

            if(strlen($cer_pem)>20)
                file_put_contents("$cer.pem",$cer_pem);

            if(strlen($key_pem)>20)
                file_put_contents("$key.pem",$key_pem);

            $respuesta_funcion['respuesta'] = $res;
            return $respuesta_funcion;
        }

        return $res;


    }
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * @param $datos
 * @return array
 */
if(!function_exists('mf_prepara_certificados'))
{
    function mf_prepara_certificados(&$datos)
    {
        // Respuesta
        $respuesta = array('abortar' => false);

        $cer= $datos['conf']['cer'];
        $key= $datos['conf']['key'];
        $pass= $datos['conf']['pass'];




        $cer=str_replace('\\','/',$cer);
        $key=str_replace('\\','/',$key);

        $cer2=strtolower($cer);
        $key2=strtolower($key);
        if(strpos($cer2, '.pem')===false)
        {
            $cer="$cer.pem";
            $datos['conf']['cer']=$cer;
        }
        if(strpos($key2, '.pem')===false)
        {
            $key="$key.pem";
            $datos['conf']['key']=$key;
        }

        //SI EL CERTIFICADO NO ESTA PREPARADO
        if(file_exists("$cer.txt"))
        {
            $certificado_numero = file_get_contents("$cer.txt");
        }
        else
        {
            $certificado_numero='';
        }

        if($certificado_numero==0  OR $certificado_numero==''  OR (filesize("$cer.txt")<5) OR file_exists($cer)==false OR file_exists($key)==false )
        {
            $res_certificado= mf_certificado_pem($datos);


            if($res_certificado['abortar'] == true)
            {
                return $res_certificado;
            }
            else
            {
                $res_certificado = $res_certificado['respuesta'];
            }

            if(!isset($res_certificado['certificado_no_serie']))
            {
                $res_certificado['certificado_no_serie']='';
            }
            $certificado_numero= $res_certificado['certificado_no_serie'];
            //crea archivo

            $file_target="$cer.txt";
            @unlink($file_target);
            if (file_exists($file_target)) {
                @chmod($file_target, 0777);
            } // add write permission
            if (($wh = fopen($file_target, 'wb')) === false) {
                $respuesta['abortar'] =  true;
                $respuesta['respuesta'] = array('codigo_mf_numero' => 7, 'codigo_mf_texto' => "ERROR ESCRITURA EN  $file_target");
            } // error messages.
            if (fwrite($wh, $certificado_numero) === false) {
                fclose($wh);
                $respuesta['abortar'] =  true;
                $respuesta['respuesta'] = array('codigo_mf_numero' => 7, 'codigo_mf_texto' => "ERROR ESCRITURA EN  $file_target");
            }
            fclose($wh);
            @chmod($file_target, 0777);
            $datos['factura']['noCertificado']=$certificado_numero;


        }

        $datos['factura']['certificado'] = cfd_certificado_pub($datos['conf']['cer']);

        if(!isset($datos['factura']['noCertificado']))
        {
            $datos['factura']['noCertificado']='';
        }
        if($datos['factura']['noCertificado']=='' OR $datos['factura']['noCertificado']==0 OR $datos['factura']['noCertificado']=='ND' )
        {
            if($certificado_numero=file_exists("$cer.txt") && !empty($certificado_numero))
            {
                $datos['factura']['noCertificado']=file_get_contents("$cer.txt");
            }
            else
            {
                $respuesta['abortar'] = true;
                $respuesta['respuesta'] = array(
                    'codigo_mf_numero' => 7,
                    'codigo_mf_texto' => 'NO SE PUDO LEER EL NUMERO DE CERTIFICADO'
                );
            }
        }
        // Se retorna la respuesta
        return $respuesta;
    }
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
