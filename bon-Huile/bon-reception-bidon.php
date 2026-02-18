<?php
$activeSection = 'bon-Huile';
$activePage = 'bon reception bidon';
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
require_once(__DIR__ . '/../config/config.ini.php');

$QueryGetClients = mysqli_query($connect, "SELECT * FROM client");
?>
<style>
#viewClientCmdBidonModal .table td,
#viewClientCmdBidonModal .table th {
  vertical-align: middle;
}

#viewClientCmdBidonModal .modal-body {
  max-height: 70vh;
  overflow-y: auto;
}

#viewClientCmdBidonModal .btn {
  border-radius: 6px;
}

#viewClientCmdBidonModal .form-label {
  font-size: 0.85rem;
}
.badge.bg-success {
  background-color: #198754 !important;
  font-size: 0.8rem;
}
.badge.bg-danger {
  background-color: #dc3545 !important;
  font-size: 0.8rem;
}
.badge.bg-secondary {
  background-color: #6c757d !important;
  font-size: 0.8rem;
}


</style>

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
                    Créer un bon Bidon
                  </button>
                </li>



                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="piece-tab" data-bs-toggle="tab" data-bs-target="#tab-bidon" type="button"
                    role="tab" aria-controls="tab-piece" aria-selected="false">
                    Generate Bons Bidons
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
                  <div class="card-title">Bon de Sortie du Bidon</div>
                </div>

                <form class="needs-validation" id="addBon_BidonClient"
                  action="../includes/ajax/Huile/Bidon-Huile/addBon_BidonClient.php" method="post" novalidate>
                  <div class="card-body">
                    <div class="row g-3">
                      <!-- Client -->
                      <div class="col-md-6">
                        <label for="List_client_name" class="form-label">Client /
                          Destinataire</label>
                        <select class="form-select js-choices" id="List_client_name" name="List_client_name" required>
                          <option value="" selected disabled>— Sélectionner —</option>
                          <?php foreach ($QueryGetClients as $client): ?>
                            <option value="<?= htmlspecialchars($client['id']) ?>">
                              <?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Sélectionnez un client.</div>
                      </div>
                      <!-- Note -->
                      <div class="col-md-6">
                        <label for="note_bon_bidon" class="form-label">Note (optionnel)</label>
                        <textarea class="form-control" id="note_bon_bidon" name="note_bon_bidon" rows="1"></textarea>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label mb-2">Statut de commande :</label>
                        <div class="btn-group" role="group" aria-label="Statut de commande">
                          <input type="radio" class="form-check-input" name="statut_commande" id="statut_paye" value="paye"
                            value="PAYE" autocomplete="off" required>&nbsp;&nbsp;
                          <label for="statut_paye">Payé</label>&nbsp;&nbsp;

                          <input type="radio" class="form-check-input" name="statut_commande" id="statut_non_paye"
                            value="NON PAYE" autocomplete="off" required>&nbsp;&nbsp;
                          <label for="statut_credit">Crédit</label>
                        </div>
                        <div class="invalid-feedback d-block">Sélectionnez un statut.</div>
                      </div>

                      <div class="col-md-6" id="wrap_mode_paiement">
                        <label for="mode_paiement" class="form-label">Mode de Paiment
                          :</label>
                        <select class="form-select js-choices" id="mode_paiement" name="mode_paiement" required>
                          <option value="" selected disabled>— Sélectionner —</option>
                          <option value="ESPECES">Espèces</option>
                          <option value="CARTE">Carte</option>
                          <option value="VIREMENT">Virement</option>
                          <option value="CHEQUE">Chèque</option>
                          <option value="AUTRE">Autre</option>
                        </select>
                      </div>



                    </div>

                    <hr>

                    <!-- Table Bidons -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="mb-0">Détails par type de bidon</h6>
                      <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLine" disabled>
                        Ajouter une ligne
                      </button>
                    </div>

                    <div class="table-responsive">
                      <table class="table table-sm align-middle" id="tableBidonTypes">
                        <thead class="table-light">
                          <tr>
                            <th style="width:35%">Type</th>
                            <th style="width:15%" class="text-end">Prix/bidon (DH)</th>
                            <th style="width:15%" class="text-end">Nombre</th>
                            <th style="width:15%" class="text-end">Litres (type×nb)</th>
                            <th style="width:20%" class="text-end">Montant (nb×prix)</th>
                            
                            <th style="width:10%" class="text-end">Action</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="table-light">
                          <tr>
                            <th colspan="3" class="text-end">Total litres:</th>
                            <th class="text-end"><span id="sumLitres">0.00</span></th>
                            <th class="text-end"><span id="sumMontant">0.00</span> DH</th>
                            <th></th>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>

                  <div class="card-footer text-end">
                    <button class="btn btn-primary" id="btnSubmitBon" name="btn_piece">Enregistrer</button>
                  </div>
                </form>
                

              </div>
            </div>





            <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
              <div class="card card-info card-outline mb-4">
                <div class="card-header">
                  <div class="card-title">Status des Bon du Bidon</div>
                </div>
                <div class="container-fluid py-4">
                  <div class="row">
                    <div class="col-12">
                      <div class="card shadow-sm">
                        <div class="card-body">

                          <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                            <h4 class="m-0">Liste des Bons</h4>
                            <div class="d-flex gap-2">
                              <input class="form-control" style="min-width:260px" id="searchBidon"
                                placeholder="Recherche (Nom, Type, Prix)...">
                              <select class="form-select" id="perPagePatron" style="width:140px">
                                <option value="10">10 / page</option>
                                <option value="25" selected>25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                              </select>
                              <button class="btn btn-outline-secondary" id="btnRefreshbidonBon" title="Actualiser">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                              </button>
                            </div>
                          </div>

                          <div class="table-responsive">
                            <table class="table align-middle table-hover" id="bonbidon">
                              <thead class="table-light">
                                <tr>
                                  <th>Client</th>
                                  <th>Type</th>
                                  <th>Qté</th>
                                  <th>Total</th>
                                  <th>Enregistrement</th>
                           
                                  <th>Date Création</th>

                                  <th class="text-end">Actions</th>
                                </tr>
                              </thead>
                              <tbody id="BidonBody"></tbody>
                            </table>
                          </div>

                          <div class="d-flex justify-content-between align-items-center mt-3">
                            <div><span id="BidonInfo" class="text-muted"></span></div>
                            <div class="btn-group">
                              <button class="btn btn-outline-secondary" id="prevBidon">←
                                Précédent</button>
                              <button class="btn btn-outline-secondary" id="nextBidon">Suivant →</button>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Modale d'édition -->
                  <div class="modal fade" id="editBidonModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Modifier Bon</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <form id="editBidonForm" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="id_client" id="edit_id_client">

                            <div class="row g-3">
                              <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <input type="text" class="form-control" name="nom_patron" id="edit_nom" required>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Qté</label>
                                <input type="number" min="0" step="1" class="form-control" name="qte_bidon"
                                  id="qte_bidon" required>
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
                  <!-- Modale de visualisation des détails client -->
                  <div class="modal fade" id="viewClientModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">
                            Détails du client — <span id="viewClientName">…</span>
                          </h5>
                        </div>
                        <!-- Filters / bulk actions toolbar -->
                        <div class="d-flex flex-wrap gap-2 align-items-center w-100 mt-2">
                          <div style="margin-left:12px;" class="form-check me-3">
                            <input class="form-check-input" type="checkbox" id="filterWithoutSortie">
                            <label class="form-check-label" for="filterWithoutSortie">Sans bon de sortie</label>
                          </div>

                          <input type="date" class="form-control" id="filterDateFrom" style="max-width:190px"
                            aria-label="Du">
                          <input type="date" class="form-control" id="filterDateTo" style="max-width:190px"
                            aria-label="Au">


                          <div class="row g-3">

                            <div class="col-md-6">
                              <input type="text" class="form-control" id="bulkDestination"
                                placeholder="Destination (optionnel)">
                            </div>

                            <div class="col-md-6">
                              <button class="btn btn-primary" id="btnCreateSortieBulk" disabled>Générer bon(s) de
                                sortie</button>
                            </div>
                          </div>



                        </div>



                        <div class="modal-body">
                          <div class="d-flex gap-2 mb-3">
                            <input class="form-control" id="viewSearch" placeholder="Rechercher dans ce client…">
                            <select class="form-select" id="viewPerPage" style="width:140px">
                              <option value="10">10 / page</option>
                              <option value="25" selected>25 / page</option>
                              <option value="50">50 / page</option>
                              <option value="100">100 / page</option>
                            </select>
                          </div>

                          <div class="table-responsive">
                            <table class="table table-sm align-middle">
                              <thead class="table-light">
                                <tr>
                                  <th style="width:36px">
                                    <input type="checkbox" id="chkAllRows" aria-label="Tout sélectionner">
                                  </th>
                                  <th>Client</th>
                                  <th>Nom bidon</th>
                                  <th>Type</th>
                                  <th class="text-end">Qté</th>
                                  <th class="text-end">Prix/bidon</th>
                                  <th class="text-end">Total ligne</th>
                                      
                                  <th>Date création</th>
                                  <th class="text-end">Actions</th>
                                </tr>
                              </thead>
                              <tbody id="viewClientTbody"></tbody>
                            </table>
                          </div>
                        </div>

                        <div class="modal-footer w-100 d-flex justify-content-between">
                          <span id="viewClientInfo" class="text-muted"></span>
                          <div class="btn-group">
                            <button class="btn btn-outline-secondary" id="viewPrev">← Précédent</button>
                            <button class="btn btn-outline-secondary" id="viewNext">Suivant →</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>


                </div>
              </div>
            </div>








            <div class="tab-pane fade" id="tab-bidon" role="tabpanel" aria-labelledby="piece-tab">
              <div class="card card-info card-outline mb-4">
                <div class="card-header">
                  <div class="card-title">Bon du Bidon</div>
                </div>
                <div class="container-fluid py-4">
                  <div class="row">
                    <div class="col-12">
                      <div class="card shadow-sm">
                        <div class="card-body">

                          <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                            <h4 class="m-0">Liste des Bons</h4>
                            <div class="d-flex gap-2">
                              <input class="form-control" style="min-width:260px" id="searchClient"
                                placeholder="Recherche (Nom, Type, Prix)...">
                              <select class="form-select" id="perPagePatron" style="width:140px">
                                <option value="10">10 / page</option>
                                <option value="25" selected>25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                              </select>
                              <button class="btn btn-outline-secondary" id="btnRefreshClientBonBd" title="Actualiser">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                              </button>
                            </div>
                          </div>

                          <div class="table-responsive">
                            <table class="table align-middle table-hover" id="Clientbonbidon">
                              <thead class="table-light">
                                <tr>
                                  <th>Client</th>
                                  <th>Type</th>
                                  <th>Qté</th>
                                  <th>Total</th>
                                  <th>Enregistrement</th>
                                           <th>Statut</th>
                                  <th>Date Création</th>

                                  <th class="text-end">Actions</th>
                                </tr>
                              </thead>
                              <tbody id="ClientBonBdBody"></tbody>
                            </table>
                          </div>

                          <div class="d-flex justify-content-between align-items-center mt-3">
                            <div><span id="BidonInfo" class="text-muted"></span></div>
                            <div class="btn-group">
                              <button class="btn btn-outline-secondary" id="prevClientBonBd">←
                                Précédent</button>
                              <button class="btn btn-outline-secondary" id="nextClientBonBd">Suivant →</button>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- New Modal -->
