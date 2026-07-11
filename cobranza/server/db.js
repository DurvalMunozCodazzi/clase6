const Database = require("better-sqlite3");
const path = require("path");

const db = new Database(path.join(__dirname, "cobranza.db"));

db.pragma("journal_mode = WAL");

db.exec(`
  CREATE TABLE IF NOT EXISTS clientes (
    id TEXT PRIMARY KEY,
    nombre TEXT NOT NULL,
    telefono TEXT,
    creado_en TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS cargos (
    id TEXT PRIMARY KEY,
    cliente_id TEXT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    concepto TEXT NOT NULL,
    monto REAL NOT NULL,
    fecha TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS pagos (
    id TEXT PRIMARY KEY,
    cliente_id TEXT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    monto REAL NOT NULL,
    fecha TEXT NOT NULL
  );
`);

module.exports = db;
