# Tarot Personal — Lecturas con Claude

Herramienta 100% local y personal (no es para publicar en la web) que arma lecturas
de tarot analíticas usando Claude Code instalado en tu Mac, con tu suscripción
Pro/Max — sin costo extra, igual que la app de horóscopo.

Dos modos:

- **Consulta puntual**: 3 cartas (Pasado / Presente / Futuro) sobre el tema que escribas.
- **Lectura ampliada**: 10 cartas — 5 sobre Amor y 5 sobre Trabajo, cada bloque siguiendo
  la secuencia: situación actual, qué bloquea, qué sentís vos, qué aporta el entorno, y
  hacia dónde va. Claude arma la síntesis de cada área con una tabla final, igual estilo
  que la versión analítica del horóscopo.

Podés tirar las cartas al azar (barajo digital) o cargar una tirada física real que
hiciste con tu propio mazo.

## Requisitos

Los mismos que ya usaste para el horóscopo: Node.js (para tener `npm`) y
[Claude Code](https://code.claude.com) instalado y logueado con tu cuenta Pro/Max
(no con una API key). Si ya lo dejaste andando para el horóscopo, no hace falta
instalar nada de nuevo — este también usa el mismo `claude` de tu Mac (revisá el
valor de `CLAUDE_PATH` en `generar_lectura.py` si tu instalación quedó en otra ruta).

## Instalación (una sola vez)

```bash
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

## Uso

Doble clic en `iniciar_app.command` (la primera vez, clic derecho → Abrir, para
saltar el aviso de macOS). Se abre el navegador en `http://localhost:5065`.

1. Elegí el tipo de lectura y cómo vas a cargar las cartas.
2. Si es digital, apretá "Barajar y tirar". Si es física, elegí cada carta y su
   orientación en los selectores.
3. Apretá "Generar lectura con Claude" y esperá (puede tardar hasta un minuto).

Cada lectura se guarda automáticamente como archivo de texto en `~/Downloads/`.
