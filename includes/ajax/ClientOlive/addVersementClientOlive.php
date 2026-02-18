<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

$response = ['status' => 'error', 'message' => 'Erreur inconnue.'];

try {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');

  $id_client = (int)($_POST['id_client_olive'] ?? 0);
  $montant   = (float)($_POST['montant'] ?? 0);
  $mode      = trim($_POST['mode_paiement'] ?? '');
  $note      = trim($_POST['note'] ?? '');

  if ($id_client <= 0 || $montant < 0 || $mode === '') {
    throw new Exception("Champs obligatoires manquants.");
  }

  $uploadPath = '../../../uploads/versement-client/';
  if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
  $imagePath = null;

  if (!empty($_FILES['image_versement']['name'])) {
    $ext = strtolower(pathinfo($_FILES['image_versement']['name'], PATHINFO_EXTENSION));
    $fileName = uniqid('versement_', true) . '.' . $ext;
    $target = $uploadPath . $fileName;

    if (move_uploaded_file($_FILES['image_versement']['tmp_name'], $target)) {
      $imagePath = 'uploads/versement-client/' . $fileName;
    } else {
      throw new Exception("Échec de l'upload du fichier.");
    }
  }

  $stmt = $connect->prepare("
    INSERT INTO versement_olive (id_client_olive, montant, mode_paiement, image_versement, note, date_versement)
    VALUES (?, ?, ?, ?, ?, NOW())
  ");
  $stmt->bind_param("idsss", $id_client, $montant, $mode, $imagePath, $note);
  $stmt->execute();

  $response = [
    'status'  => 'success',
    'message' => 'Versement ajouté avec succès.',
    'id'      => $connect->insert_id
  ];

} catch (Exception $e) {
  $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
