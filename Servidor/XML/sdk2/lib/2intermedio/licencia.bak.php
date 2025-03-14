<?php
function mf_licencia($datos=null)
{

    global $__mf__;
    global $__mf_constantes__;
	global $__mf_rfc_pruebas__;
	
	// Fecha en que caduca la licencia
	$f_vence_licencia = '2015-01-17';
	
    // Codigo original
    // Respuesta
    $respuesta = array(
        'codigo_mf_numero' => 0,
        'codigo_mf_texto' => ''
    );

    // Es modo de pruebas
    $modo_pruebas = $__mf_rfc_pruebas__ == $datos['emisor']['rfc'];
	//var_dump($__mf_rfc_pruebas__);
	//var_dump($datos['emisor']['rfc']);
	//var_dump($modo_pruebas);

    if($modo_pruebas)
    {
		$cadenaOriginal = '';
        $respuesta['codigo_mf_numero'] = 0;
		$respuesta['codigo_mf_texto'] = 'OK';
		$respuesta['cfdi'] = mf_genera_xml($datos, $__mf__['xml_cfdi'], $__mf__['sello']);
		$respuesta['cadena_original'] = $__mf__['cadena_original'];
		$respuesta['archivo_xml'] = $datos['cfdi'];
		$respuesta['fecha_licencia'] = $f_vence_licencia;
    }
	
	if(!$modo_pruebas && strlen($datos['PAC']['usuario']) >= 12)
    {
		// Se convierten las fechas a valores numericos con strtotime
		// para trabajarlas con el operador <=
		
		
		// Fecha en que vence la licencia
		$time_vence = strtotime($f_vence_licencia);
		
		// Fecha de liberacion del SDK
		$time_liberacion = strtotime(__MF_FECHA_LIBERACION__);

		// Si la fecha de liberacion es menor a la fecha en que caduca la licencia
		if($time_liberacion <= $time_vence)
		{
			$cadenaOriginal = '';
			$respuesta['cfdi'] = mf_genera_xml($datos, $__mf__['xml_cfdi'], $__mf__['sello']);
			$respuesta['codigo_mf_texto'] = 'OK';
			$respuesta['cadena_original'] = $__mf__['cadena_original'];
			$respuesta['archivo_xml'] = $datos['cfdi'];
			$respuesta['fecha_licencia'] = $f_vence_licencia;
			$respuesta['fecha_sdk'] = __MF_FECHA_LIBERACION__;
		}
		else
		{
			$respuesta['codigo_mf_numero'] = 7;
			$respuesta['codigo_mf_texto'] = 'LICENCIA VENCIDA';
			$respuesta['fecha_licencia'] = $f_vence_licencia;
			$respuesta['fecha_sdk'] = __MF_FECHA_LIBERACION__;
		}
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