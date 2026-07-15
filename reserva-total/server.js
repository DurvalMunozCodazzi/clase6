import express from "express";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { db } from "./db.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));

function isValidDate(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(Date.parse(value));
}

function roomIsAvailable(roomId, checkIn, checkOut, excludeReservationId = null) {
  let query = `
    SELECT COUNT(*) AS count FROM reservations
    WHERE room_id = ?
      AND check_in < ?
      AND check_out > ?
  `;
  const params = [roomId, checkOut, checkIn];

  if (excludeReservationId) {
    query += " AND id != ?";
    params.push(excludeReservationId);
  }

  const { count } = db.prepare(query).get(...params);
  return count === 0;
}

// GET /api/rooms - lista habitaciones, opcionalmente filtradas por disponibilidad
app.get("/api/rooms", (req, res) => {
  const { checkIn, checkOut, guests } = req.query;
  const rooms = db.prepare("SELECT * FROM rooms ORDER BY price_per_night ASC").all();

  const withAvailability = rooms.map((room) => {
    let available = true;
    if (checkIn && checkOut) {
      if (!isValidDate(checkIn) || !isValidDate(checkOut) || checkIn >= checkOut) {
        available = false;
      } else {
        available = roomIsAvailable(room.id, checkIn, checkOut);
      }
    }
    if (guests && room.capacity < Number(guests)) {
      available = false;
    }
    return { ...room, available };
  });

  res.json(withAvailability);
});

// GET /api/rooms/:id
app.get("/api/rooms/:id", (req, res) => {
  const room = db.prepare("SELECT * FROM rooms WHERE id = ?").get(req.params.id);
  if (!room) return res.status(404).json({ error: "Habitación no encontrada" });
  res.json(room);
});

// GET /api/reservations - lista reservas, opcionalmente filtradas por email
app.get("/api/reservations", (req, res) => {
  const { email } = req.query;
  let query = `
    SELECT reservations.*, rooms.name AS room_name, rooms.price_per_night
    FROM reservations
    JOIN rooms ON rooms.id = reservations.room_id
  `;
  const params = [];
  if (email) {
    query += " WHERE guest_email = ?";
    params.push(email);
  }
  query += " ORDER BY check_in ASC";

  const reservations = db.prepare(query).all(...params);
  res.json(reservations);
});

// POST /api/reservations - crea una reserva
app.post("/api/reservations", (req, res) => {
  const { roomId, guestName, guestEmail, checkIn, checkOut, guests } = req.body;

  if (!roomId || !guestName || !guestEmail || !checkIn || !checkOut || !guests) {
    return res.status(400).json({ error: "Faltan datos obligatorios." });
  }
  if (!isValidDate(checkIn) || !isValidDate(checkOut)) {
    return res.status(400).json({ error: "Fechas inválidas." });
  }
  if (checkIn >= checkOut) {
    return res.status(400).json({ error: "La fecha de salida debe ser posterior a la de entrada." });
  }

  const room = db.prepare("SELECT * FROM rooms WHERE id = ?").get(roomId);
  if (!room) {
    return res.status(404).json({ error: "Habitación no encontrada." });
  }
  if (Number(guests) > room.capacity) {
    return res.status(400).json({ error: `La habitación admite hasta ${room.capacity} huéspedes.` });
  }
  if (!roomIsAvailable(roomId, checkIn, checkOut)) {
    return res.status(409).json({ error: "La habitación no está disponible en esas fechas." });
  }

  const result = db.prepare(`
    INSERT INTO reservations (room_id, guest_name, guest_email, check_in, check_out, guests)
    VALUES (?, ?, ?, ?, ?, ?)
  `).run(roomId, guestName, guestEmail, checkIn, checkOut, guests);

  const reservation = db.prepare(`
    SELECT reservations.*, rooms.name AS room_name, rooms.price_per_night
    FROM reservations JOIN rooms ON rooms.id = reservations.room_id
    WHERE reservations.id = ?
  `).get(result.lastInsertRowid);

  res.status(201).json(reservation);
});

// DELETE /api/reservations/:id - cancela una reserva
app.delete("/api/reservations/:id", (req, res) => {
  const reservation = db.prepare("SELECT * FROM reservations WHERE id = ?").get(req.params.id);
  if (!reservation) return res.status(404).json({ error: "Reserva no encontrada" });

  db.prepare("DELETE FROM reservations WHERE id = ?").run(req.params.id);
  res.status(204).end();
});

app.listen(PORT, () => {
  console.log(`Reserva Total corriendo en http://localhost:${PORT}`);
});
