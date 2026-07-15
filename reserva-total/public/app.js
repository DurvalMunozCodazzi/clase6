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
    ...options,
    headers: { "Content-Type": "application/json", ...options.headers },
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
  btn.addEventListener("click", () => {
    switchView(btn.dataset.view);
    if (btn.dataset.view === "admin") tryShowAdminPanel();
  });
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

// ---------- Admin ----------

function getAdminToken() {
  return localStorage.getItem("adminToken") || "";
}

async function apiAdmin(path, options = {}) {
  return api(path, {
    ...options,
    headers: { "x-admin-token": getAdminToken() },
  });
}

async function tryShowAdminPanel() {
  const token = getAdminToken();
  if (!token) return;

  try {
    await apiAdmin("/api/admin/verify");
    document.getElementById("admin-login-card").classList.add("hidden");
    document.getElementById("admin-panel").classList.remove("hidden");
    loadAdminRooms();
    loadAdminReservations();
  } catch {
    localStorage.removeItem("adminToken");
  }
}

document.getElementById("admin-login-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const token = document.getElementById("adminToken").value.trim();
  localStorage.setItem("adminToken", token);

  try {
    await apiAdmin("/api/admin/verify");
    document.getElementById("adminToken").value = "";
    document.getElementById("admin-login-card").classList.add("hidden");
    document.getElementById("admin-panel").classList.remove("hidden");
    loadAdminRooms();
    loadAdminReservations();
  } catch (err) {
    localStorage.removeItem("adminToken");
    showToast("Token inválido.", "error");
  }
});

document.getElementById("admin-logout").addEventListener("click", () => {
  localStorage.removeItem("adminToken");
  document.getElementById("admin-panel").classList.add("hidden");
  document.getElementById("admin-login-card").classList.remove("hidden");
});

async function loadAdminRooms() {
  const list = document.getElementById("admin-rooms-list");
  list.innerHTML = `<p class="empty-state">Cargando...</p>`;

  try {
    const rooms = await api("/api/rooms");
    renderAdminRooms(rooms);
  } catch (err) {
    list.innerHTML = `<p class="empty-state">${err.message}</p>`;
  }
}

function renderAdminRooms(rooms) {
  const list = document.getElementById("admin-rooms-list");

  if (rooms.length === 0) {
    list.innerHTML = `<p class="empty-state">No hay habitaciones cargadas.</p>`;
    return;
  }

  list.innerHTML = rooms
    .map(
      (room) => `
        <div class="reservation-card">
          <div class="reservation-info">
            <h3>${room.name} <span class="room-type">${room.type}</span></h3>
            <p>Capacidad ${room.capacity} · ${formatCurrency(room.price_per_night)}/noche</p>
          </div>
          <div style="display:flex; gap:0.5rem">
            <button class="btn" data-edit-room="${room.id}">Editar</button>
            <button class="btn btn-danger" data-delete-room="${room.id}">Eliminar</button>
          </div>
        </div>
      `
    )
    .join("");

  list.querySelectorAll("button[data-edit-room]").forEach((btn) => {
    const room = rooms.find((r) => r.id === Number(btn.dataset.editRoom));
    btn.addEventListener("click", () => fillRoomForm(room));
  });

  list.querySelectorAll("button[data-delete-room]").forEach((btn) => {
    btn.addEventListener("click", () => deleteRoom(btn.dataset.deleteRoom));
  });
}

function fillRoomForm(room) {
  document.getElementById("roomFormId").value = room.id;
  document.getElementById("roomName").value = room.name;
  document.getElementById("roomType").value = room.type;
  document.getElementById("roomCapacity").value = room.capacity;
  document.getElementById("roomPrice").value = room.price_per_night;
  document.getElementById("roomDescription").value = room.description || "";
  document.getElementById("room-form-title").textContent = "Editar habitación";
  document.getElementById("room-form-cancel").classList.remove("hidden");
}

function resetRoomForm() {
  document.getElementById("room-form").reset();
  document.getElementById("roomFormId").value = "";
  document.getElementById("room-form-title").textContent = "Nueva habitación";
  document.getElementById("room-form-cancel").classList.add("hidden");
}

document.getElementById("room-form-cancel").addEventListener("click", resetRoomForm);

document.getElementById("room-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const id = document.getElementById("roomFormId").value;
  const payload = {
    name: document.getElementById("roomName").value.trim(),
    type: document.getElementById("roomType").value.trim(),
    capacity: Number(document.getElementById("roomCapacity").value),
    pricePerNight: Number(document.getElementById("roomPrice").value),
    description: document.getElementById("roomDescription").value.trim(),
  };

  try {
    if (id) {
      await apiAdmin(`/api/rooms/${id}`, { method: "PUT", body: JSON.stringify(payload) });
      showToast("Habitación actualizada.", "success");
    } else {
      await apiAdmin("/api/rooms", { method: "POST", body: JSON.stringify(payload) });
      showToast("Habitación creada.", "success");
    }
    resetRoomForm();
    loadAdminRooms();
  } catch (err) {
    showToast(err.message, "error");
  }
});

async function deleteRoom(id) {
  if (!confirm("¿Eliminar esta habitación?")) return;

  try {
    await apiAdmin(`/api/rooms/${id}`, { method: "DELETE" });
    showToast("Habitación eliminada.", "success");
    loadAdminRooms();
  } catch (err) {
    showToast(err.message, "error");
  }
}

async function loadAdminReservations() {
  const list = document.getElementById("admin-reservations-list");
  list.innerHTML = `<p class="empty-state">Cargando...</p>`;

  try {
    const reservations = await apiAdmin("/api/reservations");
    renderAdminReservations(reservations);
  } catch (err) {
    list.innerHTML = `<p class="empty-state">${err.message}</p>`;
  }
}

function renderAdminReservations(reservations) {
  const list = document.getElementById("admin-reservations-list");

  if (reservations.length === 0) {
    list.innerHTML = `<p class="empty-state">No hay reservas registradas.</p>`;
    return;
  }

  list.innerHTML = reservations
    .map(
      (r) => `
        <div class="reservation-card">
          <div class="reservation-info">
            <h3>${r.room_name}</h3>
            <p>${r.guest_name} (${r.guest_email})</p>
            <p>${formatDate(r.check_in)} → ${formatDate(r.check_out)} · ${r.guests} huésped${r.guests > 1 ? "es" : ""}</p>
          </div>
          <button class="btn btn-danger" data-admin-cancel="${r.id}">Cancelar</button>
        </div>
      `
    )
    .join("");

  list.querySelectorAll("button[data-admin-cancel]").forEach((btn) => {
    btn.addEventListener("click", async () => {
      if (!confirm("¿Cancelar esta reserva?")) return;
      try {
        await apiAdmin(`/api/reservations/${btn.dataset.adminCancel}`, { method: "DELETE" });
        showToast("Reserva cancelada.", "success");
        loadAdminReservations();
      } catch (err) {
        showToast(err.message, "error");
      }
    });
  });
}

// ---------- Init ----------

(function init() {
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("checkIn").min = today;
  document.getElementById("checkOut").min = today;

  loadRooms();
})();
