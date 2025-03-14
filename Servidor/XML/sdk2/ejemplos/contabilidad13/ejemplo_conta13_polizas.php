<?php
date_default_timezone_set('America/Mexico_City');

error_reporting(~(E_NOTICE|E_WARNING));

// Se incluye el archivo principal del SDK
include_once "../../sdk2.php";

// Se especifica que se usara el modulo de contabilidad 1.3
$datos['modulo'] = 'contabilidad13';

// Se especifica el tipo de documento
$datos['tipo'] = 'poliza';

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

/// Polizas ///
$datos['Polizas']['RFC'] = 'LAN7008173R5';
$datos['Polizas']['Mes'] = '07';
$datos['Polizas']['Anio'] = '2015';
$datos['Polizas']['TipoSolicitud'] = 'AF';
$datos['Polizas']['NumOrden'] = 'AAA0000000/00';

/// Polizas => Poliza ///
$datos['Polizas']['Poliza'][0]['NumUnIdenPol'] = '1968';
$datos['Polizas']['Poliza'][0]['Fecha'] = '2014-12-06';
$datos['Polizas']['Poliza'][0]['Concepto'] = 'Poliza de ingresos 1';

/// Polizas => Poliza => Transaccion ///
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['NumCta'] = '00010001';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['DesCta'] = 'XXXX';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Concepto'] = 'Venta de mercancia';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Debe'] = '0.00';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Haber'] = '400.50';

/// Polizas => Poliza => Transaccion => CompNal ///
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNal'][0]['UUID_CFDI'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNal'][0]['RFC'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNal'][0]['MontoTotal'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNal'][0]['Moneda'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNal'][0]['TipCamb'] = '123456';

/// Polizas => Poliza => Transaccion => CompNalOtr ///
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNalOtr'][0]['CFD_CBB_Serie'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNalOtr'][0]['CFD_CBB_NumFol'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNalOtr'][0]['RFC'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNalOtr'][0]['MontoTotal'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNalOtr'][0]['Moneda'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompNalOtr'][0]['TipCamb'] = '123456';

/// Polizas => Poliza => Transaccion => CompExt ///
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompExt'][0]['NumFactExt'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompExt'][0]['TaxID'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompExt'][0]['MontoTotal'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompExt'][0]['Moneda'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['CompExt'][0]['TipCamb'] = '123456';

/// Polizas => Poliza => Transaccion => Cheque ///
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['Num'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['BanEmisNal'] = '106';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['BanEmisExt'] = 'Banco Emisor Extranjero';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['CtaOri'] = '12345678910';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['Fecha'] = '2014-12-06';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['Benef'] = 'Empresa';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['RFC'] = 'AAA010101AAA';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['Monto'] = '200.50';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['Moneda'] = 'MXN';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Cheque'][0]['TipCamb'] = '1.0';

/// Polizas => Poliza => Transaccion => Transferencia ///
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['CtaOri'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['BancoOriNal'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['BancoOriExt'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['CtaDest'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['BancoDestNal'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['BancoDestExt'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['Fecha'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['Benef'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['RFC'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['Monto'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['Moneda'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['Transferencia'][0]['TipCamb'] = '123456';

/// Polizas => Poliza => Transaccion => OtrMetodoPago ///
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['OtrMetodoPago'][0]['MetPagoPol'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['OtrMetodoPago'][0]['Fecha'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['OtrMetodoPago'][0]['Benef'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['OtrMetodoPago'][0]['RFC'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['OtrMetodoPago'][0]['Monto'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['OtrMetodoPago'][0]['Moneda'] = '123456';
$datos['Polizas']['Poliza'][0]['Transaccion'][0]['OtrMetodoPago'][0]['TipCamb'] = '123456';

$res = mf_ejecuta_modulo($datos);

print_r($res);

