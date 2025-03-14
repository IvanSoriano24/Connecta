<?php

//ESTANDAR DEL COMPLEMENTO DE CARTA PORTE http://omawww.sat.gob.mx/tramitesyservicios/Paginas/documentos/cartaporte30.pdf
// Se desactivan los mensajes de debug
error_reporting(~(E_WARNING|E_NOTICE));
//error_reporting(E_ALL);
// Se especifica la zona horaria
date_default_timezone_set('America/Mexico_City');
date_default_timezone_set('America/Cancun');

// Se incluye el SDK
require_once '../../sdk2.php';

// Se especifica la version de CFDi 3.3
$datos['url'] = 'https://pac1.multifacturas.com/pac/timbrar.php?wsdl'; 
//$datos['url'] = 'http://55.cfdi.red/pac/timbrar.php?wsdl'; 
$datos['complemento'] = 'cartaporte30';
$datos['version_cfdi'] = '4.0';
$datos['validacion_local'] = 'NO'; 
// Ruta del XML Timbrado
$datos['cfdi']='../../timbrados/cfdi_ejemplo_factura_carta_porte30_autotransporte_ingreso.xml';

// Ruta del XML de Debug
$datos['xml_debug']='../../timbrados/sin_timbrar_ejemplo_factura_carta_porte30_autotransporte_ingreso.xml';

// Credenciales de Timbrado
$datos['PAC']['usuario'] = 'DEMO700101XXX';
$datos['PAC']['pass'] = 'DEMO700101XXX';
$datos['PAC']['produccion'] = 'NO';

// Rutas y clave de los CSD
$datos['conf']['cer'] = '../../certificados/EKU9003173C9.cer.pem';
$datos['conf']['key'] = '../../certificados/EKU9003173C9.key.pem';
$datos['conf']['pass'] = '12345678a';

// Datos de la Factura
$datos['factura']['serie'] = 'A';
$datos['factura']['folio'] = '659155';
$datos['factura']['fecha_expedicion'] = date('Y-m-d\TH:i:s', time() - 120);
$datos['factura']['forma_pago'] = '01';
$datos['factura']['subtotal'] = 100.00;
$datos['factura']['moneda'] = 'MXN';
$datos['factura']['tipocambio'] = 1;
$datos['factura']['total'] = 100.00;
$datos['factura']['tipocomprobante'] = 'I';
$datos['factura']['metodo_pago'] = 'PUE';
$datos['factura']['LugarExpedicion'] = '77734';
$datos['factura']['Exportacion'] = '01';

// Datos del Emisor
$datos['emisor']['rfc'] = 'EKU9003173C9'; //RFC DE PRUEBA
$datos['emisor']['nombre'] = 'ESCUELA KEMPER URGATE';  // EMPRESA DE PRUEBA+
$datos['emisor']['RegimenFiscal'] = '601';

// Datos del Receptor
$datos['receptor']['rfc'] = 'EKU9003173C9';
$datos['receptor']['nombre'] = 'ESCUELA KEMPER URGATE';
$datos['receptor']['UsoCFDI'] = 'S01';
$datos['receptor']['DomicilioFiscalReceptor'] = '42501';
$datos['receptor']['RegimenFiscalReceptor'] = '601';

// Se agregan los conceptos
$datos['conceptos'][0]['cantidad'] = 1;
$datos['conceptos'][0]['unidad'] = 'Pieza';
$datos['conceptos'][0]['ID'] = "ABCD123456789";
$datos['conceptos'][0]['descripcion'] = "FLETE";
$datos['conceptos'][0]['valorunitario'] = 100.00;
$datos['conceptos'][0]['importe'] = 100.00;
$datos['conceptos'][0]['ClaveProdServ'] = '78101802';
$datos['conceptos'][0]['ClaveUnidad'] = 'H87';
$datos['conceptos'][0]['ObjetoImp'] = '01';

// Complemento carta porte 3.0
$datos['cartaporte30']['atrs']['IdCCP']='CCCBCD94-870A-4332-A52A-A52AA52AA52A';
$datos['cartaporte30']['atrs']['TranspInternac']='No';
$datos['cartaporte30']['atrs']['TotalDistRec']='1';
$datos['cartaporte30']['atrs']['RegistroISTMO']='S�';
$datos['cartaporte30']['atrs']['UbicacionPoloOrigen']='01';
$datos['cartaporte30']['atrs']['UbicacionPoloDestino']='01';

