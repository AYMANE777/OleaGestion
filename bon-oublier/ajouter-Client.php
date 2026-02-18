<?php

$activeSection = 'bon-oublier';
$activePage = 'bon-ajouter-oublier';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
require_once(__DIR__ . '/../config/config.ini.php');


?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="card card-outline card-info mb-0">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="formTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="machine-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-machine" type="button" role="tab"
                                        aria-controls="tab-machine" aria-selected="true">
                                        Générer Bon
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-piece" type="button" role="tab" aria-controls="tab-piece"
                                        aria-selected="false">
                                        Listes des Oubliable
                                    </button>
                                </li>


                            </ul>
                        </div>
                    </div>

                    <div class="tab-content mt-3" id="formTabsContent">

                        <!-- Debut d'ajout du Patron -->
                        <div class="tab-pane fade show active" id="tab-machine" role="tabpanel"
                            aria-labelledby="machine-tab">

                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Ajouter un oubliable</div>
                                </div>

                                <form class="needs-validation" id="addClientOublier"
                                    action="../includes/ajax/Oubliable/addClientOublier.php" method="post"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="nom_client_forget" class="form-label">Nom </label>
                                                <input type="text" class="form-control" id="nom_client_forget"
                                                    name="nom_client_forget" required>
                                            </div>


                                            <div class="col-md-6">
                                                <label for="nom_machine" class="form-label">Prenom </label>
                                                <input type="text" class="form-control" id="prenom_client_forget"
                                                    name="prenom_client_forget" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="cin_bon_client_forget" class="form-label">CIN </label>
                                                <input type="text" class="form-control" id="cin_bon_client_forget"
                                                    name="cin_bon_client_forget" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="ref_bon_client_forget" class="form-label">Ref Bon : </label>
                                                <input type="text" class="form-control" id="ref_bon_forg"
                                                    name="ref_bon_forg" required>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="qte_bon_client_forget" class="form-label">Qte </label>
                                                <input type="number" min="0" class="form-control"
                                                    id="qte_bon_client_forget" name="qte_bon_client_forget" required>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="provider_client" class="form-label">Provider : </label>
                                                <select class="form-select" id="provider_client_forget"
                                                    name="provider_client_forget" required>
                                                    <option value="Youssef">Youssef</option>
                                                    <option value="Hamid">Hamid</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="num_tel_client_forget" class="form-label">Numéro de
                                                    téléphone
                                                </label>
                                                <input type="tel" class="form-control" id="num_tel_client_forget"
                                                    name="num_tel_client_forget" minlength="10" maxlength="10" required>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="image_client_forget" class="form-label">Preuve
                                                    (Optionnel)</label>
                                                <input type="file" class="form-control" id="image_client_forget"
                                                    name="image_client_forget" accept=".jpg,.jpeg,.png">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="description_piece" class="form-label">Note Optionnel</label>
                                                <textarea class="form-control" id="description_piece"
                                                    name="description_piece"></textarea>
                                            </div>



                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-primary text-white" type="submit"
                                            name="btn_add_client_oublier">Ajouter</button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <!-- Fin d'ajout du Patron -->




                        <!-- debut d'ajout du chauffeur -->
                        <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Listes des Bons Oubliable</div>
                                </div>


                                <div class="container-fluid py-4">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card shadow-sm">
                                                <div class="card-body">

                                                    <div
                                                        class="d-flex align-items-center justify-content-between flex-wrap gap-3 sticky-tools p-2 rounded mb-3">
                                                        <h4 class="m-0">Liste des Bons</h4>
                                                        <div class="d-flex gap-2">
                                                            <input class="form-control" style="min-width:260px"
                                                                id="searchBidon"
                                                                placeholder="Recherche (Nom, Type, Prix)...">
                                                            <select class="form-select" id="perPagePatron"
                                                                style="width:140px">
                                                                <option value="10">10 / page</option>
                                                                <option value="25" selected>25 / page</option>
                                                                <option value="50">50 / page</option>
                                                                <option value="100">100 / page</option>
                                                            </select>
                                                            <button class="btn btn-outline-secondary"
                                                                id="btnRefreshbidonBon" title="Actualiser">
                                                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="table-responsive">
                                                        <table class="table align-middle table-hover" id="bonbidon">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Client</th>
                                                                    <th>Ref</th>
                                                                    <th>Qté</th>
                                                                    <th>Provider</th>
                                                                    <th>Telephone</th>
                                                                    <th>Date Création</th>

                                                                    <th class="text-end">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="BidonBody"></tbody>
                                                        </table>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <div><span id="BidonInfo" class="text-muted"></span></div>
                                                        <div class="btn-group">
                                                            <button class="btn btn-outline-secondary" id="prevBidon">←
                                                                Précédent</button>
                                                            <button class="btn btn-outline-secondary"
                                                                id="nextBidon">Suivant →</button>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>


                            </div>
                        </div>
                        <!-- Fin d'ajout du chauffeur  -->

                    </div>

                </div>
            </div>

            <script>
                $(document).ready(function () {
    let currentPage = 1;
    let limit = 25;

    function loadOublierList() {
        const search = $("#searchBidon").val();

        $.getJSON("../includes/ajax/Oubliable/getClientOublier.php", { page: currentPage, limit, search }, function (response) {
            if (response.status === "success") {
                const tbody = $("#BidonBody");
                tbody.empty();

                if (response.data.length === 0) {
                    tbody.append(`<tr><td colspan="7" class="text-center text-muted">Aucun enregistrement trouvé</td></tr>`);
                } else {
                    response.data.forEach(row => {
                        tbody.append(`
                            <tr>
                                <td>${row.client}</td>
                                <td>${row.ref_bon}</td>
                                <td>${row.qte}</td>
                                <td>${row.provider}</td>
                                <td>${row.telephone}</td>
                                <td>${row.created_at}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-info voirDetailOublier" data-id="${row.id}"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary printOublier" data-id="${row.id}"><i class="bi bi-printer"></i></button>
                                </td>
                            </tr>
                        `);
                    });
                }

                $("#BidonInfo").text(`Page ${response.page}, affichant ${response.data.length} / ${response.total}`);
            }
        });
    }

    $("#searchBidon").on("input", function () {
        currentPage = 1;
        loadOublierList();
    });

    $("#perPagePatron").on("change", function () {
        limit = parseInt($(this).val());
        loadOublierList();
    });

    $("#prevBidon").on("click", function () {
        if (currentPage > 1) {
            currentPage--;
            loadOublierList();
        }
    });

    $("#nextBidon").on("click", function () {
        currentPage++;
        loadOublierList();
    });

    $("#btnRefreshbidonBon").on("click", function () {
        loadOublierList();
    });

    loadOublierList();
});
$(document).on("click", ".printOublier", function () {
  const bonId = $(this).data("id");

  Swal.fire({
    title: "Vérification du bon...",
    text: "Patientez un instant.",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../includes/ajax/Oubliable/checkAndCreateBon.php",
    method: "POST",
    dataType: "json",
    data: { id: bonId },
    success: function (response) {
      Swal.close();

      if (response.status === "success" || response.status === "exists") {
        // ✅ Open the print page
        window.open("printOublier.php?id=" + bonId, "_blank");
      } else {
        Swal.fire({
          icon: "error",
          title: "Erreur",
          text: response.message,
        });
      }
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur serveur",
        text: xhr.responseText || "Une erreur est survenue lors de la vérification.",
      });
    },
  });
});
// 🔹 When clicking the "voir" (eye) button
$(document).on("click", ".voirDetailOublier", function () {
  const bonId = $(this).data("id");

  $("#detailsOublierBody").html(`<div class="text-center text-muted py-3">Chargement...</div>`);
  $("#voirOublierModal").modal("show");

  $.ajax({
    url: "../includes/ajax/Oubliable/getOublierDetails.php",
    method: "GET",
    dataType: "json",
    data: { id: bonId },
    success: function (response) {
      if (response.status === "success") {
        const c = response.data;
        $("#detailsOublierBody").html(`
          <div class="row g-3">
            <div class="col-md-6">
              <label class="fw-bold">Nom Complet :</label>
              <p>${c.nom} ${c.prenom}</p>
            </div>
            <div class="col-md-6">
              <label class="fw-bold">CIN :</label>
              <p>${c.cin}</p>
            </div>
            <div class="col-md-6">
              <label class="fw-bold">Référence Bon :</label>
              <p>${c.ref_bon}</p>
            </div>
            <div class="col-md-6">
              <label class="fw-bold">Quantité :</label>
              <p>${c.qte}</p>
            </div>
            <div class="col-md-6">
              <label class="fw-bold">Provider :</label>
              <p>${c.provider}</p>
            </div>
            <div class="col-md-6">
              <label class="fw-bold">Téléphone :</label>
              <p>${c.telephone}</p>
            </div>
            <div class="col-md-12">
              <label class="fw-bold">Note :</label>
              <p>${c.note || "<span class='text-muted'>Aucune note</span>"}</p>
            </div>
            ${c.preuve ? `
              <div class="col-md-12 text-center">
                <label class="fw-bold">Preuve :</label><br>
                <img src="../${c.preuve}" alt="Preuve" class="img-fluid rounded shadow-sm mt-2" style="max-height:250px">
              </div>` : ""
            }
            <div class="col-md-12 text-end text-muted mt-3">
              <small>Créé le : ${c.created_at}</small>
            </div>
          </div>
        `);
      } else {
        $("#detailsOublierBody").html(`<div class="text-danger text-center py-3">${response.message}</div>`);
      }
    },
    error: function (xhr) {
      $("#detailsOublierBody").html(`<div class="text-danger text-center py-3">Erreur lors du chargement des détails.</div>`);
    }
  });
});


                (() => {
                    'use strict';
                    const forms = document.querySelectorAll('.needs-validation');
                    Array.from(forms).forEach((form) => {
                        form.addEventListener('submit', (event) => {
                            if (!form.checkValidity()) {
                                event.preventDefault();
                                event.stopPropagation();
                            }
                            form.classList.add('was-validated');
                        }, false);
                    });

                    const tabButtons = document.querySelectorAll('#formTabs [data-bs-toggle="tab"]');
                    tabButtons.forEach(btn => {
                        btn.addEventListener('shown.bs.tab', () => {
                            localStorage.setItem('activeFormTab', btn.getAttribute('data-bs-target'));
                        });
                    });
                    const saved = localStorage.getItem('activeFormTab');
                    if (saved) {
                        const triggerEl = document.querySelector(`[data-bs-target="${saved}"]`);
                        if (triggerEl) new bootstrap.Tab(triggerEl).show();
                    }
                })();
                
            </script>

        </div>
    </div>
    </div>
</main>
<!-- 🔹 Modal: Voir Détails Bon Oubliable -->
<div class="modal fade" id="voirOublierModal" tabindex="-1" aria-labelledby="voirOublierModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="voirOublierModalLabel">Détails du Bon Oubliable</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div id="detailsOublierBody">
          <div class="text-center text-muted">Chargement des données...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/scripts.php'; ?>