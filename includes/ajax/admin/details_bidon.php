<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // --- Requête principale : totaux + stock restant par bidon
    $query = "
        SELECT 
            b.id_bidon,
            b.nom_bidon,
            b.type_bidon,
            b.quantite_bidon AS stock_initial,
            COALESCE(dh.vendu_huile, 0) AS vendu_huile,
            COALESCE(db.vendu_bidon, 0) AS vendu_bidon,
            (b.quantite_bidon - COALESCE(dh.vendu_huile, 0) - COALESCE(db.vendu_bidon, 0)) AS stock_disponible
        FROM bidons b
        LEFT JOIN (
            SELECT id_bidon, SUM(quantite_bidon) AS vendu_huile
            FROM details_bon_huile
            GROUP BY id_bidon
        ) dh ON dh.id_bidon = b.id_bidon
        LEFT JOIN (
            SELECT id_bidon, SUM(quantite) AS vendu_bidon
            FROM details_bon_bidon
            GROUP BY id_bidon
        ) db ON db.id_bidon = b.id_bidon
        ORDER BY b.nom_bidon
    ";

    $res = $connect->query($query);
    $rows = [];
    $total_stock = 0;
    $total_vendu = 0;
    $total_dispo = 0;

    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'id_bidon' => (int)$r['id_bidon'],
            'nom_bidon' => $r['nom_bidon'],
            'type_bidon' => $r['type_bidon'],
            'stock_initial' => (float)$r['stock_initial'],
            'vendu_huile' => (float)$r['vendu_huile'],
            'vendu_bidon' => (float)$r['vendu_bidon'],
            'stock_disponible' => (float)$r['stock_disponible']
        ];

        $total_stock += $r['stock_initial'];
        $total_vendu += ($r['vendu_huile'] + $r['vendu_bidon']);
        $total_dispo += $r['stock_disponible'];
    }

    echo json_encode([
        'status' => 'success',
        'totaux' => [
            'total_bidons' => $total_stock,
            'total_vendus' => $total_vendu,
            'dispo' => $total_dispo
        ],
        'details' => $rows
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
