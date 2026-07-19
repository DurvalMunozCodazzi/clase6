"""
Las 78 cartas del tarot (arcanos mayores + 4 palos), con su significado
tradicional derecho/invertido. Mismos IDs y textos que usa el plugin de
WordPress (assets/js/tarot.js), para que ambas herramientas hablen el
mismo "idioma" de cartas.
"""

SUITS = [
    {"key": "bastos", "name": "Bastos", "icon": "🔥",
     "domain_up": "acción, trabajo, pasión, energía, proyectos",
     "domain_rev": "proyectos estancados, agotamiento, frustración creativa"},
    {"key": "copas", "name": "Copas", "icon": "💧",
     "domain_up": "emociones, amor, relaciones, intuición, arte",
     "domain_rev": "emociones bloqueadas, desconexión, decepción amorosa"},
    {"key": "espadas", "name": "Espadas", "icon": "💨",
     "domain_up": "mente, pensamientos, conflictos, decisiones, dolor",
     "domain_rev": "confusión mental, conflictos sin resolver, pensamientos negativos"},
    {"key": "oros", "name": "Oros", "icon": "🌍",
     "domain_up": "dinero, salud, trabajo físico, lo material, lo práctico",
     "domain_rev": "problemas económicos, inseguridad material, salud descuidada"},
]

NUMBERS = [
    ("as", "As", "Inicio, semilla, oportunidad", "Oportunidad perdida, falso comienzo, dudas al empezar"),
    ("2", "2", "Equilibrio, decisión, unión", "Desequilibrio, indecisión, desunión"),
    ("3", "3", "Expansión, crecimiento, grupo", "Estancamiento, aislamiento, plan que no despega"),
    ("4", "4", "Estabilidad, estructura, posesión", "Inestabilidad, pérdida de estructura, apego excesivo"),
    ("5", "5", "Conflicto, pérdida, cambio brusco", "Resolución del conflicto, evitar una pérdida mayor"),
    ("6", "6", "Ayuda, armonía, solución", "Desequilibrio en la ayuda, deudas emocionales, favoritismo"),
    ("7", "7", "Evaluación, desafío, esfuerzo", "Duda, falta de esfuerzo, evaluación equivocada"),
    ("8", "8", "Movimiento, práctica, trabajo duro", "Estancamiento, lentitud, trabajo sin frutos"),
    ("9", "9", "Clausura, culminación, soledad", "Aislamiento excesivo, cansancio, cierre casi listo"),
    ("10", "10", "Final de ciclo, plenitud, exceso", "Final que se alarga, carga excesiva, cierre incompleto"),
]

COURT_FIGURES = [
    ("paje", "Paje", "Mensajero, noticias, aprendizaje, energía joven y exploradora", "Inmadurez, malas noticias, bloqueo en el aprendizaje"),
    ("caballo", "Caballo", "Movimiento rápido, viaje, acción intensa, impulso", "Prisa imprudente, retrasos, acción sin dirección"),
    ("reina", "Reina", "Intuición, observación, interioridad, energía receptiva y sabia", "Inseguridad emocional, sobreprotección o frialdad"),
    ("rey", "Rey", "Autoridad, control, estabilidad, energía activa y protectora", "Autoritarismo, rigidez, abuso de poder"),
]

