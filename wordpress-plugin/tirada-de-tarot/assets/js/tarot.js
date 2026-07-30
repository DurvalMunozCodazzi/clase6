document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  var IMAGE_BASE_URL = (typeof TDT_CONFIG !== "undefined" && TDT_CONFIG.imageBaseUrl)
    ? TDT_CONFIG.imageBaseUrl
    : "assets/cards/";
  var REVERSED_PROBABILITY = 0.25;

  /* ===================== DATOS ===================== */

  var SUITS = [
    { key: "bastos", name: "Bastos", icon: "🔥",
      domainUp: "acción, trabajo, pasión, energía, proyectos",
      domainRev: "proyectos estancados, agotamiento, frustración creativa" },
    { key: "copas", name: "Copas", icon: "💧",
      domainUp: "emociones, amor, relaciones, intuición, arte",
      domainRev: "emociones bloqueadas, desconexión, decepción amorosa" },
    { key: "espadas", name: "Espadas", icon: "💨",
      domainUp: "mente, pensamientos, conflictos, decisiones, dolor",
      domainRev: "confusión mental, conflictos sin resolver, pensamientos negativos" },
    { key: "oros", name: "Oros", icon: "🌍",
      domainUp: "dinero, salud, trabajo físico, lo material, lo práctico",
      domainRev: "problemas económicos, inseguridad material, salud descuidada" }
  ];

  var NUMBERS = [
    { key: "as", label: "As", upTag: "Inicio, semilla, oportunidad", revTag: "Oportunidad perdida, falso comienzo, dudas al empezar" },
    { key: "2", label: "2", upTag: "Equilibrio, decisión, unión", revTag: "Desequilibrio, indecisión, desunión" },
    { key: "3", label: "3", upTag: "Expansión, crecimiento, grupo", revTag: "Estancamiento, aislamiento, plan que no despega" },
    { key: "4", label: "4", upTag: "Estabilidad, estructura, posesión", revTag: "Inestabilidad, pérdida de estructura, apego excesivo" },
    { key: "5", label: "5", upTag: "Conflicto, pérdida, cambio brusco", revTag: "Resolución del conflicto, evitar una pérdida mayor" },
    { key: "6", label: "6", upTag: "Ayuda, armonía, solución", revTag: "Desequilibrio en la ayuda, deudas emocionales, favoritismo" },
    { key: "7", label: "7", upTag: "Evaluación, desafío, esfuerzo", revTag: "Duda, falta de esfuerzo, evaluación equivocada" },
    { key: "8", label: "8", upTag: "Movimiento, práctica, trabajo duro", revTag: "Estancamiento, lentitud, trabajo sin frutos" },
    { key: "9", label: "9", upTag: "Clausura, culminación, soledad", revTag: "Aislamiento excesivo, cansancio, cierre casi listo" },
    { key: "10", label: "10", upTag: "Final de ciclo, plenitud, exceso", revTag: "Final que se alarga, carga excesiva, cierre incompleto" }
  ];

  var COURT_FIGURES = [
    { key: "paje", name: "Paje", upDesc: "Mensajero, noticias, aprendizaje, energía joven y exploradora", revDesc: "Inmadurez, malas noticias, bloqueo en el aprendizaje" },
    { key: "caballo", name: "Caballo", upDesc: "Movimiento rápido, viaje, acción intensa, impulso", revDesc: "Prisa imprudente, retrasos, acción sin dirección" },
    { key: "reina", name: "Reina", upDesc: "Intuición, observación, interioridad, energía receptiva y sabia", revDesc: "Inseguridad emocional, sobreprotección o frialdad" },
    { key: "rey", name: "Rey", upDesc: "Autoridad, control, estabilidad, energía activa y protectora", revDesc: "Autoritarismo, rigidez, abuso de poder" }
  ];

  var MAJOR_DATA = [
    ["el-loco", "0. El Loco", "0", "Nuevo comienzo, espontaneidad, fe ciega, viaje.", "Impulsividad, locura, riesgo tonto, miedo al cambio."],
    ["el-mago", "I. El Mago", "I", "Acción, voluntad, poder de manifestar, tienes todas las herramientas.", "Manipulación, bloqueo de energía, falta de dirección."],
    ["la-sacerdotisa", "II. La Sacerdotisa", "II", "Intuición, silencio, misterio, confía en tu voz interior.", "Ignorar tu intuición, secretos ocultos, superficialidad."],
    ["la-emperatriz", "III. La Emperatriz", "III", "Abundancia, creatividad, embarazo (físico o de ideas), naturaleza.", "Esterilidad creativa, dependencia, falta de crecimiento."],
    ["el-emperador", "IV. El Emperador", "IV", "Estructura, orden, autoridad, protección, lógica.", "Tiranía, rigidez, exceso de control, falta de disciplina."],
    ["el-hierofante", "V. El Hierofante", "V", "Tradición, educación, creencias, consejo de un experto.", "Rebelión, romper con lo establecido, dogmatismo."],
    ["los-enamorados", "VI. Los Enamorados", "VI", "Decisiones importantes, amor, unión, elección con el corazón.", "Desamor, desacuerdo, indecisión, evitar el compromiso."],
    ["el-carro", "VII. El Carro", "VII", "Voluntad, determinación, control de emociones, victoria.", "Falta de control, agresividad, ir sin dirección."],
    ["la-fuerza", "VIII. La Fuerza", "VIII", "Coraje interior, domar instintos, paciencia, persuasión.", "Inseguridad, debilidad, dejarse llevar por los impulsos."],
    ["el-ermitano", "IX. El Ermitaño", "IX", "Introspección, buscar respuestas dentro, retiro, guía interna.", "Soledad, aislamiento, negarse a ver la verdad."],
    ["la-rueda", "X. La Rueda de la Fortuna", "X", "Cambio de ciclo, destino, suerte, giro inesperado.", "Mala suerte, resistencia al cambio, patrones que se repiten."],
    ["la-justicia", "XI. La Justicia", "XI", "Verdad, consecuencias, equilibrio, decisiones legales.", "Injusticia, evasión de responsabilidad, culpa."],
    ["el-colgado", "XII. El Colgado", "XII", "Pausa, rendirse para ganar, nueva perspectiva, sacrificio.", "Estancamiento, no aprender la lección, víctima."],
    ["la-muerte", "XIII. La Muerte", "XIII", "Transformación, dejar ir, final necesario, renacimiento.", "Resistencia al cambio, duelo estancado, no soltar el pasado."],
    ["la-templanza", "XIV. La Templanza", "XIV", "Equilibrio, paciencia, mezclar opuestos, salud, fluir.", "Desequilibrio, impaciencia, caos, no encontrar el punto medio."],
    ["el-diablo", "XV. El Diablo", "XV", "Ataduras, adicciones, obsesiones, materialismo, sombra.", "Romper ataduras, liberarse, superar las adicciones."],
    ["la-torre", "XVI. La Torre", "XVI", "Caos, derrumbe, revelación impactante, cambio brusco.", "Evitar el desastre, retrasar lo inevitable, miedo al cambio."],
    ["la-estrella", "XVII. La Estrella", "XVII", "Esperanza, sanación, inspiración, calma después de la tormenta.", "Desesperanza, falta de fe, bloqueo creativo."],
    ["la-luna", "XVIII. La Luna", "XVIII", "Ilusiones, miedos ocultos, confusión, el subconsciente.", "Engaño, ansiedad, ver la verdad oculta, miedo superado."],
    ["el-sol", "XIX. El Sol", "XIX", "Éxito, alegría, claridad, vitalidad, logro.", "Exceso de optimismo, retrasos, falta de vitalidad."],
    ["el-juicio", "XX. El Juicio", "XX", "Llamado interior, renacimiento, evaluar tu vida, perdón.", "Negar el llamado, autocrítica dura, miedo al cambio."],
    ["el-mundo", "XXI. El Mundo", "XXI", "Plenitud, culminación, viaje completado, sentirte completo.", "Sin finalizar, falta de logro, sentirte fuera de lugar."]
  ];

  function buildAllCards() {
    var cards = [];

    MAJOR_DATA.forEach(function (row) {
      cards.push({
        id: row[0], name: row[1], numeral: row[2], icon: "✨",
        arcanaType: "mayor", suit: null, number: null,
        up: row[3], rev: row[4]
      });
    });

    SUITS.forEach(function (suit) {
      NUMBERS.forEach(function (num) {
        cards.push({
          id: suit.key + "-" + num.key,
          name: num.label + " de " + suit.name,
          numeral: num.label, icon: suit.icon,
          arcanaType: "menor", suit: suit.key, number: num.key,
          up: num.upTag + ", en el terreno de " + suit.domainUp + ".",
          rev: num.revTag + ", en el terreno de " + suit.domainRev + "."
        });
      });
      COURT_FIGURES.forEach(function (figure) {
        cards.push({
          id: suit.key + "-" + figure.key,
          name: figure.name + " de " + suit.name,
          numeral: figure.name, icon: suit.icon,
          arcanaType: "corte", suit: suit.key, number: null,
          up: figure.upDesc + ", orientada hacia " + suit.domainUp + ".",
          rev: figure.revDesc + ", en el terreno de " + suit.domainRev + "."
        });
      });
    });

    return cards;
  }

  var ALL_CARDS = buildAllCards(); // 22 + 40 + 16 = 78, ya en orden lógico (mayores, luego cada palo)
  var CARDS_BY_ID = {};
  ALL_CARDS.forEach(function (c) { CARDS_BY_ID[c.id] = c; });

  var POSITIONS = [
    { label: "Pasado", icon: "⏳" },
    { label: "Presente", icon: "🌙" },
    { label: "Futuro", icon: "✨" }
  ];

  /* ===================== ESTADO ===================== */

  var state = {
    mode: null, // "digital" | "fisica"
    question: "",
    reversedMap: {},
    usedIds: {},
    picks: [null, null, null],
    currentPos: null,
    pendingCardId: null,
    pendingReversed: false
  };

  /* ===================== HELPERS DOM ===================== */

  var $ = function (id) { return document.getElementById(id); };
  function escapeHtml(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }
  function shuffle(arr) {
    var a = arr.slice();
    for (var i = a.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = a[i]; a[i] = a[j]; a[j] = tmp;
    }
    return a;
  }

  function cardFaceHTML(card, reversed) {
    return (
      '<div class="card-face arcana-' + card.arcanaType + (card.suit ? " suit-" + card.suit : "") + '">' +
        '<div class="card-inner' + (reversed ? " is-reversed" : "") + '">' +
          '<img class="card-photo" src="' + IMAGE_BASE_URL + card.id + '.jpg" alt="" onerror="this.style.display=\'none\'">' +
          '<div class="card-symbolic">' +
            '<span class="card-tag">' + escapeHtml(card.numeral || "") + '</span>' +
            '<span class="card-icon">' + card.icon + '</span>' +
            '<span class="card-name-mini">' + escapeHtml(card.name) + '</span>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  var ICON_CARD_IDS = ["el-mago", "la-estrella", "el-sol"];

  function cardFanHTML() {
    return (
      '<img class="tarot-icon-card c1" src="' + IMAGE_BASE_URL + ICON_CARD_IDS[0] + '.jpg" alt="">' +
      '<img class="tarot-icon-card c2" src="' + IMAGE_BASE_URL + ICON_CARD_IDS[1] + '.jpg" alt="">' +
      '<img class="tarot-icon-card c3" src="' + IMAGE_BASE_URL + ICON_CARD_IDS[2] + '.jpg" alt="">'
    );
  }

  ["headerIcon", "modeDigitalIcon", "modeFisicaIcon", "readingIcon"].forEach(function (id) {
    var el = $(id);
    if (el) el.innerHTML = cardFanHTML();
  });

  /* ===================== SELECCIÓN DE MODO ===================== */

  document.querySelectorAll(".mode-card").forEach(function (btn) {
    btn.addEventListener("click", function () {
      state.mode = btn.dataset.mode;
      $("modeStep").hidden = true;
      $("questionStep").hidden = false;
    });
  });

  $("backToModeBtn").addEventListener("click", function () {
    $("questionStep").hidden = true;
    $("modeStep").hidden = false;
  });

  $("changeModeBtn").addEventListener("click", function () {
    $("readingSection").hidden = true;
    $("spreadSection").hidden = true;
    $("shuffleStep").hidden = true;
    $("questionStep").hidden = true;
    $("modeStep").hidden = false;
  });

  /* ===================== FLUJO PRINCIPAL ===================== */

  function resetSlotsUI() {
    document.querySelectorAll("#tarotApp .slot").forEach(function (slot) {
      slot.querySelector(".slot-visual").innerHTML = '<div class="slot-placeholder">?</div>';
      slot.querySelector(".slot-name").textContent = "";
      var btn = slot.querySelector(".slot-btn");
      btn.disabled = false;
      btn.textContent = "Elegir carta";
    });
  }

  $("startBtn").addEventListener("click", function () {
    state.question = $("questionInput").value.trim();
    state.usedIds = {};
    state.picks = [null, null, null];
    resetSlotsUI();

    $("questionStep").hidden = true;
    $("readingSection").hidden = true;
    $("readingContent").innerHTML = "";

    if (state.mode === "digital") {
      state.reversedMap = {};
      ALL_CARDS.forEach(function (c) {
        state.reversedMap[c.id] = Math.random() < REVERSED_PROBABILITY;
      });
      $("shuffleDeck").classList.remove("is-shuffling");
      $("shuffleBtn").disabled = false;
      $("shuffleStep").hidden = false;
    } else {
      $("spreadSection").hidden = false;
    }
  });

  $("shuffleBtn").addEventListener("click", function () {
    $("shuffleBtn").disabled = true;
    $("shuffleDeck").classList.add("is-shuffling");
    var reduced = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    setTimeout(function () {
      $("shuffleDeck").classList.remove("is-shuffling");
      $("shuffleStep").hidden = true;
      $("spreadSection").hidden = false;
    }, reduced ? 150 : 950);
  });

  document.querySelectorAll(".slot-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      openModal(parseInt(btn.dataset.pos, 10));
    });
  });

  $("resetBtn").addEventListener("click", function () {
    $("readingSection").hidden = true;
    $("spreadSection").hidden = true;
    $("shuffleStep").hidden = true;
    $("questionStep").hidden = false;
    $("questionInput").value = "";
  });

  /* ===================== MODAL DE SELECCIÓN ===================== */

  function openModal(posIndex) {
    state.currentPos = posIndex;
    state.pendingCardId = null;
    var verb = state.mode === "digital" ? "Elige tu carta para: " : "Busca la carta que salió para: ";
    $("modalTitle").textContent = verb + POSITIONS[posIndex].label;
    $("revealPanel").hidden = true;

    var searchInput = $("cardSearch");
    searchInput.hidden = state.mode !== "fisica";
    searchInput.value = "";
    $("cardGrid").classList.toggle("card-grid--browse", state.mode === "fisica");

    renderGrid("");
    $("modalOverlay").hidden = false;
    if (state.mode === "fisica") searchInput.focus();
  }

  function renderGrid(query) {
    var available = ALL_CARDS.filter(function (c) { return !state.usedIds[c.id]; });
    var list = state.mode === "digital" ? shuffle(available) : available.filter(function (c) {
      return !query || c.name.toLowerCase().indexOf(query.toLowerCase()) !== -1;
    });

    var grid = $("cardGrid");
    grid.innerHTML = "";

    if (state.mode === "fisica" && list.length === 0) {
      grid.innerHTML = '<p class="card-search-empty">Ninguna carta coincide con la búsqueda.</p>';
      return;
    }

    list.forEach(function (card) {
      var b = document.createElement("button");
      b.type = "button";
      b.dataset.id = card.id;
      if (state.mode === "digital") {
        b.className = "card-mini-btn";
        b.setAttribute("aria-label", "Carta boca abajo");
      } else {
        b.className = "card-search-btn";
        b.innerHTML = cardFaceHTML(card, false);
      }
      b.addEventListener("click", function () { revealCard(card.id); });
      grid.appendChild(b);
    });
  }

  $("cardSearch").addEventListener("input", function (e) {
    renderGrid(e.target.value.trim());
  });

  function closeModal() {
    $("modalOverlay").hidden = true;
  }
  $("modalClose").addEventListener("click", closeModal);
  $("modalOverlay").addEventListener("click", function (e) {
    if (e.target === $("modalOverlay")) closeModal();
  });

  /* ===================== REVELACIÓN Y ORIENTACIÓN ===================== */

  function updateOrientationButtons() {
    document.querySelectorAll("#orientationToggle .orientation-btn").forEach(function (b) {
      var isRev = b.dataset.val === "true";
      b.classList.toggle("active", isRev === state.pendingReversed);
    });
  }

  // Si el sitio tiene cargada la Base de significados curados (vía TDT_CONFIG),
  // se usa ese contenido real en vez de las palabras clave genéricas.
  function significadoCurado(card, reversed) {
    var base = (typeof TDT_CONFIG !== "undefined" && TDT_CONFIG.significados) ? TDT_CONFIG.significados : null;
    if (!base || !base[card.id]) return null;
    var lista = reversed ? base[card.id].rev : base[card.id].up;
    if (!lista || !lista.length) return null;
    return lista[Math.floor(Math.random() * lista.length)];
  }

  function textoCarta(card, reversed) {
    return significadoCurado(card, reversed) || (reversed ? card.rev : card.up);
  }

  function renderRevealVisual() {
    var card = CARDS_BY_ID[state.pendingCardId];
    $("revealCardVisual").innerHTML = cardFaceHTML(card, state.pendingReversed);
    $("revealCardName").textContent = card.name + (state.pendingReversed ? " (invertida)" : "");
    $("revealCardOrientation").textContent = textoCarta(card, state.pendingReversed);
  }

  function revealCard(cardId) {
    state.pendingCardId = cardId;
    state.pendingReversed = state.mode === "digital" ? !!state.reversedMap[cardId] : false;
    $("orientationToggle").hidden = state.mode !== "fisica";
    updateOrientationButtons();
    renderRevealVisual();
    $("revealPanel").hidden = false;
  }

  document.querySelectorAll("#orientationToggle .orientation-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      state.pendingReversed = btn.dataset.val === "true";
      updateOrientationButtons();
      renderRevealVisual();
    });
  });

  $("pickAnotherBtn").addEventListener("click", function () {
    $("revealPanel").hidden = true;
    state.pendingCardId = null;
    if (state.mode === "fisica") $("cardSearch").focus();
  });

  $("confirmCardBtn").addEventListener("click", function () {
    if (!state.pendingCardId) return;
    var card = CARDS_BY_ID[state.pendingCardId];
    state.usedIds[card.id] = true;
    state.picks[state.currentPos] = { card: card, reversed: state.pendingReversed };

    updateSlotUI(state.currentPos);
    closeModal();

    if (state.picks.every(function (p) { return p !== null; })) {
      renderReading();
    }
  });

  function updateSlotUI(posIndex) {
    var slot = document.querySelector('.slot[data-pos="' + posIndex + '"]');
    var pick = state.picks[posIndex];
    slot.querySelector(".slot-visual").innerHTML = cardFaceHTML(pick.card, pick.reversed);
    slot.querySelector(".slot-name").textContent = pick.card.name + (pick.reversed ? " (invertida)" : "");
    var btn = slot.querySelector(".slot-btn");
    btn.textContent = "✓ Elegida";
    btn.disabled = true;
  }

  /* ===================== MOTOR DE INTERPRETACIÓN ===================== */

  function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

  function inlinePhrase(str) {
    var s = str.trim();
    if (s.charAt(s.length - 1) === ".") s = s.slice(0, -1);
    return s.charAt(0).toLowerCase() + s.slice(1);
  }

  var OPENERS_WITH_Q = [
    'Sobre tu pregunta — "{q}" — esto es lo que revelan las cartas:',
    'Dejaste que tu intuición eligiera, y esto fue lo que salió para tu pregunta: "{q}".',
    'Las cartas ya hablaron. Esto es lo que tienen para decirte sobre "{q}":',
    'Con "{q}" en mente, así se despliega tu tirada:'
  ];
  var OPENERS_NO_Q = [
    "Esto es lo que revelan tus cartas:",
    "Así se despliega tu tirada:",
    "Las cartas ya hablaron, esto es lo que tienen para decirte:"
  ];

  var POSITION_INTROS = {
    "Pasado": ["Todo esto arranca con algo del pasado", "Ahí están las raíces de tu consulta", "Ese fue tu punto de partida"],
    "Presente": ["Así se ve tu momento actual", "Hoy estás atravesando esto", "El presente se resume en"],
    "Futuro": ["El camino apunta hacia esto", "Si seguís este rumbo, esto es lo que se perfila", "Hacia adelante, esto es lo que se asoma"]
  };

  var SYNTH_MAJORS_MANY = [
    function (n) { return "Hay " + n + " Arcanos Mayores en tu tirada: el tema que consultás es importante y trasciende lo cotidiano, probablemente forme parte de un aprendizaje o ciclo más grande de tu vida."; },
    function (n) { return "Con " + n + " Arcanos Mayores presentes, esto no es un tema menor — hay algo aquí que tiene peso en tu historia, no solo en tu semana."; }
  ];
  var SYNTH_MAJORS_NONE = [
    "No aparece ningún Arcano Mayor: esta tirada habla más de tu día a día y de decisiones concretas que de un destino ineludible, tenés bastante margen de acción.",
    "Sin Arcanos Mayores en juego, esto está más en tus manos de lo que parece: son decisiones cotidianas, no un mandato del destino."
  ];
  var SYNTH_SAME_SUIT = [
    function (suit) { return "Las tres cartas comparten el palo de " + suit.name + ", así que el foco real de esta situación está en " + suit.domainUp + "."; },
    function (suit) { return "Que las tres sean de " + suit.name + " no es casualidad: todo en esta tirada gira en torno a " + suit.domainUp + "."; }
  ];
  var SYNTH_REPEATED_NUMBER = [
    "Hay un número que se repite entre tus cartas: no es casualidad, es un tema que insiste y conviene prestarle atención.",
    "Un mismo número aparece más de una vez — cuando eso pasa, suele ser la vida subrayando algo que no podés seguir ignorando."
  ];
  var SYNTH_ALL_COURT = [
    "Las tres cartas son figuras de la Corte: esta situación gira mucho en torno a personas y a los roles o actitudes que tú u otros están representando, más que en hechos aislados.",
    "Al ser todas cartas de Corte, esto tiene mucho de vínculos y actitudes: mirá quién está jugando qué papel en esta historia."
  ];
  var SYNTH_REVERSED_NONE = [
    "Ninguna carta salió invertida: las energías de esta tirada fluyen sin grandes bloqueos.",
    "Todas tus cartas cayeron derechas: el camino está relativamente despejado."
  ];
  var SYNTH_REVERSED_ONE = [
    "Una de las tres cartas salió invertida: hay un punto concreto de fricción o resistencia que conviene vigilar.",
    "Con una carta invertida en la mezcla, hay un freno puntual: no es toda la tirada, pero merece atención."
  ];
  var SYNTH_REVERSED_MANY = [
    function (n) { return n + " de las tres cartas salieron invertidas: hay bastante resistencia o bloqueo en juego."; },
    function (n) { return "Con " + n + " cartas invertidas, esta tirada trae bastante fricción interna."; }
  ];
  var CLOSING_FLOWING = [
    "Es un buen momento para avanzar con confianza.",
    "Dejate llevar: por ahora, las señales acompañan."
  ];
  var CLOSING_BLOCKED = [
    "Antes de actuar, vale la pena hacer una pausa y mirar hacia adentro.",
    "No fuerces nada: hay algo que primero necesita resolverse internamente."
  ];

  function buildSynthesis(picks) {
    var sentences = [];
    var majors = picks.filter(function (p) { return p.card.arcanaType === "mayor"; }).length;
    var allCourt = picks.every(function (p) { return p.card.arcanaType === "corte"; });
    var reversedCount = picks.filter(function (p) { return p.reversed; }).length;
    var suitsPresent = picks.map(function (p) { return p.card.suit; }).filter(Boolean);
    var allSameSuit = suitsPresent.length === 3 && new Set(suitsPresent).size === 1;
    var numbersPresent = picks.map(function (p) { return p.card.number; }).filter(function (n) { return n; });
    var hasRepeatedNumber = numbersPresent.length >= 2 && new Set(numbersPresent).size < numbersPresent.length;

    if (majors >= 2) {
      sentences.push(pick(SYNTH_MAJORS_MANY)(majors));
    } else if (majors === 0) {
      sentences.push(pick(SYNTH_MAJORS_NONE));
    }

    if (allSameSuit) {
      var suit = SUITS.filter(function (s) { return s.key === suitsPresent[0]; })[0];
      sentences.push(pick(SYNTH_SAME_SUIT)(suit));
    }

    if (hasRepeatedNumber) {
      sentences.push(pick(SYNTH_REPEATED_NUMBER));
    }

    if (allCourt) {
      sentences.push(pick(SYNTH_ALL_COURT));
    }

    if (reversedCount === 0) {
      sentences.push(pick(SYNTH_REVERSED_NONE));
    } else if (reversedCount === 1) {
      sentences.push(pick(SYNTH_REVERSED_ONE));
    } else {
      sentences.push(pick(SYNTH_REVERSED_MANY)(reversedCount));
    }

    sentences.push(pick(reversedCount === 0 ? CLOSING_FLOWING : CLOSING_BLOCKED));

    return sentences.join(" ");
  }

  function renderReading() {
    var html = "";
    var opener = state.question
      ? pick(OPENERS_WITH_Q).replace("{q}", escapeHtml(state.question))
      : pick(OPENERS_NO_Q);
    html += '<p class="reading-question">' + opener + "</p>";

    state.picks.forEach(function (pick_, i) {
      var label = POSITIONS[i].label;
      var intro = pick(POSITION_INTROS[label]);
      var curado = significadoCurado(pick_.card, pick_.reversed);
      var cuerpo = curado
        ? intro + ". " + escapeHtml(curado)
        : intro + ": " + escapeHtml(inlinePhrase(pick_.reversed ? pick_.card.rev : pick_.card.up)) + ".";
      html +=
        '<div class="reading-line">' +
          "<h3>" + POSITIONS[i].icon + " " + label + ": " + escapeHtml(pick_.card.name) + (pick_.reversed ? " (invertida)" : "") + "</h3>" +
          "<p>" + cuerpo + "</p>" +
        "</div>";
    });

    html +=
      '<div class="reading-synthesis">' +
        '<h3><span class="tarot-icon tarot-icon--md" aria-hidden="true">' + cardFanHTML() + "</span>Síntesis de la tirada</h3>" +
        "<p>" + buildSynthesis(state.picks) + "</p>" +
      "</div>";

    $("readingContent").innerHTML = html;
    $("readingSection").hidden = false;
    $("readingSection").scrollIntoView({ behavior: "smooth", block: "start" });
  }
});
