<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

$response = ['status' => 'error', 'message' => 'Erreur inconnue.'];

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // === RÉCUPÉRATION DES CHAMPS ===
    $id_client     = (int)($_POST['id_client_olive'] ?? 0);
    $ref_olive     = (int)($_POST['ref_olive'] ?? 0); // <-- Champ ajouté pour ref_bon_client
    $poid_net      = (float)($_POST['poid_net'] ?? 0);
    $prix_unitaire = (float)($_POST['prix_unitaire'] ?? 0);
    $montant_total = (float)($_POST['montant_total'] ?? 0);
    $fournisseur   = trim($_POST['fournisseur'] ?? '');
    $note          = trim($_POST['note'] ?? '');

    // === VALIDATION DES DONNÉES ===
    if ($id_client <= 0 || $ref_olive <= 0 || $poid_net <= 0 || $prix_unitaire <= 0 || $montant_total <= 0 || $fournisseur === '') {
        throw new Exception("Champs obligatoires manquants ou invalides.");
    }

    // === TRAITEMENT DU FICHIER SCANNÉ ===
    if (!isset($_FILES['scanne_bon']) || $_FILES['scanne_bon']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Le fichier de preuve est obligatoire et doit être valide.");
    }

    $file = $_FILES['scanne_bon'];
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
        throw new Exception("Type de fichier invalide. Formats acceptés : JPG, PNG.");
    }

    // === Dossier de destination ===
    $uploadDir = '../../../uploads/taratonage-Client-Credit/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // === Nom de fichier unique ===
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'taratonage_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Échec de l'enregistrement du fichier.");
    }

    // === INSERTION DANS LA BASE ===
    $stmt = $connect->prepare("
        INSERT INTO taratonage_client_olive_credit 
        (id_client_olive, ref_bon_client, poid_net, prix_unitaire, montant_total, provider, scanne_bon, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iidddsss", $id_client, $ref_olive, $poid_net, $prix_unitaire, $montant_total, $fournisseur, $fileName, $note);
    $stmt->execute();

    // === RÉPONSE ===
    if ($stmt->affected_rows > 0) {
        $response = ['status' => 'success', 'message' => 'Taratonage ajouté avec succès.'];
    } else {
        throw new Exception("Impossible d’ajouter le taratonage.");
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
