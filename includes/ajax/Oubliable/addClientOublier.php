<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once(__DIR__ . '/../../../config/config.ini.php');

$response = ['status' => 'error', 'message' => 'Erreur inconnue.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée.");
    }



    // --- Just after you sanitize inputs ---
    $refBon = isset($_POST['ref_bon_forg']) ? mysqli_real_escape_string($connect, trim($_POST['ref_bon_forg'])) : '';

    // --- Step: Check if ref_bon already exists ---
    $checkQuery = "SELECT COUNT(*) AS total FROM forgeterClient WHERE ref_bon = ?";
    $checkStmt = mysqli_prepare($connect, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "s", $refBon);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $total);
    mysqli_stmt_fetch($checkStmt);
    mysqli_stmt_close($checkStmt);

    if ($total > 0) {
        throw new Exception("La référence de bon '$refBon' existe déjà dans la base.");
    }

    // Validate fields
    $required_fields = [
        'nom_client_forget',
        'prenom_client_forget',
        'cin_bon_client_forget',
        'qte_bon_client_forget',
        'provider_client_forget',
        'num_tel_client_forget'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Le champ $field est obligatoire.");
        }
    }

    // Sanitize input
    $nom = mysqli_real_escape_string($connect, trim($_POST['nom_client_forget']));
    $prenom = mysqli_real_escape_string($connect, trim($_POST['prenom_client_forget']));
    $cin = mysqli_real_escape_string($connect, trim($_POST['cin_bon_client_forget']));
    // The HTML form incorrectly names ref bon as "nom_patron"
    $refBon = isset($_POST['ref_bon_forg']) ? mysqli_real_escape_string($connect, trim($_POST['ref_bon_forg'])) : '';
    $qte = (float) $_POST['qte_bon_client_forget'];
    $provider = mysqli_real_escape_string($connect, trim($_POST['provider_client_forget']));
    $numTel = mysqli_real_escape_string($connect, trim($_POST['num_tel_client_forget']));
    $note = !empty($_POST['description_piece']) ? mysqli_real_escape_string($connect, trim($_POST['description_piece'])) : null;

    // Handle image upload
    $image_path = null;
    if (!empty($_FILES['image_client_forget']['name'])) {
        $targetDir = __DIR__ . '/../../../uploads/oublier/';
        if (!file_exists($targetDir))
            mkdir($targetDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['image_client_forget']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png'];

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Format d'image non supporté (jpg, jpeg, png uniquement).");
        }

        if (!move_uploaded_file($_FILES['image_client_forget']['tmp_name'], $targetFilePath)) {
            throw new Exception("Erreur lors du téléchargement de l'image.");
        }

        $image_path = 'uploads/oublier/' . $fileName;
    }

    // Insert into database
    $sql = "INSERT INTO forgeterClient 
            (nom, prenom, cin, ref_bon, qte, provider, telephone, preuve, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "ssssdssss",
        $nom,
        $prenom,
        $cin,
        $refBon,
        $qte,
        $provider,
        $numTel,
        $image_path,
        $note
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Erreur SQL : " . mysqli_error($connect));
    }

    $response = [
        'status' => 'success',
        'message' => 'Bon oubliable ajouté avec succès !'
    ];

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
