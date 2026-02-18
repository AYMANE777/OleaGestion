<?php
// ../includes/ajax/Fitor/Chauffeur/chauffeur_update.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../config/config.ini.php';

function out($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Méthode non autorisée');

$id = (int)($_POST['id_Fchauffeur'] ?? 0);
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$tel = trim($_POST['telephone'] ?? '');

if ($id <= 0) out(false, 'ID invalide');
if ($nom === '' || $prenom === '') out(false, 'Nom et prénom sont obligatoires');

// fetch current image
$sql = "SELECT image_Fchauffeur FROM chauffeur WHERE id_Fchauffeur = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) out(false, 'Chauffeur introuvable');
$row = $res->fetch_assoc();
$stmt->close();

$currentImg = $row['image_Fchauffeur'] ?? null;
$newPath = $currentImg;

$hasFile = isset($_FILES['image_Fchauffeur']) && is_uploaded_file($_FILES['image_Fchauffeur']['tmp_name']);
if ($hasFile) {
  $f = $_FILES['image_Fchauffeur'];
  if ($f['error'] !== UPLOAD_ERR_OK) out(false, "Erreur upload (code {$f['error']})");
  if ((int)$f['size'] > 3 * 1024 * 1024) out(false, "Image trop volumineuse (max 3 Mo).");

  $allowedExts = ['jpg','jpeg','png'];
  $allowedMimes = ['image/jpeg','image/png'];

  $tmp = $f['tmp_name'];
  $name = $f['name'];
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp) ?: '';

  if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMimes, true)) {
    out(false, "Format non supporté. Utilisez JPG ou PNG.");
  }

  // destination
  $dir = __DIR__ . '/../../../../uploads/image/Chauffeur_Fitor';
  if (!is_dir($dir)) @mkdir($dir, 0777, true);

  $safeBase = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-z0-9_\-\.]/i', '_', $name);
  $destDisk = $dir . '/' . $safeBase;

  if (!move_uploaded_file($tmp, $destDisk)) {
    out(false, 'Impossible de déplacer le fichier uploadé.');
  }

  // relative path used by the app (to prefix with ../ on display)
  $newPath = 'uploads/image/Chauffeur_Fitor/' . $safeBase;

  // Optionnel: supprimer l’ancienne image si elle existe et n’est pas une image par défaut
  if ($currentImg && strpos($currentImg, 'uploads/') === 0) {
    $oldDisk = __DIR__ . '/../../../../' . $currentImg;
    if (is_file($oldDisk)) @unlink($oldDisk);
  }
}

// update row
$upd = "UPDATE chauffeur
        SET nom = ?, prenom = ?, telephone = ?, image_Fchauffeur = ?
        WHERE id_Fchauffeur = ?";
$stmt = $connect->prepare($upd);
$stmt->bind_param('ssssi', $nom, $prenom, $tel, $newPath, $id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) out(false, 'Échec mise à jour BDD');
out(true, 'Mise à jour effectuée', ['image' => $newPath]);
