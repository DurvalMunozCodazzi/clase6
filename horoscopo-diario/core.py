"""
Cálculo astronómico real de posiciones planetarias con Swiss Ephemeris (pyswisseph).

Todo lo que hay en este archivo es matemática/astronomía verificable: posición de
cada planeta en el zodiaco, si está en retrogradación, fase lunar y aspectos
angulares entre planetas. No contiene ninguna interpretación esotérica: eso vive
en interpretar.py, separado a propósito para poder distinguir "dato real" de
"lectura tradicional".
"""
import math
import swisseph as swe

SIGN_NAMES = [
    "Aries", "Tauro", "Géminis", "Cáncer", "Leo", "Virgo",
    "Libra", "Escorpio", "Sagitario", "Capricornio", "Acuario", "Piscis",
]

SIGN_KEYS = [
    "aries", "tauro", "geminis", "cancer", "leo", "virgo",
    "libra", "escorpio", "sagitario", "capricornio", "acuario", "piscis",
]

PLANETS = {
    "sol": swe.SUN,
    "luna": swe.MOON,
    "mercurio": swe.MERCURY,
    "venus": swe.VENUS,
    "marte": swe.MARS,
    "jupiter": swe.JUPITER,
    "saturno": swe.SATURN,
    "urano": swe.URANUS,
    "neptuno": swe.NEPTUNE,
    "pluton": swe.PLUTO,
}

PLANET_LABELS = {
    "sol": "Sol", "luna": "Luna", "mercurio": "Mercurio", "venus": "Venus",
    "marte": "Marte", "jupiter": "Júpiter", "saturno": "Saturno",
    "urano": "Urano", "neptuno": "Neptuno", "pluton": "Plutón",
    "nodo_norte": "Nodo Norte", "nodo_sur": "Nodo Sur",
}

# Planetas para los que tiene sentido calcular "¿cuándo termina la retrogradación?"
# (los que la gente busca activamente: Mercurio, Venus, Marte).
PLANETAS_RETRO_RELEVANTES = ("mercurio", "venus", "marte")

# Orbe (tolerancia en grados) para considerar válido un aspecto.
MAJOR_ASPECTS = {
    0: "Conjunción",
    60: "Sextil",
    90: "Cuadratura",
    120: "Trígono",
    180: "Oposición",
}

FLAGS = swe.FLG_MOSEPH | swe.FLG_SPEED  # Moshier: no requiere archivos de efemérides


def sign_of(longitude):
    idx = int(longitude // 30) % 12
    degree = longitude % 30
    return SIGN_KEYS[idx], SIGN_NAMES[idx], degree


def julian_day_utc(dt):
    """dt: datetime.datetime en UTC. Se calcula al mediodía UTC del día indicado."""
    hour = dt.hour + dt.minute / 60 + dt.second / 3600
    return swe.julday(dt.year, dt.month, dt.day, hour)


def planet_position(jd, planet_id):
    xx, _ = swe.calc_ut(jd, planet_id, FLAGS)
    lon, lat, dist, lon_speed = xx[0], xx[1], xx[2], xx[3]
    sign_key, sign_name, degree = sign_of(lon)
    return {
        "longitude": lon,
        "sign_key": sign_key,
        "sign_name": sign_name,
        "degree_in_sign": round(degree, 2),
        "retrograde": lon_speed < 0,
        "speed": lon_speed,
    }


def moon_phase(sun_lon, moon_lon):
    elong = (moon_lon - sun_lon) % 360
    illumination = round((1 - math.cos(math.radians(elong))) / 2 * 100, 1)

    if elong < 10 or elong > 350:
        name = "Luna Nueva"
    elif elong < 80:
        name = "Luna creciente"
    elif elong < 100:
        name = "Cuarto Creciente"
    elif elong < 170:
        name = "Luna Gibosa creciente"
    elif elong < 190:
        name = "Luna Llena"
    elif elong < 260:
        name = "Luna Gibosa menguante"
    elif elong < 280:
        name = "Cuarto Menguante"
    else:
        name = "Luna menguante"

    waxing = elong < 180
    return {
        "phase_name": name,
        "illumination_pct": illumination,
        "elongation": round(elong, 2),
        "waxing": waxing,
    }


def angular_diff(a, b):
    d = abs(a - b) % 360
    return min(d, 360 - d)


def compute_aspects(positions):
    """positions: dict planeta -> info (con 'longitude'). Devuelve lista de aspectos válidos."""
    aspects = []
    keys = list(positions.keys())
    for i in range(len(keys)):
        for j in range(i + 1, len(keys)):
            p1, p2 = keys[i], keys[j]
            diff = angular_diff(positions[p1]["longitude"], positions[p2]["longitude"])
            orb = 8 if ("sol" in (p1, p2) or "luna" in (p1, p2)) else 6
            for angle, aspect_name in MAJOR_ASPECTS.items():
                delta = abs(diff - angle)
                if delta <= orb:
                    aspects.append({
                        "planeta1": p1,
                        "planeta2": p2,
                        "aspecto": aspect_name,
                        "angulo_exacto": angle,
                        "orbe": round(delta, 2),
                    })
                    break
    return aspects


def fecha_directo(planet_id, jd_inicio, max_days=150):
    """Busca hacia adelante, día por día desde jd_inicio, la primera fecha en
    que el planeta deja de estar retrógrado (velocidad >= 0). Es el dato real
    que la gente busca cuando pregunta "¿cuándo termina el Mercurio retrógrado?"."""
    for i in range(1, max_days + 1):
        jd = jd_inicio + i
        xx, _ = swe.calc_ut(jd, planet_id, FLAGS)
        if xx[3] >= 0:
            y, m, d,_h = swe.revjul(jd)
            return {"fecha": f"{y:04d}-{m:02d}-{d:02d}", "dias_restantes": i}
    return None


def compute_day(dt):
    """Calcula todo el panorama astronómico real para un datetime (UTC, se usa el mediodía)."""
    jd = julian_day_utc(dt)
    positions = {name: planet_position(jd, pid) for name, pid in PLANETS.items()}

    nodo_norte = planet_position(jd, swe.TRUE_NODE)
    nodo_sur_lon = (nodo_norte["longitude"] + 180) % 360
    sign_key, sign_name, degree = sign_of(nodo_sur_lon)
    nodo_sur = {
        "longitude": nodo_sur_lon,
        "sign_key": sign_key,
        "sign_name": sign_name,
        "degree_in_sign": round(degree, 2),
        "retrograde": nodo_norte["retrograde"],
        "speed": nodo_norte["speed"],
    }
    positions["nodo_norte"] = nodo_norte
    positions["nodo_sur"] = nodo_sur

    for name in PLANETAS_RETRO_RELEVANTES:
        if positions[name]["retrograde"]:
            positions[name]["fin_retrogrado"] = fecha_directo(PLANETS[name], jd)

    phase = moon_phase(positions["sol"]["longitude"], positions["luna"]["longitude"])
    aspects = compute_aspects({k: v for k, v in positions.items() if k in PLANETS})
    return {
        "fecha": dt.strftime("%Y-%m-%d"),
        "posiciones": positions,
        "fase_lunar": phase,
        "aspectos": aspects,
    }
