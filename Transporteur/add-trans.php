<?php


$activeSection = 'Transporteur';
$activePage = 'Transporteur-ajouter';
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="card card-outline card-info mb-0">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="formTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="patron-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-transporteur" type="button" role="tab"
                                        aria-controls="tab-transporteur" aria-selected="true">
                                        Transporteur
                                    </button>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-check-transporteur" type="button" role="tab"
                                        aria-controls="tab-check-transporteur" aria-selected="false">
                                        Statut des transporteurs
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="tab-content mt-3" id="formTabsContent">


                        <!-- Debut d'ajout du transporteur -->
                        <div class="tab-pane fade show active" id="tab-transporteur" role="tabpanel"
                            aria-labelledby="transporteur-tab">

                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title"> Ajouter un transporteur </div>
                                </div>

                                <form class="needs-validation" id="addTransporteurOlive"
                                    action="../includes/ajax/Transporteur/addTransporteur.php" method="post"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="nom_transporteur" class="form-label">Nom</label>
                                                <input type="text" class="form-control" id="nom_transporteur"
                                                    name="nom_transporteur" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="prenom_transporteur" class="form-label">Prenom </label>
                                                <input type="text" class="form-control" id="prenom_transporteur"
                                                    name="prenom_transporteur" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_transporteur" class="form-label">Photo
                                                    (Optionnel)</label>
                                                <input type="file" class="form-control" id="image_transporteur"
                                                    name="image_transporteur" accept=".jpg,.jpeg,.png">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="num_tel_transporteur" class="form-label">Numéro de téléphone
                                                </label>
                                                <input type="tel" class="form-control" id="num_tel_transporteur"
                                                    name="num_tel_transporteur" minlength="10" maxlength="10" required>
                                                <label for="num_tel_transporteur" id="checking"
                                                    class="form-label"></label>
                                            </div>


                                            <div class="col-md-6">
                                                <label for="num_tel_patron" class="form-label"> Région 
                                                </label>
                                                <textarea type="text" class="form-control" id="Address_Source"
                                                    name="adresse_source" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-primary text-white" type="submit"
                                            name="btn_machine">Ajouter</button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <!-- Fin d'ajout du Transporteur -->



                        <!-- Debut de Status du Transporteur -->
                        <div class="tab-pane fade" id="tab-check-transporteur" role="tabpanel"
                            aria-labelledby="check-transporteur">
                            <div class="card card-info card-outline mb-4">
                                <div class="container-fluid py-4">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card shadow-sm">
                                                <div class="card-body">

                                                    <div
                                                        class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                                                        <h4 class="m-0">Liste des transporteurs</h4>
                                                        <div class="d-flex gap-2">
                                                            <input class="form-control" style="min-width:260px"
                                                                id="searchTrans">
                                                            <select class="form-select" id="perPageTrans"
                                                                style="width:140px">
                                                                <option value="10">10 / page</option>
                                                                <option value="25" selected>25 / page</option>
                                                                <option value="50">50 / page</option>
                                                                <option value="100">100 / page</option>
                                                            </select>
                                                            <button class="btn btn-outline-secondary"
                                                                id="btnRefreshTrans" title="Actualiser">
                                                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="table-responsive">
                                                        <table class="table align-middle table-hover" id="patronsTable">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Date d’ajout </th>
                                                                    <th>Photo</th>
                                                                    <th>Nom complet</th>
                                                                    <th>Numéro de téléphone</th>
                                                                    <th>Région </th>

                                                                    <th class="text-end"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="transporteurBody"></tbody>
                                                        </table>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <div><span id="transpoInfo" class="text-muted"></span></div>
                                                        <div class="btn-group">
                                                            <button class="btn btn-outline-secondary" id="prevTrans">←
                                                                Précédent</button>
                                                            <button class="btn btn-outline-secondary"
                                                                id="nextTrans">Suivant →</button>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- === MODALE D'ÉDITION TRANSPORTEUR === -->
                                    <div class="modal fade" id="editTransporteurModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Modifier transporteur</h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <form id="editTransporteurForm" enctype="multipart/form-data"
                                                        novalidate>
                                                        <input type="hidden" name="id_transporteur"
                                                            id="edit_id_transporteur">

                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Nom</label>
                                                                <input type="text" class="form-control"
                                                                    name="nom_transporteur" id="edit_nom_transporteur"
                                                                    required>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <label class="form-label">Prénom</label>
                                                                <input type="text" class="form-control"
                                                                    name="prenom_transporteur"
                                                                    id="edit_prenom_transporteur" required>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <label class="form-label">Téléphone</label>
                                                                <input type="tel" class="form-control"
                                                                    name="num_tel_transporteur"
                                                                    id="edit_tel_transporteur" minlength="10"
                                                                    maxlength="10" required>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <label class="form-label">Région</label>
                                                                <textarea class="form-control" name="adresse_source"
                                                                    id="edit_adresse_source" required></textarea>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <label class="form-label">Image</label>
                                                                <input type="file" class="form-control"
                                                                    name="image_transporteur"
                                                                    id="edit_image_transporteur"
                                                                    accept=".jpg,.jpeg,.png">
                                                                <img id="edit_preview_image" src=""
                                                                    class="img-thumbnail mt-2" style="max-height:120px">
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <div id="editTransError" class="text-danger mt-2 d-none"></div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Annuler</button>
                                                    <button type="button" class="btn btn-primary"
                                                        id="saveTransporteurBtn">Enregistrer</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- Fin Modale d'édition -->

                                </div>
                            </div>
                        </div>
                        <!-- Fin de Status du Transporteur -->
                    </div>
                </div>
            </div>
            <script>
               (() => {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  const tabButtons = document.querySelectorAll('#formTabs [data-bs-toggle="tab"]');
  tabButtons.forEach(btn => {
    btn.addEventListener('shown.bs.tab', () => {
      localStorage.setItem('activeFormTab', btn.getAttribute('data-bs-target'));
    });
  });
  const saved = localStorage.getItem('activeFormTab');
  if (saved) {
    const triggerEl = document.querySelector(`[data-bs-target="${saved}"]`);
    if (triggerEl) new bootstrap.Tab(triggerEl).show();
  }
})();

