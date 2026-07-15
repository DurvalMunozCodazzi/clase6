# Reserva Total

Sistema de reservas de habitaciones. Backend en Node.js/Express con SQLite (`node:sqlite`) y frontend en HTML/CSS/JS puro.

Versión actual: **1.2.0** — ver [`CHANGELOG.md`](../CHANGELOG.md) en la raíz del repo para el historial de cambios (incluye también el [plugin de WordPress](../wordpress-plugin/reserva-total/)).

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
- **Panel de administración** (pestaña "Admin"): crear, editar y eliminar habitaciones, y ver/cancelar todas las reservas del sistema.

## Panel de administración

Protegido por un token simple (no es un sistema de autenticación completo, pensado para uso en clase/demo).

- Token por defecto: `admin123`
- Se puede cambiar con la variable de entorno `ADMIN_TOKEN` antes de iniciar el servidor:
  ```bash
  ADMIN_TOKEN=mi-clave-secreta npm start
  ```
- En el frontend, entrá a la pestaña "Admin" e ingresá el token.

## API

| Método | Endpoint                 | Descripción                                   | Auth |
|--------|---------------------------|-----------------------------------------------|------|
| GET    | `/api/rooms`              | Lista habitaciones (filtros: `checkIn`, `checkOut`, `guests`) | - |
| GET    | `/api/rooms/:id`          | Detalle de una habitación                     | - |
| POST   | `/api/rooms`              | Crea una habitación                           | admin |
| PUT    | `/api/rooms/:id`          | Edita una habitación                          | admin |
| DELETE | `/api/rooms/:id`          | Elimina una habitación (si no tiene reservas) | admin |
| GET    | `/api/reservations`       | Lista reservas (por `email`, o todas si sos admin) | email o admin |
| POST   | `/api/reservations`       | Crea una reserva                              | - |
| DELETE | `/api/reservations/:id`   | Cancela una reserva                           | - |
| GET    | `/api/admin/verify`       | Valida el token de administrador              | admin |

Las rutas marcadas como `admin` requieren el header `x-admin-token: <ADMIN_TOKEN>`.
