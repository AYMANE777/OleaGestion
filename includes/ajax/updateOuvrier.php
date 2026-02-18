<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $id       = (int)($_POST['id_ouvrier'] ?? 0);
    $prenom   = trim((string)($_POST['prenom'] ?? ''));
    $nom      = trim((string)($_POST['nom'] ?? ''));
    $cin      = trim((string)($_POST['cin'] ?? ''));
    $fonction = trim((string)($_POST['fonction'] ?? ''));
    $tele     = preg_replace('/\D+/', '', (string)($_POST['tele'] ?? ''));
    $addr     = trim((string)($_POST['adress'] ?? ''));



$stmt = $connect->prepare("SELECT COUNT(*) as cnt FROM ouvrier WHERE cin=? AND id_ouvrier<>?");
$stmt->bind_param("si", $cin, $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($res['cnt'] > 0) throw new Exception("Ce CIN existe déjà.");


$stmt = $connect->prepare("SELECT COUNT(*) as cnt FROM ouvrier WHERE tele=? AND id_ouvrier<>?");
$stmt->bind_param("si", $tele, $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($res['cnt'] > 0) throw new Exception("Ce numéro de téléphone existe déjà.");
 

    if ($id <= 0) throw new Exception("Identifiant ouvrier invalide.");
    if (!$prenom || !$nom || !$cin || !$fonction || !$tele || !$addr) {
        throw new Exception("Tous les champs sont obligatoires !");
    }
    if (strlen($tele) !== 10) throw new Exception("Le téléphone doit contenir exactement 10 chiffres.");

    $stmt = $connect->prepare("SELECT img_cin, image FROM ouvrier WHERE id_ouvrier = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) throw new Exception("Ouvrier introuvable.");

    $old_img_cin = $row['img_cin'] ?? '';
    $old_photo   = $row['image'] ?? '';

    $allowedExt = ['jpg','jpeg','png'];
    $maxSize = 5 * 1024 * 1024;
    $uploadDirCin   = __DIR__ . '/../../uploads/scanne-cin/';
    $uploadDirPhoto = __DIR__ . '/../../uploads/photo-ouvrier/';

    foreach ([$uploadDirCin, $uploadDirPhoto] as $d) {
        if (!is_dir($d) && !mkdir($d, 0755, true) && !is_dir($d)) {
            throw new Exception("Impossible de créer le dossier d'uploads : $d");
        }
    }

    $uploadOne = function($fileKey, $destDir) use ($allowedExt, $maxSize) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) return null;

        $f = $_FILES[$fileKey];
        if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception("Erreur upload pour $fileKey : code {$f['error']}");
        if ($f['size'] > $maxSize) throw new Exception("Le fichier $fileKey dépasse 5MB.");

        $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) throw new Exception("Type non autorisé pour $fileKey.");

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($f['name'], PATHINFO_FILENAME));
        $final = $safeName . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $final;

        if (!move_uploaded_file($f['tmp_name'], $dest)) throw new Exception("Impossible de déplacer $fileKey.");
        return $final;
    };

    $new_img_cin = $uploadOne('img_cin', $uploadDirCin);
    $new_photo   = $uploadOne('photo', $uploadDirPhoto);

    $sql = "UPDATE ouvrier SET prenom=?, nom=?, cin=?, fonction=?, tele=?, adress=?";
    $params = [$prenom, $nom, $cin, $fonction, $tele, $addr];
    $types = 'ssssss';

    if ($new_img_cin) { $sql .= ", img_cin=?"; $params[] = $new_img_cin; $types .= 's'; }
    if ($new_photo)   { $sql .= ", image=?";   $params[] = $new_photo;   $types .= 's'; }

    $sql .= " WHERE id_ouvrier=?";
    $params[] = $id;
    $types .= 'i';

    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new Exception("Erreur préparation SQL: " . $connect->error);

    $bind_names = [];
    $bind_names[] = $types;
    foreach ($params as $k => $v) {
        $bind_name = 'bind'.$k;
        $$bind_name = $v;
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $stmt->execute();
    $stmt->close();

    if ($new_img_cin && $old_img_cin && is_file($uploadDirCin.$old_img_cin)) unlink($uploadDirCin.$old_img_cin);
    if ($new_photo && $old_photo && is_file($uploadDirPhoto.$old_photo)) unlink($uploadDirPhoto.$old_photo);

    echo json_encode(['status'=>'success','message'=>'Ouvrier mis à jour avec succès.']);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}