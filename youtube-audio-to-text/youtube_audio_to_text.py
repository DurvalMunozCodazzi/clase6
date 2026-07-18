#!/usr/bin/env python3
"""Descarga el audio de un video de YouTube y lo transcribe a texto usando Whisper."""

import argparse
import os
import subprocess
import tempfile
from typing import Optional

import whisper


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
    model = whisper.load_model(model_size)
    result = model.transcribe(audio_path, language=language)
    return result["text"].strip()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("url", help="URL del video de YouTube")
    parser.add_argument(
        "-o", "--output", default="transcripcion.txt",
        help="Archivo de salida (default: transcripcion.txt)",
    )
    parser.add_argument(
        "-m", "--model", default="base",
        choices=["tiny", "base", "small", "medium", "large"],
        help="Tamaño del modelo Whisper (default: base). Modelos más grandes son "
             "más precisos pero más lentos.",
    )
    parser.add_argument(
        "-l", "--language", default=None,
        help="Código de idioma (ej: es, en). Si se omite, Whisper lo detecta solo.",
    )
    parser.add_argument(
        "--keep-audio", action="store_true",
        help="Conservar el archivo de audio descargado en el directorio actual.",
    )
    parser.add_argument(
        "-b", "--browser", default=None,
        choices=["chrome", "safari", "firefox", "edge", "brave"],
        help="Usar las cookies de sesión de este navegador para evitar el "
             "bloqueo 'Sign in to confirm you're not a bot' de YouTube.",
    )
    parser.add_argument(
        "-n", "--node", default=None,
        help="Ruta al ejecutable de Node.js, necesaria para resolver el "
             "challenge JS de YouTube (ej: /Users/tu_usuario/Downloads/"
             "node-vXX.X.X-darwin-x64/bin/node).",
    )
    args = parser.parse_args()

    with tempfile.TemporaryDirectory() as tmp_dir:
        print(f"Descargando audio de: {args.url}")
        audio_path = download_audio(args.url, tmp_dir, args.browser, args.node)

        print(f"Transcribiendo con el modelo '{args.model}'...")
        text = transcribe_audio(audio_path, args.model, args.language)

        if args.keep_audio:
            final_audio_path = os.path.join(os.getcwd(), os.path.basename(audio_path))
            os.replace(audio_path, final_audio_path)
            print(f"Audio guardado en: {final_audio_path}")

    with open(args.output, "w", encoding="utf-8") as f:
        f.write(text)

    print(f"Transcripción guardada en: {args.output}")


if __name__ == "__main__":
    main()
