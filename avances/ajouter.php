<?php
$activeSection = 'avances';
$activePage    = 'avances-ajouter';

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';

$ouvriers = [];
try {
    $sql = "SELECT id_ouvrier, prenom, nom
            FROM ouvrier
            ORDER BY nom ASC, prenom ASC";
    $res = $connect->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $ouvriers[] = $r;
        }
        $res->free();
    }
} catch (Throwable $e) {
    // error_log($e->getMessage());
}

?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card card-outline card-info mb-0">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="formTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="avance-tab" data-bs-toggle="tab"
                                data-bs-target="#tab-avance" type="button" role="tab" aria-controls="tab-avance"
                                aria-selected="true">
                                Avance d’argent
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ouvrier-tab" data-bs-toggle="tab" data-bs-target="#tab-ouvrier"
                                type="button" role="tab" aria-controls="tab-ouvrier" aria-selected="false">
                                Ouvrier
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content mt-3" id="formTabsContent">

                <!--Avance d'argent-->
                <div class="tab-pane fade show active" id="tab-avance" role="tabpanel" aria-labelledby="avance-tab">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Ajouter une avance d’argent</div>
                        </div>

                        <form id="form-avance" class="needs-validation" action="../includes/ajax/addAvance.php"
                            method="post" novalidate>
                            <div class="card-body">
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label for="ouvrier_id" class="form-label">Ouvrier</label>
                                        <select class="form-select js-choices" id="ouvrier_id" name="ouvrier_id"
                                            required>
                                            <option value="" selected disabled>Sélectionner</option>
                                            <?php foreach ($ouvriers as $o): ?>
                                            <option value="<?= (int)$o['id_ouvrier']; ?>">
                                                <?= htmlspecialchars(trim($o['prenom'].' '.$o['nom']), ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Montant -->
                                    <div class="col-md-6">
                                        <label for="avance" class="form-label">Montant</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="avance"
                                            name="avance" required>
                                    </div>

                                    <!-- Description -->
                                    <div class="col-12">
                                        <label for="motif" class="form-label">Motif de l'avance</label>
                                        <input type="text" class="form-control" id="motif" name="motif">
                                    </div>

                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button class="btn btn-primary text-white" type="submit" name="btn">Ajouter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ouvrier-->
                <div class="tab-pane fade" id="tab-ouvrier" role="tabpanel" aria-labelledby="ouvrier-tab">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Ajouter un ouvrier</div>
                        </div>

                        <form id="form-ouvrier" class="needs-validation" action="../includes/ajax/addOuvrier.php"
                            method="post"  enctype="multipart/form-data" novalidate>
                            <div class="card-body">
                                <div class="row g-3">

                                    <!-- Prénom -->
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" required>
                                    </div>

                                    <!-- Nom -->
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom" required>
                                    </div>

                                    <!-- CIN -->
                                    <div class="col-md-6">
                                        <label for="cin_ouvrier" class="form-label">CIN</label>
                                        <input type="text" class="form-control" id="cin" name="cin" maxlength="20" required >
                                    </div>

                                    <!-- scanne -->
                                    <div class="col-md-6">
                                        <label for="img_cin" class="form-label">Scanne CIN</label>
                                        <input type="file" class="form-control" id="img_cin" name="img_cin"
                                            accept=".jpg,.jpeg,.png" required>
                                    </div>

                                    <!-- Téléphone -->
                                    <div class="col-md-6">
                                        <label for="tel" class="form-label">Numéro de téléphone</label>
                                        <input type="tel"
                                            class="form-control <?php if(isset($_GET['err']) && $_GET['err']==='tel') echo 'is-invalid'; ?>"
                                            id="tel" name="tel" required minlength="10"
                                            maxlength="10" required>
                                    </div>

                                    <!-- Adresse -->
                                    <div class="col-md-6">
                                        <label for="adresse_ouvrier" class="form-label">Adresse</label>
                                        <textarea class="form-control" id="adresse" name="adresse" rows="2"
                                            required></textarea>
                                    </div>

                                    <!-- Fonction -->
                                    <div class="col-md-6">
                                        <label for="fonction_ouvrier" class="form-label">Fonction</label>
                                        <select class="form-select" id="fonction" name="fonction" required>
                                            <option value="" selected disabled>Sélectionner </option>
                                            <option value="Administration">Administration</option>
                                            <option value="Technicien des machines">Technicien des machines</option>
                                            <option value="Agent de service général">Agent de service général</option>
                                            <option value="Contrôleur">Contrôleur</option>
                                            <option value="Gardien">Gardien</option>
                                            <option value="Gérant">Gérant</option>
                                            <option value="Porteur">Porteur</option>
                                            <option value="Chauffeur">Chauffeur</option>
                                        </select>
                                    </div>

                                    <!-- photo -->
                                    <div class="col-md-6">
                                        <label for="photo" class="form-label">Photo</label>
                                        <input type="file" class="form-control" id="photo" name="photo" required
                                            accept=".jpg,.jpeg,.png">
                                    </div>


                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button class="btn btn-primary text-white" type="submit" name="btn">Ajouter</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/scripts.php'; ?>