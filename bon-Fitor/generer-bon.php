<style>
  body {
    background: #f6f7fb;
  }

  .card {
    border-radius: 12px;
  }

  .table thead th {
    white-space: nowrap;
  }

  .badge-pill {
    border-radius: 999px;
  }

  .sticky-tools {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #fff;
  }

  .card {
    border-radius: 1rem;
  }

  .form-section-title {
    font-weight: 600;
    font-size: 1rem;
    color: #555;
  }

  .summary-box {
    background: #f8f9fa;
    border-radius: .75rem;
    padding: 1rem;
  }

  .summary-box .val {
    font-weight: 700;
    font-size: 1.1rem;
  }

  .sticky-col {
    position: sticky;
    top: 1rem;
  }
</style>

<?php

$activeSection = 'bon-Fitor';
$activePage = 'Generer-bon';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';

require_once(__DIR__ . '/../config/config.ini.php');








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
                  <button class="nav-link" id="bon-tab" data-bs-toggle="tab" data-bs-target="#tab-bon"
                    type="button" role="tab" aria-controls="tab-bon" aria-selected="true">
                    Générer un Bon de Fitor
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="piece-tab" data-bs-toggle="tab" data-bs-target="#tab-piece" type="button"
                    role="tab" aria-controls="tab-piece" aria-selected="false">
                    Liste des Bons de Fitor
                  </button>
                </li>

              </ul>
            </div>
          </div>

          <div class="tab-content mt-3" id="formTabsContent">

            <!-- Debut d'ajout du Bon de Fitor -->
            <div class="tab-pane fade show active" id="tab-bon" role="tabpanel" aria-labelledby="bon-tab">
              <div class="card card-info card-outline mb-4">
                <div class="container py-4">
                  <div class="row g-4">
                    <div class="col-lg-8">
                      <div class="card shadow-sm">
                        <div class="card-body">
                          <h4 class="mb-3">Créer un Bon Fitor</h4>



                          <form method="post" id="bon-form" class="needs-validation" novalidate>


                            <div class="row g-3 mb-2">
                              <div class="col-md-6">
                                <label class="form-label form-section-title">Patron</label>
                                <select class="form-select" name="id_patron" id="id_patron" required>
                                  <option value="">Choisir un patron...</option>
                                </select>
                                <div class="invalid-feedback">Sélectionnez un patron.</div>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label form-section-title">Chauffeur</label>
                                <select class="form-select" name="id_Fchauffeur" id="id_chauffeur" required disabled>
                                  <option value="">Choisir un chauffeur...</option>
                                </select>
                                <div class="invalid-feedback">Sélectionnez un chauffeur.</div>
                              </div>
                            </div>

                            <div class="row g-3 mb-2">
                              <div class="col-md-6">
                                <label class="form-label form-section-title">Véhicule</label>
                                <select class="form-select" name="id_vehicule" id="id_vehicule" required disabled>
                                  <option value="">Choisir un véhicule...</option>
                                </select>
                                <div class="invalid-feedback">Sélectionnez un véhicule.</div>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label form-section-title">Taratonage</label>
                                <select class="form-select" name="id_taratonage" id="id_taratonage" required disabled>
                                  <option value="">Choisir un taratonage...</option>
                                </select>
                                <div class="form-text">Seuls les taratonages non utilisés par un bon sont listés.</div>
                                <div class="invalid-feedback">Sélectionnez un taratonage.</div>
                              </div>
                            </div>

                            <div class="row g-3 mb-2 align-items-end">
                              <div class="col-md-4">
                                <label class="form-label form-section-title">Quantité (kg)</label>
                                <div class="input-group">
                                  <input type="number" step="0.001" min="0" class="form-control" name="quantite"
                                    id="quantite" required>
                                  <button type="button" id="btnUsePoidNet" class="btn btn-outline-secondary"
                                    title="Utiliser poid_net du taratonage">Poid Net</button>
                                </div>
                                <input type="hidden" name="use_poid_net" id="use_poid_net" value="0">
                                <div class="invalid-feedback">Quantité obligatoire.</div>
                              </div>
                              <div class="col-md-4">
                                <label class="form-label form-section-title">Prix initial (DH/kg)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="prix_init"
                                  id="prix_init" required>
                                <div class="invalid-feedback">Prix initial obligatoire.</div>
                              </div>
                              <div class="col-md-4">
                                <label class="form-label form-section-title">Prix Net (DH)</label>
                                <input type="text" class="form-control" id="prix_net" readonly>
                                <div class="form-text">Calculé : quantité × prix initial.</div>
                              </div>
                            </div>

                            <div class="row g-3 mb-3">
                              <div class="col-md-6">
                                <label class="form-label form-section-title">Validé par</label>
                                <select class="form-select js-choices" name="provider" id="provider">
                                  <option value="Youssef">Youssef</option>
                                  <option value="Hamid">Hamid</option>
                                  <option value="Said">Said</option>

                                </select>


                              </div>
                              <div class="col-md-6">
                                <label class="form-label form-section-title">Infos Taratonage</label>
                                <input type="text" class="form-control" id="tara_info" placeholder="Brut / Tara / Net"
                                  readonly>
                              </div>
                            </div>

                            <div class="d-flex gap-2">
                              <button type="submit" class="btn btn-primary">Enregistrer le bon</button>
                              <button type="reset" class="btn btn-outline-secondary"
                                id="btnReset">Réinitialiser</button>
                            </div>
                          </form>
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
            <!-- Fin d'ajout du Bon de Fitor -->


            <!-- debut du Liste des Bons de Fitor -->
            <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
              <div class="card card-info card-outline mb-4">
                <div class="card-header">
                  <div class="card-title">Information des Bons</div>
                </div>

                <div class="container-fluid py-4">
                  <div class="row">
                    <div class="col-12">
                      <div class="card shadow-sm">
                        <div class="card-body">
                          <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                            <h4 class="m-0">Liste des Bons Fitor</h4>
                            <div class="d-flex gap-2">
                              <input class="form-control" style="min-width:260px" id="searchBox"
                                placeholder="Recherche (patron, chauffeur, véhicule, matricule, provider)...">
                              <select class="form-select" id="perPage" style="width:140px">
                                <option value="10">10 / page</option>
                                <option value="25" selected>25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                              </select>
                              <button class="btn btn-outline-secondary" id="btnRefresh"><i class="bi bi-arrow-repeat"
                                  aria-hidden="true"></i></button>
                            </div>
                          </div>

                          <div class="table-responsive">
                            <table class="table align-middle table-hover" id="bonsTable">
                              <thead class="table-light">
                                <tr>
                                  <th>#</th>
                                  <th>Date</th>
                                  <th>Patron</th>
                                  <th>Chauffeur</th>
                                  <th>Véhicule</th>
                                  <th>Taratonage (Net kg)</th>
                                  <th>Qté (kg)</th>
                                  <th>Prix(DH/kg)</th>
                                  <th>Prix net (DH)</th>
                           
                                  <th class="text-end">Actions</th>
                                </tr>
                              </thead>
                              <tbody id="bonsBody"></tbody>
                            </table>
                          </div>

                          <div class="d-flex justify-content-between align-items-center mt-3">
                            <div><span id="resultInfo" class="text-muted"></span></div>
                            <div class="btn-group">
                              <button class="btn btn-outline-secondary" id="prevBtn">← Précédent</button>
                              <button class="btn btn-outline-secondary" id="nextBtn">Suivant →</button>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Detail Modal -->
                <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Détail Bon Fitor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                      </div>
                      <div class="modal-body">
                        <div id="detailBody">Chargement…</div>
                      </div>
                      <div class="modal-footer">
                        <a id="printLink" class="btn btn-primary" target="_blank" rel="noopener">🖨️ Reçu de Chauffeur</a>
                          <a id="printLinkPatron" class="btn btn-primary" target="_blank" rel="noopener">🖨️ Reçu du Patron</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
            <!-- Fin du Liste des Bons de Fitor -->

          </div>

        </div>
      </div>

      <script>
        (function () {
          const API = '../includes/ajax/Fitor/BonFitor/createBonFitor.php';

          const fmtMoney = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));
          const fmtQty = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(Number(n || 0));

          function showFlash(type, msg) {
            const box = $('#flash');
            box.removeClass('d-none').attr('class', '').addClass('alert alert-' + type).text(msg);
          }

          function loadPatrons() {
            $.getJSON(API + '?action=get_patrons', (res) => {
              if (!res.ok) return;
              const sel = $('#id_patron');
              sel.empty().append('<option value="">Choisir un patron...</option>');
              res.data.forEach(r => sel.append(`<option value="${r.id_patron}">${r.label}</option>`));
            });
          }

          function loadChauffeurs(id_patron) {
            $('#id_chauffeur').prop('disabled', true).html('<option value="">...</option>');
            $('#id_vehicule').prop('disabled', true).html('<option value="">...</option>');
            $('#id_taratonage').prop('disabled', true).html('<option value="">...</option>');
            if (!id_patron) return;
            $.getJSON(API + '?action=get_chauffeurs&id_patron=' + id_patron, (res) => {
              const sel = $('#id_chauffeur'); sel.empty().append('<option value="">Choisir un chauffeur...</option>');
              res.data.forEach(r => sel.append(`<option value="${r.id_Fchauffeur}">${r.label}</option>`));
              sel.prop('disabled', false);
            });
            $.getJSON(API + '?action=get_patron_solde&id_patron=' + id_patron, (res) => {
              if (!res.ok) return;
              $('#sumVersements').text(fmtMoney(res.data.total_versements));
              $('#sumBons').text(fmtMoney(res.data.total_bons));
              $('#soldePatron').text(fmtMoney(res.data.solde));
            });
          }

          function loadVehicules(id_chauffeur) {
            $('#id_vehicule').prop('disabled', true).html('<option value="">...</option>');
            $('#id_taratonage').prop('disabled', true).html('<option value="">...</option>');
            if (!id_chauffeur) return;
            $.getJSON(API + '?action=get_vehicules&id_chauffeur=' + id_chauffeur, (res) => {
              const sel = $('#id_vehicule'); sel.empty().append('<option value="">Choisir un véhicule...</option>');
              res.data.forEach(r => sel.append(`<option value="${r.id_vehicule}">${r.label}</option>`));
              sel.prop('disabled', false);
            });
          }

          function loadTaratonages(id_vehicule) {
            $('#id_taratonage').prop('disabled', true).html('<option value="">...</option>');
            if (!id_vehicule) return;
            $.getJSON(API + '?action=get_taratonages&id_vehicule=' + id_vehicule, (res) => {
              console.log(res);

              const sel = $('#id_taratonage'); sel.empty().append('<option value="">Choisir un taratonage...</option>');
              res.data.forEach(r => sel.append(`<option value="${r.id_taratonage}">${r.label}</option>`));
              sel.prop('disabled', false);
            });
          }

          function loadTaraInfo(id_tara) {
            $('#tara_info').val('');
            if (!id_tara) return;
            $.getJSON(API + '?action=get_taratonage_info&id_taratonage=' + id_tara, (res) => {
              if (!res.ok || !res.data) return;
              const d = res.data;
              $('#tara_info').val(`Brut ${fmtMoney(d.poid_brut)} | Tara ${fmtMoney(d.poid_tarat)} | Net ${fmtQty(d.poid_net)}`);
            });
          }

          function recomputeNet() {
            const q = parseFloat($('#quantite').val() || 0);
            const p = parseFloat($('#prix_init').val() || 0);
            const net = (q * p).toFixed(2);
            $('#prix_net').val(fmtMoney(net));
          }

          // Dropdown cascades
          $('#id_patron').on('change', function () { loadChauffeurs(this.value); });
          $('#id_chauffeur').on('change', function () { loadVehicules(this.value); });
          $('#id_vehicule').on('change', function () { loadTaratonages(this.value); });
          $('#id_taratonage').on('change', function () { loadTaraInfo(this.value); });

          // Use poid_net
          $('#btnUsePoidNet').on('click', function () {
            const id_t = $('#id_taratonage').val();
            if (!id_t) {
              Swal.fire({
                title: "Choisissez un Taratonage ?",
                text: "Veuillez Selectionner un Taratonage?",
                icon: "question",
                timer: 1500
              }); return;
            }
            $.getJSON(API + '?action=get_taratonage_info&id_taratonage=' + id_t, (res) => {
              if (!res.ok || !res.data) return;
              $('#quantite').val(Number(res.data.poid_net).toFixed(3));
              $('#use_poid_net').val('1');
              recomputeNet();
            });
          });

          // Net preview
          $('#quantite, #prix_init').on('input', recomputeNet);

          // Reset
          $('#btnReset').on('click', function () {
            setTimeout(() => {
              $('#id_chauffeur, #id_vehicule, #id_taratonage').prop('disabled', true).empty().append('<option value="">...</option>');
              $('#tara_info').val('');
              $('#prix_net').val('');
              $('#use_poid_net').val('0');
              $('#flash').addClass('d-none').text('');
            }, 50);
          });

          // Bootstrap validation + AJAX submit
          (function enableValidationAndAjax() {
            const form = document.getElementById('bon-form');
            form.addEventListener('submit', function (event) {
              if (!form.checkValidity()) {
                event.preventDefault(); event.stopPropagation();
              } else {
                event.preventDefault();
                const data = $(form).serialize();    
            
                           
                $.ajax({
                  url: API, method: 'POST', data: data, dataType: 'json'
                }).done((res) => {
                  if (res.ok) {
                    // showFlash('success', res.message || 'Créé.');

                    Swal.fire({
                      icon: "success",
                      title: "Ajouté",
                      text: res.message || "Créé",
                    }).then(() => {
                      if (res.status === "success");
                    });
                    if (res.totaux) {
                      $('#sumVersements').text(fmtMoney(res.totaux.total_versements));
                      $('#sumBons').text(fmtMoney(res.totaux.total_bons));
                      $('#soldePatron').text(fmtMoney(res.totaux.solde));
                    }
                    // Optional: clear quantity/price but keep selections
                    $('#quantite').val(''); $('#prix_init').val(''); $('#prix_net').val('');
                    $('#use_poid_net').val('0');
                    // Refresh taratonage list to remove the used one
                    const vid = $('#id_vehicule').val();
                    if (vid) loadTaratonages(vid);
                  } else {
                    showFlash('danger', res.error || 'Erreur inconnue');
                  }
                }).fail((xhr) => {
                  const msg = xhr.responseJSON?.error || 'Erreur serveur';

                  Swal.fire({
                    icon: "error",
                    title: "Erreur réseau",
                    text: msg || "Impossible d'envoyer la requête.",
                  });
                });
              }
              form.classList.add('was-validated');
            }, false);
          })();

          // init
          loadPatrons();
        })();



        (function () {
          const API = '../includes/ajax/Fitor/BonFitor/listBonFitor.php';
          const fmtMoney = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));
          const fmtQty = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(Number(n || 0));
          const fmtDate = (s) => s || '';

          let page = 1, perPage = 25, lastTotal = 0, lastFrom = 0, lastTo = 0, lastQuery = '';

          function loadList() {
            $('#bonsBody').html(`<tr><td colspan="11" class="text-center text-muted py-4">Chargement…</td></tr>`);
            perPage = parseInt($('#perPage').val(), 10) || 25;
            lastQuery = $('#searchBox').val().trim();

            $.getJSON(API, { action: 'list', page, per_page: perPage, q: lastQuery }, (res) => {
              if (!res.ok) { $('#bonsBody').html(`<tr><td colspan="11" class="text-center text-danger py-4">${res.error || 'Erreur'}</td></tr>`); return; }
              const rows = res.data || [];
              lastTotal = res.total || 0;
              lastFrom = res.from || 0;
              lastTo = res.to || 0;

              if (rows.length === 0) {
                $('#bonsBody').html(`<tr><td colspan="11" class="text-center text-muted py-4">Aucun résultat</td></tr>`);
                $('#resultInfo').text('0 résultat');
                return;
              }

              const html = rows.map(r => {
                const veh = `${r.nom_vehicule || ''} (${r.matricule || ''})`;
                const tara = `${fmtQty(r.poid_net)} kg`;
                return `
          <tr>
            <td>${r.id}</td>
            <td>${fmtDate(r.created_at)}</td>
            <td>${r.patron || ''}</td>
            <td>${r.chauffeur || ''}</td>
            <td>${veh}</td>
            <td>${tara}</td>
            <td>${fmtQty(r.quantite)}</td>
            <td>${fmtMoney(r.prix_init)}</td>
            <td><span class="badge text-bg-success">${fmtMoney(r.prix_net)}</span></td>
          
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1 btn-view" data-id="${r.id}">Détail</button>
              <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="../includes/ajax/Fitor/BonFitor/print/recu_bon_fitor.php?id=${r.id}">Imprimer</a>
            </td>
          </tr>
        `;
              }).join('');
              $('#bonsBody').html(html);
              $('#resultInfo').text(`${lastFrom}–${lastTo} sur ${lastTotal}`);
            }).fail(() => {
              $('#bonsBody').html(`<tr><td colspan="11" class="text-center text-danger py-4">Erreur serveur</td></tr>`);
            });
          }

          // Pagination
          $('#prevBtn').on('click', () => { if (page > 1) { page--; loadList(); } });
          $('#nextBtn').on('click', () => { if (lastTo < lastTotal) { page++; loadList(); } });
          $('#perPage').on('change', () => { page = 1; loadList(); });
          $('#btnRefresh').on('click', () => loadList());
          $('#searchBox').on('keydown', (e) => { if (e.key === 'Enter') { page = 1; loadList(); } });

          // Detail modal
          $(document).on('click', '.btn-view', function () {
            const id = $(this).data('id');
            $('#detailBody').html('Chargement…');
            $('#printLink').attr('href', '../includes/ajax/Fitor/BonFitor/print/recu_bon_fitor.php?id=' + id);
            $('#printLinkPatron').attr('href', '../includes/ajax/Fitor/BonFitor/print/recu_bon_patron_fitor.php?id=' + id);
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();

            $.getJSON(API, { action: 'detail', id }, (res) => {
              if (!res.ok) { $('#detailBody').html(`<div class="text-danger">${res.error || 'Erreur'}</div>`); return; }
              const d = res.data;

              const info = `
        <div class="row g-3">
          <div class="col-md-6">
            <div class="card">
              <div class="card-body">
                <h6 class="text-muted">Bon</h6>
                <div><strong>N°:</strong> ${d.id}</div>
                <div><strong>Date:</strong> ${fmtDate(d.created_at)}</div>
                <div><strong>Provider:</strong> ${d.provider || '-'}</div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-body">
                <h6 class="text-muted">Montants</h6>
                <div><strong>Quantité:</strong> ${fmtQty(d.quantite)} kg</div>
                <div><strong>Prix init:</strong> ${fmtMoney(d.prix_init)} DH/kg</div>
                <div><strong>Prix net:</strong> <span class="badge text-bg-success">${fmtMoney(d.prix_net)} DH</span></div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h6 class="text-muted">Chaîne Patron → Chauffeur → Véhicule → Taratonage</h6>
                <div><strong>Patron:</strong> ${d.patron || ''}</div>
                <div><strong>Chauffeur:</strong> ${d.chauffeur || ''}</div>
                <div><strong>Véhicule:</strong> ${d.nom_vehicule || ''} (${d.matricule || ''})</div>
                <div><strong>Taratonage:</strong> Brut ${fmtMoney(d.poid_brut)} — Tara ${fmtMoney(d.poid_tarat)} — Net ${fmtQty(d.poid_net)} kg</div>
                ${d.tara_note ? `<div><strong>Note tara:</strong> ${d.tara_note}</div>` : ''}
              </div>
            </div>
          </div>
        </div>
      `;
              $('#detailBody').html(info);
            }).fail(() => $('#detailBody').html(`<div class="text-danger">Erreur serveur</div>`));
          });

          // First load
          loadList();
        })();
      </script>

    </div>
  </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>