<?php
error_reporting(0); // OPCIONAL DESACTIVA NOTIFICACIONES DE DEBUG
date_default_timezone_set('America/Mexico_City');
// NO OLVIDAR ESTE INCLUDE
include_once "lib/cfdi32_multifacturas.php";

/*
 * Se indican las credenciales de MultiFacturas
 */
$datos['PAC']['usuario'] = 'DEMO700101XXX';
$datos['PAC']['pass'] = 'DEMO700101XXX';
$datos['PAC']['produccion'] = 'NO'; //   [SI|NO]

// Certificados
$datos['conf']['cer'] = 'pruebas/XIA190128J61.cer.pem';
$datos['conf']['key'] = 'pruebas/XIA190128J61.key.pem';
$datos['conf']['pass'] = '12345678a';

//RUTA DONDE ALMACENARA EL CFDI
$datos['cfdi']='timbrados/cfdi_ejemplo_factura_modulo.xml';

/*
 * Se especifica que se utilizara el modulo 'descargamasiva'
 */

$tasaIVA = 0.16;
$numConceptos = 10;
$valUnit = 100;
$importe = 100;
$subTotal = $numConceptos * $importe;
$total = $subTotal * (1 + $tasaIVA);
$totalTraslados = $total - $subTotal;

$datos['ruta_xml'] = 'ejemplo_generarxml.xml';

$datos['modulo'] = 'generaxml';
$datos['prefijos'] = array(
    'xsi' => array(
        'xmlns' => 'http://www.w3.org/2001/XMLSchema-instance',
        'schemaURI' => ''
    ),
	'cfdi' => array(
		'xmlns' => 'http://www.sat.gob.mx/cfd/3',
		'schemaURI' => 'http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd'
	),
    'nomina' => array(
        'xmlns' => 'http://www.sat.gob.mx/nomina',
        'schemaURI' => 'http://www.sat.gob.mx/sitio_internet/cfd/nomina/nomina11.xsd'
    )
);

// Datos del Comprobante
$datos['xml']['cfdi:Comprobante']['version'] = '3.2';
$datos['xml']['cfdi:Comprobante']['serie'] = 'A';
$datos['xml']['cfdi:Comprobante']['folio'] = '1234';
$datos['xml']['cfdi:Comprobante']['fecha'] = '2016-10-07T14:19:13';
$datos['xml']['cfdi:Comprobante']['formaDePago'] = 'UNA SOLA EXHIBICION';
$datos['xml']['cfdi:Comprobante']['subTotal'] = '100';
$datos['xml']['cfdi:Comprobante']['total'] = '116';
$datos['xml']['cfdi:Comprobante']['metodoDePago'] = '01';
$datos['xml']['cfdi:Comprobante']['tipoDeComprobante'] = 'ingreso';
$datos['xml']['cfdi:Comprobante']['LugarExpedicion'] = 'MONTERREY, NUEVO LEON';
$datos['xml']['cfdi:Comprobante']['Moneda'] = 'MXN';
$datos['xml']['cfdi:Comprobante']['TipoCambio'] = '1.0000';
$datos['xml']['cfdi:Comprobante']['descuento'] = '0.0000';

// Datos del Emisor
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['rfc'] = 'XIA190128J61';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['nombre'] = 'ACCEM SERVICIOS EMPRESARIALES SC';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['calle'] = 'JUAREZ';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['noExterior'] = '100';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['colonia'] = 'CENTRO';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['localidad'] = 'MONTERREY';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['municipio'] = 'MONTERREY';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['estado'] = 'NUEVO LEON';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['pais'] = 'MEXICO';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:DomicilioFiscal']['codigoPostal'] = '01234';
$datos['xml']['cfdi:Comprobante']['cfdi:Emisor']['cfdi:RegimenFiscal']['Regimen'] = 'REGIMEN DE INCORPORACION FISCAL';

// Datos del Receptor
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['rfc'] = 'SOHM7509289MA';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['nombre'] = 'MIGUEL ANGEL SOSA HERNANDEZ';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['calle'] = 'PERIFERICO';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['noExterior'] = '1024';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['colonia'] = 'SAN ANGEL';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['localidad'] = 'CIUDAD DE MÉXICO';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['municipio'] = 'ALVARO OBREGON';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['estado'] = 'DISTRITO FEDERAL';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['pais'] = 'MEXICO';
$datos['xml']['cfdi:Comprobante']['cfdi:Receptor']['cfdi:Domicilio']['codigoPostal'] = '23010';

