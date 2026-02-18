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

$sql = "SELECT a.id_avance, a.montant, a.date_ajout,
               o.prenom, o.nom, o.cin
        FROM avance a
        LEFT JOIN ouvrier o ON o.id_ouvrier = a.id_ouvrier
        WHERE a.id_avance = ? LIMIT 1";
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
<title>Reçu Avance #<?= esc($bon['id_avance']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    body { font-family: "Times New Roman", Times, serif; font-size:12px; margin:0; padding:0; color:#000; }
    .page { width:210mm; height:148mm; padding:15mm; box-sizing:border-box; margin:auto; transform: rotate(-90deg); transform-origin: top left; }
    h1 { text-align:center; font-size:18px; font-weight:bold; margin-bottom:20px; }
    .section { margin-bottom:20px; line-height:1.6; text-align:justify; }
    .kv { display:flex; justify-content:space-between; margin-bottom:8px; font-weight:bold; }
    .signature {
        display: flex;
        justify-content: space-between;
        margin-top:40px;
    }
    .signature div {
        width:45%;
        text-align:center;
        padding-top:4px;
        font-weight:bold;
    }
    @media print {
        @page { size:A5 landscape; margin:15mm; }
        body { margin:0; }
        .page { transform:none; width:auto; height:auto; }
    }
</style>
</head>
<body>
<div class="page" style="padding:5mm 15mm 15mm 15mm;"> 

    <div style="text-align:center; margin-bottom:5px; border-bottom:2px solid #000; padding-bottom:5px;">
        <img src="../assets/img/logo.png" alt="Logo Entreprise" style="height:80px; display:block; margin:0 auto 3px;">
        <div style="font-weight:bold; font-size:18px;">Meknassa Olive</div>
    </div>

    <h1 style="margin-top:5px;">REÇU D’AVANCE D’ARGENT</h1>

    <div class="section">
        Je soussigné, <strong><?= esc(trim(($bon['prenom'] ?? '') . ' ' . ($bon['nom'] ?? ''))) ?></strong>,
        titulaire du CIN : <strong><?= esc($bon['cin'] ?? '—') ?></strong>,
        reconnais avoir reçu de l’administration de l’entreprise Meknassa Olive la somme suivante :
    </div>

    <div class="section kv" style="border:1px solid #000; padding:8px; margin-bottom:10px;">
        <div>Montant : <strong><?= nfmt($bon['montant']) ?> MAD</strong></div>
        <div>Date de remise : <strong><?= esc(date('d/m/Y', strtotime($bon['date_ajout'] ?? $today))) ?></strong></div>
    </div>

    <div class="section">
        Cette somme constitue une avance sur salaire et sera déduite conformément aux accords convenus.
    </div>

    <div class="signature" style="margin-top:40px;">
        <div style="border-top:1px solid #000;">Signature de l’ouvrier</div>
        <div style="border-top:1px solid #000;">Signature de l’Administration</div>
    </div>
</div>


<script>
    window.addEventListener('load', function(){
        setTimeout(()=>window.print(),200);
    });
</script>
</body>
</html>