////////  UBICACION 0
$datos['cartaporte30']['Ubicacion'][0]['atrs']['IDUbicacion'] = 'OR101010'; //Atributo condicional para registrar una clave que sirva para identificar el punto de salida o entrada de los bienes y/o mercancï¿½as que se trasladan a travï¿½s de los distintos medios de transporte, la cual estarï¿½ integrada de la siguiente forma: para origen el acrï¿½nimo ï¿½ORï¿½ o para destino el acrï¿½nimo ï¿½DEï¿½ seguido de 6 dï¿½gitos numï¿½ricos asignados por el contribuyente que emite el comprobante para su identificaciï¿½n.
$datos['cartaporte30']['Ubicacion'][0]['atrs']['TipoUbicacion'] = 'Origen'; //Atributo requerido para precisar si el tipo de ubicaciï¿½n corresponde al origen o destino de las ubicaciones para el traslado de los bienes y/o mercancï¿½as en los distintos medios de transporte.
$datos['cartaporte30']['Ubicacion'][0]['atrs']['RFCRemitenteDestinatario'] = 'EKU9003173C9'; //Atributo requerido para registrar el RFC del remitente o destinatario de los bienes y/o mercancï¿½as que se trasladan a travï¿½s de los distintos medios de transporte.
$datos['cartaporte30']['Ubicacion'][0]['atrs']['NombreRemitenteDestinatario'] = 'SEGUROS BANORTE SA DE CV GRUPO FINANCIERO BANORTE'; 
$datos['cartaporte30']['Ubicacion'][0]['atrs']['FechaHoraSalidaLlegada'] = '2021-11-01T00:00:00'; //Atributo requerido para registrar la fecha y hora estimada en la que salen o llegan los bienes y/o mercancï¿½as de origen o al destino, respectivamente. Se expresa en la forma AAAA-MM-DDThh:mm:ss.

$datos['cartaporte30']['Ubicacion'][0]['domicilio']['Referencia'] = 'casa blanca 1'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['Pais'] = 'MEX'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['NumeroInterior'] = '212'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['NumeroExterior'] = '211'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['Municipio'] = '011'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['Localidad'] = '13'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['Estado'] = 'CMX'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['Colonia'] = '1957'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['CodigoPostal'] = '13250'; //Atributo 
$datos['cartaporte30']['Ubicacion'][0]['domicilio']['Calle'] = 'Calle1'; //Atributo 

////////  UBICACION 1
$datos['cartaporte30']['Ubicacion'][1]['atrs']['IDUbicacion'] = 'DE202020'; //Atributo condicional para registrar una clave que sirva para identificar el punto de salida o entrada de los bienes y/o mercancï¿½as que se trasladan a travï¿½s de los distintos medios de transporte, la cual estarï¿½ integrada de la siguiente forma: para origen el acrï¿½nimo ï¿½ORï¿½ o para destino el acrï¿½nimo ï¿½DEï¿½ seguido de 6 dï¿½gitos numï¿½ricos asignados por el contribuyente que emite el comprobante para su identificaciï¿½n.
$datos['cartaporte30']['Ubicacion'][1]['atrs']['TipoUbicacion'] = 'Destino'; //Atributo requerido para precisar si el tipo de ubicaciï¿½n corresponde al origen o destino de las ubicaciones para el traslado de los bienes y/o mercancï¿½as en los distintos medios de transporte.
$datos['cartaporte30']['Ubicacion'][1]['atrs']['RFCRemitenteDestinatario'] = 'EKU9003173C9'; //Atributo requerido para registrar el RFC del remitente o destinatario de los bienes y/o mercancï¿½as que se trasladan a travï¿½s de los distintos medios de transporte.
$datos['cartaporte30']['Ubicacion'][1]['atrs']['FechaHoraSalidaLlegada'] = '2021-11-01T01:00:00'; //Atributo requerido para registrar la fecha y hora estimada en la que salen o llegan los bienes y/o mercancï¿½as de origen o al destino, respectivamente. Se expresa en la forma AAAA-MM-DDThh:mm:ss.
$datos['cartaporte30']['Ubicacion'][1]['atrs']['DistanciaRecorrida'] = '1'; //Atributo condicional para registrar en kilï¿½metros la distancia recorrida entre la ubicaciï¿½n de origen y la de destino parcial o final, por los distintos medios de transporte que trasladan los bienes y/o mercancï¿½as.

