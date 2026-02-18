<?php
require_once __DIR__ . '/../config/config.ini.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit("ID invalide"); }

$connect->set_charset('utf8mb4');

$sql = "SELECT id_ouvrier, prenom, nom, cin, fonction, tele, image, img_cin
        FROM ouvrier WHERE id_ouvrier = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$ouvrier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ouvrier) {
  http_response_code(404);
  exit("Ouvrier introuvable");
}

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
$nomComplet = trim(($ouvrier['prenom'] ?? '') . ' ' . ($ouvrier['nom'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Ouvrier - <?= esc($nomComplet) ?></title>
<style>
body {
  font-family: "Times New Roman", serif;
  font-size: 20px;
  color: #111;
  background: #fff;
  margin: 0;
}

/* === PAGE 1 === */
.page {
  width: 210mm;
  min-height: 297mm;
  padding: 25mm;
  margin: auto;
  background: #fff;
  border: 2px solid #064e2e;
  border-radius: 8px;
  box-sizing: border-box;
  page-break-after: always;
}

header {
  text-align: center;
  border-bottom: 4px solid #064e2e;
  padding-bottom: 15px;
  margin-bottom: 30px;
}
header h1 {
  font-size: 40px;
  margin: 0;
  color: #064e2e;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
}

.photo {
  text-align: center;
  margin-bottom: 40px;
}
.photo img {
  max-width: 200px;
  max-height: 200px;
  border: 3px solid #064e2e;
  border-radius: 8px;
}

.details {
  width: 95%;
  border-collapse: collapse;
  font-size: 22px;
  margin: 0 auto;
}
.details th, .details td {
  border: 3px solid #064e2e;
  padding: 25px 20px;
}
.details th {
  background: #e6f0eb;
  color: #064e2e;
  width: 30%;
  text-transform: uppercase;
  font-weight: bold;
  letter-spacing: 1px;
}
.details td {
  width: 70%;
  font-weight: 600;
  font-size: 28px;
  background: #fafafa;
}

/* === PAGE 2 : CIN agrandie === */
.page-cin {
  width: 210mm;
  height: 297mm;
  margin: 0;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  page-break-before: always;
  box-sizing: border-box;
  padding: 10mm;
}
.page-cin h2 {
  text-align: center;
  font-size: 36px;
  color: #064e2e;
  margin-bottom: 15px;
  text-transform: uppercase;
  border-bottom: 3px solid #064e2e;
  padding-bottom: 8px;
  width: 100%;
}
.page-cin .cin-wrap {
  flex: 1;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.page-cin img {
  width: 80%;
  height: 80%;
  object-fit: contain;
  border: 4px solid #064e2e;
  border-radius: 8px;
}

@media print {
  @page { size: A4 portrait; margin: 0; } /* 0 = pleine page */
  body { margin: 0; }
  .page { border: none; padding: 15mm; }
  .page-cin { padding: 0; border: none; }
  .page-cin img { border: none; border-radius: 0; }
}
</style>
</head>
<body>

<!-- === PAGE 1 : Fiche Ouvrier === -->
<div class="page">
  <header>
    <h1>FICHE OUVRIER</h1>
  </header>

  <div class="photo">
    <?php if (!empty($ouvrier['image'])): ?>
      <img src="/gestionMeknassa/uploads/photo-ouvrier/<?= esc($ouvrier['image']) ?>" alt="Photo ouvrier">
    <?php else: ?>
      <p class="text-muted">Aucune photo disponible</p>
    <?php endif; ?>
  </div>

  <table class="details">
    <tr><th>Nom complet</th><td><?= esc($nomComplet) ?></td></tr>
    <tr><th>CIN</th><td><?= esc($ouvrier['cin']) ?></td></tr>
    <tr><th>Fonction</th><td><?= esc($ouvrier['fonction']) ?></td></tr>
    <tr><th>Téléphone</th><td><?= esc($ouvrier['tele']) ?></td></tr>
  </table>
</div>

<!-- === PAGE 2 : Carte Nationale agrandie === -->
<?php if (!empty($ouvrier['img_cin'])): ?>
  <div class="page-cin">
    <h2>Carte Nationale d’Identité</h2>
    <div class="cin-wrap">
      <img src="/gestionMeknassa/uploads/scanne-cin/<?= esc($ouvrier['img_cin']) ?>" alt="Carte Nationale d'Identité">
    </div>
  </div>
<?php endif; ?>

<script>
window.addEventListener('load', () => setTimeout(() => window.print(), 400));
</script>
</body>
</html>
