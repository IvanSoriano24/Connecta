<?php
//error_reporting(E_ALL);
// <!-- phpDesigner :: Timestamp -->17/06/2016 12:34:32 p. m.<!-- /Timestamp -->
function ___fe_cafe($datos)
{
    include "imprime.php";
    
    //LEER EL XML PARA GENERAR EL QR
 

    
    
    $xml=$datos['rutaxml'];
    $titulo=$datos['titulo'];
    $tipo=$datos['tipo'];
    $path_logo=$datos['path_logo'];
    $notas=$datos['notas'];
    $color_marco=$datos['color_marco'];
    $color_marco_texto=$datos['color_marco_texto'];
    $color_texto=$datos['color_texto'];
    $fuente_texto=$datos['fuente_texto'];
    $html=imprime_factura($xml,$titulo,$tipo,$path_logo,$notas,$color_marco,$color_marco_texto,$color_texto,$fuente_texto);
    return $html;   
}



?>