#!/usr/bin/env python3
"""App web local para transcribir videos de YouTube. Uso: python app.py"""

import os
import tempfile

from flask import Flask, render_template, request

from core import download_audio, transcribe_audio

app = Flask(__name__)

# Config fija para esta Mac. Ajustar si cambia la ruta de Node o el navegador.
DEFAULT_BROWSER = "chrome"
DEFAULT_NODE_PATH = "/Users/edgardomunozcodazzi/Downloads/node-v24.14.1-darwin-x64/bin/node"
DOWNLOADS_DIR = os.path.expanduser("~/Downloads")


@app.route("/", methods=["GET"])
def index():
    return render_template("index.html", text=None, error=None, saved_path=None)


@app.route("/transcribir", methods=["POST"])
def transcribir():
    url = request.form.get("url", "").strip()
    model = request.form.get("model", "base")
    language = request.form.get("language", "").strip() or None

    if not url:
        return render_template("index.html", text=None, error="Pegá un link de YouTube.", saved_path=None)

    try:
        with tempfile.TemporaryDirectory() as tmp_dir:
            audio_path = download_audio(url, tmp_dir, DEFAULT_BROWSER, DEFAULT_NODE_PATH)
            video_id = os.path.splitext(os.path.basename(audio_path))[0]
            text = transcribe_audio(audio_path, model, language)
    except Exception as e:
        return render_template("index.html", text=None, error=f"Error: {e}", saved_path=None)

    saved_path = os.path.join(DOWNLOADS_DIR, f"transcripcion_{video_id}.txt")
    with open(saved_path, "w", encoding="utf-8") as f:
        f.write(text)

    return render_template("index.html", text=text, error=None, saved_path=saved_path)


if __name__ == "__main__":
    app.run(debug=False, port=5000)
