<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/../config/config.ini.php');

$id_client = isset($_GET['id_client_olive']) ? (int)$_GET['id_client_olive'] : 0;
if ($id_client <= 0) die("Client invalide.");

// Infos client
$q = $connect->prepare("SELECT nom, prenom, telephone, fournisseur, note FROM clientolive WHERE id_client_olive=?");
$q->bind_param("i", $id_client);
$q->execute();
$client = $q->get_result()->fetch_assoc();
if (!$client) die("Client introuvable.");

// Taratonages
$t = $connect->prepare("
  SELECT date_taratonage, poid_net, prix_unitaire, montant_total, note
  FROM taratonage_client_olive_credit
  WHERE id_client_olive=? ORDER BY date_taratonage ASC
");
$t->bind_param("i", $id_client);
$t->execute();
$taratonages = $t->get_result()->fetch_all(MYSQLI_ASSOC);
$total_poid_net = array_sum(array_column($taratonages, 'poid_net'));

// Versements
$v = $connect->prepare("
  SELECT date_versement, montant, mode_paiement, image_versement, note
  FROM versement_olive
  WHERE id_client_olive=? ORDER BY date_versement ASC
");
$v->bind_param("i", $id_client);
$v->execute();
$versements = $v->get_result()->fetch_all(MYSQLI_ASSOC);

// Totaux
$total_credit = array_sum(array_column($taratonages, 'montant_total'));
$total_versement = array_sum(array_column($versements, 'montant'));
$solde = $total_credit - $total_versement;

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
  <meta charset="utf-8" />
  <title>Relevé Client — <?= htmlspecialchars($client['nom'].' '.$client['prenom']) ?></title>
  <style>
    :root {
      --main:#115e59;
      --accent:#16a34a;
      --danger:#b91c1c;
      --muted:#6b7280;
      --border:#e5e7eb;
      --bg:#f9fafb;
    }
    * { box-sizing:border-box; }
    body {
      font-family: "Segoe UI", Roboto, Arial, sans-serif;
      margin: 0;
      background: var(--bg);
      color:#111;
      padding: 30px;
    }
    .sheet {
      background:#fff;
      max-width:1000px;
      margin:auto;
      padding:40px;
      box-shadow:0 4px 20px rgba(0,0,0,0.08);
      border-radius:12px;
    }

    /* ---- Header ---- */
    header {
      display:flex;
      justify-content:space-between;
      align-items:center;
      border-bottom:3px solid var(--main);
      padding-bottom:10px;
      margin-bottom:25px;
    }
    .brand h1 {
      font-size:24px;
      margin:0;
      color:var(--main);
      letter-spacing:0.5px;
    }
    .brand small {
      color:var(--muted);
      font-size:13px;
    }
    .logo img { max-height:90px; width:auto; }
    .meta { text-align:right; font-size:14px; }
    .meta strong { color:var(--main); }

    h2 { color:var(--main); margin-top:0; }
    p { line-height:1.5; }

    /* ---- Tables ---- */
    table {
      width:100%;
      border-collapse:collapse;
      margin-top:15px;
    }
    th, td {
      padding:8px 10px;
      border:1px solid var(--border);
      font-size:14px;
    }
    th {
      background:#ecfdf5;
      color:#065f46;
      text-align:left;
    }
    tr:nth-child(even) td {
      background:#fafafa;
    }
    .num { text-align:right; white-space:nowrap; }

    /* ---- Totals ---- */
    .summary {
      margin-top:30px;
      background:#f0fdf4;
      border:1px solid #bbf7d0;
      border-radius:8px;
      padding:16px;
    }
    .summary div {
      font-size:15px;
      margin-bottom:4px;
    }
    .summary strong { color:#064e3b; }

    /* ---- Signatures ---- */
    .signatures {
      display:flex;
      justify-content:space-between;
      margin-top:50px;
      border-top:2px solid var(--border);
      padding-top:30px;
    }
    .sign-block {
      text-align:center;
      width:45%;
    }
    .sign-line {
      border-top:1px solid #333;
      margin-top:60px;
    }

    /* ---- Print button ---- */
    #printBtn {
      display:block;
      margin:40px auto 0;
      padding:10px 22px;
      border:none;
      border-radius:6px;
      background:var(--main);
      color:#fff;
      font-size:15px;
      cursor:pointer;
      transition:0.2s;
    }
    #printBtn:hover { background:#0f766e; }

    @media print {
      body { background:#fff; padding:0; }
      .sheet { box-shadow:none; margin:0; padding:20px; border:none; border-radius:0; }
      #printBtn { display:none; }
    }
  </style>
</head>
<body>
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
        <strong>Relevé de compte</strong><br>
        <small><?= date('d/m/Y H:i') ?></small>
      </div>
    </header>

    <h2>Client : <?= htmlspecialchars($client['nom'].' '.$client['prenom']) ?></h2>
    <p>
    Téléphone : <strong><?= htmlspecialchars($client['telephone']) ?></strong><br>
      Fournisseur : <strong><?= htmlspecialchars($client['fournisseur']) ?></strong><br>
      Note : <?= htmlspecialchars($client['note'] ?: '—') ?>
    </p>

    <h3>Détails des Taratonages</h3>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Ref</th>
          <th class="num">Poids net (kg)</th>
          <th class="num">Prix unitaire (DH)</th>
          <th class="num">Crédit (DH)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$taratonages): ?>
          <tr><td colspan="5" class="num text-muted">Aucun taratonage enregistré</td></tr>
        <?php else: foreach ($taratonages as $t): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($t['date_taratonage'])) ?></td>
            <td><?= htmlspecialchars($t['note'] ?: 'Taratonage') ?></td>
            <td class="num"><?= number_format($t['poid_net'], 2, ',', ' ') ?></td>
            <td class="num"><?= number_format($t['prix_unitaire'], 2, ',', ' ') ?></td>
            <td class="num"><?= number_format($t['montant_total'], 2, ',', ' ') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <h3 style="margin-top:40px;">Détails des Versements</h3>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Mode de paiement</th>
          <th>Note</th>
          <th class="num">Montant (DH)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$versements): ?>
          <tr><td colspan="4" class="num text-muted">Aucun versement</td></tr>
        <?php else: foreach ($versements as $v): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($v['date_versement'])) ?></td>
            <td><?= htmlspecialchars(ucfirst($v['mode_paiement'])) ?></td>
            <td><?= htmlspecialchars($v['note'] ?: '—') ?></td>
            <td class="num"><?= number_format($v['montant'], 2, ',', ' ') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <div class="summary">
  <div><strong>Total Du Taratonages :</strong> <?= number_format($total_poid_net, 2, ',', ' ') ?> kg</div>
  <div><strong>Total Crédit :</strong> <?= number_format($total_credit, 2, ',', ' ') ?> DH</div>
  <div><strong>Total Versements :</strong> <?= number_format($total_versement, 2, ',', ' ') ?> DH</div>
  <div><strong>Solde Restant :</strong>
    <?php if ($solde > 0): ?>
      <span style="color:var(--danger); font-weight:bold;">-<?= number_format($solde, 2, ',', ' ') ?> DH </span>
    <?php elseif ($solde < 0): ?>
      <span style="color:var(--accent); font-weight:bold;">+<?= number_format(abs($solde), 2, ',', ' ') ?> DH </span>
    <?php else: ?>
      <span style="color:var(--muted); font-weight:bold;">0,00 DH (Équilibré)</span>
    <?php endif; ?>
  </div>
</div>

    <div class="signatures">
      <div class="sign-block">
        <div>Signature du Client</div>
        <div class="sign-line"></div>
      </div>
      <div class="sign-block">
        <div>Signature de l’Administration</div>
        <div class="sign-line"></div>
      </div>
    </div>

    <button id="printBtn" onclick="window.print()">🖨️ Imprimer le Relevé</button>
  </div>
</body>
</html>
