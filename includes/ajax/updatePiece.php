<?php
// JSON
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $id    = (int)($_POST['id_piece'] ?? 0);
    $ref   = trim($_POST['ref_piece'] ?? '');
    $nom   = trim($_POST['nom_piece'] ?? '');
    $desc  = trim($_POST['description_piece'] ?? '');
    $type  = trim($_POST['type_piece'] ?? '');
    $four  = (int)($_POST['fournisseur_piece'] ?? 0);

    if ($id <= 0) {
        throw new Exception("Identifiant pièce invalide.");
    }
    if ($ref === '' || $nom === '' || $type === '') {
        throw new Exception("Tous les champs sont obligatoires !");
    }
    $allowedTypes = ['piece_mecanique','piece_bureautique','piece_stockage','piece_transport', 'piece_sécurité'];
    if (!in_array($type, $allowedTypes, true)) {
        throw new Exception("Type de pièce invalide.");
    }
    if ($four <= 0) {
        throw new Exception("Fournisseur invalide.");
    }

    $connect->begin_transaction();

    $sql = "UPDATE piece
            SET ref_piece = ?, nom_piece = ?, description_piece = ?, type_piece = ?, id_fournisseur = ?
            WHERE id_piece = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new Exception("Erreur de préparation SQL : " . $connect->error);
    $stmt->bind_param("ssssii", $ref, $nom, $desc, $type, $four, $id);
    $stmt->execute();
    $stmt->close();

    $addedImage = null;
    if (isset($_FILES['image_piece']) && $_FILES['image_piece']['error'] !== UPLOAD_ERR_NO_FILE) {
        $sqlCnt = "SELECT COUNT(*) AS nb FROM image_piece WHERE id_piece = ?";
        $stmtC = $connect->prepare($sqlCnt);
        $stmtC->bind_param("i", $id);
        $stmtC->execute();
        $resC = $stmtC->get_result()->fetch_assoc();
        $stmtC->close();

        $already = (int)($resC['nb'] ?? 0);
        if ($already >= 5) {
            throw new Exception("Vous avez déjà 5 images pour cette pièce. Supprimez-en avant d’en ajouter.");
        }

        if ($_FILES['image_piece']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_piece']['error'] . ").");
        }
        if ($_FILES['image_piece']['size'] > 5 * 1024 * 1024) {
            throw new Exception("Image trop lourde (max 5 Mo).");
        }

        $allowed = ['jpg','jpeg','png'];
        $mimeMap = ['image/jpeg'=>'jpg','image/png'=>'png'];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['image_piece']['tmp_name']) ?: '';
        $ext   = $mimeMap[$mime] ?? strtolower(pathinfo($_FILES['image_piece']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            throw new Exception("Type d’image non valide (JPG, JPEG, PNG, WEBP).");
        }

        $uploadDirFs = __DIR__ . '/../../uploads/pieces/';
        if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0755, true)) {
            throw new Exception("Impossible de créer le dossier d’upload.");
        }

        $stamp    = date('Ymd-His');
        $rand     = bin2hex(random_bytes(3));
        $newName  = "PIECE-{$stamp}-{$rand}.{$ext}";
        $targetFs = $uploadDirFs . $newName;

        if (!move_uploaded_file($_FILES['image_piece']['tmp_name'], $targetFs)) {
            throw new Exception("Erreur lors du déplacement du fichier uploadé.");
        }

        $sqlImg = "INSERT INTO image_piece (date_ajout, image, id_piece) VALUES (NOW(), ?, ?)";
        $stmtI = $connect->prepare($sqlImg);
        $stmtI->bind_param("si", $newName, $id);
        $stmtI->execute();
        $stmtI->close();

        $addedImage = $newName;
    }

    $connect->commit();

    $response['status']  = 'success';
    $response['message'] = 'Pièce mise à jour avec succès.';
    if ($addedImage) {
        $response['added_image'] = $addedImage; 
    }

    echo json_encode($response);
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
