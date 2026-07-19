"""
Genera el horóscopo diario completo (hoy y mañana, los 12 signos) combinando:
  - Datos astronómicos reales calculados con Swiss Ephemeris (core.py)
  - Interpretación astrológica tradicional apoyada en esos datos (interpretar.py)

Uso:
    python generar_horoscopo.py            -> imprime resumen legible en pantalla
    python generar_horoscopo.py --json out.json  -> además guarda el JSON para
                                                     importar en el panel de WordPress
"""
import argparse
import json
from datetime import datetime, timedelta, timezone

from core import compute_day, SIGN_KEYS, SIGN_NAMES, PLANET_LABELS
from interpretar import generar_horoscopos_dia


def _resumen_astronomico(day_data):
    lineas = [f"Panorama astronómico real — {day_data['fecha']}"]
    for key, pos in day_data["posiciones"].items():
        retro = " [RETRÓGRADO]" if pos["retrograde"] else ""
        lineas.append(
            f"  {PLANET_LABELS[key]:9s}: {pos['sign_name']:12s} "
            f"{pos['degree_in_sign']:5.2f}°{retro}"
        )
    fase = day_data["fase_lunar"]
    lineas.append(f"  Fase lunar: {fase['phase_name']} ({fase['illumination_pct']}% iluminada)")
    lineas.append("  Aspectos activos:")
    for a in day_data["aspectos"]:
        lineas.append(
            f"    {PLANET_LABELS[a['planeta1']]} {a['aspecto']} {PLANET_LABELS[a['planeta2']]} "
            f"(orbe {a['orbe']}°)"
        )
    return "\n".join(lineas)


def generar(fecha_base_utc=None):
    if fecha_base_utc is None:
        fecha_base_utc = datetime.now(timezone.utc)

    hoy = compute_day(fecha_base_utc)
    manana = compute_day(fecha_base_utc + timedelta(days=1))

    resultado = {
        "generado_en_utc": fecha_base_utc.isoformat(),
        "hoy": {
            "fecha": hoy["fecha"],
            "astronomia": hoy,
            "signos": generar_horoscopos_dia(hoy),
        },
        "manana": {
            "fecha": manana["fecha"],
            "astronomia": manana,
            "signos": generar_horoscopos_dia(manana),
        },
    }
    return resultado


def main():
    parser = argparse.ArgumentParser(description="Genera el horóscopo diario real + interpretado.")
    parser.add_argument("--json", metavar="ARCHIVO", help="Guarda el resultado completo en un JSON")
    args = parser.parse_args()

    resultado = generar()

    print(_resumen_astronomico(resultado["hoy"]["astronomia"]))
    print()
    print(_resumen_astronomico(resultado["manana"]["astronomia"]))
    print()

    for dia_key, etiqueta in (("hoy", "HOY"), ("manana", "MAÑANA")):
        print(f"===== Horóscopo de {etiqueta} ({resultado[dia_key]['fecha']}) =====")
        for sk in SIGN_KEYS:
            nombre = SIGN_NAMES[SIGN_KEYS.index(sk)]
            print(f"\n♦ {nombre}")
            print(resultado[dia_key]["signos"][sk])
        print()

    if args.json:
        with open(args.json, "w", encoding="utf-8") as f:
            json.dump(resultado, f, ensure_ascii=False, indent=2)
        print(f"\nGuardado en {args.json}")


if __name__ == "__main__":
    main()
