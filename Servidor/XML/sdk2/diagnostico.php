<?php 
error_reporting(0);


echo "
<style>

*{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px !important;
}	
table.blueTable {
  border: 1px solid #8fa9b7;
  background-color: #EEEEEE;
  width: 100%;
  text-align: left;
  border-collapse: collapse;
}
table.blueTable td, table.blueTable th {
  border: 1px solid #d3eaf2;
  padding: 6px 12px;
}
table.blueTable tbody td {
  
}
table.blueTable thead {
  background: #8fa9b7;
  border-bottom: 2px solid #d3eaf2;
}
table.blueTable thead th {
  
  font-weight: bold;
  color: #FFFFFF;
  border-left: 2px solid #d3eaf2;
}
table.blueTable thead th:first-child {
  border-left: none;
}

table.blueTable tfoot td {
  
}
table.blueTable tfoot .links {
  text-align: right;
}
table.blueTable tfoot .links a{
  display: inline-block;
  background: #1C6EA4;
  color: #FFFFFF;
  padding: 2px 8px;
  border-radius: 5px;
}

.error{
    color: #f04e1f;
}
.OK{
   color:#008f39;     
}
.subtitulos{
    background: #55a2c2 ;
      color: #fefbf8;
      border-bottom: 2px solid #d3eaf2;    
}
table tr td {
	
}
.boton_verde{

    text-shadow: 0px 0px 1px #000 !important;
    cursor: hand; cursor: pointer;
	text-indent:0px;
	font-weight:bold;
	font-style:normal;
	line-height:20px !important;
	text-decoration:underline;
	text-align:center;
    text-transform: uppercase !important;
    min-height: 20px;
    text-decoration: none;
    float: outside;
    color : white !important;
    border-width: 0px;
    
    background-color: #29834b !important;
    border-style: solid;
    border-width: 1px;
    border-radius: 4px;
	border-color: white;
	
	display: inline-block;
	margin: 2px;  
    padding: 1px 10px 1px 10px;
	
  -webkit-transition: background-color 0.3s ease-out;
  -moz-transition: background-color 0.3s ease-out;
  -o-transition: background-color 0.3s ease-out;
  transition: background-color 0.3s ease-out;    	
    
}
.boton_verde a{

    background-color: transparent !important;
    color : white !important;
    text-shadow: 0px 0px 1px #000 !important;
    
    
    background-color: transparent !important;
}

.boton_verde:hover{
    background-color: #92c450 !important;
}

.aParent div {
    float: left;
    clear: none; 
}

</style>

";

global $_kit_ruta_;
$ruta = __DIR__.'/';
$ruta=str_replace('\\','/',$ruta);
$_kit_ruta_=$ruta;


global $__mf_constantes__;
global $__mf_constantes__;


$php_minimo=53;
$VERSIONPHP=PHP_VERSION;
list($uno,$dos,$tres)=explode('.',PHP_VERSION);
$php_actual=intval("$uno$dos");
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk253.php"))
{
	$DATOS_PHP.="SDK Compatible con PHP 5.3 : <span class='OK'>OK</span><br>";		
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 5.3 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk253.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk254.php"))
{
	$DATOS_PHP.="SDK Compatible con PHP 5.4 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 5.4 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk254.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk255.php"))
{
	$DATOS_PHP.="SDK Compatible con PHP 5.5 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 5.5 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk255.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk256.php"))
{
	$DATOS_PHP.="SDK Compatible con PHP 5.6 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 5.6 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk256.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk271.php"))
{
	$DATOS_PHP.="SDK Compatible con PHP 7.1 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 7.1 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk271.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk272.php"))
	{
	$DATOS_PHP.="SDK Compatible con PHP 7.2 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 7.2 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk272.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk274.php"))
{
	$DATOS_PHP.="SDK Compatible con PHP 7.4 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 7.4 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk274.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk281.php"))
{
	$DATOS_PHP.="SDK Compatible con PHP 8.1 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 8.1 :  <span class='error'>ERROR</span><br>";
	$DATOS_PHP_ERROR.="ERROR : Falta Archivo sdk281.php<br>";
}
if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk282.php")) 	
{
	$DATOS_PHP.="SDK Compatible con PHP 8.2 : <span class='OK'>OK</span><br>";
}else{
	$DATOS_PHP.="SDK No Compatible con PHP 8.2 :  <span class='error'>NO COMPATIBLE : Falta Archivo sdk282.php</span><br>";
	$DATOS_PHP_ERROR.="NO COMPATIBLE : Falta Archivo sdk282.php<br>";
}

