<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

$id = intval($_GET['id_client_olive'] ?? 0);
$response = ['status' => 'error', 'message' => 'Client non trouvé'];

if ($id <= 0) {
  echo json_encode($response);
  exit;
}

try {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');

  // --- Client ---
  $stmt = $connect->prepare("SELECT * FROM clientolive WHERE id_client_olive = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $client = $stmt->get_result()->fetch_assoc();
  if (!$client) throw new Exception("Client introuvable");

  // --- Préparation base URL pour les fichiers ---
  $serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
  $basePath  = '/gestionMeknassa/uploads/taratonage-Client-Credit/';

  // --- Taratonages ---
  $tar = $connect->prepare("
      SELECT poid_net, prix_unitaire, montant_total, note, scanne_bon,
             DATE_FORMAT(date_taratonage, '%d/%m/%Y %H:%i') AS date_taratonage
      FROM taratonage_client_olive_credit
      WHERE id_client_olive = ?
      ORDER BY date_taratonage DESC
  ");
  $tar->bind_param("i", $id);
  $tar->execute();
  $result = $tar->get_result();

  $taratonages = [];
  while ($t = $result->fetch_assoc()) {
    // ✅ Création du lien complet pour le bon scanné
    $t['scanne_bon_url'] = !empty($t['scanne_bon'])
      ? $serverUrl . $basePath . $t['scanne_bon']
      : null;

    // formatage valeurs numériques
    $t['poid_net'] = number_format((float)$t['poid_net'], 2, '.', ' ');
    $t['prix_unitaire'] = number_format((float)$t['prix_unitaire'], 2, '.', ' ');
    $t['montant_total'] = number_format((float)$t['montant_total'], 2, '.', ' ');

    $taratonages[] = $t;
  }

  // --- Versements ---
  $ver = $connect->prepare("
      SELECT montant, mode_paiement, image_versement, note,
             DATE_FORMAT(date_versement, '%d/%m/%Y %H:%i') AS date_versement
      FROM versement_olive
      WHERE id_client_olive = ?
      ORDER BY date_versement DESC
  ");
  $ver->bind_param("i", $id);
  $ver->execute();
  $versements = $ver->get_result()->fetch_all(MYSQLI_ASSOC);

  // --- Totaux ---
  $totalCredit = 0;
  foreach ($taratonages as $t) $totalCredit += (float)str_replace(' ', '', $t['montant_total']);
  
  $totalVersement = 0;
  foreach ($versements as $v) $totalVersement += (float)$v['montant'];

  $solde = $totalCredit - $totalVersement;

  // --- RÉPONSE JSON ---
  echo json_encode([
    'status' => 'success',
    'client' => $client,
    'taratonages' => $taratonages,
    'versements' => $versements,
    'totaux' => [
      'credit' => $totalCredit,
      'versement' => $totalVersement,
      'solde' => $solde
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
