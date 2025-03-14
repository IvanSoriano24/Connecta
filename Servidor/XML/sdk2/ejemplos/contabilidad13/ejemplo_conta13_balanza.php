<?php
date_default_timezone_set('America/Mexico_City');

error_reporting(~(E_NOTICE|E_WARNING));

// Se incluye el archivo principal del SDK
include_once "../../sdk2.php";

// Se especifica que se usara el modulo de contabilidad 1.3
$datos['modulo'] = 'contabilidad13';

// Se especifica el tipo de documento
$datos['tipo'] = 'balanza';

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

// == Balanza == 
$datos['Balanza']['RFC'] = 'FJC780315E91';
$datos['Balanza']['Mes'] = '01';
$datos['Balanza']['Anio'] = '2015';
$datos['Balanza']['TipoEnvio'] = 'N';
$datos['Balanza']['FechaModBal'] = '2017-01-01';

// == Cuentas ==
$datos['Balanza']['Ctas'][0]['NumCta'] = '1000';
$datos['Balanza']['Ctas'][0]['SaldoIni'] = '0.00';
$datos['Balanza']['Ctas'][0]['Debe'] = '1000.00';
$datos['Balanza']['Ctas'][0]['Haber'] = '990.00';
$datos['Balanza']['Ctas'][0]['SaldoFin'] = '10.00';

$datos['Balanza']['Ctas'][1]['NumCta'] = '1000';
$datos['Balanza']['Ctas'][1]['SaldoIni'] = '0.00';
$datos['Balanza']['Ctas'][1]['Debe'] = '1000.00';
$datos['Balanza']['Ctas'][1]['Haber'] = '990.00';
$datos['Balanza']['Ctas'][1]['SaldoFin'] = '10.00';

// Se ejecuta el SDK
$res = mf_ejecuta_modulo($datos);

print_r($res);