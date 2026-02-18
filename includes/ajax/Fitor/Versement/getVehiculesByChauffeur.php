<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php'; // adapte le chemin si besoin

// (Optionnel) éviter que des warnings polluent la sortie JSON
ini_set('display_errors', '0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$id = filter_input(INPUT_POST, 'id_Fchauffeur', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'id_Fchauffeur manquant ou invalide']);
    exit;
}


// Prépare la requête (évite l’injection et les soucis de format)
$stmt = $connect->prepare("
    SELECT id_vehicule, nom_vehicule, matricule
    FROM vehicule
    WHERE id_Fchauffeur = ?
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur de préparation requête']);
    exit;
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur d’exécution requête']);
    exit;
}

$result = $stmt->get_result();
$vehicules = $result->fetch_all(MYSQLI_ASSOC);

//Renvoie un **tableau JSON pur**
echo json_encode($vehicules);
exit;
