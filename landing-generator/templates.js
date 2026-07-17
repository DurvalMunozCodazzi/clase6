/* Definición de temas visuales y generación del HTML/CSS final de la landing page */

const THEMES = {
  moderno: {
    label: "Moderno",
    swatch: ["#0f172a", "#6366f1"],
    bg: "#0b1120",
    bgAlt: "#111827",
    text: "#f8fafc",
    textMuted: "#94a3b8",
    primary: "#6366f1",
    primaryText: "#ffffff",
    card: "#1a2333",
    border: "#2a3549",
    font: "'Inter', sans-serif",
  },
  minimal: {
    label: "Minimal",
    swatch: ["#ffffff", "#111827"],
    bg: "#ffffff",
    bgAlt: "#f8fafc",
    text: "#0f172a",
    textMuted: "#64748b",
    primary: "#111827",
    primaryText: "#ffffff",
    card: "#ffffff",
    border: "#e2e8f0",
    font: "'Inter', sans-serif",
  },
  corporativo: {
    label: "Corporativo",
    swatch: ["#eff6ff", "#2563eb"],
    bg: "#ffffff",
    bgAlt: "#eff6ff",
    text: "#0f172a",
    textMuted: "#475569",
    primary: "#2563eb",
    primaryText: "#ffffff",
    card: "#ffffff",
    border: "#dbeafe",
    font: "'Inter', sans-serif",
  },
  startup: {
    label: "Startup",
    swatch: ["#fff7ed", "#f97316"],
    bg: "#fffaf5",
    bgAlt: "#fff1e2",
    text: "#1c1917",
    textMuted: "#78716c",
    primary: "#f97316",
    primaryText: "#ffffff",
    card: "#ffffff",
    border: "#fed7aa",
    font: "'Poppins', sans-serif",
  },
};

