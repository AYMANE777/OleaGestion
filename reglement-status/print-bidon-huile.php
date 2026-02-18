<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

// === PARAMÈTRES ===
$id_client       = isset($_GET['id_client']) ? (int)$_GET['id_client'] : 0;
$date_debut      = $_GET['date_debut'] ?? '';
$date_fin        = $_GET['date_fin'] ?? '';
$type_commande   = $_GET['type_commande'] ?? 'tous';
$statut_paiement = $_GET['statut_paiement'] ?? 'tous';

// === INFO CLIENT ===
$client_info = [];
if ($id_client > 0) {
    $q = $connect->prepare("SELECT id, prenom, nom, ville, phone FROM client WHERE id=?");
    $q->bind_param("i", $id_client);
    $q->execute();
    $client_info = $q->get_result()->fetch_assoc();
}

// === CONDITIONS SQL ===
$where = ["1=1"];
$params = [];
$types  = "";

if ($id_client > 0) {
    $where[] = "b.client_id = ?";
    $params[] = $id_client;
    $types   .= "i";
}

if (!empty($date_debut) && !empty($date_fin)) {
    $where[] = "DATE(b.date_bon) BETWEEN ? AND ?";
    $params[] = $date_debut;
    $params[] = $date_fin;
    $types   .= "ss";
} elseif (!empty($date_debut)) {
    $where[] = "DATE(b.date_bon) >= ?";
    $params[] = $date_debut;
    $types   .= "s";
} elseif (!empty($date_fin)) {
    $where[] = "DATE(b.date_bon) <= ?";
    $params[] = $date_fin;
    $types   .= "s";
}

if ($type_commande === 'huile') {
    $where[] = "b.id NOT IN (SELECT DISTINCT d.id_bon FROM details_bon_huile d)";
} elseif ($type_commande === 'huile_bidon') {
    $where[] = "b.id IN (SELECT DISTINCT d.id_bon FROM details_bon_huile d)";
}

$whereSql = implode(" AND ", $where);

// === REQUÊTE PRINCIPALE ===
$sql = "
SELECT 
    b.id AS id_bon,
    CONCAT(c.prenom, ' ', c.nom) AS client,
    c.ville,
    b.date_bon,
    b.quantite,
    b.prix_unitaire,
    b.prix_net,
    b.provider,
    b.mode_paiement_huile,
    DATE_FORMAT(b.created_at, '%d/%m/%Y %H:%i') AS date_fmt
FROM bon_de_huile b
JOIN client c ON c.id = b.client_id
WHERE $whereSql
ORDER BY b.date_bon DESC
";

