/* App: estado, enlace de formulario, listas dinámicas, preview en vivo y export */

const STORAGE_KEY = "landingcraft_state_v1";

const DEFAULT_STATE = {
  businessName: "Nova",
  eyebrow: "Nuevo · Generador de landing pages",
  tagline: "Lanzá tu producto al mundo en minutos",
  description:
    "Nova te ayuda a crear una landing page profesional sin escribir una sola línea de código. Elegí un estilo, completá tu contenido y publicá.",
  theme: "moderno",
  ctaButtonText: "Empezar gratis",
  ctaButtonLink: "#contacto",
  secondaryButtonText: "Ver beneficios",
  ctaHeadline: "¿Listo para lanzar tu landing page?",
  ctaSubtext: "Sumate hoy y tené tu página publicada en minutos.",
  contactEmail: "hola@novaapp.com",
  sections: {
    features: true,
    testimonials: true,
    pricing: true,
    faq: true,
    cta: true,
    footer: true,
  },
  features: [
    { icon: "⚡", title: "Rápido", text: "Generá tu landing page en minutos, sin programar." },
    { icon: "🎨", title: "Personalizable", text: "Elegí entre distintos estilos y colores para tu marca." },
    { icon: "📱", title: "Responsive", text: "Se ve perfecta en celular, tablet y escritorio." },
  ],
  testimonials: [
    { name: "Marina López", role: "Fundadora, Estudio Bloom", text: "En una tarde armamos la landing de nuestro lanzamiento. Impecable." },
    { name: "Federico Ruiz", role: "Product Manager", text: "La mejor herramienta que probamos para armar páginas de producto rápido." },
  ],
  pricingPlans: [
    { name: "Gratis", price: "0", period: "/mes", features: ["1 landing page", "Plantillas básicas", "Exportar HTML"], highlighted: false, buttonText: "Empezar" },
    { name: "Pro", price: "19", period: "/mes", features: ["Landing pages ilimitadas", "Todas las plantillas", "Soporte prioritario"], highlighted: true, buttonText: "Probar Pro" },
  ],
  faqs: [
    { q: "¿Necesito saber programar?", a: "No, todo se genera visualmente desde el editor." },
    { q: "¿Puedo descargar el HTML?", a: "Sí, con un clic exportás un archivo HTML listo para publicar en cualquier hosting." },
  ],
};

function deepMerge(base, override) {
  const out = Array.isArray(base) ? [...base] : { ...base };
  if (!override) return out;
  for (const key of Object.keys(override)) {
    if (
      override[key] &&
      typeof override[key] === "object" &&
      !Array.isArray(override[key]) &&
      base[key] &&
      typeof base[key] === "object"
    ) {
      out[key] = deepMerge(base[key], override[key]);
    } else {
      out[key] = override[key];
    }
  }
  return out;
}

let state = loadState();

function loadState() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return JSON.parse(JSON.stringify(DEFAULT_STATE));
    return deepMerge(DEFAULT_STATE, JSON.parse(raw));
  } catch {
    return JSON.parse(JSON.stringify(DEFAULT_STATE));
  }
}

let saveTimeout = null;
function persistState() {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    flashSaved();
  }, 300);
}

function flashSaved() {
  const el = document.getElementById("save-indicator");
  if (!el) return;
  el.classList.add("visible");
  clearTimeout(flashSaved._t);
  flashSaved._t = setTimeout(() => el.classList.remove("visible"), 1400);
}

let renderTimeout = null;
function scheduleRender() {
  persistState();
  clearTimeout(renderTimeout);
  renderTimeout = setTimeout(renderPreview, 120);
}

const preview = document.getElementById("preview-frame");

function renderPreview() {
  preview.srcdoc = buildHTML(state);
}

/* ---------- Campos simples ---------- */

const SIMPLE_FIELDS = [
  "businessName",
  "eyebrow",
  "tagline",
  "description",
  "ctaButtonText",
  "ctaButtonLink",
  "secondaryButtonText",
  "ctaHeadline",
  "ctaSubtext",
  "contactEmail",
];

function bindSimpleFields() {
  SIMPLE_FIELDS.forEach((key) => {
    const el = document.getElementById("field-" + key);
    if (!el) return;
    el.value = state[key] || "";
    el.addEventListener("input", () => {
      state[key] = el.value;
      scheduleRender();
    });
  });
}

/* ---------- Tema ---------- */

function bindThemeButtons() {
  const container = document.getElementById("theme-picker");
  container.innerHTML = Object.keys(THEMES)
    .map((key) => {
      const t = THEMES[key];
      return `<button type="button" class="theme-swatch ${state.theme === key ? "active" : ""}" data-theme="${key}" title="${t.label}">
        <span class="swatch-dot" style="background:${t.swatch[0]}"></span>
        <span class="swatch-dot" style="background:${t.swatch[1]}"></span>
        <span class="swatch-label">${t.label}</span>
      </button>`;
    })
    .join("");

  container.querySelectorAll(".theme-swatch").forEach((btn) => {
    btn.addEventListener("click", () => {
      state.theme = btn.dataset.theme;
      container.querySelectorAll(".theme-swatch").forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      scheduleRender();
    });
  });
}

