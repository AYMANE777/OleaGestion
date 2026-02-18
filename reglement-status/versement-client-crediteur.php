<?php
$activeSection = 'journale-status';
$activePage = 'journale-versement-client-credit';
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="card card-outline card-success mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Journal des Versements (Clients)</h4>
        </div>

        <!-- 🔹 Filtres -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un client..." style="width:250px;">
          <input type="date" id="dateStart" class="form-control" style="width:180px;">
          <input type="date" id="dateEnd" class="form-control" style="width:180px;">
          <button id="btnFilter" class="btn btn-outline-success">
            <i class="bi bi-funnel"></i> Filtrer
          </button>
          <button id="btnPrint" class="btn btn-outline-dark">
            <i class="bi bi-printer"></i> Imprimer
          </button>
        </div>

        <!-- 🔹 Tableau principal -->
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Date Versement</th>
                  <th>Client</th>
                  <th>Montant (DH)</th>
                  <th>Mode Paiement</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody id="versementBody">
                <tr><td colspan="5" class="text-center text-muted">Aucun versement trouvé.</td></tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <div id="pagination-info" class="text-muted small"></div>
            <div>
              <button id="prevPage" class="btn btn-sm btn-outline-secondary">« Précédent</button>
              <button id="nextPage" class="btn btn-sm btn-outline-secondary">Suivant »</button>
            </div>
          </div>
        </div>

        <!-- 🔹 Total Global -->
        <div class="row text-center mb-4 g-3">
          <div class="col-md-4 mx-auto">
            <div class="card bg-success bg-opacity-10 border-success h-100">
              <div class="card-body">
                <h6 class="text-success">Total Versement Global</h6>
                <h3 id="totalVersementGlobal" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</main>

<script>
$(document).ready(function () {
  let currentPage = 1;
  const perPage = 25;

  function loadVersements() {
    const q = $("#searchInput").val().trim();
    const date_debut = $("#dateStart").val();
    const date_fin = $("#dateEnd").val();

    Swal.fire({ title: "Chargement...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    $.ajax({
      url: "../includes/ajax/journal/get_versement_journal.php",
      method: "POST",
      dataType: "json",
      data: { page: currentPage, perPage, q, date_debut, date_fin },
      success: function (res) {
        Swal.close();
        let html = "";
        let totalMontant = 0;

        if (res.rows && res.rows.length > 0) {
          res.rows.forEach(r => {
            totalMontant += parseFloat(r.montant || 0);
            html += `
              <tr>
                <td>${r.date_versement}</td>
                <td>${r.client_nom}</td>
                <td class="text-end fw-bold">${parseFloat(r.montant).toFixed(2)}</td>
                <td>${r.mode_paiement || '-'}</td>
                <td>${r.note || '-'}</td>
              </tr>`;
          });
        } else {
          html = '<tr><td colspan="5" class="text-center text-muted">Aucun versement trouvé.</td></tr>';
        }

        $("#versementBody").html(html);
        $("#totalVersementGlobal").text(totalMontant.toFixed(2) + " DH");

        const totalPages = Math.ceil((res.total || 0) / perPage);
        $("#pagination-info").text(`Page ${currentPage} / ${totalPages || 1} (${res.total || 0} enregistrements)`);

        $("#prevPage").prop("disabled", currentPage <= 1);
        $("#nextPage").prop("disabled", currentPage >= totalPages);
      },
      error: function (xhr) {
        Swal.close();
        Swal.fire("Erreur", xhr.responseText || "Erreur AJAX", "error");
      }
    });
  }

  $("#btnFilter, #searchInput, #dateStart, #dateEnd").on("change keyup", function () {
    currentPage = 1;
    loadVersements();
  });

  $("#prevPage").on("click", function () {
    if (currentPage > 1) {
      currentPage--;
      loadVersements();
    }
  });

  $("#nextPage").on("click", function () {
    currentPage++;
    loadVersements();
  });

  $("#btnPrint").on("click", function () {
    const params = new URLSearchParams({
      date_debut: $("#dateStart").val(),
      date_fin: $("#dateEnd").val(),
      q: $("#searchInput").val().trim()
    });
    window.open('print-journal-versement.php?' + params.toString(), "_blank");
  });

  loadVersements();
});
</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>
