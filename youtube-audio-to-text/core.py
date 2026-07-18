"""Lógica compartida: descargar audio de YouTube y transcribirlo con Whisper."""

import os
import subprocess
from typing import Optional

import whisper

_model_cache = {}


def download_audio(url: str, output_dir: str, browser: Optional[str], node_path: Optional[str]) -> str:
    output_template = os.path.join(output_dir, "%(id)s.%(ext)s")
    cmd = [
        "yt-dlp",
        "-f", "bestaudio/best",
        "-x", "--audio-format", "mp3", "--audio-quality", "192K",
        "-o", output_template,
        "--quiet", "--no-warnings",
    ]
    if browser:
        cmd += ["--cookies-from-browser", browser]
    if node_path:
        cmd += ["--js-runtimes", f"node:{node_path}", "--remote-components", "ejs:github"]
    cmd.append(url)

    subprocess.run(cmd, check=True)

    mp3_files = [f for f in os.listdir(output_dir) if f.endswith(".mp3")]
    if not mp3_files:
        raise RuntimeError("yt-dlp no generó ningún archivo de audio")
    return os.path.join(output_dir, mp3_files[0])


def transcribe_audio(audio_path: str, model_size: str, language: Optional[str]) -> str:
    if model_size not in _model_cache:
        _model_cache[model_size] = whisper.load_model(model_size)
    result = _model_cache[model_size].transcribe(audio_path, language=language)
    return result["text"].strip()
