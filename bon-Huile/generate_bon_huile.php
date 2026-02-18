<?php
require_once(__DIR__ . '/../config/config.ini.php');



$dailyPrintToken = global_24h_token(); // same for all bons today

// ─────────────────────────────────────────────────────────────
// CONFIGURATION
// ─────────────────────────────────────────────────────────────
const ORG_NAME     = 'https://meknassaolive.com/';
const ORG_PHONE    = '07 01 08 52 61';
const ORG_ADDR     = 'مكناسة الغربية تازة 35000';
const PAPER_MM     = 58;
const NAME_SOCIETE = 'MEKNASSA OLIVE';
const AUTO_PRINT   = true;



// ─────────────────────────────────────────────────────────────
// PARAMS
// ─────────────────────────────────────────────────────────────
$id_bon = (int)($_GET['bon_id'] ?? 0);
if ($id_bon <= 0) {
  http_response_code(400);
  exit("ID invalide");
}


$qr = (int)($_GET['qr'] ?? 0);
$statut = ($qr === 1) ? 'ACTIVE' : 'DESACTIVE';
$dailyPrintToken=global_24h_token();
$duree_expiration = date('Y-m-d H:i:s', strtotime('+48 hours'));
$comment = 'Bon de route mis à jour le ' . date('Y-m-d H:i:s');

// Check if a Bon_Rue already exists for this bon
$sqlCheck = "SELECT id_bon_rue, duree FROM bon_rue WHERE ref_bon_huile  = ? LIMIT 1";
$stmt = $connect->prepare($sqlCheck);
$stmt->bind_param('i', $id_bon);
$stmt->execute();
$exist = $row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$comment  = 'Bon de route mis à jour le ' . date('Y-m-d H:i:s');

if ($exist) {
    // S’il existe → on met à jour le statut et le commentaire
    $sqlUpdate = "UPDATE bon_rue 
                  SET statut = ?
                  WHERE id_bon_rue = ?";
    $stmt = $connect->prepare($sqlUpdate);
    $stmt->bind_param('si', $statut, $exist['id_bon_rue']);
    $stmt->execute();
    $stmt->close();
} else {
    // Sinon → on crée un nouvel enregistrement
     $sqlInsert = "INSERT INTO bon_rue (ref_bon_huile, statut, random_daily_value, commentaire, duree)
                VALUES (?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sqlInsert);
    $stmt->bind_param('issss', $id_bon, $statut, $dailyPrintToken, $comment,$duree_expiration);
    $stmt->execute();
    $stmt->close();
}

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n, $dec=2){ return number_format((float)$n, $dec, ',', ' '); }



// ─────────────────────────────────────────────────────────────
// FETCH BON + DETAILS BIDONS
// ─────────────────────────────────────────────────────────────
$stmt = $connect->prepare("
  SELECT
    b.id AS id_bon,
    CONCAT(c.prenom, ' ', c.nom) AS client,
    c.ville,
    b.quantite,
    b.date_bon,
    b.prix_unitaire,
    b.prix_net,
    b.provider,
    b.mode_paiement_huile,
    b.note,
    b.created_at
  FROM bon_de_huile b
  JOIN client c ON c.id = b.client_id
  WHERE b.id = ?
  LIMIT 1
");
$stmt->bind_param('i', $id_bon);
$stmt->execute();
$bon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bon) {
  exit("Bon introuvable");
}

// Fetch details bidons
$stmt2 = $connect->prepare("
  SELECT 
    d.id_detail,
    d.quantite_bidon,
    bd.nom_bidon,
    bd.type_bidon,
    bd.prix_bidon
  FROM details_bon_huile d
  LEFT JOIN bidons bd ON bd.id_bidon = d.id_bidon
  WHERE d.id_bon = ?
");
$stmt2->bind_param('i', $id_bon);
$stmt2->execute();
$res2 = $stmt2->get_result();
$details_bidons = [];
while ($d = $res2->fetch_assoc()) {
  $details_bidons[] = [
    'nom_bidon'      => $d['nom_bidon'],
    'type_bidon'     => $d['type_bidon'],
    'quantite_bidon' => (float)$d['quantite_bidon'],
    'prix_bidon'     => (float)$d['prix_bidon'],
    'total'          => (float)$d['quantite_bidon'] * (float)$d['prix_bidon']
  ];
}
$stmt2->close();

// CALCULS
$totalBidons = array_sum(array_column($details_bidons, 'total'));
$totalGlobal = $bon['prix_net'] + $totalBidons;

$paperMm = (PAPER_MM === 80) ? 80 : 58;
$px = round($paperMm * 3.78);
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Reçu Bon Huile + Bidon #<?= esc($id_bon) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    :root {
        --wmm: 75mm;
        --wrap-px: 300px;
        --fg: black;
        --muted: black;
        --line: black;
        --soft: black;
    }

    * {
        box-sizing: border-box;
        -webkit-print-color-adjust: exact;
    }

    body {
        width: var(--wmm);
        font: 12px/1.25 "Roboto", Arial, sans-serif;
        margin: 0;
        color: var(--fg);
        background: #fff;
    }

    .ticket {
        padding: 6px 8px;
    }

    .center {
        text-align: center;
    }

    .kv {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
    }

    .hr {
        border-top: 1px dashed #000;
        margin: 5px 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        text-align: center;
    }

    /* ✅ center all table content */
    th,
    td {
        padding: 4px 0;
        vertical-align: middle;
    }

    th {
        border-bottom: 1px solid #000;
        font-weight: bold;
        text-align: center;
    }

    /* ✅ center headers */
    td {
        text-align: center;
    }

    /* ✅ ensure data cells are also centered */
    .total td {
        border-top: 1px solid #000;
        font-weight: bold;
    }

    .badge {
        display: inline-block;
        border: 1px solid var(--soft);
        padding: 1px 4px;
        border-radius: 3px;
        font-size: 10px;
    }

    @page {
        size: <?=$paperMm ?>mm auto;
        margin: 0;
    }

    @media print {
        .no-print {
            display: none;
        }
    }
    </style>
