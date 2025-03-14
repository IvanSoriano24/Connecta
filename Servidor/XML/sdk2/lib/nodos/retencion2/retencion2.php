<?php

function mf_init_nodo_retencion2(array &$datos)
{
    global $__mf_constantes__;

    // Ruta XSD de Retenciones
    $__mf_constantes__['__MF_XSD_RET_DIR__'] = $__mf_constantes__['__MF_NODOS_DIR__'] . $__mf_constantes__['__MF_TIPO_DOCUMENTO__'] . '/sat/xsd/';

    // Ruta XSLT de Retenciones
    $__mf_constantes__['__MF_XSLT_RET_DIR__'] = $__mf_constantes__['__MF_NODOS_DIR__'] . $__mf_constantes__['__MF_TIPO_DOCUMENTO__'] . '/sat/xslt/';

    // Ruta de la carpeta complementos
    $__mf_constantes__['__MF_PRE_DIR__'] = $__mf_constantes__['__MF_NODOS_DIR__'] . $__mf_constantes__['__MF_TIPO_DOCUMENTO__'] . '/1pre/';
    // Ruta de la carpeta complementos
    $__mf_constantes__['__MF_INTER_DIR__'] = $__mf_constantes__['__MF_NODOS_DIR__'] . $__mf_constantes__['__MF_TIPO_DOCUMENTO__'] . '/2intermedio/';
    // Ruta de la carpeta complementos
    $__mf_constantes__['__MF_POST_DIR__'] = $__mf_constantes__['__MF_NODOS_DIR__'] . $__mf_constantes__['__MF_TIPO_DOCUMENTO__'] . '/3post/';
}

