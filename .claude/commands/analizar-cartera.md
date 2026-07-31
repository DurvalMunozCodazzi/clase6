---
description: Analiza la cartera Balanz contra datos reales del mercado y genera el informe del dia
---

Sos el analizador de la cartera de inversiones del usuario (fondos Balanz, Argentina). Hacé lo siguiente en orden:

1. Leé `analizador-cartera/cartera.json` — contiene las posiciones reales (BCAHA Ahorro Corto Plazo en ARS y BAHUSDA Corporativo en USD) y las reglas del titular.
2. Leé los últimos informes en `analizador-cartera/informes/` (si existen) para conocer la tendencia y no repetir novedades ya reportadas.
3. Buscá en la web los datos REALES de hoy del mercado argentino: dólar oficial/MEP/CCL/blue y brecha, tasas (caución, Lecaps, TNA money market), riesgo país, novedades de BCRA/Tesoro/política económica, y si es posible los valores de cuotaparte actuales de los fondos Balanz "Ahorro Corto Plazo Clase A" y "Balanz Ahorro en Dólares (Corporativo) Clase A".
4. Escribí el informe en `analizador-cartera/informes/informe_AAAA-MM-DD.md` (fecha de hoy) con esta estructura: (1) Estado de la cartera — valor estimado de cada posición y total, comparado con el informe anterior; (2) Semáforo de señales — tabla señal/VERDE-AMARILLO-ROJO/justificación: carry en pesos, brecha cambiaria y tendencia, momentum de cada fondo, contexto macro del día; (3) Novedades relevantes de hoy con fuentes; (4) Recomendación: MANTENER / AJUSTAR (qué y cuánto) / ATENCIÓN (qué vigilar) — sin sobre-reaccionar a variaciones diarias; (5) Qué mirar mañana. Al pie, una línea aclarando que no es asesoramiento financiero regulado.
5. Guardá un snapshot de datos en `analizador-cartera/historial/datos_AAAA-MM-DD.json` (dólares, brecha, tasas, riesgo país).
6. Commiteá ambos archivos a la rama `main` y pusheá.
7. Terminá con un resumen de 2-3 frases: semáforo y recomendación del día.

No inventes datos: si algo no se pudo verificar, decilo en el informe. Si el mercado no operó hoy (feriado), generá un informe corto indicándolo.
