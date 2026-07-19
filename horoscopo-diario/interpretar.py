"""
Interpretación astrológica tradicional a partir de los datos reales calculados en core.py.

Separación deliberada: core.py = matemática/astronomía (posiciones, retrogradaciones,
fase lunar, aspectos). Este archivo = lectura interpretativa (doctrina astrológica,
no verificable científicamente) que se apoya en esos datos reales.

Técnica usada: "rueda natural de casas" (la misma que usan los horóscopos de
diario/revista que no tienen tu hora de nacimiento). Cada signo se toma como si
fuera su propio ascendente, y el resto de los signos se cuentan como sus 12
casas en orden. Así, el mismo tránsito real de un planeta (por ejemplo "el Sol
está en Cáncer hoy") cae en una casa distinta según el signo — evitando que
los 12 horóscopos del día se lean igual solo porque el cielo real es uno solo.
"""
from core import SIGN_KEYS, SIGN_NAMES, PLANET_LABELS

RULER = {
    "aries": "marte", "tauro": "venus", "geminis": "mercurio", "cancer": "luna",
    "leo": "sol", "virgo": "mercurio", "libra": "venus", "escorpio": "pluton",
    "sagitario": "jupiter", "capricornio": "saturno", "acuario": "urano", "piscis": "neptuno",
}

RULER_TRADICIONAL = {
    "escorpio": "marte", "acuario": "saturno", "piscis": "jupiter",
}

MODALIDAD = {
    "aries": "cardinal", "cancer": "cardinal", "libra": "cardinal", "capricornio": "cardinal",
    "tauro": "fijo", "leo": "fijo", "escorpio": "fijo", "acuario": "fijo",
    "geminis": "mutable", "virgo": "mutable", "sagitario": "mutable", "piscis": "mutable",
}

MODALIDAD_TONO = {
    "cardinal": "es buen momento para dar el primer paso, aunque sea chico",
    "fijo": "tu fuerza está en sostener con paciencia lo que ya empezaste",
    "mutable": "la flexibilidad es tu carta ganadora: adaptá el plan si hace falta",
}

HOUSE_THEMES = {
    1: "tu imagen personal, tu energía y cómo te mostrás al mundo",
    2: "tu dinero, tus recursos y tu autoestima",
    3: "tu comunicación, el entorno cercano y los hermanos",
    4: "tu hogar, tu familia y tus raíces emocionales",
    5: "el amor, la creatividad y el disfrute",
    6: "tu trabajo cotidiano, tu rutina y tu salud",
    7: "tus relaciones de pareja y tus sociedades",
    8: "la transformación, lo compartido y la intimidad",
    9: "tus estudios, los viajes y la expansión de horizontes",
    10: "tu carrera, tu vocación y tu imagen pública",
    11: "tus amistades, los grupos y los proyectos a futuro",
    12: "tu mundo interior, el descanso y lo que se está cerrando",
}

# Consejo del día: se elige según la casa donde cae el Sol hoy para cada signo
# (el foco principal del día), para cerrar con una frase motivadora y concreta.
CONSEJOS_CASA = {
    1: "Hoy es un buen día para mostrarte tal cual sos, sin pedir permiso: tu energía personal es tu mejor herramienta.",
    2: "Cuidá lo que ya conseguiste: hoy rinde más valorar lo que tenés que arriesgarlo de más.",
    3: "Una palabra a tiempo puede destrabar algo hoy: animate a decir lo que estás pensando.",
    4: "Volver a lo simple de tu hogar o tu familia te va a dar más paz hoy que cualquier plan de afuera.",
    5: "Date el permiso de disfrutar: el amor y la creatividad piden protagonismo hoy.",
    6: "Un pequeño ajuste en tu rutina o en tu salud hoy rinde más de lo que pensás.",
    7: "Escuchar de verdad al otro es la clave hoy: tus vínculos piden presencia, no perfección.",
    8: "Soltar lo que ya cumplió su ciclo te libera más de lo que imaginás.",
    9: "Ampliá la mirada hoy: una idea, un viaje o un aprendizaje nuevo te puede cambiar el ánimo.",
    10: "Tu esfuerzo de fondo empieza a notarse: seguí construyendo tu lugar, aunque sea despacio.",
    11: "Rodeate de gente que sume: hoy un proyecto compartido vale más que uno en soledad.",
    12: "Bajar un cambio y escucharte por dentro hoy no es perder el tiempo, es prepararte para lo que viene.",
}


