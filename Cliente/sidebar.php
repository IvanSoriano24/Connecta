<style>
    .brand {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 230px;
        height: 100%;
        transition: width 0.3s ease;
        overflow: visible;
        z-index: 1000;
    }

    #sidebar ol,
    #sidebar ul {
        padding-left: 0;
    }


    #sidebar.collapsed {
        width: 65px;
    }

    /* Ocultar textos cuando esté colapsado */
    #sidebar.collapsed .text {
        display: none;
    }

    /* Asegurar que los iconos queden centrados */
    #sidebar ul li a {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #sidebar.collapsed ul li a {
        justify-content: center;
        gap: 0;
    }

    /* Botón hamburguesa */
    .hamburger {
        position: fixed;
        top: 10px;
        left: 20px;
        background: transparent;
        border: none;
        font-size: 24px;
        color: #212529;
        cursor: pointer;
        z-index: 1010;
    }

    #sidebar.collapsed~#mainContent {
        margin-left: 60px;
    }

    #sidebar.collapsed #logonav {
        display: none;
    }

    #sidebar.collapsed .dropdown-toggle::after {
        display: none !important;
    }

    #sidebar.collapsed .dropdown-toggle {
        padding-right: 0 !important;
    }

    /* Logo grande visible por defecto */
    #logoGrande {
        width: 170px;
        height: auto;
        display: block;
    }

    /* Logo chico oculto por defecto */
    #logoChico {
        width: 40px;
        height: auto;
        display: none;
    }

    /* Cuando sidebar está colapsado → mostrar chico, ocultar grande */
    #sidebar.collapsed #logoGrande {
        display: none;
    }

    #sidebar.collapsed #logoChico {
        display: block;
    }


    /* Deja cada li como ancla del menú (sirve para todos) */
    .side-menu>li {
        position: relative;
        z-index: 1001;
    }

    /* SOLO Ventas (manual) a la derecha, no toques su HTML */
    .dropdown-manual > .dropdown-menu {
        position: absolute;
        top: -90px;   /* lo subes 50px */
        left: 100%;
        margin-left: 8px;
        min-width: 180px;
    }


    /* Bootstrap ya posiciona .dropend a la derecha; solo ajusta espacio */
    .dropend .dropdown-menu {
        margin-left: 8px;
    }

    /* En colapsado, caret y padding extra fuera para alinear iconos */
    #sidebar.collapsed .dropdown-toggle::after {
        display: none !important;
    }

    #sidebar.collapsed .dropdown-toggle {
        padding-right: 0 !important;
    }

    #sidebar.collapsed {
        width: 65px;
    }

    #sidebar.collapsed .text {
        display: none;
    }

    #sidebar.collapsed #logoGrande {
        display: none;
    }

    #sidebar.collapsed #logoChico {
        display: block;
    }
</style>

<!-- sidebar.php -->
<section id="layout">
</section>

<button id="toggleSidebar" class="hamburger">
    <i class="bx bx-menu"></i>
</button>

