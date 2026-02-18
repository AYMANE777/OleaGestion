<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

$response = ['status' => 'error', 'message' => 'Erreur inconnue.'];

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // === PARAMÈTRES ===
    $id_taratonage = (int)($_POST['id_taratonage'] ?? 0);
    $poid_net      = (float)($_POST['poid_net'] ?? 0);
    $prix_unitaire = (float)($_POST['prix_unitaire'] ?? 0);
    $id_client_olive = (int)($_POST['id_client_olive'] ?? 0);
    $montant_total = (float)($_POST['montant_total'] ?? 0);
    $provider      = trim($_POST['fournisseur'] ?? '');
    $note          = trim($_POST['note'] ?? '');

    if ($id_taratonage <= 0 || $poid_net <= 0 || $prix_unitaire <= 0 || $montant_total <= 0 || $provider === '') {
        throw new Exception("Champs manquants ou invalides.");
    }

    // === RÉCUPÉRER ANCIEN FICHIER ===
    $oldFile = null;
    $stmt = $connect->prepare("SELECT scanne_bon FROM taratonage_client_olive_credit WHERE id_taratonage = ?");
    $stmt->bind_param("i", $id_taratonage);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $oldFile = $res['scanne_bon'];

    // === TRAITEMENT DU NOUVEAU FICHIER (si fourni) ===
    $fileName = $oldFile;
    if (isset($_FILES['scanne_bon']) && $_FILES['scanne_bon']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['scanne_bon'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
            throw new Exception("Type de fichier invalide. Formats acceptés : JPG, PNG.");
        }

        $uploadDir = '../../../uploads/taratonage-Client-Credit/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'taratonage_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Échec de l’enregistrement du fichier.");
        }

        // Supprimer l’ancien fichier s’il existe
        if ($oldFile && file_exists($uploadDir . $oldFile)) {
            @unlink($uploadDir . $oldFile);
        }
    }

    // === MISE À JOUR SQL ===
   $stmt = $connect->prepare("
  UPDATE taratonage_client_olive_credit
  SET id_client_olive=?, poid_net=?, prix_unitaire=?, montant_total=?, provider=?, scanne_bon=?, note=?
  WHERE id_taratonage=?
");
$stmt->bind_param("idddsssi", $id_client_olive, $poid_net, $prix_unitaire, $montant_total, $provider, $fileName, $note, $id_taratonage);

    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        $response = ['status' => 'success', 'message' => 'Taratonage mis à jour avec succès.'];
    } else {
        throw new Exception("Aucune mise à jour effectuée.");
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
