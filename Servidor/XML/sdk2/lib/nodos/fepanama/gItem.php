<?php

function mf_nodo_gitem(array $datos)
{

	$xml='';
    foreach($datos as $idx => $gitem)
    {
        /*
        $dDescProd = $gitem['dDescProd'];
        //$dDescProd = htmlspecialchars($dDescProd, ENT_QUOTES);
        $gitem['dDescProd']=$dDescProd;
echo "<pre>";
print_r($gitem);
echo "</pre>";
        echo "<br>";
        
    */    
		$xml .= '<gItem>';
        // C02:Número secuencial del ítem
        $xml .= crea_nodo_simple($gitem, 'dSecItem');
        // C03:Descripción del producto o servicio
        $xml .= crea_nodo_simple($gitem, 'dDescProd');
        // C04:Código interno del Ítem
        $xml .= crea_nodo_simple($gitem, 'dCodProd');
        // C05:Unidad de medida del código interno
        $xml .= crea_nodo_simple($gitem, 'cUnidad');
        // C06:Cantidad del producto o servicio en la unidad de medida del código interno
        $xml .= crea_nodo_simple($gitem, 'dCantCodInt');
        // C07:Fecha de fabricación/elaboración
        $xml .= crea_nodo_simple($gitem, 'dFechaFab');
        // C08:Fecha de caducidad
        $xml .= crea_nodo_simple($gitem, 'dFechaCad');
        // C09:Código del Ítem en la Codificación Panameña de Bienes y Servicios Abreviada
        $xml .= crea_nodo_simple($gitem, 'dCodCPBSabr');
        // C10:Código del Ítem en la Codificación Panameña de Bienes y Servicios
        $xml .= crea_nodo_simple($gitem, 'dCodCPBScmp');
        // C11:Unidad de medida en la Codificación Panameña de Bienes y Servicios
        $xml .= crea_nodo_simple($gitem, 'cUnidadCPBS');
        // C19:Informaciones de interés del emitente con respeto a un ítem de la FE
        $xml .= crea_nodo_simple($gitem, 'dInfEmFE');

        // C20:Grupo de precios del ítem
        $xml .= crea_nodo_rama($gitem, 'gPrecios', array('dPrUnit', 'dPrUnitDesc', 'dPrItem', 'dPrAcarItem', 'dPrSegItem', 'dValTotItem'));

        $xml .= crea_nodo_rama($gitem, 'gCodItem', array('dGTINCom', 'dCantGTINCom', 'dGTINInv', 'dCantComInvent'));

        // C40:Grupo de ITBMS del ítem
        $xml .= crea_nodo_rama($gitem, 'gITBMSItem', array('dTasaITBMS', 'dValITBMS'));

        // C50:Grupo de ISC del ítem
        $xml .= crea_nodo_rama($gitem, 'gISCItem', array('dTasaISC', 'dValISC'));


		
		$xml.= crea_nodo_gOTIItem($gitem);

        // E05: Grupo de detalle de vehículos nuevos
        $xml .= crea_nodo_rama($gitem, 'gVehicNuevo', array(
            'iModOpVN',
            'dModOpVNDesc',
            'dChasi',
            'dColorCod',
            'dColorNomb',
            'dPotVeh',
            'dCilin',
            'dPesoNet',
            'dPesoBruto',
            'dNSerie',
            'iCombust',
            'iCombustDesc',
            'dNroMotor',
            'dCapTracc',
            'dEntreEj',
            'dAnoMod',
            'dAnoFab',
            'dTipoPintura',
            'dTipoPinturaDesc',
            'dTipoVehic',
            'cEspVehic',
            'iCondVehic',
            'dLotac',
        ));

        // E10: Grupo de detalle de medicinas y materias primas farmacéuticas
        $xml .= crea_nodo_rama($gitem, 'gMedicina', array('dNroLote', 'dCtLote'));
        
        // E10: Grupo de detalle de medicinas y materias primas farmacéuticas
        $xml .= crea_nodo_rama($gitem, 'gPedComIr', array('dNroPed', 'dSecItemPed', 'dInfEmPedIt'));
		$xml .= '</gItem>';
    }

    

    return $xml;
}


function crea_nodo_gOTIItem(array $datos)
{
	$xml='';
	$cnt=count($datos['gOTIItem']);
	if($cnt>0)
	{
		foreach($datos['gOTIItem'] AS $datogOTIItem)
		{
			$dCodOTI=$datogOTIItem['dCodOTI'];
			$dValOTI=$datogOTIItem['dValOTI'];
			$xml.="<gOTIItem><dCodOTI>$dCodOTI</dCodOTI><dValOTI>$dValOTI</dValOTI></gOTIItem>";
		}		
	}
//echo " ------ ";echo htmlentities($xml);	
	return $xml;
}