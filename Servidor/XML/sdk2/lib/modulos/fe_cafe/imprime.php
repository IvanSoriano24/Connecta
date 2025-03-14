<?php
//error_reporting(E_ALL);
error_reporting(E_ALL);
error_reporting(E_ERROR | E_PARSE);

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
	if($xml->dId=='')
	{
		$tmp=file_get_contents($xml_archivo);
		list($tmpx,$au2)=explode('<xFe>',$tmp);
		list($xmltmp)=explode('</xFe>',$au2);
		$xml = simplexml_load_string($xmltmp);
		
		list($tmpx,$dProtAut2)=explode('<dProtAut>',$tmp);
		list($dProtAut)=explode('</dProtAut>',$dProtAut2);
		
		
		
	}

    $dNombEm = $xml->gDGen->gEmis->dNombEm;
    $dRuc = $xml->gDGen->gAutXML->gRucAutXML->dRuc;
    $dDV = $xml->gDGen->gAutXML->gRucAutXML->dDV;
    $dDirecEm = $xml->gDGen->gEmis->dDirecEm;
    $dNombRec = $xml->gDGen->gDatRec->dNombRec;
    $dRuc1 = $xml->gDGen->gDatRec->gRucRec->dRuc;
    $dDV1 = $xml->gDGen->gDatRec->gRucRec->dDV;
    $dDirecRec = $xml->gDGen->gDatRec->dDirecRec;
    $dNroDF = $xml->gDGen->dNroDF;
    $dFechaEm = $xml->gDGen->dFechaEm;

    $dId = $xml->dId;
    //$dTotNeto = $xml->gTot->dTotNeto;
    $dTotGravado = $xml->gTot->dTotGravado;
    $dValTotItem = $xml->gItem->gPrecios->dValTotItem;
    //$dSecItem = $xml->gItem->dSecItem;
    //$dPrUnit = $xml->gItem->gPrecios->dPrUnit;
    $dPtoFacDF = $xml->gDGen->dPtoFacDF;
    //$dDescProd = $xml->gItem->dDescProd;
    //$dCantCodInt = $xml->gItem->dCantCodInt;
    //$dValITBMS = $xml->gItem->gITBMSItem->dValITBMS;
    //$dVTot = $xml->gTot->dVTot;

    $Fecha = explode('T', $dFechaEm);
    $Hora = explode('-', $dFechaEm);
    
    
/// QR

    $cadenaqr = $xml->gNoFirm->dQRCode;
           //ARCHIVO PNG QR
    $archivo_png=str_replace(".xml",".png",$xml_archivo);
	
    
    if(!file_exists($archivo_png))
    {
        //include_once "../../sdk2.php";
        //include_once "../../lib/modulos/qr/qr.php";
        
        //MODULO MULTIFACTURAS QUE CREA QR PNG DE UN XML CFDI 
        $datosQR['modulo']="qr_fe";
        $datosQR['PAC']['usuario'] = "DEMO700101XXX";
        $datosQR['PAC']['pass'] = "DEMO700101XXX";
        $datosQR['PAC']['produccion'] = "NO";
        $datosQR['cadena']=$cadenaqr;
        $datosQR['archivo_png']=$archivo_png;
        $res = mf_ejecuta_modulo($datosQR);
        //$res = ___qr($datosQR);
        
        $archivo_png = $res['archivo_png'];
        
    }
	
	
	$png64=base64_encode(file_get_contents($archivo_png));

	
	$td['01']='Factura de operación interna';
	$td['02']='Factura de importación';
	$td['03']='Factura de exportación';
	$td['04']='Nota de Crédito referente a una o varias FE';
	$td['05']='Nota de Débito referente a una o varias FE';
	$td['06']='Nota de Crédito genérica';
	$td['07']='Nota de Débito genérica';
	$td['08']='Factura de Zona Franca';
	$td['09']='Reembolso';

	$iDoc = $xml->gDGen->iDoc;
	$tipodocumento=$td["$iDoc"];
	
	$iTipoRec=$xml->gDGen->gDatRec->iTipoRec;
	$tr['01']='Contribuyente';
	$tr['02']='Consumidor final';
	$tr['03']='Gobierno';
	$tr['04']='Extranjero';		
	$tiporeceptor=$tr["$iTipoRec"];

	$iTpEmis=$xml->gDGen->iTpEmis;
	$te['01']='Autorización de Uso Previa, operación normal';
	$te['02']='Autorización de Uso Previa, operación en contingencia';
	$te['03']='Autorización de Uso Posterior, operación normal';
	$te['04']='Autorización de Uso posterior, operación en contingencia';
	$tipoemisor=$te["$iTpEmis"];

	$nat['01']='Venta';
	$nat['02']='Exportación';
	$nat['10']='Transferencia';
	$nat['11']='Devolución';
	$nat['12']='Consignación';
	$nat['13']='Remesa';
	$nat['14']='Entrega gratuita';
	$nat['20']='Compra';
	$nat['21']='Importación';
	$iNatOp=$nat[(string)$xml->gDGen->iNatOp];
	
	$to['1']='Salida o venta';
	$to['2']='Entrada o compra	';
	$iTipoOp=$to[(string)$xml->gDGen->iTipoOp];

