# Sistema de Cobranza

## Backend

```
cd cobranza/server
npm install
npm start
```

Levanta la API en `http://localhost:3001`. Usa SQLite (`cobranza.db`, se crea sola en el primer arranque).

## Frontend

Abrir `cobranza/index.html` en el navegador (con el backend corriendo).

## Endpoints

- `GET /api/clientes` — lista clientes con cargos, pagos y saldo
- `POST /api/clientes` — crea cliente `{ nombre, telefono }`
- `DELETE /api/clientes/:id` — elimina cliente
- `POST /api/clientes/:id/cargos` — agrega cargo `{ concepto, monto }`
- `POST /api/clientes/:id/pagos` — agrega pago `{ monto }`
