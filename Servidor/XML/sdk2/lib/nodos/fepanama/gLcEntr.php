<?php

function mf_nodo_glcentr(array $datos)
{

    $xml = '<gLcEntr>';
    $xml .= crea_nodo_rama($datos, 'gRucLcEntr', array('dTipoRuc', 'dRuc', 'dDV'));
    $xml .= crea_nodo_simple($datos, 'dNombLcEntr');
    $xml .= crea_nodo_simple($datos, 'dDirecLcEntr');
	$xml .= crea_nodo_simple($datos, 'dTfnLcEntr');
	$xml .= crea_nodo_simple($datos, 'dTfnAdLcEntr');
    $xml .= crea_nodo_rama($datos, 'gUbiLcEntr', array('dCodUbi', 'dCorreg', 'dDistr', 'dProv'));
    $xml .= '</gLcEntr>';
    return $xml;
}