/* ---------- Secciones on/off ---------- */

function bindSectionToggles() {
  document.querySelectorAll("[data-section-toggle]").forEach((el) => {
    const key = el.dataset.sectionToggle;
    el.checked = !!state.sections[key];
    updateFieldsetVisibility(key, el.checked);
    el.addEventListener("change", () => {
      state.sections[key] = el.checked;
      updateFieldsetVisibility(key, el.checked);
      scheduleRender();
    });
  });
}

function updateFieldsetVisibility(key, visible) {
  const fs = document.querySelector(`[data-section-fields="${key}"]`);
  if (fs) fs.classList.toggle("hidden", !visible);
}

/* ---------- Listas dinámicas genéricas ---------- */

function renderListEditor({ containerId, items, itemTemplate, onAdd, onRemoveLabel = "Eliminar" }) {
  const container = document.getElementById(containerId);
  container.innerHTML = items
    .map(
      (item, i) => `
    <div class="list-item" data-index="${i}">
      ${itemTemplate(item, i)}
      <button type="button" class="remove-item" data-remove="${i}">${onRemoveLabel} ✕</button>
    </div>`
    )
    .join("");
}

function wireDelegatedInputs(containerId, updateFn) {
  const container = document.getElementById(containerId);
  container.addEventListener("input", (e) => {
    const field = e.target.dataset.field;
    if (!field) return;
    const idx = Number(e.target.closest("[data-index]").dataset.index);
    updateFn(idx, field, e.target.value, e.target.type === "checkbox" ? e.target.checked : undefined);
    scheduleRender();
  });
  container.addEventListener("change", (e) => {
    if (e.target.type !== "checkbox") return;
    const field = e.target.dataset.field;
    if (!field) return;
    const idx = Number(e.target.closest("[data-index]").dataset.index);
    updateFn(idx, field, e.target.checked, true);
    scheduleRender();
  });
}

/* Features */
function renderFeaturesEditor() {
  renderListEditor({
    containerId: "features-list",
    items: state.features,
    itemTemplate: (f, i) => `
      <div class="row-inline">
        <input type="text" class="input-icon" maxlength="4" value="${escapeHtml(f.icon)}" data-field="icon" placeholder="⚡" />
        <input type="text" value="${escapeHtml(f.title)}" data-field="title" placeholder="Título" />
      </div>
      <textarea data-field="text" rows="2" placeholder="Descripción">${escapeHtml(f.text)}</textarea>
    `,
  });
}

function initFeatures() {
  renderFeaturesEditor();
  wireDelegatedInputs("features-list", (i, field, value) => {
    state.features[i][field] = value;
  });
  document.getElementById("add-feature").addEventListener("click", () => {
    state.features.push({ icon: "✨", title: "Nuevo beneficio", text: "Descripción del beneficio." });
    renderFeaturesEditor();
    wireDelegatedInputs("features-list", (i, field, value) => {
      state.features[i][field] = value;
    });
    bindRemoveButtons("features-list", state.features, initFeatures);
    scheduleRender();
  });
  bindRemoveButtons("features-list", state.features, initFeatures);
}

/* Testimonials */
function renderTestimonialsEditor() {
  renderListEditor({
    containerId: "testimonials-list",
    items: state.testimonials,
    itemTemplate: (t, i) => `
      <div class="row-inline">
        <input type="text" value="${escapeHtml(t.name)}" data-field="name" placeholder="Nombre" />
        <input type="text" value="${escapeHtml(t.role)}" data-field="role" placeholder="Cargo / empresa" />
      </div>
      <textarea data-field="text" rows="2" placeholder="Testimonio">${escapeHtml(t.text)}</textarea>
    `,
  });
}

function initTestimonials() {
  renderTestimonialsEditor();
  wireDelegatedInputs("testimonials-list", (i, field, value) => {
    state.testimonials[i][field] = value;
  });
  document.getElementById("add-testimonial").addEventListener("click", () => {
    state.testimonials.push({ name: "Nombre Apellido", role: "Cargo, Empresa", text: "Escribí acá el testimonio del cliente." });
    renderTestimonialsEditor();
    wireDelegatedInputs("testimonials-list", (i, field, value) => {
      state.testimonials[i][field] = value;
    });
    bindRemoveButtons("testimonials-list", state.testimonials, initTestimonials);
    scheduleRender();
  });
  bindRemoveButtons("testimonials-list", state.testimonials, initTestimonials);
}

