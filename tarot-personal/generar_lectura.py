"""
Genera la lectura de tarot analítica, pasándole las cartas ya tiradas
(reales o digitales) a Claude (vía `claude -p`, con la suscripción
Pro/Max local, igual que el horóscopo) para que arme una síntesis fina
en vez de elegir frases de una lista fija.
"""
import subprocess

from cards_data import CARDS_BY_ID, meaning_rica

CLAUDE_PATH = "/Users/edgardomunozcodazzi/Downloads/node-v24.14.1-darwin-x64/bin/claude"
TIMEOUT_SEGUNDOS = 90


def _linea_carta(tirada_item):
    pos = tirada_item["position"]
    card = CARDS_BY_ID[tirada_item["card_id"]]
    orientacion = "invertida" if tirada_item["reversed"] else "derecha"
    sig = meaning_rica(tirada_item["card_id"], tirada_item["reversed"])
    return f"- {pos['label']}: {card['name']} ({orientacion}) — {sig}"


def _llamar_claude(prompt):
    try:
        resultado = subprocess.run(
            [CLAUDE_PATH, "-p", prompt],
            capture_output=True, text=True, timeout=TIMEOUT_SEGUNDOS,
        )
    except subprocess.TimeoutExpired:
        return None, "Claude tardó demasiado en responder."
    except FileNotFoundError:
        return None, "No se encontró Claude Code en la ruta configurada (revisá CLAUDE_PATH)."

    if resultado.returncode != 0:
        return None, (resultado.stderr or "error desconocido").strip()
    return resultado.stdout.strip(), None


def _recortar_preambulo(texto, marcador):
    idx = texto.find(marcador)
    if idx > 0:
        return texto[idx:].strip()
    return texto


def generar_lectura_puntual(tema, tirada):
    cartas_txt = "\n".join(_linea_carta(t) for t in tirada)
    prompt = f"""Sos un tarotista profesional que hace lecturas analíticas y concretas, \
cruzando las cartas entre sí para llegar a una conclusión útil (no describís cada carta \
por separado como si fueran fichas sueltas).

Tema de la consulta: {tema}

Cartas tiradas:
{cartas_txt}

Escribí la lectura completa en español rioplatense (voseo), con este formato y estos \
títulos EXACTOS, en este orden:

⏳ Pasado
(3-4 oraciones. Qué muestra esta carta sobre el origen o la raíz del tema consultado.)

🌙 Presente
(3-4 oraciones. Qué está pasando ahora, cruzando esta carta con la de Pasado si aporta algo.)

✨ Futuro
(3-4 oraciones. Hacia dónde va esto, cruzando las tres cartas entre sí.)

🔮 Síntesis
(2-3 oraciones que unan las tres cartas en una sola idea central y un consejo concreto y \
accionable para el tema consultado.)

Reglas estrictas (no las rompas):
- Tu respuesta tiene que EMPEZAR directamente con la línea "⏳ Pasado", sin nada antes: ni un \
comentario tuyo, ni un título, ni "voy a hacer la lectura...".
- Los cuatro títulos van SIEMPRE, exactos: "⏳ Pasado", "🌙 Presente", "✨ Futuro" y "🔮 Síntesis".
- No inventes cartas ni significados que no estén arriba.
- Nunca le hagas una pregunta al lector ni cierres pidiendo su opinión.
- Escribí siempre en español (ni una palabra en inglés)."""

    texto, error = _llamar_claude(prompt)
    if error:
        return f"(No se pudo generar: {error})"
    return _recortar_preambulo(texto, "⏳ Pasado")


def generar_lectura_ampliada(tirada):
    cartas_amor = [t for t in tirada if t["position"]["area"] == "amor"]
    cartas_trabajo = [t for t in tirada if t["position"]["area"] == "trabajo"]

    cartas_amor_txt = "\n".join(_linea_carta(t) for t in cartas_amor)
    cartas_trabajo_txt = "\n".join(_linea_carta(t) for t in cartas_trabajo)

    prompt = f"""Sos un tarotista profesional que hace lecturas analíticas y densas, cruzando \
las cartas entre sí para llegar a conclusiones útiles (no describís cada carta por separado \
como si fueran fichas sueltas). Esta es una lectura ampliada de 10 cartas, 5 sobre el área de \
Amor y 5 sobre el área de Trabajo, cada bloque de 5 sigue la secuencia: situación actual, qué \
bloquea, qué siente/piensa la persona de verdad, qué aporta el otro/el entorno, y hacia dónde va.

Cartas del área AMOR:
{cartas_amor_txt}

Cartas del área TRABAJO:
{cartas_trabajo_txt}

Escribí la lectura completa en español rioplatense (voseo), con este formato y estos títulos \
EXACTOS, en este orden:

💼 En el trabajo
(Un párrafo largo y denso, 5-7 oraciones, cruzando las 5 cartas de este bloque en una \
narrativa coherente que siga su secuencia (situación → bloqueo → vos → entorno → dirección). \
Cerrá con un consejo concreto y accionable.)

❤️ En el amor
(Mismo nivel de detalle y extensión, cruzando las 5 cartas de este bloque en su propia \
narrativa coherente. Cerrá con un consejo concreto y accionable.)

⚠️ La advertencia final
(2-3 oraciones con lo más importante a tener en cuenta hoy entre ambas áreas, con ejemplos \
concretos de qué hacer o evitar.)

🌟 Síntesis del día
(Una tabla en formato markdown con columnas "Área", "Diagnóstico" y "Acción", con una fila \
para Trabajo, una para Amor y una para General.)

Al final, un renglón de cierre motivador de una sola oración con un emoji.

Reglas estrictas (no las rompas):
- Tu respuesta tiene que EMPEZAR directamente con la línea "💼 En el trabajo", sin nada antes: \
ni un comentario tuyo, ni un título, ni "voy a hacer la lectura...".
- Los cuatro títulos van SIEMPRE, exactos: "💼 En el trabajo", "❤️ En el amor", \
"⚠️ La advertencia final" y "🌟 Síntesis del día".
- No inventes cartas ni significados que no estén arriba.
- Nunca le hagas una pregunta al lector ni cierres pidiendo su opinión.
- Escribí siempre en español (ni una palabra en inglés)."""

    texto, error = _llamar_claude(prompt)
    if error:
        return f"(No se pudo generar: {error})"
    return _recortar_preambulo(texto, "💼 En el trabajo")
