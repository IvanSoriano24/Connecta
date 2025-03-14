<?php
date_default_timezone_set('America/Mexico_City');

error_reporting(~(E_NOTICE|E_WARNING));

// Se incluye el archivo principal del SDK
include_once "../../sdk2.php";

// Se especifica que se usara el modulo de contabilidad 1.3
$datos['modulo'] = 'contabilidad13';

// Se especifica el tipo de documento
$datos['tipo'] = 'catalogo';

// Ruta donde se guardara el archivo xml y el zip
$datos['ruta_archivo']='../../timbrados';

// Credenciales de MultiFacturas
$datos['PAC']['usuario'] = 'DEMO700101XXX';
$datos['PAC']['pass'] = 'DEMO700101XXX';
$datos['PAC']['produccion'] = 'NO'; //   [SI|NO] SIEMPRE EN MAYUSCULAS

// Ruta de los certificados
$datos['conf']['cer'] = '../../certificados/lan7008173r5.cer.pem';
$datos['conf']['key'] = '../../certificados/lan7008173r5.key.pem';
$datos['conf']['pass'] = '12345678a';

// == Catalogo == 
$datos['Catalogo']['RFC'] = 'FJC780315E91';
$datos['Catalogo']['Mes'] = '01';
$datos['Catalogo']['Anio'] = '2015';

// == Cuentas ==
$datos['Catalogo']['Ctas'][0]['CodAgrup'] = '000';
$datos['Catalogo']['Ctas'][0]['NumCta'] = '0.00';
$datos['Catalogo']['Ctas'][0]['Desc'] = '1000.00';
$datos['Catalogo']['Ctas'][0]['SubCtaDe'] = '990.00';
$datos['Catalogo']['Ctas'][0]['Nivel'] = '1';
$datos['Catalogo']['Ctas'][0]['Natur'] = 'D';

$datos['Catalogo']['Ctas'][1]['CodAgrup'] = '000';
$datos['Catalogo']['Ctas'][1]['NumCta'] = '0.00';
$datos['Catalogo']['Ctas'][1]['Desc'] = '1000.00';
$datos['Catalogo']['Ctas'][1]['SubCtaDe'] = '990.00';
$datos['Catalogo']['Ctas'][1]['Nivel'] = '1';
$datos['Catalogo']['Ctas'][1]['Natur'] = 'A';

// Se ejecuta el SDK
$res = mf_ejecuta_modulo($datos);

print_r($res);