const express = require("express");
const cors = require("cors");
const crypto = require("crypto");
const db = require("./db");

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json());

function clienteConMovimientos(cliente) {
  const cargos = db
    .prepare("SELECT * FROM cargos WHERE cliente_id = ? ORDER BY fecha")
    .all(cliente.id);
  const pagos = db
    .prepare("SELECT * FROM pagos WHERE cliente_id = ? ORDER BY fecha")
    .all(cliente.id);
  const totalCargos = cargos.reduce((s, c) => s + c.monto, 0);
  const totalPagos = pagos.reduce((s, p) => s + p.monto, 0);
  return {
    ...cliente,
    cargos,
    pagos,
    totalCargos,
    totalPagos,
    saldo: totalCargos - totalPagos,
  };
}

// Clientes
app.get("/api/clientes", (req, res) => {
  const clientes = db.prepare("SELECT * FROM clientes ORDER BY creado_en").all();
  res.json(clientes.map(clienteConMovimientos));
});

app.post("/api/clientes", (req, res) => {
  const { nombre, telefono } = req.body;
  if (!nombre || !nombre.trim()) {
    return res.status(400).json({ error: "El nombre es obligatorio" });
  }
  const cliente = {
    id: crypto.randomUUID(),
    nombre: nombre.trim(),
    telefono: telefono ? telefono.trim() : null,
    creado_en: new Date().toISOString(),
  };
  db.prepare(
    "INSERT INTO clientes (id, nombre, telefono, creado_en) VALUES (?, ?, ?, ?)"
  ).run(cliente.id, cliente.nombre, cliente.telefono, cliente.creado_en);
  res.status(201).json(clienteConMovimientos(cliente));
});

app.delete("/api/clientes/:id", (req, res) => {
  const info = db.prepare("DELETE FROM clientes WHERE id = ?").run(req.params.id);
  if (info.changes === 0) return res.status(404).json({ error: "Cliente no encontrado" });
  res.status(204).end();
});

// Cargos
app.post("/api/clientes/:id/cargos", (req, res) => {
  const cliente = db.prepare("SELECT * FROM clientes WHERE id = ?").get(req.params.id);
  if (!cliente) return res.status(404).json({ error: "Cliente no encontrado" });

  const { concepto, monto } = req.body;
  const montoNum = parseFloat(monto);
  if (!concepto || !concepto.trim() || !(montoNum > 0)) {
    return res.status(400).json({ error: "Concepto y monto válido son obligatorios" });
  }
  const cargo = {
    id: crypto.randomUUID(),
    cliente_id: cliente.id,
    concepto: concepto.trim(),
    monto: montoNum,
    fecha: new Date().toISOString(),
  };
  db.prepare(
    "INSERT INTO cargos (id, cliente_id, concepto, monto, fecha) VALUES (?, ?, ?, ?, ?)"
  ).run(cargo.id, cargo.cliente_id, cargo.concepto, cargo.monto, cargo.fecha);
  res.status(201).json(clienteConMovimientos(cliente));
});

// Pagos
app.post("/api/clientes/:id/pagos", (req, res) => {
  const cliente = db.prepare("SELECT * FROM clientes WHERE id = ?").get(req.params.id);
  if (!cliente) return res.status(404).json({ error: "Cliente no encontrado" });

  const { monto } = req.body;
  const montoNum = parseFloat(monto);
  if (!(montoNum > 0)) {
    return res.status(400).json({ error: "Monto válido es obligatorio" });
  }
  const pago = {
    id: crypto.randomUUID(),
    cliente_id: cliente.id,
    monto: montoNum,
    fecha: new Date().toISOString(),
  };
  db.prepare(
    "INSERT INTO pagos (id, cliente_id, monto, fecha) VALUES (?, ?, ?, ?)"
  ).run(pago.id, pago.cliente_id, pago.monto, pago.fecha);
  res.status(201).json(clienteConMovimientos(cliente));
});

app.listen(PORT, () => {
  console.log(`Servidor de cobranza escuchando en http://localhost:${PORT}`);
});
