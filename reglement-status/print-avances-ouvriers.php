<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

// ====== Filtres ======
$id_ouvrier = isset($_GET['id_ouvrier']) ? (int)$_GET['id_ouvrier'] : 0;
$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin'] ?? '';
$q          = trim((string)($_GET['q'] ?? ''));

// ====== WHERE dynamique ======
$where  = ["1"];
$params = [];
$types  = "";

if ($id_ouvrier > 0) {
  $where[] = "a.id_ouvrier = ?";
  $params[] = $id_ouvrier;
  $types   .= "i";
}

if ($date_debut !== '' && $date_fin !== '') {
  $where[] = "DATE(a.date_ajout) BETWEEN ? AND ?";
  $params[] = $date_debut;
  $params[] = $date_fin;
  $types   .= "ss";
} elseif ($date_debut !== '') {
  $where[] = "DATE(a.date_ajout) >= ?";
  $params[] = $date_debut;
  $types   .= "s";
} elseif ($date_fin !== '') {
  $where[] = "DATE(a.date_ajout) <= ?";
  $params[] = $date_fin;
  $types   .= "s";
}

if ($q !== '') {
  $where[] = "(o.prenom LIKE ? OR o.nom LIKE ? OR o.cin LIKE ? OR a.motif LIKE ? OR CAST(a.montant AS CHAR) LIKE ?)";
  $like = "%{$q}%";
  $params = array_merge($params, [$like, $like, $like, $like, $like]);
  $types .= "sssss";
}

$whereSql = implode(" AND ", $where);

// ====== Récupération des avances groupées par ouvrier ======
$sql = "
  SELECT
    a.id_avance,
    a.date_ajout,
    a.montant,
    COALESCE(a.motif,'') AS motif,
    o.id_ouvrier,
    COALESCE(o.cin,'') AS cin,
    CONCAT(COALESCE(o.prenom,''),' ',COALESCE(o.nom,'')) AS ouvrier
  FROM avance a
  LEFT JOIN ouvrier o ON o.id_ouvrier = a.id_ouvrier
  WHERE $whereSql
  ORDER BY ouvrier ASC, a.date_ajout DESC, a.id_avance DESC
";

$stmt = $connect->prepare($sql);
if (!empty($types)) {
  $bind = array_merge([$types], $params);
  $refs = [];
  foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
  call_user_func_array([$stmt, 'bind_param'], $refs);
}
$stmt->execute();
$res = $stmt->get_result();

// Regrouper par ouvrier
$data = [];
while ($r = $res->fetch_assoc()) {
  $key = trim(($r['ouvrier'] ?? 'Ouvrier') . ' — ' . ($r['cin'] ?: 'NC'));
  $data[$key][] = $r;
}
$stmt->close();

