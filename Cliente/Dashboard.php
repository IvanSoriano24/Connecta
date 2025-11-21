<?php
require '../Servidor/PHP/firebase.php';
session_start();

// Verificar si existe la bandera de conexión pendiente
if (isset($_SESSION['pendiente_conexion_sae']) && $_SESSION['pendiente_conexion_sae'] === true) {
    // Si tiene la bandera, cerrar sesión y redirigir al index
    require '../Servidor/PHP/conexion.php';
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['usuario'])) {
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["usuario"];
    $nombre = $_SESSION['usuario']["nombre"];
    $usuario = $_SESSION['usuario']["usuario"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $correo = $_SESSION['usuario']["correo"];
    if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
        header('Location:Dashboard.php');
    }
    $mostrarModal = isset($_SESSION['empresa']) ? false : true;
    //$empresa = $_SESSION['empresa']['razonSocial'];
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? null;
        $contrasena = $_SESSION['empresa']['contrasena'] ?? null;
        $claveSae = $_SESSION['empresa']['claveSae'] ?? null;
        
        // Verificar si la empresa tiene conexión SAE
        if ($claveSae !== null && $noEmpresa !== null) {
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Content-Type: application/json\r\n"
                ]
            ]);
            $result = file_get_contents($url, false, $context);
            
            if ($result !== FALSE) {
                $documents = json_decode($result, true);
                if (isset($documents['documents'])) {
                    $tieneConexion = false;
                    // Buscar si existe un documento con el mismo `noEmpresa` y `claveSae`
                    foreach ($documents['documents'] as $document) {
                        $fields = $document['fields'];
                        if (isset($fields['claveSae']) && $fields['claveSae']['stringValue'] === $claveSae && 
                            isset($fields['noEmpresa']) && $fields['noEmpresa']['integerValue'] === $noEmpresa) {
                            $tieneConexion = true;
                            break;
                        }
                    }
                    
                    // Si no tiene conexión y el usuario es administrador, establecer bandera y redirigir a crearConexionSae.php
                    if (!$tieneConexion && ($tipoUsuario == 'ADMINISTRADOR' || $tipoUsuario == 'ADMIISTRADOR')) {
                        $_SESSION['pendiente_conexion_sae'] = true;
                        header('Location:crearConexionSae.php');
                        exit();
                    }
                }
            }
        }
    }

    // Creación de un CSRF Token
    $csrf_token = bin2hex(random_bytes(32));
    // Resguardo del CSRF Token en una sesión
    $_SESSION['csrf_token'] = $csrf_token;
} else {
    header('Location:../index.php');
} ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootsstrap  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <script src="JS/sideBar.js?n=1"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <!-- Titulo y Logo -->
    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body.modal-open .hero_area {
            filter: blur(5px);
            /* Difumina el fondo mientras un modal está abierto */
        }

        h1 {
            font-size: 3rem;
            font-weight: bold;
            color: #3e386c;
            margin-bottom: 2rem;
        }

        h2 {
            margin-top: .5rem;
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #4e6799;
        }
    </style>
</head>

<body>
<div class="hero_area">
    <?php include 'sidebar.php'; ?>
    <!-- CONTENT -->
    <section id="content">
        <?php include 'navbar.php'; ?>
        <!-- MAIN -->
        <main class="text-center my-5 hero_area">
            <!--<form method="POST" action="../Servidor/PHP/whatsappp.php">
        <button type="submit">Realizar Pedido</button>
        </form>-->


            <div class="row align-items-center m-5">
                <div class="col ms-5">
                    <h2>Bienvenido</h2>
                    <h1><span><?= $tipoUsuario ?></span></h1>
                    <img src="SRC/logomd.png" alt="Logo" style="max-width: 20%; height: auto;">
                </div>
            </div>

            <!-- Estadísticas de ventas del día -->
            <div class="row m-5" id="estadisticasVentas">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-subtitle mb-2 text-white-50">Ventas Activas</h6>
                                    <h3 class="card-title mb-0" id="totalActivas">0</h3>
                                    <p class="mb-0 mt-2" style="font-size: 0.9rem;">Total de pedidos activos</p>
                                </div>
                                <div class="display-4 opacity-50">
                                    <i class='bx bx-cart'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-subtitle mb-2 text-white-50">Ventas Vendidas</h6>
                                    <h3 class="card-title mb-0" id="totalVendidas">0</h3>
                                    <p class="mb-0 mt-2" style="font-size: 0.9rem;">Total de pedidos vendidos</p>
                                </div>
                                <div class="display-4 opacity-50">
                                    <i class='bx bx-check-circle'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-subtitle mb-2 text-white-50">Dinero Vendido</h6>
                                    <h3 class="card-title mb-0" id="totalDinero">$0.00</h3>
                                    <p class="mb-0 mt-2" style="font-size: 0.9rem;">Total vendido acumulado</p>
                                </div>
                                <div class="display-4 opacity-50">
                                    <i class='bx bx-dollar'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <?php
            /*echo $contrasena;
            if($contrasena === ""){
                echo "Es vacio";
            }else{
                echo "No es vacio";
            }*/

            ?>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
