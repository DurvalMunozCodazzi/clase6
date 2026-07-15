=== Reserva Total ===
Contributors: reservatotal
Tags: reservas, habitaciones, hotel, booking
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema de reservas de habitaciones para WordPress.

== Description ==

Reserva Total agrega un sistema de reservas de habitaciones a tu sitio de WordPress:

* Shortcode `[reserva_total]` para mostrar el buscador de disponibilidad, el formulario de reserva y la consulta de "Mis reservas" por email en cualquier página o entrada.
* Panel de administración ("Reserva Total" en el escritorio) para crear, editar y eliminar habitaciones, y para ver y cancelar todas las reservas del sitio.
* Guarda todo en tablas propias de la base de datos de WordPress (no depende de servicios externos).

== Installation ==

1. Subí la carpeta `reserva-total` a `/wp-content/plugins/`, o instalá el .zip desde Plugins > Añadir nuevo > Subir plugin.
2. Activá el plugin desde el menú Plugins.
3. Al activarse se crean las tablas necesarias y se cargan 6 habitaciones de ejemplo.
4. Agregá el shortcode `[reserva_total]` a cualquier página para mostrar el buscador de reservas.
5. Administrá habitaciones y reservas desde el menú "Reserva Total" del escritorio de WordPress.

== Frequently Asked Questions ==

= ¿Necesito configurar una base de datos aparte? =

No. El plugin usa la base de datos de WordPress y crea sus propias tablas (`wp_reserva_total_rooms` y `wp_reserva_total_reservations`) al activarse.

= ¿Quién puede administrar habitaciones y reservas? =

Cualquier usuario con el permiso `manage_options` (administradores del sitio, por defecto).

== Changelog ==

El número de versión se mantiene sincronizado con el de la app Node.js standalone
de Reserva Total (ver `CHANGELOG.md` en la raíz del repositorio).

= 1.2.0 =
* Primera versión del plugin de WordPress: shortcode `[reserva_total]`, panel de administración en `wp-admin` y API REST propia.

= 1.1.0 =
* (Solo app Node.js) Panel de administración de habitaciones y reservas.

= 1.0.0 =
* (Solo app Node.js) Versión inicial: búsqueda de disponibilidad, reservas, y consulta/cancelación por email.