/* Pricing */
function renderPricingEditor() {
  renderListEditor({
    containerId: "pricing-list",
    items: state.pricingPlans,
    itemTemplate: (p, i) => `
      <div class="row-inline">
        <input type="text" value="${escapeHtml(p.name)}" data-field="name" placeholder="Plan" />
        <input type="text" value="${escapeHtml(p.price)}" data-field="price" placeholder="Precio" style="max-width:90px" />
        <input type="text" value="${escapeHtml(p.period)}" data-field="period" placeholder="/mes" style="max-width:90px" />
      </div>
      <textarea data-field="featuresRaw" rows="3" placeholder="Un ítem por línea">${escapeHtml((p.features || []).join("\n"))}</textarea>
      <div class="row-inline">
        <input type="text" value="${escapeHtml(p.buttonText)}" data-field="buttonText" placeholder="Texto del botón" />
        <label class="checkbox-inline"><input type="checkbox" data-field="highlighted" ${p.highlighted ? "checked" : ""}/> Destacado</label>
      </div>
    `,
  });
}

function initPricing() {
  renderPricingEditor();
  const update = (i, field, value, isCheckbox) => {
    if (field === "featuresRaw") {
      state.pricingPlans[i].features = value.split("\n").map((s) => s.trim()).filter(Boolean);
    } else if (field === "highlighted") {
      state.pricingPlans[i].highlighted = isCheckbox ? value : !!value;
    } else {
      state.pricingPlans[i][field] = value;
    }
  };
  wireDelegatedInputs("pricing-list", update);
  document.getElementById("add-plan").addEventListener("click", () => {
    state.pricingPlans.push({ name: "Nuevo plan", price: "9", period: "/mes", features: ["Característica 1", "Característica 2"], highlighted: false, buttonText: "Elegir plan" });
    renderPricingEditor();
    wireDelegatedInputs("pricing-list", update);
    bindRemoveButtons("pricing-list", state.pricingPlans, initPricing);
    scheduleRender();
  });
  bindRemoveButtons("pricing-list", state.pricingPlans, initPricing);
}

/* FAQ */
function renderFaqEditor() {
  renderListEditor({
    containerId: "faq-list",
    items: state.faqs,
    itemTemplate: (f, i) => `
      <input type="text" value="${escapeHtml(f.q)}" data-field="q" placeholder="Pregunta" />
      <textarea data-field="a" rows="2" placeholder="Respuesta">${escapeHtml(f.a)}</textarea>
    `,
  });
}

function initFaq() {
  renderFaqEditor();
  wireDelegatedInputs("faq-list", (i, field, value) => {
    state.faqs[i][field] = value;
  });
  document.getElementById("add-faq").addEventListener("click", () => {
    state.faqs.push({ q: "¿Nueva pregunta?", a: "Respuesta a la pregunta." });
    renderFaqEditor();
    wireDelegatedInputs("faq-list", (i, field, value) => {
      state.faqs[i][field] = value;
    });
    bindRemoveButtons("faq-list", state.faqs, initFaq);
    scheduleRender();
  });
  bindRemoveButtons("faq-list", state.faqs, initFaq);
}

function bindRemoveButtons(containerId, arr, reinit) {
  const container = document.getElementById(containerId);
  container.querySelectorAll("[data-remove]").forEach((btn) => {
    btn.addEventListener("click", () => {
      arr.splice(Number(btn.dataset.remove), 1);
      reinit();
      scheduleRender();
    });
  });
}

/* ---------- Toolbar ---------- */

function bindToolbar() {
  document.getElementById("btn-download").addEventListener("click", () => {
    const html = buildHTML(state);
    const blob = new Blob([html], { type: "text/html" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `${slugify(state.businessName)}.html`;
    a.click();
    URL.revokeObjectURL(url);
  });

  document.getElementById("btn-fullscreen").addEventListener("click", () => {
    const html = buildHTML(state);
    const blob = new Blob([html], { type: "text/html" });
    const url = URL.createObjectURL(blob);
    window.open(url, "_blank");
  });

  document.getElementById("btn-reset").addEventListener("click", () => {
    if (!confirm("¿Reiniciar todo el contenido a los valores por defecto?")) return;
    localStorage.removeItem(STORAGE_KEY);
    state = JSON.parse(JSON.stringify(DEFAULT_STATE));
    initAll();
    renderPreview();
  });

  document.querySelectorAll("[data-device]").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll("[data-device]").forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      document.getElementById("preview-wrapper").dataset.device = btn.dataset.device;
    });
  });
}

/* ---------- Init ---------- */

function initAll() {
  bindSimpleFields();
  bindThemeButtons();
  bindSectionToggles();
  initFeatures();
  initTestimonials();
  initPricing();
  initFaq();
}

initAll();
bindToolbar();
renderPreview();
