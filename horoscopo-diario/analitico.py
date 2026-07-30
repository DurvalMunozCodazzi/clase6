"""
Versión "analítica" del horóscopo: en vez de armar el texto con plantillas fijas
(ver interpretar.py), le pasa los mismos datos reales a Claude (corriendo local
vía `claude -p`, usando la suscripción Pro/Max del usuario, sin API key ni costo
extra) y le pide que escriba una síntesis más fina, al estilo de un analista que
cruza varias señales (trabajo / amor / consejo).

Requiere tener Claude Code instalado y logueado en la máquina (ver README).
"""
import subprocess
import sys
from concurrent.futures import ThreadPoolExecutor

from core import PLANET_LABELS
from interpretar import (
    RULER, RULER_TRADICIONAL, _casa, _aspectos_de, _texto_aspecto,
)

VERSION = "1.8.5"

CLAUDE_PATH = "/Users/edgardomunozcodazzi/Downloads/node-v24.14.1-darwin-x64/bin/claude"
CLAUDE_MODEL = "opus"  # fijo a propósito: si no se especifica, Claude Code usa el default
                        # de tu plan, y ese default puede cambiar (por ejemplo al pasar de
                        # Pro a Max), variando la extensión/estilo del texto sin que este
                        # código haya cambiado. Cambiá este valor si preferís otro modelo.
TIMEOUT_SEGUNDOS = 90

# Debajo de esta cantidad de caracteres, la respuesta se considera "resumida"
# y se pide una vez más recordando la extensión mínima.
LARGO_MINIMO = 1400


def _datos_reales_signo(sign_key, day_data):
    """Arma el mismo resumen de hechos reales que usa interpretar.py, pero como
    lista de puntos en vez de prosa, para pasárselo a Claude como contexto."""
    posiciones = day_data["posiciones"]
    fase = day_data["fase_lunar"]
    aspectos = day_data["aspectos"]

    ruler_key = RULER[sign_key]
    ruler_pos = posiciones[ruler_key]
    n_ruler, tema_ruler = _casa(sign_key, ruler_pos["sign_key"])

    sol_pos = posiciones["sol"]
    n_sol, tema_sol = _casa(sign_key, sol_pos["sign_key"])

    luna_pos = posiciones["luna"]
    n_luna, tema_luna = _casa(sign_key, luna_pos["sign_key"])

    lineas = [
        f"- Regente ({PLANET_LABELS[ruler_key]}) transita {ruler_pos['sign_name']}"
        f"{' [RETRÓGRADO]' if ruler_pos['retrograde'] else ''}, activa la casa {n_ruler} ({tema_ruler}).",
        f"- El Sol activa la casa {n_sol} ({tema_sol}).",
        f"- La Luna activa la casa {n_luna} ({tema_luna}), fase {fase['phase_name']} "
        f"({'creciente' if fase['waxing'] else 'menguante'}).",
    ]

    ruler_trad_key = RULER_TRADICIONAL.get(sign_key)
    if ruler_trad_key:
        rt = posiciones[ruler_trad_key]
        n_rt, tema_rt = _casa(sign_key, rt["sign_key"])
        lineas.append(
            f"- Regente tradicional ({PLANET_LABELS[ruler_trad_key]}) en {rt['sign_name']}"
            f"{' [RETRÓGRADO]' if rt['retrograde'] else ''}, activa la casa {n_rt} ({tema_rt})."
        )

    aspectos_ruler = _aspectos_de(ruler_key, aspectos)
    if aspectos_ruler:
        frases = [_texto_aspecto(a, ruler_key) for a in aspectos_ruler[:3]]
        lineas.append(f"- Aspectos del regente: {', '.join(frases)}.")

    merc = posiciones["mercurio"]
    if merc["retrograde"] and ruler_key != "mercurio":
        n_merc, tema_merc = _casa(sign_key, merc["sign_key"])
        fin = merc.get("fin_retrogrado")
        fin_txt = f", vuelve directo el {fin['fecha']}" if fin else ""
        lineas.append(f"- Mercurio retrógrado activa la casa {n_merc} ({tema_merc}){fin_txt}.")

    venus = posiciones["venus"]
    if venus["retrograde"] and ruler_key != "venus":
        n_venus, tema_venus = _casa(sign_key, venus["sign_key"])
        fin = venus.get("fin_retrogrado")
        fin_txt = f", vuelve directo el {fin['fecha']}" if fin else ""
        lineas.append(f"- Venus retrógrado activa la casa {n_venus} ({tema_venus}){fin_txt}.")

    nodo_norte = posiciones["nodo_norte"]
    nodo_sur = posiciones["nodo_sur"]
    n_nn, tema_nn = _casa(sign_key, nodo_norte["sign_key"])
    n_ns, tema_ns = _casa(sign_key, nodo_sur["sign_key"])
    lineas.append(
        f"- Eje kármico: soltar casa {n_ns} ({tema_ns}) para crecer hacia casa {n_nn} ({tema_nn})."
    )

    return "\n".join(lineas)


