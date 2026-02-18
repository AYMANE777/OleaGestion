<?php
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    require_once __DIR__ . '/../../config/config.ini.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');
    $ref   = trim($_POST['ref_machine']          ?? '');
    $nom   = trim($_POST['nom_machine']          ?? '');
    $descM = trim($_POST['description_machine']  ?? '');
    $four  = (int)($_POST['fournisseur_machine'] ?? 0);
    $stat  = trim($_POST['statut_machine']       ?? '');

    if ($ref === '' || $nom === '' || $descM === '' || $four <= 0 || $stat === '') {
        throw new Exception("Tous les champs sont obligatoires ! ");
    }
    $allowedStatus = ['en_marche','en_panne','en_maintenance'];
    if (!in_array($stat, $allowedStatus, true)) {
        throw new Exception("Statut invalide.");
    }

    $chk = $connect->prepare("SELECT 1 FROM fournisseur WHERE id_fournisseur = ?");
    $chk->bind_param("i", $four);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        throw new Exception("Fournisseur introuvable.");
    }
    $chk->close();

    $files = [];
    if (isset($_FILES['image_machine']) && is_array($_FILES['image_machine']['name'])) {
        $total = count($_FILES['image_machine']['name']);
        if ($total > 5) {
            throw new Exception("Vous pouvez envoyer au maximum 5 images.");
        }
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['image_machine']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $files[] = [
                'name'     => $_FILES['image_machine']['name'][$i],
                'type'     => $_FILES['image_machine']['type'][$i],
                'tmp_name' => $_FILES['image_machine']['tmp_name'][$i],
                'error'    => $_FILES['image_machine']['error'][$i],
                'size'     => $_FILES['image_machine']['size'][$i],
            ];
        }
    }
    if (empty($files)) {
        throw new Exception("Au moins une image est obligatoire.");
    }

    $connect->begin_transaction();

    $sqlMachine = "INSERT INTO machine (
        date_ajout, ref_machine, nom_machine, id_fournisseur, description_machine
    ) VALUES (NOW(), ?, ?, ?, ?)";
    $stmt1 = $connect->prepare($sqlMachine);
    if (!$stmt1) throw new Exception("Erreur de préparation SQL (machine) : " . $connect->error);
    $stmt1->bind_param("ssis", $ref, $nom, $four, $descM);
    $stmt1->execute();
    $idMachine = (int)$connect->insert_id;
    $stmt1->close();
    $uploadDirFs = __DIR__ . '/../../uploads/machines/';
    if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0755, true)) {
        throw new Exception("Impossible de créer le dossier d’upload.");
    }

    $allowedExt = ['jpg','jpeg','png','webp'];
    $mimeMap    = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    $insertedImages = [];
    $sqlImg = "INSERT INTO image_machine (date_ajout, image, id_machine)
               VALUES (NOW(), ?, ?)";
    $stmtImg = $connect->prepare($sqlImg);
    if (!$stmtImg) throw new Exception("Erreur de préparation SQL (image_machine) : " . $connect->error);

    foreach ($files as $f) {
        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur d’upload (code " . (int)$f['error'] . ").");
        }
        if ($f['size'] > 5 * 1024 * 1024) {
            throw new Exception("Une image dépasse 5 Mo.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($f['tmp_name']) ?: '';
        $ext   = $mimeMap[$mime] ?? strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            throw new Exception("Type d’image non valide (JPG, JPEG, PNG, WEBP).");
        }

        $stamp    = date('Ymd-His');
        $rand     = bin2hex(random_bytes(3));
        $newName  = "MACHINE-{$stamp}-{$rand}.{$ext}";
        $targetFs = $uploadDirFs . $newName;

        if (!move_uploaded_file($f['tmp_name'], $targetFs)) {
            throw new Exception("Erreur lors du déplacement d’un fichier uploadé.");
        }

        $stmtImg->bind_param("si", $newName, $idMachine);
        $stmtImg->execute();
        $insertedImages[] = $newName;
    }
    $stmtImg->close();

    $sqlStat = "INSERT INTO statut_machine (date_ajout, statut, description, id_machine)
                VALUES (NOW(), ?, NULL, ?)";
    $stmtS = $connect->prepare($sqlStat);
    if (!$stmtS) throw new Exception("Erreur de préparation SQL (statut_machine) : " . $connect->error);
    $stmtS->bind_param("si", $stat, $idMachine);
    $stmtS->execute();
    $idStatut = (int)$connect->insert_id;
    $stmtS->close();
    $connect->commit();

    echo json_encode([
        'status'       => 'success',
        'message'      => 'Machine ajoutée avec succès.',
        'id_machine'   => $idMachine,
        'id_statut'    => $idStatut,
        'images'       => $insertedImages
    ]);
    exit;

} catch (Exception $e) {
    if (isset($connect) && $connect instanceof mysqli) {
        try { $connect->rollback(); } catch (\Throwable $ignore) {}
    }
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
