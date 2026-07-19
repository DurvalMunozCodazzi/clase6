import json
import os

from flask import Flask, render_template, jsonify

from generar_horoscopo import generar, _resumen_astronomico
from core import SIGN_KEYS, SIGN_NAMES
from analitico import generar_analitico_dia

app = Flask(__name__)

DOWNLOADS_DIR = os.path.expanduser("~/Downloads")


@app.route("/")
def index():
    return render_template("index.html")


@app.route("/generar", methods=["POST"])
def generar_endpoint():
    resultado = generar()

    signos_ordenados = list(zip(SIGN_KEYS, SIGN_NAMES))

    wp_import = {
        "hoy": {
            "fecha": resultado["hoy"]["fecha"],
            "panorama": resultado["hoy"]["panorama"],
            "signos": resultado["hoy"]["signos"],
        },
        "manana": {
            "fecha": resultado["manana"]["fecha"],
            "panorama": resultado["manana"]["panorama"],
            "signos": resultado["manana"]["signos"],
        },
    }

    os.makedirs(DOWNLOADS_DIR, exist_ok=True)
    out_path = os.path.join(DOWNLOADS_DIR, f"horoscopo_{resultado['hoy']['fecha']}.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(resultado, f, ensure_ascii=False, indent=2)

    return jsonify({
        "astronomia_hoy": _resumen_astronomico(resultado["hoy"]["astronomia"]),
        "astronomia_manana": _resumen_astronomico(resultado["manana"]["astronomia"]),
        "signos": signos_ordenados,
        "hoy": resultado["hoy"]["signos"],
        "manana": resultado["manana"]["signos"],
        "fecha_hoy": resultado["hoy"]["fecha"],
        "fecha_manana": resultado["manana"]["fecha"],
        "wp_import_json": json.dumps(wp_import, ensure_ascii=False, indent=2),
        "archivo_guardado": out_path,
    })


@app.route("/generar_analitico", methods=["POST"])
def generar_analitico_endpoint():
    resultado = generar()

    hoy_signos = generar_analitico_dia(
        resultado["hoy"]["astronomia"], SIGN_KEYS, SIGN_NAMES
    )
    manana_signos = generar_analitico_dia(
        resultado["manana"]["astronomia"], SIGN_KEYS, SIGN_NAMES
    )

    signos_ordenados = list(zip(SIGN_KEYS, SIGN_NAMES))

    wp_import = {
        "hoy": {
            "fecha": resultado["hoy"]["fecha"],
            "panorama": resultado["hoy"]["panorama"],
            "signos": hoy_signos,
        },
        "manana": {
            "fecha": resultado["manana"]["fecha"],
            "panorama": resultado["manana"]["panorama"],
            "signos": manana_signos,
        },
    }

    os.makedirs(DOWNLOADS_DIR, exist_ok=True)
    out_path = os.path.join(DOWNLOADS_DIR, f"horoscopo_analitico_{resultado['hoy']['fecha']}.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(wp_import, f, ensure_ascii=False, indent=2)

    return jsonify({
        "astronomia_hoy": _resumen_astronomico(resultado["hoy"]["astronomia"]),
        "astronomia_manana": _resumen_astronomico(resultado["manana"]["astronomia"]),
        "signos": signos_ordenados,
        "hoy": hoy_signos,
        "manana": manana_signos,
        "fecha_hoy": resultado["hoy"]["fecha"],
        "fecha_manana": resultado["manana"]["fecha"],
        "wp_import_json": json.dumps(wp_import, ensure_ascii=False, indent=2),
        "archivo_guardado": out_path,
    })


if __name__ == "__main__":
    app.run(debug=False, port=5050)
