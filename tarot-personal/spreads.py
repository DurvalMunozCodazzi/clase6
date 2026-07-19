"""
Definición de las dos tiradas: la consulta puntual (3 cartas, Pasado/
Presente/Futuro sobre un tema libre) y la lectura ampliada (10 cartas,
5 para Amor + 5 para Trabajo).
"""
import random

from cards_data import ALL_CARDS

SPREAD_PUNTUAL = [
    {"key": "pasado", "label": "Pasado", "area": None},
    {"key": "presente", "label": "Presente", "area": None},
    {"key": "futuro", "label": "Futuro", "area": None},
]

_POSICIONES_AREA = [
    ("situacion", "Situación actual"),
    ("bloqueo", "Qué bloquea o pesa"),
    ("vos", "Qué sentís/pensás vos de verdad"),
    ("entorno", "Qué aporta el otro / el entorno"),
    ("direccion", "Hacia dónde va esto"),
]

SPREAD_AMPLIADA = (
    [{"key": f"amor_{k}", "label": f"Amor — {label}", "area": "amor"} for k, label in _POSICIONES_AREA]
    + [{"key": f"trabajo_{k}", "label": f"Trabajo — {label}", "area": "trabajo"} for k, label in _POSICIONES_AREA]
)

SPREADS = {
    "puntual": SPREAD_PUNTUAL,
    "ampliada": SPREAD_AMPLIADA,
}


def tirar_digital(spread_key):
    """Elige cartas al azar (sin repetir) y orientación al azar para cada
    posición del spread pedido. Devuelve una lista de dicts con
    position, card_id, reversed."""
    posiciones = SPREADS[spread_key]
    elegidas = random.sample(ALL_CARDS, len(posiciones))
    resultado = []
    for pos, carta in zip(posiciones, elegidas):
        resultado.append({
            "position": pos,
            "card_id": carta["id"],
            "reversed": random.random() < 0.25,
        })
    return resultado
