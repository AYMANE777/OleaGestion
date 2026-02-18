<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>

<script src="<?= defined("APP_BASE") ? APP_BASE : "" ?>/assets/js/adminlte.js"></script>
<!-- cherche -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
const Default = {
    scrollbarTheme: 'os-theme-light',
    scrollbarAutoHide: 'leave',
    scrollbarClickScroll: true
};
document.addEventListener('DOMContentLoaded', function() {
    const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
    const isMobile = window.innerWidth <= 992;
    if (sidebarWrapper && window.OverlayScrollbarsGlobal?.OverlayScrollbars && !isMobile) {
        OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
                theme: Default.scrollbarTheme,
                autoHide: Default.scrollbarAutoHide,
                clickScroll: Default.scrollbarClickScroll
            }
        });
    }
});
// cherche
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-choices').forEach(function(el) {
        const inst = new Choices(el, {
            searchEnabled: true,
            placeholderValue: "Sélectionner",
            searchPlaceholderValue: "Rechercher par nom",
            itemSelectText: "",
            shouldSort: false,
            noResultsText: "Aucun résultat trouvé ",
            noChoicesText: "Aucune option disponible",
            fuseOptions: {
                threshold: 0.0,
                location: 0,
                distance: 0,
                isCaseSensitive: false
            }
        });
        el.choicesInstance = inst;
    });
});

// Barre de recherche
const normalize = (s) => (s || '')
    .toString()
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase();

(function() {
    // Guard: only run when the search UI is present on this page
    const $input = document.getElementById('searchInput');
    const $table = document.getElementById('ordersTable');
    if (!$input || !$table) return;

    const $clear = document.getElementById('clearSearch');
    const $tbody = $table.querySelector('tbody');
    const $nores = document.getElementById('noResults');

    let timer = null;

    function applyFilter() {
        const q = normalize($input.value);
        let visibleCount = 0;

        Array.from($tbody.rows).forEach((tr) => {
            const rowText = normalize(tr.innerText);
            const show = q === '' || rowText.includes(q);
            tr.classList.toggle('d-none', !show);
            if (show) visibleCount += 1;
        });

        // Bouton effacer
        if ($clear) $clear.classList.toggle('d-none', $input.value.trim() === '');

        // aucun résultat
        if ($nores) $nores.classList.toggle('d-none', visibleCount > 0);
    }

    $input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(applyFilter, 120);
    });

    // Effacer la recherche
    if ($clear) {
        $clear.addEventListener('click', () => {
            $input.value = '';
            applyFilter();
            $input.focus();
        });
    }

    applyFilter();
})();



// modal machine update
// Diaporama 
function buildMachineCarousel(images) {
  const $wrap = $('#m-img-carousel-wrap');
  const $inner = $('#mImgCarousel .carousel-inner');
  const $fallback = $('#m-img-fallback');
  const $count = $('#m-img-count');

  $inner.empty();

  if (!images || !images.length) {
    $wrap.addClass('d-none');
    $fallback.removeClass('d-none');
    $count.text('');
    return;
  }

  images.forEach((url, idx) => {
    const active = idx === 0 ? 'active' : '';
    $inner.append(`
      <div class="carousel-item ${active}">
        <div class="d-flex align-items-center justify-content-center"
             style="width:100%;height:100px;overflow:hidden;">
          <img src="${url}" alt="Image ${idx+1}" class="d-block"
               style="max-width:100%;max-height:100%;object-fit:cover;">
        </div>
      </div>
    `);
  });

  $count.text(images.length + (images.length > 1 ? ' images' : ' image'));
  $fallback.addClass('d-none');
  $wrap.removeClass('d-none');

  const el = document.getElementById('mImgCarousel');
  if (window.bootstrap && el) {
    (bootstrap.Carousel.getInstance(el) || new bootstrap.Carousel(el, { interval: false })).to(0);
  }
}

