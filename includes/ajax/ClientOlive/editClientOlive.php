<?php
require_once '../../../config/config.ini.php';
header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id_client_olive'] ?? 0);
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$tel = trim($_POST['telephone'] ?? '');
$fournisseur = trim($_POST['fournisseur'] ?? '');
$note = trim($_POST['note'] ?? '');

if ($id <= 0 || !$nom || !$prenom || !$tel || !$fournisseur) {
  echo json_encode(['status'=>'error','message'=>'Champs invalides']);
  exit;
}

$stmt = $connect->prepare("UPDATE clientolive SET nom=?, prenom=?, telephone=?, fournisseur=?, note=? WHERE id_client_olive=?");
$stmt->bind_param("sssssi", $nom, $prenom, $tel, $fournisseur, $note, $id);
$stmt->execute();

echo json_encode(['status'=>'success']);
?>
