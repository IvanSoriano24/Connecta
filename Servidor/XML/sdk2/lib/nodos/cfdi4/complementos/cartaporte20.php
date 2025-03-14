<?php

function mf_complemento_cartaporte20(array $datos)
{
    // Variable para los namespaces xml
    global $__mf_namespaces__;
    $__mf_namespaces__['cartaporte20']['uri'] = 'http://www.sat.gob.mx/CartaPorte20';
    $__mf_namespaces__['cartaporte20']['xsd'] = 'http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte20.xsd';

    $atrs = mf_atributos_nodo($datos['atrs']); 
    $xml = "<cartaporte20:CartaPorte Version='2.0' $atrs>";

//// Ubicaciones
	$xml .= "<cartaporte20:Ubicaciones>";
	foreach($datos['Ubicacion'] as $idx => $ubicacion)
	{
		$atrs = mf_atributos_nodo($datos['Ubicacion'][$idx]['atrs']);
		$xml .= "<cartaporte20:Ubicacion $atrs>";
		if(count($datos['Ubicacion'][$idx]['atrs'])>1)
		{
            if(count($datos['Ubicacion'][$idx]['domicilio'])>0)
            {
                $atrs = mf_atributos_nodo($datos['Ubicacion'][$idx]['domicilio']);
                $xml .= "<cartaporte20:Domicilio $atrs />";
            }
        }
		$xml .= "</cartaporte20:Ubicacion>";
	}
	$xml .= "</cartaporte20:Ubicaciones>";


//// Mercancias/mercancia
	$atrs = mf_atributos_nodo($datos['Mercancias']['atrs']);
	$xml .= "<cartaporte20:Mercancias $atrs>";
	unset($datos['Mercancias']['atrs']);	
	foreach($datos['Mercancias'] as $idmercancia => $mercancia)
	{
		if("$idmercancia"!='atrs')
		{
			$atrs = mf_atributos_nodo($datos['Mercancias'][$idmercancia]['Mercancia']['atrs']);
            $xml.="<cartaporte20:Mercancia $atrs>"; 
			
            //Pedimentos
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'])>0)
			{
                foreach($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'] AS $idtmp=>$Pedimento)
				{
				    $atrs = mf_atributos_nodo($Pedimento);
					$xml.="<cartaporte20:Pedimentos $atrs />";
				}
			}
			
			//GuiasIdentificacion
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['GuiasIdentificacion'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['GuiasIdentificacion'] AS $idtmp=>$GuiasIdentificacion)
				{
					$atrs = mf_atributos_nodo($GuiasIdentificacion);
					$xml.="<cartaporte20:GuiasIdentificacion $atrs />";
				}
			}
			
            //CantidadTransporta
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['CantidadTransporta'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['CantidadTransporta'] AS $idtmp=>$CantidadTransporta)
				{
					$atrs = mf_atributos_nodo($CantidadTransporta);
					$xml.="<cartaporte20:CantidadTransporta $atrs />";
				}
			}

			//DetalleMercancia
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['DetalleMercancia'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['DetalleMercancia'] AS $idtmp=>$DetalleMercancia)
				{
					$atrs = mf_atributos_nodo($DetalleMercancia);
					$xml.="<cartaporte20:DetalleMercancia $atrs />";
				}
			}			
			
			$xml.="</cartaporte20:Mercancia>";
        }
    } //FIN MERCANCIA
    
    //// Mercancias/Autotransporte
    foreach($datos['Mercancias'] as $idAutotransporte => $Autotransporte)
	{
        if(count($datos['Mercancias'][$idAutotransporte]['Autotransporte'])>0)
		{
	
			$atrs = mf_atributos_nodo($datos['Mercancias'][$idAutotransporte]['Autotransporte']['atrs']);
			$xml .= "<cartaporte20:Autotransporte $atrs>";

			foreach($datos['Mercancias'][$idAutotransporte]['Autotransporte'] as $idx => $datosAutotransporte)
			{

				
				{

//echo "<pre>"; print_r($Autotransporte); echo "</pre>";
		//IdentificacionVehicular			
					if("$idx"=='IdentificacionVehicular')
					{
							$atrs = mf_atributos_nodo($datosAutotransporte);
							$xml.="<cartaporte20:IdentificacionVehicular $atrs />";
					}
		//Seguros			
					if("$idx"=='Seguros')
					{
							$atrs = mf_atributos_nodo($datosAutotransporte);
							$xml.="<cartaporte20:Seguros $atrs />";
					}
		//Remolque			
					if("$idx"=='Remolque')
					{
						$xml.="<cartaporte20:Remolques>";
							foreach($datosAutotransporte AS $datoRemolque)
							{
								$atrs = mf_atributos_nodo($datoRemolque);
								$xml.="<cartaporte20:Remolque $atrs />";
								
							}
						$xml.="</cartaporte20:Remolques>";
					}				
				}
			}
			$xml .= "</cartaporte20:Autotransporte>";
		}
    } //fin auto transporte


	//// Mercancias/TransporteMaritimo
    foreach($datos['Mercancias'] as $idTransporteMaritimo => $TransporteMaritimo)
	{
    	if(count($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo'])>0)
    	{
    
    		$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo']['atrs']);
    		$xml .= "<cartaporte20:TransporteMaritimo $atrs>";
    		foreach($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo']['Contenedor'] AS $idx=>$datoscontenedor)
    		{
    			$atrs = mf_atributos_nodo($datoscontenedor);
    			$xml.="<cartaporte20:Contenedor $atrs />";
    		}
    		
    		$xml .= "</cartaporte20:TransporteMaritimo>";
    	}
	}	
	//// Mercancias/TransporteAereo	
    foreach($datos['Mercancias'] as $idTransporteAereo => $TransporteAereo)
	{
    
		if(count($datos['Mercancias'][$idTransporteAereo]['TransporteAereo'])>0)
		{
				$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteAereo]['TransporteAereo']);
				$xml.="<cartaporte20:TransporteAereo $atrs />";
			
		}
	}	
	//// Mercancias/TransporteFerroviario
    
    foreach($datos['Mercancias'] as $idTransporteFerroviario=> $TransporteFerroviario)
	{
    

		if(count($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario'])>0)
		{
			//DerechosDePaso
				$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['atrs']);
				unset($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['atrs']);
				$xml.="<cartaporte20:TransporteFerroviario $atrs>";
				foreach($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['DerechosDePaso'] AS $idx=>$datosDerechosDePaso)
				{
					$atrs = mf_atributos_nodo($datosDerechosDePaso);
					$xml.="<cartaporte20:DerechosDePaso $atrs />";
					
				}
			//Carro
				foreach($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['Carro'] AS $idcarro=>$datoCarro)
				{
					
					$atrs = mf_atributos_nodo($datoCarro['atrs']);
					unset($datoCarro['atrs']);
					$xml.="<cartaporte20:Carro $atrs >";
			//Carro/Contenedor
						foreach($datoCarro['Contenedor'] AS $idcontenedor=>$datocontenedor)
						{
							$atrs = mf_atributos_nodo($datocontenedor);
							$xml.="<cartaporte20:Contenedor $atrs />";
						}
					
					
					$xml.="</cartaporte20:Carro>";
				}
				
				
				$xml.="</cartaporte20:TransporteFerroviario>";

		}

    } //fin ciclo ferroviario

	//	}//fin ciclo mercancia
		
        $xml .= "</cartaporte20:Mercancias>";
//FiguraTransporte
	if(count($datos['FiguraTransporte'])>0)
	{
		$xml.="<cartaporte20:FiguraTransporte >";
		
		
		foreach($datos['FiguraTransporte']['TiposFigura'] AS $idpartestransporte=>$datosTiposFigura)
		{
			$atrs = mf_atributos_nodo($datosTiposFigura['atrs']);
			$xml.="<cartaporte20:TiposFigura $atrs>";
			foreach($datosTiposFigura['PartesTransporte'] AS $idPartesTransporte=>$datosPartesTransporte)
			{

				$atrs = mf_atributos_nodo($datosPartesTransporte['atrs']);
				$xml.="<cartaporte20:PartesTransporte $atrs>";
				if(count($datosPartesTransporte['Domicilio'])>0)
				{
					$atrs = mf_atributos_nodo($datosPartesTransporte['Domicilio']);
					$xml.="<cartaporte20:Domicilio $atrs />";
				}
				$xml.="</cartaporte20:PartesTransporte>";
			}
//echo " $idx=>$datosTiposFigura</hr>";
//echo "<pre>"; print_r($datosTiposFigura); echo "</pre>";			
			$xml.="</cartaporte20:TiposFigura >";
		}

		$xml.="</cartaporte20:FiguraTransporte>";
	}

    $xml .= "</cartaporte20:CartaPorte>";


//echo htmlentities($xml);die();

echo $xml;
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
