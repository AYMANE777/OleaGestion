<?php


$activeSection = 'ClientCrediteurOlive';
$activePage = 'ClientCrediteurOlive-ajouter';
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
              <button class="nav-link active" id="client-tab" data-bs-toggle="tab"
                data-bs-target="#tab-client" type="button" role="tab" aria-controls="tab-client"
                aria-selected="true">
                Ajouter un client créditeur
              </button>
            </li>

            <li class="nav-item" role="presentation">
              <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#tab-list"
                type="button" role="tab" aria-controls="tab-list" aria-selected="false">
                Liste des clients créditeurs
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="tab-content mt-3" id="formTabsContent">

        <!-- ==================== FORMULAIRE D’AJOUT CLIENT ==================== -->
        <div class="tab-pane fade show active" id="tab-client" role="tabpanel" aria-labelledby="client-tab">
          <div class="card card-info card-outline mb-4">
            <div class="card-header">
              <div class="card-title">Ajouter un client créditeur</div>
            </div>

            <form class="needs-validation" id="addClientOlive"
              action="../includes/ajax/ClientOlive/addClientOlive.php" method="post" novalidate>
              <div class="card-body">
                <div class="row g-3">

                  <div class="col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" class="form-control" name="nom" required>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Prénom</label>
                    <input type="text" class="form-control" name="prenom" required>
                  </div>

             

                  <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" name="telephone" minlength="10" maxlength="14" required>
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

                  <div class="col-md-6">
                    <label class="form-label">Note</label>
                    <textarea class="form-control" name="note"></textarea>
                  </div>

                </div>
              </div>
              <div class="card-footer text-end">
                <button class="btn btn-primary text-white" type="submit">Ajouter</button>
              </div>
            </form>
          </div>
        </div>
        <!-- ==================== FIN FORMULAIRE AJOUT ==================== -->

        <!-- ==================== LISTE DES CLIENTS CRÉDITEURS ==================== -->
        <div class="tab-pane fade" id="tab-list" role="tabpanel" aria-labelledby="list-tab">
          <div class="card card-info card-outline mb-4">
            <div class="container-fluid py-4">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-2 mb-3">
                <h4 class="m-0">Liste des clients créditeurs</h4>
                <div class="d-flex gap-2">
                  <input class="form-control" style="min-width:260px" id="searchClient" placeholder="Rechercher...">
                  <select class="form-select" id="perPageClient" style="width:140px">
                    <option value="10">10 / page</option>
                    <option value="25" selected>25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                  </select>
                  <button class="btn btn-outline-secondary" id="btnRefreshClient" title="Actualiser">
                    <i class="bi bi-arrow-repeat"></i>
                  </button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table align-middle table-hover" id="clientOliveTable">
                  <thead class="table-light">
                    <tr>
                      <th>Date d’ajout</th>
                      <th>Nom complet</th>
                      <th>Téléphone</th>
                      <th>Fournisseur</th>
                      <th>Note</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="clientOliveBody"></tbody>
                </table>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3">
                <div><span id="clientInfo" class="text-muted"></span></div>
                <div class="btn-group">
                  <button class="btn btn-outline-secondary" id="prevClient">← Précédent</button>
                  <button class="btn btn-outline-secondary" id="nextClient">Suivant →</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- ==================== FIN LISTE ==================== -->

      </div>
    </div>
  </div>
  <!-- ==================== MODAL: VOIR CLIENT ==================== -->
<div class="modal fade" id="viewClientModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Détails du client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group">
          <li class="list-group-item"><b>Nom complet:</b> <span id="viewNom"></span></li>
          <li class="list-group-item"><b>Téléphone:</b> <span id="viewTel"></span></li>
          <li class="list-group-item"><b>Fournisseur:</b> <span id="viewFournisseur"></span></li>
          <li class="list-group-item"><b>Note:</b> <span id="viewNote"></span></li>
          <li class="list-group-item"><b>Date d’ajout:</b> <span id="viewDate"></span></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- ==================== MODAL: ÉDITER CLIENT ==================== -->