function mf_nodo_retencion2($datos,$produccion='NO')
{
    global $__mf_constantes__;
    init_sdk($datos);

// DA FORMATO
    if(!isset($datos['html_a_txt']))
    {
        $datos['html_a_txt']='';
    }
    if($datos['html_a_txt']=='SI')
    {
        $datos= array_map_recursive('cfd_fix_dato_xml_html_txt', $datos);
    }

    if(!isset($datos['remueve_acentos']))
    {
        $datos['remueve_acentos']='';
    }
    if($datos['remueve_acentos']=='SI')
    {
        $datos= array_map_recursive('cfd_fix_dato_xml_acentos', $datos);
    }
    else
    {
        $datos= array_map_recursive('cfd_fix_dato_xml', $datos);
    }

//LEE VARIABLES
    if(!isset($datos['SDK']['ruta']))
    {
        $datos['SDK']['ruta']='';
    }
    $ruta=$datos['SDK']['ruta'];
    $ruta=str_replace('\\','/',$ruta);
    $cer=$datos['conf']['cer'];

    $certificado=cfd_certificado_pub($cer);

    $usuario = $datos['PAC']['usuario'];
    $clave   = $datos['PAC']['pass'];

    $codigo_mf_numero=$res_saldo['codigo_mf_numero'];

    if($codigo_mf_numero>0)
    {
        $res['codigo_mf_numero']=$res_saldo['codigo_mf_numero'];
        $res['codigo_mf_texto']=$res_saldo['codigo_mf_texto'];
        return $res;
    }

    if($datos['PAC']['produccion']!='SI')
    {
        $datos['PAC']['produccion']='NO';

    }

    $produccion=$datos['PAC']['produccion'];
    
    if(!file_exists("$cer.txt"))
    {
        mf_prepara_certificados($datos);
    }
    

    if(file_exists("$cer.txt"))
    {
        $numero_cer=file_get_contents("$cer.txt");
    }
    else
    {
        $res['produccion']=$produccion;
        $res['codigo_mf_numero']=7;
        $res['codigo_mf_texto']='CERTIFICADO NO VALIDO, NO SE PUDO LEER EL NUMERO DEL CERTIFICADO';
        $res['cancelada']='SI';
        $res['servidor']=0;
        return $res;
    }

    // Complementos
    $complementos = '';
    // namespaces
    $ns_dividendos = '';
    $sl_dividendos = '';

    // Dividendos
    if(isset($datos['dividendos']))
    {
        $ns_dividendos = 'xmlns:dividendos="http://www.sat.gob.mx/esquemas/retencionpago/1/dividendos"';
        $sl_dividendos = "http://www.sat.gob.mx/esquemas/retencionpago/1/dividendos http://www.sat.gob.mx/esquemas/retencionpago/1/dividendos/dividendos.xsd ";

        $datosDividendos = $datos['dividendos'];
        $nodoDividendos = '<dividendos:Dividendos Version="1.0">';

        if(isset($datosDividendos['DividOUtil']))
        {
            $atrs = mf_atributos_nodo($datosDividendos['DividOUtil'], '');
            $nodoDividendos .= "<dividendos:DividOUtil $atrs/>";
        }

        if(isset($datosDividendos['Remanente']))
        {
            $atrs = mf_atributos_nodo($datosDividendos['Remanente'], '');
            $nodoDividendos .= "<dividendos:Remanente $atrs/>";
        }

        $nodoDividendos .= '</dividendos:Dividendos>';

        $complementos = $nodoDividendos;
    }
    // Intereses
    if(isset($datos['intereses']))
    {
        $ns_intereses = 'xmlns:intereses="http://www.sat.gob.mx/esquemas/retencionpago/1/intereses"';
        $sl_intereses = "http://www.sat.gob.mx/esquemas/retencionpago/1/intereses http://www.sat.gob.mx/esquemas/retencionpago/1/intereses/intereses.xsd ";
        
        $atrs = mf_atributos_nodo($datosIntereses = $datos['intereses'], '');
        $nodoIntereses = "<intereses:Intereses Version=\"1.0\" $atrs />";
        $complementos = $nodoIntereses;
    }
    
    // enajenaciondeacciones
    if(isset($datos['enajenaciondeacciones']))
    {
        $ns_enajenaciondeacciones = 'xmlns:enajenaciondeacciones="http://www.sat.gob.mx/esquemas/retencionpago/1/enajenaciondeacciones"';
        $sl_enajenaciondeacciones = "http://www.sat.gob.mx/esquemas/retencionpago/1/enajenaciondeacciones http://www.sat.gob.mx/esquemas/retencionpago/1/enajenaciondeacciones/enajenaciondeacciones.xsd ";
        
        $atrs = mf_atributos_nodo($datos['enajenaciondeacciones'], '');
        $nodoEnajenaciondeacciones = "<enajenaciondeacciones:EnajenaciondeAcciones Version=\"1.0\" $atrs />";
        $complementos = $nodoEnajenaciondeacciones;
    }
    
    // operacionesconderivados
    if(isset($datos['operacionesconderivados']))
    {
        $ns_operacionesconderivados = 'xmlns:operacionesconderivados="http://www.sat.gob.mx/esquemas/retencionpago/1/operacionesconderivados"';
        $sl_operacionesconderivados = "http://www.sat.gob.mx/esquemas/retencionpago/1/operacionesconderivados http://www.sat.gob.mx/esquemas/retencionpago/1/operacionesconderivados/operacionesconderivados.xsd ";
        
        $atrs = mf_atributos_nodo($datos['operacionesconderivados'], '');
        $nodoOperacionesconderivados = "<operacionesconderivados:Operacionesconderivados Version=\"1.0\" $atrs />";
        $complementos = $nodoOperacionesconderivados;
    }
    
    // arrendamientoenfideicomiso
    if(isset($datos['arrendamientoenfideicomiso']))
    {
        $ns_arrendamientoenfideicomiso = 'xmlns:arrendamientoenfideicomiso="http://www.sat.gob.mx/esquemas/retencionpago/1/arrendamientoenfideicomiso"';
        $sl_arrendamientoenfideicomiso = "http://www.sat.gob.mx/esquemas/retencionpago/1/arrendamientoenfideicomiso http://www.sat.gob.mx/esquemas/retencionpago/1/arrendamientoenfideicomiso/arrendamientoenfideicomiso.xsd ";
        
        $atrs = mf_atributos_nodo($datos['arrendamientoenfideicomiso'], '');
        $nodoArrendamientoenfideicomiso = "<arrendamientoenfideicomiso:Arrendamientoenfideicomiso Version=\"1.0\" $atrs />";
        $complementos = $nodoArrendamientoenfideicomiso;
    }

    // Nodo Retenciones
    $nodoRetenciones = '';
    if(isset($datos['factura']))
    {
        $datos['factura']['NoCertificado'] = $numero_cer;
        $datos['factura']['Certificado'] = $certificado;
        $datosRetencion = $datos['factura'];
        $atrsRetenciones = 'Version=\'2.0\' ' . mf_atributos_nodo($datosRetencion, '') . '{SELLO} ';
        $atrsRetenciones .= "xmlns:retenciones=\"http://www.sat.gob.mx/esquemas/retencionpago/2\" $ns_arrendamientoenfideicomiso $ns_dividendos $ns_intereses $ns_enajenaciondeacciones $ns_operacionesconderivados xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sat.gob.mx/esquemas/retencionpago/2 http://www.sat.gob.mx/esquemas/retencionpago/2/retencionpagov2.xsd $sl_arrendamientoenfideicomiso $sl_operacionesconderivados $sl_enajenaciondeacciones $sl_intereses $sl_dividendos\" ";
        $nodoRetenciones = "<?xml version=\"1.0\" encoding=\"utf-8\"?><retenciones:Retenciones $atrsRetenciones>";
    }

    // Nodo emisor
    if(isset($datos['emisor']))
    {
        
        $RfcE = $datos['emisor']['RfcE'];
        $datos['emisor']['RfcE'] = utf8_decode($RfcE);
        
        $datosEmisor = $datos['emisor'];
        $atrsEmisor = mf_atributos_nodo($datosEmisor, '');
        $nodoRetenciones .= "<retenciones:Emisor $atrsEmisor/>";
    }

    // Nodo Receptor
    if(isset($datos['receptor']))
    {
        
        $RfcR = $datos['receptor']['Nacional']['RfcR'];
        $datos['receptor']['Nacional']['RfcR'] = utf8_decode($RfcR);
        $datosReceptor = $datos['receptor'];
        $atrsReceptor = mf_atributos_nodo($datosReceptor, '');
        $nodoRetenciones .= "<retenciones:Receptor $atrsReceptor>";

        // Nodo Nacional
        if (isset($datos['receptor']['Nacional']))
        {
            $datosNacional = $datos['receptor']['Nacional'];
            $atrsNacional = mf_atributos_nodo($datosNacional, '');
            $nodoRetenciones .= "<retenciones:Nacional $atrsNacional/>";
        }

        // Nodo Extrangero
        if (isset($datos['receptor']['Extranjero']))
        {
            $datosExtranjero = $datos['receptor']['Extranjero'];
            $atrsExtranjero = mf_atributos_nodo($datosExtranjero, '');
            $nodoRetenciones .= "<retenciones:Extranjero $atrsExtranjero/>";
        }
        $nodoRetenciones .= "</retenciones:Receptor>";
    }

    // Nodo Periodo
    if(isset($datos['periodo']))
    {
        $datosPeriodo = $datos['periodo'];
        $atrsPeriodo = mf_atributos_nodo($datosPeriodo, '');
        $nodoRetenciones .= "<retenciones:Periodo $atrsPeriodo/>";
    }

    // Nodo Totales
    if(isset($datos['totales']))
    {
        $datosTotales = $datos['totales'];
        $atrsTotales = mf_atributos_nodo($datosTotales, '');
        $nodoRetenciones .= "<retenciones:Totales $atrsTotales>";

        // Nodo ImpRetenidos
        if(isset($datosTotales['ImpRetenidos']))
        {
            $datosImpRetenidos = $datosTotales['ImpRetenidos'];
            foreach($datosImpRetenidos as $idx => $nodo)
            {
                $atrImpRet = mf_atributos_nodo($nodo, '');
                $nodoRetenciones .= "<retenciones:ImpRetenidos $atrImpRet/>";
            }
        }

        $nodoRetenciones .= "</retenciones:Totales>";
    }

    // Se agregan los complementos
    if($complementos != '')
    {
        $nodoRetenciones .= "<retenciones:Complemento>$complementos</retenciones:Complemento>";
    }

    $nodoRetenciones .= "</retenciones:Retenciones>";
    
    $xml_iso8859 = $nodoRetenciones;
    
    global $__mf_constantes__,$__mf__;
    //$xml_iso8859 = utf8_decode($xml_iso8859); 
    // Se guarda el XML
    mf_agrega_global('xml_cfdi', $xml_iso8859);
    // Se guarda la ruta del temporal
    $xmltmp_ret = $__mf_constantes__['__MF_SDK_TMP__'] . md5(time() . rand(1111, 9999)) . '_ret.xml';
    mf_agrega_global('ruta_tmp', $xmltmp_ret);
    // Se crea el archivo XML
    $tmpok = file_put_contents(mf_recupera_global('ruta_tmp'), mf_recupera_global('xml_cfdi'));

//die();

// MODULO SELLO
    $xslt = $__mf_constantes__['__MF_XSLT_RET_DIR__'] . 'retenciones.xslt';
    // echo   $xml_iso8859 = utf8_encode($xml_iso8859);   //CARLOS 20/01/2023  SE COMENTO ESTA LINEA PARA LAS ñ
    //$xml_iso8859=retencion_sello($datos,$xml_iso8859,$xslt);

    $xml_iso8859=retencion_sello($datos,mf_recupera_global('ruta_tmp'),$xslt);
    //$xml_iso8859=utf8_encode($xml_iso8859);
  //echo $xml_iso8859;
  
//file_put_contents("c:\\multifacturas_sdk\\dividendos.xml",utf8_encode($xml_iso8859));
//$xml_iso8859=utf8_encode($xml_iso8859);
//file_put_contents('tmp/retenciones.xml',utf8_encode($xml_iso8859));
    //$__mf__['xml_cfdi']=utf8_encode($xml_iso8859);
    $__mf__['xml_cfdi']=$xml_iso8859;
  
  
    //file_put_contents($__mf_constantes__['__MF_SDK_TMP__'] . 'retenciones.xml',utf8_encode($xml_iso8859));

//    $datos['xml']=utf8_decode($xml_iso8859);
    //$datos['xml']=$xml_iso8859;

    //$res=mf_genera_cfdi($datos);
    if(!isset($datos['PAC']['usuario']))
    {
        $datos['PAC']['usuario']='DEMO700101XXX';
    }
    if(!isset($datos['PAC']['pass']))
    {
        $datos['PAC']['pass']='DEMO700101XXX';
    }
    //$res=mf_timbrar_retencion(rand(1, 10), $datos['PAC']['usuario'],$datos['PAC']['pass'], $xml_iso8859);
    
    if(!isset($datos['PAC']['produccion']))
    {
        $datos['PAC']['produccion']='NO';
    }   
    $produccion=$datos['PAC']['produccion'];
    $res=mf_timbrar_retencion(rand(1, 10), $datos['PAC']['usuario'],$datos['PAC']['pass'], $xml_iso8859,$produccion);
    

    if($res['codigo_mf_numero'] == 0 || $res['codigo_mf_numero'] == '0')
    {
        $res['cfdi']=base64_decode($res['cfdi']);
        file_put_contents($datos['cfdi'], $res['cfdi']);
        $ruta_png = substr($datos['cfdi'], 0, -3) . 'png';
        file_put_contents($ruta_png, base64_decode($res['png']));
    }
    else
    {
        $res['png'] = '';
    }

    _cfdi_almacena_error_();
    return $res;

}