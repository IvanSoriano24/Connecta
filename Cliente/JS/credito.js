function obtenerCredito() {
    $.ajax({
      url: "../Servidor/PHP/creditos.php",
      type: "GET",
      data: { numFuncion: "1" },
      success: function (response) {
        try {
          const res = typeof response === "string" ? JSON.parse(response) : response;
  
          if (res.success) {
            console.log("Datos obtenidos: ", res);
            document.getElementById("credito").value = res.data.LIMCRED || 0;
            document.getElementById("saldo").value = res.data.SALDO || 0;
          } else {
            Swal.fire({ title: "Error", text: res.message, icon: "error" });
          }
        } catch (error) {
          console.error("Error al procesar la respuesta de clientes:", error);
        }
      },
      error: function () {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al obtener la lista de clientes.",
        });
      },
    });
  }

$(document).ready(function () {
  obtenerCredito();
});
