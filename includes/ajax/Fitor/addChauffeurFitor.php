<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];
 try{
      require_once '../../../config/config.ini.php'; // adjust if needed


    // Collect fields
        $patron     = trim($_POST['List_patron_name']            ?? '');
        $nom_Fchauffeur     = trim($_POST['nom_Fchauffeur']            ?? '');
        $prenom_Fchauffeur  = trim($_POST['prenom_Fchauffeur']         ?? '');
        $tel_Fchauffeur    = trim($_POST['num_Fchauffeur']  ?? '');
 // Basic server-side validation
    if ($nom_Fchauffeur === '' || $prenom_Fchauffeur === '' || $tel_Fchauffeur === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

     // Default if no image
    $imagePathForDb = null;
  


if (isset($_FILES['image_Fchauffeur']) && $_FILES['image_Fchauffeur']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['image_Fchauffeur']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif'];
            $origName = $_FILES['image_Fchauffeur']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                throw new Exception("Types autorisés : JPG, JPEG, PNG, GIF, WEBP.");
            }

            // Adjust these paths to match your project
            // If /uploads is at project root, use ../../ from includes/ajax/
            $targetDirFs  ='../../../uploads/image/Chauffeur_Fitor/';
            $targetDirRel = 'uploads/image/Chauffeur_Fitor/';

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

            if (!move_uploaded_file($_FILES['image_Fchauffeur']['tmp_name'], $targetFs)) {
                throw new Exception("Erreur lors du déplacement du fichier uploadé.");
            }

            $imagePathForDb = $targetDirRel . $newName;
        } else {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_Fchauffeur']['error'] . ").");
        }
}

 // Insert using prepared statement
    $sql = "INSERT INTO chauffeur (nom, prenom, telephone, image_Fchauffeur, id_patron)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Préparation SQL a échoué : " . $connect->error);
    }

    // bind_param types: s = string; phone likely string
    $stmt->bind_param(
        "sssss",
        $nom_Fchauffeur,
        $prenom_Fchauffeur,
        $tel_Fchauffeur,
        $imagePathForDb,
        $patron
    );

    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $response['status']  = "success";
    $response['message'] = "Patron ajouté avec succès.";
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