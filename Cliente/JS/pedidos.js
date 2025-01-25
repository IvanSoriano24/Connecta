
function cargarPedidos() {
  alert("o");
    $.get('../Servidor/PHP/pedidos.php', { numFuncion: '4' }, function (response) {
      if (response.success && response.data) {
        const pedidos = response.data;
        const pedidosTable = document.getElementById('datosPedidos');
        pedidosTable.innerHTML = ''; // Limpia la tabla antes de agregar nuevos datos

        // Recorrer los pedidos y agregarlos a la tabla
        pedidos.forEach(pedido => {
          const row = document.createElement('tr');
        
          // Asegúrate de que las propiedades existan en cada objeto <td>${pedido.id || 'N/A'}</td>
          row.innerHTML = `
            
            <td>${pedido.pedido || 'Sin pedidos'}</td>
            <td>${pedido.cliente || 'Sin cliente'}</td>
            <td>${pedido.total || '0'}</td>
            <td>${pedido.fecha || 'Sin fecha'}</td>
            <td>${pedido.estado || 'Sin estado'}</td>
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
        numFuncion: '5',
        idPedido: idPedido
    }, function(response) { 
        if (response.success && response.data) {
            const pedido = response.data;
            // Rellenar los campos del formulario con los datos del pedido
            $('#idPedido').val(pedido.id);
            $('#pedidos').val(pedido.pedido.stringValue);  // Accede al valor real
            $('#cliente').val(pedido.cliente.stringValue);  // Accede al valor real
            $('#total').val(pedido.total.stringValue);  // Accede al valor real
            $('#fecha').val(pedido.fecha.stringValue); 

            // Mostrar el formulario
            $('#formularioEditarPedido').show();
        } else {
            console.error('Error al cargar los datos del pedido:', response.message);
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Error en la solicitud:', textStatus, errorThrown);
        console.error('Respuesta del servidor:', jqXHR.responseText); // Verifica la respuesta del servidor
    });
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
          $.post('../Servidor/PHP/pedido.php', {
              numFuncion: '3',  // La función 3 es para cancelar el pedido
              idPedido: idPedido
          }, function (response) {
              if (response.success) {
                location.reload();
                alert('Pedido cancelado con éxito');
              } else {
                  alert('Error al cancelar el pedido: ' + response.message);
                  console.log('Error al cancelar el pedido: ' + response.message);
              }
          }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
              console.error('Error en la solicitud:', textStatus, errorThrown);
              console.error('Respuesta del servidor:', jqXHR.responseText); // Verifica la respuesta del servidor
              console.log('Error al cancelar el pedido. Verifica la consola para más detalles.');
              alert('Error al cancelar el pedido. Verifica la consola para más detalles.');
          });
      }
  });  
  }


 $(document).ready(function () {
    cargarPedidos();
    // Evento para crear pedido
    $('#btnCrearPedido').on('click', function () {
      altaPedido();
      alert('Abrir formulario para crear pedido.');
      // Lógica para abrir el modal o formulario...
    });

    $('#guardarFactura').on('click', function () {
      altaPedido();
    });

    $('#editarPedidoForm').on('submit', function (event) {
      event.preventDefault(); // Evitar la recarga de la página
      // Obtener los valores del formulario
      const idPedido = $('#idPedido').val();
      const pedido = $('#pedidos').val();
      const cliente = $('#cliente').val();
      const total = $('#total').val();
      const fecha = $('#fecha').val();
      // Crear un objeto con los datos actualizados
      const datosActualizados = {
        cliente: cliente,
        pedido: pedido,
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
    $('#cerrarFormulario').on('click', function () {
      $('#formularioEditarPedido').hide();
    });
  });