</head>

<body>
    <div class="ticket">
        <div class="center">
            <div><b><?= esc(NAME_SOCIETE) ?></b></div>
            <div><?= esc(ORG_NAME) ?></div>
            <div><?= esc(ORG_ADDR) ?><br>Tél: <?= esc(ORG_PHONE) ?></div>
           <div style="font-size: 20px;"><b>huile</b>
    <div>
        <span class="badge" style="font-size: 10px;">
            <?= esc($bon['mode_paiement_huile']) ? esc($bon['mode_paiement_huile']) : 'Credit' ?>
        </span>
    </div>
</div>

        <div class="hr"></div>
        <div class="kv">
            <div><b>Reçu — Bon Sortie huile </b></div>
        </div>

        <div class="kv">
            <div><b>N° :</b></div>
            <div><?= esc($bon['id_bon']) ?></div>
        </div>

        <div class="kv">
            <div><b>Code du Jour :</b></div>
            <div><?= esc($dailyPrintToken) ?></div>
        </div>
       <div class="kv" style="font-size: 13px;">
    <div><b>Date:</b></div>
    <div><b><?= esc($bon['date_bon']) ?></b></div>
</div>

        <div class="kv">
            <div><b>Client:</b></div>
            <div><?= esc($bon['client']) ?></div>
        </div>
        <div class="kv">
            <div>Validé par:</div>
            <div>M. <?= esc($bon['provider']) ?></div>
        </div>
        <div class="kv mini">
            <div>Statut Paiment:</div>
            <div>
                <span
                    class="badge"><?= esc($bon['mode_paiement_huile']) ? esc($bon['mode_paiement_huile']) : 'Credit' ?></span>
            </div>
        </div>
        <div class="hr"></div>

        <b>Huile

        </b>
        <table>
            <tr>
                
                <th>Prix (L)</th>
                <th>Quantité (L)</th>
                <th class="right">Prix Net (DH)</th>
            </tr>
            <tr>
               
                <td><?= nfmt($bon['prix_unitaire']) ?> DH</td>
                 <td><?= nfmt($bon['quantite']) ?> Litre</td>
                <td class="right"><?= nfmt($bon['prix_net']) ?> DH</td>
            </tr>
        </table>

        <?php if (!empty($details_bidons)): ?>
        <div class="hr"></div>
        <b>Bidons</b>
        <table>
            <tr>
                <th>Type</th>
                
                <th>Prix (DH)</th>
                <th>Qté</th>
                <th>Total (DH)</th>
            </tr>
            <?php foreach ($details_bidons as $b): ?>
            <tr>
                <td><?= esc($b['type_bidon'] ?: $b['nom_bidon']) ?></td>
                

                <td><?= nfmt($b['prix_bidon']) ?> DH</td>
                <td><?= nfmt($b['quantite_bidon'], 0) ?></td>
                <td><?= nfmt($b['total']) ?> DH</td>
            </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="3">Total Bidons</td>
                <td><?= nfmt($totalBidons) ?> DH</td>
            </tr>
        </table>
        <?php endif; ?>

        <div class="hr"></div>
        <div class="kv">
            <div><b>Total Général </b></div>
            <div><b><?= nfmt($totalGlobal) ?> DH</b></div>
        </div>

        <div class="kv" style="margin-top:10px;">
            <div>Signature Client</div>
            <div>Cachet</div>
        </div>
        <div class="center" style="margin-top:200px;">Merci pour votre confiance</div>

        <div class="no-print center" style="margin-top:10px;">
            <button onclick="window.print()">🖨️ Imprimer</button>
            <a href="javascript:history.back()">← Retour</a>
        </div>
    </div>

    <?php if (AUTO_PRINT): ?>
    <script>
    window.addEventListener('load', () => setTimeout(() => window.print(), 200));
    </script>
    <?php endif; ?>
</body>

</html>