// Modal Machine Update 
$(document).on('click', '.btn-edit', function () {
  const $btn = $(this);

  const id   = $btn.data('id');
  const ref  = $btn.data('ref') || '';
  const nom  = $btn.data('nom') || '';
  const desc = $btn.data('desc') || '';

  const imgsStr = ($btn.data('imgs') || '').toString();
  const base    = ($btn.data('imgbase') || '').toString();

  const four    = String($btn.data('four') || '');
  const fourname= ($btn.data('fourname') || '').toString();

  const $modal = $('#editMachineModal');

  $modal.find('#e-id').val(id);
  $modal.find('#e-ref').val(ref);
  $modal.find('#e-nom').val(nom);
  $modal.find('#e-desc').val(desc);

  $modal.find('#e-ref-title').text(ref || '');
  $modal.find('#e-nom-title').text(nom || '(Sans nom)');

  const $fourSel = $modal.find('#e-fournisseur');
  if ($fourSel[0]?.choicesInstance) {
    const inst = $fourSel[0].choicesInstance;
    const exists = [...$fourSel[0].options].some(o => o.value === four);
    if (!exists && four) {
      inst.setChoices([{ value: four, label: (fourname || `Fournisseur #${four}`), selected: true }],
                      'value','label', true);
    } else {
      inst.setChoiceByValue(four);
    }
  } else {
    if ($fourSel.find(`option[value="${four}"]`).length === 0 && four) {
      $fourSel.prepend(new Option(fourname || `Fournisseur #${four}`, four, true, true));
    } else {
      $fourSel.val(four);
    }
  }

  // Diaporama
  let urls = [];
  if (imgsStr.trim().length) {
    urls = imgsStr.split('||').map(s => s.trim()).filter(Boolean).map(name => base + name);
    buildMachineCarousel(urls);
  } else {
    $('#m-img-carousel-wrap').addClass('d-none');
    $('#m-img-fallback').removeClass('d-none').html('<span class="text-muted small">Aucune image</span>');
  }
  $modal.find('#e-img-file').val('');
});

// statut machine 
function statutBadgeClass(s) {
  switch ((s || '').trim()) {
    case 'en_marche':      return 'text-bg-success';
    case 'en_panne':       return 'text-bg-danger';
    case 'en_maintenance': return 'text-bg-warning';
    default:               return 'text-bg-secondary';
  }
}

$(document).on('click', '.btn-status-machine', function () {
  const $btn    = $(this);
  const id      = parseInt($btn.data('id') || 0, 10);
  const ref     = String($btn.data('ref') || '');
  const nom     = String($btn.data('nom') || '');
  const statut  = String($btn.data('statut') || '');

  const $m = $('#statusMachineModal');

  $m.find('#st-mach-id').val(id);
  $m.find('#st-mach-ref').text(ref || '—');
  $m.find('#st-mach-nom').text(nom || '(Sans nom)');

  const $badge = $m.find('#st-mach-current');
  $badge
    .removeClass('text-bg-success text-bg-danger text-bg-warning text-bg-secondary')
    .addClass(statutBadgeClass(statut))
    .text(statut || '-');
  $m.find('input[name="statut"]').prop('checked', false);
  if (statut) {
    $m.find(`input[name="statut"][value="${statut}"]`).prop('checked', true);
  }
  $m.find('#st-mach-desc').val('');
});



// voire 
function buildMachineViewCarousel(urls) {
  const $wrap     = $('#mv-img-carousel-wrap');
  const $inner    = $('#mvImgCarousel .carousel-inner');
  const $fallback = $('#mv-img-fallback');

  $inner.empty();

  if (!urls || !urls.length) {
    $wrap.addClass('d-none');
    $fallback.removeClass('d-none');
    return;
  }

  urls.forEach((url, idx) => {
    const active = idx === 0 ? 'active' : '';
    $inner.append(`
      <div class="carousel-item ${active} h-100">
        <div class="w-100 h-100 d-flex align-items-center justify-content-center overflow-hidden">
          <img src="${url}" alt="Image ${idx+1}" class="d-block"
               style="max-width:100%; max-height:100%; object-fit:cover;">
        </div>
      </div>
    `);
  });

  $fallback.addClass('d-none');
  $wrap.removeClass('d-none');

  const el = document.getElementById('mvImgCarousel');
  if (window.bootstrap && el) {
    (bootstrap.Carousel.getInstance(el) || new bootstrap.Carousel(el, { interval: false })).to(0);
  }
}

