<?php
require_once __DIR__ . '/../config/config.ini.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit("ID invalide"); }

$connect->set_charset('utf8mb4');

$stmt = $connect->prepare("SELECT id_transporteur, nom_trans, prenom_trans, Adresse_Source, qr_path FROM transporteur WHERE id_transporteur=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { http_response_code(404); exit("Transporteur introuvable"); }

$logo = '/gestionMeknassa/assets/img/logo_mek.png';
$qr   = !empty($row['qr_path']) ? '/gestionMeknassa/uploads/transporteur-qr/' . basename($row['qr_path']) : null;

$prenom = ucfirst(strtolower(trim($row['prenom_trans'] ?? '')));
$nom = strtoupper(trim($row['nom_trans'] ?? ''));
$fullName = $prenom . ' ' . $nom;

$address  = $row['Adresse_Source'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Carte Transporteur - <?= htmlspecialchars($fullName) ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

body {
  margin:0;
  padding:0;
  font-family:'Poppins', sans-serif;
  background:#f3f3f3;
  display:flex;
  justify-content:center;
  align-items:flex-start; 
  height:297mm; 
}


.page {
  width:210mm;
  min-height:297mm;
  padding-top:20mm; 
  display:flex;
  justify-content:center;
}

.card {
  width:85mm;
  height:54mm;
  background:#fff;
  display:flex;
  border-radius:8px;
  overflow:hidden;
  box-shadow:0 4px 14px rgba(0,0,0,0.15);
  border:0.4mm solid #ddd;
  box-sizing:border-box;
}

.left {
  width:50%;
  background:#fff;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  gap:6px;
  padding:6px;
  box-sizing:border-box;
}

.qr {
  width:25mm;
  height:25mm;
  object-fit:contain;
  border:1px solid #ccc;
  border-radius:6px;
  box-shadow:0 2px 6px rgba(0,0,0,0.1);
}

.name {
  font-size:11px;
  font-weight:600;
  color:#111;
  margin-top:2px;
}

.address {
  display:flex;
  align-items:flex-start;
  gap:4px;
  color:#444;
  font-size:8.5px;
  text-align:center;
  max-width:90%;
  line-height:1.3;
}
.address svg {
  width:9px; height:9px; fill:#666;
  flex-shrink:0;
}


.right {
  width:50%;
  background:#f6f7f8;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  text-align:center;
  border-left:0.4mm solid #ddd;
  padding:6px;
  box-sizing:border-box;
}
.right img {
  height:85px;
  width:auto;
  object-fit:contain;
  margin-bottom:8px;
}
.right .title {
  font-size:11px;
  font-weight:600;
  color:#111;
  text-transform:uppercase;
  letter-spacing:0.4px;
}
.right .brand {
  font-size:9px;
  color:#555;
  margin-top:2px;
}

@media print {
  @page { size:A4; margin:15mm; } 
  body { background:#fff; margin:0; }
  .card { box-shadow:none; border:0.3mm solid #999; }
}
</style>
</head>
<body>
<div class="page">
  <div class="card">

    <!-- Colonne gauche -->
    <div class="left">
      <?php if ($qr): ?>
        <img src="<?= htmlspecialchars($qr) ?>" alt="QR Code" class="qr">
      <?php else: ?>
        <div class="qr" style="display:flex;align-items:center;justify-content:center;font-size:7px;color:#aaa;">QR</div>
      <?php endif; ?>

      <div class="name"><?= htmlspecialchars($fullName ?: '—') ?></div>
      <div class="address">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 
          13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 
          9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 
          6.5 12 6.5 14.5 7.62 14.5 9 13.38 
          11.5 12 11.5z"/>
        </svg>
        <div><?= nl2br(htmlspecialchars($address ?: 'Adresse non renseignée')) ?></div>
      </div>
    </div>

    <!-- Colonne droite -->
    <div class="right">
      <img src="<?= htmlspecialchars($logo) ?>" alt="Logo Meknassa Olive" onerror="this.style.display='none'">
      <div class="title">Transporteur</div>
    </div>

  </div>
</div>

<script>
window.addEventListener('load',()=>setTimeout(()=>window.print(),200));
</script>
</body>
</html>
