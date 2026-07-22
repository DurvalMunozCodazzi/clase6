# Horóscopo Diario — Generador local

Calcula el horóscopo diario de los 12 signos usando posiciones planetarias reales
(Swiss Ephemeris, vía `pyswisseph`) y genera una interpretación astrológica basada
en esos datos: posición de cada planeta, retrogradaciones, fase lunar y aspectos.

Corre 100% en tu Mac, no necesita nada instalado en el servidor de WordPress.
El resultado se genera como JSON para pegarlo en el panel admin del plugin de tarot.

## Instalación (una sola vez)

Abrí Terminal en esta carpeta y ejecutá:

```bash
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

## Uso diario

Doble clic en `iniciar_app.command`. Se abre el navegador en `http://localhost:5050`.
Apretá **"Generar horóscopo de hoy"**, esperá unos segundos y vas a ver:

1. Los datos astronómicos reales del día (posiciones, retrogradaciones, fase lunar, aspectos).
2. La interpretación de cada uno de los 12 signos, para hoy y mañana.
3. Un bloque de **JSON listo para copiar** y pegar en el panel del plugin de tarot
   (sección Horóscopo → Importar), igual que hacés con las frases de las cartas.

También se guarda una copia automática en `~/Downloads/horoscopo_AAAA-MM-DD.json`.

## Uso por línea de comandos (opcional)

```bash
source venv/bin/activate
python generar_horoscopo.py --json salida.json
```

## Versión analítica (con Claude, opcional)

El botón **"✨ Generar versión analítica (con Claude)"** genera un horóscopo más elaborado
(secciones de Trabajo / Amor / Consejo del día), usando los mismos datos astronómicos reales
pero con una síntesis más fina, redactada por Claude en vez de plantillas fijas.

Requiere tener [Claude Code](https://code.claude.com) instalado en esta Mac y logueado con
tu cuenta de Claude Pro/Max/Team (no con una API key) — así no genera ningún costo extra,
usa el mismo cupo de tu suscripción.

Instalación (una sola vez), necesita Node.js — si ya usaste la herramienta de YouTube a Texto
seguramente ya lo tenés:

```bash
export PATH="$HOME/Downloads/node-v24.14.1-darwin-x64/bin:$PATH"   # ajustá la ruta si es distinta
npm install -g @anthropic-ai/claude-code
claude
```

La primera vez que corrés `claude` te va a pedir loguearte — elegí la opción
**"Claude account with subscription"** (no la de API key) y seguí los pasos en el navegador.

Si `claude` no quedó en una ruta estándar, editá `CLAUDE_PATH` al principio de `analitico.py`
con el resultado de `which claude`.

Esta versión tarda más (consulta a Claude 12 veces por día, en paralelo) — dejá la ventana
abierta hasta que termine.
