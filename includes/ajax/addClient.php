<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];
 try{
      require_once __DIR__ . '/../../config/config.ini.php'; // adjust if needed


    // Collect fields
        $nom_client     = trim($_POST['nom']            ?? '');
        $prenom_client  = trim($_POST['prenom']         ?? '');
        $tel_client     = trim($_POST['num_telephone']  ?? '');
        $ville_client   = trim($_POST['ville']          ?? '');
        $address_client = trim($_POST['address_client'] ?? '');
 // Basic server-side validation
    if ($nom_client === '' || $prenom_client === '' || $tel_client === '' || $ville_client === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

     // Default if no image
    $imagePathForDb = null;



if (isset($_FILES['image_client']) && $_FILES['image_client']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['image_client']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif'];
            $origName = $_FILES['image_client']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                throw new Exception("Types autorisés : JPG, JPEG, PNG, GIF, WEBP.");
            }

            // Adjust these paths to match your project
            // If /uploads is at project root, use ../../ from includes/ajax/
            $targetDirFs  = __DIR__ . '/../../uploads/image/Client/';
            $targetDirRel = 'uploads/image/Client/';

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

            if (!move_uploaded_file($_FILES['image_client']['tmp_name'], $targetFs)) {
                throw new Exception("Erreur lors du déplacement du fichier uploadé.");
            }

            $imagePathForDb = $targetDirRel . $newName;
        } else {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_client']['error'] . ").");
        }
}

 // Insert using prepared statement
    $sql = "INSERT INTO client (nom, prenom, phone, address, ville, image_client)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Préparation SQL a échoué : " . $connect->error);
    }

    // bind_param types: s = string; phone likely string
    $stmt->bind_param(
        "ssssss",
        $nom_client,
        $prenom_client,
        $tel_client,
        $address_client,
        $ville_client,
        $imagePathForDb
    );

    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $response['status']  = "success";
    $response['message'] = "Client ajouté avec succès.";
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