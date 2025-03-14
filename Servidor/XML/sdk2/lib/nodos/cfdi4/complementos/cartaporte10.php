<?php

function mf_complemento_cartaporte10(array $datos)
{
    // Variable para los namespaces xml
    global $__mf_namespaces__;
    $__mf_namespaces__['cartaporte']['uri'] = 'http://www.sat.gob.mx/CartaPorte';
    $__mf_namespaces__['cartaporte']['xsd'] = 'http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte.xsd';

    $atr_opcionales = ['EntradaSalidaMerc', 'ViaEntradaSalida', 'TotalDistRec'];
    $atrs = mf_atributos_cartaporte($datos, $atr_opcionales, 'CartaPorte'); // $atrs = mf_atributos_nodo($datos);  //$atrs = mf_atributos_nodo($datos, 'CartaPorte');
    // $atrs = mf_atributos_nodo($datos, 'CartaPorte');

    $xml = "<cartaporte:CartaPorte Version='1.0' $atrs>";
    if(isset($datos['Ubicaciones']))
    {
        $xml .= "<cartaporte:Ubicaciones>";
        foreach($datos['Ubicaciones'] as $idx => $ubicacion)
        {
            if(is_array($datos['Ubicaciones'][$idx]) && is_int($idx))
            {
                $atr_opcionales = ['TipoEstacion', 'DistanciaRecorrida'];
                $atrs = mf_atributos_cartaporte($datos['Ubicaciones'][$idx], $atr_opcionales, 'CartaPorte.Ubicacion');
                // $atrs = mf_atributos_nodo($datos['Ubicaciones'][$idx], 'CartaPorte.Ubicacion');

                $xml .= "<cartaporte:Ubicacion $atrs>";
                if(isset($datos['Ubicaciones'][$idx]['Origen']))
                {
                    $atr_opcionales = ['IDOrigen', 'RFCRemitente', 'NombreRemitente', 'NumRegIdTrib', 'ResidenciaFiscal', 'NumEstacion', 'NombreEstacion', 'NavegacionTrafico'];
                    $atrs = mf_atributos_cartaporte($datos['Ubicaciones'][$idx]['Origen'], $atr_opcionales, 'CartaPorte.Ubicacion.Origen');
                    // $atrs = mf_atributos_nodo($datos['Ubicaciones'][$idx]['Origen'], 'CartaPorte.Ubicacion.Origen');

                    $xml .= "<cartaporte:Origen $atrs>";
                    $xml .= "</cartaporte:Origen>";
                }
                if(isset($datos['Ubicaciones'][$idx]['Destino']))
                {
                    $atr_opcionales = ['IDDestino', 'RFCDestinatario', 'NombreDestinatario', 'NumRegIdTrib', 'ResidenciaFiscal', 'NumEstacion', 'NombreEstacion', 'NavegacionTrafico', 'FechaHoraProgLlegada'];
                    $atrs = mf_atributos_cartaporte($datos['Ubicaciones'][$idx]['Destino'], $atr_opcionales, 'CartaPorte.Ubicacion.Destino');
                    // $atrs = mf_atributos_nodo($datos['Ubicaciones'][$idx]['Destino'], 'CartaPorte.Ubicacion.Destino');
                
                    $xml .= "<cartaporte:Destino $atrs>";
                    $xml .= "</cartaporte:Destino>";
                }
                if(isset($datos['Ubicaciones'][$idx]['Domicilio']))
                {
                    $atr_opcionales = ['NumeroExterior', 'NumeroInterior', 'Colonia', 'Localidad', 'Referencia', 'Municipio', 'NombreEstacion', 'NavegacionTrafico', 'FechaHoraProgLlegada'];
                    $atrs = mf_atributos_cartaporte($datos['Ubicaciones'][$idx]['Domicilio'], $atr_opcionales, 'CartaPorte.Ubicacion.Domicilio');
                    // $atrs = mf_atributos_nodo($datos['Ubicaciones'][$idx]['Domicilio'], 'CartaPorte.Ubicacion.Domicilio');

                    $xml .= "<cartaporte:Domicilio $atrs>";
                    $xml .= "</cartaporte:Domicilio>";
                }
                $xml .= "</cartaporte:Ubicacion>";
            }
        }
        $xml .= "</cartaporte:Ubicaciones>";
    }
    if(isset($datos['Mercancias']))
    {
        $atr_opcionales = ['PesoBrutoTotal', 'UnidadPeso', 'PesoNetoTotal', 'CargoPorTasacion'];
        $atrs = mf_atributos_cartaporte($datos['Mercancias'], $atr_opcionales, 'CartaPorte.Mercancias');
        // $atrs = mf_atributos_nodo($datos['Mercancias'], 'CartaPorte.Mercancias');

        $xml .= "<cartaporte:Mercancias $atrs>";
        foreach($datos['Mercancias'] as $idx => $entidad)
        {
            if(is_array($datos['Mercancias'][$idx]) && is_int($idx))
            {
                $atr_opcionales = ['PesoBrutoTotal', 'UnidadPeso', 'PesoNetoTotal', 'CargoPorTasacion'];
                $atrs = mf_atributos_cartaporte($datos['Mercancias'][$idx], $atr_opcionales, 'CartaPorte.Mercancia');
                // $atrs = mf_atributos_nodo($datos['Mercancias'][$idx], 'CartaPorte.Mercancia');

                $xml .= "<cartaporte:Mercancia $atrs >";
                if(isset($datos['Mercancias'][$idx]['CantidadTransporta']))
                {
                    foreach($datos['Mercancias'][$idx]['CantidadTransporta']  as $idx2 => $entidad2)
                    {
                        $atrs = mf_atributos_nodo($entidad2);
                        // $atrs = mf_atributos_nodo($entidad2, 'CartaPorte.Mercancia.CantidadTransporta');
                        $xml .= "<cartaporte:CantidadTransporta $atrs>";
                        $xml .= "</cartaporte:CantidadTransporta>";
                    }
                }
                if(isset($datos['Mercancias'][$idx]['DetalleMercancia']))
                {
                    $atrs = mf_atributos_nodo($datos['Mercancias'][$idx]['DetalleMercancia']);
                    // $atrs = mf_atributos_nodo($datos['Mercancias'][$idx]['DetalleMercancia'], 'CartaPorte.Mercancia.DetalleMercancia');
                    $xml .= "<cartaporte:DetalleMercancia $atrs>";
                    $xml .= "</cartaporte:DetalleMercancia>";
                }
                $xml .= "</cartaporte:Mercancia>";
            }
        }
        if(isset($datos['Mercancias']['AutotransporteFederal']))
		{
			$atrs = mf_atributos_nodo($datos['Mercancias']['AutotransporteFederal']);
            // $atrs = mf_atributos_nodo($datos['Mercancias']['AutotransporteFederal'], 'CartaPorte.Mercancia.AutotransporteFederal');
			$xml .= "<cartaporte:AutotransporteFederal $atrs>";
			if(isset($datos['Mercancias']['AutotransporteFederal']['IdentificacionVehicular']))
			{
				$atrs = mf_atributos_nodo($datos['Mercancias']['AutotransporteFederal']['IdentificacionVehicular']);
                // $atrs = mf_atributos_nodo($datos['Mercancias']['AutotransporteFederal']['IdentificacionVehicular']);
				$xml .= "<cartaporte:IdentificacionVehicular $atrs>";
				$xml .= "</cartaporte:IdentificacionVehicular>";
			}
            if(isset($datos['Mercancias']['AutotransporteFederal']['Remolques']))
			{
				$atrs = mf_atributos_nodo($datos['Mercancias']['AutotransporteFederal']['Remolques']);
                // $atrs = mf_atributos_nodo($datos['Mercancias']['AutotransporteFederal']['Remolques']);
				$xml .= "<cartaporte:Remolques $atrs>";

                if(isset($datos['Mercancias']['AutotransporteFederal']['Remolques']['Remolque']))
                {
                    foreach($datos['Mercancias']['AutotransporteFederal']['Remolques']['Remolque']  as $idx2 => $entidad2)
                    {
                        $atrs2 = mf_atributos_nodo($entidad2);
                        // $atrs2 = mf_atributos_nodo($entidad2, 'CartaPorte.Remolque');
                        $xml .= "<cartaporte:Remolque $atrs2>";
                        $xml .= "</cartaporte:Remolque>";
                    }
                }
                $xml .= "</cartaporte:Remolques>";
			}
            $xml .= "</cartaporte:AutotransporteFederal>";
		}
        if(isset($datos['Mercancias']['TransporteMaritimo']))
		{
			$atrs = mf_atributos_nodo($datos['Mercancias']['TransporteMaritimo']);
            // $atrs = mf_atributos_nodo($datos['Mercancias']['TransporteMaritimo']);
			$xml .= "<cartaporte:TransporteMaritimo $atrs>";
            if(isset($datos['Mercancias']['TransporteMaritimo']['Contenedor']))
			{
				//$atrs = mf_atributos_nodo($datos['Mercancias']['TransporteMaritimo']['Contenedor']);
				//$xml .= "<cartaporte:Contenedor $atrs>";
				foreach($datos['Mercancias']['TransporteMaritimo']['Contenedor'] as $idx3 => $entidad3)
                {
                    $atrs = mf_atributos_nodo($entidad3);
                    // $atrs = mf_atributos_nodo($entidad3);
                    $xml .= "<cartaporte:Contenedor $atrs>";
                    $xml .= "</cartaporte:Contenedor>";
                }
                //$xml .= "</cartaporte:Contenedor>";
			}
            $xml .= "</cartaporte:TransporteMaritimo>";
		}  
        if(isset($datos['Mercancias']['TransporteAereo']))
		{
			$atrs = mf_atributos_nodo($datos['Mercancias']['TransporteAereo']);
            // $atrs = mf_atributos_nodo($datos['Mercancias']['TransporteAereo']);
			$xml .= "<cartaporte:TransporteAereo $atrs>";
			$xml .= "</cartaporte:TransporteAereo>";
		}
        if(isset($datos['Mercancias']['TransporteFerroviario']))
		{
			$atrs = mf_atributos_nodo($datos['Mercancias']['TransporteFerroviario']);
            // $atrs = mf_atributos_nodo($datos['Mercancias']['TransporteFerroviario']);
			$xml .= "<cartaporte:TransporteFerroviario $atrs>";
            if(isset($datos['Mercancias']['TransporteFerroviario']['DerechosDePaso']))
			{
				//$atrs = mf_atributos_nodo($datos['Mercancias']['TransporteMaritimo']['Contenedor']);
				//$xml .= "<cartaporte:Contenedor $atrs>";
				foreach($datos['Mercancias']['TransporteFerroviario']['DerechosDePaso'] as $idx3 => $entidad3)
                {
                    $atrs = mf_atributos_nodo($entidad3);
                    // $atrs = mf_atributos_nodo($entidad3);
                    $xml .= "<cartaporte:DerechosDePaso $atrs>";
                    $xml .= "</cartaporte:DerechosDePaso>";
                }
                //$xml .= "</cartaporte:Contenedor>";
			}
            if(isset($datos['Mercancias']['TransporteFerroviario']['Carro']))
			{
				//$atrs = mf_atributos_nodo($datos['Mercancias']['TransporteFerroviario']['Carro']);
				//$xml .= "<cartaporte:Carro $atrs>";
				foreach($datos['Mercancias']['TransporteFerroviario']['Carro'] as $idx4 => $entidad4)
                {
                    $atrs = mf_atributos_nodo($entidad4);
                    // $atrs = mf_atributos_nodo($entidad4);
                    $xml .= "<cartaporte:Carro $atrs>";
                    foreach($datos['Mercancias']['TransporteFerroviario']['Carro'][$idx4]['Contenedor'] as $idx5 => $entidad5)
                    {
                        $atrs = mf_atributos_nodo($entidad5);
                        // $atrs = mf_atributos_nodo($entidad5);
                        $xml .= "<cartaporte:Contenedor $atrs>";
                        $xml .= "</cartaporte:Contenedor>";
                    }
                    $xml .= "</cartaporte:Carro>";
                }
                //$xml .= "</cartaporte:Carro>";
			}
            $xml .= "</cartaporte:TransporteFerroviario>";
		}
        $xml .= "</cartaporte:Mercancias>";
    }
    if(isset($datos['FiguraTransporte']))
    {
        $atrs = mf_atributos_nodo($datos['FiguraTransporte']);
        // $atrs = mf_atributos_nodo($datos['FiguraTransporte'], 'CartaPorte.FiguraTransporte');
        $xml .= "<cartaporte:FiguraTransporte $atrs>";
        if(isset($datos['FiguraTransporte']['Operadores']))
		{
			$atrs = mf_atributos_nodo($datos['FiguraTransporte']['Operadores']);
            // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Operadores']);
			$xml .= "<cartaporte:Operadores $atrs>";
            foreach($datos['FiguraTransporte']['Operadores'] as $idx6 => $entidad6)
            {
                $atrs = mf_atributos_nodo($entidad6);
                // $atrs = mf_atributos_nodo($entidad6);
                $xml .= "<cartaporte:Operador $atrs>";
                if(isset($datos['FiguraTransporte']['Operadores'][$idx6]['Domicilio']))
                {
                    $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Operadores'][$idx6]['Domicilio']);
                    // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Operadores'][$idx6]['Domicilio']);
                    $xml .= "<cartaporte:Domicilio $atrs>";
                    $xml .= "</cartaporte:Domicilio>";
                }
                $xml .= "</cartaporte:Operador>";
            }
            $xml .= "</cartaporte:Operadores>";
        }
        if(isset($datos['FiguraTransporte']['Propietario']))
		{
            foreach($datos['FiguraTransporte']['Propietario'] as $idx7 => $entidad7)
            {
                $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Propietario'][$idx7]);
                // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Propietario'][$idx7]);
                $xml .= "<cartaporte:Propietario $atrs>";

                if(isset($datos['FiguraTransporte']['Propietario'][$idx7]['Domicilio']))
                {
                    $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Propietario'][$idx7]['Domicilio']);
                    // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Propietario'][$idx7]['Domicilio']);
                    $xml .= "<cartaporte:Domicilio $atrs>";
                    $xml .= "</cartaporte:Domicilio>";
                }
                $xml .= "</cartaporte:Propietario>";
            }
        }
        if(isset($datos['FiguraTransporte']['Arrendatario']))
		{
            foreach($datos['FiguraTransporte']['Arrendatario'] as $idx7 => $entidad7)
            {
                $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Arrendatario'][$idx7]);
                // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Arrendatario'][$idx7]);
	            $xml .= "<cartaporte:Arrendatario $atrs>";

                if(isset($datos['FiguraTransporte']['Arrendatario'][$idx7]['Domicilio']))
                {
                    $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Arrendatario'][$idx7]['Domicilio']);
                    // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Arrendatario'][$idx7]['Domicilio']);
                    $xml .= "<cartaporte:Domicilio $atrs>";
                    $xml .= "</cartaporte:Domicilio>";
                }
                $xml .= "</cartaporte:Arrendatario>";
            }
        }
        if(isset($datos['FiguraTransporte']['Notificado']))
		{
            foreach($datos['FiguraTransporte']['Notificado'] as $idx7 => $entidad7)
            {
                $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Notificado'][$idx7]);
                // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Notificado'][$idx7]);
			    $xml .= "<cartaporte:Notificado $atrs>";

                if(isset($datos['FiguraTransporte']['Notificado'][$idx7]['Domicilio']))
                {
                    $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Notificado'][$idx7]['Domicilio']);
                    // $atrs = mf_atributos_nodo($datos['FiguraTransporte']['Notificado'][$idx7]['Domicilio']);
                    $xml .= "<cartaporte:Domicilio $atrs>";
                    $xml .= "</cartaporte:Domicilio>";
                }
                $xml .= "</cartaporte:Notificado>";
            }
        }
        $xml .= "</cartaporte:FiguraTransporte>";
    }
    $xml .= "</cartaporte:CartaPorte>";


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
