<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];
 try{
      require_once '../../../../config/config.ini.php'; // adjust if needed


    // Collect fields
        $Fchauffeur     = trim($_POST['List_F_chauffeur']            ?? '');
        $montant_verser     = trim($_POST['montant_verser']            ?? '');
        $paiment_methode  = trim($_POST['paiment_methode']         ?? '');
        $image_versement  = trim($_POST['image_versement']         ?? '');
        $note    = trim($_POST['note']  ?? '');
       
 // Basic server-side validation
    if ($Fchauffeur === '' || $montant_verser === '' || $paiment_methode === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

     // Default if no image
    $imagePathForDb = null;
  


if (isset($_FILES['image_versement']) && $_FILES['image_versement']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['image_versement']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif'];
            $origName = $_FILES['image_versement']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                throw new Exception("Types autorisés : JPG, JPEG, PNG, GIF, WEBP.");
            }

            // Adjust these paths to match your project
            // If /uploads is at project root, use ../../ from includes/ajax/
            $targetDirFs  =__DIR__ . '/../../../../uploads/image/Versement_Fitor/';
            $targetDirRel = 'uploads/image/Versement_Fitor/';

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

            if (!move_uploaded_file($_FILES['image_versement']['tmp_name'], $targetFs)) {
                throw new Exception("Erreur lors du déplacement du fichier uploadé.");
            }

            $imagePathForDb = $targetDirRel . $newName;
        } else {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_versement']['error'] . ").");
        }
}

 // Insert using prepared statement
    $sql = "INSERT INTO versement (montant, mode_paiement, note, id_Fchauffeur, image_versement)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Préparation SQL a échoué : " . $connect->error);
    }

    // bind_param types: s = string; phone likely string
    $stmt->bind_param(
        "sssss",
        $montant_verser,
        $paiment_methode,
        $note,
        $Fchauffeur,
        $imagePathForDb,
    );

    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $response['status']  = "success";
    $response['message'] = "Versement ajouté avec succès.";
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