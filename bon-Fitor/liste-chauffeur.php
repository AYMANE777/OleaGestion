<?php

$activeSection = 'bon-Fitor';
$activePage = 'Liste des chauffeurs';
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
                  <button class="nav-link active" id="chauffeur-tab" data-bs-toggle="tab"
                    data-bs-target="#tab-chauffeur" type="button" role="tab" aria-controls="tab-chauffeur"
                    aria-selected="true">
                    Liste des Chauffeurs
                  </button>
                </li>

                <!-- Option A : tu l’utilises -->
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="piece-tab" data-bs-toggle="tab" data-bs-target="#tab-piece" type="button"
                    role="tab" aria-controls="tab-piece" aria-selected="false">
                    Pièces / Documents
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <div class="tab-content mt-3" id="formTabsContent">


            <!-- === Debut du Liste des Chauffeurs === -->
            <div class="tab-pane fade show active" id="tab-chauffeur" role="tabpanel" aria-labelledby="chauffeur-tab">
              <div class="card card-info card-outline mb-4">
                <div class="card-header">
                  <div class="card-title">Gestion des Chauffeurs</div>
                </div>

                <div class="container-fluid py-4">
                  <div class="row">
                    <div class="col-12">
                      <div class="card shadow-sm">
                        <div class="card-body">

                          <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                            <h4 class="m-0">Liste des Chauffeurs</h4>
                            <div class="d-flex gap-2">
                              <input class="form-control" style="min-width:260px" id="searchChauffeur"
                                placeholder="Recherche (nom, prénom, téléphone)...">
                              <select class="form-select" id="perPageChauffeur" style="width:140px">
                                <option value="10">10 / page</option>
                                <option value="25" selected>25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                              </select>
                              <button class="btn btn-outline-secondary" id="btnRefreshChauffeur" title="Actualiser">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                              </button>
                            </div>
                          </div>

                          <div class="table-responsive">
                            <table class="table align-middle table-hover" id="chauffeursTable">
                              <thead class="table-light">
                                <tr>
                                  <th>Profile</th>
                                  <th>Nom</th>
                                  <th>Prénom</th>
                                  <th>Téléphone</th>
                                  <th>Date Création</th>
                                  <th class="text-end">Actions</th>
                                </tr>
                              </thead>
                              <tbody id="chauffeursBody"></tbody>
                            </table>
                          </div>

                          <div class="d-flex justify-content-between align-items-center mt-3">
                            <div><span id="chauffeurInfo" class="text-muted"></span></div>
                            <div class="btn-group">
                              <button class="btn btn-outline-secondary" id="prevChauffeur">← Précédent</button>
                              <button class="btn btn-outline-secondary" id="nextChauffeur">Suivant →</button>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Modale d'édition Chauffeur -->
                  <div class="modal fade" id="editChauffeurModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Modifier Chauffeur</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <form id="editChauffeurForm" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="id_Fchauffeur" id="edit_id_Fchauffeur">

                            <div class="row g-3">
                              <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="nom" id="editc_nom" required>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="prenom" id="editc_prenom" required>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="telephone" id="editc_tel">
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" name="image_Fchauffeur" id="editc_image"
                                  accept=".jpg,.jpeg,.png">
                                <img id="previewImgChf" src="" alt="" class="img-thumbnail mt-2"
                                  style="max-height:120px">
                              </div>
                            </div>
                          </form>
                          <div id="editChauffeurError" class="text-danger mt-2 d-none"></div>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                          <button type="button" class="btn btn-primary" id="saveChauffeurBtn">Enregistrer</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Fin Modale d'édition Chauffeur -->

                </div>
              </div>
            </div>
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
          // === Elements
          const cTbody = document.querySelector("#chauffeursBody");
          const cInfo = document.querySelector("#chauffeurInfo");
          const cSearch = document.querySelector("#searchChauffeur");
          const cPerPage = document.querySelector("#perPageChauffeur");
          const cPrev = document.querySelector("#prevChauffeur");
          const cNext = document.querySelector("#nextChauffeur");
          const cRefresh = document.querySelector("#btnRefreshChauffeur");

          // Modal + form
          const cModalEl = document.getElementById("editChauffeurModal");
          const cModal = cModalEl ? new bootstrap.Modal(cModalEl) : null;
          const cForm = document.getElementById("editChauffeurForm");
          const cSaveBtn = document.getElementById("saveChauffeurBtn");
          const cErr = document.getElementById("editChauffeurError");
          const cPrevImg = document.getElementById("previewImgChf");

          // === Pagination state
          let cPage = 1;
          let cPer = parseInt(cPerPage?.value || "25", 10);
          let cTotal = 0;
          let cq = "";

          async function loadChauffeurs() {
            const params = new URLSearchParams({
              page: String(cPage),
              perPage: String(cPer),
              q: cq.trim()
            });
            const res = await fetch(`../includes/ajax/Fitor/Chauffeur/chauffeur_list.php?` + params.toString(), {
              headers: { "Accept": "application/json" }
            });
            if (!res.ok) {
              cTbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Erreur de chargement</td></tr>`;
              cInfo.textContent = "";
              return;
            }
            const data = await res.json();
            renderCTable(data.rows || []);
            cTotal = data.total || 0;
            renderCInfo();
          }

          function renderCTable(rows) {
            if (!rows.length) {
              cTbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Aucun chauffeur</td></tr>`;
              return;
            }
            cTbody.innerHTML = rows.map(r => `
      <tr data-id="${r.id_Fchauffeur}">
        <td>${r.image_Fchauffeur
                ? `<img src="../${r.image_Fchauffeur}" style="max-height:50px;max-width:50px;">`
                : '<img src="../uploads/image/no-profile.png" style="max-height:50px;max-width:50px;">'}</td>
        <td>${escapeHtml(r.nom)}</td>
        <td>${escapeHtml(r.prenom)}</td>
        <td>${r.telephone ?? ''}</td>
        <td>${r.created_at ?? ''}</td>
        <td class="text-end"><button class="btn btn-sm btn-outline-primary btn-edit-chf">Modifier</button></td>
      </tr>
    `).join("");
          }

          function renderCInfo() {
            const start = cTotal === 0 ? 0 : (cPage - 1) * cPer + 1;
            const end = Math.min(cPage * cPer, cTotal);
            cInfo.textContent = `Affiche ${start}–${end} sur ${cTotal}`;
            cPrev.disabled = cPage <= 1;
            cNext.disabled = cPage * cPer >= cTotal;
          }

          // Events
          cPrev?.addEventListener("click", () => { if (cPage > 1) { cPage--; loadChauffeurs(); } });
          cNext?.addEventListener("click", () => { if (cPage * cPer < cTotal) { cPage++; loadChauffeurs(); } });
          cPerPage?.addEventListener("change", () => { cPer = parseInt(cPerPage.value, 10); cPage = 1; loadChauffeurs(); });
          cRefresh?.addEventListener("click", () => loadChauffeurs());
          cSearch?.addEventListener("input", debounce(() => { cq = cSearch.value; cPage = 1; loadChauffeurs(); }, 300));

          // Edit open
          cTbody?.addEventListener("click", (e) => {
            const btn = e.target.closest(".btn-edit-chf");
            if (!btn) return;
            const tr = btn.closest("tr");
            const id = tr?.dataset?.id;
            if (!id) return;

            document.getElementById("edit_id_Fchauffeur").value = id;
            document.getElementById("editc_nom").value = tr.children[1].textContent.trim();
            document.getElementById("editc_prenom").value = tr.children[2].textContent.trim();
            document.getElementById("editc_tel").value = tr.children[3].textContent.trim();

            const imgEl = tr.querySelector("img");
            cPrevImg.src = imgEl ? imgEl.src : "";

            cErr.classList.add("d-none");
            cForm.classList.remove("was-validated");
            cModal?.show();
          });

          // Save edit
          cSaveBtn?.addEventListener("click", async () => {
            cForm.classList.add("was-validated");
            if (!cForm.checkValidity()) return;

            const formData = new FormData(cForm);
            const res = await fetch(`../includes/ajax/Fitor/Chauffeur/chauffeur_update.php`, {
              method: "POST",
              body: formData
            });

            if (!res.ok) {
              cErr.textContent = "Erreur réseau.";
              cErr.classList.remove("d-none");
              return;
            }
            const data = await res.json();
            if (data?.success) {
              cModal?.hide();
              loadChauffeurs();
            } else {
              cErr.textContent = data?.message || "Erreur lors de la mise à jour.";
              cErr.classList.remove("d-none");
            }
          });

          // Utils
          function debounce(fn, wait) {
            let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
          }
          function escapeHtml(s) {
            return String(s).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;")
              .replaceAll('"', "&quot;").replaceAll("'", "&#039;");
          }

          // Auto load if tab is active at start
          if (document.querySelector("#tab-chauffeur.show.active")) loadChauffeurs();
          loadChauffeurs();
          // Load when tab becomes active later
          document.getElementById("chauffeur-tab")?.addEventListener("shown.bs.tab", () => {
            cPage = 1;
            loadChauffeurs();
          });
        });
      </script>




    </div>
  </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>