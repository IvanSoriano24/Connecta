document.addEventListener('DOMContentLoaded', function () {
    const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');
    const currentPath = window.location.pathname.split('/').pop();

    //Inicializar el estado activo al cargar la página
    allSideMenu.forEach(item => {
        const li = item.parentElement;
        const href = item.getAttribute('href');

        if (href === currentPath) {
            li.classList.add('active');
        } else {
            li.classList.remove('active');
        }
    });

    // Agregar eventos de clic para cambiar la clase 'active'
    allSideMenu.forEach(item => {
        const li = item.parentElement;

        item.addEventListener('click', function (e) {
            // Evitar navegación no deseada si el enlace es "#"
            if (!item.getAttribute('href') || item.getAttribute('href') === '#') {
                e.preventDefault();
            }

            // Eliminar la clase 'active' de todos los elementos
            allSideMenu.forEach(i => i.parentElement.classList.remove('active'));

            // Agregar la clase 'active' al elemento actual
            li.classList.add('active');
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const menuBar = document.querySelector('#content nav .bx.bx-menu');
    const sidebar = document.getElementById('sidebar');

    // Función para mostrar/ocultar el botón de menú según el tamaño de la pantalla
    function toggleMenuButton() {
        if (window.innerWidth > 768) {
            menuBar.style.display = 'none'; // Oculta el botón en escritorio
            sidebar.classList.remove('show'); // Asegura que el sidebar esté siempre visible en escritorio
        } else {
            menuBar.style.display = 'block'; // Muestra el botón en móviles
        }
    }

    // Llamar a la función al cargar la página
    toggleMenuButton();

    // Llamar a la función cuando se cambia el tamaño de la ventana
    window.addEventListener('resize', toggleMenuButton);

    // Evento para alternar la visibilidad del sidebar
    menuBar?.addEventListener('click', function () {
        sidebar.classList.toggle('show');
    });
});

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".dropdown-toggle-manual").forEach(toggle => {
        const menu = toggle.nextElementSibling;

        toggle.addEventListener("click", (e) => {
            e.preventDefault();
            menu.classList.toggle("show");
        });

        // Cierra el menú si se hace clic fuera
        document.addEventListener("click", (e) => {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove("show");
            }
        });
    });
});



// const searchButton = document.querySelector('#content nav form .form-input button');
// const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
// const searchForm = document.querySelector('#content nav form');

// searchButton?.addEventListener('click', function (e) {
//     if (window.innerWidth < 576) {
//         e.preventDefault();
//         searchForm.classList.toggle('show');
//         if (searchForm.classList.contains('show')) {
//             searchButtonIcon.classList.replace('bx-search', 'bx-x');
//         } else {
//             searchButtonIcon.classList.replace('bx-x', 'bx-search');
//         }
//     }
// });

// if (window.innerWidth < 768) {
//     sidebar.classList.add('hide');
// } else if (window.innerWidth > 576) {
//     searchButtonIcon?.classList.replace('bx-x', 'bx-search');
//     searchForm?.classList.remove('show');
// }

// window.addEventListener('resize', function () {
//     if (this.innerWidth > 576) {
//         searchButtonIcon?.classList.replace('bx-x', 'bx-search');
//         searchForm?.classList.remove('show');
//     }
// });

// const switchMode = document.getElementById('switch-mode');

// switchMode?.addEventListener('change', function () {
//     if (this.checked) {
//         document.body.classList.add('dark');
//     } else {
//         document.body.classList.remove('dark');
//     }
// });
