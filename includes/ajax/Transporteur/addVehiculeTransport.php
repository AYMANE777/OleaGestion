<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    require_once '../../../config/config.ini.php'; // ajuste si besoin

    // --- Collect fields ---
    $List_Transporteur   = trim($_POST['List_Transporteur'] ?? '');
    $List_type_vehicule  = trim($_POST['List_type_vehicule'] ?? '');
    $nom_vehicule        = trim($_POST['nom_vehicule_transp'] ?? '');
    $matricule           = trim($_POST['matricule_vehicule_transp'] ?? '');
    $type_assurance      = trim($_POST['List_type_assurance'] ?? '');
    $date_exp_assurance  = trim($_POST['date_expiration_trans'] ?? '');
    $couleur_vehicule    = trim($_POST['List_couleur_vehicule'] ?? '');

    // Validation basique
    if ($List_Transporteur === '' || $List_type_vehicule === '' || $nom_vehicule === '' || $matricule === '' || $couleur_vehicule === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

    // Si date vide → NULL
    if ($date_exp_assurance === '') {
        $date_exp_assurance = null;
    }

    // --- Image upload ---
    $imagePathForDb = null;
    if (isset($_FILES['image_vcTrans']) && $_FILES['image_vcTrans']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image_vcTrans']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif'];
            $origName = $_FILES['image_vcTrans']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                throw new Exception("Types autorisés : JPG, JPEG, PNG, GIF, WEBP.");
            }

            $targetDirFs  = '../../../uploads/image/Transporteur/VehiculeTrans/';
            $targetDirRel = 'uploads/image/Transporteur/VehiculeTrans/';
            if (!is_dir($targetDirFs) && !mkdir($targetDirFs, 0755, true)) {
                throw new Exception("Impossible de créer le dossier d'upload.");
            }

            $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $unique = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $newName = $safeBase . '_' . $unique . '.' . $ext;
            $targetFs = $targetDirFs . $newName;

            if (!move_uploaded_file($_FILES['image_vcTrans']['tmp_name'], $targetFs)) {
                throw new Exception("Erreur lors du déplacement du fichier uploadé.");
            }

            $imagePathForDb = $targetDirRel . $newName;
        } else {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_vcTrans']['error'] . ").");
        }
    }

    // --- Insertion ---
    $sql = "INSERT INTO vehiculetrans
            (nom_vehi_trans, type, matricule, image_vehicule_trans, assurance, date_expiration, couleur, id_transporteur)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Préparation SQL a échoué : " . $connect->error);
    }

    // bind_param : s = string, i = integer
    $stmt->bind_param(
        "sssssssi",
        $nom_vehicule,
        $List_type_vehicule,
        $matricule,
        $imagePathForDb,
        $type_assurance,
        $date_exp_assurance,
        $couleur_vehicule,
        $List_Transporteur
    );

    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $response['status']  = "success";
    $response['message'] = "Véhicule ajouté avec succès.";
    $response['id']      = $stmt->insert_id;

    $stmt->close();
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