// Datos de los Conceptos
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][0]['noIdentificacion'] = 'COD01';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][0]['cantidad'] = '1';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][0]['unidad'] = 'PIEZA';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][0]['descripcion'] = 'PRODUCTO PRUEBA 1';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][0]['valorUnitario'] = '100.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][0]['importe'] = '100.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][1]['noIdentificacion'] = 'COD02';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][1]['cantidad'] = '1';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][1]['unidad'] = 'PIEZA';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][1]['descripcion'] = 'PRODUCTO PRUEBA 2';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][1]['valorUnitario'] = '100.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'][1]['importe'] = '100.00';

// Datos de los Impuestos
$datos['xml']['cfdi:Comprobante']['cfdi:Impuestos']['totalImpuestosRetenidos'] = '0.0000';
$datos['xml']['cfdi:Comprobante']['cfdi:Impuestos']['totalImpuestosTrasladados'] = '10.0000';
$datos['xml']['cfdi:Comprobante']['cfdi:Impuestos']['cfdi:Traslados']['cfdi:Traslado']['impuesto'] = 'IVA';
$datos['xml']['cfdi:Comprobante']['cfdi:Impuestos']['cfdi:Traslados']['cfdi:Traslado']['tasa'] = '16';
$datos['xml']['cfdi:Comprobante']['cfdi:Impuestos']['cfdi:Traslados']['cfdi:Traslado']['importe'] = '160';

// Datos Nomina
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['Version'] = '1.1';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['Antiguedad'] = '52';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['CURP'] = 'DESO801116HGTLRS08';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['Departamento'] = 'ALMACEN';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['FechaFinalPago'] = '2013-12-13';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['FechaInicialPago'] = '2013-12-06';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['FechaInicioRelLaboral'] = '2012-12-13';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['FechaPago'] = '2013-12-13';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['NumDiasPagados'] = '5';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['NumEmpleado'] = '1040';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['NumSeguridadSocial'] = '12988020199';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['PeriodicidadPago'] = 'semanal';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['Puesto'] = 'JEFE DE ALMACEN';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['SalarioDiarioIntegrado'] = '52';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['RegistroPatronal'] = 'B471578365';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['RiesgoPuesto'] = '003';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['SalarioBaseCotApor'] = '89.58';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['SalarioDiarioIntegrado'] = '60.50';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['TipoContrato'] = 'Base';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['TipoJornada'] = 'Diurna';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['TipoRegimen'] = '001';

// Datos Percepciones
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['TotalExento'] = '0';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['TotalGravado'] = '2885.06';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][0]['Clave'] = '019';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][0]['Concepto'] = 'SUELDOS SEMANAL';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][0]['ImporteExento'] = '0.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][0]['ImporteGravado'] = '2404.22';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][0]['TipoPercepcion'] = '001';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][1]['Clave'] = '002';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][1]['Concepto'] = 'PREMIOS DE ASISTENCIA';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][1]['ImporteExento'] = '0.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][1]['ImporteGravado'] = '240.42';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Percepciones']['nomina:Percepcion'][1]['TipoPercepcion'] = '016';

// Datos Deducciones
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['TotalExento'] = '0';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['TotalGravado'] = '489.64';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][0]['Clave'] = '008';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][0]['Concepto'] = 'IMSS';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][0]['ImporteExento'] = '0.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][0]['ImporteGravado'] = '64.39';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][0]['TipoDeduccion'] = '001';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][1]['Clave'] = '012';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][1]['Concepto'] = 'INFONAVIT';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][1]['ImporteExento'] = '0.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][1]['ImporteGravado'] = '64.39';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][1]['TipoDeduccion'] = '005';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][2]['Clave'] = '008';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][2]['Concepto'] = 'ISR';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][2]['ImporteExento'] = '0.00';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][2]['ImporteGravado'] = '360.86';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Deducciones']['nomina:Deduccion'][2]['TipoDeduccion'] = '002';

// Datos Incapacidades
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Incapacidades']['nomina:Incapacidad']['Descuento'] = '1';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Incapacidades']['nomina:Incapacidad']['DiasIncapacidad'] = '1';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:Incapacidades']['nomina:Incapacidad']['TipoIncapacidad'] = '1';

// Datos Horas Extra
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:HorasExtras']['nomina:HorasExtra']['Dias'] = '2';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:HorasExtras']['nomina:HorasExtra']['HorasExtra'] = '33';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:HorasExtras']['nomina:HorasExtra']['ImportePagado'] = '1';
$datos['xml']['cfdi:Comprobante']['cfdi:Complemento']['nomina:Nomina']['nomina:HorasExtras']['nomina:HorasExtra']['TipoHoras'] = 'Dobles';

