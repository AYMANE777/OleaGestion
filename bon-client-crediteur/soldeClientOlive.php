<?php
$activeSection = 'ClientCrediteurOlive';
$activePage = 'SoldeClientOlive';
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">

            <div class="card card-info card-outline mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Solde des clients créditeurs</h4>
                    <div style="width:300px;">
                        <select id="clientSelect" class="form-select js-choices" required>
                            <option value="">-- Sélectionner un client --</option>
                            <?php
                            require_once '../config/config.ini.php';
                            $q = $connect->query("SELECT id_client_olive, CONCAT(nom,' ',prenom) AS nomcomplet FROM clientolive ORDER BY nom ASC");
                            while ($c = $q->fetch_assoc()) {
                                echo '<option value="' . $c['id_client_olive'] . '">' . htmlspecialchars($c['nomcomplet']) . '</option>';
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
                                            <div class="col-md-2">
                                                <div class="text-end my-3">
                                                    <button id="btnPrintSolde" class="btn btn-outline-dark">
                                                        <i class="bi bi-printer"></i> Imprimer le relevé
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <small class="text-muted text-uppercase fw-semibold">Provider</small>
                                                <div id="info_fournisseur" class="fs-6 fw-bold text-dark mt-1"></div>
                                            </div>

                                            <div class="col-md-6">
                                                <small class="text-muted text-uppercase fw-semibold">Note</small>
                                                <div id="info_note" class="fs-6 text-muted mt-1"></div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Situation financière</h5>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="card bg-warning bg-opacity-10 border-warning">
                                    <div class="card-body">
                                        <h6 class="text-warning">Total Crédit</h6>
                                        <h3 class="fw-bold" id="total_credit">0.00 DH</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success bg-opacity-10 border-success">
                                    <div class="card-body">
                                        <h6 class="text-success">Total Versements</h6>
                                        <h3 class="fw-bold" id="total_versement">0.00 DH</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info bg-opacity-10 border-info">
                                    <div class="card-body">
                                        <h6 class="text-info">Solde restant</h6>
                                        <h3 class="fw-bold" id="solde_restant">0.00 DH</h3>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <h5 class="fw-bold mt-4">Détails des taratonages</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Poids net (kg)</th>
                                        <th>Prix unitaire</th>
                                        <th>Montant (DH)</th>
                                        <th>Preuve</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody id="taratonageDetailBody"></tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold mt-4">Détails des versements</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Montant (DH)</th>
                                        <th>Mode</th>
                                        <th>Preuve</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody id="versementDetailBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="noClientSelected" class="text-muted text-center py-5">
                        Sélectionne un client pour voir ses informations.
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function () {

        $("#clientSelect").on("change", function () {
            const id = $(this).val();
            if (!id) {
                $("#clientInfoSection").addClass("d-none");
                $("#noClientSelected").removeClass("d-none");
                return;
            }

            Swal.fire({
                title: "Chargement des données...",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            $.ajax({
                url: "../includes/ajax/ClientOlive/getSoldeClientOlive.php",
                method: "GET",
                dataType: "json",
                data: { id_client_olive: id },
                success: function (res) {
                    Swal.close();
                    if (res.status === "success") {
                        $("#clientInfoSection").removeClass("d-none");
                        $("#noClientSelected").addClass("d-none");

                        // === Badge statut financier ===
                        const solde = res.totaux.solde;
                        let statutBadge = "";

                        if (solde > 0) statutBadge = '<span class="badge bg-danger ms-2">Créditeur</span>';
                        else if (solde < 0) statutBadge = '<span class="badge bg-success ms-2">Débiteur</span>';
                        else statutBadge = '<span class="badge bg-secondary ms-2">Équilibré</span>';

                        // === Informations client ===
                        $("#info_nom").html(`${res.client.nom} ${res.client.prenom} ${statutBadge}`);
                        $("#info_tel").text(res.client.telephone);
                        $("#info_fournisseur").text(res.client.fournisseur);
                        $("#info_note").text(res.client.note || "");

                        // === Totaux ===
                        $("#total_credit").text(res.totaux.credit.toFixed(2) + " DH");
                        $("#total_versement").text(res.totaux.versement.toFixed(2) + " DH");

                        // === Formatage du solde avec signe et couleur ===
                        let signe = "";
                        let classe = "";

                        if (solde > 0) {
                            // Le client doit encore payer (crédit)
                            signe = "-";
                            classe = "text-danger";
                        } else if (solde < 0) {
                            // Le client est en avance (débit)
                            signe = "+";
                            classe = "text-success";
                        } else {
                            signe = "";
                            classe = "text-secondary";
                        }

                        $("#solde_restant")
                            .removeClass("text-danger text-success text-secondary")
                            .addClass(classe)
                            .text(`${signe}${Math.abs(solde).toFixed(2)} DH`);

                        // === Liste taratonage ===
                      let tRows = "";
res.taratonages.forEach(t => {
  tRows += `<tr>
    <td>${t.date_taratonage}</td>
    <td>${t.poid_net}</td>
    <td>${t.prix_unitaire}</td>
    <td class="fw-bold text-success">${t.montant_total}</td>
    
    <!-- ✅ Affichage image scannée -->
    <td class="text-center">
      ${t.scanne_bon_url 
        ? `<a href="${t.scanne_bon_url}" target="_blank" title="Voir le bon scanné">
             <img src="${t.scanne_bon_url}" style="width:55px;height:55px;object-fit:cover;border-radius:6px;border:1px solid #ccc;">
           </a>`
        : `<span class="text-muted">—</span>`
      }
    </td>

    <td>${t.note || ''}</td>
  </tr>`;
                        });
                        $("#taratonageDetailBody").html(tRows || `<tr><td colspan='5' class='text-muted text-center'>Aucun taratonage</td></tr>`);

                        // === Liste versements ===
                        let vRows = "";
                        res.versements.forEach(v => {
                            vRows += `<tr>
              <td>${v.date_versement}</td>
              <td class="fw-bold text-primary">${v.montant}</td>
              <td>${v.mode_paiement}</td>
              <td>${v.image_versement
                                    ? `<a href="../${v.image_versement}" target="_blank">
                      <img src="../${v.image_versement}" style="max-height:50px;max-width:50px;border-radius:6px;">
                    </a>`
                                    : `<span class='text-muted'>—</span>`
                                }</td>
              <td>${v.note || ''}</td>
            </tr>`;
                        });
                        $("#versementDetailBody").html(vRows || `<tr><td colspan='5' class='text-muted text-center'>Aucun versement</td></tr>`);

                    } else {
                        Swal.fire("Erreur", res.message, "error");
                    }
                },
                error: function (xhr) {
                    Swal.close();
                    Swal.fire("Erreur réseau", xhr.responseText || "Impossible de charger les données.", "error");
                }
            });
        });
    });

    // === Bouton Imprimer ===
    $("#btnPrintSolde").on("click", function () {
        const id = $("#clientSelect").val();
        if (!id) {
            Swal.fire("Aucun client sélectionné", "Veuillez choisir un client avant d’imprimer.", "warning");
            return;
        }
        // Ouvre la page d’impression dans un nouvel onglet
        window.open(`printSoldeClientOlive.php?id_client_olive=${id}`, "_blank");
    });

</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>