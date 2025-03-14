<?php

function mf_nodo_ginfolog(array $datos)
{
    $xml = '<gInfoLog>';
    $xml .= crea_nodo_simple($datos, 'dNroVols');
    $xml .= crea_nodo_simple($datos, 'dPesoTot');
    $xml .= crea_nodo_simple($datos, 'dUnPesoTot');
    $xml .= crea_nodo_simple($datos, 'dLicCamion');
    $xml .= crea_nodo_simple($datos, 'dNomTransp');
    $xml .= crea_nodo_rama($datos, 'gRucTransp', array('dTipoRuc', 'dRuc', 'dDV'));
    $xml .= crea_nodo_simple($datos, 'dInfEmLog');
    $xml .= '</gInfoLog>';
    return $xml;
}