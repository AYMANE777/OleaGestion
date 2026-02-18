<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');

$id_client = isset($_GET['id_client']) ? (int)$_GET['id_client'] : 0;
if ($id_client <= 0) die("Client invalide.");

// =====================
// 1️⃣ Infos client
// =====================
$q = $connect->prepare("SELECT id, nom, prenom, phone AS telephone, ville FROM client WHERE id=?");
$q->bind_param("i", $id_client);
$q->execute();
$client = $q->get_result()->fetch_assoc();
if (!$client) die("Client introuvable.");

// =====================
// 2️⃣ Commandes mixtes (huile + bidon)
// =====================
$sql_mixte = "
    SELECT
      b.id AS id_bon,
      b.date_bon,
      b.prix_net,
      b.quantite,
      b.prix_unitaire,
      b.provider,
      b.mode_paiement_huile,
      GROUP_CONCAT(
          CONCAT(
              bd.nom_bidon, '::',
              bd.type_bidon, '::',
              d.quantite_bidon, '::',
              bd.prix_bidon
          ) SEPARATOR '||'
      ) AS bidons_details
    FROM bon_de_huile b
    LEFT JOIN details_bon_huile d ON d.id_bon = b.id
    LEFT JOIN bidons bd ON bd.id_bidon = d.id_bidon
    WHERE b.client_id = ?
    GROUP BY b.id
    ORDER BY b.date_bon DESC
";
$stmt = $connect->prepare($sql_mixte);
$stmt->bind_param('i', $id_client);
$stmt->execute();
$res_mixte = $stmt->get_result();
$commandes_mixte = [];
while ($r = $res_mixte->fetch_assoc()) {
    $bidons = [];
    if (!empty($r['bidons_details'])) {
        foreach (explode('||', $r['bidons_details']) as $b) {
            [$nom, $type, $qte, $prix] = explode('::', $b);
            $bidons[] = [
                'nom_bidon' => $nom,
                'type_bidon' => $type,
                'quantite_bidon' => (float)$qte,
                'prix_bidon' => (float)$prix,
                'total' => (float)$qte * (float)$prix
            ];
        }
    }
    $commandes_mixte[] = [
        'id_bon' => (int)$r['id_bon'],
        'date_bon' => $r['date_bon'],
        'quantite' => (float)$r['quantite'],
        'prix_unitaire' => (float)$r['prix_unitaire'],
        'prix_net' => (float)$r['prix_net'],
        'provider' => $r['provider'],
        'mode_paiement' => $r['mode_paiement_huile'],
        'bidons' => $bidons
    ];
}
$stmt->close();
// =====================
// 3️⃣ Détails Bidons séparés
// =====================
$sql_bidon = "
  SELECT
    bon.created_at AS date_bon,
    bdn.nom_bidon,
    bdn.type_bidon,
    dbb.quantite,
    dbb.prix_unitaire,
    (dbb.quantite * dbb.prix_unitaire) AS total,
    bon.statut
  FROM bon_sortie_bidon bon
  JOIN details_bon_bidon dbb ON dbb.id_bon = bon.id_bon_sortie_bidon
  JOIN bidons bdn ON bdn.id_bidon = dbb.id_bidon
  WHERE bon.id_client = ?
  ORDER BY bon.created_at DESC
";
$stmt = $connect->prepare($sql_bidon);
$stmt->bind_param('i', $id_client);
$stmt->execute();
$bons_bidon = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =====================
// 3️⃣ Totaux et versements
// =====================
// =====================
// =====================
// 3️⃣ Totaux et versements (corrigé pour ignorer les payés)
// =====================

// 🔹 Total Huile (seulement les bons non payés)
$total_huile = 0;
foreach ($commandes_mixte as $cmd) {
    if ($cmd['mode_paiement'] === 'CREDIT') {
        $total_huile += (float)$cmd['prix_net'];
    }
}

// 🔹 Total Bidons des commandes mixtes (seulement non payés)
$total_bidon_mixte = 0;
foreach ($commandes_mixte as $cmd) {
    if ($cmd['mode_paiement'] === 'CREDIT') {
        foreach ($cmd['bidons'] as $b) {
            $total_bidon_mixte += (float)$b['total'];
        }
    }
}

// 🔹 Total Bidons séparés (seulement non payés)
$total_bidon_separe = 0;
if (!empty($bons_bidon)) {
    foreach ($bons_bidon as $b) {
        if (strtoupper($b['statut']) !== 'PAYE') {
            $total_bidon_separe += (float)$b['total'];
        }
    }
}

// 🧮 Total global bidons = mixtes + séparés
$total_bidon = $total_bidon_mixte + $total_bidon_separe;

