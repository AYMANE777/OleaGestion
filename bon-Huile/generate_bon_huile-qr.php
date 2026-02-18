<?php
require_once __DIR__ . '/../config/config.ini.php';
$connect->set_charset('utf8mb4');

$bon_id = (int)($_GET['bon_id'] ?? 0);
if ($bon_id <= 0) { http_response_code(400); exit('ID de bon invalide'); }

$qr_flag = (int)($_GET['qr'] ?? 1);
$dailyPrintToken = function_exists('global_24h_token') ? global_24h_token() : bin2hex(random_bytes(4));
$duree_expiration = date('Y-m-d H:i:s', strtotime('+48 hours'));
$commentaire = 'Bon de route généré le ' . date('Y-m-d H:i:s');


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
    $s->close();
    $bon_rue_id = $existRow['id_bon_rue'];
} else {
    $sqlInsert = "INSERT INTO bon_rue (ref_bon_huile, statut, random_daily_value, commentaire, duree, created_at)
                  VALUES (?, ?, ?, ?, ?, NOW())";
    $s = $connect->prepare($sqlInsert);
    $s->bind_param('issss', $bon_id, $statut, $dailyPrintToken, $commentaire, $duree_expiration);
    $s->execute();
    $bon_rue_id = $s->insert_id;
    $s->close();
}

