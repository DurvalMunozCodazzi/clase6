document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  var root = document.getElementById("tdtHoroscopo");
  if (!root) return;

  var state = {
    dia: "hoy",
    signo: (root.querySelector(".tdt-signo-btn.active") || {}).dataset
      ? root.querySelector(".tdt-signo-btn.active").dataset.signo
      : null
  };

  function actualizarVista() {
    root.querySelectorAll(".tdt-tab").forEach(function (btn) {
      btn.classList.toggle("active", btn.dataset.dia === state.dia);
    });
    root.querySelectorAll(".tdt-panorama").forEach(function (el) {
      el.classList.toggle("active", el.dataset.panorama === state.dia);
    });
    root.querySelectorAll(".tdt-signo-btn").forEach(function (btn) {
      btn.classList.toggle("active", btn.dataset.signo === state.signo);
    });
    root.querySelectorAll(".tdt-signo-contenido").forEach(function (el) {
      el.classList.toggle("active", el.dataset.dia === state.dia && el.dataset.signo === state.signo);
    });
  }

  root.querySelectorAll(".tdt-tab").forEach(function (btn) {
    btn.addEventListener("click", function () {
      state.dia = btn.dataset.dia;
      actualizarVista();
    });
  });

  root.querySelectorAll(".tdt-signo-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      state.signo = btn.dataset.signo;
      actualizarVista();
    });
  });
});
