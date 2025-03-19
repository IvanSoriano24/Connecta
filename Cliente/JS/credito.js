function obtenerCredito() {
  $.ajax({
    url: "../Servidor/PHP/creditos.php",
    type: "GET",
    data: { numFuncion: "1" },
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;
          if (res.success) {
            // Formatea el crédito y el saldo con comas, dos decimales y añade el signo de pesos
            const creditoFormateado = `$${parseFloat(res.data.LIMCRED || 0)
              .toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const saldoFormateado = `$${parseFloat(res.data.SALDO || 0)
              .toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
          
            document.getElementById("credito").value = creditoFormateado;
            document.getElementById("saldo").value = saldoFormateado;
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
