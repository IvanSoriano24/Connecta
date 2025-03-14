<?php

function mf_phpversion()
{
    $version = phpversion();
    $numversion = '';
    for($i = 0, $punto = 0; $i < strlen($version); $i++)
    {
        if($version[$i] == '.')
        {
            if($punto == 0)
            {
                $numversion .= $version[$i];
                $punto++;
            }
        }
        else
        {
            $numversion .= $version[$i];
        }
    }

	$version_final="$numversion";
	
	$ver=$version_final[0].'.'.$version_final[2];
	return $ver;
    //return doubleval($numversion);
}
///////////////////////////////////////////////////////
//phpinfo();
function mf_postfijo_php()
{
	global $_kit_ruta_;
    $ruta = __DIR__.'/';
    $ruta=str_replace('\\','/',$ruta);
	$_kit_ruta_=$ruta;
    $rutaweb=$_SERVER['REQUEST_URI'];
    $ruta_local='ifacturas_docs/sdk2_desa';
    $mystring = 'abc';
    $findme   = 'a';
    if (strpos($rutaweb, $ruta_local)>0)
    {
        return 'XX';
    }


//    if($_SERVER["SERVER_NAME"]=='55.cfdi.red' || $_SERVER["SERVER_NAME"]=='56.cfdi.red' || $_SERVER['SERVER_ADDR']=='192.168.10.11'  || $_SERVER['SERVER_ADDR']=='51.222.71.161')
    if(strpos($rutaweb, 'sdk2_desarrollo')>1)
    {
        if(file_exists($ruta.'sdk2XX.php')==true)
        {

            return 'XX';
        }
        else
        {
//			if($_SERVER["SERVER_NAME"]=='55.cfdi.red')
//				return '55';

//            return '71';
        }
        
    }


/*


    if($_SERVER["SERVER_NAME"]=='55.cfdi.red' || $_SERVER["SERVER_NAME"]=='56.cfdi.red' || $_SERVER['SERVER_ADDR']=='192.168.10.11' )
    {
        if(file_exists($ruta.'sdk2XX.php')==true)
        {

            return 'XX';
        }
        else
        {
			if($_SERVER["SERVER_NAME"]=='55.cfdi.red')
				return '56';

            return '71';
        }
        
    }
*/
    $php_version = mf_phpversion();

    switch($php_version)
    {
        case 5.2: return '53'; break;
    	case 5.3: return '53'; break;
    	case 5.4: return '54'; break;
    	case 5.5: return '55'; break;
    	case 5.6: return '56'; break;
    	case 7.0: return '56'; break;
    	case 7.1: return '71'; break;
    	case 7.2: return '72'; break;
    	case 7.3: return '72'; break;
        case 7.4: return '74'; break;
        case 7.5: return '74'; break;
        case 7.6: return '74'; break;
        case 8.0: return '81'; break;
//        case 8.0: echo "php 8.0 no compatible use 7.+ u 8.1"; die(); break;		
        case 8.1: return '81'; break;
        case 8.2: return '82'; break;
        case 8.3: return '82'; break;
        case 8.4: return '82'; break;
        default : echo "La version '$php_version' no es compatible";die();
    }

}
////////////////////////////////////////////////
$php_version = mf_postfijo_php();
 $ruta=$ruta."sdk2$php_version.php";
//echo $ruta; die(); 
//echo "<bR>ruta sdk $ruta <br>";
//$ruta="sdk2XX.php";
require_once $ruta;  


