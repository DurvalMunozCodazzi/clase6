const state = {
  lastSearch: { checkIn: "", checkOut: "", guests: 1 },
};

// ---------- Utilidades ----------

function formatCurrency(value) {
  return new Intl.NumberFormat("es-AR", { style: "currency", currency: "ARS" }).format(value);
}

function formatDate(value) {
  return new Date(`${value}T00:00:00`).toLocaleDateString("es-AR");
}

function showToast(message, type = "success") {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.className = `toast ${type}`;
  toast.classList.remove("hidden");
  setTimeout(() => toast.classList.add("hidden"), 3500);
}

async function api(path, options = {}) {
  const res = await fetch(path, {
    headers: { "Content-Type": "application/json" },
    ...options,
  });
  const isJson = res.headers.get("content-type")?.includes("application/json");
  const data = isJson ? await res.json() : null;
  if (!res.ok) {
    throw new Error(data?.error || "Ocurrió un error inesperado.");
  }
  return data;
}

// ---------- Navegación ----------

function switchView(view) {
  document.querySelectorAll(".view").forEach((el) => el.classList.add("hidden"));
  document.getElementById(`view-${view}`).classList.remove("hidden");
  document.querySelectorAll(".nav-link").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.view === view);
  });
}

document.querySelectorAll(".nav-link").forEach((btn) => {
  btn.addEventListener("click", () => switchView(btn.dataset.view));
});

// ---------- Habitaciones ----------

async function loadRooms(params = {}) {
  const grid = document.getElementById("rooms-grid");
  grid.innerHTML = `<p class="empty-state">Cargando habitaciones...</p>`;

  try {
    const query = new URLSearchParams(params).toString();
    const rooms = await api(`/api/rooms${query ? `?${query}` : ""}`);
    renderRooms(rooms);
  } catch (err) {
    grid.innerHTML = `<p class="empty-state">${err.message}</p>`;
  }
}

function renderRooms(rooms) {
  const grid = document.getElementById("rooms-grid");

  if (rooms.length === 0) {
    grid.innerHTML = `<p class="empty-state">No hay habitaciones para mostrar.</p>`;
    return;
  }

  const hasSearch = Boolean(state.lastSearch.checkIn && state.lastSearch.checkOut);

  grid.innerHTML = rooms
    .map((room) => {
      const unavailable = hasSearch && !room.available;
      return `
        <article class="room-card ${unavailable ? "unavailable" : ""}">
          <span class="room-type">${room.type}</span>
          <h3>${room.name}</h3>
          <p class="room-capacity">👤 Hasta ${room.capacity} huésped${room.capacity > 1 ? "es" : ""}</p>
          <p class="room-desc">${room.description || ""}</p>
          <p class="room-price">${formatCurrency(room.price_per_night)} <span>/ noche</span></p>
          ${
            hasSearch
              ? `<span class="availability-badge ${room.available ? "available" : "unavailable"}">
                  ${room.available ? "✔ Disponible" : "✘ No disponible"}
                </span>`
              : ""
          }
          <button class="btn btn-primary" data-room-id="${room.id}" data-room-name="${room.name}"
            data-room-price="${room.price_per_night}" ${unavailable ? "disabled" : ""}>
            Reservar
          </button>
        </article>
      `;
    })
    .join("");

  grid.querySelectorAll("button[data-room-id]").forEach((btn) => {
    btn.addEventListener("click", () => openReservationModal(btn.dataset));
  });
}

document.getElementById("search-form").addEventListener("submit", (e) => {
  e.preventDefault();
  const checkIn = document.getElementById("checkIn").value;
  const checkOut = document.getElementById("checkOut").value;
  const guests = document.getElementById("guests").value;

  if (checkIn >= checkOut) {
    showToast("La fecha de salida debe ser posterior a la de entrada.", "error");
    return;
  }

  state.lastSearch = { checkIn, checkOut, guests };
  loadRooms({ checkIn, checkOut, guests });
});

// ---------- Modal de reserva ----------

const modal = document.getElementById("reservation-modal");

function openReservationModal({ roomId, roomName, roomPrice }) {
  const { checkIn, checkOut } = state.lastSearch;
  if (!checkIn || !checkOut) {
    showToast("Primero elegí fechas de entrada y salida para reservar.", "error");
    return;
  }

  document.getElementById("modalRoomId").value = roomId;
  document.getElementById("modal-room-summary").textContent =
    `${roomName} · ${formatDate(checkIn)} → ${formatDate(checkOut)} · ${formatCurrency(roomPrice)}/noche`;
  modal.classList.remove("hidden");
}

function closeReservationModal() {
  modal.classList.add("hidden");
  document.getElementById("reservation-form").reset();
}

document.getElementById("modal-close").addEventListener("click", closeReservationModal);
modal.addEventListener("click", (e) => {
  if (e.target === modal) closeReservationModal();
});

document.getElementById("reservation-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const { checkIn, checkOut, guests } = state.lastSearch;

  const payload = {
    roomId: Number(document.getElementById("modalRoomId").value),
    guestName: document.getElementById("guestName").value.trim(),
    guestEmail: document.getElementById("guestEmail").value.trim(),
    checkIn,
    checkOut,
    guests: Number(guests),
  };

  try {
    await api("/api/reservations", { method: "POST", body: JSON.stringify(payload) });
    showToast("¡Reserva confirmada!", "success");
    closeReservationModal();
    loadRooms(state.lastSearch);
  } catch (err) {
    showToast(err.message, "error");
  }
});

// ---------- Mis reservas ----------

document.getElementById("my-reservations-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const email = document.getElementById("lookupEmail").value.trim();
  await loadMyReservations(email);
});

async function loadMyReservations(email) {
  const list = document.getElementById("reservations-list");
  list.innerHTML = `<p class="empty-state">Buscando reservas...</p>`;

  try {
    const reservations = await api(`/api/reservations?email=${encodeURIComponent(email)}`);
    renderReservations(reservations);
  } catch (err) {
    list.innerHTML = `<p class="empty-state">${err.message}</p>`;
  }
}

function renderReservations(reservations) {
  const list = document.getElementById("reservations-list");

  if (reservations.length === 0) {
    list.innerHTML = `<p class="empty-state">No se encontraron reservas para ese email.</p>`;
    return;
  }

  list.innerHTML = reservations
    .map(
      (r) => `
        <div class="reservation-card">
          <div class="reservation-info">
            <h3>${r.room_name}</h3>
            <p>${formatDate(r.check_in)} → ${formatDate(r.check_out)} · ${r.guests} huésped${r.guests > 1 ? "es" : ""}</p>
          </div>
          <button class="btn btn-danger" data-reservation-id="${r.id}">Cancelar</button>
        </div>
      `
    )
    .join("");

  list.querySelectorAll("button[data-reservation-id]").forEach((btn) => {
    btn.addEventListener("click", () => cancelReservation(btn.dataset.reservationId));
  });
}

async function cancelReservation(id) {
  if (!confirm("¿Seguro que querés cancelar esta reserva?")) return;

  try {
    await api(`/api/reservations/${id}`, { method: "DELETE" });
    showToast("Reserva cancelada.", "success");
    const email = document.getElementById("lookupEmail").value.trim();
    loadMyReservations(email);
  } catch (err) {
    showToast(err.message, "error");
  }
}

// ---------- Init ----------

(function init() {
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("checkIn").min = today;
  document.getElementById("checkOut").min = today;

  loadRooms();
})();
