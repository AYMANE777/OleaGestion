<?php
/**
 * Meknassa Olive - Impression Journal des Versements
 * 🧾 Liste des versements filtrés par date + recherche client
 */

header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

// === PARAMÈTRES ===
$q          = trim($_GET['q'] ?? '');
$date_debut = trim($_GET['date_debut'] ?? '');
$date_fin   = trim($_GET['date_fin'] ?? '');

$where = ["1"];
$params = [];
$types = "";

if ($q !== '') {
  $where[] = "(c.nom LIKE CONCAT('%', ?, '%') 
            OR c.prenom LIKE CONCAT('%', ?, '%') 
            OR c.ville LIKE CONCAT('%', ?, '%'))";
  $params = array_merge($params, [$q, $q, $q]);
  $types .= "sss";
}

if (!empty($date_debut) && !empty($date_fin)) {
  $where[] = "DATE(v.date_versement) BETWEEN ? AND ?";
  $params[] = $date_debut;
  $params[] = $date_fin;
  $types .= "ss";
} elseif (!empty($date_debut)) {
  $where[] = "DATE(v.date_versement) >= ?";
  $params[] = $date_debut;
  $types .= "s";
} elseif (!empty($date_fin)) {
  $where[] = "DATE(v.date_versement) <= ?";
  $params[] = $date_fin;
  $types .= "s";
}

$whereSql = "WHERE " . implode(" AND ", $where);

// === REQUÊTE PRINCIPALE ===
$sql = "
  SELECT 
    DATE_FORMAT(v.date_versement, '%d/%m/%Y %H:%i') AS date_versement,
    CONCAT(c.prenom, ' ', c.nom) AS client_nom,
    v.montant,
    v.mode_paiement,
    v.note
  FROM versement_olive v
  JOIN clientolive c ON c.id_client_olive = v.id_client_olive
  $whereSql
  ORDER BY v.date_versement DESC
";

$stmt = $connect->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$versements = [];
$total_montant = 0;

while ($r = $res->fetch_assoc()) {
  $total_montant += (float)$r['montant'];
  $versements[] = $r;
}

// === INFO ENTREPRISE ===
$bank = [
  'name'  => 'Meknassa Olive',
  'phone' => '+212 7 01 08 52 61',
  'bic'   => 'Meknassa El Gharbia',
  'logo'  => '../assets/img/logo_meknassa.png',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Journal des Versements</title>
<style>
:root {
  --main:#115e59; --success:#16a34a; --danger:#b91c1c; --muted:#6b7280;
  --border:#e5e7eb; --bg:#f9fafb;
}
body {
  font-family:"Segoe UI",Arial,sans-serif;
  background:var(--bg); color:#111;
  padding:25px; margin:0;
}
.sheet {
  background:#fff; max-width:1050px; margin:20px auto;
  padding:35px 40px; border-radius:12px;
  box-shadow:0 4px 20px rgba(0,0,0,0.1);
}
header {
  display:flex; justify-content:space-between; align-items:center;
  border-bottom:3px solid var(--main); padding-bottom:8px; margin-bottom:20px;
}
.brand h1 { margin:0; color:var(--main); font-size:22px; }
.logo img { height:140px; }
.meta { text-align:right; font-size:14px; color:var(--muted); }

h2 { margin-top:0; color:var(--main); }
table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13.5px; }
th, td { padding:6px 8px; border:1px solid var(--border); }
th { background:#ecfdf5; color:#065f46; text-align:left; }
.num { text-align:right; white-space:nowrap; }

.summary {
  margin-top:25px; display:flex; justify-content:center; text-align:center;
}
.summary .box {
  flex:0 0 300px; margin:0 5px; border-radius:8px; padding:10px;
  background:#dcfce7; color:#166534; border:1px solid #bbf7d0;
}

#printBtn {
  display:block; margin:20px auto 0;
  padding:8px 18px; background:var(--main);
  color:#fff; border:none; border-radius:6px; cursor:pointer;
}
#printBtn:hover { background:#0f766e; }

@media print {
  body { background:#fff; padding:0; }
  .sheet { box-shadow:none; border:none; margin:0; padding:20px; page-break-after:auto; }
  #printBtn { display:none; }
}
.signatures { margin-top:80px; display:flex; justify-content:space-between; }
</style>
</head>
<body>
<div class="sheet">
  <header>
    <div class="brand">
      <h1><?= htmlspecialchars($bank['name']) ?></h1>
      <small><?= htmlspecialchars($bank['bic']) ?><br>Tél: <?= htmlspecialchars($bank['phone']) ?></small>
    </div>
    <div class="logo">
      <?php if (file_exists($bank['logo'])): ?>
        <img src="<?= htmlspecialchars($bank['logo']) ?>" alt="Logo Meknassa">
      <?php endif; ?>
    </div>
    <div class="meta">
      <strong>Journal des Versements</strong><br>
      <?php if ($date_debut && $date_fin): ?>
        Période : du <?= htmlspecialchars($date_debut) ?> au <?= htmlspecialchars($date_fin) ?><br>
      <?php elseif ($date_debut): ?>
        À partir du <?= htmlspecialchars($date_debut) ?><br>
      <?php elseif ($date_fin): ?>
        Jusqu'au <?= htmlspecialchars($date_fin) ?><br>
      <?php else: ?>
        Toutes les dates<br>
      <?php endif; ?>
      <?= date('d/m/Y H:i') ?>
      <?php if ($q): ?><br><em>Filtre client : <?= htmlspecialchars($q) ?></em><?php endif; ?>
    </div>
  </header>

  <?php if (empty($versements)): ?>
    <h3 style='text-align : center;'>Aucun versement trouvé pour cette période.</h3>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Date Versement</th>
          <th>Client</th>
          <th>Montant (DH)</th>
          <th>Mode Paiement</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($versements as $v): ?>
        <tr>
          <td><?= htmlspecialchars($v['date_versement']) ?></td>
          <td><?= htmlspecialchars($v['client_nom']) ?></td>
          <td class="num"><?= number_format($v['montant'], 2, ',', ' ') ?></td>
          <td><?= htmlspecialchars($v['mode_paiement'] ?: '-') ?></td>
          <td><?= htmlspecialchars($v['note'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="summary">
      <div class="box">
        <strong>Total Général des Versements</strong><br>
        <?= number_format($total_montant, 2, ',', ' ') ?> DH
      </div>
    </div>
    <div class="signatures">
    <div class="sign"><div class="sign-line"></div>Visé par :</div>
    <div class="sign"><div class="sign-line"></div>Signature de l’Administration</div>
  </div>
  <?php endif; ?>
</div>

<button id="printBtn" onclick="window.print()">🖨️ Imprimer le Journal</button>
</body>
</html>
