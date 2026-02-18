<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../config/config.ini.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // Utiliser $_GET au lieu de $_POST
    $q       = trim((string)($_GET['q'] ?? ''));
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_GET['perPage'] ?? 25);
    
    // Valider le nombre d'éléments par page
    $allowedPerPage = [10, 25, 50, 100];
    if (!in_array($perPage, $allowedPerPage)) {
        $perPage = 25;
    }

    $offset = ($page - 1) * $perPage;

    // Construction de la requête avec recherche
    $where = [];
    $params = [];
    $types = '';

    if (!empty($q)) {
        $where[] = '(prenom_fournisseur LIKE ? OR nom_fournisseur LIKE ? OR tele_fournisseur LIKE ? OR adress_fournisseur LIKE ?)';
        $searchTerm = "%{$q}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $types .= 'ssss';
    }

    $whereClause = empty($where) ? '1=1' : implode(' AND ', $where);

    // Requête pour le total
    $sqlCount = "SELECT COUNT(*) AS total FROM fournisseur WHERE $whereClause";
    $stmtCount = $connect->prepare($sqlCount);
    
    if (!$stmtCount) {
        throw new Exception("Erreur préparation count : " . $connect->error);
    }

    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }

    $stmtCount->execute();
    $totalResult = $stmtCount->get_result();
    $total = (int)($totalResult->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    // Requête pour les données
    $sqlData = "SELECT 
                    id_fournisseur, 
                    prenom_fournisseur, 
                    nom_fournisseur, 
                    tele_fournisseur, 
                    adress_fournisseur, 
                    date_ajout
                FROM fournisseur 
                WHERE $whereClause
                ORDER BY date_ajout DESC, id_fournisseur DESC
                LIMIT ? OFFSET ?";

    $stmtData = $connect->prepare($sqlData);
    if (!$stmtData) {
        throw new Exception("Erreur préparation data : " . $connect->error);
    }

    // Ajouter les paramètres de pagination
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    if (!empty($params)) {
        $stmtData->bind_param($types, ...$params);
    }

    $stmtData->execute();
    $result = $stmtData->get_result();

    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $rows[] = [
            'id_fournisseur' => (int)$r['id_fournisseur'],
            'prenom' => (string)($r['prenom_fournisseur'] ?? ''),
            'nom' => (string)($r['nom_fournisseur'] ?? ''),
            'tele' => (string)($r['tele_fournisseur'] ?? ''),
            'adress' => (string)($r['adress_fournisseur'] ?? ''),
            'date_ajout' => $r['date_ajout'],
            'date_fmt' => $r['date_ajout'] ? date('d/m/Y H:i', strtotime($r['date_ajout'])) : '',
        ];
    }
    $stmtData->close();

    // Calculer les informations de pagination
    $totalPages = $perPage > 0 ? max(1, ceil($total / $perPage)) : 1;
    $from = $total > 0 ? ($offset + 1) : 0;
    $to = $total > 0 ? min($offset + $perPage, $total) : 0;

    echo json_encode([
        'ok' => true,
        'rows' => $rows,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'perPage' => $perPage,
        'from' => $from,
        'to' => $to
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'rows' => [],
        'total' => 0,
        'totalPages' => 0
    ]);
}
?>