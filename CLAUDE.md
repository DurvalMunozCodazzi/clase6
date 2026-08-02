# ReservaTotal — instrucciones del proyecto

Plugin de WordPress para reservas de habitaciones/cabañas con pago por
MercadoPago y licencia anual por dominio. Vive en
`wordpress-plugin/reservatotal/` (slug del plugin: `reservatotal`, sin guion).

## Versionado

- El proyecto usa [SemVer](https://semver.org/lang/es/). La versión se
  mantiene sincronizada entre el header `Version:` y la constante
  `RT_VERSION` en `wordpress-plugin/reservatotal/reservatotal.php`.
- Cada cambio funcional agrega una entrada nueva en `CHANGELOG.md` (raíz del
  repo) y bumpea la versión: PATCH para fixes, MINOR para funcionalidad nueva
  compatible, MAJOR si rompe algo.
- **Al entregar el plugin como .zip, el nombre del archivo debe incluir el
  número de versión** (ej. `reservatotal-2.3.1.zip`), para que no se mezclen
  versiones viejas al ir iterando. No entregar el zip sin versión en el
  nombre.

## Notas del código

- `RT_DEV_MODE` (en `reservatotal.php`) está en `true` mientras no haya
  servidor de licencias activo — hace que `RT_License::is_active()` devuelva
  siempre `true`. Ponerlo en `false` recién cuando `RT_LICENSE_SERVER` esté
  respondiendo de verdad.
- Los AJAX públicos (`rt_public_action`) y de admin (`rt_admin_action`)
  corren ambos por `wp-admin/admin-ajax.php`, donde `is_admin()` de WordPress
  siempre da `true` — por eso `reservatotal.php` usa `wp_doing_ajax()` para
  decidir qué módulo cargar en cada request, no `is_admin()` solo.
