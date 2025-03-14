<?php

function mf_complemento_pagos20($datos)
{
	// Variable para los namespaces xml
	global $__mf_namespaces__;
	$__mf_namespaces__['pago20']['uri'] = 'http://www.sat.gob.mx/Pagos20';
	$__mf_namespaces__['pago20']['xsd'] = 'http://www.sat.gob.mx/sitio_internet/cfd/Pagos/Pagos20.xsd';

	$atrs = mf_atributos_nodo($datos);
    $xml = "<pago20:Pagos Version='2.0' $atrs>";
    //totales   
    if(isset($datos['Totales']))
    {
        if(is_array($datos['Totales']))
		{
            $atrs = mf_atributos_nodo($datos['Totales']);
            $xml .= "<pago20:Totales $atrs />";
        }
    }
    //pagos y doc relacioneados
	if(isset($datos['Pagos']))
    {
        foreach($datos['Pagos'] as $idx =>$entidad)
		{
            if(is_array($datos['Pagos'][$idx]))
			{
				$atrs = mf_atributos_nodo($datos['Pagos'][$idx]);
				$xml .= "<pago20:Pago $atrs >";
				
				if(isset($entidad['DoctoRelacionado']))
				{
				    foreach($entidad['DoctoRelacionado'] as $idx2 => $entidad2)
					{
						if(is_array($entidad['DoctoRelacionado'][$idx2]))
						{
							if(!is_array($entidad['DoctoRelacionado'][$idx2]['ImpuestosDR']))
    						{
                                $atrs = mf_atributos_nodo($entidad2);
                                $xml.= "<pago20:DoctoRelacionado $atrs/>";
    						}else{
                            
                                $atrs = mf_atributos_nodo($entidad2);
    							$xml.= "<pago20:DoctoRelacionado $atrs>";
    						
                                //impuestos del documento relacionados
                                if(is_array($entidad['DoctoRelacionado'][$idx2]['ImpuestosDR']))
        						{
                                    $xml .= "<pago20:ImpuestosDR>";
                                    
                                    if(is_array($entidad['DoctoRelacionado'][$idx2]['ImpuestosDR']['RetencionDR']))
            						{
            						    $xml .= "<pago20:RetencionesDR>"; 
                                        foreach($entidad['DoctoRelacionado'][$idx2]['ImpuestosDR']['RetencionDR'] as $idx3 => $entidad3)
                                        {
                                            $atrs = mf_atributos_nodo($entidad3);
            			                    $xml .= "<pago20:RetencionDR $atrs/>";
                                        }
            							$xml .= "</pago20:RetencionesDR>"; 
            						}
                                    if(is_array($entidad['DoctoRelacionado'][$idx2]['ImpuestosDR']['TrasladoDR']))
            						{
            						    $xml .= "<pago20:TrasladosDR>"; 
                                        foreach($entidad['DoctoRelacionado'][$idx2]['ImpuestosDR']['TrasladoDR'] as $idx4 => $entidad4)
                                        {
                                            $atrs = mf_atributos_nodo($entidad4);
            			                    $xml .= "<pago20:TrasladoDR $atrs/>";
                                        }
            							$xml .= "</pago20:TrasladosDR>"; 
            						}
                                     
                                    $xml .= "</pago20:ImpuestosDR>";
                                  
    						    }
                                $xml.= "</pago20:DoctoRelacionado>";
                            
                        }
                        
                        }
                        
					}
				}
                
                if(isset($entidad['ImpuestosP']))
				{
                    $xml .= "<pago20:ImpuestosP>";
                    if(is_array($entidad['ImpuestosP']['RetencionesP']))
                    {
                        $xml .= "<pago20:RetencionesP>";
    					foreach($entidad['ImpuestosP']['RetencionesP'] as $idxRp =>$subentidadRp)
    					{
                            $atrs = mf_atributos_nodo($subentidadRp);
                            $xml .= "<pago20:RetencionP $atrs/>";
                        }   
                        $xml .= "</pago20:RetencionesP>";
                    }
                    if(is_array($entidad['ImpuestosP']['TrasladosP']))
                    {
                        $xml .= "<pago20:TrasladosP>";
    					foreach($entidad['ImpuestosP']['TrasladosP'] as $idxTp =>$subentidadTp)
    					{
                            $atrs = mf_atributos_nodo($subentidadTp);
                            $xml .= "<pago20:TrasladoP $atrs/>";
                        }   
                        $xml .= "</pago20:TrasladosP>";
                    }
							
				    $xml .= "</pago20:ImpuestosP>";
				}
                
				$xml .= "</pago20:Pago>";
			}	
		}
	}
 
    $xml .= "</pago20:Pagos>";
    return $xml;
}