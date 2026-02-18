<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// === FUSEAU HORAIRE MAROC ===
date_default_timezone_set('Africa/Casablanca');

// === CONFIG DATABASE ===
require_once('config/config.ini.php');
$connect->set_charset('utf8mb4');

// === ID BON DE RUE ===
$bon_rue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bon_rue_id <= 0) {
    die("<h3 style='color:red;text-align:center;'>ID bon de rue manquant ou invalide.</h3>");
}

// === INFOS BON DE RUE AVEC JOIN POUR LES DONNÉES CLIENT ===
$q = $connect->prepare("
    SELECT 
        br.id_bon_rue,
        br.ref_bon_huile,
        br.statut,
        br.duree,
        bh.`cin-huil` AS cin_huil,
        bh.`matricul-voiture` AS matricule_voiture,
        bh.`image-voiture` AS image_voiture,
        CONCAT(c.prenom, ' ', c.nom) AS client_nom
    FROM bon_rue br
    JOIN bon_de_huile bh ON bh.id = br.ref_bon_huile
    LEFT JOIN client c ON c.id = bh.client_id
    WHERE br.id_bon_rue = ?
");
$q->bind_param("i", $bon_rue_id);
$q->execute();
$bon = $q->get_result()->fetch_assoc();
$q->close();

if (!$bon) {
    die("<h3 style='color:red;text-align:center;'>Bon de rue introuvable.</h3>");
}

// === VÉRIFICATION EXPIRATION ===
$now = time();
$expireTimestamp = strtotime($bon['duree']);
$estExpire = ($expireTimestamp < $now);

// === SI EXPIRE ET TOUJOURS ACTIF → DÉSACTIVER AUTOMATIQUEMENT ===
if ($bon['statut'] === 'ACTIVE' && $estExpire) {
    $update = $connect->prepare("UPDATE bon_rue SET statut = 'DESACTIVE' WHERE id_bon_rue = ?");
    $update->bind_param("i", $bon_rue_id);
    $update->execute();
    $update->close();
    $bon['statut'] = 'DESACTIVE'; // on met à jour localement aussi
}

// === DÉTERMINER LE MESSAGE ET LES COULEURS ===
if ($bon['statut'] === 'ACTIVE' && !$estExpire) {
    $phraseArabe = "يُعتبر هذا البون ساري المفعول";
    $bgColor = 'bg-success';
    $afficherInfos = true;
} else {
    $phraseArabe = "يُعتبر هذا البون غير ساري المفعول، يُرجى إتلافه وعدم استخدامه.";
    $bgColor = 'bg-danger';
    $afficherInfos = false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Statut Bon de Route - Meknassa Olive</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/gestionMeknassa/assets/img/logo_meknassa-no-bg.png">
  <style>
    body { 
        background-color: #f8f9fa; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 10px;
    }
    .logo-container {
        text-align: center;
        padding: 1rem 0;
    }
    .logo-container img {
        height: 140px;
        max-width: 100%;
    }
    .arabic-banner {
        padding: 1.5rem;
        text-align: center;
        font-size: 1.2rem;
        font-weight: bold;
        color: white;
        margin: 1rem 0;
        border-radius: 8px;
        direction: rtl;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .info-grid {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    .info-row {
        display: flex;
        border-bottom: 1px solid #e9ecef;
        padding: 0.8rem 0;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label {
        flex: 1;
        font-weight: 600;
        color: #495057;
        padding-right: 1rem;
    }
    .info-value {
        flex: 1;
        color: #212529;
    }
    .vehicle-image-container {
        text-align: center;
        margin-top: 1rem;
    }
    .vehicle-image {
        max-width: 100%;
        height: auto;
        max-height: 200px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
        cursor: pointer;
        transition: transform 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .vehicle-image:hover {
        transform: scale(1.02);
        border-color: #198754;
    }
    .cin-badge {
        background: #e9ecef;
        padding: 0.3rem 0.8rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.9rem;
    }
    .matricule-badge {
        background: #6c757d;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 4px;
        font-size: 0.9rem;
    }
  </style>
</head>
<body>

<div class="container-fluid py-2">

    <!-- LOGO -->
    <div class="logo-container">
        <img src="/gestionMeknassa/assets/img/logo_meknassa-no-bg.png" alt="Logo Meknassa">
    </div>

    <!-- BANNIÈRE ARABE -->
    <div class="arabic-banner <?= $bgColor ?>">
        <?= $phraseArabe ?>
    </div>

    <!-- AFFICHAGE DES INFOS UNIQUEMENT SI ACTIF -->
    <?php if ($afficherInfos): ?>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom Complet</div>
                <div class="info-value fw-bold"><?= htmlspecialchars($bon['client_nom'] ?? 'Non spécifié') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Carte Nationale</div>
                <div class="info-value"><span class="cin-badge"><?= htmlspecialchars($bon['cin_huil']) ?></span></div>
            </div>
            <div class="info-row">
                <div class="info-label">Matricule Véhicule</div>
                <div class="info-value"><span class="matricule-badge"><?= htmlspecialchars($bon['matricule_voiture']) ?></span></div>
            </div>
        </div>

        <?php if (!empty($bon['image_voiture'])): ?>
        <div class="info-grid">
            <div class="vehicle-image-container">
                <div class="info-label text-center mb-2">Image du Véhicule</div>
                <img src="/gestionMeknassa/<?= htmlspecialchars($bon['image_voiture']) ?>" 
                     alt="Véhicule" 
                     class="vehicle-image"
                     onclick="showImage('/gestionMeknassa/<?= htmlspecialchars($bon['image_voiture']) ?>')">
                <div class="text-muted mt-2 small">Cliquez pour agrandir</div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<!-- MODAL IMAGE AGRANDIE -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title">Image du Véhicule</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
              <img id="modalImage" src="" alt="Véhicule" class="img-fluid rounded">
          </div>
      </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showImage(src) {
    document.getElementById('modalImage').src = src;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}
// Rafraîchissement automatique toutes les 30s
setTimeout(()=>window.location.reload(),30000);
</script>
</body>
</html>
