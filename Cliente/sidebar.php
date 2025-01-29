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
<section id="sidebar">


    <ul class="side-menu top">
        <a href="#" class="brand">
            <img src="SRC/logomd.png" alt="" style="width: 170px; height: auto;">
        </a>
        <br>
        <li class="active">
            <a href="Dashboard.php">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Inicio</span>
            </a>
        </li>
        <?php if ($tipoUsuario == "ADMINISTRADOR" || $tipoUsuario == "VENDEDOR") { ?>
            <li>
                <a href="Ventas.php">
                    <i class='bx bxs-shopping-bag-alt'></i>
                    <span class="text">Ventas</span>
                </a>
            </li>
        <?php } ?>

        <li>
            <a href="Productos.php">
                <i class='bx bxs-package'></i>
                <span class="text">Productos</span>
            </a>
        </li>
        <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
            <li>
                <a href="Clientes.php">
                    <i class='bx bxs-user'></i>
                    <span class="text">Mis Clientes</span>
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="Mensajes.php">
                <i class='bx bxs-message-dots'></i>
                <span class="text">Mensajes</span>
                <span id="mensajesNotificacion" class="badge bg-danger text-white d-none" style="font-size: 0.8rem; margin-left: 10px;">0</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class='bx bxs-file'></i>
                <span class="text">Reportes</span>
            </a>
        </li>

        <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
            <li>
            <a href="Menu.php">
                <i class='bx bxs-store'></i>
                <span class="text">E-commerce</span>
            </a>
        </li>
        <?php } ?>
       
    </ul>
    <ul class="side-menu">
        <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
            <li>
                <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class='bx bxs-cog'></i>
                    <span class="text">Configuración</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="infoEmpresa.php" id="informaEmpresa">Información Empresa</a></li>
                    <li><a class="dropdown-item" href="ConexioSAE.php" id="infoSae">Conexión SAE</a></li>
                </ul>
            </li>
        <?php } ?>
        <li>
            <a href="" class="logout" id="cerrarSesion">
                <i class='bx bxs-log-out-circle'></i>
                <span class="text">Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</section>
</section>