<?php
// 🔧 Mise à jour Bon d’Huile
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function ($sev, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $sev, $file, $line);
});

require_once '../../../../config/config.ini.php';

try {
    // === Lecture des entrées ===
    $id_bon        = (int)($_POST['id_bon'] ?? 0);
    $quantite      = (float)($_POST['quantite'] ?? 0);
    $prix_unitaire = (float)($_POST['prix_unitaire'] ?? 0);
    $mode          = trim((string)($_POST['mode_paiement_huile'] ?? ''));
    $note          = trim((string)($_POST['note'] ?? ''));

    // === Validation minimale ===
    if ($id_bon <= 0) throw new Exception("Identifiant du bon invalide.");
    if ($quantite <= 0) throw new Exception("Quantité invalide.");
    if ($prix_unitaire <= 0) throw new Exception("Prix unitaire invalide.");

    // === Calcul du prix net ===
    $prix_net = round($quantite * $prix_unitaire, 2);

    // === Vérification de l’existence du bon ===
    $stmt = $connect->prepare("SELECT id, justif_paiement FROM bon_de_huile WHERE id = ?");
    $stmt->bind_param('i', $id_bon);
    $stmt->execute();
    $rs = $stmt->get_result();

    if ($rs->num_rows === 0) {
        throw new Exception("Bon d’huile introuvable (ID=$id_bon).");
    }
    $bon = $rs->fetch_assoc();
    $stmt->close();

    // === Gestion de l’upload du justificatif (optionnel) ===
    $file_path_db = $bon['justif_paiement'] ?? null;

    if (!empty($_FILES['justification_paiement']['name'])) {
        $dir = __DIR__ . '/../../../../uploads/huile-justif/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['justification_paiement']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            throw new Exception("Format non autorisé (jpg, png, pdf uniquement).");
        }

        $newName = 'justif_' . $id_bon . '_' . time() . '.' . $ext;
        $target = $dir . $newName;

        if (!move_uploaded_file($_FILES['justification_paiement']['tmp_name'], $target)) {
            throw new Exception("Erreur lors du transfert du fichier.");
        }

        $file_path_db = 'uploads/huile-justif/' . $newName;
    }

    // === Mise à jour SQL ===
    $sql = "
        UPDATE bon_de_huile
        SET 
            quantite = ?,
            prix_unitaire = ?,
            prix_net = ?,
            mode_paiement_huile = ?,
            note = ?,
            justif_paiement = ?
        WHERE id = ?
    ";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('dddsssi', $quantite, $prix_unitaire, $prix_net, $mode, $note, $file_path_db, $id_bon);
    $stmt->execute();
    $stmt->close();

    // === Retour JSON ===
    echo json_encode([
        'ok'         => true,
        'message'    => "Bon d’huile mis à jour avec succès.",
        'id_bon'     => $id_bon,
        'prix_net'   => $prix_net,
        'justif'     => $file_path_db
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => 'Erreur serveur : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
