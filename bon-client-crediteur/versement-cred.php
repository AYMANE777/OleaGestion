<?php
$activeSection = 'ClientCrediteurOlive';
$activePage = 'ClientCrediteurOlive-versement';
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
                            <button class="nav-link active" id="versement-tab" data-bs-toggle="tab"
                                data-bs-target="#tab-versement" type="button" role="tab" aria-controls="tab-versement"
                                aria-selected="true">
                                Ajouter un versement
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#tab-list"
                                type="button" role="tab" aria-controls="tab-list" aria-selected="false">
                                Liste des versements
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content mt-3" id="formTabsContent">

                <!-- ==================== FORMULAIRE D’AJOUT VERSEMENT ==================== -->
                <div class="tab-pane fade show active" id="tab-versement" role="tabpanel"
                    aria-labelledby="versement-tab">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Ajouter un versement client olive</div>
                        </div>

                        <form class="needs-validation" id="addVersementClientOlive"
                            action="../includes/ajax/ClientOlive/addVersementClientOlive.php" method="post"
                            enctype="multipart/form-data" novalidate>
                            <div class="card-body">
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Client</label>
                                        <select class="form-select js-choices" name="id_client_olive" required>
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
                                        <label class="form-label">Montant (DH)</label>
                                        <input type="number" step="0.01" class="form-control" name="montant" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Mode de paiement</label>
                                        <select class="form-select" name="mode_paiement" required>
                                            <option value="">-- Choisir --</option>
                                            <option value="virement">Virement</option>
                                            <option value="espece">Espèce</option>
                                            <option value="cheque">Chèque</option>
                                            <option value="Sortie">Sortie</option>
                                            <option value="LCN">LCN</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Preuve (image du versement)</label>
                                        <input type="file" class="form-control" name="image_versement"
                                            accept=".jpg,.jpeg,.png">
                                    </div>

                                    <div class="col-md-12">
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
                <!-- ==================== FIN FORMULAIRE AJOUT ==================== -->

                <!-- ==================== LISTE DES VERSEMENTS ==================== -->
                <div class="tab-pane fade" id="tab-list" role="tabpanel" aria-labelledby="list-tab">
                    <div class="card card-info card-outline mb-4">
                        <div class="container-fluid py-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-2 mb-3">
                                <h4 class="m-0">Liste des versements client olive</h4>
                                <div class="d-flex gap-2">
                                    <input class="form-control" style="min-width:260px" id="searchVersement"
                                        placeholder="Rechercher...">
                                    <select class="form-select" id="perPageVersement" style="width:140px">
                                        <option value="10">10 / page</option>
                                        <option value="25" selected>25 / page</option>
                                        <option value="50">50 / page</option>
                                        <option value="100">100 / page</option>
                                    </select>
                                    <button class="btn btn-outline-secondary" id="btnRefreshVersement"
                                        title="Actualiser">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle table-hover" id="versementTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Montant (DH)</th>
                                            <th>Mode</th>
                                            <th>Preuve</th>
                                            <th>Note</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="versementBody"></tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div><span id="versementInfo" class="text-muted"></span></div>
                                <div class="btn-group">
                                    <button class="btn btn-outline-secondary" id="prevVersement">← Précédent</button>
                                    <button class="btn btn-outline-secondary" id="nextVersement">Suivant →</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ==================== FIN LISTE ==================== -->

            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const tbody = document.querySelector("#versementBody");
        const infoSpan = document.querySelector("#versementInfo");
        const searchBox = document.querySelector("#searchVersement");
        const perPageSel = document.querySelector("#perPageVersement");
        const btnPrev = document.querySelector("#prevVersement");
        const btnNext = document.querySelector("#nextVersement");
        const btnRefresh = document.querySelector("#btnRefreshVersement");

        let page = 1;
        let perPage = parseInt(perPageSel?.value || "25", 10);
        let total = 0;
        let q = "";

        async function loadVersements() {
            const params = new URLSearchParams({ page, perPage, q });
            const res = await fetch(`../includes/ajax/ClientOlive/versement_list.php?` + params.toString(), {
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

        loadVersements();

        function renderTable(rows) {
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Aucun versement</td></tr>`;
                return;
            }

            tbody.innerHTML = rows.map((r) => `
      <tr>
        <td>${escapeHtml(r.date_versement || '')}</td>
        <td>${escapeHtml(r.client_nom || '')}</td>
        <td class="fw-bold text-success">${escapeHtml(r.montant || '')}</td>
        <td>${escapeHtml(r.mode_paiement || '')}</td>
        <td>
          ${r.image_versement ?
                    `<a href="../${r.image_versement}" target="_blank"><img src="../${r.image_versement}" style="max-height:45px;border-radius:6px;"></a>` :
                    `<span class="text-muted">—</span>`}
        </td>
        <td>${escapeHtml(r.note || '')}</td>
        <td class="text-end">
  <button class="btn btn-sm btn-outline-dark btn-print" data-id="${r.id_versement}">
    <i class="bi bi-printer"></i>
  </button>
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

        btnPrev?.addEventListener("click", () => { if (page > 1) { page--; loadVersements(); } });
        btnNext?.addEventListener("click", () => { if (page * perPage < total) { page++; loadVersements(); } });
        perPageSel?.addEventListener("change", () => { perPage = parseInt(perPageSel.value, 10); page = 1; loadVersements(); });
        btnRefresh?.addEventListener("click", () => loadVersements());
        searchBox?.addEventListener("input", debounce(() => { q = searchBox.value; page = 1; loadVersements(); }, 300));

        function debounce(fn, wait) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); }; }
        function escapeHtml(s) { return String(s || '').replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;"); }
    });
    // === Impression du bon de versement ===
document.addEventListener("click", (e) => {
  const btn = e.target.closest(".btn-print");
  if (!btn) return;

  const id = btn.dataset.id;
  if (!id) return;

  window.open(`/gestionMeknassa/bon-client-crediteur/print_versement_clientolive.php?id_versement=${encodeURIComponent(id)}`, "_blank");
});
</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>