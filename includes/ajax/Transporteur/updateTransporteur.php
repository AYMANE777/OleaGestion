<?php
header('Content-Type: application/json; charset=utf-8');

try {
    // ── CONFIG DB ───────────────────────────────
    require_once '../../../config/config.ini.php';
    if (!isset($connect) || !$connect instanceof mysqli) {
        throw new Exception("Connexion DB invalide.");
    }

    // ── VALIDATION ID ───────────────────────────
    $id = isset($_POST['id_transporteur']) ? (int)$_POST['id_transporteur'] : 0;
    if ($id <= 0) {
        throw new Exception("ID transporteur invalide.");
    }

    // ── RÉCUPÉRATION DES CHAMPS ─────────────────
    $nom        = trim($_POST['nom_transporteur'] ?? '');
    $prenom     = trim($_POST['prenom_transporteur'] ?? '');
    $telephone  = trim($_POST['num_tel_transporteur'] ?? '');
    $adresse    = trim($_POST['adresse_source'] ?? '');

    if ($nom === '' || $prenom === '' || $telephone === '' || $adresse === '') {
        throw new Exception("Tous les champs sont obligatoires.");
    }

    // ── RÉCUPÉRER TRANSPORTEUR ACTUEL ───────────
    $sel = $connect->prepare("SELECT image_trans FROM transporteur WHERE id_transporteur = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $res = $sel->get_result();
    $old = $res->fetch_assoc();
    $sel->close();

    if (!$old) {
        throw new Exception("Transporteur introuvable.");
    }

    $oldImage = $old['image_trans'];
    $imagePathForDb = $oldImage;

    // ── UPLOAD NOUVELLE IMAGE (SI FOURNIE) ──────
    if (isset($_FILES['image_transporteur']) && $_FILES['image_transporteur']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image_transporteur']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $origName = $_FILES['image_transporteur']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                throw new Exception("Types autorisés : JPG, JPEG, PNG, GIF, WEBP.");
            }

            $targetDirFs  = __DIR__ . '/../../../uploads/image/Transporteur/';
            $targetDirRel = 'uploads/image/Transporteur/';
            if (!is_dir($targetDirFs) && !mkdir($targetDirFs, 0755, true)) {
                throw new Exception("Impossible de créer le dossier d'upload.");
            }

            $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $unique = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $newName = $safeBase . '_' . $unique . '.' . $ext;
            $targetFs = $targetDirFs . $newName;

            if (!move_uploaded_file($_FILES['image_transporteur']['tmp_name'], $targetFs)) {
                throw new Exception("Erreur lors du déplacement du fichier uploadé.");
            }

            $imagePathForDb = $targetDirRel . $newName;

            // Supprimer l’ancienne image
            if ($oldImage && file_exists(__DIR__ . '/../../../' . $oldImage)) {
                @unlink(__DIR__ . '/../../../' . $oldImage);
            }
        } else {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_transporteur']['error'] . ").");
        }
    }

    // ── MISE À JOUR DES DONNÉES ─────────────────
    $sql = "UPDATE transporteur 
            SET nom_trans=?, prenom_trans=?, telephone_trans=?, Adresse_Source=?, image_trans=? 
            WHERE id_transporteur=?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sssssi", $nom, $prenom, $telephone, $adresse, $imagePathForDb, $id);

    if (!$stmt->execute()) {
        throw new Exception("Erreur SQL : " . $stmt->error);
    }
    $stmt->close();

    // ── RÉPONSE OK ───────────────────────────────
    echo json_encode([
        'status' => 'success',
        'message' => 'Transporteur mis à jour avec succès.',
        'id' => $id,
        'image' => $imagePathForDb
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
