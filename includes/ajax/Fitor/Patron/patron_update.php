<?php
// patron_update.php
require_once __DIR__ . '/../../../../config/config.ini.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_POST['id_patron'] ?? 0);
$nom = trim($_POST['nom_patron'] ?? '');
$prenom = trim($_POST['prenom_patron'] ?? '');
$tel = trim($_POST['telephone_patron'] ?? '');
$imagePath = null;

if ($id <= 0 || $nom === '' || $prenom === '') {
  echo json_encode(['success'=>false,'message'=>'Champs obligatoires manquants']); exit;
}

// Upload image si fournie
if (!empty($_FILES['image_Patron']['name'])) {
  $dir = __DIR__ . '/../../../../uploads/image/Patron_Fitor/';
  if (!is_dir($dir)) mkdir($dir, 0777, true);
  $filename = 'patron_'.$id.'_'.time().'.'.pathinfo($_FILES['image_Patron']['name'], PATHINFO_EXTENSION);
  $target = $dir.$filename;
  if (move_uploaded_file($_FILES['image_Patron']['tmp_name'], $target)) {
    $imagePath = 'uploads/image/Patron_Fitor/'.$filename;
  }
}

if ($imagePath) {
  $sql = "UPDATE patron SET nom_patron=?, prenom_patron=?, telephone_patron=?, image_Patron=? WHERE id_patron=?";
  $stmt = $connect->prepare($sql);
  $stmt->bind_param("ssssi", $nom, $prenom, $tel, $imagePath, $id);
} else {
  $sql = "UPDATE patron SET nom_patron=?, prenom_patron=?, telephone_patron=? WHERE id_patron=?";
  $stmt = $connect->prepare($sql);
  $stmt->bind_param("sssi", $nom, $prenom, $tel, $id);
}

if ($stmt->execute()) {
  echo json_encode(['success'=>true]);
} else {
  echo json_encode(['success'=>false,'message'=>$stmt->error]);
}

