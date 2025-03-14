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

    //$xml = simplexml_load_file($xml_archivo);

    //$ns = $xml->getNamespaces(true);
/*
    $xml->registerXPathNamespace('c', $ns['cfdi']);
    $xml->registerXPathNamespace('t', $ns['tfd']);
   */ 
//    $xml->registerXPathNamespace('i', $ns['implocal']);

/*
    $xml->registerXPathNamespace('c', $ns['cfdi']);
    $xml->registerXPathNamespace('t', $ns['tfd']);
*/

    $xml = simplexml_load_file($xml_archivo);
    $ns = $xml->getNamespaces(true);
    foreach($ns as $prefijo => $uri)
    {
    $xml->registerXPathNamespace($prefijo, $uri);
  }


     
    $version=''; //declarar version xml cfdi
    //EMPIEZO A LEER LA INFORMACION DEL CFDI E IMPRIMIRLA
    foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante)
    {
         $version= $cfdiComprobante['version'];  //3.2
         if($version =='')
            $version= $cfdiComprobante['Version'];  //3.3
          
          $fecha_expedicion= $cfdiComprobante['fecha'];
          if($fecha_expedicion == '')
            $fecha_expedicion= $cfdiComprobante['Fecha'];

          $metodo_pago=$cfdiComprobante['metodoDePago'];
          if($metodo_pago == '')
            $metodo_pago= $cfdiComprobante['MetodoPago'];
          
          $metodo_pago= formato_metodo_pago33_modulo($metodo_pago);

          
          $sello= $cfdiComprobante['sello'];
          if($sello == '')
                $sello= $cfdiComprobante['Sello'];
          
          $total=$cfdiComprobante['total'];
          if($total == '')
                $total= $cfdiComprobante['Total'];
          
          $total_=number_format((string)$total,2);

          $Moneda=$cfdiComprobante['Moneda'];
          
          
          $subtotal=$cfdiComprobante['subTotal'];
          if($subtotal == '')
                $subtotal= $cfdiComprobante['SubTotal'];
          
          
          $subtotal_=number_format((string)$subtotal,2);
          
          $descuento=$cfdiComprobante['descuento'];
          if($descuento == '')
                $descuento= $cfdiComprobante['Descuento'];
          $descuento_=number_format((string)$descuento,2);
          
          $serie=$cfdiComprobante['serie'];
          if($serie == '')
                $serie= $cfdiComprobante['Serie'];
          $folio=$cfdiComprobante['folio'];
          if($folio == '')
                $folio= $cfdiComprobante['Folio'];

          $NumCtaPago=$cfdiComprobante['NumCtaPago'];
          
          $certificado_key=$cfdiComprobante['certificado'];
          if($certificado_key == '')
                $certificado_key= $cfdiComprobante['Certificado'];
          
          $forma_pago=autoformato_impresion_modulo( $cfdiComprobante['formaDePago']);
          if($forma_pago == '')
                $forma_pago= $cfdiComprobante['FormaPago'];
          
          $forma_pago=formato_forma_pago33_modulo($forma_pago);
          
          $certificado_no=$cfdiComprobante['noCertificado'];
          if($certificado_no == '')
                $certificado_no= $cfdiComprobante['NoCertificado'];
          
          $cfdiComprobante['tipoDeComprobante'];
          $TipoDeComprobante = $cfdiComprobante['TipoDeComprobante'];
          
          $LugarExpedicion=autoformato_impresion_modulo($cfdiComprobante['LugarExpedicion']);
    }
    
    
    //cfdi relacionado
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:CfdiRelacionados') as $CfdiRelacionados)
    {
        $TipoRelacion=$CfdiRelacionados['TipoRelacion'];
        $TipoRelacion_txt = formato_cfdi_relacionados_modulo($TipoRelacion);
        $html_cfdi_relacionados="<BR/>TIPO RELACION: $TipoRelacion_txt<BR/>";
        
    }
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:CfdiRelacionados//cfdi:CfdiRelacionado') as $CfdiRelacionado)
    {
        $UUID=$CfdiRelacionado['UUID'];
        
        $html_cfdi_relacionados.="CFDI RELACIONADO: $UUID<BR/>";
    }


    //3.2
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor//cfdi:RegimenFiscal') as $RegimenFiscal)
    {
       $regimen_fiscal=autoformato_impresion_modulo($RegimenFiscal['Regimen']);
    }
    
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor)
    {
       $emisor_rfc=$Emisor['rfc'];
       if($emisor_rfc == '')
                $emisor_rfc= $Emisor['Rfc'];
       
       $emisor_nombre= $Emisor['nombre'];
       if($emisor_nombre == '')
                $emisor_nombre= $Emisor['Nombre'];
       $emisor_nombre= autoformato_impresion_modulo($emisor_nombre);
       
       $regimen_fiscal=$Emisor['RegimenFiscal'];
       
    }
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor//cfdi:DomicilioFiscal') as $DomicilioFiscal)
    {
       $emisor_pais= autoformato_impresion_modulo($DomicilioFiscal['pais']);
       
       $emisor_calle= autoformato_impresion_modulo($DomicilioFiscal['calle']);
       
       $emisor_estado= autoformato_impresion_modulo($DomicilioFiscal['estado']);
       
       $emisor_colonia= autoformato_impresion_modulo($DomicilioFiscal['colonia']);
       
       $emisor_municipio= autoformato_impresion_modulo($DomicilioFiscal['municipio']);
       
$emisor_localidad= autoformato_impresion_modulo($DomicilioFiscal['localidad']);
       
       
       
       $emisor_noExterior= autoformato_impresion_modulo($DomicilioFiscal['noExterior']);

       $emisor_noInterior= autoformato_impresion_modulo($DomicilioFiscal['noInterior']);
       
       
       $emisor_CP= autoformato_impresion_modulo($DomicilioFiscal['codigoPostal']);
       $emisor_CP=sprintf('%05d',$emisor_CP);
       
    }
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor//cfdi:ExpedidoEn') as $ExpedidoEn)
    {
       $expedido_pais= autoformato_impresion_modulo($ExpedidoEn['pais']);
       
       $expedido_calle=autoformato_impresion_modulo($ExpedidoEn['calle']);
       
       $expedido_estado=autoformato_impresion_modulo($ExpedidoEn['estado']);
       
       $expedido_colonia=autoformato_impresion_modulo($ExpedidoEn['colonia']);
       
       $expedido_noExterior=autoformato_impresion_modulo($ExpedidoEn['noExterior']);

       $expedido_noInterior=autoformato_impresion_modulo($ExpedidoEn['noInterior']);


       
       $expedido_CP=autoformato_impresion_modulo($ExpedidoEn['codigoPostal']);
       $expedido_CP=sprintf('%05d',$expedido_CP);
       
       $expedido_municipio=autoformato_impresion_modulo($ExpedidoEn['municipio']);
$expedido_localidad=autoformato_impresion_modulo($ExpedidoEn['localidad']);
       
    }

    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor)
    {
        if($version=='3.2'){   
            $receptor_rfc=$Receptor['rfc'];
            $receptor_nombre=autoformato_impresion_modulo($Receptor['nombre']);
        }
        if($version=='3.3'){   
            $receptor_rfc=$Receptor['Rfc'];
            $receptor_nombre=autoformato_impresion_modulo($Receptor['Nombre']);
            $uso_CFDi=$Receptor['UsoCFDI'];

      $ResidenciaFiscal=$Receptor['ResidenciaFiscal'];
            $NumRegIdTrib=$Receptor['NumRegIdTrib'];

    $NumRegIdTrib=$Receptor['NumRegIdTrib'];
            if($NumRegIdTrib != "")
            {
                $rfc_extranjero="<br/>Residencia Fiscal: $ResidenciaFiscal <br/>
                NumRegIdTrib: $NumRegIdTrib<br/>";
            }else{
                $rfc_extranjero="";
            }

  }
       
    }
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor//cfdi:Domicilio') as $ReceptorDomicilio)
    {
        
       $receptor_pais=autoformato_impresion_modulo($ReceptorDomicilio['pais']);
       
       $receptor_calle=autoformato_impresion_modulo($ReceptorDomicilio['calle']);
       
       $receptor_estado=autoformato_impresion_modulo($ReceptorDomicilio['estado']);
       
       $receptor_colonia=autoformato_impresion_modulo($ReceptorDomicilio['colonia']);
       
       $receptor_municipio=autoformato_impresion_modulo($ReceptorDomicilio['municipio']);
$receptor_localidad=autoformato_impresion_modulo($ReceptorDomicilio['localidad']);
       
       $receptor_noExterior=autoformato_impresion_modulo($ReceptorDomicilio['noExterior']);

       $receptor_noInterior=autoformato_impresion_modulo($ReceptorDomicilio['noInterior']);
       
       $receptor_CP=autoformato_impresion_modulo($ReceptorDomicilio['codigoPostal']);
       $receptor_CP=sprintf('%05d',$receptor_CP);
       
    }
    
    /***************************** PRODUCTOS **************************/
    if($version=='3.2'){
        $desgloce='
        <table width="100%">
           <tr class="factura_detalles_cabecera">
            <td width="44px">CNT</td>
            <td  width="75px">UNIDAD</td>
            <td width="75px">CODIGO</td>
            <td>DESCRIPCION</td>
            <td   width="100px" align="right">PRECIO UNITARIO</td>
            <td   width="100px"  align="right">IMPORTE</td>
           </tr>
        
        ';
    }
    if($version=='3.3'){
        $desgloce='<table width="100%">
           <tr class="factura_detalles_cabecera">
           <td width="44px">CveProdServ</td>
           <td width="44px">NoIdent</td>
           <td width="44px">CNT</td>
            <td width="44px">CveUnidad</td>
            <td  width="75px">UNIDAD</td>
            <td width="75px">DESCRIPCION</td>
            <td   width="100px" align="right">PRECIO UNITARIO</td>
            <td   width="100px"  align="right">IMPORTE</td>
           </tr>
        
        ';
    }
    if($TipoDeComprobante=='P'){
        $desgloce='<table width="100%">
           <tr class="factura_detalles_cabecera">
           <td width="44px">CveProdServ</td>
            <td width="44px">CNT</td>
            <td width="44px">CveUnidad</td>
            <td  width="75px">UNIDAD</td>
            <td width="75px">DESCRIPCION</td>
            <td   width="100px" align="right">PRECIO UNITARIO</td>
            <td   width="100px"  align="right">IMPORTE</td>
           </tr>
        
        ';
    }


