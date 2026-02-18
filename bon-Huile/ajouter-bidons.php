<?php
$activeSection = 'bon-Huile';
$activePage = 'add Bidons';

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
                  <button class="nav-link active" id="machine-tab" data-bs-toggle="tab" data-bs-target="#tab-machine"
                    type="button" role="tab" aria-controls="tab-machine" aria-selected="true">
                    Ajouter Bidon
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="piece-tab" data-bs-toggle="tab" data-bs-target="#tab-piece" type="button"
                    role="tab" aria-controls="tab-piece" aria-selected="false">
                    Status des Bidons
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <div class="tab-content mt-3" id="formTabsContent">
            <div class="tab-pane fade show active" id="tab-machine" role="tabpanel" aria-labelledby="machine-tab">
              <div class="card card-info card-outline mb-4">
                <div class="card-header">
                  <div class="card-title">Ajouter un Bidon</div>
                </div>

                <form id="addBidonForm" class="needs-validation"
                  action="../includes/ajax/Huile/Bidon-Huile/addBidon.php" method="POST" enctype="multipart/form-data"
                  novalidate>
                  <div class="card-body">
                    <div class="row g-3">

                      <div class="col-md-6">
                        <label for="nom_bidon" class="form-label">Nom :</label>
                        <input type="text" class="form-control" id="nom_bidon" name="nom_bidon" required>
                      </div>

                      <div class="col-md-6">
                        <label for="prenom_client" class="form-label">Type :</label>
                        <select class="form-select js-choices" id="type_bidon" name="type_bidon" required>
                          <option value="">--Selectionner Type de Bidon--</option>
                          <option value="1L">1 L</option>
                          <option value="5L">5 L</option>
                          <option value="10L">10 L</option>
                          <option value="20L">20 L</option>
                          <option value="25L">25 L</option>
                          <option value="30L">30 L</option>
                          <option value="50L">50 L</option>
                        <option value="120L">120 L</option>
                          <option value="200L">200 L</option>
                           <option value="1T">1 T</option>
                            <option value="3T">3 T</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="image_client" class="form-label">Prix Bidon :</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="prix_bidon" name="prix_bidon" required>
                      </div>

                      <div class="col-md-6">
                        <label for="image_client" class="form-label">Quantité :</label>
                        <input type="number" min="0" class="form-control" id="qte_bidon" name="qte_bidon" required>
                      </div>

                    </div>
                  </div>
                  <div class="card-footer text-end">
                    <button class="btn btn-primary text-white" type="submit" name="addBidonBtn">Ajouter</button>
                  </div>
                </form>

              </div>
            </div>

            <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
              <div class="card card-info card-outline mb-4">
                <div class="card-header">
                  <div class="card-title">Bidon Status</div>
                </div>
                <div class="card-body">
                  <div
                    class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                    <h4 class="m-0">Liste des Bidons</h4>
                    <div class="d-flex gap-2">
                      <input class="form-control" style="min-width:260px" id="searchBidon"
                        placeholder="Recherche (nom, Type, quantite)...">
                      <select class="form-select" id="perPageBidon" style="width:140px">
                        <option value="10">10 / page</option>
                        <option value="25" selected>25 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                      </select>
                      <button class="btn btn-outline-secondary" id="btnRefreshBidon" title="Actualiser">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                      </button>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table align-middle table-hover" id="BidonsTable">
                      <thead class="table-light">
                        <tr>

                          <th>Nom</th>
                          <th>Type</th>
                          <th>Prix</th>
                          <th>Quantité</th>
                          <th>Reste</th>
                          <th>Vendu</th>
                          <th>Date Création</th>

                          <th class="text-end"></th>
                        </tr>
                      </thead>
                      <tbody id="BidonsBody"></tbody>
                    </table>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mt-3">
                    <div><span id="BidonInfo" class="text-muted"></span></div>
                    <div class="btn-group">
                      <button class="btn btn-outline-secondary" id="prevBidon">← Précédent</button>
                      <button class="btn btn-outline-secondary" id="nextBidon">Suivant →</button>
                    </div>
                  </div>

                </div>




              </div>
            </div>

          </div>

        </div>
      </div>




    </div>
  </div>
  </div>
  <!-- Modal: Éditer un bidon -->
  <div class="modal fade" id="editBidonModal" tabindex="-1" aria-labelledby="editBidonLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="editBidonForm" class="modal-content needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="editBidonLabel">Modifier le bidon</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id_bidon" name="id_bidon">
          <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" class="form-control" id="edit_nom_bidon" name="nom_bidon" required>
            <div class="invalid-feedback">Nom requis.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Type</label>
            <select class="form-select" id="edit_type_bidon" name="type_bidon" required>
              <option value="">--Sélectionner--</option>
              <option value="5L">5 L</option>
              <option value="10L">10 L</option>
              <option value="20L">20 L</option>
              <option value="25L">25 L</option>
              <option value="30L">30 L</option>
              <option value="50L">50 L</option>
            </select>
            <div class="invalid-feedback">Type requis.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Prix (DH)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="edit_prix_bidon" name="prix_bidon"
              required>
            <div class="invalid-feedback">Prix valide requis.</div>
          </div>
          <div class="alert alert-danger d-none" id="editBidonError"></div>
          <div class="alert alert-success d-none" id="editBidonOk">Modifié avec succès.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" id="saveBidonBtn">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Modal: Modifier le stock -->
  <div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="editStockForm" class="modal-content needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="editStockLabel">Modifier le stock du bidon</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="stock_id_bidon" name="id_bidon">
          <div class="mb-3">
            <label class="form-label">Nom du Bidon</label>
            <input type="text" class="form-control" id="stock_nom_bidon" readonly>
          </div>
          <div class="mb-3">
  <label class="form-label">Action</label>
  <select class="form-select" id="stock_action" name="type_action" required>
    <option value="ajout" selected>Ajouter</option>
    <option value="retrait">Retirer</option>
  </select>