//B19	$iTipoTranVenta=$xml->gDGen->iTipoTranVenta;


    $valor="
    <!DOCTYPE html>
	<html>
    <head>
	<meta charset='UTF-8'> 
	$css

        </head>
		
        <body>
            <table  style=width:100% border=1>
                <tr> 
                    <th colspan=3>
                        <p align=center>COMPROBANTE AUXILIAR DE FACTURA ELECTRONICA</p>
						$tipodocumento
                    </th>
                </tr>
                <tr>
                    <td rowspan=2 width='170px' > <p>  </p> </td>
                    <td > 
					<b>DATOS DEL EMISOR</b><br/>
                        Emisor:  $dNombEm <br/>
						RUC: $dRuc <br/>
						DV: $dDV <br/>
						Direccion: $dDirecEm
                    </td>
                    <td rowspan=2 width='170px' valign='top' align='right'>
						<img src=\"data:image/png;base64,$png64\">
						
					</td>
                </tr>
                <tr>
                    <td>
					<b>DATOS DEL RECEPTOR</b><br/>
                        Tipo de receptor: $tiporeceptor<br/>
                        Contribuyente :  $dNombRec <br/>
						RUC/Cedula/Pasaporte: $dRuc1  <br/>
						DV: $dDV1 <br/>
						Direccion:  $dDirecRec 
                    </td>
                </tr>
            </table>

            <table style=width:100% border=1  valign='top'>
                <tr >
                    <td colspan=1 style=width:35%>

Naturaleza de la Operación : $iNatOp <br/>
Tipo de la operación : $iTipoOp <br/>
						Numero: $dNroDF <br/>
						Fecha de emisión: $Fecha[0] <br/>
						Punto de Facturacion:  $dPtoFacDF 
                    </td>
                    <td colspan=2 style=width:65% valign='top'>
                        Consulte por la clave de acceso en: https://dgi-fep.mef.gob.pa/Consultas <br/>
						CUFE: $dId  <br/>
						Protocolo de autorización: $dProtAut <br/>
						Tipo Emision : $tipoemisor <br/>
                    </td>
                </tr>
            </table>
