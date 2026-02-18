<?php
$activeSection = 'inventaire';   
$activePage    = 'inventaire-ajouter'; 

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';

// fournisseur list
$fournisseur = [];
try { 
    $sql = "SELECT id_fournisseur, nom_fournisseur, prenom_fournisseur 
            FROM fournisseur 
            ORDER BY nom_fournisseur ASC";
    $result = $connect->query($sql);

    if ($result && $result->num_rows > 0) {
        $fournisseur = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Throwable $e) {
    $fournisseur = [];
}

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
                                        Machine
                                    </button>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="piece-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-piece" type="button" role="tab" aria-controls="tab-piece"
                                        aria-selected="false">
                                        Pièce
                                    </button>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="reglement-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-reglement" type="button" role="tab"
                                        aria-controls="tab-reglement" aria-selected="false">
                                        Règlement
                                    </button>
                                </li>


                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="fournisseur-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-fournisseur" type="button" role="tab"
                                        aria-controls="tab-fournisseur" aria-selected="false">
                                        Fournisseur
                                    </button>
                                </li>


                            </ul>
                        </div>
                    </div>
                    <div class="app-content-header">
                        <div class="container-fluid">

                            <div class="tab-content mt-3" id="formTabsContent">
                                <div class="tab-pane fade show active" id="tab-machine" role="tabpanel"
                                    aria-labelledby="machine-tab">
                                    <div class="card card-info card-outline mb-4">
                                        <div class="card-header">
                                            <div class="card-title">Ajouter une machine</div>
                                        </div>

                                        <form id="form-machine" class="needs-validation"
                                            action="../includes/ajax/addMachine.php" method="post"
                                            enctype="multipart/form-data" novalidate>
                                            <div class="card-body">
                                                <div class="row g-3">

                                                    <div class="col-md-6">
                                                        <label for="ref_machine" class="form-label">Référence</label>
                                                        <input type="text" class="form-control" id="ref_machine"
                                                            name="ref_machine" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="nom_machine" class="form-label">Nom</label>
                                                        <input type="text" class="form-control" id="nom_machine"
                                                            name="nom_machine" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="image_machine" class="form-label">Images</label>
                                                        <input type="file" class="form-control" id="image_machine"
                                                            name="image_machine[]" accept=".jpg,.jpeg,.png,.webp"
                                                            multiple>
                                                        <small class="text-muted">Vous pouvez sélectionner jusqu’à 5
                                                            images.</small>
                                                        <ul id="fileListMachines" class="mt-2 small text-muted"></ul>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="description_machine"
                                                            class="form-label">Description</label>
                                                        <textarea class="form-control" id="description_machine"
                                                            name="description_machine" required></textarea>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="fournisseur_machine"
                                                            class="form-label">Fournisseur</label>
                                                        <select class="form-select js-choices" id="fournisseur_machine"
                                                            name="fournisseur_machine" required>
                                                            <option value="" selected disabled>Sélectionner</option>
                                                            <?php foreach ($fournisseur as $f): ?>
                                                            <option value="<?= (int)$f['id_fournisseur']; ?>">
                                                                <?= htmlspecialchars($f['prenom_fournisseur'].' '.$f['nom_fournisseur']); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="statut_machine" class="form-label">Statut de cette
                                                            machine</label>
                                                        <select class="form-select" id="statut_machine"
                                                            name="statut_machine" required>
                                                            <option value="" selected disabled>Sélectionner </option>
                                                            <option value="en_marche">En marche</option>
                                                            <option value="en_panne">En panne</option>
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

                                        <form id="form-piece" class="needs-validation"
                                            action="../includes/ajax/addPiece.php" method="post"
                                            enctype="multipart/form-data" novalidate>
                                            <div class="card-body">
                                                <div class="row g-3">

                                                    <div class="col-md-6">
                                                        <label for="type_piece" class="form-label">Type de pièce</label>
                                                        <select class="form-select" id="type_piece" name="type_piece"
                                                            required>
                                                            <option value="" selected disabled>Sélectionner </option>
                                                            <option value="piece_mecanique">Pièce mécanique</option>
                                                            <option value="piece_bureautique">Pièce bureautique</option>
                                                            <option value="piece_stockage">Pièce de stockage</option>
                                                            <option value="piece_transport">Pièce de transport</option>
                                                            <option value="piece_sécurité">Pièce de sécurité</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="ref_piece" class="form-label">Référence</label>
                                                        <input type="text" class="form-control" id="ref_piece"
                                                            name="ref_piece" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="nom_piece" class="form-label">Nom</label>
                                                        <input type="text" class="form-control" id="nom_piece"
                                                            name="nom_piece" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="image_piece" class="form-label">Images </label>
                                                        <input type="file" class="form-control" id="image_piece"
                                                            name="image_piece[]" accept=".jpg,.jpeg,.png" multiple>
                                                        <small class="text-muted">Vous pouvez sélectionner jusqu’à 5
                                                            images.</small>
                                                        <ul id="fileList" class="mt-2 small text-muted"></ul>
                                                    </div>


                                                    <div class="col-md-6">
                                                        <label for="description_piece"
                                                            class="form-label">Description</label>
                                                        <textarea class="form-control" id="description_piece"
                                                            name="description_piece"></textarea>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="fournisseur_piece"
                                                            class="form-label">Fournisseur</label>
                                                        <select class="form-select js-choices" id="fournisseur_piece"
                                                            name="fournisseur_piece" required>
                                                            <option value="" selected disabled>Sélectionner </option>
                                                            <?php foreach ($fournisseur as $f): ?>
                                                            <option value="<?= (int)$f['id_fournisseur']; ?>">
                                                                <?= htmlspecialchars($f['prenom_fournisseur'].' '.$f['nom_fournisseur']); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>


                                                    <div class="col-md-6">
                                                        <label for="quantite_piece" class="form-label">Quantité</label>
                                                        <input type="number" class="form-control" id="quantite_piece"
                                                            name="quantite_piece" min="1" required>
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
                                <div class="tab-pane fade" id="tab-fournisseur" role="tabpanel"
                                    aria-labelledby="fournisseur-tab">
                                    <div class="card card-info card-outline mb-4">
                                        <div class="card-header">
                                            <div class="card-title">Ajouter un fournisseur</div>
                                        </div>

                                        <form id="form-fournisseur" class="needs-validation"
                                            action="../includes/ajax/addFournisseur.php" method="post"
                                            enctype="multipart/form-data" novalidate>
                                            <div class="card-body">
                                                <div class="row g-3">

                                                    <div class="col-md-6">
                                                        <label for="prenom_fournisseur"
                                                            class="form-label">Prénom</label>
                                                        <input type="text" class="form-control" id="prenom_fournisseur"
                                                            name="prenom_fournisseur" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="nom_fournisseur" class="form-label">Nom</label>
                                                        <input type="text" class="form-control" id="nom_fournisseur"
                                                            name="nom_fournisseur" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="num_fournisseur" class="form-label">Numéro de
                                                            téléphone</label>
                                                        <input type="tel"
                                                            class="form-control <?php if(isset($_GET['err']) && $_GET['err']==='tel') echo 'is-invalid'; ?>"
                                                            id="num_fournisseur" name="num_fournisseur" required
                                                            minlength="10" maxlength="10"
                                                            oninput="if(this.value.length>10) this.value=this.value.slice(0,10);">
                                                        <?php if(isset($_GET['err']) && $_GET['err']==='tel'): ?>
                                                        <div class="invalid-feedback">
                                                            Le numéro doit contenir exactement 10 chiffres.
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="adress_fournisseur"
                                                            class="form-label">Adress</label>
                                                        <textarea class="form-control" id="adress_fournisseur"
                                                            name="adress_fournisseur" required></textarea>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="card-footer text-end">
                                                <button class="btn btn-primary text-white" type="submit"
                                                    name="btn_fournisseur">
                                                    Ajouter
                                                </button>
                                            </div>
                                        </form>

                                    </div>
                                </div>


                                <div class="tab-pane fade" id="tab-reglement" role="tabpanel"
                                    aria-labelledby="reglement-tab">
                                    <div class="card card-info card-outline mb-4">
                                        <div class="card-header">
                                            <div class="card-title">Centre de Paiement Fournisseur</div>
                                        </div>

                                        <form id="form-reglement" class="needs-validation"
                                            action="../includes/ajax/addReglement.php" method="post"
                                            enctype="multipart/form-data" novalidate>

                                            <div class="card-body">
                                                <div class="row g-3">

                                                    <!-- Référence + Fournisseur -->
                                                    <div class="col-md-6">
                                                        <label for="reference_facture" class="form-label">Référence
                                                            facture</label>
                                                        <input type="text" class="form-control" id="reference_facture"
                                                            name="reference_facture" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="id_fournisseur"
                                                            class="form-label">Fournisseur</label>
                                                        <select class="form-select js-choices" id="id_fournisseur"
                                                            name="id_fournisseur" required>
                                                            <option value="" selected disabled>Sélectionner</option>
                                                            <?php
                    $query = $connect->query("SELECT id_fournisseur, nom_fournisseur, prenom_fournisseur FROM fournisseur ORDER BY nom_fournisseur ASC");
                    while ($row = $query->fetch_assoc()):
                    ?>
                                                            <option value="<?= (int)$row['id_fournisseur']; ?>">
                                                                <?= htmlspecialchars($row['prenom_fournisseur'].' '.$row['nom_fournisseur']); ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>

                                                    <hr>

                                                    <div class="row g-3 align-items-start">
                                                        <!-- Situation -->
                                                        <div class="col-md-6">
                                                            <label class="form-label d-block">Situation de
                                                                paiement</label>

                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="is_paye" id="paye_non" value="Non payé"
                                                                    checked required>
                                                                <label class="form-check-label" for="paye_non">Non
                                                                    payé</label>
                                                            </div>

                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="is_paye" id="paye_oui" value="Payé" required>
                                                                <label class="form-check-label"
                                                                    for="paye_oui">Payé</label>
                                                            </div>

                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="is_paye" id="paye_avance" value="Avance"
                                                                    required>
                                                                <label class="form-check-label"
                                                                    for="paye_avance">Avance</label>
                                                            </div>

                                                            <!-- Zones dynamiques -->
                                                            <div id="payment-zone-paye"
                                                                style="display:none; margin-top:10px;">
                                                                <div id="paye-container"></div>
                                                                <button type="button" id="add-paye-btn"
                                                                    class="btn btn-sm btn-success mt-2">
                                                                    <i class="bi bi-plus-circle"></i> Ajouter un autre
                                                                    paiement
                                                                </button>
                                                            </div>

                                                            <div id="payment-zone-avance"
                                                                style="display:none; margin-top:10px;">
                                                                <div id="avance-container"></div>
                                                                <button type="button" id="add-avance-btn"
                                                                    class="btn btn-sm btn-primary mt-2">
                                                                    <i class="bi bi-plus-circle"></i> Ajouter une autre
                                                                    avance
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <!-- Image facture -->
                                                        <div class="col-md-6">
                                                            <label for="image_facture" class="form-label">Images de la
                                                                facture (max 8)</label>
                                                            <input type="file" class="form-control" id="image_facture"
                                                                name="image_facture[]" accept=".jpg,.jpeg,.png" multiple
                                                                required>
                                                            <div class="form-text">Vous pouvez choisir jusqu’à 8 images.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <hr>

                                                    <!-- Motif -->
                                                    <div class="col-12 mt-3">
                                                        <label for="motif" class="form-label">Commentaire sur le
                                                            règlement (optionnel)</label>
                                                        <textarea id="motif" name="motif" class="form-control"
                                                            rows="2"></textarea>
                                                    </div>

                                                    <hr>

                                                    <!-- Nombre de pièces -->
                                                    <div class="col-md-4">
                                                        <label for="nb_pieces_input" class="form-label">Nombre de
                                                            pièces</label>
                                                        <input type="number" id="nb_pieces_input" class="form-control"
                                                            name="nb_pieces" min="1" max="50" value="1">
                                                        <div class="form-text">Max 50 pièces.</div>
                                                    </div>

                                                </div>

                                                <!-- Zone dynamique des pièces -->
                                                <div id="pieces-container" class="mt-4"></div>
                                            </div>

                                            <div class="card-footer text-end">
                                                <button class="btn btn-primary text-white" type="submit"
                                                    name="btn_reglement">
                                                    Ajouter un règlement
                                                </button>
                                            </div>
                                        </form>

                                    </div>
                                </div>


                            </div>

                        </div>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        (() => {
                            const forms = document.querySelectorAll('.needs-validation');
                            Array.from(forms).forEach(form => {
                                form.addEventListener('submit', e => {
                                    if (!form.checkValidity()) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                    }
                                    form.classList.add('was-validated');
                                }, false);
                            });
                        })();

                        const nbInput = document.getElementById('nb_pieces_input');
                        const piecesContainer = document.getElementById('pieces-container');
                        const MAX_PIECES = 50;

                        function renderPieces(n) {
                            const oldValues = [];
                            piecesContainer.querySelectorAll('.piece-row').forEach(row => {
                                const nom = row.querySelector('input[name*="[nom]"]').value;
                                const prix = row.querySelector('input[name*="[prix]"]').value;
                                const qte = row.querySelector('input[name*="[qte]"]').value;
                                oldValues.push({
                                    nom,
                                    prix,
                                    qte
                                });
                            });

                            piecesContainer.innerHTML = '';
                            n = Math.min(n || 1, MAX_PIECES);

                            for (let i = 0; i < n; i++) {
                                const val = oldValues[i] || {
                                    nom: '',
                                    prix: '',
                                    qte: '1'
                                };
                                const row = document.createElement('div');
                                row.className = 'row g-3 align-items-end mb-2 piece-row';
                                row.innerHTML = `
        <div class="col-md-6">
          <label class="form-label">Nom de la pièce ${i + 1}</label>
          <input type="text" class="form-control" name="pieces[${i}][nom]" value="${val.nom}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Prix</label>
          <input type="number" step="0.01" class="form-control" name="pieces[${i}][prix]" value="${val.prix}" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Quantité</label>
          <input type="number" min="1" class="form-control" name="pieces[${i}][qte]" value="${val.qte}" required>
        </div>`;
                                piecesContainer.appendChild(row);
                            }
                        }

                        if (nbInput && piecesContainer) {
                            nbInput.addEventListener('input', () => renderPieces(parseInt(nbInput.value, 10)));
                            renderPieces(parseInt(nbInput.value, 10));
                        }

                        const radioNon = document.getElementById('paye_non');
                        const radioOui = document.getElementById('paye_oui');
                        const radioAvance = document.getElementById('paye_avance');

                        const zonePaye = document.getElementById('payment-zone-paye');
                        const zoneAvance = document.getElementById('payment-zone-avance');

                        const payeContainer = document.getElementById('paye-container');
                        const addPayeBtn = document.getElementById('add-paye-btn');

                        const avanceContainer = document.getElementById('avance-container');
                        const addAvanceBtn = document.getElementById('add-avance-btn');

                        const MAX_FACTURE_IMAGES = 8;

                        function countFactureImages() {
                            const factureInput = document.getElementById('image_facture');
                            return factureInput && factureInput.files ? factureInput.files.length : 0;
                        }

                        const factureInput = document.getElementById('image_facture');
                        if (factureInput) {
                            factureInput.addEventListener('change', function() {
                                if (this.files.length > MAX_FACTURE_IMAGES) {
                                    alert(
                                        `Vous ne pouvez pas sélectionner plus de ${MAX_FACTURE_IMAGES} images pour la facture.`);
                                    this.value = '';
                                }
                            });
                        }


                        function createPaymentBlock(type) {
                            const isPaye = (type === 'paye');
                            return `
      <div class="row g-3 align-items-end mb-2 payment-block">
        <div class="col-md-4">
          <label class="form-label small">${isPaye ? 'Mode de paiement' : 'Mode d’avance'}</label>
          <select name="paiements_${type}_mode[]" class="form-control payment-mode" required>
            <option value="" disabled selected>Choisir</option>
            <option value="Especes">Espèces</option>
            <option value="Cheque">Chèque</option>
            <option value="Virement">Virement</option>
            <option value="LCN">LCN</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label small">${isPaye ? 'Montant payé (DH)' : 'Montant avance (DH)'}</label>
          <input type="number" step="0.01" min="0" class="form-control" name="paiements_${type}_montant[]" required>
        </div>

        <div class="col-md-3 justif-wrap" style="display:none;">
          <label class="form-label small">Justificatif (${isPaye ? 'paiement' : 'avance'})</label>
          <input type="file" class="form-control" name="paiements_${type}_justif[]" accept=".jpg,.jpeg,.png">
        </div>

        <div class="col-md-1 text-center">
          <button type="button" class="btn btn-outline-danger btn-sm remove-pay-btn" title="Supprimer">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>`;
                        }


                        function setRequiredIn(container, enabled) {
                            container.querySelectorAll('input, select').forEach(el => {
                                if (enabled) {
                                    if (el.dataset.wasRequired === "true") el.required = true;
                                } else {
                                    if (el.required) el.dataset.wasRequired = "true";
                                    el.required = false;
                                }
                            });
                        }

                        function clearContainer(container) {
                            container.querySelectorAll('.payment-block input[type="file"]').forEach(file => file
                                .value = '');
                            container.innerHTML = '';
                        }

                        function updateVisibility() {
                            if (radioOui.checked) {
                                zonePaye.style.display = 'block';
                                zoneAvance.style.display = 'none';
                                setRequiredIn(zonePaye, true);
                                setRequiredIn(zoneAvance, false);
                                clearContainer(avanceContainer);
                            } else if (radioAvance.checked) {
                                zoneAvance.style.display = 'block';
                                zonePaye.style.display = 'none';
                                setRequiredIn(zoneAvance, true);
                                setRequiredIn(zonePaye, false);
                                clearContainer(payeContainer);
                            } else {
                                zonePaye.style.display = 'none';
                                zoneAvance.style.display = 'none';
                                setRequiredIn(zonePaye, false);
                                setRequiredIn(zoneAvance, false);
                                clearContainer(payeContainer);
                                clearContainer(avanceContainer);
                            }
                        }

                        [radioNon, radioOui, radioAvance].forEach(r => r.addEventListener('change',
                            updateVisibility));
                        updateVisibility();


                        if (addPayeBtn) {
                            addPayeBtn.addEventListener('click', () => {
                                const wrapper = document.createElement('div');
                                wrapper.innerHTML = createPaymentBlock('paye');
                                payeContainer.appendChild(wrapper);
                            });
                        }

                        if (addAvanceBtn) {
                            addAvanceBtn.addEventListener('click', () => {
                                const wrapper = document.createElement('div');
                                wrapper.innerHTML = createPaymentBlock('avance');
                                avanceContainer.appendChild(wrapper);
                            });
                        }


                        document.addEventListener('click', function(e) {
                            const btn = e.target.closest('.remove-pay-btn');
                            if (!btn) return;
                            const block = btn.closest('.payment-block');
                            if (!block) return;
                            const fileInput = block.querySelector('input[type="file"]');
                            if (fileInput) fileInput.value = '';
                            block.remove();
                        });

                        document.addEventListener('change', function(e) {
                            if (!e.target.classList.contains('payment-mode')) return;
                            const block = e.target.closest('.payment-block');
                            const wrap = block.querySelector('.justif-wrap');
                            const fileInput = wrap.querySelector('input[type="file"]');
                            const val = e.target.value;
                            const needsJustif = ['Cheque', 'Virement', 'LCN'].includes(val);
                            wrap.style.display = needsJustif ? 'block' : 'none';
                            fileInput.required = needsJustif;
                            if (!needsJustif) fileInput.value = '';
                        });

                        const form = document.getElementById('form-reglement');
                        if (form) {
                            form.addEventListener('submit', function(e) {
                                if (countFactureImages() > MAX_FACTURE_IMAGES) {
                                    e.preventDefault();
                                    alert(
                                        `Limite de ${MAX_FACTURE_IMAGES} images dépassée pour la facture.`);
                                    return;
                                }

                                if (radioOui.checked && payeContainer.querySelectorAll('.payment-block')
                                    .length === 0) {
                                    e.preventDefault();
                                    alert('Ajoutez au moins un paiement.');
                                    return;
                                }
                                if (radioAvance.checked && avanceContainer.querySelectorAll(
                                        '.payment-block').length === 0) {
                                    e.preventDefault();
                                    alert('Ajoutez au moins une avance.');
                                    return;
                                }
                            });
                        }
                    });
                    </script>



                </div>
            </div>
        </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>