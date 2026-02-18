<?php
$activeSection = 'ClientCrediteurOlive';
$activePage = 'ClientCrediteurOlive-taratonage';
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="card card-outline card-info mb-0">
        <div class="card-header p-0 border-bottom-0">
          <ul class="nav nav-tabs" id="formTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="taratonage-tab" data-bs-toggle="tab" data-bs-target="#tab-taratonage"
                type="button" role="tab" aria-controls="tab-taratonage" aria-selected="true">
                Ajouter un taratonage
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#tab-list" type="button"
                role="tab" aria-controls="tab-list" aria-selected="false">
                Liste des taratonages
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="tab-content mt-3" id="formTabsContent">

        <!-- ==================== FORMULAIRE D’AJOUT TARATONAGE ==================== -->
        <div class="tab-pane fade show active" id="tab-taratonage" role="tabpanel" aria-labelledby="taratonage-tab">
          <div class="card card-info card-outline mb-4">
            <div class="card-header">
              <div class="card-title">Ajouter un taratonage client olive</div>
            </div>

            <form class="needs-validation" id="addTaratonageClientOlive"
              action="../includes/ajax/ClientOlive/addTaratonageClientOlive.php" method="post" novalidate>
              <div class="card-body">
                <div class="row g-3">

                  <!-- Client -->
                  <div class="col-md-6">
                    <label class="form-label">Client</label>
                    <select class="form-select js-choices" name="id_client_olive" required>
                      <option value="">-- Sélectionner un client --</option>
                      <?php
                      require_once '../config/config.ini.php';
                      $connect->set_charset('utf8mb4');
                      $query = $connect->query("SELECT id_client_olive, CONCAT(nom,' ',prenom) AS nomcomplet FROM clientolive ORDER BY nom ASC");
                      while ($row = $query->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['id_client_olive']) . '">' . htmlspecialchars($row['nomcomplet']) . '</option>';
                      }
                      ?>
                    </select>
                  </div>



                  <!-- Poids net (calcul auto) -->
                  <div class="col-md-6">
                    <label class="form-label">Poids net (kg)</label>
                    <input type="number" step="0.01" class="form-control" id="poid_net" name="poid_net" required>
                  </div>

                  <!-- Prix unitaire -->
                  <div class="col-md-6">
                    <label class="form-label">Prix unitaire (DH/kg)</label>
                    <input type="number" step="0.01" class="form-control" id="prix_unitaire" name="prix_unitaire"
                      required>
                  </div>

                  <!-- Montant total (auto) -->
                  <div class="col-md-6">
                    <label class="form-label">Montant total (DH)</label>
                    <input type="number" step="0.01" class="form-control" id="montant_total" name="montant_total"
                      readonly required>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Provider</label>
                    <select class="form-select js-choices" name="fournisseur" required>
                      <option value="">-- Sélectionner --</option>
                      <option value="Youssef">Youssef</option>
                      <option value="Hamid">Hamid</option>
                      <option value="Said">Said</option>
                      <option value="Yassine">Yassine</option>
                    </select>
                  </div>

                  <!-- Preuve Scanne  -->
                  <div class="col-md-6">
                    <label class="form-label">Preuve (Obligatoire)</label>
                    <input type="file" class="form-control" id="scanne_bon" name="scanne_bon" accept=".jpg,.jpeg,.png"
                      required>
                  </div>
<div class="col-md-6">
  <label class="form-label">Référence de bon</label>
  <input type="number" class="form-control" id="ref_olive" name="ref_olive"  required>
