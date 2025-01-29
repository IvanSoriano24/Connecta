
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.html" class="logo d-flex align-items-center me-auto me-xl-0">
        <!-- <img src="assets/img/logo.png" alt=""> -->
        <img src="SRC/imagen.png" alt="Logo" id="logop" href="Menu.php" style="width: 100px; height: 180px;">
        <!-- <h1 class="sitename">Interzenda</h1> -->
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="Menu.php" >Inicio</a></li>
          <li><a href="#">Crédito</a></li>
          <li><a href="Articulos.php">Productos</a></li>
          <li><a href="Pedidos.php">Pedidos</a></li>
          <li><a href="#">Guias</a></li>
          <li><a href="Carrito.php">Carrito</a></li>
          <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
          <li><a href="imagenes.php">Imagenes</a></li>
          <li><a href="Dashboard.php">MDConnecta</a></li>
          <?php } ?>
         
          <!-- <li class="dropdown"><a href="#"><span>Dropdown</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="#">Dropdown 1</a></li>
              <li class="dropdown"><a href="#"><span>Deep Dropdown</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                <ul>
                  <li><a href="#">Deep Dropdown 1</a></li>
                  <li><a href="#">Deep Dropdown 2</a></li>
                  <li><a href="#">Deep Dropdown 3</a></li>
                  <li><a href="#">Deep Dropdown 4</a></li>
                  <li><a href="#">Deep Dropdown 5</a></li>
                </ul>
              </li>
              <li><a href="#">Dropdown 2</a></li>
              <li><a href="#">Dropdown 3</a></li>
              <li><a href="#">Dropdown 4</a></li>
            </ul>
          </li> -->
          
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <a href="" class="btn-getstarted" id="cerrarSesion" name="cerrarSesion">
      <i class='bx bxs-log-out-circle'></i>
      <span class="text">Cerrar Sesión</span>
      </a>
      
    </div>
  </header>

 