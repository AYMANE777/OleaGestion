<?php
require_once __DIR__ . '/../config/config.ini.php';
$connect->set_charset('utf8mb4');

$bon_id = (int)($_GET['bon_id'] ?? 0);
if ($bon_id <= 0) { http_response_code(400); exit('ID de bon invalide'); }

$qr_flag = (int)($_GET['qr'] ?? 1);
$dailyPrintToken = function_exists('global_24h_token') ? global_24h_token() : bin2hex(random_bytes(4));
$duree_expiration = date('Y-m-d H:i:s', strtotime('+48 hours'));
$commentaire = 'Bon de route généré le ' . date('Y-m-d H:i:s');

// Vérifier ou créer le bon_rue
$stmt = $connect->prepare("SELECT id_bon_rue FROM bon_rue WHERE ref_bon_huile = ? LIMIT 1");
$stmt->bind_param('i', $bon_id);
$stmt->execute();
$existRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$statut = ($qr_flag === 1) ? 'ACTIVE' : 'DESACTIVE';
if ($existRow) {
  $sqlUpdate = "UPDATE bon_rue SET statut=?, random_daily_value=?, commentaire=?, duree=? WHERE id_bon_rue=?";
  $s = $connect->prepare($sqlUpdate);
  $s->bind_param('ssssi', $statut, $dailyPrintToken, $commentaire, $duree_expiration, $existRow['id_bon_rue']);
  $s->execute(); 
  $bon_rue_id = $existRow['id_bon_rue'];
  $s->close();
} else {
  $sqlInsert = "INSERT INTO bon_rue (ref_bon_huile, statut, random_daily_value, commentaire, duree, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
  $s = $connect->prepare($sqlInsert);
  $s->bind_param('issss', $bon_id, $statut, $dailyPrintToken, $commentaire, $duree_expiration);
  $s->execute();
  $bon_rue_id = $s->insert_id;
  $s->close();
}


