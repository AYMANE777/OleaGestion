<?php
$configPath = __DIR__ . '/../config/config.ini.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo "Fichier de configuration introuvable : " . htmlspecialchars($configPath);
    exit;
}
require_once $configPath;

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n){ return number_format((float)$n, 2, ',', ' '); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "ID invalide"; exit; }

$connect->set_charset('utf8mb4');


$sql = "SELECT v.id_versement, v.montant, v.mode_paiement, v.date_versement,
               c.nom, c.prenom, c.ville
        FROM versement_client v
        LEFT JOIN client c ON c.id = v.id_client
        WHERE v.id_versement = ? LIMIT 1";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$bon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bon) { http_response_code(404); echo "Reçu introuvable"; exit; }

$today = date('d/m/Y');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Reçu Versement #<?= esc($bon['id_versement']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body {
    font-family: "Times New Roman", Times, serif;
    font-size: 13px;
    color: #000;
    margin: 0;
    padding: 0;
    background: #fff;
}
.page {
    width: 210mm;
    height: 148mm;
    margin: 0 auto;
    padding: 8mm 18mm 5mm 18mm; /* compacté en haut, plus d'espace en bas */
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

/* --- En-tête --- */
.header {
    text-align: center;
    line-height: 0;
    margin: 0;
    padding: 0;
}
.header img {
    height: 140px;
    display: block;
    margin: 0 auto;
    padding: 0;
    border: 0;
}
.header hr {
    border: none;
    border-top: 1.8px solid #000;
    width: 100%;
    margin: 0;
    padding: 0;
    position: relative;
    top: -3px;
}

/* --- Titre --- */
h1 {
    text-align: center;
    font-size: 19px;
    font-weight: bold;
    margin: 3px 0 6px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* --- Texte principal --- */
.text {
    text-align: justify;
    line-height: 1.55;
    font-size: 13px;
    margin: 0 5px 8px 5px;
    text-justify: inter-word;
}

/* --- Tableau administratif --- */
.table-admin {
    width: 70%;
    margin: 6px auto 10px auto;
    border-collapse: collapse;
    border: 1px solid #000;
    font-size: 13px;
}
.table-admin th,
.table-admin td {
    border: 1px solid #000;
    padding: 5px 10px;
    vertical-align: middle;
}
.table-admin th {
    background: #f2f2f2;
    width: 45%;
    text-align: left;
    font-weight: bold;
    letter-spacing: 0.2px;
}
.table-admin td {
    text-align: left;
    font-weight: normal;
}
@media print {
    .table-admin th {
        background: #eaeaea !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* --- Signatures --- */
.signature {
    display: flex;
    justify-content: space-between;
    margin-top: 18px;
    padding: 0 25px;
}
.signature div {
    width: 45%;
    text-align: center;
    font-weight: bold;
    border-top: 1px solid #000;
    padding-top: 5px;
}

@media print {
    @page { size: A5 landscape; margin: 10mm; }
    body { margin: 0; }
    .page { width: auto; height: auto; padding: 0; }
}
</style>
</head>
<body>
<div class="page">

    <!-- LOGO + LIGNE COLLÉE -->
    <div class="header">
        <img src="../assets/img/logo_meknassa-no-bg.png" alt="Logo Meknassa Olive">
        <hr>
    </div>

    <!-- TITRE -->
    <h1>Reçu de Versement Client</h1>

    <!-- TEXTE -->
    <div class="text">
        Je soussigné, <strong><?= esc(trim(($bon['prenom'] ?? '') . ' ' . ($bon['nom'] ?? ''))) ?></strong>,
        demeurant à <strong><?= esc($bon['ville'] ?? '—') ?></strong>,
        reconnais avoir versé à l’administration de l’entreprise
        <strong>Meknassa Olive</strong> la somme suivante :
    </div>

    <!-- TABLEAU -->
    <table class="table-admin">
        <tr>
            <th>Montant versé</th>
            <td><strong><?= nfmt($bon['montant']) ?> MAD</strong></td>
        </tr>
        <tr>
            <th>Date du versement</th>
   <td>
  <?= esc(
    $bon['date_versement']
      ? date('d/m/Y à H:i', strtotime($bon['date_versement']))
      : date('d/m/Y à H:i')
  ) ?>
</td>


        </tr>
        <tr>
            <th>Mode de paiement</th>
            <td><?= esc($bon['mode_paiement']) ?></td>
        </tr>
    </table>

    <!-- TEXTE FINAL -->
    <div class="text">
        Le présent reçu est établi afin d’attester du versement effectué par la personne susmentionnée,
        à titre de règlement partiel ou total d’une commande d’huile d’olive ou de bidons,
        conformément aux conditions convenues avec l’Administration de
        <strong>Meknassa Olive</strong>.
    </div>

    <!-- SIGNATURES -->
    <div class="signature">
        <div>VISÉ PAR</div>
        <div>Signature de l’Administration</div>
    </div>
</div>

<script>
window.addEventListener('load', function(){
    setTimeout(()=>window.print(), 200);
});
</script>
</body>
</html>
