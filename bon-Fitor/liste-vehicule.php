<?php
$activeSection = 'bon-Fitor';
$activePage = 'Liste des vehicules';
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';

// Charger la liste des assurance du maroc (JSON local ou API)
$assurance = json_decode(file_get_contents(__DIR__ . "/../assets/assurance.json"), true);
$vehicule = json_decode(file_get_contents(__DIR__ . "/../assets/vehicule.json"), true);
$couleur = json_decode(file_get_contents(__DIR__ . "/../assets/couleur.json"), true);


?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="card card-outline card-info mb-0">
        <div class="card-header p-0 border-bottom-0">
          <ul class="nav nav-tabs" id="formTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="vehicule-tab" data-bs-toggle="tab" data-bs-target="#tab-vehicule"
                type="button" role="tab" aria-controls="tab-vehicule" aria-selected="true">
                Liste des Véhicules
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="tab-content mt-3" id="formTabsContent">
        <!-- === Liste des Véhicules === -->
        <div class="tab-pane fade show active" id="tab-vehicule" role="tabpanel" aria-labelledby="vehicule-tab">
          <div class="card card-info card-outline mb-4">
            <div class="card-header">
              <div class="card-title">Gestion des Véhicules</div>
            </div>

            <div class="container-fluid py-4">
              <div class="row">
                <div class="col-12">
                  <div class="card shadow-sm">
                    <div class="card-body">

                      <div
                        class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                        <h4 class="m-0">Liste des Véhicules</h4>
                        <div class="d-flex gap-2">
                          <input class="form-control" style="min-width:260px" id="searchVehicule"
                            placeholder="Recherche (matricule, marque, modèle)...">
                          <select class="form-select" id="perPageVehicule" style="width:140px">
                            <option value="10">10 / page</option>
                            <option value="25" selected>25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                          </select>
                          <button class="btn btn-outline-secondary" id="btnRefreshVehicule" title="Actualiser">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                          </button>
                        </div>
                      </div>

                      <!-- ... header identique ... -->
                      <div class="table-responsive">
                        <table class="table align-middle table-hover" id="vehiculesTable">
                          <thead class="table-light">
                            <tr>
                              <th>Photo</th>
                              <th>Matricule</th>
                              <th>Nom</th>
                              <th>Type</th>
                              <th>Couleur</th>
                              <th>Assurance</th>
                              <th>Expiration</th>
                              <th>Créé le</th>
                              <th class="text-end">Actions</th>
                            </tr>
                          </thead>
                          <tbody id="vehiculesBody"></tbody>
                        </table>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                          <div><span id="vehiculeInfo" class="text-muted"></span></div>
                          <div class="btn-group">
                            <button class="btn btn-outline-secondary" id="prevVehicule">← Précédent</button>
                            <button class="btn btn-outline-secondary" id="nextVehicule">Suivant →</button>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>

                <!-- Modal d'édition Véhicule -->
                <div class="modal fade" id="editVehiculeModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg ">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Modifier Véhicule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <form id="editVehiculeForm" enctype="multipart/form-data" novalidate class="needs-validation">
                          <input type="hidden" name="id_vehicule" id="edit_id_vehicule">
                          <div class="row g-3">
                            <div class="col-md-6">
                              <label class="form-label">Matricule</label>
                              <input type="text" class="form-control" name="matricule" id="edit_matricule" required>
                              <div class="invalid-feedback">Matricule obligatoire.</div>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Nom</label>
                              <input type="text" class="form-control" name="nom_vehicule" id="edit_nom_vehicule"
                                required>
                              <div class="invalid-feedback">Nom obligatoire.</div>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Type</label>
                              <input type="text" class="form-control" name="type" id="edit_type">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Couleur</label>
                               <select class="form-select js-choices" name="couleur" id="edit_couleur"
                                                    required>
                                                    <?php foreach ($couleur as $v): ?>
                                                        <option value="<?= htmlspecialchars($v) ?>">
                                                            <?= htmlspecialchars($v) ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                </select>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Assurance</label>
                             
                               <select class="form-select js-choices" id="edit_assurance"
                                                    name="assurance">
                                                    <option value="null">-- Sélectionner un chauffeur --</option>
                                                    <?php foreach ($assurance as $v): ?>
                                                        <option value="<?= htmlspecialchars($v) ?>">
                                                            <?= htmlspecialchars($v) ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                </select>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Date d'expiration</label>
                              <input type="date" class="form-control" name="date_expiration" id="edit_date_expiration">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Photo</label>
                              <input type="file" class="form-control" name="image_vehicule" id="edit_image_vehicule"
                                accept=".jpg,.jpeg,.png">
                              <img id="previewImgVeh" src="" alt="" class="img-thumbnail mt-2" style="max-height:120px">
                            </div>
                          </div>
                        </form>
                        <div id="editVehiculeError" class="text-danger mt-2 d-none"></div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" id="saveVehiculeBtn">Enregistrer</button>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Fin Modal -->

              </div>
            </div>
          </div>
          <!-- === Fin Liste des Véhicules === -->
        </div>
      </div>
    </div>
