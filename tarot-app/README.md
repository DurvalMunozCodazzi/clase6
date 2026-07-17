# Tirada de Tarot — 3 Cartas (Pasado / Presente / Futuro)

App autocontenida (HTML + CSS + JS en un solo archivo) para hacer tiradas de tarot con las 78 cartas, basada en el sistema de arquetipos y palabras clave.

## Cómo funciona

Al entrar, primero se elige el **modo de tirada**:

### 1) Tirada digital

Para quien hace la consulta directamente en la web, sin cartas físicas de por medio.

1. El visitante escribe su pregunta.
2. Pantalla de **mezclado**: un mazo virtual con botón "🔀 Mezclar cartas" y una animación de barajado antes de empezar a elegir (le da ritual a la tirada, no es solo un clic instantáneo).
3. Se abre un popup con las 78 cartas boca abajo (mezcladas) y elige una para **Pasado**. Se repite para **Presente** y **Futuro** (las cartas ya elegidas no vuelven a aparecer).
4. Cada carta tiene ~25% de probabilidad de salir invertida, decidida al azar apenas se mezcla el mazo (no cuando se hace clic sobre ella).

### 2) Tirada física

Para cuando el operador ya tiró las cartas reales, físicamente, sobre la mesa, y quiere usar la app solo para obtener la lectura escrita.

1. Se escribe la pregunta igual que en el modo digital, pero **no hay paso de mezclado** (la baraja ya se mezcló en la mesa).
2. Para cada posición (Pasado/Presente/Futuro) se abre un buscador con las 78 cartas visibles (no boca abajo) y un campo de texto para filtrar por nombre — pensado para encontrar rápido la carta que salió en la mesa durante una lectura en vivo.
3. Al seleccionar una carta, el operador indica manualmente si salió **derecha o invertida** (esto lo decide la mesa, no el azar de la app).

### En ambos modos

Al completar las 3 cartas se genera automáticamente una lectura debajo: el significado de cada carta según su posición, más una **síntesis** que cruza patrones entre las 3 (mayoría de Arcanos Mayores, mismo palo, número repetido, cuántas invertidas, etc.).

Todo el motor de interpretación es local (reglas + plantillas combinando número + palo, tal como se armó la "chuleta" original). No depende de ninguna API externa ni de conexión a internet.

## Instalar en WordPress

**Opción recomendada — Bloque "HTML personalizado":**

1. Abre `index.html` con un editor de texto.
2. Copia **todo** el contenido.
3. En el editor de WordPress (Gutenberg), añade un bloque **HTML personalizado** y pega el contenido completo.
4. Publica. Listo — no necesita ningún plugin adicional.

Si prefieres un shortcode, se puede envolver este mismo HTML en una función de `functions.php` con `add_shortcode`; avísame si quieres que te prepare esa versión.

## Fotos de las cartas

Las 78 ya están incluidas en `assets/cards/` (formato `.png`, nombradas por id: `el-loco.png`, `bastos-as.png`, `copas-reina.png`, etc. — ver `assets/cards/README.md` para la lista completa y su origen/licencia). Si falta alguna, esa carta cae automáticamente en el diseño simbólico (icono del palo + número/nombre) sin romper nada.

**Si vas a alojarlas en tu propio WordPress** (recomendado en vez de servirlas desde esta carpeta):

1. Sube el contenido de `assets/cards/` a tu Biblioteca de medios (o a una carpeta `tarot-cartas` dentro de `wp-content/uploads/`), manteniendo los mismos nombres de archivo.
2. En `index.html`, busca la línea (cerca del inicio del `<script>`):
   ```js
   var IMAGE_BASE_URL = "assets/cards/";
   ```
   y cámbiala por la URL real donde subiste las fotos, por ejemplo:
   ```js
   var IMAGE_BASE_URL = "https://tusitio.com/wp-content/uploads/tarot-cartas/";
   ```
3. Guarda y pega de nuevo el HTML actualizado en el bloque de WordPress.

Si en algún momento quieres reemplazarlas por tus propias fotos (otro mazo, diseño propio, etc.), solo sobrescribe los archivos manteniendo los mismos nombres — el código no cambia.

## Personalizar posiciones o probabilidad de invertidas

Al inicio del `<script>`:

```js
var IMAGE_BASE_URL = "assets/cards/";
var REVERSED_PROBABILITY = 0.25; // 0 = nunca invertidas, 1 = siempre invertidas
```

Y el arreglo `POSITIONS` (más abajo) define las etiquetas de las 3 posiciones — puedes cambiar "Pasado / Presente / Futuro" por "Situación / Acción / Resultado" u otra que prefieras.

## Próximo nivel (opcional)

Si más adelante quieres que la lectura final sea un texto narrativo generado por IA (más rico y personalizado a la pregunta exacta) en vez de las plantillas por reglas, se puede añadir un botón "Profundizar con IA" que llame a la API de Claude desde un pequeño endpoint en tu WordPress. La estructura actual ya deja separado el motor de interpretación (`buildSynthesis` / `renderReading`) para conectar eso sin rehacer el resto de la app.
