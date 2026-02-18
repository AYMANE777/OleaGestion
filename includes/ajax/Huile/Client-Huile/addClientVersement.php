<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
    // ============================
    // 1️⃣ PARAMÈTRES GLOBAUX
    // ============================
    $UPLOAD_DIR = __DIR__ . '/../../../../uploads/justificatif_versement_client_Huile/';
    $ALLOWED_MIMES = ['image/jpeg', 'image/png', 'application/pdf'];
    $MAX_SIZE = 10 * 1024 * 1024; // 10 MB max

    if (!is_dir($UPLOAD_DIR)) {
        mkdir($UPLOAD_DIR, 0775, true);
    }

    // ============================
    // 2️⃣ RÉCEPTION DES DONNÉES
    // ============================
    $id_client     = (int)($_POST['id_client'] ?? 0);
    $montant       = (float)($_POST['montant'] ?? 0);
    $mode_paiement = trim($_POST['mode_paiement'] ?? 'ESPECES');
    $note          = trim($_POST['note'] ?? '');
    $date_versement = date('Y-m-d'); // date du jour (automatique)

    if ($id_client <= 0 || $montant <= 0) {
        throw new Exception("Client ou montant invalide.");
    }

    // ============================
    // 3️⃣ GESTION DU FICHIER (optionnel)
    // ============================
    $justificatif = null;
    if (!empty($_FILES['justificatif']['name'])) {
        $file = $_FILES['justificatif'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur d’upload du justificatif.");
        }

        if ($file['size'] > $MAX_SIZE) {
            throw new Exception("Fichier trop volumineux (max 10MB).");
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $ALLOWED_MIMES)) {
            throw new Exception("Type de fichier non autorisé.");
        }

        // Générer un nom unique
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'justif_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($ext);
        $targetPath = $UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Impossible d’enregistrer le fichier justificatif.");
        }

        $justificatif = 'uploads/justificatif_versement_client_Huile/' . $filename;
    }

    // ============================
    // 4️⃣ INSERTION EN BASE
    // ============================
    $sql = "INSERT INTO versement_client 
            (id_client, montant, mode_paiement, note, justificatif, date_versement)
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("idsss", $id_client, $montant, $mode_paiement, $note, $justificatif);
    $stmt->execute();

    // ============================
    // 5️⃣ RÉPONSE JSON
    // ============================
    echo json_encode([
        "success" => true,
        "message" => "Versement ajouté avec succès.",
        "id" => $stmt->insert_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
