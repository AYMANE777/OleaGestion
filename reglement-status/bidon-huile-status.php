<?php
$activeSection = 'journale-status';
$activePage = 'journale-status-bidon-huile';
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
          <h4 class="card-title mb-0">Journal - Bons d’Huile (Clients)</h4>
        </div>

        <!-- 🔹 Filtres -->
        <div class="card-header d-flex justify-content-between align-items-center">

          <select id="typeCommande" class="form-select" style="width:180px;">
            <option value="tous">Tous</option>
            <option value="huile">Huile seule</option>
            <option value="huile_bidon">Huile + Bidons</option>
          </select>

          <select id="statutPaiement" class="form-select" style="width:180px;">
            <option value="tous">Tous</option>
            <option value="paye">Payé</option>
            <option value="non_paye">Non Payé</option>
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

        <!-- 🔹 Tableau principal -->
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Client</th>
                  <th>Quantité (L)</th>
                  <th>Prix Unitaire (DH)</th>
                  <th>Total Huile (DH)</th>
                  <th>Total Bidons (DH)</th>
                  <th>Total Global (DH)</th>
                  <th>Mode Paiement</th>
                  <th>Fournisseur du Prix</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="bonHuileBody">
                <tr>
                  <td colspan="10" class="text-center text-muted">
                    Sélectionnez un client ou une période pour afficher les bons d’huile.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 🔹 Section Totaux -->
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
                <h6 class="text-primary">Montant Total (Huile + Bidons)</h6>
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

          <!-- 🟡 Nouvelle carte : Total Bidons -->
         

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

    function loadBons() {
      const id_client = $("#clientSelect").val();
      const date_debut = $("#dateStart").val();
      const date_fin = $("#dateEnd").val();

      if (!id_client && !date_debut && !date_fin) {
        $("#bonHuileBody").html('<tr><td colspan="10" class="text-center text-muted">Sélectionnez un client et/ou une période.</td></tr>');
        $("#quantite_totale").text("0.00 L");
        $("#somme_totale").text("0.00 DH");
        $("#total_bons").text("0");
        $("#total_bidon").text("0");
        return;
      }

      Swal.fire({
        title: "Chargement...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      $.ajax({
        url: "../includes/ajax/journal/get_bidon_huile_statut.php",
        method: "POST",
        dataType: "json",
        data: {
          id_client, date_debut, date_fin,
          type_commande: $("#typeCommande").val(),
          statut_paiement: $("#statutPaiement").val()
        },
        success: function (res) {
          Swal.close();
          if (res.ok) {
            const quantiteTotale = res.quantite_totale || 0;
            const sommeTotale = res.somme_totale || 0;
            const sommePayee = res.somme_payee || 0;
            const sommeNonPayee = res.somme_non_paye || 0;
            const totalBidon = res.total_bidon || 0;
            const rows = res.rows || [];

            $("#quantite_totale").text(quantiteTotale.toFixed(2) + " L");
            $("#somme_totale").text(sommeTotale.toFixed(2) + " DH");
            $("#somme_payee").text(sommePayee.toFixed(2) + " DH");
            $("#somme_non_paye").text(sommeNonPayee.toFixed(2) + " DH");
            $("#total_bons").text(rows.length);
            $("#total_bidon").text(totalBidon.toFixed(0));

            if (rows.length === 0) {
              $("#bonHuileBody").html('<tr><td colspan="10" class="text-center text-muted">Aucun bon trouvé.</td></tr>');
              return;
            }

            let html = "";
            rows.forEach(b => {
              const totalBidonDH = parseFloat(b.total_prix_bidon_bon || 0);
              const totalGlobal = parseFloat(b.prix_net || 0);
              html += `
                <tr>
                  <td>${b.date_bon}</td>
                  <td>${b.client}</td>
                  <td>${parseFloat(b.quantite || 0).toFixed(2)}</td>
                  <td>${parseFloat(b.prix_unitaire || 0).toFixed(2)}</td>
                  <td>${(totalGlobal - totalBidonDH).toFixed(2)}</td>
                  <td>${totalBidonDH.toFixed(2)}</td>
                  <td class="fw-bold text-end">${totalGlobal.toFixed(2)}</td>
                  <td>${b.mode_paiement || "-"}</td>
                  <td>${b.provider || "-"}</td>
                  <td>
                    <button class="btn btn-sm btn-outline-info btn-view-bon"
                      data-client="${b.client}"
                      data-date="${b.date_bon}"
                      data-ville="${b.ville}"
                      data-quantite="${parseFloat(b.quantite || 0).toFixed(2)}"
                      data-prix-net="${parseFloat(b.prix_net || 0).toFixed(2)}"
                      data-prix-unitaire="${parseFloat(b.prix_unitaire || 0).toFixed(2)}"
                      data-mode="${b.mode_paiement || '-'}"
                      data-provider="${b.provider || '-'}"
                      data-details='${JSON.stringify(b.details_bidons)}'>
                      <i class="bi bi-eye"></i>
                    </button>
                  </td>
                </tr>`;
            });
            $("#bonHuileBody").html(html);
          } else {
            Swal.fire("Erreur", res.error || "Impossible de charger les données.", "error");
          }
        },
        error: function (xhr) {
          Swal.close();
          Swal.fire("Erreur Réseau", xhr.responseText || "Erreur AJAX", "error");
        }
      });
    }

    // === Modal détails ===
    $(document).on('click', '.btn-view-bon', function () {
      const client = $(this).data('client');
      const date = $(this).data('date');
      const ville = $(this).data('ville');
      const quantite = $(this).data('quantite');
      const prix_net = $(this).data('prix-net');
      const prix_unitaire = $(this).data('prix-unitaire');
      const mode = $(this).data('mode');
      const provider = $(this).data('provider');
      const details = JSON.parse($(this).attr('data-details'));

      $("#bonHuileInfo").html(`
        <p><strong>Client :</strong> ${client}</p>
        <p><strong>Date :</strong> ${date}</p>
        <p><strong>Ville :</strong> ${ville || '-'}</p>
        <p><strong>Quantité :</strong> ${quantite} L</p>
        <p><strong>Prix Unitaire :</strong> ${prix_unitaire} DH</p>
        <p><strong>Total (Huile + Bidons) :</strong> <span class="fw-bold">${prix_net} DH</span></p>
        <p><strong>Mode de Paiement :</strong> ${mode}</p>
        <p><strong>Fournisseur du prix :</strong> ${provider}</p>
      `);

      let html = "";
      if (details && details.length) {
        details.forEach(d => {
          html += `<tr>
            <td>${d.nom_bidon}</td>
            <td>${d.type_bidon}</td>
            <td>${d.quantite_bidon}</td>
            <td>${d.prix_bidon}</td>
          </tr>`;
        });
      } else {
        html = `<tr><td colspan="4" class="text-center text-muted">Aucun bidon enregistré</td></tr>`;
      }
      $("#bonHuileDetails").html(html);

      new bootstrap.Modal(document.getElementById('bonHuileModal')).show();
    });

    // === Filtres ===
    $("#clientSelect, #dateStart, #dateEnd, #btnFilter").on("change click", loadBons);

    // === Impression ===
    $("#btnPrint").on("click", function () {
      const params = new URLSearchParams({
        id_client: $("#clientSelect").val(),
        date_debut: $("#dateStart").val(),
        date_fin: $("#dateEnd").val(),
        type_commande: $("#typeCommande").val(),
        statut_paiement: $("#statutPaiement").val()
      });
      window.open('print-bidon-huile.php?' + params.toString(), "_blank");
    });
  });
</script>

<?php
require __DIR__ . '/../includes/scripts.php';
?>
