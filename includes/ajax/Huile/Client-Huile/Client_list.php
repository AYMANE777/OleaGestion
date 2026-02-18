<?php
// Client_list.php — API JSON pour la liste paginée + recherche
require_once '../../../../config/config.ini.php';
header('Content-Type: application/json; charset=utf-8');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(1, (int)($_GET['perPage'] ?? 25)));
$q       = trim($_GET['q'] ?? '');
$offset  = ($page - 1) * $perPage;

$where  = '';
$params = [];
$types  = '';

if ($q !== '') {
  $like   = "%{$q}%";
  $where  = "WHERE (c.nom LIKE ?
                OR c.prenom LIKE ?
                OR c.phone LIKE ?
                OR c.address LIKE ?
                OR c.ville LIKE ?
                OR CAST(c.created_at AS CHAR) LIKE ?)";
  $params = [$like, $like, $like, $like, $like, $like];
  $types  = 'ssssss';
}

/** total */
$sqlTotal = "SELECT COUNT(*) AS total FROM client c " . ($where ?: "");
$stmt = $connect->prepare($sqlTotal);
if ($where) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

/** rows */
$sql = "SELECT
          c.id,
          c.nom,
          c.prenom,
          c.image_client,
          c.phone,
          c.address,
          c.ville,
          c.created_at
        FROM client c
        " . ($where ?: "") . "
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $connect->prepare($sql);
if ($where) {
  $types2  = $types . 'ii';
  $params2 = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();

echo json_encode([
  'rows'       => $rows,
  'total'      => $total,
  'page'       => $page,
  'perPage'    => $perPage,
  'totalPages' => max(1, (int)ceil($total / max(1, $perPage))),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