<br/>
            <table style=width:99% cellspacing=0 cellpadding=0 border=1>
                <tr>
                    <td style=width:50px>
                        ITEM
                    </td>
                    <td style=width:50px>
                        Cantidad
                    </td>					
                    <td >
                        Descripcion
                    </td>


                    <td style=width:50px>
                        Valor Unitario
                    </td>
                    <td style=width:50px>
                        Descuento Unitario
                    </td>
                    <td style=width:90px>
                        Precio Item
                    </td>
                    <td style=width:90px>
                        ITBMS
                    </td>
                    <td style=width:90px>
                        TOTAL
                    </td>
                </tr>";
	$cnt=intval(count($xml->gItem ));
	if($cnt==1)
	{

            $dSecItem=$xml->gItem->dSecItem; 
			$cUnidad=$xml->gItem->cUnidad;
			$dCodProd=$xml->gItem->dCodProd; 
            $dDescProd=$xml->gItem->dDescProd; 
            $dCantCodInt=$xml->gItem->dCantCodInt; 
            $dPrUnit=$xml->gItem->gPrecios->dPrUnit; 
			$dPrUnitDesc=$xml->gItem->gPrecios->dPrUnitDesc; 
			$dPrItem=$xml->gItem->gPrecios->dPrItem; 
			$dValTotItem=$xml->gItem->gPrecios->dValTotItem; 
			
			
            $dTotNeto=$xml->gItem->dTotNeto;
            $gITBMSItem=$xml->gItem->gITBMSItem;
            $dVTot=$xml->gItem->dVTot;
			$dFechaFab=$xml->gItem->dFechaFab;
			$dFechaCad=$xml->gItem->dFechaCad;
			$fechas="$dFechaFab$dFechaCad";
			if($fechas!='')
			{
				
				if($dFechaFab!='')
					$dFechaFab=" Fabricacion $dFechaFab ";
				if($dFechaCad!='')
					$dFechaCad=" Caducidad $dFechaCad ";
				$fechas="<br/>$dFechaFab$dFechaCad";
			}
			else
			{
				$fechas='';
			}
			if($dCodProd!='')
				$dCodProd="[$dCodProd] ";
			else
			{
				$dCodProd='';
			}
			
			$cnt2=count($gITBMSItem);
			$ITBMS='';
			$ITBMS_txt='';
			if($cnt2==1)
			{
				$dTasaITBMS=$gITBMSItem->dTasaITBMS;
				$dValITBMS=$gITBMSItem->dValITBMS;
				switch($dTasaITBMS)
				{
					case '00': $dTasaITBMS='Excento';break;
					case '01': $dTasaITBMS='7%';break;
					case '02': $dTasaITBMS='10%';break;
					case '03': $dTasaITBMS='13%';break;
				}
				$itbms_total[$dTasaITBMS]['porcentaje']=$dTasaITBMS;
				$itbms_total[$dTasaITBMS]['impuesto']+=(float)$dValITBMS;
				$itbms_total[$dTasaITBMS]['montobase']+=(float)$dPrItem;

				
				$ITBMS_txt="$dTasaITBMS $$dValITBMS";
			}
			if($cnt2>1)
			{
				foreach($gITBMSItem AS $tmp)
				{
					$dTasaITBMS=$tmp->dTasaITBMS;
					$dValITBMS=$tmp->dValITBMS;
					switch($dTasaITBMS)
					{
						case '00': $dTasaITBMS='0%';break;
						case '01': $dTasaITBMS='7%';break;
						case '02': $dTasaITBMS='10%';break;
						case '03': $dTasaITBMS='13%';break;
					}
					$itbms_total[$dTasaITBMS]['porcentaje']=$dTasaITBMS;
					$itbms_total[$dTasaITBMS]['impuesto']+=(float)$dValITBMS;
					$itbms_total[$dTasaITBMS]['montobase']+=(float)$dPrItem;

					$ITBMS_txt.="$dTasaITBMS $$dValITBMS<br/>";
				}
			}
			
			
			if($dPrUnitDesc=='')
				$dPrUnitDesc="0.00";
$valor.="
            <tr>

                        <td align=center>
                            $dSecItem
                        </td>
                        <td align=right>
                            $dCantCodInt
                        </td>
                        <td>
                            $dCodProd$dDescProd $cUnidad $fechas
                        </td>
                        <td align=right>
                            $$dPrUnit 
                        </td>
                        <td align=right>
                            $$dPrUnitDesc
                        </td>
                        <td align=right>
                            $$dPrItem
                        </td>
                        <td align=right>
                            $ITBMS_txt
                        </td>
                        <td align=right>
                            $$dValTotItem
                        </td>

        </tr>";

		$cnt=count($xml->gItem->gISCItem);
		if($cnt==1)
		{
			$dTasaISC=(string) $xml->gItem->gISCItem->dTasaISC;
			$dValISC=(float) $xml->gItem->gISCItem->dValISC;
			$isc_total[$dTasaISC]['tasa']=$dTasaISC;
			$isc_total[$dTasaISC]['valor']+=(float)$dValISC;
			$isc_total[$dTasaISC]['monto']+=(float)$dPrItem;
		}
		if($cnt>1)
		{
			foreach($xml->gItem->gISCItem AS $dato)
			{
				$dTasaISC=(string) $dato->dTasaISC;
				$dValISC=(float) $dato->dValISC;
				$isc_total[$dTasaISC]['tasa']=$dTasaISC;
				$isc_total[$dTasaISC]['valor']+=(float)$dValISC;
				$isc_total[$dTasaISC]['monto']+=(float)$dPrItem;
			}
		}

    }            

    if($cnt>1) 
    {
        foreach ($xml->gItem as $item) 
        {
			
            $dSecItem=$item->dSecItem; 
			$cUnidad=$item->cUnidad;
			$dCodProd=$item->dCodProd; 
            $dDescProd=$item->dDescProd; 
            $dCantCodInt=$item->dCantCodInt; 
            $dPrUnit=$item->gPrecios->dPrUnit; 
			$dPrUnitDesc=$item->gPrecios->dPrUnitDesc; 
			$dPrItem=$item->gPrecios->dPrItem; 
			$dValTotItem=$item->gPrecios->dValTotItem; 
			
			
            $dTotNeto=$item->dTotNeto;
            $gITBMSItem=$item->gITBMSItem;
            $dVTot=$item->dVTot;
			$dFechaFab=$item->dFechaFab;
			$dFechaCad=$item->dFechaCad;
			$fechas="$dFechaFab$dFechaCad";
			if($fechas!='')
			{
				
				if($dFechaFab!='')
					$dFechaFab=" Fabricacion $dFechaFab ";
				if($dFechaCad!='')
					$dFechaCad=" Caducidad $dFechaCad ";
				$fechas="<br/>$dFechaFab$dFechaCad";
			}
			else
			{
				$fechas='';
			}
			if($dCodProd!='')
				$dCodProd="[$dCodProd] ";
			else
			{
				$dCodProd='';
			}
			
			$cnt2=count($gITBMSItem);
			$ITBMS='';
			$ITBMS_txt='';
			if($cnt2==1)
			{
				$dTasaITBMS=$gITBMSItem->dTasaITBMS;
				$dValITBMS=$gITBMSItem->dValITBMS;
				switch($dTasaITBMS)
				{
					case '00': $dTasaITBMS='0%';break;
					case '01': $dTasaITBMS='7%';break;
					case '02': $dTasaITBMS='10%';break;
					case '03': $dTasaITBMS='13%';break;
				}
				$ITBMS_txt="$dTasaITBMS $$dValITBMS";
				$itbms_total[$dTasaITBMS]['porcentaje']=$dTasaITBMS;
				$itbms_total[$dTasaITBMS]['impuesto']+=(float)$dValITBMS;
				$itbms_total[$dTasaITBMS]['montobase']+=(float)$dPrItem;
				
			}
			if($cnt2>1)
			{
				foreach($gITBMSItem AS $tmp)
				{
					$dTasaITBMS=$tmp->dTasaITBMS;
					$dValITBMS=$tmp->dValITBMS;
					switch($dTasaITBMS)
					{
						case '00': $dTasaITBMS='0%';break;
						case '01': $dTasaITBMS='7%';break;
						case '02': $dTasaITBMS='10%';break;
						case '03': $dTasaITBMS='13%';break;
					}
					$ITBMS_txt.="$dTasaITBMS $$dValITBMS<br/>";
					$itbms_total[$dTasaITBMS]['porcentaje']=$dTasaITBMS;
					$itbms_total[$dTasaITBMS]['impuesto']+=(float)$dValITBMS;
					$itbms_total[$dTasaITBMS]['montobase']+=(float)$dPrItem;
					
				}
			}
			
			
			if($dPrUnitDesc=='')
				$dPrUnitDesc="0.00";
$valor.="
            <tr>

                        <td align=center>
                            $dSecItem
                        </td>
                        <td align=right>
                            $dCantCodInt
                        </td>
                        <td>
                            $dCodProd$dDescProd $cUnidad $fechas
                        </td>
                        <td align=right>
                            $$dPrUnit 
                        </td>
                        <td align=right>
                            $$dPrUnitDesc
                        </td>
                        <td align=right>
                            $$dPrItem
                        </td>
                        <td align=right>
                            $ITBMS_txt
                        </td>
                        <td align=right>
                            $$dValTotItem
                        </td>

        </tr>";


        }
    }
