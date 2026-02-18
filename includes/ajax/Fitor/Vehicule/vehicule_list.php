<?php
// ../includes/ajax/Fitor/Vehicule/vehicule_list.php
require_once __DIR__ . '/../../../../config/config.ini.php'; // $connect (mysqli)
header('Content-Type: application/json; charset=utf-8');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = max(1, min(200, (int) ($_GET['perPage'] ?? 25)));
$q = trim((string) ($_GET['q'] ?? ''));

// Filtre WHERE
$where = "1=1";
$params = [];
$types = '';

if ($q !== '') {
  $where .= " AND (matricule LIKE ? OR nom_vehicule LIKE ? OR type LIKE ? OR couleur LIKE ?)";
  $like = "%$q%";
  $params = [$like, $like, $like, $like];
  $types = 'ssss';
}

// Total
$sqlCount = "SELECT COUNT(*) AS cnt FROM vehicule WHERE $where";
$stmt = $connect->prepare($sqlCount);
if (!$stmt) {
  echo json_encode(['rows' => [], 'total' => 0]);
  exit;
}
if ($types)
  $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$total = (int) ($res->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$offset = ($page - 1) * $perPage;

// Rows
$sql = "SELECT
          id_vehicule,
          matricule,
          nom_vehicule,
          type,
          couleur,
          assurance,
          DATE_FORMAT(date_expiration, '%Y-%m-%d') AS date_expiration,
          image_vehicule,
          DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at
        FROM vehicule
        WHERE $where
        ORDER BY id_vehicule DESC
        LIMIT ? OFFSET ?";

$stmt = $connect->prepare($sql);
if (!$stmt) {
  echo json_encode(['rows' => [], 'total' => 0]);
  exit;
}

// bind dynamiques (+ limit/offset)
if ($types) {
  $typesAll = $types . 'ii';
  // bind dynamiques (+ limit/offset)
  if ($types) {
    $typesAll = $types . 'ii';
    // Fusionner les paramètres de filtre avec limit/offset
    $vals = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($typesAll, ...$vals);
  } else {
    $stmt->bind_param('ii', $perPage, $offset);
  }

} else {
  $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
  $rows[] = $r;
}
$stmt->close();

echo json_encode(['rows' => $rows, 'total' => $total], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
