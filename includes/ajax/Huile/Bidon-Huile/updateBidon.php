<?php
// ../includes/ajax/Bidon-Huile/updateBidon.php
require_once '../../../../config/config.ini.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // === Inputs
    $id_bidon = isset($_POST['id_bidon']) ? (int)$_POST['id_bidon'] : 0;
    $nom      = trim($_POST['nom_bidon'] ?? '');
    $type     = trim($_POST['type_bidon'] ?? '');
    $prix     = $_POST['prix_bidon'] ?? null;
    

    if ($id_bidon <= 0) throw new Exception("Identifiant invalide.");
    if ($nom === '') throw new Exception("Nom requis.");
    if (!in_array($type, ['5L','10L','20L','25L','30L','50L'], true)) throw new Exception("Type invalide.");
    if (!is_numeric($prix) || $prix < 0) throw new Exception("Prix invalide.");
    

    $prixDec = (float)$prix;
    

    // === Get previous quantity
    $stOld = $connect->prepare("SELECT quantite_bidon FROM bidons WHERE id_bidon = ?");
    $stOld->bind_param('i', $id_bidon);
    $stOld->execute();
    $resOld = $stOld->get_result();
    if ($resOld->num_rows === 0) throw new Exception("Bidon introuvable.");
    $old = $resOld->fetch_assoc();
    $oldQty = (int)$old['quantite_bidon'];
    $stOld->close();

    // === Update bidon
    $stmt = $connect->prepare("
        UPDATE bidons
        SET nom_bidon = ?, type_bidon = ?, prix_bidon = ?
        WHERE id_bidon = ?
    ");
    $stmt->bind_param('ssdi', $nom, $type, $prixDec, $id_bidon);
    $stmt->execute();
    $stmt->close();

    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