$valor.= "
            </table>";

///////  ITBMS
$itbms_excento=0.00;
$itbms_grabado=0.00;
$tabla_itbms.="
            <table  class=itbms cellspacing=0 cellpadding=0 border=1>
                <tr><td colspan=3 align=center>Desglose ITBMS</td></tr>
                <tr><td align=center class=sin>Monto Base</td>
                    <td align=center class=sin>%</td>
                    <td align=center class=sin>Impuesto</td>
                </tr>";
//echo "<pre>";print_r($itbms_total);echo "</pre>";
			foreach($itbms_total AS $dato)
			{
				$montobase=$dato['montobase'];
				$porcentaje=$dato['porcentaje'];
				$impuesto=$dato['impuesto'];
				

				if($porcentaje=='0%')
				{

					$itbms_excento+=(float)$montobase;
				}
				else
				{

					$itbms_grabado+=(float)$montobase;
				}

//				$montobase=printf("%1.2f","$montobase");
//				$impuesto=printf("%0.2f",(float)$impuesto);
//echo "--$montobase--";				
				
				$tabla_itbms.="
			
                <tr>
				<td align=right>$montobase</td>
                    <td align=center>$porcentaje</td>
                    <td align=right>$impuesto -</td>
                </tr>
			";
				
			}
			$dTotITBMS=$xml->gTot->dTotITBMS;			
				$tabla_itbms.="
			
                <tr>
					<td align=right>TOTAL</td>
                    <td align=center></td>
                    <td align=right>$dTotITBMS -</td>
                </tr>
				
				</table>";



