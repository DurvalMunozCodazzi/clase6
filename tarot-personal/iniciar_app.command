#!/bin/bash
# Doble clic en este archivo instala lo necesario (solo la primera vez) y
# arranca la app, abriendo el navegador solo.
cd "$(dirname "$0")"

if [ ! -d "venv" ]; then
  echo "Primera vez: instalando (puede tardar un minuto)..."
  python3 -m venv venv
  source venv/bin/activate
  pip install -r requirements.txt
else
  source venv/bin/activate
fi

echo ""
echo "Iniciando Tarot Personal..."
echo "(Para cerrar la app: cerrá esta ventana o apretá Ctrl+C)"
echo ""

(sleep 2 && open http://localhost:5065) &
python app.py
