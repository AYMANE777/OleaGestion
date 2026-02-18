<?php
$activeSection = 'inventaire';
$activePage    = 'inventaire-mouvements-machines';

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Historique des changements de statut des machines</h3>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Recherche -->
                        <div class="input-group input-group-sm" style="max-width: 360px;">
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
                        <select class="form-select form-select-sm" id="perPage" style="width:180px">
                            <option value="10" selected>10 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                            <option value="all">Tous les résultats</option>
                        </select>

                        <!-- Rafraîchir -->
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
                                    <th style="min-width:160px;">Date</th>
                                    <th style="min-width:220px;">Machine</th>
                                    <th style="min-width:140px;">Statut</th>
                                    <th>Description</th>
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

                        <!-- Aucun résultat -->
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
    const API = '/gestionMeknassa/includes/ajax/list_mouvements_statut_machines.php';

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

    function badgeClass(stat) {
        const s = String(stat || '').trim().toLowerCase();
        if (s === 'en_marche') return 'text-bg-success';
        if (s === 'en_panne') return 'text-bg-danger';
        if (s === 'en_maintenance') return 'text-bg-warning';
        return 'text-bg-secondary';
    }

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
                    const nom = r.nom_machine || '';
                    const ref = r.ref_machine || '';
                    const stat = r.statut || '-';
                    const bCls = badgeClass(stat);
                    const desc = r.description || '';

                    return `
            <tr>
              <td class="text-nowrap">${escapeHtml(dt)}</td>
              <td class="text-break">
                <div class="fw-semibold">${escapeHtml(nom)}</div>
                <div class="small text-muted">Réf : ${escapeHtml(ref)}</div>
              </td>
              <td><span class="badge ${bCls}">${escapeHtml(stat)}</span></td>
              <td class="text-break">${escapeHtml(desc)}</td>
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

        $clear.toggleClass('d-none', $search.val().trim() === '');
    }

    $search.on('input', function() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            page = 1;
            loadRows();
        }, 250);
    });

    // Effacer recherche
    $clear.on('click', function() {
        $search.val('');
        page = 1;
        loadRows();
        $search.trigger('focus');
    });

    // Par page
    $perPage.on('change', function() {
        page = 1;
        loadRows();
    });

    // Pagination
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

    // Rafraîchir
    $refresh.on('click', function() {
        page = 1;
        loadRows();
    });

    // Chargement initial
    $(loadRows);
})();
</script>