import express from "express";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { db } from "./db.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = process.env.PORT || 3000;
const ADMIN_TOKEN = process.env.ADMIN_TOKEN || "admin123";

app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));

function isValidDate(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(Date.parse(value));
}

function isAdmin(req) {
  return req.get("x-admin-token") === ADMIN_TOKEN;
}

function requireAdmin(req, res, next) {
  if (!isAdmin(req)) {
    return res.status(403).json({ error: "Acceso de administrador requerido." });
  }
  next();
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

// POST /api/rooms - crea una habitación (admin)
app.post("/api/rooms", requireAdmin, (req, res) => {
  const { name, type, capacity, pricePerNight, description } = req.body;

  if (!name || !type || !capacity || !pricePerNight) {
    return res.status(400).json({ error: "Faltan datos obligatorios." });
  }
  if (Number(capacity) <= 0 || Number(pricePerNight) <= 0) {
    return res.status(400).json({ error: "Capacidad y precio deben ser mayores a cero." });
  }

  const result = db.prepare(`
    INSERT INTO rooms (name, type, capacity, price_per_night, description)
    VALUES (?, ?, ?, ?, ?)
  `).run(name, type, Number(capacity), Number(pricePerNight), description || "");

  const room = db.prepare("SELECT * FROM rooms WHERE id = ?").get(result.lastInsertRowid);
  res.status(201).json(room);
});

// PUT /api/rooms/:id - edita una habitación (admin)
app.put("/api/rooms/:id", requireAdmin, (req, res) => {
  const room = db.prepare("SELECT * FROM rooms WHERE id = ?").get(req.params.id);
  if (!room) return res.status(404).json({ error: "Habitación no encontrada." });

  const { name, type, capacity, pricePerNight, description } = req.body;
  if (!name || !type || !capacity || !pricePerNight) {
    return res.status(400).json({ error: "Faltan datos obligatorios." });
  }
  if (Number(capacity) <= 0 || Number(pricePerNight) <= 0) {
    return res.status(400).json({ error: "Capacidad y precio deben ser mayores a cero." });
  }

  db.prepare(`
    UPDATE rooms SET name = ?, type = ?, capacity = ?, price_per_night = ?, description = ?
    WHERE id = ?
  `).run(name, type, Number(capacity), Number(pricePerNight), description || "", req.params.id);

  const updated = db.prepare("SELECT * FROM rooms WHERE id = ?").get(req.params.id);
  res.json(updated);
});

// DELETE /api/rooms/:id - elimina una habitación (admin), si no tiene reservas
app.delete("/api/rooms/:id", requireAdmin, (req, res) => {
  const room = db.prepare("SELECT * FROM rooms WHERE id = ?").get(req.params.id);
  if (!room) return res.status(404).json({ error: "Habitación no encontrada." });

  const { count } = db.prepare("SELECT COUNT(*) AS count FROM reservations WHERE room_id = ?").get(req.params.id);
  if (count > 0) {
    return res.status(409).json({ error: "No se puede eliminar: la habitación tiene reservas asociadas." });
  }

  db.prepare("DELETE FROM rooms WHERE id = ?").run(req.params.id);
  res.status(204).end();
});

// GET /api/reservations - lista reservas propias por email, o todas si es admin
app.get("/api/reservations", (req, res) => {
  const { email } = req.query;

  if (!email && !isAdmin(req)) {
    return res.status(400).json({ error: "Indicá un email para consultar tus reservas." });
  }

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

// GET /api/admin/verify - valida el token de administrador
app.get("/api/admin/verify", requireAdmin, (req, res) => {
  res.json({ ok: true });
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
