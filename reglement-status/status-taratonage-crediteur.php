<?php
$activeSection = 'journale-status';
$activePage = 'journale-status-taratonage-credit';
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
          <h4 class="card-title mb-0">Journal - Situation Globale par Bon Client</h4>
        </div>

        <!-- 🔹 Filtres -->
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

        <!-- 🔹 Tableau principal -->
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Client</th>
                  <th>Réf. Bon</th>
                  <th>Note(s)</th>
                  <th>Poids Total (Kg)</th>
                
                </tr>
              </thead>
              <tbody id="clientCreditBody">
                <tr>
                  <td colspan="5" class="text-center text-muted">
                    Sélectionnez une période pour afficher les résultats.
                  </td>
                </tr>
              </tbody>
            </table>
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
      $("#clientCreditBody").html('<tr><td colspan="5" class="text-center text-muted">Veuillez choisir une période.</td></tr>');
      return;
    }

    Swal.fire({ title: "Chargement...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    $.ajax({
      url: "../includes/ajax/journal/get_taratonage_credit_status.php",
      method: "POST",
      dataType: "json",
      data: { date_debut, date_fin },
      success: function (res) {
        Swal.close();

        if (!res.ok) {
          Swal.fire("Erreur", res.error || "Impossible de charger les données.", "error");
          return;
        }

        let html = "";
        (res.bons || []).forEach(b => {
          html += `
            <tr>
              <td>${b.client_nom}</td>
              <td>${b.ref_bon_client || '-'}</td>
              <td>${b.notes || '-'}</td>
              <td class="text-end">${parseFloat(b.total_poids || 0).toFixed(2)}</td>
          
            </tr>`;
        });

        $("#clientCreditBody").html(html || '<tr><td colspan="5" class="text-center text-muted">Aucun bon trouvé.</td></tr>');

        // 🔹 Totaux globaux
        $("#total_poids").text(res.totaux.poids + " Kg");
        $("#total_credit").text(res.totaux.credit + " DH");
      },
      error: function (xhr) {
        Swal.close();
        Swal.fire("Erreur Réseau", xhr.responseText || "Erreur AJAX", "error");
      }
    });
  }

  $("#dateStart, #dateEnd, #btnFilter").on("change click", loadClientCredit);

  $("#btnPrint").on("click", function () {
    const params = new URLSearchParams({
      date_debut: $("#dateStart").val(),
      date_fin: $("#dateEnd").val()
    });
    window.open('print-client-taratonage-global.php?' + params.toString(), "_blank");
  });
});
</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>