def _prompt_signo(sign_name, sign_key, day_data):
    datos = _datos_reales_signo(sign_key, day_data)
    return f"""Sos un astrólogo profesional que escribe horóscopos diarios muy analíticos, \
cruzando varias señales reales para llegar a conclusiones útiles y específicas (no una lista de \
datos sueltos ni frases genéricas de horóscopo de diario).

Datos astronómicos reales de hoy para {sign_name}:
{datos}

Escribí el horóscopo completo de hoy para {sign_name} en español rioplatense (voseo), con este \
formato y estos títulos textuales EXACTOS, en este orden:

💼 En el trabajo
(Un párrafo largo y denso, de 5 a 7 oraciones y MÍNIMO 130 palabras. Desarrollá en detalle qué \
significa la combinación de datos de arriba para esta área: el tránsito del regente, los aspectos, \
el retrógrado si aplica. Dale contexto y un consejo concreto y accionable, con ejemplos de \
situaciones posibles — una llamada, un mail, una negociación puntual.)

❤️ En el amor
(Mismo nivel de detalle y extensión que la sección anterior: 5 a 7 oraciones y MÍNIMO 130 \
palabras. Usá el eje kármico para explicar hacia dónde conviene crecer en esta área. Dale \
ejemplos concretos y separados según si la persona está en pareja o soltera.)

⚠️ La advertencia final
(2-3 oraciones combinando el consejo más importante del día con cualquier retrógrado activo: qué \
hacer y qué evitar concretamente, con ejemplos de frases o situaciones.)

🌟 Síntesis del día
(Una tabla en formato markdown con columnas "Área", "Diagnóstico" y "Acción", con una fila para \
Trabajo, una para Amor y una para General. Cada celda: una frase corta y concreta.)

Al final, un renglón de cierre motivador de una sola oración con un emoji, distinto para cada \
signo (no repitas siempre la misma frase).

Reglas estrictas de formato (muy importante, no las rompas):
- Tu respuesta tiene que EMPEZAR directamente con la línea "💼 En el trabajo", carácter por \
carácter. Nada antes: ni el nombre del signo, ni un título, ni ningún comentario tuyo sobre lo \
que vas a hacer (nunca escribas cosas como "voy a redactar" o "cruzando estos datos").
- Los cuatro títulos van SIEMPRE, exactos: "💼 En el trabajo", "❤️ En el amor", \
"⚠️ La advertencia final" y "🌟 Síntesis del día". Nunca los omitas ni los cambies.
- No repitas el nombre del signo dentro del cuerpo del texto.
- No inventes datos astronómicos que no estén arriba. No repitas un número de casa sin \
explicar qué significa.
- Nunca le hagas una pregunta al lector ni cierres pidiendo su opinión (esto no es una \
conversación, es un texto para publicar).
- Escribí siempre en español (ni una palabra en inglés).
- La extensión NO es negociable: las secciones de trabajo y amor tienen que ser párrafos \
largos y desarrollados (mínimo 130 palabras cada una). Un texto corto o resumido se considera \
una respuesta incorrecta."""


