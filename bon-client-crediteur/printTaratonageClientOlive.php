<?php
require_once __DIR__ . '/../config/config.ini.php';

// ───────────────────── CONFIG GÉNÉRALE ─────────────────────
const ORG_NAME   = 'Meknassa Olive';
const ORG_PHONE  = '+212 7 01 08 52 61';
const ORG_ADDR   = 'Meknassa El Gharbia - Taza 35000';
const PAPER_MM   = 58; // 58 ou 80 mm
const NAME_SOCIETE = 'MEKNASSA OLIVE';
const AUTO_PRINT = true;
// ──────────────────────────────────────────────────────────

// ID taratonage
$id = (int)($_GET['id_taratonage'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "ID invalide"; exit; }

// Récupération des données
$sql = "
  SELECT 
    t.id_taratonage,
    t.date_taratonage,
    t.poid_net,
    t.prix_unitaire,
    t.montant_total,
    t.provider,
    t.note,
    CONCAT(c.nom, ' ', c.prenom) AS client_nom,
    c.telephone
  FROM taratonage_client_olive_credit t
  JOIN clientolive c ON c.id_client_olive = t.id_client_olive
  WHERE t.id_taratonage = ?
  LIMIT 1
";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$bon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bon) { http_response_code(404); echo "Taratonage introuvable"; exit; }

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n, $dec=2){ return number_format((float)$n, $dec, ',', ' '); }

// Largeur CSS équivalente
$paperMm = (PAPER_MM === 80) ? 80 : 58;
$px = round($paperMm * 3.78);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Bon Taratonage #<?= esc($bon['id_taratonage']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
  --wmm: 75mm;
  --wrap-px: 290px;
  --fg:black; --muted:black; --line:black; --soft:black;
}
*{ box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
html, body{ margin:0; padding:0; background:#fff; color:var(--fg); }
body{
  width: var(--wmm);
  max-width: var(--wmm);
  font: 12px/1.3 "Segoe UI", Roboto, Arial, sans-serif;
}
.ticket{
  width:100%; max-width:var(--wmm);
  margin:0 auto; padding:6px 8px;
}
.center{ text-align:center; }
.right{ text-align:right; }
.muted{ color:var(--muted); }
.title{ font-weight:700; font-size:13px; margin:3px 0 2px; }
.logo{ text-align:center; margin:80px 0; }
.logo img{ max-width:calc(var(--wmm) - 15mm); height:auto; }
.hr{ border-top:1px dashed var(--line); margin:6px 0; }
.kv{ display:flex; justify-content:space-between; font-size:11px; }
.table{ width:100%; border-collapse:collapse; font-size:12px; }
.table th, .table td{ padding:3px 0; border-bottom:1px dotted var(--soft); text-align:center; }
.table th{ font-weight:600; }
.total td{ font-weight:700; border-top:1px solid var(--line); border-bottom:1px solid var(--line); }
.sign{ height:45px; border-top:1px dashed var(--soft); margin-top:8px; padding-top:4px; }
@page{ size: <?= $paperMm ?>mm auto; margin:0; }
@media print{ .no-print{ display:none !important; } }
</style>
</head>
<body>
<div class="ticket w-wrap">

  <!-- Logo et titre -->
  <div class="logo">
   
  </div>

  <div class="center">
    <div class="title"><?= esc(NAME_SOCIETE) ?></div>
    <div class="muted" style="font-size:11px;">
      <?= esc(ORG_ADDR) ?><br>
      Tél: <?= esc(ORG_PHONE) ?>
    </div>
  </div>

  <div class="hr"></div>

  <div class="kv"><div><b>Bon de Taratonage</b></div><div>N° <?= esc($bon['id_taratonage']) ?></div></div>
  <div class="kv"><div>Date:</div><div><?= date('d/m/Y H:i', strtotime($bon['date_taratonage'])) ?></div></div>
  <div class="kv"><div>Client:</div><div><?= esc($bon['client_nom']) ?></div></div>
  <div class="kv"><div>Téléphone:</div><div><?= esc($bon['telephone']) ?></div></div>
  <div class="kv"><div>Traiter par:</div><div><?= esc($bon['provider']) ?></div></div>

  



  <div class="hr"></div>

  <!-- Détails du poids -->
  <table class="table">
    <thead>
      <tr><th>Poids net</th><th>Prix unitaire</th><th>Montant total</th></tr>
    </thead>
    <tbody>
      <tr>
        <td><?= nfmt($bon['poid_net']) ?> kg</td>
        <td><?= nfmt($bon['prix_unitaire'], 2) ?> DH/kg</td>
        <td><b><?= nfmt($bon['montant_total'], 2) ?> DH</b></td>
      </tr>
    </tbody>
  </table>

  <?php if (!empty($bon['note'])): ?>
  <div class="hr"></div>
  <div style="font-size:11px;"><b>Note :</b> <?= esc($bon['note']) ?></div>
  <?php endif; ?>

  <div class="sign">Signature du client</div>

  <div class="center muted" style="margin-top: 70px;">Merci pour votre confiance</div>

  <div class="no-print center" style="margin-top:10px;">
    <button onclick="window.print()">🖨️ Imprimer</button>
    <a href="javascript:history.back()" style="margin-left:10px;">← Retour</a>
  </div>

</div>

<?php if (AUTO_PRINT): ?>
<script>
window.addEventListener('load', ()=>setTimeout(()=>window.print(),200));
</script>
<?php endif; ?>
</body>
</html>
