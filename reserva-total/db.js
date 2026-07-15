import { DatabaseSync } from "node:sqlite";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const dbPath = path.join(__dirname, "data", "reserva-total.sqlite");

export const db = new DatabaseSync(dbPath);

db.exec(`
  CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL,
    capacity INTEGER NOT NULL,
    price_per_night REAL NOT NULL,
    description TEXT
  );

  CREATE TABLE IF NOT EXISTS reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    guest_name TEXT NOT NULL,
    guest_email TEXT NOT NULL,
    check_in TEXT NOT NULL,
    check_out TEXT NOT NULL,
    guests INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
  );
`);

const roomCount = db.prepare("SELECT COUNT(*) AS count FROM rooms").get().count;

if (roomCount === 0) {
  const insertRoom = db.prepare(`
    INSERT INTO rooms (name, type, capacity, price_per_night, description)
    VALUES (?, ?, ?, ?, ?)
  `);

  const seedRooms = [
    ["Habitación Individual 101", "Individual", 1, 35000, "Habitación cómoda con escritorio, ideal para viajeros solos."],
    ["Habitación Doble 102", "Doble", 2, 52000, "Cama matrimonial, vista al jardín."],
    ["Habitación Doble 103", "Doble", 2, 52000, "Dos camas individuales, ideal para amigos o colegas."],
    ["Habitación Triple 201", "Triple", 3, 68000, "Espaciosa, con balcón y vista a la ciudad."],
    ["Suite Familiar 202", "Suite", 4, 95000, "Sala de estar separada, ideal para familias."],
    ["Suite Premium 301", "Suite", 2, 120000, "Jacuzzi privado y vista panorámica."],
  ];

  for (const room of seedRooms) {
    insertRoom.run(...room);
  }

  console.log(`Se sembraron ${seedRooms.length} habitaciones.`);
}

if (process.argv.includes("--seed")) {
  console.log("Base de datos lista en", dbPath);
}
