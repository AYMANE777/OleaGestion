<?php
$activeSection = 'inventaire';
$activePage    = 'inventaire-liste-reglements';

require_once __DIR__ . '/../config/config.ini.php';
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>

<!-- Modal voir -->
<div class="modal fade" id="reglementModal" tabindex="-1" aria-labelledby="reglementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reglementModalLabel">Détails du règlement</h5>

            </div>
            <div class="modal-body p-0">
                <div id="m-img-carousel-wrap" class="d-none border-bottom" style="height:420px; background:#f8f9fa;">
                    <div id="mImgCarousel" class="carousel slide h-100" data-bs-ride="false">
                        <div class="carousel-inner h-100"></div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#mImgCarousel"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"
                                style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                            <span class="visually-hidden">Précédent</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#mImgCarousel"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"
                                style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                            <span class="visually-hidden">Suivant</span>
                        </button>
                    </div>
                    <div class="small text-muted mt-2 text-center" id="m-img-count"></div>
                </div>
                <div id="m-img-fallback" class="d-flex align-items-center justify-content-center border-bottom"
                    style="height:200px;">
                    <span class="text-muted small">Aucune image</span>
                </div>

                <div id="reglementInfo" class="p-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- === MODAL SITUATION PAIEMENT (PAYÉ / AVANCE UNIQUEMENT) === -->
<div class="modal fade" id="changeModal" tabindex="-1" aria-labelledby="changeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <!-- taille standard -->
        <div class="modal-content">
            <form id="changeForm" class="needs-validation" enctype="multipart/form-data" novalidate>

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="changeModalLabel">Confirmer le paiement</h5>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id_reglement" id="chg_id">
                    <input type="hidden" id="total_general">

                    <!-- Situation -->
                    <div class="w-100 mb-3">
                        <label class="form-label fw-bold d-block mb-2">Situation de paiement</label>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_paye" id="paye_oui" value="Payé"
                                required>
                            <label class="form-check-label fw-semibold" for="paye_oui">Payé</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_paye" id="paye_avance" value="Avance"
                                required>
                            <label class="form-check-label fw-semibold" for="paye_avance">Avance</label>
                        </div>
                    </div>

                    <!-- Zone PAYÉ -->
                    <div id="payment-zone-paye" class="mt-2" style="display:none;">
                        <div id="paye-container"></div>
                        <button type="button" id="add-paye-btn" class="btn btn-sm btn-success mt-2">
                            <i class="bi bi-plus-circle"></i> Ajouter un autre paiement
                        </button>
                    </div>

                    <!-- Zone AVANCE -->
                    <div id="payment-zone-avance" class="mt-2" style="display:none;">
                        <div id="avance-container"></div>
                        <button type="button" id="add-avance-btn" class="btn btn-sm btn-primary mt-2">
                            <i class="bi bi-plus-circle"></i> Ajouter une autre avance
                        </button>
                    </div>
                </div>

                <!-- FOOTER normal comme les autres modals -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="chg_submit" class="btn btn-success">Enregistrer</button>
                </div>

            </form>
        </div>
    </div>
</div>



<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Liste des règlements fournisseurs</h3>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Recherche -->
                        <div class="input-group input-group-sm" style="max-width: 360px;">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="bi bi-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 shadow-none"
                                autocomplete="off" />
                            <button class="btn btn-outline-secondary d-none" id="clearSearch" type="button"
                                title="Effacer"><i class="bi bi-x-lg"></i></button>
                        </div>

                        <select class="form-select form-select-sm" id="perPage" style="width:180px">
                            <option value="10" selected>10 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                            <option value="all">Tous</option>
                        </select>

                        <button class="btn btn-outline-secondary btn-sm" id="btnRefresh" title="Rafraîchir"><i
                                class="bi bi-arrow-repeat"></i></button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date d’ajout</th>
                                    <th>Référence</th>
                                    <th>Fournisseur</th>
                                    <th>Situation</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="rowsBody"></tbody>
                        </table>

                        <div class="d-flex justify-content-between align-items-center p-3">
                            <div class="text-muted" id="resultInfo">—</div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm" id="prevBtn">← Précédent</button>
                                <button class="btn btn-outline-secondary btn-sm" id="nextBtn">Suivant →</button>
                            </div>
                        </div>

                        <div id="noResults" class="text-center py-4 d-none text-muted"><i class="bi bi-search me-1"></i>
                            Aucun résultat.</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/scripts.php'; ?>

