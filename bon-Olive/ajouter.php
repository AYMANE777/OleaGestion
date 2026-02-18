<?php

$activeSection = 'bon';   
$activePage    = 'bon-ajouter'; 

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
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
                                        L'inventaire des machines
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-piece" type="button" role="tab" aria-controls="tab-piece"
                                        aria-selected="false">
                                        L'inventaire des pièces
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="tab-content mt-3" id="formTabsContent">
                        <div class="tab-pane fade show active" id="tab-machine" role="tabpanel"
                            aria-labelledby="machine-tab">

                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Ajouter une machine</div>
                                </div>

                                <form class="needs-validation" action="#" method="post" enctype="multipart/form-data"
                                    novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="ref_machine" class="form-label">Référence de machine</label>
                                                <input type="text" class="form-control" id="ref_machine"
                                                    name="ref_machine" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="nom_machine" class="form-label">Nom de machine</label>
                                                <input type="text" class="form-control" id="nom_machine"
                                                    name="nom_machine" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_machine" class="form-label">Image de machine</label>
                                                <input type="file" class="form-control" id="image_machine"
                                                    name="image_machine" accept=".jpg,.jpeg,.png">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="description_machine" class="form-label">Description de
                                                    machine</label>
                                                <textarea class="form-control" id="description_machine"
                                                    name="description_machine"></textarea>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="fournisseur_machine" class="form-label">Fournisseur de
                                                    machine</label>
                                                <input type="text" class="form-control" id="fournisseur_machine"
                                                    name="fournisseur_machine" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="statut_machine" class="form-label">Statut de machine</label>
                                                <select class="form-select" id="statut_machine" name="statut_machine"
                                                    required>
                                                    <option value="en_marche">En marche</option>
                                                    <option value="en_panne">En panne</option>
                                                    <option value="au_magasin">Au magasin</option>
                                                    <option value="en_maintenance">En maintenance</option>
                                                </select>
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

                        <div class="tab-pane fade" id="tab-piece" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Ajouter une pièce</div>
                                </div>

                                <form class="needs-validation" action="#" method="post" enctype="multipart/form-data"
                                    novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="type_piece" class="form-label">Type de pièce</label>
                                                <select class="form-select" id="type_piece" name="type_piece" required>
                                                    <option value="piece_bureautique">Pièce bureautique</option>
                                                    <option value="piece_mecanique">Pièce mécanique</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="ref_piece" class="form-label">Référence de pièce</label>
                                                <input type="text" class="form-control" id="ref_piece" name="ref_piece"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="nom_piece" class="form-label">Nom de pièce</label>
                                                <input type="text" class="form-control" id="nom_piece" name="nom_piece"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="image_piece" class="form-label">Image de pièce</label>
                                                <input type="file" class="form-control" id="image_piece"
                                                    name="image_piece" accept=".jpg,.jpeg,.png">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="description_piece" class="form-label">Description de
                                                    pièce</label>
                                                <textarea class="form-control" id="description_piece"
                                                    name="description_piece"></textarea>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="fournisseur_piece" class="form-label">Fournisseur de
                                                    pièce</label>
                                                <input type="text" class="form-control" id="fournisseur_piece"
                                                    name="fournisseur_piece" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="statut_piece" class="form-label">Statut de pièce</label>
                                                <select class="form-select" id="statut_piece" name="statut_piece"
                                                    required>
                                                    <option value="en_marche">En marche</option>
                                                    <option value="en_panne">En panne</option>
                                                    <option value="au_magasin">Au magasin</option>
                                                    <option value="en_maintenance">En maintenance</option>
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