MAJOR_DATA = [
    ("el-loco", "0. El Loco", "Nuevo comienzo, espontaneidad, fe ciega, viaje.", "Impulsividad, locura, riesgo tonto, miedo al cambio."),
    ("el-mago", "I. El Mago", "Acción, voluntad, poder de manifestar, tienes todas las herramientas.", "Manipulación, bloqueo de energía, falta de dirección."),
    ("la-sacerdotisa", "II. La Sacerdotisa", "Intuición, silencio, misterio, confía en tu voz interior.", "Ignorar tu intuición, secretos ocultos, superficialidad."),
    ("la-emperatriz", "III. La Emperatriz", "Abundancia, creatividad, embarazo (físico o de ideas), naturaleza.", "Esterilidad creativa, dependencia, falta de crecimiento."),
    ("el-emperador", "IV. El Emperador", "Estructura, orden, autoridad, protección, lógica.", "Tiranía, rigidez, exceso de control, falta de disciplina."),
    ("el-hierofante", "V. El Hierofante", "Tradición, educación, creencias, consejo de un experto.", "Rebelión, romper con lo establecido, dogmatismo."),
    ("los-enamorados", "VI. Los Enamorados", "Decisiones importantes, amor, unión, elección con el corazón.", "Desamor, desacuerdo, indecisión, evitar el compromiso."),
    ("el-carro", "VII. El Carro", "Voluntad, determinación, control de emociones, victoria.", "Falta de control, agresividad, ir sin dirección."),
    ("la-fuerza", "VIII. La Fuerza", "Coraje interior, domar instintos, paciencia, persuasión.", "Inseguridad, debilidad, dejarse llevar por los impulsos."),
    ("el-ermitano", "IX. El Ermitaño", "Introspección, buscar respuestas dentro, retiro, guía interna.", "Soledad, aislamiento, negarse a ver la verdad."),
    ("la-rueda", "X. La Rueda de la Fortuna", "Cambio de ciclo, destino, suerte, giro inesperado.", "Mala suerte, resistencia al cambio, patrones que se repiten."),
    ("la-justicia", "XI. La Justicia", "Verdad, consecuencias, equilibrio, decisiones legales.", "Injusticia, evasión de responsabilidad, culpa."),
    ("el-colgado", "XII. El Colgado", "Pausa, rendirse para ganar, nueva perspectiva, sacrificio.", "Estancamiento, no aprender la lección, víctima."),
    ("la-muerte", "XIII. La Muerte", "Transformación, dejar ir, final necesario, renacimiento.", "Resistencia al cambio, duelo estancado, no soltar el pasado."),
    ("la-templanza", "XIV. La Templanza", "Equilibrio, paciencia, mezclar opuestos, salud, fluir.", "Desequilibrio, impaciencia, caos, no encontrar el punto medio."),
    ("el-diablo", "XV. El Diablo", "Ataduras, adicciones, obsesiones, materialismo, sombra.", "Romper ataduras, liberarse, superar las adicciones."),
    ("la-torre", "XVI. La Torre", "Caos, derrumbe, revelación impactante, cambio brusco.", "Evitar el desastre, retrasar lo inevitable, miedo al cambio."),
    ("la-estrella", "XVII. La Estrella", "Esperanza, sanación, inspiración, calma después de la tormenta.", "Desesperanza, falta de fe, bloqueo creativo."),
    ("la-luna", "XVIII. La Luna", "Ilusiones, miedos ocultos, confusión, el subconsciente.", "Engaño, ansiedad, ver la verdad oculta, miedo superado."),
    ("el-sol", "XIX. El Sol", "Éxito, alegría, claridad, vitalidad, logro.", "Exceso de optimismo, retrasos, falta de vitalidad."),
    ("el-juicio", "XX. El Juicio", "Llamado interior, renacimiento, evaluar tu vida, perdón.", "Negar el llamado, autocrítica dura, miedo al cambio."),
    ("el-mundo", "XXI. El Mundo", "Plenitud, culminación, viaje completado, sentirte completo.", "Sin finalizar, falta de logro, sentirte fuera de lugar."),
]


def _build_all_cards():
    cards = []
    for card_id, name, up, rev in MAJOR_DATA:
        cards.append({"id": card_id, "name": name, "up": up, "rev": rev})

    for suit in SUITS:
        for num_key, num_label, up_tag, rev_tag in NUMBERS:
            cards.append({
                "id": f"{suit['key']}-{num_key}",
                "name": f"{num_label} de {suit['name']}",
                "up": f"{up_tag}, en el terreno de {suit['domain_up']}.",
                "rev": f"{rev_tag}, en el terreno de {suit['domain_rev']}.",
            })
        for court_key, court_name, up_desc, rev_desc in COURT_FIGURES:
            cards.append({
                "id": f"{suit['key']}-{court_key}",
                "name": f"{court_name} de {suit['name']}",
                "up": f"{up_desc}, orientada hacia {suit['domain_up']}.",
                "rev": f"{rev_desc}, en el terreno de {suit['domain_rev']}.",
            })
    return cards


ALL_CARDS = _build_all_cards()  # 22 + 40 + 16 = 78
CARDS_BY_ID = {c["id"]: c for c in ALL_CARDS}
CARD_NAMES = [(c["id"], c["name"]) for c in ALL_CARDS]


def meaning(card_id, reversed_):
    card = CARDS_BY_ID[card_id]
    return card["rev"] if reversed_ else card["up"]


# --- Significados curados (opcional), exportados desde el panel admin del
# plugin de WordPress (los que armaste a partir de las transcripciones de
# YouTube). Si existen para una carta, se usan en vez de la etiqueta
# genérica de arriba.
import json
import os
import random

CURATED_PATH = os.path.join(os.path.dirname(__file__), "card-meanings.json")


def _cargar_curados():
    if not os.path.exists(CURATED_PATH):
        return {}
    try:
        with open(CURATED_PATH, "r", encoding="utf-8") as f:
            return json.load(f)
    except (json.JSONDecodeError, OSError):
        return {}


CURATED = _cargar_curados()


def recargar_curados():
    global CURATED
    CURATED = _cargar_curados()
    return CURATED


def meaning_rica(card_id, reversed_):
    """Significado real (curado) si existe para esta carta, si no, la
    etiqueta genérica tradicional."""
    entry = CURATED.get(card_id)
    pool = entry.get("rev" if reversed_ else "up") if entry else None
    if pool:
        return random.choice(pool)
    return meaning(card_id, reversed_)


def hay_significados_curados():
    return bool(CURATED)
