<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<h1>En creacion</h1>
<table id="tablaPedidos" style="display: none;">
  <thead>
  <tr>
      <th colspan="5">
        <input type="button" id="btnCrearPedido" value="Crear Pedido">
      </th>
    </tr>
    <tr>
      <th>ID Pedido</th>
      <th>Cliente</th>
      <th>Total</th>
      <th>Fecha</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody id="datosPedidos">
    <!-- Pedidos cargados dinámicamente -->
  </tbody>
</table>

<!--
<div id="modalPedidos">
<form id="formAltaPedido">


  <button type="button" id="btnGuardarPedido">Guardar Pedido</button>
</form>
</div>
-->
<script src="JS/pedidos.js"></script>
</body>
</html>