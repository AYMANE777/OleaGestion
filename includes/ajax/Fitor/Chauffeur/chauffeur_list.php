<?php
// ../includes/ajax/Fitor/Chauffeur/chauffeur_list.php
require_once __DIR__ . '/../../../../config/config.ini.php';
header('Content-Type: application/json; charset=utf-8');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(1, (int)($_GET['perPage'] ?? 25)));
$q       = trim($_GET['q'] ?? '');

$offset = ($page - 1) * $perPage;

$like = "%{$q}%";
$where = '';
$params = [];
$types  = '';

if ($q !== '') {
  $where = "WHERE (c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR c.created_at LIKE ?)";
  $params = [$like, $like, $like, $like];
  $types  = 'ssss';
}

// total
$sqlCount = "SELECT COUNT(*) AS cnt FROM chauffeur c $where";
$stmt = $connect->prepare($sqlCount);
if ($where !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total = (int)($res['cnt'] ?? 0);
$stmt->close();

// rows
$sql = "SELECT c.id_Fchauffeur, c.nom, c.prenom, c.telephone, c.created_at, c.image_Fchauffeur
        FROM chauffeur c
        $where
        ORDER BY c.id_Fchauffeur DESC
        LIMIT ? OFFSET ?";
$stmt = $connect->prepare($sql);

if ($where !== '') {
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
