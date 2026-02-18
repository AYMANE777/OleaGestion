<?php
// print/recu_bon_bidon.php — Ticket thermique XPrinter (58/80mm)

// ── CONFIG & DB ───────────────────────────────────────────────────────────────
require_once(__DIR__ . '/../config/config.ini.php');



/**
 * Deterministic token that changes every day.
 * If you have a secret in config, set: $APP_SECRET = 'your_secret';
 */
/** Per-day token (per bon) */
// One token for the whole day (Africa/Casablanca). Changes automatically at midnight.

$dailyPrintToken = global_24h_token(); // same for all bons today


// Identité
const ORG_NAME     = 'https://meknassaolive.com/';
const ORG_PHONE    = '07 01 08 52 61';
const ORG_ADDR     = 'مكناسة الغربية تازة 35000';
const PAPER_MM     = 58;                 // 58 ou 80
const NAME_SOCIETE = 'MEKNASSA OLIVE';
const AUTO_PRINT   = true;               // auto-lancement print()





$id = (int)($_GET['id'] ?? $_GET['bon_id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "ID invalide"; exit; }

//========================================== QR CODE ==================================
$qr = (int)($_GET['qr'] ?? 0);
$qrToken   = null;
$qrPayload = null;

$comment = 'Bon de route mis à jour le ' . date('Y-m-d H:i:s');

// Check if a Bon_Rue already exists for this bon
$sqlCheck = "SELECT id_bon_rue, duree FROM bon_rue WHERE ref_bon_bidon = ? LIMIT 1";
$stmt = $connect->prepare($sqlCheck);
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($qr === 1) {
  // Generate payload and activate bon
  $qrPayload = "BON_ROUTE|BIDON={$id}|TOKEN={$dailyPrintToken}|DATE=" . date('Y-m-d');

  if ($row) {
    // Update existing Bon_Rue → Activate
    $sqlUpd = "
      UPDATE bon_rue 
      SET random_daily_value = ?, 
          statut = 'ACTIVE', 
          commentaire = ?,
          duree = COALESCE(duree, DATE_ADD(NOW(), INTERVAL 72 HOUR))
      WHERE id_bon_rue = ?
    ";
    $stmt = $connect->prepare($sqlUpd);
    $stmt->bind_param('ssi', $dailyPrintToken, $comment, $row['id_bon_rue']);
    $stmt->execute();
    $stmt->close();
  } else {
    // Insert new Bon_Rue → First time
    $sqlIns = "
      INSERT INTO bon_rue
        (ref_bon_huile, ref_bon_bidon, statut, random_daily_value, commentaire, duree)
      VALUES (NULL, ?, 'ACTIVE', ?, ?, DATE_ADD(NOW(), INTERVAL 72 HOUR))
    ";
    $stmt = $connect->prepare($sqlIns);
    $stmt->bind_param('iss', $id, $dailyPrintToken, $comment);
    $stmt->execute();
    $stmt->close();
  }

} else {
  // qr = 0 → Deactivate and update if exists
  if ($row) {
    $sqlDeact = "
      UPDATE bon_rue 
      SET random_daily_value = ?, 
          statut = 'DESACTIVE', 
          commentaire = ? 
      WHERE id_bon_rue = ?
    ";
    $stmt = $connect->prepare($sqlDeact);
    $stmt->bind_param('ssi', $dailyPrintToken, $comment, $row['id_bon_rue']);
    $stmt->execute();
    $stmt->close();
  }
}

//=============================== End QR code ====================================







// ── HELPERS ──────────────────────────────────────────────────────────────────
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n, $dec=2){ return number_format((float)$n, $dec, ',', ' '); }

// ── FETCH: ENTÊTE BON + CLIENT ───────────────────────────────────────────────
$sqlBon = "
  SELECT 
    b.id_bon_sortie_bidon AS id,
    b.id_client,
    DATE_FORMAT(b.created_at, '%d/%m/%Y %H:%i') AS created_at,
    b.created_at AS created_at_raw,
    b.destination,
    b.statut,
    b.mode_paiement,
    CONCAT(c.nom, ' ', c.prenom) AS client_nom,
    c.phone AS client_phone,
    c.ville  AS client_ville,
    c.address AS client_address
  FROM bon_sortie_bidon b
  JOIN client c ON c.id = b.id_client
  WHERE b.id_bon_sortie_bidon = ?
  LIMIT 1
";
$stmt = $connect->prepare($sqlBon);
$stmt->bind_param('i', $id);
$stmt->execute();
$bon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bon) { http_response_code(404); echo "Bon introuvable"; exit; }

