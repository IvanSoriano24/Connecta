<!-- sidebar.php -->
<section id="layout">
</section>
<section id="sidebar">
    <a href="#" class="brand">
        <i class='bx bxs-cloud'></i>
        <span class="text">MDCloud</span>
    </a>
    <ul class="side-menu top">
        <li class="active">
            <a href="Dashboard.php">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Inicio</span>
            </a>
        </li>
        <li>
            <a href="Ventas.php">
                <i class='bx bxs-shopping-bag-alt'></i>
                <span class="text">Ventas</span>
            </a>
        </li>
        <li>
            <a href="Productos.php">
                <i class='bx bxs-package'></i>
                <span class="text">Productos</span>
            </a>
        </li>
        <li>
            <a href="Clientes.php">
                <i class='bx bxs-user'></i>
                <span class="text">Mis Clientes</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class='bx bxs-message-dots'></i>
                <span class="text">Mensajes</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class='bx bxs-file'></i>
                <span class="text">Reportes</span>
            </a>
        </li>
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
<!-- NAVBAR -->
<section id="navbar" style="background-color: #f8f9fa; padding: 10px;">
    <div style="display: flex; justify-content: flex-end; align-items: center;">
        <button class="btn btn-secondary" style="background-color: #49A1DF; color: white;">
            <i class='bx bxs-user'></i>
            <a class="brand" href="Usuarios.php" style="color: white;">Usuarios</a>
        </button>
    </div>
</section>
</section>