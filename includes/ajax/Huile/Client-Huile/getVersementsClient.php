<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
    // Récupération de tous les versements + nom client
    $sql = "
        SELECT v.id_versement,
               CONCAT(c.nom, ' ', c.prenom) AS client,
               v.montant,
               v.mode_paiement,
               v.note,
               v.justificatif,
               v.date_versement,
               v.created_at
        FROM versement_client v
        JOIN client c ON c.id = v.id_client
        ORDER BY v.created_at DESC
    ";

    $res = $connect->query($sql);
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    echo json_encode(["success" => true, "rows" => $rows]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