if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."sdk2$php_actual.php"))
{
	$DATOS_PHP_RESPUESTA="OK: Tu Version de PHP ".PHP_VERSION." es Compatible con el SDK";
}else{
	$DATOS_PHP_RESPUESTA="NO COMPATIBLE : Falta Archivo sdk2$php_actual.php en el SDK <br> Verifica que exista el archivo sdk2$php_actual.php en la raiz de la carpeta SDK2 <BR> Actualiza el SDK";
    $ERROR_VERSION_SDK="SI";
}

if(intval($php_actual) < intval($php_minimo)){
	$TR_PHP.="<tr><td>Tu Version PHP ".PHP_VERSION."  <BR><br> $DATOS_PHP</td>
				  <td class='error'>ERROR</td>
				  <td>ERROR</td>
				  <td>ERROR: $DATOS_PHP_RESPUESTA</td></tr>";
}
else
{
	$td_php="<td class='OK'>OK</td>";
	if($ERROR_VERSION_SDK=='SI')
		$td_php="<td class='error'>ERROR</td>";
 
    $TR_PHP.="<tr><td>Tu Version PHP ".PHP_VERSION."  <BR><br> $DATOS_PHP</td>
				<td class='error'></td>
				$td_php
				<td >$DATOS_PHP_RESPUESTA</td></tr>";
}

/////////////////////////// IONCUBE //////////////////////////////////////////////////

if (function_exists('ioncube_loader_version'))
{
    $versionI = ioncube_loader_iversion();
    $versionI = sprintf('%d.%d.%d', $versionI / 10000, ($versionI / 100) % 100, $versionI % 100);
    
    $DATOS_IONCUBE="Ioncube Loader: Instalado Correctamente <span class='OK'>OK</span>";
    $tr_IONCUBE.="<tr><td>Ioncube Loader <span class='OK'>OK</span></td><td class='error'></td><td class='OK'>OK</td><td>OK: Ioncube Correctamente Instalado<BR> Version Ioncube Loader $versionI</td></tr>";
}
else
{
    $DATOS_IONCUBE="Ioncube Loader: No Instalado <span class='error'>ERROR</span>";
    $tr_IONCUBE.="<tr><td>Ioncube Loader</td><td class='error'>ERROR</td><td>Ioncube NO Instalado</td><td>IONCUBE:Para instalar IONCUBE <a href=''>VER DOCUMENTACION</a></td></tr>";
}
////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////   XSL //////////////////////////
$xsl='';
if(function_exists('shell_exec'))
{
    $respuesta=shell_exec('xsltproc');
    if(strpos($respuesta,'--verbose'))
    {
        $xsl="OK";
        $datos_XSLT.="Extencion XSLT <span class='OK'>OK</span><br>";
        $datos_XSLT_resp.="OK: XSTLPROC Instalado Correctamente<br>";
    }
    else
    {
        $datos_XSLT.="Extencion XSLT <span class='error'>Falta libreria XSTLPROC</span><br>";
        $datos_XSLT_resp.="NO INSTALADO: Falta libreria XSTLPROC<br>Instala XSLTPROC para Generar la Cadena Original del Certificado<br><br>";
    }
}
else
{
    $datos_XSLT.="<span class='error'>NO SE PUEDE VERICAR XSLTPROC</span><br>";
    $datos_XSLT_resp.="FALTAN PERMISOS : Instala 'shell_exec' para verificar EXTENSION XSL<br><br>";
}

