# Reserva Total — instrucciones del proyecto

## Versionado

- El proyecto usa [SemVer](https://semver.org/lang/es/). La versión se mantiene
  sincronizada entre `reserva-total/package.json` y
  `wordpress-plugin/reserva-total/reserva-total.php`
  (header `Version:` + constante `RESERVA_TOTAL_VERSION`) y su `readme.txt`
  (`Stable tag:` + sección `Changelog`).
- Cada cambio funcional agrega una entrada nueva en `CHANGELOG.md` (raíz del
  repo) y bumpea la versión: PATCH para fixes, MINOR para funcionalidad nueva
  compatible, MAJOR si rompe algo.
- **Al entregar el plugin de WordPress como .zip, el nombre del archivo debe
  incluir el número de versión** (ej. `reserva-total-1.2.0.zip`), para que no
  se mezclen versiones viejas al ir iterando. No entregar el zip sin versión
  en el nombre.
