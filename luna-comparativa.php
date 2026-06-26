<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Luna Workspace — Comparativa 2026</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --dark:    #050810;
    --dark2:   #0d1224;
    --dark3:   #161d38;
    --acc:     #5b6af0;
    --acc2:    #7c8af7;
    --green:   #22c55e;
    --red:     #ef4444;
    --yellow:  #f59e0b;
    --text:    #e2e8f0;
    --text2:   #94a3b8;
    --text3:   #64748b;
    --luna:    #5b6af0;
  }

  body {
    font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
    background: var(--dark);
    color: var(--text);
    line-height: 1.6;
    overflow-x: hidden;
  }

  /* ── HERO ── */
  .hero {
    background: linear-gradient(135deg, #050810 0%, #0d1224 50%, #1a1060 100%);
    padding: 80px 24px 100px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(91,106,240,.18) 0%, transparent 70%);
    pointer-events: none;
  }
  .hero-badge {
    display: inline-block;
    background: rgba(91,106,240,.15);
    border: 1px solid rgba(91,106,240,.4);
    color: var(--acc2);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 6px 18px;
    border-radius: 999px;
    margin-bottom: 28px;
  }
  .hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -1px;
    margin-bottom: 20px;
  }
  .hero h1 span { color: var(--acc2); }
  .hero p {
    font-size: clamp(1rem, 2vw, 1.25rem);
    color: var(--text2);
    max-width: 640px;
    margin: 0 auto 40px;
  }
  .hero-cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--acc);
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    padding: 14px 32px;
    border-radius: 10px;
    text-decoration: none;
    transition: background .2s, transform .2s;
    box-shadow: 0 4px 24px rgba(91,106,240,.4);
  }
  .hero-cta:hover { background: var(--acc2); transform: translateY(-2px); }

  /* ── SECCIÓN ── */
  section { padding: 80px 24px; }
  .container { max-width: 1100px; margin: 0 auto; }
  .section-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--acc2);
    margin-bottom: 12px;
  }
  .section-title {
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 800;
    margin-bottom: 12px;
  }
  .section-sub {
    color: var(--text2);
    font-size: 1.05rem;
    margin-bottom: 48px;
    max-width: 600px;
  }

  /* ── PILLS DIFERENCIALES ── */
  .pills { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 64px; }
  .pill {
    background: var(--dark2);
    border: 1px solid var(--dark3);
    border-radius: 999px;
    padding: 8px 20px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text);
  }
  .pill .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); flex-shrink: 0; }

  /* ── CARDS DESTACADAS ── */
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 64px;
  }
  .card {
    background: var(--dark2);
    border: 1px solid var(--dark3);
    border-radius: 16px;
    padding: 28px 24px;
    transition: border-color .2s, transform .2s;
  }
  .card:hover { border-color: var(--acc); transform: translateY(-4px); }
  .card.highlight {
    background: linear-gradient(135deg, #0d1224, #1a1060);
    border-color: var(--acc);
    box-shadow: 0 0 32px rgba(91,106,240,.15);
  }
  .card-icon { font-size: 32px; margin-bottom: 16px; }
  .card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
  .card p { color: var(--text2); font-size: 13.5px; }

  /* ── TABLA COMPARATIVA ── */
  .table-wrap { overflow-x: auto; }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    min-width: 700px;
  }
  thead tr { background: var(--dark3); }
  thead th {
    padding: 16px 20px;
    text-align: center;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: .5px;
  }
  thead th:first-child { text-align: left; }
  thead th.luna-col {
    background: linear-gradient(135deg, #1a1060, #0d1224);
    color: var(--acc2);
    border-top: 2px solid var(--acc);
    border-radius: 8px 8px 0 0;
  }
  tbody tr { border-bottom: 1px solid var(--dark3); transition: background .15s; }
  tbody tr:hover { background: rgba(255,255,255,.02); }
  tbody tr:last-child { border-bottom: none; }
  tbody td {
    padding: 14px 20px;
    text-align: center;
    color: var(--text2);
    font-size: 13.5px;
  }
  tbody td:first-child {
    text-align: left;
    color: var(--text);
    font-weight: 600;
  }
  td.luna-col {
    background: rgba(91,106,240,.06);
    border-left: 1px solid rgba(91,106,240,.2);
    border-right: 1px solid rgba(91,106,240,.2);
    color: var(--text) !important;
    font-weight: 600 !important;
  }
  td.luna-col.last { border-radius: 0 0 8px 8px; border-bottom: 2px solid var(--acc); }
  .yes { color: var(--green); font-size: 18px; }
  .no  { color: var(--red);   font-size: 18px; }
  .partial { color: var(--yellow); font-size: 13px; font-weight: 600; }
  .bold-green { color: var(--green); font-weight: 700; font-size: 14px; }

  /* ── PRECIO COMPARATIVO ── */
  .pricing-bg { background: var(--dark2); }
  .pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
  }
  .price-card {
    background: var(--dark);
    border: 1px solid var(--dark3);
    border-radius: 14px;
    padding: 24px 20px;
    text-align: center;
  }
  .price-card.luna-price {
    border-color: var(--acc);
    background: linear-gradient(135deg, #0d1224, #1a1060);
    box-shadow: 0 0 32px rgba(91,106,240,.2);
    transform: scale(1.05);
  }
  .price-card .brand { font-size: 13px; font-weight: 700; color: var(--text2); margin-bottom: 8px; }
  .price-card.luna-price .brand { color: var(--acc2); }
  .price-card .amount {
    font-size: 2rem;
    font-weight: 900;
    color: var(--red);
    line-height: 1;
    margin-bottom: 4px;
  }
  .price-card.luna-price .amount { color: var(--green); }
  .price-card .period { font-size: 11px; color: var(--text3); margin-bottom: 12px; }
  .price-card .note { font-size: 12px; color: var(--text2); }
  .price-card .savings {
    display: inline-block;
    background: rgba(34,197,94,.12);
    color: var(--green);
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    margin-top: 8px;
  }
  .pricing-note {
    text-align: center;
    color: var(--text2);
    font-size: 13px;
    margin-top: 28px;
    padding: 16px;
    background: rgba(91,106,240,.08);
    border: 1px solid rgba(91,106,240,.2);
    border-radius: 10px;
  }
  .pricing-note strong { color: var(--text); }

  /* ── WA HIGHLIGHT ── */
  .wa-block {
    background: linear-gradient(135deg, #0a1f0a, #0d2a0d);
    border: 1px solid #22c55e44;
    border-radius: 20px;
    padding: 48px 40px;
    display: flex;
    gap: 40px;
    align-items: center;
    flex-wrap: wrap;
  }
  .wa-block .wa-icon { font-size: 72px; flex-shrink: 0; }
  .wa-block h2 { font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; margin-bottom: 12px; }
  .wa-block p { color: var(--text2); font-size: 15px; max-width: 520px; }
  .wa-badge {
    display: inline-block;
    background: rgba(34,197,94,.12);
    border: 1px solid rgba(34,197,94,.3);
    color: var(--green);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 4px 14px;
    border-radius: 999px;
    margin-bottom: 14px;
  }

  /* ── PLANES ── */
  .plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
  }
  .plan-card {
    background: var(--dark2);
    border: 1px solid var(--dark3);
    border-radius: 16px;
    padding: 28px 22px;
    position: relative;
    overflow: hidden;
  }
  .plan-card.popular {
    border-color: var(--acc);
    box-shadow: 0 0 32px rgba(91,106,240,.15);
  }
  .plan-badge {
    position: absolute;
    top: 14px;
    right: 14px;
    background: var(--acc);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 3px 10px;
    border-radius: 999px;
  }
  .plan-name { font-size: 13px; font-weight: 700; color: var(--text2); margin-bottom: 8px; }
  .plan-price {
    font-size: 2.2rem;
    font-weight: 900;
    color: var(--text);
    line-height: 1;
    margin-bottom: 4px;
  }
  .plan-price span { font-size: 14px; font-weight: 400; color: var(--text3); }
  .plan-users { font-size: 13px; color: var(--text2); margin: 12px 0 16px; }
  .plan-features { list-style: none; }
  .plan-features li {
    font-size: 13px;
    color: var(--text2);
    padding: 5px 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .plan-features li::before { content: '✓'; color: var(--green); font-weight: 700; flex-shrink: 0; }
  .plan-cta {
    display: block;
    width: 100%;
    margin-top: 20px;
    padding: 11px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    border: 1.5px solid var(--acc);
    background: transparent;
    color: var(--acc2);
    font-family: inherit;
    transition: background .2s, color .2s, transform .15s;
  }
  .plan-cta:hover { background: var(--acc); color: #fff; transform: translateY(-1px); }
  .plan-card.popular .plan-cta { background: var(--acc); color: #fff; }
  .plan-card.popular .plan-cta:hover { background: var(--acc2); }

  /* ── DATA PRIVACY ── */
  .privacy-block {
    background: linear-gradient(135deg, #0a0a1f, #0d0d2a);
    border: 1px solid rgba(91,106,240,.3);
    border-radius: 20px;
    padding: 48px 40px;
    text-align: center;
  }
  .privacy-block h2 { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; margin-bottom: 16px; }
  .privacy-block p { color: var(--text2); font-size: 15px; max-width: 600px; margin: 0 auto 28px; }
  .privacy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-top: 32px;
    text-align: left;
  }
  .privacy-item {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px;
    padding: 20px 18px;
  }
  .privacy-item .icon { font-size: 24px; margin-bottom: 10px; }
  .privacy-item h4 { font-size: 13px; font-weight: 700; margin-bottom: 6px; }
  .privacy-item p { font-size: 12.5px; color: var(--text3); margin: 0; }

  /* ── MODAL ── */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5,8,16,.85);
    backdrop-filter: blur(6px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: var(--dark2);
    border: 1px solid rgba(91,106,240,.3);
    border-radius: 16px;
    padding: 40px;
    width: 100%;
    max-width: 480px;
    position: relative;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
    animation: modal-in .25s ease;
  }
  @keyframes modal-in {
    from { opacity:0; transform:translateY(20px) scale(.97); }
    to   { opacity:1; transform:translateY(0)  scale(1); }
  }
  .modal-close {
    position: absolute;
    top: 16px; right: 20px;
    background: none;
    border: none;
    color: var(--text3);
    font-size: 22px;
    cursor: pointer;
    line-height: 1;
    transition: color .2s;
  }
  .modal-close:hover { color: var(--text); }
  .modal h3 {
    font-size: 1.4rem;
    font-weight: 800;
    margin-bottom: 6px;
  }
  .modal-sub {
    color: var(--text2);
    font-size: 0.88rem;
    margin-bottom: 28px;
  }
  .form-group {
    margin-bottom: 16px;
  }
  .form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text2);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: .5px;
  }
  .form-group input,
  .form-group select {
    width: 100%;
    background: var(--dark3);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    padding: 10px 14px;
    outline: none;
    transition: border-color .2s;
  }
  .form-group input:focus,
  .form-group select:focus { border-color: var(--acc); }
  .form-group input::placeholder { color: var(--text3); }
  .phone-row {
    display: flex;
    gap: 8px;
  }
  .phone-row select { width: 160px; flex-shrink: 0; }
  .phone-row input  { flex: 1; }
  .form-group select option { background: #1a2040; }
  .modal-submit {
    width: 100%;
    margin-top: 8px;
    background: var(--acc);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    padding: 13px;
    cursor: pointer;
    transition: background .2s, transform .2s;
    box-shadow: 0 4px 20px rgba(91,106,240,.4);
  }
  .modal-submit:hover:not(:disabled) { background: var(--acc2); transform: translateY(-1px); }
  .modal-submit:disabled { opacity: .6; cursor: default; }
  .modal-msg {
    margin-top: 14px;
    font-size: 0.88rem;
    text-align: center;
    min-height: 20px;
  }
  .modal-msg.ok  { color: var(--green); }
  .modal-msg.err { color: var(--red); }

  /* plan selector */
  .plan-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 16px;
  }
  .plan-btn {
    background: var(--dark3);
    border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 10px;
    color: var(--text2);
    cursor: pointer;
    padding: 10px 8px;
    text-align: center;
    transition: border-color .2s, background .2s, color .2s;
    font-size: 0.82rem;
    font-family: inherit;
  }
  .plan-btn strong { display: block; font-size: 0.95rem; color: var(--text); margin-bottom: 2px; }
  .plan-btn .plan-price { font-size: 0.78rem; color: var(--text3); }
  .plan-btn:hover { border-color: var(--acc); }
  .plan-btn.selected {
    border-color: var(--acc);
    background: rgba(91,106,240,.15);
    color: var(--text);
  }
  .plan-btn.selected .plan-price { color: var(--acc2); }
  .phone-hint { font-size: 0.75rem; color: var(--text3); margin-top: 4px; }

  /* ── CTA FINAL ── */
  .cta-section {
    text-align: center;
    padding: 100px 24px;
    background: linear-gradient(180deg, var(--dark) 0%, #0d0d2a 100%);
  }
  .cta-section h2 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; margin-bottom: 16px; }
  .cta-section p { color: var(--text2); font-size: 1.1rem; margin-bottom: 40px; }
  .cta-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
  .btn-primary {
    background: var(--acc);
    color: #fff;
    font-weight: 700;
    font-size: 16px;
    padding: 14px 32px;
    border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 4px 24px rgba(91,106,240,.4);
    transition: background .2s, transform .2s;
  }
  .btn-primary:hover { background: var(--acc2); transform: translateY(-2px); }
  .btn-ghost {
    background: transparent;
    color: var(--text2);
    font-weight: 600;
    font-size: 16px;
    padding: 14px 32px;
    border-radius: 10px;
    text-decoration: none;
    border: 1px solid var(--dark3);
    transition: border-color .2s, color .2s;
  }
  .btn-ghost:hover { border-color: var(--acc); color: var(--text); }

  /* ── FOOTER ── */
  footer {
    background: var(--dark2);
    border-top: 1px solid var(--dark3);
    text-align: center;
    padding: 24px;
    font-size: 13px;
    color: var(--text3);
  }
  footer a { color: var(--text2); text-decoration: none; }
  footer a:hover { color: var(--text); }

  /* ── DIVIDER ── */
  .divider { border: none; border-top: 1px solid var(--dark3); margin: 0; }

  /* ── LABEL LUNA EN TABLA ── */
  .luna-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(91,106,240,.15);
    border: 1px solid rgba(91,106,240,.35);
    color: var(--acc2);
    font-size: 12px;
    font-weight: 800;
    padding: 3px 12px;
    border-radius: 999px;
  }