if(class_exists("DOMDocument")==true)
{
        $xsl="OK";
	    $datos_XSLT.="Extension DOMDocument 'extension_dom' <span class='OK'>OK</span><br>";
        $datos_XSLT_resp.="OK: DOMDocument extension 'extension_dom' Instalado Correctamente<br>";
}
else
{        
        $datos_XSLT.="Extension DOMDocument <span class='error'>Falta extension 'extension_dom'</span><br>";
        $datos_XSLT_resp.="NO INSTALADO: Falta extension 'extension_dom'<br>Instala 'extension_dom' para Generar la Cadena Original del Certificado<br><br>";
}

if(class_exists("XSLTProcessor")==true)
{
    $xsl="OK";
    $datos_XSLT.="Extension DOMDocument 'XSLTProcessor' <span class='OK'>OK</span><br>";
    $datos_XSLT_resp.="OK: Extension DOMDocument 'XSLTProcessor' Instalado Correctamente<br>";
}else
{        
    $datos_XSLT.="Extension DOMDocument <span class='error'>Falta extension 'XSLTProcessor'</span><br>";
    $datos_XSLT_resp.="NO INSTALADO: Falta Extension 'XSLTProcessor'<br>Instala 'XSLTProcessor' para Generar la Cadena Original del Certificado<br><br>";
}

if(file_exists($_kit_ruta_."lib/nodos/cfdi4/sat/xslt40/cadenaoriginal40.xslt")==true )
{
    $xsl="OK";
	$datos_XSLT.="Archivos XSLT en SDK: <span class='OK'>OK</span><br>";
    $datos_XSLT_resp.="OK: Archivos XSLT en SDK Instalados Correctamente<br>";
} 
else
{
    $datos_XSLT.="Archivos XSLT en SDK<span class='error'>FALTA CARPETA XSLT</span><br>";
    $datos_XSLT_resp.="Falta Carpeta XSLT: <br>FALTA CARPETA XSLT SIN ELLA NO SE PUEDE GENERAR EL SELLO<br><br>";
}

if($xsl !='OK'){
    $xsl="<span class='error'>ERROR</span>";
}else{
    $xsl="<span class='OK'>OK</span>";
}
$tr_XSLT.="<tr><td>$datos_XSLT</td><td class='error'></td><td>$xsl</td><td>$datos_XSLT_resp</td></tr>";

/////////////////////////////////////////////////
////        PRUEBA XSD 
/////////////////////////////////////////////////

$xds='';
if(file_exists($_kit_ruta_."lib/nodos/cfdi4/sat/xsd40/cfdv40.xsd")==true)
{
    $xds='OK';
    $datos_XSD.="Archivos XSD en SDK <span>OK</span> <br>";
    $datos_XSD_resp.="OK: Archivos XSD se encuentran en SDK <br>";
    
}
else
{
    $datos_XSD.="Archivos XSD en SDK <span>NO SE ENCUENTRAN</span> <br>";
    $datos_XSD_resp.="NO SE ENCUENTRAN: Archivos XSD NO se encuentran en SDK <br> <br> Verifica que existan <br> -Revisa permisos de Lectura y Escritura en el SDK <br> <br>";
    
    //$pruebas['validar_xsd'] = 'ESTADO : ADVERTENCIA!!!! falta la carpeta xsd, sin ella no se puede validar el XML antes de timbrar haciendo mas probable consumas timbres inecesarios';
    //$tr.="<tr><td>VALIDACION XSD</td><td class='error'>ERROR</td><td>ERROR: VALIDACION XSD</td><td>ESTADO : ADVERTENCIA!!!! falta la carpeta xsd, sin ella no se puede validar el XML antes de timbrar haciendo mas probable consumas timbres inecesarios</td></tr>";
}


