#!/bin/bash
# Doble clic en este archivo arranca la app y abre el navegador solo.
cd "$(dirname "$0")"
source venv/bin/activate

echo "Iniciando YouTube a Texto..."
echo "(Para cerrar la app: cerrá esta ventana o apretá Ctrl+C)"
echo ""

(sleep 2 && open http://localhost:5000) &
python app.py