</style>
</head>
<body>

<!-- ══════════════════════ HERO ══════════════════════ -->
<div class="hero">
  <div class="container">
    <div class="hero-badge">🌙 Comparativa 2026 — Edición Actualizada</div>
    <h1>Tu pizarra colaborativa.<br><span>Tus datos. Tu servidor.</span></h1>
    <p>Luna Workspace hace lo que Trello, Asana y Monday cobran una fortuna — instalado directamente en tu WordPress, con notificaciones por WhatsApp incluidas.</p>
    <a href="https://websobreruedas.com" class="hero-cta">
      Empezar gratis →
    </a>
  </div>
</div>

<!-- ══════════════════════ DIFERENCIADORES ══════════════════════ -->
<section>
  <div class="container">
    <div class="section-label">Por qué Luna</div>
    <h2 class="section-title">Lo que ningún SaaS te puede ofrecer</h2>
    <p class="section-sub">Las grandes plataformas te cobran por usuario, guardan tus datos en sus servidores y no hablan el idioma de tu equipo.</p>

    <div class="cards-grid">
      <div class="card">
        <div class="card-icon">🏠</div>
        <h3>100% en tu servidor</h3>
        <p>Se instala en tu WordPress. Tus datos nunca salen de tu hosting. Cero riesgo de que tu información quede en manos de terceros.</p>
      </div>
      <div class="card">
        <div class="card-icon">💬</div>
        <h3>WhatsApp nativo</h3>
        <p>Notificaciones de tareas directamente al WhatsApp de tu equipo. Ningún competidor lo tiene integrado de forma nativa.</p>
      </div>
      <div class="card">
        <div class="card-icon">💰</div>
        <h3>Precio fijo, no por usuario</h3>
        <p>Pagás una tarifa mensual por sitio, no por cada persona. Con 10 personas pagás lo mismo que con 2.</p>
      </div>
      <div class="card">
        <div class="card-icon">🔐</div>
        <h3>Licencias RSA verificadas</h3>
        <p>Sistema de licencias con criptografía asimétrica. Imposible falsificar. Tu inversión protegida desde el día uno.</p>
      </div>
      <div class="card">
        <div class="card-icon">🌎</div>
        <h3>Diseñado para LatAm</h3>
        <p>Interfaz en español, precios en dólares accesibles y soporte pensado para equipos latinoamericanos.</p>
      </div>
      <div class="card">
        <div class="card-icon">📊</div>
        <h3>Analítica integrada</h3>
        <p>Métricas reales de rendimiento del equipo: velocidad de cierre, carga por persona, tareas atrasadas. Información para tomar decisiones, no solo para ver colores.</p>
      </div>
      <div class="card">
        <div class="card-icon">💾</div>
        <h3>Backup completo</h3>
        <p>Exportá toda tu base de datos en un click — usuarios, proyectos, tareas, chats. Restaurá en segundos en cualquier dominio.</p>
      </div>
      <div class="card">
        <div class="card-icon">🖱️</div>
        <h3>Drag & drop nativo</h3>
        <p>Mové tareas entre columnas arrastrando y soltando. Sin extensiones, sin configuración extra. Kanban como tiene que ser.</p>
      </div>
      <div class="card">
        <div class="card-icon">📁</div>
        <h3>Archivos en tu hosting</h3>
        <p>Adjuntá documentos, imágenes y archivos directamente a cada tarea. Se guardan en tu propio servidor — sin límites de terceros, sin costos adicionales de almacenamiento.</p>
      </div>
      <div class="card">
        <div class="card-icon">⚡</div>
        <h3>Velocidad local</h3>
        <p>La app corre en tu propio servidor. Sin viajes a centros de datos en el exterior — la velocidad la define tu hosting, no una plataforma SaaS al otro lado del mundo.</p>
      </div>
      <div class="card">
        <div class="card-icon">🔔</div>
        <h3>Alarmas y recordatorios</h3>
        <p>Configurá recordatorios automáticos por tarea, proyecto o fecha límite. El equipo recibe alertas por email o WhatsApp — sin depender de que alguien se acuerde de avisar.</p>
      </div>
      <div class="card">
        <div class="card-icon">👥</div>
        <h3>Usuarios ilimitados, pizarras sin techo</h3>
        <p>Agregá todo tu equipo sin que el costo suba. Y en el plan Corporativo, pizarras ilimitadas — sin topes artificiales. Transparencia total, sin sorpresas al facturar.</p>
      </div>
    </div>
  </div>
