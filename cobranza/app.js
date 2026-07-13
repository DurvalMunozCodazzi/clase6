const API_URL = "http://localhost:3001/api";

let clientes = [];
const expandidos = new Set();

function movimientos(cliente) {
  const cargos = cliente.cargos.map((c) => ({ ...c, tipo: "cargo" }));
  const pagos = cliente.pagos.map((p) => ({ ...p, tipo: "pago" }));
  return [...cargos, ...pagos].sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
}

function formatFecha(fecha) {
  return new Date(fecha).toLocaleString("es-AR");
}

function mensajeRecordatorio(cliente) {
  return `Hola ${cliente.nombre}, te recordamos que tenés un saldo pendiente de $${cliente.saldo.toFixed(
    2
  )}. Por favor contactanos para coordinar el pago. ¡Gracias!`;
}

function linkWhatsapp(cliente) {
  const numero = (cliente.telefono || "").replace(/\D/g, "");
  if (!numero) return null;
  return `https://wa.me/${numero}?text=${encodeURIComponent(mensajeRecordatorio(cliente))}`;
}

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

function renderFilaHistorial(cliente) {
  const movs = movimientos(cliente);
  const filas = movs.length
    ? movs
        .map(
          (m) => `
        <tr>
          <td>${formatFecha(m.fecha)}</td>
          <td class="${m.tipo === "cargo" ? "tag-cargo" : "tag-pago"}">${
            m.tipo === "cargo" ? "Cargo" : "Pago"
          }</td>
          <td>${m.tipo === "cargo" ? m.concepto : "Pago recibido"}</td>
          <td>$${m.monto.toFixed(2)}</td>
        </tr>
      `
        )
        .join("")
    : `<tr><td colspan="4">Sin movimientos</td></tr>`;

  return `
    <tr class="fila-historial">
      <td colspan="6">
        <table class="tabla-historial">
          <thead>
            <tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th>Monto</th></tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </td>
    </tr>
  `;
}

function renderTabla() {
  const tbody = document.querySelector("#tabla-clientes tbody");
  tbody.innerHTML = clientes
    .map((c) => {
      const claseSaldo = c.saldo > 0 ? "saldo-positivo" : "saldo-cero";
      const wa = linkWhatsapp(c);
      const filaPrincipal = `
        <tr>
          <td>${c.nombre}</td>
          <td>${c.telefono || "-"}</td>
          <td>$${c.totalCargos.toFixed(2)}</td>
          <td>$${c.totalPagos.toFixed(2)}</td>
          <td class="${claseSaldo}">$${c.saldo.toFixed(2)}</td>
          <td class="acciones">
            <button class="btn-secondary btn-historial" data-id="${c.id}">
              ${expandidos.has(c.id) ? "Ocultar" : "Historial"}
            </button>
            ${
              c.saldo > 0 && wa
                ? `<a class="btn-secondary" target="_blank" rel="noopener" href="${wa}">Recordatorio WhatsApp</a>`
                : ""
            }
            <button class="btn-danger" data-id="${c.id}">Eliminar</button>
          </td>
        </tr>
      `;
      return filaPrincipal + (expandidos.has(c.id) ? renderFilaHistorial(c) : "");
    })
    .join("");

  tbody.querySelectorAll(".btn-danger").forEach((btn) => {
    btn.addEventListener("click", async () => {
      await api(`/clientes/${btn.dataset.id}`, { method: "DELETE" });
      expandidos.delete(btn.dataset.id);
      await cargarClientes();
    });
  });

  tbody.querySelectorAll(".btn-historial").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      if (expandidos.has(id)) {
        expandidos.delete(id);
      } else {
        expandidos.add(id);
      }
      renderTabla();
    });
  });
}

function exportarCSV() {
  const encabezado = ["Cliente", "Telefono", "Cargos", "Pagado", "Saldo"];
  const filas = clientes.map((c) => [
    c.nombre,
    c.telefono || "",
    c.totalCargos.toFixed(2),
    c.totalPagos.toFixed(2),
    c.saldo.toFixed(2),
  ]);
  const csv = [encabezado, ...filas]
    .map((fila) => fila.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(","))
    .join("\n");
  descargarArchivo(csv, "cobranza.csv", "text/csv");
}

function exportarJSON() {
  descargarArchivo(JSON.stringify(clientes, null, 2), "cobranza-backup.json", "application/json");
}

function descargarArchivo(contenido, nombre, tipo) {
  const blob = new Blob([contenido], { type: tipo });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = nombre;
  a.click();
  URL.revokeObjectURL(url);
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

document.getElementById("btn-export-csv").addEventListener("click", exportarCSV);
document.getElementById("btn-export-json").addEventListener("click", exportarJSON);

cargarClientes().catch((err) => {
  alert("No se pudo conectar con el servidor de cobranza. ¿Está corriendo `npm start` en cobranza/server?");
  console.error(err);
});
