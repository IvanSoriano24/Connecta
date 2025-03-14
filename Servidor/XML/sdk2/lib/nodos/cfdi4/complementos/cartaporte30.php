<?php

function mf_complemento_cartaporte30(array $datos)
{
    // Variable para los namespaces xml
    global $__mf_namespaces__;
    $__mf_namespaces__['cartaporte30']['uri'] = 'http://www.sat.gob.mx/CartaPorte30';
    $__mf_namespaces__['cartaporte30']['xsd'] = 'http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte30.xsd';

    $atrs = mf_atributos_nodo($datos['atrs']); 
    $xml = "<cartaporte30:CartaPorte Version='3.0' $atrs>";

//// Ubicaciones
	$xml .= "<cartaporte30:Ubicaciones>";
	foreach($datos['Ubicacion'] as $idx => $ubicacion)
	{
		$atrs = mf_atributos_nodo($datos['Ubicacion'][$idx]['atrs']);
		$xml .= "<cartaporte30:Ubicacion $atrs>";
		if(count($datos['Ubicacion'][$idx]['atrs'])>1)
		{
            if(count($datos['Ubicacion'][$idx]['domicilio'])>0)
            {
                $atrs = mf_atributos_nodo($datos['Ubicacion'][$idx]['domicilio']);
                $xml .= "<cartaporte30:Domicilio $atrs />";
            }
        }
		$xml .= "</cartaporte30:Ubicacion>";
	}
	$xml .= "</cartaporte30:Ubicaciones>";


//// Mercancias/mercancia
	$atrs = mf_atributos_nodo($datos['Mercancias']['atrs']);
	$xml .= "<cartaporte30:Mercancias $atrs>";
	unset($datos['Mercancias']['atrs']);	
	foreach($datos['Mercancias'] as $idmercancia => $mercancia)
	{
		if("$idmercancia"!='atrs')
		{
			$atrs = mf_atributos_nodo($datos['Mercancias'][$idmercancia]['Mercancia']['atrs']);
            $xml.="<cartaporte30:Mercancia $atrs>"; 
	
            //Pedimentos
//            if(is_countable($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'] && count($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos']))>0)
            if(count($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'])>0)
			{
                foreach($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'] AS $idtmp=>$Pedimento)
				{
				    $atrs = mf_atributos_nodo($Pedimento);
					$xml.="<cartaporte30:Pedimentos $atrs />";
				}
			}
			
			//GuiasIdentificacion
            //if(is_countable($datos['Mercancias'][$idmercancia]['Mercancia']['GuiasIdentificacion'] && count($datos['Mercancias'][$idmercancia]['Mercancia']['GuiasIdentificacion']))>0)
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['GuiasIdentificacion'])>0)				
			//if(count($datos['Mercancias'][$idmercancia]['Mercancia']['GuiasIdentificacion'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['GuiasIdentificacion'] AS $idtmp=>$GuiasIdentificacion)
				{
					$atrs = mf_atributos_nodo($GuiasIdentificacion);
					$xml.="<cartaporte30:GuiasIdentificacion $atrs />";
				}
			}
			
            //CantidadTransporta
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['CantidadTransporta'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['CantidadTransporta'] AS $idtmp=>$CantidadTransporta)
				{
					$atrs = mf_atributos_nodo($CantidadTransporta);
					$xml.="<cartaporte30:CantidadTransporta $atrs />";
				}
			}

			//DetalleMercancia
            //if(is_countable($datos['Mercancias'][$idmercancia]['Mercancia']['DetalleMercancia'] && count($datos['Mercancias'][$idmercancia]['Mercancia']['DetalleMercancia']))>0)
            if(count($datos['Mercancias'][$idmercancia]['Mercancia']['DetalleMercancia'])>0)
			//if(count($datos['Mercancias'][$idmercancia]['Mercancia']['DetalleMercancia'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['DetalleMercancia'] AS $idtmp=>$DetalleMercancia)
				{
					$atrs = mf_atributos_nodo($DetalleMercancia);
					$xml.="<cartaporte30:DetalleMercancia $atrs />";
				}
			}			
			
			$xml.="</cartaporte30:Mercancia>";
        }
    } //FIN MERCANCIA
    
    //// Mercancias/Autotransporte
    foreach($datos['Mercancias'] as $idAutotransporte => $Autotransporte)
	{
//        if(is_countable($datos['Mercancias'][$idAutotransporte]['Autotransporte'] && count($datos['Mercancias'][$idAutotransporte]['Autotransporte']))>0)
        if(count($datos['Mercancias'][$idAutotransporte]['Autotransporte'])>0)
        //if(count($datos['Mercancias'][$idAutotransporte]['Autotransporte'])>0)
		{

			$atrs = mf_atributos_nodo($datos['Mercancias'][$idAutotransporte]['Autotransporte']['atrs']);
			$xml .= "<cartaporte30:Autotransporte $atrs>";

			foreach($datos['Mercancias'][$idAutotransporte]['Autotransporte'] as $idx => $datosAutotransporte)
			{

				
				{

//echo "<pre>"; print_r($Autotransporte); echo "</pre>";
		//IdentificacionVehicular			
					if("$idx"=='IdentificacionVehicular')
					{
							$atrs = mf_atributos_nodo($datosAutotransporte);
							$xml.="<cartaporte30:IdentificacionVehicular $atrs />";
					}
		//Seguros			
					if("$idx"=='Seguros')
					{
							$atrs = mf_atributos_nodo($datosAutotransporte);
							$xml.="<cartaporte30:Seguros $atrs />";
					}
		//Remolque			
					if("$idx"=='Remolque')
					{
						$xml.="<cartaporte30:Remolques>";
							foreach($datosAutotransporte AS $datoRemolque)
							{
								$atrs = mf_atributos_nodo($datoRemolque);
								$xml.="<cartaporte30:Remolque $atrs />";
								
							}
						$xml.="</cartaporte30:Remolques>";
					}				
				}
			}
			$xml .= "</cartaporte30:Autotransporte>";
		}
    } //fin auto transporte


	//// Mercancias/TransporteMaritimo
    foreach($datos['Mercancias'] as $idTransporteMaritimo => $TransporteMaritimo)
	{
    	if(count($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo'])>0)
        //if(count($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo'])>0)
    	{
    
    		$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo']['atrs']);
    		$xml .= "<cartaporte30:TransporteMaritimo $atrs>";
    		foreach($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo']['Contenedor'] AS $idx=>$datoscontenedor)
    		{
    			$atrs = mf_atributos_nodo($datoscontenedor);
    			$xml.="<cartaporte30:Contenedor $atrs />";
    		}
    		
    		$xml .= "</cartaporte30:TransporteMaritimo>";
    	}
	}	
	//// Mercancias/TransporteAereo	
    foreach($datos['Mercancias'] as $idTransporteAereo => $TransporteAereo)
	{
    
		if(count($datos['Mercancias'][$idTransporteAereo]['TransporteAereo'])>0)
        //if(count($datos['Mercancias'][$idTransporteAereo]['TransporteAereo'])>0)
		{
				$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteAereo]['TransporteAereo']);
				$xml.="<cartaporte30:TransporteAereo $atrs />";
			
		}
	}	
	//// Mercancias/TransporteFerroviario
    
    foreach($datos['Mercancias'] as $idTransporteFerroviario=> $TransporteFerroviario)
	{
    
        if(count($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario'])>0)
		//if(count($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario'])>0)
		{
			//DerechosDePaso
				$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['atrs']);
				unset($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['atrs']);
				$xml.="<cartaporte30:TransporteFerroviario $atrs>";
				foreach($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['DerechosDePaso'] AS $idx=>$datosDerechosDePaso)
				{
					$atrs = mf_atributos_nodo($datosDerechosDePaso);
					$xml.="<cartaporte30:DerechosDePaso $atrs />";
					
				}
			//Carro
				foreach($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['Carro'] AS $idcarro=>$datoCarro)
				{
					
					$atrs = mf_atributos_nodo($datoCarro['atrs']);
					unset($datoCarro['atrs']);
					$xml.="<cartaporte30:Carro $atrs >";
			//Carro/Contenedor
						foreach($datoCarro['Contenedor'] AS $idcontenedor=>$datocontenedor)
						{
							$atrs = mf_atributos_nodo($datocontenedor);
							$xml.="<cartaporte30:Contenedor $atrs />";
						}
					
					
					$xml.="</cartaporte30:Carro>";
				}
				
				
				$xml.="</cartaporte30:TransporteFerroviario>";

		}

    } //fin ciclo ferroviario

	//	}//fin ciclo mercancia
		
        $xml .= "</cartaporte30:Mercancias>";
//FiguraTransporte
	if(count($datos['FiguraTransporte'])>0)
	{
		$xml.="<cartaporte30:FiguraTransporte >";
		
		
		foreach($datos['FiguraTransporte']['TiposFigura'] AS $idpartestransporte=>$datosTiposFigura)
		{
			$atrs = mf_atributos_nodo($datosTiposFigura['atrs']);
			$xml.="<cartaporte30:TiposFigura $atrs>";
			foreach($datosTiposFigura['PartesTransporte'] AS $idPartesTransporte=>$datosPartesTransporte)
			{

				$atrs = mf_atributos_nodo($datosPartesTransporte['atrs']);
				$xml.="<cartaporte30:PartesTransporte $atrs>";
				if(count($datosPartesTransporte['Domicilio'])>0)
				{
					$atrs = mf_atributos_nodo($datosPartesTransporte['Domicilio']);
					$xml.="<cartaporte30:Domicilio $atrs />";
				}
				$xml.="</cartaporte30:PartesTransporte>";
			}
//echo " $idx=>$datosTiposFigura</hr>";
//echo "<pre>"; print_r($datosTiposFigura); echo "</pre>";			
			$xml.="</cartaporte30:TiposFigura >";
		}

		$xml.="</cartaporte30:FiguraTransporte>";
	}

    $xml .= "</cartaporte30:CartaPorte>";


//echo htmlentities($xml);die();
//echo bbb;
/*echo "<br>";
echo $xml;
echo "<br>";*/
    return $xml;
}

function mf_atributos_cartaporte(array $datos, array $atr_opcionales, $ruta='')
{
    $atributos = mf_atributos_nodo($datos, $ruta);

    foreach ($atr_opcionales as $key => $atributo)
    {
        if(!isset($datos[$atributo]))
        {
            $atributos = str_replace($atributo, '', $atributos);
        }
    }

    return $atributos;
}
