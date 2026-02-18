<?php
$activeSection = 'journale-status';
$activePage = 'journale-status-ouvrier';
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
          <h4 class="card-title mb-0">Journal des Avances - Ouvriers</h4>
        </div>

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div style="width:250px;">
            <select id="clientSelect" class="form-select js-choices">
              <option value="">-- Sélectionner un ouvrier --</option>
              <?php
              $sql = "SELECT id_ouvrier, CONCAT(prenom, ' ', nom) AS nom FROM ouvrier ORDER BY nom ASC";
              $res = $connect->query($sql);
              while ($c = $res->fetch_assoc()) {
                echo "<option value='{$c['id_ouvrier']}'>" . htmlspecialchars($c['nom']) . "</option>";
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
                  <th>Ouvrier</th>
                  <th>CIN</th>
                  <th>Montant (DH)</th>
           
          
                </tr>
              </thead>
              <tbody id="statusdesavances">
                <tr>
                  <td colspan="6" class="text-center text-muted">
                    Sélectionnez un ouvrier ou une période pour afficher.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="row text-center mb-2 g-3">
        

    

          <div class="col-md-6 col-sm-6">
            <div class="card bg-primary bg-opacity-10 border-primary h-100">
              <div class="card-body">
                <h6 class="text-primary">Montant Total</h6>
                <h3 id="somme_totale" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>

          <div class="col-md-6 col-sm-6">
            <div class="card bg-secondary bg-opacity-10 border-secondary h-100">
              <div class="card-body">
                <h6 class="text-secondary">Total des Avances</h6>
                <h3 id="total_bons" class="fw-bold">0</h3>
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

  const tbody = $("#statusdesavances");

  async function loadAvances() {
    const id_client = $("#clientSelect").val();
    const date_from = $("#dateStart").val();
    const date_to   = $("#dateEnd").val();

    if (!id_client && !date_from && !date_to) {
      tbody.html(`<tr><td colspan="6" class="text-center text-muted">Sélectionnez un ouvrier ou une période.</td></tr>`);
      resetTotals();
      return;
    }

    Swal.fire({ title: "Chargement...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
      const res = await fetch("../includes/ajax/journal/get_List_avances_ouvriers.php", {
        method: "POST",
        body: new URLSearchParams({
          id_client: id_client || "",
          date_debut: date_from || "",
          date_fin: date_to || ""
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
        tbody.html(`<tr><td colspan="6" class="text-center text-muted">Aucune avance trouvée.</td></tr>`);
        resetTotals();
        return;
      }

      let html = "";
      rows.forEach(r => {
       

        html += `
          <tr>
            <td>${r.date}</td>
            <td>${r.ouvrier}</td>
            <td>${r.cin}</td>
            <td class="text-end fw-bold">${r.montant} DH</td>
          
       
          </tr>`;
      });

      tbody.html(html);

      $("#somme_totale").text(parseFloat(data.somme_totale).toFixed(2) + " DH");
      $("#somme_payee").text(parseFloat(data.somme_payee).toFixed(2) + " DH");

      $("#total_bons").text(data.total_avance);

    } catch (err) {
      Swal.close();
      Swal.fire("Erreur", err.message || "Erreur de chargement.", "error");
    }
  }

  function resetTotals() {
    $("#somme_totale, #somme_payee, #somme_non_paye").text("0.00 DH");
    $("#total_bons").text("0");
  }

  $("#btnFilter").on("click", e => { e.preventDefault(); loadAvances(); });
  $("input").on("keypress", e => { if (e.which === 13) { e.preventDefault(); loadAvances(); } });
  
  
    // === Bouton Imprimer ===
  $("#btnPrint").on("click", function (e) {
    e.preventDefault();

    const id_ouvrier = $("#clientSelect").val();
    const date_debut = $("#dateStart").val();
    const date_fin   = $("#dateEnd").val();

    // Construit proprement l’URL
    const params = new URLSearchParams();
    if (id_ouvrier) params.set("id_ouvrier", id_ouvrier);  // <— le print attend id_ouvrier
    if (date_debut) params.set("date_debut", date_debut);
    if (date_fin)   params.set("date_fin", date_fin);

    const url = "print-avances-ouvriers.php" + (params.toString() ? "?" + params.toString() : "");
    window.open(url, "_blank");
  });


});
</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>