</div>

                  <!-- Note -->
                  <div class="col-md-6">
                    <label class="form-label">Note</label>
                    <textarea class="form-control" name="note" rows="2"></textarea>
                  </div>

                </div>
              </div>
              <div class="card-footer text-end">
                <button class="btn btn-primary text-white" type="submit">Enregistrer</button>
              </div>
            </form>
          </div>
        </div>
        <!-- ==================== FIN FORMULAIRE ==================== -->

        <!-- ==================== LISTE TARATONAGES ==================== -->
        <div class="tab-pane fade" id="tab-list" role="tabpanel" aria-labelledby="list-tab">
          <div class="card card-info card-outline mb-4">
            <div class="container-fluid py-4">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-2 mb-3">
                <h4 class="m-0">Liste des taratonages client olive</h4>
                <div class="d-flex gap-2">
                  <input class="form-control" style="min-width:260px" id="searchTaratonage" placeholder="Rechercher...">
                  <select class="form-select" id="perPageTaratonage" style="width:140px">
                    <option value="10">10 / page</option>
                    <option value="25" selected>25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                  </select>
                  <button class="btn btn-outline-secondary" id="btnRefreshTaratonage" title="Actualiser">
                    <i class="bi bi-arrow-repeat"></i>
                  </button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table align-middle table-hover" id="taratonageTable">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>Client</th>
                      <th>Poids net (kg)</th>
                      <th>Prix unitaire</th>
                      <th>Montant total (DH)</th>
                      <th>Note</th>
                      <th>Preuve</th>
                      <th class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="taratonageBody"></tbody>
                </table>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3">
                <div><span id="taratonageInfo" class="text-muted"></span></div>
                <div class="btn-group">
                  <button class="btn btn-outline-secondary" id="prevTaratonage">← Précédent</button>
                  <button class="btn btn-outline-secondary" id="nextTaratonage">Suivant →</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- ==================== FIN LISTE ==================== -->

      </div>
    </div>
  </div>
  <div class="modal fade" id="editTaratonageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form id="editTaratonageForm" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title">Modifier Taratonage</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id_taratonage" id="edit_id_taratonage">
            <div class="row g-3">

              <div class="col-md-6">
                <label class="form-label">Client</label>
               <select class="form-select js-choices" id="edit_id_client_olive" name="id_client_olive" required>
                  <option value="">-- Sélectionner un client --</option>
                  <?php
                  require_once '../config/config.ini.php';
                  $query = $connect->query("SELECT id_client_olive, CONCAT(nom,' ',prenom) AS nomcomplet FROM clientolive ORDER BY nom ASC");
                  while ($row = $query->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($row['id_client_olive']) . '">' . htmlspecialchars($row['nomcomplet']) . '</option>';
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Nouvelle preuve (facultatif)</label>
                <input type="file" class="form-control" name="scanne_bon" accept=".jpg,.jpeg,.png">
              </div>
            </div>



            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Poids net (kg)</label>
                <input type="number" step="0.01" class="form-control" id="edit_poid_net" name="poid_net" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Prix unitaire (DH/kg)</label>
                <input type="number" step="0.01" class="form-control" id="edit_prix_unitaire" name="prix_unitaire"
                  required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Montant total (DH)</label>
                <input type="number" step="0.01" class="form-control" id="edit_montant_total" name="montant_total"
                  readonly required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Provider</label>
                <select class="form-select" name="fournisseur" id="edit_provider" required>
                    <option value="Yassine">Yassine</option>
                  <option value="Youssef">Youssef</option>
                  <option value="Hamid">Hamid</option>
                  <option value="Said">Said</option>
                </select>
              </div>
              
              <div class="col-md-12">
                <label class="form-label">Note</label>
                <textarea class="form-control" name="note" id="edit_note" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</main>

<script>
  document.addEventListener("DOMContentLoaded", () => {


    const poidNetInput = document.getElementById("poid_net");
    const prixUnitaireInput = document.getElementById("prix_unitaire");
    const montantTotalInput = document.getElementById("montant_total");

    function updateMontantTotal() {
      const poidNet = parseFloat(poidNetInput.value) || 0;
      const prixUnitaire = parseFloat(prixUnitaireInput.value) || 0;
      const montantTotal = poidNet * prixUnitaire;

      montantTotalInput.value = montantTotal.toFixed(2);
    }

    // Écoute les changements
    poidNetInput.addEventListener("input", updateMontantTotal);
    prixUnitaireInput.addEventListener("input", updateMontantTotal);



    // === AJAX Ajout taratonage ===
    $("#addTaratonageClientOlive").on("submit", function (e) {
      e.preventDefault();
      const fd = new FormData(this);

      Swal.fire({
        title: "Ajout en cours...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
      });

      $.ajax({
        url: "../includes/ajax/ClientOlive/addTaratonageClientOlive.php",
        method: "POST",
        dataType: "json",
        data: fd,
        processData: false,
        contentType: false,
        success: function (response) {
          Swal.close();
          Swal.fire({
            icon: response.status === "success" ? "success" : "error",
            title: response.status === "success" ? "Ajouté" : "Erreur",
            text: response.message || "",
          }).then(() => {
            if (response.status === "success") {
              $("#addTaratonageClientOlive")[0].reset();
              loadTaratonages();
            }
          });
        },
        error: function (xhr) {
          Swal.close();
          Swal.fire({
            icon: "error",
            title: "Erreur réseau",
            text: xhr.responseText || "Impossible d'envoyer la requête.",
          });
        },
      });
    });

    // === Liste taratonages ===
    const tbody = $("#taratonageBody");
    const infoSpan = $("#taratonageInfo");
    const searchBox = $("#searchTaratonage");
    const perPageSel = $("#perPageTaratonage");
    const btnPrev = $("#prevTaratonage");
    const btnNext = $("#nextTaratonage");
    const btnRefresh = $("#btnRefreshTaratonage");

    let page = 1, perPage = 25, total = 0, q = "";

    async function loadTaratonages() {
      const params = new URLSearchParams({ page, perPage, q });
      const res = await fetch(`../includes/ajax/ClientOlive/taratonage_list.php?` + params.toString(), { headers: { "Accept": "application/json" } });
      if (!res.ok) {
        tbody.html(`<tr><td colspan="6" class="text-center text-danger">Erreur de chargement</td></tr>`);
        infoSpan.text("");
        return;
      }
      const data = await res.json();
      renderTable(data.rows || []);
      total = data.total || 0;
      renderInfo();
    }

    function renderTable(rows) {
      if (!rows.length) {
        tbody.html(`<tr><td colspan="6" class="text-center text-muted">Aucun taratonage</td></tr>`);
        return;
      }
      tbody.html(rows.map(r => `
      <tr>
        <td>${r.date_taratonage}</td>
        <td>${r.client_nom}</td>
        <td>${r.poid_net}</td>
        <td>${r.prix_unitaire}</td>
        <td class="fw-bold text-success">${r.montant_total}</td>
        <td>${r.note || ''}</td>
        <td class="text-center">
        ${r.scanne_bon && r.scanne_bon_url
          ? `<a href="${r.scanne_bon_url}" target="_blank" title="Voir le bon scanné">
               <img src="${r.scanne_bon_url}" alt="Preuve" style="width:55px;height:55px;object-fit:cover;border-radius:6px;cursor:pointer;border:1px solid #ddd;">
             </a>`
          : '<span class="text-muted">Aucune</span>'
        }
      </td>
        <td class="text-center">
        <button class="btn btn-outline-success btn-sm"
                onclick="window.open('printTaratonageClientOlive.php?id_taratonage=${r.id_taratonage}', '_blank')">
          <i class="bi bi-printer"></i>
        </button>
        <button class="btn btn-outline-warning btn-sm ms-1 btn-edit-taratonage-olive"
          data-id="${r.id_taratonage}"
          data-client="${r.id_client_olive}"
          data-poid="${r.poid_net}"
          data-prix="${r.prix_unitaire}"
          data-montant="${r.montant_total}"
          data-provider="${r.provider}"
          data-note="${r.note || ''}">
    <i class="bi bi-pencil"></i>
  </button>
      </td>
      </tr>
    `).join(""));
    }

    function renderInfo() {
      const start = total === 0 ? 0 : (page - 1) * perPage + 1;
      const end = Math.min(page * perPage, total);
      infoSpan.text(`Affiche ${start}–${end} sur ${total}`);
      btnPrev.prop('disabled', page <= 1);
      btnNext.prop('disabled', page * perPage >= total);
    }

    btnPrev.on("click", () => { if (page > 1) { page--; loadTaratonages(); } });
    btnNext.on("click", () => { if (page * perPage < total) { page++; loadTaratonages(); } });
    perPageSel.on("change", () => { perPage = parseInt(perPageSel.val(), 10); page = 1; loadTaratonages(); });
    btnRefresh.on("click", loadTaratonages);
    searchBox.on("input", debounce(() => { q = searchBox.val(); page = 1; loadTaratonages(); }, 300));

    function debounce(fn, wait) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); }; }

    loadTaratonages();
  });
  // === OUVERTURE MODALE ===
