<?php
$activeSection = 'journale-status';
$activePage = 'journale-status-bidon';
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="card card-outline card-primary mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Journal - Bidons (Clients)</h4>
        </div>
        <div class="card-header d-flex justify-content-between align-items-center">


         

          <select id="statutPaiement" class="form-select" style="width:180px;">
            <option value="tous">Tous</option>
            <option value="paye">Payé</option>
            <option value="non paye">Non Payé</option>
          </select>
          <div style="width:250px;">
            <select id="clientSelect" class="form-select js-choices">
              <option value="">-- Sélectionner un client --</option>
              <?php
              $sql = "SELECT id, CONCAT(prenom, ' ', nom) AS nom FROM client ORDER BY nom ASC";
              $res = $connect->query($sql);
              while ($c = $res->fetch_assoc()) {
                echo "<option value='{$c['id']}'>" . htmlspecialchars($c['nom']) . "</option>";
              }
              ?>
            </select>
          </div>

          <input type="date" id="dateStart" class="form-control" style="width:180px;">
          <input type="date" id="dateEnd" class="form-control" style="width:180px;">

          <button id="btnFilter" class="btn btn-outline-primary">
            <i class="bi bi-funnel"></i>
          </button>

          <button id="btnPrint" class="btn btn-outline-dark">
            <i class="bi bi-printer"></i>
          </button>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Client</th>
                  <th>Quantité</th>
                  <th>Prix Unitaire (DH)</th>
                  <th>Total (DH)</th>
                  <th>Mode Paiement</th>
                  <th>Type</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="bonHuileBody">
                <tr>
                  <td colspan="8" class="text-center text-muted">
                    Sélectionnez un client ou une période pour afficher les bons d’huile.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="row text-center mb-4 g-3">

          <div class="col-md-3 col-sm-6">
            <div class="card bg-success bg-opacity-10 border-success h-100">
              <div class="card-body">
                <h6 class="text-success">Montant Payé</h6>
                <h3 id="somme_payee" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6">
            <div class="card bg-danger bg-opacity-10 border-danger h-100">
              <div class="card-body">
                <h6 class="text-danger">Montant Non Payé</h6>
                <h3 id="somme_non_paye" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6">
            <div class="card bg-primary bg-opacity-10 border-primary h-100">
              <div class="card-body">
                <h6 class="text-primary">Montant Total</h6>
                <h3 id="somme_totale" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6">
            <div class="card bg-secondary bg-opacity-10 border-secondary h-100">
              <div class="card-body">
                <h6 class="text-secondary">Quantité Totale / Bons</h6>
                <h5 class="mb-1 fw-bold" id="quantite_totale">0.00 L</h5>
                <div class="small text-muted">Bons: <span id="total_bons">0</span></div>
              </div>
            </div>
          </div>

        </div>


      </div>
    </div>
  </div>

  <!-- === Modal Détails Bon d’Huile === -->
  <div class="modal fade" id="bonHuileModal" tabindex="-1" aria-labelledby="bonHuileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bonHuileModalLabel">Détails du Bon d’Huile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="bonHuileInfo"></div>
          <hr>
          <h6>Détails Bidons :</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Nom Bidon</th>
                  <th>Type</th>
                  <th>Quantité</th>
                  <th>Prix (DH)</th>
                </tr>
              </thead>
              <tbody id="bonHuileDetails"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
