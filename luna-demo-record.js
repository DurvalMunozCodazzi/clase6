const { chromium } = require('playwright');
const path = require('path');

const VIDEO_DIR = path.join(__dirname, 'luna-demo-video');
const HTML_PATH = 'file://' + path.join(__dirname, 'luna-demo.html');

(async () => {
  const browser = await chromium.launch({
    executablePath: '/opt/pw-browsers/chromium',
    headless: true,
    args: ['--no-sandbox','--disable-setuid-sandbox'],
  });

  const ctx = await browser.newContext({
    viewport: { width: 1280, height: 720 },
    recordVideo: { dir: VIDEO_DIR, size: { width: 1280, height: 720 } },
    locale: 'es-AR',
  });

  const page = await ctx.newPage();
  const delay = ms => page.waitForTimeout(ms);

  await page.goto(HTML_PATH, { waitUntil: 'load' });
  await delay(2500);  // presentación inicial del tablero

  // ── ESCENA 1: hover cards para mostrar interactividad ──
  await page.hover('[data-id="1"]');
  await delay(600);
  await page.hover('[data-id="4"]');
  await delay(600);
  await page.hover('[data-id="7"]');
  await delay(800);

  // ── ESCENA 2: abrir detalle de "Campaña Copa América" ──
  await page.click('[data-id="4"]');
  await delay(2000);

  // Marcar checkboxes
  const checks = page.locator('#det-checklist input[type=checkbox]');
  await checks.nth(0).click();
  await delay(500);
  await checks.nth(1).click();
  await delay(500);
  await checks.nth(2).click();
  await delay(800);

  // Cerrar detalle
  await page.click('#ov-detail .modal-close');
  await delay(1000);

  // ── ESCENA 3: crear nueva tarea ──
  // Clic en botón "Nueva tarea" de la topbar
  await page.evaluate(() => openCreate());
  await delay(800);

  await page.fill('#new-title', 'Nuevo stock zapatillas running');
  await delay(400);
  await page.selectOption('#new-resp', 'LW');
  await delay(300);
  await page.selectOption('#new-prio', 'alta');
  await delay(300);
  await page.selectOption('#new-col', 'pendiente');
  await delay(300);
  await page.fill('#new-date', '2026-07-05');
  await delay(300);
  await page.fill('#new-desc', 'Incorporar nueva línea de zapatillas running Adidas. Coordinar con logística y proveedor.');
  await delay(600);

  await page.click('#ov-create .btn-primary');
  await delay(2200);   // muestra notificación WhatsApp

  // ── ESCENA 4: drag & drop → mover tarea de Pendiente a En proceso ──
  const dragCard = page.locator('[data-id="3"]');
  const targetCol = page.locator('#body-proceso');

  const dBox = await dragCard.boundingBox();
  const tBox = await targetCol.boundingBox();

  const sx = dBox.x + dBox.width / 2;
  const sy = dBox.y + dBox.height / 2;
  const ex = tBox.x + tBox.width / 2;
  const ey = tBox.y + tBox.height / 2;

  await page.mouse.move(sx, sy);
  await delay(400);
  await page.mouse.down();
  await delay(300);

  const steps = 25;
  for (let i = 1; i <= steps; i++) {
    await page.mouse.move(
      sx + (ex - sx) * (i / steps),
      sy + (ey - sy) * (i / steps)
    );
    await delay(25);
  }
  await delay(300);
  await page.mouse.up();
  await delay(2000);   // notificación "movida a En proceso"

  // ── ESCENA 5: vista Analítica ──
  await page.evaluate(() => showView('analytics', null));
  await delay(2500);

  // Scroll para mostrar tabla de equipo
  await page.evaluate(() => {
    document.querySelector('#view-analytics > div').scrollBy({ top: 220, behavior: 'smooth' });
  });
  await delay(2000);

  // ── ESCENA 6: vista Calendario ──
  await page.evaluate(() => showView('calendar', null));
  await delay(2000);

  // Hover sobre días con tareas
  const calDays = page.locator('.cal-day');
  await calDays.nth(24).hover();
  await delay(800);
  await calDays.nth(27).hover();
  await delay(800);
  await calDays.nth(21).hover();
  await delay(1000);

  // ── ESCENA 7: volver al Kanban ──
  await page.evaluate(() => showView('kanban', null));
  await delay(2000);

  // Pausa final
  await delay(1000);

  await ctx.close();
  await browser.close();

  console.log('✓ Video grabado en:', VIDEO_DIR);
})();