$(document).on("click", ".btn-edit-taratonage-olive", function () {
  const b = $(this);

  // Nettoyage des valeurs numériques
  const poid = (b.data("poid") || "").toString().replace(/\s/g, '').replace(',', '.');
  const prix = (b.data("prix") || "").toString().replace(/\s/g, '').replace(',', '.');
  const montant = (b.data("montant") || "").toString().replace(/\s/g, '').replace(',', '.');

  // Remplir les champs
  $("#edit_id_taratonage").val(b.data("id"));
  $("#edit_poid_net").val(poid);
  $("#edit_prix_unitaire").val(prix);
  $("#edit_montant_total").val(montant);
  $("#edit_provider").val(b.data("provider"));
  $("#edit_note").val(b.data("note") || "");

  // Ouvre la modale
  new bootstrap.Modal("#editTaratonageModal").show();
});

  // === CALCUL AUTO DU MONTANT ===
  $("#edit_poid_net, #edit_prix_unitaire").on("input", function () {
    const p = parseFloat($("#edit_poid_net").val()) || 0;
    const u = parseFloat($("#edit_prix_unitaire").val()) || 0;
    $("#edit_montant_total").val((p * u).toFixed(2));
  });

  // === SUBMIT FORM UPDATE ===
  $("#editTaratonageForm").on("submit", function (e) {
    e.preventDefault();
    const fd = new FormData(this);


    Swal.fire({ title: "Mise à jour...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    $.ajax({
      url: "../includes/ajax/ClientOlive/updateTaratonageClientOlive.php",
      method: "POST",
      data: fd,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (res) {
        Swal.close();
        Swal.fire({
          icon: res.status === "success" ? "success" : "error",
          title: res.status === "success" ? "Modifié" : "Erreur",
          text: res.message
        }).then(() => {
          if (res.status === "success") { $("#editTaratonageModal").modal("hide"); loadTaratonages(); }
        });
      },
      error: function (xhr) {
        Swal.close();
        Swal.fire("Erreur réseau", xhr.responseText || "Erreur AJAX", "error");
      }
    });
  });

</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>