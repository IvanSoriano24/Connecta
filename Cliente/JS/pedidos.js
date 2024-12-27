
function cargarPedidos() {
    $.get('../Servidor/PHP/pedido.php', { numFuncion: '4' }, function (response) {
        console.log("dentro");
      if (response.success && response.data) {
        console.log('Respuesta del servidor:', response);
        const pedidos = response.data;
        const pedidosTable = document.getElementById('datosPedidos');
        pedidosTable.innerHTML = ''; // Limpia la tabla antes de agregar nuevos datos

        // Recorrer los pedidos y agregarlos a la tabla
        pedidos.forEach(pedido => {
          const row = document.createElement('tr');

          row.innerHTML = `
            <td>${pedido.id}</td>
            <td>${pedido.cliente}</td>
            <td>${pedido.total}</td>
            <td>${pedido.fecha}</td>
            <td>
              <button class="btnEditarPedido" data-id="${pedido.id}">Editar</button>
              <button class="btnCancelarPedido" data-id="${pedido.id}">Cancelar</button>
            </td>
          `;

          pedidosTable.appendChild(row);
        });

        // Agregar eventos a los botones dinámicos
        agregarEventosBotones();
      } else {
        //alert(response.message || 'Error al obtener los pedidos.');
        console.error('Error en la solicitud:', textStatus, errorThrown);
        console.log('Detalles:', jqXHR.responseText); 
      }
    }, 'json');
  }

  // Función para agregar eventos a los botones "Editar" y "Cancelar"
  function agregarEventosBotones() {
    // Evento para editar pedido
    $('.btnEditarPedido').on('click', function () {
      const idPedido = $(this).data('id');
      alert(`Editar Pedido ID: ${idPedido}`);
      
    });

    // Evento para cancelar pedido
    $('.btnCancelarPedido').on('click', function () {
      const idPedido = $(this).data('id');
      const confirmacion = confirm('¿Estás seguro de que quieres cancelar este pedido?');
      if (confirmacion) {
        alert(`Cancelar Pedido ID: ${idPedido}`);
        
      }
    });
  }

 $(document).ready(function () {
    cargarPedidos();

    // Evento para crear pedido
    $('#btnCrearPedido').on('click', function () {
      alert('Abrir formulario para crear pedido.');
      // Lógica para abrir el modal o formulario...
    });
  });