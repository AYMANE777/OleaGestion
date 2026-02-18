<?php
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $type = trim($_POST['type_piece']        ?? '');
    $ref  = trim($_POST['ref_piece']         ?? '');
    $nom  = trim($_POST['nom_piece']         ?? '');
    $desc = trim($_POST['description_piece'] ?? '');
    $qte  = (int)($_POST['quantite_piece']   ?? 0);
    $four = (int)($_POST['fournisseur_piece'] ?? 0); // <-- NOUVEAU

    $allowedTypes = ['piece_mecanique', 'piece_bureautique', 'piece_stockage', 'piece_transport', 'piece_sécurité'];
    if (!in_array($type, $allowedTypes, true)) {
        throw new Exception("Type de pièce invalide.");
    }
    if ($type === '' || $ref === '' || $nom === '' || $qte < 1) {
        throw new Exception("Type, référence, nom et quantité (>=1) sont obligatoires.");
    }
    if ($four <= 0) {
        throw new Exception("Veuillez sélectionner un fournisseur valide.");
    }

    $chk = $connect->prepare("SELECT 1 FROM fournisseur WHERE id_fournisseur = ?");
    $chk->bind_param("i", $four);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        throw new Exception("Fournisseur introuvable.");
    }
    $chk->close();

    $files = [];
    if (isset($_FILES['image_piece']) && is_array($_FILES['image_piece']['name'])) {
        $total = count($_FILES['image_piece']['name']);
        if ($total > 5) throw new Exception("Vous pouvez envoyer au maximum 5 images.");
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['image_piece']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $files[] = [
                'name' => $_FILES['image_piece']['name'][$i],
                'type' => $_FILES['image_piece']['type'][$i],
                'tmp_name' => $_FILES['image_piece']['tmp_name'][$i],
                'error' => $_FILES['image_piece']['error'][$i],
                'size' => $_FILES['image_piece']['size'][$i],
            ];
        }
    }

    $connect->begin_transaction();

    $sqlPiece = "INSERT INTO piece (
                    date_ajout, ref_piece, nom_piece, description_piece, type_piece, id_fournisseur
                 ) VALUES (
                    NOW(), ?, ?, ?, ?, ?
                 )";
    $stmt1 = $connect->prepare($sqlPiece);
    if (!$stmt1) throw new Exception("Erreur de préparation SQL (piece) : " . $connect->error);

    $stmt1->bind_param("ssssi", $ref, $nom, $desc, $type, $four);
    $stmt1->execute();
    $idPiece = (int)$connect->insert_id;
    $stmt1->close();

    $sqlStock = "INSERT INTO stock_piece (date_ajout, quantite_piece, id_piece)
                 VALUES (NOW(), ?, ?)";
    $stmt2 = $connect->prepare($sqlStock);
    if (!$stmt2) throw new Exception("Erreur de préparation SQL (stock_piece) : " . $connect->error);
    $stmt2->bind_param("ii", $qte, $idPiece);
    $stmt2->execute();
    $idStock = (int)$connect->insert_id;
    $stmt2->close();


    $uploadDirFs = __DIR__ . '/../../uploads/pieces/';
    if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0755, true)) {
        throw new Exception("Impossible de créer le dossier d’upload.");
    }
    $allowedExt = ['jpg','jpeg','png','webp'];
    $mimeMap    = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    $insertedImages = [];
    if (!empty($files)) {
        $sqlImg = "INSERT INTO image_piece (date_ajout, image, id_piece) VALUES (NOW(), ?, ?)";
        $stmtImg = $connect->prepare($sqlImg);
        if (!$stmtImg) throw new Exception("Erreur de préparation SQL (image_piece) : " . $connect->error);

        foreach ($files as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception("Erreur d’upload (code " . (int)$f['error'] . ").");
            if ($f['size'] > 5 * 1024 * 1024) throw new Exception("Une image dépasse 5 Mo.");

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($f['tmp_name']) ?: '';
            $ext   = $mimeMap[$mime] ?? strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) throw new Exception("Type d’image non valide (JPG, JPEG, PNG, WEBP).");

            $stamp    = date('Ymd-His');
            $rand     = bin2hex(random_bytes(3));
            $newName  = "PIECE-{$stamp}-{$rand}.{$ext}";
            $targetFs = $uploadDirFs . $newName;
            if (!move_uploaded_file($f['tmp_name'], $targetFs)) throw new Exception("Erreur lors du déplacement d’un fichier uploadé.");

            $stmtImg->bind_param("si", $newName, $idPiece);
            $stmtImg->execute();
            $insertedImages[] = $newName;
        }
        $stmtImg->close();
    }

    $connect->commit();

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Pièce ajoutée avec succès.',
        'id_piece' => $idPiece,
        'id_stock' => $idStock,
        'images'   => $insertedImages
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
