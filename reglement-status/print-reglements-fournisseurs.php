<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

$id_fournisseur = isset($_GET['id_fournisseur']) ? (int)$_GET['id_fournisseur'] : 0;
$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin'] ?? '';

$whereSql = "r.id_reglement > 0";
$params = [];
$types  = "";

if ($id_fournisseur > 0) {
    $whereSql .= " AND r.id_fournisseur = ?";
    $params[] = $id_fournisseur;
    $types   .= "i";
}
if (!empty($date_debut) && !empty($date_fin)) {
    $whereSql .= " AND DATE(r.date_ajout) BETWEEN ? AND ?";
    $params[] = $date_debut;
    $params[] = $date_fin;
    $types .= "ss";
} elseif (!empty($date_debut)) {
    $whereSql .= " AND DATE(r.date_ajout) >= ?";
    $params[] = $date_debut;
    $types .= "s";
} elseif (!empty($date_fin)) {
    $whereSql .= " AND DATE(r.date_ajout) <= ?";
    $params[] = $date_fin;
    $types .= "s";
}

$sql = "
SELECT 
    r.id_reglement,
    r.ref_reglement,
    r.date_ajout,
    r.situation,
    COALESCE(r.prix_total, 0) AS prix_total,
    COALESCE(f.nom_fournisseur, '') AS nom_fournisseur,
    COALESCE(f.prenom_fournisseur, '') AS prenom_fournisseur,
    (SELECT GROUP_CONCAT(DISTINCT img_facture)
       FROM image_reglement
      WHERE id_reglement = r.id_reglement) AS img_facture,
    (SELECT GROUP_CONCAT(DISTINCT img_justification)
       FROM paiement_reglement
      WHERE id_reglement = r.id_reglement
        AND img_justification IS NOT NULL) AS img_justification,
    (SELECT COALESCE(SUM(montant), 0)
       FROM paiement_reglement
      WHERE id_reglement = r.id_reglement) AS total_paye,
    (COALESCE(r.prix_total, 0) -
     (SELECT COALESCE(SUM(montant), 0)
        FROM paiement_reglement
       WHERE id_reglement = r.id_reglement)) AS reste_payer
FROM reglement_fournisseur r
LEFT JOIN fournisseur f ON f.id_fournisseur = r.id_fournisseur
WHERE $whereSql
ORDER BY f.nom_fournisseur ASC, r.date_ajout DESC, r.id_reglement DESC
";

$stmt = $connect->prepare($sql);
if (!empty($types)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) {
    $fid = trim(($r['prenom_fournisseur'] ?? '') . ' ' . ($r['nom_fournisseur'] ?? 'Inconnu'));
    $data[$fid][] = $r;
}
$stmt->close();

