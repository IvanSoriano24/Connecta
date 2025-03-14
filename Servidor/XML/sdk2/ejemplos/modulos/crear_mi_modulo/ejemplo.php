<?php
/**
 * La funcion siempre debe comenzar con tres guiones bajo y el nombre del mismo archivo PHP
 * SIN extension, y recibir una variable; esta variable puede tener el nombre que se desee.
 */

function ___ejemplo($datos)
{
    // Se extraen los datos necesarios del parametro de entrada
    $num1=$datos['num1'];
    $num2=$datos['num2'];
    
    // Se procesa la informacion recibida y/o ejecutan las operaciones pertinentes
    $resultado = $num1 + $num2;
    
    /*
     * Siempre se debe de regresar un arreglo asociativo, es decir que los datos que se
     * pretendan devolver siempre se puedan identificar/localizar por medio de una cadena
     */
    return array('resultado' => $resultado);
}