</div>

<!-- Modal para la seleccion de empresa -->
<div class="modal fade" id="empresaModal" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false" class="modal <?php echo $mostrarModal ? '' : 'd-none'; ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="text-center mb-0">
                <h1 class="display-4 text-primary txth1 mb-0">Bienvenido</h1>
                <h2 class="card-title text-center txth2"><?php echo $nombreUsuario; ?></h2>
            </div>
            <div class="modal-body">
                <select class="form-select" id="empresaSelect" name="empresaSelect">
                    <option value="" selected disabled class="txt">Selecciona una empresa</option>
                </select>

            </div>
            <!-- Boton para confirmar y cerrar sesion (ver JS/menu.js) para su funcionamiento -->
            <div class="modal-footer d-flex justify-content-between">
                <!-- Boton para cerrar sesion -->
                <button type="button" class="btn btn-danger" id="cerrarSesionModal">Cerrar Sesion</button>
                <!-- Boton para confirmar empresa -->
                <button type="button" class="btn btn-secondary" id="confirmarEmpresa">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<!-- JS Para el funcionamiento del sistemo -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    //Funcion para cargar las empresas del usuario
    document.getElementById('empresaModal').addEventListener('shown.bs.modal', function () {
        var usuario = '<?php echo $nombreUsuario; ?>';
        cargarEmpresa(usuario);
    });
    //Funcion para verificar si ya se selecciono una empresa
    document.addEventListener('DOMContentLoaded', function () {
        const empresaSeleccionada = <?php echo json_encode(isset($_SESSION['empresa']) ? $_SESSION['empresa'] : null); ?>;
        if (empresaSeleccionada === null) {
            const empresaModal = new bootstrap.Modal(document.getElementById('empresaModal'));
            empresaModal.show();
        }
    });
    //Funcion para seleccionar una empresa
    document.getElementById('confirmarEmpresa').addEventListener('click', function () {
        const empresaSeleccionada = document.getElementById('empresaSelect').value;
        if (!empresaSeleccionada) {
            //Mensaje cuando no seleccionas una empresa
            Swal.fire({
                title: 'Error',
                text: 'Por favor, selecciona una empresa.',
                icon: 'error'
            });
            return;
        }
        const empresaOption = document.querySelector(`#empresaSelect option[value="${empresaSeleccionada}"]`);


        if (!empresaOption) {
            //Mensaje cuando no cargan los datos de una empresa
            Swal.fire({
                title: 'Error',
                text: 'No se pudo obtener la información de la empresa.',
                icon: 'error'
            });
            return;
        }
        //Obtener los datos de la empresa
        const noEmpresa = empresaOption.getAttribute('data-no-empresa');
        const razonSocial = empresaOption.getAttribute('data-razon-social');
        const claveUsuario = empresaOption.getAttribute('data-clave-vendedor');
        const claveSae = empresaOption.getAttribute('data-clave-sae');
        const contrasena = empresaOption.getAttribute('data-contrasena');
        // Verificar en PHP si la empresa tiene conexión a SAE
        fetch('../Servidor/PHP/sae.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'verificar',
                claveSae: claveSae,
                noEmpresa: noEmpresa
            })
        })
            .then(response => response.json())
            .then(response => {
                if (response.success && response.tieneConexion) {
                    //Si tiene conexion a SAE
                    const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
                    $("#empresaSelect").prop("disabled", true);
                    seleccionarEmpresa(noEmpresa);
                    //const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));

                    modal.hide();
                    // Guardar los datos en la variable global
                    idEmpresarial = {
                        id: empresaSeleccionada,
                        noEmpresa: noEmpresa,
                        razonSocial: razonSocial,
                        claveUsuario: claveUsuario,
                        claveSae: claveSae,
                        contrasena: contrasena
                    };

                    // Llamar la función sesionEmpresa para registrar sesión
                    sesionEmpresa(idEmpresarial);

                    /*
                    Swal.fire({
                        title: 'Has seleccionado:',
                        text: `${noEmpresa} - ${razonSocial}`,
                        icon: 'success'
                    }).then(() => {
                        seleccionarEmpresa(noEmpresa);
                        //const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
                        modal.hide();
                        // Guardar los datos en la variable global
                        idEmpresarial = {
                            id: empresaSeleccionada,
                            noEmpresa: noEmpresa,
                            razonSocial: razonSocial,
                            claveUsuario: claveUsuario,
                            claveSae: claveSae,
                            contrasena: contrasena
                        };

                        // Llamar la función sesionEmpresa para registrar sesión
                        sesionEmpresa(idEmpresarial);

                    }); */

                } else {
                    //No tiene conexion a SAE
                    Swal.fire({
                        title: 'Error',
                        text: 'La empresa seleccionada no tiene conexión a SAE.',
                        icon: 'error'
                    }).then(() => {
                        //Obtener el tipo de usuario
                        var tipoUsuario = '<?php echo $tipoUsuario ?>';
                        //Si el usuario es administrador, redirigir a la creacion de conexion
                        if (tipoUsuario == 'ADMINISTRADOR') {
                            seleccionarEmpresa(noEmpresa);
                            const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
                            modal.hide();

                            // Guardar los datos en la variable global
                            idEmpresarial = {
                                id: empresaSeleccionada,
                                noEmpresa: noEmpresa,
                                razonSocial: razonSocial,
                                claveUsuario: claveUsuario,
                                claveSae: claveSae,
                                contrasena: contrasena
                            };

                            // Llamar la función sesionEmpresa para registrar sesión
                            sesionNoEmpresa(idEmpresarial);

                            // Redirigir a la página de creación de conexión después de que el usuario cierre la alerta
                            //window.location.href = "crearConexionSae.php";
                            window.location.href = "../Cliente/crearConexionSae.php";
                            
                            //window.location.reload();
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error al procesar la solicitud:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo verificar la conexión a SAE.',
                    icon: 'error'
                });
            });
    });