$(document).on('click', '.btn-view', function () {
  const $btn  = $(this);

  const id     = parseInt($btn.data('id') || 0, 10);
  const ref    = String($btn.data('ref') || '');
  const nom    = String($btn.data('nom') || '(Sans nom)');
  const desc   = String($btn.data('desc') || '');
  const statut = String($btn.data('statut') || '');
  const four   = String($btn.data('fourname') || '');

  let urls = [];
  const imgsRaw = String($btn.data('imgs') || '').trim();
  const imgBase = String($btn.data('imgbase') || '');
  const single  = String($btn.data('img') || ''); 

  if (imgsRaw) {
    urls = imgsRaw.split('||')
                  .map(s => s.trim())
                  .filter(Boolean)
                  .map(name => (imgBase ? (imgBase + name) : name));
  } else if (single) {
    urls = [single];
  }

  const $m = $('#viewMachineModal');
  $m.find('#mv-id').val(id);
  $m.find('#mv-nom').text(nom);
  $m.find('#mv-ref').text(ref);
  $m.find('#mv-four').text(four || '-');
  $m.find('#mv-desc').text(desc || '-');

  const $badge = $m.find('#mv-statut');
  $badge
    .removeClass('text-bg-success text-bg-danger text-bg-warning text-bg-secondary')
    .addClass(statutBadgeClass(statut))
    .text(statut || '-');

  buildMachineViewCarousel(urls);
});


// piece
// Diaporama image
function buildPieceCarousel(images) {
    const $wrap = $('#p-img-carousel-wrap');
    const $inner = $('#pImgCarousel .carousel-inner');
    const $fallback = $('#p-img-fallback');
    const $count = $('#p-img-count');

    $inner.empty();

    if (!images || !images.length) {
        $wrap.addClass('d-none');
        $fallback.removeClass('d-none');
        $count.text('');
        return;
    }

    images.forEach((url, idx) => {
        const active = idx === 0 ? 'active' : '';
        $inner.append(`
      <div class="carousel-item ${active}">
        <div class="d-flex align-items-center justify-content-center"
             style="width:100%;height:100px;overflow:hidden;">
          <img src="${url}"
               alt="Image ${idx+1}"
               class="d-block"
               style="max-width:100%;max-height:100%;object-fit:cover;display:none">
        </div>
      </div>
    `);
    });

    $count.text(images.length + (images.length > 1 ? ' images' : ' image'));
    $fallback.addClass('d-none');
    $wrap.removeClass('d-none');

    const el = document.getElementById('pImgCarousel');
    if (window.bootstrap && el) {
        (bootstrap.Carousel.getInstance(el) || new bootstrap.Carousel(el, {
            interval: false
        })).to(0);
    }
}

