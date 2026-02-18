<?php

$activeSection = 'bon-Fitor';
$activePage = 'Taratonage';

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';


require_once(__DIR__ . '/../config/config.ini.php');






$QueryGetChauffeur = mysqli_query($connect, "SELECT * FROM chauffeur");
$QueryGetData = mysqli_query($connect, "SELECT *
FROM `chauffeur` inner JOIN `vehicule` ON `vehicule`.`id_Fchauffeur` = `chauffeur`.`id_Fchauffeur` 
	inner JOIN `taratonage` ON `taratonage`.`id_vehicule_Fitor` = `vehicule`.`id_vehicule`;");
$QueryGetDataDistinct = mysqli_query($connect, "SELECT 
    p.nom_patron,
    p.prenom_patron,
    v.id_vehicule,
    c.id_Fchauffeur,
    v.nom_vehicule,
    v.matricule,
    c.nom,
    c.prenom,
    c.image_Fchauffeur,
    COUNT(t.id_taratonage) AS nb_taratonages,
       COALESCE(SUM(t.poid_net), 0)   AS total_poid_net,
    COALESCE(SUM(t.poid_brut), 0)  AS total_poid_brut,
    COALESCE(SUM(t.poid_tarat), 0) AS total_poid_tarat
FROM patron p LEFT JOIN chauffeur c ON p.id_patron = c.id_patron
LEFT JOIN vehicule v 
    ON v.id_Fchauffeur = c.id_Fchauffeur
LEFT JOIN taratonage t 
    ON t.id_vehicule_Fitor = v.id_vehicule
GROUP BY c.id_Fchauffeur, c.nom, c.prenom
ORDER BY nb_taratonages DESC;");











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
                                        Déclancher une Taratonage
                                    </button>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-vehicule" type="button" role="tab"
                                        aria-controls="tab-piece" aria-selected="false">
                                        Listes des Taratonages
                                    </button>
                                </li>

                            </ul>
                        </div>
                    </div>

                    <div class="tab-content mt-3" id="formTabsContent">

                        <!-- Debut du Taratonage -->
                        <div class="tab-pane fade show active" id="tab-machine" role="tabpanel"
                            aria-labelledby="machine-tab">

                            <div class="card card-info card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Information du Taratonage</div>
                                </div>

                                <form class="needs-validation" id="addTaratonageFitour"
                                    action="../includes/ajax/Fitor/Taratonage/addTaratonageFitour.php" method="post"
                                    enctype="multipart/form-data" novalidate>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label for="nom_patron" class="form-label">Chauffeur :</label>
                                                <select class="form-select js-choices" id="List_F_chauffeur"
                                                    name="List_F_chauffeur" required>
                                                    <option value="null" selected disabled>--Selectionner Chauffeur--
                                                    </option>
                                                    <?php foreach ($QueryGetChauffeur as $chauffeur): ?>
                                                        <option
                                                            value="<?= htmlspecialchars($chauffeur['id_Fchauffeur']) ?>">
                                                            <?= htmlspecialchars($chauffeur['nom']) . ' ' . htmlspecialchars($chauffeur['prenom']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nom_patron" class="form-label">Vehicule :</label>
                                                <select class="form-select js-choices" id="F_chauffeur_Vehicule"
                                                    name="F_chauffeur_Vehicule" required>

                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="poid_taratone" class="form-label">Poid Taratone :</label>
                                                <input type="number" class="form-control" id="poid_taratone"
                                                    name="poid_taratone" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="poid_brut" class="form-label">Poid Brut :</label>
                                                <input type="number" class="form-control" id="poid_brut"
                                                    name="poid_brut" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="poid_net" class="form-label">Poid Net : </label>
                                                <input type="number" class="form-control" id="poid_net" name="poid_net"
                                                    readonly>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="poid_brut" class="form-label">Note (Optionnel) :</label>
                                                <textarea type="text" class="form-control" id="note"
                                                    name="note"></textarea>
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
                        <!-- Fin d'ajout du Taratonage -->

                        <!-- Debut du Liste de Taratonage -->
                        <div class="tab-pane fade" id="tab-vehicule" role="tabpanel" aria-labelledby="piece-tab">
                            <div class="container-fluid">
                                <div class="card">
                                    <div
                                        class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                        <h3 class="card-title mb-0">Liste des Taratonages</h3>

                                        <!-- Barre de recherche -->
                                        <div class="input-group input-group-sm" style="max-width: 320px;">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="bi bi-search text-muted"></i>
                                            </span>
                                            <input type="text" id="searchInput"
                                                class="form-control border-start-0 shadow-none" placeholder="Rechercher"
                                                autocomplete="off" aria-label="Rechercher une machine" />
                                            <button class="btn btn-outline-secondary d-none" id="clearSearch"
                                                type="button" aria-label="Effacer la recherche">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table m-0 align-middle" id="ordersTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="min-width:140px;">Patron</th>
                                                        <th style="min-width:140px;">Chauffeur</th>
                                                        <th style="min-width:180px;">Véhicule</th>

                                                        <th style="width:120px;">Taratonage (Kg)</th>
                                                        <th style="width:120px;">Enregistremnt</th>

                                                        <th style="width:120px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    while ($row = mysqli_fetch_assoc($QueryGetDataDistinct)):

                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <?= htmlspecialchars($row['nom_patron']) . ' ' . htmlspecialchars($row['prenom_patron']); ?>
                                                            </td>
                                                            <td>
                                                                <?= htmlspecialchars($row['nom']) . ' ' . htmlspecialchars($row['prenom']); ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['nom_vehicule']) . ' ' . htmlspecialchars($row['matricule']); ?>
                                                            </td>
                                                            <td>

                                                                <span
                                                                    class="badge text-bg-success"><?= $row['total_poid_net'] ?></span>
                                                            </td>

                                                            <td>
                                                                <span class="badge text-bg-primary">
                                                                    <?= (int) $row['nb_taratonages'] ?>
                                                                </span>
                                                            </td>

                                                            <td class="text-center">
                                                                <!-- Bouton VOIR -->
                                                                <form method="post" id="handlerVoir"
                                                                    action="../includes/ajax/Fitor/Taratonage/getListinfoTaratonage.php">
                                                                    <button type="button" name="clickable" id="clickable"
                                                                        value="<?= (int) $row['id_Fchauffeur'] ?>"
                                                                        class="btn btn-sm btn-outline-primary me-1 btn-view"
                                                                        title="Voir" data-bs-toggle="modal"
                                                                        data-chauffeur="<?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?>"
                                                                        data-id_Fchauffeur="<?= htmlspecialchars($row['id_Fchauffeur']) ?>"
                                                                        data-vehicule="<?= htmlspecialchars($row['nom_vehicule'] . ' ' . $row['matricule']) ?>"
                                                                        data-bs-target="#viewPieceModal" data-id="42"
                                                                        data-statut="Shipped"
                                                                        data-desc="Description brève de la machine…"
                                                                        data-imgFchauffeur="<?= htmlspecialchars($row['image_Fchauffeur'] ?? '') ?>">
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>
                                                                </form>

                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>

                                            <!-- Aucun resultat -->
                                            <div id="noResults" class="text-center py-4 d-none text-muted">
                                                <i class="bi bi-search me-1"></i> Aucun résultat pour votre recherche.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal update -->
                                <div class="modal fade" id="editTaratonageModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form id="editTaratonageForm" class="modal-content"
                                            action="../includes/ajax/Fitor/Taratonage/update_taratonage.php"
                                            method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modifier Taratonage</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Fermer"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_taratonage" id="e_id_taratonage">
                                                <div class="mb-3">
                                                    <label class="form-label">Poid Brut (kg)</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="poid_brut" id="e_poid_brut" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Poid Tarat (kg)</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="poid_taratone" id="e_poid_taratone" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Poid Net (kg)</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="e_poid_net" readonly>
                                                    <div class="form-text">Calculé automatiquement : Brut − Tarat</div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Note</label>
                                                    <textarea class="form-control" name="note" id="e_note"
                                                        rows="2"></textarea>
                                                </div>
                                                <div id="e_alert" class="alert alert-warning d-none"></div>
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-secondary" type="button"
                                                    data-bs-dismiss="modal">Annuler</button>
                                                <button class="btn btn-primary" type="submit"
                                                    id="e_submit">Enregistrer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- End Modal update -->
                                <!-- Modal Voir -->
                                <div class="modal fade" id="viewPieceModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Détails de cette Chauffeur
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Fermer"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="row g-3">

                                                    <!-- Image -->
                                                    <div class="col-md-4 text-center">
                                                        <div class="border rounded p-1"
                                                            style="width:100%;max-width:200px;height:200px;display:flex;align-items:center;justify-content:center;margin:auto;">
                                                            <img id="v-imgFchauffeur" src="" alt="Image de chauffeur"
                                                                style="max-width:100%;max-height:100%;object-fit:cover;">
                                                        </div>
                                                    </div>

                                                    <!-- Infos texte -->
                                                    <div class="col-md-8">
                                                        <p></p><br/>
                                                        <p><strong>Chauffeur :</strong> <span id="v-chauffeur"
                                                                class="text-dark"></span></p>
                                                        <p><strong>Véhicule :</strong> <span id="v-vehicule"
                                                                class="text-dark"></span></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <div class="card" id="tarat-zone">
                                                        <!-- Barre de recherche -->
                                                        <div
                                                            class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                                            <h3 class="card-title mb-0">Taratonage de Chauffeur</h3>

                                                            <!-- Barre de recherche -->
                                                            <div class="input-group input-group-sm"
                                                                style="max-width: 320px;">
                                                                <span class="input-group-text bg-white border-end-0">
                                                                    <i class="bi bi-search text-muted"></i>
                                                                </span>
                                                                <input type="text" id="searchInputTar"
                                                                    class="form-control border-start-0 shadow-none"
                                                                    placeholder="Rechercher" autocomplete="off"
                                                                    aria-label="Rechercher une machine" />
                                                                <button class="btn btn-outline-secondary d-none"
                                                                    id="clearSearch" type="button"
                                                                    aria-label="Effacer la recherche">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table m-0 align-middle details-Fchauffeur"
                                                                id="ordersTableTar">

                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th style="min-width:140px;">Chauffeur</th>
                                                                    </tr>
                                                                </thead>

                                                                <!-- TBODY CUSTOMIZE -->
                                                                <tbody id="add-info-taratonage">
                                                                </tbody>
                                                            </table>
                                                            <!-- Aucun resultat -->
                                                            <div id="noResultsTar"
                                                                class="text-center py-4 d-none text-muted">
                                                                <i class="bi bi-search me-1"></i> Aucun résultat pour
                                                                votre
                                                                recherche.
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-dark"
                                                                    data-bs-dismiss="modal">Fermer</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- /modal -->
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
                        <!-- Fin du Liste de Taratonage -->
                    </div>
                </div>

                <script>
                    document.addEventListener("DOMContentLoaded", () => {
                            
                    // ====== Calcul Poid Net (Brut - Taratone) ======
                        const taratoneInput = document.getElementById("poid_taratone");
                        const brutInput = document.getElementById("poid_brut");
                        const netInput = document.getElementById("poid_net");

                        function calculerNet() {
                            const taratone = parseFloat(taratoneInput?.value || "0");
                            const brut = parseFloat(brutInput?.value || "0");
                            const net = brut - taratone;
                            if (netInput) netInput.value = net >= 0 ? net : 0;
                        }
                        taratoneInput?.addEventListener("input", calculerNet);
                        brutInput?.addEventListener("input", calculerNet);
                    
                        // ====== Remplissage véhicules par chauffeur (onglet Déclencher) ======
                        const tab = document.querySelector("#tab-machine");
                        const chauffeurSelect = tab?.querySelector("#List_F_chauffeur");
                        const vehiculeSelect = tab?.querySelector("#F_chauffeur_Vehicule");
                        let vehiculeChoices = null;

                        if (vehiculeSelect) {
                            if (vehiculeSelect.choices) {
                                vehiculeChoices = vehiculeSelect.choices;
                            } else if (window.Choices) {
                                vehiculeChoices = new Choices(vehiculeSelect, {
                                    searchEnabled: true,
                                    shouldSort: false,
                                    placeholderValue: '--Sélectionner Véhicule--'
                                });
                            }
                        }
                        const renderVehicules = (list) => {
                            if (!vehiculeSelect) return;
                            if (vehiculeChoices) {
                                vehiculeChoices.clearStore();
                                vehiculeChoices.setChoices(
                                    [{ value: "", label: "--Sélectionner Véhicule--", disabled: true, selected: true },
                                    ...list.map(v => ({ value: v.id_vehicule, label: `${v.nom_vehicule} (${v.matricule})` }))],
                                    'value', 'label', true
                                );
                            } else {
                                vehiculeSelect.innerHTML = '<option value="" disabled selected>--Sélectionner Véhicule--</option>';
                                list.forEach(v => {
                                    const opt = document.createElement("option");
                                    opt.value = v.id_vehicule;
                                    opt.textContent = `${v.nom_vehicule} (${v.matricule})`;
                                    vehiculeSelect.appendChild(opt);
                                });
                            }
                        };
                        chauffeurSelect?.addEventListener("change", () => {
                            const chauffeurId = chauffeurSelect.value;
                            if (!chauffeurId || chauffeurId === "null") return renderVehicules([]);
                            fetch("../includes/ajax/Fitor/Versement/getVehiculesByChauffeur.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: "id_Fchauffeur=" + encodeURIComponent(chauffeurId)
                            })
                                .then(r => r.json())
                                .then(d => renderVehicules(Array.isArray(d) ? d : (d?.vehicules ?? [])))
                                .catch(() => renderVehicules([]));
                        });
                        // ====== Modal “Voir” : 1 seul listener + rendu ======
                        const viewModal = document.getElementById('viewPieceModal');
                        const tbodyTar = document.getElementById('add-info-taratonage');

                        function renderTaratonage(list) {
                            if (!tbodyTar) return;
                            if (!Array.isArray(list) || list.length === 0) {
                                tbodyTar.innerHTML = `<tr><td class="text-center text-muted">Aucun résultat trouvé</td></tr>`;
                                return;
                            }
                            tbodyTar.innerHTML = "";
                            list.forEach((item, index) => {
                                const row = document.createElement("tr");
                                row.innerHTML = `
        <td>
          <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0">Taratonage #${index + 1}</h6>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse" title="Replier">
                  <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                  <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <p class="mb-2">
                <strong>${(item.nom ?? '')} ${(item.prenom ?? '')}</strong> —
                ${(item.nom_vehicule ?? '')} (${(item.matricule ?? '')})
              </p>
              <ul class="list-unstyled mb-0">
                <li><strong>Date :</strong> ${item.taratonage_created_at ?? '-'}</li>
                <li><strong>Brut :</strong> ${item.poid_brut ?? '-'} kg</li>
                <li><strong>Tarat :</strong> ${item.poid_taratone ?? '-'} kg</li>
                <li><strong>Net :</strong> ${item.poid_net ?? '-'} kg</li>
                <li><strong>Note :</strong> ${item.note ? item.note : '-'}</li>
              </ul>
            </div>
            <div class="btn-group">
              <button type="button" style='color:black;'
                class="btn btn-sm btn-outline-warning btn-edit-taratonage"
                title="Modifier"
                data-id_taratonage="${item.id_taratonage}">
                <i class="bi bi-pencil-square"></i> Modifier
              </button>
            </div>
          </div>
        </td>`;
                                tbodyTar.appendChild(row);
                            });

                            // relancer le filtre s’il existe
                            const input = document.querySelector("#tarat-zone #searchInputTar");
                            if (input) {
                                const evt = new Event("input");
                                setTimeout(() => input.dispatchEvent(evt), 0);
                            }
                        }

                        viewModal?.addEventListener('show.bs.modal', (event) => {
                            const button = event.relatedTarget;
                            const modal = event.target;

                            // Renseigne en-tête modal (texte + image)
                            const setText = (id, val) => { const el = modal.querySelector('#' + id); if (el) el.textContent = val ?? ''; };
                            setText('v-chauffeur', button?.dataset?.chauffeur || '');
                            setText('v-vehicule', button?.dataset?.vehicule || '');
                            const img = modal.querySelector('#v-imgFchauffeur');
                            if (img && button?.dataset?.imgfchauffeur) {
                                                
                                img.src = "../" + button.dataset.imgfchauffeur;
                                img.alt = "Image chauffeur";
                            }
                            else{
                                img.src="../uploads/image/no-profile.png"
                            }

                            // Charge la liste des taratonages par chauffeur (AJAX)
                            const chauffeurId = button?.dataset?.id_fchauffeur;
                            if (!chauffeurId) { renderTaratonage([]); return; }

                            fetch("../includes/ajax/Fitor/Taratonage/getListinfoTaratonage.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: "id_Fchauffeur=" + encodeURIComponent(chauffeurId)
                            })
                                .then(r => r.json())
                                .then(data => renderTaratonage(Array.isArray(data) ? data : []))
                                .catch(() => {
                                    tbodyTar.innerHTML = `<tr><td class="text-center text-danger">Erreur lors du chargement</td></tr>`;
                                });
                        });

                        // ====== Filtre dans le modal (IDs corrigés) ======
                        (function () {
                            const $scope = document.getElementById("tarat-zone");
                            if (!$scope) return;
                            const $input = $scope.querySelector("#searchInputTar");
                            const $clear = $scope.querySelector("#clearSearchTar"); // <- corrigé
                            const $tbody = $scope.querySelector("#add-info-taratonage");
                            const $nores = $scope.querySelector("#noResultsTar");
                            let timer = null;

                            const normalize = (s) => (s || "").toString().normalize("NFKD").toLowerCase();

                            function applyFilter() {
                                const q = normalize($input.value);
                                let visibleCount = 0;
                                Array.from($tbody?.rows || []).forEach((tr) => {
                                    const show = q === "" || normalize(tr.innerText).includes(q);
                                    tr.classList.toggle("d-none", !show);
                                    if (show) visibleCount++;
                                });
                                $clear?.classList.toggle("d-none", $input.value.trim() === "");
                                $nores?.classList.toggle("d-none", visibleCount > 0);
                            }

                            $input?.addEventListener("input", () => {
                                clearTimeout(timer);
                                timer = setTimeout(applyFilter, 120);
                            });
                            $input?.addEventListener("keydown", (e) => { if (e.key === "Enter") e.preventDefault(); });
                            $clear?.addEventListener("click", () => { $input.value = ""; applyFilter(); $input.focus(); });

                            const observer = new MutationObserver(() => applyFilter());
                            if ($tbody) observer.observe($tbody, { childList: true, subtree: true });

                            applyFilter();
                        })();
                    });
                    
                 
                    </script>
           

            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>