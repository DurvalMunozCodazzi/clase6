# YouTube audio a texto

Script de línea de comandos que descarga el audio de un video de YouTube y lo
transcribe a texto usando [yt-dlp](https://github.com/yt-dlp/yt-dlp) y
[Whisper](https://github.com/openai/whisper) (OpenAI).

## Requisitos

- Python 3.9+
- [ffmpeg](https://ffmpeg.org/) instalado y disponible en el `PATH`
  - Linux: `sudo apt install ffmpeg`
  - macOS: `brew install ffmpeg`
  - Windows: `choco install ffmpeg` o descargarlo desde ffmpeg.org

## Instalación

```bash
cd youtube-audio-to-text
pip install -r requirements.txt
```

## Uso

```bash
python youtube_audio_to_text.py "https://www.youtube.com/watch?v=XXXXXXXXXXX"
```

Esto genera `transcripcion.txt` con el texto transcrito.

### Opciones

| Flag | Descripción | Default |
| --- | --- | --- |
| `-o`, `--output` | Archivo de salida | `transcripcion.txt` |
| `-m`, `--model` | Tamaño del modelo Whisper: `tiny`, `base`, `small`, `medium`, `large` | `base` |
| `-l`, `--language` | Código de idioma (ej: `es`, `en`). Si se omite, se detecta automáticamente | auto |
| `--keep-audio` | Conserva el mp3 descargado en el directorio actual | desactivado |

### Ejemplo con más precisión y en español

```bash
python youtube_audio_to_text.py "https://youtu.be/XXXXXXXXXXX" -m small -l es -o clase.txt
```

## Notas

- Modelos más grandes (`small`, `medium`, `large`) son más precisos pero más
  lentos y usan más memoria. `base` suele ser un buen punto de partida.
- La primera vez que uses un modelo, Whisper lo descarga automáticamente
  (unos cientos de MB a un par de GB según el tamaño).
- Todo corre localmente: no se envía el audio a ningún servicio externo.
