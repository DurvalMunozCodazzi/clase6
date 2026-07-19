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
