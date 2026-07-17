<?php if (!defined('ABSPATH')) exit; ?>
<div class="tarot-app" id="tarotApp">
  <header class="tarot-header">
    <h1>🔮 Tirada de las 3 Cartas</h1>
    <p class="subtitle">Concéntrate en tu pregunta y deja que tu intuición elija.</p>
  </header>

  <section class="mode-step" id="modeStep">
    <p class="step-intro">¿Cómo quieres hacer esta tirada?</p>
    <div class="mode-options">
      <button type="button" class="mode-card" data-mode="digital">
        <span class="mode-icon">🔮</span>
        <h3>Tirada digital</h3>
        <p>Baraja virtual mezclada al azar. Elige tus 3 cartas por intuición, aquí mismo.</p>
      </button>
      <button type="button" class="mode-card" data-mode="fisica">
        <span class="mode-icon">🃏</span>
        <h3>Tirada física</h3>
        <p>Ya tiraste las cartas reales sobre la mesa. Búscalas aquí una por una para registrar la lectura.</p>
      </button>
    </div>
  </section>

  <section class="question-step" id="questionStep" hidden>
    <button type="button" id="backToModeBtn" class="link-btn">‹ Cambiar modo</button>
    <label for="questionInput">¿Qué quieres consultarle al tarot?</label>
    <textarea id="questionInput" rows="2" placeholder="Ej: ¿Cómo va a evolucionar mi situación laboral?"></textarea>
    <button id="startBtn" class="btn-primary">Comenzar tirada</button>
  </section>

  <section class="shuffle-step" id="shuffleStep" hidden>
    <p class="step-intro">Mezcla tu mazo virtual antes de elegir tus cartas.</p>
    <div class="shuffle-deck" id="shuffleDeck">
      <div class="deck-card"></div>
      <div class="deck-card"></div>
      <div class="deck-card"></div>
      <div class="deck-card"></div>
      <div class="deck-card"></div>
    </div>
    <button type="button" id="shuffleBtn" class="btn-primary">🔀 Mezclar cartas</button>
  </section>

  <section class="spread" id="spreadSection" hidden>
    <div class="slot" data-pos="0">
      <span class="slot-label">⏳ Pasado</span>
      <div class="slot-visual"><div class="slot-placeholder">?</div></div>
      <div class="slot-name"></div>
      <button class="btn-secondary slot-btn" data-pos="0">Elegir carta</button>
    </div>
    <div class="slot" data-pos="1">
      <span class="slot-label">🌙 Presente</span>
      <div class="slot-visual"><div class="slot-placeholder">?</div></div>
      <div class="slot-name"></div>
      <button class="btn-secondary slot-btn" data-pos="1">Elegir carta</button>
    </div>
    <div class="slot" data-pos="2">
      <span class="slot-label">✨ Futuro</span>
      <div class="slot-visual"><div class="slot-placeholder">?</div></div>
      <div class="slot-name"></div>
      <button class="btn-secondary slot-btn" data-pos="2">Elegir carta</button>
    </div>
  </section>

  <section class="reading" id="readingSection" hidden>
    <h2>🔮 Tu lectura</h2>
    <div id="readingContent"></div>
    <div class="footer-actions">
      <button id="resetBtn" class="btn-secondary">Nueva tirada</button>
      <button id="changeModeBtn" class="btn-secondary">Cambiar modo</button>
    </div>
  </section>

  <div class="modal-overlay" id="modalOverlay" hidden>
    <div class="modal">
      <div class="modal-header">
        <h2 id="modalTitle"></h2>
        <button id="modalClose" class="modal-close" aria-label="Cerrar">✕</button>
      </div>
      <input type="text" id="cardSearch" class="card-search-input" placeholder="Buscar carta (ej. &quot;Rey de Copas&quot;, &quot;Torre&quot;)..." hidden />
      <div class="card-grid" id="cardGrid"></div>
      <div class="reveal-panel" id="revealPanel" hidden>
        <div class="reveal-card" id="revealCardVisual"></div>
        <div class="reveal-info">
          <h3 id="revealCardName"></h3>
          <div class="orientation-toggle" id="orientationToggle" hidden>
            <button type="button" class="orientation-btn active" data-val="false">Derecha</button>
            <button type="button" class="orientation-btn" data-val="true">Invertida</button>
          </div>
          <p id="revealCardOrientation"></p>
          <div class="reveal-actions">
            <button id="confirmCardBtn" class="btn-primary">Confirmar esta carta</button>
            <button id="pickAnotherBtn" class="btn-secondary">Elegir otra</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
