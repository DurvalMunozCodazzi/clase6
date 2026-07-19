import json
import os

from flask import Flask, render_template, jsonify, request

import cards_data
from cards_data import CARD_NAMES, CARDS_BY_ID
from spreads import SPREADS, tirar_digital
from generar_lectura import generar_lectura_puntual, generar_lectura_ampliada

app = Flask(__name__)

DOWNLOADS_DIR = os.path.expanduser("~/Downloads")


@app.route("/")
def index():
    return render_template(
        "index.html", card_names=CARD_NAMES, spreads=SPREADS,
        hay_curados=cards_data.hay_significados_curados(),
    )


@app.route("/importar_significados", methods=["POST"])
def importar_significados_endpoint():
    raw = request.json.get("json", "")
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        return jsonify({"error": "Ese texto no es un JSON válido."}), 400
    if not isinstance(data, dict):
        return jsonify({"error": "El JSON tiene que ser un objeto (carta -> significados)."}), 400

    with open(cards_data.CURATED_PATH, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    cards_data.recargar_curados()

    return jsonify({"ok": True, "cartas": len(data)})


@app.route("/tirar_digital", methods=["POST"])
def tirar_digital_endpoint():
    spread_key = request.json.get("spread")
    if spread_key not in SPREADS:
        return jsonify({"error": "Spread inválido"}), 400
    tirada = tirar_digital(spread_key)
    return jsonify({"tirada": tirada})


@app.route("/generar_lectura", methods=["POST"])
def generar_lectura_endpoint():
    data = request.json
    spread_key = data.get("spread")
    tirada_in = data.get("tirada", [])

    if spread_key not in SPREADS:
        return jsonify({"error": "Spread inválido"}), 400

    posiciones = {p["key"]: p for p in SPREADS[spread_key]}
    tirada = []
    for item in tirada_in:
        pos = posiciones.get(item["position_key"])
        if not pos:
            return jsonify({"error": f"Posición inválida: {item.get('position_key')}"}), 400
        tirada.append({
            "position": pos,
            "card_id": item["card_id"],
            "reversed": bool(item.get("reversed")),
        })

    if spread_key == "puntual":
        tema = (data.get("tema") or "").strip()
        if not tema:
            return jsonify({"error": "Falta el tema de la consulta"}), 400
        texto = generar_lectura_puntual(tema, tirada)
    else:
        texto = generar_lectura_ampliada(tirada)

    os.makedirs(DOWNLOADS_DIR, exist_ok=True)
    from datetime import datetime
    marca = datetime.now().strftime("%Y-%m-%d_%H-%M")
    out_path = os.path.join(DOWNLOADS_DIR, f"lectura_tarot_{spread_key}_{marca}.txt")
    with open(out_path, "w", encoding="utf-8") as f:
        if spread_key == "puntual":
            f.write(f"Tema: {data.get('tema', '')}\n\n")
        for item in tirada:
            nombre = CARDS_BY_ID[item["card_id"]]["name"]
            f.write(f"{item['position']['label']}: {nombre} "
                     f"({'invertida' if item['reversed'] else 'derecha'})\n")
        f.write("\n" + texto)

    return jsonify({"texto": texto, "archivo_guardado": out_path})


if __name__ == "__main__":
    app.run(debug=False, port=5065)