$bank = [
    'name'  => 'Meknassa Olive',
    'phone' => '+212 7 01 08 52 61',
    'bic'   => 'Meknassa El Gharbia',
    'logo'  => '../assets/img/logo_meknassa.png',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Rapport Règlements Fournisseurs</title>
<style>
:root {
    --main:#115e59; --success:#16a34a; --danger:#b91c1c; --info:#0284c7;
    --muted:#6b7280; --border:#e5e7eb; --bg:#f9fafb;
}
body { font-family:"Segoe UI",Arial,sans-serif; background:var(--bg); color:#111; padding:25px; margin:0; }
.sheet {
    background:#fff; max-width:1050px; margin:20px auto; padding:35px 40px;
    border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1);
    page-break-after:always;
}
header { display:flex; justify-content:space-between; align-items:center;
    border-bottom:3px solid var(--main); padding-bottom:8px; margin-bottom:20px; }
.brand h1 { margin:0; color:var(--main); font-size:22px; }
.logo img { height:140px; }
.meta { text-align:right; font-size:14px; color:var(--muted); }

h2 { margin-top:0; color:var(--main); }
table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13.5px; }
th, td { padding:6px 8px; border:1px solid var(--border); }
th { background:#ecfdf5; color:#065f46; text-align:left; }
.num { text-align:right; white-space:nowrap; }
.badge { padding:2px 6px; border-radius:4px; font-weight:600; font-size:12px; }
.badge-success { background:#dcfce7; color:#166534; }
.badge-danger { background:#fecaca; color:#991b1b; }
.badge-info { background:#dbeafe; color:#1e3a8a; }

.summary { margin-top:25px; display:flex; justify-content:space-between; text-align:center; }
.summary .box { flex:1; margin:0 5px; border-radius:8px; padding:10px; }
.paye { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
.avance { background:#e0f2fe; color:#1e3a8a; border:1px solid #bae6fd; }
.reste { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
.total { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }

.signatures { margin-top:10px; display:flex; justify-content:space-between; }
.sign { width:45%; text-align:center; font-size:13px; }
.sign-line { border-top:1px solid #000; margin-top:10px; }

#printBtn { display:block; margin:20px auto 0; padding:8px 18px; background:var(--main);
    color:#fff; border:none; border-radius:6px; cursor:pointer; }
#printBtn:hover { background:#0f766e; }

@media print {
    body { background:#fff; padding:0; }
    .sheet { box-shadow:none; border:none; margin:0; padding:20px; page-break-after:always; }
    #printBtn { display:none; }
}
</style>
</head>
<body>
<?php if (empty($data)): ?>
    <div class="sheet"><h2>Aucun règlement trouvé</h2></div>
<?php else: foreach ($data as $fournisseur => $rows): 
    $total_general = $somme_payee = $somme_avance = $somme_reste = 0;
?>
<div class="sheet">
    <header>
        <div class="brand">
            <h1><?= htmlspecialchars($bank['name']) ?></h1>
            <small><?= htmlspecialchars($bank['bic']) ?><br>Tél: <?= htmlspecialchars($bank['phone']) ?></small>
        </div>
        <div class="logo">
            <?php if (file_exists($bank['logo'])): ?>
                <img src="<?= htmlspecialchars($bank['logo']) ?>" alt="Logo Meknassa">
            <?php endif; ?>
        </div>
        <div class="meta">
            <strong>Règlements Fournisseur</strong><br><?= date('d/m/Y H:i') ?>
        </div>
    </header>

    <h2><?= htmlspecialchars($fournisseur) ?></h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Situation</th>
                <th>Total (DH)</th>
                <th>Payé (DH)</th>
                <th>Reste (DH)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): 
            $sit = strtolower(trim($r['situation']));
            $badge = (str_contains($sit, 'avance')) ? 'badge-info' :
                     ((str_contains($sit, 'pay') && !str_contains($sit, 'non')) ? 'badge-success' : 'badge-danger');

            $prix = (float)$r['prix_total'];
            $paye = (float)$r['total_paye'];
            $reste = (float)$r['reste_payer'];
            $total_general += $prix;
            $somme_payee += $paye;
            $somme_reste += $reste;
            if (str_contains($sit, 'avance')) $somme_avance += $prix;
        ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($r['date_ajout'])) ?></td>
                <td><?= htmlspecialchars($r['ref_reglement']) ?></td>
                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($r['situation']) ?></span></td>
                <td class="num"><?= number_format($prix, 2, ',', ' ') ?></td>
                <td class="num"><?= number_format($paye, 2, ',', ' ') ?></td>
                <td class="num"><?= number_format($reste, 2, ',', ' ') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="box total">
            <div><strong>Total Général</strong></div>
            <div><?= number_format($total_general, 2, ',', ' ') ?> DH</div>
        </div>
        <div class="box paye">
            <div><strong>Total Verser</strong></div>
            <div><?= number_format($somme_payee, 2, ',', ' ') ?> DH</div>
        </div>
   
        <div class="box reste">
            <div><strong>Total Reste à Payer</strong></div>
            <div><?= number_format($somme_reste, 2, ',', ' ') ?> DH</div>
        </div>
        
    </div>

    <div class="signatures">
        <div class="sign"><div class="sign-line"></div>Signature du Fournisseur</div>
        <div class="sign"><div class="sign-line"></div>Signature de l’Administration</div>
    </div>
</div>
<?php endforeach; endif; ?>
<button id="printBtn" onclick="window.print()">🖨️ Imprimer le Rapport</button>
</body>
</html>
