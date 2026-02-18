<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

$response = ['status' => 'error', 'message' => 'Erreur inconnue.'];

try {
    // === Récupération et validation des champs ===
    $nom         = trim($_POST['nom'] ?? '');
    $prenom      = trim($_POST['prenom'] ?? '');
    $telephone   = trim($_POST['telephone'] ?? '');
    $fournisseur = trim($_POST['fournisseur'] ?? '');
    $note        = trim($_POST['note'] ?? '');

    if ($nom === '' || $prenom === '' || $telephone === '' || $fournisseur === '') {
        throw new Exception("Veuillez remplir tous les champs obligatoires.");
    }

    // === Vérifier si le client existe déjà (même téléphone) ===
    $check = $connect->prepare("SELECT COUNT(*) FROM clientolive WHERE telephone = ?");
    $check->bind_param('s', $telephone);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        throw new Exception("Un client avec ce numéro de téléphone existe déjà.");
    }

    // === Insertion dans la base ===
    $stmt = $connect->prepare("
        INSERT INTO clientolive (nom, prenom, telephone, fournisseur, note)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssss', $nom, $prenom, $telephone, $fournisseur, $note);

    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de l'insertion : " . $stmt->error);
    }

    $response = [
        'status' => 'success',
        'message' => 'Client créditeur ajouté avec succès.'
    ];

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
