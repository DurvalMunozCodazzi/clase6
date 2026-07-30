import json
import os
import urllib.error
import urllib.parse
import urllib.request

from flask import Flask, render_template, jsonify, request

from generar_horoscopo import generar, _resumen_astronomico
from core import SIGN_KEYS, SIGN_NAMES
from analitico import generar_analitico_dia, VERSION, CLAUDE_MODEL

app = Flask(__name__)

DOWNLOADS_DIR = os.path.expanduser("~/Downloads")


@app.route("/")
def index():
    return render_template("index.html", version=VERSION, claude_model=CLAUDE_MODEL)


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


NAVEGADOR_HEADERS = {
    "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
                  "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Accept": "application/json",
}


def _post_json(url, token, payload, timeout=15):
    body = json.dumps(payload).encode("utf-8")
    headers = dict(NAVEGADOR_HEADERS)
    headers["Content-Type"] = "application/json"
    headers["X-TDT-Token"] = token
    req = urllib.request.Request(url, data=body, method="POST", headers=headers)
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        return json.loads(resp.read().decode("utf-8"))


@app.route("/enviar_wordpress", methods=["POST"])
def enviar_wordpress_endpoint():
    data = request.json
    site_url = (data.get("site_url") or "").strip().rstrip("/")
    token = (data.get("token") or "").strip()
    payload = data.get("payload")

    if not site_url or not token:
        return jsonify({"ok": False, "error": "Falta la URL del sitio o el token."}), 400

    # Primero la URL "linda" (requiere enlaces permanentes no-simples en WP);
    # si da 404, WordPress también entiende esta otra forma siempre, sin
    # depender de esa configuración. El token va también en la URL (no solo
    # en la cabecera), igual que en la prueba manual que sí funciona.
    token_q = urllib.parse.quote(token)
    url_linda = f"{site_url}/wp-json/tirada-de-tarot/v1/horoscopo?token={token_q}"
    url_alternativa = f"{site_url}/index.php?rest_route=/tirada-de-tarot/v1/horoscopo&token={token_q}"

    try:
        resultado = _post_json(url_linda, token, payload)
        return jsonify({"ok": True, "resultado": resultado})
    except urllib.error.HTTPError as e:
        if e.code != 404:
            detalle = e.read().decode("utf-8", "ignore")
            return jsonify({"ok": False, "error": f"El sitio respondió con error {e.code}: {detalle}"}), 502
    except urllib.error.URLError as e:
        return jsonify({"ok": False, "error": f"No se pudo conectar: {e.reason}"}), 502

    try:
        resultado = _post_json(url_alternativa, token, payload)
        return jsonify({"ok": True, "resultado": resultado})
    except urllib.error.HTTPError as e:
        detalle = e.read().decode("utf-8", "ignore")
        return jsonify({"ok": False, "error": f"El sitio respondió con error {e.code}: {detalle}"}), 502
    except urllib.error.URLError as e:
        return jsonify({"ok": False, "error": f"No se pudo conectar: {e.reason}"}), 502


if __name__ == "__main__":
    app.run(debug=False, port=5050)