$datos['cartaporte30']['Ubicacion'][1]['domicilio']['Referencia'] = 'casa blanca 2'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['Pais'] = 'MEX'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['NumeroInterior'] = '215'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['NumeroExterior'] = '214'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['Municipio'] = '004'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['Localidad'] = '23'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['Estado'] = 'COA'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['Colonia'] = '0347'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['CodigoPostal'] = '25350'; //Atributo 
$datos['cartaporte30']['Ubicacion'][1]['domicilio']['Calle'] = 'CALLE'; //Atributo 


//MERCANCIAS DATOS GENERALES
$datos['cartaporte30']['Mercancias']['atrs']['PesoBrutoTotal']='1.0';
$datos['cartaporte30']['Mercancias']['atrs']['UnidadPeso']='XBX';
$datos['cartaporte30']['Mercancias']['atrs']['NumTotalMercancias']='1';
$datos['cartaporte30']['Mercancias']['atrs']['PesoNetoTotal']='1';
$datos['cartaporte30']['Mercancias']['atrs']['LogisticaInversaRecoleccionDevolucion']='S�';

////////  MERCANCIA 0
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['BienesTransp']='11121900';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['Descripcion']='Accesorios de equipo de telefon�a';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['Cantidad']='1.0';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['ClaveUnidad']='XBX';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['PesoEnKg']='1';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['DenominacionGenericaProd']='DenominacionGenericaProd1';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['DenominacionDistintivaProd']='DenominacionDistintivaProd1';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['Fabricante']='Fabricante';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['FechaCaducidad']= "2028-01-01";
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['CondicionesEspTransp']='01';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['RegistroSanitarioFolioAutorizacion']='0102040';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['MaterialPeligroso']="No";
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['SectorCOFEPRIS']="01";
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['LoteMedicamento']="LoteMedic1";
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['atrs']['FormaFarmaceutica']="01";

//Cantidad transporta
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['CantidadTransporta'][0]['IDOrigen']='OR101010';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['CantidadTransporta'][0]['IDDestino']='DE202020';
$datos['cartaporte30']['Mercancias'][0]['Mercancia']['CantidadTransporta'][0]['Cantidad']='1';


//Autotransporte 
// Este nodo no lo detecta en el xml, cuando lo timbre si dice que no esta el nodo de autotrasnporte.

$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['atrs']['PermSCT']='TPAF01';
$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['atrs']['NumPermisoSCT']='NumPermisoSCT';

$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['IdentificacionVehicular']['PlacaVM']='plac892';
$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['IdentificacionVehicular']['ConfigVehicular']='VL';
$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['IdentificacionVehicular']['AnioModeloVM']='2020';
$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['IdentificacionVehicular']['PesoBrutoVehicular']='1';

$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['Seguros']['PolizaRespCivil']='123456789';
$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['Seguros']['AseguraRespCivil']='SW Seguros';

$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['Remolque'][0]['SubTipoRem']='CTR021';
$datos['cartaporte30']['Mercancias'][0]['Autotransporte']['Remolque'][0]['Placa']='VL45K98';
 
$datos['cartaporte30']['FiguraTransporte']['TiposFigura'][0]['atrs']['TipoFigura']='01';
$datos['cartaporte30']['FiguraTransporte']['TiposFigura'][0]['atrs']['RFCFigura']='VAAM130719H60';
$datos['cartaporte30']['FiguraTransporte']['TiposFigura'][0]['atrs']['NumLicencia']='a234567890';
$datos['cartaporte30']['FiguraTransporte']['TiposFigura'][0]['atrs']['NombreFigura']='NombreFigura';

//echo "<pre>";print_r($datos);echo "</pre>";
// Se ejecuta el SDK
$res = mf_genera_cfdi4($datos);

///////////    MOSTRAR RESULTADOS DEL ARRAY $res   ///////////
 
echo "<h1>Respuesta Generar XML y Timbrado</h1>";
foreach($res AS $variable=>$valor)
{
    $valor=htmlentities($valor);
    $valor=str_replace('<br/>','<br/>',$valor);
    echo "<b>[$variable]=</b>$valor<hr>";
}
