<!-- navbar.php -->
<style>
    .navbar {
        display: flex;
        justify-content: space-between;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;

    }

    .left-section {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .right-section {
        margin-left: auto;
    }
</style>

<nav class="navbar">
    <div class="left-section">
        <h6 class="card-title text-center " style="margin-top: 2px;">
            <i class="bx bxs-building"></i> <?php echo $empresa ?>
        </h6>
        <h6 class="card-title " style="margin-top: 2px;">
            <i class=""></i> <?php echo $tipoUsuario; ?>
        </h6>
    </div>
    <div class="">
        <a href="Usuarios.php" class="btn btn-secondary"
            style="background-color: #49A1DF; text-align: right; color: white; height: 40px;">
            <i class='bx bxs-user'></i>
            Usuario</a>
    </div>
</nav>