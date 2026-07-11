const API_URL = "http://localhost:3001/api";

let clientes = [];

async function api(path, options) {
  const res = await fetch(`${API_URL}${path}`, {
    headers: { "Content-Type": "application/json" },
    ...options,
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error || "Error de red");
  }
  return res.status === 204 ? null : res.json();
}

async function cargarClientes() {
  clientes = await api("/clientes");
  render();
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
      const claseSaldo = c.saldo > 0 ? "saldo-positivo" : "saldo-cero";
      return `
        <tr>
          <td>${c.nombre}</td>
          <td>${c.telefono || "-"}</td>
          <td>$${c.totalCargos.toFixed(2)}</td>
          <td>$${c.totalPagos.toFixed(2)}</td>
          <td class="${claseSaldo}">$${c.saldo.toFixed(2)}</td>
          <td><button class="btn-danger" data-id="${c.id}">Eliminar</button></td>
        </tr>
      `;
    })
    .join("");

  tbody.querySelectorAll(".btn-danger").forEach((btn) => {
    btn.addEventListener("click", async () => {
      await api(`/clientes/${btn.dataset.id}`, { method: "DELETE" });
      await cargarClientes();
    });
  });
}

function render() {
  renderSelects();
  renderTabla();
}

document.getElementById("form-cliente").addEventListener("submit", async (e) => {
  e.preventDefault();
  const nombre = document.getElementById("cliente-nombre").value.trim();
  const telefono = document.getElementById("cliente-telefono").value.trim();
  if (!nombre) return;

  try {
    await api("/clientes", { method: "POST", body: JSON.stringify({ nombre, telefono }) });
    e.target.reset();
    await cargarClientes();
  } catch (err) {
    alert(err.message);
  }
});

document.getElementById("form-deuda").addEventListener("submit", async (e) => {
  e.preventDefault();
  const clienteId = document.getElementById("deuda-cliente").value;
  const concepto = document.getElementById("deuda-concepto").value.trim();
  const monto = document.getElementById("deuda-monto").value;
  if (!clienteId || !concepto || !monto) return;

  try {
    await api(`/clientes/${clienteId}/cargos`, {
      method: "POST",
      body: JSON.stringify({ concepto, monto }),
    });
    e.target.reset();
    await cargarClientes();
  } catch (err) {
    alert(err.message);
  }
});

document.getElementById("form-pago").addEventListener("submit", async (e) => {
  e.preventDefault();
  const clienteId = document.getElementById("pago-cliente").value;
  const monto = document.getElementById("pago-monto").value;
  if (!clienteId || !monto) return;

  try {
    await api(`/clientes/${clienteId}/pagos`, {
      method: "POST",
      body: JSON.stringify({ monto }),
    });
    e.target.reset();
    await cargarClientes();
  } catch (err) {
    alert(err.message);
  }
});

cargarClientes().catch((err) => {
  alert("No se pudo conectar con el servidor de cobranza. ¿Está corriendo `npm start` en cobranza/server?");
  console.error(err);
});
