<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

// --- Paramètres GET ---
$id_client   = isset($_GET['id_client']) ? (int)$_GET['id_client'] : 0;
$date_debut  = $_GET['date_debut'] ?? '';
$date_fin    = $_GET['date_fin'] ?? '';
$statut      = $_GET['statut_paiement'] ?? 'tous';

// --- Informations client ---
$client_info = [];
if ($id_client > 0) {
  $stmt = $connect->prepare("SELECT id, nom, prenom, phone, ville FROM client WHERE id = ?");
  $stmt->bind_param("i", $id_client);
  $stmt->execute();
  $client_info = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

$w = ["1=1"];
  $types = "";
  $args = [];

  if ($id_client > 0) {
    $w[] = "bon.id_client = ?";
    $types .= "i";
    $args[] = $id_client;
  }

  if ($date_debut !== '') {
    $w[] = "DATE(bon.created_at) >= ?";
    $types .= "s";
    $args[] = $date_debut;
  }
  if ($date_fin !== '') {
    $w[] = "DATE(bon.created_at) <= ?";
    $types .= "s";
    $args[] = $date_fin;
  }

  if ($statut !== '' && $statut !== 'tous') {
    $w[] = "bon.statut = ?";
    $types .= "s";
    $args[] = $statut;
  }

  $where = "WHERE " . implode(" AND ", $w);


$sql = "
  SELECT 
    bon.id_bon_sortie_bidon AS id_bon,
    DATE_FORMAT(bon.created_at,'%d/%m/%Y %H:%i') AS date_bon,
    CONCAT(c.prenom,' ',c.nom) AS client,
    bdn.nom_bidon,
    bdn.type_bidon,
    dbb.quantite,
    dbb.prix_unitaire,
    (dbb.quantite * dbb.prix_unitaire) AS total,
    bon.mode_paiement,
    bon.statut
  FROM details_bon_bidon dbb
  JOIN bon_sortie_bidon bon ON bon.id_bon_sortie_bidon = dbb.id_bon
  JOIN client c ON c.id = bon.id_client
  JOIN bidons bdn ON bdn.id_bidon = dbb.id_bidon
  $where
  ORDER BY bon.created_at DESC
";
$stmt = $connect->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$args);
$stmt->execute();
$bons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Calculs totaux ---
$somme_payee = 0;
$somme_non_paye = 0;
$total_general = 0;
$quantite_totale = 0;

foreach ($bons as $b) {
  $q = (float)$b['quantite'];
  $t = (float)$b['total'];
  $quantite_totale += $q;
  $total_general += $t;
  $s = strtolower(trim($b['statut']));
  if ($s === 'paye' || $s === 'payé') $somme_payee += $t;
  else $somme_non_paye += $t;
}

