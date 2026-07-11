const STORAGE_KEY = "cobranza-clientes";

function cargarClientes() {
  return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
}

function guardarClientes(clientes) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(clientes));
}

let clientes = cargarClientes();

function saldo(cliente) {
  const totalCargos = cliente.cargos.reduce((s, c) => s + c.monto, 0);
  const totalPagos = cliente.pagos.reduce((s, p) => s + p.monto, 0);
  return totalCargos - totalPagos;
}

function renderSelects() {
  const selects = [
    document.getElementById("deuda-cliente"),
    document.getElementById("pago-cliente"),
  ];
  selects.forEach((select) => {
    select.innerHTML = clientes
      .map((c) => `<option value="${c.id}">${c.nombre}</option>`)
      .join("");
  });
}

function renderTabla() {
  const tbody = document.querySelector("#tabla-clientes tbody");
  tbody.innerHTML = clientes
    .map((c) => {
      const totalCargos = c.cargos.reduce((s, x) => s + x.monto, 0);
      const totalPagos = c.pagos.reduce((s, x) => s + x.monto, 0);
      const saldoActual = totalCargos - totalPagos;
      const claseSaldo = saldoActual > 0 ? "saldo-positivo" : "saldo-cero";
      return `
        <tr>
          <td>${c.nombre}</td>
          <td>${c.telefono || "-"}</td>
          <td>$${totalCargos.toFixed(2)}</td>
          <td>$${totalPagos.toFixed(2)}</td>
          <td class="${claseSaldo}">$${saldoActual.toFixed(2)}</td>
          <td><button class="btn-danger" data-id="${c.id}">Eliminar</button></td>
        </tr>
      `;
    })
    .join("");

  tbody.querySelectorAll(".btn-danger").forEach((btn) => {
    btn.addEventListener("click", () => {
      clientes = clientes.filter((c) => c.id !== btn.dataset.id);
      guardarClientes(clientes);
      render();
    });
  });
}

function render() {
  renderSelects();
  renderTabla();
}

document.getElementById("form-cliente").addEventListener("submit", (e) => {
  e.preventDefault();
  const nombre = document.getElementById("cliente-nombre").value.trim();
  const telefono = document.getElementById("cliente-telefono").value.trim();
  if (!nombre) return;

  clientes.push({
    id: crypto.randomUUID(),
    nombre,
    telefono,
    cargos: [],
    pagos: [],
  });
  guardarClientes(clientes);
  e.target.reset();
  render();
});

document.getElementById("form-deuda").addEventListener("submit", (e) => {
  e.preventDefault();
  const clienteId = document.getElementById("deuda-cliente").value;
  const concepto = document.getElementById("deuda-concepto").value.trim();
  const monto = parseFloat(document.getElementById("deuda-monto").value);
  const cliente = clientes.find((c) => c.id === clienteId);
  if (!cliente || !concepto || !(monto > 0)) return;

  cliente.cargos.push({ concepto, monto, fecha: new Date().toISOString() });
  guardarClientes(clientes);
  e.target.reset();
  render();
});

document.getElementById("form-pago").addEventListener("submit", (e) => {
  e.preventDefault();
  const clienteId = document.getElementById("pago-cliente").value;
  const monto = parseFloat(document.getElementById("pago-monto").value);
  const cliente = clientes.find((c) => c.id === clienteId);
  if (!cliente || !(monto > 0)) return;

  cliente.pagos.push({ monto, fecha: new Date().toISOString() });
  guardarClientes(clientes);
  e.target.reset();
  render();
});

render();
