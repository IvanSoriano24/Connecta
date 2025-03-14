<?php

function mf_complemento_cartaporte31(array $datos)
{
    // Variable para los namespaces xml
    global $__mf_namespaces__;
    $__mf_namespaces__['cartaporte31']['uri'] = 'http://www.sat.gob.mx/CartaPorte31';
    $__mf_namespaces__['cartaporte31']['xsd'] = 'http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte31.xsd';

    $atrs = mf_atributos_nodo($datos['atrs']); 
    $xml = "<cartaporte31:CartaPorte Version='3.1' $atrs>";

//// Ubicaciones
	$xml .= "<cartaporte31:Ubicaciones>";
	foreach($datos['Ubicacion'] as $idx => $ubicacion)
	{
		$atrs = mf_atributos_nodo($datos['Ubicacion'][$idx]['atrs']);
		$xml .= "<cartaporte31:Ubicacion $atrs>";
		if(count($datos['Ubicacion'][$idx]['atrs'])>1)
		{
            if(count($datos['Ubicacion'][$idx]['domicilio'])>0)
            {
                $atrs = mf_atributos_nodo($datos['Ubicacion'][$idx]['domicilio']);
                $xml .= "<cartaporte31:Domicilio $atrs />";
            }
        }
		$xml .= "</cartaporte31:Ubicacion>";
	}
	$xml .= "</cartaporte31:Ubicaciones>";


//// Mercancias/mercancia
	$atrs = mf_atributos_nodo($datos['Mercancias']['atrs']);
	$xml .= "<cartaporte31:Mercancias $atrs>";
	unset($datos['Mercancias']['atrs']);	
	foreach($datos['Mercancias'] as $idmercancia => $mercancia)
	{
		if("$idmercancia"!='atrs')
		{
			$atrs = mf_atributos_nodo($datos['Mercancias'][$idmercancia]['Mercancia']['atrs']);
            $xml.="<cartaporte31:Mercancia $atrs>"; 
	
            //DocumentacionAduanera
			
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['DocumentacionAduanera'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['DocumentacionAduanera'] AS $idtmp=>$DocumentacionAduanera)
				{
					$atrs = mf_atributos_nodo($DocumentacionAduanera);
					$xml.="<cartaporte31:DocumentacionAduanera $atrs />";
				}
			}
			
			//Pedimentos
//            if(is_countable($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'] && count($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos']))>0)
            if(count($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'])>0)
			{
                foreach($datos['Mercancias'][$idmercancia]['Mercancia']['Pedimentos'] AS $idtmp=>$Pedimento)
				{
				    $atrs = mf_atributos_nodo($Pedimento);
					$xml.="<cartaporte31:Pedimentos $atrs />";
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
					$xml.="<cartaporte31:GuiasIdentificacion $atrs />";
				}
			}
			
            //CantidadTransporta
			if(count($datos['Mercancias'][$idmercancia]['Mercancia']['CantidadTransporta'])>0)
			{
				foreach($datos['Mercancias'][$idmercancia]['Mercancia']['CantidadTransporta'] AS $idtmp=>$CantidadTransporta)
				{
					$atrs = mf_atributos_nodo($CantidadTransporta);
					$xml.="<cartaporte31:CantidadTransporta $atrs />";
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
					$xml.="<cartaporte31:DetalleMercancia $atrs />";
				}
			}	

			
			
			$xml.="</cartaporte31:Mercancia>";
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
			$xml .= "<cartaporte31:Autotransporte $atrs>";

			foreach($datos['Mercancias'][$idAutotransporte]['Autotransporte'] as $idx => $datosAutotransporte)
			{

				
				{

//echo "<pre>"; print_r($Autotransporte); echo "</pre>";
		//IdentificacionVehicular			
					if("$idx"=='IdentificacionVehicular')
					{
							$atrs = mf_atributos_nodo($datosAutotransporte);
							$xml.="<cartaporte31:IdentificacionVehicular $atrs />";
					}
		//Seguros			
					if("$idx"=='Seguros')
					{
							$atrs = mf_atributos_nodo($datosAutotransporte);
							$xml.="<cartaporte31:Seguros $atrs />";
					}
		//Remolque			
					if("$idx"=='Remolque')
					{
						$xml.="<cartaporte31:Remolques>";
							foreach($datosAutotransporte AS $datoRemolque)
							{
								$atrs = mf_atributos_nodo($datoRemolque);
								$xml.="<cartaporte31:Remolque $atrs />";
								
							}
						$xml.="</cartaporte31:Remolques>";
					}				
				}
			}
			$xml .= "</cartaporte31:Autotransporte>";
		}
    } //fin auto transporte


	//// Mercancias/TransporteMaritimo
    foreach($datos['Mercancias'] as $idTransporteMaritimo => $TransporteMaritimo)
	{
    	if(count($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo'])>0)
        //if(count($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo'])>0)
    	{
    
    		$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo']['atrs']);
    		$xml .= "<cartaporte31:TransporteMaritimo $atrs>";
    		foreach($datos['Mercancias'][$idTransporteMaritimo]['TransporteMaritimo']['Contenedor'] AS $idx=>$datoscontenedor)
    		{
    			$atrs = mf_atributos_nodo($datoscontenedor);
    			$xml.="<cartaporte31:Contenedor $atrs />";
    		}
    		
    		$xml .= "</cartaporte31:TransporteMaritimo>";
    	}
	}	
	//// Mercancias/TransporteAereo	
    foreach($datos['Mercancias'] as $idTransporteAereo => $TransporteAereo)
	{
    
		if(count($datos['Mercancias'][$idTransporteAereo]['TransporteAereo'])>0)
        //if(count($datos['Mercancias'][$idTransporteAereo]['TransporteAereo'])>0)
		{
				$atrs = mf_atributos_nodo($datos['Mercancias'][$idTransporteAereo]['TransporteAereo']);
				$xml.="<cartaporte31:TransporteAereo $atrs />";
			
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
				$xml.="<cartaporte31:TransporteFerroviario $atrs>";
				foreach($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['DerechosDePaso'] AS $idx=>$datosDerechosDePaso)
				{
					$atrs = mf_atributos_nodo($datosDerechosDePaso);
					$xml.="<cartaporte31:DerechosDePaso $atrs />";
					
				}
			//Carro
				foreach($datos['Mercancias'][$idTransporteFerroviario]['TransporteFerroviario']['Carro'] AS $idcarro=>$datoCarro)
				{
					
					$atrs = mf_atributos_nodo($datoCarro['atrs']);
					unset($datoCarro['atrs']);
					$xml.="<cartaporte31:Carro $atrs >";
			//Carro/Contenedor
						foreach($datoCarro['Contenedor'] AS $idcontenedor=>$datocontenedor)
						{
							$atrs = mf_atributos_nodo($datocontenedor);
							$xml.="<cartaporte31:Contenedor $atrs />";
						}
					
					
					$xml.="</cartaporte31:Carro>";
				}
				
				
				$xml.="</cartaporte31:TransporteFerroviario>";

		}

    } //fin ciclo ferroviario

	//	}//fin ciclo mercancia
		
        $xml .= "</cartaporte31:Mercancias>";
//FiguraTransporte
	if(count($datos['FiguraTransporte'])>0)
	{
		$xml.="<cartaporte31:FiguraTransporte >";
		
		
		foreach($datos['FiguraTransporte']['TiposFigura'] AS $idpartestransporte=>$datosTiposFigura)
		{
			$atrs = mf_atributos_nodo($datosTiposFigura['atrs']);
			$xml.="<cartaporte31:TiposFigura $atrs>";
			foreach($datosTiposFigura['PartesTransporte'] AS $idPartesTransporte=>$datosPartesTransporte)
			{

				$atrs = mf_atributos_nodo($datosPartesTransporte['atrs']);
				$xml.="<cartaporte31:PartesTransporte $atrs>";
				if(count($datosPartesTransporte['Domicilio'])>0)
				{
					$atrs = mf_atributos_nodo($datosPartesTransporte['Domicilio']);
					$xml.="<cartaporte31:Domicilio $atrs />";
				}
				$xml.="</cartaporte31:PartesTransporte>";
			}
//echo " $idx=>$datosTiposFigura</hr>";
//echo "<pre>"; print_r($datosTiposFigura); echo "</pre>";			
			$xml.="</cartaporte31:TiposFigura >";
		}

		$xml.="</cartaporte31:FiguraTransporte>";
	}

    $xml .= "</cartaporte31:CartaPorte>";

//echo $xml;

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