<!-- MODAL: Modifier les bidons d’un bon -->
<div class="modal fade" id="viewClientCmdBidonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">
          Modifier les bidons — Bon <span id="editBonId">#1</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="editClientCmdForm" class="needs-validation" novalidate>
        <input type="hidden" name="id_bon" id="edit_id_bon">
        <input type="hidden" name="id_client" id="edit_id_client">

        <div class="modal-body">
          <!-- Tableau des bidons -->
          <div class="table-responsive">
            <table class="table table-bordered align-middle" id="editBidonTable">
              <thead class="table-light text-center">
                <tr>
                  <th>Nom bidon</th>
                  <th>Type</th>
                  <th>Quantité</th>
                  <th>Prix/bidon (DH)</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <!-- Les lignes seront insérées ici via JS -->
              </tbody>
            </table>
          </div>

          <!-- Total général -->
          <div class="d-flex justify-content-end mt-2">
            <h6 class="fw-bold me-2">Total:</h6>
            <span id="edit_total_montant" class="fw-bold text-success">0,00 DH</span>
          </div>

          <hr>

          <!-- Section Ajouter un bidon -->
          <h6 class="fw-bold">
            <i class="bi bi-plus-circle"></i> Ajouter un bidon
          </h6>
          <div class="row g-2 align-items-center mt-1">
            <div class="col-md-6">
              <label class="form-label mb-0">Type bidon</label>
              <select class="form-select" id="select_bidon_type">
                <option value="">— Type —</option>
                <!-- Options dynamiques JS -->
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label mb-0">Quantité</label>
              <input type="number" class="form-control text-end" id="input_bidon_qte" min="0" value="0">
            </div>
            <div class="col-md-3 d-grid">
              <button type="button" class="btn btn-primary mt-3" id="btnAddBidonLineEdit">
                Ajouter
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        </div>
      </form>
    </div>
  </div>
