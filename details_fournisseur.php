<?php
define('BASE_PATH', __DIR__);
ini_set('display_errors', 1);
$activeSection = 'Fournisseur';
$activePage = 'DetailsFournisseur';

require_once('config/config.ini.php');
require BASE_PATH . '/includes/head.php';
require BASE_PATH . '/includes/sidebare.php';
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="card card-outline card-primary mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h4 class="card-title mb-0">Règlements Fournisseurs</h4>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <div style="width:250px;">
              <select id="fournisseurSelect" class="form-select js-choices">
                <option value="">-- Sélectionner un fournisseur --</option>
                <?php
                $sql = "SELECT id_fournisseur, CONCAT(prenom_fournisseur, ' ', nom_fournisseur) AS nom
                        FROM fournisseur ORDER BY nom_fournisseur ASC";
                $res = $connect->query($sql);
                while ($f = $res->fetch_assoc()) {
                  echo "<option value='{$f['id_fournisseur']}'>" . htmlspecialchars($f['nom']) . "</option>";
                }
                ?>
              </select>
            </div>
            <input type="date" id="dateStart" class="form-control" style="width:180px;" placeholder="Date début">
            <input type="date" id="dateEnd" class="form-control" style="width:180px;" placeholder="Date fin">
            <button id="btnFilter" class="btn btn-outline-primary">
              <i class="bi bi-funnel"></i>
            </button>
          </div>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Référence</th>
                  <th>Situation</th>
                  <th>Montant (DH)</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="reglementBody">
                <tr>
                  <td colspan="5" class="text-center text-muted">
                    Sélectionnez un fournisseur pour afficher ses règlements.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="row text-center mb-4">
          <div class="col-md-4">
            <div class="card bg-success bg-opacity-10 border-success">
              <div class="card-body">
                <h6 class="text-success">Total Payé</h6>
                <h3 id="total_paye" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card bg-danger bg-opacity-10 border-danger">
              <div class="card-body">
                <h6 class="text-danger">Total Non Payé</h6>
                <h3 id="total_non_paye" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card bg-primary bg-opacity-10 border-primary">
              <div class="card-body">
                <h6 class="text-primary">Total Général</h6>
                <h3 id="total_general" class="fw-bold">0.00 DH</h3>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Modal Voir Règlement -->
  <div class="modal fade" id="reglementModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Détails du règlement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <div id="m-img-carousel-wrap" class="d-none border-bottom" style="height:420px;background:#f8f9fa;">
            <div id="mImgCarousel" class="carousel slide h-100">
              <div class="carousel-inner h-100"></div>
              <button class="carousel-control-prev" type="button" data-bs-target="#mImgCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" style="background:black;border-radius:50%;width:32px;height:32px;"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#mImgCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" style="background:black;border-radius:50%;width:32px;height:32px;"></span>
              </button>
            </div>
            <div class="small text-muted text-center mt-2" id="m-img-count"></div>
          </div>
          <div id="m-img-fallback" class="d-flex align-items-center justify-content-center border-bottom" style="height:200px;">
            <span class="text-muted small">Aucune image</span>
          </div>
          <div id="reglementInfo" class="p-3"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-dark" data-bs-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