/////////////////////////////////////////////////
////        PRUEBA OPENSSL 
/////////////////////////////////////////////////
//$openssl='';

/*
    if(function_exists('shell_exec'))
    {
        $respuesta=shell_exec('openssl --');
        if(strpos($respuesta,'x509'))
        {
            $openssl.="OK";
			$pruebas['openssl'] = 'OK';
        }
        else
        {
            $pruebas['openssl'] = 'ERROR : Requiere ejecutable openssl';
        }
        
    }
    else
    {
		$pruebas['openssl'] = 'ERROR : requiere permiso shell_exec para validar openssl';
    }
*/
if(function_exists('openssl_sign')==true)
{
    //$openssl="OK";            
    $xds='OK';
    $datos_XSD.="OPEN SSL: 'openssl_sign' <span class='OK'>OK</span> <br>";
    $datos_XSD_resp.="OK: OPEN SSL: 'openssl_sign' Instalado Correctamente<br>";
        
}
else
{
        //$pruebas['openssl_sign'] = 'ERROR : Requiere libreria OpenSSL para PHP';
        //$tr.="<tr><td>OPEN SSL</td><td class='error'>ERROR</td><td>ERROR : Requiere libreria OpenSSL para PHP</td><td>ERROR: Instala OPEN SSL</td></tr>";
        
    $datos_XSD.="OPEN SSL: 'openssl_sign' <span class='error'>NO ENCOTRADO</span> <br>";
    $datos_XSD_resp.="NO ENCONTRADO: OPEN SSL: 'openssl_sign' Requiere libreria OpenSSL para PHP para generar el SELLO<br>";
}
if(function_exists('simplexml_load_file')==true)
{
		//$pruebas['lib_simplexml'] = 'OK';
        //$tr.="<tr><td>simplexml_load_file</td><td class='error'>ERROR</td><td class='OK'>OK</td><td>OK: simplexml_load_file Instalada Correctamente</td></tr>";
        
        $xds='OK';
        $datos_XSD.="Extencion SIMPLEXML: 'simplexml_load_file' <span class='OK'>OK</span> <br>";
        $datos_XSD_resp.="OK: SIMPLEXML: 'simplexml_load_file' Instalado Correctamente<br>";
}
else
{
		//$pruebas['lib_simplexml'] = 'ERROR : Requiere libreria simplexml para PHP';
        //$tr.="<tr><td>simplexml_load_file</td><td class='error'>ERROR</td><td>ERROR: simplexml_load_file</td><td>ERROR : Requiere libreria simplexml para PHP</td></tr>";
        
        $datos_XSD.="Extencion SIMPLEXML: 'simplexml_load_file' <span class='error'>NO ENCOTRADO</span> <br>";
        $datos_XSD_resp.="NO ENCONTRADO: SIMPLEXML: 'simplexml_load_file' Requiere libreria SIMPLEXML para PHP para generar el SELLO<br>";
}



if(class_exists("DOMDocument")==true )
{
		$xds='OK';
        
        $datos_XSD.="Extencion DOMDocument: <span class='OK'>OK</span> <br>";
        $datos_XSD_resp.="OK: DOMDocument: Instalado Correctamente<br>";
        
}
else
{
		$datos_XSD.="Extencion DOMDocument: <span class='error'>NO ENCONTRADA</span> <br>";
        $datos_XSD_resp.="NO ENCONTRADA: Requiere libreria DOMDocument PHP <br>";
}

if($xds !='OK'){
    $xsl="<span class='error'>ERROR</span>";
}else{
    $xds="<span class='OK'>OK</span>";
}
$tr_XSD.="<tr><td>$datos_XSD</td><td class='error'></td><td>$xds</td><td>$datos_XSD_resp</td></tr>";


