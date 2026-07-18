# YouTube audio a texto

Descarga el audio de un video de YouTube y lo transcribe a texto usando
[yt-dlp](https://github.com/yt-dlp/yt-dlp) y [Whisper](https://github.com/openai/whisper)
(OpenAI). Incluye un script de línea de comandos (`youtube_audio_to_text.py`)
y una app web local (`app.py`) para pegar el link desde el navegador.

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

## Uso: app web local (recomendado para uso personal)

```bash
python app.py
```

Abrí `http://localhost:5000` en el navegador, pegá el link de YouTube y
apretá "Transcribir". El navegador, cookies y ruta de Node quedan
configurados como constantes al principio de `app.py` (`DEFAULT_BROWSER`,
`DEFAULT_NODE_PATH`) — ajustalas ahí si cambia la ruta de tu instalación de
Node o si usás otro navegador.

## Uso: línea de comandos

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
| `-b`, `--browser` | Navegador (`chrome`, `safari`, `firefox`, `edge`, `brave`) del que tomar cookies de sesión, para evitar el bloqueo "Sign in to confirm you're not a bot" de YouTube | ninguno |
| `-n`, `--node` | Ruta al ejecutable de Node.js, necesaria para que `yt-dlp` resuelva el challenge JS que protege los formatos de audio/video de YouTube | ninguno |

### Ejemplo con más precisión y en español

```bash
python youtube_audio_to_text.py "https://youtu.be/XXXXXXXXXXX" -m small -l es -o clase.txt -b chrome -n /Users/tu_usuario/Downloads/node-v24.14.1-darwin-x64/bin/node
```

### Sobre `--browser` y `--node`

YouTube protege sus videos con dos capas que suelen requerir estas opciones:

1. **Verificación de bot**: si `yt-dlp` recibe "Sign in to confirm you're not a bot", pasale `-b` con el navegador donde tenés una sesión de YouTube abierta.
2. **Challenge JS**: YouTube ofusca las URLs reales de audio/video con JavaScript. `yt-dlp` necesita un motor de JS (Node.js) para resolverlo. Si no tenés Node instalado, bajá el binario oficial precompilado (no requiere compilar nada):
   ```bash
   cd ~/Downloads
   curl -O https://nodejs.org/dist/v24.14.1/node-v24.14.1-darwin-x64.tar.gz
   tar -xzf node-v24.14.1-darwin-x64.tar.gz
   ```
   Y pasá la ruta al binario con `-n ~/Downloads/node-v24.14.1-darwin-x64/bin/node`.

## Notas

- Modelos más grandes (`small`, `medium`, `large`) son más precisos pero más
  lentos y usan más memoria. `base` suele ser un buen punto de partida.
- La primera vez que uses un modelo, Whisper lo descarga automáticamente
  (unos cientos de MB a un par de GB según el tamaño).
- Todo corre localmente: no se envía el audio a ningún servicio externo.