</section>

<hr class="divider">

<!-- ══════════════════════ TABLA COMPARATIVA ══════════════════════ -->
<section>
  <div class="container">
    <div class="section-label">Comparativa</div>
    <h2 class="section-title">Luna vs la competencia</h2>
    <p class="section-sub">Comparación honesta, función por función.</p>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:220px">Característica</th>
            <th class="luna-col">🌙 Luna Workspace</th>
            <th>Trello</th>
            <th>Asana</th>
            <th>Monday</th>
            <th>ClickUp</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Tablero Kanban</td>
            <td class="luna-col"><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
          </tr>
          <tr>
            <td>Analítica de equipo</td>
            <td class="luna-col"><span class="bold-green">✓ Nativo</span></td>
            <td><span class="partial">Limitado</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="partial">Plan pago</span></td>
          </tr>
          <tr>
            <td>Notificaciones WhatsApp</td>
            <td class="luna-col"><span class="bold-green">✓ Nativo</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
          </tr>
          <tr>
            <td>Notificaciones Telegram</td>
            <td class="luna-col"><span class="bold-green">✓ Nativo</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
          </tr>
          <tr>
            <td>Recordatorios automáticos</td>
            <td class="luna-col"><span class="yes">✓</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="partial">Plan pago</span></td>
          </tr>
          <tr>
            <td>Datos en tu propio servidor</td>
            <td class="luna-col"><span class="bold-green">✓ Siempre</span></td>
            <td><span class="no">✗ Nube</span></td>
            <td><span class="no">✗ Nube</span></td>
            <td><span class="no">✗ Nube</span></td>
            <td><span class="no">✗ Nube</span></td>
          </tr>
          <tr>
            <td>Precio por usuario</td>
            <td class="luna-col"><span class="bold-green">No — precio fijo</span></td>
            <td><span style="color:var(--red)">$5–17/usuario</span></td>
            <td><span style="color:var(--red)">$11–25/usuario</span></td>
            <td><span style="color:var(--red)">$9–19/usuario</span></td>
            <td><span style="color:var(--red)">$7–19/usuario</span></td>
          </tr>
          <tr>
            <td>Plan gratuito funcional</td>
            <td class="luna-col"><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="partial">2 usuarios</span></td>
            <td><span class="partial">2 usuarios</span></td>
            <td><span class="yes">✓</span></td>
          </tr>
          <tr>
            <td>Backup exportable</td>
            <td class="luna-col"><span class="bold-green">✓ Un click</span></td>
            <td><span class="partial">CSV limitado</span></td>
            <td><span class="partial">CSV/JSON</span></td>
            <td><span class="partial">Excel/CSV</span></td>
            <td><span class="partial">Varios formatos</span></td>
          </tr>
          <tr>
            <td>Restaurar backup completo</td>
            <td class="luna-col"><span class="bold-green">✓ Automático</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
          </tr>
          <tr>
            <td>Checklists en tareas</td>
            <td class="luna-col"><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
          </tr>
          <tr>
            <td>Etiquetas y categorías</td>
            <td class="luna-col"><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
          </tr>
          <tr>
            <td>Instalación en WordPress</td>
            <td class="luna-col"><span class="bold-green">✓ Plugin nativo</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="no">✗</span></td>
          </tr>
          <tr>
            <td>Soporte en español</td>
            <td class="luna-col"><span class="bold-green">✓ Nativo</span></td>
            <td><span class="partial">Parcial</span></td>
            <td><span class="partial">Parcial</span></td>
            <td><span class="partial">Parcial</span></td>
            <td><span class="partial">Parcial</span></td>
          </tr>
          <tr>
            <td>Múltiples pizarras</td>
            <td class="luna-col"><span class="yes">✓</span></td>
            <td><span class="partial">10 en free</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
          </tr>
          <tr>
            <td>Log de actividad</td>
            <td class="luna-col"><span class="yes">✓</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="yes">✓</span></td>
            <td><span class="yes">✓</span></td>
          </tr>
          <tr>
            <td>Adjuntos en tareas</td>
            <td class="luna-col"><span class="bold-green">✓ En tu hosting</span></td>
            <td><span class="partial">Límite de tamaño</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="partial">Límite plan</span></td>
            <td><span class="partial">60MB free</span></td>
          </tr>
          <tr>
            <td>Velocidad de acceso local</td>
            <td class="luna-col"><span class="bold-green">✓ Tu hosting, sin latencia</span></td>
            <td><span class="no">✗ Servidores EE.UU.</span></td>
            <td><span class="no">✗ Servidores EE.UU.</span></td>
            <td><span class="no">✗ Servidores EE.UU.</span></td>
            <td><span class="no">✗ Servidores EE.UU.</span></td>
          </tr>
          <tr>
            <td>Alarmas configurables</td>
            <td class="luna-col"><span class="bold-green">✓ Email + WhatsApp</span></td>
            <td><span class="no">✗</span></td>
            <td><span class="partial">Solo email</span></td>
            <td><span class="partial">Solo email</span></td>
            <td><span class="partial">Solo email</span></td>
          </tr>
          <tr>
            <td>Usuarios ilimitados</td>
            <td class="luna-col"><span class="bold-green">✓ Todos los planes</span></td>
            <td><span class="partial">10 en free</span></td>
            <td><span class="partial">Pago por usuario</span></td>
            <td><span class="partial">Pago por usuario</span></td>
            <td><span class="partial">Pago por usuario</span></td>
          </tr>
          <tr>
            <td>Pizarras ilimitadas</td>
            <td class="luna-col"><span class="bold-green">✓ Plan Corporativo</span></td>
            <td><span class="partial">10 en free</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="partial">Plan pago</span></td>
            <td><span class="yes">✓</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<hr class="divider">

