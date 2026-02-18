<?php
$activeSection = 'inventaire';     
$activePage    = 'inventaire-liste-fournisseur';

require_once(__DIR__ . '/../config/config.ini.php');
require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/sidebare.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

$sqlSup = "SELECT id_fournisseur, prenom_fournisseur, nom_fournisseur, tele_fournisseur, adress_fournisseur, date_ajout
           FROM fournisseur
           ORDER BY date_ajout DESC";
$resSup = $connect->query($sqlSup);

?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Liste des fournisseurs</h3>

                    <!-- Barre de recherche -->
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 shadow-none"
                            placeholder="Rechercher" autocomplete="off" aria-label="Rechercher un fournisseur" />
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
                                    <th style="min-width:160px;">Prénom</th>
                                    <th style="min-width:180px;">Nom</th>
                                    <th style="min-width:140px;">Téléphone</th>
                                    <th>Adresse</th>
                                    <th style="width:120px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($resSup && $resSup->num_rows > 0): ?>
                                <?php while ($row = $resSup->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-break"><?= htmlspecialchars($row['prenom_fournisseur'] ?? '') ?>
                                    </td>
                                    <td class="text-break"><?= htmlspecialchars($row['nom_fournisseur'] ?? '') ?></td>
                                    <td class="text-break"><?= htmlspecialchars($row['tele_fournisseur'] ?? '') ?></td>
                                    <td class="text-break"><?= htmlspecialchars($row['adress_fournisseur'] ?? '') ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <!-- IMPRIMER -->
<a href="print-fournisseur.php?id=<?= (int)$row['id_fournisseur'] ?>" 
   target="_blank" 
   class="btn btn-sm btn-outline-primary" 
   title="Imprimer les infos du fournisseur">
   <i class="bi bi-printer"></i>
</a>

                                            <!-- MODIFIER -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-success btn-edit-fournisseur"
                                                data-bs-toggle="modal" data-bs-target="#editFournisseurModal"
                                                data-id="<?= (int)$row['id_fournisseur'] ?>"
                                                data-prenom="<?= htmlspecialchars($row['prenom_fournisseur'] ?? '') ?>"
                                                data-nom="<?= htmlspecialchars($row['nom_fournisseur'] ?? '') ?>"
                                                data-tele="<?= htmlspecialchars($row['tele_fournisseur'] ?? '') ?>"
                                                data-adress="<?= htmlspecialchars($row['adress_fournisseur'] ?? '') ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aucun fournisseur trouvé</td>
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
            <div class="modal fade" id="editFournisseurModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form id="editFournisseurForm" class="modal-content needs-validation"
                        action="../includes/ajax/updateFournisseur.php" method="POST" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier le fournisseur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id_fournisseur" id="f-id">

                            <div class="mb-3">
                                <label for="f-prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="f-prenom" name="prenom_fournisseur"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="f-nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="f-nom" name="nom_fournisseur" required>
                            </div>

                            <div class="mb-3">
                                <label for="f-tele" class="form-label">Téléphone</label>
                                <input type="number" class="form-control" id="f-tele" name="tele_fournisseur"
                                    inputmode="numeric" pattern="\d{10}" maxlength="10" required>
                            </div>

                            <div class="mb-3">
                                <label for="f-adress" class="form-label">Adresse</label>
                                <textarea class="form-control" id="f-adress" name="adress_fournisseur" rows="2"
                                    required></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-dark" type="button" data-bs-dismiss="modal">Annuler</button>
                            <button class="btn btn-success" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- /modal -->
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/scripts.php'; ?>