<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $id     = (int)($_POST['id_fournisseur'] ?? 0);
    $prenom = trim($_POST['prenom_fournisseur'] ?? '');
    $nom    = trim($_POST['nom_fournisseur'] ?? '');
    $tele   = preg_replace('/\D+/', '', (string)($_POST['tele_fournisseur'] ?? '')); // garder chiffres
    $addr   = trim($_POST['adress_fournisseur'] ?? '');

    if ($id <= 0)                     throw new Exception("Identifiant fournisseur invalide.");
    if ($prenom === '' || $nom === '' || $addr === '') {
        throw new Exception("Prénom, nom et adresse sont obligatoires.");
    }
    if (strlen($tele) !== 10)         throw new Exception("Le téléphone doit contenir exactement 10 chiffres.");

    $chk = $connect->prepare("SELECT 1 FROM fournisseur WHERE id_fournisseur = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        throw new Exception("Fournisseur introuvable.");
    }
    $chk->close();

    $sql = "UPDATE fournisseur
            SET prenom_fournisseur = ?, nom_fournisseur = ?, tele_fournisseur = ?, adress_fournisseur = ?
            WHERE id_fournisseur = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new Exception("Erreur de préparation SQL : " . $connect->error);

    $stmt->bind_param("ssssi", $prenom, $nom, $tele, $addr, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Fournisseur mis à jour avec succès.'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