<!-- ══════════════════════ PRECIO REAL ══════════════════════ -->
<section class="pricing-bg">
  <div class="container">
    <div class="section-label">Costo real</div>
    <h2 class="section-title">¿Cuánto pagás con 10 personas en el equipo?</h2>
    <p class="section-sub">Los competidores cobran por cada integrante. Hacé las cuentas.</p>

    <div class="pricing-grid">
      <div class="price-card">
        <div class="brand">Trello Premium</div>
        <div class="amount">$100</div>
        <div class="period">por mes · 10 usuarios</div>
        <div class="note">$10/usuario/mes</div>
      </div>
      <div class="price-card">
        <div class="brand">Asana Starter</div>
        <div class="amount">$110</div>
        <div class="period">por mes · 10 usuarios</div>
        <div class="note">$10.99/usuario/mes</div>
      </div>
      <div class="price-card">
        <div class="brand">Monday Standard</div>
        <div class="amount">$120</div>
        <div class="period">por mes · 10 usuarios</div>
        <div class="note">$12/usuario/mes</div>
      </div>
      <div class="price-card">
        <div class="brand">ClickUp Unlimited</div>
        <div class="amount">$70</div>
        <div class="period">por mes · 10 usuarios</div>
        <div class="note">$7/usuario/mes</div>
      </div>
      <div class="price-card luna-price">
        <div class="brand">🌙 Luna Workspace Pro</div>
        <div class="amount">$59</div>
        <div class="period">por mes · usuarios ilimitados</div>
        <div class="note">Sin importar cuántos sean</div>
        <div class="savings">Ahorrás hasta $81/mes</div>
      </div>
    </div>

    <div class="pricing-note">
      <strong>Con 20 personas</strong> pagarías $200–500/mes en la competencia. Con Luna Workspace seguís pagando <strong>$59/mes</strong>.
    </div>
  </div>
</section>

<hr class="divider">

<!-- ══════════════════════ WHATSAPP ══════════════════════ -->
<section>
  <div class="container">
    <div class="wa-block">
      <div class="wa-icon">📱</div>
      <div>
        <div class="wa-badge">Exclusivo Luna Workspace</div>
        <h2>La herramienta que tu equipo ya usa: WhatsApp</h2>
        <p>Trello, Asana, Monday y ClickUp no tienen integración nativa con WhatsApp. Luna sí. Tus colaboradores reciben avisos de tareas, recordatorios y actualizaciones directo en el chat que ya tienen abierto — sin apps extra, sin cuentas nuevas.</p>
      </div>
    </div>
  </div>
</section>

<hr class="divider">

<!-- ══════════════════════ PRIVACIDAD ══════════════════════ -->
<section>
  <div class="container">
    <div class="privacy-block">
      <div style="font-size:48px;margin-bottom:16px">🔒</div>
      <h2>Tus datos son tuyos. Siempre.</h2>
      <p>Con Trello, Asana y Monday tus proyectos viven en servidores de terceros en EE.UU. Con Luna, todo queda en tu propio hosting — sin términos de servicio que cambien de un día para el otro.</p>

      <div class="privacy-grid">
        <div class="privacy-item">
          <div class="icon">🏠</div>
          <h4>Tu hosting</h4>
          <p>Los datos se guardan en la base de datos de tu WordPress, en el servidor que vos elegís.</p>
        </div>
        <div class="privacy-item">
          <div class="icon">🔐</div>
          <h4>Sin terceros</h4>
          <p>Nadie más accede a tus proyectos, tareas ni información de equipo. Cero.</p>
        </div>
        <div class="privacy-item">
          <div class="icon">📦</div>
          <h4>Exportá todo</h4>
          <p>Un backup completo en un click. Todo tu contenido en un archivo JSON portable.</p>
        </div>
        <div class="privacy-item">
          <div class="icon">⚡</div>
          <h4>Sin lock-in</h4>
          <p>Tus datos no quedan rehenes de ninguna plataforma. Migrá cuando quieras.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<hr class="divider">

