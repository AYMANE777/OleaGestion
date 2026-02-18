<?php
require_once(__DIR__ . '/../config/config.ini.php');

// ─────────────────────────────────────────────
// PARAMS
// ─────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit('ID invalide');

// ─────────────────────────────────────────────
// FETCH DATA
// ─────────────────────────────────────────────
$stmt = $connect->prepare("
    SELECT 
        id,
        CONCAT(prenom, ' ', nom) AS client,
        cin,
        ref_bon,
        qte,
        provider,
        telephone,
        note,
        created_at
    FROM forgeterclient
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) exit('Bon introuvable');

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────
function esc($v){return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');}
function nfmt($n){return number_format($n, 0, ',', ' ');}

// ─────────────────────────────────────────────
// CONFIG
// ─────────────────────────────────────────────
const SOCIETE = 'MEKNASSA OLIVE';
const TEL = '07 01 08 52 61';
const ADR = 'مكناسة الغربية تازة 35000';
const AUTO_PRINT = true;
const PAPER_MM = 58;
$paperPx = round(PAPER_MM * 3.78);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Bon Oublier #<?= esc($row['ref_bon']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root {--wmm: 75mm;--wrap-px: 300px;}
body {width:var(--wmm); font:12px/1.3 "Roboto",Arial; margin:0; color:#000;}
.ticket{padding:8px;}
.center{text-align:center;}
.kv{display:flex; justify-content:space-between; font-size:11px;}
.hr{border-top:1px dashed #000; margin:6px 0;}
table{width:100%; border-collapse:collapse; font-size:11px;}
th,td{padding:3px 0; text-align:center;}
.total td{border-top:1px solid #000; font-weight:bold;}
@media print{.no-print{display:none;}}
@page{size: <?= PAPER_MM ?>mm auto; margin:0;}
</style>
</head>
<body>
<div class="ticket">
  <div class="center">
    <b><?= esc(SOCIETE) ?></b><br>
    <?= esc(ADR) ?><br>
    Tél: <?= esc(TEL) ?>
  </div>
  <div class="hr"></div>

  <div class="kv"><div><b>Client:</b></div><div><?= esc($row['client']) ?></div></div>
  <div class="kv"><div>CIN:</div><div><?= esc($row['cin']) ?></div></div>
  <div class="kv"><div style="font-size:15px">Ref Bon:</div><div style="font-size:15px"><b><?= esc($row['ref_bon']) ?></b></div></div>
  <div class="kv"><div>Provider:</div><div><?= esc($row['provider']) ?></div></div>
  <div class="kv"><div>Téléphone:</div><div><?= esc($row['telephone']) ?></div></div>
  <div class="kv"><div>Date:</div><div><?= esc($row['created_at']) ?></div></div>
  <div class="hr"></div>

  <table>
    <tr><th>Qté</th><th>Note</th></tr>
    <tr><td><?= nfmt($row['qte']) ?></td><td><?= esc($row['note']) ?></td></tr>
  </table>

  <div class="hr"></div>
<center>
<h2>ملاحظة : لم  نتوصل بهدا البون</h2>
</center>
  
  <div class="hr"></div>
  <div class="center">Merci pour votre confiance</div>

  <div class="no-print center" style="margin-top:8px;">
    <button onclick="window.print()">🖨️ Imprimer</button>
    <a href="javascript:history.back()">← Retour</a>
  </div>
</div>

<?php if (AUTO_PRINT): ?>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),200));</script>
<?php endif; ?>
</body>
</html>