document.addEventListener("DOMContentLoaded", () => {

  // === Elements ===
  const tbody = document.querySelector("#transporteurBody");
  const infoSpan = document.querySelector("#transpoInfo");
  const searchBox = document.querySelector("#searchTrans");
  const perPageSel = document.querySelector("#perPageTrans");
  const btnPrev = document.querySelector("#prevTrans");
  const btnNext = document.querySelector("#nextTrans");
  const btnRefresh = document.querySelector("#btnRefreshTrans");

  // === Modale et formulaire d'édition ===
  const editModalEl = document.getElementById("editTransporteurModal");
  const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
  const editForm = document.getElementById("editTransporteurForm");
  const saveBtn = document.getElementById("saveTransporteurBtn");
  const editError = document.getElementById("editTransError");

  // === Pagination state ===
  let page = 1;
  let perPage = parseInt(perPageSel?.value || "25", 10);
  let total = 0;
  let q = "";

  // === Charger la liste des transporteurs ===
  async function loadTransporteur() {
    const params = new URLSearchParams({
      page: String(page),
      perPage: String(perPage),
      q: q.trim()
    });
    const res = await fetch(`../includes/ajax/Transporteur/transporteur_list.php?` + params.toString(), {
      headers: { "Accept": "application/json" }
    });
    if (!res.ok) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Erreur de chargement</td></tr>`;
      infoSpan.textContent = "";
      return;
    }
    const data = await res.json();
    renderTable(data.rows || []);
    total = data.total || 0;
    renderInfo();
  }

  loadTransporteur();

  function renderTable(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Aucun transporteur</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map((r) => `
      <tr data-id="${r.id_transporteur}">
        <td><span class="text-primary fw-bold">${escapeHtml(r.created_at || '')}</span></td>
        <td>
          ${
            r.image_trans
              ? `<a href="../${r.image_trans}" target="_blank">
                  <img src="../${r.image_trans}" style="max-height:50px;max-width:50px;border-radius:6px;">
                 </a>`
              : `<img src="../uploads/image/no-profile.png" style="max-height:50px;max-width:50px;border-radius:6px;">`
          }
        </td>
        <td class="fw-semibold">${escapeHtml((r.nom_trans || '') + ' ' + (r.prenom_trans || ''))}</td>
        <td>${escapeHtml(r.telephone_trans || '')}</td>
        <td>${escapeHtml(r.Adresse_Source || '')}</td>
        <td class="text-end">
          <div class="btn-group">
            
            <button class="btn btn-sm btn-outline-primary btn-print-card me-2" title="Imprimer la carte" data-id="${r.id_transporteur}">
              <i class="bi bi-printer-fill"></i>
            </button>
            <button class="btn btn-sm btn-outline-success btn-edit-transporteur" title="Modifier" data-id="${r.id_transporteur}">
              <i class="bi bi-pencil-square"></i>
            </button>
          </div>
        </td>
      </tr>
    `).join("");
  }

  function renderInfo() {
    const start = total === 0 ? 0 : (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, total);
    infoSpan.textContent = `Affiche ${start}–${end} sur ${total}`;
    btnPrev.disabled = page <= 1;
    btnNext.disabled = page * perPage >= total;
  }

  // === Pagination & recherche ===
  btnPrev?.addEventListener("click", () => {
    if (page > 1) { page--; loadTransporteur(); }
  });
  btnNext?.addEventListener("click", () => {
    if (page * perPage < total) { page++; loadTransporteur(); }
  });
  perPageSel?.addEventListener("change", () => {
    perPage = parseInt(perPageSel.value, 10);
    page = 1;
    loadTransporteur();
  });
  btnRefresh?.addEventListener("click", () => loadTransporteur());
  searchBox?.addEventListener("input", debounce(() => {
    q = searchBox.value;
    page = 1;
    loadTransporteur();
  }, 300));

  // === Actions du tableau ===
  tbody?.addEventListener("click", async (e) => {
    const id = e.target.closest("tr")?.dataset.id;
    if (!id) return;

   

    // Imprimer carte
    if (e.target.closest(".btn-print-card")) {
      const url = 'transporteur_carte.php?id=' + encodeURIComponent(id);
      const w = window.open(url, '_blank');
      if (!w) alert("Autorise les popups pour voir la carte.");
      return;
    }

    // Modifier transporteur
    if (e.target.closest(".btn-edit-transporteur")) {
      const tr = e.target.closest("tr");
      const tds = tr.querySelectorAll("td");
      const nomComplet = tds[2]?.textContent.trim().split(" ");
      const tel = tds[3]?.textContent.trim();
      const region = tds[4]?.textContent.trim();
      const imageSrc = tr.querySelector("img")?.src;

      document.getElementById("edit_id_transporteur").value = id;
      document.getElementById("edit_nom_transporteur").value = nomComplet?.[0] || "";
      document.getElementById("edit_prenom_transporteur").value = nomComplet?.[1] || "";
      document.getElementById("edit_tel_transporteur").value = tel || "";
      document.getElementById("edit_adresse_source").value = region || "";
      document.getElementById("edit_preview_image").src = imageSrc || "../uploads/image/no-profile.png";

      editError.classList.add("d-none");
      editForm.classList.remove("was-validated");
      editModal?.show();
      return;
    }
  });

  // === Enregistrer les modifications ===
  saveBtn?.addEventListener("click", async () => {
    editForm.classList.add("was-validated");
    if (!editForm.checkValidity()) return;

    const formData = new FormData(editForm);
    const res = await fetch("../includes/ajax/Transporteur/updateTransporteur.php", {
      method: "POST",
      body: formData,
    });

    if (!res.ok) {
      editError.textContent = "Erreur serveur.";
      editError.classList.remove("d-none");
      return;
    }

    const data = await res.json();
    if (data?.status === "success") {
      editModal?.hide();
      loadTransporteur();
    } else {
      editError.textContent = data?.message || "Erreur lors de la mise à jour.";
      editError.classList.remove("d-none");
    }
  });

  // === Utils ===
  function debounce(fn, wait) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function escapeHtml(s) {
    return String(s || '')
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // Reload on tab show
  document.getElementById("patron-tab")?.addEventListener("shown.bs.tab", () => {
    page = 1;
    loadTransporteur();
  });
});
            </script>


        </div>
    </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>