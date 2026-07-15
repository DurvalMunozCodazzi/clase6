(function () {
  "use strict";

  var API_BASE = (window.ReservaTotalConfig && window.ReservaTotalConfig.apiUrl) || "/wp-json/reserva-total/v1/";

  var state = {
    lastSearch: { checkIn: "", checkOut: "", guests: 1 },
  };

  function formatCurrency(value) {
    return new Intl.NumberFormat("es-AR", { style: "currency", currency: "ARS" }).format(value);
  }

  function formatDate(value) {
    return new Date(value + "T00:00:00").toLocaleDateString("es-AR");
  }

  function showToast(message, type) {
    var toast = document.getElementById("rt-toast");
    if (!toast) return;
    toast.textContent = message;
    toast.className = "rt-toast rt-" + (type || "success");
    toast.classList.remove("rt-hidden");
    setTimeout(function () {
      toast.classList.add("rt-hidden");
    }, 3500);
  }

  function api(path, options) {
    options = options || {};
    return fetch(API_BASE + path, Object.assign({}, options, {
      headers: Object.assign({ "Content-Type": "application/json" }, options.headers || {}),
    })).then(function (res) {
      var isJson = (res.headers.get("content-type") || "").indexOf("application/json") !== -1;
      var parsed = isJson ? res.json() : Promise.resolve(null);
      return parsed.then(function (data) {
        if (!res.ok) {
          var message = (data && (data.message || data.error)) || "Ocurrió un error inesperado.";
          throw new Error(message);
        }
        return data;
      });
    });
  }

  // ---------- Navegación ----------

  function switchView(view) {
    document.querySelectorAll(".reserva-total .rt-view").forEach(function (el) {
      el.classList.add("rt-hidden");
    });
    var target = document.getElementById("rt-view-" + view);
    if (target) target.classList.remove("rt-hidden");

    document.querySelectorAll(".reserva-total .rt-nav-link").forEach(function (btn) {
      btn.classList.toggle("active", btn.dataset.view === view);
    });
  }

  document.querySelectorAll(".reserva-total .rt-nav-link").forEach(function (btn) {
    btn.addEventListener("click", function () {
      switchView(btn.dataset.view);
    });
  });

  // ---------- Habitaciones ----------

  function loadRooms(params) {
    var grid = document.getElementById("rt-rooms-grid");
    grid.innerHTML = '<p class="rt-empty">Cargando habitaciones...</p>';

    var query = params ? "?" + new URLSearchParams(params).toString() : "";

    api("rooms" + query)
      .then(renderRooms)
      .catch(function (err) {
        grid.innerHTML = '<p class="rt-empty">' + err.message + "</p>";
      });
  }

  function renderRooms(rooms) {
    var grid = document.getElementById("rt-rooms-grid");

    if (rooms.length === 0) {
      grid.innerHTML = '<p class="rt-empty">No hay habitaciones para mostrar.</p>';
      return;
    }

    var hasSearch = Boolean(state.lastSearch.checkIn && state.lastSearch.checkOut);

    grid.innerHTML = rooms
      .map(function (room) {
        var unavailable = hasSearch && !room.available;
        return (
          '<article class="rt-room-card ' + (unavailable ? "rt-unavailable" : "") + '">' +
          '<span class="rt-room-type">' + room.type + "</span>" +
          "<h4>" + room.name + "</h4>" +
          '<p class="rt-room-capacity">👤 Hasta ' + room.capacity + " huésped" + (room.capacity > 1 ? "es" : "") + "</p>" +
          '<p class="rt-room-desc">' + (room.description || "") + "</p>" +
          '<p class="rt-room-price">' + formatCurrency(room.price_per_night) + " <span>/ noche</span></p>" +
          (hasSearch
            ? '<span class="rt-availability-badge ' + (room.available ? "rt-available" : "rt-unavailable-text") + '">' +
              (room.available ? "✔ Disponible" : "✘ No disponible") +
              "</span>"
            : "") +
          '<button type="button" class="rt-btn rt-btn-primary" data-room-id="' + room.id + '" data-room-name="' + room.name +
          '" data-room-price="' + room.price_per_night + '" ' + (unavailable ? "disabled" : "") + ">Reservar</button>" +
          "</article>"
        );
      })
      .join("");

    grid.querySelectorAll("button[data-room-id]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        openReservationModal(btn.dataset);
      });
    });
  }

  var searchForm = document.getElementById("rt-search-form");
  if (searchForm) {
    searchForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var checkIn = document.getElementById("rt-checkIn").value;
      var checkOut = document.getElementById("rt-checkOut").value;
      var guests = document.getElementById("rt-guests").value;

      if (checkIn >= checkOut) {
        showToast("La fecha de salida debe ser posterior a la de entrada.", "error");
        return;
      }

      state.lastSearch = { checkIn: checkIn, checkOut: checkOut, guests: guests };
      loadRooms({ checkIn: checkIn, checkOut: checkOut, guests: guests });
    });
  }

  // ---------- Modal de reserva ----------

  var modal = document.getElementById("rt-modal");

  function openReservationModal(data) {
    var checkIn = state.lastSearch.checkIn;
    var checkOut = state.lastSearch.checkOut;

    if (!checkIn || !checkOut) {
      showToast("Primero elegí fechas de entrada y salida para reservar.", "error");
      return;
    }

    document.getElementById("rt-modalRoomId").value = data.roomId;
    document.getElementById("rt-modal-summary").textContent =
      data.roomName + " · " + formatDate(checkIn) + " → " + formatDate(checkOut) + " · " + formatCurrency(data.roomPrice) + "/noche";
    modal.classList.remove("rt-hidden");
  }

  function closeReservationModal() {
    modal.classList.add("rt-hidden");
    document.getElementById("rt-reservation-form").reset();
  }

  var modalCloseBtn = document.getElementById("rt-modal-close");
  if (modalCloseBtn) modalCloseBtn.addEventListener("click", closeReservationModal);
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeReservationModal();
    });
  }

  var reservationForm = document.getElementById("rt-reservation-form");
  if (reservationForm) {
    reservationForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var checkIn = state.lastSearch.checkIn;
      var checkOut = state.lastSearch.checkOut;
      var guests = state.lastSearch.guests;

      var payload = {
        roomId: Number(document.getElementById("rt-modalRoomId").value),
        guestName: document.getElementById("rt-guestName").value.trim(),
        guestEmail: document.getElementById("rt-guestEmail").value.trim(),
        checkIn: checkIn,
        checkOut: checkOut,
        guests: Number(guests),
      };

      api("reservations", { method: "POST", body: JSON.stringify(payload) })
        .then(function () {
          showToast("¡Reserva confirmada!", "success");
          closeReservationModal();
          loadRooms(state.lastSearch);
        })
        .catch(function (err) {
          showToast(err.message, "error");
        });
    });
  }

  // ---------- Mis reservas ----------

  var myReservationsForm = document.getElementById("rt-my-reservations-form");
  if (myReservationsForm) {
    myReservationsForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var email = document.getElementById("rt-lookupEmail").value.trim();
      loadMyReservations(email);
    });
  }

  function loadMyReservations(email) {
    var list = document.getElementById("rt-reservations-list");
    list.innerHTML = '<p class="rt-empty">Buscando reservas...</p>';

    api("reservations?email=" + encodeURIComponent(email))
      .then(function (reservations) {
        renderReservations(reservations, email);
      })
      .catch(function (err) {
        list.innerHTML = '<p class="rt-empty">' + err.message + "</p>";
      });
  }

  function renderReservations(reservations, email) {
    var list = document.getElementById("rt-reservations-list");

    if (reservations.length === 0) {
      list.innerHTML = '<p class="rt-empty">No se encontraron reservas para ese email.</p>';
      return;
    }

    list.innerHTML = reservations
      .map(function (r) {
        return (
          '<div class="rt-reservation-card">' +
          '<div class="rt-reservation-info">' +
          "<h4>" + r.room_name + "</h4>" +
          "<p>" + formatDate(r.check_in) + " → " + formatDate(r.check_out) + " · " + r.guests + " huésped" + (r.guests > 1 ? "es" : "") + "</p>" +
          "</div>" +
          '<button type="button" class="rt-btn rt-btn-danger" data-reservation-id="' + r.id + '">Cancelar</button>' +
          "</div>"
        );
      })
      .join("");

    list.querySelectorAll("button[data-reservation-id]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        cancelReservation(btn.dataset.reservationId, email);
      });
    });
  }

  function cancelReservation(id, email) {
    if (!window.confirm("¿Seguro que querés cancelar esta reserva?")) return;

    api("reservations/" + id + "?email=" + encodeURIComponent(email), { method: "DELETE" })
      .then(function () {
        showToast("Reserva cancelada.", "success");
        loadMyReservations(email);
      })
      .catch(function (err) {
        showToast(err.message, "error");
      });
  }

  // ---------- Init ----------

  function init() {
    var checkInInput = document.getElementById("rt-checkIn");
    var checkOutInput = document.getElementById("rt-checkOut");
    if (checkInInput && checkOutInput) {
      var today = new Date().toISOString().split("T")[0];
      checkInInput.min = today;
      checkOutInput.min = today;
    }
    loadRooms();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
