
function cargarPedidos() {
    $.get('../Servidor/PHP/pedido.php', { numFuncion: '4' }, function (response) {
      if (response.success && response.data) {
        const pedidos = response.data;
        const pedidosTable = document.getElementById('datosPedidos');
        pedidosTable.innerHTML = ''; // Limpia la tabla antes de agregar nuevos datos

        // Recorrer los pedidos y agregarlos a la tabla
        pedidos.forEach(pedido => {
          const row = document.createElement('tr');
        
          // Asegúrate de que las propiedades existan en cada objeto
          row.innerHTML = `
            <td>${pedido.id || 'N/A'}</td>
            <td>${pedido.cliente || 'Sin cliente'}</td>
            <td>${pedido.total || '0'}</td>
            <td>${pedido.fecha || 'Sin fecha'}</td>
            <td>
              <button class="btnEditarPedido" name="btnEditarPedido" data-id="${pedido.id}">Editar</button>
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

  function cargarDatosPedido(idPedido) {
      $.get('../Servidor/PHP/pedido.php', { 
        action: 'sesion', 
        id: id,  
        noEmpresa: noEmpresa,
        razonSocial: razonSocial
      }, function (response) { 
      if (response.success && response.data) {
        console.log(response.success);  // Verificar los datos que se obtienen
        const pedido = response.data;
        // Rellenar los campos del formulario con los datos del pedido
        $('#idPedido').val(pedido.id);
        $('#cliente').val(pedido.cliente);
        $('#total').val(pedido.total);
        $('#fecha').val(pedido.fecha);
  
        // Mostrar el formulario
        $('#formularioEditarPedido').show();
      } else {
        console.error('Error al cargar los datos del pedido.');
      }
    }, 'json');
}


  // Función para agregar eventos a los botones "Editar" y "Cancelar"
  function agregarEventosBotones() {    
    // Evento para editar pedido
    $('.btnEditarPedido').on('click', function () {
      const idPedido = $(this).data('id');
      cargarDatosPedido(idPedido);
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

    $('#editarPedidoForm').on('submit', function (event) {
      event.preventDefault(); // Evitar la recarga de la página
      // Obtener los valores del formulario
      const idPedido = $('#idPedido').val();
      const cliente = $('#cliente').val();
      const total = $('#total').val();
      const fecha = $('#fecha').val();
      // Crear un objeto con los datos actualizados
      const datosActualizados = {
        cliente: cliente,
        total: total,
        fecha: fecha
      };
      // Realizar la solicitud para actualizar el pedido
      $.ajax({
        url: '../Servidor/PHP/pedido.php',
        method: 'POST',
        data: {
          numFuncion: '5', // Código para la actualización
          id: idPedido,
          datos: datosActualizados
        },
        success: function (response) {
          if (response.success) {
            alert('Pedido actualizado con éxito');
            cargarPedidos(); // Recargar la lista de pedidos
            $('#formularioEditarPedido').hide(); // Ocultar el formulario
          } else {
            alert('Error al actualizar el pedido');
          }
        }
      });
    });
    
  });