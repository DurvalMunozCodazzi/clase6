# Cómo armar esta página en Divi

El archivo original era una landing HTML/CSS/JS autocontenida. Divi no tiene un módulo "pegar HTML completo" para una página entera, así que la forma correcta de portarlo es con el **módulo "Code"** de Divi Builder, que acepta HTML/CSS/JS crudo tal cual (sin sanitizar). Se dividió en 10 módulos, uno por sección, para poder reordenar y editar cada bloque desde el builder sin tocar código.

## Pasos

1. En WordPress: **Páginas → Añadir nueva** → activá **"Usar Divi Builder"**.
2. Elegí **"Construir desde cero"**.
3. Agregá una **Sección** (Regular, ancho completo) → una **Fila** de 1 columna → un módulo **"Code"** (Especialidad → Code).
4. Abrí `modulos/01-hero-y-estilos.html` y pegá **todo** su contenido en ese primer módulo Code. Este módulo incluye el `<style>` completo — aplica a toda la página en cuanto carga, aunque esté en la primera sección.
5. Repetí el paso 3 (nueva Sección → Fila 1 columna → módulo Code) para cada archivo restante, **en este orden**:
   - `02-diferenciadores.html`
   - `03-tabla-comparativa.html`
   - `04-pricing.html`
   - `05-whatsapp.html`
   - `06-recordatorios.html`
   - `07-privacidad.html`
   - `08-planes.html`
   - `09-cta-final.html`
   - `10-modal-footer-script.html` (modal de solicitud + footer + script del formulario — va al final)
6. Publicá la página y revisala en frontend (el editor visual de Divi no siempre renderiza `<script>` en vivo; para probar el modal y el formulario hay que ver la página publicada, no la vista previa del builder).

## Notas importantes

- **El endpoint del formulario** (`https://websobreruedas.com/wp-json/luna-licenses/v1/solicitar`, dentro del módulo 10) sigue apuntando al mismo backend que ya tenían. Si esa página se publica en otro dominio, hay que actualizar esa URL.
- **Módulos "Code" en Divi**, por defecto, dejan pasar `<script>` sin problema (no como el editor clásico de WordPress, que a veces lo filtra). Si tu tema/host tiene algún plugin de seguridad que sanitiza HTML en el editor, puede recortar el `<script>` — en ese caso conviene pegar el bloque `<script>...</script>` del módulo 10 en **Divi → Opciones del tema → Integración → "Añadir código antes de `</body>`"** en vez de dejarlo en el módulo Code.
- Si preferís una sola sección con todo en un solo módulo Code (menos flexible para reordenar en el builder, pero un solo paso), podés concatenar los 10 archivos en uno y pegarlo en un único módulo — el orden ya está numerado para eso.
- El CSS usa `:root { --dark; --acc; ... }` con variables globales — si tu tema Divi también define variables CSS con esos mismos nombres, revisá que no haya colisión. No es común, pero vale chequear.
