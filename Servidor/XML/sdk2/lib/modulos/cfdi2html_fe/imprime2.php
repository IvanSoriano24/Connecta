<?php
//error_reporting(E_ALL);

function _inim_imprime_factura()
{

  
}


///////////////////////////////////////////////////////////////////////////////
function imprime_factura($xml_archivo,$titulo,$tipo_factura,$logo,$nota_impresa,$color_marco,$color_marco_texto,$color_texto,$fuente_texto)
{
    
    //FUNCION QUE REGRESA CSS -> CARLOS
    if($color_marco==''){
        $color_marco='black';
    }
    if($color_texto==''){
        $color_texto='black';
    }
    if($color_marco_texto==''){
        $color_marco_texto='white';
    }

    $css=html_css($color_marco,$color_marco_texto,$color_texto,$fuente_texto);
    $valor.="<head>$css</head>";
    
    if(file_exists($xml_archivo)==false)
    {
        return 'ERROR 1, NO EXISTE XML, MUY  POSIBLEMENTE ES UNA PRUEBA FALLIDA';
    }
    if(filesize($xml_archivo)<100)
    {
        return 'ERROR 2, XML INVALIDO';        
    }

//     $xml = simplexml_load_file($xml_archivo);

//  echo     $cadenaqr = $xml->gNoFirm->dQRCode;

 $xml = simplexml_load_file($xml_archivo);
    //$ns = $xml->getNamespaces(true);
    foreach($xml as $prefijo => $uri)
    {
    $xml->registerXPathNamespace($prefijo, $uri);
    }

    
/*
$valor.="
$cabecera
<div class=factura_detalles>
$desgloce
$nominas_txt
$nomina_general
$cancelado
</div>
$pie
$sellos_pie
$barcode_factura
$leyenda 
";
*/
 $valor.="$cabecera
 <div class=factura_detalles>
 $desgloce
 $nominas_txt
 $INE_general
 $HTML_PAGOS
 $nomina_general
 $html_comercion_exterior
 $cancelado
 </div>
 $pie
 $sellos_pie
 $barcode_factura
 $leyenda 
 ";


global $masheditor;
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' OR count($masheditor)==0) 
    {
        $valor=str_replace('{URL}/','',$valor);
    }

    return $valor;
}
///////////////////////////////////////////////////////////////////////////////
function autoformato_impresion_modulo($txt)
{
    //$txt=utf8_decode(utf8_decode($txt));
    $txt=utf8_decode($txt);
    return $txt;
}
///////////////////////////////////////////////////////////////////////////////

function object2array_modulo($object)
{
    $return = NULL;
      
    if(is_array($object))
    {
        foreach($object as $key => $value)
            $return[$key] = object2array($value);
    }
    else
    {
        $var = get_object_vars($object);
          
        if($var)
        {
            foreach($var as $key => $value)
                $return[$key] = ($key && !$value) ? NULL : object2array($value);
        }
        else return $object;
    }

    return $return;
} 


function XML2Array_modulo( $xml )
{
    $array = simplexml_load_string ( $xml );
    $newArray = array ( ) ;
    $array = ( array ) $array ;
    foreach ( $array as $key => $value )
    {
        $value = ( array ) $value ;
        $newArray [ $key] = $value [ 0 ] ;
    }
    $newArray = array_map("trim", $newArray);
  return $newArray ;
} 

class simple_xml_extended_modulo extends SimpleXMLElement
{
    public    function    Attribute($name)
    {
        foreach($this->Attributes() as $key=>$val)
        {
            if($key == $name)
                return (string)$val;
        }
    }

}

