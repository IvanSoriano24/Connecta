<?php

function mf_nodo_gtot(array $datos)
{
    $xml = '<gTot>';

    // D02: Suma de los precios antes de impuesto
    $xml .= crea_nodo_simple($datos, 'dTotNeto');
    // D03: Total del ITBMS
    $xml .= crea_nodo_simple($datos, 'dTotITBMS');
    // D04: Total del ISC
    $xml .= crea_nodo_simple($datos, 'dTotISC');
    // D05: Suma total de monto gravado
    $xml .= crea_nodo_simple($datos, 'dTotGravado');
    // D06: Suma de los descuentos y bonificaciones concedidos sobre el valor total de la factura
    $xml .= crea_nodo_simple($datos, 'dTotDesc');
    // D07: Valor del acarreo cobrado en el precio total
    $xml .= crea_nodo_simple($datos, 'dTotAcar');
    // D08: Valor del seguro cobrado en el precio total
    $xml .= crea_nodo_simple($datos, 'dTotSeg');
    // D09: Valor total de la factura
    $xml .= crea_nodo_simple($datos, 'dVTot');
    // D10: Suma de los valores recibidos
    $xml .= crea_nodo_simple($datos, 'dTotRec');
    // D11: Vuelto entregado al cliente
    $xml .= crea_nodo_simple($datos, 'dVuelto');
    // D12: Tiempo de pago
    $xml .= crea_nodo_simple($datos, 'iPzPag');
    // D13: Número total de ítems de la factura
    $xml .= crea_nodo_simple($datos, 'dNroItems');
    // D14: Suma total de los ítems con los montos de los impuestos
    $xml .= crea_nodo_simple($datos, 'dVTotItems');

    // Definición de tipo para el grupo: D20: Grupo de datos de que describen descuentos o bonificaciones adicionales aplicados a la factura
    if(array_key_exists('gDescBonif', $datos))
    {
        foreach($datos['gDescBonif'] as $idx => $nodo)
        {
            $xml .= '<gDescBonif>';
            // D200: Descripción de descuentos o bonificaciones adicionales aplicados a la factura
            $xml .= crea_nodo_simple($nodo, 'dDetalDesc');
            // D201: Monto Descuentos/Bonificaciones y otros ajustes
            $xml .= crea_nodo_simple($nodo, 'dValDesc');
            $xml .= '</gDescBonif>';
        }
    }

    // Definición de tipo para el grupo: D30: Grupo de formas de pago de la factura
    if(array_key_exists('gFormaPago', $datos))
    {
        foreach ($datos['gFormaPago'] as $idx => $nodo)
        {
            $xml .= '<gFormaPago>';
            // D301: Forma de pago de la factura
            $xml .= crea_nodo_simple($nodo, 'iFormaPago');
            // D302: Descripción de forma de pago no listada en el formato
            $xml .= crea_nodo_simple($nodo, 'dFormaPagoDesc');
            // D303: Valor de la fracción pagada utilizando esta forma de pago
            $xml .= crea_nodo_simple($nodo, 'dVlrCuota');
            $xml .= '</gFormaPago>';
        }
    }

    // Definición de tipo para el grupo: D40: Grupo datos cuando a la factura aplican retenciones
    $xml .= crea_nodo_rama($datos, 'gRetenc', array('cCodRetenc', 'cValRetenc'));

    // Definición de tipo para el grupo: D50: Grupo de informaciones de pago a plazo
    if(array_key_exists('gPagPlazo', $datos))
    {
        foreach ($datos['gPagPlazo'] as $idx => $nodo)
        {
            $xml .= '<gPagPlazo>';
            // D501: Número secuencial de cada fracción de pago a plazo
            $xml .= crea_nodo_simple($nodo, 'dSecItem');
            // D502: Fecha de vencimiento de la fracción
            $xml .= crea_nodo_simple($nodo, 'dFecItPlazo');
            // D503: Valor de la fracción
            $xml .= crea_nodo_simple($nodo, 'dValItPlazo');
            // D504: Informaciones de interés del emitente con respeto a esta fracción de pago
            $xml .= crea_nodo_simple($nodo, 'dInfPagPlazo');
            $xml .= '</gPagPlazo>';
        }
    }
    
    // Definición de tipo para el grupo: D50: Grupo de informaciones de pago a plazo
    if(array_key_exists('gOTITotal', $datos))
    {
        foreach ($datos['gOTITotal'] as $idx => $nodo)
        {
            $xml .= '<gOTITotal>';
            
            $xml .= crea_nodo_simple($nodo, 'dCodOTITotal');
            
            $xml .= crea_nodo_simple($nodo, 'dValOTITotal');
            
            $xml .= '</gOTITotal>';
        }
    }

    $xml .= '</gTot>';
    return $xml;
}