// Récupération des données
$stmt = $connect->prepare("
  SELECT 
    b.id AS id_bon, 
    b.date_bon, 
    b.quantite, 
    b.`cin-huil` AS cin_huil, 
    b.`matricul-voiture` AS matricule_voiture, 
    b.`image-voiture` AS image_voiture,
    COALESCE(CONCAT(c.prenom,' ',c.nom),'') AS client,
    COALESCE(c.ville,'') AS ville
  FROM bon_de_huile b
  LEFT JOIN client c ON c.id = b.client_id
  WHERE b.id = ? LIMIT 1
");
$stmt->bind_param('i', $bon_id);
$stmt->execute();
$bon = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt2 = $connect->prepare("
  SELECT d.quantite_bidon, bd.nom_bidon, bd.type_bidon
  FROM details_bon_huile d
  LEFT JOIN bidons bd ON bd.id_bidon=d.id_bidon
  WHERE d.id_bon = ?
");
$stmt2->bind_param('i', $bon_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$details_bidons = $res2->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// QR code
$uploadsDirRel = '/gestionMeknassa/uploads/rue-qr/';
$uploadsDirFS  = __DIR__ . '/../uploads/rue-qr/';
if(!is_dir($uploadsDirFS)) mkdir($uploadsDirFS,0755,true);



$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')?'https':'http';
$siteBase = $scheme.'://'.($_SERVER['HTTP_HOST']??'localhost');
$bonUrl = $siteBase."/gestionMeknassa/bon_rue_status.php?id=".$bon_rue_id;


$qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=".urlencode($bonUrl);
$fname = sprintf('bonrue_%d_%s.png',$bon_rue_id,date('Ymd_His'));
$fsPath = $uploadsDirFS.$fname;
$publicPath = $uploadsDirRel.$fname;

$img = @file_get_contents($qrApiUrl);
if($img && strlen($img)>50){ file_put_contents($fsPath,$img); }
$imgToShow = file_exists($fsPath) ? $publicPath : $qrApiUrl;

function esc($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
$logoPath='/gestionMeknassa/assets/img/logo_mek.png';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Bon de Route — Bon #<?= esc($bon_id) ?></title>
<style>
@page{size:A5 portrait;margin:1mm;}
body{font-family:"Poppins",Arial,sans-serif;margin:0;background:#fff;-webkit-print-color-adjust:exact;}
.sheet{width:148mm;min-height:210mm;padding:10mm;margin:0 auto;box-sizing:border-box;}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.header-left{flex:1;font-size:14px;color:#333;}
.header-right{flex:1;font-size:13px;text-align:right;line-height:1.4;}
.logo img{height:80px;display:block;margin:0 auto;}
.hr{border-top:2px solid #000;margin:8px 0;}
.title{text-align:center;font-size:18px;font-weight:700;margin-bottom:15px;}
table {
  width: 100%;
  border-collapse: collapse;
  margin: 10px 0;
  font-family: "Times New Roman", Times, serif;
}

th, td {
  padding: 6px 8px;
  border-bottom: 1px solid #ddd;
  text-align: left;
}

/* Titres (colonnes th) → 14px */
th {
  background: #f9f9f9;
  font-weight: 700;
  font-size: 14px;
  font-family: "Times New Roman", Times, serif;
}

/* Contenu (td) → 12px */
td {
  font-size: 12px;
  font-family: "Times New Roman", Times, serif;
}

.qr-text {
  margin: 25px 0 10px 0;
  font-size: 14px;
  color: red;
  font-weight: 700;
  text-align: center;
  display: block;
  width: 100%;
  white-space: nowrap; 
  direction: rtl;
}
.info-client-title {
  font-family: "Times New Roman", Times, serif;
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 5px;
  color : green ; 
}

.image-row{display:flex;justify-content:space-evenly;align-items:center;margin-top:15px;gap:10px;}
.image-row img{width:140px;height:140px;border:1px solid #ccc;padding:4px;background:#fff;object-fit:cover;}
.signature-block{display:flex;justify-content:space-between;margin-top:25px;font-size:14px;}
@media print{.no-print{display:none;}}
</style>
</head>
<body>
<div class="sheet">

<!-- HEADER -->

<div class="header">
  <div class="header-left" style="font-family:Arial, sans-serif; line-height:1.4;">
    <div style="font-weight:700; font-size:18px; color:#222;">Meknassa Olive</div>
    <div style="font-size:12px; color:#555; margin-top:2px;">
      Adresse : Meknassa el Gharbia<br>
      Tél : +212 7 01 08 52 61<br>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; +212 6 80 65 00 59
    </div>
  </div>
  <div class="logo"><img src="<?= esc($logoPath) ?>" alt="Logo"></div>
  <div class="header-right">Date: <?= esc($bon['date_bon']) ?></div>
</div>



<div class="hr"></div>

<!-- TITRE -->
<div class="title">Bon de route</div>

<!-- INFORMATIONS CLIENT -->
<div class="info-client-title">Les informations du client :</div>


<!-- TABLE INFOS CLIENT -->
<table>
  <tr>
    <th>Nom complet </th>
    <td><?= esc($bon['client']) ?></td>
  </tr>
  <tr>
    <th>Code d’identité nationale (CIN)</th>
    <td><?= esc($bon['cin_huil']) ?></td>
  </tr>
  <tr>
    <th>Matricule du véhicule</th>
    <td><?= esc($bon['matricule_voiture']) ?></td>
  </tr>
</table>

<!-- DÉTAILS DE LA COMMANDE -->
<div class="info-client-title">Détails de la commande :</div>
<table>
  <thead>
    <tr>
      <th>Article</th>
      <th>Quantité</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Huile d'olive (L)</td>
      <td><?= esc($bon['quantite']) ?> Litre</td>
    </tr>
    <?php foreach($details_bidons as $b): ?>
    <tr>
      <td>Bidon : <?= esc($b['type_bidon'] ?: $b['nom_bidon']) ?></td>
      <td><?= (int)$b['quantite_bidon'] ?>
</td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- TEXTE ARABE UNE SEULE LIGNE -->
<div class="qr-text">هذا البون صالح لمدة 48 ساعة فقط الرجاء مسح الرمز للتحقق من صلاحيته.</div>


<div class="image-row">
  <?php if(!empty($bon['image_voiture'])): ?>
    <img src="/gestionMeknassa/<?= esc($bon['image_voiture']) ?>" alt="Véhicule">
  <?php endif; ?>
  <img src="<?= esc($imgToShow) ?>" alt="QR Code">
</div>

<!-- SIGNATURES -->
<div class="signature-block">
  <div><strong>Signature du client</strong></div>
  <div><strong>Signature de l’administration</strong></div>
</div>

<div class="no-print" style="text-align:center; margin-top:15px;">
  <button onclick="window.print()">Imprimer</button>
</div>

</div>
</body>
</html>
