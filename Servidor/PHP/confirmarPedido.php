<?php
//$logFile = '/var/log/confirmarPedido.log';
// al inicio de tu script PHP
$logDir  = __DIR__ . '/logs';
$logFile = $logDir . '/confirmarPedido.log';
// Se establece la zona horaria
date_default_timezone_set('America/Mexico_City');
// Se importa los datos de firebase
require 'firebase.php'; // Archivo de configuración de Firebase
//Funcion para obtener la conexion de la empresa
function obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa)
{
    // URL de la coleccion
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);
    //Realizamos la peticion
    $result = file_get_contents($url, false, $context);

    //Si no fue correcta, nos regresa un false
    if ($result === FALSE) {
        return ['success' => false, 'message' => 'Error al obtener los datos de Firebase'];
    }
    //Guardamos los resultados decodificados
    $documents = json_decode($result, true);
    //Regresa un false si no hay datos
    if (!isset($documents['documents'])) {
        return ['success' => false, 'message' => 'No se encontraron documentos'];
    }
    // Busca el documento donde coincida el campo `noEmpresa`
    foreach ($documents['documents'] as $document) {
        $fields = $document['fields'];
        if ($fields['noEmpresa']['integerValue'] === $noEmpresa) {
            //Retorna los datos obtenedos
            return [
                'success' => true,
                'data' => [
                    'host' => $fields['host']['stringValue'],
                    'puerto' => $fields['puerto']['stringValue'],
                    'usuario' => $fields['usuario']['stringValue'],
                    'password' => $fields['password']['stringValue'],
                    'nombreBase' => $fields['nombreBase']['stringValue'],
                    'claveSae' => $fields['claveSae']['stringValue']
                ]
            ];
        }
    }
    //Regresa un false si no se encontro una conexion para la empresa especificada
    return ['success' => false, 'message' => 'No se encontró una conexión para la empresa especificada'];
}
function obtenerProductos($pedidoId, $conexionData, $claveSae)
{
    //Creamos la conexion
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    //Verificamos que la conexion sea exitosa
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }
    //Formateamos la clave para coincidir con SAE
    $CVE_DOC = str_pad($pedidoId, 10, '0', STR_PAD_LEFT);
    $CVE_DOC = str_pad($CVE_DOC, 20, ' ', STR_PAD_LEFT);
    //Creamos el nombre de la tabla de forma dinamica con respecto a la claveSAE
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    //Construimos la consulta
    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_DOC = ?";
    //Construimos los parametros
    $params = [$CVE_DOC];
    //Ejecutamos la consulta con los parametros
    $stmt = sqlsrv_query($conn, $sql, $params);
    //Dar mensaje de error si falla
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    //Se crear un array donde se guardaran los datos
    $partidas = [];
    //Se itera sobre los resultados de la consulta y se guardan en el array
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partidas[] = $row;
    }
    //Se retorna los resultados
    return $partidas;
    //Se libera y se cierra la conexion
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function obtenerDescripcion($producto, $conexionData, $claveSae)
{
    //Creamos la conexion
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }
    //Creamos el nombre de la tabla de forma dinamica con respecto a la claveSAE
    $nombreTabla  = "[{$conexionData['nombreBase']}].[dbo].[INVE"  . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    //Construimos la consulta
    $sql = "SELECT * FROM $nombreTabla WHERE
        CVE_ART = ?";
    $params = [$producto];
    //Ejecutamos la consulta con los parametros
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta', 'errors' => sqlsrv_errors()]));
    }
    // Obtener los resultados
    $productoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($productoData) {
        //Retornar los resultados
        return $productoData;
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function formatearClaveVendedor($clave)
{
    // Asegurar que la clave sea un string y eliminar espacios innecesarios
    $clave = trim((string) $clave);
    $clave = str_pad($clave, 5, ' ', STR_PAD_LEFT);
    // Si la clave ya tiene 5 caracteres, devolverla tal cual
    if (strlen($clave) === 5) {
        return $clave;
    }
    // Si es menor a 5 caracteres, rellenar con espacios a la izquierda
    $clave = str_pad($clave, 5, ' ', STR_PAD_LEFT);
    return $clave;
}

use Google\Cloud\Firestore\FirestoreClient;

function datosEnvioNuevo($idEnvios, $firebaseProjectId, $firebaseApiKey)
{
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/DATOS_PEDIDO/$idEnvios?key=$firebaseApiKey";

    $json = @file_get_contents($url);
    $response = json_decode($json, true);

    if (!isset($response['fields'])) {
        echo "<div class='container'>
                <div class='title'>Error</div>
                <div class='message'>No se encontró el documento con ID: $idEnvios en la colección DATOS_PEDIDO.</div>
              </div>";
        return [];
    }

    $fields = $response['fields'];

    return [
        'codigoContacto' => $fields['codigoContacto']['stringValue'] ?? '',
        'companiaContacto' => $fields['companiaContacto']['stringValue'] ?? '',
        'correoContacto' => $fields['correoContacto']['stringValue'] ?? '',
        'direccion1Contacto' => $fields['direccion1Contacto']['stringValue'] ?? '',
        'direccion2Contacto' => $fields['direccion2Contacto']['stringValue'] ?? '',
        'estadoContacto' => $fields['estadoContacto']['stringValue'] ?? '',
        'idPedido' => $fields['idPedido']['integerValue'] ?? 0,
        'municipioContacto' => $fields['municipioContacto']['stringValue'] ?? '',
        'noEmpresa' => $fields['noEmpresa']['integerValue'] ?? 0,
        'nombreContacto' => $fields['nombreContacto']['stringValue'] ?? '',
        'telefonoContacto' => $fields['telefonoContacto']['stringValue'] ?? '',
        'observaciones' => $fields['observaciones']['stringValue'] ?? ''
    ];
}
//Verificamos si se recibe los datos
if (isset($_GET['pedidoId']) && isset($_GET['accion'])) {
    //Guardamos los datos recibidos en variables
    $pedidoId         = $_GET['pedidoId'] ?? "";
    $accion           = $_GET['accion'];
    $nombreCliente    = urldecode($_GET['nombreCliente'] ?? 'Desconocido');
    $enviarA          = urldecode($_GET['enviarA'] ?? 'No especificado');
    $vendedor         = urldecode($_GET['vendedor'] ?? 'Sin vendedor');
    $claveSae         = $_GET['claveSae'] ?? "";
    $claveCliente     = $_GET['claveCliente'] ?? "";
    $noEmpresa        = $_GET['noEmpresa'] ?? "";
    $clave            = $_GET['clave'] ?? "";
    $conCredito       = $_GET['conCredito'] ?? "";
    $fechaElaboracion = urldecode($_GET['fechaElab'] ?? 'Sin fecha');
    $idEnvios = $_GET['idEnvios'] ?? "";

    $error = error_get_last();
    $msg = sprintf(
        "[%s] INFO: El cliente $clave realizo la accion → %s\n",
        date('Y-m-d H:i:s'),
        json_encode($error, JSON_UNESCAPED_UNICODE)
    );
    error_log($msg, 3, $logFile);
    //Verificamos si el pedido ya fue aceptado
    $resultado = verificarExistencia($firebaseProjectId, $firebaseApiKey, $pedidoId);
    /*var_dump($resultado);
    die();*/
    if (!$resultado) {
        //Si ya fue aceptado, mostrar este mensaje
        $err = error_get_last();
        $msg = sprintf(
            "[%s] Advertencia: Confirmacion Repetida de  $pedidoId → %s\n",
            date('Y-m-d H:i:s'),
            json_encode($err, JSON_UNESCAPED_UNICODE)
        );
        error_log($msg, 3, $logFile);
        echo "<div class='container'>
            <div class='title'>Solicitud Inválida</div>
            <div class='message'>Este Pedido ya Fue Aceptado.</div>
            <!--<a href='/index.php' class='button'>Volver al inicio</a>-->
          </div>";
    } else {
        //Obtenemos los datos de conexion
        $conexionResult = obtenerConexion($claveSae, $firebaseProjectId, $firebaseApiKey, $noEmpresa);
        if (!$conexionResult['success']) {
            echo json_encode($conexionResult);
            die();
        }
        $conexionData = $conexionResult['data'];
        //Verifcamos que accion realizara
        if ($accion === 'confirmar') {
            $rechazo = buscarRechazo($firebaseProjectId, $firebaseApiKey, $pedidoId, $noEmpresa);
            if (!$rechazo) {
                //Inicia validacion
                $exsitencias = verificarExistencias($pedidoId, $conexionData, $claveSae, $logFile);
                if ($exsitencias['success']) {
                    $error = error_get_last();
                    $msg = sprintf(
                        "[%s] INFO: El pedido $pedidoId cuenta con las existencias necesarias → %s\n",
                        date('Y-m-d H:i:s'),
                        json_encode($error, JSON_UNESCAPED_UNICODE)
                    );
                    error_log($msg, 3, $logFile);
                    //Verificamos si es un pedido realizado con credito o sin credito
                    if ($conCredito === 'S') {
                        $error = error_get_last();
                        $msg = sprintf(
                            "[%s] INFO: El cliente $clave maneja credico → %s\n",
                            date('Y-m-d H:i:s'),
                            json_encode($error, JSON_UNESCAPED_UNICODE)
                        );
                        error_log($msg, 3, $logFile);
                        // Obtener la hora actual
                        $horaActual = (int) date('H'); // Hora actual en formato 24 horas (e.g., 13 para 1:00 PM)
                        // Determinar el estado según la hora
                        $estadoComanda = $horaActual >= 13 ? "Pendiente" : "Abierta"; // "Pendiente" después de 1:00 PM
                        //$estadoComanda = $horaActual >= 15 ? "Pendiente" : "Abierta"; // "Pendiente" después de 3:00 PM
                        //Obtenemos los productos
                        $producto = obtenerProductos($pedidoId, $conexionData, $claveSae);
                        $envioData = datosEnvioNuevo($idEnvios, $firebaseProjectId, $firebaseApiKey);
                        // Preparar datos para Firebase
                        $comanda = [
                            "fields" => [
                                "idComanda" => ["stringValue" => uniqid()],
                                "folio" => ["stringValue" => $pedidoId],
                                "claveCliente" => ["stringValue" => $claveCliente],
                                "nombreCliente" => ["stringValue" => $nombreCliente],
                                "enviarA" => ["stringValue" => $enviarA],
                                "fechaHoraElaboracion" => ["stringValue" => $fechaElaboracion ?? ""],
                                "productos" => [
                                    "arrayValue" => [
                                        "values" => array_map(function ($producto) use ($conexionData, $claveSae) {
                                            $productoData = obtenerDescripcion($producto["CVE_ART"], $conexionData, $claveSae);
                                            return [
                                                "mapValue" => [
                                                    "fields" => [
                                                        "clave" => ["stringValue" => $producto["CVE_ART"]],
                                                        "descripcion" => ["stringValue" => $productoData["DESCR"]],
                                                        "cantidad" => ["integerValue" => (int) $producto["CANT"]],
                                                    ]
                                                ]
                                            ];
                                        }, $producto)
                                    ]
                                ],
                                "envio" => [
                                    'mapValue' => ['fields' => [
                                        'codigoContacto' => ['stringValue' => $envioData['codigoContacto']],
                                        'companiaContacto' => ['stringValue' => $envioData['companiaContacto']],
                                        'correoContacto' => ['stringValue' => $envioData['correoContacto']],
                                        'direccion1Contacto' => ['stringValue' => $envioData['direccion1Contacto']],
                                        'direccion2Contacto' => ['stringValue' => $envioData['direccion2Contacto']],
                                        'estadoContacto' => ['stringValue' => $envioData['estadoContacto']],
                                        'idPedido' => ['integerValue' => $envioData['idPedido']],
                                        'municipioContacto' => ['stringValue' => $envioData['municipioContacto']],
                                        'noEmpresa' => ['integerValue' => $envioData['noEmpresa']],
                                        'nombreContacto' => ['stringValue' => $envioData['nombreContacto']],
                                        'telefonoContacto' => ['stringValue' => $envioData['telefonoContacto']],
                                    ]]
                                ],
                                "vendedor" => ["stringValue" => $vendedor],
                                "status" => ["stringValue" => $estadoComanda], // Establecer estado según la hora
                                "claveSae" => ["stringValue" => $claveSae],
                                "noEmpresa" => ["integerValue" => $noEmpresa], //integerValue
                                "pagada" => ["booleanValue" => true], //true
                                "credito" => ["booleanValue" => true],
                                "facturado" => ["booleanValue" => false],
                                "observaciones" => ["stringValue" => $envioData['observaciones'] ?? ""]
                            ]
                        ];

                        // URL de Firebase
                        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/COMANDA?key=$firebaseApiKey";

                        // Enviar los datos a Firebase
                        $context = stream_context_create([
                            'http' => [
                                'method' => 'POST',
                                'header' => "Content-Type: application/json\r\n",
                                'content' => json_encode($comanda)
                            ]
                        ]);
                        //Realizamos la consulta
                        $response = @file_get_contents($url, false, $context);

                        if ($response === false) {
                            //Si la consulta no fue correcta, mostrará este mensaje
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] ERROR: Error al crear la Comanda → %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            echo "<div class='container'>
                        <div class='title'>Error al Conectarse</div>
                        <div class='message'>Hubo un problema al confirmar su pedido</div>
                        <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                      </div>";
                            die();
                        } else {
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] Succes: Comanda Creada→ %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            //Si fue correcta, empieza a realizar la remision obteniendo 
                            $result = json_decode($response, true);
                            if (isset($result['name'])) {

                                $remisionUrl = "https://mdconecta.mdcloud.mx/Servidor/PHP/remision.php";
                                //$remisionUrl = 'http://localhost/MDConnecta/Servidor/PHP/remision.php';

                                $data = [
                                    'numFuncion' => 1,
                                    'pedidoId' => $pedidoId,
                                    'claveSae' => $claveSae,
                                    'noEmpresa' => $noEmpresa,
                                    'vendedor' => $vendedor
                                ];

                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $remisionUrl);
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                    'Content-Type: application/x-www-form-urlencoded'
                                ]);

                                $remisionResponse = curl_exec($ch);

                                if (curl_errno($ch)) {
                                    $error = error_get_last();
                                    $msg = sprintf(
                                        "[%s] ERROR: Error al crear la Comanda → %s\n",
                                        date('Y-m-d H:i:s'),
                                        json_encode($error, JSON_UNESCAPED_UNICODE)
                                    );
                                    error_log($msg, 3, $logFile);
                                    echo 'Error cURL: ' . curl_error($ch);
                                }

                                curl_close($ch);

                                //echo "Respuesta de remision.php: " . $remisionResponse;
                                $remisionData = json_decode($remisionResponse, true);
                                //echo "Respuesta de decodificada.php: " . $remisionData;
                                //$cveDoc = trim($remisionData['cveDoc']);

                                // Verificar si la respuesta es un PDF
                                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                                if (strpos($contentType, 'application/pdf') !== false) {
                                    // Guardar el PDF localmente o redireccionar
                                    file_put_contents("remision.pdf", $remisionResponse);
                                    echo "<script>window.open('remision.pdf', '_blank');</script>";
                                }
                                bitacora($clave, $firebaseProjectId, $firebaseApiKey, $pedidoId, "aceptado", $noEmpresa);
                                echo "<div class='container'>
                            <div class='title'>Confirmación Exitosa</div>
                            <div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>
                            <!--<a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>-->
                          </div>";
                            } else {
                                $error = error_get_last();
                                $msg = sprintf(
                                    "[%s] ERROR: Error al crear la remision → %s\n",
                                    date('Y-m-d H:i:s'),
                                    json_encode($error, JSON_UNESCAPED_UNICODE)
                                );
                                error_log($msg, 3, $logFile);
                                echo "<div class='container'>
                            <div class='title'>Error al Registrar</div>
                            <div class='message'>Hubo un problema al aceptar su pedido.</div>
                            <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                          </div>";
                            }
                        }
                    } else {
                        //Actualizar status para buscar pago
                        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS?key=$firebaseApiKey";

                        $response = @file_get_contents($url);
                        if ($response === false) {
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] ERROR: Error al obtener los datos de los pagos → %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            echo "Error al obtener los ...\n";
                            return;
                        }
                        $data = json_decode($response, true);
                        if (!isset($data['documents'])) {
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] ERROR: No se encontraron datos de pagos → %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            echo "No se encontraron ...\n";
                            return;
                        }

                        $error = error_get_last();
                        $msg = sprintf(
                            "[%s] Succes: Se encontro datos de pago → %s\n",
                            date('Y-m-d H:i:s'),
                            json_encode($error, JSON_UNESCAPED_UNICODE)
                        );
                        error_log($msg, 3, $logFile);

                        // Recorrer todas las comandas y verificar si el folio ya está en la base de datos
                        foreach ($data['documents'] as $document) {
                            $fields = $document['fields'];
                            if (isset($fields['folio']['stringValue']) && $fields['folio']['stringValue'] === $pedidoId) {
                                $pagoId = basename($document['name']);
                                $status = $fields['status'];
                                $buscar = $fields['buscar'];
                            }
                        }
                        $error = error_get_last();
                        $msg = sprintf(
                            "[%s] Succes: dato encontrado → %s\n",
                            date('Y-m-d H:i:s'),
                            json_encode($error, JSON_UNESCAPED_UNICODE)
                        );
                        error_log($msg, 3, $logFile);
                        if ($buscar['booleanValue']) {
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] Succes: Pedido $pedidoId aceptado y esperando el pago → %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            echo "<div class='container'>
                        <div class='title'>Pedido aceptado</div>
                        <div class='message'>El pedido fue aceptado y esperando el pago.</div>
                        <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                      </div>";
                        } else if ($status['stringValue'] === 'Pagada') {
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] Advertencia: El pago del pedido $pedidoId ya se confirmo → %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                            echo "<div class='container'>
                        <div class='title'>Pedido pagado</div>
                        <div class='message'>El pedido ya fue pagado.</div>
                        <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                      </div>";
                        } else {
                            $urlActualizacion = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS/$pagoId?updateMask.fieldPaths=buscar&key=$firebaseApiKey";
                            $data = [
                                'fields' => [
                                    'buscar' => ['booleanValue' => true]
                                ]
                            ];
                            $context = stream_context_create([
                                'http' => [
                                    'method' => 'PATCH',
                                    'header' => "Content-Type: application/json\r\n",
                                    'content' => json_encode($data)
                                ]
                            ]);

                            $response = @file_get_contents($urlActualizacion, false, $context);

                            if ($response === false) {
                                $error = error_get_last();
                                echo "<div class='container'>
                        <div class='title'>Error al actualizar el Pago</div>
                        <div class='message'>No se pudo actualizar la información.</div>
                        <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                      </div>";
                            } else {
                                bitacora($clave, $firebaseProjectId, $firebaseApiKey, $pedidoId, "anticipo", $noEmpresa);
                                echo "<div class='container'>
                            <div class='title'>Confirmación Exitosa</div>
                            <div class='message'>El pedido ha sido confirmado y tiene 72 horas para pagarlo.</div>
                            <!--<a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>-->
                          </div>";
                            }
                        }
                    }
                } else {
                    bitacora($clave, $firebaseProjectId, $firebaseApiKey, $pedidoId, "sin existencias", $noEmpresa);
                    if ($conCredito === 'S') {

                        $result = notificarSinExistencias($exsitencias, $firebaseProjectId, $firebaseApiKey, $vendedor, $pedidoId, $nombreCliente, $noEmpresa, $claveSae);
                        //var_dump($result);
                        $error = error_get_last();
                        $msg = sprintf(
                            "[%s] Advertencia: Pedido: $pedidoId sin existencias → %s\n",
                            date('Y-m-d H:i:s'),
                            json_encode($error, JSON_UNESCAPED_UNICODE)
                        );
                        error_log($msg, 3, $logFile);
                        //Hacer for de los pedidos sin existencias
                        echo "<div class='container'>
                            <div class='title'>Confirmación Exitosa</div>
                            <!--<div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>-->
                            <div class='message'>El pedido ha sido confirmado y registrado correctamente.</div>
                          </div>";
                    } else {
                        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS?key=$firebaseApiKey";

                        $response = @file_get_contents($url);
                        if ($response === false) {
                            echo "Error al obtener los ...\n";
                            return;
                        }
                        $data = json_decode($response, true);
                        if (!isset($data['documents'])) {
                            echo "No se encontraron ...\n";
                            return;
                        }

                        // Recorrer todas las comandas y verificar si el folio ya está en la base de datos
                        foreach ($data['documents'] as $document) {
                            $fields = $document['fields'];
                            if (isset($fields['folio']['stringValue']) && $fields['folio']['stringValue'] === $pedidoId) {
                                $pagoId = basename($document['name']);
                                $status = $fields['status'];
                                $buscar = $fields['buscar'];
                            }
                        }
                        if ($buscar['booleanValue']) {
                            echo "<div class='container'>
                                <div class='title'>Pedido aceptado</div>
                                <div class='message'>El pedido fue aceptado y esperando el pago.</div>
                                <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                            </div>";
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] Advertencia: Pedido: $pedidoId sin existencias → %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                        } else if ($status['stringValue'] === 'Pagada') {
                            echo "<div class='container'>
                                    <div class='title'>Pedido pagado</div>
                                    <div class='message'>El pedido ya fue pagado.</div>
                                    <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                                </div>";
                            $error = error_get_last();
                            $msg = sprintf(
                                "[%s] Advertencia: Pedido: $pedidoId sin existencias → %s\n",
                                date('Y-m-d H:i:s'),
                                json_encode($error, JSON_UNESCAPED_UNICODE)
                            );
                            error_log($msg, 3, $logFile);
                        } else {
                            $urlActualizacion = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PAGOS/$pagoId?updateMask.fieldPaths=buscar&key=$firebaseApiKey";
                            $data = [
                                'fields' => [
                                    'buscar' => ['booleanValue' => true]
                                ]
                            ];
                            $context = stream_context_create([
                                'http' => [
                                    'method' => 'PATCH',
                                    'header' => "Content-Type: application/json\r\n",
                                    'content' => json_encode($data)
                                ]
                            ]);

                            $response = @file_get_contents($urlActualizacion, false, $context);

                            if ($response === false) {
                                $error = error_get_last();
                                echo "<div class='container'>
                        <div class='title'>Error al actualizar el Pago</div>
                        <div class='message'>No se pudo actualizar la información.</div>
                        <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                      </div>";
                                $error = error_get_last();
                                $msg = sprintf(
                                    "[%s] ERROR: No se pudo actualizar la informacion de pago para el pedido: $pedidoId → %s\n",
                                    date('Y-m-d H:i:s'),
                                    json_encode($error, JSON_UNESCAPED_UNICODE)
                                );
                                error_log($msg, 3, $logFile);
                            } else {
                                bitacora($clave, $firebaseProjectId, $firebaseApiKey, $pedidoId, "anticipo", $noEmpresa);
                                echo "<div class='container'>
                                    <div class='title'>Confirmación Exitosa</div>
                                    <div class='message'>El pedido ha sido confirmado y tiene 72 horas para pagarlo.</div>
                                    <!--<a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>-->
                                </div>";
                                $error = error_get_last();
                                $msg = sprintf(
                                    "[%s] Advertencia: Pedido: $pedidoId sin existencias → %s\n",
                                    date('Y-m-d H:i:s'),
                                    json_encode($error, JSON_UNESCAPED_UNICODE)
                                );
                                error_log($msg, 3, $logFile);
                            }
                        }
                    }
                }
            } else {
                $error = error_get_last();
                $msg = sprintf(
                    "[%s] Advertencia: El cliente: $clave acepto un pedido rechazado → %s\n",
                    date('Y-m-d H:i:s'),
                    json_encode($error, JSON_UNESCAPED_UNICODE)
                );
                error_log($msg, 3, $logFile);
                //El pedido fue rechazado
                echo "<div class='container'>
                        <div class='title'>Pedido Rechazado</div>
                        <div class='message'>Estimado cliente, se le informa que este pedido $pedidoId fue rechazado.</div>
                        <!-- <a href='/Cliente/altaPedido.php' class='button'>Volver</a> -->
                      </div>";
            }
            //Termina validacion
        } elseif ($accion === 'rechazar') {
            $rechazo = buscarRechazo($firebaseProjectId, $firebaseApiKey, $pedidoId, $noEmpresa);
            if (!$rechazo) {
                $error = error_get_last();
                $msg = sprintf(
                    "[%s] Advertencia: Pedido: $pedidoId Rechazado → %s\n",
                    date('Y-m-d H:i:s'),
                    json_encode($error, JSON_UNESCAPED_UNICODE)
                );
                error_log($msg, 3, $logFile);
                $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
                // Consultar Firebase para obtener los datos del vendedor
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "Content-Type: application/json\r\n"
                    ]
                ]);

                $response = @file_get_contents($firebaseUrl, false, $context);

                $usuariosData = json_decode($response, true);
                $telefonoVendedor = null;
                $nombreVendedor = null;
                $vendedor = 1;
                $vendedor = formatearClaveVendedor($vendedor);
                //var_dump($clave);
                // Buscar al vendedor por clave
                if (isset($usuariosData['documents'])) {
                    foreach ($usuariosData['documents'] as $document) {
                        $fields = $document['fields'];
                        if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
                            if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $vendedor) {
                                if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                                    $telefonoVendedor = $fields['telefono']['stringValue'];
                                    $nombreVendedor = $fields['nombre']['stringValue'];
                                    break;
                                }
                            }
                        }
                    }
                }
                //$telefonoVendedor = '+527772127123'; // Interzenda
                //$telefonoVendedor = '+527773750925';
                //$telefonoVendedor = '+527773340218';
                //$telefonoVendedor = '+527775681612';
                if (!$telefonoVendedor) {
                    $error = error_get_last();
                    $msg = sprintf(
                        "[%s] ERROR: No se notifico al vendedor sobre el pedido $pedidoId → %s\n",
                        date('Y-m-d H:i:s'),
                        json_encode($error, JSON_UNESCAPED_UNICODE)
                    );
                    error_log($msg, 3, $logFile);
                    echo "<div class='container'>
                        <div class='title'>Error al Encontrar Vendedor</div>
                        <div class='message'>No se encontró el número de teléfono del vendedor.</div>
                        <!-- <a href='/Cliente/altaPedido.php' class='button'>Volver</a> -->
                      </div>";
                    exit;
                }
                // Enviar mensaje de WhatsApp
                $resultadoWhatsApp = enviarWhatsApp($telefonoVendedor, $pedidoId, $nombreCliente);
                guardarRechazo($firebaseProjectId, $firebaseApiKey, $noEmpresa, $pedidoId, $nombreCliente);
                if ($resultadoWhatsApp) {
                    $error = error_get_last();
                    $msg = sprintf(
                        "[%s] Advertencia: Se le notifico al vendedor sobre el pedido $pedidoId → %s\n",
                        date('Y-m-d H:i:s'),
                        json_encode($error, JSON_UNESCAPED_UNICODE)
                    );
                    error_log($msg, 3, $logFile);
                    echo "<div class='container'>
                        <div class='title'>Pedido Rechazado</div>
                        <div class='message'>El pedido $pedidoId fue rechazado correctamente y se notificó al vendedor.</div>
                        <!--<a href='/Cliente/altaPedido.php' class='button'>Regresar al inicio</a>-->
                      </div>";
                } else {
                    echo "<div class='container'>
                        <div class='title'>Error al Notificar</div>
                        <div class='message'>El pedido fue rechazado, pero no se pudo notificar al vendedor.</div>
                        <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                      </div>";
                }
            } else {
                $error = error_get_last();
                $msg = sprintf(
                    "[%s] Advertencia: El cliente: $clave rechazo un pedido previamente rechazado → %s\n",
                    date('Y-m-d H:i:s'),
                    json_encode($error, JSON_UNESCAPED_UNICODE)
                );
                error_log($msg, 3, $logFile);
                //El pedido fue rechazado
                echo "<div class='container'>
                        <div class='title'>Pedido Rechazado</div>
                        <div class='message'>Estimado cliente, se le informa que este pedido $pedidoId fue rechazado.</div>
                        <!-- <a href='/Cliente/altaPedido.php' class='button'>Volver</a> -->
                      </div>";
            }
        } else {
            echo "<div class='container'>
                    <div class='title'>Acción no válida</div>
                    <div class='message'>No se reconoció la acción solicitada.</div>
                    <!--<a href='/Cliente/altaPedido.php' class='button'>Volver</a>-->
                  </div>";
        }
    }
} else {
    echo "<div class='container'>
            <div class='title'>Solicitud Inválida</div>
            <div class='message'>No se enviaron los parámetros necesarios para continuar.</div>
            <!--<a href='/index.php' class='button'>Volver al inicio</a>-->
          </div>";
}

