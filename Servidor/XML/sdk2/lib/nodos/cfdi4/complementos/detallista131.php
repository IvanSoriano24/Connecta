<?php

function mf_complemento_detallista131($datos)
{
	// Variable para los namespaces xml
	global $__mf_namespaces__;
	$__mf_namespaces__['detallista']['uri'] = 'http://www.sat.gob.mx/detallista';
	$__mf_namespaces__['detallista']['xsd'] = 'http://www.sat.gob.mx/sitio_internet/cfd/detallista/detallista.xsd';

	$atrs = mf_atributos_nodo($datos);
    $xml = "<detallista:detallista type='SimpleInvoiceType' contentVersion='1.3.1' documentStructureVersion='AMC8.1' $atrs>";

    if(isset($datos['requestForPaymentIdentification']))
    {
		$xml .= "<detallista:requestForPaymentIdentification>";
		if(isset($datos['requestForPaymentIdentification']['entityType']))
		{
			$xml .= "<detallista:entityType>";
			$xml .= $datos['requestForPaymentIdentification']['entityType'];
			$xml .= "</detallista:entityType>";
		}
        $xml .= "</detallista:requestForPaymentIdentification>";
    }
	if(isset($datos['specialInstruction']))
    {
		foreach($datos['specialInstruction'] as $idx => $entidad)
		{
			$atrs = mf_atributos_nodo($entidad);
            $xml .= "<detallista:specialInstruction $atrs>";
            if (isset($entidad['textos']))
			{
				foreach($entidad['textos'] as $idx2 => $subentidad)
				{
					$atrs = mf_atributos_nodo($subentidad);
					$xml .= "<detallista:text>";
                    $xml .= $entidad['textos'][$idx2]['text'];
                    $xml .= "</detallista:text>";
				}
			}
            $xml .= "</detallista:specialInstruction>";
		}
		
    }
	if(isset($datos['orderIdentification']))
    {
		$atrs = mf_atributos_nodo($datos['orderIdentification']);
		$xml .= "<detallista:orderIdentification>";
		if(isset($datos['orderIdentification']['referenceIdentification']))
		{
            foreach($datos['orderIdentification']['referenceIdentification']  as $idx2 => $entidad2)
			{
                $atrs = mf_atributos_nodo($entidad2);
				$xml .= "<detallista:referenceIdentification $atrs>";
                // $xml .= $entidad2['type'];
                $xml .= "</detallista:referenceIdentification >";
			}
		}
		if(isset($datos['orderIdentification']['ReferenceDate']))
		{
			$xml .= "<detallista:ReferenceDate >";
			$xml .= $datos['orderIdentification']['ReferenceDate'];
			$xml .= "</detallista:ReferenceDate >";
		}
		$xml .= "</detallista:orderIdentification>";	
	}
	if(isset($datos['AdditionalInformation']))
	{
		$atrs = mf_atributos_nodo($datos['AdditionalInformation']);
		$xml .= "<detallista:AdditionalInformation $atrs>";
		if(isset($datos['AdditionalInformation']['referenceIdentification']))
		{
			foreach($datos['AdditionalInformation']['referenceIdentification']  as $idx2 => $entidad2)
			{
				$atrs = mf_atributos_nodo($entidad2);		
				$xml .= "<detallista:referenceIdentification $atrs>";
				// $xml .= $entidad2['type'];
				$xml .= "</detallista:referenceIdentification >";
			}
		}
		$xml .= "</detallista:AdditionalInformation>";
	}
	if(isset($datos['DeliveryNote']))
	{
		$xml .= "<detallista:DeliveryNote>";
		if(isset($datos['DeliveryNote']['referenceIdentification']))
		{
			foreach($datos['DeliveryNote']['referenceIdentification']  as $idx => $entidad)
			{		
				$xml .= "<detallista:referenceIdentification>";
				$xml .= $entidad['referenceIdentification'];
				$xml .= "</detallista:referenceIdentification >";
			}
		}
		if(isset($datos['orderIdentification']['ReferenceDate']))
		{
			$xml .= "<detallista:ReferenceDate >";
			$xml .= $datos['DeliveryNote']['ReferenceDate'];
			$xml .= "</detallista:ReferenceDate >";
		}
		$xml .= "</detallista:DeliveryNote>";
	}
	if(isset($datos['buyer']))
    {
		// $atrs = mf_atributos_nodo($datos['buyer']);
		$xml .= "<detallista:buyer >"; //$atrs
		if(isset($datos['buyer']['gln']))
		{
			// $atrs = mf_atributos_nodo($entidad2);
			$xml .= "<detallista:gln >"; // $atrs
			$xml .= $datos['buyer']['gln'];
			$xml .= "</detallista:gln >";
		}
		if(isset($datos['buyer']['contactInformation']))
		{
			// $atrs = mf_atributos_nodo($datos['buyer']['contactInformation']);
			$xml .= "<detallista:contactInformation >";  //$atrs
			if(isset($datos['buyer']['contactInformation']['personOrDepartmentName']))
			{
				
				$xml .= "<detallista:personOrDepartmentName>";
				if(isset($datos['buyer']['contactInformation']['personOrDepartmentName']['text']))
				{
					$xml .= "<detallista:personOrDepartmentName>";
					$xml .= $datos['buyer']['contactInformation']['personOrDepartmentName']['text'];
					$xml .= "</detallista:personOrDepartmentName>";
				}
				$xml .= "</detallista:personOrDepartmentName>";
			}
			$xml .= "</detallista:contactInformation>";
		}
		$xml .= "</detallista:buyer>";
	}
	if(isset($datos['seller']))
    {
		// $atrs = mf_atributos_nodo($datos['seller']);
		$xml .= "<detallista:seller >"; //$atrs
		if(isset($datos['seller']['gln']))
		{
			$xml .= "<detallista:gln >";
			$xml .= $datos['seller']['gln'];
			$xml .= "</detallista:gln >";
		}
		if(isset($datos['seller']['alternatePartyIdentification']))
		{
			$atrsentidad = mf_atributos_nodo($datos['seller']['alternatePartyIdentification']);
			$xml .= "<detallista:alternatePartyIdentification $atrsentidad >";
			$xml .= "</detallista:alternatePartyIdentification>";
		}
		$xml .= "</detallista:seller>";
	}
	if(isset($datos['shipTo']))
    {
		$xml .= "<detallista:shipTo>";
		if(isset($datos['shipTo']['gln']))
		{
			$xml .= "<detallista:gln >";
			$xml .= $datos['shipTo']['gln'];
			$xml .= "</detallista:gln >";
		}
		if(isset($datos['shipTo']['nameAndAddress']))
		{
			$xml .= "<detallista:nameAndAddress >";
			foreach($datos['shipTo']['nameAndAddress'] as $idx => $entidad)
			{
				foreach($datos['shipTo']['nameAndAddress'][$idx] as $idx2 => $entidad2)
				{
					$xml .= "<detallista:$idx2 >";
					$xml .= $datos['shipTo']['nameAndAddress'][$idx][$idx2];
					$xml .= "</detallista:$idx2 >";
				}
			}
			$xml .= "</detallista:nameAndAddress >";
		}
		$xml .= "</detallista:shipTo>";
	}
	if(isset($datos['InvoiceCreator']))
    {
		$xml .= "<detallista:InvoiceCreator>";
		if(isset($datos['InvoiceCreator']['gln']))
		{
			$xml .= "<detallista:gln >";
			$xml .= $datos['InvoiceCreator']['gln'];
			$xml .= "</detallista:gln >";
		}
		if(isset($datos['InvoiceCreator']['alternatePartyIdentification']))
		{
			$atrsentidad = mf_atributos_nodo($datos['InvoiceCreator']['alternatePartyIdentification']);
			$xml .= "<detallista:alternatePartyIdentification $atrsentidad>";
			$xml .= "</detallista:alternatePartyIdentification>";
		}
		if(isset($datos['InvoiceCreator']['nameAndAddress']))
		{
			$xml .= "<detallista:nameAndAddress>";
			foreach($datos['InvoiceCreator']['nameAndAddress'] as $idx => $entidad)
			{
				$xml .= "<detallista:$idx >";
				$xml .= $datos['InvoiceCreator']['nameAndAddress'][$idx];
				$xml .= "</detallista:$idx >";
			}
			$xml .= "</detallista:nameAndAddress>";
		}
		$xml .= "</detallista:InvoiceCreator>";
	}
	if(isset($datos['Customs']))
	{
		$xml .= "<detallista:Customs >";
		foreach($datos['Customs'] as $idx => $entidad)
		{
			if(isset($datos['Customs'][$idx]['gln']))
			{
				$xml .= "<detallista:gln >";
				$xml .= $datos['Customs'][$idx]['gln'];
				$xml .= "</detallista:gln >";
			}
		}
		$xml .= "</detallista:Customs>";
	}
	if(isset($datos['currency']))
    {
		foreach($datos['currency'] as $idx => $entidad)
		{
			//eliminamos los elementos que no son atributos, sólo queda currencyISOCode
			$array_atrs = $datos['currency'][$idx];
			unset($array_atrs['currencyFunction'], $array_atrs['rateOfChange']);

			$atrs_currency = mf_atributos_nodo($array_atrs);
			$xml .= "<detallista:currency $atrs_currency>";
			if(isset($datos['currency'][$idx]['currencyFunction']))
			{
				foreach($datos['currency'][$idx]['currencyFunction'] as $idx2 => $entidad2)
				{
					if(isset($datos['currency'][$idx]['currencyFunction'][$idx2]['currencyFunction']))
					{
						$xml .= "<detallista:currencyFunction>";
						$xml .= $datos['currency'][$idx]['currencyFunction'][$idx2]['currencyFunction'];
						$xml .= "</detallista:currencyFunction>";
					}
					
				}
			}
			if(isset($datos['currency'][$idx]['rateOfChange']))
			{
				$xml .= "<detallista:rateOfChange>";
				$xml .= $datos['currency'][$idx]['rateOfChange'];
				$xml .= "</detallista:rateOfChange>";
			}
			$xml .= "</detallista:currency>";
		}
	}
	if(isset($datos['paymentTerms']))
    {
		$atrs = mf_atributos_nodo($datos['paymentTerms']);
		$xml .= "<detallista:paymentTerms $atrs>";
		if(isset($datos['paymentTerms']['netPayment']))
		{
			$atrs = mf_atributos_nodo($datos['paymentTerms']['netPayment']);
			$xml .= "<detallista:netPayment $atrs>";
			if(isset($datos['paymentTerms']['netPayment']['paymentTimePeriod']))
			{
				$xml .= "<detallista:paymentTimePeriod>";
				if(isset($datos['paymentTerms']['netPayment']['paymentTimePeriod']['timePeriodDue']))
				{
					$array_atrs = $datos['paymentTerms']['netPayment']['paymentTimePeriod']['timePeriodDue'];
					unset($array_atrs['value']);

					$atrsentidad = mf_atributos_nodo($array_atrs);
					$xml .= "<detallista:timePeriodDue $atrsentidad>";
					if(isset($datos['paymentTerms']['netPayment']['paymentTimePeriod']['timePeriodDue']['value']))
					{
						$xml .= "<detallista:value>";
						$xml .= $datos['paymentTerms']['netPayment']['paymentTimePeriod']['timePeriodDue']['value'];
						$xml .= "</detallista:value>";
					}
					$xml .= "</detallista:timePeriodDue>";
				}
				$xml .= "</detallista:paymentTimePeriod>";
			}
			$xml .= "</detallista:netPayment>";
		}
		if(isset($datos['paymentTerms']['discountPayment']))
		{
			$array_atrs = $datos['paymentTerms']['discountPayment'];
			unset($array_atrs['percentage']);

			$atrs = mf_atributos_nodo($array_atrs);
			$xml .= "<detallista:discountPayment $atrs>";
			if(isset($datos['paymentTerms']['discountPayment']['percentage']))
			{
				$xml .= "<detallista:percentage>";
				$xml .= $datos['paymentTerms']['discountPayment']['percentage'];
				$xml .= "</detallista:percentage>";
			}
			$xml .= "</detallista:discountPayment>";
		}
		$xml .= "</detallista:paymentTerms>";
	}
	if(isset($datos['shipmentDetail']))
	{
		$atrs = mf_atributos_nodo($datos['shipmentDetail']);
		$xml .= "<detallista:shipmentDetail $atrs/>";
	}
	if(isset($datos['allowanceCharge']))
    {
		$array_atrs = $datos['allowanceCharge'];
		unset($array_atrs['monetaryAmountOrPercentage'], $array_atrs['specialServicesType']);

		$atrs = mf_atributos_nodo($array_atrs);
		$xml .= "<detallista:allowanceCharge $atrs>";
		if(isset($datos['allowanceCharge']['specialServicesType']))
		{
			$xml .= "<detallista:specialServicesType>";
			$xml .= $datos['allowanceCharge']['specialServicesType'];
			$xml .= "</detallista:specialServicesType>";
		}
		if(isset($datos['allowanceCharge']['monetaryAmountOrPercentage']))
		{
			$xml .= "<detallista:monetaryAmountOrPercentage>"; 
			if(isset($datos['allowanceCharge']['monetaryAmountOrPercentage']['rate']))
			{
				$array_atrs = $datos['allowanceCharge']['monetaryAmountOrPercentage']['rate'];
				unset($array_atrs['percentage']);

				$atrsentidad = mf_atributos_nodo($array_atrs);
				$xml .= "<detallista:rate $atrsentidad>";
				if(isset($datos['allowanceCharge']['monetaryAmountOrPercentage']['rate']['percentage']))
				{
					$xml .= "<detallista:percentage>";
					$xml .= $datos['allowanceCharge']['monetaryAmountOrPercentage']['rate']['percentage'];
					$xml .= "</detallista:percentage>";
				}
				$xml .= "</detallista:rate>";
			}
			$xml .= "</detallista:monetaryAmountOrPercentage>";
		}
		$xml .= "</detallista:allowanceCharge>";
	}
	if(isset($datos['lineItem']))
    {
		foreach($datos['lineItem'] as $idx => $entidad)
		{
			$atrs = mf_atributos_nodo($entidad);
			$xml .= "<detallista:lineItem $atrs>";
			if(isset($entidad['tradeItemIdentification']))
			{
				$xml .= "<detallista:tradeItemIdentification>";
				if(isset($entidad['tradeItemIdentification']['gtin']))
				{
					$xml .= "<detallista:gtin>";
					$xml .= $entidad['tradeItemIdentification']['gtin'];
					$xml .= "</detallista:gtin>";
				}
				$xml .= "</detallista:tradeItemIdentification>";
			}
			if(isset($entidad['alternateTradeItemIdentification']))
			{
				foreach($entidad['alternateTradeItemIdentification'] as $idx2 => $entidad2)
				{
					$atrs = mf_atributos_nodo($entidad2);
					$xml .= "<detallista:alternateTradeItemIdentification $atrs>";
					$xml .= "</detallista:alternateTradeItemIdentification> ";
				}
			}
			if(isset($entidad['tradeItemDescriptionInformation']))
			{
				$array_atrs = $entidad['tradeItemDescriptionInformation'];
				unset($array_atrs['longText']);

				$atrs = mf_atributos_nodo($array_atrs);
				$xml .= "<detallista:tradeItemDescriptionInformation $atrs>";
				if(isset($entidad['tradeItemDescriptionInformation']['longText']))
				{
					$xml .= "<detallista:longText>";
					$xml .= $entidad['tradeItemDescriptionInformation']['longText'];
					$xml .= "</detallista:longText>";
				}
				$xml .= "</detallista:tradeItemDescriptionInformation>";
			}		
			if(isset($entidad['invoicedQuantity']))
			{
				$atrs = mf_atributos_nodo($entidad['invoicedQuantity']);
				$xml .= "<detallista:invoicedQuantity $atrs>";
				$xml .= "</detallista:invoicedQuantity>";
			}
			if(isset($entidad['aditionalQuantity']))
			{
				foreach($entidad['aditionalQuantity'] as $idx2 => $entidad2)
				{
					$atrs = mf_atributos_nodo($entidad2);
					$xml .= "<detallista:aditionalQuantity $atrs>";
					$xml .= "</detallista:aditionalQuantity>";
				}
			}
			if(isset($entidad['grossPrice']))
			{
				$xml .= "<detallista:grossPrice>";
				if(isset($entidad['grossPrice']['Amount']))
				{
					$xml .= "<detallista:Amount>";
					$xml .= $entidad['grossPrice']['Amount'];
					$xml .= "</detallista:Amount>";
				}
				$xml .= "</detallista:grossPrice>";
			}
			if(isset($entidad['netPrice']))
			{
				$xml .= "<detallista:netPrice>";
				if(isset($entidad['netPrice']['Amount']))
				{
					$xml .= "<detallista:Amount>";
					$xml .= $entidad['netPrice']['Amount'];
					$xml .= "</detallista:Amount>";
				}
				$xml .= "</detallista:netPrice>";
			}
			if(isset($entidad['AdditionalInformation']))
			{
				$xml .= "<detallista:AdditionalInformation>";
				if(isset($entidad['AdditionalInformation']['referenceIdentification']))
				{
					$atrs = mf_atributos_nodo($entidad['AdditionalInformation']['referenceIdentification']);
					$xml .= "<detallista:referenceIdentification $atrs>";
					$xml .= "</detallista:referenceIdentification>";
				}
				$xml .= "</detallista:AdditionalInformation>";
			}
			if(isset($entidad['Customs']))
			{
				foreach($entidad['Customs'] as $idx2 => $entidad2)
				{
					$xml .= "<detallista:Customs>";
					if(isset($entidad2['gln']))
					{
						$xml .= "<detallista:gln>";
						$xml .= $entidad2['gln'];
						$xml .= "</detallista:gln>";
					}
					if(isset($entidad2['alternatePartyIdentification']))
					{
						$atrs = mf_atributos_nodo($entidad2['alternatePartyIdentification']);
						$xml .= "<detallista:alternatePartyIdentification $atrs>";
						$xml .= "</detallista:alternatePartyIdentification>";
					}
					if(isset($entidad2['ReferenceDate']))
					{
						$xml .= "<detallista:ReferenceDate>";
						$xml .= $entidad2['ReferenceDate'];
						$xml .= "</detallista:ReferenceDate>";
					}
					if(isset($entidad2['nameAndAddress']))
					{
						$xml .= "<detallista:nameAndAddress>";
						if(isset($entidad2['nameAndAddress']['name']))
						{
							$xml .= "<detallista:name>";
							$xml .= $entidad2['nameAndAddress']['name'];
							$xml .= "</detallista:name>";
						}
						$xml .= "</detallista:nameAndAddress>";
					}
					$xml .= "</detallista:Customs>";
				}
			}
			if(isset($entidad['LogisticUnits']))
			{
				$xml .= "<detallista:LogisticUnits>";
				if(isset($entidad['LogisticUnits']['serialShippingContainerCode']))
				{
					$atrs = mf_atributos_nodo($entidad['LogisticUnits']['serialShippingContainerCode']);
					$xml .= "<detallista:serialShippingContainerCode $atrs>";
					$xml .= "</detallista:serialShippingContainerCode>";
				}
				$xml .= "</detallista:LogisticUnits>";
			}
			if(isset($entidad['palletInformation']))
			{
				$xml .= "<detallista:palletInformation>";
				if(isset($entidad['palletInformation']['palletQuantity']))
				{
					$xml .= "<detallista:palletQuantity>";
					$xml .= $entidad['palletInformation']['palletQuantity'];
					$xml .= "</detallista:palletQuantity>";
				}
				if(isset($entidad['palletInformation']['description']))
				{
					$atrs = mf_atributos_nodo($entidad['palletInformation']['description']);
					$xml .= "<detallista:description $atrs>";
					$xml .= "</detallista:description>";
				}
				if(isset($entidad['palletInformation']['transport']))
				{
					$xml .= "<detallista:transport>";
					if(isset($entidad['palletInformation']['transport']['methodOfPayment']))
					{
						$xml .= "<detallista:methodOfPayment>";
						$xml .= $entidad['palletInformation']['transport']['methodOfPayment'];
						$xml .= "</detallista:methodOfPayment>";
					}
					$xml .= "</detallista:transport>";
				}
				$xml .= "</detallista:palletInformation>";
			}
			if(isset($entidad['extendedAttributes']))
			{
				$xml .= "<detallista:extendedAttributes>";
				if(isset($entidad['extendedAttributes']['lotNumber']))
				{
					foreach($entidad['extendedAttributes']['lotNumber'] as $idx2 => $entidad2)
					{
						$atrs = mf_atributos_nodo($entidad2);
						$xml .= "<detallista:lotNumber $atrs>";
						$xml .= "</detallista:lotNumber>";
					}
				}
				$xml .= "</detallista:extendedAttributes>";
			}
			if(isset($entidad['allowanceCharge']))
			{
				foreach($entidad['allowanceCharge'] as $idx2 => $entidad2)
				{
					$array_atrs = $entidad2;
					unset($array_atrs['specialServicesType']);

					$atrs = mf_atributos_nodo($array_atrs);
					$xml .= "<detallista:allowanceCharge $atrs>"; 
					if(isset($entidad2['specialServicesType']))
					{
						$xml .= "<detallista:specialServicesType>";
						$xml .= $entidad2['specialServicesType'];
						$xml .= "</detallista:specialServicesType>";
					}
					if(isset($entidad2['monetaryAmountOrPercentage']))
					{
						$xml .= "<detallista:monetaryAmountOrPercentage>";
						if(isset($entidad2['monetaryAmountOrPercentage']['percentagePerUnit']))
						{
							$xml .= "<detallista:percentagePerUnit>";
							$xml .= $entidad2['monetaryAmountOrPercentage']['percentagePerUnit'];
							$xml .= "</detallista:percentagePerUnit>";
						}
						if(isset($entidad2['monetaryAmountOrPercentage']['ratePerUnit']))
						{
							$xml .= "<detallista:ratePerUnit>";
							if(isset($entidad2['monetaryAmountOrPercentage']['ratePerUnit']['amountPerUnit']))
							{
								$xml .= "<detallista:amountPerUnit>";
								$xml .= $entidad2['monetaryAmountOrPercentage']['ratePerUnit']['amountPerUnit'];
								$xml .= "</detallista:amountPerUnit>";
							}
							$xml .= "</detallista:ratePerUnit>";
						}
						$xml .= "</detallista:monetaryAmountOrPercentage>";
					}
					$xml .= "</detallista:allowanceCharge>";
				}
			}
			if(isset($entidad['tradeItemTaxInformation']))
			{
				foreach($entidad['tradeItemTaxInformation'] as $idx2 => $entidad2)
				{
					$xml .= "<detallista:tradeItemTaxInformation>";
					if(isset($entidad2['taxTypeDescription']))
					{
						$xml .= "<detallista:taxTypeDescription>";
						$xml .= $entidad2['taxTypeDescription'];
						$xml .= "</detallista:taxTypeDescription>";
					}
					if(isset($entidad2['referenceNumber']))
					{
						$xml .= "<detallista:referenceNumber>";
						$xml .= $entidad2['referenceNumber'];
						$xml .= "</detallista:referenceNumber>";
					}
					if(isset($entidad2['taxCategory']))
					{
						$xml .= "<detallista:taxCategory>";
						$xml .= $entidad2['taxCategory'];
						$xml .= "</detallista:taxCategory>";
					}
					if(isset($entidad2['tradeItemTaxAmount']))
					{
						$xml .= "<detallista:tradeItemTaxAmount>";
						if(isset($entidad2['tradeItemTaxAmount']['taxPercentage']))
						{
							$xml .= "<detallista:taxPercentage>";
							$xml .= $entidad2['tradeItemTaxAmount']['taxPercentage'];
							$xml .= "</detallista:taxPercentage>";
						}
						if(isset($entidad2['tradeItemTaxAmount']['taxAmount']))
						{
							$xml .= "<detallista:taxAmount>";
							$xml .= $entidad2['tradeItemTaxAmount']['taxAmount'];
							$xml .= "</detallista:taxAmount>";
						}
						$xml .= "</detallista:tradeItemTaxAmount>";
					}
					$xml .= "</detallista:tradeItemTaxInformation>";
				}
			}
			if(isset($entidad['totalLineAmount']))
			{
				$xml .= "<detallista:totalLineAmount>";
				if(isset($entidad['totalLineAmount']['grossAmount']))
				{
					$xml .= "<detallista:grossAmount>";
					if(isset($entidad['totalLineAmount']['grossAmount']['Amount']))
					{
						$xml .= "<detallista:Amount>";
						$xml .= $entidad['totalLineAmount']['grossAmount']['Amount'];
						$xml .= "</detallista:Amount>";
					}
					$xml .= "</detallista:grossAmount>";
				}
				if(isset($entidad['totalLineAmount']['netAmount']))
				{
					$xml .= "<detallista:netAmount>";
					if(isset($entidad['totalLineAmount']['netAmount']['Amount']))
					{
						$xml .= "<detallista:Amount>";
						$xml .= $entidad['totalLineAmount']['netAmount']['Amount'];
						$xml .= "</detallista:Amount>";
					}
					$xml .= "</detallista:netAmount>";
				}
				$xml .= "</detallista:totalLineAmount>";
			}
			$xml .= "</detallista:lineItem>";
		}
    }
	if(isset($datos['totalAmount']))
    {
		$xml .= "<detallista:totalAmount>";
		if(isset($datos['totalAmount']['Amount']))
		{
			$xml .= "<detallista:Amount>";
			$xml .= $datos['totalAmount']['Amount'];
			$xml .= "</detallista:Amount>";
		}
		$xml .= "</detallista:totalAmount>";
    }
	if(isset($datos['TotalAllowanceCharge']))
    {
		foreach($datos['TotalAllowanceCharge'] as $idx => $entidad)
		{
			$array_atrs = $entidad;
			unset($array_atrs['specialServicesType'], $array_atrs['Amount']);

			$atrs = mf_atributos_nodo($array_atrs);
			$xml .= "<detallista:TotalAllowanceCharge $atrs>";
			if(isset($entidad['specialServicesType']))
			{
				$xml .= "<detallista:specialServicesType>";
				$xml .= $entidad['specialServicesType'];
				$xml .= "</detallista:specialServicesType>";
			}
			if(isset($entidad['Amount']))
			{
				$xml .= "<detallista:Amount>";
				$xml .= $entidad['Amount'];
				$xml .= "</detallista:Amount>";
			}
			$xml .= "</detallista:TotalAllowanceCharge>";
		}
	}

    $xml .= "</detallista:detallista>";
    return $xml;
}
