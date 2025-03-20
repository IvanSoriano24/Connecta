function cargarArticulosConImagenes() {
  const numFuncion = 20; // Identificador de la función en PHP
  const xhr = new XMLHttpRequest();
  xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion, true);
  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        // Validar si la respuesta es válida y contiene artículos
        if (response.success && Array.isArray(response.productos)) {
          mostrarArticulos(response.productos);
        } else {
          console.error(
            "Error en la respuesta del servidor:",
            response.message || "Estructura inesperada"
          );
          alert("Error: " + (response.message || "Datos no encontrados."));
        }
      } catch (error) {
        console.error("Error al analizar JSON:", error);
        alert("Error al analizar JSON: " + error.message);
      }
    } else {
      console.error("Error HTTP:", xhr.status);
      alert("Error HTTP: " + xhr.status);
    }
  };
  xhr.onerror = function () {
    alert("Hubo un problema con la conexión al servidor.");
  };
  xhr.send();
}
function mostrarArticulos(articulos) {
  const productList = document.getElementById("product-list");
  productList.innerHTML = ""; // Limpia el contenedor antes de cargar los productos

  articulos.forEach((articulo) => {
    const card = document.createElement("div");
    card.classList.add("col-lg-4", "col-md-6", "mb-4");

    // Crear el carrusel de imágenes
    let imagenesHtml = "";
    if (Array.isArray(articulo.IMAGEN_ML) && articulo.IMAGEN_ML.length > 0) {
      imagenesHtml = `
          <div id="carrusel-${
            articulo.CVE_ART
          }" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
              ${articulo.IMAGEN_ML.map(
                (imagenUrl, index) => `
                <div class="carousel-item ${index === 0 ? "active" : ""}">
                  <img src="${imagenUrl}" class="d-block w-100 img-fluid" alt="Imagen ${
                  index + 1
                }" style="max-height: 300px; object-fit: cover;">
                  <div class="carousel-caption d-flex justify-content-center">
                    <button class="btn btn-danger btn-sm custom-delete-btn" onclick="eliminarImagen('${
                      articulo.CVE_ART
                    }', '${imagenUrl}')">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </div>
              `
              ).join("")}
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${
              articulo.CVE_ART
            }" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${
              articulo.CVE_ART
            }" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Siguiente</span>
            </button>
          </div>
        `;
    } else {
      imagenesHtml = `<p>No hay imágenes disponibles para este producto.</p>`;
    }
    // Crear la tarjeta del producto
    card.innerHTML = `
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">${articulo.DESCR}</h5>
            <div class="imagenes-container mb-3">
              ${imagenesHtml}
            </div>
            <form class="upload-form mt-3" data-cve-art="${articulo.CVE_ART}">
              <input type="file" name="imagen" accept="image/*" class="form-control mb-2" multiple required>
              <button type="submit" class="btn btn-primary btn-sm">Subir Imágenes</button>
            </form>
          </div>
        </div>
      `;
    productList.appendChild(card);
  });
  agregarEventosSubida(); // Agregar eventos para subir imágenes
}
function agregarEventosSubida() {
  const forms = document.querySelectorAll(".upload-form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault(); // Evita que el formulario se envíe de forma tradicional
      const cveArt = this.getAttribute("data-cve-art");
      const fileInput = this.querySelector("input[name='imagen']");
      const files = fileInput.files;

      // Validar el límite de 6 imágenes
      const totalImagenes = document.querySelectorAll(
        `.imagenes-container img`
      ).length;
      if (files.length > 6) {
        alert("No puedes subir más de 6 imágenes para este producto.");
        return;
      }
      if (files.length > 0) {
        subirImagenes(cveArt, files);
      } else {
        alert("Por favor, selecciona al menos una imagen.");
      }
    });
  });
}
function eliminarImagen(cveArt, imageUrl) {
  // Confirmación con SweetAlert2
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción eliminará permanentemente la imagen seleccionada.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      // Crear el cuerpo de la solicitud en formato x-www-form-urlencoded
      const formData = new URLSearchParams();
      formData.append("numFuncion", 13); // Identificador del case
      formData.append("cveArt", cveArt); // Clave del artículo
      formData.append("imageUrl", imageUrl); // URL de la imagen a eliminar

      // Realizar la solicitud al backend para eliminar la imagen
      fetch("../Servidor/PHP/ventas.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: formData.toString(),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Mostrar alerta de éxito
            Swal.fire({
              title: "¡Eliminada!",
              text: "La imagen se eliminó correctamente.",
              icon: "success",
              confirmButtonColor: "#3085d6",
            }).then(() => {
              actualizarCarrusel(cveArt); // Recargar solo el carrusel del producto
            });
          } else {
            // Mostrar alerta de error
            Swal.fire({
              title: "Error",
              text: `No se pudo eliminar la imagen: ${data.message}`,
              icon: "error",
              confirmButtonColor: "#d33",
            });
            console.log(data.response);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          // Mostrar alerta de error general
          Swal.fire({
            title: "Error",
            text: "Ocurrió un error al intentar eliminar la imagen.",
            icon: "error",
            confirmButtonColor: "#d33",
          });
        });
    }
  });
}
// Nueva función para actualizar el carrusel
function actualizarCarrusel(cveArt) {
  fetch(`../Servidor/PHP/ventas.php?numFuncion=15&cveArt=${cveArt}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const carruselContainer = document.querySelector(`#carrusel-${cveArt}`);
        if (carruselContainer) {
          let carruselHtml = `
              <div class="carousel-inner">
                ${data.producto.IMAGENES.map(
                  (imagenUrl, index) => `
                  <div class="carousel-item ${index === 0 ? "active" : ""}">
                    <img src="${imagenUrl}" class="d-block w-100 img-fluid" alt="Imagen ${
                    index + 1
                  }" style="max-height: 300px; object-fit: cover;">
                    <div class="carousel-caption d-flex justify-content-center">
                      <button class="btn btn-danger btn-sm custom-delete-btn" onclick="eliminarImagen('${cveArt}', '${imagenUrl}')">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </div>
                  </div>
                `
                ).join("")}
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${cveArt}" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${cveArt}" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
              </button>
            `;
          carruselContainer.innerHTML = carruselHtml;
          // Reiniciar el carrusel (si usas Bootstrap)
          new bootstrap.Carousel(carruselContainer, { interval: false });
        }
      } else {
        console.error(
          "Error al cargar las imágenes del producto:",
          data.message
        );
      }
    })
    .catch((error) => {
      console.error("Error al cargar el carrusel:", error);
    });
}
function subirImagenes(cveArt, files) {
  const formData = new FormData();
  formData.append("numFuncion", 14);
  formData.append("cveArt", cveArt);

  // Añadir múltiples imágenes
  Array.from(files).forEach((file) => {
    formData.append("imagen[]", file);
  });
  fetch("../Servidor/PHP/ventas.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        Swal.fire({
          title: "Subida",
          text: "Las imagenes se subieron correctamente.",
          icon: "success",
          confirmButtonColor: "#3085d6",
        }).then(() => {
          actualizarCarrusel(cveArt); // Recargar solo el carrusel del producto
        });
      } else {
        alert("Error al subir las imágenes: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Ocurrió un error al subir las imágenes.");
    });
}
// Llama a la función al cargar la página
document.addEventListener("DOMContentLoaded", cargarArticulosConImagenes);