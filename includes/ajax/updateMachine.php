<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $id   = (int)($_POST['id_machine'] ?? 0);
    $ref  = trim($_POST['ref_machine'] ?? '');
    $nom  = trim($_POST['nom_machine'] ?? '');
    $desc = trim($_POST['description_machine'] ?? '');
    $four = (int)($_POST['fournisseur_machine'] ?? 0);

    if ($id <= 0) {
        throw new Exception("Identifiant machine invalide.");
    }
    if ($ref === '' || $nom === '' || $desc === '') {
        throw new Exception("Tous les champs sont obligatoires !");
    }
    if ($four <= 0) {
        throw new Exception("Fournisseur invalide.");
    }

    $chk = $connect->prepare("SELECT 1 FROM fournisseur WHERE id_fournisseur = ?");
    $chk->bind_param("i", $four);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        throw new Exception("Fournisseur introuvable.");
    }
    $chk->close();

    $connect->begin_transaction();

    $sql = "UPDATE machine
            SET ref_machine = ?, nom_machine = ?, description_machine = ?, id_fournisseur = ?
            WHERE id_machine = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new Exception("Erreur de préparation SQL : " . $connect->error);
    $stmt->bind_param("sssii", $ref, $nom, $desc, $four, $id);
    $stmt->execute();
    $stmt->close();
    $addedImage = null;
    if (isset($_FILES['image_machine']) && $_FILES['image_machine']['error'] !== UPLOAD_ERR_NO_FILE) {
        $sqlCnt = "SELECT COUNT(*) AS nb FROM image_machine WHERE id_machine = ?";
        $stmtC = $connect->prepare($sqlCnt);
        $stmtC->bind_param("i", $id);
        $stmtC->execute();
        $resC = $stmtC->get_result()->fetch_assoc();
        $stmtC->close();

        $already = (int)($resC['nb'] ?? 0);
        if ($already >= 5) {
            throw new Exception("Vous avez déjà 5 images pour cette machine.");
        }

        if ($_FILES['image_machine']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_machine']['error'] . ").");
        }
        if ($_FILES['image_machine']['size'] > 5 * 1024 * 1024) {
            throw new Exception("Image trop lourde (max 5 Mo).");
        }

        $allowed = ['jpg','jpeg','png','webp'];
        $mimeMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['image_machine']['tmp_name']) ?: '';
        $ext   = $mimeMap[$mime] ?? strtolower(pathinfo($_FILES['image_machine']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new Exception("Type d’image non valide (JPG, JPEG, PNG).");
        }

        $uploadDirFs = __DIR__ . '/../../uploads/machines/';
        if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0755, true)) {
            throw new Exception("Impossible de créer le dossier d’upload.");
        }

        $stamp    = date('Ymd-His');
        $rand     = bin2hex(random_bytes(3));
        $newName  = "MACHINE-{$stamp}-{$rand}.{$ext}";
        $targetFs = $uploadDirFs . $newName;

        if (!move_uploaded_file($_FILES['image_machine']['tmp_name'], $targetFs)) {
            throw new Exception("Erreur lors du déplacement du fichier uploadé.");
        }

        $sqlImg = "INSERT INTO image_machine (date_ajout, image, id_machine) VALUES (NOW(), ?, ?)";
        $stmtI = $connect->prepare($sqlImg);
        $stmtI->bind_param("si", $newName, $id);
        $stmtI->execute();
        $stmtI->close();

        $addedImage = $newName;
    }

    $connect->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Machine mise à jour avec succès.',
        'added_image' => $addedImage
    ]);
    exit;

} catch (Exception $e) {
    if (isset($connect) && $connect instanceof mysqli) {
        try { $connect->rollback(); } catch (\Throwable $ignore) {}
    }
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
