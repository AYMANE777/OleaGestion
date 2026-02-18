<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../../config/config.ini.php');

$response = ['status' => 'error', 'message' => 'Erreur inconnue.'];

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("ID du bon non spécifié.");
    }

    $id = (int) $_GET['id'];

    $sql = "SELECT 
                id, nom, prenom, cin, ref_bon, qte, provider, telephone, preuve, note, created_at
            FROM forgeterclient 
            WHERE id = $id
            LIMIT 1";

    $query = mysqli_query($connect, $sql);

    if (!$query) {
        throw new Exception("Erreur SQL : " . mysqli_error($connect));
    }

    if (mysqli_num_rows($query) === 0) {
        throw new Exception("Aucun bon trouvé avec cet ID.");
    }

    $data = mysqli_fetch_assoc($query);

    $response = [
        'status' => 'success',
        'data' => $data
    ];
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
