"""
Versión "analítica" del horóscopo: en vez de armar el texto con plantillas fijas
(ver interpretar.py), le pasa los mismos datos reales a Claude (corriendo local
vía `claude -p`, usando la suscripción Pro/Max del usuario, sin API key ni costo
extra) y le pide que escriba una síntesis más fina, al estilo de un analista que
cruza varias señales (trabajo / amor / consejo).

Requiere tener Claude Code instalado y logueado en la máquina (ver README).
"""
import subprocess
from concurrent.futures import ThreadPoolExecutor

from core import PLANET_LABELS
from interpretar import (
    RULER, RULER_TRADICIONAL, _casa, _aspectos_de, _texto_aspecto,
)

CLAUDE_PATH = "/Users/edgardomunozcodazzi/Downloads/node-v24.14.1-darwin-x64/bin/claude"
TIMEOUT_SEGUNDOS = 90


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
    return f"""Sos un astrólogo profesional que escribe horóscopos diarios muy específicos, \
cruzando varias señales reales para llegar a una conclusión útil (no una lista de datos sueltos).

Datos astronómicos reales de hoy para {sign_name}:
{datos}

Escribí el horóscopo de hoy para {sign_name} en español rioplatense (voseo), con este formato \
exacto y estos títulos textuales:

💼 En el trabajo
(2-4 oraciones. Explicá qué significa la combinación de datos de arriba para esta área, con un \
consejo concreto y accionable.)

❤️ En el amor
(2-4 oraciones. Mismo criterio, usando el eje kármico si aporta algo relevante acá.)

⚠️ Consejo del día
(1-2 oraciones, un consejo concreto y accionable, no genérico.)

Reglas: no inventes datos astronómicos que no estén arriba. No repitas un número de casa sin \
explicar qué significa. No uses tablas ni bullets dentro de las secciones, son párrafos cortos. \
Máximo 200 palabras en total. No agregues ninguna introducción ni cierre fuera de esas 3 \
secciones."""


def generar_analisis_signo(sign_name, sign_key, day_data):
    prompt = _prompt_signo(sign_name, sign_key, day_data)
    try:
        resultado = subprocess.run(
            [CLAUDE_PATH, "-p", prompt],
            capture_output=True, text=True, timeout=TIMEOUT_SEGUNDOS,
        )
    except subprocess.TimeoutExpired:
        return "(No se pudo generar: Claude tardó demasiado en responder. Probá de nuevo.)"
    except FileNotFoundError:
        return "(No se encontró Claude Code en la ruta configurada. Revisá CLAUDE_PATH.)"

    if resultado.returncode != 0:
        error = (resultado.stderr or "error desconocido").strip()
        return f"(No se pudo generar: {error})"

    return resultado.stdout.strip()


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