///////////////////////////////////////////////////////////////////////////////
function genera_pdf_modulo($idfactura,$ruta_pdf=NULL,$ruta_url=NULL)
{

    $idfactura=intval($idfactura);
    if($idfactura==0 )
    {
        
    }
    else
    {
        if($idfactura>0)
        {
            $sql="
            SELECT 
              multi_facturas.XML
            FROM
              multi_facturas
            WHERE
              (multi_facturas.idfactura = $idfactura)    
            ";
            if(function_exists('lee_sql_mash'))
                list($xml)=lee_sql_mash($sql);
        }
        
    }

    $pdf=str_replace('.xml','.pdf',$xml);
    if($ruta_pdf!=NULL)
        $pdf=$ruta_pdf;
    global $masheditor;
    $urlbase=$masheditor['url'];

//echo debug_mash($masheditor);
    $rfc=$_COOKIE["RFC"];
    $hora=time();
    $md5=md5("mash,$rfc,$hora,");
    $authpdf="$rfc,$hora,$md5";
    $carpeta_instalacion=$masheditor['carpeta_instalacion'];
    if($carpeta_instalacion!='')
        $carpeta_instalacion="$carpeta_instalacion/";
    $url="$urlbase/mt,46,1/idfacturahtml,$idfactura/impresion,si/?authpdf=$authpdf";

    if($ruta_url!=NULL)
    {
        

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
        {
            $url=$ruta_url;
        }
        else
        {
            $url=$ruta_url;
            if(function_exists('formato_url_mash'))
                $url=formato_url_mash($url);
            #$url="$url/?authpdf=$authpdf";        
        } 

    }
unlink("$carpeta_instalacion$pdf");

    $ruta='';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
    {
        $SO=$_SERVER['PROCESSOR_IDENTIFIER'];
        if(strpos("  $SO",'x86')>0)
        {
            //32bits
            //$ruta='c:\\cfdipdf\\32\\';
            $ruta='c:\\cfdipdf\\htdocs\\32\\';
        }
        else
        {
            //64 bits
            //$ruta='c:\\cfdipdf\\64\\';
            $ruta='c:\\cfdipdf\\htdocs\\32\\';
        }
    }
    if(file_exists("$carpeta_instalacion$pdf")==false)
    {
        $comando=$ruta."wkhtmltopdf  -s A4 -B 1 -T 1 -L 1 -R 1  \"$url\"  \"$carpeta_instalacion$pdf\"    "; //   -B 1 -T 1 -L 1 -R 1 -s A4 &        
    }

//echo $comando;

    $resultado=shell_exec($comando);
    $url=$masheditor['url'];
    $valor= ver_pdf_modulo("$url/$pdf");


    return $valor;
    
}
///////////////////////////////////////////////////////////////////////////////

function ver_pdf_modulo_modulo($pdf)
{   

    $hora=time();
    inicializa_jquery();
    $idrand=rand();
    $html="
    <div id='iddbme_pdf_$idrand'></div>
    <script>
        dbme_muestra_pdf($idrand,'$pdf');
    </script>
    
    ";


    return $html;
}

