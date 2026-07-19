"""
Interpretación astrológica tradicional a partir de los datos reales calculados en core.py.

Separación deliberada: core.py = matemática/astronomía (posiciones, retrogradaciones,
fase lunar, aspectos). Este archivo = lectura interpretativa (doctrina astrológica,
no verificable científicamente) que se apoya en esos datos reales.
"""
from core import SIGN_KEYS, SIGN_NAMES, PLANET_LABELS

ELEMENT = {
    "aries": "fuego", "leo": "fuego", "sagitario": "fuego",
    "tauro": "tierra", "virgo": "tierra", "capricornio": "tierra",
    "geminis": "aire", "libra": "aire", "acuario": "aire",
    "cancer": "agua", "escorpio": "agua", "piscis": "agua",
}

MODALIDAD = {
    "aries": "cardinal", "cancer": "cardinal", "libra": "cardinal", "capricornio": "cardinal",
    "tauro": "fijo", "leo": "fijo", "escorpio": "fijo", "acuario": "fijo",
    "geminis": "mutable", "virgo": "mutable", "sagitario": "mutable", "piscis": "mutable",
}

RULER = {
    "aries": "marte", "tauro": "venus", "geminis": "mercurio", "cancer": "luna",
    "leo": "sol", "virgo": "mercurio", "libra": "venus", "escorpio": "pluton",
    "sagitario": "jupiter", "capricornio": "saturno", "acuario": "urano", "piscis": "neptuno",
}

RULER_TRADICIONAL = {
    "escorpio": "marte", "acuario": "saturno", "piscis": "jupiter",
}

COMPATIBLES = [frozenset(["fuego", "aire"]), frozenset(["tierra", "agua"])]

ELEMENT_TONE = {
    "fuego": "impulso, iniciativa y ganas de actuar ya",
    "tierra": "necesidad de resultados concretos y paso a paso",
    "aire": "cabeza acelerada, ideas y ganas de hablar/conectar",
    "agua": "sensibilidad a flor de piel e intuición muy despierta",
}


def _elem_relation(e1, e2):
    if e1 == e2:
        return "igual"
    if frozenset([e1, e2]) in COMPATIBLES:
        return "compatible"
    return "tenso"


def _fmt_planet_transit(planet_key, pos):
    label = PLANET_LABELS[planet_key]
    retro = " (retrógrado)" if pos["retrograde"] else ""
    return f"{label} en {pos['sign_name']}{retro}"


def _aspectos_de(planet_key, aspectos):
    return [a for a in aspectos if planet_key in (a["planeta1"], a["planeta2"])]


def _texto_aspecto(a, ruler_key):
    otro = a["planeta2"] if a["planeta1"] == ruler_key else a["planeta1"]
    return f"{a['aspecto'].lower()} con {PLANET_LABELS.get(otro, otro)}"


def interpretar_signo(sign_key, day_data):
    posiciones = day_data["posiciones"]
    fase = day_data["fase_lunar"]
    aspectos = day_data["aspectos"]

    sign_name = SIGN_NAMES[SIGN_KEYS.index(sign_key)]
    elemento = ELEMENT[sign_key]
    modalidad = MODALIDAD[sign_key]
    ruler_key = RULER[sign_key]
    ruler_pos = posiciones[ruler_key]
    ruler_trad_key = RULER_TRADICIONAL.get(sign_key)

    luna_pos = posiciones["luna"]
    relacion_luna = _elem_relation(elemento, ELEMENT[luna_pos["sign_key"]])

    partes = []

    partes.append(
        f"Tu planeta regente, {PLANET_LABELS[ruler_key]}, transita hoy por "
        f"{ruler_pos['sign_name']}{' en fase retrógrada' if ruler_pos['retrograde'] else ''}."
    )
    if ruler_pos["retrograde"]:
        partes.append(
            "Con tu regente retrógrado conviene revisar y afinar antes de lanzarte a cosas nuevas: "
            "es un buen momento para corregir rumbo más que para arrancar de cero."
        )
    if ruler_trad_key:
        rt = posiciones[ruler_trad_key]
        partes.append(
            f"Tu regente tradicional, {PLANET_LABELS[ruler_trad_key]}, está en {rt['sign_name']}"
            f"{' y también retrógrado' if rt['retrograde'] else ''}, lo que suma matices a tu día."
        )

    partes.append(
        f"La Luna está hoy en {luna_pos['sign_name']} ({fase['phase_name']}, "
        f"{fase['illumination_pct']}% de iluminación), en relación {relacion_luna} con tu elemento "
        f"({elemento}). Eso se traduce en {ELEMENT_TONE[elemento]}."
    )

    aspectos_ruler = _aspectos_de(ruler_key, aspectos)
    if aspectos_ruler:
        frases = [_texto_aspecto(a, ruler_key) for a in aspectos_ruler[:3]]
        partes.append(
            f"Aspectos activos de tu regente hoy: {', '.join(frases)}."
        )

    merc = posiciones["mercurio"]
    if merc["retrograde"] and ruler_key != "mercurio":
        partes.append(
            "Mercurio retrógrado en el cielo general: cuidado con firmas, mensajes cruzados y "
            "malentendidos, aplica para todos los signos, no solo el tuyo."
        )
    venus = posiciones["venus"]
    if venus["retrograde"] and ruler_key != "venus":
        partes.append(
            "Venus retrógrado en el ambiente general: es buen momento para revisar vínculos y "
            "finanzas antes de tomar decisiones grandes."
        )

    if modalidad == "cardinal":
        partes.append("Día propicio para iniciar, aunque sea en pequeño.")
    elif modalidad == "fijo":
        partes.append("Tu fuerza hoy está en sostener lo que ya empezaste, con paciencia.")
    else:
        partes.append("Flexibilidad es tu carta ganadora hoy: adapta el plan si hace falta.")

    return " ".join(partes)


def generar_horoscopos_dia(day_data):
    return {sk: interpretar_signo(sk, day_data) for sk in SIGN_KEYS}
