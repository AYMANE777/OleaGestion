<?php
$activeSection = 'avances';
$activePage    = 'avances-liste_avances';

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>
<style>
#rowsBody td:nth-child(1) {
    color: red;
    font-weight: bold;
}

#rowsBody td:nth-child(2) {
    color: blue;
    font-weight: bold;
}
</style>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Liste des avances</h3>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Recherche -->
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

                        <!-- Par page -->
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
                                    <th style="min-width:140px;">Date</th>
                                    <th style="min-width:120px;">CIN</th>
                                    <th style="min-width:220px;">Ouvrier</th>
                                    <th style="min-width:120px;">Montant</th>
                                    <th>Motif</th>
                                    <th style="width:120px;"></th>
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

<?php require __DIR__ . '/../includes/scripts.php'; ?>

<script>
(function() {
    const API = '/gestionMeknassa/includes/ajax/list_avances.php';

    let page = 1;
    let lastPage = 1;
    let total = 0;
    let from = 0;
    let to = 0;

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
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
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
                    $nores.removeClass('d-none')
                        .html('<i class="bi bi-search me-1"></i> Aucun résultat pour votre recherche.');
                    $prev.prop('disabled', true);
                    $next.prop('disabled', true);
                    return;
                }

                const html = rows.map(r => {
                    const dt = r.date_fmt || '';
                    const cin = r.cin || '—';
                    const pren = r.prenom || '';
                    const nom = r.nom || '';
                    const nomDisplay = (pren || nom) ? `${pren} ${nom}`.trim() : '—';
                    const montant = (typeof r.montant !== 'undefined') ? Number(r.montant)
                        .toFixed(2) : '0.00';
                    const motif = r.Motif || '';

                    return `
            <tr>
              <td class="text-nowrap">${escapeHtml(dt)}</td>
              <td class="text-wrap">${escapeHtml(cin)}</td>
              <td class="text-break">${escapeHtml(nomDisplay)}</td>
              <td class="fw-semibold">${escapeHtml(montant)} MAD</td>
              <td class="text-break">${escapeHtml(motif)}</td>
              <td class="text-center text-nowrap">
                <button class="btn btn-sm btn-outline-primary btn-print" data-id="${r.id_avance}" title="Imprimer">
                  <i class="bi bi-printer"></i>
                </button>
              </td>
            </tr>
          `;
                }).join('');

                $rowsBody.html(html);
                $info.text(`Affichage ${from}–${to} sur ${total}`);
                $prev.prop('disabled', page <= 1);
                $next.prop('disabled', page >= lastPage);
            },
            error: function() {
                $rowsBody.html('');
                $info.text('—');
                $nores.removeClass('d-none').text('Erreur serveur.');
                $prev.prop('disabled', true);
                $next.prop('disabled', true);
            }
        });
    }

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

    $(document).on('click', '.btn-print', function() {
        const id = $(this).data('id');
        if (!id) return;
        const url = '/gestionMeknassa/avances/print_avance.php?id=' + encodeURIComponent(id);
        window.open(url, '_blank');
    });

    $(loadRows);
})();
</script>