// ── FETCH: DÉTAILS LIGNES ────────────────────────────────────────────────────
$sqlDet = "
  SELECT 
    d.id_detail,
    d.id_bidon,
    COALESCE(bi.nom_bidon, '—') AS nom_bidon,
    COALESCE(bi.type_bidon, '—') AS type_bidon,
    d.quantite,                     -- nombre d’unités (ex: 5 bidons)
    d.quantite_bidon,               -- capacité par bidon si applicable (ex: 20 L)
    d.prix_unitaire                 -- prix unitaire (par unité)
  FROM details_bon_bidon d
  LEFT JOIN bidons bi ON bi.id_bidon = d.id_bidon
  WHERE d.id_bon = ?
  ORDER BY d.id_detail ASC
";
$stmt = $connect->prepare($sqlDet);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
$total_qte = 0;
$total_capacite = 0.0; // somme (quantite * quantite_bidon) si utile
$total_montant = 0.0;

while ($r = $res->fetch_assoc()) {
  $montant = ((float)$r['quantite']) * ((float)$r['prix_unitaire']);
  $r['montant'] = $montant;
  $rows[] = $r;

  $total_qte     += (int)$r['quantite'];
  $total_capacite += ((float)$r['quantite']) * ((float)$r['quantite_bidon']);
  $total_montant += $montant;
}
$stmt->close();