</script>
<script>
    //Funcion para guardar los datos de empresa en la sesion
    function sesionNoEmpresa(idEmpresarial) {
        var id = idEmpresarial.id;
        var noEmpresa = idEmpresarial.noEmpresa;
        var razonSocial = idEmpresarial.razonSocial;
        var claveUsuario = idEmpresarial.claveUsuario;
        var claveSae = idEmpresarial.claveSae;
        var contrasena = idEmpresarial.contrasena;
        $.post('../Servidor/PHP/empresas.php', {
            action: 'sesion',
            id: id,
            noEmpresa: noEmpresa,
            razonSocial: razonSocial,
            claveUsuario: claveUsuario,
            claveSae: claveSae,
            contrasena: contrasena
        }, function (response) {
            if (response.success) {
                if (response.data && response.data.id && response.data.noEmpresa && response.data.razonSocial) {
                    console.log(response.data);
                    // alert(response.data);
                } else {
                    alert(response.message || 'Error al guardar la sesión de empresa.');
                }
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.log("Error en la solicitud: " + textStatus + ", " + errorThrown);
            alert('Error al comunicar con el servidor.');
        });
    }
</script>
<script src="JS/menu.js?n=1"></script>
<script src="JS/app.js?n=1"></script>
<script src="JS/script.js?n=1"></script>

<script>
    // Cargar estadísticas de ventas del día
    function cargarEstadisticasVentas() {
        fetch('../Servidor/PHP/estadisticasVentas.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalActivas').textContent = data.data.activas.totalPedidos;
                    document.getElementById('totalVendidas').textContent = data.data.vendidas.totalPedidos;
                    
                    // Formatear el dinero con separadores de miles
                    const totalDinero = parseFloat(data.data.totalDineroVendido);
                    document.getElementById('totalDinero').textContent = '$' + totalDinero.toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    console.error('Error al cargar estadísticas:', data.message);
                }
            })
            .catch(error => {
                console.error('Error al cargar estadísticas:', error);
            });
    }

    // Cargar estadísticas al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        cargarEstadisticasVentas();
        
        // Actualizar cada 5 minutos
        setInterval(cargarEstadisticasVentas, 300000);
    });
</script>
</body>

</html>