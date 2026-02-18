<?php
// patron_list.php
require_once __DIR__ . '/../../../../config/config.ini.php'; // defines $connect (mysqli)
header('Content-Type: application/json; charset=utf-8');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(1, (int)($_GET['perPage'] ?? 25)));
$q       = trim($_GET['q'] ?? '');

$offset = ($page - 1) * $perPage;

// Champs autorisés pour la recherche
$like = "%{$q}%";
$where = '';
$params = [];
$types  = '';

if ($q !== '') {
  $where = "WHERE (p.nom_patron LIKE ? OR p.prenom_patron LIKE ? OR p.telephone_patron LIKE ? OR p.created_at LIKE ?)";
  $params = [$like, $like, $like, $like];
  $types  = 'ssss';
}

// total
$sqlCount = "SELECT COUNT(*) AS cnt FROM patron p $where";
$stmt = $connect->prepare($sqlCount);
if ($where !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total = (int)($res['cnt'] ?? 0);
$stmt->close();

// rows
$sql = "SELECT p.id_patron, p.nom_patron, p.prenom_patron, p.telephone_patron,p.created_at,p.image_Patron
        FROM patron p
        $where
        ORDER BY p.id_patron DESC
        LIMIT ? OFFSET ?";
$stmt = $connect->prepare($sql);

if ($where !== '') {
  // ajouter limit/offset
  $types2 = $types . 'ii';
  $params2 = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$rows = [];
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) { $rows[] = $row; }
$stmt->close();

echo json_encode(['rows' => $rows, 'total' => $total], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