//productos
/*
    $desgloce='<table width="100%">
       <tr class="factura_detalles_cabecera">
        <td width="44px">CNT</td>
        <td  width="75px">UNIDAD</td>
        <td width="75px">CODIGO</td>
        <td>DESCRIPCION</td>
        <td   width="100px" align="right">PRECIO UNITARIO</td>
        <td   width="100px"  align="right">IMPORTE</td>
       </tr>
    
    ';
    */
    $tmp=1;
    
    
    

    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Conceptos//cfdi:CuentaPredial') as $PredialData)
    {
        $predial=(string) $PredialData['numero'];
        if($predial == '')
            $predial=(string) $PredialData['Numero'];
            
        if($predial!='')
        {
            $predial="<br/>PREDIAL : $predial";
        }
    }

    $subtotal_productos=0.00;
    
    $subtotal_productos=0.00;
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Conceptos//cfdi:Concepto') as $Concepto)
    {
        if($version=='3.2')
        {  
            $unidad=$Concepto['unidad'];
            $importe=$Concepto['importe'];
            $cantidad=$Concepto['cantidad'];
            $descripcion=$Concepto['descripcion'];
            $descripcion=str_replace("\n","<br/>",$descripcion);
            //$descripcion=str_replace("\r","<br/>",$descripcion);
            $descripcion=$descripcion.$predial;
            $precio_unitario=$Concepto['valorUnitario'];
            $codigo=$Concepto['noIdentificacion'];
            $numero=$Concepto['numero'];
            if($tmp==0)
            {
                $class='factura_detalles_renglon1';
                $tmp=1;
            }else{    
                $class='factura_detalles_renglon2';
                $tmp=0;
            }
            $descripcion=autoformato_impresion_modulo($descripcion);
            $precio_unitario_=number_format((string)$precio_unitario,2);     
            $importe_=number_format((string)$importe,2);  
            $subtotal_productos+=(float)$importe;
            $desgloce.="
                <tr class='$class'>
                <td>$cantidad</td>
                <td>$unidad </td>
                <td>$codigo </td>
                <td>$descripcion</td>
                <td align='right'>$$precio_unitario_</td>
                <td  align='right'>$$importe_</td>
                </tr>
                ";
        }
        if($version=='3.3')
        {  
            $CveProdServ=$Concepto['ClaveProdServ'];
            $CveUnidad=$Concepto['ClaveUnidad'];
            
            
            $unidad=$Concepto['Unidad'];
            $importe=$Concepto['Importe'];
            $cantidad=$Concepto['Cantidad'];
            $descripcion=$Concepto['Descripcion'];
            $descripcion=str_replace("\n","<br/>",$descripcion);
            //$descripcion=str_replace("\r","<br/>",$descripcion);
            $descripcion=$descripcion.$predial;
            $precio_unitario=$Concepto['ValorUnitario'];
            $codigo=$Concepto['NoIdentificacion'];
            $numero=$Concepto['numero'];
            if($tmp==0)
            {
                $class='factura_detalles_renglon1';
                $tmp=1;
            }else{    
                $class='factura_detalles_renglon2';
                $tmp=0;
            }
            $descripcion=autoformato_impresion_modulo($descripcion);
            $precio_unitario_=number_format((string)$precio_unitario,2);     
            $importe_=number_format((string)$importe,2);  
            $subtotal_productos+=(float)$importe;
            /*
            $desgloce.="
                <tr class='$class'>
                <td>$CveProdServ</td>
                <td>$codigo</td>
                <td>$cantidad</td>
                <td>$CveUnidad </td>
                <td>$unidad </td>
                <td>$descripcion</td>
                <td align='right'>$$precio_unitario_</td>
                <td  align='right'>$$importe_</td>
                </tr>
                ";
             */   
            if($TipoDeComprobante != 'P')
            {
                $desgloce.="
                <tr class='$class'>
                <td>$CveProdServ</td>
                <td>$codigo</td>
                <td>$cantidad</td>
                <td>$CveUnidad </td>
                <td>$unidad </td>
                <td>$descripcion</td>
                <td align='right'>$$precio_unitario_</td>
                <td  align='right'>$$importe_</td>
                </tr>
                ";    
            }else{  //ES UN PAGO
                $desgloce.="
                <tr class='$class'>
                <td>$CveProdServ</td>
                <td>$cantidad</td>
                <td>$CveUnidad </td>
                <td>$unidad </td>
                <td>$descripcion</td>
                <td align='right'>$$precio_unitario_</td>
                <td  align='right'>$$importe_</td>
                </tr>
                ";
            }    
        }
    }
    $desgloce.='</table>';
    
    $isr_retenido=0.00;
    $iva_retenido=0.00;
    foreach ($xml->xpath('//tfd:TimbreFiscalDigital') as $tfd)
    {
        if($version=='3.2')
        {
            $timbre_selloCFD= $tfd['selloCFD'];
            $timbre_fecha= $tfd['FechaTimbrado'];
            $timbre_uuid= $uuid=$tfd['UUID'];
            $timbre_noCertificadoSAT= $tfd['noCertificadoSAT'];
            $timbre_version= $tfd['version'];
            $timbre_selloSAT = $sellosat=$tfd['selloSAT'];
        }
        if($version=='3.3')
        {
            $timbre_selloCFD= $tfd['SelloCFD'];
            $timbre_fecha= $tfd['FechaTimbrado'];
            $timbre_uuid= $uuid=$tfd['UUID'];
            $timbre_noCertificadoSAT= $tfd['NoCertificadoSAT'];
            $timbre_version= $tfd['Version'];
            $timbre_selloSAT = $sellosat=$tfd['SelloSAT'];
        }   

    }
    
    //TRANSLADOS (impuestos comprobante)
    $total_translados=$total_translados_locales=0;
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Impuestos//cfdi:Traslados//cfdi:Traslado') as $Traslado)
    {
        if($version=='3.2')
        {
            $tasa=$Traslado['tasa'];
            $importe=$Traslado['importe'];
            $importe_=number_format((string)$importe,2);
            $impuesto= $Traslado['impuesto'];
            $total_translados=$total_translados+(float)$importe;
            $tasa_txt=number_format((string)$tasa,2);
            $iva_txt.="
                    <tr>
                        <td class='factura_totales'>
                        $impuesto ($tasa_txt%)
                        </td>
                        <td class='factura_totales'>
                         $importe_ 
                        </td>
                    </tr>
            ";
        }
        if($version=='3.3')
        {
            $Base=$Traslado['Base'];         //COMPARA SI ES IMPUESTO DE PRODUCTO O DE COMPROBANTE
            $tasa=$Traslado['TasaOCuota'];
            $importe=$Traslado['Importe'];
            $importe_=number_format((string)$importe,2);
            $impuesto= $Traslado['Impuesto'];
            $impuesto_txt=formato_impuestos_modulo($impuesto);
            $TipoFactor= $Traslado['TipoFactor'];
            if($Base =='')
            {
                $total_translados=$total_translados+(float)$importe;
                $tasa_txt=doubleval($tasa)*100;
                //$tasa_txt=number_format((string)$tasa,2);
                //$tasa_txt=number_format((string)$tasa_txt,2);
                $iva_txt.="
                        <tr>
                            <td class='factura_totales'>
                            $impuesto_txt ($tasa_txt%)  $Base
                            </td>
                            <td class='factura_totales'>
                             $$importe_ 
                            </td>
                        </tr>
                ";
            }
        }
    }


    //LOCALES
    //CFDI 3.2  
    $cadena=file_get_contents($xml_archivo);
    if(strpos($cadena,'ImpuestosLocales')>0)
    {

                    list($tmp,$cadena,$tmp)=explode('ImpuestosLocales',$cadena);
                
                    list($tmp,$cadena2)=explode('>',(string)$cadena,2);//
                    $cadena="<implocal:ImpuestosLocales  >
                    $cadena2"."ImpuestosLocales>
                    ";
                    $xml2 = simplexml_load_string($cadena);
                    $arr = object2array($xml2);
                    $TrasladosLocales=$arr['TrasladosLocales'];
                
                //TRANSLADO LOCAL
                
                    foreach($TrasladosLocales AS $llave_=>$TrasladosLocal)
                    {
                        $ImpLocTrasladado=$TrasladosLocal['@attributes']['ImpLocTrasladado'];
                    if($ImpLocTrasladado=='')
                        $ImpLocTrasladado=$TrasladosLocal['ImpLocTrasladado'];
            
            
                    $Importe=$TrasladosLocal['@attributes']['Importe'];
                    if($Importe=='')
                        $Importe=$TrasladosLocal['Importe']; 
            
            
                    $TasadeTraslado=$TrasladosLocal['@attributes']['TasadeTraslado'];
                    if($TasadeTraslado=='')
                        $TasadeTraslado=$TrasladosLocal['TasadeTraslado'];
            
                   $total_translados=$total_translados+(float)$Importe;
            
            //echo "TL $ImpLocTrasladado $TasadeTraslado% $Importe<br>";
                    $importe_=number_format((string)$Importe,2);
                    $iva_txt.="
                            <tr>
            
                                <td class='factura_totales'>
                                (LOCAL) $ImpLocTrasladado ($TasadeTraslado%) 
                                </td>
                                <td class='factura_totales'>
                                 $importe_
                                </td>
                            </tr>
                    ";        
                }
            
            
            //RETENCIONES LOCAL
              $RetencionesLocales=$arr['RetencionesLocales'];
                foreach($RetencionesLocales AS $llave_=>$RetencionesLocal)
                {
                    $ImpLocRetenido=$RetencionesLocal['@attributes']['ImpLocRetenido'];
                    if($ImpLocRetenido=='')
                        $ImpLocRetenido=$RetencionesLocal['ImpLocRetenido'];
            
            
                    $Importe=$RetencionesLocal['@attributes']['Importe'];
                    if($Importe=='')
                        $Importe=$RetencionesLocal['Importe']; 
            
            
                    $TasadeRetencion=$RetencionesLocal['@attributes']['TasadeRetencion'];
                    if($TasadeRetencion=='')
                        $TasadeRetencion=$RetencionesLocal['TasadeRetencion'];
            
                    $importe_=number_format((string)$Importe,2);
                   $importe_retenciones=$importe_retenciones+$Importe;

                   $retenciones_txt.="
                                   <tr>
                                        <td class='factura_totales'>
                                        RET LOCAL $ImpLocRetenido ($TasadeRetencion%) $
                                        </td>
                                        <td class='factura_totales'>
                                         $importe_
                                        </td>
                                    </tr>
            
                   ";                    
                }
            


        
    }
       

    //CFDI 3.3
    //LOCALES 2
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//implocal:ImpuestosLocales') as $ImpuestosLocales)
    {
        $TotaldeTraslados=$ImpuestosLocales['TotaldeTraslados'];
        $TotaldeRetenciones=$ImpuestosLocales['TotaldeRetenciones'];
    }
    //LOCALES 2 RETENCIONES
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//implocal:ImpuestosLocales//implocal:RetencionesLocales') as $RetencionesLocales)
    {
        $ImpLocRetenido=$RetencionesLocales['ImpLocRetenido'];
        $TasadeRetencion=$RetencionesLocales['TasadeRetencion'];
        $TasadeRetencion_txt=number_format((string)$TasadeRetencion,2);
        $Importe=$RetencionesLocales['Importe'];
        
        $retenciones_txt.="
                           <tr>
                                <td class='factura_totales'>
                                RET LOCAL $ImpLocRetenido ($TasadeRetencion_txt%) $
                                </td>
                                <td class='factura_totales'>
                                 $Importe
                                </td>
                            </tr>
    
           ";       
    }
    
    //LOCALES 2 TRASLADOS
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//implocal:ImpuestosLocales//implocal:TrasladosLocales') as $TrasladosLocales)
    {
        $ImpLocTrasladado=$TrasladosLocales['ImpLocTrasladado'];
        $TasadeTraslado=$TrasladosLocales['TasadeTraslado'];
        $TasadeTraslado_txt=number_format((string)$TasadeTraslado,2);
        $importe_=$TrasladosLocales['Importe'];
        
        $iva_txt.="
                    <tr>
    
                        <td class='factura_totales'>
                        (LOCAL) $ImpLocTrasladado ($TasadeTraslado_txt%) 
                        </td>
                        <td class='factura_totales'>
                         $importe_
                        </td>
                    </tr>
            ";        
    }

