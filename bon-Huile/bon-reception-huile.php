<?php
$activeSection = 'bon-Huile';
$activePage = 'bon reception huile';
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
require_once(__DIR__ . '/../config/config.ini.php');

$QueryGetClients = mysqli_query($connect, "SELECT * FROM client");
$dailyToken = function_exists('global_24h_token')
  ? global_24h_token()
  : bin2hex(random_bytes(4));
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
                                        Créer un bon d’huile
                                    </button>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-piece" type="button" role="tab" aria-controls="tab-piece"
                                        aria-selected="false">
                                        Status d’Huile
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
                                  <div class="card-title ">Bon de Sortie d’Huile</div>
                                    
                                       <span class="text-success fw-bold d-block text-center" style="font-size: 0.95rem;">
    Code du jour : 
    <span style="letter-spacing:1px;"><?= htmlspecialchars($dailyToken) ?></span>
</span>


                                </div>

                                <form class="needs-validation" id="addBon_HuileClient"
                                    action="../includes/ajax/Huile/Bidon-Huile/addBon_HuileClient.php" method="post"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="List_client_name" class="form-label">Client </label>
                                                <select class="form-select js-choices" id="List_client_name"
                                                    name="List_client_name" required>
                                                    <option value="" selected disabled>Sélectionner un client</option>
                                                    <?php foreach ($QueryGetClients as $client): ?>
                                                    <option value="<?= htmlspecialchars($client['id']) ?>">
                                                        <?= htmlspecialchars($client['nom']) . ' ' . htmlspecialchars($client['prenom']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>


                                            <div class="col-md-6">
                                                <label for="quantite_en_litre" class="form-label">Quantité (L)</label>
                                                <input type="number" class="form-control" id="quantite_en_litre"
                                                    name="quantite_en_litre" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="prix_unitaire" class="form-label">Prix par litre
                                                    (DH)</label>
                                                <input type="number" class="form-control" id="prix_unitaire"
                                                    name="prix_unitaire" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="prix_net" class="form-label">Total net (DH)</label>
                                                <input type="number" class="form-control" id="prix_net" name="prix_net"
                                                    readonly>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="provider_of_price" class="form-label">Validé par</label>
                                                <select class="form-select" id="provider_of_price"
                                                    name="provider_of_price" required>
                                                    <option value="Youssef">Youssef</option>
                                                    <option value="Hamid">Hamid</option>
                                                    <option value="Said">Said</option>
                                                </select>
                                            </div>


                                            <div class="col-md-6">
                                                <label for="note_bon_huile" class="form-label">Remarques
                                                    (optionnel)</label>
                                                <textarea class="form-control" id="note_bon_huile"
                                                    name="note_bon_huile"></textarea>
                                            </div>
                                            <hr>
                                            <div class="col-md-6">
                                                <label for="cin" class="form-label">Carte d’Identité (optionnel)</label>
                                                <input type="text" class="form-control" id="cin" name="cin">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="matricule" class="form-label">Matricule du véhicule
                                                    (optionnel)</label>
                                                <input type="text" class="form-control" id="matricule" name="matricule">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_voiture" class="form-label">Image du véhicule
                                                    (optionnel)</label>
                                                <input type="file" class="form-control" id="image_voiture"
                                                    name="image_voiture" accept="image/png, image/jpeg, image/jpg">
                                            </div>
                                            <hr>

                                            <div class="row mb-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label mb-2">Statut de commande </label>
                                                    <div class="btn-group" role="group" aria-label="Statut de commande"
                                                        required>
                                                        <input type="radio" class="form-check-input"
                                                            name="statut_commande" id="statut_paye_huile" value="PAYE"
                                                            autocomplete="off" required>&nbsp;&nbsp;
                                                        <label for="statut_paye_huile">Payé</label>&nbsp;&nbsp;

                                                        <input type="radio" class="form-check-input"
                                                            name="statut_commande" id="statut_non_paye_huile"
                                                            value="CREDIT" autocomplete="off" required>&nbsp;&nbsp;
                                                        <label for="statut_non_paye_huile">Crédit</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6" id="wrap_mode_paiement_huile">
                                                    <label for="mode_paiement_huile" class="form-label">Mode de Paiement
                                                        :</label>
                                                    <select class="form-select js-choices" id="mode_paiement_huile"
                                                        name="mode_paiement_huile" required>
                                                        <option value="" selected disabled>Sélectionner</option>
                                                        <option value="ESPECES">Espèces</option>
                                                        <option value="VIREMENT">Virement</option>
                                                        <option value="CHEQUE">Cheque</option>
                                                        <option value="LCN">LCN</option>
                                                    </select>

                                                    <!-- Champ image de justification -->
                                                    <div id="image_justification_wrap"
                                                        style="display:none; margin-top:10px;">
                                                        <label for="image_justification"
                                                            class="form-label small">Justification de paiement</label>
                                                        <input type="file" class="form-control" id="image_justification"
                                                            name="image_justification" accept=".jpg,.jpeg,.png">
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
<div style='display:none;'>
                                            <h5 class="mb-2">Ajouter des bidons</h5>

                                            <div class="d-flex justify-content-between align-items-center mb-2 ">
                                                <div class="small text-muted">
                                                    Choisissez le type de bidon et précisez le nombre à délivrer.
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    id="btnAddLine" disabled>
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
                                                            <th style="width:20%" class="text-end">Montant (nb×prix)
                                                            </th>
                                                            <th style="width:10%" class="text-end"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <th colspan="3" class="text-end">Total litres:</th>
                                                            <th class="text-end"><span id="sumLitres">0.00</span></th>
                                                            <th class="text-end"><span id="sumMontant">0.00</span> DH
                                                            </th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="card-footer text-end">
                                        <button class="btn btn-primary text-white" id="btnSubmitBon"
                                            name="btn_piece">Ajouter</button>
                                    </div>
                                </form>

                            </div>
                        </div>





                        <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="card card-info card-outline mb-4">

                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div class="card-title mb-0">Liste de Bon de Sortie d’Huile</div>
                                    <span class="text-success fw-bold" style="font-size: 0.95rem;">
                                        Code du jour : <span
                                            style="letter-spacing:1px;"><?= htmlspecialchars($dailyToken) ?></span>
                                    </span>
                                </div>


                                <div class="card-body">
                                    <form id="filterBonHuile" class="row g-2 align-items-end mb-3">
                                        <div class="col-sm-3">
                                            <label class="form-label">Recherche</label>
                                            <input type="search" class="form-control" id="bh_q"
                                                placeholder="Client, provider, note, prix…">
                                        </div>
                                        <div class="col-sm-2">
                                            <label class="form-label">Du</label>
                                            <input type="date" class="form-control" id="bh_date_from">
                                        </div>
                                        <div class="col-sm-2">
                                            <label class="form-label">Au</label>
                                            <input type="date" class="form-control" id="bh_date_to">
                                        </div>
                                        <div class="col-sm-2">
                                            <label class="form-label">Par page</label>
                                            <select class="form-select" id="bh_perPage">
                                                <option value="10">10</option>
                                                <option value="25" selected>25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-3 text-end">
                                            <button type="submit" class="btn btn-primary">Filtrer</button>
                                            <button type="button" class="btn btn-outline-secondary" id="bh_reset"><i
                                                    class="bi bi-arrow-repeat"></i></button>
                                        </div>
                                    </form>

                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover align-middle" id="tblBonHuile">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 100px;">Date</th>
                                                    <th>Client</th>
                                                    <th class="text-end">Qté (L)</th>
                                                    <th class="text-end">PU</th>
                                                    <th class="text-end">Prix net</th>
                                                    <th>Provider</th>
                                                    <th>Mode</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="2" class="text-end">Totaux (page):</th>
                                                    <th class="text-end" id="bh_sum_qte">0.00</th>
                                                    <th></th>
                                                    <th class="text-end" id="bh_sum_net">0.00</th>
                                                    <th colspan="4"></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="small text-muted" id="bh_pager_info">Page 1 / 1 — 0 enregistrements
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary" id="bh_prev">«
                                                Préc.</button>
                                            <button type="button" class="btn btn-outline-secondary" id="bh_next">Suiv.
                                                »</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Détails modal -->
                                <div class="modal fade" id="bhModalDetails" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modifier les bidons — Bon #<span
                                                        id="bh_modal_id"></span></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Fermer"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Nom bidon</th>
                                                                <th>Type</th>
                                                                <th class="text-end">Quantité</th>
                                                                <th class="text-end">Prix/bidon</th>
                                                                <th class="text-end">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="bh_modal_tbody"></tbody>
                                                    </table>
                                                </div>

                                                <div class="text-end fw-semibold mt-2">
                                                    Total: <span id="bh_modal_total_bidon">0.00</span> DH
                                                </div>

                                                <hr>
                                                <h6>➕ Ajouter un bidon</h6>
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-5">
                                                        <label class="form-label">Type bidon</label>
                                                        <select id="bh_add_type" class="form-select"></select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Quantité</label>
                                                        <input type="number" id="bh_add_qte" class="form-control"
                                                            min="1" step="1" placeholder="0">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <button id="bh_add_btn"
                                                            class="btn btn-primary w-100">Ajouter</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Fermer</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                 <!-- Modal: Update Bon d’Huile -->
<!-- 🔹 MODAL: Modifier Bon d’Huile -->
<div class="modal fade" id="bhModalUpdate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-pencil-square me-1"></i> Modifier Bon d’Huile #<span id="bh_upd_id"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="formUpdateBonHuile" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="bh_upd_hidden_id" name="id_bon">

          <div class="mb-3">
            <label class="form-label">Quantité (L)</label>
            <input type="number" step="0.01" class="form-control" id="bh_upd_quantite" name="quantite" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Prix unitaire (DH/L)</label>
            <input type="number" step="0.01" class="form-control" id="bh_upd_pu" name="prix_unitaire" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select class="form-select" id="bh_upd_mode" name="mode_paiement_huile">
              <option value="">— Sélectionner —</option>
              <option value="ESPECES">Espèces</option>
              <option value="VIREMENT">Virement</option>
              <option value="CHEQUE">Chèque</option>
              <option value="LCN">LCN</option>
              <option value="CARTE">Carte</option>
              <option value="AUTRE">Autre</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Justification du paiement (optionnel)</label>
            <input type="file" class="form-control" id="bh_upd_justif"
                   name="justification_paiement" accept=".jpg,.jpeg,.png,.pdf">
            <div class="form-text">Laisse vide pour conserver la justification actuelle.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Note</label>
            <textarea class="form-control" id="bh_upd_note" name="note" rows="2"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
          <button type="submit" class="btn btn-info text-white">
            <i class="bi bi-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- 🔹 FIN MODAL -->


 <!-- Modal: End Update Bon d’Huile -->



                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <script>
            (function() {
                'use strict';

                const API_BON = '../includes/ajax/Huile/Bidon-Huile/getBon_Huile.php';
                const API_TYPES = '../includes/ajax/Huile/Bidon-Huile/get_types_dispo.php';

                // -------------------------
                // Elements
                // -------------------------
                const tbody = document.querySelector('#tblBonHuile tbody');
                const prevBtn = document.getElementById('bh_prev');
                const nextBtn = document.getElementById('bh_next');
                const pagerInfo = document.getElementById('bh_pager_info');
                const perEl = document.getElementById('bh_perPage');
                const qEl = document.getElementById('bh_q');
                const fromEl = document.getElementById('bh_date_from');
                const toEl = document.getElementById('bh_date_to');
                const form = document.getElementById('filterBonHuile');
                const resetBtn = document.getElementById('bh_reset');

                // Update modal
                const updateModal = document.getElementById('bhModalUpdate');
                const formUpd = document.getElementById('formUpdateBonHuile');
                const updIdSpan = document.getElementById('bh_upd_id');
                const updHidden = document.getElementById('bh_upd_hidden_id');
                const updQte = document.getElementById('bh_upd_quantite');
                const updPU = document.getElementById('bh_upd_pu');
                const updMode = document.getElementById('bh_upd_mode');
                const updNote = document.getElementById('bh_upd_note');

                // Details modal
                const modalId = document.getElementById('bh_modal_id');
                const modalBodyTbody = document.getElementById('bh_modal_tbody');
                const modalTotal = document.getElementById('bh_modal_total_bidon');

                // Bidon lines table
                const tblBody = document.querySelector("#tableBidonTypes tbody");
                const sumLit = document.getElementById("sumLitres");
                const sumMont = document.getElementById("sumMontant");
                const qteInput = document.getElementById("quantite_en_litre");
                const puInput = document.getElementById("prix_unitaire");
                const netInput = document.getElementById("prix_net");
                const btnAdd = document.getElementById("btnAddLine");
                const select = $("#bh_add_type");

                // Statut / Mode paiement
                const wrapMode = document.getElementById('wrap_mode_paiement_huile');
                const selMode = document.getElementById('mode_paiement_huile');
                const rPaye = document.getElementById('statut_paye_huile');
                const rNonPaye = document.getElementById('statut_non_paye_huile');

                // -------------------------
                // State
                // -------------------------
                let TYPES_ROWS = [];
                let state = {
                    page: 1,
                    perPage: parseInt(perEl.value, 10) || 25,
                    totalPages: 1,
                    lastItems: []
                };
                const nf2 = new Intl.NumberFormat('fr-FR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // -------------------------
                // Helpers
                // -------------------------
                const typeToLiters = t => {
                    const m = String(t || '').toUpperCase().match(/(\d+(?:\.\d+)?)\s*L/);
                    return m ? parseFloat(m[1]) : 0;
                };
                const getRowByType = t => TYPES_ROWS.find(r => r.type_bidon === t) || {
                    prix_bidon: 0,
                    stock_restant: 0
                };

                function buildOptionsHtml() {


                    return '<option value="">-- Type --</option>' + TYPES_ROWS.map(r => `
      <option value="${r.type_bidon}" data-prix="${r.prix_bidon}" ${r.stock_restant<=0?'disabled':''}>
        ${r.type_bidon} — dispo: ${r.stock_restant} — prix: ${nf2.format(r.prix_bidon)} DH
      </option>
    `).join('');
                }

                function rowTemplate() {
                    return `
      <tr>
        <td><select name="detail_type[]" class="form-select sel-type" required>${buildOptionsHtml()}</select></td>
        <td class="text-end"><span class="cell-prix">0.00</span></td>
        <td><input type="number" step="1" min="0" class="form-control text-end inp-count" name="detail_count[]" required></td>
        <td class="text-end"><span class="cell-litres">0.00</span></td>
        <td class="text-end"><span class="cell-montant">0.00</span></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-del">Supprimer</button></td>
      </tr>`;
                }

                function recalc() {
                    let totalL = 0,
                        totalDH = 0;
                    tblBody.querySelectorAll("tr").forEach(tr => {
                        const sel = tr.querySelector(".sel-type");
                        const type = sel?.value || "";
                        const row = getRowByType(type);
                        const prix = parseFloat(row.prix_bidon || 0);

                        tr.querySelector(".cell-prix").textContent = nf2.format(prix);

                        const count = parseFloat(tr.querySelector(".inp-count")?.value || 0);
                        const liters = typeToLiters(type) * count;
                        tr.querySelector(".cell-litres").textContent = nf2.format(liters);
                        totalL += liters;

                        const montant = count * prix;
                        tr.querySelector(".cell-montant").textContent = nf2.format(montant);
                        totalDH += montant;
                    });
                    sumLit.textContent = nf2.format(totalL);
                    sumMont.textContent = nf2.format(totalDH);

                    const pu = parseFloat(puInput?.value || 0);
                    const q = parseFloat(qteInput?.value || 0);
                    netInput.value = (q * pu).toFixed(2);
                }

                function refreshModePaiementVisibility() {
                    const isCredit = rNonPaye?.checked;
                    if (isCredit) {
                        wrapMode?.classList.add('d-none');
                        selMode.selectedIndex = 0;
                        selMode?.removeAttribute('required');
                    } else {
                        wrapMode?.classList.remove('d-none');
                        if (selMode?.getAttribute('data-was-required') === '1') selMode.setAttribute('required',
                            'required');
                    }
                }

                // -------------------------
                // Load Types
                // -------------------------
                async function loadTypes() {
                    try {
                        const res = await fetch(API_TYPES);
                        const result = await res.json();
                        TYPES_ROWS = Array.isArray(result.data) ? result.data : [];
                        select.empty().append(`<option value="">— Type —</option>`);
                        TYPES_ROWS.forEach(t => {
                            const dispo = Number(t.stock_restant || 0);
                            const disabled = dispo <= 0 ? 'disabled' : '';
                            select.append(
                                `<option value="${t.id_bidon}" ${disabled}>${t.type_bidon} (dispo: ${dispo})</option>`
                            );
                        });
                        btnAdd.disabled = TYPES_ROWS.length === 0;
                    } catch (err) {
                        console.error("Erreur chargement types:", err);
                        select.empty().append(`<option value="">Erreur de chargement</option>`);
                        btnAdd.disabled = true;
                    }
                }

                // -------------------------
                // Load Bons
                // -------------------------
                function buildUrl() {
                    const p = new URLSearchParams();
                    if (qEl.value.trim() !== '') p.set('q', qEl.value.trim());
                    if (fromEl.value) p.set('date_from', fromEl.value);
                    if (toEl.value) p.set('date_to', toEl.value);
                    p.set('page', state.page);
                    p.set('perPage', state.perPage);
                    return `${API_BON}?${p.toString()}`;
                }

                async function loadBons() {
                    tbody.innerHTML =
                        `<tr><td colspan="9" class="text-center py-4"><div class="spinner-border" role="status"></div></td></tr>`;
                    try {
                        const res = await fetch(buildUrl());
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Erreur serveur');
                        state.totalPages = data.totalPages || 1;
                        state.lastItems = Array.isArray(data.items) ? data.items : [];
                        renderRows(state.lastItems);
                        renderPager(data);
                    } catch (err) {
                        tbody.innerHTML =
                            `<tr><td colspan="9" class="text-danger text-center">${err.message}</td></tr>`;
                        pagerInfo.textContent = '—';
                    }
                }

                function renderRows(items) {
                    if (!items.length) {
                        tbody.innerHTML =
                            `<tr><td colspan="9" class="text-center text-muted">Aucun résultat.</td></tr>`;
                        return;
                    }
                    let html = '',
                        sumQ = 0,
                        sumNet = 0;
                    items.forEach(r => {
                        sumQ += Number(r.quantite || 0);
                        sumNet += Number(r.prix_net || 0);
                        const modeBadge = r.mode_paiement ?
                            `<span class="badge text-bg-light">${r.mode_paiement}</span>` :
                            '<span class="badge text-bg-light">Credit</span>';
                        html += `
        <tr>
          <td>${r.date_bon||''}</td>
          <td><div class="fw-semibold">${r.client||''}</div>${r.ville?`<div class="small text-muted">${r.ville}</div>`:''}</td>
          <td class="text-end">${nf2.format(Number(r.quantite||0))}</td>
          <td class="text-end">${nf2.format(Number(r.prix_unitaire||0))}</td>
          <td class="text-end">${nf2.format(Number(r.prix_net||0))}</td>
          <td>${r.provider||''}</td>
          <td>${modeBadge}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary " data-action="print-a" data-id="${r.id_bon}"><i class="bi bi-printer"></i></button>
            <button class="btn btn-sm btn-outline-info" data-action="details" data-id="${r.id_bon}"><i class="bi bi-eye"></i></button>
            <button class="btn btn-sm btn-outline-warning" data-action="update" data-id="${r.id_bon}"><i class="bi bi-pencil-square"></i></button>
            <button class="btn btn-sm btn-outline-secondary" data-action="print" data-id="${r.id_bon}"><i class="bi bi-printer"></i></button>
          </td>
        </tr>`;
                    });
                    tbody.innerHTML = html;
                    document.getElementById('bh_sum_qte').textContent = nf2.format(sumQ);
                    document.getElementById('bh_sum_net').textContent = nf2.format(sumNet);
                }

                function renderPager(data) {
                    pagerInfo.textContent =
                        `Page ${data.page||state.page} / ${data.totalPages||1} — ${data.total||0} enregistrements`;
                    prevBtn.disabled = state.page <= 1;
                    nextBtn.disabled = state.page >= state.totalPages;
                }


                window.openBonRouteChoice = async function(bonId) {
                    // Fallback if SweetAlert2 isn't available
                    if (!window.Swal) {
                        const withQr = confirm(
                            'Générer le Bon de route avec QR ?\nOK = avec QR, Annuler = sans QR');
                        const qr = withQr ? 1 : 0;
                        window.open(
                            `../bon-Huile/generate_bon_route.php?bon_id=${encodeURIComponent(bonId)}&qr=${qr}`,
                            '_blank');
                        return;
                    }

                    const result = await Swal.fire({
                        title: 'Générer Bon de route',
                        text: 'Voulez-vous inclure un QR code ?',
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Avec Route',
                        denyButtonText: 'Sans Route',
                        cancelButtonText: 'Annuler',
                        reverseButtons: true
                    });

                    if (result.isConfirmed) {
                        // Avec QR
                        window.open(
                            `../bon-Huile/generate_bon_huile-qr.php?bon_id=${encodeURIComponent(bonId)}`,
                            '_blank');
                    } else if (result.isDenied) {
                        // Sans QR
                        window.open(
                            `../bon-Huile/generate_bon_huile.php?bon_id=${encodeURIComponent(bonId)}&qr=0`,
                            '_blank');
                    }

                };





                // -------------------------
                // Event Listeners
                // -------------------------
                document.addEventListener("DOMContentLoaded", loadTypes);
                qEl?.addEventListener('input', () => state.page = 1);
                perEl?.addEventListener('change', () => state.page = 1);

                form.addEventListener('submit', e => {
                    e.preventDefault();
                    state.page = 1;
                    state.perPage = parseInt(perEl.value, 10) || 25;
                    loadBons();
                });
                resetBtn.addEventListener('click', () => {
                    qEl.value = '';
                    fromEl.value = '';
                    toEl.value = '';
                    perEl.value = '25';
                    state.page = 1;
                    state.perPage = 25;
                    loadBons();
                });
                prevBtn.addEventListener('click', () => {
                    if (state.page > 1) {
                        state.page--;
                        loadBons();
                    }
                });
                nextBtn.addEventListener('click', () => {
                    if (state.page < state.totalPages) {
                        state.page++;
                        loadBons();
                    }
                });

                // -------------------------
// Actions sur les lignes (voir / modifier / imprimer)
// -------------------------
tbody.addEventListener('click', e => {
  const btn = e.target.closest('button[data-action]');
  if (!btn) return;
  const action = btn.dataset.action;
  const id = Number(btn.dataset.id);
  const row = state.lastItems.find(x => Number(x.id_bon) === id);
  if (!row) return;

  if (action === 'details') {
    modalId.textContent = id;
    const details = Array.isArray(row.details_bidons) ? row.details_bidons : [];
    let html = '', tot = 0;
    details.forEach(d => {
      const q = Number(d.quantite_bidon || 0);
      const p = Number(d.prix_bidon || 0);
      tot += q * p;
      html += `<tr>
        <td>${d.nom_bidon||''}</td>
        <td>${d.type_bidon||''}</td>
        <td class="text-end">${nf2.format(q)}</td>
        <td class="text-end">${nf2.format(p)}</td>
      </tr>`;
    });
    modalBodyTbody.innerHTML = html || `<tr><td colspan="4" class="text-center text-muted">Aucun détail.</td></tr>`;
    modalTotal.textContent = nf2.format(tot);
    new bootstrap.Modal(document.getElementById('bhModalDetails')).show();
  }

  else if (action === 'update') {
    // Remplir le modal
    updIdSpan.textContent = id;
    updHidden.value = id;
    updQte.value = row.quantite || '';
    updPU.value = row.prix_unitaire || '';
    updMode.value = row.mode_paiement_huile || row.mode_paiement || '';
    updNote.value = row.note || '';

    // Affiche le modal
    new bootstrap.Modal(updateModal).show();
  }

  else if (action === 'print') {
    openBonRouteChoice(id);
  }
});

// -------------------------
// Soumission du formulaire de mise à jour
// -------------------------
formUpd.addEventListener('submit', async e => {
  e.preventDefault();

  const fd = new FormData(formUpd);
  try {
    const res = await fetch('../includes/ajax/Huile/Bidon-Huile/updateBon_Huile.php', {
      method: 'POST',
      body: fd
    });
    const data = await res.json();

    if (!data.ok) throw new Error(data.error || 'Erreur serveur');

    bootstrap.Modal.getInstance(updateModal).hide();

    Swal.fire({
      icon: 'success',
      title: 'Modifié avec succès',
      text: `Le bon #${updHidden.value} a été mis à jour.`,
      timer: 1600,
      showConfirmButton: false
    });

    loadBons(); // Rafraîchir la liste
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: err.message
    });
  }
});


                // Bidon table events
                tblBody.addEventListener('input', e => {
                    if (e.target.matches(".inp-count")) recalc();
                });
                tblBody.addEventListener('change', e => {
                    if (e.target.matches(".sel-type")) recalc();
                });
                tblBody.addEventListener('click', e => {
                    if (e.target.closest(".btn-del")) {
                        e.target.closest("tr")?.remove();
                        recalc();
                    }
                });
                btnAdd?.addEventListener('click', () => {
                    tblBody.insertAdjacentHTML("beforeend", rowTemplate());
                    recalc();
                });

                // Mode paiement toggle
                rPaye?.addEventListener('change', refreshModePaiementVisibility);
                rNonPaye?.addEventListener('change', refreshModePaiementVisibility);
                refreshModePaiementVisibility();

                // Recalculate net
                qteInput?.addEventListener('input', recalc);
                puInput?.addEventListener('input', recalc);

                // Load Bons initially if tab active
                const tabBtn = document.querySelector('#piece-tab');
                if (tabBtn) {
                    tabBtn.addEventListener('shown.bs.tab', loadBons);
                    if (tabBtn.classList.contains('active')) loadBons();
                }

            })();

            document.addEventListener('DOMContentLoaded', function() {
                const statutPaye = document.getElementById('statut_paye_huile');
                const statutNonPaye = document.getElementById('statut_non_paye_huile');
                const modePaiement = document.getElementById('mode_paiement_huile');
                const imageWrap = document.getElementById('image_justification_wrap');
                const imageInput = document.getElementById('image_justification');

                function updateImageField() {
                    const mode = modePaiement.value;
                    if (statutPaye.checked && (mode === 'CHEQUE' || mode === 'VIREMENT' || mode === 'LCN')) {
                        imageWrap.style.display = 'block';
                        imageInput.required = true;
                    } else {
                        imageWrap.style.display = 'none';
                        imageInput.required = false;
                        imageInput.value = '';
                    }
                }

                statutPaye.addEventListener('change', updateImageField);
                statutNonPaye.addEventListener('change', updateImageField);
                modePaiement.addEventListener('change', updateImageField);
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('[data-action="print-a"]');
                if (!btn) return;

                const bonId = btn.dataset.id;
                if (!bonId) {
                    console.error(" ID du bon manquant !");
                    return;
                }


                window.open(
                    `../bon-Huile/generate_bon_huile_a.php?bon_id=${encodeURIComponent(bonId)}`,
                    '_blank'
                );
            });
            </script>


        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>