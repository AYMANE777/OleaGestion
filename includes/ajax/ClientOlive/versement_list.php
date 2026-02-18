<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = max(1, intval($_GET['perPage'] ?? 25));
$q = trim($_GET['q'] ?? '');
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
$types = '';

if ($q !== '') {
  $where = "WHERE c.nom LIKE CONCAT('%', ?, '%') 
            OR c.prenom LIKE CONCAT('%', ?, '%') 
            OR c.telephone LIKE CONCAT('%', ?, '%')";
  $params = [$q, $q, $q];
  $types = 'sss';
}

// total
$total = 0;
$countSql = "SELECT COUNT(*) 
             FROM versement_olive v 
             JOIN clientolive c ON c.id_client_olive = v.id_client_olive
             $where";
$stmt = $connect->prepare($countSql);
if ($q !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

// données
$sql = "SELECT v.id_versement, v.montant, v.mode_paiement, v.image_versement, v.note, v.date_versement,
               CONCAT(c.nom, ' ', c.prenom) AS client_nom
        FROM versement_olive v
        JOIN clientolive c ON c.id_client_olive = v.id_client_olive
        $where
        ORDER BY v.date_versement DESC
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

$rows = [];
while ($r = $result->fetch_assoc()) $rows[] = $r;

echo json_encode(['total' => $total, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