// 🔹 Total versements client (inchangé)
$versements = $connect->query("
  SELECT created_at, montant, mode_paiement, note
  FROM versement_client
  WHERE id_client = {$id_client}
  ORDER BY created_at ASC
")->fetch_all(MYSQLI_ASSOC);
$total_versement = array_sum(array_column($versements, 'montant'));

// 🔹 Calcul du solde (seulement pour les non payés)
$total_credit = $total_huile + $total_bidon;
$solde = $total_credit - $total_versement;



// =====================
// 4️⃣ Informations Meknassa
// =====================
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
<title>Relevé Client — <?= htmlspecialchars($client['nom'].' '.$client['prenom']) ?></title>
<style>
:root {
  --main:#115e59;
  --accent:#16a34a;
  --danger:#b91c1c;
  --muted:#6b7280;
  --border:#e5e7eb;
}
body {
  font-family:"Segoe UI",Roboto,Arial,sans-serif;
  margin:0;
  background:#f9fafb;
  color:#111;
  padding:30px;
}
.sheet {
  background:#fff;
  max-width:1050px;
  margin:auto;
  padding:40px;
  box-shadow:0 4px 20px rgba(0,0,0,0.08);
  border-radius:12px;
}
header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:3px solid var(--main);
  padding-bottom:10px;
  margin-bottom:25px;
}
.brand h1 {font-size:24px;margin:0;color:var(--main);}
.brand small {color:var(--muted);}
.logo img {max-height:110px;}
.meta {text-align:right;font-size:14px;}
.meta strong {color:var(--main);}
table {width:100%;border-collapse:collapse;margin-top:15px;}
th,td {padding:8px 10px;border:1px solid var(--border);font-size:14px;}
th {background:#ecfdf5;color:#065f46;}
.num {text-align:right;white-space:nowrap;}
tfoot td {font-weight:bold;}
.bidons-table th {background:#f0f9ff;color:#0369a1;}
.summary {
  margin-top:30px;
  background:#f0fdf4;
  border:1px solid #bbf7d0;
  border-radius:8px;
  padding:16px;
}
.summary div {font-size:15px;margin-bottom:4px;}
.summary strong {color:#064e3b;}
.signatures {
  display:flex;
  justify-content:space-between;
  margin-top:50px;
  border-top:2px solid var(--border);
  padding-top:30px;
}
.sign-block {text-align:center;width:45%;}
.sign-line {border-top:1px solid #333;margin-top:60px;}
#printBtn {
  display:block;
  margin:40px auto 0;
  padding:10px 22px;
  border:none;
  border-radius:6px;
  background:var(--main);
  color:#fff;
  font-size:15px;
  cursor:pointer;
  transition:0.2s;
}
#printBtn:hover {background:#0f766e;}
@media print {
  body {background:#fff;padding:0;}
  .sheet {box-shadow:none;margin:0;padding:20px;border:none;}
  #printBtn {display:none;}
}
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
    <strong>Relevé de compte</strong><br>
    <small><?= date('d/m/Y H:i') ?></small><br><br>
  
  </div>
</header>

<h2>Client : <?= htmlspecialchars($client['nom'].' '.$client['prenom']) ?></h2>
<p>
Téléphone : <strong><?= htmlspecialchars($client['telephone']) ?></strong><br>

</p>

<!-- Commandes mixtes -->
<h3>Liste des Commandes</h3>
<table>
<thead>
  <tr>
    <th>Date</th><th>Qté</th><th>Prix/L</th><th>Total Huile (MAD)</th><th>Total Bidon </th><th>Total Général</th><th>Validé par</th><th>Statut</th><th>Détails Bidons</th>
  </tr>
</thead>
<tbody>
<?php if(!$commandes_mixte): ?>
<tr><td colspan="9" class="num text-muted">Aucune commande </td></tr>
<?php else: 
foreach($commandes_mixte as $cmd):
  $totalBidon = 0;
  foreach ($cmd['bidons'] as $b) $totalBidon += $b['total'];
  $totalCmd = $cmd['prix_net'] + $totalBidon;
  $statut = ($cmd['mode_paiement'] === 'CREDIT') ? 'Non Payé' : 'Payé';
  $color  = ($cmd['mode_paiement'] === 'CREDIT') ? 'var(--danger)' : 'var(--accent)';
?>
<tr>
  <td><?= date('d/m/Y', strtotime($cmd['date_bon'])) ?></td>
  <td class="num"><?= number_format($cmd['quantite'],2,',',' ') ?>L</td>
  <td class="num"><?= number_format($cmd['prix_unitaire'],2,',',' ') ?> MAD</td>
  <td class="num" style="color:var(--accent);font-weight:bold;"><?= number_format($cmd['prix_net'],2,',',' ') ?> MAD</td>
  <td class="num" style="color:orange;font-weight:bold;"><?= number_format($totalBidon,2,',',' ') ?></td>
  <td class="num" style="color:blue;font-weight:bold;"><?= number_format($totalCmd,2,',',' ') ?> MAD</td>
  <td>M. <?= htmlspecialchars($cmd['provider']) ?></td>
  <td style="color:<?= $color ?>;font-weight:bold;"><?= $statut ?></td>
  <td>
    <?php if ($cmd['bidons']): ?>
      <table class="bidons-table" style="width:100%;">
        <thead><tr><th>Nom</th><th>Type</th><th>Qté</th><th>Prix</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach($cmd['bidons'] as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['nom_bidon']) ?></td>
            <td><?= htmlspecialchars($b['type_bidon']) ?></td>
            <td class="num"><?= number_format($b['quantite_bidon'],2,',',' ') ?></td>
            <td class="num"><?= number_format($b['prix_bidon'],2,',',' ') ?></td>
            <td class="num" style="color:var(--accent);"><?= number_format($b['total'],2,',',' ') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <span class="text-muted">Aucun bidon</span>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
<!-- étails Bidons séparés -->
<h3 style="margin-top:40px;">Détails des Bidons</h3>
<table>
<thead>
  <tr>
    <th>Date</th><th>Nom Bidon</th><th>Type</th><th>Prix Unitaire</th><th>Quantité</th><th>Prix Total (MAD)</th><th>Statut</th>
  </tr>
</thead>
<tbody>
<?php if(!$bons_bidon): ?>
<tr><td colspan="7" class="num text-muted" style="text-align: center;">Aucun bidon enregistré</td></tr>
<?php else: foreach($bons_bidon as $b): 
$color = ($b['statut'] === 'PAYE') ? 'var(--accent)' : 'var(--danger)';
?>
<tr>
  <td><?= date('d/m/Y', strtotime($b['date_bon'])) ?></td>
  <td><?= htmlspecialchars($b['nom_bidon']) ?></td>
  <td><?= htmlspecialchars($b['type_bidon']) ?></td>
  <td class="num"><?= number_format($b['prix_unitaire'],2,',',' ') ?> MAD</td>
   <td class="num"><?= number_format($b['quantite'],0,',',' ') ?></td>
  <td class="num" style="color:blue;font-weight:bold;"><?= number_format($b['total'],2,',',' ') ?> MAD</td>
  <td style="color:<?= $color ?>;font-weight:bold;"><?= htmlspecialchars($b['statut']) ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
<!-- Versements -->
<h3 style="margin-top:40px;">Détails des Versements</h3>
<table>
<thead>
  <tr>
    <th>Date</th><th>Montant</th><th>Mode de paiement</th><th>Note</th>
  </tr>
</thead>
<tbody>
<?php if(!$versements): ?>
<tr>
  <td colspan="4" class="num text-muted" style="text-align: center;">
    Aucun versement enregistré
  </td>
</tr>

<?php else: foreach($versements as $v): ?>
<tr>
  <td><?= date('d/m/Y', strtotime($v['created_at'])) ?></td>
  <td class="num" style="color:blue;font-weight:bold;"><?= number_format($v['montant'],2,',',' ') ?></td>
  <td><?= htmlspecialchars($v['mode_paiement']) ?></td>
  <td><?= htmlspecialchars($v['note'] ?: '—') ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>

<!-- Résumé final -->
<div class="summary">
  <div><strong>Total Huile :</strong> <?= number_format($total_huile, 2, ',', ' ') ?> MAD</div>
  <div><strong>Total Bidon :</strong> <?= number_format($total_bidon, 2, ',', ' ') ?> MAD</div>
  <div><strong>Total Versements :</strong> <?= number_format($total_versement, 2, ',', ' ') ?> MAD</div>
  <div><strong>Solde Restant :</strong>
    <?php if ($solde > 0): ?>
      <span style="color:var(--danger);font-weight:bold;">-<?= number_format($solde, 2, ',', ' ') ?> MAD </span>
    <?php elseif ($solde < 0): ?>
      <span style="color:var(--accent);font-weight:bold;">+<?= number_format(abs($solde), 2, ',', ' ') ?> MAD </span>
    <?php else: ?>
      <span style="color:var(--muted);font-weight:bold;">0,00 MAD</span>
    <?php endif; ?>
  </div>
</div>

<!-- Signatures -->
<div class="signatures">
  <div class="sign-block">
    <div>Signature du Client</div>

  </div>
  <div class="sign-block">
    <div>Signature de l’Administration</div>

  </div>
</div>

<button id="printBtn" onclick="window.print()">🖨️ Imprimer le Relevé</button>
</div>
</body>
</html>
