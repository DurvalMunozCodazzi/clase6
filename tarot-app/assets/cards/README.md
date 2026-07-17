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
el-loco.png          el-mago.png           la-sacerdotisa.png    la-emperatriz.png
el-emperador.png      el-hierofante.png     los-enamorados.png    el-carro.png
la-fuerza.png         el-ermitano.png       la-rueda.png          la-justicia.png
el-colgado.png        la-muerte.png         la-templanza.png      el-diablo.png
la-torre.png          la-estrella.png       la-luna.png           el-sol.png
el-juicio.png         el-mundo.png
```

### Arcanos Menores (40) — patrón: `{palo}-{numero}.png`

Palos: `bastos`, `copas`, `espadas`, `oros` · Números: `as`, `2`...`10`

### Cartas de la Corte (16) — patrón: `{palo}-{figura}.png`

Figuras: `paje`, `caballo`, `reina`, `rey`

## Total: 78 archivos, todos presentes en esta carpeta.

Si quieres usar otro mazo o tus propias fotos más adelante, solo sobrescribe estos archivos manteniendo los mismos nombres — el código de `index.html` no necesita ningún cambio.