/////////////////////////////// PRUBEAS DE CONEXCION AL PAC
$error_pac=0;
for($i=1;$i<=10;$i++)
{
	$res= file_get_contents("http://pac$i.facturacionmexico.com.mx/pac?wsdl");
	if(strlen($res)>5000)
	{
		$DATOS_PAC.="Conexion al Servidor PAC$i: <span class='OK'>OK</span><br>";		
	}
	else
	{
		$error_pac++;
		$DATOS_PAC.="Conexion al Servidor PAC$i: <span class='error'>ERROR</span><br>";
	}
	
	if($error_pac >=1)
	{
		$TR_PAC="<tr><td>$DATOS_PAC</td><td class='error'></td><td class='error'></td><td><SPAN class='error'>ERROR: CONEXION Y RESPUESTA AL PAC </span><BR><br>Falla de Comunucacion al servidor timbrado:<br> <br>- Revise firewall o conexion a internet <br>- Haz PING a los servidores <BR> &nbsp &nbsp http://pac1.facturacionmexico.com.mx<BR>  &nbsp &nbsp hasta <BR>  &nbsp &nbsp http://pac10.facturacionmexico.com.mx <BR>  &nbsp &nbsp Para verificar que haya respuesta de los servidores PAC</td></tr>";
	}else{
	
		$TR_PAC="<tr><td>$DATOS_PAC</td><td class='error'></td><td class='OK'>OK</td><td>OK: La Comunicacion al servidor de Timbrado es Correcta</td></tr>";
	}	
}

//PERMISOS CARPETAS TMB Y LIB

$rand=rand(111,999).".txt";
file_put_contents($__mf_constantes__['__MF_SDK_TMP__'].$rand,$rand);
file_put_contents($__mf_constantes__['__MF_LIBS_DIR__'].$rand,$rand);

$tmp=file_get_contents($__mf_constantes__['__MF_SDK_TMP__'].$rand);
$lib=file_get_contents($__mf_constantes__['__MF_LIBS_DIR__'].$rand);

$tmp=unlink($__mf_constantes__['__MF_SDK_TMP__'].$rand);
$lib=unlink($__mf_constantes__['__MF_LIBS_DIR__'].$rand);

if($tmp==$rand)
{
    $permisos_tmp='OK';
	$DATOS_CARPETAS.="CARPETA TMP: Permisos de Lectura y Escritura <span class='OK'>OK</span><BR>";
} 
else
{
	$DATOS_CARPETAS.="CARPETA TMP: Permisos de Lectura y Escritura <span class='error'>ERROR</span><BR>";
}
/*
if($lib==$rand)
{
    $permisos_lib='OK';
	$DATOS_CARPETAS.="CARPETA LIB: Permisos de Lectura y Escritura <span class='OK'>OK</span><BR>";
} 
else
{
   $DATOS_CARPETAS.="CARPETA LIB: Permisos de Lectura y Escritura <span class='error'>ERROR</span><BR>";
}

if($permisos_tmp=='OK' AND $permisos_lib == 'OK'){
		
		$tr_PERMISOS.="<tr><td>$DATOS_CARPETAS</td><td class='error'></td><td class='OK'>OK</td><td>OK: Permisos de Lectura y Escritura en Carpeta TMP Y LIB Correctos</td></tr>";
}else{
		$tr_PERMISOS.="<tr><td>$DATOS_CARPETAS</td><td class='error'>ERROR</td><td></td><td>ERROR: No Hay Permisos de Lectura y Escritura en Carpeta TMP Y LIB Correctos</td></tr>";
}

*/
$permisos_lib='OK';
if($permisos_tmp=='OK' AND $permisos_lib == 'OK')
{
		$tr_PERMISOS.="<tr><td>$DATOS_CARPETAS</td><td class='error'></td><td class='OK'>OK</td><td>OK: Permiso de Lectura y Escritura en Carpeta TMP Correcto</td></tr>";
}
else
{
		$tr_PERMISOS.="<tr><td>$DATOS_CARPETAS</td><td class='error'>ERROR</td><td></td><td>ERROR: NO Hay Permisos de Lectura y Escritura en Carpeta TMP </td></tr>";
}