//RETENCIONES
//    $retenciones_txt='';
//    $importe_retenciones=0.00;
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Impuestos//cfdi:Retenciones//cfdi:Retencion') as $Retencion)
    {
       if($version=='3.2')
       {
           $importe=$Retencion['importe'];
           $impuesto=$Retencion['impuesto'];
           $importe_retenciones=$importe_retenciones+(float)$importe;
           $importe_=number_format((string)$importe,2);
           $retenciones_txt.="
                           <tr>
                                <td class='factura_totales'>
                                RET $impuesto $
                                </td>
                                <td class='factura_totales'>
                                 $importe_
                                </td>
                            </tr>
            ";
        }
        if($version=='3.3')
        {
            $Base=$Retencion['Base'];
            $importe=$Retencion['Importe'];
            $impuesto=$Retencion['Impuesto'];
            $importe_retenciones=$importe_retenciones+(float)$importe;
            $importe_=number_format((string)$importe,2);
            $impuesto_txt_ret=formato_impuestos_modulo($impuesto);
            if($Base =='')
            {
                $retenciones_txt.="
                        <tr>
                            <td class='factura_totales'>
                            RET $impuesto_txt_ret $
                            </td>
                            <td class='factura_totales'>
                             $importe_
                            </td>
                        </tr>
                ";
            }
        }
    }
    if($importe_retenciones==0)
    {
       $retenciones_txt=''; 
    }


