<?php

function mf_nodo_gpedcomgl(array $datos)
{
    $xml = '<gPedComGl>';
    $xml .= crea_nodo_simple($datos, 'dNroPed');
    $xml .= crea_nodo_simple($datos, 'dNumAcept');
    $xml .= crea_nodo_simple($datos, 'dInfEmPedGl');
    $xml .= '</gPedComGl>';
    return $xml;
}