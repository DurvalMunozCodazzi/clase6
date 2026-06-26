# Reglas de negocio — Luna Workspace

## Características a NO mencionar
- **Gantt**: eliminado del producto. No mencionarlo en ningún material de marketing, comparativas, planes ni documentación dirigida al cliente.

## Características a DESTACAR
- **Analítica**: es la diferencia clave frente a la competencia. Siempre debe aparecer destacada en comparativas, planes y materiales de venta.
- **WhatsApp nativo**: ningún competidor lo tiene. Siempre mencionarlo como exclusividad.
- **Datos en tu servidor**: argumento principal de privacidad vs competencia SaaS.
- **Precio fijo por sitio** (no por usuario): diferenciador de precio clave.

## Nombres y versiones
- Nombre del plugin: **Luna Workspace**
- Versión actual: **11.1.3**
- Autor: **Web Sobre Ruedas**
- Plugin URI: **https://websobreruedas.com**
- Dominio cliente de referencia: **misioneslahermosa.com**

## Seguridad
- Nunca hardcodear HMAC secrets ni claves privadas en el código distribuido.
- El sistema usa RSA asimétrico: clave privada solo en el servidor de licencias, clave pública en el plugin.
- `luna-setup.php` siempre excluido del ZIP de distribución.

## Licencias
- Las licencias gratuitas requieren registro manual: OTP → verificado → Durval revisa y envía la clave por email. NUNCA generación automática.
- WhatsApp de soporte: 5491153283558 (CallMeBot, apikey 6291539)