$stmt = $connect->prepare($sql);
if (!empty($types)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// === CALCULS ===
$somme_payee = $somme_non_paye = $total_general = $quantite_totale = 0;
$total_bidon_general = 0; // 🔹 AJOUT PRIX BIDONS

foreach ($res as &$r) {

    $id_bon = (int)$r['id_bon'];
    $prix_huile = (float)$r['prix_net'];
    $quantite_totale += (float)$r['quantite'];

    // 🔹 AJOUT PRIX BIDONS — récupère total bidons de ce bon
    $sql_bidon = "
        SELECT IFNULL(SUM(d.quantite_bidon * bdn.prix_bidon),0) AS total_bidon
        FROM details_bon_huile d
        LEFT JOIN bidons bdn ON bdn.id_bidon = d.id_bidon
        WHERE d.id_bon = ?
    ";
    $stmt_bidon = $connect->prepare($sql_bidon);
    $stmt_bidon->bind_param("i", $id_bon);
    $stmt_bidon->execute();
    $total_bidon = (float)$stmt_bidon->get_result()->fetch_assoc()['total_bidon'];
    $stmt_bidon->close();

    $prix_total = $prix_huile + $total_bidon; // total huile + bidons
    $total_general += $prix_total;
    $total_bidon_general += $total_bidon;

    $r['total_bidon'] = $total_bidon;
    $r['prix_total'] = $prix_total;

    // === STATUT PAIEMENT ===
    $mode = strtolower(trim($r['mode_paiement_huile'] ?? ''));
    $mode = str_replace(['é','è','ê'], 'e', $mode);
    $mode = preg_replace('/\s+/', ' ', $mode);

    $keywords_paye = ['espece', 'en espece', 'cash', 'virement', 'cheque', 'paye', 'deja paye', 'payer', 'regle', 'payement'];
    $is_paye = false;
    foreach ($keywords_paye as $mot) {
        if (strpos($mode, $mot) !== false) {
            $is_paye = true;
            break;
        }
    }

    if ($is_paye) {
        $somme_payee += $prix_total;
    } else {
        $somme_non_paye += $prix_total;
    }
}

$bank = [
    'name'  => 'Meknassa Olive',
    'phone' => '+212 7 01 08 52 61',
    'bic'   => 'Meknassa El Gharbia',
    'logo'  => '../assets/img/logo_meknassa.png'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Rapport Bons d’Huile</title>
<style>
:root {
  --main:#115e59; --accent:#16a34a; --danger:#b91c1c; --muted:#6b7280;
  --border:#e5e7eb; --bg:#f9fafb;
}
body { font-family:"Segoe UI",Roboto,Arial,sans-serif; margin:0; background:var(--bg); color:#111; padding:30px; }
.sheet {
  background:#fff; max-width:1000px; margin:auto; padding:40px;
  box-shadow:0 4px 20px rgba(0,0,0,0.08); border-radius:12px;
}
header { display:flex; justify-content:space-between; align-items:center; border-bottom:3px solid var(--main); padding-bottom:10px; margin-bottom:25px; }
.brand h1 { font-size:24px; margin:0; color:var(--main); }
.brand small { color:var(--muted); font-size:13px; }
.logo img { max-height:140px; }
.meta { text-align:right; font-size:14px; }
h2 { color:var(--main); margin-top:0; }

table { width:100%; border-collapse:collapse; margin-top:15px; }
th,td { padding:8px 10px; border:1px solid var(--border); font-size:14px; }
th { background:#ecfdf5; color:#065f46; }
tr:nth-child(even) td { background:#fafafa; }
.num { text-align:right; white-space:nowrap; }
.badge { padding:3px 8px; border-radius:4px; font-size:12px; }
.badge-success { background:#dcfce7; color:#166534; }
.badge-danger { background:#fecaca; color:#991b1b; }

.summary { margin-top:30px; display:grid; grid-template-columns:repeat(5,1fr); gap:15px; }
.total-card { padding:16px; border-radius:8px; text-align:center; font-size:14px; }
.total-paye { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.total-non-paye { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.total-general { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; }
.total-bidon { background:#faf5ff; border:1px solid #e9d5ff; color:#6b21a8; }
.amount { font-size:20px; font-weight:bold; }
.signatures{display:flex;justify-content:space-between;margin-top:50px;border-top:2px solid var(--border);padding-top:30px}
.sign-block{text-align:center;width:45%}
#printBtn { display:block; margin:40px auto 0; padding:10px 22px; border:none; border-radius:6px; background:var(--main); color:#fff; font-size:15px; cursor:pointer; }
@media print { body{background:#fff;padding:0}.sheet{box-shadow:none;border:none;margin:0;padding:20px}#printBtn{display:none} }
</style>
</head>
<body>
<div class="sheet">
<header>
  <div class="brand">
    <h1><?= htmlspecialchars($bank['name']) ?></h1>
    <small><?= htmlspecialchars($bank['bic']) ?><br>Tél: <?= htmlspecialchars($bank['phone']) ?></small>
  </div>
  <div class="logo"><?php if(file_exists($bank['logo'])): ?><img src="<?= htmlspecialchars($bank['logo']) ?>" alt="Logo"><?php endif; ?></div>
  <div class="meta">
    <strong>Registre d’huile</strong><br>
    <small>Imprimé le : <?= date('d/m/Y H:i') ?></small>
  </div>
</header>
<div class="filtres-info">
  <strong>Période :</strong>
  <?= !empty($date_debut)&&!empty($date_fin)
      ? 'Du '.date('d/m/Y',strtotime($date_debut)).' au '.date('d/m/Y',strtotime($date_fin))
      : (!empty($date_debut)
        ? 'À partir du '.date('d/m/Y',strtotime($date_debut))
        : (!empty($date_fin)
          ? 'Jusqu\'au '.date('d/m/Y',strtotime($date_fin))
          : 'Toute période')) ?>
</div>
<h2>
  <?= $client_info ? 'Client : '.htmlspecialchars($client_info['prenom'].' '.$client_info['nom']).' — '.htmlspecialchars($client_info['ville'] ?? '') : 'Détails des commandes d’huile' ?>
</h2>

<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Client</th>
      <th>Quantité</th>
      <th>Prix/L</th>
      <th>Prix total</th>
      <th>Mode Paiement</th>
      <th>Validé Par</th>
      <th>N° bon </th>
      <th>Valide</th>
    </tr>
  </thead>
  <tbody>
  <?php if(empty($res)): ?>
    <tr><td colspan="8" class="num text-muted">Aucun bon trouvé</td></tr>
  <?php else: foreach($res as $b): ?>
    <tr>
      <td><?= htmlspecialchars($b['date_fmt']) ?></td>
      <td><?= htmlspecialchars($b['client']) ?></td>
      <td class="num"><?= number_format($b['quantite'],2,',',' ') ?>L</td>
      <td class="num"><?= number_format($b['prix_unitaire'],2,',',' ') ?></td>
      <td class="num"><?= number_format($b['prix_total'],2,',',' ') ?></td>
     <td>
  <?php 
    $mode = htmlspecialchars($b['mode_paiement_huile'] ?: '-'); 
    $isCredit = strtolower($mode) === 'credit';
  ?>
  <span class="badge" style="<?= $isCredit ? 'color: red;' : 'color: black; font-weight: bold;' ?>">
    <?= $mode ?>
  </span>
</td>

      <td><?= htmlspecialchars($b['provider'] ?: '-') ?></td>
      <td><?= htmlspecialchars($b['id_bon'] ?: '-') ?></td>
      <td></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>

<div class="summary" style="display:flex; justify-content:center; gap:20px; text-align:center;">

      <div class="total-card total-general">
    <h4>Total Général</h4><div class="amount"><?= number_format($total_general,2,',',' ') ?> DH</div>
  </div>
  <div class="total-card total-paye">
    <h4>Total Payé</h4><div class="amount"><?= number_format($somme_payee,2,',',' ') ?> DH</div>
  </div>
  <div class="total-card total-non-paye">
    <h4>Total Non Payé</h4><div class="amount"><?= number_format($somme_non_paye,2,',',' ') ?> DH</div>
  </div>

</div>

<?php if ($quantite_totale > 0): ?>
  <p style="margin-top:20px; font-size:15px; color:red; text-align:center; font-weight:bold;">
    Au cours de cette période, un total de <?= strpos($quantite_totale, '.') !== false 
    ? number_format($quantite_totale, 2, ',', ' ') 
    : number_format($quantite_totale, 0, ',', ' ') ?>
L d’huile a été sorti.
  </p>
<?php endif; ?>

<div class="signatures">
  <div class="sign-block">VISÉ PAR<div class="sign-line"></div></div>
  <div class="sign-block">Signature de l’Administration<div class="sign-line"></div></div>
</div>

<button id="printBtn" onclick="window.print()">🖨️ Imprimer le Rapport</button>
</div>
</body>
</html>