// SI HAY RETENCIONES MUESTA EL SUBTOTAL ANTES DE RETENCIONES CON IMPUESTOS AGREGADOS
    if($retenciones_txt!='')
    {
        $subtotal_con_retenciones=(float)$subtotal+(float)$total_translados+(float)$total_translados_locales;

        $subtotal_con_retenciones=number_format($subtotal_con_retenciones-$descuento_,2);
        //$subtotal_ //aki
       $retenciones_txt="
                       <tr>
                            <td class='factura_totales'>
                            SUB TOTAL $
                            </td>
                            <td class='factura_totales'>
                            
                             $subtotal_con_retenciones
                            </td>
                        </tr>
                        $retenciones_txt
       ";
        
    }
    
    //INE
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//ine:INE') as $INE)
    {
        $TipoProceso=$INE['TipoProceso'];
        $TipoComite=$INE['TipoComite'];
        $IdContabilidad=$DescInmueble['IdContabilidad'];
        /*
        $html_Ine.= "<hr/><div>
                                INE:<br/><br/>
                                Tipo de Proceso: $TipoProceso Comite: $TipoComite Contabilidad $IdContabilidad<br/>
                                Col.: $Colonia Localidad: $Localidad<br/>
                                Estado: $Estado Pais: $Pais<br/>
                                C.P.: $CodigoPostal
                                </div>
                                ";
                                */
         $html_Ine.= "<hr/><div>
                                INE:<br/><br/>
                                Tipo de Proceso: $TipoProceso Comite: $TipoComite Contabilidad $IdContabilidad<br/>
                            </div>
                                ";
    }
    //
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//ine:INE//ine:Entidad') as $Entidad)
    {
        $ClaveEntidad=$Entidad['ClaveEntidad'];
        $Ambito=$Entidad['Ambito'];
        $TipoComite=$Entidad['TipoComite'];
        
        $html_Entidad.= "<hr/>
                        <div>
                            Clave Entidad: $ClaveEntidad Ambito: $Ambito Tipo Comite $TipoComite<br/>
                        </div>
                                ";
        
        foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//ine:INE//ine:Entidad//ine:Contabilidad') as $Entidad_Contabilidad)
        {
            $IdContabilidad=$Entidad_Contabilidad['IdContabilidad'];
            
            $html_Entidad.= "
                            <div>
                                Contabilidad: $IdContabilidad<br/>
                            </div>
                                    ";
            }
    }
    
    if($TipoProceso!=''  OR $TipoProceso !='no_proceso')
    {
    $INE_general="
            <div>
            <table width='70%' border=0  >
                <tr><td>$html_Ine</td></tr>
                <tr><td>$html_Entidad</td></tr>
            </table>
            </div>
            ";
    }   


