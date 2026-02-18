<?php
// print/recu_bon_fitor.php — Ticket thermique XPrinter (58/80mm) + logo

require_once __DIR__ . '/../../../../../config/config.ini.php';

// ── CONFIG ────────────────────────────────────────────────────────────────────
                   // Nom de votre société
const ORG_NAME   = 'https://meknassaolive.com/';   
const ORG_PHONE  = '07 01 08 52 61';                   // Optionnel
const ORG_ADDR   = 'مكناسة الغربية تازة 35000';               // Optionnel
const PAPER_MM   = 58;                                  // 58 ou 80
const NAME_SOCIETE  = 'MEKNASSA OLIVE'; // chemin image
const AUTO_PRINT = true;                                // auto-lancement print()
// ─────────────────────────────────────────────────────────────────────────────

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "ID invalide"; exit; }

// Détails du bon (adaptez si vos colonnes diffèrent)
$sql = "
  SELECT 
    b.id_bon AS id,
    IFNULL(b.created_at, t.taratonage_created_at) AS created_at_raw,
    DATE_FORMAT(IFNULL(b.created_at, t.taratonage_created_at), '%d/%m/%Y %H:%i') AS created_at,
    b.quantite, b.prix_init, b.prix_net, b.provider,
    t.poid_brut, t.poid_tarat, t.poid_net, t.note AS tara_note,
    vh.nom_vehicule, vh.matricule,
    CONCAT(c.nom,' ',c.prenom) AS chauffeur,
    CONCAT(p.nom_patron,' ',p.prenom_patron) AS patron
  FROM bon_fitor b
  JOIN taratonage t ON t.id_taratonage = b.id_taratonage
  JOIN vehicule vh  ON vh.id_vehicule = t.id_vehicule_Fitor
  JOIN chauffeur c  ON c.id_Fchauffeur = vh.id_Fchauffeur
  JOIN patron p     ON p.id_patron = c.id_patron
  WHERE b.id_bon = ?
  LIMIT 1
";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$bon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bon) { http_response_code(404); echo "Bon introuvable"; exit; }

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n, $dec=2){ return number_format((float)$n, $dec, ',', ' '); }

// Encodage base64 du logo si présent (meilleure fiabilité à l’impression)


// Largeur réelle en mm + largeur utile en px (96dpi ~ 3.78 px/mm)
$paperMm = (PAPER_MM === 80) ? 80 : 58;
$px = round($paperMm * 3.78); // largeur CSS pour de bons wraps
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Reçu Bon Fitor #<?= esc($bon['id']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{
      --wmm: 75mm;
      --wrap-px: 290px;
      --fg:black; --muted:black; --line:black; --soft:black;
    }
    *{ box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    html, body{ margin:0; padding:0; }
    body{
      width: var(--wmm);
      max-width: var(--wmm);
      font: 12px/1.25 "Roboto", "Segoe UI", Arial, sans-serif;
      color: var(--fg);
      background:#fff;
    }
    .ticket{
      width: 100%;
      max-width: var(--wmm);
      margin: 0 auto;
      padding: 6px 8px;
    }
    .center{ text-align:center; }
    .right { text-align:right; }
    .muted{ color: var(--muted); }
    .title{ font-weight:700; font-size: 13px; margin:4px 0 2px; }
    .logo{
      width: 100%;
      text-align: center;
      margin-top: 4px;
      margin-bottom: 4px;
    }
    .logo img{
      display:inline-block;
      max-width: calc(var(--wmm) - 16mm);
      width: 100%;
      height: auto;
    }
    .hr{ border-top:1px dashed var(--line); margin:6px 0; }
    .kv{ display:flex; justify-content:space-between; gap:8px; }
    .cols{ display:flex; justify-content:space-between; gap:8px; }
    .mini{ font-size:11px; }
    .table{ width:100%; border-collapse:collapse; }
    .table th, .table td{ padding:3px 0; border-bottom:1px dotted var(--soft); }
    .table th{ font-weight:600; }
    .total td{ font-weight:700; border-top:1px solid var(--line); border-bottom:1px solid var(--line); }
    .sign{ height:50px; border-top:1px dashed var(--soft); margin-top:8px; padding-top:4px; }
    .w-wrap{ width: var(--wrap-px); } /* aide au wrap dans Chrome */

    /* Impression stricte */
    @page{
      size: <?= $paperMm ?>mm auto;
      margin: 0;
    }
    @media print{
      body{ margin:0; }
      .no-print{ display:none !important; }
    }
  </style>
</head>
<body>
  <div class="ticket w-wrap">
    <!-- En-tête -->
    <div class="center">
      <div class="mini muted">
           <div class="title"><?= esc(NAME_SOCIETE) ?></div>
           <div class="title"><?= esc(ORG_NAME) ?></div>
        <?php if (ORG_ADDR)  echo esc(ORG_ADDR) . '<br>'; ?>
        <?php if (ORG_PHONE) echo 'Tél: ' . esc(ORG_PHONE); ?>
      </div>
    </div>

    <div class="hr"></div>

    <div class="kv mini"><div>Reçu — <b>Bon Achat de Grenion</b></div><div>N° <?= esc($bon['id']) ?></div></div>
    <div class="kv mini"><div>Validé par:</div><div><?= esc($bon['provider'] ?: '—') ?></div></div>
    <div class="kv mini"><div>Date:</div><div><?= esc($bon['created_at']) ?></div></div>

    <div class="hr"></div>

    <!-- Chaîne -->
    <div class="mini">
      <div class="kv"><div><b>Patron</b></div><div><?= esc($bon['patron']) ?></div></div>
      <div class="kv"><div><b>Chauffeur</b></div><div><?= esc($bon['chauffeur']) ?></div></div>
      <div class="kv"><div><b>Véhicule</b></div><div><?= esc($bon['nom_vehicule']) ?> (<?= esc($bon['matricule']) ?>)</div></div>
    </div>

    <div class="hr"></div>

    
    <!-- Ligne article -->
    <table class="table mini">
  <thead>
    <tr>
      <th class="center">Tarat</th>
      <th class="center">Brute</th>
      <th class="center">Net</th>
    </tr>
  </thead>

  <tbody>
    <tr>
      <td class="center"><b><?= nfmt($bon['poid_tarat'], 2) ?></b> kg</td>
      <td class="center"><b><?= nfmt($bon['poid_brut'], 2) ?></b> kg</td>
      <td class="center"><b><?= nfmt($bon['poid_net'], 3) ?></b> kg</td>
    </tr>
  </tbody>

  <tfoot>
    <tr class="total">
      <!-- 3 colonnes → on laisse 2 pour le label, 1 pour la valeur -->
      <td colspan="2" class="right">Total</td>
      <td class="right"><b><?= nfmt($bon['poid_net'], 3) ?></b> kg</td>
    </tr>
  </tfoot>
</table>

    <div class="hr"></div>

    <!-- Signatures -->
    <div class="cols mini">
      <div>Signature Chauffeur</div>
    </div>

    <div class="center mini muted" style="margin-top: 200px;">
      Merci pour votre confiance
    </div>

    <div class="no-print center" style="margin-top:8px">
      <button onclick="window.print()">🖨️ Imprimer</button>
      <a href="javascript:history.back()" style="margin-left:8px">← Retour</a>
    </div>
  </div>

  <?php if (AUTO_PRINT): ?>
  <script>window.addEventListener('load',()=>setTimeout(()=>window.print(),200));</script>
  <?php endif; ?>
</body>
</html>
