<?php
require_once __DIR__ . '/../../../../config/config.ini.php'; // définit $connect (mysqli)

function json_response($data, int $status = 200) {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function clean_string(?string $s): ?string {
  if ($s === null) return null;
  $s = trim($s);
  return $s === '' ? null : $s;
}

/**
 * Accepte 'YYYY-MM-DD' ou 'DD/MM/YYYY'. Retourne 'YYYY-MM-DD' ou null si invalide.
 */
function parse_date(?string $s): ?string {
  if ($s === null || trim($s) === '') return null;
  $s = trim($s);

  // Try YYYY-MM-DD
  $d = DateTime::createFromFormat('Y-m-d', $s);
  if ($d && $d->format('Y-m-d') === $s) return $s;

  // Try DD/MM/YYYY
  $d = DateTime::createFromFormat('d/m/Y', $s);
  if ($d) return $d->format('Y-m-d');

  return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$id = isset($_POST['id_vehicule_trans']) ? (int)$_POST['id_vehicule_trans'] : 0;
if ($id <= 0) json_response(['success' => false, 'message' => 'id_vehicule_trans requis'], 400);

// ---- Récupérer la ligne courante (pour supprimer l’ancienne image si remplacée)
$stmt = $connect->prepare("SELECT image_vehicule_trans FROM vehiculetrans WHERE id_vehicule_trans = ?");
if (!$stmt) json_response(['success' => false, 'message' => 'DB prepare failed (select)'], 500);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) json_response(['success' => false, 'message' => 'Véhicule introuvable'], 404);
$current = $res->fetch_assoc();
$stmt->close();

// ---- Inputs (alignés sur le schéma)
$nom_vehicule   = clean_string($_POST['nom_vehi_trans'] ?? null);
$type           = clean_string($_POST['type'] ?? null);
$matricule      = clean_string($_POST['matricule'] ?? null);
$assurance      = clean_string($_POST['assurance'] ?? null); // texte (compagnie / police / etc.)
$date_exp       = parse_date($_POST['date_expiration'] ?? null); // 'YYYY-MM-DD' ou null
$couleur        = clean_string($_POST['couleur'] ?? null);
$id_Fchauffeur  = isset($_POST['id_vehicule_trans']) && $_POST['id_vehicule_trans'] !== '' ? (int)$_POST['id_vehicule_trans'] : null;

// ---- Upload image (optionnel)
$newImagePath = null;

if (isset($_FILES['image_vehicule_trans']) && is_array($_FILES['image_vehicule_trans']) && $_FILES['image_vehicule_trans']['error'] !== UPLOAD_ERR_NO_FILE) {
  $file = $_FILES['image_vehicule_trans'];

  if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'message' => 'Erreur upload image (code '.$file['error'].')'], 400);
  }

  // Limite 5 Mo
  if ($file['size'] > 5 * 1024 * 1024) {
    json_response(['success' => false, 'message' => 'Image trop volumineuse (max 5MB)'], 400);
  }

  // Extensions/MIME autorisés
  $allowedExt = ['jpg','jpeg','png'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) {
    json_response(['success' => false, 'message' => 'Format image non supporté (jpg, jpeg, png)'], 400);
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime  = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);
  $allowedMime = ['image/jpeg','image/png'];
  if (!in_array($mime, $allowedMime, true)) {
    json_response(['success' => false, 'message' => 'MIME type non supporté'], 400);
  }

  // Racine projet = même base que pour config.ini.php (4 niveaux au-dessus)
  $projectRoot = realpath(__DIR__ . '/../../../../');
  if ($projectRoot === false) {
    json_response(['success' => false, 'message' => 'Impossible de déterminer la racine du projet'], 500);
  }

  $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'VehiculeTrans';
  if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
      json_response(['success' => false, 'message' => 'Impossible de créer le dossier uploads/VehiculeTrans'], 500);
    }
  }

  $basename = 'veh_' . $id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $absPath  = $uploadDir . DIRECTORY_SEPARATOR . $basename;

  if (!move_uploaded_file($file['tmp_name'], $absPath)) {
    json_response(['success' => false, 'message' => 'Échec du déplacement du fichier'], 500);
  }

  // Chemin relatif stocké en DB
  $newImagePath = 'uploads/image/VehiculeTrans/' . $basename;
}

// ---- Construction dynamique de l’UPDATE
$fields = [];
$values = [];
$types  = '';

if ($nom_vehicule !== null) { $fields[] = 'nom_vehi_trans = ?';   $values[] = $nom_vehicule; $types .= 's'; }
if ($type         !== null) { $fields[] = 'type = ?';           $values[] = $type;         $types .= 's'; }
if ($matricule    !== null) { $fields[] = 'matricule = ?';      $values[] = $matricule;    $types .= 's'; }
if ($assurance    !== null) { $fields[] = 'assurance = ?';      $values[] = $assurance;    $types .= 's'; }
if ($date_exp     !== null) { $fields[] = 'date_expiration = ?';$values[] = $date_exp;     $types .= 's'; } // DATE sous forme string 'YYYY-MM-DD'
if ($couleur      !== null) { $fields[] = 'couleur = ?';        $values[] = $couleur;      $types .= 's'; }
if ($id_Fchauffeur!== null) { $fields[] = 'id_vehicule_trans = ?';  $values[] = $id_Fchauffeur;$types .= 'i'; }
if ($newImagePath !== null) { $fields[] = 'image_vehicule_trans = ?'; $values[] = $newImagePath; $types .= 's'; }

if (empty($fields)) {
  json_response(['success' => true, 'message' => 'Aucun changement'], 200);
}

$fields[] = 'updated_at = NOW()';

$sql = "UPDATE vehiculetrans SET " . implode(', ', $fields) . " WHERE id_vehicule_trans = ?";
$types .= 'i';
$values[] = $id;

$stmt = $connect->prepare($sql);
if (!$stmt) {
  // rollback nouveau fichier si uploadé
  if ($newImagePath) {
    $projectRoot = realpath(__DIR__ . '/../../../../');
    @unlink($projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $newImagePath));
  }
  json_response(['success' => false, 'message' => 'DB prepare failed (update)'], 500);
}

$stmt->bind_param($types, ...$values);
$ok  = $stmt->execute();
$err = $stmt->errno;
$aff = $stmt->affected_rows;
$stmt->close();

if (!$ok) {
  // unicité matricule
  if ($err === 1062) {
    if ($newImagePath) {
      $projectRoot = realpath(__DIR__ . '/../../../../');
      @unlink($projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $newImagePath));
    }
    json_response(['success' => false, 'message' => 'Matricule déjà utilisé'], 409);
  }

  if ($newImagePath) {
    $projectRoot = realpath(__DIR__ . '/../../../../');
    @unlink($projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $newImagePath));
  }
  json_response(['success' => false, 'message' => 'Échec de la mise à jour'], 500);
}

// Suppression de l’ancienne image uniquement si on a bien remplacé
if ($newImagePath !== null) {
  $old = $current['image_vehicule_trans'] ?? null;
  if ($old && strpos($old, 'uploads/VehiculeTrans/') === 0) {
    $projectRoot = realpath(__DIR__ . '/../../../../');
    $oldAbs = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $old);
    if (is_file($oldAbs)) @unlink($oldAbs);
  }
}

json_response([
  'success' => true,
  'message' => $aff > 0 ? 'Mise à jour effectuée' : 'Aucun changement détecté',
  'image_vehicule' => $newImagePath
], 200);
