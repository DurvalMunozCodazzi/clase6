<?php
// Luna Workspace — Página de ventas animada
// Colocar en la raíz de WordPress o usar como template de página
// URL sugerida: websobreruedas.com/luna  o  /luna-workspace
$reg_url   = '/luna-gratis';   // URL del shortcode [luna_gratis]
$plans_url = '/luna-planes';   // URL de planes pagos (opcional)
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Luna Workspace — La herramienta que el resto no puede igualar</title>
<meta name="description" content="Kanban, calendario y notificaciones por WhatsApp en tu propio servidor. 25% más barato que Trello. Gratis para empezar.">
<style>
:root{
  --acc:#5b6af0;--acc2:#8b5cf6;--grn:#22c55e;--red:#ef4444;--ora:#f59e0b;--cya:#06b6d4;--pin:#ec4899;
  --bg:#080b14;--surf:#0f1322;--elev:#161b2e;--bdr:#1e2540;--bdrl:#2a3260;
  --t1:#e8edf8;--t2:#8892c0;--t3:#3d4470;--f:'Segoe UI',system-ui,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0;-webkit-font-smoothing:antialiased}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--t1);font-family:var(--f);overflow-x:hidden}
a{text-decoration:none;color:inherit}

/* ── REVEAL ON SCROLL ── */
.reveal{opacity:0;transform:translateY(32px);transition:opacity .7s ease,transform .7s ease}
.reveal.up{opacity:0;transform:translateY(32px)}
.reveal.left{opacity:0;transform:translateX(-32px)}
.reveal.right{opacity:0;transform:translateX(32px)}
.reveal.visible{opacity:1!important;transform:none!important}

/* ── NAV ── */
nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid transparent;transition:.3s;backdrop-filter:blur(0)}
nav.scrolled{background:rgba(8,11,20,.92);border-bottom-color:var(--bdr);backdrop-filter:blur(16px)}
.nav-logo{font-size:17px;font-weight:900;display:flex;align-items:center;gap:8px}
.nav-logo span{background:linear-gradient(135deg,#a5b4fc,#67e8f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-cta{background:linear-gradient(135deg,var(--acc),var(--acc2));color:#fff;padding:8px 20px;border-radius:99px;font-size:13px;font-weight:800;transition:.2s;border:none;cursor:pointer;white-space:nowrap}
.nav-cta:hover{transform:translateY(-1px);box-shadow:0 4px 20px rgba(91,106,240,.4)}

/* ── HERO ── */
.hero{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:100px 24px 60px;position:relative;overflow:hidden}
.hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse 100% 60% at 50% -10%,rgba(91,106,240,.25),transparent 70%)}
.hero-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(30,37,64,.25) 1px,transparent 1px),linear-gradient(90deg,rgba(30,37,64,.25) 1px,transparent 1px);background-size:40px 40px;mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black,transparent)}

/* orbiting badges */
.hero-orbit{position:absolute;inset:0;pointer-events:none}
.orb{position:absolute;padding:8px 14px;border-radius:99px;font-size:11px;font-weight:700;border:1px solid;white-space:nowrap;animation:orbit-float 6s ease-in-out infinite}
.orb:nth-child(1){top:18%;left:8%;background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.3);color:#86efac;animation-delay:0s}
.orb:nth-child(2){top:22%;right:9%;background:rgba(91,106,240,.1);border-color:rgba(91,106,240,.3);color:#a5b4fc;animation-delay:1.2s}
.orb:nth-child(3){top:55%;left:5%;background:rgba(6,182,212,.1);border-color:rgba(6,182,212,.3);color:#67e8f9;animation-delay:2.4s}
.orb:nth-child(4){top:60%;right:6%;background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.3);color:#fde68a;animation-delay:0.8s}
.orb:nth-child(5){top:38%;left:3%;background:rgba(139,92,246,.1);border-color:rgba(139,92,246,.3);color:#c4b5fd;animation-delay:3.5s}
@keyframes orbit-float{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-14px) scale(1.03)}}
@media(max-width:768px){.orb{display:none}}

.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#86efac;font-size:11px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;padding:6px 16px;border-radius:99px;margin-bottom:28px;animation:fadein .6s ease}
.hero-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--grn);animation:blink 1.4s ease infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

.hero h1{font-size:clamp(32px,6vw,68px);font-weight:900;letter-spacing:-2px;line-height:1.05;margin-bottom:20px;animation:fadein .7s ease}
.hero h1 .grad{background:linear-gradient(135deg,#a5b4fc 0%,#67e8f9 45%,#c4b5fd 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero h1 .strike{position:relative;color:var(--t3)}
.hero h1 .strike::after{content:'';position:absolute;left:0;right:0;top:50%;height:3px;background:var(--red);transform:rotate(-3deg)}

.hero-sub{font-size:clamp(15px,2.2vw,19px);color:var(--t2);max-width:620px;margin:0 auto 40px;line-height:1.7;animation:fadein .8s ease}
.hero-sub strong{color:var(--t1)}

.hero-ctas{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;animation:fadein .9s ease}
.btn-main{background:linear-gradient(135deg,var(--acc),var(--acc2));color:#fff;padding:15px 32px;border-radius:12px;font-size:15px;font-weight:800;box-shadow:0 8px 30px rgba(91,106,240,.4);transition:.2s;border:none;cursor:pointer}
.btn-main:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(91,106,240,.55)}
.btn-sec{background:transparent;color:var(--t1);padding:14px 28px;border-radius:12px;font-size:15px;font-weight:700;border:1.5px solid var(--bdr);transition:.2s;cursor:pointer}
.btn-sec:hover{border-color:var(--bdrl);background:rgba(255,255,255,.04)}

/* trust strip */
.trust-strip{display:flex;gap:28px;justify-content:center;margin-top:52px;flex-wrap:wrap;animation:fadein 1s ease}
.trust-item{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--t3)}
.trust-item span{color:var(--t2);font-weight:700}

/* scroll hint */
.scroll-hint{position:absolute;bottom:32px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--t3);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;animation:fadein 1.2s ease}
.scroll-hint-arrow{width:20px;height:30px;border:2px solid var(--t3);border-radius:99px;position:relative}
.scroll-hint-arrow::after{content:'';width:4px;height:4px;border-radius:50%;background:var(--t3);position:absolute;left:50%;transform:translateX(-50%);top:4px;animation:scroll-dot 1.8s ease infinite}
@keyframes scroll-dot{0%{top:4px;opacity:1}80%{top:16px;opacity:.2}100%{top:4px;opacity:1}}

