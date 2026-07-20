import json
import os
import urllib.error
import urllib.parse
import urllib.request

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


NAVEGADOR_HEADERS = {
    "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
                  "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Accept": "application/json",
}


def _get_json(url, token, timeout=15):
    headers = dict(NAVEGADOR_HEADERS)
    headers["X-TDT-Token"] = token
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        return json.loads(resp.read().decode("utf-8"))


@app.route("/traer_significados", methods=["POST"])
def traer_significados_endpoint():
    body = request.json
    site_url = (body.get("site_url") or "").strip().rstrip("/")
    token = (body.get("token") or "").strip()

    if not site_url or not token:
        return jsonify({"error": "Falta la URL del sitio o el token."}), 400

    # Primero la URL "linda" (requiere enlaces permanentes no-simples en WP);
    # si da 404, WordPress también entiende esta otra forma siempre.
    token_q = urllib.parse.quote(token)
    url_linda = f"{site_url}/wp-json/tirada-de-tarot/v1/meanings?token={token_q}"
    url_alternativa = f"{site_url}/index.php?rest_route=/tirada-de-tarot/v1/meanings&token={token_q}"

    data = None
    try:
        data = _get_json(url_linda, token)
    except urllib.error.HTTPError as e:
        if e.code != 404:
            return jsonify({"error": f"El sitio respondió con error {e.code}"}), 502
    except urllib.error.URLError as e:
        return jsonify({"error": f"No se pudo conectar: {e.reason}"}), 502

    if data is None:
        try:
            data = _get_json(url_alternativa, token)
        except urllib.error.HTTPError as e:
            return jsonify({"error": f"El sitio respondió con error {e.code}"}), 502
        except urllib.error.URLError as e:
            return jsonify({"error": f"No se pudo conectar: {e.reason}"}), 502

    if not isinstance(data, dict):
        return jsonify({"error": "La respuesta del sitio no tiene el formato esperado."}), 502

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