<section id="sidebar" tabindex="-1">


    <ul class="side-menu top">

        <a href="Dashboard.php" class="brand" tabindex="-1">
            <img src="SRC/imagen.png" alt="Logo grande" id="logoGrande">
            <img src="SRC/imagen-small.png" alt="Logo pequeño" id="logoChico">
        </a>
        <li class="active">
            <a href="Dashboard.php" title="Dashboard" tabindex="-1">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Inicio</span>
            </a>
        </li>

        <?php if ($tipoUsuario == "ADMINISTRADOR" || $tipoUsuario == "VENDEDOR" || $tipoUsuario == "FACTURISTA" || $tipoUsuario == "ALMACENISTA") { ?>
            <li class="dropdown-manual">
                <a href="#" class="dropdown-toggle dropdown-toggle-manual" title="Ventas" tabindex="-1">
                    <i class='bx bxs-shopping-bag-alt'></i>
                    <span class="text">Ventas</span>
                </a>
                <ul class="dropdown-menu">
                    <?php if ($tipoUsuario != "ALMACENISTA") { ?>
                        <li><a class="dropdown-item" href="Ventas.php" tabindex="-1">Pedidos</a></li>
                    <?php } ?>
                    <li><a class="dropdown-item" href="Remisiones.php" tabindex="-1">Remisiones</a></li>
                    <?php if ($tipoUsuario != "ALMACENISTA") { ?>
                        <li><a class="dropdown-item" href="Facturas.php" tabindex="-1">Facturas</a></li>
                    <?php } ?>
                </ul>
            </li>
        <?php } ?>

        <!--<li>
            <a href="Productos.php" title="Productos" tabindex="-1">
                <i class='bx bxs-package'></i>
                <span class="text">Productos</span>
            </a>
        </li> -->
        <li class="dropdown-manual">
            <!--<a href="Productos.php" tabindex="-1">
                <i class='bx bxs-package'></i>
                <span class="text">Productos</span>
            </a>-->
            <a href="#" class="dropdown-toggle dropdown-toggle-manual" tabindex="-1">
                <i class='bx bxs-package'></i>
                <span class="text">Inventario</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="Productos.php" tabindex="-1">Productos</a></li>
                <?php if ($tipoUsuario == "ALMACENISTA" || $tipoUsuario == "ADMINISTRADOR" || $tipoUsuario == "SUPER-ALMACENISTA") { ?>
                    <li><a class="dropdown-item" href="inventarioFisico.php" tabindex="-1">Inventario Fisico</a></li>
                <?php } ?>
            </ul>

        </li>
        <?php if ($tipoUsuario == "ADMINISTRADOR" || $tipoUsuario == "VENDEDOR") { ?>

            <li class="dropdown-manual">
                <a href="#" class="dropdown-toggle dropdown-toggle-manual" title="Clientes" tabindex="-1">
                    <i class='bx bxs-user'></i>
                    <span class="text">Clientes</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="Clientes.php">Mis Clientes</a></li>
                    <li><a class="dropdown-item" href="DatosEnvio.php">Datos de Envío</a></li>
                </ul>
            </li>


        <?php } ?>
        <li>
            <a href="mensajes.php" title="Mensajes" tabindex="-1">
                <i class='bx bxs-message-dots'></i>
                <span class="text">Mensajes</span>
                <span id="mensajesNotificacion" class="badge bg-danger text-white d-none" style="font-size: 0.8rem; margin-left: 10px;">0</span>
            </a>
        </li>
        <?php if ($tipoUsuario == "ADMINISTRADOR" || $tipoUsuario == "VENDEDOR") { ?>
            <li>
                <a href="Reportes.php" title="Reportes" tabindex="-1"> <!--  Dashboard -->
                    <i class='bx bxs-file'></i>
                    <span class="text">Reportes</span>
                </a>
            </li>
        <?php } ?>

        <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
            <li class="dropdown-manual">
                <a href="#" class="dropdown-toggle dropdown-toggle-manual" title="Configuración" tabindex="-1">
                    <i class='bx bxs-cog'></i>
                    <span class="text">Configuración</span>
                </a>
                <ul class="dropdown-menu">
                    <!--    -->
                    <li><a class="dropdown-item" href="Parametros.php" id="parametrosSistema" tabindex="-1">Parametros del Sistema</a></li>
                    
                    <li><a class="dropdown-item" href="infoEmpresa.php" id="informaEmpresa" tabindex="-1">Información Empresa</a></li>
                    <li><a class="dropdown-item" href="ConexioSAE.php" id="infoSae" tabindex="-1">Conexión SAE</a></li>
                    <li><a class="dropdown-item" href="Correo.php" id="infoCorreo" tabindex="-1">Configuracion de Correo</a></li>
                </ul>
            </li>
            <!--
            <li>
                <a href="Menu.php" class="ecommers-button" tabindex="-1">
                    <i class='bx bxs-store'></i>
                    <span class="text">E-Commers</span>
                    <span id="eCommers" class="badge bg-danger text-white d-none" style="font-size: 0.8rem; margin-left: 10px;">0</span>
                </a>
            </li>
            -->
        <?php } ?>
        <li>
            <a href="" class="logout" id="cerrarSesion" title="Cerrar Sesión" tabindex="-1">
                <i class='bx bxs-log-out-circle'></i>
                <span class="text">Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</section>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const toggleBtn = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            // Guardar el estado
            localStorage.setItem("sidebar-collapsed", sidebar.classList.contains("collapsed"));
        });
    });
</script>