=== Tirada de Tarot — 3 Cartas ===
Contributors: Durval Muñoz Codazzi (websobreruedas.com)
Tags: tarot, tirada, cartas, adivinacion, shortcode
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author: Durval Muñoz Codazzi
Author URI: https://websobreruedas.com

Widget de tirada de tarot de 3 cartas (Pasado / Presente / Futuro), con modo digital y modo físico, más un horóscopo diario de los 12 signos importado desde la app local de horóscopos.

== Description ==

Inserta el shortcode `[tirada_tarot]` en cualquier página o entrada.

Ofrece dos modos:

* **Tirada digital**: el visitante escribe su pregunta, mezcla un mazo virtual (con animación) y elige sus 3 cartas de una baraja de 78 mezclada al azar.
* **Tirada física**: pensado para cuando el operador ya tiró las cartas reales sobre la mesa. Permite buscar cada una de las 78 cartas por nombre y marcar manualmente si salió derecha o invertida.

En ambos casos, al completar las 3 cartas se genera automáticamente una lectura narrada (con variación de frases para que no se sienta repetitiva) más una síntesis que cruza patrones entre las 3 cartas (mayoría de Arcanos Mayores, mismo palo, número repetido, cantidad de invertidas, etc.).

Todo el motor de interpretación funciona localmente — no depende de ninguna API externa ni tiene costo por uso.

Incluye las 78 ilustraciones clásicas del mazo Rider-Waite-Smith (edición de 1909, dominio público en EE. UU.).

**Horóscopo diario**: el shortcode `[horoscopo]` muestra el horóscopo de los 12 signos (hoy y mañana), con un selector de signo y pestañas Hoy/Mañana. Los datos se cargan desde la app local `horoscopo-diario` (Mac), que los envía a la ruta REST `tirada-de-tarot/v1/horoscopo` autenticada con un token — o se pueden pegar a mano en el panel de administración (**Tirada de Tarot** en el menú de WordPress) si el envío directo no funciona.

== Installation ==

1. Sube la carpeta `tirada-de-tarot` completa a `/wp-content/plugins/`, o sube el .zip desde Plugins > Añadir nuevo > Subir plugin.
2. Activa el plugin desde el panel de Plugins de WordPress.
3. Inserta el shortcode `[tirada_tarot]` en cualquier página o entrada donde quieras que aparezca la tirada.

== Frequently Asked Questions ==

= ¿Puedo usar mis propias fotos de cartas? =

Sí. Reemplaza los archivos dentro de `assets/images/cards/` manteniendo exactamente los mismos nombres de archivo (por ejemplo `el-loco.jpg`, `bastos-as.jpg`, `copas-reina.jpg`). No hace falta tocar ningún código.

= ¿Puedo poner el shortcode más de una vez en la misma página? =

El plugin está pensado para un solo widget por página. Si necesitás varias tiradas en la misma página, avisá para adaptarlo.

= ¿Consume alguna API o tiene costo por uso? =

No. Toda la interpretación se genera localmente en el navegador del visitante.

== Changelog ==

= 1.5.0 =
* Nuevo: sección **Ajustes** en el panel de administración, con los dos shortcodes listos para copiar (para pegar en un módulo de Código/Texto de Divi o cualquier editor) y un campo de **imagen de cabecera**: pegás la URL de una imagen de tu Biblioteca de medios y aparece arriba de ambos widgets ([tirada_tarot] y [horoscopo]).

= 1.1.0 =
* Nuevo: módulo de horóscopo diario. Ruta REST `tirada-de-tarot/v1/horoscopo` (con token) para recibir los datos desde la app local, panel de administración (**Tirada de Tarot**) con el token, estado del último envío e importación manual por si el envío directo falla, y shortcode `[horoscopo]` con selector de los 12 signos y pestañas Hoy/Mañana.

= 1.0.4 =
* Reemplaza los íconos de emoji por un abanico de 3 cartas reales del propio mazo (El Mago, La Estrella, El Sol), en el título principal, "Tirada digital", "Tirada física", "Tu lectura" y "Síntesis de la tirada".

= 1.0.3 =
* Cambia el emoji de ícono (🎴, con mal soporte en muchas fuentes/sistemas y que podía verse como un cuadrado sin sentido) por 🃏, que renderiza de forma confiable como una carta.

= 1.0.2 =
* Cartas más grandes (~40%) en la grilla de búsqueda, la grilla de selección y la vista al revelar, para que se distingan mejor.

= 1.0.1 =
* Corrige el contraste de "Tu lectura" y "Síntesis de la tirada" cuando el plugin se inserta dentro de temas/builders (Divi, etc.) que fuerzan su propio color de encabezados.
* Cambia el ícono de bola de cristal por tres cartas.
* Fotos de las cartas comprimidas a JPEG (~83% más livianas), para que el .zip instale sin problemas de tamaño.

= 1.0.0 =
* Versión inicial: modo digital + modo físico, 78 cartas con arte incluido, motor de lectura narrado.
