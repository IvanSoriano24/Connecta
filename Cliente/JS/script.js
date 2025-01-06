document.addEventListener('DOMContentLoaded', function () {
    const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');
    const currentPath = window.location.pathname.split('/').pop();

    // Inicializar el estado activo al cargar la página
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

// Resto del código (sidebar toggle, search button, etc.) permanece igual...

// TOGGLE SIDEBAR
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar?.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
});

const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton?.addEventListener('click', function (e) {
    if (window.innerWidth < 576) {
        e.preventDefault();
        searchForm.classList.toggle('show');
        if (searchForm.classList.contains('show')) {
            searchButtonIcon.classList.replace('bx-search', 'bx-x');
        } else {
            searchButtonIcon.classList.replace('bx-x', 'bx-search');
        }
    }
});

if (window.innerWidth < 768) {
    sidebar.classList.add('hide');
} else if (window.innerWidth > 576) {
    searchButtonIcon?.classList.replace('bx-x', 'bx-search');
    searchForm?.classList.remove('show');
}

window.addEventListener('resize', function () {
    if (this.innerWidth > 576) {
        searchButtonIcon?.classList.replace('bx-x', 'bx-search');
        searchForm?.classList.remove('show');
    }
});

const switchMode = document.getElementById('switch-mode');

switchMode?.addEventListener('change', function () {
    if (this.checked) {
        document.body.classList.add('dark');
    } else {
        document.body.classList.remove('dark');
    }
});
