<?php
$activeSection = 'inventaire';     
$activePage    = 'inventaire-liste-machine';

require_once(__DIR__ . '/../config/config.ini.php');
$connect->set_charset('utf8mb4');
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

$sql = " 
SELECT
  m.id_machine,
  m.ref_machine,
  m.nom_machine,
  m.description_machine,
  m.date_ajout,
  f.id_fournisseur,
  f.nom_fournisseur,
  f.prenom_fournisseur,
  f.tele_fournisseur,
  f.adress_fournisseur,
  im.image AS image_machine,
  (
    SELECT GROUP_CONCAT(im2.image ORDER BY im2.date_ajout ASC, im2.id_image ASC SEPARATOR '||')
    FROM image_machine im2
    WHERE im2.id_machine = m.id_machine
  ) AS images_list,
  sm.statut AS statut_machine,
  sm.description AS statut_description,
  sm.date_ajout AS statut_date

FROM machine m
INNER JOIN fournisseur f ON m.id_fournisseur = f.id_fournisseur
LEFT JOIN (
  SELECT id_machine, image
  FROM image_machine
  WHERE id_image IN (SELECT MAX(id_image) FROM image_machine GROUP BY id_machine)
) im ON m.id_machine = im.id_machine
LEFT JOIN (
  SELECT id_machine, statut, description, date_ajout
  FROM statut_machine
  WHERE id_statut IN (SELECT MAX(id_statut) FROM statut_machine GROUP BY id_machine)
) sm ON m.id_machine = sm.id_machine
ORDER BY m.date_ajout DESC;

";
$result = $connect->query($sql);

