<?php
$activeSection = 'journale-status';
$activePage = 'journale-taratonage-credit';
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
$connect->set_charset('utf8mb4');
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="card card-outline card-primary mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Journal - Situation Globale Clients</h4>
        </div>

        <!-- ðŸ”¹ Filtres -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <input type="date" id="dateStart" class="form-control" style="width:180px;">
          <input type="date" id="dateEnd" class="form-control" style="width:180px;">

          <button id="btnFilter" class="btn btn-outline-primary">
            <i class="bi bi-funnel"></i> Filtrer
          </button>

          <button id="btnPrint" class="btn btn-outline-dark">
            <i class="bi bi-printer"></i> Imprimer
          </button>
        </div>

        <!-- ðŸ”¹ Tableau principal -->
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Client</th>
                  <th>Ref. Bon(s)</th>
                  <th>Poids Total (Kg)</th>
                  <th>Prix Moyen (DH)</th>
                  <th>Montant Credit (DH)</th>
                </tr>
              </thead>
              <tbody id="clientCreditBody">
                <tr>
                  <td colspan="5" class="text-center text-muted">
                    SÃ©lectionnez une pÃ©riode pour afficher les resultats.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ðŸ”¹ Totaux -->
        <div class="row text-center mb-4 g-3">
          <div class="col-md-4 col-sm-6">
            <div class="card bg-info bg-opacity-10 border-info h-100">
              <div class="card-body">
                <h6 class="text-info">Poids Total (Kg)</h6>
                <h3 id="total_poids" class="fw-bold">0.00 Kg</h3>
              </div>
            </div>
          </div>

          <div class="col-md-4 col-sm-6">
            <div class="card bg-primary bg-opacity-10 border-primary h-100">
              <div class="card-body">
                <h6 class="text-primary">Montant Total CrÃ©dit</h6>
                <h3 id="total_credit" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>

          <div class="col-md-4 col-sm-6">
            <div class="card bg-success bg-opacity-10 border-success h-100">
              <div class="card-body">
                <h6 class="text-success">Montant Total VersÃ©</h6>
                <h3 id="total_versement" class="fw-bold">0.00 DH</h3>
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
  function loadClientCredit() {
    const date_debut = $("#dateStart").val();
    const date_fin = $("#dateEnd").val();

    if (!date_debut && !date_fin) {
      $("#clientCreditBody").html('<tr><td colspan="5" class="text-center text-muted">Veuillez choisir une pÃ©riode.</td></tr>');
      return;
    }

    Swal.fire({ title: "Chargement...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    $.ajax({
      url: "../includes/ajax/journal/get_client_credit_status.php",
      method: "POST",
      dataType: "json",
      data: { date_debut, date_fin },
      success: function (res) {
        Swal.close();

        if (!res.ok) {
          Swal.fire("Erreur", res.error || "Impossible de charger les donnÃ©es.", "error");
          return;
        }

        let html = "";
        (res.clients || []).forEach(c => {
          html += `
            <tr>
              <td>${c.client_nom}</td>
              <td>${c.ref_bons || '-'}</td>
              <td class="text-end">${parseFloat(c.total_poids || 0).toFixed(2)}</td>
              <td class="text-end">${parseFloat(c.prix_moyen || 0).toFixed(2)}</td>
              <td class="text-end">${parseFloat(c.total_credit).toFixed(2)}</td>
            </tr>`;
        });

        $("#clientCreditBody").html(html || '<tr><td colspan="5" class="text-center text-muted">Aucun client trouvÃ©.</td></tr>');

        // ðŸ”¹ Totaux
        $("#total_poids").text(res.totaux.poids + " Kg");
        $("#total_credit").text(res.totaux.credit + " DH");
        $("#total_versement").text(res.totaux.versement + " DH");
      },
      error: function (xhr) {
        Swal.close();
        Swal.fire("Erreur RÃ©seau", xhr.responseText || "Erreur AJAX", "error");
      }
    });
  }

  $("#dateStart, #dateEnd, #btnFilter").on("change click", loadClientCredit);

  $("#btnPrint").on("click", function () {
    const params = new URLSearchParams({
      date_debut: $("#dateStart").val(),
      date_fin: $("#dateEnd").val()
    });
    window.open('print-client-tarat.php?' + params.toString(), "_blank");
  });
});
</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>