$stmt = $connect->prepare("
  SELECT 
    b.id AS id_bon, 
    b.date_bon, 
    b.quantite, 
    b.prix_unitaire, 
    b.prix_net, 
    b.`cin-huil` AS cin_huil, 
    b.`matricul-voiture` AS matricule_voiture, 
    b.`image-voiture` AS image_voiture,
    COALESCE(CONCAT(c.prenom,' ',c.nom),'') AS client,
    COALESCE(c.phone,'') AS telephone
  FROM bon_de_huile b
  LEFT JOIN client c ON c.id = b.client_id
  WHERE b.id = ? LIMIT 1
");
$stmt->bind_param('i', $bon_id);
$stmt->execute();
$bon = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Détails bidons avec prix
$stmt2 = $connect->prepare("
  SELECT d.quantite_bidon, bd.nom_bidon, bd.type_bidon, bd.prix_bidon
  FROM details_bon_huile d
  LEFT JOIN bidons bd ON bd.id_bidon=d.id_bidon
  WHERE d.id_bon = ?
");
$stmt2->bind_param('i', $bon_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$details_bidons = $res2->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

function esc($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
$logoPath='/gestionMeknassa/assets/img/logo_mek.png';
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Bon de Route — Bon #<?= esc($bon_id) ?></title>
    <style>
    @page {
        size: A5 landscape;
        margin: 10mm;
    }

    body {
        font-family: "Poppins", Arial, sans-serif;
        margin: 0;
        background: #fff;
        -webkit-print-color-adjust: exact;
    }

    .sheet {
        width: 210mm;
        height: 138mm;
        padding: 8mm 10mm;
        margin: 0 auto;
        box-sizing: border-box;
        overflow: hidden;
    }

    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .header-left {
        flex: 1;
        font-size: 14px;
        color: #333;
    }

    .header-right {
        flex: 1;
        font-size: 13px;
        text-align: right;
        line-height: 1.4;
    }

    .logo img {
        height: 70px;
        display: block;
        margin: 0 auto;
    }

    .hr {
        border-top: 2px solid #000;
        margin: 8px 0;
    }

    .title {
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        font-family: "Times New Roman", Times, serif;
    }

    th,
    td {
        padding: 6px 8px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }

    th {
        background: #f9f9f9;
        font-weight: 700;
        font-size: 14px;
    }

    td {
        font-size: 12px;
    }

    .info-client-title {
        font-family: "Times New Roman", Times, serif;
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 5px;
        color: green;
    }

    .signature-block {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        font-size: 14px;
    }

    .no-print {
        text-align: center;
        margin-top: 15px;
    }

    @media print {
        .no-print {
            display: none;
        }
    }
    </style>
</head>

<body>
    <div class="sheet">

        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <div style="font-weight:700;font-size:18px;">Meknassa Olive</div>
                <div style="font-size:12px;color:#555;">
                    Adresse : Meknassa el Gharbia<br>
                    Tél : +212 7 01 08 52 61 / +212 6 80 65 00 59
                </div>
            </div>
            <div class="logo">
                <img src="<?= esc($logoPath) ?>" alt="Logo">
            </div>
            <div class="header-right">Date: <?= esc($bon['date_bon']) ?></div>
        </div>

        <div class="hr"></div>
        <div class="title">Bon de Livraison</div>
        <div class="info-client-title">Les informations du client :</div>

        <table style="width:100%; border:1px solid #999; border-collapse:collapse; margin-bottom:10px;">
            <tr>
                <th style="background:#f0f0f0; width:50%;">Nom complet</th>
                <th style="background:#f0f0f0; width:50%;">Téléphone</th>
            </tr>
            <tr>
                <td><?= esc($bon['client']) ?></td>
                <td><?= esc($bon['telephone']) ?></td>
            </tr>
        </table>

        <!-- DÉTAILS COMMANDE -->
        <div class="info-client-title">Détails de la commande :</div>
       <?php 
$total_general = (float)$bon['prix_net']; // total de l’huile

foreach($details_bidons as $b){
    $prixU = (float)($b['prix_bidon'] ?? 0);
    $qt = (int)$b['quantite_bidon'];
    $total_general += $prixU * $qt;
}
?>

<table>
    <thead>
        <tr>
            <th>Article</th>
            <th>Prix Unitaire (DH)</th>
            <th>Quantité</th>
            <th>Total (DH)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Huile d'olive (L)</td>
            <td><?= number_format($bon['prix_unitaire'], 2, ',', ' ') ?> DH</td>
            <td><?= esc($bon['quantite']) ?> L</td>
            <td><?= number_format($bon['prix_net'], 2, ',', ' ') ?> DH</td>
        </tr>

        <?php foreach($details_bidons as $b): 
            $prixU = (float)($b['prix_bidon'] ?? 0);
            $qt = (int)$b['quantite_bidon'];
            $total = $prixU * $qt;
        ?>
        <tr>
            <td>Bidon : <?= esc($b['type_bidon'] ?: $b['nom_bidon']) ?></td>
            <td><?= number_format($prixU, 2, ',', ' ') ?> DH</td>
            <td><?= $qt ?></td>
            <td><?= number_format($total, 2, ',', ' ') ?> DH</td>
        </tr>
        <?php endforeach; ?>


        <tr style="font-weight:bold; background:#f5f5f5;">
            <td colspan="3" style="text-align:right;">Total Général :</td>
            <td><?= number_format($total_general, 2, ',', ' ') ?> DH</td>
        </tr>
    </tbody>
</table>


        <!-- SIGNATURES -->
        <div class="signature-block">
            <div><strong>Signature du client</strong></div>
            <div><strong>Signature de l’administration</strong></div>
        </div>

        <div class="no-print">
            <button onclick="window.print()">Imprimer</button>
        </div>

    </div>
</body>

</html>