////////
//PAGOS
    //CANTIDAD DE NODOS PAGO
    
    $np = count($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//pago10:Pagos//pago10:Pago'));
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//pago10:Pagos') as $Pagos)
    {
    
    }

    for($_P=1;$_P<=$np;$_P++ )
    {
        foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//pago10:Pagos//pago10:Pago['.$_P.']') as $Pago)
        {
            $HTML_PAGO.="<table width='100%'>
                          <tr class='factura_detalles_cabecera'><td colspan='3'>PAGO</td></tr>"; 
            
            $FechaPago=$Pago['FechaPago'];
            $FormaDePagoP=$Pago['FormaDePagoP'];
            $MonedaP=$Pago['MonedaP'];
            $TipoCambioP=$Pago['TipoCambioP'];
            if($MonedaP == 'USD'){
                $td_tipoCambioP="Tipo de Cambio $TipoCambioP";
            }
            $tr_moneda="<tr><td width='33%'colspan='3'>Moneda Pago: $MonedaP $td_tipoCambioP</td></tr>";
                         
            $Monto_Pago=$Pago['Monto'];
            $NumOperacion=$Pago['NumOperacion'];
            if($NumOperacion !='')
                $NumOperacion_txt="Num. operacion: $NumOperacion <br/>";
                
            $RfcEmisorCtaOrd=$Pago['RfcEmisorCtaOrd'];
            if($RfcEmisorCtaOrd !='')
                $RfcEmisorCtaOrd_txt="RFC Emisor cuenta: $RfcEmisorCtaOrd <br/>";
                
            $NomBancoOrdExt=$Pago['NomBancoOrdExt'];
            if($NomBancoOrdExt !='')
                $NomBancoOrdExt_txt="Banco: $NomBancoOrdExt ";  
            
            $CtaOrdenante=$Pago['CtaOrdenante'];
            if($CtaOrdenante !='')
                $CtaOrdenante_txt="Num. Cuenta Ordenante: $CtaOrdenante <br/>";  
                
            $RfcEmisorCtaBen=$Pago['RfcEmisorCtaBen'];
            if($RfcEmisorCtaBen !='')
                $RfcEmisorCtaBen_txt="RFC Cuenta Beneficiario: $RfcEmisorCtaBen <br/>";
                
            $CtaBeneficiario=$Pago['CtaBeneficiario'];
            if($CtaBeneficiario !='')
                $CtaBeneficiario_txt="Num Cuenta Beneficiario: $CtaBeneficiario";
            
            $FormaDePagoP_txt=formato_forma_pago33_modulo($FormaDePagoP);
            $Monto_Pago_txt=number_format((string)$Monto_Pago,2);
            
            $TipoCadPago=$Pago['TipoCadPago'];
            $CertPago=$Pago['CertPago'];
            $CadPago=$Pago['CadPago'];
            $SelloPago=$Pago['SelloPago'];
            if($TipoCadPago !='')
            {
                $tr_="<tr><td colspan='3'>
                        Cadena pago $TipoCadPago <br/>
                        Certificado pago $CertPago <br/>
                        Cadena origianal pago $CertPago <br/>
                        Sello pago $SelloPago <br/>
                        </td></tr>";
            }
           $HTML_PAGO.="<tr>
                            <td width='30%'>Monto: $Monto_Pago_txt <br/> $NumOperacion_txt Fecha de pago: $FechaPago <br/> Forma de pago: $FormaDePagoP_txt</td>
                            <td width='30%'>$RfcEmisorCtaOrd_txt $NomBancoOrdExt_txt </td>
                            <td width='30%'>$CtaOrdenante_txt $RfcEmisorCtaBen_txt  $CtaBeneficiario_txt</td>
                        </tr>
                        $tr_moneda
                        $tr_
                        ";
            $HTML_PAGO.="</table>";
            
            
            foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//pago10:Pagos//pago10:Pago['.$_P.']//pago10:DoctoRelacionado') as $DoctoRelacionado)
            {
                $HTML_PAGO.="<table width='100%'>
                <tr class='factura_detalles_cabecera'><td>FACT</td><td>UUID</td><td>Metodo de pago</td><td>Saldo anterior</td><td>Monto Pagado</td><td>Saldo Pendiente</td></tr>";
                
                $SerieDocumento=$DoctoRelacionado['Serie'];
                $FolioDocumento=$DoctoRelacionado['Folio'];
                $IdDocumento=$DoctoRelacionado['IdDocumento'];
                $MonedaDR=$DoctoRelacionado['MonedaDR'];
                $TipoCambioDR=$DoctoRelacionado['TipoCambioDR'];
                $MetodoDePagoDR=$DoctoRelacionado['MetodoDePagoDR'];
                $NumParcialidad=$DoctoRelacionado['NumParcialidad'];
                $ImpSaldoAnt=$DoctoRelacionado['ImpSaldoAnt'];
                $ImpPagado=$DoctoRelacionado['ImpPagado'];
                $ImpSaldoInsoluto=$DoctoRelacionado['ImpSaldoInsoluto'];
                $MetodoDePagoDR_txt=formato_metodo_pago33_modulo($MetodoDePagoDR);
                
                $ImpSaldoAnt_txt=number_format((string)$ImpSaldoAnt,2);
                $ImpPagado_txt=number_format((string)$ImpPagado,2);
                $ImpSaldoInsoluto_txt=number_format((string)$ImpSaldoInsoluto,2);
                
                if($MonedaDR == 'USD')
                {
                    $td_MonedaDR="<BR>Moneda: $MonedaDR <br/>Tipo de Cambio: $$TipoCambioDR";
                }                                
                
                $HTML_PAGO.="
                <tr><td>$SerieDocumento$FolioDocumento </td><td>$IdDocumento</td><td>$MetodoDePagoDR_txt $td_MonedaDR</td><td>$$ImpSaldoAnt_txt</td><td>$$ImpPagado_txt</td><td>$$ImpSaldoInsoluto_txt</td></tr>";
                
                $HTML_PAGO.="</table>";
            }
            
            $HTML_PAGO.="<hr/>";
        }
    }
    if($TipoDeComprobante !='P')
    {
        $HTML_PAGOS="";   
    }else{
        $HTML_PAGOS=$HTML_PAGO;
    }
        
//////////
//NOMINAS
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//nomina:Nomina') as $Nomina)
    {
        $RegistroPatronal= autoformato_impresion_modulo($Nomina['RegistroPatronal']);
        $NumEmpleado= autoformato_impresion_modulo($Nomina['NumEmpleado']);
        $CURP= autoformato_impresion_modulo($Nomina['CURP']);
        
        $TipoRegimen= autoformato_impresion_modulo($Nomina['TipoRegimen']);
        
        $NumSeguridadSocial= autoformato_impresion_modulo($Nomina['NumSeguridadSocial']);
        $FechaPago= autoformato_impresion_modulo($Nomina['FechaPago']);
        $FechaInicialPago= autoformato_impresion_modulo($Nomina['FechaInicialPago']);
        $FechaFinalPago= autoformato_impresion_modulo($Nomina['FechaFinalPago']);
        $NumDiasPagados= autoformato_impresion_modulo($Nomina['NumDiasPagados']);
        $Departamento= autoformato_impresion_modulo($Nomina['Departamento']);
        $Banco= autoformato_impresion_modulo($Nomina['Banco']);
        $CLABE= autoformato_impresion_modulo($Nomina['CLABE']);
        $FechaInicioRelLaboral= autoformato_impresion_modulo($Nomina['FechaInicioRelLaboral']);
        $Antiguedad= autoformato_impresion_modulo($Nomina['Antiguedad']);
        $Puesto= autoformato_impresion_modulo($Nomina['Puesto']);
        $TipoContrato= autoformato_impresion_modulo($Nomina['TipoContrato']);
        $TipoJornada= autoformato_impresion_modulo($Nomina['TipoJornada']);
        $PeriodicidadPago= autoformato_impresion_modulo($Nomina['PeriodicidadPago']);
        $SalarioBaseCotApor= autoformato_impresion_modulo($Nomina['SalarioBaseCotApor']);
        $RiesgoPuesto= autoformato_impresion_modulo($Nomina['RiesgoPuesto']);
        $SalarioDiarioIntegrado= autoformato_impresion_modulo($Nomina['SalarioDiarioIntegrado']);
        $RegistroPatronal= autoformato_impresion_modulo($Nomina['RegistroPatronal']);
       
    }
    if($CURP!='')
    {
        $letra=10;
        $nomina_general="
        <div>
        <table width='100%' border=0 class='factura_titulo_ch' >
            <tr>
            <td width='50%' style='font-size:$letra px !important'> 
            Registro Patronal : $RegistroPatronal</td><td style='font-size:$letra px !important'> Fecha Inicio Laboral : $FechaInicioRelLaboral
            </td></tr><tr><td style='font-size:$letra px !important'> Tipo de Regimen : $TipoRegimen</td><td style='font-size:$letra px !important'> Fecha Inicial : $FechaInicialPago
            </td></tr><tr><td style='font-size:$letra px !important'> Numero de Empleado : $NumEmpleado</td><td style='font-size:$letra px !important'> Fecha Final : $FechaFinalPago
            </td></tr><tr><td style='font-size:$letra px !important'> CURP : $CURP</td><td style='font-size:$letra px !important'> Fecha de Pago : $FechaPago
            </td></tr><tr><td style='font-size:$letra px !important'> No. Seguro Social : $NumSeguridadSocial</td><td style='font-size:$letra px !important'> Dias pagados : $NumDiasPagados
            </td></tr><tr><td style='font-size:$letra px !important'> DEPARTAMENTO : $Departamento</td><td style='font-size:$letra px !important'> Antiguedad : $Antiguedad semanas
            </td></tr><tr><td style='font-size:$letra px !important'> PUESTO : $Puesto</td><td style='font-size:$letra px !important'> PERIODICIDAD : $PeriodicidadPago
            </td></tr><tr><td style='font-size:$letra px !important'> BANCO :$Banco</td><td style='font-size:$letra px !important'> SALARIO BASE : $SalarioBaseCotApor
            </td></tr><tr><td style='font-size:$letra px !important'> CLAVE : $CLABE</td><td style='font-size:$letra px !important'> RIESGO : $RiesgoPuesto
            </td></tr><tr><td style='font-size:$letra px !important'> TIPO CONTRATO : $TipoContrato</td><td style='font-size:$letra px !important'> SALARIO DIARIO INTEGRADO: $SalarioDiarioIntegrado
            </td></tr><tr><td style='font-size:$letra px !important'> TIPO JORNADA : $TipoJornada</td><td>
    
            </td></tr>
        </table>
        </div>    
        ";
    }

//NOMINAS PERCEPCIONES
    $NominaPercepciones='';
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//nomina:Percepciones//nomina:Percepcion') as $Percepciones)
    {
        
        $TipoPercepcion= autoformato_impresion_modulo($Percepciones['TipoPercepcion']);
        $Clave= autoformato_impresion_modulo($Percepciones['Clave']);
        $Concepto= autoformato_impresion_modulo($Percepciones['Concepto']);
        $ImporteGravado= autoformato_impresion_modulo($Percepciones['ImporteGravado']);
        $ImporteExento= autoformato_impresion_modulo($Percepciones['ImporteExento']);
        
        
        $NominaPercepciones.="<tr><td style='font-size:$letra px !important'>$TipoPercepcion</td><td style='font-size:$letra px !important'>$Clave</td><td style='font-size:$letra px !important'>$Concepto</td><td style='font-size:$letra px !important'>$ImporteGravado</td><td style='font-size:$letra px !important'>$ImporteExento</td></tr>";
        

    }
    if(strlen($NominaPercepciones)>10)
    {
        $NominaPercepciones="
        <b>PERCEPCIONES:</b>
            <table width='100%'>
       <tr class='factura_detalles_cabecera'  >
        <td style='font-size:$letra px !important'>TP</td>
        <td style='font-size:$letra px !important'>CLAVE</td>
        <td style='font-size:$letra px !important' >CONCEPTO</td>
        <td style='font-size:$letra px !important'>IMP GRAVADO</td>
        <td style='font-size:$letra px !important'>IMP EXENTO</td>
       </tr>

            $NominaPercepciones
            </table>
        ";
    }


//NOMINAS DEDUCCIONES
    $NominaDeducciones='';
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//nomina:Deducciones//nomina:Deduccion') AS $Deducciones)
    {
        
        $TipoDeduccion= autoformato_impresion_modulo($Deducciones['TipoDeduccion']);
        $Clave= autoformato_impresion_modulo($Deducciones['Clave']);
        $Concepto= autoformato_impresion_modulo($Deducciones['Concepto']);
        $ImporteGravado= autoformato_impresion_modulo($Deducciones['ImporteGravado']);
        $ImporteExento= autoformato_impresion_modulo($Deducciones['ImporteExento']);
        
        $NominaDeducciones.="<tr><td style='font-size:$letra px !important'>$TipoDeduccion</td><td style='font-size:$letra px !important'>$Clave</td><td style='font-size:$letra px !important'>$Concepto</td><td style='font-size:$letra px !important'>$ImporteGravado</td><td style='font-size:$letra px !important'>$ImporteExento</td></tr>";
        

    }
    if(strlen($NominaDeducciones)>10)
    {
        $NominaDeducciones="
        <b>DEDUCCIONES:</b>
            <table width='100%'>
       <tr class='factura_detalles_cabecera'  >
        <td style='font-size:$letra px !important'>TP</td>
        <td style='font-size:$letra px !important'>CLAVE</td>
        <td style='font-size:$letra px !important'>CONCEPTO</td>
        <td style='font-size:$letra px !important'>IMP GRAVADO</td>
        <td style='font-size:$letra px !important'>IMP EXENTO</td>
       </tr>

            $NominaDeducciones
            </table>
        ";
    }



//NOMINAS HORAS EXTRA
    $NominaHorasExtras='';
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//nomina:HorasExtras//nomina:HorasExtra') AS $HoraExtra)
    {
        
        $Dias= autoformato_impresion_modulo($HoraExtra['Dias']);
        $TipoHoras= autoformato_impresion_modulo($HoraExtra['TipoHoras']);
        $HorasExtra= autoformato_impresion_modulo($HoraExtra['HorasExtra']);
        
        $NominaHorasExtras.="<tr><td style='font-size:$letra px !important'>$Dias</td><td style='font-size:$letra px !important'>$TipoHoras</td><td style='font-size:$letra px !important'>$HorasExtra</td></tr>";

        

    }
    if(strlen($NominaHorasExtras)>10)
    {
        $NominaHorasExtras="
        <b>HORAS EXTRA:</b>
            <table width='100%'>
       <tr class='factura_detalles_cabecera'  >
        <td style='font-size:$letra px !important'>DIAS</td>
        <td style='font-size:$letra px !important'>TIPO</td>
        <td style='font-size:$letra px !important'>HORAS EXTRA</td>
       </tr>

            $NominaHorasExtras
            </table>
        ";
    }



//NOMINAS INCAPACIDADES
    $NominaIncapacidades='';
    foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Complemento//nomina:Incapacidades//nomina:Incapacidad') AS $Incapacidad)
    {
        
        $DiasIncapacidad= autoformato_impresion_modulo($Incapacidad['DiasIncapacidad']);
        $TipoIncapacidad= autoformato_impresion_modulo($Incapacidad['TipoIncapacidad']);
        $Descuento= autoformato_impresion_modulo($Incapacidad['Descuento']);
        
        $NominaIncapacidades.="<tr><td style='font-size:$letra px !important'>$DiasIncapacidad</td><td style='font-size:$letra px !important'>$TipoIncapacidad</td><td style='font-size:$letra px !important'>$Descuento</td></tr>";

        

    }
    if(strlen($NominaIncapacidades)>10)
    {
        $NominaIncapacidades="
        <b>INCAPACIDADES:</b>
            <table width='100%'>
       <tr class='factura_detalles_cabecera'  >
        <td style='font-size:$letra px !important'>DIAS</td>
        <td style='font-size:$letra px !important'>TIPO</td>
        <td style='font-size:$letra px !important'>DESCUENTO</td>
       </tr>

            $NominaIncapacidades
            </table>
        ";
    }




$nominas_txt="
<table>
<tr valign='top'>
    <td width='50%' valign='top'>
        $NominaPercepciones
        $NominaHorasExtras
    </td>
    <td width='50%'>
        $NominaDeducciones
        $NominaIncapacidades
    </td>

</tr>
</table>
";
$emisor_municipio2=trim(strtolower($emisor_municipio2));
$emisor_municipio2=str_replace(' ','',$emisor_municipio2);
$emisor_municipio2=str_replace('.','',$emisor_municipio2);
$emisor_municipio2=str_replace(',','',$emisor_municipio2);

$emisor_localidad2=trim(strtolower($emisor_localidad2));
$emisor_localidad2=str_replace(' ','',$emisor_localidad2);
$emisor_localidad2=str_replace('.','',$emisor_localidad2);
$emisor_localidad2=str_replace(',','',$emisor_localidad2);


if($emisor_municipio2==$emisor_localidad2)
{
    $emisor_localidad='';
}

//////////  DISEÑO ////////////

if($version=='3.2')
{
    $Emisor="
    <div class='factura_emisor factura_cuadro'>
        <div class='factura_titulo_ch'>EMISOR:</div>
        <div class='factura_titulo_empresa'>$emisor_nombre </div>
        <div> RFC: <b>$emisor_rfc</b></div>
        
        $emisor_calle $emisor_noExterior $emisor_noInterior, $emisor_colonia CP:$emisor_CP
        <br/>
        $emisor_municipio $emisor_localidad,
        $emisor_estado,
        $emisor_pais
        
    </div>
    ";
}
if($version=='3.3')
{
    $Emisor="
    <div class='factura_emisor factura_cuadro'>
        <div class='factura_titulo_ch'>EMISOR:</div>
        <div class='factura_titulo_empresa'>$emisor_nombre </div>
        <div> RFC: <b>$emisor_rfc</b></div>
        
        <br/>
     </div>
    ";
}

$ciudad_estado="$emisor_municipio $emisor_localidad $emisor_estado,";
if($expedido_municipio==$expedido_localidad)
{
    $expedido_localidad='';
}
$ExpedidoEn='';


$ExpedidoEn="
<div class='factura_expedidoen factura_cuadro_linea'>
    <span class='factura_titulo_ch'>EXPEDIDO EN:</span>
    $expedido_calle $expedido_noExterior $expedido_noInterior, $expedido_colonia 
    $expedido_municipio $expedido_localidad, $expedido_estado, $expedido_pais CP:$expedido_CP
</div>

";

if($version=='3.3')
{
    $ExpedidoEn='<hr/>';
}


        $idreceptor=$datosfacturas['idreceptor'];
//        $datosreceptor=lee_sql_mash(sql_agenda($idreceptor));
        $Fiscal_Orientacion=$datosreceptor['Fiscal_Orientacion'];

if($receptor_municipio==$receptor_localidad)
{
    $receptor_localidad='';
}

if($version=='3.2')
{
    $Receptor="
    <div class='factura_receptor factura_cuadro '>
        <div class='factura_titulo_ch'>RECEPTOR:</div>
        <div class='factura_titulo_empresa'>$receptor_nombre  </div>
        RFC: <b> $receptor_rfc </b><br/>
        
        $receptor_calle $receptor_noExterior $receptor_noInterior $Fiscal_Orientacion $receptor_colonia CP:$receptor_CP
        <br/>
        $receptor_municipio  $receptor_localidad,
         $receptor_estado,
         $receptor_pais
    
    </div>
    ";
}
if($version=='3.3')
{
    $Receptor="
    <div class='factura_receptor factura_cuadro '>
        <div class='factura_titulo_ch'>RECEPTOR:</div>
        <div class='factura_titulo_empresa'>$receptor_nombre  </div>
        RFC: <b> $receptor_rfc </b><br/>
        Uso CFDI: $uso_CFDi
  $rfc_extranjero
        
    </div>
    ";
}

$DatosGenerales="
<div class='factura_titulo_serie_folio'>$titulo</div>
<div class='factura_datosgenerales'>

<div class='factura_titulo_serie_folio'>$tipo_factura : $serie$folio</div>

<div> Folio Fiscal : $timbre_uuid  </div>
<div> Numero Certificado CSD : $certificado_no  </div>
<div> Lugar y Fecha: $LugarExpedicion $fecha_expedicion  </div>

</div>
";
//$logo="<div class='logo'><img src='http://192.168.1.111/multifacturas_docs/multifacturas_sdk_desarrollo/$logo'></div>";

global $masheditor;
$ruta_logo=$masheditor['carpeta_instalacion']."$logo";

/*
if(!file_exists($ruta_logo))
{
    $ruta_logo="c:/cfdipdf/transparente.gif";
}
else
{
    $conflogo['max']=220;
    if(function_exists('ver_imagen_mash'))
    {
        $ruta_logo=ver_imagen_mash($ruta_logo,250,0,$conflogo);
    }
    else
    {
        $ruta_logo=$ruta_logo;
    }    
}
*/

$cabecera="<table width='100%'>
        <tr valign='top'>
            <td width='260'><img  width='250px' src='$ruta_logo'></td>

            <td >$DatosGenerales</td>
        </tr>
    </table>
    $ExpedidoEn
    $html_cfdi_relacionados
    <table width='100%'>
        <tr valign='top'>
            <td width='50%'>$Emisor</td>
            <td width='50%'>$Receptor</td>
        </tr>
    </table>

";
//$nomina_general
$certificado_key= $datosjson['datoscertificado']['SAT_Llave_PEM'];

//cfd_lee_cadena($sello,$certificado_key,$timbre_noCertificadoSAT);
//echo base64_decode($timbre_selloSAT);

    $cadena_sat="||$timbre_version|$timbre_uuid|$timbre_fecha|$timbre_selloCFD|$timbre_noCertificadoSAT||";

$longitud=95;
$sello = wordwrap($sello,$longitud,'<br>',true);
$timbre_selloSAT = wordwrap($timbre_selloSAT,$longitud,'<br>',true);
$cadena_sat = wordwrap($cadena_sat,$longitud,'<br>',true);
//$sello = wordwrap($sello,$longitud,'<br>',true);


$idsession=$datosfacturas['idtpv_session'];
$idempresa_atendio=$datosfacturas['idempresa_atendio'];
$idcliente=$datosfacturas['idcliente'];
$idempresa=$datosfacturas['idempresa'];

//        $timbre_selloSAT = $sellosat=$tfd['selloSAT'];

$archivo_png=str_replace('.xml','.png',$xml_archivo);
$archivo_png=str_replace('.XML','.PNG',$archivo_png);


if(function_exists('libreria_mash'))
{
    libreria_mash('num2letras');
    
}
else
{

    include_once 'num2letras.php';
}

    switch($Moneda)
    {
        case 'MXN' :  $moneda_txt='PESOS'; break;
        case 'USD' :  $moneda_txt='DOLAR'; break;
        case 'EUR' :  $moneda_txt='EUROS'; break;
        default :  $moneda_txt='PESOS'; break;
    }
    
    $numeroletras=num2letras($total,'  ');    


if(intval($NumCtaPago)>0)
    $NumCtaPago_txt="CUENTA : $NumCtaPago";


$ruta_qr=$masheditor['carpeta_instalacion']."/$archivo_png";
/*
$sellos_pie="
<div class='factura_sellos factura_cuadro '>
<table width='100%' border=0>
    <tr valign='top'>
        <td width=198px;>
            <img src='$ruta_qr'><br/>
            
        </td>
        <td>
            <div class='factura_sellos_txt'>
            CANTIDAD CON LETRA: $numeroletras $moneda_txt ($Moneda)<br>
            <b>METODO PAGO: $metodo_pago | FORMA PAGO: $forma_pago | $NumCtaPago_txt  </b><br/>
                <b>REGIMEN FISCAL : </b>$regimen_fiscal<b>Fecha Timbrado : </b>$timbre_fecha <br/> 
                <b>SELLO : </b><br/>$sello <br/>
                <b>SELLO SAT : </b><br/>$timbre_selloSAT <br/>

                <b>Numero Certificado SAT : </b> $timbre_noCertificadoSAT <br/>
                <b>Cadena Original</b><br/><br/>
                $cadena_sat 
                <br/>

<b>Este documento es una representación impresa de un CFDI</b> EFECTOS FISCALES AL PAGO 
            </div>
            <br/>
$referencia $barcode_factura 
        </td>
    
    </tr>
</table>

</div>
";
 */
 
$sellos_pie="

<div class='factura_sellos factura_cuadro '>
<table width='100%' border=0>
    <tr valign='top'>
        <td width=200px;>
            <img src='$ruta_qr'><br/>
            
        </td>
        <td>
            <!-- CANTIDAD CON LETRA: $numeroletras $moneda_txt ($Moneda)<br> -->
            <div class='factura_sellos_txt'>
            CANTIDAD CON LETRA: $numeroletras ($Moneda)<br>
            "; 
            
            if($TipoDeComprobante !='P')
                  $sellos_pie.="<b>METODO PAGO: $metodo_pago | FORMA PAGO: $forma_pago | $NumCtaPago_txt  </b><br/>";
            
            if($html_parcialidades !='')
                $html_parcialidades="<b>$html_parcialidades</b>";
                                                    
            $sellos_pie.="$html_parcialidades
                <b>REGIMEN FISCAL : $regimen_fiscal Fecha Timbrado : $timbre_fecha </b><br/> 
                <b>SELLO : </b><br/>$sello <br/>
                <b>SELLO SAT : </b>$timbre_selloSAT <br/>

                <b>Numero Certificado SAT : </b> $timbre_noCertificadoSAT <br/>
                <b>Cadena Original</b><br/><br/>
                $cadena_sat 
                <br/>

<b>Este documento es una representación impresa de un CFDI</b> EFECTOS FISCALES AL PAGO 
            </div>
            <br/>
$referencia $barcode_factura 
        </td>
    
    </tr>
</table>

</div>
";
$importeneto=(float)$subtotal;
//echo "$importeneto=$subtotal-$descuento";
//mash $importeneto=sprintf('%1.2f',$importeneto);


if(strlen($nota_impresa)>5)
{
    $notas_impresas="
        <div class='factura_sellos factura_cuadro'>
        $nota_impresa
        </div>
    ";    
}


$desc1= $datosfacturas['descuento_adicional_porcentaje'];
$desc2= $datosfacturas['descuento_formapago_porcentaje'];


$importeneto_=number_format((string)$importeneto,2);

if($descuento>0)
{
        $descuento_txt_="
                        <tr>
        
                            <td class='factura_totales'>
                            DESCUENTO $<br>
                            
                            </td>
                            <td class='factura_totales'>
                             $descuento_
                            </td>
                        </tr>
        ";
}

/*


                        <tr>
        
                            <td class='factura_totales'>
                            IMPORTE NETO $
                            </td>
                            <td class='factura_totales'>
                             $importeneto_
                            </td>
                        </tr>

*/




if($CURP!='')
{
//    $retenciones_txt='';
}



$pie="

<table width='100%'>
    <tr>
        <td valign='top' >
            $notas_impresas
        </td>
        <td width='300px' >
            
                    <table width='300px' >
                        <tr >
                            <td class='factura_totales'>
                            IMPORTE $
                            </td>
                            <td class='factura_totales'>
                             $subtotal_
                            </td>
                        </tr>
$descuento_txt_
$iva_txt
                        $retenciones_txt
                        <tr>
        
                            <td class='factura_totales'>
                            TOTAL $
                            </td>
                            <td class='factura_totales'>
                             $total_<br/>$Moneda
                             
                            </td>
                            
                        </tr>
                        
                    </table> 
          
        </td>
    </tr>
</table>

";


//ES UN PAGO
if($TipoDeComprobante =='P')
{
    $pie="
    <table width='100%'>
        <tr>
            <td valign='top' >
                $notas_impresas
            </td>
            <td width='300px' >
                
                        <table width='300px' >
                            <tr >
                                <td class='factura_totales'>

                                </td>
                                <td class='factura_totales'>
    
                                </td>
                            </tr>
                            <tr>
            
                                <td class='factura_totales'>
    
                                </td>
                                <td class='factura_totales'>
    
                                 
                                </td>
                                
                            </tr>
                            
                        </table> 
              
            </td>
        </tr>
    </table>
    
    ";

}


$idfactura2=sprintf('%06d',$idfactura);

if($datosfacturas['factura_cancelada']==1)
{
    $cancelado_fecha=$datosfacturas['factura_cancelada_fecha'];
    $cancelado_motivo=$datosfacturas['factura_cancelada_motivo'];
    $cancelado_msg_pac=$datosfacturas['factura_cancelada_pac_msg'];

    $cancelado="
    <div class='factura_cancelada'>
    <h4>FACTURA CANCELADA</h4> <br/>
    FECHA CANCELACION : $cancelado_fecha  <br/>
    MOTIVO : $cancelado_motivo  <br/>
    MENSAJE DEL TIMBRADO : $cancelado_msg_pac  <br/>
    
    </div>
    ";
}

if($datosfacturas['factura_cancelada']==2)
{
    $cancelado_fecha=$datosfacturas['factura_cancelada_fecha'];
    $cancelado_motivo=$datosfacturas['factura_cancelada_motivo'];
    $cancelado_msg_pac=$datosfacturas['factura_cancelada_pac_msg'];

    $cancelado="
    <div class='factura_cancelada'>
    <h4>FACTURA PENDIENTE DE CANCELAR</h4> <br/>
    <h4>NO ESTA INCLUIDO EN EL REPORTE DE VENTAS</h4> <br/>
    FECHA CANCELACION : $cancelado_fecha  <br/>
    MOTIVO : $cancelado_motivo  <br/>
    MENSAJE DEL TIMBRADO : $cancelado_msg_pac  <br/>
    
    </div>
    ";
}


if($certificado_no==20001000000100005867)
{
    $cancelado="
    <div class='factura_cancelada'>
<br/><br/>    
PRUEBA DEL SISTEMA
<br/>
FACTURA NO VALIDA ANTE EL SAT
<br/><br/><br/>    
    </div>
    ";
}

$leyenda="
<div class='factura_leyenda' style='font-size:10px !important;'>
Por este pagare me(nos) obligo(amos) a pagar incondicionalmente el dia ___________________ en esta ciudad de $ciudad_estado.
o en cualquier otra plaza que se me(nos) requiera a la orden de $emisor_nombre
la cantidad de $________________ valor recibido a mi(nuestra) entrega satisfaccion, queda convenida que en caso de mora
el presente pagare causara un interes ____% mensual hasta la liquidacion.
<br><br>
FIRMA CLIENTE : _____________________________ <br/>

</div>
";
if($CURP!='')
{
    $leyenda='';
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
