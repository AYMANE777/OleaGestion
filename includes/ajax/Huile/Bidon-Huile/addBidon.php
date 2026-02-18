<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    require_once '../../../../config/config.ini.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $nom_bidon  = trim($_POST['nom_bidon'] ?? '');
    $type_bidon = trim($_POST['type_bidon'] ?? '');
    $prix_bidon = (float)($_POST['prix_bidon'] ?? 0);
    $qte_bidon  = (float)($_POST['qte_bidon'] ?? 0);

    if ($nom_bidon === '' || $type_bidon === '' || $prix_bidon <= 0 || $qte_bidon <= 0) {
        throw new Exception("Veuillez remplir correctement tous les champs.");
    }

    $stmt = $connect->prepare("INSERT INTO bidons (nom_bidon, type_bidon, prix_bidon, quantite_bidon) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdd", $nom_bidon, $type_bidon, $prix_bidon, $qte_bidon);
    $stmt->execute();

    echo json_encode([
        'status' => 'success',
        'message' => 'Bidon ajouté avec succès.',
        'id' => $stmt->insert_id
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
