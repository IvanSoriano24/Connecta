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

    //$css=html_css($color_marco,$color_marco_texto,$color_texto,$fuente_texto);
    //$valor.="<head>$css</head>";
    
    if(file_exists($xml_archivo)==false)
    {
        return 'ERROR 1, NO EXISTE XML, MUY  POSIBLEMENTE ES UNA PRUEBA FALLIDA';
    }
    if(filesize($xml_archivo)<100)
    {
        return 'ERROR 2, XML INVALIDO';        
    }



     $xml=file_get_content($xml_archivo); //para leer el contenido del xml
     $xml = str_replace("<?xml version=\"1.0\"?>\n", '', $xml);
     //$xml = simplexml_load_string($xml_archivo);
     //$xml=file_get_content('../../timbrados/autorizacion_uso_ref_3755.xml'); //para leer el contenido del xml
    //  $xml =preg_replace('/\s+/','',$xml);//se elimina espacio en tabulacion
    //  $xml = str_replace('<rContFe xmlns="http://dgi-fep.mef.gob.pa">
    

    // $xml = simplexml_load_string($xml_archivo);
   

    $dProtAut = $xml->xProtFe->rProtFe->gInfProt->dProtAut;
    $dNombEm = $xml->gDGen->gEmis->dNombEm;
    $dRuc = $xml->gDGen->gEmis->gRucEmi->dRuc;
    $dDV = $xml->gDGen->gEmis->gRucEmi->dDV;
    $dDirecEm = $xml->gDGen->gEmis->dDirecEm;
    $dNombRec = $xml->gDGen->gDatRec->dNombRec;
    $dRuc1 = $xml->gDGen->gDatRec->gRucRec->dRuc;
    $dDV1 = $xml->gDGen->gDatRec->gRucRec->dDV;
    $dDirecRec = $xml->gDGen->gDatRec->dDirecRec;
    $dNroDF = $xml->gDGen->dNroDF;
    $dFechaEm = $xml->gDGen->dFechaEm;
    $dSucEm = $xml->gDGen->gEmis->dSucEm;
    $dSecItem = $xml->gItem->dSecItem;
    $dId = $xml->dId;
    $dCodProd = $xml->gItem->dCodProd;
    $dDescProd = $xml->gItem->dDescProd;
    $dCantCodInt = $xml->gItem->dCantCodInt;
    $dPrUnit = $xml->gItem->gPrecios->dPrUnit;
    $dTotNeto = $xml->gTot->dTotNeto;
    $dCantCodInt = $xml->gItem->dCantCodInt;
    $dValITBMS = $xml->gItem->gITBMSItem->dValITBMS;
    $dVTot = $xml->gTot->dVTot;
    

    // $xml= simplexml_load_file($xml_archivo);
     
    // $dProtAut = $xml->xProtFe->rProtFe->gInfProt->dProtAut;
    // $dNombEm = $xml->gDGen->gEmis->dNombEm;
    // $dRuc = $xml->gDGen->gEmis->gRucEmi->dRuc;
    // $dDV = $xml->gDGen->gEmis->gRucEmi->dDV;
    // $dDirecEm = $xml->gDGen->gEmis->dDirecEm;
    // $dNombRec = $xml->gDGen->gDatRec->dNombRec;
    // $dRuc1 = $xml->gDGen->gDatRec->gRucRec->dRuc;
    // $dDV1 = $xml->gDGen->gDatRec->gRucRec->dDV;
    // $dDirecRec = $xml->gDGen->gDatRec->dDirecRec;
    // $dNroDF = $xml->gDGen->dNroDF;
    // $dFechaEm = $xml->gDGen->dFechaEm;
    // $dSucEm = $xml->gDGen->gEmis->dSucEm;
    // $dSecItem = $xml->gItem->dSecItem;
    // $dId = $xml->dId;
    // $dCodProd = $xml->gItem->dCodProd;
    // $dDescProd = $xml->gItem->dDescProd;
    // $dCantCodInt = $xml->gItem->dCantCodInt;
    // $dPrUnit = $xml->gItem->gPrecios->dPrUnit;
    // $dTotNeto = $xml->gTot->dTotNeto;
    // $dCantCodInt = $xml->gItem->dCantCodInt;
    // $dValITBMS = $xml->gItem->gITBMSItem->dValITBMS;
    // $dVTot = $xml->gTot->dVTot;

    // $Fecha = explode('T', $dFechaEm);
    // $Hora = explode('-', $dFechaEm);
    
    $archivo_png=str_replace(".xml",".png",$xml_archivo);
    $valor="
    <!DOCTYPE html>
    <html>
            <style>
                table.table2,table.table1,table.table3,td {
                    border:1px solid black;
                    border-collapse: collapse;
                    font-size:8px;
                    face=Comic Sans MS,arial;
                }
                table.table1{
                    position:relative; 
                    top: -23px; 
                    left: -3px;
                }
                table.table2{
                    position:relative; 
                    top: -24px; 
                    left: -3px;
                }
                table.table3{
                    position:relative; 
                    top: -25px; 
                    left: -3px;
                }
                h2{
                    text-align:center;
                }
                td{
                    width=30%;
                    font-size:8px;
                    face=Comic Sans MS,arial;
                }
                th{
                    position:relative; 
                    top: 0px; 
                    left: 0px;
                    font-size:8px;
                }
                table.uno {
                    width: 28%;
                    position:relative; 
                    top: -22px; 
                    left: 130px;
                    font-size:10px;
                    face=Comic Sans MS,arial;

                }
                table.dos {
                    width: 27%;
                    position:relative; 
                    top:-94px; 
                    left: 514px;
                    font-size:10px;
                    face=Comic Sans MS,arial;

                }
                p.ult {
                    position:relative; 
                    top:-101px; 
                    left: 520px;
                    font-size:9px;
                    face=Comic Sans MS,arial;

                }
                p.ultd {
                    position:relative; 
                    top:-109px; 
                    left: 540px;
                    font-size:9px;
                    face=Comic Sans MS,arial;

                }
                p.ultt {
                    position:relative; 
                    border: none;
                    top: -126px; 
                    left: 699px;
                    font-size:7px;
                    face=Comic Sans MS,arial;

                }
            </style>
        <body>

            <table class=table1 style=width:80%>
                <tr> 
                    <th colspan=3>
                        <p font-size:7px; align=center><FONT FACE=Helvetica >COMPROBANTE AUXILIAR DE FACTURA ELECTRONICA</FONT></p>
                        <p font-size:7px; align=center><FONT FACE=Helvetica >Factura de Importacion </FONT></p>
                    </th>
                    </tr>
                    <tr>
                        <td rowspan=2 style=width:20% align=center> <p> LOGO </p> </td>
                        <td style=width:60%> 
                        <p><b><FONT FACE=Helvetica>Emisor: </FONT></b>&nbsp;
                            <FONT FACE=Helvetica> $dNombEm </FONT></p>
                        <p><b><FONT FACE=Helvetica> RUC:</FONT></b>&nbsp;
                            <FONT FACE=Helvetica>$dRuc</FONT></p>
                        <p><b><FONT FACE=Helvetica> DV: </FONT></b>&nbsp;
                            <FONT FACE=Helvetica> $dDV</FONT></p>
                        <p><b><FONT FACE=Helvetica> Direccion: </FONT></b>&nbsp;
                            <FONT FACE=Helvetica> $dDirecEm </FONT></p>
                        </td>
                        <td rowspan=2 style=width:1%><img src='$archivo_png'></td>
                    </tr>
                    <tr>
                        <td>
                            <p><b><FONT FACE=Helvetica>Tipo de receptor: </FONT></b>&nbsp;
                                <FONT FACE=Helvetica> $dNombRec </FONT></p>
                            <p><b><FONT FACE=Helvetica>Cliente: </FONT></b>&nbsp;
                                <FONT FACE=Helvetica> $dNombRec </FONT></p>
                            <p><b><FONT FACE=Helvetica> RUC: </FONT></b>&nbsp;
                                <FONT FACE=Helvetica> $dRuc1 </FONT></p>
                            <p><b><FONT FACE=Helvetica> DV: </FONT></b>&nbsp;
                                <FONT FACE=Helvetica> $dDV1 </FONT></p>
                            <p><b><FONT FACE=Helvetica> Direccion: </FONT></b>&nbsp;
                                <FONT FACE=Helvetica> $dDirecRec </FONT></p>
                        </td>
                    </tr>
                </tr>
            </table>

            <table class=table2 style=width:102.8% >
                <tr>
                    <td colspan=1 style=width:40%>
                    <p><FONT FACE=Helvetica>Tipo: Factura</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <b><FONT FACE=Helvetica>Numero:</FONT></b>
                        <FONT FACE=Helvetica> $dNroDF</FONT></p>
                    <p><FONT FACE=Helvetica>Fecha: $Fecha[0]</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    
                        <FONT FACE=Helvetica>Hora: $Fecha[1]</FONT></p>
                    <p><FONT FACE=Helvetica>Sucursal: $dSucEm - Matriz</FONT></p>
                    <p><FONT FACE=Helvetica>Caja/Pto Fact: $dSecItem </FONT></p>
                    </td>
                    <td colspan=2 style=width:60%>
                    <p><b><FONT FACE=Helvetica>Consulte por la clave de acceso en: </b>
                        <FONT>https://dgi-fep.mef.gob.pa/Consultas</FONT></p>
                    <p><b><FONT FACE=Helvetica>CUFE:</FONT></b>&nbsp;
                        <FONT FACE=Helvetica>$dId </FONT></p>
                    <p><b><FONT FACE=Helvetica>Protocolo de autorizacion:</FONT></b>
                        <FONT FACE=Helvetica>$dProtAut</FONT></p>
                    </td>
                </tr>
            </table>

            <table class=table3 style=width:102.8%>
                <tr>
                    <td colspan=3>
                        <p aling=center><FONT FACE=Helvetica>Codigo</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <FONT FACE=Helvetica>Descripcion</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <FONT FACE=Helvetica>Cantidad</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <FONT FACE=Helvetica>Unidad</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <FONT FACE=Helvetica>Valor Unitario</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <FONT FACE=Helvetica>%Impuesto</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;
                        <FONT FACE=Helvetica>Valor Total</FONT></p>
                    </td>
                </tr>

                <tr>
                    <td colspan=3>
                        <p><FONT FACE=Helvetica>$dCodProd</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <FONT FACE=Helvetica>$dDescProd</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <FONT FACE=Helvetica>$dCantCodInt</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <FONT FACE=Helvetica>-</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <FONT FACE=Helvetica>7</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <FONT FACE=Helvetica>$dPrUnit</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <FONT FACE=Helvetica>$dVTot</FONT>
                        </p>
                   
                        <p> <FONT FACE=Helvetica>Cantidad Total:</FONT>&nbsp;&nbsp;
                            <FONT FACE=Helvetica>$dCantCodInt</FONT>

                            <table class=uno border=1 FRAME=void RULES=cols>
                                <tr><td colspan=3 align=center>Desglose ITBMS</td></tr>
                                <tr><td align=center class=sin>Monto Base</td>
                                <td align=center class=sin>%</td>
                                <td align=center class=sin>Impuesto</td></tr>

                                <tr><td align=right>0.00</td>
                                <td align=center>Exento</td>
                                <td align=right>0.00</td></tr>

                                <tr><td align=right>$dTotNeto</td>
                                <td align=center>7</td>
                                <td align=right>$dValITBMS</td></tr>

                                <tr align=right><td>0.00</td>
                                <td align=center>$dCantCodInt</td>
                                <td align=right>0.00</td></tr>

                                <tr><td align=right>0.00</td>
                                <td align=center>15</td>
                                <td align=right>0.00</td></tr>
                            </table>

                            <table class=dos border=1 FRAME=border RULES=none>
                                <tr><td>Total Neto</td>
                                <td align=right>$dTotNeto</td></tr>

                                <tr><td>Monto Exento</td>
                                <td align=right>0.00</td></tr>

                                <tr><td>Monto Gravado</td>
                                <td align=right>$dTotNeto</td></tr>

                                <tr><td>Total Impuesto</td>
                                <td align=right>$dValITBMS</td></tr>

                                <tr><td>Descuento</td>
                                <td align=right>0.00</td></tr>

                                <tr><td>Total</td>
                                <td align=right>$dVTot</td></tr>
                            </table>
                        </p>  
                        <p class=ult><FONT FACE=Helvetica>Forma pago</FONT></p>
                        <p class=ultd><FONT FACE=Helvetica>Credito</FONT></p>
                        <p class=ultt><FONT FACE=Helvetica>$dVTot</FONT></p>
                    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
                    <br><br><br><br>
                    </td>
                </tr>
            </table>
        </body>
    </html>
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

    font-size: 10px !important;
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
