<?php
$activeSection = 'avances';
$activePage    = 'avances-liste_ouvrier';

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Liste des ouvriers</h3>

                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="max-width: 420px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 shadow-none"
                                autocomplete="off" />
                            <button class="btn btn-outline-secondary d-none" id="clearSearch" type="button"
                                title="Effacer">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <select class="form-select form-select-sm" id="perPage" style="width:160px">
                            <option value="10" selected>10 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                            <option value="all">Tous</option>
                        </select>

                        <button class="btn btn-outline-secondary btn-sm" id="btnRefresh" title="Rafraîchir">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:120px;">CIN</th>
                                    <th style="min-width:220px;">Nom complet</th>
                                    <th style="min-width:160px;">Fonction</th>
                                    <th style="min-width:140px;">Téléphone</th>
                                    <th>Adresse</th>
                                    <th style="width:140px;">Actions</th>
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

                        <div id="noResults" class="text-center py-4 d-none text-muted">
                            <i class="bi bi-search me-1"></i> Aucun résultat pour votre recherche.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Update-->
<div class="modal fade" id="editOuvrierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editOuvrierForm" class="modal-content needs-validation"
            action="/gestionMeknassa/includes/ajax/updateOuvrier.php" method="post" enctype="multipart/form-data"
            novalidate>
            <div class="modal-header">
                <p class="mb-2 text-muted">
                    Modification des informations de
                    <span class="fw-semibold">l'ouvrier :</span>
                    <span id="o-nom-title" class="fw-bold text-dark"></span>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <span class="fw-semibold">CIN :</span> <span id="o-cin-title" class="fw-bold text-dark"></span>
                </p>

            </div>

            <div class="modal-body">
                <input type="hidden" name="id_ouvrier" id="edit_id">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="edit_prenom" class="form-label">Modifier le prénom</label>
                        <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_nom" class="form-label">Modifier le nom</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" required>
                    </div>


                    <div class="col-md-6">
                        <label for="edit_cin" class="form-label">Modifier le CIN</label>
                        <input type="text" class="form-control" id="edit_cin" name="cin" required>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_fonction" class="form-label">Modifier la fonction</label>
                        <select class="form-select" id="edit_fonction" name="fonction" required>
                            <option value="" selected disabled>Sélectionner </option>
                               <option value="Administration">	Administration</option>
                            <option value="Technicien des machines">Technicien des machines</option>
                            <option value="Agent de service général">Agent de service général</option>
                            <option value="Contrôleur">Contrôleur</option>
                            <option value="Gardien">Gardien</option>
                            <option value="Gérant">Gérant</option>
                            <option value="Porteur">Porteur</option>
                            <option value="Chauffeur">Chauffeur</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_tele" class="form-label">Modifier le numéro de téléphone</label>
                        <input type="text" class="form-control" id="edit_tele" name="tele" maxlength="10">
                    </div>

                    <div class="col-md-6">
                        <label for="edit_adress" class="form-label">Modifier l’adresse</label>
                        <textarea class="form-control" id="edit_adress" name="adress" rows="1"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Photo existante</label>
                        <div id="o-photo-wrap"
                            class="border rounded p-2 d-flex align-items-center justify-content-center bg-light shadow-sm"
                            style="min-height:180px; max-height:180px; overflow:hidden;">
                            <span class="text-muted small">Aucune photo</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">scan du CIN existant</label>
                        <div id="currentImgCin"
                            class="border rounded p-2 d-flex align-items-center justify-content-center bg-light shadow-sm"
                            style="min-height:180px; max-height:180px; overflow:hidden;">
                            <span class="text-muted small">Aucun scan disponible</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-6 d-flex flex-column">
                                <label for="edit_photo" class="form-label">Modifier la photo</label>
                                <input type="file" class="form-control" id="edit_photo" name="photo"
                                    accept=".jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-6 d-flex flex-column">
                                <label for="edit_img_cin" class="form-label">Modifier le scan du CIN</label>
                                <input type="file" class="form-control" id="edit_img_cin" name="img_cin"
                                    accept=".jpg,.jpeg,.png">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-dark" data-bs-dismiss="modal" type="button">Annuler</button>
                <button class="btn btn-success" type="submit">Enregistrer la modification</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Voir Ouvrier -->
<div class="modal fade" id="viewOuvrierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Informations de l’ouvrier</h5>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Nom complet :</strong> <span id="v-fullname"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>CIN :</strong> <span id="v-cin"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Fonction :</strong> <span id="v-fonction"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Téléphone :</strong> <span id="v-tele"></span>
                    </div>
                    <div class="col-12">
                        <strong>Adresse :</strong> <span id="v-adress"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Photo :</strong>
                        <div id="v-photo" class="border p-2 text-center" style="min-height:200px;">
                            <span class="text-muted small">Aucune photo</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <strong>Scan CIN :</strong>
                        <div id="v-cin-img" class="border p-2 text-center" style="min-height:200px;">
                            <span class="text-muted small">Aucun scan disponible</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>


