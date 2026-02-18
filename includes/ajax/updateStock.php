<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $id_piece = (int)($_POST['id_piece'] ?? 0);
    $type     = trim($_POST['mouvement_type'] ?? '');   
    $quantite = (int)($_POST['quantite'] ?? 0);
    $desc  = trim($_POST['description'] ?? '');  

    if ($id_piece <= 0)        throw new Exception("Pièce invalide.");
    if (!in_array($type, ['entree','sortie'], true)) throw new Exception("Type de mouvement invalide.");
    if ($quantite <= 0)        throw new Exception("Quantité doit être >= 1.");
    $desc = trim($_POST['description'] ?? '');
    if ($desc === '') {
    throw new Exception("La description est obligatoire.");
    }

    $sql  = "SELECT COALESCE(SUM(quantite_piece),0) AS q_stock FROM stock_piece WHERE id_piece = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id_piece);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $q_stock = (int)($row['q_stock'] ?? 0);
    $stmt->close();

    $q_marche = $q_panne = $q_maint = 0;
    $sql  = "SELECT statut, COALESCE(SUM(quantite),0) AS q
             FROM statut_piece
             WHERE id_piece = ?
             GROUP BY statut";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id_piece);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        switch ($r['statut']) {
            case 'en_marche':      $q_marche = (int)$r['q']; break;
            case 'en_panne':       $q_panne  = (int)$r['q']; break;
            case 'en_maintenance': $q_maint  = (int)$r['q']; break;
        }
    }
    $stmt->close();


    $q_dispo = $q_stock;

    if ($type === 'sortie') {
        if ($quantite > $q_dispo) {
            throw new Exception(
                "Sortie impossible, le stock disponible est : $q_stock"
            );
        }
    }

    $connect->begin_transaction();

    $delta = ($type === 'entree') ? $quantite : -$quantite;

$sql  = "INSERT INTO stock_piece (date_ajout, quantite_piece, id_piece, description)
         VALUES (NOW(), ?, ?, ?)";
$stmt = $connect->prepare($sql);
$stmt->bind_param("iis", $delta, $id_piece, $desc);
$stmt->execute();
$stmt->close();


    $connect->commit();
    $sql  = "SELECT COALESCE(SUM(quantite_piece),0) AS q_stock FROM stock_piece WHERE id_piece = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id_piece);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $q_stock = (int)($row['q_stock'] ?? 0);
    $stmt->close();

    $q_dispo = max(0, $q_stock - ($q_marche + $q_panne + $q_maint));

    echo json_encode([
        'status'  => 'success',
        'message' => 'l’opération enregistré.',
        'stock'   => $q_stock,
        'dispo'   => $q_dispo,
        'statuts' => [
            'marche' => $q_marche,
            'panne'  => $q_panne,
            'maint'  => $q_maint,
        ],
    ]);
    exit;

} catch (Exception $e) {
    if (isset($connect) && $connect instanceof mysqli) {
        try { $connect->rollback(); } catch (\Throwable $ignore) {}
    }
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}