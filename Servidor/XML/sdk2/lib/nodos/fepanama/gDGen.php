<?php

function mf_nodo_gdgen(array $datos)
{
    $xml = '<gDGen>';

    $xml .= crea_nodo_simple($datos, 'iAmb');
    $xml .= crea_nodo_simple($datos, 'iTpEmis');

    // ID: B04 Fecha y hora de inicio de la operación en contingencia
    $xml .= crea_nodo_simple($datos, 'dFechaCont');

    // ID: B05 Razón de la operación en contigencia
    $xml .= crea_nodo_simple($datos, 'dMotCont');

    // ID: B06 - Tipo de documento
    $xml .= crea_nodo_simple($datos, 'iDoc');

    // B07: Número del documento fiscal en la serie correspondiente, de 000000001 a 999999999, no siendo permitido el reinicio de la numeración.
    $xml .= crea_nodo_simple($datos, 'dNroDF');

    // B08: Serie del documento fiscal. La serie sirve para permitir que existan secuencias independientes de numeración de facturas, con diversas finalidades, sea por libre elección del emisor, tales como puntos de facturación distintos (como cajas de un supermercado, o dársenas de un distribuidor), tipos de productos, especies de operación, etc., sea para finalidades que vengan a ser determinadas por la DGI.
    $xml .= crea_nodo_simple($datos, 'dPtoFacDF');

    // B09: Codigo de seguridad.
    $xml .= crea_nodo_simple($datos, 'dSeg');

    // B10: Fecha de emisión del documento
    $xml .= crea_nodo_simple($datos, 'dFechaEm');

    // B11: Fecha de salida de las mercancías. Informar cuando sea conocida
    $xml .= crea_nodo_simple($datos, 'dFechaSalida');

    // B12: Naturaleza de la Operación
    $xml .= crea_nodo_simple($datos, 'iNatOp');

    // B13: Tipo de la operación
    $xml .= crea_nodo_simple($datos, 'iTipoOp');

    // B14: Destino u origen de la operación
    $xml .= crea_nodo_simple($datos, 'iDest');

    // B15: Formato de generación del CIFE
    $xml .= crea_nodo_simple($datos, 'iFormCAFE');

    // B16: Manera de entrea del CIFE al receptor
    $xml .= crea_nodo_simple($datos, 'iEntCAFE');

    // B17: Envío del contenedor para el receptor
    $xml .= crea_nodo_simple($datos, 'dEnvFE');

    // B18: Proceso de generación de la FE
    $xml .= crea_nodo_simple($datos, 'iProGen');

    // B19: Tipo de transacción de venta
    $xml .= crea_nodo_simple($datos, 'iTipoTranVenta');

    // B29: Informaciones de interés del emitente con respecto a la FE
    $xml .= crea_nodo_simple($datos, 'dInfEmFE');

    // B30: Grupo de datos que identifican al emisor
    if(array_key_exists('gEmis', $datos))
    {
        $xml .= '<gEmis>';

        // B301: Tipo, RUC y DV del Contribuyente Emisor
        $xml .= crea_nodo_rama($datos['gEmis'], 'gRucEmi', array('dTipoRuc', 'dRuc', 'dDV'));

        // B302: Razón Social (persona jurídica) o Nombre y Apellido (persona natural) del emisor de la FE
        $xml .= crea_nodo_simple($datos['gEmis'], 'dNombEm');

        // B303: Código de la sucursal desde donde se emite la factura
        $xml .= crea_nodo_simple($datos['gEmis'], 'dSucEm');

        // B304: Coordenadas geográficas de la sucursal donde se ubica el punto de facturación
        $xml .= crea_nodo_simple($datos['gEmis'], 'dCoordEm');

        // B305: Dirección de la sucursal emisora, o de la persona física emisora
        $xml .= crea_nodo_simple($datos['gEmis'], 'dDirecEm');

        // B306: Codigo, Corregimiento, Distrito, Provincia donde se ubica el punto de facturación
        $xml .= crea_nodo_rama($datos['gEmis'], 'gUbiEm', array('dCodUbi', 'dCorreg', 'dDistr', 'dProv'));

        // B309: Teléfono de contacto de la sucursal emisora o de la persona emisora
        if(array_key_exists('dTfnEm', $datos['gEmis']))
        {
            $xml .= crea_nodos_numerico($datos['gEmis'], 'dTfnEm');
        }

        // B310: Correo electrónico del emisor
        if(array_key_exists('dCorElectEmi', $datos['gEmis']))
        {
            $xml .= crea_nodos_numerico($datos['gEmis'], 'dCorElectEmi');
        }

        $xml .= '</gEmis>';
    }

    // B40: Grupo de datos que identifican al receptor
    if(array_key_exists('gDatRec', $datos))
    {
        $xml .= '<gDatRec>';

        // ID: B401 - Identifica el tipo de receptor de la FE
        $xml .= crea_nodo_simple($datos['gDatRec'], 'iTipoRec');

        // 402: RUC del Contribuyente Receptor
        $xml .= crea_nodo_rama($datos['gDatRec'], 'gRucRec', array('dTipoRuc', 'dRuc', 'dDV'));

        // B403: Razón social (persona jurídica) o Nombre y Apellido (persona natural) del receptor de la FE
        $xml .= crea_nodo_simple($datos['gDatRec'], 'dNombRec');

        // B404: Dirección del receptor de la FE
        $xml .= crea_nodo_simple($datos['gDatRec'], 'dDirecRec');

        // B405: Codigo, Corregimiento, Distrito, Provincia donde se ubica el punto de facturación
        $xml .= crea_nodo_rama($datos['gDatRec'], 'gUbiRec', array('dCodUbi', 'dCorreg', 'dDistr', 'dProv'));
        
        // B406: Identificacion extranjera
        $xml .= crea_nodo_rama($datos['gDatRec'], 'gIdExt', array('dIdExt', 'dPaisExt'));

        // B408: Teléfono de contacto del receptor de la FE
        $xml .= crea_nodos_numerico($datos['gDatRec'], 'dTfnRec');

        // B409: Correo electrónico del receptor
        $xml .= crea_nodos_numerico($datos['gDatRec'], 'dCorElectRec');

        // B411: País del receptor de la FE. Debe ser PAN(Panamá) si B15=1 (destino u origen de la operacion es Panamá)
        $xml .= crea_nodo_simple($datos['gDatRec'], 'cPaisRec');

        // B411: País del receptor de la FE no existente en la tabla
        $xml .= crea_nodo_simple($datos['gDatRec'], 'dPaisRecDesc');

        $xml .= '</gDatRec>';
    }

    // B50: Grupo de datos de facturas en caso de exportación
    $xml .= crea_nodo_rama($datos, 'gFExp', array('cCondEntr', 'cMoneda', 'cMonedaDesc', 'dCambio', 'dVTotEst', 'dPuertoEmbarq'));

//echo "<pre>";print_r($datos);echo "</pre>";
    // B60: Información de documento fiscal referenciado
    if(array_key_exists('gDFRef', $datos))
    {

        foreach($datos['gDFRef'] as $idx => $nodo)
        {

            $xml .= '<gDFRef>';
            // B601: RUC del emisor del documento fiscal referenciado
            $xml .= crea_nodo_rama($nodo, 'gRucEmDFRef', array('dTipoRuc', 'dRuc', 'dDV'));

            // B602: Razón Social (Persona Jurídica) o Nombre y Apellido (Persona Natural) del emisor del documento fiscal referenciado
            $xml .= crea_nodo_simple($nodo, 'dNombEmRef');

            // B603: Fecha de emisión del Documento Fiscal Referenciado
            $xml .= crea_nodo_simple($nodo, 'dFechaDFRef');

            // B604: Información de Referencia de la FE
            if(array_key_exists('gDFRefNum', $nodo))
            {
                $xml .= '<gDFRefNum>';

                // B605: Infomracion de Referencia de la FE
                $xml .= crea_nodo_rama($nodo['gDFRefNum'], 'gDFRefFE', array('dCUFERef'));

                // B615: Información de Referencia a factura en papel
                $xml .= crea_nodo_rama($nodo['gDFRefNum'], 'gDFRefFacPap', array('dNroFacPap'));

                // B620: Infomración de Referencia a factura en papel
                $xml .= crea_nodo_rama($nodo['gDFRefNum'], 'gDFRefFacIE', array('dNroFacIE'));

                $xml .= '</gDFRefNum>';
            }
            $xml .= '</gDFRef>';
        }
    }

    // B70: Grupo de datos que identifican al autorizado a descargar
    if(array_key_exists('gAutXML', $datos))
    {
        foreach ($datos['gAutXML'] as $idx => $nodo)
        {
            $xml .= '<gAutXML>';

            // B701: RUC del autorizado a descargar
            $xml .= crea_nodo_rama($nodo, 'gRucAutXML', array('dTipoRuc', 'dRuc', 'dDV'));

            $xml .= '</gAutXML>';
        }
    }

    $xml .= '</gDGen>';

    return $xml;
}