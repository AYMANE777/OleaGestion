<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];
 try{
      require_once '../../../config/config.ini.php'; // adjust if needed


    // Collect fields
        $Fchauffeur     = trim($_POST['List_F_chauffeur']            ?? '');
        $type_vehicule     = trim($_POST['List_type_vehicule']            ?? '');
        $nom_vehicule  = trim($_POST['nom_vehicule']         ?? '');
        $matricule    = trim($_POST['matricule_vehicule']  ?? '');
        $type_assurance    = trim($_POST['List_type_assurance']  ?? '');
        $date_exp_assurance    = trim($_POST['date_expiration_trans']  ?? null);
        $couleur_vehicule    = trim($_POST['List_couleur_vehicule']  ?? '');
        // Si le champ date est vide, on met la valeur spéciale
    if ($date_exp_assurance === '') {
        $date_exp_assurance = '0000-00-00';
    }
 // Basic server-side validation
    if ($Fchauffeur === '' || $type_vehicule === '' || $nom_vehicule === '' || $matricule === '' || $couleur_vehicule === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

     // Default if no image
    $imagePathForDb = null;
  


if (isset($_FILES['image_vehicule']) && $_FILES['image_vehicule']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['image_vehicule']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif'];
            $origName = $_FILES['image_vehicule']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                throw new Exception("Types autorisés : JPG, JPEG, PNG, GIF, WEBP.");
            }

            // Adjust these paths to match your project
            // If /uploads is at project root, use ../../ from includes/ajax/
            $targetDirFs  ='../../../uploads/image/Vehicule/';
            $targetDirRel = 'uploads/image/Vehicule/';

            if (!is_dir($targetDirFs)) {
                if (!mkdir($targetDirFs, 0755, true)) {
                    throw new Exception("Impossible de créer le dossier d'upload.");
                }
            }

            // Create a safe unique filename
            $base = pathinfo($origName, PATHINFO_FILENAME);
            $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
            $unique = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $newName = $safeBase . '_' . $unique . '.' . $ext;
            $targetFs = $targetDirFs . $newName;

            if (!move_uploaded_file($_FILES['image_vehicule']['tmp_name'], $targetFs)) {
                throw new Exception("Erreur lors du déplacement du fichier uploadé.");
            }

            $imagePathForDb = $targetDirRel . $newName;
        } else {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_vehicule']['error'] . ").");
        }
}

 // Insert using prepared statement
    $sql = "INSERT INTO vehicule (nom_vehicule, type, matricule, image_vehicule, assurance, date_expiration, couleur, id_Fchauffeur)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Préparation SQL a échoué : " . $connect->error);
    }

    // bind_param types: s = string; phone likely string
    $stmt->bind_param(
        "ssssssss",
        $nom_vehicule,
        $type_vehicule,
        $matricule,
        $imagePathForDb,
        $type_assurance,
        $date_exp_assurance,
        $couleur_vehicule,
        $Fchauffeur
    );

    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $response['status']  = "success";
    $response['message'] = "Vehicule ajouté avec succès.";
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
?>