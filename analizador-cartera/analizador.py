#!/usr/bin/env python3
"""
Analizador diario de cartera — compara las posiciones reales (Balanz)
contra datos reales de mercado y genera un informe con senales y consejos.

Uso:
    export ANTHROPIC_API_KEY=sk-ant-...
    python analizador.py

Salida:
    informes/informe_AAAA-MM-DD.md   (informe del dia)
    historial/datos_AAAA-MM-DD.json  (snapshot de datos para tendencias)
"""

import json
import os
import sys
import urllib.request
from datetime import date
from pathlib import Path

import anthropic

BASE = Path(__file__).parent
DIR_INFORMES = BASE / "informes"
DIR_HISTORIAL = BASE / "historial"
ARCHIVO_CARTERA = BASE / "cartera.json"

MODELO = "claude-opus-5"


def http_json(url: str):
    """GET simple con timeout; devuelve JSON o None si falla."""
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "analizador-cartera/1.0"})
        with urllib.request.urlopen(req, timeout=15) as r:
            return json.loads(r.read().decode())
    except Exception as e:
        print(f"  [aviso] no se pudo obtener {url}: {e}", file=sys.stderr)
        return None


def obtener_datos_mercado() -> dict:
    """Datos reales del dia: cotizaciones del dolar (dolarapi.com, API publica argentina)."""
    datos = {"fecha": date.today().isoformat(), "dolares": {}}
    cotizaciones = http_json("https://dolarapi.com/v1/dolares")
    if cotizaciones:
        for c in cotizaciones:
            datos["dolares"][c.get("casa", "?")] = {
                "compra": c.get("compra"),
                "venta": c.get("venta"),
                "fecha": c.get("fechaActualizacion"),
            }
    # Brecha CCL vs oficial, si tenemos ambos
    try:
        oficial = datos["dolares"]["oficial"]["venta"]
        ccl = datos["dolares"]["contadoconliqui"]["venta"]
        datos["brecha_ccl_pct"] = round((ccl / oficial - 1) * 100, 2)
    except (KeyError, TypeError, ZeroDivisionError):
        datos["brecha_ccl_pct"] = None
    return datos


def cargar_historial(max_dias: int = 30) -> list:
    """Ultimos snapshots guardados, para que el analisis vea tendencias."""
    if not DIR_HISTORIAL.exists():
        return []
    archivos = sorted(DIR_HISTORIAL.glob("datos_*.json"))[-max_dias:]
    historial = []
    for a in archivos:
        try:
            historial.append(json.loads(a.read_text()))
        except Exception:
            pass
    return historial


def construir_prompt(cartera: dict, mercado: dict, historial: list) -> str:
    return f"""Sos un analista financiero especializado en el mercado argentino. Analiza mi cartera de fondos comunes de inversion contra los datos reales de mercado de hoy.

## Mi cartera (posiciones reales en Balanz)
{json.dumps(cartera, indent=2, ensure_ascii=False)}

## Datos reales de mercado de hoy
{json.dumps(mercado, indent=2, ensure_ascii=False)}

## Historial de dias anteriores (para detectar tendencias)
{json.dumps(historial, indent=2, ensure_ascii=False) if historial else "(primer dia de ejecucion, sin historial todavia)"}

## Tu tarea
Usa la busqueda web para verificar el contexto de HOY del mercado argentino: tasas (caucion, Lecaps, TNA de money market), riesgo pais, novedades del BCRA/Tesoro, y si es posible los valores de cuotaparte actuales de los fondos Balanz "Ahorro Corto Plazo Clase A" y "Balanz Ahorro en Dolares (Corporativo) Clase A". Despues genera un informe en Markdown, en espanol, con esta estructura exacta:

# Informe diario de cartera — {mercado['fecha']}

## 1. Estado de la cartera
Valor estimado de cada posicion y del total (en ARS y USD), comparado con el dia anterior si hay historial.

## 2. Semaforo de senales
Tabla con senal / estado (VERDE, AMARILLO o ROJO) / justificacion en una frase. Senales minimas: carry en pesos (tasa mensual vs devaluacion esperada), brecha cambiaria y su tendencia, momentum de cada fondo propio, contexto macro/politico del dia.

## 3. Novedades relevantes de hoy
2 a 4 puntos con lo que paso hoy que afecta (o no) a esta cartera, citando la fuente.

## 4. Recomendacion
Una de tres: MANTENER / AJUSTAR (que y cuanto) / ATENCION (que vigilar). Se concreto y justifica con los datos. Recorda las reglas del titular: no sobre-reaccionar a lo diario, decidir con tendencias.

## 5. Que mirar manana
Un punto concreto.

Reglas: no inventes datos — si no pudiste verificar algo, decilo. Los rendimientos pasados no garantizan resultados futuros y esto no es asesoramiento regulado; incluye esa aclaracion al pie en una linea."""


def analizar(prompt: str) -> str:
    client = anthropic.Anthropic()
    with client.messages.stream(
        model=MODELO,
        max_tokens=16000,
        tools=[{"type": "web_search_20260209", "name": "web_search", "max_uses": 8}],
        messages=[{"role": "user", "content": prompt}],
    ) as stream:
        respuesta = stream.get_final_message()
    if respuesta.stop_reason == "refusal":
        raise RuntimeError("El modelo declino la consulta (refusal). Reintentar mas tarde.")
    # pause_turn: el loop de herramientas del servidor puede pausar; continuamos
    mensajes = [{"role": "user", "content": prompt}]
    reintentos = 0
    while respuesta.stop_reason == "pause_turn" and reintentos < 5:
        mensajes.append({"role": "assistant", "content": respuesta.content})
        with anthropic.Anthropic().messages.stream(
            model=MODELO,
            max_tokens=16000,
            tools=[{"type": "web_search_20260209", "name": "web_search", "max_uses": 8}],
            messages=mensajes,
        ) as stream:
            respuesta = stream.get_final_message()
        reintentos += 1
    return "\n".join(b.text for b in respuesta.content if b.type == "text")


def main():
    if not os.environ.get("ANTHROPIC_API_KEY"):
        sys.exit("Falta la variable de entorno ANTHROPIC_API_KEY (conseguila en console.anthropic.com)")

    cartera = json.loads(ARCHIVO_CARTERA.read_text())
    print("Obteniendo datos reales de mercado...")
    mercado = obtener_datos_mercado()
    historial = cargar_historial()
    print(f"  Dolar oficial: {mercado['dolares'].get('oficial', {}).get('venta', 's/d')} | "
          f"CCL: {mercado['dolares'].get('contadoconliqui', {}).get('venta', 's/d')} | "
          f"Brecha: {mercado.get('brecha_ccl_pct', 's/d')}% | Historial: {len(historial)} dias")

    print("Consultando a Claude (con busqueda web del dia)...")
    informe = analizar(construir_prompt(cartera, mercado, historial))

    hoy = mercado["fecha"]
    DIR_INFORMES.mkdir(exist_ok=True)
    DIR_HISTORIAL.mkdir(exist_ok=True)
    ruta_informe = DIR_INFORMES / f"informe_{hoy}.md"
    ruta_informe.write_text(informe)
    (DIR_HISTORIAL / f"datos_{hoy}.json").write_text(
        json.dumps(mercado, indent=2, ensure_ascii=False)
    )
    print(f"\nListo: {ruta_informe}")


if __name__ == "__main__":
    main()