def _house_of(base_sign_key, transit_sign_key):
    base_idx = SIGN_KEYS.index(base_sign_key)
    transit_idx = SIGN_KEYS.index(transit_sign_key)
    return ((transit_idx - base_idx) % 12) + 1


def _casa(base_sign_key, sign_key_transitado):
    n = _house_of(base_sign_key, sign_key_transitado)
    return n, HOUSE_THEMES[n]


def _aspectos_de(planet_key, aspectos):
    return [a for a in aspectos if planet_key in (a["planeta1"], a["planeta2"])]


def _texto_aspecto(a, ruler_key):
    otro = a["planeta2"] if a["planeta1"] == ruler_key else a["planeta1"]
    return f"{a['aspecto'].lower()} con {PLANET_LABELS.get(otro, otro)}"


def interpretar_signo(sign_key, day_data):
    posiciones = day_data["posiciones"]
    fase = day_data["fase_lunar"]
    aspectos = day_data["aspectos"]

    modalidad = MODALIDAD[sign_key]
    ruler_key = RULER[sign_key]
    ruler_pos = posiciones[ruler_key]
    ruler_trad_key = RULER_TRADICIONAL.get(sign_key)

    sol_pos = posiciones["sol"]
    luna_pos = posiciones["luna"]

    partes = []

    n_sol, tema_sol = _casa(sign_key, sol_pos["sign_key"])
    partes.append(
        f"Hoy el Sol ilumina tu casa {n_sol}: {tema_sol}."
    )

    n_luna, tema_luna = _casa(sign_key, luna_pos["sign_key"])
    animo = "con ganas de sumar y crecer" if fase["waxing"] else "con ganas de soltar y cerrar etapas"
    partes.append(
        f"La Luna ilumina tu casa {n_luna} ({tema_luna}), {animo}."
    )

    n_ruler, tema_ruler = _casa(sign_key, ruler_pos["sign_key"])
    retro_txt = " y está retrógrado" if ruler_pos["retrograde"] else ""
    partes.append(
        f"Tu planeta regente, {PLANET_LABELS[ruler_key]}, transita {ruler_pos['sign_name']}{retro_txt}, "
        f"activando tu casa {n_ruler}: {tema_ruler}."
    )
    if ruler_pos["retrograde"]:
        partes.append(
            f"Con tu regente retrógrado, eso de {tema_ruler} pide revisar y afinar antes de "
            "avanzar con algo nuevo."
        )

    if ruler_trad_key:
        rt = posiciones[ruler_trad_key]
        n_rt, tema_rt = _casa(sign_key, rt["sign_key"])
        partes.append(
            f"Tu regente tradicional, {PLANET_LABELS[ruler_trad_key]}, está en {rt['sign_name']} "
            f"{'(también retrógrado) ' if rt['retrograde'] else ''}"
            f"y toca tu casa {n_rt}: {tema_rt}."
        )

    aspectos_ruler = _aspectos_de(ruler_key, aspectos)
    if aspectos_ruler:
        frases = [_texto_aspecto(a, ruler_key) for a in aspectos_ruler[:3]]
        partes.append(f"Tu regente hoy hace: {', '.join(frases)}.")

    merc = posiciones["mercurio"]
    if merc["retrograde"] and ruler_key != "mercurio":
        n_merc, tema_merc = _casa(sign_key, merc["sign_key"])
        partes.append(
            f"Mercurio retrógrado cae en tu casa {n_merc} ({tema_merc}): evitá firmar o cerrar "
            "algo importante ahí sin revisarlo dos veces."
        )
    venus = posiciones["venus"]
    if venus["retrograde"] and ruler_key != "venus":
        n_venus, tema_venus = _casa(sign_key, venus["sign_key"])
        partes.append(
            f"Venus retrógrado está en tu casa {n_venus} ({tema_venus}): es momento de revisar, "
            "no de decidir a las apuradas ahí."
        )

    partes.append(f"Como signo {modalidad}, {MODALIDAD_TONO[modalidad]}.")

    partes.append(f"✨ Consejo del día: {CONSEJOS_CASA[n_sol]}")

    return " ".join(partes)


def generar_horoscopos_dia(day_data):
    return {sk: interpretar_signo(sk, day_data) for sk in SIGN_KEYS}
