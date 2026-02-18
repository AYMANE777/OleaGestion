<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

// === PARAMÈTRES ===
$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin'] ?? '';

$where = "1";
$params = [];
$types  = "";

if (!empty($date_debut) && !empty($date_fin)) {
  $where .= " AND DATE(t.date_taratonage) BETWEEN ? AND ?";
  $params[] = $date_debut;
  $params[] = $date_fin;
  $types .= "ss";
} elseif (!empty($date_debut)) {
  $where .= " AND DATE(t.date_taratonage) >= ?";
  $params[] = $date_debut;
  $types .= "s";
} elseif (!empty($date_fin)) {
  $where .= " AND DATE(t.date_taratonage) <= ?";
  $params[] = $date_fin;
  $types .= "s";
}

// === RÉCUPÉRER LES CLIENTS AVEC LEURS TOTAUX + Référence des bons ===
$sql = "
SELECT 
  c.id_client_olive,
  CONCAT(c.prenom, ' ', c.nom) AS client_nom,
  GROUP_CONCAT(DISTINCT NULLIF(t.ref_bon_client, '') SEPARATOR ', ') AS ref_bons,
  COALESCE(SUM(t.poid_net), 0) AS total_poids,
  CASE WHEN SUM(t.poid_net) > 0 THEN
    ROUND(SUM(t.montant_total) / SUM(t.poid_net), 2)
  ELSE 0 END AS prix_moyen,
  COALESCE(SUM(t.montant_total), 0) AS total_credit,
  COALESCE((
    SELECT SUM(v.montant)
    FROM versement_olive v
    WHERE v.id_client_olive = c.id_client_olive
  ), 0) AS total_versement
FROM taratonage_client_olive_credit t
JOIN clientolive c ON c.id_client_olive = t.id_client_olive
WHERE $where
  AND (t.ref_bon_client IS NOT NULL AND t.ref_bon_client <> '')
GROUP BY c.id_client_olive
ORDER BY ref_bons ASC
";

$stmt = $connect->prepare($sql);
if (!empty($types)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$clients = [];
$total_general_poids = $total_general_credit = $total_general_versement = $total_general_solde = 0;
while ($r = $res->fetch_assoc()) {
  $r['solde'] = (float)$r['total_credit'] - (float)$r['total_versement'];
  $total_general_poids += (float)$r['total_poids'];
  $total_general_credit += (float)$r['total_credit'];
  $total_general_versement += (float)$r['total_versement'];
  $total_general_solde += $r['solde'];
  $clients[] = $r;
}

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
<title>Situation Clients Créanciers</title>
<style>
:root {
  --main:#115e59; --success:#16a34a; --danger:#b91c1c; --muted:#6b7280;
  --border:#e5e7eb; --bg:#f9fafb;
}
body { font-family:"Segoe UI",Arial,sans-serif; background:var(--bg); color:#111; padding:25px; margin:0; }
.sheet {
  background:#fff; max-width:1100px; margin:20px auto; padding:35px 40px;
  border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1);
}
header { display:flex; justify-content:space-between; align-items:center;
  border-bottom:3px solid var(--main); padding-bottom:8px; margin-bottom:20px; }
.brand h1 { margin:0; color:var(--main); font-size:22px; }
.logo img { height:120px; }
.meta { text-align:right; font-size:14px; color:var(--muted); }

h2 { margin-top:0; color:var(--main); }
table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13.5px; }
th, td { padding:6px 8px; border:1px solid var(--border); vertical-align:top; }
th { background:#ecfdf5; color:#065f46; text-align:left; }
.num { text-align:right; white-space:nowrap; }

.summary { margin-top:25px; display:flex; flex-wrap:wrap; justify-content:space-between; text-align:center; gap:10px; }
.summary .box { flex:1; min-width:220px; border-radius:8px; padding:10px; }
.poids { background:#e0f2fe; color:#1e3a8a; border:1px solid #bae6fd; }
.credit { background:#fef9c3; color:#854d0e; border:1px solid #fde68a; }
.paye { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.reste { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

#printBtn { display:block; margin:20px auto 0; padding:8px 18px; background:var(--main);
  color:#fff; border:none; border-radius:6px; cursor:pointer; }
#printBtn:hover { background:#0f766e; }

.badge {
  display:inline-block;  color:black; font-size:12px;
  padding:2px 6px; border-radius:4px; margin:1px;
}

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
      <strong>Situation Clients Créanciers</strong><br>
      Période :
      <?php if ($date_debut && $date_fin): ?>
        du <?= htmlspecialchars($date_debut) ?> au <?= htmlspecialchars($date_fin) ?>
      <?php elseif ($date_debut): ?>
        à partir du <?= htmlspecialchars($date_debut) ?>
      <?php elseif ($date_fin): ?>
        jusqu'au <?= htmlspecialchars($date_fin) ?>
      <?php else: ?>
        toutes les dates
      <?php endif; ?>
      <br><?= date('d/m/Y H:i') ?>
    </div>
  </header>

  <?php if (empty($clients)): ?>
    <h3 style='text-align : center;'>Aucun enregistrement du taratonage trouvé pour cette période.</h3>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Client</th>
          <th>Réf. Bon(s)</th>
          <th>Poids Total (Kg)</th>
          <th>Prix Moyen (DH)</th>
          <th>Total Crédit (DH)</th>
          <th>Total Versé (DH)</th>
          <th>Solde Restant (DH)</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($clients as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['client_nom']) ?></td>
          <td>
            <?php
              if (!empty($c['ref_bons'])) {
                foreach (explode(',', $c['ref_bons']) as $ref) {
                  echo '<span class="badge">' . htmlspecialchars(trim($ref)) . '</span> ';
                }
              } else {
                echo '-';
              }
            ?>
          </td>
          <td class="num"><?= number_format($c['total_poids'], 2, ',', ' ') ?></td>
          <td class="num"><?= number_format($c['prix_moyen'], 2, ',', ' ') ?></td>
          <td class="num"><?= number_format($c['total_credit'], 2, ',', ' ') ?></td>
          <td class="num"><?= number_format($c['total_versement'], 2, ',', ' ') ?></td>
          <td class="num" style="color:<?= ($c['solde'] > 0) ? '#b91c1c' : '#16a34a' ?>">
            <?= number_format($c['solde'], 2, ',', ' ') ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="summary">
      <div class="box poids">
        <strong>Total Poids Global</strong><br><?= number_format($total_general_poids, 2, ',', ' ') ?> Kg
      </div>
      <div class="box credit">
        <strong>Total Crédit Global</strong><br><?= number_format($total_general_credit, 2, ',', ' ') ?> DH
      </div>
      <div class="box paye">
        <strong>Total Versé Global</strong><br><?= number_format($total_general_versement, 2, ',', ' ') ?> DH
      </div>
      <div class="box reste">
        <strong>Total Reste Global</strong><br><?= number_format($total_general_solde, 2, ',', ' ') ?> DH
      </div>
    </div>
    <div class="signatures">
    <div class="sign"><div class="sign-line"></div>Visé par :</div>
    <div class="sign"><div class="sign-line"></div>Signature de l’Administration</div>
  </div>
  <?php endif; ?>
</div>

<button id="printBtn" onclick="window.print()">🖨️ Imprimer la Situation</button>
</body>
</html>
