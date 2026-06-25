<?php
/**
 * luna-demo.php — Página de demostración interactiva de Luna Workspace
 * Instalación: subir este archivo solo a cualquier carpeta del servidor
 * No requiere WordPress ni base de datos
 */
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Luna Workspace — Demo en vivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --luna: #5b6af0;
  --luna2: #7c3aed;
  --luna3: #06b6d4;
  --ok: #22d3a0;
  --warn: #f59e0b;
  --danger: #ef4444;
  --dark: #0f1117;
  --dark2: #161925;
  --dark3: #1e2235;
  --dark4: #252a40;
  --border: rgba(91,106,240,.18);
  --text: #e2e8f0;
  --muted: #8892a4;
  --rad: 14px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--text);min-height:100vh;overflow-x:hidden}

/* ── TOP BAR ── */
.topbar{position:fixed;top:0;left:0;right:0;height:56px;background:rgba(15,17,23,.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:1000}
.topbar-logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.05rem;letter-spacing:-.5px}
.topbar-logo .icon{width:32px;height:32px;background:linear-gradient(135deg,var(--luna),var(--luna2));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.85rem}
.demo-badge{background:linear-gradient(135deg,var(--luna),var(--luna2));color:#fff;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.5px}
.topbar-cta{display:flex;gap:10px;align-items:center}
.btn{display:inline-flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:.82rem;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none;border:none}
.btn-ghost{background:transparent;color:var(--text);border:1px solid var(--border)}
.btn-ghost:hover{background:var(--dark3)}
.btn-primary{background:linear-gradient(135deg,var(--luna),var(--luna2));color:#fff;box-shadow:0 4px 18px rgba(91,106,240,.35)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 24px rgba(91,106,240,.45)}
.btn-green{background:linear-gradient(135deg,#22d3a0,#059669);color:#fff;box-shadow:0 4px 14px rgba(34,211,160,.3)}
.btn-green:hover{transform:translateY(-1px)}

/* ── HERO ── */
.hero{padding:130px 28px 60px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-200px;left:50%;transform:translateX(-50%);width:900px;height:900px;background:radial-gradient(ellipse,rgba(91,106,240,.15) 0%,transparent 70%);pointer-events:none}
.hero h1{font-size:clamp(2rem,5vw,3.2rem);font-weight:900;line-height:1.15;letter-spacing:-1.5px;margin-bottom:18px}
.hero h1 span{background:linear-gradient(135deg,var(--luna),var(--luna3));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero p{color:var(--muted);font-size:1.1rem;max-width:540px;margin:0 auto 32px;line-height:1.7}
.hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}

/* ── KANBAN DEMO ── */
.kanban-section{padding:20px 20px 60px}
.section-label{text-align:center;color:var(--muted);font-size:.75rem;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px}
.section-title{text-align:center;font-size:1.5rem;font-weight:800;margin-bottom:6px;letter-spacing:-.5px}
.section-sub{text-align:center;color:var(--muted);font-size:.9rem;margin-bottom:36px}

/* ── APP TOPBAR ── */
.app-topbar{height:48px;background:var(--dark3);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 14px;gap:10px;overflow:hidden}
.ws-dots{display:flex;gap:6px;flex-shrink:0}
.dot{width:11px;height:11px;border-radius:50%}
.dot-r{background:#ef4444}.dot-y{background:#f59e0b}.dot-g{background:#22d3a0}
.ws-badge{display:flex;align-items:center;gap:7px;background:var(--dark4);border:1px solid var(--border);border-radius:8px;padding:4px 10px;cursor:pointer;flex-shrink:0}
.ws-badge:hover{border-color:var(--luna)}
.ws-badge-dot{width:20px;height:20px;border-radius:6px;background:var(--luna);display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#fff;flex-shrink:0}
.ws-badge-name{font-size:.75rem;font-weight:600;color:var(--text);white-space:nowrap}
.ws-badge-arrow{font-size:.6rem;color:var(--muted)}
.vtab-demo{display:flex;align-items:center;gap:4px;font-size:.73rem;font-weight:700;color:var(--text);background:var(--luna);border:none;border-radius:7px;padding:4px 11px;cursor:pointer;font-family:inherit}
.search-demo{flex:1;max-width:280px;position:relative;flex-shrink:1;min-width:0}
.search-demo i{position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.75rem;pointer-events:none}
.search-demo input{width:100%;background:var(--dark4);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:5px 10px 5px 28px;font-size:.73rem;font-family:inherit;outline:none}
.search-demo input:focus{border-color:var(--luna)}
.search-demo input::placeholder{color:var(--muted)}
.live-pill{display:flex;align-items:center;gap:5px;font-size:.62rem;font-weight:700;color:var(--ok);letter-spacing:.5px;flex-shrink:0}
.live-dot2{width:6px;height:6px;border-radius:50%;background:var(--ok);animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.tb{height:28px;padding:0 9px;border-radius:7px;font-size:.7rem;font-weight:700;font-family:inherit;cursor:pointer;border:1px solid var(--border);background:var(--dark4);color:var(--muted);display:flex;align-items:center;gap:5px;transition:.15s;white-space:nowrap;flex-shrink:0}
.tb:hover{border-color:var(--luna);color:var(--text)}
.tb.accent{background:linear-gradient(135deg,var(--luna),var(--luna2));border-color:transparent;color:#fff}
.notif-wrap{position:relative;flex-shrink:0}
.notif-wrap .tb{padding:0 9px}
.notif-count{position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.55rem;font-weight:800;width:14px;height:14px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.tb-sep{width:1px;height:20px;background:var(--border);flex-shrink:0}
.user-chip{display:flex;align-items:center;gap:7px;cursor:pointer;flex-shrink:0}
.user-av{width:26px;height:26px;border-radius:8px;background:linear-gradient(135deg,var(--luna),var(--luna2));display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff}
.user-inf{display:flex;flex-direction:column;line-height:1.2}
.user-nm{font-size:.7rem;font-weight:700;color:var(--text)}
.user-rl{font-size:.6rem;color:var(--muted)}

.kanban-board{display:flex;gap:14px;padding:20px;overflow-x:auto;min-height:420px}
.kanban-board::-webkit-scrollbar{height:5px}
.kanban-board::-webkit-scrollbar-track{background:var(--dark2)}
.kanban-board::-webkit-scrollbar-thumb{background:var(--dark4);border-radius:4px}

.col{flex:0 0 240px;background:var(--dark3);border-radius:12px;border:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.col-header{padding:12px 14px;display:flex;align-items:center;justify-content:space-between}
.col-name{font-size:.82rem;font-weight:700;display:flex;align-items:center;gap:7px}
.col-dot{width:8px;height:8px;border-radius:50%}
.col-count{background:var(--dark4);color:var(--muted);font-size:.7rem;font-weight:700;padding:2px 7px;border-radius:20px}
.col-add{color:var(--muted);font-size:.75rem;cursor:pointer;transition:.2s}
.col-add:hover{color:var(--luna)}
.col-cards{padding:0 10px 10px;display:flex;flex-direction:column;gap:8px;flex:1;overflow-y:auto}
.col-cards::-webkit-scrollbar{width:3px}
.col-cards::-webkit-scrollbar-thumb{background:var(--dark4);border-radius:2px}

.card{background:var(--dark2);border:1px solid var(--border);border-radius:10px;padding:12px;cursor:grab;transition:.2s;position:relative}
.card:hover{border-color:rgba(91,106,240,.4);transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.3)}
.card.dragging{opacity:.5;cursor:grabbing}
.card-title{font-size:.82rem;font-weight:600;margin-bottom:8px;line-height:1.4}
.card-tags{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px}
.tag{font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:20px}
.card-meta{display:flex;align-items:center;justify-content:space-between}
.card-avatars{display:flex}
.avatar{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;color:#fff;border:2px solid var(--dark2);margin-left:-6px}
.avatar:first-child{margin-left:0}
.card-due{font-size:.65rem;color:var(--muted);display:flex;align-items:center;gap:3px}
.card-prio{position:absolute;top:10px;right:10px;width:6px;height:6px;border-radius:50%}
.prio-high{background:var(--danger)}
.prio-medium{background:var(--warn)}
.prio-low{background:var(--ok)}

.col-footer{padding:10px;border-top:1px solid var(--border)}
.add-card-btn{width:100%;background:transparent;border:1px dashed rgba(91,106,240,.25);color:var(--muted);font-size:.75rem;padding:7px;border-radius:8px;cursor:pointer;font-family:inherit;transition:.2s;display:flex;align-items:center;justify-content:center;gap:5px}
.add-card-btn:hover{border-color:var(--luna);color:var(--luna);background:rgba(91,106,240,.05)}

/* ── FEATURES ── */
.features{padding:60px 28px}
.features-grid{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
.feat-card{background:var(--dark2);border:1px solid var(--border);border-radius:var(--rad);padding:24px;transition:.3s}
.feat-card:hover{border-color:rgba(91,106,240,.4);transform:translateY(-3px)}
.feat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:14px}
.feat-card h3{font-size:.92rem;font-weight:700;margin-bottom:7px}
.feat-card p{color:var(--muted);font-size:.82rem;line-height:1.6}

/* ── STATS ── */
.stats{padding:40px 28px;background:var(--dark2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.stats-inner{max-width:900px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:24px;text-align:center}
.stat-num{font-size:2.2rem;font-weight:900;letter-spacing:-1px;background:linear-gradient(135deg,var(--luna),var(--luna3));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.stat-label{color:var(--muted);font-size:.78rem;margin-top:4px}

/* ── PLANS ── */
.plans{padding:60px 28px}
.plans-grid{max-width:900px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px}
.plan-card{background:var(--dark2);border:1px solid var(--border);border-radius:var(--rad);padding:24px;position:relative;transition:.3s}
.plan-card:hover{transform:translateY(-4px)}
.plan-card.featured{border-color:var(--luna);box-shadow:0 0 40px rgba(91,106,240,.2)}
.plan-badge{position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--luna),var(--luna2));color:#fff;font-size:.65rem;font-weight:700;padding:3px 12px;border-radius:20px;white-space:nowrap}
.plan-name{font-size:.82rem;font-weight:700;color:var(--muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px}
.plan-price{font-size:2rem;font-weight:900;letter-spacing:-1px;margin-bottom:4px}
.plan-price sup{font-size:.9rem;font-weight:600}
.plan-price span{font-size:.78rem;font-weight:400;color:var(--muted)}
.plan-users{color:var(--muted);font-size:.78rem;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border)}
.plan-features{list-style:none;display:flex;flex-direction:column;gap:7px;margin-bottom:20px}
.plan-features li{font-size:.78rem;display:flex;align-items:center;gap:7px}
.plan-features li i{font-size:.65rem;color:var(--ok)}
.plan-features li.no i{color:var(--muted)}
.plan-features li.no{color:var(--muted)}
.plan-btn{width:100%;padding:9px;border-radius:9px;font-size:.8rem;font-weight:700;cursor:pointer;transition:.2s;font-family:inherit;border:none}
.plan-btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}
.plan-btn-outline:hover{border-color:var(--luna);color:var(--luna)}
.plan-btn-primary{background:linear-gradient(135deg,var(--luna),var(--luna2));color:#fff}
.plan-btn-primary:hover{opacity:.9}

/* ── MODAL CARD DETAIL ── */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:2000;align-items:center;justify-content:center;padding:20px}
.overlay.active{display:flex}
.modal{background:var(--dark2);border:1px solid var(--border);border-radius:20px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto;animation:modalIn .25s ease}
@keyframes modalIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
.modal-header{padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-title{font-size:1rem;font-weight:700}
.modal-close{background:var(--dark3);border:1px solid var(--border);color:var(--muted);width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem;transition:.2s}
.modal-close:hover{color:var(--danger);border-color:var(--danger)}
.modal-body{padding:20px 24px}
.modal-field{margin-bottom:16px}
.modal-field label{display:block;font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.modal-field textarea,.modal-field input{width:100%;background:var(--dark3);border:1px solid var(--border);color:var(--text);border-radius:9px;padding:10px 12px;font-size:.83rem;font-family:inherit;resize:vertical;outline:none;transition:.2s}
.modal-field textarea:focus,.modal-field input:focus{border-color:var(--luna)}
.modal-field textarea{min-height:80px}
.modal-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.checklist{display:flex;flex-direction:column;gap:6px}
.check-item{display:flex;align-items:center;gap:8px;font-size:.82rem;padding:6px 10px;background:var(--dark3);border-radius:8px}
.check-item input[type=checkbox]{accent-color:var(--luna);width:14px;height:14px;cursor:pointer}
.check-item label{cursor:pointer;flex:1}
.check-item label.done{text-decoration:line-through;color:var(--muted)}
.check-add{display:flex;gap:8px;margin-top:6px}
.check-add input{flex:1;background:var(--dark3);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:6px 10px;font-size:.8rem;font-family:inherit;outline:none}
.check-add button{background:var(--luna);color:#fff;border:none;border-radius:8px;padding:6px 12px;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit}
.select-field{width:100%;background:var(--dark3);border:1px solid var(--border);color:var(--text);border-radius:9px;padding:10px 12px;font-size:.83rem;font-family:inherit;outline:none;cursor:pointer}
.select-field:focus{border-color:var(--luna)}
.modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}

/* ── TOAST ── */
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--dark3);border:1px solid var(--border);color:var(--text);padding:11px 20px;border-radius:12px;font-size:.83rem;font-weight:500;z-index:3000;transition:transform .35s cubic-bezier(.34,1.56,.64,1);display:flex;align-items:center;gap:8px;white-space:nowrap}
.toast.show{transform:translateX(-50%) translateY(0)}
.toast i{color:var(--ok)}

/* ── COMPARATIVO ── */
.compare-section{padding:80px 28px}
.compare-wrap{max-width:1000px;margin:0 auto;overflow-x:auto}
.compare-table{width:100%;border-collapse:collapse;font-size:.82rem}
.compare-table th,.compare-table td{padding:13px 16px;text-align:center;border-bottom:1px solid var(--border)}
.compare-table td:first-child{text-align:left;font-weight:500;color:var(--text)}
.compare-table th{font-weight:700;font-size:.75rem;letter-spacing:.5px;padding-bottom:18px;vertical-align:bottom}
.compare-table tbody tr:hover{background:rgba(91,106,240,.04)}
.col-luna{background:rgba(91,106,240,.06)}
.col-luna th{background:linear-gradient(180deg,rgba(91,106,240,.18),rgba(91,106,240,.06))}
.luna-header{background:linear-gradient(135deg,var(--luna),var(--luna2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:.95rem;font-weight:900;display:block;margin-bottom:4px}
.comp-name{color:var(--muted);font-size:.78rem;font-weight:600}
.ico-yes{color:var(--ok);font-size:1rem}
.ico-no{color:#ef4444;opacity:.7;font-size:.85rem}
.ico-partial{color:var(--warn);font-size:.85rem}
.price-tag{font-weight:800;font-size:1rem}
.price-tag.luna-price{background:linear-gradient(135deg,var(--luna),var(--ok));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.price-sub{font-size:.68rem;color:var(--muted);display:block;margin-top:2px}
.compare-table tr.section-row td{background:var(--dark3);color:var(--muted);font-size:.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:8px 16px;border-top:1px solid var(--border)}
.winner-badge{display:inline-block;background:linear-gradient(135deg,var(--luna),var(--luna2));color:#fff;font-size:.6rem;font-weight:800;padding:2px 8px;border-radius:20px;margin-left:6px;vertical-align:middle;letter-spacing:.5px}
.saving-bar{margin-top:32px;background:linear-gradient(135deg,rgba(34,211,160,.1),rgba(91,106,240,.1));border:1px solid rgba(34,211,160,.3);border-radius:16px;padding:24px 32px;display:flex;flex-wrap:wrap;gap:24px;justify-content:center;text-align:center}
.saving-stat{flex:0 0 auto}
.saving-stat .num{font-size:2rem;font-weight:900;line-height:1}
.saving-stat .num.green{color:var(--ok)}
.saving-stat .num.blue{background:linear-gradient(135deg,var(--luna),var(--luna3));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.saving-stat .lbl{font-size:.72rem;color:var(--muted);margin-top:4px}

/* ── CTA FINAL ── */
.cta-section{padding:80px 28px;text-align:center;background:linear-gradient(135deg,rgba(91,106,240,.08),rgba(6,182,212,.05))}
.cta-section h2{font-size:2rem;font-weight:900;letter-spacing:-.8px;margin-bottom:16px}
.cta-section p{color:var(--muted);margin-bottom:32px;font-size:1rem}
.cta-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}

/* ── FOOTER ── */
footer{background:var(--dark2);border-top:1px solid var(--border);padding:24px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
footer p{color:var(--muted);font-size:.78rem}
.footer-logo{font-weight:800;font-size:.9rem;display:flex;align-items:center;gap:8px}
.footer-logo .icon{width:26px;height:26px;background:linear-gradient(135deg,var(--luna),var(--luna2));border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.7rem}

/* ── DRAG HIGHLIGHT ── */
.col.drag-over .col-cards{background:rgba(91,106,240,.06);border-radius:8px}

/* ── RESPONSIVE ── */
@media(max-width:600px){.topbar-cta .btn-ghost{display:none}.hero h1{font-size:1.8rem}.kanban-board{padding:12px}}
</style>
</head>
<body>

<!-- TOP BAR -->
<nav class="topbar">
  <div class="topbar-logo">
    <div class="icon"><i class="fas fa-moon" style="color:#fff"></i></div>
    Luna Workspace
    <span class="demo-badge">DEMO</span>
  </div>
  <div class="topbar-cta">
    <a href="https://websobreruedas.ar" class="btn btn-ghost" target="_blank"><i class="fas fa-info-circle"></i> Más info</a>
    <a href="https://websobreruedas.ar/luna-gratis" class="btn btn-primary"><i class="fas fa-rocket"></i> Empezar gratis</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <h1>Gestión de proyectos<br><span>hecha para tu equipo</span></h1>
  <p>Probá Luna Workspace en acción. Este es un tablero demo completamente funcional — arrastre tarjetas, abrí detalles, explorá todo.</p>
  <div class="hero-btns">
    <a href="#kanban" class="btn btn-primary"><i class="fas fa-play"></i> Probar el tablero</a>
    <a href="#comparativo" class="btn btn-ghost"><i class="fas fa-trophy"></i> Comparativo</a>
    <a href="#planes" class="btn btn-ghost"><i class="fas fa-tag"></i> Ver planes y precios</a>
  </div>
</section>

<!-- KANBAN BOARD DEMO -->
<section class="kanban-section" id="kanban">
  <p class="section-label">Demo en vivo</p>
  <h2 class="section-title">Tablero Kanban interactivo</h2>
  <p class="section-sub">Arrastrá las tarjetas entre columnas · Hacé clic para ver el detalle completo</p>

  <div class="app-shell">
    <div class="app-topbar">
      <div class="ws-dots"><span class="dot dot-r"></span><span class="dot dot-y"></span><span class="dot dot-g"></span></div>
      <!-- Workspace badge -->
      <div class="ws-badge">
        <div class="ws-badge-dot"><i class="fas fa-project-diagram"></i></div>
        <span class="ws-badge-name">Proyecto Web</span>
        <i class="fas fa-chevron-down ws-badge-arrow"></i>
      </div>
      <!-- Vista activa -->
      <button class="vtab-demo"><i class="fas fa-columns"></i> Tareas</button>
      <!-- Búsqueda -->
      <div class="search-demo">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar tareas..." id="demoSearch" oninput="demoSearchFilter(this.value)">
      </div>
      <!-- Spacer -->
      <div style="flex:1"></div>
      <!-- EN VIVO -->
      <div class="live-pill"><div class="live-dot2"></div>EN VIVO</div>
      <div class="tb-sep"></div>
      <!-- Acciones -->
      <button class="tb" title="Equipo"><i class="fas fa-users"></i> <span>Equipo</span></button>
      <button class="tb" title="Calendario"><i class="fas fa-calendar-alt"></i></button>
      <button class="tb" title="Analítica"><i class="fas fa-chart-bar"></i></button>
      <div class="notif-wrap"><button class="tb" title="Notificaciones"><i class="fas fa-bell"></i></button><span class="notif-count">3</span></div>
      <button class="tb" title="Personalizar tema"><i class="fas fa-palette"></i></button>
      <button class="tb" title="Google Drive"><i class="fab fa-google-drive"></i></button>
      <button class="tb" title="Configurar correo SMTP"><i class="fas fa-envelope"></i></button>
      <button class="tb" title="Importar desde Trello"><i class="fas fa-file-import"></i></button>
      <button class="tb accent" id="addColBtn" title="Exportar workspace"><i class="fas fa-download"></i> Exportar</button>
      <div class="tb-sep"></div>
      <!-- Usuario -->
      <div class="user-chip">
        <div class="user-av">DM</div>
        <div class="user-inf"><span class="user-nm">Durval M.</span><span class="user-rl">Admin</span></div>
      </div>
      <button class="tb" style="color:#ef4444;border-color:transparent;background:transparent" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></button>
    </div>

    <div class="kanban-board" id="board">
      <!-- Columnas generadas por JS -->
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="features">
  <p class="section-label">Funcionalidades</p>
  <h2 class="section-title">Todo lo que tu equipo necesita</h2>
  <p class="section-sub">Sin límites artificiales en los planes de pago</p>
  <div class="features-grid">
    <div class="feat-card">
      <div class="feat-icon" style="background:rgba(91,106,240,.15);color:var(--luna)"><i class="fas fa-th-large"></i></div>
      <h3>Tablero Kanban</h3>
      <p>Drag & drop fluido. Columnas personalizables, tarjetas con prioridad, fechas, checklist y asignados.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:rgba(34,211,160,.12);color:var(--ok)"><i class="fas fa-users"></i></div>
      <h3>Gestión de equipo</h3>
      <p>Roles admin / miembro / visitante. Perfiles con foto, cargo, departamento y canal de notificaciones.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:rgba(6,182,212,.12);color:var(--luna3)"><i class="fas fa-bell"></i></div>
      <h3>Notificaciones</h3>
      <p>Alertas por email, WhatsApp y Telegram. Recordatorios automáticos de vencimientos.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:rgba(245,158,11,.12);color:var(--warn)"><i class="fas fa-chart-bar"></i></div>
      <h3>Analítica</h3>
      <p>Panel con velocidad del equipo, tarjetas por estado, tiempos promedio y carga de trabajo por persona.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:rgba(124,58,237,.12);color:var(--luna2)"><i class="fas fa-project-diagram"></i></div>
      <h3>Múltiples proyectos</h3>
      <p>Workspaces ilimitados (según plan). Cada uno con sus columnas, miembros y configuración propia.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:rgba(239,68,68,.12);color:var(--danger)"><i class="fas fa-lock"></i></div>
      <h3>100% tu servidor</h3>
      <p>Plugin WordPress. Tus datos en tu hosting. Sin SaaS, sin mensualidad por usuario, sin dependencias externas.</p>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats">
  <div class="stats-inner">
    <div><div class="stat-num">∞</div><div class="stat-label">Tarjetas por tablero</div></div>
    <div><div class="stat-num">5</div><div class="stat-label">Minutos de instalación</div></div>
    <div><div class="stat-num">3</div><div class="stat-label">Canales de notificación</div></div>
    <div><div class="stat-num">$0</div><div class="stat-label">Costo extra por usuario</div></div>
    <div><div class="stat-num">100%</div><div class="stat-label">Tus datos, tu servidor</div></div>
  </div>
</div>

<!-- PLANES -->
<section class="plans" id="planes">
  <p class="section-label">Precios</p>
  <h2 class="section-title">Un solo pago, para siempre</h2>
  <p class="section-sub">Sin mensualidades por usuario. Sin sorpresas.</p>
  <div class="plans-grid">

    <div class="plan-card">
      <div class="plan-name">Gratis</div>
      <div class="plan-price"><sup>$</sup>0 <span>/ mes</span></div>
      <div class="plan-users"><i class="fas fa-user" style="color:var(--luna)"></i> 1 usuario · 1 pizarra</div>
      <ul class="plan-features">
        <li><i class="fas fa-check"></i> Tablero Kanban básico</li>
        <li><i class="fas fa-check"></i> Tarjetas con checklist</li>
        <li class="no"><i class="fas fa-times"></i> Notificaciones</li>
        <li class="no"><i class="fas fa-times"></i> Múltiples proyectos</li>
        <li class="no"><i class="fas fa-times"></i> Métricas</li>
      </ul>
      <button class="plan-btn plan-btn-outline" onclick="goTo('gratis')">Obtener gratis</button>
    </div>

    <div class="plan-card">
      <div class="plan-name">Básico</div>
      <div class="plan-price"><sup>$</sup>19 <span>/ mes</span></div>
      <div class="plan-users"><i class="fas fa-users" style="color:var(--luna)"></i> Hasta 5 usuarios</div>
      <ul class="plan-features">
        <li><i class="fas fa-check"></i> Todo lo del plan Gratis</li>
        <li><i class="fas fa-check"></i> Notificaciones email</li>
        <li><i class="fas fa-check"></i> Múltiples proyectos</li>
        <li><i class="fas fa-check"></i> Métricas básicas</li>
        <li class="no"><i class="fas fa-times"></i> WhatsApp / Telegram</li>
      </ul>
      <button class="plan-btn plan-btn-outline" onclick="goTo('basico')">Empezar</button>
    </div>

    <div class="plan-card featured">
      <div class="plan-badge"><i class="fas fa-star"></i> MÁS POPULAR</div>
      <div class="plan-name">Profesional</div>
      <div class="plan-price"><sup>$</sup>39 <span>/ mes</span></div>
      <div class="plan-users"><i class="fas fa-users" style="color:var(--luna)"></i> Hasta 20 usuarios</div>
      <ul class="plan-features">
        <li><i class="fas fa-check"></i> Todo lo del plan Básico</li>
        <li><i class="fas fa-check"></i> WhatsApp + Telegram</li>
        <li><i class="fas fa-check"></i> Analítica avanzada</li>
        <li><i class="fas fa-check"></i> Prioridad de soporte</li>
        <li><i class="fas fa-check"></i> Recordatorios automáticos</li>
      </ul>
      <button class="plan-btn plan-btn-primary" onclick="goTo('profesional')">Elegir Profesional</button>
    </div>

    <div class="plan-card">
      <div class="plan-name">Corporativo</div>
      <div class="plan-price"><sup>$</sup>89 <span>/ mes</span></div>
      <div class="plan-users"><i class="fas fa-building" style="color:var(--luna)"></i> Hasta 20 usuarios incluidos</div>
      <ul class="plan-features">
        <li><i class="fas fa-check"></i> Todo lo del plan Pro</li>
        <li><i class="fas fa-check"></i> <strong>$6 por usuario adicional</strong></li>
        <li><i class="fas fa-check"></i> Analítica avanzada + reportes</li>
        <li><i class="fas fa-check"></i> Workspaces ilimitados</li>
        <li><i class="fas fa-check"></i> Soporte dedicado</li>
        <li><i class="fas fa-check"></i> Onboarding incluido</li>
        <li><i class="fas fa-check"></i> Personalización de marca</li>
      </ul>
      <button class="plan-btn plan-btn-outline" onclick="goTo('corporativo')">Contactar</button>
    </div>
  </div>
</section>

<!-- COMPARATIVO -->
<section class="compare-section" id="comparativo">
  <p class="section-label">Comparativo</p>
  <h2 class="section-title">Luna vs la competencia</h2>
  <p class="section-sub">Para un equipo de 10 personas. Precios en USD/mes.</p>

  <div class="compare-wrap">
    <table class="compare-table">
      <thead>
        <tr>
          <th style="text-align:left;width:240px">Característica</th>
          <th class="col-luna">
            <span class="luna-header">🌙 LUNA</span>
            <span class="comp-name">Workspace</span>
          </th>
          <th>
            <span style="display:block;font-size:.9rem;margin-bottom:4px">🟦 Trello</span>
            <span class="comp-name">Premium</span>
          </th>
          <th>
            <span style="display:block;font-size:.9rem;margin-bottom:4px">🟥 Asana</span>
            <span class="comp-name">Premium</span>
          </th>
          <th>
            <span style="display:block;font-size:.9rem;margin-bottom:4px">🟨 Monday</span>
            <span class="comp-name">Standard</span>
          </th>
          <th>
            <span style="display:block;font-size:.9rem;margin-bottom:4px">⬛ Notion</span>
            <span class="comp-name">Business</span>
          </th>
        </tr>
      </thead>
      <tbody>

        <!-- PRECIO -->
        <tr class="section-row"><td colspan="6">PRECIO — equipo de 10 personas/mes</td></tr>
        <tr>
          <td>Costo mensual (10 usuarios)</td>
          <td class="col-luna"><span class="price-tag luna-price">$39</span><span class="price-sub">plan Profesional completo</span></td>
          <td><span class="price-tag">$100</span><span class="price-sub">$10/usuario/mes</span></td>
          <td><span class="price-tag">$109</span><span class="price-sub">$10.99/usuario/mes</span></td>
          <td><span class="price-tag">$120</span><span class="price-sub">$12/usuario/mes</span></td>
          <td><span class="price-tag">$150</span><span class="price-sub">$15/usuario/mes</span></td>
        </tr>
        <tr>
          <td>Costo anual (10 usuarios)</td>
          <td class="col-luna"><span class="price-tag luna-price">$468</span></td>
          <td><span class="price-tag">$1.200</span></td>
          <td><span class="price-tag">$1.308</span></td>
          <td><span class="price-tag">$1.440</span></td>
          <td><span class="price-tag">$1.800</span></td>
        </tr>
        <tr>
          <td>Precio por usuario extra</td>
          <td class="col-luna"><span style="font-size:.82rem;color:var(--ok);font-weight:700">$6/usuario</span><span class="price-sub">plan Corporativo</span></td>
          <td><span style="font-size:.78rem;color:var(--warn)">+$10/usuario</span></td>
          <td><span style="font-size:.78rem;color:var(--warn)">+$10.99/usuario</span></td>
          <td><span style="font-size:.78rem;color:var(--warn)">+$12/usuario</span></td>
          <td><span style="font-size:.78rem;color:var(--warn)">+$15/usuario</span></td>
        </tr>

        <!-- INFRAESTRUCTURA -->
        <tr class="section-row"><td colspan="6">INFRAESTRUCTURA Y PRIVACIDAD</td></tr>
        <tr>
          <td>Datos en tu propio servidor <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>
        <tr>
          <td>Integración nativa WordPress <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>
        <tr>
          <td>Instalación en hosting propio <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>
        <tr>
          <td>Herramientas de mantenimiento DB <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>

        <!-- FUNCIONALIDADES -->
        <tr class="section-row"><td colspan="6">FUNCIONALIDADES</td></tr>
        <tr>
          <td>Tablero Kanban</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
        </tr>
        <tr>
          <td>Alertas vencimiento en tablero <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i> <span style="font-size:.7rem;color:var(--ok)">nativo</span></td>
          <td><i class="fas fa-times ico-partial"></i> <span style="font-size:.7rem;color:var(--muted)">solo badge</span></td>
          <td><i class="fas fa-times ico-partial"></i> <span style="font-size:.7rem;color:var(--muted)">solo badge</span></td>
          <td><i class="fas fa-times ico-partial"></i> <span style="font-size:.7rem;color:var(--muted)">solo badge</span></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>
        <tr>
          <td>Múltiples proyectos / workspaces</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
        </tr>
        <tr>
          <td>Checklists en tarjetas</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
        </tr>
        <tr>
          <td>Asignación de tareas</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
        </tr>
        <tr>
          <td>Panel de Analítica</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>

        <!-- NOTIFICACIONES -->
        <tr class="section-row"><td colspan="6">NOTIFICACIONES</td></tr>
        <tr>
          <td>Email</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
        </tr>
        <tr>
          <td>WhatsApp nativo <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>
        <tr>
          <td>Telegram nativo <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>

        <!-- SOPORTE -->
        <tr class="section-row"><td colspan="6">SOPORTE</td></tr>
        <tr>
          <td>Soporte en español <span class="winner-badge">LUNA</span></td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>
        <tr>
          <td>Interfaz en español</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
          <td><i class="fas fa-check ico-yes"></i></td>
        </tr>
        <tr>
          <td>Onboarding asistido</td>
          <td class="col-luna"><i class="fas fa-check ico-yes"></i> <span style="font-size:.7rem;color:var(--muted)">(plan Corp)</span></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-times ico-no"></i></td>
          <td><i class="fas fa-check ico-yes"></i> <span style="font-size:.7rem;color:var(--muted)">(pago)</span></td>
          <td><i class="fas fa-times ico-no"></i></td>
        </tr>

      </tbody>
    </table>

    <!-- Ahorro -->
    <div class="saving-bar">
      <div class="saving-stat">
        <div class="num green">$732</div>
        <div class="lbl">ahorrás vs Trello/año</div>
      </div>
      <div class="saving-stat">
        <div class="num green">$840</div>
        <div class="lbl">ahorrás vs Asana/año</div>
      </div>
      <div class="saving-stat">
        <div class="num green">$972</div>
        <div class="lbl">ahorrás vs Monday/año</div>
      </div>
      <div class="saving-stat">
        <div class="num green">$1.332</div>
        <div class="lbl">ahorrás vs Notion/año</div>
      </div>
      <div class="saving-stat">
        <div class="num blue">5 únicas</div>
        <div class="lbl">funciones que solo Luna tiene</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section class="cta-section">
  <h2>¿Listo para organizar tu equipo?</h2>
  <p>Empezá con el plan Gratis hoy mismo. Sin tarjeta de crédito.</p>
  <div class="cta-btns">
    <a href="https://websobreruedas.ar/luna-gratis" class="btn btn-green" style="padding:13px 28px;font-size:.9rem"><i class="fas fa-rocket"></i> Obtener Luna Gratis</a>
    <a href="https://websobreruedas.ar" class="btn btn-ghost" style="padding:13px 28px;font-size:.9rem"><i class="fas fa-envelope"></i> Hablar con ventas</a>
  </div>
</section>

<footer>
  <div class="footer-logo">
    <div class="icon"><i class="fas fa-moon" style="color:#fff;font-size:.7rem"></i></div>
    Luna Workspace
  </div>
  <p>© <?php echo date('Y') ?> websobreruedas.ar — Todos los derechos reservados</p>
  <p style="color:var(--luna);font-size:.75rem"><i class="fas fa-shield-alt"></i> Tus datos, tu servidor</p>
</footer>

<!-- MODAL DETALLE DE TARJETA -->
<div class="overlay" id="overlay">
  <div class="modal" id="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Detalle de tarea</div>
      <div class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></div>
    </div>
    <div class="modal-body" id="modalBody">
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Cerrar</button>
      <button class="btn btn-primary" onclick="saveCard()"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i> <span id="toastMsg"></span></div>

<script>
// ── DATA ──────────────────────────────────────────────────────────────────────
const COLORS = {
  todo:   { label:'Por hacer',   color:'#5b6af0' },
  prog:   { label:'En progreso', color:'#f59e0b' },
  rev:    { label:'En revisión', color:'#06b6d4' },
  done:   { label:'Completado',  color:'#22d3a0' },
};

let columns = [
  { id:'todo', ...COLORS.todo, cards: [
    { id:1, title:'Diseñar wireframes del dashboard', tags:[{l:'UI/UX',c:'#5b6af0'}], prio:'high',   due:'2026-07-05', assignees:['LA','MG'], desc:'Crear mockups en Figma para las pantallas principales.', checklist:[{t:'Relevamiento',d:true},{t:'Wireframes',d:true},{t:'Prototipar',d:false}] },
    { id:2, title:'Configurar entorno de desarrollo', tags:[{l:'DevOps',c:'#22d3a0'}], prio:'medium',due:'2026-07-02', assignees:['RV'],       desc:'Instalar dependencias y configurar variables de entorno.', checklist:[{t:'Node.js',d:true},{t:'Docker',d:false}] },
    { id:3, title:'Definir paleta de colores',         tags:[{l:'Diseño',c:'#ec4899'}],prio:'low',   due:'2026-07-10', assignees:['LA'],       desc:'Elegir la paleta de marca del cliente.', checklist:[] },
  ]},
  { id:'prog', ...COLORS.prog, cards: [
    { id:4, title:'Integrar API de pagos',             tags:[{l:'Backend',c:'#7c3aed'},{l:'Urgente',c:'#ef4444'}], prio:'high', due:'2026-07-01', assignees:['RV','MG'], desc:'Conectar Stripe y Mercado Pago como opciones de cobro.', checklist:[{t:'Stripe',d:true},{t:'Mercado Pago',d:false},{t:'Tests',d:false}] },
    { id:5, title:'Componente de login',               tags:[{l:'Frontend',c:'#06b6d4'}], prio:'medium', due:'2026-07-03', assignees:['MG'],    desc:'Formulario de login con validación y remember me.', checklist:[{t:'HTML/CSS',d:true},{t:'Validación',d:true},{t:'2FA',d:false}] },
  ]},
  { id:'rev', ...COLORS.rev, cards: [
    { id:6, title:'Code review del módulo usuarios',   tags:[{l:'Backend',c:'#7c3aed'}], prio:'medium', due:'2026-06-30', assignees:['LA','RV'], desc:'Revisar el módulo de usuarios antes de merge.', checklist:[{t:'Estilo de código',d:true},{t:'Tests unitarios',d:true},{t:'Documentación',d:false}] },
  ]},
  { id:'done', ...COLORS.done, cards: [
    { id:7, title:'Reunión kickoff con el cliente',    tags:[{l:'Gestión',c:'#f59e0b'}], prio:'low',   due:'2026-06-20', assignees:['LA'],       desc:'Primera reunión para alinear expectativas.', checklist:[{t:'Agenda',d:true},{t:'Acta',d:true}] },
    { id:8, title:'Setup del repositorio Git',         tags:[{l:'DevOps',c:'#22d3a0'}], prio:'low',   due:'2026-06-22', assignees:['RV'],        desc:'Crear repo, ramas main/dev y configurar CI básico.', checklist:[{t:'Repo',d:true},{t:'Branches',d:true},{t:'CI',d:true}] },
  ]},
];

const AVATAR_COLORS = { LA:'#5b6af0', MG:'#ec4899', RV:'#22d3a0', JP:'#f59e0b' };
let currentCardId = null;

// ── RENDER ────────────────────────────────────────────────────────────────────
function renderBoard() {
  const board = document.getElementById('board');
  board.innerHTML = '';
  columns.forEach(col => {
    const el = document.createElement('div');
    el.className = 'col';
    el.dataset.colId = col.id;
    el.innerHTML = `
      <div class="col-header">
        <div class="col-name">
          <span class="col-dot" style="background:${col.color}"></span>
          ${col.label}
          <span class="col-count">${col.cards.length}</span>
        </div>
        <span class="col-add" onclick="addCard('${col.id}')"><i class="fas fa-plus"></i></span>
      </div>
      <div class="col-cards" data-col="${col.id}">
        ${col.cards.map(c => renderCard(c)).join('')}
      </div>
      <div class="col-footer">
        <button class="add-card-btn" onclick="addCard('${col.id}')"><i class="fas fa-plus"></i> Agregar tarea</button>
      </div>`;
    board.appendChild(el);
    setupDropZone(el.querySelector('.col-cards'));
  });
  setupDraggables();
  setupColumnDrag();
}

function renderCard(c) {
  const prioClass = c.prio === 'high' ? 'prio-high' : c.prio === 'medium' ? 'prio-medium' : 'prio-low';
  const dueStr = c.due ? new Date(c.due).toLocaleDateString('es-AR',{day:'2-digit',month:'short'}) : '';
  const doneCL = c.checklist.filter(i=>i.d).length;
  const totalCL = c.checklist.length;
  return `<div class="card" data-card-id="${c.id}" draggable="true" onclick="openCard(${c.id})">
    <div class="card-prio ${prioClass}"></div>
    <div class="card-title">${c.title}</div>
    ${c.tags.length?`<div class="card-tags">${c.tags.map(t=>`<span class="tag" style="background:${t.c}22;color:${t.c}">${t.l}</span>`).join('')}</div>`:''}
    <div class="card-meta">
      <div class="card-avatars">${c.assignees.map(a=>`<div class="avatar" style="background:${AVATAR_COLORS[a]||'#5b6af0'}" title="${a}">${a}</div>`).join('')}</div>
      <div style="display:flex;gap:8px;align-items:center">
        ${totalCL?`<span style="font-size:.65rem;color:var(--muted)"><i class="fas fa-check-square" style="color:${doneCL===totalCL?'var(--ok)':'var(--muted)'}"></i> ${doneCL}/${totalCL}</span>`:''}
        ${dueStr?`<div class="card-due"><i class="fas fa-clock"></i>${dueStr}</div>`:''}
      </div>
    </div>
  </div>`;
}

// ── DRAG & DROP ───────────────────────────────────────────────────────────────
let dragCardId = null, dragFromCol = null;

function setupDraggables() {
  document.querySelectorAll('.card').forEach(el => {
    el.addEventListener('dragstart', e => {
      dragCardId = parseInt(el.dataset.cardId);
      const col = el.closest('.col-cards');
      dragFromCol = col ? col.dataset.col : null;
      el.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    el.addEventListener('dragend', () => el.classList.remove('dragging'));
  });
}

function setupDropZone(zone) {
  zone.addEventListener('dragover', e => {
    e.preventDefault(); e.dataTransfer.dropEffect = 'move';
    zone.closest('.col').classList.add('drag-over');
  });
  zone.addEventListener('dragleave', () => zone.closest('.col').classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    const toCol = zone.dataset.col;
    zone.closest('.col').classList.remove('drag-over');
    if (!dragCardId) return;
    // Move card
    let card = null;
    columns = columns.map(c => {
      const idx = c.cards.findIndex(x => x.id === dragCardId);
      if (idx !== -1) { card = c.cards[idx]; c.cards.splice(idx,1); }
      return c;
    });
    if (card) {
      const dest = columns.find(c => c.id === toCol);
      if (dest) dest.cards.push(card);
      renderBoard();
      toast(`Tarea movida a "${COLORS[toCol]?.label || toCol}"`);
    }
    dragCardId = null;
  });
}

let dragColId = null;
function setupColumnDrag() { /* column reorder omitted for simplicity */ }

// ── CARD MODAL ────────────────────────────────────────────────────────────────
function openCard(id) {
  const card = findCard(id);
  if (!card) return;
  currentCardId = id;
  document.getElementById('modalTitle').textContent = card.title;
  document.getElementById('modalBody').innerHTML = `
    <div class="modal-field">
      <label>Título</label>
      <input id="mTitle" value="${card.title}">
    </div>
    <div class="modal-field">
      <label>Descripción</label>
      <textarea id="mDesc">${card.desc||''}</textarea>
    </div>
    <div class="modal-row">
      <div class="modal-field">
        <label>Prioridad</label>
        <select id="mPrio" class="select-field">
          <option value="low" ${card.prio==='low'?'selected':''}>Baja</option>
          <option value="medium" ${card.prio==='medium'?'selected':''}>Media</option>
          <option value="high" ${card.prio==='high'?'selected':''}>Alta</option>
        </select>
      </div>
      <div class="modal-field">
        <label>Fecha límite</label>
        <input type="date" id="mDue" value="${card.due||''}">
      </div>
    </div>
    <div class="modal-field">
      <label><i class="fas fa-check-square" style="color:var(--luna)"></i> Checklist (${card.checklist.filter(i=>i.d).length}/${card.checklist.length})</label>
      <div class="checklist" id="mChecklist">
        ${card.checklist.map((item,i)=>`
          <div class="check-item">
            <input type="checkbox" id="ci${i}" ${item.d?'checked':''} onchange="toggleCheck(${i})">
            <label for="ci${i}" class="${item.d?'done':''}">${item.t}</label>
          </div>`).join('')}
      </div>
      <div class="check-add">
        <input id="newCheck" placeholder="Nueva tarea..." onkeydown="if(event.key==='Enter')addCheck()">
        <button onclick="addCheck()"><i class="fas fa-plus"></i></button>
      </div>
    </div>`;
  document.getElementById('overlay').classList.add('active');
}

function findCard(id) {
  for (const col of columns) {
    const c = col.cards.find(x => x.id === id);
    if (c) return c;
  }
}

function closeModal() {
  document.getElementById('overlay').classList.remove('active');
  currentCardId = null;
}

function saveCard() {
  if (!currentCardId) return;
  const card = findCard(currentCardId);
  if (!card) return;
  card.title = document.getElementById('mTitle').value;
  card.desc  = document.getElementById('mDesc').value;
  card.prio  = document.getElementById('mPrio').value;
  card.due   = document.getElementById('mDue').value;
  renderBoard();
  closeModal();
  toast('Tarea guardada');
}

function toggleCheck(i) {
  if (!currentCardId) return;
  const card = findCard(currentCardId);
  if (card && card.checklist[i]) {
    card.checklist[i].d = !card.checklist[i].d;
    const lbl = document.querySelector(`#mChecklist .check-item:nth-child(${i+1}) label`);
    if (lbl) lbl.classList.toggle('done', card.checklist[i].d);
  }
}

function addCheck() {
  const inp = document.getElementById('newCheck');
  const val = inp.value.trim();
  if (!val || !currentCardId) return;
  const card = findCard(currentCardId);
  if (!card) return;
  card.checklist.push({t:val, d:false});
  inp.value = '';
  // Re-render checklist only
  const i = card.checklist.length - 1;
  const cl = document.getElementById('mChecklist');
  const div = document.createElement('div');
  div.className = 'check-item';
  div.innerHTML = `<input type="checkbox" id="ci${i}" onchange="toggleCheck(${i})"><label for="ci${i}">${val}</label>`;
  cl.appendChild(div);
}

function addCard(colId) {
  event.stopPropagation();
  const col = columns.find(c => c.id === colId);
  if (!col) return;
  const id = Date.now();
  col.cards.push({ id, title:'Nueva tarea', tags:[], prio:'low', due:'', assignees:[], desc:'', checklist:[] });
  renderBoard();
  openCard(id);
}

document.getElementById('overlay').addEventListener('click', e => { if (e.target===e.currentTarget) closeModal(); });

// ── ADD COLUMN ────────────────────────────────────────────────────────────────
document.getElementById('addColBtn').addEventListener('click', () => {
  const name = prompt('Nombre de la nueva columna:');
  if (!name) return;
  const id = 'col_' + Date.now();
  const color = ['#8b5cf6','#f97316','#ec4899','#14b8a6'][Math.floor(Math.random()*4)];
  columns.push({ id, label:name, color, cards:[] });
  renderBoard();
  toast(`Columna "${name}" creada`);
});

// ── TOAST ─────────────────────────────────────────────────────────────────────
function toast(msg) {
  const t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

function demoSearchFilter(q) {
  q = q.toLowerCase().trim();
  document.querySelectorAll('.kcard').forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = (!q || text.includes(q)) ? '' : 'none';
  });
}

function goTo(plan) {
  const urls = { gratis:'https://websobreruedas.ar/luna-gratis', basico:'https://websobreruedas.ar/luna-gratis', profesional:'https://websobreruedas.ar/luna-gratis', corporativo:'https://websobreruedas.ar' };
  window.open(urls[plan]||'https://websobreruedas.ar','_blank');
}

// ── INIT ──────────────────────────────────────────────────────────────────────
renderBoard();
</script>
</body>
</html>