</div>
<div class="mb-3">
  <label class="form-label">Quantité</label>
  <input type="number" class="form-control" id="stock_ajout" name="quantite_changee" min="0.01" step="0.01" required>
  <div class="invalid-feedback">Quantité requise.</div>
</div>
          <div class="mb-3">
            <label class="form-label">Commentaire</label>
            <textarea class="form-control" id="stock_commentaire" name="commentaire" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    let loadBidons; // declare at the top
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


    // === Modal pour modifier le stock ===
    const stockModalEl = document.getElementById("editStockModal");
    const stockModal = stockModalEl ? new bootstrap.Modal(stockModalEl) : null;
    const stockForm = document.getElementById("editStockForm");
   


    const f_stock_id = document.getElementById("stock_id_bidon");
    const f_stock_nom = document.getElementById("stock_nom_bidon");
    const f_stock_ajout = document.getElementById("stock_ajout");
    const f_stock_comment = document.getElementById("stock_commentaire");

    // Ouverture du modal quand on clique sur btn-edit-stock
    document.querySelector("#BidonsTable")?.addEventListener("click", (e) => {
      const tr = e.target.closest("tr[data-id]");
      const id = tr?.dataset?.id;
      if (!id) return;

      if (e.target.closest(".btn-edit-stock")) {
        f_stock_id.value = id;
        f_stock_nom.value = tr.dataset.nom || tr.children[0].textContent.trim();
        f_stock_ajout.value = "";
        f_stock_comment.value = "";
      

        stockForm.classList.remove("was-validated");
        stockModal?.show();
      }
    });

    // Soumission du formulaire
    stockForm?.addEventListener("submit", async (ev) => {
      ev.preventDefault();
      stockForm.classList.add("was-validated");
      if (!stockForm.checkValidity()) return;




      const formData = new FormData(stockForm);

      try {
        const res = await fetch("../includes/ajax/Huile/Bidon-Huile/updateStock.php", {
          method: "POST",
          body: formData,
        });
        if (!res.ok) throw new Error("HTTP " + res.status);
        const data = await res.json();

        if (data?.success) {
           Swal.fire({
            icon: "success",
            title: "Stock mis à jour",
            text: data.message || "Opération réussie",
            timer: 1800,
            showConfirmButton: false
          });
          await loadBidons(); // refresh table
          setTimeout(() => stockModal?.hide(), 500);
        } else {
          console.error("Erreur détectée:", data);
          Swal.fire({
            icon: "error",
            title: "Erreur",
            text: data?.message || "Erreur inconnue",
          });
         
        }
      } catch (err) {
         Swal.fire({
    icon: "error",
    title: "Erreur réseau",
    text: "Impossible de contacter le serveur. Vérifiez votre connexion.",
  });
      }
    });
    // =======================
    // Liste des Bidons (AJAX)
    // =======================
    document.addEventListener("DOMContentLoaded", () => {
      // ====== Modal edit
      const editModalEl = document.getElementById("editBidonModal");
      const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
      const editForm = document.getElementById("editBidonForm");
      const editErr = document.getElementById("editBidonError");
      const editOk = document.getElementById("editBidonOk");

      // Champs du form
      const f_id = document.getElementById("edit_id_bidon");
      const f_nom = document.getElementById("edit_nom_bidon");
      const f_type = document.getElementById("edit_type_bidon");
      const f_prix = document.getElementById("edit_prix_bidon");



      document.querySelector("#BidonsTable")?.addEventListener("click", async (e) => {
        const tr = e.target.closest("tr[data-id]");
        const id = tr?.dataset?.id;
        if (!id) return;

        if (e.target.closest(".btn-edit-bidon")) {
          // Pré-remplir depuis la ligne (évite un aller-retour à l'API)
          f_id.value = id;
          f_nom.value = tr.children[0].textContent.trim();
          f_type.value = tr.children[1].textContent.trim();
          f_prix.value = (tr.children[2].textContent || '').replace(/\s/g, '').replace(',', '.');


          editErr.classList.add("d-none");
          editOk.classList.add("d-none");
          editForm.classList.remove("was-validated");
          editModal?.show();
        }


      });
       // === Fetch list
      loadBidons = async function () {
        const params = new URLSearchParams({
          page: String(page),
          perPage: String(perPage),
          q: q.trim()
        });

        try {
          const res = await fetch(`../includes/ajax/Huile/Bidon-Huile/Bidon_list.php?` + params.toString(), {
            headers: { "Accept": "application/json" }
          });
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const data = await res.json();

          const rows = Array.isArray(data.rows) ? data.rows : [];
          total = Number(data.total || 0);


          renderTable(rows);
          renderInfo();
        } catch (e) {
          console.error(e);
          tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Erreur de chargement</td></tr>`;
          infoSpan.textContent = "";
          btnPrev.disabled = true;
          btnNext.disabled = true;
        }
      }

      editForm?.addEventListener("submit", async (ev) => {
        ev.preventDefault();
        editForm.classList.add("was-validated");
        if (!editForm.checkValidity()) return;

        editErr.classList.add("d-none");
        editOk.classList.add("d-none");

        const formData = new FormData(editForm);

        try {
          const res = await fetch(`../includes/ajax/Huile/Bidon-Huile/updateBidon.php`, {
            method: "POST",
            body: formData
          });
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const data = await res.json();

          if (data?.success) {
            editOk.classList.remove("d-none");
            // refresh la page courante
            await loadBidons();
            // referme après 500ms
            setTimeout(() => editModal?.hide(), 500);
          } else {
            editErr.textContent = data?.message || "Erreur lors de la mise à jour.";
            editErr.classList.remove("d-none");
          }
        } catch (err) {
          console.error(err);
          editErr.textContent = "Erreur réseau.";
          editErr.classList.remove("d-none");
        }
      });






      // === Elements (onglet "Status des Bidons")
      const tbody = document.querySelector("#BidonsBody");
      const infoSpan = document.querySelector("#BidonInfo");
      const searchBox = document.querySelector("#searchBidon");
      const perPageSel = document.querySelector("#perPageBidon");
      const btnPrev = document.querySelector("#prevBidon");
      const btnNext = document.querySelector("#nextBidon");
      const btnRefresh = document.querySelector("#btnRefreshBidon");

      // === Pagination state
      let page = 1;
      let perPage = parseInt(perPageSel?.value || "25", 10);
      let total = 0;
      let q = "";

      const nfMoney = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const nfInt = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });

     

      function renderTable(rows) {
        if (!rows.length) {
          tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Aucun bidon</td></tr>`;
          return;
        }

        tbody.innerHTML = rows.map((r) => {
          const id = r.id_bidon;
          const nom = escapeHtml(r.nom_bidon ?? '');
          const type = escapeHtml(r.type_bidon ?? '');
          const prix = Number(r.prix_bidon) || 0;

          const stockTotalEstime = Number(r.stock_initial) || 0;   // Quantité (total hist.)
          const stockRestant = Number(r.stock_restant) || 0;     // Reste (disponible)
          const venduTotal = Number(r.total_vendu_ou_sorti) || 0; // Vendu total
          const venduHuile = Number(r.vendu_via_bon_huile) || 0;
          const venduBidon = Number(r.vendu_via_bon_bidon) || 0;

          const dateC = escapeHtml(r.bidon_created_at ?? '');

          const prixTxt = nfMoney.format(prix);
          const qtyTotalTxt = nfInt.format(stockTotalEstime);
          const resteTxt = nfInt.format(stockRestant);
          const venduTxt = nfInt.format(venduTotal);

          const venduTitle = `Détail vendu:\n- via bon huile: ${nfInt.format(venduHuile)}\n- via bon bidon: ${nfInt.format(venduBidon)}`;

          return `
      <tr data-id="${id}"
          data-nom="${nom}"
          data-type="${type}"
          data-prix="${prix}"
          data-quantite="${stockRestant}">
        <td>${nom}</td>
        <td>${type}</td>
        <td>${prixTxt}</td>
        <td>${qtyTotalTxt}</td>
        <td class="text-success">${resteTxt}</td>
        <td class="text-danger" title="${venduTitle}">${venduTxt}</td>
        <td class="">${dateC}</td>
        <td class="">
          <div class="btn-group">
            <button class="btn btn-sm btn-outline-success btn-edit-bidon"><i class="bi bi-pencil-square"></i></button>
            <button class="btn btn-sm btn-outline-warning btn-edit-stock"><i class="bi bi-box-seam"></i></button>
          </div>
        </td>
      </tr>
    `;
        }).join("");
      }
      function renderInfo() {
        const start = total === 0 ? 0 : (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        infoSpan.textContent = `Affiche ${start}–${end} sur ${total}`;
        btnPrev.disabled = page <= 1;
        btnNext.disabled = page * perPage >= total;
      }

      // === Events
      btnPrev?.addEventListener("click", () => { if (page > 1) { page--; loadBidons(); } });
      btnNext?.addEventListener("click", () => { if (page * perPage < total) { page++; loadBidons(); } });
      perPageSel?.addEventListener("change", () => { perPage = parseInt(perPageSel.value, 10); page = 1; loadBidons(); });
      btnRefresh?.addEventListener("click", () => loadBidons());
      searchBox?.addEventListener("input", debounce(() => { q = searchBox.value; page = 1; loadBidons(); }, 300));

      // (facultatif) actions de la colonne "Actions"
      document.querySelector("#BidonsTable")?.addEventListener("click", (e) => {
        const tr = e.target.closest("tr[data-id]");
        const id = tr?.dataset?.id;
        if (!id) return;

        if (e.target.closest(".btn-edit-bidon")) {
          // ouvre ton modal d'édition (à brancher)
          console.log("Edit bidon", id);
        } else if (e.target.closest(".btn-delete-bidon")) {
          // suppression (à brancher)
          console.log("Delete bidon", id);
        }
      });

      // Utils
      function debounce(fn, wait) {
        let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
      }
      function escapeHtml(s) {
        return String(s ?? '')
          .replaceAll("&", "&amp;")
          .replaceAll("<", "&lt;")
          .replaceAll(">", "&gt;")
          .replaceAll('"', "&quot;")
          .replaceAll("'", "&#039;");
      }

      // Chargement initial uniquement si l’onglet liste est visible
      if (document.querySelector("#tab-piece.show.active")) {
        loadBidons();
      }
      // Recharger quand l’onglet “Status des Bidons” devient actif
      document.getElementById("piece-tab")?.addEventListener("shown.bs.tab", () => {
        page = 1; loadBidons();
      });
    });
  </script>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>