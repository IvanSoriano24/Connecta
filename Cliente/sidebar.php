<style>
    .brand {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .small-image {
        width: 100%;
        height: auto;
    }

</style>
<!-- sidebar.php -->
<section id="layout">
</section>
<section id="sidebar" tabindex="-1">


    <ul class="side-menu top">

        <br>
        <a href="Dashboard.php" class="brand" tabindex="-1">
            <!--<img src="SRC/logomd.png" alt="" style="width: 170px; height: auto;" id="logonav">-->
            <img src="SRC/imagen.png" alt="" style="width: 170px; height: auto;" id="logonav">
        </a>
        <br>
        <li class="active">
            <a href="Dashboard.php" tabindex="-1">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Inicio</span>
            </a>
        </li>
        <?php if ($tipoUsuario == "ADMINISTRADOR" || $tipoUsuario == "VENDEDOR") { ?>
            <li class="dropdown-manual">
                <a href="#" class="dropdown-toggle-manual" tabindex="-1">
                    <i class='bx bxs-shopping-bag-alt'></i>
                    <span class="text">Ventas</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="Ventas.php" tabindex="-1">Pedidos</a></li>
                    <li><a class="dropdown-item" href="Remisiones.php" tabindex="-1">Remisiones</a></li>
                    <li><a class="dropdown-item" href="Facturas.php" tabindex="-1">Facturas</a></li>
                </ul>
            </li>
        <?php } ?>

        <li>
            <a href="Productos.php" tabindex="-1">
                <i class='bx bxs-package'></i>
                <span class="text">Productos</span>
            </a>
        </li>
        <?php if ($tipoUsuario == "ADMINISTRADOR" || $tipoUsuario == "VENDEDOR") { ?>
            <li>
                <a href="Clientes.php" tabindex="-1">
                    <i class='bx bxs-user'></i>
                    <span class="text">Mis Clientes</span>
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="Mensajes.php" tabindex="-1">
                <i class='bx bxs-message-dots'></i>
                <span class="text">Mensajes</span>
                <span id="mensajesNotificacion" class="badge bg-danger text-white d-none" style="font-size: 0.8rem; margin-left: 10px;">0</span>
            </a>
        </li>
        <li>
            <a href="Reportes.php" tabindex="-1"> <!--  Dashboard -->
                <i class='bx bxs-file'></i>
                <span class="text">Reportes</span>
            </a>
        </li>
    </ul>
    <ul class="side-menu">
        <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
            <li>
                <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" tabindex="-1">
                    <i class='bx bxs-cog'></i>
                    <span class="text">Configuración</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="infoEmpresa.php" id="informaEmpresa" tabindex="-1">Información Empresa</a></li>
                    <li><a class="dropdown-item" href="ConexioSAE.php" id="infoSae" tabindex="-1">Conexión SAE</a></li>
                    <li><a class="dropdown-item" href="Correo.php" id="infoCorreo" tabindex="-1">Configuracion de Correo</a></li>
                </ul>
            </li>
            
            <li>
                <a href="Menu.php" class="ecommers-button" tabindex="-1">
                    <i class='bx bxs-store'></i>
                    <span class="text">E-Commers</span>
                    <span id="eCommers" class="badge bg-danger text-white d-none" style="font-size: 0.8rem; margin-left: 10px;">0</span>
                </a>
            </li>
            
        <?php } ?>
        <li>
            <a href="" class="logout" id="cerrarSesion" tabindex="-1">
                <i class='bx bxs-log-out-circle'></i>
                <span class="text">Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</section>
</section>