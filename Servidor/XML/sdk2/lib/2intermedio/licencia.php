<?php





































































// EJEMPLO GENERA LICENCIA
/*  
    $fecha_='2019-01-16';  
    $nada_=base64_encode(rand().rand());
    
    $clave_ =md5(md5($fecha_));
    echo "
    <pre>
    LIC_$nada_:$fecha_:$clave_
    </pre>
    ";
*/


function mf_licencia($datos=null)
{
//
    global $__mf__;
    global $__mf_constantes__;
	global $__mf_rfc_pruebas__;

	// Fecha en que caduca la licencia
	$f_vence_licencia = '2000-01-01';
//    $licencia_txt='';
    $ruta=$__mf_constantes__[__MF_INTER_DIR__].'licencia_sdk.lic';

    if($ruta)
    {

        $licencia_txt=file_get_contents($ruta);

    }
    list($nada,$licencia_tmp,$clave)=explode(':',$licencia_txt);
    $clave_tmp=md5(md5($licencia_tmp));
    
    if($clave_tmp==$clave)
    {
        $f_vence_licencia=$licencia_tmp;
    }
//    $f_vence_licencia='2030-11-11';

	
    // Codigo original
    // Respuesta
    $respuesta = array(
        'codigo_mf_numero' => 0,
        'codigo_mf_texto' => ''
    );


/// VALIDA LICENCIA DE FECHA
	$time_vence = strtotime($f_vence_licencia);
	// Fecha de liberacion del SDK
	$time_liberacion = strtotime(__MF_FECHA_LIBERACION__);

	// Si la fecha de liberacion es menor a la fecha en que caduca la licencia
	if( ($time_liberacion <= $time_vence) OR ($__mf_rfc_pruebas__ == $datos['emisor']['rfc']) )
	{
//LICENCIA VALIDA	   
    	$cadenaOriginal = '';
        $respuesta['codigo_mf_numero'] = 0;
    	$respuesta['codigo_mf_texto'] = 'OK';
    	$respuesta['cfdi'] = mf_genera_xml($datos, $__mf__['xml_cfdi'], $__mf__['sello']);
    	$respuesta['cadena_original'] = $__mf__['cadena_original'];
    	$respuesta['archivo_xml'] = $datos['cfdi'];
    	$respuesta['fecha_licencia'] = $f_vence_licencia;
        $respuesta['fecha_sdk'] = __MF_FECHA_LIBERACION__;
	}
	else
	{
//LICENCIA CADUCADA	   
		$respuesta['codigo_mf_numero'] = 7;
		$respuesta['codigo_mf_texto'] = 'LICENCIA VENCIDA O INVALIDA';
		$respuesta['fecha_licencia'] = $f_vence_licencia;
		$respuesta['fecha_sdk'] = __MF_FECHA_LIBERACION__;
	}

    return array('abortar' => true, 'respuesta' => $respuesta);

}

function mf_genera_xml($datos, $xml_cfdi, $sello)
{
	$xml_cfdi = utf8_encode($xml_cfdi);
	$xml_cfdi = str_replace("{SELLO}", $sello, $xml_cfdi);
	$dom = new DOMDocument();
	$dom->loadXML($xml_cfdi);
	$xml_cfdi = $dom->saveXML();
	file_put_contents($datos['cfdi'], $xml_cfdi);
	return $xml_cfdi;
}