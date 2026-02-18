<style>
   /* top toolbars */
  .sticky-tools, .status-toolbar {
    position: sticky;
    top: 0;
    z-index: 3;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
  }

  .status-toolbar .ctrl { min-width: 180px; }

  /* summary panel */
  .summary-box .val { font-weight: 700; }

  /* make the right column sticky */
  .sticky-col { position: sticky; top: 1rem; }

  /* buttons look crisper on small screens */
  @media (max-width: 576px) {
    .status-toolbar .ctrl { min-width: 100%; }
  }

  /* print: only print the printable area */
  @media print {
    body * { visibility: hidden !important; }
    #statusPrintable, #statusPrintable * { visibility: visible !important; }
    #statusPrintable { position: absolute; left: 0; top: 0; width: 100%; }
  }
</style>



<?php

$activeSection = 'bon-Fitor';
$activePage = 'Liste des patrons';
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
                  <button class="nav-link active" id="patron-tab" data-bs-toggle="tab" data-bs-target="#tab-patron"
                    type="button" role="tab" aria-controls="tab-patron" aria-selected="true">
                    Liste des Patrons
                  </button>
                </li>

                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="piece-tab" data-bs-toggle="tab" data-bs-target="#tab-vehicule"
                    type="button" role="tab" aria-controls="tab-vehicule" aria-selected="false">
                    Statut Patron
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <div class="tab-content mt-3" id="formTabsContent">

            <!-- debut du Liste des Patrons -->
            <div class="tab-pane fade show active" id="tab-patron" role="tabpanel" aria-labelledby="patron-tab">
              <div class="card card-info card-outline mb-4">
                <div class="card-header">
                  <div class="card-title">Gestion des Patrons</div>
                </div>
                <div class="container-fluid py-4">
                  <div class="row">
                    <div class="col-12">
                      <div class="card shadow-sm">
                        <div class="card-body">

                          <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                            <h4 class="m-0">Liste des Patrons</h4>
                            <div class="d-flex gap-2">
                              <input class="form-control" style="min-width:260px" id="searchPatron"
                                placeholder="Recherche (nom, prénom, téléphone)...">
                              <select class="form-select" id="perPagePatron" style="width:140px">
                                <option value="10">10 / page</option>
                                <option value="25" selected>25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                              </select>
                              <button class="btn btn-outline-secondary" id="btnRefreshPatron" title="Actualiser">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                              </button>
                            </div>
                          </div>

                          <div class="table-responsive">
                            <table class="table align-middle table-hover" id="patronsTable">
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
                              <tbody id="patronsBody"></tbody>
                            </table>
                          </div>

                          <div class="d-flex justify-content-between align-items-center mt-3">
                            <div><span id="patronInfo" class="text-muted"></span></div>
                            <div class="btn-group">
                              <button class="btn btn-outline-secondary" id="prevPatron">← Précédent</button>
                              <button class="btn btn-outline-secondary" id="nextPatron">Suivant →</button>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Modale d'édition -->
                  <div class="modal fade" id="editPatronModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Modifier Patron</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <form id="editPatronForm" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="id_patron" id="edit_id_patron">

                            <div class="row g-3">
                              <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="nom_patron" id="edit_nom" required>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="prenom_patron" id="edit_prenom" required>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="telephone_patron" id="edit_tel">
                              </div>

                              <div class="col-md-6">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" name="image_Patron" id="edit_image"
                                  accept=".jpg,.jpeg,.png">
                                <img id="previewImg" src="" alt="" class="img-thumbnail mt-2" style="max-height:120px">
                              </div>
                            </div>
                          </form>
                          <div id="editError" class="text-danger mt-2 d-none"></div>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                          <button type="button" class="btn btn-primary" id="savePatronBtn">Enregistrer</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Fin Modale d'édition -->

                </div>
              </div>
            </div>

            <!-- Fin du Liste des Patrons -->


            <!-- Debut de Status du Patron -->
            <div class="tab-pane fade" id="tab-vehicule" role="tabpanel" aria-labelledby="piece-tab">
              <div class="card card-info card-outline mb-4">
                <div class="container py-4">
                  <div class="row g-4">
                    <div class="col-lg-8">
                      <!-- Toolbar: date filter + print -->
                          <div class="status-toolbar bg-white border rounded-3 shadow-sm p-2 mb-3">
                            <div class="d-flex flex-wrap align-items-end gap-2">
                              <div class="ctrl">
                                <label for="fltFrom" class="form-label mb-1 small text-muted">Du</label>
                                <input type="date" class="form-control" id="fltFrom" placeholder="jj/mm/aaaa">
                              </div>
                              <div class="ctrl">
                                <label for="fltTo" class="form-label mb-1 small text-muted">Au</label>
                                <input type="date" class="form-control" id="fltTo" placeholder="jj/mm/aaaa">
                              </div>
                              <div class="d-flex align-items-end gap-2">
                                <button class="btn btn-outline-secondary" id="btnApplyDate">
                                  <i class="bi bi-funnel me-1" aria-hidden="true"></i>
                                </button>
                                <button class="btn btn-outline-secondary" id="btnResetDate">
                                  <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                                </button>
                                <button class="btn btn-primary" id="btnPrint">
                                  <i class="bi bi-printer me-1" aria-hidden="true"></i>
                                </button>
                              </div>
                            </div>

                             
                          </div>
                      <div class="card shadow-sm">
                        <div class="card-body">

                          

                          <!-- Printable area starts -->
                          <div id="statusPrintable">
                            <h4 class="mb-3">Détail de Status</h4>

                            <div class="mt-3">
                              <h5 class="mb-2">Versements</h5>
                              <div id="tblVersements" class="table-responsive"></div>
                            </div>

                            <div class="mt-4">
                              <h5 class="mb-2">Bons Fitor</h5>
                              <div id="tblBons" class="table-responsive"></div>
                            </div>
                          </div>
                          <!-- Printable area ends -->

                        </div>
                      </div>
                    </div>

                    <div class="col-lg-4">
                      <div class="sticky-col">
                        <div class="card shadow-sm">
                          <div class="card-body">
                            <h5 class="mb-3">Solde du Patron (compte courant)</h5>
                            <div class="summary-box">
                              <div class="d-flex justify-content-between"><span>Total Versements</span><span class="val"
                                  id="sumVersements">0</span></div>
                              <div class="d-flex justify-content-between"><span>Total Bons</span><span class="val"
                                  id="sumBons">0</span></div>
                              <hr>
                              <div class="d-flex justify-content-between"><span>Solde (V - B)</span><span class="val"
                                  id="soldePatron">0</span></div>
                            </div>
                            <div class="form-text mt-2">Le solde devient <strong>négatif</strong> si les bons dépassent
                              les versements.</div>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>

              </div>
            </div>
            <!-- Fin de Status du Patron -->
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
  const tbody = document.querySelector("#patronsBody");
  const infoSpan = document.querySelector("#patronInfo");
  const searchBox = document.querySelector("#searchPatron");
  const perPageSel = document.querySelector("#perPagePatron");
  const btnPrev = document.querySelector("#prevPatron");
  const btnNext = document.querySelector("#nextPatron");
  const btnRefresh = document.querySelector("#btnRefreshPatron");

  // Status toolbar elements
  const fltFrom = document.getElementById("fltFrom");
  const fltTo = document.getElementById("fltTo");
  const btnApplyDate = document.getElementById("btnApplyDate");
  const btnResetDate = document.getElementById("btnResetDate");
  const btnPrint = document.getElementById("btnPrint");

  // Totals labels
  const lblSumV = document.getElementById("sumVersements");
  const lblSumB = document.getElementById("sumBons");
  const lblSolde = document.getElementById("soldePatron");

  // Modal + form
  const editModalEl = document.getElementById("editPatronModal");
  const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
  const editForm = document.getElementById("editPatronForm");
  const saveBtn = document.getElementById("savePatronBtn");
  const editError = document.getElementById("editError");

  // === Pagination state
  let page = 1;
  let perPage = parseInt(perPageSel?.value || "25", 10);
  let total = 0;
  let q = "";

  // State for current status view
  let currentPatronId = null;
  let rawVersements = [];
  let rawBons = [];

  const nf = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // === Fetch list
  async function loadPatrons() {
    const params = new URLSearchParams({
      page: String(page),
      perPage: String(perPage),
      q: q.trim()
    });
    try {
      const res = await fetch(`../includes/ajax/Fitor/Patron/patron_list.php?` + params.toString(), {
        headers: { "Accept": "application/json" }
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      renderTable(data.rows || []);
      total = data.total || 0;
      renderInfo();
    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Erreur de chargement</td></tr>`;
      infoSpan.textContent = "";
    }
  }

  function renderTable(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Aucun patron</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map((r) =>
      `
      <tr data-id="${r.id_patron}">
        <td>${r.image_Patron ? `<img src="../${r.image_Patron}" style="max-height:50px;max-width:50px;">` : '<img src="../uploads/image/no-profile.png" style="max-height:50px;max-width:50px;">'}</td>
        <td>${escapeHtml(r.nom_patron ?? '')}</td>
        <td>${escapeHtml(r.prenom_patron ?? '')}</td>
        <td>${escapeHtml(r.telephone_patron ?? '')}</td>
        <td>${escapeHtml(r.created_at ?? '')}</td>
        <td class="text-end">
          <div class="btn-group">
            <button class="btn btn-sm btn-outline-primary btn-edit-Patron">Modifier</button>
            <button class="btn btn-sm btn-outline-success btn-view-status">Voir statut</button>
          </div>
        </td>
      </tr>
      `).join("");
  }

  function renderVersementsTable(rows) {
    const host = document.querySelector("#tblVersements");
    if (!host) return;
    if (!rows.length) { host.innerHTML = `<div class="text-muted">Aucun versement</div>`; return; }

    host.innerHTML = `
      <table class="table table-sm align-middle" id="tableVersements">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Date</th><th>Montant</th><th>Déduit</th><th>Reste crédit</th><th>Mode</th><th>Note</th><th>Preuve</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((r) => `
            <tr data-date="${escapeHtml(r.date_versement ?? '')}">
              <td>${r.id_versement}</td>
              <td>${escapeHtml(r.date_versement ?? '')}</td>
              <td data-val="${Number(r.montant) || 0}">${nf.format(Number(r.montant) || 0)}</td>
              <td data-val="${Number(r.montant_deduit) || 0}">${nf.format(Number(r.montant_deduit) || 0)}</td>
              <td data-val="${Number(r.reste_credit) || 0}">${nf.format(Number(r.reste_credit) || 0)}</td>
              <td>${escapeHtml(r.mode_paiement ?? '')}</td>
              <td>${escapeHtml(r.note ?? '')}</td>
              <td>${r.image_versement ? `<a href="../${r.image_versement}" target="_blank" rel="noopener">Voir</a>` : ''}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>`;
  }

  function renderBonsTable(rows) {
    const host = document.querySelector("#tblBons");
    if (!host) return;
    if (!rows.length) { host.innerHTML = `<div class="text-muted">Aucun bon</div>`; return; }

    host.innerHTML = `
      <table class="table table-sm align-middle" id="tableBons">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Date</th><th>Quantité</th><th>Prix init</th><th>Prix net</th><th>Déjà payé</th><th>Reste à payer</th><th>Provider</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((r) => `
            <tr data-date="${escapeHtml(r.created_at ?? '')}">
              <td>${r.id_bon}</td>
              <td>${escapeHtml(r.created_at ?? '')}</td>
              <td>${nf.format(Number(r.quantite) || 0)}</td>
              <td data-val="${Number(r.prix_init) || 0}">${nf.format(Number(r.prix_init) || 0)}</td>
              <td data-val="${Number(r.prix_net) || 0}">${nf.format(Number(r.prix_net) || 0)}</td>
              <td data-val="${Number(r.deja_paye) || 0}">${nf.format(Number(r.deja_paye) || 0)}</td>
              <td data-val="${Number(r.reste_a_payer) || 0}">${nf.format(Number(r.reste_a_payer) || 0)}</td>
              <td>${escapeHtml(r.provider ?? '')}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>`;
  }

  function renderInfo() {
    const start = total === 0 ? 0 : (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, total);
    infoSpan.textContent = `Affiche ${start}–${end} sur ${total}`;
    btnPrev.disabled = page <= 1;
    btnNext.disabled = page * perPage >= total;
  }

  // === Events: list
  btnPrev?.addEventListener("click", () => { if (page > 1) { page--; loadPatrons(); } });
  btnNext?.addEventListener("click", () => { if (page * perPage < total) { page++; loadPatrons(); } });
  perPageSel?.addEventListener("change", () => { perPage = parseInt(perPageSel.value, 10); page = 1; loadPatrons(); });
  btnRefresh?.addEventListener("click", () => loadPatrons());
  searchBox?.addEventListener("input", debounce(() => { q = searchBox.value; page = 1; loadPatrons(); }, 300));

  // View status
  tbody?.addEventListener("click", async (e) => {
    const viewBtn = e.target.closest(".btn-view-status");
    if (!viewBtn) return;

    const tr = viewBtn.closest("tr");
    const id = tr?.dataset?.id;
    if (!id) return;

    // switch to status tab
    const triggerEl = document.querySelector('[data-bs-target="#tab-vehicule"]');
    if (triggerEl) new bootstrap.Tab(triggerEl).show();

    // reset filters
    fltFrom.value = '';
    fltTo.value = '';

    currentPatronId = Number(id);
    await loadPatronStatus(currentPatronId);
    applyStatusFilters(); // ensure totals formatting
  });

  async function loadPatronStatus(id_patron) {
    try {
      const res = await fetch(`../includes/ajax/Fitor/Patron/patron_status_details.php?id_patron=${id_patron}`, {
        headers: { "Accept": "application/json" }
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      // Keep raw copies for client filtering
      rawVersements = data.versements || [];
      rawBons = data.bons || [];

      // Totals (all-time from backend shown first; will be overridden by filter if user applies it)
      lblSumV.textContent = nf.format(Number(data.totaux?.total_versements ?? 0));
      lblSumB.textContent = nf.format(Number(data.totaux?.total_bons ?? 0));
      lblSolde.textContent = nf.format(Number(data.totaux?.solde_global ?? 0));

      renderVersementsTable(rawVersements);
      renderBonsTable(rawBons);

      // Optionally select patron in any <select id="id_patron">
      const selPatron = document.querySelector("#id_patron");
      if (selPatron) {
        if (![...selPatron.options].some(o => o.value == id_patron)) {
          // list not yet loaded… ignore
        }
        selPatron.value = String(id_patron);
      }
    } catch (err) {
      console.error(err);
    }
  }

  /** Apply date filter (client-side) and recompute visible totals */
  function applyStatusFilters() {
    const fromStr = (fltFrom?.value || '').trim();
    const toStr = (fltTo?.value || '').trim();

    const from = fromStr ? new Date(fromStr + 'T00:00:00') : null;
    const to = toStr ? new Date(toStr + 'T23:59:59') : null;

    // Filter helpers
    const inRange = (isoOrDateStr) => {
      if (!isoOrDateStr) return false;
      // Support ISO 'YYYY-MM-DD' or 'YYYY-MM-DD HH:mm:ss'
      const d = new Date(isoOrDateStr.replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return false;
      if (from && d < from) return false;
      if (to && d > to) return false;
      return true;
    };

    // Filter data
    const vFiltered = (from || to) ? rawVersements.filter(v => inRange(v.date_versement)) : rawVersements.slice();
    const bFiltered = (from || to) ? rawBons.filter(b => inRange(b.created_at)) : rawBons.slice();

    renderVersementsTable(vFiltered);
    renderBonsTable(bFiltered);

    // Recompute totals off visible rows
    const sum = (arr, key) => arr.reduce((acc, r) => acc + (Number(r[key]) || 0), 0);

    const totalV = sum(vFiltered, 'montant');
    const totalB = sum(bFiltered, 'prix_net');
    const solde = totalV - totalB;

    lblSumV.textContent = nf.format(totalV);
    lblSumB.textContent = nf.format(totalB);
    lblSolde.textContent = nf.format(solde);
  }

  // Toolbar events
  btnApplyDate?.addEventListener('click', () => applyStatusFilters());
  btnResetDate?.addEventListener('click', () => {
    fltFrom.value = '';
    fltTo.value = '';
    applyStatusFilters();
  });

  // Print only the printable section
  btnPrint?.addEventListener('click', () => {
    if (!currentPatronId) {
    alert("Sélectionnez d'abord un patron (bouton 'Voir statut').");
    return;
  }
  const params = new URLSearchParams();
  params.set('id_patron', String(currentPatronId));
  if (fltFrom?.value) params.set('from', fltFrom.value); // YYYY-MM-DD
  if (fltTo?.value)   params.set('to', fltTo.value);     // YYYY-MM-DD

  // adjust the relative path to where your PHP file is actually located
  window.open(`../bon-Fitor/releve_de_compte_patron.php?${params.toString()}`, '_blank');
  });

  // Edit button (event delegation)
  tbody?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-edit-Patron");
    if (!btn) return;
    const tr = btn.closest("tr");
    const id = tr?.dataset?.id;
    if (!id) return;

    // Prefill from row
    document.getElementById("edit_id_patron").value = id;
    document.getElementById("edit_nom").value = tr.children[1].textContent.trim();
    document.getElementById("edit_prenom").value = tr.children[2].textContent.trim();
    document.getElementById("edit_tel").value = tr.children[3].textContent.trim();

    editError.classList.add("d-none");
    editForm.classList.remove("was-validated");
    editModal?.show();
  });

  // Save edit
  saveBtn?.addEventListener("click", async () => {
    editForm.classList.add("was-validated");
    if (!editForm.checkValidity()) return;

    const formData = new FormData(editForm);
    try {
      const res = await fetch(`../includes/ajax/Fitor/Patron/patron_update.php`, {
        method: "POST",
        body: formData
      });

      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      if (data?.success) {
        editModal?.hide();
        loadPatrons(); // refresh page
        // If we were viewing status of this patron, refresh it too
        if (currentPatronId) {
          await loadPatronStatus(currentPatronId);
          applyStatusFilters();
        }
      } else {
        editError.textContent = data?.message || "Erreur lors de la mise à jour.";
        editError.classList.remove("d-none");
      }
    } catch (e) {
      console.error(e);
      editError.textContent = "Erreur réseau.";
      editError.classList.remove("d-none");
    }
  });

  // Utils
  function debounce(fn, wait) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }
  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // Initial load only if the list tab is visible
  if (document.querySelector("#tab-patron.show.active")) {
    loadPatrons();
  }

  // Reload when the list tab becomes active
  document.getElementById("patron-tab")?.addEventListener("shown.bs.tab", () => {
    page = 1; loadPatrons();
  });
});
       </script>


    </div>
  </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>