function enviarWhatsApp($numero, $pedidoId, $nombreCliente)
{
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';
    // Crear el cuerpo de la solicitud para la API

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $numero, // Número del vendedor
        "type" => "template",
        "template" => [
            "name" => "rechazar_pedido", // Nombre de la plantilla aprobada
            "language" => ["code" => "es_MX"], // Idioma de la plantilla
            "components" => [
                // Parámetros del cuerpo de la plantilla
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $nombreCliente], // {{1}}: Nombre del vendedor
                        ["type" => "text", "text" => $pedidoId]  // {{2}}: Número del pedido
                    ]
                ]
            ]
        ]
    ];

    // Convertir los datos a JSON
    $data_string = json_encode($data);

    // Configurar cURL para enviar la solicitud
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ]);

    // Ejecutar la solicitud y cerrar cURL
    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}
function verificarExistencias($pedidoId, $conexionData, $claveSae, $logFile){
    //Creamos la conexion
    $serverName = $conexionData['host'];
    $connectionInfo = [
        "Database" => $conexionData['nombreBase'],
        "UID" => $conexionData['usuario'],
        "PWD" => $conexionData['password'],
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    //Verificamos que la conexion sea exitosa
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar a la base de datos', 'errors' => sqlsrv_errors()]));
    }

    $nombreTabla = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $nombreTabla2 = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos', 'errors' => sqlsrv_errors()]));
    }

    // Inicializar listas de productos
    $productosSinExistencia = [];
    $productosConExistencia = [];

    $partidasData = obtenerProductos($pedidoId, $conexionData, $claveSae);

    foreach ($partidasData as $partida) {
        $CVE_ART = $partida['CVE_ART'];
        $cantidad = $partida['CANT'];
        //(COALESCE(M.[EXIST], 0) - COALESCE(I.[APART], 0)) AS DISPONIBLE ##QUITAR
        // Consultar existencias reales considerando apartados
        $sqlCheck = "SELECT 
                        COALESCE(M.[EXIST], 0) AS EXIST, 
                        COALESCE(I.[APART], 0) AS APART 
                     FROM $nombreTabla I
                     INNER JOIN $nombreTabla2 M ON M.[CVE_ART] = I.CVE_ART
                     WHERE I.[CVE_ART] = ? AND M.[CVE_ALM] = 1";
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$CVE_ART]);

        if ($stmtCheck === false) {
            sqlsrv_close($conn);
            die(json_encode(['success' => false, 'message' => 'Error al verificar existencias', 'errors' => sqlsrv_errors()]));
        }

        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $existencias = (float)$row['EXIST'];
            //$apartados = (float)$row['APART'] - $cantidad;
            if((float)$row['APART'] < 0){
                $apartados = 0;
            } else{
                $apartados = (float)$row['APART'] - $cantidad;
            }
            var_dump("apartados: ", $apartados);
            if($existencias < 0){
                $existencias = 0;
            }
            var_dump("existencias: ", $existencias);
            //$disponible = (float)$row['DISPONIBLE']; //$disponible = $existencias - ($apartados - $cantidad);
            $disponible = $existencias - $apartados;
            if($disponible < 0){
                $disponible = 0;
            }
            var_dump("disponible: ", $disponible);
            //$disponible = $existencias - abs($apartados);
            /*var_dump($existencias);
            var_dump($apartados);
            var_dump($disponible);*/
            if ($disponible >= $cantidad && $disponible != 0) {
                // opcional: también loguear los que sí tienen existencia
                $msg = sprintf(
                    "[%s] Info: Pedido $pedidoId %s verificadas existencias correctamente → %s\n",
                    date('Y-m-d H:i:s'),
                    $pedidoId,
                    json_encode($productosConExistencia, JSON_UNESCAPED_UNICODE)
                );
                error_log($msg, 3, $logFile);
                $productosConExistencia[] = [
                    'producto' => $CVE_ART,
                    'existencias' => $existencias,
                    'apartados' => $apartados,
                    'disponible' => $disponible
                ];
            } else {
                $msg = sprintf(
                    "[%s] Advertencia: Pedido $pedidoId %s sin existencias $CVE_ART → %s\n",
                    date('Y-m-d H:i:s'),
                    $pedidoId,
                    json_encode($productosSinExistencia, JSON_UNESCAPED_UNICODE)
                );
                // escribe en el log (flag 3 = append a fichero)
                error_log($msg, 3, $logFile);
                $productosSinExistencia[] = [
                    'producto' => $CVE_ART,
                    'existencias' => $existencias,
                    'apartados' => $apartados,
                    'disponible' => $disponible
                ];
            }
        } else {
            $productosSinExistencia[] = [
                'producto' => $CVE_ART,
                'existencias' => 0,
                'apartados' => 0,
                'disponible' => 0
            ];
        }
        sqlsrv_free_stmt($stmtCheck);
    }

    sqlsrv_close($conn);

    // Responder con el estado de las existencias
    if (!empty($productosSinExistencia)) {
        return [
            'success' => false,
            'exist' => true,
            'message' => 'No hay suficientes existencias para algunos productos',
            'productosSinExistencia' => $productosSinExistencia
        ];
    } else {
        return [
            'success' => true,
            'message' => 'Existencias verificadas correctamente',
            'productosConExistencia' => $productosConExistencia
        ];
    }
}
function verificarExistencia($firebaseProjectId, $firebaseApiKey, $pedidoId){
    //var_dump("Pedido: ", $pedidoId);
    // Endpoint de runQuery
    $url = sprintf(
        'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents:runQuery?key=%s',
        urlencode($firebaseProjectId),
        urlencode($firebaseApiKey)
    );

    // Estructura de la consulta
    $payload = [
        'structuredQuery' => [
            'from'  => [
                ['collectionId' => 'COMANDA']
            ],
            'where' => [
                'fieldFilter' => [
                    'field' => [
                        'fieldPath' => 'folio'   // aquí va el nombre del campo
                    ],
                    'op'    => 'EQUAL',
                    'value' => [
                        'stringValue' => $pedidoId // aquí sí va stringValue
                    ]
                ]
            ],
            'limit' => 1
        ]
    ];
    $jsonPayload = json_encode($payload);

    // Crear contexto HTTP para POST JSON
    $options = [
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n",
            'content'       => $jsonPayload,
            'ignore_errors' => true  // Para capturar respuestas 4xx/5xx
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    // Si la petición falla o no devuelve nada, asumimos que no existe
    if ($response === false || empty($response)) {
        return true;
    }

    // Opcional: verificar código HTTP
    /*if (
        !isset($http_response_header[0]) ||
        stripos($http_response_header[0], '200') === false
    ) {
         var_dump("JA");
        return false;
    }*/

    $data = json_decode($response, true);

    if (is_array($data)) {
        foreach ($data as $item) {
            if (isset($item['document']['name'])) {
                // Documento encontrado
                //var_dump($item['document']);
                return false;
            }
        }
    }

    // Si llega aquí, no encontró nada
    return true;
}
function buscarRechazo($firebaseProjectId, $firebaseApiKey, $pedidoId, $noEmpresa)
{
    $collection = "PEDIDOS_RECHAZO";
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents:runQuery?key=$firebaseApiKey";

    // Payload para hacer un where compuesto (idPedido y noEmpresa)
    $payload = json_encode([
        "structuredQuery" => [
            "from" => [
                ["collectionId" => $collection]
            ],
            "where" => [
                "compositeFilter" => [
                    "op" => "AND",
                    "filters" => [
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "pedido"],
                                "op" => "EQUAL",
                                "value" => ["stringValue" => $pedidoId]
                            ]
                        ],
                        [
                            "fieldFilter" => [
                                "field" => ["fieldPath" => "noEmpresa"],
                                "op" => "EQUAL",
                                "value" => ["integerValue" => (int)$noEmpresa]
                            ]
                        ]
                    ]
                ]
            ],
            "limit" => 1
        ]
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Inicializa la variable donde guardarás el id
    $pedidoRechazado = null;

    if ($response !== false) {
        $resultArray = json_decode($response, true);
        // runQuery devuelve un array con un elemento por cada match
        if (isset($resultArray[0]['document'])) {
            $doc    = $resultArray[0]['document'];
            // y para tomar tu campo direccion1Contacto:
            $fields = $doc['fields'];
            $pedidoRechazado = $fields['pedido']['stringValue'] ?? null;
        }
    }
    if ($pedidoRechazado == null) {
        return false;
    } else {
        return true;
    }
}
function bitacora($claveCliente, $firebaseProjectId, $firebaseApiKey, $pedidoId, $accion, $noEmpresa)
{
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México

    $fechaCreacion = date("Y-m-d H:i:s"); // Fecha y hora actual
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/BITACORA?key=$firebaseApiKey";
    $fields = [
        'fechaCreacion' => ['stringValue' => $fechaCreacion],
        'claveCliente'   => ['stringValue' => $claveCliente],
        'accion'   => ['stringValue' => $accion],
        'pedido'   => ['stringValue' => $pedidoId],
        'noEmpresa'   => ['integerValue' => $noEmpresa]
    ];

    $payload = json_encode(['fields' => $fields]);

    // Configurar las opciones HTTP para el POST
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ],
    ];
    $context = stream_context_create($options);

    // Ejecutar la petición a Firebase
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        die(json_encode(['success' => false, 'message' => 'Error al guardar el pedido autorizado en Firebase.', 'error' => $error]));
        //return;
    }
}
function notificarSinExistencias($exsitencias, $firebaseProjectId, $firebaseApiKey, $vendedor, $pedidoId, $nombreCliente, $noEmpresa, $claveSae)
{
    $firebaseUrl = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
    // Consultar Firebase para obtener los datos del vendedor
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($firebaseUrl, false, $context);

    $usuariosData = json_decode($response, true);
    $telefonoVendedor = null;
    $nombreVendedor = null;
    $vendedor = formatearClaveVendedor($vendedor);
    //var_dump($vendedor);
    // Buscar al vendedor por clave
    if (isset($usuariosData['documents'])) {
        foreach ($usuariosData['documents'] as $document) {
            $fields = $document['fields'];
            //if (isset($fields['tipoUsuario']['stringValue']) && $fields['tipoUsuario']['stringValue'] === "VENDEDOR") {
            if (isset($fields['claveUsuario']['stringValue']) && $fields['claveUsuario']['stringValue'] === $vendedor) {
                if (isset($fields['noEmpresa']['integerValue']) && $fields['noEmpresa']['integerValue'] === $noEmpresa && isset($fields['claveSae']['stringValue']) && $fields['claveSae']['stringValue'] === $claveSae) {
                    $telefonoVendedor = $fields['telefono']['stringValue'];
                    $nombreVendedor = $fields['nombre']['stringValue'];
                    break;
                }
            }
            //}
        }
    }

    //$telefonoVendedor = "+527773750925";
    $url = 'https://graph.facebook.com/v21.0/509608132246667/messages';
    $token = 'EAAQbK4YCPPcBOZBm8SFaqA0q04kQWsFtafZChL80itWhiwEIO47hUzXEo1Jw6xKRZBdkqpoyXrkQgZACZAXcxGlh2ZAUVLtciNwfvSdqqJ1Xfje6ZBQv08GfnrLfcKxXDGxZB8r8HSn5ZBZAGAsZBEvhg0yHZBNTJhOpDT67nqhrhxcwgPgaC2hxTUJSvgb5TiPAvIOupwZDZD';

    $productosStr = "";
    foreach ($exsitencias['productosSinExistencia'] as $esxist) {
        $producto = $esxist['producto'];
        $existencias = $esxist['existencias'];
        $disponible = $esxist['disponible'];
        $apartados = $esxist['apartados'];

        $productosStr .= " $producto - Existencias: $existencias - Apartados: $apartados - Disponible: $disponible, ";
    }

    $data = [
        "messaging_product" => "whatsapp", // 📌 Campo obligatorio
        "recipient_type" => "individual",
        "to" => $telefonoVendedor,
        "type" => "template",
        "template" => [
            "name" => "pedidos_sin_existencias", // 📌 Nombre EXACTO en Meta Business Manager
            "language" => ["code" => "es_MX"], // 📌 Corregido a español España
            "components" => [
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $nombreVendedor], // 📌 Confirmación del pedido
                        ["type" => "text", "text" => $pedidoId], // 📌 Lista de productos
                        ["type" => "text", "text" => $nombreCliente],
                        ["type" => "text", "text" => $productosStr]
                    ]
                ],
            ]
        ]
    ];
    //var_dump($data);
    // Convertir los datos a JSON
    $data_string = json_encode($data);

    // Configurar cURL para enviar la solicitud
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ]);

    // Ejecutar la solicitud y cerrar cURL
    $result = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    error_log("WhatsApp Response: " . $result);
    error_log("HTTP Status Code: " . $http_code);

    return $result;
}
function guardarRechazo($firebaseProjectId, $firebaseApiKey, $noEmpresa, $pedidoId, $nombreCliente)
{
    date_default_timezone_set('America/Mexico_City'); // Ajusta la zona horaria a México

    $fechaCreacion = date("Y-m-d H:i:s"); // Fecha y hora actual
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/PEDIDOS_RECHAZO?key=$firebaseApiKey";
    $fields = [
        'fechaCreacion' => ['stringValue' => $fechaCreacion],
        'nombreCliente'   => ['stringValue' => $nombreCliente],
        'pedido'   => ['stringValue' => $pedidoId],
        'noEmpresa'   => ['integerValue' => $noEmpresa]
    ];

    $payload = json_encode(['fields' => $fields]);

    // Configurar las opciones HTTP para el POST
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
        ],
    ];
    $context = stream_context_create($options);

    // Ejecutar la petición a Firebase
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        die(json_encode(['success' => false, 'message' => 'Error al guardar el pedido autorizado en Firebase.', 'error' => $error]));
        //return;
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pedido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #212529;
        }

        .container {
            text-align: center;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .message {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            margin-top: 10px;
            background-color: #007bff;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
</body>

</html>