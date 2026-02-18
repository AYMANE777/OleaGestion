<?php
$activeSection = 'journale-status';
$activePage = 'journale-status-reglement';
ini_set('display_errors', 1);
require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
$connect->set_charset('utf8mb4');
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">

      <div class="card card-outline card-primary mb-4">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
          <h4 class="card-title mb-0">Règlements Fournisseurs</h4>
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

            <button id="btnPrint" class="btn btn-outline-dark">
              <i class="bi bi-printer"></i>
            </button>
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

          <!-- Pagination Bar -->
          <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
            <small id="paginationInfo" class="text-muted"></small>
            <nav>
              <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
          </div>
        </div>
      </div>

      <!-- Totaux -->
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

  <!-- Modal Voir Règlement -->
  <div class="modal fade" id="reglementModal" tabindex="-1" aria-labelledby="reglementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reglementModalLabel">Détails du règlement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
          <div id="m-img-carousel-wrap" class="d-none border-bottom" style="height:420px; background:#f8f9fa;">
            <div id="mImgCarousel" class="carousel slide h-100" data-bs-ride="false">
              <div class="carousel-inner h-100"></div>
              <button class="carousel-control-prev" type="button" data-bs-target="#mImgCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#mImgCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
              </button>
            </div>
            <div class="small text-muted mt-2 text-center" id="m-img-count"></div>
          </div>

          <div id="m-img-fallback" class="d-flex align-items-center justify-content-center border-bottom" style="height:200px;">
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
</main>

<script>
$(document).on('click', '.btn-view-reglement', function () {
  const ref = $(this).data('ref');
  const date = $(this).data('date');
  const fournisseur = $(this).data('fournisseur');
  const situation = $(this).data('situation');
  const prix = $(this).data('prix');
  const imgFacture = $(this).data('img-facture');
  const imgJustif = $(this).data('img-justification');

  // === Infos textuelles ===
  $("#reglementInfo").html(`
    <div class="row">
      <div class="col-md-6">
        <p><strong>Référence :</strong> ${ref}</p>
        <p><strong>Date :</strong> ${date}</p>
        <p><strong>Fournisseur :</strong> ${fournisseur}</p>
      </div>
      <div class="col-md-6">
        <p><strong>Situation :</strong> 
          <span class="badge bg-${situation.toLowerCase().includes('pay') && !situation.toLowerCase().includes('non') ? 'success' : 'danger'}">${situation}</span>
        </p>
        <p><strong>Montant total :</strong> <span class="fw-bold">${prix} DH</span></p>
      </div>
    </div>
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
          <img src="${img.src}" class="h-100 w-auto mx-auto d-block" style="object-fit:contain;">
          <div class="carousel-caption d-none d-md-block">
            <small class="bg-dark bg-opacity-50 px-2 rounded">${img.label} (${i + 1}/${imgs.length})</small>
          </div>
        </div>
      `);
    });
    $("#m-img-count").text(`${imgs.length} image${imgs.length > 1 ? 's' : ''}`);
  } else {
    $("#m-img-carousel-wrap").addClass("d-none");
    $("#m-img-fallback").removeClass("d-none");
  }

  new bootstrap.Modal(document.getElementById('reglementModal')).show();
});

$(document).ready(function () {
  function loadReglements() {
    const id_fournisseur = $("#fournisseurSelect").val();
    const date_debut = $("#dateStart").val();
    const date_fin = $("#dateEnd").val();

    if (!id_fournisseur && !date_debut && !date_fin) {
      $("#reglementBody").html('<tr><td colspan="6" class="text-center text-muted">Sélectionnez un fournisseur et/ou des dates pour afficher les règlements.</td></tr>');
      $("#total_paye, #total_non_paye, #total_general").text("0.00 DH");
      return;
    }

    Swal.fire({
      title: "Chargement...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: "../includes/ajax/journal/get_fournisseur_statut.php",
      method: "POST",
      dataType: "json",
      data: { id_fournisseur, date_debut, date_fin },
      success: function (res) {
        Swal.close();
        const sommePayee = res.somme_payee || 0;
        const sommeNonPayee = res.somme_non_paye || 0;
        const totalGeneral = res.total_general || 0;
        $("#total_paye").text(sommePayee.toFixed(2) + " DH");
        $("#total_non_paye").text(sommeNonPayee.toFixed(2) + " DH");
        $("#total_general").text(totalGeneral.toFixed(2) + " DH");

        if (!res.ok) {
          Swal.fire("Erreur", res.error || "Impossible de charger les données.", "error");
          return;
        }
        if (res.rows.length === 0) {
          $("#reglementBody").html('<tr><td colspan="6" class="text-center text-muted">Aucun règlement trouvé pour les critères sélectionnés.</td></tr>');
          return;
        }

        let html = "";
        res.rows.forEach(r => {
          let color = "secondary";
          const sit = r.situation.toLowerCase().trim();
          if (sit.includes('avance')) color = "info";
          else if (sit.includes('pay') && !sit.includes('non')) color = "success";
          else color = "danger";

          const facture = r.img_facture || "";
          const justification = r.img_justification || "";

          html += `
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
                  data-prix="${r.prix_total.toFixed(2)}"
                  data-img-facture="${facture}"
                  data-img-justification="${justification}"
                  title="Voir">
                  <i class="bi bi-eye"></i>
                </button>
              </td>
            </tr>`;
        });
        $("#reglementBody").html(html);
      },
      error: function (xhr) {
        Swal.close();
        console.error(xhr.responseText);
        Swal.fire("Erreur réseau", "Erreur AJAX: " + xhr.statusText, "error");
      }
    });
  }

  $("#fournisseurSelect, #dateStart, #dateEnd, #btnFilter").on("change click", loadReglements);

  $("#btnPrint").on("click", function () {
    const id_fournisseur = $("#fournisseurSelect").val();
    const date_debut = $("#dateStart").val();
    const date_fin = $("#dateEnd").val();
    let url = 'print-reglements-fournisseurs.php?';
    const params = [];
    if (id_fournisseur) params.push(`id_fournisseur=${id_fournisseur}`);
    if (date_debut) params.push(`date_debut=${date_debut}`);
    if (date_fin) params.push(`date_fin=${date_fin}`);
    url += params.join('&');
    window.open(url, "_blank");
  });
});
</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>