// Largeur utile (96 dpi ≈ 3.78 px/mm)
$paperMm = (PAPER_MM === 80) ? 80 : 58;
$px = round($paperMm * 3.78);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Reçu Bon Bidon #<?= esc($bon['id']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{
      --wmm: 75mm;
      --wrap-px: 300px;
      --fg:black; --muted:black; --line:black; --soft:black;
    }
    *{ box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    html, body{ margin:0; padding:0; }
    body{
      width: var(--wmm);
      max-width: var(--wmm);
      font: 12px/1.25 "Roboto","Segoe UI",Arial,sans-serif;
      color: var(--fg);
      background:#fff;
    }
    .ticket{ width:100%; max-width:var(--wmm); margin:0 auto; padding:6px 8px; }
    .center{ text-align:center; }
    .right { text-align:right; }
    .muted{ color: var(--muted); }
    .title{ font-weight:700; font-size:13px; margin:4px 0 2px; }
    .hr{ border-top:1px dashed var(--line); margin:6px 0; }
    .kv{ display:flex; justify-content:space-between; gap:8px; }
    .mini{ font-size:11px; }
    .table{ width:100%; border-collapse:collapse; }
    .table th, .table td{ padding:3px 0; border-bottom:1px dotted var(--soft); }
    .table th{ font-weight:600; font-size:11px; }
    .total td{ font-weight:700; border-top:1px solid var(--line); border-bottom:1px solid var(--line); }
    .w-wrap{ width: var(--wrap-px); }
    .badge{ display:inline-block; border:1px solid var(--soft); padding:1px 4px; border-radius:3px; font-size:10px; }
    @page{ size: <?= $paperMm ?>mm auto; margin:0; }
    @media print{ .no-print{ display:none !important; } }
  </style>
</head>
<body>
  <div class="ticket w-wrap">
    <!-- En-tête -->
    <div class="center">
      <div class="title"><?= esc(NAME_SOCIETE) ?></div>
      <div class="mini muted"><?= esc(ORG_NAME) ?></div>
      <div class="mini muted">
        <?php if (ORG_ADDR)  echo esc(ORG_ADDR) . '<br>'; ?>
        <?php if (ORG_PHONE) echo 'Tél: ' . esc(ORG_PHONE); ?>
      </div>
       <div class="title">Bidons <div>
        <span class="title"><?= esc($bon['statut']) ?></span>
        <?php if (!empty($bon['mode_paiement'])): ?>
          <span class="title"><?= esc($bon['mode_paiement']) ?></span>
        <?php endif; ?>
      </div></div>
    </div>

    <div class="hr"></div>

    <!-- Infos bon -->
    <div class="kv mini"><div><b>Reçu — Bon Sortie Bidon</b></div><div>N° <?= esc($bon['id']) ?></div></div>
 
    <div class="kv mini">
      <div>
      <b>Code du jour : &nbsp;<?= esc($dailyPrintToken) ?><div></div>
      </div>
      <div>
  

      </div>
    <div>



    </div></div>
 <div class="kv mini" style="font-size: 13px;">
  <div>Date:</div>
  <div><?= esc($bon['created_at']) ?></div>
</div>

    <div class="kv mini"><div>Statut:</div>
      <div>
        <span class="badge"><?= esc($bon['statut']) ?></span>
        <?php if (!empty($bon['mode_paiement'])): ?>
          <span class="badge"><?= esc($bon['mode_paiement']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php if (!empty($bon['destination'])): ?>
      <div class="kv mini"><div>Destination:</div><div><?= esc($bon['destination']) ?></div></div>
    <?php endif; ?>

    <div class="hr"></div>

    <!-- Client -->
    <div class="mini">
      <div class="kv"><div><b>Client</b></div><div><?= esc($bon['client_nom']) ?></div></div>

    </div>

    <div class="hr"></div>

    <!-- Détails lignes -->
    <table class="table mini">
      <thead>
        <tr>
        
          <th></th>
          <th>Type</th>
        <th class="center">P.U</th>
          <th class="center">Quantité</th>
          

          <th class="center">Prix total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="center">Aucune ligne</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><b><?= esc($r['nom_bidon']) ?></b></td>
              <td>
                
                <div class="muted mini"><?= esc($r['type_bidon']) ?></div>
              </td>
         <td class="right"><?= nfmt($r['prix_unitaire'], 2) ?> DH</td>
              <td class="center"><?= esc((int)$r['quantite']) ?></td>
              <td class="right"><?= nfmt($r['montant'], 2) ?> DH</td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="total"> 
          <td>Total général </td>
          <td></td>
          <td class="center"></td>
          <td class="center"></td>
         
          <td class="right"><?= nfmt($total_montant, 2) ?> DH</td>
        </tr>
      </tfoot>
    </table>








    <!-- Notes / Signatures
    <div class="mini muted">
      <div>Montants en MAD</div>
    </div> -->

    <div class="kv mini" style="margin-top:8px">
      <div>Signature Client</div>
      <div>Cachet</div>
    </div>

    <div class="center mini muted" style="margin-top: 160px;">
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

<?php if ($qr === 1 && $qrPayload): ?>
  <!-- Minimal QR generator (client-side). If CDN blocked, token still shows. -->

  <script>
    (function() {
      try {
        if (window.QRCode && document.getElementById('qrBox')) {
          new QRCode(document.getElementById('qrBox'), {
            text: <?= json_encode($qrPayload) ?>,
            width: 160,
            height: 160,
            correctLevel: QRCode.CorrectLevel.M
          });
        }
      } catch (e) { /* no-op fallback: token text is still visible */ }

  
    })();
  </script>
<?php endif; ?>




</body>
</html>