</div>



                  <!-- Modale d'édition -->
                  <div class="modal fade" id="editBidonModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Modifier Bon</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <form id="editClientBonBdForm" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="id_client" id="edit_id_client">

                            <div class="row g-3">
                              <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <input type="text" class="form-control" name="nom_patron" id="edit_nom" required>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Qté</label>
                                <input type="number" min="0" step="1" class="form-control" name="qte_bidon"
                                  id="qte_bidon" required>
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
                  <!-- Modale de visualisation des détails client -->
                  <div class="modal fade" id="viewClientModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">
                            Détails du client — <span id="viewClientName">…</span>
                          </h5>
                        </div>
                        <!-- Filters / bulk actions toolbar -->
                        <div class="d-flex flex-wrap gap-2 align-items-center w-100 mt-2">
                          <div style="margin-left:12px;" class="form-check me-3">
                            <input class="form-check-input" type="checkbox" id="filterWithoutSortie">
                            <label class="form-check-label" for="filterWithoutSortie">Sans bon de sortie</label>
                          </div>

                          <input type="date" class="form-control" id="filterDateFrom" style="max-width:190px"
                            aria-label="Du">
                          <input type="date" class="form-control" id="filterDateTo" style="max-width:190px"
                            aria-label="Au">


                          <div class="row g-3">

                            <div class="col-md-6">
                              <input type="text" class="form-control" id="bulkDestination"
                                placeholder="Destination (optionnel)">
                            </div>

                            <div class="col-md-6">
                              <button class="btn btn-primary" id="btnCreateSortieBulk" disabled>Générer bon(s) de
                                sortie</button>
                            </div>
                          </div>



                        </div>



                        <div class="modal-body">
                          <div class="d-flex gap-2 mb-3">
                            <input class="form-control" id="viewSearch" placeholder="Rechercher dans ce client…">
                            <select class="form-select" id="viewPerPage" style="width:140px">
                              <option value="10">10 / page</option>
                              <option value="25" selected>25 / page</option>
                              <option value="50">50 / page</option>
                              <option value="100">100 / page</option>
                            </select>
                          </div>

                          <div class="table-responsive">
                            <table class="table table-sm align-middle">
                              <thead class="table-light">
                                <tr>
                                  <th style="width:36px">
                                    <input type="checkbox" id="chkAllRows" aria-label="Tout sélectionner">
                                  </th>
                                  <th>Client</th>
                                  <th>Nom bidon</th>
                                  <th>Type</th>
                                  <th class="text-end">Qté</th>
                                  <th class="text-end">Prix/bidon</th>
                                  <th class="text-end">Total ligne</th>
                                  <th>Date création</th>
                                  <th class="text-end">Actions</th>
                                </tr>
                              </thead>
                              <tbody id="viewClientBonBdTbody"></tbody>
                            </table>
                          </div>
                        </div>

                        <div class="modal-footer w-100 d-flex justify-content-between">
                          <span id="viewClientBonBdInfo" class="text-muted"></span>
                          <div class="btn-group">
                            <button class="btn btn-outline-secondary" id="viewPrev">← Précédent</button>
                            <button class="btn btn-outline-secondary" id="viewNext">Suivant →</button>
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

      <script>
        (() => {
          'use strict';

          // --- Bootstrap validation + exiger au moins une ligne
          const forms = document.querySelectorAll('.needs-validation');
          Array.from(forms).forEach((form) => {
            form.addEventListener('submit', (event) => {
              if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
              }
              if (!document.querySelector('#tableBidonTypes tbody tr')) {
                event.preventDefault();
                event.stopPropagation();
                Swal.fire({
                  icon: "error",
                  title: "Oops...",
                  text: "Ajoutez au moins une ligne de bidon!",
                  footer: '<a href="#">Pourquoi je trouve cette error?</a>'
                });

              }
              form.classList.add('was-validated');
            }, false);
          });


          document.addEventListener('hidden.bs.modal', function (event) {
  document.body.classList.remove('modal-open');
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
});
          // --- Toggle Mode de Paiement when statut is NON PAYE (Crédit)
const wrapMode = document.getElementById('wrap_mode_paiement');
const selMode  = document.getElementById('mode_paiement');
const rPaye    = document.getElementById('statut_paye');
const rNonPaye = document.getElementById('statut_non_paye');

function refreshModePaiementVisibility() {
  const isCredit = rNonPaye?.checked; // NON PAYE
  if (isCredit) {
    wrapMode?.classList.add('d-none');
    selMode.selectedIndex = 0;
    // disable required so HTML5 validation won't block
    selMode?.setAttribute('data-was-required', '1');
    selMode?.removeAttribute('required');
  } else {
    wrapMode?.classList.remove('d-none');
    // restore required if it was required before
    if (selMode?.getAttribute('data-was-required') === '1') {
      selMode.setAttribute('required', 'required');
    }
  }
}

    // events
    rPaye?.addEventListener('change', refreshModePaiementVisibility);
    rNonPaye?.addEventListener('change', refreshModePaiementVisibility);

    // run once on load (handles pre-filled values)
    refreshModePaiementVisibility();







          //============================================================ BEGIN EDIT Commande BIDON ===========================================================



(() => {
  const modalEl = document.getElementById('viewClientCmdBidonModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);

  const tableBody = modalEl.querySelector('#editBidonTable tbody');
  const totalEl = document.getElementById('edit_total_montant');
  const addBtn = document.getElementById('btnAddBidonLineEdit');
  const form = document.getElementById('editClientCmdForm');
  const selectType = document.getElementById('select_bidon_type');
  const inputQte = document.getElementById('input_bidon_qte');
  const bonIdEl = document.getElementById('editBonId');

  let bidonList = [];
  const nf2 = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // Charger la liste des bidons
  async function loadBidonsDispo() {
    try {
      const res = await fetch('../includes/ajax/Huile/Bidon-Huile/get_types_dispo.php');
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Erreur liste bidons');
      bidonList = Array.isArray(data.data) ? data.data : [];
      if (selectType) {
        selectType.innerHTML = `<option value="">— Type —</option>` +
          bidonList.map(b => `
            <option value="${b.id_bidon}" data-type="${b.type_bidon}" data-nom="${b.nom_bidon}" data-prix="${b.prix_bidon}">
              ${b.type_bidon} — dispo: ${b.stock_restant} — prix: ${nf2.format(b.prix_bidon)} DH
            </option>
          `).join('');
      }
    } catch (err) {
      console.error('Erreur chargement bidons:', err);
      bidonList = [];
    }
  }

  // Recalcul du total
  const recalc = () => {
    let total = 0;
    tableBody?.querySelectorAll('tr').forEach(tr => {
      const qte = parseFloat(tr.querySelector('.inp-qte')?.value || 0);
      const prix = parseFloat(tr.querySelector('.inp-prix')?.value || 0);
      total += qte * prix;
    });
    if (totalEl) totalEl.textContent = nf2.format(total) + ' DH';
  };

  // Template ligne
  const rowTemplate = (item = {}) => `
    <tr>
      <td>${item.nom_bidon || ''}</td>
      <td>${item.type_bidon || ''}</td>
      <td><input type="number" class="form-control text-end inp-qte" value="${item.quantite_bidon || 0}" min="0"></td>
      <td><input type="number" class="form-control text-end inp-prix" value="${item.prix_bidon || 0}" step="0.01" readOnly></td>
      <td class="text-center">
        <button type="button" class="btn btn-danger btn-del"><i class="bi bi-trash"></i></button>

      </td>
    </tr>
  `;

  // Ajouter une ligne
  addBtn?.addEventListener('click', () => {
    const opt = selectType?.selectedOptions?.[0];
    if (!opt) return Swal.fire({ icon: 'warning', text: 'Sélectionnez un type de bidon' });
    const nom = opt.dataset.nom;
    const type = opt.dataset.type;
    const prix = parseFloat(opt.dataset.prix || 0);
    const qte = parseFloat(inputQte?.value || 0);
    if (qte <= 0) return Swal.fire({ icon: 'warning', text: 'Quantité invalide' });
    tableBody?.insertAdjacentHTML('beforeend', rowTemplate({
      nom_bidon: nom, type_bidon: type, prix_bidon: prix, quantite_bidon: qte
    }));
    recalc();
    if (inputQte) inputQte.value = '0';
    if (selectType) selectType.selectedIndex = 0;
  });

  // Supprimer ligne
  tableBody?.addEventListener('click', (e) => {
    if (e.target.closest('.btn-del')) {
      e.target.closest('tr').remove();
      recalc();
    }
  });

  // Recalcul en direct
  tableBody?.addEventListener('input', (e) => {
    if (e.target.matches('.inp-qte, .inp-prix')) recalc();
  });

  // Ouvrir modal
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-view-payments');
    if (!btn) return;
    const bonId = btn.dataset.bonId;
    if (bonIdEl) bonIdEl.textContent = '#' + bonId;
    tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">Chargement...</td></tr>`;
    try {
      await loadBidonsDispo();
      const res = await fetch(`../includes/ajax/Huile/Bidon-Huile/get_cmd_bidon_detail.php?bon_id=${bonId}`);
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Erreur chargement');
      tableBody.innerHTML = data.items.map(rowTemplate).join('');
      recalc();
      modal.show();
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="5" class="text-danger text-center py-3">${err.message}</td></tr>`;
    }
  });

  // Sauvegarder
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const rows = [];
    tableBody?.querySelectorAll('tr').forEach(tr => {
      const tds = tr.querySelectorAll('td');
      rows.push({
        nom_bidon: tds[0]?.textContent.trim(),
        type_bidon: tds[1]?.textContent.trim(),
        quantite_bidon: tr.querySelector('.inp-qte')?.value,
        prix_bidon: tr.querySelector('.inp-prix')?.value
      });
    });
    const bonId = bonIdEl?.textContent.replace('#', '') || 0;
    const formData = new FormData();
    formData.append('id_bon', bonId);
    formData.append('details', JSON.stringify(rows));
    try {
      const res = await fetch('../includes/ajax/Huile/Bidon-Huile/update_cmd_bidon.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Erreur de sauvegarde');
      Swal.fire({ icon: 'success', title: 'Commande mise à jour', timer: 1800, showConfirmButton: false });
      modal.hide();
      window.location.reload();
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Erreur', text: err.message });
    }
  });
})();






















          //============================================================ END EDIT Commande BIDON ===========================================================
















          //============================================================ BEGIN LISTE BIDON ===========================================================
          // --- Elements for the list tab
          const tbody = document.getElementById('BidonBody');
          const info = document.getElementById('BidonInfo');
          const btnPrev = document.getElementById('prevBidon');
          const btnNext = document.getElementById('nextBidon');
          const search = document.getElementById('searchBidon');
          const perPage = document.getElementById('perPagePatron');
          const btnRef = document.getElementById('btnRefreshbidonBon');

          // State
          let state = {
            q: '',
            page: 1,
            perPage: parseInt(perPage?.value || '25', 10)
          };

          const nf3 = new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          });
          const fmtDate = (s) => {
            if (!s) return '';
            // Expecting 'YYYY-MM-DD HH:MM:SS' or similar
            const d = new Date(s.replace(' ', 'T'));
            if (isNaN(d)) return s;
            return d.toLocaleString('fr-FR');
          };

          function rowHtmlDetails(item) {
            const nomBidon = item.nom_bidon ?? '';
            const type = item.type_bidon ?? '';
            const disabled = item.has_sortie ? 'disabled' : '';
            return `
    <tr data-id="${item.id_detail}">
      <td><input type="checkbox" class="chkRow" ${disabled}></td>
      <td>${escapeHtml(item.client)}</td>
      <td>${escapeHtml(nomBidon)}</td>
      <td>${escapeHtml(type)}</td>
      <td class="text-end">${item.quantite_bidon}</td>
      <td class="text-end">${nf3.format(item.prix_bidon)} DH</td>
      <td class="text-end fw-semibold">${nf3.format(item.total_ligne)} DH</td>
      <td>${fmtDate(item.created_at)}</td>
      <td class="text-end">
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-primary btn-edit-bidon" data-id="${item.id_detail}">
            <i class="bi bi-pencil-square"></i>
          </button>
          ${item.has_sortie
                ? `<button class="btn btn-sm btn-outline-secondary" disabled title="Déjà généré">
                 <i class="bi bi-check2-circle"></i>
               </button>`
                : `<button class="btn btn-sm btn-outline-secondary btn-create-sortie" data-id="${item.id_detail}">
                 <i class="bi bi-sign-turn-slight-right"></i>
               </button>`}
        </div>
      </td>
    </tr>
  `;
          }














          function rowHtml(item) {
            const nf3 = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const fmtDate = (s) => {
              if (!s) return '';
              const d = new Date(String(s).replace(' ', 'T'));
              return isNaN(d) ? s : d.toLocaleString('fr-FR');
            };
            const esc = (s) => String(s ?? '')
              .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
              .replaceAll('"', '&quot;').replaceAll("'", "&#39;");

            return `
                <tr data-client-id="${item.client_id}">
                  <td>${esc(item.client)}</td>
                  
                  <td class="text-end">${item.nb_bidon_types}</td>
                  <td class="text-end">${item.total_quantite}</td>
                  <td class="text-end fw-semibold">${nf3.format(item.total_montant)} DH</td>
                  <td class="text-end">${item.lignes}</td>
                  <td>${fmtDate(item.last_created_at)}</td>
                  <td class="text-end">
                    <div class="btn-group">
                      <button class="btn btn-sm btn-outline-primary btn-view-client" data-client-id="${item.client_id}">
                          <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              `;
          }




          function escapeHtml(s) {
            return String(s ?? '')
              .replaceAll('&', '&amp;')
              .replaceAll('<', '&lt;')
              .replaceAll('>', '&gt;')
              .replaceAll('"', '&quot;')
              .replaceAll("'", '&#39;');
          }

          async function loadList() {
            const params = new URLSearchParams({
              q: state.q,
              page: String(state.page),
              perPage: String(state.perPage)
            });
            const url = '../includes/ajax/Huile/Bidon-Huile/get_List_bidon_enregistrement.php?' + params.toString();
            tbody.innerHTML = `
      <tr><td colspan="8" class="text-center text-muted py-4">Chargement…</td></tr>
    `;
            try {
              const res = await fetch(url, {
                cache: 'no-store'
              });
              const data = await res.json();

              if (!data.ok) {
                tbody.innerHTML =
                  `<tr><td colspan="8" class="text-danger">Erreur de chargement.</td></tr>`;
                return;
              }

              // rows
              if (data.items.length === 0) {
                tbody.innerHTML =
                  `<tr><td colspan="8" class="text-center text-muted py-3">Aucun résultat</td></tr>`;
              } else {
                tbody.innerHTML = data.items.map(rowHtml).join('');
              }

              // footer info + nav
              const start = (data.total === 0) ? 0 : ((data.page - 1) * data.perPage + 1);
              const end = Math.min(data.page * data.perPage, data.total);
              info.textContent = `Affichage ${start}–${end} sur ${data.total}`;

              btnPrev.disabled = (data.page <= 1);
              btnNext.disabled = (data.page >= data.totalPages);

            } catch (e) {
              console.error(e);
              tbody.innerHTML = `<tr><td colspan="8" class="text-danger">Erreur réseau.</td></tr>`;
              info.textContent = '';
              btnPrev.disabled = true;
              btnNext.disabled = true;
            }
          }

          // Events
          perPage?.addEventListener('change', () => {
            state.perPage = parseInt(perPage.value, 10);
            state.page = 1;
            loadList();
          });

          search?.addEventListener('input', debounce(() => {
            state.q = search.value.trim();
            state.page = 1;
            loadList();
          }, 350));

          btnPrev?.addEventListener('click', () => {
            if (state.page > 1) {
              state.page--;
              loadList();
            }
          });
          btnNext?.addEventListener('click', () => {
            state.page++;
            loadList();
          });

          btnRef?.addEventListener('click', () => {
            const tabDetails = document.getElementById('tab-piece');
            const isListTab = tabDetails?.classList.contains('show');

            if (isListTab) {
              // We're in the "Liste des Bons" tab
              loadList();
            } else if (viewState?.clientId) {
              // We're in the modal / detail view
              loadClientDetails();
            }
          });

          // Delegate actions (edit/print hooks)
          document.getElementById('bonbidon')?.addEventListener('click', (e) => {
            const btnView = e.target.closest('.btn-view-client');
            if (btnView) {
              const clientId = parseInt(btnView.dataset.clientId, 10);
              // Essaye de récupérer le nom du client à partir de la ligne de tableau
              const tr = btnView.closest('tr');
              const firstCellText = tr?.querySelector('td')?.textContent?.trim() || '';

              viewState.clientId = clientId;
              viewState.clientName = firstCellText; // sera écrasé par la réponse si vide
              viewState.page = 1;
              viewState.q = '';
              if (viewPerPage) viewState.perPage = parseInt(viewPerPage.value || '25', 10);

              if (viewClientName) viewClientName.textContent = viewState.clientName || ('#' + clientId);

              viewModal?.show();
              loadClientDetails();
            }
          });

          function debounce(fn, ms) {
            let t;
            return (...args) => {
              clearTimeout(t);
              t = setTimeout(() => fn(...args), ms);
            };
          }

          // Initial load only when the list tab is opened first time
          let listLoadedOnce = false;
          const tabPiece = document.getElementById('piece-tab');
          tabPiece?.addEventListener('shown.bs.tab', () => {
            if (!listLoadedOnce) {
              listLoadedOnce = true;
              loadList();
            }
          });

          // If the list tab is initially active by code or user, ensure we load too
          if (document.getElementById('tab-piece')?.classList.contains('show')) {
            listLoadedOnce = true;
            loadList();
          }

          perPage?.addEventListener('change', () => {
            state.perPage = parseInt(perPage.value, 10);
            state.page = 1;
            (loadList());
          });

          search?.addEventListener('input', debounce(() => {
            state.q = search.value.trim();
            state.page = 1;
            (loadList());
          }, 350));

          btnPrev?.addEventListener('click', () => {
            if (state.page > 1) {
              state.page--;
              (loadList());
            }
          });
          btnNext?.addEventListener('click', () => {
            state.page++;
            (loadList());
          });

          btnRef?.addEventListener('click', () => (loadList()));

          //============================================================ END LISTE BIDON ===========================================================




















          //============================================================ LISTE BON CLIENT BIDON ===========================================================



      // Make it global for inline onclick handlers
  window.openBonRouteChoice = async function (bonId) {
    // Fallback if SweetAlert2 isn't available
    if (!window.Swal) {
      const withQr = confirm('Générer le Bon de route ?\nOK = avec, Annuler = sans');
      const qr = withQr ? 1 : 0;
      window.open(`../bon-Huile/generate_bon_route.php?bon_id=${encodeURIComponent(bonId)}&qr=${qr}`, '_blank');
      return;
    }

    const result = await Swal.fire({
      title: 'Générer Bon Bidon',
      text: 'Voulez-vous Générer ?',
      icon: 'question',
      showCancelButton: true,
      showDenyButton: false,
      confirmButtonText: 'Générer',
    
      cancelButtonText: 'Annuler',
      reverseButtons: true
    });

    if (result.isConfirmed) {
      // Avec QR
       window.open(`../bon-Huile/generate_bon_bidon.php?bon_id=${encodeURIComponent(bonId)}&qr=0`, '_blank');
    }
    // Cancel: do nothing
  };

 // ======================= Helpers =======================
function esc(s) {
  return String(s ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}



// ======================= Row renderer =======================
function rowCmdBidonHtml(item) {
  const statut = (item.statut || '').toUpperCase();
  let badge = '';

  if (statut === 'PAYE' || statut === 'PAYÉ') {
    badge = '<span class="badge bg-success">Payé</span>';
  } else if (statut === 'NON PAYE' || statut === 'NON PAYÉ' || statut === 'CREDIT') {
    badge = '<span class="badge bg-danger">Crédit</span>';
  } else {
    badge = `<span class="badge bg-secondary">${esc(item.statut || 'Inconnu')}</span>`;
  }

  return `
    <tr data-bon-id="${esc(item.bon_id)}" data-client-id="${esc(item.client_id)}">
      <td>${esc(item.client)}</td>
      <td class="text-end">${esc(item.nb_bidon_types)}</td>
      <td class="text-end">${esc(item.total_quantite)}</td>
      <td class="text-end fw-semibold">${nf2.format(Number(item.total_montant || 0))} DH</td>
      <td class="text-end">${esc(item.lignes)}</td>
      <td class="text-center">${badge}</td> <!-- ✅ badge visuel -->
      <td>${fmtDate(item.last_created_at)}</td>
      <td class="text-end">
        <div class="btn-group">
          <button
            class="btn btn-sm btn-outline-warning btn-view-payments"
            data-client-id="${esc(item.client_id)}"
            data-client-name="${esc(item.client)}"
            data-bon-id="${esc(item.bon_id)}"
            data-bon-status="${esc(item.statut)}"
            data-bs-toggle="modal"
            data-bs-target="#viewClientCmdBidonModal"
            title="Modifier les quantités">
            <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-sm btn-outline-secondary"
                  onclick="openBonRouteChoice('${item.bon_id}')"
                  title="Imprimer le bon">
            <i class="bi bi-printer"></i>
          </button>
        </div>
      </td>
    </tr>
  `;
}

  



// ======================= Row renderer for modal =======================
function rowClientDetailHtml(row) {
   // prefer keys from your API; fall back if another endpoint uses different names
  const client = row.client ?? row.client_nom_complet ?? '';
  const nomBidon = row.nom_bidon ?? '';
  const typeBidon = row.type_bidon ?? '';
  const qty = Number(row.quantite_bidon ?? row.quantite ?? 0);
  const unit = Number(row.prix_bidon ?? row.prix_unitaire ?? 0);
  const total = Number(row.total_ligne ?? (qty * unit));
  const createdAt = row.created_at ?? row.date_commande ?? null;
  const idBon = row.id_bon ?? row.id_bon_sortie_bidon ?? '';
  
   return `
    <tr>
      <td>${esc(client)}</td>
      <td>${esc(nomBidon)}</td>
      <td>${esc(typeBidon)}</td>
      <td class="text-end">${nf2.format(qty)}</td>
      <td class="text-end">${nf2.format(unit)} DH</td>
      <td class="text-end fw-semibold">${nf2.format(total)} DH</td>
      <td>${fmtDate(createdAt)}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-secondary" title="Imprimer cette ligne"
          onclick="window.open('../print/bon_bidon.php?bon_id=' + encodeURIComponent('${idBon}'),'_blank')">
          <i class="bi bi-printer"></i>
        </button>
      </td>
    </tr>
  `;
}
// ======================= Wiring for tab-bidon =======================
// ======================= Main Tab Loader =======================
(function initTabBidonList() {
  const tbody    = document.getElementById('ClientBonBdBody');
  const search   = document.getElementById('searchClient');
  const perPage  = document.getElementById('perPagePatron');
  const btnPrev  = document.getElementById('prevClientBonBd');
  const btnNext  = document.getElementById('nextClientBonBd');
  const btnRef   = document.getElementById('btnRefreshClientBonBd');
  const info     = document.getElementById('BidonInfo');

  if (!tbody) return; // not on this page

  const state = {
    q: '',
    page: 1,
    perPage: parseInt(perPage?.value || '25', 10),
      id_bon: null // add this line
  };

  async function loadCmdBons() {
    const params = new URLSearchParams({
      q: state.q,
      page: String(state.page),
      perPage: String(state.perPage)
    });
      // add id_bon if present
  if (state.id_bon) {
    params.append('id_bon', String(state.id_bon));
  }

    const url = '../includes/ajax/Huile/Bidon-Huile/get_List_cmd_bidon.php?' + params.toString();

    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">Chargement…</td></tr>`;
    try {
      const res = await fetch(url, { cache: 'no-store' });
      const data = await res.json();

      if (!data.ok) throw new Error(data.error || 'Erreur de chargement');

      if (!Array.isArray(data.items) || data.items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">Aucun résultat</td></tr>`;
      } else {
        tbody.innerHTML = data.items.map(rowCmdBidonHtml).join('');
      }

      const start = data.total === 0 ? 0 : (data.page - 1) * data.perPage + 1;
      const end   = Math.min(data.page * data.perPage, data.total);
      if (info) info.textContent = `Affichage ${start}–${end} sur ${data.total}`;

      btnPrev.disabled = data.page <= 1;
      btnNext.disabled = data.page >= data.totalPages;
    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="7" class="text-danger py-3">Erreur: ${esc(String(e.message))}</td></tr>`;
      if (info) info.textContent = '';
      btnPrev.disabled = true;
      btnNext.disabled = true;
    }
  }

  // Events
  perPage?.addEventListener('change', () => {
    state.perPage = parseInt(perPage.value, 10);
    state.page = 1;
    loadCmdBons();
  });

  search?.addEventListener('input', debounce(() => {
    state.q = search.value.trim();
    state.page = 1;
    loadCmdBons();
  }, 350));

  btnPrev?.addEventListener('click', () => {
    if (state.page > 1) { state.page--; loadCmdBons(); }
  });
  btnNext?.addEventListener('click', () => {
    state.page++; loadCmdBons();
  });

  btnRef?.addEventListener('click', () => loadCmdBons());

  // Auto-load on tab show
  const tabBtn = document.querySelector('[data-bs-target="#tab-bidon"]');
  let loadedOnce = false;
  tabBtn?.addEventListener('shown.bs.tab', () => {
    if (!loadedOnce) { loadedOnce = true; loadCmdBons(); }
  });
  if (document.getElementById('tab-bidon')?.classList.contains('show')) {
    loadedOnce = true; loadCmdBons();
  }

  // ======================= VIEW CLIENT MODAL =======================
  const modalEl = document.getElementById('viewClientModal');
  const modal   = modalEl ? new bootstrap.Modal(modalEl) : null;
  const modalName = document.getElementById('viewClientName');
  const modalBody = document.getElementById('viewClientBonBdTbody');
  const modalInfo = document.getElementById('viewClientBonBdInfo');

  async function loadClientDetails(clientId) {
    modalBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-3">Chargement…</td></tr>`;
    try {
      const url = `../includes/ajax/Huile/Bidon-Huile/get_List_bidon.php?client_id=${encodeURIComponent(clientId)}&page=1&perPage=100`;
      const res = await fetch(url, { cache: 'no-store' });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Erreur chargement');

      if (data.items.length === 0) {
        modalBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-3">Aucune ligne trouvée</td></tr>`;
      } else {
        modalBody.innerHTML = data.items.map(rowClientDetailHtml).join('');
      }

      modalInfo.textContent = `Total lignes: ${data.items.length}`;
    } catch (e) {
      modalBody.innerHTML = `<tr><td colspan="8" class="text-danger">Erreur: ${esc(e.message)}</td></tr>`;
      modalInfo.textContent = '';
    }
  }

  // click handler on eye button
  document.getElementById('Clientbonbidon')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-view-client');
    if (!btn) return;
    const clientId = btn.getAttribute('data-client-id');
    const clientName = btn.getAttribute('data-client-name') || '';
    if (modalName) modalName.textContent = clientName || `Client #${clientId}`;
    modal?.show();
    loadClientDetails(clientId);
  });
})();

(() => {
  const modalEl = document.getElementById('viewClientCmdBidonModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);

  const nameEl  = document.getElementById('editClientCmdName');
  const tableBody = modalEl.querySelector('#editBidonTable tbody');
  const qteTotEl = document.getElementById('edit_total_qte');
  const montantTotEl = document.getElementById('edit_total_montant');
  const addLineBtn = document.getElementById('btnAddBidonLineEdit');
  const form = document.getElementById('editClientCmdForm');

  let editState = { bon_id: null, client_id: null, items: [] };

  const nf2 = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  const recalcTotals = () => {
    let totalQte = 0, totalDH = 0;
    tableBody.querySelectorAll('tr').forEach(tr => {
      const qte = parseFloat(tr.querySelector('.inp-qte')?.value || 0);
      const prix = parseFloat(tr.querySelector('.inp-prix')?.value || 0);
      const tot = qte * prix;
      tr.querySelector('.cell-total').innerHTML = nf2.format(tot);
      totalQte += qte;
      totalDH += tot;
    });
    qteTotEl.textContent = totalQte;
    montantTotEl.textContent = nf2.format(totalDH);
  };

  const rowTemplate = (item = {}) => `
    <tr>
      <td><input type="text" class="form-control inp-type" value="${item.type_bidon || item.nom_bidon || ''}" required></td>
      <td><input type="number" step="0.01" min="0" class="form-control text-end inp-prix" value="${item.prix_bidon || 0}" required></td>
      <td><input type="number" step="1" min="0" class="form-control text-end inp-qte" value="${item.quantite_bidon || 0}" required></td>
      <td class="text-end cell-total">${nf2.format((item.quantite_bidon || 0) * (item.prix_bidon || 0))}</td>
      <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger btn-del">Supprimer</button>
      </td>
    </tr>
  `;

  addLineBtn?.addEventListener('click', () => {
    tableBody.insertAdjacentHTML('beforeend', rowTemplate());
  });

  tableBody.addEventListener('input', (e) => {
    if (e.target.matches('.inp-qte, .inp-prix')) recalcTotals();
  });
  tableBody.addEventListener('click', (e) => {
    if (e.target.closest('.btn-del')) {
      e.target.closest('tr').remove();
      recalcTotals();
    }
  });

  // Charger les détails d'une commande pour édition
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-view-payments'); // edit button
    if (!btn) return;
    const bonId = btn.dataset.bonId;
    const clientId = btn.dataset.clientId;
    const clientName = btn.dataset.clientName;
    nameEl.textContent = clientName;
    editState = { bon_id: bonId, client_id: clientId };

    tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">Chargement…</td></tr>`;
    try {
      // On s'assure que la liste des bidons est prête avant d'afficher les lignes
    await loadBidonsDispo();
      const url = `../includes/ajax/Huile/Bidon-Huile/get_cmd_bidon_detail.php?bon_id=${encodeURIComponent(bonId)}`;
      const res = await fetch(url);
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Erreur de chargement');
      tableBody.innerHTML = data.items.map(rowTemplate).join('');
      recalcTotals();
      modal.show();
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="5" class="text-danger text-center py-3">${err.message}</td></tr>`;
    }
  });

  // Soumission du formulaire (mise à jour commande + stock)
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!form.checkValidity()) return form.classList.add('was-validated');

    const rows = [];
    tableBody.querySelectorAll('tr').forEach(tr => {
      rows.push({
        type_bidon: tr.querySelector('.inp-type')?.value,
        prix_bidon: tr.querySelector('.inp-prix')?.value,
        quantite_bidon: tr.querySelector('.inp-qte')?.value
      });
    });

    const formData = new FormData(form);
    formData.append('id_bon', editState.bon_id);
    formData.append('details', JSON.stringify(rows));

    try {
      const res = await fetch('../includes/ajax/Huile/Bidon-Huile/update_cmd_bidon.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Erreur de sauvegarde');
      Swal.fire({ icon: 'success', title: 'Commande mise à jour', timer: 1800, showConfirmButton: false });
      modal.hide();

      // 🔄 Auto-refresh table principale (si DataTable ou reload simple)
      if (typeof loadBonBidonList === 'function') loadBonBidonList();
      else window.location.reload();

    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Erreur', text: err.message });
    }
  });
})();



































          //============================================================ DETAIL BON CLIENT BIDON ===========================================================
































          //============================================================ DETAIL LISTE BIDON ===========================================================
          // ====== STATE & ELEMENTS POUR LA MODALE DE DÉTAILS ======
          const viewModalEl = document.getElementById('viewClientModal');
          const viewModal = viewModalEl ? new bootstrap.Modal(viewModalEl) : null;
          const viewClientName = document.getElementById('viewClientName');
          const viewTbody = document.getElementById('viewClientTbody');
          const viewInfo = document.getElementById('viewClientInfo');
          const viewPrev = document.getElementById('viewPrev');
          const viewNext = document.getElementById('viewNext');
          const viewSearch = document.getElementById('viewSearch');
          const viewPerPage = document.getElementById('viewPerPage');

          let viewState = {
            clientId: null,
            clientName: '',
            q: '',
            page: 1,
            perPage: parseInt(viewPerPage?.value || '25', 10)
          };

          async function loadClientDetails() {
            if (!viewState.clientId) return;
            const withoutSortie = document.getElementById('filterWithoutSortie')?.checked ? '1' : '0';
            const df = document.getElementById('filterDateFrom')?.value || '';
            const dt = document.getElementById('filterDateTo')?.value || '';
            const params = new URLSearchParams({
              client_id: String(viewState.clientId),
              q: viewState.q,
              page: String(viewState.page),
              perPage: String(viewState.perPage),
              without_sortie: withoutSortie,
              date_from: df,
              date_to: dt
            });
            const url = '../includes/ajax/Huile/Bidon-Huile/get_List_bidon.php?' + params.toString();




            viewTbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Chargement…</td></tr>`;

            try {
              const res = await fetch(url, { cache: 'no-store' });
              const text = await res.text();
              let data;
              try { data = JSON.parse(text); } catch { throw new Error(text); }

              if (!data.ok) throw new Error(data.error || 'Erreur de chargement');

              // Renseigner le nom du client (depuis le 1er item si pas déjà connu)
              if (!viewState.clientName) {
                const firstItem = data.items[0];
                if (firstItem && firstItem.client) viewState.clientName = String(firstItem.client);
              }
              if (viewClientName) viewClientName.textContent = viewState.clientName || ('#' + viewState.clientId);

              // Lignes
              viewTbody.innerHTML = data.items.length
                ? data.items.map(rowHtmlDetails).join('')
                : `<tr><td colspan="8" class="text-center text-muted py-3">Aucun résultat</td></tr>`;

              // Footer info + nav
              const start = (data.total === 0) ? 0 : ((data.page - 1) * data.perPage + 1);
              const end = Math.min(data.page * data.perPage, data.total);
              if (viewInfo) viewInfo.textContent = `Affichage ${start}–${end} sur ${data.total}`;

              if (viewPrev) viewPrev.disabled = (data.page <= 1);
              if (viewNext) viewNext.disabled = (data.page >= data.totalPages);

            } catch (e) {
              console.error(e);
              viewTbody.innerHTML = `<tr><td colspan="8" class="text-danger">Erreur: ${escapeHtml(String(e.message)).slice(0, 2000)}</td></tr>`;
              if (viewInfo) viewInfo.textContent = '';
              if (viewPrev) viewPrev.disabled = true;
              if (viewNext) viewNext.disabled = true;
            }
          }


          document.getElementById('filterWithoutSortie')?.addEventListener('change', () => {
            viewState.page = 1; loadClientDetails();
          });
          document.getElementById('filterDateFrom')?.addEventListener('change', () => {
            viewState.page = 1; loadClientDetails();
          });
          document.getElementById('filterDateTo')?.addEventListener('change', () => {
            viewState.page = 1; loadClientDetails();
          });
          const bulkBtn = document.getElementById('btnCreateSortieBulk');
          const tbodyEl = document.getElementById('viewClientTbody');
          const chkAll = document.getElementById('chkAllRows');

          function refreshBulkBtn() {
            const anyChecked = !!tbodyEl?.querySelector('input.chkRow:checked');
            if (bulkBtn) bulkBtn.disabled = !anyChecked;
          }

          tbodyEl?.addEventListener('change', (e) => {
            if (e.target.classList.contains('chkRow')) refreshBulkBtn();
          });

          chkAll?.addEventListener('change', () => {
            const rows = tbodyEl?.querySelectorAll('input.chkRow:not(:disabled)');
            rows?.forEach(cb => cb.checked = chkAll.checked);
            refreshBulkBtn();
          });

          // After reload of data, clear master checkbox and disable bulk by default
          function afterDetailsRendered() {
            if (chkAll) chkAll.checked = false;
            refreshBulkBtn();
          }

          //============================================================


          bulkBtn?.addEventListener('click', async () => {
            const ids = [];
            tbodyEl?.querySelectorAll('tr').forEach(tr => {
              const cb = tr.querySelector('input.chkRow');
              if (cb && !cb.disabled && cb.checked) {
                const id = parseInt(tr.getAttribute('data-id') || '0', 10);
                if (id > 0) ids.push(id);
              }
            });
            if (!ids.length) return;

            const destination = document.getElementById('bulkDestination')?.value || '';
            bulkBtn.disabled = true;

            try {
              const res = await fetch('../includes/ajax/Huile/Bidon-Huile/create_bon_sortie_bulk.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                  destination,
                  ...ids.reduce((acc, id, i) => (acc[`id_details[${i}]`] = String(id), acc), {})
                })
              });
              const data = await res.json();
              if (!data.ok) throw new Error(data.error || 'Erreur inconnue');

              // Optional: toast / Swal
              // Swal.fire({icon:'success', title:'Création terminée', text:`${data.created} créé(s), ${data.skipped} ignoré(s)`});

              loadClientDetails(); // refresh list & buttons
            } catch (err) {
              alert('Erreur: ' + String(err.message || err));
            } finally {
              bulkBtn.disabled = false;
            }
          });




          // Événements de la modale
          viewPerPage?.addEventListener('change', () => {
            viewState.perPage = parseInt(viewPerPage.value, 10);
            viewState.page = 1;
            loadClientDetails();
          });
          viewSearch?.addEventListener('input', debounce(() => {
            viewState.q = viewSearch.value.trim();
            viewState.page = 1;
            loadClientDetails();
          }, 350));
          viewPrev?.addEventListener('click', () => {
            if (viewState.page > 1) { viewState.page--; loadClientDetails(); }
          });
          viewNext?.addEventListener('click', () => {
            viewState.page++; loadClientDetails();
          });

          // (Optionnel) Réinitialise l’état quand la modale se ferme
          viewModalEl?.addEventListener('hidden.bs.modal', () => {
            viewState = { clientId: null, clientName: '', q: '', page: 1, perPage: parseInt(viewPerPage?.value || '25', 10) };
            if (viewSearch) viewSearch.value = '';
            if (viewTbody) viewTbody.innerHTML = '';
            if (viewInfo) viewInfo.textContent = '';
          });



          //============================================================ END DETAIL LISTE BIDON =======================================================


 // --- Charger les types disponibles
    fetch('../includes/ajax/Huile/Bidon-Huile/get_types_dispo.php')
  .then(r => r.json())
  .then(res => {
    if (!res.ok || !Array.isArray(res.data)) throw new Error('Invalid response');
    TYPES_ROWS = res.data.map(r => ({
      type_bidon: String(r.type_bidon || ''),
      qte_dispo: parseFloat(r.stock_restant || 0),  // ✅ FIXED: use stock_restant
      prix_bidon: parseFloat(r.prix_bidon || 0)
    }));
    btnAdd.removeAttribute('disabled');
    btnAdd.click();
    recalc();
  })
  .catch(err => {
    console.error(err);
    TYPES_ROWS = [];
  });






          // --- Elements
          const tblBody = document.querySelector("#tableBidonTypes tbody");
          const btnAdd = document.getElementById("btnAddLine");
          const sumLit = document.getElementById("sumLitres");
          const sumMont = document.getElementById("sumMontant");

          // --- Data
          let TYPES_ROWS = []; // [{type_bidon, qte_dispo, prix_bidon}, ...]

          // --- Helpers
          const typeToLiters = (t) => {
            const m = String(t || '').toUpperCase().match(/(\d+(?:\.\d+)?)\s*L/);
            return m ? parseFloat(m[1]) : 0;
          };
          const nf2 = new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          });
          const getRowByType = (type) => TYPES_ROWS.find(r => r.type_bidon === type) || {
            prix_bidon: 0,
            qte_dispo: 0
          };

          function buildOptionsHtml() {
          
            
            return '<option value="">-- Type --</option>' + TYPES_ROWS.map(r => `
      <option value="${r.type_bidon}" data-prix="${r.prix_bidon}" ${r.qte_dispo <= 0 ? 'disabled' : ''}>
        ${r.type_bidon} — dispo: ${r.qte_dispo} — prix: ${nf2.format(r.prix_bidon)} DH
      </option>
    `).join('');
          }

          function rowTemplate() {
            return `
      <tr>
        <td>
          <select name="detail_type[]" class="form-select sel-type" required>
            ${buildOptionsHtml()}
          </select>
          <div class="invalid-feedback">Choisir un type.</div>
        </td>
        <td class="text-end"><span class="cell-prix">0.00</span></td>
        <td>
          <input type="number" step="1" min="0"
                 class="form-control text-end inp-count" name="detail_count[]" required>
          <div class="invalid-feedback">Saisir un nombre.</div>
        </td>
        <td class="text-end"><span class="cell-litres">0.00</span></td>
        <td class="text-end"><span class="cell-montant">0.00</span></td>
        <td class="text-end">
          <button type="button" class="btn btn-sm btn-outline-danger btn-del">Supprimer</button>
        </td>
      </tr>
    `;
          }

          function recalc() {
            let totalL = 0;
            let totalDH = 0;

            tblBody.querySelectorAll("tr").forEach(tr => {
              const sel = tr.querySelector(".sel-type");
              const type = sel?.value || "";
              const row = getRowByType(type);
              const prix = parseFloat(row.prix_bidon || 0) || 0;

              tr.querySelector(".cell-prix").textContent = nf2.format(prix);

              const count = parseFloat(tr.querySelector(".inp-count")?.value || "0") || 0;
              const cap = typeToLiters(type);
              const liters = cap * count;
              tr.querySelector(".cell-litres").textContent = nf2.format(liters);
              totalL += liters;

              const montant = count * prix;
              tr.querySelector(".cell-montant").textContent = nf2.format(montant);
              totalDH += montant;
            });

            sumLit.textContent = nf2.format(totalL);
            sumMont.textContent = nf2.format(totalDH);
          }

          // events
          tblBody.addEventListener("input", (e) => {
            if (e.target.matches(".inp-count")) recalc();
          });
          tblBody.addEventListener("change", (e) => {
            if (e.target.matches(".sel-type")) recalc();
          });
          tblBody.addEventListener("click", (e) => {
            if (e.target.closest(".btn-del")) {
              e.target.closest("tr")?.remove();
              recalc();
            }
          });
          btnAdd?.addEventListener("click", () => {
            tblBody.insertAdjacentHTML("beforeend", rowTemplate());
          });

         




        })();
      </script>

    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>