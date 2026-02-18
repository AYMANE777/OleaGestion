<?php
$activeSection = 'bon-Huile';
$activePage = 'bon-huile-versementt';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
require_once(__DIR__ . '/../config/config.ini.php');

$QueryGetClients = mysqli_query($connect, "SELECT * FROM client ORDER BY nom, prenom");
?>
<style>
img {
    object-fit: cover;
    border-radius: 6px;
    cursor: zoom-in;
}

.card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.modal-image-viewer .modal-content {
    background-color: rgba(0, 0, 0, 0.85) !important;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 12px;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-image-viewer img {
    transition: transform 0.3s ease;
}

.modal-image-viewer img:hover {
    transform: scale(1.03);
}
</style>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card card-outline card-info mb-0">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="formTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="versement-tab" data-bs-toggle="tab"
                                data-bs-target="#tab-ajouter-versement" type="button" role="tab"
                                aria-controls="tab-ajouter-versement" aria-selected="true">
                                Ajouter un Versement
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="liste-tab" data-bs-toggle="tab"
                                data-bs-target="#tab-liste-versement" type="button" role="tab"
                                aria-controls="tab-liste-versement" aria-selected="false">
                                Liste des Versements
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content mt-3" id="formTabsContent">
                <!-- === AJOUTER UN VERSEMENT === -->
                <div class="tab-pane fade show active" id="tab-ajouter-versement" role="tabpanel"
                    aria-labelledby="versement-tab">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Ajouter un Versement Client</div>
                        </div>

                        <form id="addClientVersementForm" class="needs-validation"
                            action="../includes/ajax/Huile/Client-Huile/addClientVersement.php" method="POST"
                            enctype="multipart/form-data" novalidate>
                            <div class="card-body">
                                <div class="row g-3">

                                    <!-- Client -->
                                    <div class="col-md-6">
                                        <label for="id_client" class="form-label">Client :</label>
                                        <select class="form-select js-choices" id="id_client" name="id_client" required>
                                            <option value="" selected disabled>Sélectionner un client</option>
                                            <?php while ($client = mysqli_fetch_assoc($QueryGetClients)): ?>
                                            <option value="<?= htmlspecialchars($client['id']) ?>">
                                                <?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Sélectionner un client.</div>
                                    </div>

                                    <!-- Montant -->
                                    <div class="col-md-6">
                                        <label for="montant" class="form-label">Montant versé :</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="montant"
                                            name="montant" required>
                                        <div class="invalid-feedback">Saisir un montant valide.</div>
                                    </div>

                                    <!-- Mode de paiement -->
                                    <div class="col-md-6">
                                        <label for="mode_paiement" class="form-label">Mode de paiement :</label>
                                        <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                                            <option value="ESPECES" selected>ESPECES</option>
                                            <option value="LCN">LCN</option>
                                            <option value="VIREMENT">VIREMENT</option>
                                            <option value="CHEQUE">CHEQUE</option>
                                        </select>
                                    </div>



                                    <!-- Justificatif -->
                                    <div class="col-md-6">
                                        <label for="justificatif" class="form-label">Justificatif (optionnel) :</label>
                                        <input type="file" class="form-control" id="justificatif" name="justificatif"
                                            accept=".jpg,.jpeg,.png,.pdf">
                                    </div>

                                    <!-- Note -->
                                    <div class="col-md-6">
                                        <label for="note" class="form-label">Note / Observation :</label>
                                        <textarea type="text" class="form-control" id="note" name="note"
                                            placeholder="Ex: Acompte huile 2025, paiement partiel..."></textarea>
                                    </div>

                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button class="btn btn-primary text-white" type="submit" name="addClientVersementBtn">
                                    <i class="bi bi-save me-1"></i> Ajouter le versement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- === LISTE DES VERSEMENTS (prochaine étape si tu veux) === -->
                <div class="tab-pane fade" id="tab-liste-versement" role="tabpanel" aria-labelledby="liste-tab">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Liste des Versements Clients</div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                                <h4 class="m-0">Liste des Versements Clients</h4>
                                <button class="btn btn-outline-secondary" id="refreshVersements">
                                    <i class="bi bi-arrow-repeat me-1"></i> Actualiser
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="versementsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Client</th>
                                            <th>Montant (MAD)</th>
                                            <th>Mode Paiement</th>
                                            <th>Note</th>
                                            <th>Date Versement</th>
                                            <th>Justificatif</th>
                                            <th class="text-center">Actions</th> <!-- 🆕 Nouvelle colonne -->
                                        </tr>
                                    </thead>
                                    <tbody id="versementsBody"></tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();


$(document).on("submit", "#addClientVersementForm", function(e) {
    e.preventDefault();

    const form = this;
    const fd = new FormData(form);

    const id_client = fd.get("id_client");
    const montant = fd.get("montant")?.trim();
    const mode_paiement = fd.get("mode_paiement")?.trim();

    if (!id_client || !montant || parseFloat(montant) <= 0) {
        Swal.fire({
            icon: "warning",
            title: "Attention",
            text: "Veuillez sélectionner un client et saisir un montant valide !",
        });
        return;
    }

    Swal.fire({
        title: "Ajout du versement...",
        text: "Veuillez patienter.",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    $.ajax({
        url: form.action,
        method: "POST",
        dataType: "json",
        data: fd,
        processData: false,
        contentType: false,
        headers: {
            "X-Requested-With": "XMLHttpRequest"
        },
        success: function(response) {
            Swal.close();

            if (response && response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Versement ajouté avec succès",
                    text: response.message || "Le versement a été enregistré.",
                    confirmButtonText: "Imprimer le versement",
                    showCancelButton: true,
                    cancelButtonText: "Fermer",
                    confirmButtonColor: "#198754"
                }).then((resSwal) => {
                    if (resSwal.isConfirmed) {
                        const id = response.id || "";
                        if (id) {
                            const url =
                                "/gestionMeknassa/bon-Huile/print_versement.php?id=" +
                                encodeURIComponent(id);
                            window.open(url, "_blank");
                        } else {
                            Swal.fire({
                                icon: "info",
                                title: "Information manquante",
                                text: "Impossible d’imprimer : identifiant manquant."
                            });
                        }
                    }

                    form.reset();
                    form.classList.remove("was-validated");
                    loadVersements();
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: response.message ||
                        "Une erreur est survenue lors de l’enregistrement.",
                });
            }
        },
        error: function(xhr) {
            Swal.close();
            let msg = "Impossible d'envoyer la requête.";
            try {
                const j = JSON.parse(xhr.responseText);
                if (j && j.message) msg = j.message;
            } catch (_) {}
            Swal.fire({
                icon: "error",
                title: "Erreur réseau",
                text: msg,
            });
        },
    });
});


// ===========================
// CHARGER LA LISTE VERSEMENTS
// ===========================
function loadVersements() {
    $("#versementsBody").html(
        `<tr><td colspan="7" class="text-center text-muted p-3">
      <div class="spinner-border text-success spinner-border-sm"></div> Chargement...
     </td></tr>`
    );

    $.ajax({
        url: "../includes/ajax/Huile/Client-Huile/getVersementsClient.php",
        method: "GET",
        dataType: "json",
        success: function(res) {
            if (res.success && Array.isArray(res.rows) && res.rows.length > 0) {
                let rows = res.rows.map((r, i) => {
                    const link = r.justificatif ?
                        `<a href="../${r.justificatif}" target="_blank" class="btn btn-sm btn-outline-success">
         <i class="bi bi-eye"></i> Voir
       </a>` :
                        `<span class="text-muted">Aucun</span>`;

                    // 🖨️ Bouton imprimer
                    const printBtn = `
    <button class="btn btn-outline-primary btn-sm print-versement"
      data-id="${r.id_versement}" title="Imprimer le reçu">
      <i class="bi bi-printer"></i>
    </button>
  `;

                    return `
    <tr>
      <td>${i + 1}</td>
      <td>${r.client || ''}</td>
      <td class="fw-bold text-success">${Number(r.montant).toFixed(2)}</td>
      <td>${r.mode_paiement || ''}</td>
      <td>${r.note || ''}</td>
      <td>${r.date_versement || ''}</td>
      <td>
        <div class="preview-box">
          ${r.justificatif ? 
            `<img src="/gestionMeknassa/${r.justificatif}" 
              alt="Justificatif" 
              style="max-height:50px; max-width:80px; border-radius:6px; object-fit:cover; box-shadow:0 0 3px #ccc;">` 
            : `<span class="text-muted">—</span>`}
        </div>
      </td>
      <td class="text-center">${printBtn}</td> 
    </tr>
  `;
                }).join("");

                $("#versementsBody").html(rows);
            } else {
                $("#versementsBody").html(
                    `<tr><td colspan="7" class="text-center text-muted p-3">Aucun versement trouvé</td></tr>`
                );
            }
        },
        error: function() {
            $("#versementsBody").html(
                `<tr><td colspan="7" class="text-center text-danger p-3">Erreur de chargement</td></tr>`
            );
        },
    });
}
// === UNIVERSAL IMAGE PREVIEW (ENLARGED VIEW) ===
document.addEventListener('click', (e) => {
    const target = e.target;

    // Ne déclenche que sur des <img> hors du modal existant
    if (target.tagName === 'IMG' && !target.closest('.modal-image-viewer')) {
        const src = target.getAttribute('src');
        if (!src) return;

        // Supprimer tout modal précédent
        const existing = document.querySelector('.modal-image-viewer');
        if (existing) existing.remove();

        // Créer le modal dynamiquement
        const viewer = document.createElement('div');
        viewer.className = 'modal fade modal-image-viewer';
        viewer.innerHTML = `
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 shadow-none">
          <div class="modal-body p-0 text-center position-relative">
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
              data-bs-dismiss="modal" aria-label="Fermer"></button>
            <img src="${src}" 
                 class="img-fluid rounded shadow-lg"
                 style="max-height:85vh; object-fit:contain; border-radius:10px;">
          </div>
        </div>
      </div>
    `;

        document.body.appendChild(viewer);

        // Lancer le modal Bootstrap
        const modal = new bootstrap.Modal(viewer, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modal.show();

        // Supprimer après fermeture (nettoyage propre)
        viewer.addEventListener('hidden.bs.modal', () => {
            viewer.remove();
        });
    }
});
$(document).on("click", "#refreshVersements", loadVersements);


$("#liste-tab").on("shown.bs.tab", function() {
    loadVersements();
});

$(document).on("click", ".print-versement", function () {
  const id = $(this).data("id");
  if (!id) return;

  const url = `/gestionMeknassa/bon-Huile/print_versement.php?id=${encodeURIComponent(id)}`;
  window.open(url, "_blank");
});
</script>

<?php require __DIR__ . '/../includes/scripts.php'; ?>