// Modal Pièce Update
$(document).on('click', '.btn-edit-piece', function() {
    const $btn = $(this);
    const id = $btn.data('id');
    const ref = $btn.data('ref') || '';
    const nom = $btn.data('nom') || '';
    const type = $btn.data('type') || '';
    const desc = $btn.data('desc') || '';
    const qte = $btn.data('qte') || '';
    const img = $btn.data('img') || '';
    const imgsStr = ($btn.data('imgs') || '').toString();
    const base = ($btn.data('imgbase') || '').toString();
    const four = String($btn.data('four') || '');
    const fourname = ($btn.data('fourname') || '').toString();

    const $modal = $('#editPieceModal');

    $modal.find('#p-id').val(id);
    $modal.find('#p-ref').val(ref);
    $modal.find('#p-nom').val(nom);
    $modal.find('#p-desc').val(desc);
    $modal.find('#p-type').val(type);
    $modal.find('#p-qte').val(qte);

    $modal.find('#p-ref-title').text(ref || '');
    $modal.find('#p-nom-title').text(nom || '(Sans nom)');

    const $fourSel = $modal.find('#p-fournisseur');
    if ($fourSel[0]?.choicesInstance) {
        const inst = $fourSel[0].choicesInstance;
        const exists = [...$fourSel[0].options].some(o => o.value === four);
        if (!exists && four) {
            inst.setChoices(
                [{
                    value: four,
                    label: (fourname || `Fournisseur #${four}`),
                    selected: true
                }],
                'value', 'label', true
            );
        } else {
            inst.setChoiceByValue(four);
        }
    } else {
        if ($fourSel.find(`option[value="${four}"]`).length === 0 && four) {
            $fourSel.prepend(new Option(fourname || `Fournisseur #${four}`, four, true, true));
        } else {
            $fourSel.val(four);
        }
    }

    let urls = [];
    if (imgsStr.trim().length) {
        urls = imgsStr.split('||').map(s => s.trim()).filter(Boolean).map(name => base + name);
        buildPieceCarousel(urls);
    } else {
        const $wrap = $('#p-img-carousel-wrap');
        const $fallback = $('#p-img-fallback');
        $wrap.addClass('d-none');
        if (img) {
            $fallback.html(
                `<img src="${img}" alt="Aperçu" style="width:100%;height:220px;object-fit:cover;">`
            ).removeClass('d-none');
        } else {
            $fallback.html('<span class="text-muted small">Aucune image</span>').removeClass('d-none');
        }
    }
    $modal.find('#p-img-file').val('');
});


// stock piece
$(document).on('click', '.btn-stock-piece', function() {
    const $btn = $(this);
    const id = parseInt($btn.data('id') || 0, 10);
    const ref = ($btn.data('ref') || '').toString();
    const nom = ($btn.data('nom') || '').toString();
    const qte = parseInt($btn.data('qte') || 0, 10);

    const $modal = $('#stockPieceModal');
    $modal.find('#stock-piece-id').val(id);
    $modal.find('#stock-piece-ref').text(ref || '');
    $modal.find('#stock-piece-nom').text(nom || '(Sans nom)');

    const $badgeQte = $modal.find('#stock-piece-qte');
    $badgeQte
        .removeClass('bg-warning text-dark bg-danger bg-success')
        .css({
            "color": "black",
            "font-weight": "bold",
            "font-size": "1rem"
        })
        .text(qte);


    const $form = $('#stockPieceForm')[0];
    if ($form) {
        $form.reset?.();
    }
    $modal.find('input[name="mouvement_type"][value="entree"]').prop('checked', true);
    $modal.find('#stock-piece-quantite').val('').removeClass('is-invalid');
});


// statut piece
(function() {
    let currentPiece = null;
    $('#statusPieceModal').on('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        if (!btn) return;

        currentPiece = {
            id: parseInt($(btn).data('id')) || 0,
            ref: $(btn).data('ref') || '',
            nom: $(btn).data('nom') || ''
        };

        $('#st2-piece-nom').text(currentPiece.nom);
        $('#st2-piece-ref').text(currentPiece.ref);
        $('#st2-total').text($(btn).data('qstock') || 0);
        $('#st2-marche').text($(btn).data('marche') || 0);
        $('#st2-panne').text($(btn).data('panne') || 0);
        $('#st2-maint').text($(btn).data('maint') || 0);
    });

    $('#btn-marche-total, #btn-panne-total, #btn-maint-total').on('click', function(e) {
        e.preventDefault();
        
        if (!currentPiece) return;
        
        const targetModal = this.id.replace('btn-', '').replace('-total', 'PieceModal');
        sessionStorage.setItem('statutPieceData', JSON.stringify({
            piece: currentPiece,
            targetModal: targetModal
        }));
        location.reload();
    });
    $(document).ready(function() {
        const stored = sessionStorage.getItem('statutPieceData');
        if (stored) {
            const data = JSON.parse(stored);
            sessionStorage.removeItem('statutPieceData');
                        setTimeout(() => {
                if (data.targetModal === 'marchePieceModal') {
                    $('#marche-piece-id').val(data.piece.id);
                    $('#marche-piece-ref').text(data.piece.ref);
                    $('#marche-piece-nom').text(data.piece.nom);
                    $('#marchePieceModal').modal('show');
                }
                else if (data.targetModal === 'pannePieceModal') {
                    $('#panne-piece-id').val(data.piece.id);
                    $('#panne-piece-ref').text(data.piece.ref);
                    $('#panne-piece-nom').text(data.piece.nom);
                    $('#pannePieceModal').modal('show');
                }
                else if (data.targetModal === 'maintPieceModal') {
                    $('#maint-piece-id').val(data.piece.id);
                    $('#maint-piece-ref').text(data.piece.ref);
                    $('#maint-piece-nom').text(data.piece.nom);
                    $('#maintPieceModal').modal('show');
                }
            }, 300);
        }
    });

})();




