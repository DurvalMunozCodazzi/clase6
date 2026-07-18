#!/usr/bin/env python3
"""App web local para transcribir videos de YouTube. Uso: python app.py"""

import tempfile

from flask import Flask, render_template, request, send_file

from core import download_audio, transcribe_audio

app = Flask(__name__)

# Config fija para esta Mac. Ajustar si cambia la ruta de Node o el navegador.
DEFAULT_BROWSER = "chrome"
DEFAULT_NODE_PATH = "/Users/edgardomunozcodazzi/Downloads/node-v24.14.1-darwin-x64/bin/node"

_last_result = {"text": None}


@app.route("/", methods=["GET"])
def index():
    return render_template("index.html", text=None, error=None)


@app.route("/transcribir", methods=["POST"])
def transcribir():
    url = request.form.get("url", "").strip()
    model = request.form.get("model", "base")
    language = request.form.get("language", "").strip() or None

    if not url:
        return render_template("index.html", text=None, error="Pegá un link de YouTube.")

    try:
        with tempfile.TemporaryDirectory() as tmp_dir:
            audio_path = download_audio(url, tmp_dir, DEFAULT_BROWSER, DEFAULT_NODE_PATH)
            text = transcribe_audio(audio_path, model, language)
    except Exception as e:
        return render_template("index.html", text=None, error=f"Error: {e}")

    _last_result["text"] = text
    return render_template("index.html", text=text, error=None)


@app.route("/descargar")
def descargar():
    text = _last_result.get("text")
    if not text:
        return "No hay ninguna transcripción todavía.", 404

    path = tempfile.NamedTemporaryFile(
        mode="w", suffix=".txt", delete=False, encoding="utf-8"
    )
    path.write(text)
    path.close()
    return send_file(path.name, as_attachment=True, download_name="transcripcion.txt")


if __name__ == "__main__":
    app.run(debug=False, port=5000)
