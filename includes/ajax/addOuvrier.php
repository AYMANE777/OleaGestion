<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $prenom   = trim((string)($_POST['prenom']   ?? ''));
    $nom      = trim((string)($_POST['nom']      ?? ''));
    $cin      = trim((string)($_POST['cin']      ?? ''));
    $tel      = preg_replace('/\D+/', '', (string)($_POST['tel'] ?? ''));
    $fonction = trim((string)($_POST['fonction'] ?? ''));
    $adresse  = trim((string)($_POST['adresse']  ?? ''));

    $prenom = $prenom !== '' ? ucfirst(mb_strtolower($prenom)) : '';
    $nom    = $nom !== '' ? mb_strtoupper($nom) : '';
    $cin    = $cin !== '' ? mb_strtoupper($cin) : '';

    if ($prenom === '' || $nom === '' || $cin === '' || $tel === '' || $fonction === '' || $adresse === '') {
        throw new InvalidArgumentException('Tous les champs (y compris le CIN et les images) sont obligatoires.');
    }
    if (!preg_match('/^[0-9]{10}$/', $tel)) {
        throw new InvalidArgumentException('Le numéro de téléphone doit contenir exactement 10 chiffres.');
    }

   
    $sql = "SELECT id_ouvrier, cin, tele FROM ouvrier WHERE cin = ? OR tele = ? LIMIT 1";
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new RuntimeException('Erreur préparation SQL (doublon check): ' . $connect->error);
    $stmt->bind_param('ss', $cin, $tel);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $conflicts = [];
        if (!empty($row['cin']) && strtoupper($row['cin']) === strtoupper($cin)) {
            $conflicts[] = 'CIN';
        }

        $existingTel = preg_replace('/\D+/', '', (string)$row['tele']);
        if ($existingTel !== '' && $existingTel === $tel) {
            $conflicts[] = 'Téléphone';
        }

        if (count($conflicts) > 0) {
            http_response_code(409);
            $msg = count($conflicts) === 1
                 ? ($conflicts[0] === 'CIN' ? 'Ce CIN existe déjà.' : 'Ce numéro de téléphone existe déjà.')
                 : 'Le CIN et le numéro de téléphone existent déjà.';
            echo json_encode(['status' => 'error', 'message' => $msg, 'conflicts' => $conflicts]);
            $stmt->close();
            exit;
        }
    }
    $stmt->close();


    $allowedExt = ['jpg','jpeg','png'];
    $maxSize = 5 * 1024 * 1024; 


    $uploadDirCin   = __DIR__ . '/../../uploads/scanne-cin/';
    $uploadDirPhoto = __DIR__ . '/../../uploads/photo-ouvrier/';

    foreach ([$uploadDirCin, $uploadDirPhoto] as $d) {
        if (!is_dir($d)) {
            if (!mkdir($d, 0755, true) && !is_dir($d)) {
                throw new RuntimeException("Impossible de créer le dossier d'uploads : $d");
            }
        }
    }


    $handleUpload = function(string $fileKey, string $destDir, string $type) use ($allowedExt, $maxSize) : string {
        if (!isset($_FILES[$fileKey]) || !is_array($_FILES[$fileKey])) {
            throw new RuntimeException("Le fichier '$fileKey' est obligatoire.");
        }

        $f = $_FILES[$fileKey];

        if ($f['error'] === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException("Le fichier '$fileKey' est obligatoire.");
        }

        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Erreur upload pour $fileKey : code " . $f['error']);
        }

        if ($f['size'] > $maxSize) {
            throw new RuntimeException("Le fichier $fileKey dépasse la taille maximale autorisée (5MB).");
        }

        $orig = basename($f['name']);
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException("Type de fichier non autorisé pour $fileKey. Extensions permises : jpg, jpeg, png.");
        }

  
        $stamp = date('Ymd-His');
        $rand  = bin2hex(random_bytes(3)); 
        if ($type === 'cin') {
            $newName = "CIN-{$stamp}-{$rand}.{$ext}";
        } else { 
            $newName = "PHOTO-{$stamp}-{$rand}.{$ext}";
        }

        $dest = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;

        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            throw new RuntimeException("Impossible de déplacer le fichier uploadé ($fileKey).");
        }

        return $newName;
    };


    $movedFiles = [];
    try {
        $img_cin_filename = $handleUpload('img_cin', $uploadDirCin, 'cin');
        $movedFiles[] = $uploadDirCin . $img_cin_filename;

        $photo_filename = $handleUpload('photo', $uploadDirPhoto, 'photo');
        $movedFiles[] = $uploadDirPhoto . $photo_filename;
    } catch (RuntimeException $e) {

        foreach ($movedFiles as $mf) { if (is_file($mf)) @unlink($mf); }
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }

    $sqlIns = "INSERT INTO ouvrier (prenom, nom, cin, img_cin, image, tele, fonction, adress, date_ajout)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $connect->prepare($sqlIns);
    if (!$stmt) {
        foreach ($movedFiles as $mf) { if (is_file($mf)) @unlink($mf); }
        throw new RuntimeException('Erreur préparation SQL (insert): ' . $connect->error);
    }

    $img_cin_db = $img_cin_filename;
    $photo_db   = $photo_filename;

    $stmt->bind_param('ssssssss', $prenom, $nom, $cin, $img_cin_db, $photo_db, $tel, $fonction, $adresse);
    $stmt->execute();
    $insertId = $connect->insert_id;
    $stmt->close();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Ouvrier ajouté avec succès.',
        'id'      => $insertId,
        'img_cin' => $img_cin_db,
        'image'   => $photo_db
    ]);
    exit;

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    exit;
}