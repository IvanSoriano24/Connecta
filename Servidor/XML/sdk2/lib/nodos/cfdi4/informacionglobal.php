<?php

function mf_nodo_informacionglobal($datos)
{
    global $__mf_constantes__;
    if($__mf_constantes__['__MF_VERSION_CFDI__'] == '4.0')
    {
        $atributos = mf_atributos_nodo($datos, 'InformacionGlobal');
        $InformacionGlobal = "<cfdi:InformacionGlobal $atributos/>";
    }
    return $InformacionGlobal;
}