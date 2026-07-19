#!/bin/bash
# Doble clic en este archivo arranca la app y abre el navegador solo.
cd "$(dirname "$0")"
source venv/bin/activate

echo "Iniciando Horóscopo Diario..."
echo "(Para cerrar la app: cerrá esta ventana o apretá Ctrl+C)"
echo ""

(sleep 2 && open http://localhost:5050) &
python app.py
