# Fotos de las 78 cartas

Las imágenes de esta carpeta son las ilustraciones clásicas del mazo **Rider–Waite–Smith** (publicado originalmente en 1909, ilustrado por Pamela Colman Smith).

## Origen de estos archivos

Se obtuvieron del paquete público de npm [`@cometpisces/tarot-kit-images`](https://www.npmjs.com/package/@cometpisces/tarot-kit-images) (código MIT) y se renombraron para que coincidan con los ids que usa `index.html`. No fueron descargadas directamente de internet en esta sesión (el entorno de desarrollo no tiene acceso general a internet, solo a registros de paquetes como npm).

## Nota sobre licencia — léela antes de publicar

El propio paquete de origen lo indica así, y aplica igual aquí:

> Las imágenes del Rider-Waite se consideran de dominio público en muchas jurisdicciones, pero su estatus de derechos de autor puede variar según el país y el uso. Eres responsable de verificar los requisitos de licencia en tu jurisdicción y, si el uso es comercial, de confirmar que no necesitas permisos adicionales.

En la práctica: la edición de 1909 es de dominio público en EE. UU. y se usa así en miles de sitios y apps (Wikipedia, Wikimedia Commons, etc.), así que el riesgo para un blog/app personal es mínimo. Si tu proyecto es comercial o vas a distribuirlo ampliamente, vale la pena una revisión rápida por tu cuenta.

## Nomenclatura (por si necesitas reemplazar alguna)

### Arcanos Mayores (22)

```
el-loco.jpg          el-mago.jpg           la-sacerdotisa.jpg    la-emperatriz.jpg
el-emperador.jpg      el-hierofante.jpg     los-enamorados.jpg    el-carro.jpg
la-fuerza.jpg         el-ermitano.jpg       la-rueda.jpg          la-justicia.jpg
el-colgado.jpg        la-muerte.jpg         la-templanza.jpg      el-diablo.jpg
la-torre.jpg          la-estrella.jpg       la-luna.jpg           el-sol.jpg
el-juicio.jpg         el-mundo.jpg
```

### Arcanos Menores (40) — patrón: `{palo}-{numero}.jpg`

Palos: `bastos`, `copas`, `espadas`, `oros` · Números: `as`, `2`...`10`

### Cartas de la Corte (16) — patrón: `{palo}-{figura}.jpg`

Figuras: `paje`, `caballo`, `reina`, `rey`

## Total: 78 archivos, todos presentes en esta carpeta.

Si quieres usar otro mazo o tus propias fotos más adelante, solo sobrescribe estos archivos manteniendo los mismos nombres — el código de `index.html` no necesita ningún cambio.