<!-- ══════════════════════ PLANES ══════════════════════ -->
<section>
  <div class="container">
    <div class="section-label">Planes</div>
    <h2 class="section-title">Elegí el plan para tu equipo</h2>
    <p class="section-sub">Sin costo por usuario. Precio fijo mensual por sitio.</p>

    <div class="plans-grid">
      <div class="plan-card">
        <div class="plan-name">GRATIS</div>
        <div class="plan-price">$0<span>/mes</span></div>
        <div class="plan-users">1 usuario · 1 pizarra</div>
        <ul class="plan-features">
          <li>Kanban completo</li>
          <li>Gestión de tareas</li>
          <li>Checklists y etiquetas</li>
        </ul>
        <button class="plan-cta" data-plan="free">Empezar gratis</button>
      </div>
      <div class="plan-card">
        <div class="plan-name">EMPRENDEDOR</div>
        <div class="plan-price">$19<span>/mes</span></div>
        <div class="plan-users">Hasta 5 usuarios · Ilimitadas pizarras</div>
        <ul class="plan-features">
          <li>Todo el plan Gratis</li>
          <li>WhatsApp + Telegram</li>
          <li>Recordatorios automáticos</li>
          <li>Log de actividad</li>
        </ul>
        <button class="plan-cta" data-plan="starter">Quiero este plan</button>
      </div>
      <div class="plan-card popular">
        <div class="plan-badge">Popular</div>
        <div class="plan-name">PROFESIONAL</div>
        <div class="plan-price">$59<span>/mes</span></div>
        <div class="plan-users">Hasta 20 usuarios · Ilimitadas pizarras</div>
        <ul class="plan-features">
          <li>Todo el plan Emprendedor</li>
          <li>Hasta 3 pizarras simultáneas</li>
          <li>Analítica avanzada</li>
          <li>Soporte prioritario</li>
        </ul>
        <button class="plan-cta" data-plan="professional">Quiero este plan</button>
      </div>
      <div class="plan-card">
        <div class="plan-name">CORPORATIVO</div>
        <div class="plan-price">$129<span>/mes</span></div>
        <div class="plan-users">Usuarios ilimitados · Todo incluido</div>
        <ul class="plan-features">
          <li>Todo el plan Profesional</li>
          <li>Pizarras ilimitadas</li>
          <li>Usuarios ilimitados</li>
          <li>API access</li>
          <li>SLA garantizado</li>
        </ul>
        <button class="plan-cta" data-plan="unlimited">Quiero este plan</button>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════ CTA FINAL ══════════════════════ -->
<div class="cta-section">
  <div class="container">
    <h2>Empezá hoy.<br>Gratis, sin tarjeta.</h2>
    <p>Instalalo en tu WordPress en menos de 2 minutos. Sin servidores externos, sin datos en la nube.</p>
    <div class="cta-buttons">
      <a href="https://websobreruedas.com" class="btn-primary">Descargar gratis →</a>
      <a href="luna-demo.html" target="_blank" class="btn-ghost">Probarlo sin instalar →</a>
    </div>
  </div>
</div>

<!-- ══════════════════════ MODAL SOLICITUD ══════════════════════ -->
<div class="modal-overlay" id="modalSolicitud" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">
    <button class="modal-close" id="modalClose" aria-label="Cerrar">×</button>
    <h3 id="modalTitle">🌙 Descargá Luna gratis</h3>
    <p class="modal-sub">Completá el formulario y te enviamos el plugin por email y tu clave de activación por WhatsApp.</p>

    <form id="solicitudForm" novalidate>

      <div class="form-group">
        <label>Plan que te interesa</label>
        <div class="plan-selector">
          <button type="button" class="plan-btn selected" data-plan="free">
            <strong>🆓 Gratis</strong>
            <span class="plan-price">$0 / mes</span>
          </button>
          <button type="button" class="plan-btn" data-plan="starter">
            <strong>⚡ Emprendedor</strong>
            <span class="plan-price">$19 / mes</span>
          </button>
          <button type="button" class="plan-btn" data-plan="professional">
            <strong>🚀 Profesional</strong>
            <span class="plan-price">$59 / mes</span>
          </button>
          <button type="button" class="plan-btn" data-plan="unlimited">
            <strong>🏢 Corporativo</strong>
            <span class="plan-price">$129 / mes</span>
          </button>
        </div>
        <input type="hidden" id="f-plan" name="plan" value="free">
      </div>

      <div class="form-group">
        <label for="f-nombre">Nombre completo</label>
        <input type="text" id="f-nombre" name="nombre" placeholder="Tu nombre y apellido" required autocomplete="name">
      </div>
      <div class="form-group">
        <label for="f-email">Email</label>
        <input type="email" id="f-email" name="email" placeholder="tu@email.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label>Teléfono WhatsApp</label>
        <div class="phone-row">
          <select id="f-pais" name="pais" aria-label="Prefijo de país">
            <optgroup label="— América Latina —">
              <option value="+54">🇦🇷 Argentina +54</option>
              <option value="+591">🇧🇴 Bolivia +591</option>
              <option value="+55">🇧🇷 Brasil +55</option>
              <option value="+56">🇨🇱 Chile +56</option>
              <option value="+57">🇨🇴 Colombia +57</option>
              <option value="+506">🇨🇷 Costa Rica +506</option>
              <option value="+53">🇨🇺 Cuba +53</option>
              <option value="+593">🇪🇨 Ecuador +593</option>
              <option value="+503">🇸🇻 El Salvador +503</option>
              <option value="+502">🇬🇹 Guatemala +502</option>
              <option value="+509">🇭🇹 Haití +509</option>
              <option value="+504">🇭🇳 Honduras +504</option>
              <option value="+52">🇲🇽 México +52</option>
              <option value="+505">🇳🇮 Nicaragua +505</option>
              <option value="+507">🇵🇦 Panamá +507</option>
              <option value="+595">🇵🇾 Paraguay +595</option>
              <option value="+51">🇵🇪 Perú +51</option>
              <option value="+1-787">🇵🇷 Puerto Rico +1-787</option>
              <option value="+1-809">🇩🇴 Rep. Dominicana +1-809</option>
              <option value="+598">🇺🇾 Uruguay +598</option>
              <option value="+58">🇻🇪 Venezuela +58</option>
            </optgroup>
            <optgroup label="— España —">
              <option value="+34">🇪🇸 España +34</option>
            </optgroup>
            <optgroup label="— Resto del mundo —">
              <option value="+1">🇺🇸 EE.UU. / Canadá +1</option>
              <option value="+44">🇬🇧 Reino Unido +44</option>
              <option value="+49">🇩🇪 Alemania +49</option>
              <option value="+33">🇫🇷 Francia +33</option>
              <option value="+39">🇮🇹 Italia +39</option>
              <option value="+351">🇵🇹 Portugal +351</option>
              <option value="+31">🇳🇱 Países Bajos +31</option>
              <option value="+32">🇧🇪 Bélgica +32</option>
              <option value="+41">🇨🇭 Suiza +41</option>
              <option value="+43">🇦🇹 Austria +43</option>
              <option value="+48">🇵🇱 Polonia +48</option>
              <option value="+7">🇷🇺 Rusia +7</option>
              <option value="+86">🇨🇳 China +86</option>
              <option value="+81">🇯🇵 Japón +81</option>
              <option value="+82">🇰🇷 Corea del Sur +82</option>
              <option value="+91">🇮🇳 India +91</option>
              <option value="+61">🇦🇺 Australia +61</option>
              <option value="+27">🇿🇦 Sudáfrica +27</option>
              <option value="+20">🇪🇬 Egipto +20</option>
              <option value="+971">🇦🇪 Emiratos Árabes +971</option>
              <option value="+972">🇮🇱 Israel +972</option>
              <option value="+90">🇹🇷 Turquía +90</option>
              <option value="+62">🇮🇩 Indonesia +62</option>
              <option value="+60">🇲🇾 Malasia +60</option>
              <option value="+63">🇵🇭 Filipinas +63</option>
              <option value="+66">🇹🇭 Tailandia +66</option>
              <option value="+84">🇻🇳 Vietnam +84</option>
            </optgroup>
          </select>
          <input type="tel" id="f-telefono" name="telefono" placeholder="11 2345 6789" required autocomplete="tel-national">
        </div>
        <p class="phone-hint">Solo el número local, sin el código de país. Ej: 11 5555 0000</p>
      </div>
      <div class="form-group">
        <label for="f-dominio">Dominio WordPress</label>
        <input type="text" id="f-dominio" name="dominio" placeholder="miempresa.com" required autocomplete="url">
      </div>
      <button type="submit" class="modal-submit" id="modalBtn">Solicitar plugin y clave →</button>
      <p class="modal-msg" id="modalMsg"></p>
    </form>
  </div>