// --- Infos entreprise ---
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
<meta charset="utf-8" />
<title>Journal des Bidons — <?= htmlspecialchars($client_info ? $client_info['prenom'].' '.$client_info['nom'] : 'Tous les Clients') ?></title>
<style>
:root {
  --main:#115e59;
  --accent:#16a34a;
  --danger:#b91c1c;
  --muted:#6b7280;
  --border:#e5e7eb;
  --bg:#f9fafb;
}
*{box-sizing:border-box}
body{font-family:"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:var(--bg);color:#111;padding:30px}
.sheet{background:#fff;max-width:1000px;margin:auto;padding:40px;box-shadow:0 4px 20px rgba(0,0,0,.08);border-radius:12px}
header{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid var(--main);padding-bottom:10px;margin-bottom:25px}
.brand h1{font-size:24px;margin:0;color:var(--main);letter-spacing:.5px}
.brand small{color:var(--muted);font-size:13px}
.logo img{max-height:120px;width:auto}

h2{color:var(--main);margin-top:0}
table{width:100%;border-collapse:collapse;margin-top:15px}
th,td{padding:8px 10px;border:1px solid var(--border);font-size:14px}
th{background:#ecfdf5;color:#065f46;text-align:left}
tr:nth-child(even) td{background:#fafafa}
.num{text-align:right;white-space:nowrap}
.badge{padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold}
.badge-success{background:#dcfce7;color:#166534}
.badge-danger{background:#fecaca;color:#991b1b}
.summary{margin-top:30px;display:grid;grid-template-columns:repeat(3,1fr);gap:15px}
.total-card{padding:16px;border-radius:8px;text-align:center}
.total-paye{background:#f0fdf4;border:1px solid #bbf7d0}
.total-non-paye{background:#fef2f2;border:1px solid #fecaca}
.total-general{background:#eff6ff;border:1px solid #bfdbfe}
.total-card h4{margin:0 0 8px 0;font-size:14px}
.total-card .amount{font-size:20px;font-weight:bold}
.signatures{display:flex;justify-content:space-between;margin-top:50px;border-top:2px solid var(--border);padding-top:30px}
.sign-block{text-align:center;width:45%}
#printBtn{display:block;margin:40px auto 0;padding:10px 22px;border:none;border-radius:6px;background:var(--main);color:#fff;font-size:15px;cursor:pointer;transition:.2s}
#printBtn:hover{background:#0f766e}

@media print{body{background:#fff;padding:0}.sheet{box-shadow:none;margin:0;padding:20px;border:none;border-radius:0}#printBtn{display:none}}
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
    <strong>Registre des Bidons d’Huile</strong><br>
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
<h2><?= $client_info ? 'Client : '.htmlspecialchars($client_info['prenom'].' '.$client_info['nom']) : 'Détails des Commandes de Bidons : ' ?></h2>



<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Client</th>
      <th>Bidon (Type)</th>
      <th>Quantité</th>
      <th>Prix Unitaire (DH)</th>
      <th>Total (DH)</th>
      <th>Mode Paiement</th>
      <th>Statut</th>
      <th>N° bon </th>
      <th>Valide</th>
    </tr>
  </thead>
  <tbody>
  <?php if(empty($bons)): ?>
    <tr><td colspan="8" class="num text-muted">Aucune commande trouvée</td></tr>
  <?php else: foreach($bons as $b): ?>
    <tr>
      <td><?= htmlspecialchars($b['date_bon']) ?></td>
      <td><?= htmlspecialchars($b['client']) ?></td>
      <td><?= htmlspecialchars($b['nom_bidon'].' ('.$b['type_bidon'].')') ?></td>
      <td class="num"><?= number_format($b['quantite'],0,',',' ') ?></td>
      <td class="num"><?= number_format($b['prix_unitaire'],2,',',' ') ?></td>
      <td class="num"><?= number_format($b['total'],2,',',' ') ?></td>
      <td><?= htmlspecialchars($b['mode_paiement'] ?: '-') ?></td>
      <td>
        <span class="badge <?= (strtolower($b['statut'])==='paye'||strtolower($b['statut'])==='payé')?'badge-success':'badge-danger' ?>">
          <?= htmlspecialchars($b['statut']) ?>
        </span>
      </td>
  <td class="num"><?= number_format($b['id_bon'],0,',',' ') ?></td>
 <td class="num"></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>

<div class="summary">
  <div class="total-card total-general">
    <h4>Total Général</h4>
    <div class="amount"><?= number_format($total_general,2,',',' ') ?> DH</div>
  </div>
  <div class="total-card total-paye">
    <h4>Total Payé</h4>
    <div class="amount"><?= number_format($somme_payee,2,',',' ') ?> DH</div>
  </div>
  <div class="total-card total-non-paye">
    <h4>Total Non Payé</h4>
    <div class="amount"><?= number_format($somme_non_paye,2,',',' ') ?> DH</div>
  </div>
</div>

<?php if ($quantite_totale > 0): ?>
  <p style="margin-top:20px; font-size:15px; color:red; text-align:center; font-weight:bold;">
    Au cours de cette période, <?= number_format($quantite_totale, 0, ',', ' ') ?> sorties de bidons ont été enregistrées..
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