///////  ISC
//print_r($itbms_total);

$tabla_isc.="
            <table  class=isc cellspacing=0 cellpadding=0 border=1>
                <tr><td colspan=3 align=center>Desglose ISC</td></tr>
                <tr><td align=center class=sin>Monto Base</td>
                    <td align=center class=sin>%</td>
                    <td align=center class=sin>Impuesto</td>
                </tr>";
				

			
			foreach($isc_total AS $dato)
			{
				$montobase=$dato['monto'];
				$porcentaje=$dato['tasa'];
				$impuesto=$dato['valor'];

				if($porcentaje=='0%')
				{
					$isc_excento+=(float)$montobase;
				}
				else
				{
					$isc_grabado+=(float)$montobase;
				}				
				//$montobase=number_format($montobase);
				$impuesto=number_format($impuesto);
				//$montobase=printf ("%1.2f", (float) "$montobase");
				//$impuesto=sprintf ("%1.2f", $impuesto);
				
				$tabla_isc.="
			
                <tr>
				<td align=right>$montobase</td>
                    <td align=center>$porcentaje</td>
                    <td align=right>$impuesto</td>
                </tr>
			";
				
			}


			$dTotISC=$xml->gTot->dTotISC;
			$tabla_isc.="
               <tr>
				<td align=right>TOTAL</td>
                    <td align=center> </td>
                    <td align=right>$dTotISC</td>
                </tr>			
            </table>";
			
			if(count($isc_total)==0)
			{
				$tabla_isc='';
			}
			else
			{
				$dTotISC=$xml->gTot->dTotISC;
				$isc_excento=sprintf("%1.2f",$isc_excento);
				$isc_grabado=sprintf("%1.2f",$isc_grabado);				
				$isc_txt="
                <tr><td>Monto Exento ISC</td>
                    <td align=right>$$isc_excento</td>
                </tr>

                <tr><td>Monto Gravado ISC</td>
                    <td align=right>$$isc_grabado</td>
                </tr>

                <tr><td>ISC</td>
                    <td align=right>$$dTotISC</td>
                </tr>				
				";
			}
			