</main>

<script>
  (() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
    });

    // Sauvegarde/restauration de l’onglet actif
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
    const vTbody = document.querySelector("#vehiculesBody");
    const vInfo = document.querySelector("#vehiculeInfo");
    const vSearch = document.querySelector("#searchVehicule");
    const vPerPage = document.querySelector("#perPageVehicule");
    const vPrev = document.querySelector("#prevVehicule");
    const vNext = document.querySelector("#nextVehicule");
    const vRefresh = document.querySelector("#btnRefreshVehicule");

    const vModalEl = document.getElementById("editVehiculeModal");
    const vModal = vModalEl ? new bootstrap.Modal(vModalEl) : null;
    const vForm = document.getElementById("editVehiculeForm");
    const vSaveBtn = document.getElementById("saveVehiculeBtn");
    const vErr = document.getElementById("editVehiculeError");
    const vPrevImg = document.getElementById("previewImgVeh");

    let vPage = 1;
    let vPer = parseInt(vPerPage?.value || "25", 10);
    let vTotal = 0;
    let vq = "";



     couleurChoices = new Choices("#edit_couleur", {
    shouldSort: false,
    searchPlaceholderValue: "Rechercher par nom...",
    allowHTML: false
  });

  assuranceChoices = new Choices("#edit_assurance", {
    shouldSort: false,
    searchPlaceholderValue: "Rechercher assurance...",
    allowHTML: false
  });

  function setChoicesValue(instance, value) {
  if (value == null) { instance.clearStore(); return; }
  value = String(value).trim();
  const selectEl = instance.passedElement.element;
  const exists = Array.from(selectEl.options).some(o => o.value === value);
  if (!exists && value !== "") {
    // ajoute la valeur sans écraser les options existantes
    instance.setChoices([{ value, label: value }], 'value', 'label', false);
  }
  instance.setChoiceByValue(value);
}


    async function loadVehicules() {
      const params = new URLSearchParams({
        page: String(vPage),
        perPage: String(vPer),
        q: vq.trim()
      });
      const res = await fetch(`../includes/ajax/Fitor/Vehicule/vehicule_list.php?` + params.toString(), {
        headers: { "Accept": "application/json" }
      });
      if (!res.ok) {
        vTbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">Erreur de chargement</td></tr>`;
        vInfo.textContent = "";
        return;
      }
      const data = await res.json();
      renderVTable(data.rows || []);
      vTotal = data.total ?? 0;
      renderVInfo();
    }

    function renderVTable(rows) {
      if (!rows.length) {
        vTbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted">Aucun véhicule</td></tr>`;
        return;
      }
      vTbody.innerHTML = rows.map(r => {
        const img = r.image_vehicule
          ? `../${escapeAttr(r.image_vehicule)}`
          : `../uploads/image/no-vehicle.png`;
        return `
        <tr data-id="${escapeAttr(r.id_vehicule)}">
          <td><img src="${img}" style="max-height:50px;max-width:50px;"></td>
          <td>${escapeHtml(r.matricule ?? '')}</td>
          <td>${escapeHtml(r.nom_vehicule ?? '')}</td>
          <td>${escapeHtml(r.type ?? '')}</td>
          <td>${escapeHtml(r.couleur ?? '')}</td>
          <td>${escapeHtml(r.assurance ?? '')}</td>
          <td>${escapeHtml(r.date_expiration ?? '')}</td>
          <td>${escapeHtml(r.created_at ?? '')}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary btn-edit-veh">Modifier</button>
          </td>
        </tr>`;
      }).join("");
    }

    function renderVInfo() {
      const start = vTotal === 0 ? 0 : (vPage - 1) * vPer + 1;
      const end = Math.min(vPage * vPer, vTotal);
      vInfo.textContent = `Affiche ${start}–${end} sur ${vTotal}`;
      vPrev.disabled = vPage <= 1;
      vNext.disabled = vPage * vPer >= vTotal;
    }

    vPrev?.addEventListener("click", () => { if (vPage > 1) { vPage--; loadVehicules(); } });
    vNext?.addEventListener("click", () => { if (vPage * vPer < vTotal) { vPage++; loadVehicules(); } });
    vPerPage?.addEventListener("change", () => { vPer = parseInt(vPerPage.value, 10); vPage = 1; loadVehicules(); });
    vRefresh?.addEventListener("click", () => loadVehicules());
    vSearch?.addEventListener("input", debounce(() => { vq = vSearch.value; vPage = 1; loadVehicules(); }, 300));

    // Ouvrir le modal + pré-remplir
    vTbody?.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-edit-veh");
      if (!btn) return;
      const tr = btn.closest("tr");
      const id = tr?.dataset?.id;
      if (!id) return;

      document.getElementById("edit_id_vehicule").value = id;
      document.getElementById("edit_matricule").value = tr.children[1].textContent.trim();
      document.getElementById("edit_nom_vehicule").value = tr.children[2].textContent.trim();
      document.getElementById("edit_type").value = tr.children[3].textContent.trim();
      document.getElementById("edit_couleur").value = tr.children[4].textContent.trim();
      document.getElementById("edit_assurance").value = tr.children[5].textContent.trim();
      document.getElementById("edit_date_expiration").value = normalizeDateForInput(tr.children[6].textContent.trim());