$(document).on('click', '.btn-view-reglement', function () {
  const $b = $(this);
  const ref = $b.data('ref');
  const date = $b.data('date');
  const fournisseur = $b.data('fournisseur');
  const situation = $b.data('situation');
  const prix = parseFloat($b.data('prix') || 0);
  const paye = parseFloat($b.data('paye') || 0);
  const reste = (prix - paye).toFixed(2);
  const imgFacture = $b.data('img-facture');
  const imgJustif = $b.data('img-justification');

  $("#reglementInfo").html(`
    <div class="row">
      <div class="col-md-6">
        <p><strong>Référence :</strong> ${ref}</p>
        <p><strong>Date :</strong> ${date}</p>
        <p><strong>Fournisseur :</strong> ${fournisseur}</p>
      </div>
      <div class="col-md-6">
        <p><strong>Situation :</strong> <span class="badge bg-${situation.toLowerCase().includes('avance') ? 'info' : (situation.toLowerCase().includes('pay') && !situation.toLowerCase().includes('non') ? 'success' : 'danger')}">${situation}</span></p>
        <p><strong>Montant total :</strong> <span class="fw-bold">${prix.toFixed(2)} DH</span></p>
      </div>
    </div>
    <hr class="my-2">
    <table class="table table-sm table-bordered text-center mb-0">
      <thead class="table-light"><tr><th>Total</th><th>Versé</th><th>Reste</th></tr></thead>
      <tbody><tr>
        <td class="fw-bold text-danger">${prix.toFixed(2)} MAD</td>
        <td class="fw-bold text-success">${paye.toFixed(2)} MAD</td>
        <td class="fw-bold ${reste>0?'text-primary':'text-success'}">${reste} MAD</td>
      </tr></tbody>
    </table>
  `);

  // === Handle Multiple Images ===
  const carouselInner = $("#mImgCarousel .carousel-inner");
  carouselInner.empty();
  let imgs = [];

  if (imgFacture) {
    imgFacture.split(',').forEach(fn => {
      const trimmed = fn.trim();
      if (trimmed) imgs.push({ src: `/gestionMeknassa/uploads/reglements/${trimmed}`, label: "Facture" });
    });
  }

  if (imgJustif) {
    imgJustif.split(',').forEach(fn => {
      const trimmed = fn.trim();
      if (trimmed) imgs.push({ src: `/gestionMeknassa/uploads/justification_paiment/${trimmed}`, label: "Justification" });
    });
  }

  if (imgs.length > 0) {
    $("#m-img-carousel-wrap").removeClass("d-none");
    $("#m-img-fallback").addClass("d-none");
    imgs.forEach((img, i) => {
      carouselInner.append(`
        <div class="carousel-item ${i === 0 ? 'active' : ''} h-100 text-center">
          <img src="${img.src}" class="h-100 w-auto mx-auto" style="object-fit:contain;">
          <div class="carousel-caption d-none d-md-block">
            <small class="bg-dark bg-opacity-50 px-2 rounded">${img.label} ${imgs.length > 1 ? (i + 1) : ''}</small>
          </div>
        </div>
      `);
    });
    $("#m-img-count").text(`${imgs.length} image${imgs.length > 1 ? 's' : ''}`);
  } else {
    $("#m-img-carousel-wrap").addClass("d-none");
    $("#m-img-fallback").removeClass("d-none");
  }

  new bootstrap.Modal('#reglementModal').show();
});

function loadReglements(){
  const id_fournisseur = $("#fournisseurSelect").val();
  const date_debut = $("#dateStart").val();
  const date_fin = $("#dateEnd").val();

  if(!id_fournisseur && !date_debut && !date_fin){
    $("#reglementBody").html('<tr><td colspan="6" class="text-center text-muted">Sélectionnez un fournisseur ou une période.</td></tr>');
    return;
  }

  Swal.fire({title:"Chargement...",allowOutsideClick:false,didOpen:()=>Swal.showLoading()});

  $.ajax({
    url:"includes/ajax/admin/getReglementFournisseur.php",
    method:"POST",
    dataType:"json",
    data:{id_fournisseur,date_debut,date_fin,per_page:"all"},
    success:function(res){
      Swal.close();
      if(!res.ok){Swal.fire("Erreur",res.error,"error");return;}

      $("#total_paye").text(res.somme_payee.toFixed(2)+" DH");
      $("#total_non_paye").text(res.somme_non_paye.toFixed(2)+" DH");
      $("#total_general").text(res.total_general.toFixed(2)+" DH");

      if(res.rows.length===0){
        $("#reglementBody").html('<tr><td colspan="6" class="text-center text-muted">Aucun règlement trouvé</td></tr>');
        return;
      }

      let html="";
      res.rows.forEach(r=>{
        let color="secondary";
        const sit=r.situation.toLowerCase();
        if(sit.includes('avance')) color="info";
        else if(sit.includes('pay')&&!sit.includes('non')) color="success";
        else color="danger";

        html+=`
          <tr>
            <td>${r.date_fmt}</td>
            <td>${r.ref}</td>
            <td><span class="badge bg-${color}">${r.situation}</span></td>
            <td class="fw-bold text-end">${r.prix_total.toFixed(2)} DH</td>
            <td>
              <button class="btn btn-sm btn-outline-info btn-view-reglement"
                data-ref="${r.ref}"
                data-date="${r.date_fmt}"
                data-fournisseur="${r.fournisseur}"
                data-situation="${r.situation}"
                data-prix="${r.prix_total}"
                data-paye="${r.total_paye}"
                data-img-facture="${r.img_facture || ''}"
                data-img-justification="${r.img_justification || ''}">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>`;
      });
      $("#reglementBody").html(html);
    },
    error:function(xhr){Swal.close();Swal.fire("Erreur réseau",xhr.responseText||"Erreur AJAX","error");}
  });
}

$("#fournisseurSelect,#dateStart,#dateEnd,#btnFilter").on("change click",loadReglements);
</script>
<?php require BASE_PATH . '/includes/scripts.php'; ?>
