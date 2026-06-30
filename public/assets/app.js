(function () {
  "use strict";

  function fmtEur(n) {
    return n.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Dependent product -> offer selects (new sale & new barème).
  function wireOffers(typeId, offreId, dataEl) {
    var typeSel = document.getElementById(typeId);
    var offreSel = document.getElementById(offreId);
    if (!typeSel || !offreSel || !dataEl) return;
    var offers;
    try { offers = JSON.parse(dataEl.getAttribute("data-offers")); } catch (e) { return; }
    function fill() {
      var list = offers[typeSel.value] || [];
      offreSel.innerHTML = "";
      list.forEach(function (o) {
        var opt = document.createElement("option");
        opt.value = o; opt.textContent = o;
        offreSel.appendChild(opt);
      });
    }
    typeSel.addEventListener("change", fill);
    fill();
  }

  document.addEventListener("DOMContentLoaded", function () {
    // Login role selector: prefill email (cosmetic, like the React design).
    var roles = document.getElementById("auth-roles");
    if (roles) {
      var emailInput = document.getElementById("auth-email");
      roles.querySelectorAll(".auth-role").forEach(function (b) {
        b.addEventListener("click", function () {
          roles.querySelectorAll(".auth-role").forEach(function (x) { x.classList.remove("is-active"); });
          b.classList.add("is-active");
          if (emailInput) emailInput.value = b.getAttribute("data-email") || "";
        });
      });
    }

    var saleForm = document.getElementById("sale-form");
    if (saleForm) wireOffers("type-select", "offre-select", saleForm);

    var baremeData = document.getElementById("bareme-offers-data");
    if (baremeData) wireOffers("bareme-type", "bareme-offre", baremeData);

    // Live CA/h recompute as hours are typed.
    document.querySelectorAll("#results-table tbody tr").forEach(function (row) {
      var ca = parseFloat(row.getAttribute("data-ca") || "0");
      var input = row.querySelector(".hours-input");
      var objCell = row.querySelector(".objectif");
      var btn = row.querySelector(".btn-icon");
      if (!input || !objCell) return;
      function update() {
        var h = parseFloat(input.value || "0");
        objCell.textContent = h > 0 ? fmtEur(ca / h) + " €/h" : "—";
        if (btn) {
          var saved = parseFloat(input.getAttribute("data-saved") || "0");
          btn.classList.toggle("dirty", (parseFloat(input.value || "0") || 0) !== saved);
        }
      }
      input.addEventListener("input", update);
    });
  });
})();
