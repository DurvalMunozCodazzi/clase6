const { chromium } = require('playwright');
const path = require('path');

const VIDEO_DIR  = path.join(__dirname, 'luna-demo-video');
const APP_URL    = 'https://websobreruedas.ar/wordpress/wp-content/plugins/luna-workspace/app/index.html';
const USER       = 'admin';
const PASS       = 'Luna2024';

(async () => {
  const browser = await chromium.launch({
    executablePath: '/opt/pw-browsers/chromium',
    headless: true,
    args: ['--no-sandbox','--disable-setuid-sandbox','--disable-web-security'],
  });

  const ctx = await browser.newContext({
    viewport: { width: 1280, height: 720 },
    recordVideo: { dir: VIDEO_DIR, size: { width: 1280, height: 720 } },
    locale: 'es-AR',
    ignoreHTTPSErrors: true,
  });

  const page = await ctx.newPage();
  const delay = ms => page.waitForTimeout(ms);

  // ── LOGIN ──────────────────────────────────────────────────────────────
  console.log('Abriendo app...');
  await page.goto(APP_URL, { waitUntil: 'networkidle', timeout: 30000 });
  await delay(2000);

  // Tomar screenshot para ver qué muestra la pantalla
  await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-01-login.png') });
  console.log('Screenshot login tomado');

  // Intentar login — buscar campo usuario/email
  const usernameField = page.locator('input[name="username"], input[type="text"], input[placeholder*="usuario"], input[placeholder*="Usuario"], input[placeholder*="user"], #username, #login');
  const passwordField = page.locator('input[name="password"], input[type="password"], #password');

  await usernameField.first().waitFor({ timeout: 15000 });
  await usernameField.first().fill(USER);
  await delay(400);
  await passwordField.first().fill(PASS);
  await delay(400);

  // Clic en botón de login
  const loginBtn = page.locator('button[type="submit"], button:has-text("Ingresar"), button:has-text("Login"), button:has-text("Entrar"), input[type="submit"]');
  await loginBtn.first().click();
  await delay(3000);

  await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-02-after-login.png') });
  console.log('Screenshot post-login tomado');

  // ── VERIFICAR QUE ESTAMOS DENTRO ──────────────────────────────────────
  const url = page.url();
  console.log('URL actual:', url);

  // Esperar que cargue el tablero (sidebar o kanban)
  await page.waitForSelector('.workspace, #sidebar, .kanban, [class*="board"], [class*="workspace"], .pizarra', {
    timeout: 15000
  }).catch(() => console.log('Selector de tablero no encontrado, continuando...'));

  await delay(2000);
  await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-03-dashboard.png') });
  console.log('Dashboard screenshot tomado');

  // ── ESCENA 1: presentación del tablero real ────────────────────────────
  await delay(2500);

  // Scroll suave para mostrar la interfaz
  await page.mouse.move(640, 360);
  await delay(1000);

  // ── ESCENA 2: abrir/crear workspace Messifactory ──────────────────────
  // Buscar botón de nuevo workspace o ir al existente
  const newWsBtn = page.locator('button:has-text("Nuevo"), button:has-text("Nueva"), button:has-text("Crear"), [title*="workspace"], [title*="Workspace"]');
  const hasNewWs = await newWsBtn.count();

  if (hasNewWs > 0) {
    await newWsBtn.first().click();
    await delay(1000);

    // Buscar campo nombre del workspace
    const wsNameField = page.locator('input[placeholder*="nombre"], input[placeholder*="Nombre"], input[name*="name"], input[name*="nombre"]');
    if (await wsNameField.count() > 0) {
      await wsNameField.first().fill('Messifactory');
      await delay(400);
      const saveBtn = page.locator('button:has-text("Guardar"), button:has-text("Crear"), button[type="submit"]');
      await saveBtn.first().click();
      await delay(2000);
    }
  }

  await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-04-workspace.png') });
  console.log('Workspace screenshot tomado');

  // ── ESCENA 3: crear tarea ─────────────────────────────────────────────
  // Buscar botón nueva tarea
  const newCardBtn = page.locator(
    'button:has-text("Nueva tarea"), button:has-text("Agregar"), button:has-text("+ Nueva"), [title*="tarea"], .add-card, .new-card, button:has-text("Nueva")'
  );
  const hasNewCard = await newCardBtn.count();
  console.log('Botones de nueva tarea encontrados:', hasNewCard);

  if (hasNewCard > 0) {
    await newCardBtn.first().click();
    await delay(1200);

    await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-05-new-task.png') });

    // Llenar formulario de tarea
    const titleField = page.locator('input[placeholder*="título"], input[placeholder*="Título"], input[placeholder*="tarea"], input[name*="title"], input[name*="titulo"], textarea[placeholder*="título"]');
    if (await titleField.count() > 0) {
      await titleField.first().fill('Lanzamiento Camiseta Argentina 2026');
      await delay(400);
    }

    // Descripción
    const descField = page.locator('textarea[placeholder*="descripción"], textarea[placeholder*="Descripción"], textarea[name*="desc"]');
    if (await descField.count() > 0) {
      await descField.first().fill('Coordinación del lanzamiento de la nueva camiseta oficial de la selección para Messifactory.');
      await delay(400);
    }

    // Guardar tarea
    const saveTask = page.locator('button:has-text("Guardar"), button:has-text("Crear"), button:has-text("Agregar"), button[type="submit"]');
    if (await saveTask.count() > 0) {
      await saveTask.first().click();
      await delay(2000);
    }

    await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-06-task-created.png') });
    console.log('Tarea creada screenshot tomado');
  }

  // ── ESCENA 4: intentar drag & drop ───────────────────────────────────
  // Buscar cards en el tablero
  const cards = page.locator('.card, .kanban-card, [class*="card"], .tarjeta, [draggable="true"]');
  const cardCount = await cards.count();
  console.log('Cards encontradas:', cardCount);

  if (cardCount >= 1) {
    const firstCard = cards.first();
    const cardBox = await firstCard.boundingBox();

    if (cardBox) {
      // Buscar segunda columna para drop
      const cols = page.locator('.column, .col, [class*="column"], .kol, [class*="col-"]');
      const colCount = await cols.count();
      console.log('Columnas encontradas:', colCount);

      if (colCount >= 2) {
        const targetCol = cols.nth(1);
        const colBox = await targetCol.boundingBox();

        if (colBox) {
          const sx = cardBox.x + cardBox.width / 2;
          const sy = cardBox.y + cardBox.height / 2;
          const ex = colBox.x + colBox.width / 2;
          const ey = colBox.y + colBox.height / 2;

          await page.mouse.move(sx, sy);
          await delay(500);
          await page.mouse.down();
          await delay(400);

          const steps = 30;
          for (let i = 1; i <= steps; i++) {
            await page.mouse.move(
              sx + (ex - sx) * (i / steps),
              sy + (ey - sy) * (i / steps)
            );
            await delay(20);
          }
          await delay(300);
          await page.mouse.up();
          await delay(2000);

          await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-07-drag.png') });
          console.log('Drag screenshot tomado');
        }
      }
    }
  }

  // ── ESCENA 5: navegar a analítica ────────────────────────────────────
  const analyticsBtn = page.locator(
    'a:has-text("Analítica"), a:has-text("Analytics"), button:has-text("Analítica"), [href*="analytic"], [title*="nalít"], nav a:nth-child(2), .sb-item:nth-child(3)'
  );
  if (await analyticsBtn.count() > 0) {
    await analyticsBtn.first().click();
    await delay(2500);
    await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-08-analytics.png') });
    console.log('Analytics screenshot tomado');
  }

  // ── ESCENA 6: navegar a calendario ───────────────────────────────────
  const calBtn = page.locator(
    'a:has-text("Calendario"), button:has-text("Calendario"), [href*="calendar"], [title*="alendario"]'
  );
  if (await calBtn.count() > 0) {
    await calBtn.first().click();
    await delay(2000);
    await page.screenshot({ path: path.join(VIDEO_DIR, 'debug-09-calendar.png') });
    console.log('Calendar screenshot tomado');
  }

  // ── PAUSA FINAL ───────────────────────────────────────────────────────
  await delay(2000);

  await ctx.close();
  await browser.close();

  console.log('✓ Grabación completada. Revisá los screenshots en:', VIDEO_DIR);
})();