$(document).ready(function () {

  const tbody = $("#bonHuileBody");
  const clientSelect = $("#clientSelect");
  const dateStart = $("#dateStart");
  const dateEnd = $("#dateEnd");
  const statut = $("#statutPaiement");

  // === Charger les bidons filtrés ===
  async function loadBons() {
    const id_client = $("#clientSelect").val();
    const date_from = dateStart.val();
    const date_to = dateEnd.val();
    const statut_paiement = statut.val();

    if (!id_client && !date_from && !date_to) {
      tbody.html(`<tr><td colspan="8" class="text-center text-muted">Sélectionnez un client et/ou une période.</td></tr>`);
      resetTotals();
      return;
    }

    Swal.fire({
      title: "Chargement...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    try {
      // 🔹 Envoi POST vers ton nouveau script PHP
      const res = await fetch(`../includes/ajax/journal/get_List_bidon.php`, {
        method: "POST",
        body: new URLSearchParams({
          id_client: id_client || "",
          date_debut: date_from || "",
          date_fin: date_to || "",
          statut_paiement: statut_paiement || "tous"
        })
      });

      const data = await res.json();
      Swal.close();

      if (!data.ok) {
        Swal.fire("Erreur", data.error || "Impossible de charger les données.", "error");
        return;
      }

      const rows = data.rows || [];
      if (!rows.length) {
        tbody.html(`<tr><td colspan="8" class="text-center text-muted">Aucun enregistrement trouvé.</td></tr>`);
        resetTotals();
        return;
      }

  let html = "";
rows.forEach(r => {
  // Couleur badge selon statut
  let badgeClass = "bg-secondary";
  let statutTexte = (r.statut || "").toLowerCase().trim();

  if (statutTexte === "paye" || statutTexte === "payé") {
    badgeClass = "bg-success text-white"; // vert
  } else if (statutTexte === "non paye" || statutTexte === "non_paye" || statutTexte === "non payé" || statutTexte === "nonpayé") {
    badgeClass = "bg-danger text-white"; // rouge
  }

  html += `
    <tr>
      <td>${r.date_bon}</td>
      <td>${r.client}</td>
      <td>${parseFloat(r.quantite).toFixed(0)}</td>
      <td>${parseFloat(r.prix_unitaire).toFixed(2)} DH</td>
      <td class="fw-bold text-end">${parseFloat(r.prix_total).toFixed(2)} DH</td>
      <td>${r.mode_paiement || "CREDIT"}</td>
      <td>${r.nom_bidon} (${r.type_bidon})</td>
      <td>
        <span class="badge ${badgeClass}" style="font-size:0.9rem;">
          ${r.statut}
        </span>
      </td>
    </tr>`;
});

      tbody.html(html);

      // === Totaux dans les cartes ===
      $("#quantite_totale").text(parseFloat(data.quantite_totale).toFixed(0));
      $("#somme_totale").text(parseFloat(data.somme_totale).toFixed(2) + " DH");
      $("#somme_payee").text(parseFloat(data.somme_payee).toFixed(2) + " DH");
      $("#somme_non_paye").text(parseFloat(data.somme_non_paye).toFixed(2) + " DH");
      $("#total_bons").text(rows.length);

    } catch (err) {
      Swal.close();
      Swal.fire("Erreur", err.message || "Erreur de chargement des données.", "error");
    }
  }

  // === Reset totaux ===
  function resetTotals() {
    $("#quantite_totale").text("0");
    $("#somme_totale").text("0.00 DH");
    $("#somme_payee").text("0.00 DH");
    $("#somme_non_paye").text("0.00 DH");
    $("#total_bons").text("0");
  }

  // === Lancer le chargement quand filtre change ===
 $("#btnFilter").on("click", function (e) {
  e.preventDefault(); // évite rechargement ou comportement par défaut
  loadBons();
});
$("input").on("keypress", function (e) {
  if (e.which === 13) { // touche Entrée
    e.preventDefault();
    loadBons();
  }
});


});
// === Bouton Imprimer ===
  $("#btnPrint").on("click", function () {
   const id_client = $("#clientSelect").val();
  const date_debut = $("#dateStart").val();
  const date_fin = $("#dateEnd").val();
  const statut_paiement = $("#statutPaiement").val();
    // Construction de l’URL avec les filtres actifs
    let url = `print-bidon.php?`;
    const params = [];

    if (id_client) params.push(`id_client=${encodeURIComponent(id_client)}`);
    if (date_debut) params.push(`date_debut=${encodeURIComponent(date_debut)}`);
    if (date_fin) params.push(`date_fin=${encodeURIComponent(date_fin)}`);
    if (statut_paiement) params.push(`statut_paiement=${encodeURIComponent(statut_paiement)}`);

    window.open(url + params.join("&"), "_blank");
  });
</script>


<?php
require __DIR__ . '/../includes/scripts.php';
?>