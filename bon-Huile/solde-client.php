<?php
$activeSection = 'bon-Huile';
$activePage = 'client-solde-status';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
require_once '../config/config.ini.php';
?>
<style>
    .badge {
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  padding: 4px 8px;
  border-radius: 0.35rem;
}
/* === Style pour le zoom image justificatif === */
.image-viewer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  z-index: 2000;
  transition: opacity 0.2s ease-in-out;
}
.image-viewer-overlay.visible {
  opacity: 1;
}
.image-viewer-content img {
  max-width: 90%;
  max-height: 85vh;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0,0,0,0.5);
  object-fit: contain;
  cursor: zoom-out;
  animation: zoomIn 0.25s ease;
}
@keyframes zoomIn {
  from { transform: scale(0.8); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="card card-info card-outline mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0"> Solde des Clients (Huile + Bidon)</h4>
          <div style="width:300px;">
            <select id="clientSelect" class="form-select js-choices" required>
              <option value="">-- Sélectionner un client --</option>
              <?php
              $q = $connect->query("SELECT id, CONCAT(nom,' ',prenom) AS nomcomplet FROM client ORDER BY nom ASC");
              while ($c = $q->fetch_assoc()) {
                echo '<option value="' . $c['id'] . '">' . htmlspecialchars($c['nomcomplet']) . '</option>';
              }
              ?>
            </select>
          </div>
        </div>

        <div class="card-body">
          <div id="clientInfoSection" class="d-none">

            <h5 class="fw-bold mb-3">Informations du client</h5>
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-body">
                <div class="row g-3 align-items-center">
                  <div class="col-md-3 text-center">
                    <div class="avatar avatar-xl border rounded-circle d-inline-flex align-items-center justify-content-center bg-light"
                      style="width:90px;height:90px;">
                      <i class="bi bi-person-fill fs-1 text-secondary"></i>
                    </div>
                  </div>

                  <div class="col-md-9">
                    <div class="row g-2">
                      <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold">Nom complet</small>
                        <div id="info_nom" class="fs-6 fw-bold text-dark mt-1"></div>
                      </div>
                      <div class="col-md-4">
                        <small class="text-muted text-uppercase fw-semibold">Téléphone</small>
                        <div id="info_tel" class="fs-6 fw-bold text-dark mt-1"></div>
                      </div>
                      <div class="col-md-2 text-end">
                        <button id="btnPrintSolde" class="btn btn-outline-dark mt-2">
                          <i class="bi bi-printer"></i> Imprimer
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Totaux -->
            <h5 class="fw-bold mt-4 mb-3">Situation financière</h5>
            <div class="row text-center">
              <div class="col-md-3">
                <div class="card bg-warning bg-opacity-10 border-warning">
                  <div class="card-body">
                    <h6 class="text-warning">Total Huile</h6>
                    <h3 class="fw-bold" id="total_huile">0.00 DH</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-secondary bg-opacity-10 border-secondary">
                  <div class="card-body">
                    <h6 class="text-secondary">Total Bidon</h6>
                    <h3 class="fw-bold" id="total_bidon">0.00 DH</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success bg-opacity-10 border-success">
                  <div class="card-body">
                    <h6 class="text-success">Versements</h6>
                    <h3 class="fw-bold" id="total_versement">0.00 DH</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-info bg-opacity-10 border-info">
                  <div class="card-body">
                    <h6 class="text-info">Solde Restant</h6>
                    <h3 class="fw-bold" id="solde_restant">0.00 DH</h3>
                  </div>
                </div>
              </div>
            </div>

        
            <h5 class="fw-bold mt-4">Détails de l’huile</h5>
<div class="table-responsive">
  <table class="table table-hover table-sm align-middle">
    <thead class="table-light">
      <tr>
        <th>Date</th>
        <th>Quantité Huile (L)</th>
        <th>Prix Unitaire</th>
        <th>Total Huile (DH)</th>
        <th>Total Bidons (DH)</th>
        <th>Total Général (DH)</th>
        <th>Realiser Par :</th>
        <th>Mode Paiement</th>
        <th>Bidons (Détails)</th>
      </tr>
    </thead>
    <tbody id="bonsMixteBody"></tbody>
 
  </table>
</div>
            <!-- Détails Bidon -->
            <h5 class="fw-bold mt-4">Détails des Bidons</h5>
            <div class="table-responsive">
              <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Nom Bidon</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Prix Unitaire</th>
                    <th>Total (DH)</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody id="bonsBidonBody"></tbody>
              </table>
            </div>

            <!-- Détails Versements -->
            <h5 class="fw-bold mt-4">Détails des Versements</h5>
            <div class="table-responsive">
              <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Montant (DH)</th>
                    <th>Mode</th>
                    <th>Justificatif</th>
                    <th>Note</th>
                  </tr>
                </thead>
                <tbody id="versementDetailBody"></tbody>
              </table>
            </div>

          </div>

          <div id="noClientSelected" class="text-muted text-center py-5">
            Sélectionnez un client pour afficher son solde.
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
// === UNIVERSAL IMAGE PREVIEW (ENLARGED VIEW) ===
document.addEventListener('click', (e) => {
  const target = e.target;
  if (target.tagName === 'IMG' && target.getAttribute('src') && target.closest('td')) {
    const overlay = document.createElement('div');
    overlay.className = 'image-viewer-overlay';
    overlay.innerHTML = `
      <div class="image-viewer-content">
        <img src="${target.getAttribute('src')}" alt="Justificatif" />
      </div>`;
    document.body.appendChild(overlay);
    requestAnimationFrame(() => overlay.classList.add('visible'));
    overlay.addEventListener('click', () => {
      overlay.classList.remove('visible');
      setTimeout(() => overlay.remove(), 200);
    });
  }
});

$(document).ready(function () {
  $("#clientSelect").on("change", function () {
    const id = $(this).val();
    if (!id) {
      $("#clientInfoSection").addClass("d-none");
      $("#noClientSelected").removeClass("d-none");
      return;
    }

    Swal.fire({
      title: "Chargement...",
      text: "Veuillez patienter.",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: "../includes/ajax/Huile/Client-Huile/getSoldeClientGlobal.php",
      method: "GET",
      dataType: "json",
      data: { id_client: id },
    success: function (res) {
  Swal.close();

  if (res.status === "success") {
    $("#clientInfoSection").removeClass("d-none");
    $("#noClientSelected").addClass("d-none");

    // === Infos client ===
    $("#info_nom").text(res.client.nom + " " + res.client.prenom);
    $("#info_tel").text(res.client.telephone || "—");

    // === CALCUL TOTALS GLOBAUX (non payés uniquement) ===
    let totalHuile = 0;
    let totalBidon = 0;
    const totalVersement = parseFloat(res.totaux.versement || 0);

    // 🔹 Commandes mixtes (calcul seulement CREDIT)
    if (Array.isArray(res.commandes_mixte)) {
      res.commandes_mixte.forEach(cmd => {
        if (cmd.mode_paiement === "CREDIT") {
          totalHuile += parseFloat(cmd.prix_net || 0);

          // Bidons associés à cette commande
          if (cmd.bidons && cmd.bidons.length > 0) {
            cmd.bidons.forEach(b => {
              totalBidon += parseFloat(b.quantite_bidon || 0) * parseFloat(b.prix_bidon || 0);
            });
          }
        }
      });
    }

    // 🔹 Bons de bidon séparés (calcul seulement non payés)
    if (Array.isArray(res.bons_bidon)) {
      res.bons_bidon.forEach(b => {
        if (b.statut.toUpperCase() !== "PAYE") {
          totalBidon += parseFloat(b.montant_total || 0);
        }
      });
    }

    const totalGeneral = totalHuile + totalBidon;
    const solde = totalGeneral - totalVersement;

    // === MISE À JOUR DES CARTES ===
    $("#total_huile").text(totalHuile.toFixed(2) + " DH");
    $("#total_bidon").text(totalBidon.toFixed(2) + " DH");
    $("#total_versement").text(totalVersement.toFixed(2) + " DH");

    // === STATUT GLOBAL ===
    let statutTexte = "";
    let signe = "";
    let couleur = "";

    if (solde > 0) {
      statutTexte = "Débiteur";
      signe = "-";
      couleur = "text-danger";
    } else if (solde < 0) {
      statutTexte = "Créditeur";
      signe = "+";
      couleur = "text-success";
    } else {
      statutTexte = "Équilibré";
      signe = "";
      couleur = "text-secondary";
    }

    $("#solde_restant")
      .removeClass("text-danger text-success text-secondary")
      .addClass(couleur)
      .html(`${signe}${Math.abs(solde).toFixed(2)} DH <span class="badge bg-light text-dark ms-2">${statutTexte}</span>`);

    // === TABLEAUX ===

    // 🔹 Commandes Mixtes (afficher toutes)
    let mRows = "";
    if (Array.isArray(res.commandes_mixte) && res.commandes_mixte.length > 0) {
      res.commandes_mixte.forEach(cmd => {
        let totalBidonCmd = 0;
        let bidonTable = "";

        if (cmd.bidons && cmd.bidons.length > 0) {
          bidonTable = `
            <table class="table table-bordered table-sm mb-0">
              <thead><tr><th>Nom</th><th>Type</th><th>Qté</th><th>Prix (DH)</th><th>Total (DH)</th></tr></thead>
              <tbody>
                ${cmd.bidons.map((b) => {
                  const total = (b.quantite_bidon * b.prix_bidon).toFixed(2);
                  totalBidonCmd += parseFloat(total);
                  return `
                    <tr>
                      <td>${b.nom_bidon}</td>
                      <td>${b.type_bidon}</td>
                      <td>${b.quantite_bidon}</td>
                      <td>${b.prix_bidon}</td>
                      <td class="fw-bold text-success">${total}</td>
                    </tr>`;
                }).join("")}
              </tbody>
            </table>`;
        } else {
          bidonTable = "<span class='text-muted'>Aucun bidon</span>";
        }

        const totalHuileCmd = parseFloat(cmd.prix_net);
        const totalCmd = totalHuileCmd + totalBidonCmd;

        const statutBadge = cmd.mode_paiement === "CREDIT"
          ? `<span class='badge bg-danger'>Non Payé</span>`
          : `<span class='badge bg-success'>Payé</span>`;

        mRows += `
          <tr>
            <td>${cmd.date_bon}</td>
            <td>${cmd.quantite}</td>
            <td>${cmd.prix_unitaire}</td>
            <td class="fw-bold text-success">${totalHuileCmd.toFixed(2)}</td>
            <td class="fw-bold text-warning">${totalBidonCmd.toFixed(2)}</td>
            <td class="fw-bold text-primary">${totalCmd.toFixed(2)}</td>
            <td>${cmd.provider}</td>
            <td>${statutBadge}</td>
            <td>${bidonTable}</td>
          </tr>`;
      });
    } else {
      mRows = `<tr><td colspan="9" class="text-center text-muted">Aucune commande mixte</td></tr>`;
    }
    $("#bonsMixteBody").html(mRows);

    // 🔹 Bons de Bidon (afficher tous)
    let bRows = "";
    if (Array.isArray(res.bons_bidon) && res.bons_bidon.length > 0) {
      res.bons_bidon.forEach(b => {
        const statutBadge = b.statut.toUpperCase() === "PAYE"
          ? `<span class="badge bg-success">Payé</span>`
          : `<span class="badge bg-danger">Non Payé</span>`;

        bRows += `<tr>
          <td>${b.date}</td>
          <td>${b.nom_bidon}</td>
          <td>${b.type_bidon}</td>
          <td>${b.quantite}</td>
          <td>${b.prix_unitaire}</td>
          <td class="fw-bold text-success">${b.montant_total}</td>
          <td>${statutBadge}</td>
        </tr>`;
      });
    } else {
      bRows = `<tr><td colspan="7" class="text-center text-muted">Aucun bon de bidon</td></tr>`;
    }
    $("#bonsBidonBody").html(bRows);

    // 🔹 Versements (inchangé)
    let vRows = "";
    if (Array.isArray(res.versements) && res.versements.length > 0) {
      res.versements.forEach((v) => {
        vRows += `<tr>
          <td>${v.date_versement}</td>
          <td class="fw-bold text-primary">${v.montant.toFixed(2)}</td>
          <td>${v.mode_paiement}</td>
          <td class="text-center">
            ${v.image_versement
              ? `<img src="../${v.image_versement}" alt="Justificatif"
                  style="max-height:50px;max-width:60px;border-radius:6px;object-fit:cover;cursor:zoom-in;">`
              : `<span class="text-muted">—</span>`}
          </td>
          <td>${v.note || ""}</td>
        </tr>`;
      });
    } else {
      vRows = `<tr><td colspan="5" class="text-center text-muted">Aucun versement</td></tr>`;
    }
    $("#versementDetailBody").html(vRows);
  } else {
    Swal.fire("Erreur", res.message, "error");
  }
},

      error: function (xhr) {
        Swal.close();
        Swal.fire("Erreur réseau", xhr.responseText || "Impossible de charger les données.", "error");
      },
    });
  });

  // === Imprimer le relevé ===
  $("#btnPrintSolde").on("click", function () {
    const id = $("#clientSelect").val();
    if (!id) {
      Swal.fire("Aucun client sélectionné", "Veuillez choisir un client avant d’imprimer.", "warning");
      return;
    }
    window.open(`printSoldeClientGlobalOlive.php?id_client=${id}`, "_blank");
  });
});
</script>




<?php require __DIR__ . '/../includes/scripts.php'; ?>
