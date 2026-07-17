# Plugin de WordPress — Tirada de Tarot

Versión instalable como plugin (en vez de pegar HTML a mano) de la misma app que está en `../tarot-app/`.

## Instalar

**Opción A — subir el .zip (más simple):**

1. En tu WordPress: **Plugins → Añadir nuevo → Subir plugin**.
2. Selecciona `tirada-de-tarot.zip` y súbelo.
3. Actívalo.
4. Inserta el shortcode `[tirada_tarot]` en cualquier página o entrada.

Si tu hosting rechaza el .zip por tamaño (pesa ~22 MB por las 78 ilustraciones incluidas), usa la opción B.

**Opción B — subir por FTP / Administrador de archivos:**

1. Sube la carpeta `tirada-de-tarot/` completa (sin comprimir) a `wp-content/plugins/` de tu sitio.
2. Actívalo desde **Plugins** en el panel de WordPress.
3. Inserta el shortcode `[tirada_tarot]`.

## Estructura

```
tirada-de-tarot/
  tirada-de-tarot.php     ← archivo principal: registra el shortcode y encola CSS/JS
  templates/app.php       ← el HTML del widget
  assets/css/tarot.css
  assets/js/tarot.js
  assets/images/cards/    ← las 78 ilustraciones
  readme.txt              ← metadata en formato estándar de plugin de WordPress
```

## Cómo se genera

Este plugin **no se edita a mano** — se genera automáticamente a partir de `../tarot-app/index.html` (la fuente de verdad de toda la lógica y el diseño) para que ambas versiones nunca queden desincronizadas.

Si en el futuro se modifica `tarot-app/index.html` (nuevas cartas, cambios de diseño, ajustes al motor de interpretación, etc.), para regenerar el plugin:

```bash
python3 wordpress-plugin/build-plugin.py
```

Esto vuelve a extraer el CSS, el HTML y el JS de `index.html`, adapta la carga de imágenes para que use la URL que WordPress inyecta (`TDT_CONFIG.imageBaseUrl`), y copia las 78 fotos. Después hay que volver a comprimir la carpeta en `.zip` si vas a distribuirla así.

## Personalizar

* **Fotos de las cartas**: reemplaza los archivos en `assets/images/cards/` manteniendo los mismos nombres — no requiere tocar código ni volver a generar nada.
* **Textos de las cartas / motor de interpretación**: se editan en `tarot-app/index.html` (arrays `MAJOR_DATA`, `NUMBERS`, `COURT_FIGURES`, `SUITS`, y las plantillas narrativas al final del script) y después se corre `build-plugin.py` para propagar el cambio a este plugin.