@keyframes fadein{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}

/* ── SECTIONS ── */
section{padding:96px 24px;max-width:1160px;margin:0 auto}
.sec-badge{display:inline-block;background:rgba(91,106,240,.15);border:1px solid rgba(91,106,240,.3);color:#a5b4fc;font-size:10px;font-weight:800;padding:3px 13px;border-radius:99px;letter-spacing:.7px;text-transform:uppercase;margin-bottom:14px}
.sec-title{font-size:clamp(24px,4vw,40px);font-weight:900;letter-spacing:-.8px;line-height:1.15;margin-bottom:12px}
.sec-title em{font-style:normal;background:linear-gradient(135deg,#a5b4fc,#67e8f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.sec-sub{font-size:15px;color:var(--t2);line-height:1.7;max-width:560px;margin-bottom:48px}
.sec-center{text-align:center}
.sec-center .sec-sub{margin-left:auto;margin-right:auto}

/* divider */
.divider{height:1px;background:linear-gradient(90deg,transparent,var(--bdr),transparent);margin:0}

/* ── PAIN SECTION ── */
.pain-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media(max-width:800px){.pain-grid{grid-template-columns:1fr}}
.pain-card{background:var(--surf);border:1px solid var(--bdr);border-radius:16px;padding:28px;position:relative;overflow:hidden;transition:.3s}
.pain-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--red);transform:scaleX(0);transform-origin:left;transition:.5s}
.pain-card.visible::before{transform:scaleX(1)}
.pain-ico{font-size:32px;margin-bottom:14px}
.pain-title{font-size:15px;font-weight:800;color:var(--t1);margin-bottom:8px}
.pain-desc{font-size:13px;color:var(--t2);line-height:1.6}
.pain-price{font-size:22px;font-weight:900;color:var(--red);margin-top:12px}
.pain-price span{font-size:12px;color:var(--t3);font-weight:400}

/* ── PRICE SLIDER ── */
.slider-wrap{background:linear-gradient(135deg,var(--surf),var(--elev));border:1px solid var(--bdr);border-radius:24px;padding:40px;margin-top:16px;position:relative;overflow:hidden}
.slider-wrap::before{content:'';position:absolute;top:-40px;right:-40px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(91,106,240,.12),transparent);pointer-events:none}
.slider-label{font-size:14px;font-weight:700;color:var(--t2);margin-bottom:16px}
.slider-label strong{color:var(--t1);font-size:18px}
input[type=range]{width:100%;-webkit-appearance:none;height:6px;border-radius:99px;background:var(--bdr);outline:none;margin-bottom:32px;cursor:pointer}
input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,var(--acc),var(--acc2));cursor:pointer;box-shadow:0 0 0 4px rgba(91,106,240,.25),0 4px 12px rgba(91,106,240,.4);transition:.2s}
input[type=range]::-webkit-slider-thumb:hover{transform:scale(1.15)}
.price-race{display:flex;flex-direction:column;gap:10px}
.price-row{display:flex;align-items:center;gap:14px}
.price-row-lbl{font-size:12px;font-weight:800;min-width:88px;text-align:right;color:var(--t2)}
.price-row-lbl.luna{color:#a5b4fc}
.price-bar-wrap{flex:1;background:rgba(30,37,64,.5);border-radius:99px;height:32px;overflow:hidden;position:relative}
.price-bar{height:100%;border-radius:99px;display:flex;align-items:center;padding-left:12px;font-size:12px;font-weight:800;color:#fff;transition:width .5s cubic-bezier(.4,0,.2,1);min-width:52px;white-space:nowrap;position:relative}
.price-bar.luna-bar{background:linear-gradient(90deg,var(--acc),var(--acc2));box-shadow:0 0 20px rgba(91,106,240,.4)}
.price-bar.comp-bar{background:rgba(239,68,68,.7)}
.price-saving{text-align:center;margin-top:24px;padding:16px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:14px}
.price-saving-num{font-size:36px;font-weight:900;color:var(--grn);line-height:1}
.price-saving-lbl{font-size:13px;color:var(--t2);margin-top:4px}

/* ── WA DEMO ── */
.wa-demo-wrap{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center}
@media(max-width:800px){.wa-demo-wrap{grid-template-columns:1fr}}
.phone-mockup{background:#1a1a2e;border-radius:36px;padding:16px;box-shadow:0 32px 80px rgba(0,0,0,.6),0 0 0 2px rgba(255,255,255,.05);max-width:300px;margin:0 auto}
.phone-notch{width:80px;height:20px;background:#000;border-radius:99px;margin:0 auto 16px}
.phone-screen{background:#0a0a14;border-radius:24px;padding:16px;min-height:380px;position:relative;overflow:hidden}
.phone-status{display:flex;justify-content:space-between;font-size:10px;color:#555;margin-bottom:20px}
/* whatsapp ui */
.wa-header{background:#111827;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-bottom:16px}
.wa-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--grn),var(--cya));display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.wa-contact{font-size:12px;font-weight:800;color:#e8edf8}
.wa-status{font-size:10px;color:#22c55e;margin-top:1px}
.wa-msgs{display:flex;flex-direction:column;gap:8px}
.wa-msg{max-width:85%;padding:10px 13px;border-radius:12px;font-size:11px;line-height:1.5;position:relative}
.wa-msg.out{background:#1f3a2a;align-self:flex-end;border-radius:12px 12px 2px 12px;color:#d1fae5}
.wa-msg.in{background:#1e2332;align-self:flex-start;border-radius:12px 12px 12px 2px;color:#e8edf8}
.wa-msg.in strong{color:#67e8f9}
.wa-msg-time{font-size:9px;color:rgba(255,255,255,.3);margin-top:4px;text-align:right}
.wa-typing{display:flex;gap:4px;align-items:center;padding:8px 12px;background:#1e2332;border-radius:12px;width:fit-content;opacity:0;transition:.3s}
.wa-typing span{width:6px;height:6px;border-radius:50%;background:var(--t3);animation:typing .8s ease infinite}
.wa-typing span:nth-child(2){animation-delay:.15s}
.wa-typing span:nth-child(3){animation-delay:.3s}
@keyframes typing{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}
.wa-notification{position:absolute;top:-80px;left:0;right:0;background:#1e2332;border:1px solid rgba(34,197,94,.3);border-radius:14px;padding:12px 14px;display:flex;gap:10px;align-items:center;transition:top .5s cubic-bezier(.34,1.56,.64,1);z-index:10}
.wa-notification.show{top:8px}
.wa-notif-icon{font-size:20px;flex-shrink:0}
.wa-notif-title{font-size:11px;font-weight:800;color:var(--t1)}
.wa-notif-body{font-size:10px;color:var(--t2);margin-top:2px}
/* no-wa competitors */
.nocomp-grid{display:flex;flex-direction:column;gap:12px}
.nocomp-row{background:var(--surf);border:1px solid var(--bdr);border-radius:12px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;font-size:12px;transition:.3s}
.nocomp-row:hover{border-color:var(--bdrl)}
.nocomp-name{font-weight:700;color:var(--t2)}
.nocomp-status{font-size:10px;font-weight:800;padding:4px 10px;border-radius:99px}
.nocomp-status.no{background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.2)}
.nocomp-status.yes{background:rgba(34,197,94,.1);color:#86efac;border:1px solid rgba(34,197,94,.2)}
.nocomp-status.partial{background:rgba(245,158,11,.1);color:#fde68a;border:1px solid rgba(245,158,11,.2)}

/* ── UNIQUE FEATURES ── */
.unique-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media(max-width:800px){.unique-grid{grid-template-columns:1fr}}
.unique-card{border-radius:20px;padding:32px;position:relative;overflow:hidden;transition:.3s}
.unique-card:hover{transform:translateY(-4px)}
.unique-card.u1{background:linear-gradient(145deg,rgba(34,197,94,.1),rgba(6,182,212,.06));border:1.5px solid rgba(34,197,94,.3)}
.unique-card.u2{background:linear-gradient(145deg,rgba(91,106,240,.1),rgba(139,92,246,.07));border:1.5px solid rgba(91,106,240,.35)}
.unique-card.u3{background:linear-gradient(145deg,rgba(245,158,11,.08),rgba(236,72,153,.05));border:1.5px solid rgba(245,158,11,.3)}
.unique-tag{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;padding:4px 12px;border-radius:99px;display:inline-block;margin-bottom:16px}
.unique-ico{font-size:40px;margin-bottom:14px;display:block}
.unique-title{font-size:18px;font-weight:900;margin-bottom:10px}
.unique-desc{font-size:13px;color:var(--t2);line-height:1.65}
.unique-proof{margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06);font-size:12px;color:var(--t3);line-height:1.5}
.unique-proof strong{color:var(--t2)}

/* ── LIVE COMPARISON CARDS ── */
.comp-battle{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:8px}
@media(max-width:700px){.comp-battle{grid-template-columns:1fr}}
.battle-card{background:var(--surf);border:1px solid var(--bdr);border-radius:18px;overflow:hidden}
.battle-card.luna-side{border-color:rgba(91,106,240,.45);background:linear-gradient(145deg,rgba(91,106,240,.08),rgba(139,92,246,.05))}
.battle-head{padding:18px 22px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;gap:12px}
.battle-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.battle-name{font-size:14px;font-weight:900}
.battle-cat{font-size:10px;color:var(--t3);font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-top:1px}
.battle-body{padding:18px 22px}
.battle-feat{display:flex;align-items:flex-start;gap:10px;padding:7px 0;border-bottom:1px solid rgba(30,37,64,.6);font-size:12px;color:var(--t2)}
.battle-feat:last-child{border:none}
.bf-icon{flex-shrink:0;font-size:14px;width:18px;text-align:center;margin-top:1px}
.bf-win{color:var(--grn)}.bf-los{color:var(--red)}.bf-par{color:var(--ora)}
.battle-verdict{margin-top:14px;padding:12px 16px;border-radius:10px;font-size:12px;font-weight:700;text-align:center}
.verdict-win{background:rgba(34,197,94,.1);color:#86efac;border:1px solid rgba(34,197,94,.25)}
.verdict-los{background:rgba(239,68,68,.08);color:#fca5a5;border:1px solid rgba(239,68,68,.2)}

/* ── COMPETITOR SELECTOR ── */
.comp-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px}
.comp-tab{padding:8px 18px;border-radius:99px;font-size:12px;font-weight:700;border:1.5px solid var(--bdr);color:var(--t2);cursor:pointer;transition:.2s;background:transparent}
.comp-tab:hover{border-color:var(--bdrl);color:var(--t1)}
.comp-tab.active{border-color:var(--acc);color:#a5b4fc;background:rgba(91,106,240,.1)}

/* ── SELF HOSTED ── */
.hosted-wrap{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center}
@media(max-width:800px){.hosted-wrap{grid-template-columns:1fr}}
.server-anim{background:var(--surf);border:1px solid var(--bdr);border-radius:20px;padding:24px;font-family:'Courier New',monospace;font-size:11px;color:#22c55e;line-height:2;position:relative;overflow:hidden}
.server-anim::before{content:'● websobreruedas.com';position:absolute;top:0;left:0;right:0;background:var(--elev);padding:10px 16px;font-size:11px;color:var(--t2);font-family:var(--f);font-weight:700;border-bottom:1px solid var(--bdr)}
.server-anim-body{margin-top:36px}
.server-line{opacity:0;animation:line-appear .3s ease forwards}
.server-line.ok::before{content:'✓ ';color:#22c55e}
.server-line.info::before{content:'→ ';color:#67e8f9}
.server-line.warn::before{content:'⚠ ';color:#f59e0b}
.server-line span{color:var(--t2)}
@keyframes line-appear{to{opacity:1}}
.lock-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#86efac;padding:8px 16px;border-radius:99px;font-size:12px;font-weight:700;margin-bottom:16px}
.hosted-list{list-style:none;font-size:14px;color:var(--t2);display:flex;flex-direction:column;gap:12px;margin-top:8px}
.hosted-list li{display:flex;align-items:flex-start;gap:12px;line-height:1.5}
.hosted-list li::before{content:'✓';color:var(--grn);font-weight:900;flex-shrink:0;margin-top:2px}

/* ── CTA FINAL ── */
.cta-section{background:linear-gradient(135deg,#0a0d1a 0%,#111630 50%,#0a0d1a 100%);border-top:1px solid var(--bdr);border-bottom:1px solid var(--bdr);padding:96px 24px;text-align:center;position:relative;overflow:hidden}
.cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 50% 50%,rgba(91,106,240,.18),transparent)}
.cta-section::after{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:500px;height:1px;background:linear-gradient(90deg,transparent,rgba(91,106,240,.5),transparent)}
.cta-title{font-size:clamp(28px,5vw,52px);font-weight:900;letter-spacing:-1.5px;line-height:1.1;margin-bottom:16px;position:relative;z-index:1}
.cta-sub{font-size:16px;color:var(--t2);margin-bottom:36px;line-height:1.7;position:relative;z-index:1;max-width:560px;margin-left:auto;margin-right:auto}
.cta-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1}
.cta-trust{margin-top:28px;font-size:12px;color:var(--t3);position:relative;z-index:1}

/* ── FOOTER ── */
footer{padding:40px 32px;border-top:1px solid var(--bdr);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;max-width:1160px;margin:0 auto;font-size:12px;color:var(--t3)}
footer a{color:var(--t2);transition:.2s}
footer a:hover{color:var(--t1)}

/* ── FLOATING CTA ── */
.float-cta{position:fixed;bottom:24px;right:24px;z-index:50;transition:.3s;transform:translateY(100px);opacity:0}
.float-cta.show{transform:none;opacity:1}
.float-cta a{background:linear-gradient(135deg,var(--acc),var(--acc2));color:#fff;padding:12px 24px;border-radius:99px;font-size:13px;font-weight:800;box-shadow:0 8px 30px rgba(91,106,240,.5);display:flex;align-items:center;gap:8px;transition:.2s}
.float-cta a:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(91,106,240,.6)}

@media(max-width:600px){
  section{padding:64px 16px}
  .slider-wrap{padding:24px}
  .wa-demo-wrap,.hosted-wrap{gap:32px}
}
</style>
</head>
<body>

<!-- NAV -->
<nav id="main-nav">
  <div class="nav-logo">🌙 <span>Luna Workspace</span></div>
  <a href="<?= $reg_url ?>" class="nav-cta">Empezar gratis →</a>
</nav>

<!-- ══ HERO ════════════════════════════════════════════════════════════════ -->
<div class="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>
  <div class="hero-orbit">
    <div class="orb">📲 WhatsApp nativo</div>
    <div class="orb">🔒 Tus datos, tu servidor</div>
    <div class="orb">💰 25% más barato</div>
    <div class="orb">⚡ Ultra rápido</div>
    <div class="orb">🌍 Hecho para LATAM</div>
  </div>

  <div style="position:relative;z-index:1;max-width:800px">
    <div class="hero-badge">Disponible ahora · Plan Gratis sin tarjeta</div>
    <h1>
      Dejá de pagar de más<br>por herramientas que<br>
      <span class="strike">no te entienden</span>
    </h1>
    <p class="hero-sub">
      Luna Workspace es el único kanban <strong>self-hosted</strong> con notificaciones nativas por <strong>WhatsApp y Telegram</strong>. Instalado en tu WordPress. Tus datos, en tu servidor. <strong>25% más barato que Trello</strong> para cualquier equipo.
    </p>
    <div class="hero-ctas">
      <a href="<?= $reg_url ?>" class="btn-main">🚀 Empezar gratis ahora</a>
      <a href="#comparar" class="btn-sec">Ver comparativa →</a>
    </div>
    <div class="trust-strip">
      <div class="trust-item">✓ <span>Sin tarjeta de crédito</span></div>
      <div class="trust-item">✓ <span>Gratis para siempre (1 usuario)</span></div>
      <div class="trust-item">✓ <span>Activo en 5 minutos</span></div>
      <div class="trust-item">✓ <span>Datos 100% en tu servidor</span></div>
    </div>
  </div>

  <div class="scroll-hint">
    <div class="scroll-hint-arrow"></div>
    Descubrí la diferencia
  </div>
</div>

<div class="divider"></div>

<!-- ══ EL PROBLEMA ════════════════════════════════════════════════════════ -->
<section>
  <div class="sec-center reveal up">
    <div class="sec-badge">El problema</div>
    <h2 class="sec-title">¿Cuánto te cuesta realmente<br><em>la competencia?</em></h2>
    <p class="sec-sub">Con 10 personas en el equipo, esto es lo que pagás cada mes sin siquiera darte cuenta.</p>
  </div>
  <div class="pain-grid">
    <div class="pain-card reveal left">
      <div class="pain-ico">🟦</div>
      <div class="pain-title">Trello Standard</div>
      <div class="pain-desc">$5 por usuario al mes. Suena barato hasta que el equipo crece.</div>
      <div class="pain-price">$50/mes <span>· 10 usuarios</span></div>
    </div>
    <div class="pain-card reveal up">
      <div class="pain-ico">🔴</div>
      <div class="pain-title">Monday.com Basic</div>
      <div class="pain-desc">$9 por usuario. Mínimo 3 asientos obligatorios aunque seas 1 solo.</div>
      <div class="pain-price">$90/mes <span>· 10 usuarios</span></div>
    </div>
    <div class="pain-card reveal right">
      <div class="pain-ico">🔆</div>
      <div class="pain-title">Asana Starter</div>
      <div class="pain-desc">$10.99 por usuario. La función de automatización solo está en planes superiores.</div>
      <div class="pain-price">$110/mes <span>· 10 usuarios</span></div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ══ PRICE SLIDER ══════════════════════════════════════════════════════ -->
<section id="comparar">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start">
    <div class="reveal left">
      <div class="sec-badge">Calculadora</div>
      <h2 class="sec-title">Mové el slider y<br><em>mirá la diferencia</em></h2>
      <p class="sec-sub">Luna cobra una tarifa fija sin importar cuántos usuarios tengás en el rango. El resto escala indefinidamente.</p>
      <div style="background:var(--elev);border-radius:14px;padding:20px;font-size:13px;color:var(--t2);line-height:1.7;border:1px solid var(--bdr)">
        <strong style="color:var(--t1)">¿Por qué tarifa plana?</strong><br>
        En LATAM los equipos crecen rápido. Con un modelo por usuario cada incorporación es una sorpresa en la factura. Con Luna sabés exactamente cuánto pagás cada mes.
      </div>
    </div>
    <div class="reveal right">
      <div class="slider-wrap">
        <div class="slider-label">Tamaño del equipo: <strong id="slider-val">10 personas</strong></div>
        <input type="range" id="team-slider" min="1" max="50" value="10">
        <div class="price-race" id="price-race"></div>
        <div class="price-saving">
          <div class="price-saving-num" id="saving-num">$612</div>
          <div class="price-saving-lbl" id="saving-lbl">ahorrás por año vs Monday.com</div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ══ WHATSAPP ÚNICO ═════════════════════════════════════════════════════ -->
<section>
  <div class="wa-demo-wrap">
    <div class="reveal left">
      <div class="sec-badge">Solo en Luna</div>
      <h2 class="sec-title">WhatsApp nativo.<br><em>El único en el mercado.</em></h2>
      <p class="sec-sub">En LATAM el 70% de la comunicación laboral pasa por WhatsApp. Tus tareas viven donde ya trabajás — sin apps extra, sin integraciones de pago, sin Zapier.</p>

      <div class="nocomp-grid">
        <div class="nocomp-row"><span class="nocomp-name">🌙 Luna Workspace</span><span class="nocomp-status yes">✓ WhatsApp nativo</span></div>
        <div class="nocomp-row"><span class="nocomp-name">🟦 Trello</span><span class="nocomp-status no">✗ No disponible</span></div>
        <div class="nocomp-row"><span class="nocomp-name">🔴 Monday.com</span><span class="nocomp-status no">✗ No disponible</span></div>
        <div class="nocomp-row"><span class="nocomp-name">🔆 Asana</span><span class="nocomp-status no">✗ No disponible</span></div>
        <div class="nocomp-row"><span class="nocomp-name">💜 ClickUp</span><span class="nocomp-status partial">~ Solo via Zapier ($)</span></div>
        <div class="nocomp-row"><span class="nocomp-name">🔲 Notion</span><span class="nocomp-status no">✗ No disponible</span></div>
      </div>
    </div>

    <div class="reveal right">
      <div class="phone-mockup">
        <div class="phone-notch"></div>
        <div class="phone-screen">
          <div class="phone-status"><span>9:41</span><span>●●●</span></div>
          <div class="wa-notification" id="wa-notif">
            <div class="wa-notif-icon">🌙</div>
            <div><div class="wa-notif-title">Luna Workspace</div><div class="wa-notif-body">Tarea vencida: "Entrega propuesta"</div></div>
          </div>
          <div class="wa-header">
            <div class="wa-avatar">🌙</div>
            <div><div class="wa-contact">Luna Workspace</div><div class="wa-status">● en línea</div></div>
          </div>
          <div class="wa-msgs" id="wa-msgs">
            <div class="wa-msg out">Hola! Configuré las notificaciones de tareas 😊<div class="wa-msg-time">9:30 ✓✓</div></div>
            <div class="wa-msg in"><strong>Luna Workspace</strong><br>⚠️ Recordatorio: "Diseñar logo cliente" vence hoy a las 18hs. Responsable: María.<div class="wa-msg-time">9:35</div></div>
            <div class="wa-msg in" id="wa-new" style="display:none"><strong>Luna Workspace</strong><br>✅ Tarea completada: "Reunión kickoff" marcada por Juan.<div class="wa-msg-time">9:41</div></div>
          </div>
          <div class="wa-typing" id="wa-typing">
            <span></span><span></span><span></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ══ VENTAJAS ÚNICAS ═══════════════════════════════════════════════════ -->
<section class="sec-center">
  <div class="reveal up">
    <div class="sec-badge">Diferenciadores</div>
    <h2 class="sec-title">3 razones por las que<br><em>no existe nada igual</em></h2>
    <p class="sec-sub">No son funciones que prometemos agregar — están disponibles hoy, en el plan Gratis.</p>
  </div>
  <div class="unique-grid">
    <div class="unique-card u1 reveal left">
      <div class="unique-tag" style="background:rgba(34,197,94,.15);color:#86efac;border:1px solid rgba(34,197,94,.25)">Solo en Luna</div>
      <span class="unique-ico">📲</span>
      <div class="unique-title" style="color:#86efac">WhatsApp & Telegram</div>
      <div class="unique-desc">Notificaciones de tareas, recordatorios y alertas de vencimiento directo en el chat. Sin configuraciones complicadas, sin costo adicional.</div>
      <div class="unique-proof">En LATAM el <strong>70% de la comunicación laboral</strong> es por WhatsApp. Por eso lo integramos directamente.</div>
    </div>
    <div class="unique-card u2 reveal up">
      <div class="unique-tag" style="background:rgba(91,106,240,.15);color:#a5b4fc;border:1px solid rgba(91,106,240,.25)">Solo en Luna</div>
      <span class="unique-ico">🏠</span>
      <div class="unique-title" style="color:#a5b4fc">Tus datos, tu servidor</div>
      <div class="unique-desc">Se instala en tu WordPress. Tus tareas, usuarios y archivos nunca salen de tu hosting. Cumplimiento de privacidad garantizado.</div>
      <div class="unique-proof">Ideal para <strong>salud, jurídico y finanzas</strong> donde los datos no pueden estar en servidores de terceros.</div>
    </div>
    <div class="unique-card u3 reveal right">
      <div class="unique-tag" style="background:rgba(245,158,11,.12);color:#fde68a;border:1px solid rgba(245,158,11,.25)">Solo en Luna</div>
      <span class="unique-ico">💰</span>
      <div class="unique-title" style="color:#fde68a">Precio que no sube</div>
      <div class="unique-desc">Tarifa plana por rango de usuarios. Si pasás de 8 a 18 personas en el equipo, no pagás ni un peso más. Tu presupuesto, predecible.</div>
      <div class="unique-proof">Un equipo de <strong>20 personas paga $39/mes</strong>. En Monday pagarían $180. En Trello $100.</div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ══ COMPARATIVA INTERACTIVA ══════════════════════════════════════════ -->
<section>
  <div class="reveal up sec-center">
    <div class="sec-badge">Luna vs competencia</div>
    <h2 class="sec-title"><em>Cara a cara</em> con cada rival</h2>
    <p class="sec-sub">Elegí un competidor y mirá cómo se compara con Luna en las métricas que importan.</p>
  </div>

  <div class="comp-tabs reveal up" id="comp-tabs">
    <button class="comp-tab active" data-comp="trello">🟦 Trello</button>
    <button class="comp-tab" data-comp="monday">🔴 Monday</button>
    <button class="comp-tab" data-comp="asana">🔆 Asana</button>
    <button class="comp-tab" data-comp="clickup">💜 ClickUp</button>
    <button class="comp-tab" data-comp="notion">🔲 Notion</button>
  </div>

  <div class="comp-battle" id="comp-battle"></div>
</section>

<div class="divider"></div>

<!-- ══ SELF-HOSTED ════════════════════════════════════════════════════════ -->
<section>
  <div class="hosted-wrap">
    <div class="reveal left">
      <div class="lock-badge">🔒 100% privado</div>
      <h2 class="sec-title">Tu información<br>nunca sale de<br><em>tu servidor</em></h2>
      <p class="sec-sub" style="margin-bottom:24px">Cada tarea, cada usuario, cada archivo queda en tu hosting. No en servidores de EE.UU., no en bases de datos de terceros.</p>
      <ul class="hosted-list">
        <li>Cumplimiento de normativas de privacidad locales (Ley 25.326 Argentina, LFPDPPP México)</li>
        <li>Sin riesgo de que tu proveedor lea tus datos estratégicos</li>
        <li>Sin dependencia: si cancelás, tus datos siguen siendo tuyos</li>
        <li>Control total sobre backups y retención de datos</li>
      </ul>
    </div>
    <div class="reveal right">
      <div class="server-anim" id="server-log">
        <div class="server-anim-body" id="server-lines"></div>
      </div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ══ CTA FINAL ══════════════════════════════════════════════════════════ -->
<div class="cta-section">
  <div class="cta-title reveal up">Empezá gratis hoy.<br><span style="background:linear-gradient(135deg,#a5b4fc,#67e8f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Sin excusas, sin tarjeta.</span></div>
  <p class="cta-sub reveal up">1 usuario, 1 pizarra, tareas ilimitadas. Cuando el equipo crezca, crecés al plan que necesitás — desde $19/mes para hasta 5 personas.</p>
  <div class="cta-btns reveal up">
    <a href="<?= $reg_url ?>" class="btn-main">🚀 Crear cuenta gratis ahora</a>
    <a href="<?= $plans_url ?>" class="btn-sec">Ver todos los planes</a>
  </div>
  <div class="cta-trust reveal up">Sin tarjeta · Activación inmediata · Cancelás cuando quieras</div>
</div>

<footer>
  <div>🌙 Luna Workspace · <a href="<?= $reg_url ?>">websobreruedas.com</a></div>
  <div style="display:flex;gap:20px">
    <a href="<?= $reg_url ?>">Registro gratis</a>
    <a href="<?= $plans_url ?>">Planes</a>
    <a href="mailto:pedidos47@gmail.com">Contacto</a>
  </div>
</footer>

<!-- FLOATING CTA -->
<div class="float-cta" id="float-cta">
  <a href="<?= $reg_url ?>">🚀 Empezar gratis</a>
</div>

<script>
// ── DATA ───────────────────────────────────────────────────────────────────────
const PLANS = {
  luna: { name:'Luna Workspace', price: n => n<=5?19:n<=20?39:89 },
  trello:  { name:'Trello',   price: n => n*5 },
  monday:  { name:'Monday',   price: n => n*9 },
  asana:   { name:'Asana',    price: n => Math.round(n*10.99) },
  clickup: { name:'ClickUp',  price: n => n*7 },
  notion:  { name:'Notion',   price: n => n*10 },
};

const COMP_DATA = {
  trello: {
    icon:'🟦', name:'Trello', cat:'Atlassian · Kanban',
    feats:[
      {label:'Precio (10 usuarios)',luna:'$39/mes fijo',them:'$50/mes',winner:'luna'},
      {label:'WhatsApp nativo',     luna:'✓ Incluido',  them:'✗ No existe',winner:'luna'},
      {label:'Self-hosted',         luna:'✓ WordPress', them:'✗ Solo SaaS',winner:'luna'},
      {label:'Pizarras ilimitadas', luna:'✓',them:'Solo en plan pago',winner:'luna'},
      {label:'Facilidad de uso',    luna:'✓ Simple',them:'✓ Simple',winner:'empate'},
      {label:'App móvil',           luna:'Web responsive',them:'✓ App nativa',winner:'them'},
      {label:'Integraciones',       luna:'Básicas',them:'200+ Power-Ups',winner:'them'},
    ],
    verdict: 'Luna gana en precio y en WhatsApp. Trello gana en ecosistema de integraciones — si no las necesitás, Luna es la mejor opción.'
  },
  monday: {
    icon:'🔴', name:'Monday.com', cat:'Work OS · Todo-en-uno',
    feats:[
      {label:'Precio (10 usuarios)',luna:'$39/mes fijo',them:'$90/mes',winner:'luna'},
      {label:'WhatsApp nativo',     luna:'✓ Incluido',  them:'✗ No existe',winner:'luna'},
      {label:'Self-hosted',         luna:'✓ WordPress', them:'✗ Solo SaaS',winner:'luna'},
      {label:'Curva de aprendizaje',luna:'✓ Baja',them:'Alta / compleja',winner:'luna'},
      {label:'Reporting avanzado',  luna:'Básico',them:'✓ Muy completo',winner:'them'},
      {label:'Automatizaciones',    luna:'Recordatorios',them:'✓ Completas',winner:'them'},
      {label:'Mínimo de asientos',  luna:'Sin mínimo',them:'3 asientos mín.',winner:'luna'},
    ],
    verdict: 'Luna gana en precio ($51 menos al mes), facilidad y WhatsApp. Monday gana si necesitás reporting empresarial avanzado.'
  },
  asana: {
    icon:'🔆', name:'Asana', cat:'Gestión de tareas',
    feats:[
      {label:'Precio (10 usuarios)',luna:'$39/mes fijo',them:'$110/mes',winner:'luna'},
      {label:'WhatsApp nativo',     luna:'✓ Incluido',  them:'✗ No existe',winner:'luna'},
      {label:'Self-hosted',         luna:'✓ WordPress', them:'✗ Solo SaaS',winner:'luna'},
      {label:'Notificaciones email', luna:'✓',them:'✓',winner:'empate'},
      {label:'Calendario integrado', luna:'✓',them:'Solo plan pago',winner:'luna'},
      {label:'Flujos de trabajo',    luna:'Básicos',them:'✓ Muy avanzados',winner:'them'},
      {label:'Modo oscuro',          luna:'✓ Siempre',them:'✗ No tiene',winner:'luna'},
    ],
    verdict: 'Luna gana en precio ($71 menos al mes), WhatsApp y modo oscuro. Asana gana en flujos de trabajo complejos para grandes empresas.'
  },
  clickup: {
    icon:'💜', name:'ClickUp', cat:'Todo-en-uno',
    feats:[
      {label:'Precio (10 usuarios)',luna:'$39/mes fijo',them:'$70/mes',winner:'luna'},
      {label:'WhatsApp nativo',     luna:'✓ Incluido',  them:'Solo via Zapier',winner:'luna'},
      {label:'Self-hosted',         luna:'✓ WordPress', them:'✗ Solo SaaS',winner:'luna'},
      {label:'Vistas disponibles',  luna:'Kanban+Lista+Cal',them:'✓ 15+ vistas',winner:'them'},
      {label:'Curva de aprendizaje',luna:'✓ Baja',them:'Alta / abruma',winner:'luna'},
      {label:'Plan gratuito',       luna:'✓ Para siempre',them:'✓ Generoso',winner:'empate'},
      {label:'Velocidad de carga',  luna:'✓ Muy rápido',them:'Moderada',winner:'luna'},
    ],
    verdict: 'Luna gana en precio, velocidad y WhatsApp real. ClickUp gana si necesitás las 15 vistas diferentes y muchas integraciones.'
  },
  notion: {
    icon:'🔲', name:'Notion', cat:'Wiki + DB + Kanban',
    feats:[
      {label:'Precio (10 usuarios)',luna:'$39/mes fijo',them:'$100/mes',winner:'luna'},
      {label:'WhatsApp nativo',     luna:'✓ Incluido',  them:'✗ No existe',winner:'luna'},
      {label:'Self-hosted',         luna:'✓ WordPress', them:'✗ Solo SaaS',winner:'luna'},
      {label:'Gestión de tareas',   luna:'✓ Nativo',them:'Bases de datos',winner:'luna'},
      {label:'Wiki / documentos',   luna:'No incluye',them:'✓ Excelente',winner:'them'},
      {label:'Recordatorios auto',  luna:'✓',them:'✗ No tiene',winner:'luna'},
      {label:'Para equipos no tech',luna:'✓ Muy fácil',them:'Curva media',winner:'luna'},
    ],
    verdict: 'Luna gana en precio, gestión de tareas y WhatsApp. Notion gana si necesitás una wiki de documentos compartida — no es un kanban real.'
  },
};

// ── NAV SCROLL ────────────────────────────────────────────────────────────────
window.addEventListener('scroll', () => {
  document.getElementById('main-nav').classList.toggle('scrolled', scrollY > 60);
  document.getElementById('float-cta').classList.toggle('show', scrollY > 500);
});

// ── REVEAL OBSERVER ───────────────────────────────────────────────────────────
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      // trigger bar animation on pain cards
      if (e.target.classList.contains('pain-card')) e.target.classList.add('visible');
    }
  });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal, .pain-card').forEach(el => observer.observe(el));

// ── PRICE SLIDER ──────────────────────────────────────────────────────────────
const slider = document.getElementById('team-slider');
function renderPriceRace(n) {
  document.getElementById('slider-val').textContent = n + (n===1?' persona':' personas');
  const lunaP = PLANS.luna.price(n);
  const competitors = [
    {key:'trello',color:'#0052cc',label:'Trello'},
    {key:'monday',color:'#f02e65',label:'Monday'},
    {key:'clickup',color:'#7b68ee',label:'ClickUp'},
    {key:'asana',color:'#f06a6a',label:'Asana'},
  ];
  const maxP = Math.max(lunaP, ...competitors.map(c => PLANS[c.key].price(n)));
  let html = `<div class="price-row">
    <div class="price-row-lbl luna">🌙 Luna</div>
    <div class="price-bar-wrap"><div class="price-bar luna-bar" style="width:${Math.max(10, lunaP/maxP*100)}%">$${lunaP}</div></div>
  </div>`;
  competitors.forEach(c => {
    const p = PLANS[c.key].price(n);
    const pct = Math.max(10, p/maxP*100);
    html += `<div class="price-row">
      <div class="price-row-lbl">${c.label}</div>
      <div class="price-bar-wrap"><div class="price-bar comp-bar" style="width:${pct}%;background:${c.color}80">$${p}</div></div>
    </div>`;
  });
  document.getElementById('price-race').innerHTML = html;

  // saving vs monday
  const mondayP = PLANS.monday.price(n);
  const saving = (mondayP - lunaP) * 12;
  document.getElementById('saving-num').textContent = saving > 0 ? `$${saving}` : '$0';
  document.getElementById('saving-lbl').textContent = saving > 0
    ? `ahorrás por año vs Monday.com`
    : `Luna y Monday cuestan similar a este tamaño`;
}
slider.addEventListener('input', e => renderPriceRace(+e.target.value));
renderPriceRace(10);

// ── WHATSAPP ANIMATION ────────────────────────────────────────────────────────
function startWaAnim() {
  const typing = document.getElementById('wa-typing');
  const newMsg  = document.getElementById('wa-new');
  const notif   = document.getElementById('wa-notif');
  let step = 0;
  setInterval(() => {
    step = (step + 1) % 4;
    if (step === 1) { typing.style.opacity = '1'; }
    if (step === 2) { typing.style.opacity = '0'; newMsg.style.display = ''; }
    if (step === 3) { notif.classList.add('show'); }
    if (step === 0) { notif.classList.remove('show'); newMsg.style.display = 'none'; }
  }, 2000);
}
// Start WA anim when in view
const waObserver = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) { startWaAnim(); waObserver.disconnect(); }
}, { threshold: 0.3 });
waObserver.observe(document.getElementById('wa-msgs'));

// ── SERVER LOG ANIMATION ──────────────────────────────────────────────────────
const serverLines = [
  {cls:'info', txt:'Conectando a <span>tu-dominio.com</span>...'},
  {cls:'ok',   txt:'Sesión iniciada · usuario: <span>admin@tuempresa.com</span>'},
  {cls:'ok',   txt:'Base de datos: <span>tu_servidor_local · 100% privado</span>'},
  {cls:'ok',   txt:'42 tareas cargadas · 3 usuarios activos'},
  {cls:'ok',   txt:'Recordatorio enviado por <span>WhatsApp</span> → María García'},
  {cls:'info', txt:'Backup automático: <span>tu_hosting/backups/luna_2026.sql</span>'},
  {cls:'ok',   txt:'Todos los datos en <span>tu servidor</span> · ningún tercero tiene acceso'},
  {cls:'warn', txt:'Intento de acceso externo bloqueado · IP: 185.xx.xx.xx'},
  {cls:'ok',   txt:'Sistema seguro · tus datos nunca salen de tu servidor ✓'},
];
function animateServer() {
  const container = document.getElementById('server-lines');
  container.innerHTML = '';
  serverLines.forEach((l, i) => {
    const div = document.createElement('div');
    div.className = `server-line ${l.cls}`;
    div.innerHTML = l.txt;
    div.style.animationDelay = `${i * 0.25}s`;
    container.appendChild(div);
  });
}
const srvObserver = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) {
    animateServer();
    setInterval(animateServer, serverLines.length * 260 + 2000);
    srvObserver.disconnect();
  }
}, { threshold: 0.3 });
srvObserver.observe(document.getElementById('server-log'));

// ── COMPETITOR TABS ───────────────────────────────────────────────────────────
function renderBattle(key) {
  const d = COMP_DATA[key];
  const luna = {icon:'🌙', name:'Luna Workspace', cat:'Self-hosted · WordPress'};
  function feats(side) {
    return d.feats.map(f => {
      const isLuna = side === 'luna';
      const val  = isLuna ? f.luna : f.them;
      const icon = f.winner === 'empate' ? '≈' : (f.winner === 'luna') === isLuna ? '✓' : '✗';
      const cls  = f.winner === 'empate' ? 'bf-par' : (f.winner === 'luna') === isLuna ? 'bf-win' : 'bf-los';
      return `<div class="battle-feat"><span class="bf-icon ${cls}">${icon}</span>${val}</div>`;
    }).join('');
  }
  document.getElementById('comp-battle').innerHTML = `
    <div class="battle-card luna-side">
      <div class="battle-head">
        <div class="battle-icon" style="background:rgba(91,106,240,.2)">🌙</div>
        <div><div class="battle-name">Luna Workspace</div><div class="battle-cat">${luna.cat}</div></div>
      </div>
      <div class="battle-body">
        ${feats('luna')}
        <div class="battle-verdict verdict-win">✓ Tu mejor opción</div>
      </div>
    </div>
    <div class="battle-card">
      <div class="battle-head">
        <div class="battle-icon" style="background:var(--elev)">${d.icon}</div>
        <div><div class="battle-name">${d.name}</div><div class="battle-cat">${d.cat}</div></div>
      </div>
      <div class="battle-body">
        ${feats('them')}
        <div class="battle-verdict verdict-los" style="font-size:11px;font-weight:400;text-align:left">${d.verdict}</div>
      </div>
    </div>`;
}

document.querySelectorAll('.comp-tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('.comp-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    renderBattle(this.dataset.comp);
  });
});
renderBattle('trello');
</script>

</body>
</html>