// Datos de la factura
/*$datos['factura']['LugarExpedicion'] = 'MONTERREY NUEVO LEÓN';
$datos['factura']['Moneda'] = 'MXN';
$datos['factura']['tipocambio'] = '1';
$datos['factura']['descuento'] = '0.0';
$datos['factura']['fecha'] = '2016-07-29T13:18:00';
$datos['factura']['folio'] = '100';
$datos['factura']['serie'] = 'A';
$datos['factura']['formaDePago'] = 'PAGO EN UNA SOLA EXHIBICION';
$datos['factura']['metodoDePago'] = '01';
$datos['factura']['subTotal'] = $subTotal;
$datos['factura']['tipoDeComprobante'] = 'ingreso';
$datos['factura']['total'] = $total;
$datos['factura']['version'] = '3.2';

// Datos del Emisor
$datos['factura']['cfdi:Emisor']['rfc'] = 'AAA010101AAA';
$datos['factura']['cfdi:Emisor']['nombre'] = 'ACCEM SERVICIOS EMPRESARIALES SC';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['calle'] = 'JUAREZ';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['codigoPostal'] = '00000';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['colonia'] = 'CENTRO';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['estado'] = 'NUEVO LEON';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['localidad'] = 'MONTERREY';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['municipio'] = 'MONTERREY';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['noExterior'] = '100';
$datos['factura']['cfdi:Emisor']['cfdi:DomicilioFiscal']['pais'] = 'MEXICO';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['calle'] = 'HIDALGO';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['codigoPostal'] = '00000';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['colonia'] = 'LAS CUMBRES 3 SECTOR';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['estado'] = 'NUEVO LEON';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['localidad'] = 'MONTERREY';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['municipio'] = 'MONTERREY';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['noExterior'] = '240';
$datos['factura']['cfdi:Emisor']['cfdi:ExpedidoEn']['pais'] = 'MEXICO';
$datos['factura']['cfdi:Emisor']['cfdi:RegimenFiscal']['regimen'] = 'MI REGIMEN';

// Datos del receptor
$datos['factura']['cfdi:Receptor']['nombre'] = 'MIGUEL ANGEL SOSA HERNANDEZ';
$datos['factura']['cfdi:Receptor']['rfc'] = 'AAA010101AAA';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['calle'] = 'PERIFERICO';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['codigoPostal'] = '00000';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['colonia'] = 'SAN ANGEL';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['estado'] = 'DISTRITO FEDERAL';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['localidad'] = 'CIUDAD DE MEXICO';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['municipio'] = 'ALVARO OBREGON';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['noExterior'] = '1024';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['noInterior'] = 'B';
$datos['factura']['cfdi:Receptor']['cfdi:Domicilio']['pais'] = 'MEXICO';

// Conceptos
for($i = 0; $i < $numConceptos; $i++) {
	$datos['factura']['cfdi:Conceptos'][$i]['cfdi:Concepto']['cantidad'] = 1;
	$datos['factura']['cfdi:Conceptos'][$i]['cfdi:Concepto']['descripcion'] = 'PRODUCTO DE PRUEBA ' . ($i + 1);
	$datos['factura']['cfdi:Conceptos'][$i]['cfdi:Concepto']['unidad'] = 'PIEZA';
	$datos['factura']['cfdi:Conceptos'][$i]['cfdi:Concepto']['noIdentificacion'] = 'COD' . ($i + 1);
	$datos['factura']['cfdi:Conceptos'][$i]['cfdi:Concepto']['valorUnitario'] = $valUnit;
	$datos['factura']['cfdi:Conceptos'][$i]['cfdi:Concepto']['importe'] = $importe;
}

// Impuestos
$datos['factura']['cfdi:Impuestos']['totalImpuestosTrasladados'] = $totalTraslados;
$datos['factura']['cfdi:Impuestos']['cfdi:Traslados']['cfdi:Traslado']['impuesto'] = 'IVA';
$datos['factura']['cfdi:Impuestos']['cfdi:Traslados']['cfdi:Traslado']['tasa'] = $tasaIVA * 100;
$datos['factura']['cfdi:Impuestos']['cfdi:Traslados']['cfdi:Traslado']['importe'] = $totalTraslados;*/

/*
 * Se obtiene la respuesta del modulo
 */
$res = cargar_modulo_multifacturas($datos);

// Se muestran los resultados
print_r($res);
