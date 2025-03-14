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


    $xml = simplexml_load_file($xml_archivo);

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

    $Fecha = explode('T', $dFechaEm);
    $Fecha[1] = str_replace("-05:00", "",$Fecha[1] );

    $archivo_png=str_replace(".xml",".png",$xml_archivo);
    $valor="
    <!DOCTYPE html>
    <html>
            <style>
            table.table2,table.table1,table.table3,td {
                border-collapse: collapse;
                font-size:10px;
                face=Comic Sans MS,arial;
            }
            td.a{
                font-size:11px;
                face=Comic Sans MS,arial;
            }
            td.c{
                font-size:10px;
                face=Comic Sans MS,arial;
            }
            p.b{
                font-size:14px;
                face=Comic Sans MS,arial;

            }
            p.d{
                font-size:8px;
                face=Comic Sans MS,arial;

            }
            table.fir{
                position:relative; 
                top: 10px; 
                left: 0px;
            }
            table.sec{
                position:relative; 
                top: -50px; 
                left: 500px;
            }
            table.dos {
                width: 25%;
                position:relative; 
                top:-28px; 
                left: 530px;
                font-size:12px;
                face=Comic Sans MS,arial;

            }
            table.uno {
                width: 26%;
                position:relative; 
                top: 40px; 
                left: 10px;
                font-size:10px;
                face=Comic Sans MS,arial;

            }
            </style>

        <body>
            <table style=width:100% colspan=2 FRAME=void RULES=rows>

                <tr>
                    <td class=a align=center> 
                    <p><FONT FACE=Helvetica> $dNombEm </FONT></p>
                    <p><FONT FACE=Helvetica> RUC</FONT>&nbsp;&nbsp;
                    <FONT FACE=Helvetica>$dRuc</FONT>&nbsp;&nbsp;&nbsp;
                    <FONT FACE=Sans-Helvetica> DV </FONT>&nbsp;
                    <FONT FACE=Sans-Helvetica> $dDV</FONT></p>
                    <p><FONT FACE=Helvetica> $dDirecEm </FONT></p>
                    <br>
                    <p> LOGO </p>
                    <br>
                    <p class=b align=center><FONT FACE=Helvetica >COMPROBANTE AUXILIAR DE FACTURA ELECTRONICA</FONT></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table class=fir style=width:50% align=center>
                            <tr>
                                    <td class=a><FONT FACE=Helvetica>Tipo: Factura</FONT></td>
                                    <td class=a align=right><FONT FACE=Helvetica>Numero: $dNroDF</FONT></td>
                            </tr>
                            <tr>
                                    <td class=a><FONT FACE=Helvetica>Fecha: $Fecha[0]</FONT></td>                     
                                    <td class=a align=right><FONT FACE=Helvetica>Hora: $Fecha[1]</FONT></td>
                            </tr>
                            <tr>    
                                    <td class=a><FONT FACE=Helvetica><FONT FACE=Helvetica>Sucursal: $dSucEm - Matriz</FONT></td>
                                    <td class=a align=right><FONT FACE=Helvetica><FONT FACE=Helvetica>Caja/Pto Fact: $dSecItem </FONT></td>
                            </tr>
                            <tr><br>
                                
                                <td class=c colspan=2><FONT FACE=Helvetica> $dDirecRec </FONT></td>
                            </tr>
                            <tr>
                                <td class=c colspan=2><FONT FACE=Helvetica> $dNombRec </FONT></td>    
                            </tr>        
                            <tr>
                                <td class=c colspan=2><FONT FACE=Helvetica> RUC </FONT>&nbsp;
                                <FONT FACE=Helvetica> $dRuc1 </FONT>&nbsp;&nbsp;&nbsp;
                                <FONT FACE=Helvetica> DV </FONT>&nbsp;
                                <FONT FACE=Helvetica> $dDV1 </FONT>
                                </td>
                            </tr><br>  
                        </table>
                    </td>
                </tr> 
                <tr>
                    <td class=b align=center>
                        <p class=b align=center><FONT FACE=Helvetica> FACTURA</FONT></p>
                    </td>
                </tr>
                <tr>
                    <td >
                        <table  style=width:100% aling=center>
                            <tr>
                                <td colspan=2 class=c>
                                    <p aling=center><FONT FACE=Helvetica>Codigo</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <FONT FACE=Helvetica>Descripcion</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <FONT FACE=Helvetica>Cantidad</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <FONT FACE=Helvetica>Unidad</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <FONT FACE=Helvetica>Valor Unitario</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <FONT FACE=Helvetica>%Impuesto</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    &nbsp;&nbsp;
                                    <FONT FACE=Helvetica>Valor Total</FONT></p>
                                </td>
                            </tr>
            
                            <tr>
                                <td colspan=2 class=c>
                                    <p><FONT FACE=Helvetica>$dCodProd</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                        <FONT FACE=Helvetica>$dDescProd</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;
                                        <FONT FACE=Helvetica>$dCantCodInt</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <FONT FACE=Helvetica>-</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <FONT FACE=Helvetica>7</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <FONT FACE=Helvetica>$dPrUnit</FONT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <FONT FACE=Helvetica>$dVTot</FONT>
                                    </p>
                            
                                    <p class=d> <FONT FACE=Helvetica>Cantidad Total:</FONT>&nbsp;&nbsp;
                                        <FONT FACE=Helvetica>$dCantCodInt</FONT>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td colspan=2>
                                    <table class=dos border=1 FRAME=border RULES=none>
                                        <tr><td class=a align=right style=width:40%>Total Neto</td>
                                            <td class=a align=right style=width:40%>$dTotNeto</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Monto Exento</td>
                                            <td class=a align=right style=width:40%>0.00</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Monto Gravado</td>
                                            <td class=a align=right style=width:40%>$dTotNeto</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Total Impuesto</td>
                                            <td class=a align=right style=width:40%>$dValITBMS</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Descuento</td>
                                            <td class=a align=right style=width:40%>0.00</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Total</td>
                                            <td class=a align=right style=width:40%>$dVTot</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>  

                            <tr>
                                <td colspan=2><br>
                                    <table class=dos border=1 FRAME=border RULES=none>
                                        <tr><td class=a align=right style=width:40%>Forma pago</td>
                                            <td class=a align=right style=width:40%></td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Credito</td>
                                            <td class=a align=right style=width:40%>$dVTot</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Efectivo</td>
                                            <td class=a align=right style=width:40%>0.00</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Debito</td>
                                            <td class=a align=right style=width:40%>0.00</td>
                                        </tr>
                                        <tr><td class=a align=right style=width:40%>Vuelto</td>
                                            <td class=a align=right style=width:40%>0.00</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td colspan=2><br>
                                    <table class=uno border=1 FRAME=void RULES=cols>
                                        <tr><td colspan=3 class=a align=center>Desglose ITBMS</td></tr>
                                        <tr><td align=center class=a style=width:40%>Monto Base</td>
                                        <td align=center class=a>%</td>
                                        <td align=center class=a>Impuesto</td></tr>

                                        <tr><td align=right class=a>0.00</td>
                                        <td align=center class=a>Exento</td>
                                        <td align=right class=a>0.00</td></tr>

                                        <tr><td align=right class=a>$dTotNeto</td>
                                        <td align=center class=a>7</td>
                                        <td align=right class=a>$dValITBMS</td></tr>

                                        <tr align=right><td class=a>0.00</td>
                                        <td align=center class=a>$dCantCodInt</td>
                                        <td align=right class=a>0.00</td></tr>

                                        <tr><td align=right class=a>0.00</td>
                                        <td align=center class=a>15</td>
                                        <td align=right class=a>0.00</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table><br><br><br><br>
                    </td>
                </tr>
                <tr>
                    <td class=a align=center>
                        <p><FONT FACE=Helvetica> Autorizacion DGI: $dNroDF</FONT>&nbsp;&nbsp;
                            <FONT FACE=Helvetica> de $Fecha[0]$Fecha[1]</FONT>
                        </p>
                        <p><FONT FACE=Helvetica> Consulte en: https://dgi-fep.mef.gob.pa/Consultas </FONT>&nbsp;
                            <FONT FACE=Helvetica> usando el CUFE: </FONT>
                        </p>
                        <p><FONT FACE=Helvetica> $dId</FONT></p>
                        <p><FONT FACE=Helvetica> o escaneando el codigo QR </FONT></p>
                        <p> <img src='$archivo_png'> </p>
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
        $comando=$ruta."wkhtmltopdf  -s A7 -B 1 -T 1 -L 1 -R 1  \"$url\"  \"$carpeta_instalacion$pdf\"    "; //   -B 1 -T 1 -L 1 -R 1 -s A4 &        
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