?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Liste des machines</h3>

                    <!-- Barre de recherche -->
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 shadow-none"
                            placeholder="Rechercher" autocomplete="off" aria-label="Rechercher une machine" />
                        <button class="btn btn-outline-secondary d-none" id="clearSearch" type="button"
                            aria-label="Effacer la recherche">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0 align-middle" id="ordersTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:140px;">Référence</th>
                                    <th style="min-width:180px;">Nom</th>
                                    <th style="min-width:140px;">Statut</th>
                                    <th style="width:120px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                  if ($result && $result->num_rows > 0):
                                  while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="link-primary">
                                        <?= htmlspecialchars($row['ref_machine']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['nom_machine']) ?>
                                    </td>
                                    <td>
                                        <span class="badge 
                <?= $row['statut_machine'] === 'en_marche' ? 'text-bg-success' :
                    ($row['statut_machine'] === 'en_panne' ? 'text-bg-danger' : 'text-bg-warning') ?>">
                                            <?= htmlspecialchars($row['statut_machine']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="d-inline-flex align-items-center gap-2">

                                            <!-- VOIR -->
                                            <button type="button" class="btn btn-sm btn-outline-info btn-view"
                                                title="Voir" data-bs-toggle="modal" data-bs-target="#viewMachineModal"
                                                data-id="<?= (int)$row['id_machine'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_machine']) ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_machine']) ?>"
                                                data-statut="<?= htmlspecialchars($row['statut_machine'] ?? '') ?>"
                                                data-desc="<?= htmlspecialchars($row['description_machine'] ?? '') ?>"
                                                data-fourname="<?= htmlspecialchars(trim(($row['prenom_fournisseur'] ?? '') . ' ' . ($row['nom_fournisseur'] ?? ''))) ?>"
                                                data-imgs="<?= htmlspecialchars($row['images_list'] ?? '') ?>"
                                                data-imgbase="../uploads/machines/">
                                                <i class="bi bi-eye"></i>
                                            </button>


                                            <!-- STATUT -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-status-machine"
                                                title="Voir / changer le statut" data-bs-toggle="modal"
                                                data-bs-target="#statusMachineModal"
                                                data-id="<?= (int)$row['id_machine'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_machine']) ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_machine']) ?>"
                                                data-statut="<?= htmlspecialchars($row['statut_machine'] ?? '') ?>">
                                                <i class="bi bi-clipboard-data"></i>
                                            </button>

                                            <!-- MODIFIER -->
                                            <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                title="Modifier" data-bs-toggle="modal"
                                                data-bs-target="#editMachineModal"
                                                data-id="<?= (int)$row['id_machine'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_machine']) ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_machine']) ?>"
                                                data-desc="<?= htmlspecialchars($row['description_machine']) ?>"
                                                data-imgs="<?= htmlspecialchars($row['images_list'] ?? '') ?>"
                                                data-imgbase="../uploads/machines/"
                                                data-four="<?= (int)$row['id_fournisseur'] ?>"
                                                data-fourname="<?= htmlspecialchars(trim(($row['prenom_fournisseur'] ?? '') . ' ' . ($row['nom_fournisseur'] ?? ''))) ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                                <?php
    endwhile;
else:
?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aucune machine trouvée</td>
                                </tr>
                                <?php endif; ?>
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
            <div class="modal fade" id="editMachineModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form id="editMachineForm" class="modal-content needs-validation"
                        action="../includes/ajax/updateMachine.php" method="POST" enctype="multipart/form-data"
                        novalidate>
                        <div class="modal-header">
                            <p class="mb-2 text-muted">
                                Modification des informations de
                                <span class="fw-semibold">Machine :</span> <span id="e-nom-title"
                                    class="fw-bold text-dark"></span>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <span class="fw-semibold">Référence :</span> <span id="e-ref-title"
                                    class="fw-bold text-dark"></span>
                            </p>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id_machine" id="e-id">

                            <div class="row g-3">
                                <!-- Référence -->
                                <div class="col-md-6">
                                    <label for="e-ref" class="form-label">Modifier la référence</label>
                                    <input type="text" class="form-control" id="e-ref" name="ref_machine" required>
                                </div>

                                <!-- Nom -->
                                <div class="col-md-6">
                                    <label for="e-nom" class="form-label">Modifier le nom</label>
                                    <input type="text" class="form-control" id="e-nom" name="nom_machine" required>
                                </div>

                                <!-- Diaporama images existantes -->
                                <div class="col-md-6">
                                    <label class="form-label d-block">Images existantes</label>

                                    <div id="m-img-carousel-wrap" class="border rounded p-1 d-none">
                                        <div id="mImgCarousel" class="carousel slide" data-bs-ride="false"
                                            style="width:100%;max-width:360px;margin:auto;">
                                            <div class="carousel-inner"></div>
                                            <button class="carousel-control-prev" type="button"
                                                data-bs-target="#mImgCarousel" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"
                                                    style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                                                <span class="visually-hidden">Précédent</span>
                                            </button>
                                            <button class="carousel-control-next" type="button"
                                                data-bs-target="#mImgCarousel" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"
                                                    style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                                                <span class="visually-hidden">Suivant</span>
                                            </button>
                                        </div>
                                        <div class="small text-muted mt-2" id="m-img-count"></div>
                                    </div>

                                    <div id="m-img-fallback"
                                        class="border rounded p-1 d-flex align-items-center justify-content-center mt-2">
                                        <span class="text-muted small">Aucune image</span>
                                    </div>
                                </div>

                                <!-- ajouter image -->
                                <div class="col-md-6 d-flex flex-column">
                                    <label for="e-img-file" class="form-label">Ajouter une image</label>
                                    <input type="file" class="form-control mb-2" id="e-img-file" name="image_machine"
                                        accept=".jpg,.jpeg,.png">
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label for="e-desc" class="form-label">Modifier la description</label>
                                    <textarea class="form-control" id="e-desc" name="description_machine"
                                        rows="2"></textarea>
                                </div>

                                <!-- Fournisseur -->
                                <div class="col-md-6">
                                    <label for="e-fournisseur" class="form-label">Modifier le fournisseur</label>
                                    <select class="form-select js-choices" id="e-fournisseur" name="fournisseur_machine"
                                        required>
                                        <?php if (!empty($fournisseur)): ?>
                                        <?php foreach ($fournisseur as $f): ?>
                                        <option value="<?= (int)$f['id_fournisseur']; ?>">
                                            <?= htmlspecialchars($f['prenom_fournisseur'] . ' ' . $f['nom_fournisseur']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-dark" data-bs-dismiss="modal" type="button">Annuler</button>
                            <button class="btn btn-success" type="submit">Enregistrer la modification</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /modal -->

            <!-- Modal Statut -->
            <div class="modal fade" id="statusMachineModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form id="statusMachineForm" class="modal-content needs-validation"
                        action="../includes/ajax/updateStatutMachine.php" method="POST" novalidate>
                        <div class="modal-header">
                            <p class="mb-2 text-muted">
                                <span class="fw-semibold">Statut de la machine :</span> <span id="st-mach-nom"
                                    class="fw-bold text-dark"></span>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <span class="fw-semibold">Référence :</span> <span id="st-mach-ref"
                                    class="fw-bold text-dark"></span>
                            </p>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id_machine" id="st-mach-id">

                            <p class="mb-2">
                                <strong>Statut actuel :</strong>
                                <span id="st-mach-current" class="badge text-bg-secondary">-</span>
                            </p>

                            <fieldset class="row mb-3">
                                <legend class="col-form-label col-sm-3 pt-0">Nouveau statut</legend>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="statut" id="stMachMarche"
                                            value="en_marche" required>
                                        <label class="form-check-label" for="stMachMarche">En marche</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="statut" id="stMachPanne"
                                            value="en_panne">
                                        <label class="form-check-label" for="stMachPanne">En panne</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="statut" id="stMachMaint"
                                            value="en_maintenance">
                                        <label class="form-check-label" for="stMachMaint">En maintenance</label>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label for="st-mach-desc" class="form-label">Description</label>
                                <textarea class="form-control" id="st-mach-desc" name="description" rows="2"
                                    required></textarea>
                            </div>

                            <div id="st-mach-history" class="small text-muted d-none">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /modal -->
            <!-- Modal Voir Machine -->
            <div class="modal fade" id="viewMachineModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <form id="viewMachineForm" class="modal-content" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Détails de la machine</h5>
                        </div>

                        <!-- Diaporama -->
                        <div class="p-0">
                            <div id="mv-img-carousel-wrap" class="border-bottom d-none" style="height:320px;">
                                <div id="mvImgCarousel" class="carousel slide h-100" data-bs-ride="false">
                                    <div class="carousel-inner h-100"></div>

                                    <button class="carousel-control-prev" type="button" data-bs-target="#mvImgCarousel"
                                        data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"
                                            style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                                        <span class="visually-hidden">Précédent</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#mvImgCarousel"
                                        data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"
                                            style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                                        <span class="visually-hidden">Suivant</span>
                                    </button>
                                </div>
                            </div>
                            <div id="mv-img-fallback"
                                class="border-bottom d-flex align-items-center justify-content-center"
                                style="height:200px;">
                                <span class="text-muted small">Aucune image</span>
                            </div>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" id="mv-id">

                            <table class="table table-sm align-middle mb-3 table-borderless"
                                style="table-layout: fixed; width: 100%;">
                                <colgroup>
                                    <col style="width:50%">
                                    <col style="width:50%">
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td class="align-top">
                                            <strong>Nom :</strong>
                                            <span id="mv-nom" class="text-dark text-break"></span>
                                        </td>
                                        <td class="align-top">
                                            <strong>Référence :</strong>
                                            <span id="mv-ref" class="text-dark text-break"></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="align-top">
                                            <strong>Statut :</strong>
                                            <span id="mv-statut" class="badge text-bg-secondary">-</span>
                                        </td>
                                        <td class="align-top">
                                            <strong>Fournisseur :</strong>
                                            <span id="mv-four" class="text-dark text-break"></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" class="align-top">
                                            <strong>Description :</strong>
                                            <span id="mv-desc" class="text-muted text-break"></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /modal -->

        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>