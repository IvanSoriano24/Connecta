<?php
//

function mf_init_nodo_fepanama(array $datos = array())
{
    
}

function mf_nodo_fepanama(array $datos)
{
	global $__mf_constantes__;
	$__mf_constantes__['__MF_XSD_DIR__'] = $__mf_constantes__['__MF_NODOS_DIR__'] . $__mf_constantes__['__MF_TIPO_DOCUMENTO__'] . '/xsd/' ;

    // Respuesta del SDK
    $respuesta_sdk = array();
      
//ajusta variables

if(isset($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['0']))
{
	$datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['0']=trim($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['0']);
}
if(isset($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['1']))
{
	$datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['1']=trim($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['1']);
}


if(isset($datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['0']))
{
	$datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['0']=trim($datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['0']);
}
if(isset($datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['1']))
{
	$datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['1']=trim($datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['1']);
}
if(isset($datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['2']))
{
	$datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['2']=trim($datos['rFE']['gDGen']['gDatRec']['dCorElectRec']['2']);
}



$datos['rFE']['gDGen']['dFechaEm']=trim($datos['rFE']['gDGen']['dFechaEm']);
$datos['rFE']['gDGen']['gEmis']['gRucEmi']['dRuc']=trim($datos['rFE']['gDGen']['gEmis']['gRucEmi']['dRuc']);
$datos['rFE']['gDGen']['gEmis']['gRucEmi']['dDV']=trim($datos['rFE']['gDGen']['gEmis']['gRucEmi']['dDV']);
$datos['rFE']['gDGen']['gEmis']['dCoordEm']=trim($datos['rFE']['gDGen']['gEmis']['dCoordEm']);

if(isset($datos['rFE']['gDGen']['gDatRec']['gRucRec']['dRuc']))
{
	$datos['rFE']['gDGen']['gDatRec']['gRucRec']['dRuc']=trim($datos['rFE']['gDGen']['gDatRec']['gRucRec']['dRuc']);
}
if(isset($datos['rFE']['gDGen']['gDatRec']['gRucRec']['dDV']))
{
	$datos['rFE']['gDGen']['gDatRec']['gRucRec']['dDV']=trim($datos['rFE']['gDGen']['gDatRec']['gRucRec']['dDV']);
}

if(isset($datos['rFE']['gDGen']['gEmis']['dTfnEm']['0']))
{
	$datos['rFE']['gDGen']['gEmis']['dTfnEm']['0']=trim($datos['rFE']['gDGen']['gEmis']['dTfnEm']['0']);
}
if(isset($datos['rFE']['gDGen']['gEmis']['dTfnEm']['1']))
{
	$datos['rFE']['gDGen']['gEmis']['dTfnEm']['1']=trim($datos['rFE']['gDGen']['gEmis']['dTfnEm']['1']);
}
if(isset($datos['rFE']['gDGen']['gEmis']['dTfnEm']['2']))
{
	$datos['rFE']['gDGen']['gEmis']['dTfnEm']['2']=trim($datos['rFE']['gDGen']['gEmis']['dTfnEm']['2']);
}

if(isset($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['0']))
{
	$datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['0']=trim($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['0']);
}
if(isset($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['1']))
{
	$datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['1']=trim($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['1']);
}
if(isset($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['2']))
{
	$datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['2']=trim($datos['rFE']['gDGen']['gEmis']['dCorElectEmi']['2']);
}

if(isset($datos['rFE']['gDGen']['gDatRec']['gUbiRec']['dCodUbi']))
{
	$datos['rFE']['gDGen']['gDatRec']['gUbiRec']['dCodUbi']=trim($datos['rFE']['gDGen']['gDatRec']['gUbiRec']['dCodUbi']);
}


/*
$datos['rFE']['gItem']['0']['dCantCodInt']
$datos['rFE']['gItem']['0']['gPrecios']['dPrUnit']
$datos['rFE']['gItem']['0']['gPrecios']['dPrItem']
$datos['rFE']['gItem']['0']['gPrecios']['dValTotItem']
$datos['rFE']['gItem']['0']['gITBMSItem']['dValITBMS']
$datos['rFE']['gItem']['0']['gPrecios']['dPrUnitDesc']
*/
    // Inicio del XML
    
    $datos= array_map_recursive('cfd_fix_dato_xml', $datos);

    $xml_sin_firmar = '<rFE xmlns="http://dgi-fep.mef.gob.pa">';
    
    // A02: Versión del formato
    $xml_sin_firmar .= crea_nodo_simple($datos['rFE'], 'dVerForm');

    // A03: Identificador para firma electrónica
    if($datos['rFE']['dId']=='')
    {
        $datos['rFE']['dId']="FE".cufe($datos);
    }
    
    $xml_sin_firmar .= crea_nodo_simple($datos['rFE'], 'dId');

    // B01: Datos generales de la transacción documentada.
    $xml_sin_firmar .= mf_carga_nodo('gDGen', $datos['rFE']['gDGen']);

    // C01: Grupo de datos que especifica cada ítem del detalle de la transacción
    $xml_sin_firmar .= mf_carga_nodo('gItem', $datos['rFE']['gItem']);

    // D01: Grupo con información de subtotales y totales
    $xml_sin_firmar .= mf_carga_nodo('gTot', $datos['rFE']['gTot']);

    // E15: Grupo de detalle de Pedido Comercial de ítem
    $xml_sin_firmar .= mf_carga_nodo('gPedComIr', $datos['rFE']['gPedComIr']);

    // F10: Grupo de detalle de Pedido Comercial global
    $xml_sin_firmar .= mf_carga_nodo('gPedComGl', $datos['rFE']['gPedComGl']);

    // F20: Grupo de informaciones de logística
    $xml_sin_firmar .= mf_carga_nodo('gInfoLog', $datos['rFE']['gInfoLog']);

    // F25: Grupo de datos que identifican el local de la entrega
    $xml_sin_firmar .= mf_carga_nodo('gLcEntr', $datos['rFE']['gLcEntr']);

    // Se cierra el xml
    $xml_sin_firmar .= '</rFE>';
    
    // Se guarda la ruta del temporal
    $xmltmp = $__mf_constantes__['__MF_SDK_TMP__'] . md5(time() . rand(1111, 9999)) . '.xml';
    //echo "<br>xmltmp sin firmar $xmltmp<br>";
   /////////////////////////////////////////////////////////////////////////////////////
    $xml_sin_firmar = str_replace (array("\r\n", "\n", "\r", "\t"), ' ', $xml_sin_firmar);
    //file_put_contents($xmltmp,$xml_sin_firmar);
    mf_agrega_global('ruta_tmp', $xmltmp);

    mf_agrega_global('xml_sin_firmar', $xml_sin_firmar);
	mf_agrega_global('modo_panama', 'SI');
  

//echo htmlentities($xml_sin_firmar);die();

	// Se envia para su recepcion al ws de siteck
	$respuesta_timbrado = feRecepFE_v100($xml_sin_firmar,$datos);
	

	$xProtFe_xml=base64_decode($respuesta_timbrado['AU']);

	$dCodRes=$respuesta_timbrado['codigo'];
	$dMsgRes=json_encode($respuesta_timbrado['codigo_txt']);

//echo "<pre>";print_r($respuesta_timbrado);echo "</pre>"; die();		
	if($dCodRes == '0260')
	{
		
		/*
		$respuesta_sdk['dId'] =$xProtFe['gInfProt']['dId'];
		$respuesta_sdk['saldo']=$respuesta_timbrado['saldo'];
		$respuesta_sdk['iAmb'] =$iAmb=$xProtFe['gInfProt']['iAmb'];		
		$respuesta_sdk['CUFE'] =$xProtFe['gInfProt']['dCUFE'];
		*/

		$respuesta_sdk['AU'] =$respuesta_timbrado['dProtAut'];
		$respuesta_sdk['dId'] =$respuesta_timbrado['dId'];
		$respuesta_sdk['saldo']=$respuesta_timbrado['saldo'];
		$respuesta_sdk['iAmb'] =$iAmb =$respuesta_timbrado['iAmb'];		
		$respuesta_sdk['CUFE'] =$respuesta_timbrado['CUFE'];
		$respuesta_sdk['dFecProc'] =$respuesta_timbrado['dFecProc'];

		$respuesta_sdk['QR']=base64_decode($respuesta_timbrado['QR']);
		$respuesta_sdk['xml_autorizacion_de_uso']=base64_encode($xProtFe_xml);
		
		/* PRUEBAS */

		if($iAmb == 2)
		{
			$respuesta_sdk['produccion'] = 'NO';
			$respuesta_sdk['codigo_mf_numero'] = 0;
			$respuesta_sdk['codigo_mf_texto'] = 'OK_PRUEBAS';
		}
		/* PRODUCCION */
		if($iAmb == 1)
		{
			$respuesta_sdk['produccion'] = 'SI';
			$respuesta_sdk['codigo_mf_numero'] = 0;
			$respuesta_sdk['codigo_mf_texto'] = 'OK';
		}
	}
	else
	{
		//no se timbro
		// ERROR de timbrado
		$respuesta_sdk['saldo']=$respuesta_timbrado['saldo'];											   
		$respuesta_sdk['codigo_mf_numero'] = "2";
		$respuesta_sdk['AU'] =$respuesta_timbrado['dProtAut'];												
		$respuesta_sdk['codigo_mf_texto'] = json_encode($respuesta_timbrado['codigo_txt']);//"$errores_array:$errores_json";
		$respuesta_sdk['CUFE_ERROR'] =$respuesta_timbrado['CUFE'];
	}
	$respuesta_sdk['CodRes'] = $dCodRes;
	$respuesta_sdk['MsgRes'] = $dMsgRes;
	
	
//mash
	if($dCodRes=='0260')
	{
		$xml_firmado=mf_recupera_global('xml_firmado');
		$respuesta_sdk['xml']=base64_encode($xml_firmado);
	}
	$respuesta_sdk['respuesta_ws']=mf_recupera_global('respuesta_ws');

	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//echo "<pre>";print_r($respuesta_sdk);echo "</pre>"; //die();

	
   


    return $respuesta_sdk;
}