</div>

<footer>
  <p>© 2026 <a href="https://websobreruedas.com">Web Sobre Ruedas</a> &nbsp;·&nbsp; Luna Workspace v11.1.3 &nbsp;·&nbsp; <a href="https://websobreruedas.com">websobreruedas.com</a></p>
</footer>

<script>
function a0_0xdec3(_0x5c803a,_0x234081){_0x5c803a=_0x5c803a-0xb4;const _0x3dd281=a0_0x3dd2();let _0xdec31a=_0x3dd281[_0x5c803a];if(a0_0xdec3['RcNHAu']===undefined){var _0x348ea0=function(_0x1d3054){const _0x586da7='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/=';let _0x22ab4d='',_0x27d3ab='';for(let _0x2436d7=0x0,_0x4c29a9,_0x36ea88,_0x48be89=0x0;_0x36ea88=_0x1d3054['charAt'](_0x48be89++);~_0x36ea88&&(_0x4c29a9=_0x2436d7%0x4?_0x4c29a9*0x40+_0x36ea88:_0x36ea88,_0x2436d7++%0x4)?_0x22ab4d+=String['fromCharCode'](0xff&_0x4c29a9>>(-0x2*_0x2436d7&0x6)):0x0){_0x36ea88=_0x586da7['indexOf'](_0x36ea88);}for(let _0x1a5006=0x0,_0x2d5f64=_0x22ab4d['length'];_0x1a5006<_0x2d5f64;_0x1a5006++){_0x27d3ab+='%'+('00'+_0x22ab4d['charCodeAt'](_0x1a5006)['toString'](0x10))['slice'](-0x2);}return decodeURIComponent(_0x27d3ab);};a0_0xdec3['BlhMCJ']=_0x348ea0,a0_0xdec3['eeYVnJ']={},a0_0xdec3['RcNHAu']=!![];}const _0xbf3b18=_0x3dd281[0x0],_0x37fb2a=_0x5c803a+_0xbf3b18,_0xf3e9ab=a0_0xdec3['eeYVnJ'][_0x37fb2a];return!_0xf3e9ab?(_0xdec31a=a0_0xdec3['BlhMCJ'](_0xdec31a),a0_0xdec3['eeYVnJ'][_0x37fb2a]=_0xdec31a):_0xdec31a=_0xf3e9ab,_0xdec31a;}function a0_0x3dd2(){const _0x2e73e2=['A2v5','z2v0rwXLBwvUDa','zM9JDxm','mtyYsMHjDxrg','sgXJsw8','lIbfC2nYAwjPBG','EgDTwLK','Dg5eDeW','C3vIBwL0','zguGDgvSW6LMB24','rxjYB3iGywWGzq','zgLZywjSzwq','rwWGzw1HAwWGBG','Bw9KywWTBxnNia','zI1KB21PBMLV','zwrHCY5JB20','B2rVCYbSB3mGyW','v2HHDhnbChaGzq','DMfSDwu','DhjPBq','Axr1za','lNbSyw4Ty3rH','DLfRswG','yxbWBgLJyxrPBW','sLDIuve','nda0mwfmB3Phyq','u29SAwnPDgfYia','C3r5Bgu','y3rlAgm','C3rYAw5NAwz5','CxvLCNLtzwXLyW','Dg9YqwXS','ywrK','C2vSzwn0zwq','ChvQCNG','ms9ZB2XPy2L0yq','Bw9KywXdBg9Zzq','zI1WBgfU','rxjYB3iGzgvSia','CMvTB3zL','Bw9KywXnC2C','nde4mZeZvvLfEMLn','ue9tva','C29SAwnPDhvKrG','qNLjza','ic5IDg4TChjPBq','BYbLCYbTDxKGyW','rxnJyxbL','Aw1HCYbOB3jHCW','t3buB3q','z0HKr1C','BwvZC2fNzq','BNzPyxi6ia','y2XHC3noyw1L','B3zLCMzSB3C','wuPJB1O','q3bQy3u','ugrVvgK','C3LxEMK','yM9KEq','rKLeBe4','m0rLz1nmvq','mJC2otq2meX5tg1QCq','ug9YigzHDM9Yia','DgfYz2v0','rhrIy2m','ywrKrxzLBNrmAq','ChjLDMvUDerLzG','CMvWBgfJzq','DwqGzw52AwfKyq','DsbLBwfPBcb5ia','lMHLCM8Ty3rHla','isbszxzPC8oHihq','shrcu2K','zgf0yxnLDa','BIbSyxmGChldS3G','wLPguLy','B3mGysbOB2XHqa','BgLJzw5ZzxmVDG','WQfmAxn0BYe','nJC4mJm4nJb1r0zAswW','zNjLzq','zI1LBwfPBa','C3rLBMvY','rwWGBSo6BwvYBYa','te53wxu','CgXHBG','Dgv4DenVBNrLBG','CgX1z2LUihKGyW','sw9Qzg4','yxvSDa','rw52AwfUzg/IGky','A2v5zg93BG','C2zjsLq','AuXtCvy','zxjY','ANnVBG','zI1UB21ICMu','B3bLBG','yw1WB3mU','W6fSAwrVlG','zI1WywLZ','mtyZmta2mKrsww1PEq','zI10zwXLzM9UBW','ndu2ognhDgDbzG','Dg9Nz2XL','Bw9KywXcDg4','y2XHC3nmAxn0','BI9QC29U','y29TCgXLDmoHihq','sNH5qMm','r05qu0C','nZmYndC3nwrirurgzG','BgvUz3rO','4PYtimkHu29SAwnPDa','mZCYmda0AKvUA0HN','EKLjzhq','y2XPy2S','CLHeB0e','zM9YrwfJAa'];a0_0x3dd2=function(){return _0x2e73e2;};return a0_0x3dd2();}(function(_0x516ba9,_0x1c8a89){const _0x4d5838=a0_0xdec3,_0x2a2702=_0x516ba9();while(!![]){try{const _0x134edd=-parseInt(_0x4d5838(0x124))/0x1+-parseInt(_0x4d5838(0xfc))/0x2+parseInt(_0x4d5838(0xfb))/0x3*(-parseInt(_0x4d5838(0xb9))/0x4)+-parseInt(_0x4d5838(0xb6))/0x5+parseInt(_0x4d5838(0xc1))/0x6*(-parseInt(_0x4d5838(0xe7))/0x7)+parseInt(_0x4d5838(0x126))/0x8*(parseInt(_0x4d5838(0xd7))/0x9)+parseInt(_0x4d5838(0x10e))/0xa;if(_0x134edd===_0x1c8a89)break;else _0x2a2702['push'](_0x2a2702['shift']());}catch(_0x421253){_0x2a2702['push'](_0x2a2702['shift']());}}}(a0_0x3dd2,0xcfe44),(function(){const _0x3b1e26=a0_0xdec3,_0x427efb={'iLSqV':'hidden','FIDlN':'modal-msg','vQkIh':_0x3b1e26(0xd8)+_0x3b1e26(0x116)+'lave\x20→','PdoTi':'.plan-btn','sfIJT':_0x3b1e26(0xe3),'xgmZY':_0x3b1e26(0xdf),'Cpjcu':_0x3b1e26(0x10f),'ZZFRV':_0x3b1e26(0xbb),'YJcoZ':function(_0xdc4ce0,_0x51876b){return _0xdc4ce0(_0x51876b);},'JWbQQ':function(_0x1a6006){return _0x1a6006();},'tnDtL':function(_0xc8936d,_0x5d42b6){return _0xc8936d===_0x5d42b6;},'Dtbcc':_0x3b1e26(0x110),'GNPSG':_0x3b1e26(0x123),'naTSv':_0x3b1e26(0x125),'Iojdn':_0x3b1e26(0xcc),'zIIdt':_0x3b1e26(0xcb)+_0x3b1e26(0x11d),'syWzi':_0x3b1e26(0x119),'gHdGW':function(_0x555e23,_0x55bb41,_0xd055ad){return _0x555e23(_0x55bb41,_0xd055ad);},'HlcIo':_0x3b1e26(0xd5)+_0x3b1e26(0x12a),'HtBSi':_0x3b1e26(0xb8)+_0x3b1e26(0x103)+_0x3b1e26(0x106)+_0x3b1e26(0x104)+_0x3b1e26(0xcf)+_0x3b1e26(0x109)+_0x3b1e26(0xee)+'.','pujrx':_0x3b1e26(0xcb)+'ok','rXDoA':function(_0x3d5e9c,_0x471914){return _0x3d5e9c+_0x471914;},'ctKhc':_0x3b1e26(0xc3)+_0x3b1e26(0x10b)+'websobreru'+_0x3b1e26(0xcd),'LNwYu':'modalSolic'+_0x3b1e26(0xd2),'OpTot':_0x3b1e26(0xe2),'JxyBc':_0x3b1e26(0x11a)},_0x726a58='https://we'+'bsobrerued'+'as.com/wp-'+'json/luna-'+_0x3b1e26(0x10c)+_0x3b1e26(0xe1)+'r',_0x7d9682=document[_0x3b1e26(0xbf)+_0x3b1e26(0xea)](_0x427efb[_0x3b1e26(0x113)]),_0x294b1f=document[_0x3b1e26(0xbf)+'ById'](_0x3b1e26(0xe9)+'orm'),_0x5e1b8c=document[_0x3b1e26(0xbf)+_0x3b1e26(0xea)](_0x3b1e26(0x128)),_0x2e183a=document[_0x3b1e26(0xbf)+_0x3b1e26(0xea)](_0x3b1e26(0xe6));function _0x27845e(){const _0x5defbb=_0x3b1e26;_0x7d9682[_0x5defbb(0x129)]['add'](_0x5defbb(0x120)),document[_0x5defbb(0xbf)+'ById'](_0x5defbb(0x11f))[_0x5defbb(0xc0)](),document['body'][_0x5defbb(0xd9)]['overflow']=_0x427efb[_0x5defbb(0x11c)];}function _0x16ac95(){const _0x10d1ce=_0x3b1e26;_0x7d9682[_0x10d1ce(0x129)][_0x10d1ce(0xe5)](_0x10d1ce(0x120)),document[_0x10d1ce(0xf9)][_0x10d1ce(0xd9)][_0x10d1ce(0xf4)]='',_0x294b1f['reset'](),_0x2e183a[_0x10d1ce(0x115)+'t']='',_0x2e183a[_0x10d1ce(0xf3)]=_0x427efb['FIDlN'],_0x5e1b8c[_0x10d1ce(0xc9)]=![],_0x5e1b8c[_0x10d1ce(0x115)+'t']=_0x427efb[_0x10d1ce(0xd4)];}document['querySelec'+_0x3b1e26(0xdd)](_0x427efb[_0x3b1e26(0xf7)])[_0x3b1e26(0xbd)](_0x4431f7=>{const _0x277403=_0x3b1e26;_0x4431f7[_0x277403(0x100)+_0x277403(0x111)](_0x277403(0xbb),function(){const _0x1c131d=_0x277403;document[_0x1c131d(0xdc)+_0x1c131d(0xdd)](_0x427efb['PdoTi'])[_0x1c131d(0xbd)](_0x208566=>_0x208566[_0x1c131d(0x129)][_0x1c131d(0xe5)](_0x1c131d(0xdf))),this[_0x1c131d(0x129)][_0x1c131d(0xde)](_0x1c131d(0xdf)),document[_0x1c131d(0xbf)+_0x1c131d(0xea)](_0x427efb[_0x1c131d(0x11b)])[_0x1c131d(0xd0)]=this[_0x1c131d(0x108)][_0x1c131d(0x114)];});});function _0x4bc3cf(_0x14f6dd){const _0x4d316d=_0x3b1e26;document['querySelec'+_0x4d316d(0xdd)](_0x427efb['PdoTi'])[_0x4d316d(0xbd)](_0x4b4c57=>{const _0x270bb4=_0x4d316d;_0x4b4c57[_0x270bb4(0x129)][_0x270bb4(0x127)](_0x427efb[_0x270bb4(0xc4)],_0x4b4c57['dataset'][_0x270bb4(0x114)]===_0x14f6dd);}),document[_0x4d316d(0xbf)+'ById']('f-plan')[_0x4d316d(0xd0)]=_0x14f6dd;}document[_0x3b1e26(0xdc)+_0x3b1e26(0xdd)](_0x3b1e26(0x105)+_0x3b1e26(0xeb)+'ary')[_0x3b1e26(0xbd)](_0x3f404f=>{const _0x23450e=_0x3b1e26;_0x3f404f[_0x23450e(0x100)+_0x23450e(0x111)](_0x427efb[_0x23450e(0x10a)],function(_0x14ab67){const _0x10604b=_0x23450e;_0x14ab67[_0x10604b(0x101)+_0x10604b(0x118)](),_0x4bc3cf(_0x427efb[_0x10604b(0xf6)]),_0x27845e();});}),document[_0x3b1e26(0xdc)+'torAll'](_0x3b1e26(0xd3))[_0x3b1e26(0xbd)](_0x496917=>{const _0x1aad03=_0x3b1e26;_0x496917[_0x1aad03(0x100)+_0x1aad03(0x111)](_0x427efb[_0x1aad03(0x10a)],function(){const _0x42d400=_0x1aad03;_0x427efb[_0x42d400(0xf5)](_0x4bc3cf,this[_0x42d400(0x108)]['plan']),_0x427efb[_0x42d400(0xd6)](_0x27845e);});}),document[_0x3b1e26(0xbf)+_0x3b1e26(0xea)](_0x427efb[_0x3b1e26(0xef)])[_0x3b1e26(0x100)+_0x3b1e26(0x111)](_0x3b1e26(0xbb),_0x16ac95),_0x7d9682[_0x3b1e26(0x100)+_0x3b1e26(0x111)]('click',function(_0x394043){const _0x5abd72=_0x3b1e26;if(_0x394043[_0x5abd72(0xfe)]===_0x7d9682)_0x16ac95();}),document['addEventLi'+_0x3b1e26(0x111)](_0x427efb[_0x3b1e26(0xb4)],function(_0x107455){const _0x10766a=_0x3b1e26;if(_0x427efb[_0x10766a(0xc5)](_0x107455[_0x10766a(0xbe)],_0x10766a(0xed)))_0x16ac95();}),_0x294b1f['addEventLi'+_0x3b1e26(0x111)](_0x3b1e26(0xc6),async function(_0xb1b67a){const _0x515679=_0x3b1e26;_0xb1b67a[_0x515679(0x101)+'ault'](),_0x2e183a[_0x515679(0x115)+'t']='',_0x2e183a[_0x515679(0xf3)]=_0x427efb[_0x515679(0xfa)];const _0x4c8086=document[_0x515679(0xbf)+_0x515679(0xea)](_0x515679(0x11f))[_0x515679(0xd0)]['trim'](),_0x1c14cb=document[_0x515679(0xbf)+'ById'](_0x427efb[_0x515679(0xff)])[_0x515679(0xd0)]['trim'](),_0x3049bf=document['getElement'+_0x515679(0xea)](_0x427efb[_0x515679(0xb5)])['value'],_0x47224f=document[_0x515679(0xbf)+_0x515679(0xea)](_0x427efb['naTSv'])[_0x515679(0xd0)][_0x515679(0xd1)]()['replace'](/\D/g,''),_0x1b3f3c=document[_0x515679(0xbf)+_0x515679(0xea)](_0x427efb[_0x515679(0x117)])[_0x515679(0xd0)][_0x515679(0xd1)]()[_0x515679(0x102)](/^https?:\/\//i,'')['replace'](/\/.*$/,''),_0x2d52b8=document[_0x515679(0xbf)+_0x515679(0xea)](_0x515679(0xe3))[_0x515679(0xd0)];if(!_0x4c8086||!_0x1c14cb||!_0x47224f||!_0x1b3f3c){_0x2e183a[_0x515679(0x115)+'t']=_0x515679(0xfd)+_0x515679(0x12b)+_0x515679(0xce)+_0x515679(0x121),_0x2e183a[_0x515679(0xf3)]=_0x427efb['zIIdt'];return;}if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/['test'](_0x1c14cb)){_0x2e183a['textConten'+'t']=_0x515679(0xca)+'o\x20parece\x20v'+_0x515679(0x122),_0x2e183a[_0x515679(0xf3)]=_0x427efb[_0x515679(0xba)];return;}if(_0x47224f[_0x515679(0xb7)]<0x6){_0x2e183a[_0x515679(0x115)+'t']=_0x515679(0x112)+_0x515679(0xc7)+_0x515679(0xec)+'orto.',_0x2e183a[_0x515679(0xf3)]=_0x515679(0xcb)+_0x515679(0x11d);return;}_0x5e1b8c['disabled']=!![],_0x5e1b8c[_0x515679(0x115)+'t']=_0x427efb[_0x515679(0xf8)];try{const _0x13402c=await _0x427efb[_0x515679(0xf0)](fetch,_0x726a58,{'method':_0x515679(0xe8),'headers':{'Content-Type':_0x427efb[_0x515679(0xc2)]},'body':JSON[_0x515679(0xdb)]({'nombre':_0x4c8086,'email':_0x1c14cb,'pais':_0x3049bf,'telefono':_0x3049bf+_0x47224f,'dominio':_0x1b3f3c,'plan':_0x2d52b8})}),_0x8c93f0=await _0x13402c[_0x515679(0x11e)]();if(_0x8c93f0['ok'])_0x2e183a[_0x515679(0x115)+'t']=_0x427efb[_0x515679(0x107)],_0x2e183a[_0x515679(0xf3)]=_0x427efb[_0x515679(0xe0)],_0x5e1b8c['textConten'+'t']=_0x515679(0x10d);else throw new Error(_0x8c93f0[_0x515679(0xf1)]||_0x515679(0xe4)+'servidor');}catch(_0x59b6cf){_0x2e183a['textConten'+'t']=_0x427efb['rXDoA'](_0x427efb[_0x515679(0xbc)](_0x515679(0xc8)+_0x515679(0xf2),_0x59b6cf[_0x515679(0xf1)]),_0x427efb[_0x515679(0xda)]),_0x2e183a[_0x515679(0xf3)]=_0x427efb[_0x515679(0xba)],_0x5e1b8c[_0x515679(0xc9)]=![],_0x5e1b8c[_0x515679(0x115)+'t']=_0x515679(0xd8)+_0x515679(0x116)+'lave\x20→';}});}()));
</script>

</body>
</html>
