<?php
$activeSection = 'inventaire';
$activePage    = 'inventaire-liste-piece';

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
$connect->set_charset('utf8mb4');

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

$machines = [];
try {
    $resM = $connect->query("SELECT id_machine, nom_machine FROM machine ORDER BY nom_machine ASC");
    if ($resM && $resM->num_rows > 0) {
        $machines = $resM->fetch_all(MYSQLI_ASSOC);
    }
} catch (Throwable $e) { $machines = []; }

$sql = "
SELECT
  p.id_piece,
  p.ref_piece,
  p.nom_piece,
  p.description_piece,
  p.type_piece,
  p.date_ajout,
  p.id_fournisseur,
  CONCAT(f.prenom_fournisseur, ' ', f.nom_fournisseur) AS fournisseur_nom,
  (
    SELECT COALESCE(SUM(sp.quantite_piece), 0)
    FROM stock_piece sp
    WHERE sp.id_piece = p.id_piece
  ) AS quantite,
  (
    SELECT COALESCE(SUM(st.quantite), 0)
    FROM statut_piece st
    WHERE st.id_piece = p.id_piece AND st.statut = 'en_marche'
  ) AS statut_marche,

  (
    SELECT COALESCE(SUM(st.quantite), 0)
    FROM statut_piece st
    WHERE st.id_piece = p.id_piece AND st.statut = 'en_panne'
  ) AS statut_panne,

  (
    SELECT COALESCE(SUM(st.quantite), 0)
    FROM statut_piece st
    WHERE st.id_piece = p.id_piece AND st.statut = 'en_maintenance'
  ) AS statut_maintenance,
  (
    SELECT ip.image
    FROM image_piece ip
    WHERE ip.id_piece = p.id_piece
    ORDER BY ip.date_ajout ASC, ip.id_image ASC
    LIMIT 1
  ) AS first_image,
  (
    SELECT COUNT(*)
    FROM image_piece ip2
    WHERE ip2.id_piece = p.id_piece
  ) AS nb_images,
  (
    SELECT GROUP_CONCAT(ip3.image ORDER BY ip3.date_ajout ASC, ip3.id_image ASC SEPARATOR '||')
    FROM image_piece ip3
    WHERE ip3.id_piece = p.id_piece
  ) AS images_list

FROM piece p
LEFT JOIN fournisseur f ON f.id_fournisseur = p.id_fournisseur
ORDER BY p.date_ajout DESC
";

$result = $connect->query($sql);

