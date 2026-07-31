# Analizador diario de cartera

Programa que analiza cada día tu cartera real de fondos (Balanz) contra datos
reales del mercado argentino y genera un informe con semáforo de señales y una
recomendación concreta (MANTENER / AJUSTAR / ATENCIÓN).

## Qué hace en cada corrida

1. Lee tus posiciones reales de `cartera.json` (BCAHA Ahorro Corto Plazo + BAHUSDA Corporativo).
2. Baja cotizaciones reales del dólar (oficial, MEP, CCL, blue) de la API pública dolarapi.com y calcula la brecha.
3. Carga el historial de días anteriores para ver tendencias, no fotos.
4. Consulta a Claude (con búsqueda web del día) para verificar tasas, riesgo país, noticias y valores de cuotaparte.
5. Guarda `informes/informe_AAAA-MM-DD.md` y el snapshot de datos en `historial/`.

## Correrlo en local (recomendado para empezar)

```bash
cd analizador-cartera
pip install -r requirements.txt
export ANTHROPIC_API_KEY=sk-ant-...   # conseguila en console.anthropic.com
python analizador.py
```

Para automatizarlo todos los días a las 18:30 (Linux/Mac), agregá al crontab (`crontab -e`):

```
30 18 * * 1-5 cd /ruta/a/clase6/analizador-cartera && ANTHROPIC_API_KEY=sk-ant-... python3 analizador.py
```

## Correrlo online (gratis, sin tener la PC prendida)

La opción más simple es **GitHub Actions** (no hace falta contratar un dominio ni un servidor):
seguí las instrucciones dentro de `.github-workflow-ejemplo.yml`. Cada día el informe
queda commiteado en el repo y lo leés desde el celular en GitHub.

**Consejo**: empezá en local unos días para ajustar el informe a tu gusto, y después
pasalo a GitHub Actions. Un dominio/servidor propio solo tiene sentido si más adelante
querés una página web con gráficos — se puede agregar en una segunda etapa.

## Costo estimado

Una corrida diaria con búsqueda web cuesta del orden de u$s 0,05–0,15 por día
(según cuánto busque). La API key se crea en console.anthropic.com con tarjeta.

## Actualizar tus posiciones

Editá `cartera.json` cuando hagas un movimiento (suscripción, rescate, canje).
El campo `cuotapartes` y `precio_cuotaparte_inicial` deben reflejar lo que ves en Balanz.

## Aviso

Los informes son análisis educativos sobre datos públicos. No constituyen
asesoramiento financiero regulado; rendimientos pasados no garantizan resultados futuros.
