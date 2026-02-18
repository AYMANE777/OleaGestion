<?php

$activeSection = 'Transporteur';
$activePage = 'Liste des Transporteur';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';

require_once(__DIR__ . '/../config/config.ini.php');

$QueryGetPatrons = mysqli_query($connect, "SELECT * FROM Patron");
$QueryGetChauffeur = mysqli_query($connect, "SELECT * FROM chauffeur");

// Charger la liste des assurance du maroc (JSON local ou API)
$assurance = json_decode(file_get_contents(__DIR__ . "/../assets/assurance.json"), true);
$vehicule = json_decode(file_get_contents(__DIR__ . "/../assets/vehicule.json"), true);
$couleur = json_decode(file_get_contents(__DIR__ . "/../assets/couleur.json"), true);


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
                                        Transporteur
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-piece" type="button" role="tab" aria-controls="tab-piece"
                                        aria-selected="false">
                                        Listes des Transporteur
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-vehicule" type="button" role="tab"
                                        aria-controls="tab-piece" aria-selected="false">
                                        Vehicule
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
                                    <div class="card-title">Information du Patron</div>
                                </div>

                                <form class="needs-validation" id="addPatronFitour"
                                    action="../includes/ajax/Fitor/addPatronFitour.php" method="post"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="nom_patron" class="form-label">Nom :</label>
                                                <input type="text" class="form-control" id="nom_patron"
                                                    name="nom_patron" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="nom_machine" class="form-label">Prenom :</label>
                                                <input type="text" class="form-control" id="prenom_patron"
                                                    name="prenom_patron" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_patron" class="form-label">Image de Patron
                                                    (Optionnel)</label>
                                                <input type="file" class="form-control" id="image_patron"
                                                    name="image_patron" accept=".jpg,.jpeg,.png">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="num_tel_patron" class="form-label">Numero de Téléphone
                                                    :</label>
                                                <input type="number" class="form-control" id="num_tel_patron"
                                                    name="num_tel_patron" required>
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

                        <!-- Fin d'ajout du Patron -->

                        <!-- Debut d'ajout du vehicule -->
                        <div class="tab-pane fade" id="tab-vehicule" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Information du véhicule</div>
                                </div>

                                <form class="needs-validation" id="addVehiculeFitor"
                                 action="../includes/ajax/Fitor/addVehiculeFitor.php" method="post"
                                  enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">



                                            <div class="col-md-6">
                                                <label for="List_F_chauffeur" class="form-label">Chauffeur :</label>
                                                <select class="form-select js-choices" id="List_F_chauffeur"
                                                    name="List_F_chauffeur" required>
                                                    
                                                    <?php foreach ($QueryGetChauffeur as $chauffeur): ?>
                                                        <option
                                                            value="<?= htmlspecialchars($chauffeur['id_Fchauffeur']) ?>">
                                                            <?= htmlspecialchars($chauffeur['nom']) . ' ' . htmlspecialchars($chauffeur['prenom']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="Type de vehicule" class="form-label">Type du véhicule
                                                    (Optionnel) :</label>
                                                <select class="form-select js-choices" id="List_type_vehicule"
                                                    name="List_type_vehicule" required>
                                                    <?php foreach ($vehicule as $v): ?>
                                                        <option value="<?= htmlspecialchars($v) ?>">
                                                            <?= htmlspecialchars($v) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nom_vehicule" class="form-label">Nom de Véhicule :</label>
                                                <input type="text" class="form-control" id="nom_vehicule"
                                                    name="nom_vehicule" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="matricule" class="form-label">Matricule :</label>
                                                <input type="text" class="form-control" id="matricule_vehicule"
                                                    name="matricule_vehicule" required>
                                            </div>


                                            <div class="col-md-6">
                                                <label for="image_vehicule" class="form-label">Image Véhicule :</label>
                                                <input type="file" class="form-control" id="image_vehicule"
                                                    name="image_vehicule" accept=".jpg,.jpeg,.png">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="ref_piece" class="form-label">Type d'assurance (Optionnel)
                                                    :</label>
                                                <select class="form-select js-choices" id="List_type_assurance"
                                                    name="List_type_assurance">
                                                    <option value="null">-- Sélectionner un chauffeur --</option>
                                                    <?php foreach ($assurance as $v): ?>
                                                        <option value="<?= htmlspecialchars($v) ?>">
                                                            <?= htmlspecialchars($v) ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="date_expiration" class="form-label">date expiration (Optionnel)
                                                    :</label>
                                                <input type="date" class="form-control" id="date_expiration"
                                                    name="date_expiration">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="color-vehicule" class="form-label">Couleur du vehicule
                                                    :</label>
                                                <select class="form-select js-choices" id="List_couleur_vehicule"
                                                    name="List_couleur_vehicule" required>
                                                    <?php foreach ($couleur as $v): ?>
                                                        <option value="<?= htmlspecialchars($v) ?>">
                                                            <?= htmlspecialchars($v) ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-primary text-white" type="submit"
                                            name="btn_piece">Ajouter</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                        <!-- Fin d'ajout du du vehicule -->


                        <!-- debut d'ajout du chauffeur -->
                        <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Information du Chauffeur</div>
                                </div>

                                <form class="needs-validation" id="addChauffeurFitor"
                                    action="../includes/ajax/Fitor/addChauffeurFitor.php" method="post"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="List_patron_name" class="form-label">Patron :</label>
                                                <select class="form-select js-choices" id="List_patron_name"
                                                    name="List_patron_name" required>
                                                    <?php foreach ($QueryGetPatrons as $patron): ?>
                                                        <option value="<?= htmlspecialchars($patron['id_patron']) ?>">
                                                            <?= htmlspecialchars($patron['nom_patron']) . ' ' . htmlspecialchars($patron['prenom_patron']) ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_piece" class="form-label">Téléphone :</label>
                                                <input type="tel" class="form-control" id="num_Fchauffeur" minlength="10" maxlength="10"
                                                    name="num_Fchauffeur" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="nom_piece" class="form-label">Nom :</label>
                                                <input type="text" class="form-control" id="nom_Fchauffeur"
                                                    name="nom_Fchauffeur" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_piece" class="form-label">Prénom :</label>
                                                <input type="text" class="form-control" id="prenom_Fchauffeur"
                                                    name="prenom_Fchauffeur" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="image_Fchauffeur" class="form-label">Image de Chauffeur
                                                    (Optionnel)</label>
                                                <input type="file" class="form-control" id="image_Fchauffeur"
                                                    name="image_Fchauffeur" accept=".jpg,.jpeg,.png">
                                            </div>

                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-primary text-white" type="submit"
                                            name="btn_piece">Ajouter</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                        <!-- Fin d'ajout du chauffeur  -->

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