// Récupère les valeurs affichées dans le tableau
const couleurVal   = tr.children[4].textContent.trim();
const assuranceVal = tr.children[5].textContent.trim();
const dateExp      = tr.children[6].textContent.trim();

// Applique dans Choices.js (pré-sélection)
setChoicesValue(couleurChoices,   couleurVal);
setChoicesValue(assuranceChoices, assuranceVal);


      const imgEl = tr.querySelector("img");
      vPrevImg.src = imgEl ? imgEl.src : "";

      vErr.classList.add("d-none");
      vForm.classList.remove("was-validated");
      vModal?.show();
    });

    // Sauvegarde
    document.getElementById("saveVehiculeBtn")?.addEventListener("click", async () => {
      vForm.classList.add("was-validated");
      if (!vForm.checkValidity()) return;

      const formData = new FormData(vForm);
      const res = await fetch(`../includes/ajax/Fitor/Vehicule/vehicule_update.php`, {
        method: "POST",
        body: formData
      });


      if (!res.ok) {
        vErr.textContent = "Erreur réseau.";
        vErr.classList.remove("d-none");
        return;
      }
      const data = await res.json();
      if (data?.success) {
        vModal?.hide();
        loadVehicules();
      } else {
        vErr.textContent = data?.message || "Erreur lors de la mise à jour.";
        vErr.classList.remove("d-none");
      }
    });

    function debounce(fn, wait) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), wait); }; }
    function escapeHtml(s) { return String(s).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;"); }
    function escapeAttr(s) { return escapeHtml(s); }

    // Convertit "DD/MM/YYYY" en "YYYY-MM-DD" si besoin (pour input[type=date])
    function normalizeDateForInput(s) {
      if (!s) return "";
      if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
      const m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (m) return `${m[3]}-${m[2]}-${m[1]}`;
      return s; // on laisse tel quel
    }

    // Premier chargement
    loadVehicules();
  });



</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>