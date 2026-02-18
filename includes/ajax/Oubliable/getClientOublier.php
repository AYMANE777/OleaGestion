<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../../config/config.ini.php');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$offset = ($page - 1) * $limit;

$where = '';
if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($connect, $search);
    $where = "WHERE nom LIKE '%$safeSearch%' 
              OR prenom LIKE '%$safeSearch%' 
              OR cin LIKE '%$safeSearch%' 
              OR ref_bon LIKE '%$safeSearch%' 
              OR provider LIKE '%$safeSearch%'";
}

// ---- Récupération des données ----
$query = "SELECT * FROM forgeterClient $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($connect, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'client' => $row['nom'] . ' ' . $row['prenom'],
        'cin' => $row['cin'],
        'ref_bon' => $row['ref_bon'],
        'qte' => $row['qte'],
        'provider' => $row['provider'],
        'telephone' => $row['telephone'],
        'preuve' => $row['preuve'],
        'note' => $row['note'],
        'created_at' => $row['created_at']
    ];
}

// ---- Total global ----
$totalResult = mysqli_query($connect, "SELECT COUNT(*) as total FROM forgeterClient $where");
$total = mysqli_fetch_assoc($totalResult)['total'] ?? 0;

// ---- Réponse JSON ----
if (empty($data)) {
    echo json_encode([
        'status' => 'empty',
        'message' => 'Aucun résultat trouvé.',
        'data' => [],
        'total' => 0,
        'page' => $page,
        'limit' => $limit
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ], JSON_UNESCAPED_UNICODE);
}
?>
