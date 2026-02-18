<style>
/* ===== Versements table — lively polish ===== */
#tab-vercement .table-wrap {
    border-color: #2b2f36;
}

#tab-vercement thead th {
    color: white;
    background: #0DCAF0;
    border-bottom-color: #1f242b !important;
}

#tab-vercement tbody tr.data-row:hover {
    background: #0f1317;
}

#tab-vercement tbody td {
    border-top-color: #1b2026;
}

#tab-vercement .details-row {
    background: #d7dadcff;
}

#tab-vercement .d-item {
    background: #d7dadcff;
    border-color: #1b2026;
    align-items: center;
    text-align: center;
}

#tab-vercement .d-k {
    color: black;
    font-weight: bold;
}

#tab-vercement .d-img-link {
    border-color: #2b2f36;
}

#tab-vercement .d-img-link:hover {
    border-color: #3a3f47;
    box-shadow: 0 2px 10px rgba(0, 0, 0, .35);
}

.d-images {
    justify-content: center;
}
</style>


<?php
$activeSection = 'bon-Fitor';
$activePage = 'Versement';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';

require_once(__DIR__ . '/../config/config.ini.php');
$QueryGetChauffeur = mysqli_query($connect, "SELECT * FROM chauffeur");
$QueryGetChauffeurVersement = mysqli_query($connect, "SELECT DISTINCT c.id_Fchauffeur, c.nom, c.prenom
FROM chauffeur c
INNER JOIN versement v
  ON c.id_Fchauffeur = v.id_Fchauffeur;");


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
                                        Versement
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-vercement" type="button" role="tab"
                                        aria-controls="tab-vercement" aria-selected="false">
                                        List des Versements
                                    </button>
                                </li>



                            </ul>
                        </div>
                    </div>

                    <div class="tab-content mt-3" id="formTabsContent">

                        <!-- Debut d'ajout du Versement -->
                        <div class="tab-pane fade show active" id="tab-machine" role="tabpanel"
                            aria-labelledby="machine-tab">

                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Ajouter un versement</div>
                                </div>

                                <form class="needs-validation" id="addVersementChauffeur"
                                    action="../includes/ajax/Fitor/Versement/addVersement.php" method="post"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="nom_machine" class="form-label">Chauffeur </label>
                                                <select class="form-select js-choices" id="List_F_chauffeur"
                                                    name="List_F_chauffeur" required>
                                                    <option value="null">Selectionner</option>
                                                    <?php foreach ($QueryGetChauffeur as $chauffeur): ?>
                                                    <option
                                                        value="<?= htmlspecialchars($chauffeur['id_Fchauffeur']) ?>">
                                                        <?= htmlspecialchars($chauffeur['nom']) . ' ' . htmlspecialchars($chauffeur['prenom']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="montant_verser" class="form-label">Montant en MAD </label>
                                                <input type="number" class="form-control" id="montant_verser"
                                                    name="montant_verser">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="paiment_methode" class="form-label">Mode de Paiment
                                                </label>
                                                <select class="form-select js-choices" id="paiment_methode"
                                                    name="paiment_methode" required>
                                                    <option value="espece">espece</option>
                                                    <option value="virement">virement</option>
                                                    <option value="cheque">cheque</option>
                                                    <option value="LCN">LCN</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="image_versement" class="form-label"> Justification du
                                                    paiement (en cas de chèque, virement ou LCN)</label>
                                                <input type="file" class="form-control" id="image_versement"
                                                    name="image_versement" accept=".jpg,.jpeg,.png">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="note" class="form-label">Note (Optionnel) </label>
                                                <textarea class="form-control" name="note" id="note"
                                                    rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-primary text-white" type="submit"
                                            name="btn_machine">Ajouter</button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <!-- Fin d'ajout du Versement -->





                        <!-- debut de Liste du Versement -->
                        <div class="tab-pane fade" id="tab-vercement" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Information des Versements</div>
                                </div>
                                <!-- Debut du Table Striped -->
                                <div class="card mb-4">
                                    <div class="card-header py-2">
                                        <div class="row g-3 align-items-center">

                                            <!-- Radios -->
                                            <div class="col-12 col-md-6">
                                                <fieldset class="m-0 p-0">
                                                    <legend class="form-label mb-1 fw-semibold">Rechercher par :
                                                    </legend>
                                                </fieldset>
                                            </div>

                                            <!-- Chauffeur select -->
                                            <div class="col-12 col-md-6" id="chauffeurSelectContainer">
                                                <!-- <label for="List_F_chauffeur_vercement"
                                                    class="form-label mb-1">Chauffeur :</label> -->
                                                <select class="form-select js-choices" id="List_F_chauffeur_vercement"
                                                    name="List_F_chauffeur_vercement">
                                                    <option value="null">Sélectionner</option>
                                                    <?php foreach ($QueryGetChauffeurVersement as $chauffeur): ?>
                                                    <option
                                                        value="<?= htmlspecialchars($chauffeur['id_Fchauffeur']) ?>">
                                                        <?= htmlspecialchars($chauffeur['nom']) . ' ' . htmlspecialchars($chauffeur['prenom']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Patron select (hidden by default) -->
                                            <div class="col-12 col-md-6 d-none" id="patronSelectContainer">
                                                <label for="List_F_patron_vercement" class="form-label mb-1">Patron
                                                    :</label>
                                                <select class="form-select js-choices" id="List_F_patron_vercement"
                                                    name="List_F_patron_vercement">
                                                    <option value="null">Sélectionner</option>
                                                    <?php foreach ($QueryGetPatronVersement as $patron): ?>
                                                    <option value="<?= htmlspecialchars($patron['id_Patron']) ?>">
                                                        <?= htmlspecialchars($patron['nom']) . ' ' . htmlspecialchars($patron['prenom']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- /.card-header -->

                                    <div class="card-body p-0">
                                        <div class="table-wrap">
                                            <table class="table table-striped align-middle text-center">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 10px">#</th>
                                                        <th>Patron</th>
                                                        <th>Versement</th>
                                                        <th>Prix (MAD)</th>
                                                        <th>Note</th>
                                                        <th>Mode de Paiement</th>
                                                        <th>Action</th> <!-- ✅ nouvelle colonne -->
                                                    </tr>
                                                </thead>
                                                <tbody id="versementTableBody">
                                                    <tr>
                                                        <td colspan="7" class="text-center">Aucun versement trouvé</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>

                                    </div>

                                </div>
                                <!-- Fin du Table Striped -->
                            </div>
                        </div>
                        <!-- Fin de Liste du Versement  -->

                    </div>

                </div>
            </div>

            <script>
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

<?php require __DIR__ . '/../includes/scripts.php'; ?>