<div class="modal fade" id="editClientModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title">Modifier le client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editClientForm" class="needs-validation" novalidate>
        <div class="modal-body">
          <input type="hidden" name="id_client_olive" id="editId">
          <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" class="form-control" name="nom" id="editNom" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Prénom</label>
            <input type="text" class="form-control" name="prenom" id="editPrenom" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Téléphone</label>
            <input type="tel" class="form-control" name="telephone" id="editTel" required minlength="10" maxlength="10">
          </div>
          <div class="mb-3">
            <label class="form-label">Fournisseur</label>
            <select class="form-select" name="fournisseur" id="editFournisseur" required>
              <option value="">-- Sélectionner --</option>
              <option value="Youssef">Youssef</option>
              <option value="Hamid">Hamid</option>
              <option value="Said">Said</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Note</label>
            <textarea class="form-control" name="note" id="editNote"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Annuler</button>
          <button class="btn btn-primary text-white" type="submit">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const tbody = document.querySelector("#clientOliveBody");
  const infoSpan = document.querySelector("#clientInfo");
  const searchBox = document.querySelector("#searchClient");
  const perPageSel = document.querySelector("#perPageClient");
  const btnPrev = document.querySelector("#prevClient");
  const btnNext = document.querySelector("#nextClient");
  const btnRefresh = document.querySelector("#btnRefreshClient");
  const editForm = document.querySelector("#editClientForm");

  let page = 1;
  let perPage = parseInt(perPageSel?.value || "25", 10);
  let total = 0;
  let q = "";

  // === Charger la liste des clients ===
  async function loadClients() {
    const params = new URLSearchParams({ page, perPage, q });
    const res = await fetch(`../includes/ajax/ClientOlive/clientolive_list.php?` + params.toString(), {
      headers: { "Accept": "application/json" }
    });

    if (!res.ok) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Erreur de chargement</td></tr>`;
      infoSpan.textContent = "";
      return;
    }

    const data = await res.json();
    renderTable(data.rows || []);
    total = data.total || 0;
    renderInfo();
  }

  // === Afficher le tableau ===
  function renderTable(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Aucun client créditeur</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map((r) => `
      <tr data-id="${r.id_client_olive}">
        <td>${escapeHtml(r.created_at || '')}</td>
        <td>${escapeHtml((r.nom || '') + ' ' + (r.prenom || ''))}</td>
        <td>${escapeHtml(r.telephone || '')}</td>
        <td>${escapeHtml(r.fournisseur || '')}</td>
        <td>${escapeHtml(r.note || '')}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-info btn-view" data-id="${r.id_client_olive}" title="Voir">
            <i class="bi bi-eye"></i>
          </button>
          <button class="btn btn-sm btn-outline-warning btn-edit" data-id="${r.id_client_olive}" title="Modifier">
            <i class="bi bi-pencil"></i>
          </button>
        </td>
      </tr>
    `).join("");

    // === Bouton Voir ===
    tbody.querySelectorAll(".btn-view").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        const res = await fetch(`../includes/ajax/ClientOlive/getClientOlive.php?id=${id}`);
        const data = await res.json();
        if (!data || !data.nom) return alert("Erreur de chargement");

        document.querySelector("#viewNom").textContent = data.nom + " " + data.prenom;
        document.querySelector("#viewTel").textContent = data.telephone;
        document.querySelector("#viewFournisseur").textContent = data.fournisseur;
        document.querySelector("#viewNote").textContent = data.note || "-";
        document.querySelector("#viewDate").textContent = data.created_at || "-";
        new bootstrap.Modal(document.querySelector("#viewClientModal")).show();
      });
    });

    // === Bouton Modifier ===
    tbody.querySelectorAll(".btn-edit").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        const res = await fetch(`../includes/ajax/ClientOlive/getClientOlive.php?id=${id}`);
        const data = await res.json();
        if (!data || !data.nom) return alert("Erreur de chargement");

        document.querySelector("#editId").value = id;
        document.querySelector("#editNom").value = data.nom;
        document.querySelector("#editPrenom").value = data.prenom;
        document.querySelector("#editTel").value = data.telephone;
        document.querySelector("#editFournisseur").value = data.fournisseur;
        document.querySelector("#editNote").value = data.note;
        new bootstrap.Modal(document.querySelector("#editClientModal")).show();
      });
    });
  }

  // === Pagination et recherche ===
  function renderInfo() {
    const start = total === 0 ? 0 : (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, total);
    infoSpan.textContent = `Affiche ${start}–${end} sur ${total}`;
    btnPrev.disabled = page <= 1;
    btnNext.disabled = page * perPage >= total;
  }

  btnPrev?.addEventListener("click", () => { if (page > 1) { page--; loadClients(); } });
  btnNext?.addEventListener("click", () => { if (page * perPage < total) { page++; loadClients(); } });
  perPageSel?.addEventListener("change", () => { perPage = parseInt(perPageSel.value, 10); page = 1; loadClients(); });
  btnRefresh?.addEventListener("click", () => loadClients());
  searchBox?.addEventListener("input", debounce(() => { q = searchBox.value; page = 1; loadClients(); }, 300));

  // === Soumission du formulaire d’édition ===
  editForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(editForm);
    const res = await fetch("../includes/ajax/ClientOlive/editClientOlive.php", {
      method: "POST",
      body: fd
    });
    const data = await res.json();

    if (data.status === "success") {
      bootstrap.Modal.getInstance(document.querySelector("#editClientModal")).hide();
      await loadClients();
      Swal.fire({ icon: "success", title: "Modifié", text: "Client mis à jour avec succès !" });
    } else {
      Swal.fire({ icon: "error", title: "Erreur", text: data.message || "Échec de la modification" });
    }
  });

  // === Utilitaires ===
  function debounce(fn, wait) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); }; }
  function escapeHtml(s) { return String(s || '').replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;"); }

  // === Chargement initial ===
  loadClients();
});
</script>




    </div>
  </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>