/////////////////////////////////////////////////
////        GENERACION PEM 
/////////////////////////////////////////////////
$cer_base64=base64_encode(file_get_contents("certificados/EKU9003173C9.cer"));
$key_base64=base64_encode(file_get_contents("certificados/EKU9003173C9.key"));
$pass_base64=base64_encode('12345678a');
$ruta_nusoap = $_kit_ruta_."/lib/nusoap/nusoap.php";
include $ruta_nusoap;
$pac=rand(1,10);
$url2="http://pac9.facturacionmexico.com.mx/pac/?wsdl";
$soapclient = new nusoap_client($url2,$esWSDL = true);
$tim = array('cer_base64' => $cer_base64, 'key_base64' => $key_base64,'pass_base64' => $pass_base64);
$soap_timbrado = $soapclient->call('generar_pem', $tim);
$res=$soap_timbrado;
$cer_pem= base64_decode($res['cer_pem']);
$key_pem= base64_decode($res['key_pem']);
if(strlen($cer_pem)>20)
	file_put_contents($__mf_constantes__['__MF_SDK_DIR__']."certificados/EKU9003173C9.cer.pem",$cer_pem);
if(strlen($key_pem)>20)
	file_put_contents($__mf_constantes__['__MF_SDK_DIR__']."certificados/EKU9003173C9.key.pem",$key_pem);

if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."certificados/EKU9003173C9.cer.pem"))
{
	$DATOS_CERTIFICADOS.="GENERAR ARCHIVO CER .PEM <SPAN CLASS='OK'>OK: ARCHIVO CER .PEM GENERADO CORRECTAMENTE</SPAN><br>";
	//$tr.="<tr><td>GENERA CER .PEM</td><td class='error'>ERROR</td><td class='OK'>OK</td><td>OK: ARCHIVO CER .PEM GENERADO CORRECTAMENTE</td></tr>";
}
else
{
	$error_certificados='SI';
	$DATOS_CERTIFICADOS.="GENERAR ARCHIVO CER .PEM <SPAN CLASS='error'>ERROR: NO SE PUEDEN PROCESAR ARCHIVO CER .PEM</SPAN><br>";
	//$tr.="<tr><td>GENERA CER .PEM</td><td class='error'>ERROR</td><td>ERROR:GENERA ARCHIVO CER .PEM</td><td>ERROR GENERANDO ARCHIVO .CER.PEM, ERROR GRAVE, NO SE PUEDEN PROCESAR LOS CERTIFICADOS</td></tr>";
}

if(file_exists($__mf_constantes__['__MF_SDK_DIR__']."certificados/EKU9003173C9.key.pem"))
{
	$DATOS_CERTIFICADOS.="GENERAR ARCHIVO KEY .PEM <SPAN CLASS='OK'>OK: ARCHIVO KEY .PEM GENERADO CORRECTAMENTE</SPAN><br>";
	//$tr.="<tr><td>GENERA KEY .PEM</td><td class='error'>ERROR</td><td class='OK'>OK</td><td>OK: ARCHIVO KEY .PEM GENERADO CORRECTAMENTE</td></tr>";
}
else
{
	$error_certificados='SI';
	$DATOS_CERTIFICADOS.="GENERAR ARCHIVO KEY .PEM <SPAN CLASS='error'>ERROR: NO SE PUEDEN PROCESAR ARCHIVO KEY .PEM</SPAN><br>";
	//$tr.="<tr><td>GENERA KEY .PEM</td><td class='error'>ERROR</td><td>ERROR: GENERA ARCHIVO KEY .PEM</td><td>ERROR GENERANDO ARCHIVO .CER.PEM, ERROR GRAVE, NO SE PUEDEN PROCESAR LOS CERTIFICADOS</td></tr>";
}