///////  PLAZOS
            $tabla_plazos.="<table class='plazos' cellspacing='0' cellpadding='0' border='1'>
                <tr>
					<td colspan=3 align=center>Informacion de Pago a Plazo</td>
				</tr>
                <tr>
					<td align=center class=sin>Cuota</td>
                    <td align=center class=sin>Fecha de Vencimiento</td>
                    <td align=center class=sin>Valor</td>
                </tr>";
				
			$cnt=count($xml->gTot->gPagPlazo);
			if($cnt==1)
			{
				$dSecItem=$xml->gTot->gPagPlazo->dSecItem;
				$dFecItPlazo=$xml->gTot->gPagPlazo->dFecItPlazo;
				$dValItPlazo=$xml->gTot->gPagPlazo->dValItPlazo;
				$tabla_plazos.="
					<tr>
						<td align=right>$dSecItem</td>
						<td align=center>$dFecItPlazo</td>
						<td align=right>$dValItPlazo</td>
					</tr>
					";
			}
			if($cnt>1)
			{
				foreach($xml->gTot->gPagPlazo AS $dato)
				{
				$dSecItem=$dato->dSecItem;
				$dFecItPlazo=$dato->dFecItPlazo;
				$dValItPlazo=$dato->dValItPlazo;
				list($dFecItPlazo,$tmp)=explode('T',$dFecItPlazo);
				$tabla_plazos.="
					<tr>
						<td align=right>$dSecItem</td>
						<td align=center>$dFecItPlazo</td>
						<td align=right>$dValItPlazo</td>
					</tr>
					";					
				}
			}
				
$tabla_plazos.="
</table>
";

//////////  OTI
$oti['01']='SUME 911';
$oti['02']='Tasa Portabilidad Numérica';
$oti['03']='Impuesto sobre seguro';

	$cnt=count($xml->gTot->gOTITotal);

	if($cnt>0)
	$tabla_OTI.="<table class='plazos' cellspacing='0' cellpadding='0' border='1'>
		<tr>
			<td colspan=2 align=center>Informacion OTI</td>
		</tr>
		<tr>
			<td align=center class=sin>IMPUESTO</td>
			<td align=center class=sin>MONTO</td>
		</tr>";

	if($cnt==1)
	{
		$dCodOTITotal=$xml->gTot->gOTITotal->dCodOTITotal;
		$dValOTITotal=$xml->gTot->gOTITotal->dValOTITotal;
		$txt=$oti["$dCodOTITotal"];
		$oti_txt="
		<tr><td>OTI $txt</td>
			<td align=right>$$dValOTITotal</td>
		</tr>";
		$tabla_OTI.="
		<tr><td>[$dCodOTITotal] $txt</td>
			<td align=right>$$dValOTITotal</td>
		</tr>";
	}
	if($cnt>1)
	{
		foreach($xml->gTot->gOTITotal AS $idx=>$dato)
		{
			$dCodOTITotal=$dato->dCodOTITotal;
			$dValOTITotal=$dato->dValOTITotal;
			$txt=$oti["$dCodOTITotal"];
			$oti_txt.="
			<tr><td>OTI $txt</td>
				<td align=right>$$dValOTITotal</td>
			</tr>";
		$tabla_OTI.="
		<tr><td>[$dCodOTITotal] $txt</td>
			<td align=right>$$dValOTITotal</td>
		</tr>";			
		}
	}
	if($cnt>0)
		$tabla_OTI.="</table>";
		


//////////TOTALES

