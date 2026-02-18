<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = max(1, intval($_GET['perPage'] ?? 25));
$q = trim($_GET['q'] ?? '');
$offset = ($page - 1) * $perPage;

// --- Clause WHERE dynamique ---
$where = '';
$params = [];
$types = '';

if ($q !== '') {
    $where = "WHERE nom LIKE CONCAT('%', ?, '%') 
              OR prenom LIKE CONCAT('%', ?, '%') 
              OR telephone LIKE CONCAT('%', ?, '%') 
              OR fournisseur LIKE CONCAT('%', ?, '%')";
    $params = [$q, $q, $q, $q];
    $types = 'ssss';
}

// --- Compter le total ---
$total = 0;
$countSql = "SELECT COUNT(*) FROM clientolive $where";
if ($stmt = $connect->prepare($countSql)) {
    if ($q !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
}

// --- Requête paginée ---
$sql = "SELECT 
            id_client_olive,
            nom,
            prenom,
            telephone,
            fournisseur,
            note,
            created_at
        FROM clientolive
        $where
        ORDER BY created_at DESC
        LIMIT ?, ?";

$stmt = $connect->prepare($sql);

if ($q !== '') {
    $params[] = $offset;
    $params[] = $perPage;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}

$stmt->execute();
$result = $stmt->get_result();

// --- Construction du JSON ---
$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = [
        'id_client_olive' => $r['id_client_olive'],
        'nom'             => $r['nom'],
        'prenom'          => $r['prenom'],
        'telephone'       => $r['telephone'],
        'fournisseur'     => $r['fournisseur'],
        'note'            => $r['note'],
        'created_at'      => $r['created_at']
    ];
}

echo json_encode(['total' => $total, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
