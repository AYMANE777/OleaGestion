<?php
// updateClient.php
require_once '../../../../config/config.ini.php';
header('Content-Type: application/json; charset=utf-8');

try {
  // inputs
  $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $nom    = trim($_POST['nom'] ?? '');
  $prenom = trim($_POST['prenom'] ?? '');
  $phone  = trim($_POST['phone'] ?? '');
  $ville  = trim($_POST['ville'] ?? '');
  $addr   = trim($_POST['address'] ?? '');

  if ($id <= 0) throw new Exception("Identifiant invalide.");
  if ($nom === '') throw new Exception("Nom requis.");
  if ($prenom === '') throw new Exception("Prénom requis.");

  // Image upload optionnel
  $imgPath = null;
  if (!empty($_FILES['image_client']['name'])) {
    // dossier cible (adapte le chemin selon ton projet)
    $uploadDir = __DIR__ . '/../../../../uploads/image/Client/';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }

    $ext = pathinfo($_FILES['image_client']['name'], PATHINFO_EXTENSION);
    $base = 'client_' . $id . '_' . time();
    $destName = $base . '.' . strtolower($ext);
    $destPath = $uploadDir . $destName;

    if (!move_uploaded_file($_FILES['image_client']['tmp_name'], $destPath)) {
      throw new Exception("Échec de l'upload de l'image.");
    }
    // chemin pour affichage web (adapte si nécessaire)
    $imgPath = 'uploads/image/Client/' . $destName;
  }

  if ($imgPath) {
    $sql = "UPDATE client SET nom=?, prenom=?, phone=?, address=?, ville=?, image_client=? WHERE id=?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ssssssi', $nom, $prenom, $phone, $addr, $ville, $imgPath, $id);
  } else {
    $sql = "UPDATE client SET nom=?, prenom=?, phone=?, address=?, ville=? WHERE id=?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sssssi', $nom, $prenom, $phone, $addr, $ville, $id);
  }

  if (!$stmt->execute()) throw new Exception("Erreur SQL: " . $stmt->error);
  $stmt->close();

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