if($error_certificados=='SI')
{
	$td_certificados="<td class='error'></td><td CLASS='error'>ERROR</td>";
	$DATOS_CERTIFICADOS_RESPUESTA='ERROR AL PROCESAR LOS CERTIFICADOS CER Y KEY<BR>
	&nbsp&nbsp -Revisa Conexion a internet<br>
	&nbsp&nbsp -Verifica que la carpeta "certificados"  en en la carpeta SDK tenga permisos de LECTURA Y ESCRITURA<br>
	&nbsp&nbsp -Revisa que existan los archivos CER y KEY de los Certificados en la carpeta "Certificados" en la carpeta SDK<br>
	';
}else{
	$td_certificados="<td class='error'></td><td CLASS='OK'>OK</td>";
	$DATOS_CERTIFICADOS_RESPUESTA='OK: ARCHIVO CER y KEY .PEM GENERADOS CORRECTAMENTE';

}

$tr_certificados.="<tr><td>$DATOS_CERTIFICADOS</td>$td_certificados<td>$DATOS_CERTIFICADOS_RESPUESTA</td></tr>";
//BORRAR .PEM DE LOS CERTIFICADOS
unlink($$__mf_constantes__['__MF_SDK_DIR__']."certificados/EKU9003173C9.cer.pem");
unlink($$__mf_constantes__['__MF_SDK_DIR__']."certificados/EKU9003173C9.key.pem");



echo "<BR><BR><div class='aParent'><a target=''mf href='https://multifacturas.com'><img border=0 src='https://www.multifacturas.com/attachments/Logo/logo-grande.png'></a></div><div></div>";
echo "<BR><BR><CENTER><h1>SERVICIO DE DIAGNOSTICO LIBRERIA CFDI</h1><CENTER><BR>";
echo $tabla="<table class='blueTable'>
<thead>
<tr>
<th>REQUISITOS SDK</th>
<th>EJECUTABLE</th>
<th>LIBRERIA PHP</th>
<th>RESPUESTA</th>
</tr>
</thead>
<tr class='subtitulos'><td>PRUEBA IONCUBE LOADER</td><td></td><td></td><td></td></tr>
$tr_IONCUBE
<tr class='subtitulos'><td>PRUEBA DE CONEXION A LOS SERVIDORES DEL PAC</td><td></td><td></td><td></td></tr>
$TR_PAC
<tr class='subtitulos'><td>PRUEBA DE VERSIONES PHP COMPATIBLES</td><td></td><td></td><td></td></tr>
$TR_PHP
<tr class='subtitulos'><td>PRUEBA GENERAR CERTIFICADOS CSD</td><td></td><td></td><td></td></tr>
$tr_certificados
<tr class='subtitulos'><td>PRUEBA PERMISOS DE CARPETAS EN LA CARPETA SDK2</td><td></td><td></td><td></td></tr>
$tr_PERMISOS
<tr class='subtitulos'><td>PRUEBA PARA LEER LA CADENA ORIGINAL DEL CERTIFICADO</td><td></td><td></td><td></td></tr>
$tr_XSLT
<tr class='subtitulos'><td>PRUEBA PARA GENERAR EL CERTIFICADO DEL SELLO DIGITAL</td><td></td><td></td><td></td></tr>
$tr_XSD
</table>";


$server = $_SERVER['SERVER_NAME'];
$diag=$_SERVER['SCRIPT_NAME'];
$diag= str_replace("diagnostico.php", "", $diag);
$url="http://".$server.$diag."ejemplos/cfdi40/ejemplo_factura_basica4.php";
echo "<BR><DIV class='boton_verde'><A HREF='$url' target='_blank'>EJECUTAR EJEMPLO PARA CREAR FACTURA</A></DIV>";

echo "<DIV class='boton_verde'><A HREF='https://www.multifacturas.com/' target='_blank'>SI NESECITAS AYUDA CONTACTA A SOPORTE</A></DIV>";

echo "<BR><BR><BR><BR><BR><BR><BR><BR>";


?>