$dTotNeto=$xml->gTot->dTotNeto;
$dTotITBMS=$xml->gTot->dTotITBMS;
$dTotGravado=$xml->gTot->dTotGravado;
$dVTot=$xml->gTot->dVTot;


			$itbms_excento=sprintf("%1.2f",$itbms_excento);
			$itbms_grabado=sprintf("%1.2f",$itbms_grabado);
			


            $tabla_totales.="<table class=totales cellspacing=0 cellpadding=0 border=1 >
                <tr><td>Total Neto</td>
                    <td align=right>$$dTotNeto</td>
                </tr>

                <tr><td>Monto Exento ITBMS</td>
                    <td align=right>$$itbms_excento</td>
                </tr>

                <tr><td>Monto Gravado ITBMS</td>
                    <td align=right>$$itbms_grabado $dTotNeto__ $dTotGravado___</td>
                </tr>

                <tr><td>ITBMS</td>
                    <td align=right>$$dTotITBMS</td>
                </tr>
				
				$isc_txt
				$oti_txt2

                <tr><td>Total Impuesto</td>
                    <td align=right>$$dTotGravado</td>
                </tr>

                <tr><td>Total</td>
                    <td align=right >$$dVTot</td>
                </tr>
            </table>";
			
///////////// 			
/// FORMA DE PAGO			
		

		$fp['01']="Crédito";
		$fp['02']="Efectivo";
		$fp['03']="Tarjeta Crédito";
		$fp['04']="Tarjeta Débito";
		$fp['05']="Tarjeta Fidelización";
		$fp['06']="Vale";
		$fp['07']="Tarjeta de Regalo";
		$fp['08']="Transf./Depósito a cta. Bancaria";
		$fp['09']="Cheque";
		$fp['10']="Punto de Pago";
		$fp['99']="otro";



            $tabla_formes_pago.="<table class=formapago cellspacing=0 cellpadding=0 border=1>
                <tr><td colspan=2 align=center>Forma de pago</td></tr>";
				
			$cnt=count($xml->gTot->gFormaPago);
			if($cnt==1)
			{
				$iFormaPago=$xml->gTot->gFormaPago->iFormaPago;
				$dVlrCuota=$xml->gTot->gFormaPago->dVlrCuota;
				$titulo=$fp["$iFormaPago"];
                $tabla_formes_pago.="<tr><td>[$iFormaPago] $titulo</td>
                    <td align=right>$dVlrCuota</td>
                </tr>";				
			}
			if($cnt>1)
			{
				
			}
			

			$dVuelto=$xml->gTot->dVuelto;
			if($dVuelto=='')
				$dVuelto="0.00";
$tabla_formes_pago.="
                <tr><td>Vuelto</td>
                    <td align=right>$dVuelto</td>
                </tr>
            </table>";


//echo $tabla_OTI	;die();
$valor.="
<br/>
	<table border=1  width='100%' cellspacing=1 cellpadding=1 >
		<tr>
			<td valign='top' width='50%'  >
				$tabla_itbms
				
				
				$tabla_isc
				<br/>
				$tabla_plazos
				<br/>
				$tabla_OTI
			</td>
			<td valign='top' width='50%' valign='top' align='right'>

					$tabla_totales
					<br/>
  				$tabla_formes_pago
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
function html_css($color_marco,$color_marco_texto,$color_texto,$fuente_texto)
{
$css="
<style type='text/css'> 
*{

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

			

                h2{
                    text-align:center;
                }
                td{

                    font-size:8px;
                    face=Comic Sans MS,arial;
                }
                th{


                }
                table.itbms {
                    
					margin: 10px;
                    font-size:12px;
                    face=Comic Sans MS,arial;

                }
                table.isc {
                    
					margin: 10px;
                    font-size:12px;
                    face=Comic Sans MS,arial;

                }				
                table.plazos {
                    
					margin: 10px;
                    font-size:12px;
                    face=Comic Sans MS,arial;

                }
                table.totales {
					
                    
					
					margin: 10px;
                    font-size:12px;
                    face=Comic Sans MS,arial;
					
  
                }
				
                table.formapago {
					
                    
					
					margin: 10px;
                    font-size:12px;
                    face=Comic Sans MS,arial;
					
                }

</style> 
";
    
    return $css;
}
///////////////////////////////////////////////////////////////////////////////

/*
					float: right;
					display: inline-block;

*/



?>
