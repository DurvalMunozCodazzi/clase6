# Changelog — Reserva Total

Todos los cambios notables de este proyecto se documentan acá. El formato sigue
[Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/) y el versionado es
[Semántico](https://semver.org/lang/es/) (MAJOR.MINOR.PATCH).

Reserva Total se distribuye en dos formatos que comparten versión y funcionalidad:

- `reserva-total/` — app standalone (Node.js/Express + SQLite)
- `wordpress-plugin/reserva-total/` — plugin de WordPress (PHP)

## [1.2.0] - 2026-07-15

### Agregado
- Plugin de WordPress instalable (`wordpress-plugin/reserva-total/`), reescritura
  completa en PHP de la app Node.js para poder correr dentro de cualquier sitio
  WordPress:
  - Shortcode `[reserva_total]` con buscador de disponibilidad, reserva y
    consulta/cancelación de "Mis reservas" por email.
  - Panel "Reserva Total" en `wp-admin` para gestionar habitaciones y reservas,
    usando las capacidades nativas de WordPress (`manage_options`).
  - API REST propia bajo `/wp-json/reserva-total/v1/`.
  - Tablas propias vía `$wpdb`, creadas y sembradas al activar el plugin.

## [1.1.0] - 2026-07-15

### Agregado
- Panel de administración en la app Node.js: alta, edición y eliminación de
  habitaciones, y vista de todas las reservas del sistema.
- Protección de las rutas de administración con token (`ADMIN_TOKEN`).

### Corregido
- `GET /api/reservations` sin filtro exponía las reservas de todos los usuarios;
  ahora exige email propio o token de administrador.

## [1.0.0] - 2026-07-15

### Agregado
- Primera versión del sistema de reservas de habitaciones (Node.js/Express +
  SQLite, frontend HTML/CSS/JS): búsqueda de disponibilidad por fecha y
  huéspedes, creación de reservas con validación de solapamientos y capacidad,
  y consulta/cancelación de reservas propias por email.