<script>
(function() {
    const API = '/gestionMeknassa/includes/ajax/listReglements.php';
    const CHANGE_API = '/gestionMeknassa/includes/ajax/changeSituation.php';
    let page = 1,
        lastPage = 1,
        total = 0,
        from = 0,
        to = 0;
    let timer = null;

    const $search = $('#searchInput'),
        $clear = $('#clearSearch');
    const $perPage = $('#perPage'),
        $rowsBody = $('#rowsBody');
    const $info = $('#resultInfo'),
        $prev = $('#prevBtn'),
        $next = $('#nextBtn');
    const $nores = $('#noResults'),
        $refresh = $('#btnRefresh');

    function esc(s) {
        return String(s || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll(
            '"', '&quot;').replaceAll("'", '&#039;');
    }

    function buildCarousel(urls) {
        const $wrap = $('#m-img-carousel-wrap');
        const $inner = $('#mImgCarousel .carousel-inner');
        $inner.html('');
        if (!urls.length) {
            $wrap.addClass('d-none');
            $('#m-img-fallback').removeClass('d-none');
            return;
        }
        $('#m-img-fallback').addClass('d-none');
        $wrap.removeClass('d-none');
        urls.forEach((url, i) => {
            $inner.append(`
        <div class="carousel-item ${i===0?'active':''}">
          <a href="${url}" target="_blank" rel="noopener">
            <img src="${url}" class="d-block w-100 h-100" style="object-fit:contain; max-height:420px;">
          </a>
        </div>
      `);
        });

    }

    function loadRows() {
        const q = $search.val().trim();
        const per = $perPage.val();
        $nores.addClass('d-none');

        $.ajax({
            url: API,
            method: 'POST',
            dataType: 'json',
            data: {
                page,
                per_page: per,
                q
            },
            success: function(res) {
                if (!res || !res.ok) {
                    $rowsBody.html('');
                    $info.text('—');
                    $nores.removeClass('d-none').text('Erreur serveur');
                    $prev.prop('disabled', true);
                    $next.prop('disabled', true);
                    return;
                }
                total = res.total || 0;
                from = res.from || 0;
                to = res.to || 0;
                lastPage = res.last_page || 1;
                const rows = res.rows || [];
                if (rows.length === 0) {
                    $rowsBody.html('');
                    $info.text('0 résultat');
                    $nores.removeClass('d-none');
                    $prev.prop('disabled', true);
                    $next.prop('disabled', true);
                    return;
                }

                const html = rows.map(r => {
                    const datetime = r.date_fmt || '';
                    const isPaid = String(r.situation || '').toLowerCase() === 'payé';
                    const sitLower = String(r.situation || '').toLowerCase();
                    let situationLabel = '';

                    if (sitLower === 'payé' || sitLower === 'paye') {
                        situationLabel = '<span class="badge bg-success px-3 py-2">Payé</span>';
                    } else if (sitLower === 'avance') {
                        situationLabel =
                            '<span class="badge bg-primary px-3 py-2">Avance</span>';
                    } else {
                        situationLabel =
                            '<span class="badge bg-danger px-3 py-2">Non payé</span>';
                    }


                    return `
  <tr>
    <td><span class="text-danger">${esc(datetime)}</span></td>
    <td><span class="text-success fw-bold">${esc(r.ref)}</span></td>
    <td>${esc(r.fournisseur)}</td>
    <td>${situationLabel}</td>
    <td class="text-nowrap">
      <button class="btn btn-sm btn-outline-info btn-view-piece me-2"
              data-id="${r.id_reglement}"
              data-ref="${esc(r.ref)}"
              data-date="${esc(datetime)}"
              data-fournisseur="${esc(r.fournisseur)}"
              data-situation="${esc(r.situation||'')}"
              data-payment="${esc(r.payment_mode||'')}"
              data-prix="${esc(r.prix_total||0)}"
              data-img-facture="${esc(r.img_facture||'')}"
              data-img-justification="${esc(r.img_justification||'')}"
              data-paye="${esc(r.total_paye||0)}"
              title="Voir"><i class="bi bi-eye"></i></button>

      <button class="btn btn-sm btn-outline-danger btn-change me-2" 
              data-id="${r.id_reglement}"
              data-situation="${esc(r.situation||'')}"
              data-payment="${esc(r.payment_mode||'')}"
              title="Confirmer le paiement"
              ${isPaid ? 'disabled aria-disabled="true"':''}>
        <i class="bi bi-arrow-left-right"></i>
      </button>

      <a href="/gestionMeknassa/inventaire/print_reglement.php?id=${r.id_reglement}" 
         target="_blank" class="btn btn-sm btn-outline-primary" title="Imprimer">
         <i class="bi bi-printer"></i>
      </a>
    </td>
  </tr>`;
                }).join('');


                $rowsBody.html(html);
                $info.text(`Affichage ${from}-${to} sur ${total}`);
                $prev.prop('disabled', page <= 1);
                $next.prop('disabled', page >= lastPage);
            },
            error: function() {
                $rowsBody.html('');
                $info.text('—');
                $nores.removeClass('d-none').text('Erreur serveur');
                $prev.prop('disabled', true);
                $next.prop('disabled', true);
            }
        });

        $clear.toggleClass('d-none', $search.val().trim() === '');
    }

    $search.on('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            page = 1;
            loadRows();
        }, 250);
    });
    $clear.on('click', () => {
        $search.val('');
        page = 1;
        loadRows();
        $search.trigger('focus');
    });
    $perPage.on('change', () => {
        page = 1;
        loadRows();
    });
    $prev.on('click', () => {
        if (page > 1) {
            page--;
            loadRows();
        }
    });
    $next.on('click', () => {
        if (page < lastPage) {
            page++;
            loadRows();
        }
    });
    $refresh.on('click', () => {
        page = 1;
        loadRows();
    });


    $(document).on('click', '.btn-view, .btn-view-piece', function() {
        const $b = $(this);
        const ref = $b.data('ref');
        const date = $b.data('date');
        const fournisseur = $b.data('fournisseur');
        const situation = String($b.data('situation') || '').trim();
        const payment = $b.data('payment');
        const img_facture = ($b.data('img-facture') || '').trim();
        const img_just = ($b.data('img-justification') || '').trim();
        const prix = parseFloat($b.data('prix') || 0);
        const paye = parseFloat($b.data('paye') || 0);
        const reste = (prix - paye).toFixed(2);


        let situationBadge = '';
        const sitLower = situation.toLowerCase();

        if (sitLower === 'payé' || sitLower === 'paye') {
            situationBadge = `<span class="badge bg-success px-3 py-2">Payé</span>`;
        } else if (sitLower === 'avance') {
            situationBadge = `<span class="badge bg-primary px-3 py-2">Avance</span>`;
        } else {
            situationBadge = `<span class="badge bg-danger px-3 py-2">Non payé</span>`;
        }


const urls = [];

(img_facture || '').split(',').forEach(f => {
  f = f.trim();
  if (f) urls.push('/gestionMeknassa/uploads/reglements/' + f);
});

(img_just || '').split(',').forEach(f => {
  f = f.trim();
  if (f) urls.push('/gestionMeknassa/uploads/justification_paiment/' + f);
});

buildCarousel(urls);


        // === Contenu HTML du modal
       $('#reglementInfo').html(`
  <table class="table table-sm table-borderless mb-0">
    <tbody>
      <tr>
        <td><strong>Date ajout :</strong> <span class="text-danger">${esc(date)}</span></td>
        <td><strong>Référence :</strong> <span class="text-success fw-bold">${esc(ref)}</span></td>
        <td><strong>Fournisseur :</strong> ${esc(fournisseur)}</td>
      </tr>
      <tr>
        <td><strong>Situation :</strong> ${situationBadge}</td>
      </tr>
    </tbody>
  </table>

  <hr class="my-2">

  <table class="table table-sm table-bordered text-center align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Total à payer</th>
        <th>Total versé</th>
        <th>Reste à payer</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="fw-bold text-danger">${prix.toFixed(2)} MAD</td>
        <td class="fw-bold text-success">${paye.toFixed(2)} MAD</td>
        <td class="fw-bold ${reste > 0 ? 'text-primary' : 'text-success'}">${reste} MAD</td>
      </tr>
    </tbody>
  </table>
`);
        $('#reglementModal').modal('show');
    });


    $(document).on('click', '.btn-change', function() {
        const id = $(this).data('id');
        const situation = String($(this).data('situation') || '').trim();
        const total = parseFloat($(this).data('prix') || 0);
        $('#chg_id').val(id);
        $('#total_general').val(total);
        $('#paye-container').empty();
        $('#avance-container').empty();
        $('#payment-zone-paye, #payment-zone-avance').hide();
        $('input[name="is_paye"]').prop('checked', false);

        if (situation.toLowerCase() === 'payé') {
            Swal.fire({
                icon: 'info',
                title: 'Déjà payé',
                text: 'Ce règlement est déjà marqué comme Payé. Aucune modification possible.'
            });
            return;
        }

        $('#changeModal').modal('show');
    });


    const payeContainer = document.getElementById('paye-container');
    const avanceContainer = document.getElementById('avance-container');
    const addPayeBtn = document.getElementById('add-paye-btn');
    const addAvanceBtn = document.getElementById('add-avance-btn');
    const zonePaye = document.getElementById('payment-zone-paye');
    const zoneAvance = document.getElementById('payment-zone-avance');

    function createPaymentBlock(type) {
        const isPaye = type === 'paye';
        return `
  <div class="row g-3 align-items-end mb-2 payment-block">
    <div class="col-md-4">
      <label class="form-label small">${isPaye ? 'Mode de paiement' : 'Mode d’avance'}</label>
      <select name="paiements_${type}_mode[]" class="form-control payment-mode" required>
        <option value="" disabled selected>Choisir</option>
        <option value="Especes">Espèces</option>
        <option value="Cheque">Chèque</option>
        <option value="Virement">Virement</option>
        <option value="LCN">LCN</option>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label small">${isPaye ? 'Montant payé (DH)' : 'Montant avance (DH)'}</label>
      <input type="number" step="0.01" min="0" class="form-control montant-field" name="paiements_${type}_montant[]" required>
    </div>

    <div class="col-md-3 justif-wrap" style="display:none;">
      <label class="form-label small">Justificatif (${isPaye ? 'paiement' : 'avance'})</label>
      <input type="file" class="form-control" name="paiements_${type}_justif[]" accept=".jpg,.jpeg,.png">
    </div>

    <div class="col-md-1 text-center">
      <button type="button" class="btn btn-outline-danger btn-sm remove-pay-btn" title="Supprimer">
        <i class="bi bi-trash"></i>
      </button>
    </div>
  </div>`;
    }

    $('input[name="is_paye"]').on('change', function() {
        const val = $(this).val();

        $('#paye-container').empty();
        $('#avance-container').empty();
        $('#payment-zone-paye, #payment-zone-avance').hide();

        if (val === 'Payé') {
            zonePaye.style.display = 'block';
            $(payeContainer).append(createPaymentBlock('paye'));
        } else if (val === 'Avance') {

            zoneAvance.style.display = 'block';
            $(avanceContainer).append(createPaymentBlock('avance'));
        }

        const formEl = document.getElementById('changeForm');
        if (formEl) {
            formEl.querySelectorAll('input[type="file"]').forEach(f => f.value = '');
        }

    });

    addPayeBtn.addEventListener('click', () => $(payeContainer).append(createPaymentBlock('paye')));
    addAvanceBtn.addEventListener('click', () => $(avanceContainer).append(createPaymentBlock('avance')));

    $(document).on('click', '.remove-pay-btn', function() {
        $(this).closest('.payment-block').remove();
    });

    $(document).on('change', '.payment-mode', function() {
        const mode = $(this).val();
        const justifWrap = $(this).closest('.payment-block').find('.justif-wrap');
        if (['Cheque', 'Virement', 'LCN'].includes(mode)) justifWrap.show();
        else justifWrap.hide();
    });

    $(document).on('input', '.montant-field', function() {
        const total = parseFloat($('#total_general').val() || 0);
        let somme = 0;
        $('#avance-container .montant-field').each(function() {
            somme += parseFloat($(this).val() || 0);
        });
        if (somme >= total && total > 0) {
            Swal.fire({
                icon: 'info',
                title: 'Total atteint',
                text: 'Les avances atteignent le total général. Passage automatique à Payé.',
                timer: 3000,
                showConfirmButton: false
            });
            $('#paye_oui').prop('checked', true).trigger('change');
        }
    });



    $('#changeForm').on('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const situation = $('input[name="is_paye"]:checked').val();

        if (!situation) {
            Swal.fire({
                icon: 'warning',
                title: 'Choix requis',
                text: 'Veuillez sélectionner la situation.'
            });
            return;
        }

        Swal.fire({
            title: 'Enregistrement...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: '/gestionMeknassa/includes/ajax/changeSituation.php',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(res => {
            Swal.close();

            if (res.ok || res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: res.message || 'Mise à jour effectuée.'
                });
                $('#changeModal').modal('hide');
                loadRows();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: res.message || res.error || 'Erreur serveur.'
                });
            }

        }).fail(xhr => {
            Swal.close();

            let msg = "Impossible d’enregistrer.";
            try {
                const j = JSON.parse(xhr.responseText);
                if (j && j.message) msg = j.message;
            } catch (e) {
                console.error("Erreur de parsing JSON:", e, xhr.responseText);
            }

            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: msg
            });
        });
    });

    function submitChange(fd, form) {
        const $btn = $(form).find('button[type="submit"]');
        const oldHtml = $btn.html();
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…');
        Swal.fire({
            title: 'Enregistrement...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: CHANGE_API,
            method: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            dataType: 'json',
            cache: false
        }).done(function(res) {
            Swal.close();
            const ok = res && (res.status === 'success' || res.ok === true);
            Swal.fire({
                icon: ok ? 'success' : 'error',
                title: ok ? 'Succès' : 'Erreur',
                text: res && (res.message || res.error) ? (res.message || res.error) : (ok ?
                    'Mis à jour.' : 'Erreur serveur.')
            }).then(() => {
                if (ok) {
                    const modalEl = document.getElementById('changeModal');
                    if (modalEl)(bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(
                        modalEl)).hide();
                    form.reset();
                    loadRows();
                }
            });
        }).fail(function(xhr) {
            Swal.close();
            let msg = "Impossible d'envoyer la requête.";
            try {
                const j = JSON.parse(xhr.responseText);
                if (j && j.message) msg = j.message;
            } catch {}
            Swal.fire({
                icon: 'error',
                title: 'Erreur réseau',
                text: msg
            });
        }).always(function() {
            $btn.prop('disabled', false).html(oldHtml);
        });
    }

    $(loadRows);
})();
</script>