// Voir piece
function buildViewCarousel(urls) {
  const $wrap = $('#v-img-carousel-wrap');
  const $inner = $('#vImgCarousel .carousel-inner');
  const $fallback = $('#v-img-fallback');

  $inner.empty();

  if (!urls || !urls.length) {
    $wrap.addClass('d-none');
    $fallback.removeClass('d-none');
    return;
  }

  urls.forEach((url, idx) => {
    const active = idx === 0 ? 'active' : '';
    $inner.append(`
      <div class="carousel-item ${active} h-100">
        <div class="w-100 h-100 d-flex align-items-center justify-content-center overflow-hidden">
          <img src="${url}" alt="Image ${idx+1}" class="d-block"
               style="max-width:100%; max-height:100%; object-fit:cover;">
        </div>
      </div>
    `);
  });

  $fallback.addClass('d-none');
  $wrap.removeClass('d-none');

  const el = document.getElementById('vImgCarousel');
  if (window.bootstrap && el) {
    (bootstrap.Carousel.getInstance(el) || new bootstrap.Carousel(el, { interval: false })).to(0);
  }
}

$(document).on('click', '.btn-view-piece', function () {
  const $btn = $(this);

  const id     = parseInt($btn.data('id')     || 0, 10);
  const ref    = String($btn.data('ref')      || '');
  const nom    = String($btn.data('nom')      || '(Sans nom)');
  const type   = String($btn.data('type')     || '');
  const desc   = String($btn.data('desc')     || '');
  const qte    = parseInt($btn.data('qte')    || 0, 10);
  const marche = parseInt($btn.data('marche') || 0, 10);
  const panne  = parseInt($btn.data('panne')  || 0, 10);
  const maint  = parseInt($btn.data('maint')  || 0, 10);
  const S = (qte   || 0)
          + (marche|| 0)
          + (panne || 0)
          + (maint || 0);

  const four    = String($btn.data('fourname') || '');
  let urls = [];
  const imgsRaw = String($btn.data('imgs') || '').trim();
  const imgBase = String($btn.data('imgbase') || '');
  const single  = String($btn.data('img') || '');

  if (imgsRaw) {
    urls = imgsRaw.split('||')
                  .map(s => s.trim())
                  .filter(Boolean)
                  .map(name => (imgBase ? (imgBase + name) : name));
  } else if (single) {
    urls = [single];
  }
  $('#v-id').val(id);
  $('#v-nom').text(nom);
  $('#v-ref').text(ref);
  $('#v-type').text(type);
  $('#v-four').text(four || '-');
  $('#v-desc').text(desc || '-');
  $('#v-qte').text(S);
  buildViewCarousel(urls);
});

// fournisseur 
$(document).on('click', '.btn-edit-fournisseur', function () {
  const $btn = $(this);
  $('#f-id').val($btn.data('id') || 0);
  $('#f-prenom').val($btn.data('prenom') || '');
  $('#f-nom').val($btn.data('nom') || '');
  $('#f-tele').val(String($btn.data('tele') || '').replace(/\D+/g, '').slice(0,10));
  $('#f-adress').val($btn.data('adress') || '');
});



</script>

</div>
<script src="<?= defined("APP_BASE") ? APP_BASE : "" ?>/assets/js/main.js"></script>
</body>

</html>