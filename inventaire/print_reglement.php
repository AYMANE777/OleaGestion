<?php
require_once __DIR__ . '/../config/config.ini.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit("ID invalide"); }

$connect->set_charset('utf8mb4');


$sql = "SELECT r.id_reglement, r.ref_reglement, r.date_ajout, 
               r.situation, r.prix_total, r.motif,
               f.nom_fournisseur, f.prenom_fournisseur, 
               f.adress_fournisseur, f.tele_fournisseur
        FROM reglement_fournisseur r
        LEFT JOIN fournisseur f ON f.id_fournisseur = r.id_fournisseur
        WHERE r.id_reglement = ? LIMIT 1";

$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$reg) { http_response_code(404); exit("Règlement introuvable"); }


$stmt = $connect->prepare("SELECT nom, prix, quantite FROM piece_reglement WHERE id_reglement=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$pieces = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$stmt = $connect->prepare("SELECT payment_mode, montant, img_justification, date_ajout 
                           FROM paiement_reglement 
                           WHERE id_reglement=? ORDER BY id_paiement ASC");
$stmt->bind_param('i', $id);
$stmt->execute();
$paiements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n){ return number_format((float)$n,2,',',' '); }

$today = date('d/m/Y');

$bank = [
  'name'  => 'Meknassa Olive',
  'phone' => '+212 7 01 08 52 61',
  'bic'   => 'Meknassa el Gharbia',
  'logo'  => '../assets/img/logo_mek.png',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Relevé de Règlement #<?= esc($reg['ref_reglement']) ?></title>
<style>
:root {
  --ink:#111; --muted:#6b7280; --line:#bbb; --bg:#fff;
  --green:#0b6f3a; --danger:#c0392b; --header-bg:#f2f2f2;
}
body { font-family:"Times New Roman",serif; font-size:12px; margin:0; color:var(--ink); background:var(--bg); }
.page { width:210mm; min-height:297mm; padding:20mm; margin:auto; background:var(--bg); box-sizing:border-box; }
header { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:24px;
         border-bottom:1px solid var(--line); padding-bottom:10px; margin-bottom:15px; }
.brand h1{margin:0;font-size:22px;font-weight:700;} .brand small{color:var(--muted);font-size:12px;display:block;margin-top:2px;}
.logo-wrap img{max-height:90px;width:auto;object-fit:contain;}
.meta{text-align:right;font-size:13px;}
.details-table,.parts-table{width:100%;border-collapse:collapse;margin:10px 0;font-size:12px;}
.details-table th,.parts-table th{background:var(--header-bg);border:1px solid var(--line);padding:8px;text-transform:uppercase;font-weight:700;color:#111;}
.details-table td,.parts-table td{padding:8px;vertical-align:top;border:1px solid var(--line);}
.right{text-align:right;} .center{text-align:center;}
.parts-table tbody tr:nth-child(even){background:#fafafa;}
tfoot{font-weight:700;}
.summary-box{margin-top:15px;border-top:2px solid #064e2e;padding-top:10px;font-size:13px;}
.summary-line{display:flex;justify-content:space-between;margin-bottom:4px;}
.summary-line strong{color:#064e2e;}
.motif-box{margin-top:10px;padding:8px;border:1px dashed var(--line);background:#f9f9f9;}
.bank-sign{width:100%;margin-top:20px;}
.bank-sign td{padding:5px;border-top:1px solid var(--line);text-align:center;}
.label{font-weight:700;text-transform:uppercase;font-size:13px;color:#111;}
tfoot tr:last-child td { border-top:2px solid #064e2e; }
@media print {
  @page { size:A4 portrait; margin:15mm; }
  .page{width:auto;min-height:auto;padding:15mm;}
    tfoot { 
    display: table-row-group; 
  }
}
</style>
</head>
<body>
<div class="page">

<header>
  <div class="brand">
    <h1><?= esc($bank['name']) ?></h1>
    <small><strong>Adresse :</strong> <?= esc($bank['bic']) ?><br/><strong>Tél :</strong> <?= esc($bank['phone']) ?></small>
  </div>

  <div class="logo-wrap">
    <?php if (!empty($bank['logo'])): ?><img src="<?= esc($bank['logo']) ?>" alt="Logo" /><?php endif; ?>
  </div>

  <div class="meta">
    <div class="big" style="font-weight:700;">Règlement fournisseur</div>
    <div class="sub"><strong>Date :</strong> <?= esc(date('d/m/Y', strtotime($reg['date_ajout'] ?? $today))) ?></div>
  </div>
</header>

<table class="details-table">
  <tr>
    <th style="width:50%;color:#064e2e;">Détails du règlement</th>
    <th style="width:50%;color:#064e2e;">Fournisseur</th>
  </tr>
  <tr>
   <td>
  <div><strong>Référence :</strong> <?= esc($reg['ref_reglement']) ?></div>

  <?php
    $situation = strtolower(trim((string)($reg['situation'] ?? '')));
    if ($situation === '') $situation = 'non payé';
    $color = match($situation) {
        'payé' => 'var(--green)',
        'avance' => '#007bff',
        default => 'var(--danger)'
    };
  ?>
  <div style="margin-top:6px;">
    <strong>Situation de paiement :</strong>
    <span style="color:<?= $color ?>; font-weight:700;">
      <?= esc(ucfirst($situation)) ?>
    </span>
  </div>
</td>

    <td>
      <?php
        $prenom = (string)($reg['prenom_fournisseur'] ?? '');
        $nom = (string)($reg['nom_fournisseur'] ?? '');
      ?>
      <div><strong>Nom complet :</strong> <?= esc(trim("$prenom $nom")) ?></div>
      <div style="margin-top:6px;"><strong>Téléphone :</strong> <?= esc($reg['tele_fournisseur'] ?? '—') ?></div>
    </td>
  </tr>
</table>

<table class="parts-table">
<thead>
  <tr>
    <th>Nom de la pièce</th>
    <th class="right">Prix Unitaire</th>
    <th class="center">Quantité</th>
    <th class="right">Prix Total</th>
  </tr>
</thead>
<tbody>
<?php
$totalGeneral = 0;
if (empty($pieces)):
  echo '<tr><td colspan="4" class="center" style="color:var(--muted);padding:12px;">Aucune pièce enregistrée.</td></tr>';
else:
  foreach($pieces as $p):
    $prix = (float)$p['prix'];
    $qte = (int)$p['quantite'];
    $totalLigne = $prix * $qte;
    $totalGeneral += $totalLigne;
?>
<tr>
  <td><?= esc($p['nom']) ?></td>
  <td class="right"><?= nfmt($prix) ?> MAD</td>
  <td class="center"><?= $qte ?></td>
  <td class="right"><?= nfmt($totalLigne) ?> MAD</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
<tfoot>
  <tr>
    <td colspan="3" class="right" style="color:#064e2e;">TOTAL GÉNÉRAL</td>
    <td class="right" style="color:#064e2e;"><?= nfmt($totalGeneral) ?> MAD</td>
  </tr>
</tfoot>
</table>

<?php if (!empty($paiements)): ?>
<h3 style="margin-top:15px;color:#064e2e;font-size:14px;">Historique des paiements</h3>
<table class="parts-table">
  <thead>
    <tr>
      <th>Mode de paiement</th>
      <th class="center">Montant</th>
      <th class="center">Date</th>
    </tr>
  </thead>
  <tbody>
    <?php 
      $totalVerse = 0;
      foreach ($paiements as $p):
        $montant = (float)$p['montant'];
        $totalVerse += $montant;
        $mode = esc($p['payment_mode']);
        $date = esc(date('d/m/Y', strtotime((string)$p['date_ajout'])));
    ?>
    <tr>
      <td><?= $mode ?></td>
      <td class="right"><?= nfmt($montant) ?> MAD</td>
      <td class="center"><?= $date ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="2" class="right" style="color:#064e2e;">TOTAL VERSÉ</td>
      <td class="right" style="color:#064e2e;"><?= nfmt($totalVerse) ?> MAD</td>
    </tr>
  </tfoot>
</table>
<?php else: $totalVerse = 0; endif; ?>

<?php
  $reste = $totalGeneral - $totalVerse;
?>
<div class="summary-box">
  <div class="summary-line"><strong>Total facture :</strong> <span><?= nfmt($totalGeneral) ?> MAD</span></div>
  <div class="summary-line"><strong>Total versé :</strong> <span><?= nfmt($totalVerse) ?> MAD</span></div>
  <div class="summary-line"><strong>Reste à payer :</strong> 
    <span style="color:<?= $reste > 0 ? 'var(--danger)' : 'var(--green)' ?>;">
      <?= nfmt($reste) ?> MAD
    </span>
  </div>
</div>

<?php 
$motif = trim((string)($reg['motif'] ?? ''));
if ($motif !== ''): ?>
<div class="motif-box">
  <strong>Motif :</strong> <?= esc($motif) ?>
</div>
<?php endif; ?>

<table class="bank-sign">
  <tr>
    <td><div class="label">Visé par</div></td>
    <td><div class="label">Signature de l’Administration</div></td>
  </tr>
</table>

</div>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),200));</script>
</body>
</html>
