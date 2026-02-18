<?php

$activeSection = 'bon-Huile';
$activePage = 'bon-huile-ajouter';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
require_once(__DIR__ . '/../config/config.ini.php');

$QueryGetClients = mysqli_query($connect, "SELECT * FROM client");

// Charger la liste des villes (JSON local ou API)
$villes = json_decode(file_get_contents(__DIR__ . "/../assets/ma.json"), true);


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
                                    <button class="nav-link active" id="machine-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-machine" type="button" role="tab"
                                        aria-controls="tab-machine" aria-selected="true">
                                        Ajouter Client
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-piece" type="button" role="tab" aria-controls="tab-piece"
                                        aria-selected="false">
                                        Listes des Clients
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="tab-content mt-3" id="formTabsContent">
                        <div class="tab-pane fade show active" id="tab-machine" role="tabpanel"
                            aria-labelledby="machine-tab">
                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Ajouter un Client</div>
                                </div>

                                <form id="addClientForm" class="needs-validation"
                                    action="../includes/ajax/Huile/Client-Huile/addClient.php" method="POST"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="nom_client" class="form-label">Nom :</label>
                                                <input type="text" class="form-control" id="nom" name="nom" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="prenom_client" class="form-label">Prénom :</label>
                                                <input type="text" class="form-control" id="prenom" name="prenom"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_client" class="form-label">Photo de Client :</label>
                                                <input type="file" class="form-control" id="image_client"
                                                    name="image_client" accept=".jpg,.jpeg,.png">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="description_machine" class="form-label">Num de Téléphone
                                                    :</label>
                                                <input type="tel" class="form-control" minlength="10" maxlength="13" id="num_telephone"
                                                    name="num_telephone"></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="ville_client" class="form-label">Ville :</label>
                                                <select class="form-select js-choices" id="ville" name="ville" required>
                                                    <?php foreach ($villes as $v): ?>
                                                        <option value="<?= htmlspecialchars($v['city']) ?>">
                                                            <?= htmlspecialchars($v['city']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="address_client" class="form-label">Adresse :</label>
                                                <input type="text" class="form-control" id="address_client"
                                                    name="address_client" required>
                                            </div>



                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-primary text-white" type="submit"
                                            name="addClientBtn">Ajouter</button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Client Info</div>
                                </div>
                                <div class="card-body">

                                    <div
                                        class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                                        <h4 class="m-0">Liste des Clients</h4>
                                        <div class="d-flex gap-2">
                                            <input class="form-control" style="min-width:260px" id="searchHclient"
                                                placeholder="Recherche (Nom, Prenom, Adresse)...">
                                            <select class="form-select" id="perPageHclient" style="width:140px">
                                                <option value="10">10 / page</option>
                                                <option value="25" selected>25 / page</option>
                                                <option value="50">50 / page</option>
                                                <option value="100">100 / page</option>
                                            </select>
                                            <button class="btn btn-outline-secondary" id="btnRefreshHclient"
                                                title="Actualiser">
                                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table align-middle table-hover" id="HclientTable">
                                            <thead class="table-light">
                                                <tr>

                                                    <th>Profile</th>
                                                    <th>Nom</th>
                                                    <th>Prenom</th>
                                                    <th>Téléphone</th>
                                                    <th>Adress</th>
                                                    <th>Ville</th>
                                                    <th>Date Création</th>

                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="HclientBody"></tbody>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div><span id="HclientInfo" class="text-muted"></span></div>
                                        <div class="btn-group">
                                            <button class="btn btn-outline-secondary" id="prevHclient">←
                                                Précédent</button>
                                            <button class="btn btn-outline-secondary" id="nextHclient">Suivant
                                                →</button>
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
    <!-- Modal: Éditer un client -->
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editClientForm" class="modal-content needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="editClientLabel">Modifier le client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_client_id" name="id">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        <div class="invalid-feedback">Nom requis.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
                        <div class="invalid-feedback">Prénom requis.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="edit_phone" minlength="10" maxlength="10" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ville</label>
                        <input type="text" class="form-control" id="edit_ville" name="ville">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-control" id="edit_address" name="address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo (optionnel)</label>
                        <input type="file" class="form-control" id="edit_image_client" name="image_client"
                            accept=".jpg,.jpeg,.png">
                    </div>

                    <div class="alert alert-danger d-none" id="editClientError"></div>
                    <div class="alert alert-success d-none" id="editClientOk">Modifié avec succès.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="saveClientBtn">Enregistrer</button>
                </div>
            </form>
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
  
// =======================
// Liste Clients (AJAX)
// =======================
document.addEventListener("DOMContentLoaded", () => {
  // Elements
  const tbody      = document.querySelector("#HclientBody");
  const infoSpan   = document.querySelector("#HclientInfo");
  const searchBox  = document.querySelector("#searchHclient");
  const perPageSel = document.querySelector("#perPageHclient");
  const btnPrev    = document.querySelector("#prevHclient");
  const btnNext    = document.querySelector("#nextHclient");
  const btnRefresh = document.querySelector("#btnRefreshHclient");

  // Modal Edit
  const editModalEl = document.getElementById("editClientModal");
  const editModal   = editModalEl ? new bootstrap.Modal(editModalEl) : null;
  const editForm    = document.getElementById("editClientForm");
  const e_id        = document.getElementById("edit_client_id");
  const e_nom       = document.getElementById("edit_nom");
  const e_prenom    = document.getElementById("edit_prenom");
  const e_phone     = document.getElementById("edit_phone");
  const e_ville     = document.getElementById("edit_ville");
  const e_address   = document.getElementById("edit_address");
  const e_img       = document.getElementById("edit_image_client");
  const editErr     = document.getElementById("editClientError");
  const editOk      = document.getElementById("editClientOk");

  // State
  let page = 1;
  let perPage = parseInt(perPageSel?.value || "25", 10);
  let total = 0;
  let q = "";

  async function loadClients() {
    const params = new URLSearchParams({
      page: String(page),
      perPage: String(perPage),
      q: q.trim()
    });

    try {
      const res = await fetch(`../includes/ajax/Huile/Client-Huile/Client_list.php?` + params.toString(), {
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

  function renderTable(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Aucun client</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map((r) => {
      const id    = r.id;
      const img   = r.image_client ? `../${escapeHtml(r.image_client)}` : `../uploads/image/no-profile.png`;
      const nom   = escapeHtml(r.nom ?? '');
      const pren  = escapeHtml(r.prenom ?? '');
      const tel   = escapeHtml(r.phone ?? '');
      const addr  = escapeHtml(r.address ?? '');
      const ville = escapeHtml(r.ville ?? '');
      const dateC = escapeHtml(r.created_at ?? '');

      return `
        <tr data-id="${id}">
          <td><img src="${img ? img : '<img src="../uploads/image/no-profile.png" style="max-height:50px;max-width:50px;">'}" style="max-height:50px;max-width:50px;object-fit:cover;border-radius:6px"></td>
          <td>${nom}</td>
          <td>${pren}</td>
          <td>${tel}</td>
          <td>${addr}</td>
          <td>${ville}</td>
          <td class="text-center">${dateC}</td>
          <td class="text-end">
            <div class="btn-group">
              <button class="btn btn-sm btn-outline-primary btn-edit-client">Modifier</button>
            </div>
          </td>
        </tr>
      `;
    }).join("");
  }

  function renderInfo() {
    const start = total === 0 ? 0 : (page - 1) * perPage + 1;
    const end   = Math.min(page * perPage, total);
    infoSpan.textContent = `Affiche ${start}–${end} sur ${total}`;
    btnPrev.disabled = page <= 1;
    btnNext.disabled = page * perPage >= total;
  }

  // Events liste
  btnPrev?.addEventListener("click", () => { if (page > 1) { page--; loadClients(); } });
  btnNext?.addEventListener("click", () => { if (page * perPage < total) { page++; loadClients(); } });
  perPageSel?.addEventListener("change", () => { perPage = parseInt(perPageSel.value, 10); page = 1; loadClients(); });
  btnRefresh?.addEventListener("click", () => loadClients());
  searchBox?.addEventListener("input", debounce(() => { q = searchBox.value; page = 1; loadClients(); }, 300));

  // Actions
  document.querySelector("#HclientTable")?.addEventListener("click", (e) => {
    const tr = e.target.closest("tr[data-id]");
    const id = tr?.dataset?.id;
    if (!id) return;

    if (e.target.closest(".btn-edit-client")) {
      // Pré-remplir depuis la ligne
      e_id.value    = id;
      e_nom.value   = tr.children[1].textContent.trim();
      e_prenom.value= tr.children[2].textContent.trim();
      e_phone.value = tr.children[3].textContent.trim();
      e_address.value = tr.children[4].textContent.trim();
      e_ville.value = tr.children[5].textContent.trim();

      editErr.classList.add("d-none");
      editOk.classList.add("d-none");
      editForm.classList.remove("was-validated");
      editModal?.show();
    }

    if (e.target.closest(".btn-delete-client")) {
      console.log("TODO supprimer client id=", id);
      // on pourra ajouter deleteClient.php plus tard si tu veux
    }
  });

  // Submit édition
  editForm?.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    editForm.classList.add("was-validated");
    if (!editForm.checkValidity()) return;

    editErr.classList.add("d-none");
    editOk.classList.add("d-none");

    const formData = new FormData(editForm);
    try {
      const res = await fetch(`../includes/ajax/Huile/Client-Huile/updateClient.php`, {
        method: "POST",
        body: formData
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      if (data?.success) {
        editOk.classList.remove("d-none");
        await loadClients();
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

  // Chargement initial si l’onglet “Listes des Clients” est actif
  if (document.querySelector("#tab-piece.show.active")) {
    loadClients();
  }
  // Recharger à l’ouverture de l’onglet
  document.getElementById("piece-tab")?.addEventListener("shown.bs.tab", () => {
    page = 1; loadClients();
  });
});




    </script>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>