?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card">

                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Liste des pièces</h3>

                    <!-- Barre de recherche -->
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 shadow-none"
                            placeholder="Rechercher " autocomplete="off" aria-label="Rechercher une pièce" />
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
                                    <th style="min-width:140px;">Type</th>
                                    <th style="width:120px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
      // Badge type
      $type = strtolower(trim($row['type_piece'] ?? ''));
      switch ($type) {
        case 'piece_mecanique':
          $badgeType = 'text-bg-primary'; $typeLabel = 'Pièce mécanique';   break;
        case 'piece_bureautique':
          $badgeType = 'text-bg-warning';   $typeLabel = 'Pièce bureautique'; break;
        case 'piece_stockage':
          $badgeType = 'text-bg-success';    $typeLabel = 'Pièce de stockage'; break;
        case 'piece_transport':
          $badgeType = 'text-bg-dark';      $typeLabel = 'Pièce de transport'; break;
        case 'piece_sécurité':
          $badgeType = 'text-bg-danger';      $typeLabel = 'Pièce de sécurité'; break;
      }
      $firstImage = $row['first_image'] ?? '';
      $imgUrl = $firstImage ? "../uploads/pieces/".htmlspecialchars($firstImage)
                            : ""; 
    ?>
                                <tr>
                                    <td class="link-primary"><?= htmlspecialchars($row['ref_piece'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['nom_piece'] ?? '') ?></td>
                                    <td><span class="badge <?= $badgeType ?>"><?= $typeLabel ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <!-- Voir -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-info btn-view-piece me-3" title="Voir"
                                                data-bs-toggle="modal" data-bs-target="#viewPieceModal"
                                                data-id="<?= (int)$row['id_piece'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_piece'] ?? '') ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_piece'] ?? '') ?>"
                                                data-type="<?= htmlspecialchars($row['type_piece'] ?? '') ?>"
                                                data-desc="<?= htmlspecialchars($row['description_piece'] ?? '') ?>"
                                                data-qte="<?= (int)($row['quantite'] ?? 0) ?>" data-img="<?= $imgUrl ?>"
                                                data-imgs="<?= htmlspecialchars($row['images_list'] ?? '') ?>"
                                                data-imgbase="../uploads/pieces/"
                                                data-fourname="<?= htmlspecialchars($row['fournisseur_nom'] ?? '') ?>"
                                                data-marche="<?= (int)($row['statut_marche'] ?? 0) ?>"
                                                data-panne="<?= (int)($row['statut_panne'] ?? 0) ?>"
                                                data-maint="<?= (int)($row['statut_maintenance'] ?? 0) ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>


                                            <!-- Stock -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-warning btn-stock-piece me-3"
                                                title="Stock" data-bs-toggle="modal" data-bs-target="#stockPieceModal"
                                                data-id="<?= (int)$row['id_piece'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_piece'] ?? '') ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_piece'] ?? '') ?>"
                                                data-qte="<?= (int)($row['quantite'] ?? 0) ?>">
                                                <i class="bi bi-box-seam"></i>
                                            </button>

                                            <!-- Statut -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-status-piece me-3"
                                                title="Voir statuts" data-bs-toggle="modal"
                                                data-bs-target="#statusPieceModal"
                                                data-id="<?= (int)$row['id_piece'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_piece'] ?? '') ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_piece'] ?? '') ?>"
                                                data-qstock="<?= (int)($row['quantite'] ?? 0) ?>"
                                                data-marche="<?= (int)($row['statut_marche'] ?? 0) ?>"
                                                data-panne="<?= (int)($row['statut_panne'] ?? 0) ?>"
                                                data-maint="<?= (int)($row['statut_maintenance'] ?? 0) ?>"
                                                data-type="<?= htmlspecialchars($row['type_piece'] ?? '') ?>">
                                                <i class="bi bi-clipboard-data"></i>
                                            </button>
                                            
                                            <!-- Modifier -->
                                            <button type="button" class="btn btn-sm btn-outline-success btn-edit-piece"
                                                title="Modifier" data-bs-toggle="modal" data-bs-target="#editPieceModal"
                                                data-id="<?= (int)$row['id_piece'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_piece'] ?? '') ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_piece'] ?? '') ?>"
                                                data-type="<?= htmlspecialchars($row['type_piece'] ?? '') ?>"
                                                data-desc="<?= htmlspecialchars($row['description_piece'] ?? '') ?>"
                                                data-qte="<?= (int)($row['quantite'] ?? 0) ?>" data-img="<?= $imgUrl ?>"
                                                data-imgs="<?= htmlspecialchars($row['images_list'] ?? '') ?>"
                                                data-imgbase="../uploads/pieces/"
                                                data-four="<?= (int)($row['id_fournisseur'] ?? 0) ?>"
                                                data-fourname="<?= htmlspecialchars(trim(($row['prenom_fournisseur'] ?? '') . ' ' . ($row['nom_fournisseur'] ?? ''))) ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                        </div>
                                    </td>

                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aucune pièce trouvée</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>


                        <!-- Aucun résultat -->
                        <div id="noResults" class="text-center py-4 d-none text-muted">
                            <i class="bi bi-search me-1"></i> Aucun résultat pour votre recherche.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal update -->
            <div class="modal fade" id="editPieceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form id="editPieceForm" class="modal-content needs-validation"
                        action="../includes/ajax/updatePiece.php" method="POST" enctype="multipart/form-data"
                        novalidate>
                        <div class="modal-header">
                            <p class="mb-2 text-muted">
                                Modification des informations de la
                                <span class="fw-semibold">pièce :</span> <span id="p-nom-title"
                                    class="fw-bold text-dark"></span>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <span class="fw-semibold">référence :</span> <span id="p-ref-title"
                                    class="fw-bold text-dark"></span>
                            </p>
                        </div>


                        <div class="modal-body">
                            <input type="hidden" name="id_piece" id="p-id">

                            <div class="row g-3">
                                <!-- Référence -->
                                <div class="col-md-6">
                                    <label for="p-ref" class="form-label">Modifier la référence</label>
                                    <input type="text" class="form-control" id="p-ref" name="ref_piece" required>
                                </div>

                                <!-- Nom -->
                                <div class="col-md-6">
                                    <label for="p-nom" class="form-label">Modifier le nom</label>
                                    <input type="text" class="form-control" id="p-nom" name="nom_piece" required>
                                </div>


                                <!-- Diaporama-->
                                <div class="col-md-6">
                                    <label class="form-label d-block">Images existantes</label>

                                    <div id="p-img-carousel-wrap" class="border rounded p-1 d-none">
                                        <div id="pImgCarousel" class="carousel slide" data-bs-ride="false"
                                            style="width:100%;max-width:360px;margin:auto;">
                                            <div class="carousel-inner"></div>
                                            <button class="carousel-control-prev" type="button"
                                                data-bs-target="#pImgCarousel" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"
                                                    style="background-color:black; border-radius:50%; width:32px; height:32px;"></span>
                                                <span class="visually-hidden">Précédent</span>
                                            </button>
                                            <button class="carousel-control-next" type="button"
                                                data-bs-target="#pImgCarousel" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"
                                                    style="background-color:black; border-radius:50%; width:32px; height:32px;"></span>
                                                <span class="visually-hidden">Suivant</span>
                                            </button>
                                        </div>
                                        <div class="small text-muted mt-2" id="p-img-count"></div>
                                    </div>
                                    <div id="p-img-fallback"
                                        class="border rounded p-1 d-flex align-items-center justify-content-center mt-2">
                                        <span class="text-muted small">Aucune image</span>
                                    </div>
                                </div>


                                <!-- Upload image -->
                                <div class="col-md-6 d-flex flex-column">
                                    <label for="p-img-file" class="form-label">Ajouter une image</label>
                                    <input type="file" class="form-control mb-2" id="p-img-file" name="image_piece"
                                        accept=".jpg,.jpeg,.png,.webp">
                                </div>



                                <!-- Description -->
                                <div class="col-12">
                                    <label for="p-desc" class="form-label">Modifier la description</label>
                                    <textarea class="form-control" id="p-desc" name="description_piece"
                                        rows="2"></textarea>
                                </div>

                                <!-- Type -->
                                <div class="col-md-6">
                                    <label for="p-type" class="form-label">Type</label>
                                    <select class="form-select" id="p-type" name="type_piece" required>
                                        <option value="piece_mecanique">Pièce mécanique</option>
                                        <option value="piece_bureautique">Pièce bureautique</option>
                                        <option value="piece_stockage">Pièce de stockage</option>
                                        <option value="piece_transport">Pièce de transport</option>
                                        <option value="piece_sécurité">Pièce de sécurité</option>
                                    </select>
                                </div>

                                <!-- Fournisseur -->
                                <div class="col-md-6">
                                    <label for="p-fournisseur" class="form-label">Modifier le fournisseur</label>
                                    <select class="form-select js-choices" id="p-fournisseur" name="fournisseur_piece"
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
            <!-- /Modal -->
            <!-- Modal Stock -->
            <div class="modal fade" id="stockPieceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form id="stockPieceForm" class="modal-content needs-validation"
                        class="modal-content needs-validation" action="../includes/ajax/updateStock.php" method="POST"
                        novalidate>
                        <div class="modal-header">
                            <p class="mb-2 text-muted">

                                <span class="fw-semibold">Pièce :</span> <span id="stock-piece-nom"
                                    class="fw-bold text-dark"></span>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <span class="fw-semibold">Référence :</span> <span id="stock-piece-ref"
                                    class="fw-bold text-dark"></span>
                            </p>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id_piece" id="stock-piece-id">

                            <p>
                                <strong>Quantité disponible :</strong>
                                <span id="stock-piece-qte" class="badge bg-warning text-dark"></span>
                            </p>

                            <fieldset class="row mb-3">
                                <legend class="col-form-label col-sm-3 pt-0">Type d’opération</legend>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="mouvEntree" value="entree" checked>
                                        <label class="form-check-label" for="mouvEntree">Ajouter au
                                            stock</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="mouvSortie" value="sortie">
                                        <label class="form-check-label" for="mouvSortie">Retirer du
                                            stock</label>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label for="stock-piece-quantite" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="stock-piece-quantite" name="quantite"
                                    min="1" required>
                            </div>

                            <div class="mb-3">
                                <label for="marche-desc" class="form-label">Description de l’opération</label>
                                <textarea class="form-control" id="description" name="description" rows="2"
                                    placeholder=""></textarea>
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
            <!-- Modal Voir -->
            <div class="modal fade" id="viewPieceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <form id="viewPieceForm" class="modal-content" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Détails de la pièce</h5>
                        </div>
                        <div class="p-0">
                            <div id="v-img-carousel-wrap" class="border-bottom d-none" style="height:320px;">
                                <div id="vImgCarousel" class="carousel slide h-100" data-bs-ride="false">
                                    <div class="carousel-inner h-100"></div>

                                    <button class="carousel-control-prev" type="button" data-bs-target="#vImgCarousel"
                                        data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"
                                            style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                                        <span class="visually-hidden">Précédent</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#vImgCarousel"
                                        data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"
                                            style="background-color:black;border-radius:50%;width:32px;height:32px;"></span>
                                        <span class="visually-hidden">Suivant</span>
                                    </button>
                                </div>
                            </div>
                            <div id="v-img-fallback"
                                class="border-bottom d-flex align-items-center justify-content-center"
                                style="height:200px;">
                                <span class="text-muted small">Aucune image</span>
                            </div>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" id="v-id">

                            <table class="table table-sm align-middle mb-3 table-borderless"
                                style="table-layout: fixed; width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="width:33%; word-wrap: break-word;">
                                            <strong>Nom :</strong> <span id="v-nom" class="text-dark"></span>
                                        </td>
                                        <td style="width:33%; word-wrap: break-word;">
                                            <strong>Référence :</strong> <span id="v-ref" class="text-dark"></span>
                                        </td>
                                        <td style="width:34%; word-wrap: break-word;">
                                            <strong>Quantité totale :</strong> <span id="v-qte"
                                                class="text-dark"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width:33%; word-wrap: break-word;">
                                            <strong>Type :</strong> <span id="v-type" class="text-dark"></span>
                                        </td>
                                        <td colspan="2" style="word-wrap: break-word;">
                                            <strong>Fournisseur :</strong> <span id="v-four" class="text-dark"></span>
                                        </td>
                                    </tr>
                                    <tr>

                                        <td colspan="3" class="align-top">
                                            <strong>Description :</strong>
                                            <span id="v-desc" class="text-muted"></span>
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
            <!--/modal-->


            <div class="modal fade" id="statusPieceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">

                        <div class="modal-header">
                            <p class="mb-2 text-muted">
                                <span class="fw-semibold">Statuts de la pièce :</span>
                                <span id="st2-piece-nom" class="fw-bold text-dark"></span>
                                &nbsp;|&nbsp;
                                <span class="fw-semibold">Référence :</span>
                                <span id="st2-piece-ref" class="fw-bold text-dark"></span>
                            </p>

                        </div>

                        <div class="modal-body">
                            <input type="hidden" id="st2-piece-id">

                            <div class="row g-3 mb-3">
                                <!-- En stock -->
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 text-center">
                                        <div class="small text-muted">En stock</div>
                                        <span id="st2-total" class="fs-5 fw-bold">0</span>
                                    </div>
                                </div>

                                <!-- En maintenance -->
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 text-center">
                                        <div class="small text-muted">En maintenance</div>
                                        <button type="button"
                                            class="btn btn-link p-0 fw-bold text-warning btn-open-maint"
                                            data-bs-toggle="modal" data-bs-target="#maintPieceModal">
                                            <span id="st2-maint">0</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- En marche -->
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 text-center">
                                        <div class="small text-muted">En marche</div>
                                        <button type="button"
                                            class="btn btn-link p-0 fw-bold text-success btn-open-marche"
                                            data-bs-toggle="modal" data-bs-target="#marchePieceModal">
                                            <span id="st2-marche">0</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- En panne -->
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 text-center">
                                        <div class="small text-muted">En panne</div>
                                        <button type="button"
                                            class="btn btn-link p-0 fw-bold text-danger btn-open-panne"
                                            data-bs-toggle="modal" data-bs-target="#pannePieceModal">
                                            <span id="st2-panne">0</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Fermer</button>
                        </div>

                    </div>
                </div>
            </div>


            <!-- Modal En Marche -->
            <div class="modal fade" id="marchePieceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form id="marchePieceForm" class="modal-content needs-validation"
                        action="../includes/ajax/updateStatutPiece.php" method="POST" novalidate>
                        <div class="modal-header">
                            <p class="mb-2 text-muted">
                                <span class="fw-semibold">Pièce :</span>
                                <span id="marche-piece-nom" class="fw-bold text-dark"></span>
                                &nbsp;|&nbsp;
                                <span class="fw-semibold">Réf :</span>
                                <span id="marche-piece-ref" class="fw-bold text-dark"></span>
                            </p>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id_piece" id="marche-piece-id">
                            <input type="hidden" name="statut" value="en_marche">

                            <p class="mb-3 text-success"><strong>En marche</strong></p>

                            <fieldset class="row mb-3">
                                <legend class="col-form-label col-sm-3 pt-0">Type d’opération</legend>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="marcheSortie" value="sortie" checked>
                                        <label class="form-check-label" for="marcheSortie">Passage du stock à l’état en
                                            march</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="marcheEntree" value="entree">
                                        <label class="form-check-label" for="marcheEntree">Passage de l’état en marche
                                            au retour en stock</label>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label for="marche-quantite" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="marche-quantite" name="quantite" min="1"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="marche-desc" class="form-label">Description</label>
                                <textarea class="form-control" id="marche-desc" name="description" rows="2"></textarea>
                            </div>

                            <div class="mb-3 d-none" id="wrap-machine-select-marche">
                                <label for="machine-select-marche" class="form-label">Machine associée</label>
                                <select class="form-select" name="id_machine_statut" id="machine-select-marche">
                                    <option value="">Sélectionner une machine </option>
                                    <?php if (!empty($machines)): foreach ($machines as $m): ?>
                                    <option value="<?= (int)$m['id_machine'] ?>">
                                        <?= htmlspecialchars($m['nom_machine']) ?>
                                    </option>
                                    <?php endforeach; endif; ?>
                                </select>
                                <div class="form-text">Visible uniquement pour les pièces mécaniques.</div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- EN PANNE -->
            <div class="modal fade" id="pannePieceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form id="pannePieceForm" class="modal-content needs-validation"
                        action="../includes/ajax/updateStatutPiece.php" method="POST" novalidate>
                        <div class="modal-header">
                            <p class="mb-2 text-muted">
                                <span class="fw-semibold">Pièce :</span>
                                <span id="panne-piece-nom" class="fw-bold text-dark"></span>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <span class="fw-semibold">Réf :</span>
                                <span id="panne-piece-ref" class="fw-bold text-dark"></span>
                            </p>
      
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id_piece" id="panne-piece-id">
                            <input type="hidden" name="statut" value="en_panne">

                            <p class="mb-3 text-danger"><strong>En panne</strong></p>

                            <fieldset class="row mb-3">
                                <legend class="col-form-label col-sm-3 pt-0">Type d’opération</legend>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="panneSortie" value="sortie" checked>
                                        <label class="form-check-label" for="panneSortie">Passage du stock à l’état en
                                            panne</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="panneEntree" value="entree">
                                        <label class="form-check-label" for="panneEntree">Passage de l’état en panne
                                            au retour en stock</label>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label for="panne-quantite" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="panne-quantite" name="quantite" min="1"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="panne-desc" class="form-label">Description de l’opération</label>
                                <textarea class="form-control" id="panne-desc" name="description" rows="2"
                                    required></textarea>
                            </div>

                            <div class="mb-3 d-none" id="wrap-machine-select-panne">
                                <label for="machine-select-panne" class="form-label">Machine associée</label>
                                <select class="form-select" name="id_machine_statut" id="machine-select-panne">
                                    <option value="">Sélectionner une machine </option>
                                    <?php if (!empty($machines)): foreach ($machines as $m): ?>
                                    <option value="<?= (int)$m['id_machine'] ?>">
                                        <?= htmlspecialchars($m['nom_machine']) ?>
                                    </option>
                                    <?php endforeach; endif; ?>
                                </select>
                                <div class="form-text">Visible uniquement pour les pièces mécaniques.</div>
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- EN MAINTENANCE -->
            <div class="modal fade" id="maintPieceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form id="maintPieceForm" class="modal-content needs-validation"
                        action="../includes/ajax/updateStatutPiece.php" method="POST" novalidate>
                        <div class="modal-header">
                            <p class="mb-2 text-muted">
                                <span class="fw-semibold">Pièce :</span>
                                <span id="maint-piece-nom" class="fw-bold text-dark"></span>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <span class="fw-semibold">Réf :</span>
                                <span id="maint-piece-ref" class="fw-bold text-dark"></span>
                            </p>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id_piece" id="maint-piece-id">
                            <input type="hidden" name="statut" value="en_maintenance">

                            <p class="mb-3 text-warning"><strong>En Maintenance</strong></p>

                            <fieldset class="row mb-3">
                                <legend class="col-form-label col-sm-3 pt-0">Type d’opération</legend>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="maintSortie" value="sortie" checked>
                                        <label class="form-check-label" for="maintSortie">Passage du stock à l’état en
                                            maintenance</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mouvement_type"
                                            id="maintEntree" value="entree">
                                        <label class="form-check-label" for="maintEntree">Passage de l’état en
                                            maintenance
                                            au retour en stock</label>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label for="maint-quantite" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="maint-quantite" name="quantite" min="1"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="maint-desc" class="form-label">Description de l’opération</label>
                                <textarea class="form-control" id="maint-desc" name="description" rows="2"
                                    required></textarea>
                            </div>


                            <div class="mb-3 d-none" id="wrap-machine-select-maint">
                                <label for="machine-select-maint" class="form-label">Machine associée</label>
                                <select class="form-select" name="id_machine_statut" id="machine-select-maint">
                                    <option value="">Sélectionner une machine</option>
                                    <?php if (!empty($machines)): foreach ($machines as $m): ?>
                                    <option value="<?= (int)$m['id_machine'] ?>">
                                        <?= htmlspecialchars($m['nom_machine']) ?>
                                    </option>
                                    <?php endforeach; endif; ?>
                                </select>
                                <div class="form-text">Visible uniquement pour les pièces mécaniques.</div>
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



        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>



<script>
document.addEventListener("DOMContentLoaded", function() {

    let currentPieceType = null;
    let currentPieceData = {
        id: 0,
        nom: "",
        ref: ""
    };

    document.addEventListener("click", function(e) {
        const btn = e.target.closest(".btn-status-piece");
        if (!btn) return;

        currentPieceType = (btn.dataset.type || "").trim();
        sessionStorage.setItem("currentPieceType", currentPieceType);

        currentPieceData = {
            id: parseInt(btn.dataset.id || 0, 10),
            nom: btn.dataset.nom || "",
            ref: btn.dataset.ref || ""
        };

        document.getElementById("st2-piece-id").value = currentPieceData.id;
        document.getElementById("st2-piece-nom").textContent = currentPieceData.nom;
        document.getElementById("st2-piece-ref").textContent = currentPieceData.ref;
    });

    function toggleMachineSelect(show, wrapId, selectId) {
        const wrap = document.getElementById(wrapId);
        const select = document.getElementById(selectId);
        if (!wrap || !select) return;
        if (show) {
            wrap.classList.remove("d-none");
            select.required = true;
        } else {
            wrap.classList.add("d-none");
            select.required = false;
            select.value = "";
        }
    }

    document.addEventListener("click", function(e) {
        const btn = e.target.closest(".btn-open-marche, .btn-open-panne, .btn-open-maint");
        if (!btn) return;

        const statutModal = bootstrap.Modal.getInstance(document.getElementById("statusPieceModal"));
        if (statutModal) statutModal.hide();
        const id = document.getElementById("st2-piece-id").value;
        const nom = document.getElementById("st2-piece-nom").textContent;
        const ref = document.getElementById("st2-piece-ref").textContent;

        setTimeout(() => {
            if (btn.classList.contains("btn-open-marche")) {
                document.getElementById("marche-piece-id").value = id;
                document.getElementById("marche-piece-nom").textContent = nom;
                document.getElementById("marche-piece-ref").textContent = ref;
                const type = sessionStorage.getItem("currentPieceType") || "";
                toggleMachineSelect(type === "piece_mecanique", "wrap-machine-select-marche",
                    "machine-select-marche");
                new bootstrap.Modal(document.getElementById("marchePieceModal")).show();
            }

            if (btn.classList.contains("btn-open-panne")) {
                document.getElementById("panne-piece-id").value = id;
                document.getElementById("panne-piece-nom").textContent = nom;
                document.getElementById("panne-piece-ref").textContent = ref;
                const type = sessionStorage.getItem("currentPieceType") || "";
                toggleMachineSelect(type === "piece_mecanique", "wrap-machine-select-panne",
                    "machine-select-panne");
                new bootstrap.Modal(document.getElementById("pannePieceModal")).show();
            }

            if (btn.classList.contains("btn-open-maint")) {
                document.getElementById("maint-piece-id").value = id;
                document.getElementById("maint-piece-nom").textContent = nom;
                document.getElementById("maint-piece-ref").textContent = ref;
                const type = sessionStorage.getItem("currentPieceType") || "";
                toggleMachineSelect(type === "piece_mecanique", "wrap-machine-select-maint",
                    "machine-select-maint");
                new bootstrap.Modal(document.getElementById("maintPieceModal")).show();
            }
        }, 250);
    });

});


document.addEventListener("hidden.bs.modal", function() {
    if (document.querySelectorAll('.modal.show').length === 0) {
        document.body.classList.remove('modal-open');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }
});
</script>