$brand = [
  'name'  => 'Meknassa Olive',
  'phone' => '+212 7 01 08 52 61',
  'addr'  => 'Meknassa El Gharbia',
  'logo'  => '../assets/img/logo_meknassa.png',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Rapport des Avances — Ouvriers</title>
<style>
:root { --main:#115e59; --muted:#6b7280; --border:#e5e7eb; --bg:#f9fafb; }
body { font-family:"Segoe UI",Arial,sans-serif; background:var(--bg); color:#111; padding:25px; margin:0; }
.sheet {
  background:#fff; max-width:1050px; margin:20px auto; padding:35px 40px;
  border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1);
  page-break-after:always;
}
header { display:flex; justify-content:space-between; align-items:center;
  border-bottom:3px solid var(--main); padding-bottom:8px; margin-bottom:20px; }
.brand h1 { margin:0; color:var(--main); font-size:22px; }
.logo img { height:140px; }
.meta { text-align:right; font-size:14px; color:var(--muted); }

h2 { margin:10px 0 5px; color:#111; font-size:18px; }
.small { color:var(--muted); font-size:12px; }

.table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13.5px; }
.table th, .table td { padding:6px 8px; border:1px solid var(--border); }
.table th { background:#ecfdf5; color:#065f46; text-align:left; }
.num { text-align:right; white-space:nowrap; }

.summary { margin-top:18px; display:flex; justify-content:flex-end; gap:10px; }
.box { border:1px solid #bfdbfe; background:#eff6ff; color:#1e40af; border-radius:8px; padding:10px 14px; min-width:220px; text-align:center; }
.box strong { display:block; margin-bottom:4px; }

.signatures { margin-top:15px; display:flex; justify-content:space-between; }
.sign { width:45%; text-align:center; font-size:13px; }
.sign-line { border-top:1px solid #000; margin-top:10px; }

.filters { font-size:12px; color:var(--muted); margin-top:4px; }

#printBtn { display:block; margin:20px auto 0; padding:8px 18px; background:var(--main);
  color:#fff; border:none; border-radius:6px; cursor:pointer; }
#printBtn:hover { background:#0f766e; }

@media print {
  body { background:#fff; padding:0; }
  .sheet { box-shadow:none; border:none; margin:0; padding:20px; page-break-after:always; }
  #printBtn { display:none; }
}
</style>
</head>
<body>

<?php if (empty($data)): ?>
  <div class="sheet"><h2>Aucune avance trouvée</h2></div>
<?php else: ?>

<?php
// Affichage des filtres appliqués
$filtreTxt = [];
if ($id_ouvrier > 0) $filtreTxt[] = "Ouvrier: #$id_ouvrier";
if ($date_debut !== '' && $date_fin !== '') $filtreTxt[] = "Période: {$date_debut} → {$date_fin}";
elseif ($date_debut !== '') $filtreTxt[] = "Depuis: {$date_debut}";
elseif ($date_fin !== '') $filtreTxt[] = "Jusqu’au: {$date_fin}";
if ($q !== '') $filtreTxt[] = "Recherche: “".htmlspecialchars($q, ENT_QUOTES, 'UTF-8')."”";
$filtreLigne = implode(" — ", $filtreTxt);
?>

<?php foreach ($data as $ouvrierKey => $rows): 
  $total_general = 0.0;
  foreach ($rows as $r) $total_general += (float)$r['montant'];
?>
<div class="sheet">
  <header>
    <div class="brand">
      <h1><?= htmlspecialchars($brand['name']) ?></h1>
      <small><?= htmlspecialchars($brand['addr']) ?><br>Tél: <?= htmlspecialchars($brand['phone']) ?></small>
    </div>
    <div class="logo">
      <?php if (is_string($brand['logo']) && @file_exists($brand['logo'])): ?>
        <img src="<?= htmlspecialchars($brand['logo']) ?>" alt="Logo">
      <?php endif; ?>
    </div>
    <div class="meta">
      <strong>Rapport des Avances </strong><br><?= date('d/m/Y H:i') ?>
   
    </div>
  </header>

  <h2><?= htmlspecialchars($ouvrierKey) ?></h2>
  <div class="small">Nombre d’avances: <?= count($rows) ?></div>

  <table class="table">
    <thead>
      <tr>
        <th style="width:140px;">Date</th>
      
        <th style="width:140px;">Montant</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= $r['date_ajout'] ? date('d/m/Y H:i', strtotime($r['date_ajout'])) : '' ?></td>
       
        <td class="num"><?= number_format((float)$r['montant'], 2, ',', ' ') ?> MAD</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="summary">
    <div class="box">
      <strong>Total des Avances</strong>
      <div><?= number_format($total_general, 2, ',', ' ') ?> DH</div>
    </div>
  </div>

  <!-- === Signatures === -->
  <div class="signatures">
    <div class="sign">
              <div class="sign-line"></div>
      Signature de l’Ouvrier

    </div>
    <div class="sign">
            <div class="sign-line"></div>
      Signature de l’Administration
  
    </div>
  </div>

</div>
<?php endforeach; ?>

<?php endif; ?>

<button id="printBtn" onclick="window.print()">🖨️ Imprimer le Rapport</button>
</body>
</html>