def _llamar_claude(prompt):
    try:
        resultado = subprocess.run(
            [CLAUDE_PATH, "-p", prompt, "--model", CLAUDE_MODEL],
            capture_output=True, text=True, timeout=TIMEOUT_SEGUNDOS,
        )
    except subprocess.TimeoutExpired:
        return None, "Claude tardó demasiado en responder."
    except FileNotFoundError:
        return None, "No se encontró Claude Code en la ruta configurada (revisá CLAUDE_PATH)."

    if resultado.returncode != 0:
        return None, (resultado.stderr or "error desconocido").strip()
    return resultado.stdout.strip(), None


def generar_analisis_signo(sign_name, sign_key, day_data):
    prompt = _prompt_signo(sign_name, sign_key, day_data)

    texto, error = _llamar_claude(prompt)
    if error:
        return f"(No se pudo generar: {error})"

    if "💼 En el trabajo" not in texto:
        # No respetó el formato: un reintento con el recordatorio reforzado.
        texto_reintento, error = _llamar_claude(
            prompt + "\n\nIMPORTANTE: tu respuesta tiene que empezar EXACTO con "
            "'💼 En el trabajo', sin nada antes."
        )
        if not error and "💼 En el trabajo" in texto_reintento:
            texto = texto_reintento

    if len(texto) < LARGO_MINIMO:
        # Salió resumido pese al prompt: un reintento recordando la extensión.
        print(f"  [{sign_name}] salió corto ({len(texto)} caracteres), reintentando...",
              file=sys.stderr, flush=True)
        texto_reintento, error = _llamar_claude(
            prompt + "\n\nIMPORTANTE: tu respuesta anterior fue demasiado corta. Las "
            "secciones de trabajo y amor tienen que tener MÍNIMO 130 palabras cada una, "
            "con ejemplos concretos. Escribí la versión completa y desarrollada."
        )
        if not error and len(texto_reintento) > len(texto):
            texto = texto_reintento

    print(f"  [{sign_name}] generado: {len(texto)} caracteres (modelo: {CLAUDE_MODEL})",
          file=sys.stderr, flush=True)

    return _normalizar_saltos(_recortar_preambulo(texto))


def _recortar_preambulo(texto):
    """Red de seguridad: si Claude se manda algún comentario antes del primer
    título pese a las reglas del prompt, lo descarta y arranca desde ahí."""
    marcador = "💼 En el trabajo"
    idx = texto.find(marcador)
    if idx > 0:
        return texto[idx:].strip()
    return texto


SECCIONES = ["💼 En el trabajo", "❤️ En el amor", "⚠️ La advertencia final", "🌟 Síntesis del día"]


def _normalizar_saltos(texto):
    """Garantiza un salto de línea en blanco antes de cada título de sección,
    aunque Claude no lo haya puesto. Así el texto se lee bien se muestre donde
    se muestre (no depende de que el visor tenga el parser de trabajo/amor/etc.)."""
    for marcador in SECCIONES:
        texto = texto.replace(marcador, "\n\n" + marcador)
    return texto.strip()


def generar_analitico_dia(day_data, sign_keys, sign_names, max_workers=4):
    """sign_keys/sign_names: listas paralelas (ver core.SIGN_KEYS / SIGN_NAMES).
    Corre varias consultas a Claude en paralelo (moderado, para no saturar el
    cupo de uso de la cuenta) en vez de una por una."""
    resultado = {}
    with ThreadPoolExecutor(max_workers=max_workers) as pool:
        futuros = {
            pool.submit(generar_analisis_signo, name, key, day_data): key
            for key, name in zip(sign_keys, sign_names)
        }
        for futuro, key in futuros.items():
            resultado[key] = futuro.result()
    return resultado