<?php require __DIR__ . '/../includes/scripts.php'; ?>

<script>
(function() {
    const API = '/gestionMeknassa/includes/ajax/list_ouvriers.php';
    const UPDATE_URL = '/gestionMeknassa/includes/ajax/updateOuvrier.php';

    const PUBLIC_IMG_BASE_CIN = '/gestionMeknassa/uploads/scanne-cin/';
    const PUBLIC_IMG_BASE_PHOTO = '/gestionMeknassa/uploads/photo-ouvrier/';

    let page = 1,
        lastPage = 1,
        total = 0,
        from = 0,
        to = 0;
    const $search = $('#searchInput');
    const $clear = $('#clearSearch');
    const $perPage = $('#perPage');
    const $rowsBody = $('#rowsBody');
    const $info = $('#resultInfo');
    const $prev = $('#prevBtn');
    const $next = $('#nextBtn');
    const $nores = $('#noResults');
    const $refresh = $('#btnRefresh');
    let timer = null;

    function escapeHtml(s) {
        return String(s || '')
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;').replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function buildRow(r) {
        const cin = r.cin || '—';
        const pren = r.prenom || '';
        const nom = r.nom || '';
        const fullname = (pren || nom) ? `${pren} ${nom}`.trim() : '—';
        const fonction = r.fonction || '';
        const tele = r.tele || '';
        const adress = r.adress || '';

        const imgCinName = r.img_cin ? String(r.img_cin) : '';
        const photoName = r.image ? String(r.image) : '';

        return `
      <tr data-id="${r.id_ouvrier}"
          data-imgcin="${escapeHtml(imgCinName)}"
          data-photo="${escapeHtml(photoName)}"
          data-cin="${escapeHtml(cin)}"
          data-prenom="${escapeHtml(pren)}"
          data-nom="${escapeHtml(nom)}"
          data-fonction="${escapeHtml(fonction)}"
          data-tele="${escapeHtml(tele)}"
          data-adress="${escapeHtml(adress)}">
        <td class="fw-bold text-primary">${escapeHtml(cin)}</td>
        <td class="fw-bold text-danger">${escapeHtml(fullname)}</td>
        <td>${escapeHtml(fonction)}</td>
        <td>${escapeHtml(tele)}</td>
        <td>${escapeHtml(adress)}</td>
        <td>
          <button type="button" class="btn btn-sm btn-outline-info btn-view-ouvrier" title="Voir">
             <i class="bi bi-eye"></i>
          </button>
          <button class="btn btn-sm btn-outline-success btn-edt" title="Modifier">
             <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-sm btn-outline-primary btn-print" title="Imprimer">
             <i class="bi bi-printer"></i>
          </button>
        </td>
      </tr>
    `;
    }



    function loadRows() {
        const q = $search.val().trim();
        const per = $perPage.val();
        $nores.addClass('d-none');
        $clear.toggleClass('d-none', q === '');

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
                    $nores.removeClass('d-none').text('Erreur lors du chargement.');
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
                    $nores.removeClass('d-none').html(
                        '<i class="bi bi-search me-1"></i> Aucun résultat.');
                    $prev.prop('disabled', true);
                    $next.prop('disabled', true);
                    return;
                }
                $rowsBody.html(rows.map(buildRow).join(''));
                $info.text(`Affichage ${from}–${to} sur ${total}`);
                $prev.prop('disabled', page <= 1);
                $next.prop('disabled', page >= lastPage);
            },
            error: function(xhr, status, err) {
                $rowsBody.html('');
                $info.text('—');
                $nores.removeClass('d-none').text('Erreur serveur.');
                $prev.prop('disabled', true);
                $next.prop('disabled', true);
                console.error('loadRows error', status, err, xhr && xhr.responseText);
            }
        });
    }


    function testAndShow(url, $container, filenameEl) {
        const img = new Image();
        img.onload = function() {
            $container.html(
                `<a href="${url}" target="_blank" rel="noopener"><img src="${url}" alt="" style="max-width:180px;max-height:140px;border-radius:6px;" class="shadow-sm"></a>`
            );
            if (filenameEl) filenameEl.textContent = decodeURIComponent(url.split('/').pop());
        };
        img.onerror = function() {
            $container.html('<span class="text-danger small">Fichier introuvable</span>');
            if (filenameEl) filenameEl.textContent = '';
        };
        img.src = url;
    }
    $(document).on('click', '.btn-print', function() {
        const $tr = $(this).closest('tr');
        const id = $tr.data('id');
        if (!id) return alert('Ouvrier introuvable.');
        window.open(`/gestionMeknassa/avances/print-ouvrier.php?id=${id}`, '_blank');
    });

    $(document).on('click', '.btn-edt', function() {
        const $tr = $(this).closest('tr');
        const id = parseInt($tr.attr('data-id') || $tr.data('id') || '0', 10);

        $('#edit_id').val(id);
        $('#edit_cin').val($tr.find('td:eq(0)').text().trim());
        const fullname = $tr.find('td:eq(1)').text().trim().split(' ');
        $('#edit_prenom').val(fullname[0] || '');
        $('#edit_nom').val(fullname.slice(1).join(' ') || '');
        $('#edit_fonction').val($tr.find('td:eq(2)').text().trim());
        $('#edit_tele').val($tr.find('td:eq(3)').text().trim());
        $('#edit_adress').val($tr.find('td:eq(4)').text().trim());

        $('#o-nom-title').text(($('#edit_prenom').val() + ' ' + $('#edit_nom').val()).trim());
        $('#o-cin-title').text($('#edit_cin').val());

        const imgCinName = ($tr.attr('data-imgcin') || '').trim();
        const photoName = ($tr.attr('data-photo') || '').trim();

        const $cinWrap = $('#currentImgCin');
        const $photoWrap = $('#o-photo-wrap');
        const photoNameEl = document.getElementById('o-photo-filename');
        const cinNameEl = document.getElementById('o-cin-filename');

        $cinWrap.html('<span class="text-muted small">Chargement…</span>');
        $photoWrap.html('<span class="text-muted small">Chargement…</span>');
        if (photoNameEl) photoNameEl.textContent = '';
        if (cinNameEl) cinNameEl.textContent = '';

        if (imgCinName) {
            const url = PUBLIC_IMG_BASE_CIN + encodeURIComponent(imgCinName);
            testAndShow(url, $cinWrap, cinNameEl);
        } else {
            $cinWrap.html('<span class="text-muted small">Aucun scan disponible</span>');
            if (cinNameEl) cinNameEl.textContent = '';
        }

        if (photoName) {
            const url = PUBLIC_IMG_BASE_PHOTO + encodeURIComponent(photoName);
            testAndShow(url, $photoWrap, photoNameEl);
        } else {
            $photoWrap.html('<span class="text-muted small">Aucune photo</span>');
            if (photoNameEl) photoNameEl.textContent = '';
        }

        $('#edit_img_cin').val('');
        $('#edit_photo').val('');

        const modal = new bootstrap.Modal(document.getElementById('editOuvrierModal'));
        modal.show();
    });

    $(document).on('click', '.btn-view-ouvrier', function() {
        const $tr = $(this).closest('tr');

        const cin = $tr.attr('data-cin') || '';
        const prenom = $tr.attr('data-prenom') || '';
        const nom = $tr.attr('data-nom') || '';
        const fullname = (prenom + ' ' + nom).trim();
        const fonction = $tr.attr('data-fonction') || '';
        const tele = $tr.attr('data-tele') || '';
        const adress = $tr.attr('data-adress') || '';
        const photo = $tr.attr('data-photo') || '';
        const imgCin = $tr.attr('data-imgcin') || '';

        $('#v-cin').text(cin);
        $('#v-fullname').text(fullname);
        $('#v-fonction').text(fonction);
        $('#v-tele').text(tele);
        $('#v-adress').text(adress);

        const $vPhoto = $('#v-photo');
        const $vCinImg = $('#v-cin-img');

        if (photo) {
            const urlPhoto = '/gestionMeknassa/uploads/photo-ouvrier/' + encodeURIComponent(photo);
            $vPhoto.html(
                `<a href="${urlPhoto}" target="_blank"><img src="${urlPhoto}" style="max-height:180px;max-width:100%;" class="shadow-sm rounded"></a>`
            );
        } else {
            $vPhoto.html('<span class="text-muted small">Aucune photo</span>');
        }
        if (imgCin) {
            const urlCin = '/gestionMeknassa/uploads/scanne-cin/' + encodeURIComponent(imgCin);
            $vCinImg.html(
                `<a href="${urlCin}" target="_blank"><img src="${urlCin}" style="max-height:180px;max-width:100%;" class="shadow-sm rounded"></a>`
            );
        } else {
            $vCinImg.html('<span class="text-muted small">Aucun scan disponible</span>');
        }
        const modal = new bootstrap.Modal(document.getElementById('viewOuvrierModal'));
        modal.show();
    });

    $search.on('input', function() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            page = 1;
            loadRows();
        }, 250);
    });
    $clear.on('click', function() {
        $search.val('');
        page = 1;
        loadRows();
        $search.trigger('focus');
    });
    $perPage.on('change', function() {
        page = 1;
        loadRows();
    });
    $prev.on('click', function() {
        if (page > 1) {
            page--;
            loadRows();
        }
    });
    $next.on('click', function() {
        if (page < lastPage) {
            page++;
            loadRows();
        }
    });
    $refresh.on('click', function() {
        page = 1;
        loadRows();
    });

    $(loadRows);
})();
</script>