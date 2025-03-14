<?php

function mf_complemento_comercioexterior20($datos)
{
    // Variable para los namespaces xml
    global $__mf_namespaces__;
    $__mf_namespaces__['cce20']['uri'] = 'http://www.sat.gob.mx/ComercioExterior20';
    $__mf_namespaces__['cce20']['xsd'] = 'http://www.sat.gob.mx/sitio_internet/cfd/ComercioExterior20/ComercioExterior20.xsd';

    $atrs = mf_atributos_nodo($datos);
    $xml = "<cce20:ComercioExterior Version='2.0' $atrs>";
	
	if(isset($datos['Emisor']))
    {
        $atrsentidad = mf_atributos_nodo($datos['Emisor']);
        $xml .= "<cce20:Emisor $atrsentidad>";
        if(isset($datos['Emisor']['Domicilio']))
		{
			$atrs = mf_atributos_nodo($datos['Emisor']['Domicilio']);
			$xml.= "<cce20:Domicilio $atrs/>";
		}
        $xml.= "</cce20:Emisor>";
    }
	if(isset($datos['Propietario']))
    {
        $atrsentidad = mf_atributos_nodo($datos['Propietario']);
	    $xml .= "<cce20:Propietario $atrsentidad/>";
    }
    if(isset($datos['Receptor']))
    {
		$atrsentidad = mf_atributos_nodo($datos['Receptor']);
		$xml .= "<cce20:Receptor $atrsentidad>";
		if(isset($datos['Receptor']['Domicilio']))
		{
			$atrs = mf_atributos_nodo($datos['Receptor']['Domicilio']);
			$xml .= "<cce20:Domicilio $atrs/>";
		}
		$xml .= "</cce20:Receptor>";
    }
	if(isset($datos['Destinatario']))
    {
		foreach($datos['Destinatario'] as $idx =>$entidad)
		{
			if(is_array($datos['Destinatario'][$idx]))
			{
				$atrsentidad = mf_atributos_nodo($datos['Destinatario'][$idx]);
				$xml .= "<cce20:Destinatario $atrsentidad>";
				if(isset($datos['Destinatario'][$idx]['Domicilio']))
				{
					$atrs = mf_atributos_nodo($datos['Destinatario'][$idx]['Domicilio']);
					$xml .= "<cce20:Domicilio $atrs/>";
				}
				$xml .= "</cce20:Destinatario>";
			}
		}
    }
	if(isset($datos['Mercancias']))
    {
		$atrsentidad = mf_atributos_nodo($datos['Mercancias']);
		$xml .= "<cce20:Mercancias $atrsentidad>";
		foreach($datos['Mercancias'] as $idx =>$entidad)
		{
			if(is_array($datos['Mercancias'][$idx]))
			{
				$atrs = mf_atributos_nodo($datos['Mercancias'][$idx]);
				$xml .= "<cce20:Mercancia $atrs >";
				
				if(isset($datos['Mercancias'][$idx]['DescripcionesEspecificas']))
				{
					foreach($datos['Mercancias'][$idx]['DescripcionesEspecificas']  as $idx2 => $entidad2)
					{
						$atrs = mf_atributos_nodo($entidad2);
						$xml .= "<cce20:DescripcionesEspecificas $atrs/>";
					}
				}
				$xml .= "</cce20:Mercancia>";
			}	
		}
		$xml .= "</cce20:Mercancias>";
    }
	
    $xml .= "</cce20:ComercioExterior>";
    return $xml;
}
