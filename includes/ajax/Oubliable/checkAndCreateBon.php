<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../../config/config.ini.php');

$response = ['status' => 'error', 'message' => 'Erreur inconnue.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée.");
    }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("ID du bon non fourni.");
    }

    $id = (int) $_POST['id'];

    // ✅ Check if ForgeterClient exists
    $checkClient = mysqli_query($connect, "SELECT id FROM forgeterclient WHERE id = $id LIMIT 1");
    if (mysqli_num_rows($checkClient) === 0) {
        throw new Exception("Ce bon n'existe pas dans la table ForgeterClient.");
    }

    // ✅ Check if already in ListdesBonOublier
    $checkBon = mysqli_query($connect, "SELECT idbonOublier FROM listdesbonoublier WHERE id_client = $id LIMIT 1");

    if (mysqli_num_rows($checkBon) > 0) {
        $response = ['status' => 'exists', 'message' => 'Ce bon est déjà dans la liste des bons oubliés.'];
    } else {
        // ✅ Create the record in ListdesBonOublier
        $insert = mysqli_query($connect, "INSERT INTO listdesbonoublier (id_client) VALUES ($id)");
        if (!$insert) {
            throw new Exception("Erreur SQL : " . mysqli_error($connect));
        }

        $response = ['status' => 'success', 'message' => 'Bon ajouté à la liste des oublis avec succès.'];
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
