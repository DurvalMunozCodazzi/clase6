=== Tirada de Tarot — 3 Cartas ===
Contributors: Durval Muñoz Codazzi (websobreruedas.com)
Tags: tarot, tirada, cartas, adivinacion, shortcode
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author: Durval Muñoz Codazzi
Author URI: https://websobreruedas.com

Widget de tirada de tarot de 3 cartas (Pasado / Presente / Futuro), con modo digital y modo físico, para insertar en cualquier página o entrada con un shortcode.

== Description ==

Inserta el shortcode `[tirada_tarot]` en cualquier página o entrada.

Ofrece dos modos:

* **Tirada digital**: el visitante escribe su pregunta, mezcla un mazo virtual (con animación) y elige sus 3 cartas de una baraja de 78 mezclada al azar.
* **Tirada física**: pensado para cuando el operador ya tiró las cartas reales sobre la mesa. Permite buscar cada una de las 78 cartas por nombre y marcar manualmente si salió derecha o invertida.

En ambos casos, al completar las 3 cartas se genera automáticamente una lectura narrada (con variación de frases para que no se sienta repetitiva) más una síntesis que cruza patrones entre las 3 cartas (mayoría de Arcanos Mayores, mismo palo, número repetido, cantidad de invertidas, etc.).

Todo el motor de interpretación funciona localmente — no depende de ninguna API externa ni tiene costo por uso.

Incluye las 78 ilustraciones clásicas del mazo Rider-Waite-Smith (edición de 1909, dominio público en EE. UU.).

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

= 1.0.2 =
* Cartas más grandes (~40%) en la grilla de búsqueda, la grilla de selección y la vista al revelar, para que se distingan mejor.

= 1.0.1 =
* Corrige el contraste de "Tu lectura" y "Síntesis de la tirada" cuando el plugin se inserta dentro de temas/builders (Divi, etc.) que fuerzan su propio color de encabezados.
* Cambia el ícono de bola de cristal por tres cartas.
* Fotos de las cartas comprimidas a JPEG (~83% más livianas), para que el .zip instale sin problemas de tamaño.

= 1.0.0 =
* Versión inicial: modo digital + modo físico, 78 cartas con arte incluido, motor de lectura narrado.