///////////////////////////////////////////////////////////////////////////////
function html_css($color_marco,$color_marco_texto,$color_texto,$fuente_texto)
{
$css="
<style type='text/css'> 
*{
/*    
    font-family: monospace !important; 
fantasy sans-serif
*/
    font-family: $fuente_texto;

    font-size: 12px !important;
    font-weight: bold !important;
    color: $color_texto;
}

.factura_cuadro{
    margin: 3px !important;
    padding: 3px !important;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px;
}

.factura_cuadro_linea{
    margin: 3px !important;
    padding: 3px !important;
    border-bottom-width: 1px;
    border-bottom-style: solid;
    
    
}

.factura_emisor{
    text-transform: uppercase !important;
    min-height: 80px;
}

.factura_expedidoen{
    text-transform: uppercase !important;
}
.factura_receptor{
    text-transform: uppercase !important;
    min-height: 80px;
}
.factura_datosgenerales{
    text-transform: uppercase !important;
    font-weight: bold;
    font-size: 14px;
    text-align: right;
}
.factura_sellos{
    word-wrap:break-word;
     
}
.factura_titulo_empresa{
    text-transform: uppercase !important;
    font-weight: bold;
    font-size: 14px !important;
}

.factura_titulo_ch{
    text-transform: uppercase !important;
    font-weight: bold;
    font-size: 13px !important;    
    
}

.factura_cbb{
    display: inline-block;
    width: auto;
    display: inline-block;
    background-color: red;
    
}
.factura_sellos_txt{
    display: inline-block;    
   word-wrap:break-word;
   font-size: 8px !important;
}

.factura_sellos_txt b{
    display: inline-block;    
   word-wrap:break-word;
   font-size: 9px !important;
   text-transform: uppercase;
}


.factura_totales{
    text-align: right !important;
    font-size: 12px !important;
    font-weight: bold !important;
}

.factura_detalles{
    min-height: 100px;
}

.factura_detalles_renglon1{
    font-weight: bold;
    font-style: normal;
}
.factura_detalles_renglon2{
    font-weight: bold;
    font-style: italic;
}
.factura_detalles_cabecera{
    background-color: $color_marco;
    color: $color_marco_texto;
    font-weight: bolder;
}
.factura_detalles_cabecera td{
    background-color: $color_marco;
    color: $color_marco_texto;
}

.factura_titulo_serie_folio{
    font-weight: bold !important;
    font-size: 24px !important;
}

.factura_cancelada{
    position: relative;
    top : -30px;
    margin: 10px;
    margin-left: 100px;
    padding: 5px;
    text-align: center;
    width: 350px;
    font-size: 18px;
    border-style: double;
    border-width: 3px;

    -moz-transform:rotate(-3deg);
    -webkit-transform:rotate(-3deg); 
    -ms-transform:rotate(-3deg);
}
</style> 
";
    
    return $css;
}
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
function formato_impuestos_modulo($impuesto)
{
    $impuesto=str_replace('001','ISR',$impuesto);
    $impuesto=str_replace('002','IVA',$impuesto);
    $impuesto=str_replace('003','IEPS',$impuesto);
    
    $impuesto=strtoupper($impuesto);
    return $impuesto;
}
////////////////////////////////////////////////////////////////////////////////
function formato_metodo_pago33_modulo($metodo_pago)
{
    $metodo_pago=str_replace('PUE','Pago en una sola exhibiciOn (PUE)',$metodo_pago);
    $metodo_pago=str_replace('PIP','Pago inicial y parcialidades (PIP)',$metodo_pago);
    $metodo_pago=str_replace('PPD','Pago en parcialidades o diferido (PPD)',$metodo_pago);

    $metodo_pago=strtoupper($metodo_pago);
    return $metodo_pago;
}
///////////////////////////////////////////////////////////////////////////////
function formato_forma_pago33_modulo($forma_pago)
{
    $forma_pago=str_replace('01','Efectivo (01)',$forma_pago);
    $forma_pago=str_replace('02','Cheque Nominativo (02)',$forma_pago);
    $forma_pago=str_replace('03','Transferencia electrónica de fondos (03)',$forma_pago);
    $forma_pago=str_replace('04','Tarjetas de crédito (04)',$forma_pago);
    $forma_pago=str_replace('05','Monederos electrónicos (05)',$forma_pago);
    $forma_pago=str_replace('06','Dinero electrónico (06)',$forma_pago);
    //$forma_pago=str_replace('07','Tarjetas digitales (07)',$forma_pago);
    $forma_pago=str_replace('08','Vales de despensa (08)',$forma_pago);
    //$forma_pago=str_replace('09','Bienes (09)',$forma_pago);
    //$forma_pago=str_replace('10','Servicio (10)',$forma_pago);
    //$forma_pago=str_replace('11','Por cuenta de tercero (11)',$forma_pago);
    $forma_pago=str_replace('12','Dación en pago (12)',$forma_pago);
    $forma_pago=str_replace('13','Pago por subrogación (13)',$forma_pago);
    $forma_pago=str_replace('14','Pago por consignación (14)',$forma_pago);
    $forma_pago=str_replace('15','Condonación (15)',$forma_pago);
    //$forma_pago=str_replace('16','Cancelación (16)',$forma_pago);
    $forma_pago=str_replace('17','Compensación (17)',$forma_pago);
    $forma_pago=str_replace('23','Novación (23)',$forma_pago);
    $forma_pago=str_replace('24','Confusión (24)',$forma_pago);
    $forma_pago=str_replace('25','Remisión de deuda (25)',$forma_pago);
    $forma_pago=str_replace('26','Prescripción o caducidad (26)',$forma_pago);
    $forma_pago=str_replace('27','A satisfacción del acreedor (27)',$forma_pago);
    $forma_pago=str_replace('28','Tarjeta de Débito (28)',$forma_pago);
    $forma_pago=str_replace('29','Tarjeta de Servicio (29)',$forma_pago);
    $forma_pago=str_replace('99','Por definir (99)',$forma_pago);
/*
01 – Efectivo
02 – Cheque
03 – Transferencia
04 – Tarjetas de crédito
05 – Monederos electrónicos
06 – Dinero electrónico
07 – Tarjetas digitales
08 – Vales de despensa
09 – Bienes
10 – Servicio
11 – Por cuenta de tercero
12 – Dación en pago
13 – Pago por subrogación
14 – Pago por consignación
15 – Condonación
16 – Cancelación
17 – Compensación
98 – NA
99 – Otros
*/
    $forma_pago=strtoupper($forma_pago);
    return $forma_pago;
}


///////////////////////////////////////////////////////////////////////////////
function formato_cfdi_relacionados_modulo($tipo_relacion)
{
    $tipo_relacion=str_replace('01','Nota de crédito de los documentos relacionados (01)',$tipo_relacion);
    $tipo_relacion=str_replace('02','Nota de débito de los documentos relacionados (02)',$tipo_relacion);
    $tipo_relacion=str_replace('03','Devolución de mercancía sobre facturas o traslados previos (03)',$tipo_relacion);
    $tipo_relacion=str_replace('04','Sustitución de los CFDI previos (04)',$tipo_relacion);
    $tipo_relacion=str_replace('05','Traslados de mercancias facturados previamente (05)',$tipo_relacion);
    $tipo_relacion=str_replace('06','Factura generada por los traslados previos (06)',$tipo_relacion);
    
    return $tipo_relacion;
}


?>