function escapeHtml(str) {
  return String(str ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function slugify(str) {
  return String(str || "landing-page")
    .toLowerCase()
    .normalize("NFD").replace(/[̀-ͯ]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "") || "landing-page";
}

function initials(name) {
  return String(name || "?")
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() || "")
    .join("");
}

function buildCSS() {
  return `
*,*::before,*::after{box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{margin:0;font-family:var(--font);background:var(--bg);color:var(--text);line-height:1.6;-webkit-font-smoothing:antialiased;}
img{max-width:100%;display:block;}
a{color:inherit;}
h1,h2,h3{line-height:1.15;margin:0;}
p{margin:0;}
.container{max-width:1160px;margin:0 auto;padding:0 24px;}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 30px;border-radius:999px;font-weight:600;text-decoration:none;border:none;cursor:pointer;font-size:15px;font-family:inherit;transition:transform .15s ease, box-shadow .15s ease, opacity .15s ease;}
.btn-primary{background:var(--primary);color:var(--primary-text);box-shadow:0 10px 30px -10px var(--primary);}
.btn-primary:hover{transform:translateY(-2px);}
.btn-ghost{background:transparent;color:var(--text);border:1.5px solid var(--border);}
.btn-ghost:hover{border-color:var(--primary);}
.eyebrow{display:inline-block;font-size:13px;font-weight:600;letter-spacing:.04em;color:var(--primary);background:color-mix(in srgb, var(--primary) 14%, transparent);padding:6px 14px;border-radius:999px;margin-bottom:20px;}

/* Nav */
.nav{position:sticky;top:0;z-index:50;display:flex;align-items:center;justify-content:space-between;padding:18px 24px;background:color-mix(in srgb, var(--bg) 85%, transparent);backdrop-filter:blur(10px);border-bottom:1px solid var(--border);}
.nav-inner{max-width:1160px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;width:100%;}
.brand{font-weight:800;font-size:20px;letter-spacing:-.02em;}
.nav-links{display:flex;gap:28px;list-style:none;margin:0;padding:0;}
.nav-links a{font-size:14px;font-weight:500;color:var(--text-muted);text-decoration:none;}
.nav-links a:hover{color:var(--text);}
.nav-cta{display:flex;align-items:center;gap:14px;}
.nav-cta .btn{padding:10px 22px;font-size:14px;}
.nav-toggle{display:none;}

/* Hero */
.hero{padding:96px 0 88px;position:relative;overflow:hidden;}
.hero::before{content:"";position:absolute;top:-140px;right:-160px;width:480px;height:480px;background:var(--primary);opacity:.18;filter:blur(120px);border-radius:50%;}
.hero-inner{position:relative;display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center;}
.hero h1{font-size:clamp(34px,5vw,54px);font-weight:800;letter-spacing:-.03em;margin-bottom:22px;}
.hero p.lead{font-size:18px;color:var(--text-muted);max-width:520px;margin-bottom:34px;}
.hero-actions{display:flex;gap:14px;flex-wrap:wrap;}
.hero-visual{position:relative;aspect-ratio:4/3;border-radius:24px;background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 40%, var(--bg)));box-shadow:0 30px 80px -30px color-mix(in srgb, var(--primary) 60%, transparent);}
.hero-visual::after{content:"";position:absolute;inset:18px;border-radius:16px;background:color-mix(in srgb, var(--card) 92%, transparent);backdrop-filter:blur(2px);}
.hero-stats{display:flex;gap:36px;margin-top:40px;}
.hero-stats div b{display:block;font-size:24px;font-weight:800;}
.hero-stats div span{font-size:13px;color:var(--text-muted);}

/* Section shared */
section{padding:88px 0;}
.section-head{max-width:640px;margin:0 auto 52px;text-align:center;}
.section-head h2{font-size:clamp(26px,3.5vw,38px);font-weight:800;letter-spacing:-.02em;margin-bottom:14px;}
.section-head p{color:var(--text-muted);font-size:16px;}

/* Features */
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;}
.feature-card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:32px;}
.feature-icon{font-size:30px;width:56px;height:56px;display:flex;align-items:center;justify-content:center;border-radius:14px;background:color-mix(in srgb, var(--primary) 14%, transparent);margin-bottom:20px;}
.feature-card h3{font-size:18px;font-weight:700;margin-bottom:10px;}
.feature-card p{color:var(--text-muted);font-size:15px;}

/* Testimonials */
.section-alt{background:var(--bg-alt);}
.testimonials-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;}
.testimonial-card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:32px;}
.testimonial-card p.quote{font-size:16px;margin-bottom:24px;}
.testimonial-card p.quote::before{content:"\\201C";}
.testimonial-card p.quote::after{content:"\\201D";}
.t-author{display:flex;align-items:center;gap:12px;}
.t-avatar{width:42px;height:42px;border-radius:50%;background:var(--primary);color:var(--primary-text);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
.t-author b{display:block;font-size:14px;}
.t-author span{font-size:13px;color:var(--text-muted);}

/* Pricing */
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;max-width:800px;margin:0 auto;}
.price-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:36px;display:flex;flex-direction:column;}
.price-card.highlighted{border-color:var(--primary);box-shadow:0 20px 50px -25px color-mix(in srgb, var(--primary) 60%, transparent);position:relative;}
.price-card.highlighted::before{content:"Más popular";position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--primary);color:var(--primary-text);font-size:12px;font-weight:700;padding:5px 14px;border-radius:999px;}
.price-card h3{font-size:19px;font-weight:700;margin-bottom:12px;}
.price-amount{font-size:40px;font-weight:800;margin-bottom:4px;}
.price-amount span{font-size:15px;font-weight:500;color:var(--text-muted);}
.price-card ul{list-style:none;margin:24px 0 28px;padding:0;flex-grow:1;}
.price-card li{font-size:14px;color:var(--text-muted);padding:8px 0;border-top:1px solid var(--border);}
.price-card li:first-child{border-top:none;}
.price-card li::before{content:"\\2713  ";color:var(--primary);font-weight:700;}
.price-card .btn{width:100%;}

/* FAQ */
.faq-list{max-width:720px;margin:0 auto;display:flex;flex-direction:column;gap:14px;}
.faq-item{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
.faq-q{padding:20px 24px;font-weight:600;font-size:15.5px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;list-style:none;}
.faq-q::-webkit-details-marker{display:none;}
.faq-q::after{content:"+";font-size:20px;color:var(--primary);transition:transform .2s ease;}
details[open] .faq-q::after{transform:rotate(45deg);}
.faq-a{padding:0 24px 20px;color:var(--text-muted);font-size:14.5px;}

/* CTA */
.cta-band{background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 55%, var(--bg)));border-radius:28px;margin:0 24px;padding:64px 40px;text-align:center;color:var(--primary-text);}
.cta-wrap{max-width:1160px;margin:0 auto;}
.cta-band h2{font-size:clamp(26px,3.5vw,36px);font-weight:800;margin-bottom:16px;}
.cta-band p{opacity:.9;max-width:520px;margin:0 auto 30px;}
.cta-band .btn-primary{background:var(--primary-text);color:var(--primary);}

/* Footer */
.site-footer{padding:56px 0 40px;border-top:1px solid var(--border);}
.footer-inner{display:flex;flex-wrap:wrap;gap:24px;align-items:center;justify-content:space-between;}
.footer-brand{font-weight:800;font-size:18px;}
.footer-meta{color:var(--text-muted);font-size:13.5px;}
.footer-links{display:flex;gap:20px;list-style:none;margin:0;padding:0;}
.footer-links a{color:var(--text-muted);font-size:13.5px;text-decoration:none;}
.footer-links a:hover{color:var(--text);}

@media (max-width:860px){
  .hero-inner{grid-template-columns:1fr;}
  .hero-visual{order:-1;aspect-ratio:16/9;}
  .nav-links{display:none;}
  .cta-band{margin:0 16px;padding:48px 24px;}
}
`;
}

function renderNav(data) {
  const links = [];
  if (data.sections.features) links.push(["#features", "Beneficios"]);
  if (data.sections.pricing) links.push(["#precios", "Precios"]);
  if (data.sections.testimonials) links.push(["#testimonios", "Testimonios"]);
  if (data.sections.faq) links.push(["#faq", "Preguntas"]);
  return `
<header class="nav">
  <div class="nav-inner">
    <div class="brand">${escapeHtml(data.businessName)}</div>
    <ul class="nav-links">
      ${links.map(([href, label]) => `<li><a href="${href}">${label}</a></li>`).join("")}
    </ul>
    <div class="nav-cta">
      <a class="btn btn-primary" href="${escapeHtml(data.ctaButtonLink || "#")}">${escapeHtml(data.ctaButtonText || "Empezar")}</a>
    </div>
  </div>
</header>`;
}

function renderHero(data) {
  return `
<section class="hero">
  <div class="container hero-inner">
    <div>
      <span class="eyebrow">${escapeHtml(data.eyebrow || "Nuevo")}</span>
      <h1>${escapeHtml(data.tagline)}</h1>
      <p class="lead">${escapeHtml(data.description)}</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="${escapeHtml(data.ctaButtonLink || "#")}">${escapeHtml(data.ctaButtonText || "Empezar gratis")}</a>
        ${data.secondaryButtonText ? `<a class="btn btn-ghost" href="#features">${escapeHtml(data.secondaryButtonText)}</a>` : ""}
      </div>
      <div class="hero-stats">
        <div><b>+2.500</b><span>Usuarios activos</span></div>
        <div><b>4.9/5</b><span>Valoración media</span></div>
        <div><b>10 min</b><span>Para publicar</span></div>
      </div>
    </div>
    <div class="hero-visual"></div>
  </div>
</section>`;
}

function renderFeatures(data) {
  const items = (data.features || [])
    .map(
      (f) => `
    <div class="feature-card">
      <div class="feature-icon">${escapeHtml(f.icon || "✨")}</div>
      <h3>${escapeHtml(f.title)}</h3>
      <p>${escapeHtml(f.text)}</p>
    </div>`
    )
    .join("");
  return `
<section id="features">
  <div class="container">
    <div class="section-head">
      <h2>Todo lo que necesitás</h2>
      <p>Pensado para lanzar una landing page profesional sin fricción.</p>
    </div>
    <div class="features-grid">${items}</div>
  </div>
</section>`;
}

function renderTestimonials(data) {
  const items = (data.testimonials || [])
    .map(
      (t) => `
    <div class="testimonial-card">
      <p class="quote">${escapeHtml(t.text)}</p>
      <div class="t-author">
        <div class="t-avatar">${escapeHtml(initials(t.name))}</div>
        <div><b>${escapeHtml(t.name)}</b><span>${escapeHtml(t.role)}</span></div>
      </div>
    </div>`
    )
    .join("");
  return `
<section id="testimonios" class="section-alt">
  <div class="container">
    <div class="section-head">
      <h2>Lo que dicen nuestros clientes</h2>
      <p>Historias reales de equipos que ya lanzaron su landing page.</p>
    </div>
    <div class="testimonials-grid">${items}</div>
  </div>
</section>`;
}

function renderPricing(data) {
  const plans = (data.pricingPlans || [])
    .map((p) => {
      const feats = (p.features || [])
        .map((f) => `<li>${escapeHtml(f)}</li>`)
        .join("");
      return `
    <div class="price-card ${p.highlighted ? "highlighted" : ""}">
      <h3>${escapeHtml(p.name)}</h3>
      <div class="price-amount">$${escapeHtml(p.price)}<span>${escapeHtml(p.period || "")}</span></div>
      <ul>${feats}</ul>
      <a class="btn btn-primary" href="${escapeHtml(data.ctaButtonLink || "#")}">${escapeHtml(p.buttonText || "Elegir plan")}</a>
    </div>`;
    })
    .join("");
  return `
<section id="precios">
  <div class="container">
    <div class="section-head">
      <h2>Planes simples y transparentes</h2>
      <p>Empezá gratis y escalá cuando lo necesites.</p>
    </div>
    <div class="pricing-grid">${plans}</div>
  </div>
</section>`;
}

function renderFAQ(data) {
  const items = (data.faqs || [])
    .map(
      (f) => `
    <details class="faq-item">
      <summary class="faq-q">${escapeHtml(f.q)}</summary>
      <div class="faq-a">${escapeHtml(f.a)}</div>
    </details>`
    )
    .join("");
  return `
<section id="faq" class="section-alt">
  <div class="container">
    <div class="section-head">
      <h2>Preguntas frecuentes</h2>
    </div>
    <div class="faq-list">${items}</div>
  </div>
</section>`;
}

function renderCTA(data) {
  return `
<section>
  <div class="cta-wrap">
    <div class="cta-band">
      <h2>${escapeHtml(data.ctaHeadline || "¿Listo para lanzar tu landing page?")}</h2>
      <p>${escapeHtml(data.ctaSubtext || "Sumate hoy y tené tu página publicada en minutos.")}</p>
      <a class="btn btn-primary" href="${escapeHtml(data.ctaButtonLink || "#")}">${escapeHtml(data.ctaButtonText || "Empezar gratis")}</a>
    </div>
  </div>
</section>`;
}

function renderFooter(data) {
  return `
<footer class="site-footer">
  <div class="container footer-inner">
    <div>
      <div class="footer-brand">${escapeHtml(data.businessName)}</div>
      <div class="footer-meta">${data.contactEmail ? escapeHtml(data.contactEmail) : ""}</div>
    </div>
    <ul class="footer-links">
      <li><a href="#">Términos</a></li>
      <li><a href="#">Privacidad</a></li>
      <li><a href="mailto:${escapeHtml(data.contactEmail || "")}">Contacto</a></li>
    </ul>
    <div class="footer-meta">&copy; ${new Date().getFullYear()} ${escapeHtml(data.businessName)}. Todos los derechos reservados.</div>
  </div>
</footer>`;
}

function buildHTML(data) {
  const theme = THEMES[data.theme] || THEMES.moderno;
  const body = [
    renderNav(data),
    renderHero(data),
    data.sections.features ? renderFeatures(data) : "",
    data.sections.testimonials ? renderTestimonials(data) : "",
    data.sections.pricing ? renderPricing(data) : "",
    data.sections.faq ? renderFAQ(data) : "",
    data.sections.cta ? renderCTA(data) : "",
    data.sections.footer ? renderFooter(data) : "",
  ].join("\n");

  return `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>${escapeHtml(data.businessName || "Mi Landing Page")}</title>
<meta name="description" content="${escapeHtml(data.description || "")}" />
<link rel="icon" href="data:," />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
<style>
:root{
  --bg:${theme.bg};
  --bg-alt:${theme.bgAlt};
  --text:${theme.text};
  --text-muted:${theme.textMuted};
  --primary:${theme.primary};
  --primary-text:${theme.primaryText};
  --card:${theme.card};
  --border:${theme.border};
  --font:${theme.font};
}
${buildCSS()}
</style>
</head>
<body>
${body}
</body>
</html>`;
}
