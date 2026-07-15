# Changelog — ReservaTotal

Todos los cambios notables de este proyecto se documentan acá. El formato sigue
[Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/) y el versionado es
[Semántico](https://semver.org/lang/es/) (MAJOR.MINOR.PATCH).

El plugin vive en `wordpress-plugin/reservatotal/`.

## [2.3.1] - 2026-07-15

### Corregido

- **AJAX público completamente roto**: `is_admin()` de WordPress también es
  `true` en pedidos a `wp-admin/admin-ajax.php`, así que el plugin nunca
  cargaba `RT_Public` (y por lo tanto nunca registraba los handlers
  `wp_ajax_rt_public_action` / `wp_ajax_nopriv_rt_public_action`) durante una
  llamada AJAX. Esto bloqueaba **todo** el formulario público: verificar
  disponibilidad y crear una reserva no funcionaban para ningún visitante.
  Ahora `reservatotal.php` usa `wp_doing_ajax()` para cargar los dos módulos
  (admin y público) durante requests AJAX.
- **Error fatal al ver el detalle de una reserva**: faltaba el archivo
  `admin/views/booking-detail.php`. Se agregó la vista.
- **Error fatal en el shortcode `[reservatotal_disponibilidad]`**: faltaba
  `templates/availability-calendar.php`. Se agregó la vista.
- **Respuestas AJAX inconsistentes**: `check_availability` (público) y
  `get_occupied_dates` (admin) devolvían JSON plano con `wp_send_json(...)`,
  pero el JS esperaba la respuesta envuelta en `data` (formato de
  `wp_send_json_success`). Se corrigieron ambos endpoints.
- **Función indefinida si se entraba directo a "Reservas"**:
  `rt_booking_status_badge()` y `rt_payment_badge()` estaban definidas dentro
  de `dashboard.php` y `bookings-list.php` respectivamente, así que si se
  visitaba una página sin haber cargado la otra antes, WordPress tiraba
  "Call to undefined function". Se extrajeron a `includes/rt-helpers.php`,
  cargado siempre desde `reservatotal.php`.

Todo lo anterior se probó de punta a punta en una instalación real de
WordPress (WP 6.4.3 + MariaDB): activación, alta de habitación, calendario
con fechas ocupadas y bloqueadas, tarifas por temporada, creación de reserva
completa (búsqueda → disponibilidad → reserva), cambio de estado, y el
detalle de reserva que antes rompía.

## [2.3] y anteriores

Versión original entregada por Durval Muñoz Codazzi: habitaciones/cabañas
como custom post type, tarifas por temporada, calendario de bloqueos,
reservas con pago por MercadoPago (preferencias + webhook), licencia anual
por dominio, notificaciones por email. Sin historial de changelog previo a
esta fecha.
