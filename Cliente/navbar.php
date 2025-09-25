<!-- navbar.php -->
<style>
    .navbar {
        height: 56px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 30px; /* Hacer el navbar más amplio */
        background-color: #f8f9fa;
        border-bottom: 1px solid #ddd;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .left-section {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        line-height: 1.5;
    }

    .left-section .empresa {
        font-size: 20px; /* Tamaño más grande para la empresa */
        font-weight: 700;
        color: #343a40;
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }

    .left-section .empresa i {
        margin-right: 10px;
        font-size: 22px; /* Icono un poco más grande */
        color: #007bff;
    }

    .left-section .usuario {
        font-size: 14px; /* Tamaño más pequeño para el usuario */
        font-weight: 500;
        color: #6c757d;
        display: flex;
        align-items: center;
    }

    .left-section .usuario i {
        margin-right: 8px;
        font-size: 16px;
        color: #007bff;
    }

    .right-section {
        display: flex;
        align-items: center;
    }

    .btn-secondary {
        display: flex;
        align-items: center;
        background-color: #49A1DF;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .btn-secondary i {
        margin-right: 8px;
        font-size: 18px;
    }

    .btn-secondary:hover {
        background-color: #3583bf;
    }

    .btn-usuario {
        display: flex;
        align-items: center;
        background-color: #3c91e6;
        color: white !important;
        border: none;
        border-radius: 20px;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .btn-usuario i {
        margin-right: 8px;
        font-size: 18px;
    }

    .btn-usuario:hover {
        background-color: #0d6efd;
    }


    @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .right-section {
            margin-top: 10px;
        }
    }
</style>


<nav class="navbar">
    <!--  <i class="bx bx-menu"></i> -->
    <div class="left-section">
        <h6 class="empresa">
            <i class=""></i> 
            <span><?php echo !empty($empresa) ? $empresa : "Nombre de la empresa"; ?></span>
        </h6>
        <h6 class="usuario">
            <i class=""></i> 
            <span><?php echo !empty($nombreUsuario) ? $nombreUsuario : "Tipo de Usuario"; ?></span>
        </h6>
    </div>
    <div class="right-section">
        <a href="Usuarios.php" class="btn btn-usuario" tabindex="-1">
            <i class='bx bxs-user'></i> Usuario
        </a>
    </div>
</nav>

<script src="JS/alerts.js"></script>
