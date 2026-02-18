<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// === CONFIG DATABASE ===
require_once('config/config.ini.php');

// === ID TRANSPORTEUR ===
$idTransporteur = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idTransporteur <= 0) {
    die("<h3 style='color:red;text-align:center;'>ID transporteur manquant ou invalide.</h3>");
}

// === INFOS TRANSPORTEUR ===
$q = $connect->prepare("
    SELECT nom_trans, prenom_trans, telephone_trans, image_trans, Adresse_Source 
    FROM transporteur 
    WHERE id_transporteur = ?
");
$q->bind_param("i", $idTransporteur);
$q->execute();
$trans = $q->get_result()->fetch_assoc();
if (!$trans) {
    die("<h3 style='color:red;text-align:center;'>Transporteur introuvable.</h3>");
}

// === TOTAL POIDS NET DU TRANSPORTEUR ===
$qTotal = $connect->prepare("
    SELECT SUM(taratonage_olive.poid_net) AS total_net
    FROM taratonage_olive
    JOIN vehiculetrans ON vehiculetrans.id_vehicule_trans = taratonage_olive.id_vehicule_trans
    WHERE vehiculetrans.id_transporteur = ?
");
$qTotal->bind_param("i", $idTransporteur);
$qTotal->execute();
$total = $qTotal->get_result()->fetch_assoc()['total_net'] ?? 0;

// === GRAPHIQUE : POIDS NET PAR JOUR ===
$qGraph = $connect->prepare("
    SELECT DATE(taratonage_olive.taratonage_olive_created_at) AS jour,
           SUM(taratonage_olive.poid_net) AS total
    FROM taratonage_olive
    JOIN vehiculetrans ON vehiculetrans.id_vehicule_trans = taratonage_olive.id_vehicule_trans
    WHERE vehiculetrans.id_transporteur = ?
    GROUP BY jour
    ORDER BY jour ASC
");
$qGraph->bind_param("i", $idTransporteur);
$qGraph->execute();
$resGraph = $qGraph->get_result();

$labels = [];
$values = [];
while ($r = $resGraph->fetch_assoc()) {
    $labels[] = $r['jour'];
    $values[] = (float)$r['total'];
}

// === DÉTAILS DES TARATONNAGES ===
$qList = $connect->prepare("
    SELECT 
        taratonage_olive.id_taratonage,
        vehiculetrans.matricule,
        vehiculetrans.image_vehicule_trans,
        taratonage_olive.poid_brut,
        taratonage_olive.poid_tarat,
        taratonage_olive.poid_net,
        taratonage_olive.note,
        taratonage_olive.taratonage_olive_created_at
    FROM taratonage_olive
    JOIN vehiculetrans ON vehiculetrans.id_vehicule_trans = taratonage_olive.id_vehicule_trans
    WHERE vehiculetrans.id_transporteur = ?
    ORDER BY taratonage_olive.taratonage_olive_created_at DESC
");
$qList->bind_param("i", $idTransporteur);
$qList->execute();
$list = $qList->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Fiche Transporteur - Meknassa Olive</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="icon" type="image/png" href="/gestionMeknassa/assets/img/logo_meknassa-no-bg.png" sizes="64x64">
  <style>
    body { background-color:#f8fafb; font-family:'Poppins', sans-serif; }
    .logo-header img { width:140px; }
    .header-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .header-bar h2 { color:#2b4c2b; font-weight:700; }
    .card { border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
    .profile { width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #28a745; }
    .table img { transition:transform 0.2s; border-radius:10px; }
    .table img:hover { transform:scale(1.1); }
    .bg-success-light { background:rgba(40,167,69,0.1); color:#28a745; }
    
  
  </style>
</head>
<body>
<div class="container py-4">

  <!-- HEADER -->
  <div class="header-bar">
    <div class="logo-header">
      <img src="/gestionMeknassa/assets/img/logo_meknassa-no-bg.png" alt="Logo Meknassa">
    </div>
    <h2>Fiche Transporteur</h2>
   
  </div>

  <!-- INFO TRANSPORTEUR -->
  <div class="card mb-4">
    <div class="card-body text-center">
      <img src="<?= !empty($trans['image_trans']) ? htmlspecialchars($trans['image_trans']) : 'uploads/image/no-profile.png' ?>" 
           class="profile mb-3" alt="Photo Transporteur">
      <h3 class="text-success"><?= htmlspecialchars($trans['nom_trans'].' '.$trans['prenom_trans']) ?></h3>
      <p class="mb-1"><strong>Téléphone :</strong> <?= htmlspecialchars($trans['telephone_trans']) ?></p>
      <p><strong>Adresse :</strong> <?= htmlspecialchars($trans['Adresse_Source']) ?></p>
    </div>
  </div>

  <!-- TOTAL POIDS -->
  <div class="card text-center mb-4 bg-success-light p-3">
    <h5>Total Poids Net Transporté</h5>
    <h1 class="fw-bold"><?= number_format($total, 2) ?> kg</h1>
  </div>
   <!-- HISTORIQUE TARATONNAGE -->
  <div class="card">
    <div class="card-header bg-light"><b>Historique des Taratonnages</b></div>
    <div class="card-body table-responsive">
      <table class="table table-hover align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Matricule</th>
            <th>Image Véhicule</th>
            <th>Poids Net</th>
            <th>Ref</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($list->num_rows): ?>
            <?php while ($r = $list->fetch_assoc()): ?>
              <tr>
                <td><?= $r['id_taratonage'] ?></td>
                <td><?= htmlspecialchars($r['taratonage_olive_created_at']) ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($r['matricule']) ?></td>
                <td>
                  <?php if (!empty($r['image_vehicule_trans'])): ?>
                    <img src="<?= htmlspecialchars($r['image_vehicule_trans']) ?>" 
                         alt="Véhicule"
                         style="width:80px;height:60px;object-fit:cover;"
                         onclick="showImage('<?= htmlspecialchars($r['image_vehicule_trans']) ?>')">
                  <?php else: ?>
                    <img src="uploads/image/no-vehicle.png" alt="Aucune image" style="width:80px;height:60px;opacity:0.6;">
                  <?php endif; ?>
                </td>
             
                <td class="text-success fw-bold"><?= number_format($r['poid_net'],2) ?> kg</td>
                <td><?= htmlspecialchars($r['note'] ?? '-') ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="8" class="text-muted text-center">Aucun enregistrement</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- GRAPH -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">Évolution du Poids Net (par jour)</div>
    <div class="card-body">
      <canvas id="chartTaratonnage"></canvas>
    </div>
  </div>

 
</div>

<!-- MODAL IMAGE AGRANDIE -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <img id="modalImage" src="" alt="Véhicule" class="img-fluid rounded shadow">
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('chartTaratonnage');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Poids net total (kg)',
      data: <?= json_encode($values) ?>,
      borderColor: '#28a745',
      backgroundColor: 'rgba(40,167,69,0.15)',
      fill: true,
      tension: 0.3,
      borderWidth: 2
    }]
  }
});
function showImage(src) {
  document.getElementById('modalImage').src = src;
  new bootstrap.Modal(document.getElementById('imageModal')).show();
}
</script>
</body>
</html>
