<?php

function mf_nodo_gpedcomir(array $datos)
{
    $xml = '<gPedComIr>';
    // E151:Número del pedido de compra
    $xml .= crea_nodo_simple($datos, 'dNroPed');
    // E152:Número secuencial del ítem en el pedido
    $xml .= crea_nodo_simple($datos, 'dSecItemPed');
    // E159:Informaciones de interés del emitente con respeto al Pedido Comercial, relacionadas con un ítem de la factura
    $xml .= crea_nodo_simple($datos, 'dNroPed');
    $xml .= '</gPedComIr>';
    return $xml;
}