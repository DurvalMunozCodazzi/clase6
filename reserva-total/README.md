# Reserva Total

Sistema de reservas de habitaciones. Backend en Node.js/Express con SQLite (`node:sqlite`) y frontend en HTML/CSS/JS puro.

## Requisitos

- Node.js 22.5+ (usa el módulo nativo `node:sqlite`)

## Uso

```bash
npm install
npm start
```

La app queda disponible en `http://localhost:3000`.

Al iniciar por primera vez se crea `data/reserva-total.sqlite` y se siembran 6 habitaciones de ejemplo.

## Funcionalidad

- Buscar habitaciones disponibles por fecha de entrada/salida y cantidad de huéspedes.
- Reservar una habitación (nombre y email del huésped).
- Consultar y cancelar reservas propias por email.

## API

| Método | Endpoint                 | Descripción                                   |
|--------|---------------------------|-----------------------------------------------|
| GET    | `/api/rooms`              | Lista habitaciones (filtros: `checkIn`, `checkOut`, `guests`) |
| GET    | `/api/rooms/:id`          | Detalle de una habitación                     |
| GET    | `/api/reservations`       | Lista reservas (filtro: `email`)              |
| POST   | `/api/reservations`       | Crea una reserva                              |
| DELETE | `/api/reservations/:id`   | Cancela una reserva                           |
