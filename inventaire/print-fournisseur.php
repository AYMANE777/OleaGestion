<?php
require_once __DIR__ . '/../config/config.ini.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit("ID invalide"); }

$connect->set_charset('utf8mb4');

// Récupération des infos du fournisseur
$sql = "SELECT id_fournisseur, prenom_fournisseur, nom_fournisseur, tele_fournisseur, adress_fournisseur
        FROM fournisseur WHERE id_fournisseur = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$fournisseur = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fournisseur) {
  http_response_code(404);
  exit("Fournisseur introuvable");
}

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
$nomComplet = trim(($fournisseur['prenom_fournisseur'] ?? '') . ' ' . ($fournisseur['nom_fournisseur'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Fournisseur - <?= esc($nomComplet) ?></title>
<style>
body {
  font-family: "Times New Roman", serif;
  font-size: 20px;
  color: #111;
  background: #fff;
  margin: 0;
}

.page {
  width: 210mm;
  min-height: 297mm;
  padding: 25mm;
  margin: auto;
  box-sizing: border-box;
  background: #fff;
  border: 2px solid #064e2e;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

header {
  text-align: center;
  border-bottom: 4px solid #064e2e;
  padding-bottom: 15px;
  margin-bottom: 50px;
}
header h1 {
  font-size: 42px;
  margin: 0;
  color: #064e2e;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 3px;
}

.details {
  width: 95%;
  border-collapse: collapse;
  font-size: 22px;
  margin-top: 10px;
}
.details th, .details td {
  border: 3px solid #064e2e;
  padding: 35px 25px;
}
.details th {
  background: #e6f0eb;
  color: #064e2e;
  width: 30%;
  text-transform: uppercase;
  font-weight: bold;
  font-size: 22px;
  letter-spacing: 1px;
}
.details td {
  width: 70%;
  font-weight: 600;
  font-size: 30px;
  background: #fafafa;
  text-align: left;
  line-height: 1.6;
}

@media print {
  @page { size: A4 portrait; margin: 10mm; }
  body { background: #fff; }
  .page { border: none; padding: 10mm; }
}
</style>
</head>
<body>
<div class="page">
  <header>
    <h1>FOURNISSEUR</h1>
  </header>

  <table class="details">
    <tr><th>Nom complet</th><td><?= esc($nomComplet) ?></td></tr>
    <tr><th>Téléphone</th><td><?= esc($fournisseur['tele_fournisseur']) ?></td></tr>
    <tr><th>Adresse</th><td><?= esc($fournisseur['adress_fournisseur']) ?></td></tr>
  </table>
</div>

<script>
window.addEventListener('load', () => setTimeout(() => window.print(), 300));
</script>
</body>
</html>
