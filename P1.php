
<div id="divContenedor" style="display: none;">
    <div id="menuPrincipal">
        <div id="divLogo">
        <img src="../SRC/imagen.png" alt="Logo-MDConeccta" id="logo">
        </div>
        <button id="btnMenu">Clientes</button>
        <button id="btnMenu">Productos</button>
        <button id="btnMenu">Pedidos</button>
        <?php if($tipoUsuario == 2) : ?><button id="btnMenu">Cambiar Empresa</button><?php endif; ?>
        <div id="divLogo">
            <img src="/SRC/imagen.png" alt="Logo-MDConeccta" id="logo">
        </div>
    </div>
    <div id="Contenido">
        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Sequi aliquid ipsam laborum ullam nostrum sunt expedita blanditiis veniam modi, tempora at autem atque odio tempore veritatis earum, similique molestiae et!</p>
        <!---->
    </div>
</div>



<!-- Modal donde se seleccionara la empresa -->
    <div class="w3-modal" id="modalEmpresas" style="display: block;">
        <div class="w3-modal-content w3-card-4 w3-padding-16 w3-animate-zoom w3-light-gray" style="width:40%;border-radius:20px;">
            <div class="w3-bar" style="display: flex; justify-content: flex-start; "> <!-- justify-content: flex-start; -->
                <h2 style="padding-left:3%;">Escoge la Empresa</h2>
            </div>
            <div class=" w3-panel w3-padding-16">
                <div id="divEmpresas" class="w3-col s12">
                    <div id="botonEmpresa" style="width: 90%; height:70%; background:white; margin:auto; margin-left:15px;opacity: 0.9; border-radius:15px;">
                        <img src="" width="100px" height="100px" style="margin:px">
                        <input type="button" value="Empresa" onclick="cerrarModal()">
                    </div>
                </div>
            </div>
        </div>
    </div>




       <!-- slider section 
    <section class=" slider_section position-relative">
      <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
          <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
          <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
          <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
          <li data-target="#carouselExampleIndicators" data-slide-to="3"></li>
        </ol>
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="container-fluid">
              <div class="row">
                <div class="col-md-4 offset-md-2">
                  <div class="slider_detail-box">
                    <h1>
                      Professional
                      <span>
                        Care Your Pet
                      </span>
                    </h1>
                    <p>
                      Lorem Ipsum is simply dummy text of the printing and
                      typesetting industry.
                      Lorem Ipsum has been the industry's standard dummy text ever
                    </p>
                    <div class="btn-box">
                      <a href="" class="btn-1">
                        Buy now
                      </a>
                      <a href="" class="btn-2">
                        Contact
                      </a>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="slider_img-box">
                    <img src="images/slider-img.png" alt="">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="container-fluid">
              <div class="row">
                <div class="col-md-4 offset-md-2">
                  <div class="slider_detail-box">
                    <h1>
                      Professional
                      <span>
                        Care Your Pet
                      </span>
                    </h1>
                    <p>
                      Lorem Ipsum is simply dummy text of the printing and
                      typesetting industry.
                      Lorem Ipsum has been the industry's standard dummy text ever
                    </p>
                    <div class="btn-box">
                      <a href="" class="btn-1">
                        Buy now
                      </a>
                      <a href="" class="btn-2">
                        Contact
                      </a>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="slider_img-box">
                    <img src="images/slider-img.png" alt="">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="container-fluid">
              <div class="row">
                <div class="col-md-4 offset-md-2">
                  <div class="slider_detail-box">
                    <h1>
                      Professional
                      <span>
                        Care Your Pet
                      </span>
                    </h1>
                    <p>
                      Lorem Ipsum is simply dummy text of the printing and
                      typesetting industry.
                      Lorem Ipsum has been the industry's standard dummy text ever
                    </p>
                    <div class="btn-box">
                      <a href="" class="btn-1">
                        Buy now
                      </a>
                      <a href="" class="btn-2">
                        Contact
                      </a>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="slider_img-box">
                    <img src="images/slider-img.png" alt="">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="container-fluid">
              <div class="row">
                <div class="col-md-4 offset-md-2">
                  <div class="slider_detail-box">
                    <h1>
                      Professional
                      <span>
                        Care Your Pet
                      </span>
                    </h1>
                    <p>
                      Lorem Ipsum is simply dummy text of the printing and
                      typesetting industry.
                      Lorem Ipsum has been the industry's standard dummy text ever
                    </p>
                    <div class="btn-box">
                      <a href="" class="btn-1">
                        Buy now
                      </a>
                      <a href="" class="btn-2">
                        Contact
                      </a>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="slider_img-box">
                    <img src="images/slider-img.png" alt="">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </section>
     end slider section -->