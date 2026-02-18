<?php
// ../includes/ajax/Fitor/Vehicule/vehicule_list.php
require_once __DIR__ . '/../../../../config/config.ini.php'; // $connect (mysqli)
header('Content-Type: application/json; charset=utf-8');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['perPage'] ?? 25);
$perPage = ($perPage >= 1 && $perPage <= 200) ? $perPage : 25;
$qRaw    = trim((string)($_GET['q'] ?? ''));
$offset  = ($page - 1) * $perPage;

// ----- WHERE & params -----
$where  = '1=1';
$params = [];
$types  = '';

if ($qRaw !== '') {
  $like   = "%{$qRaw}%";
  $where .= " AND (
      vt.matricule LIKE ?
      OR vt.nom_vehi_trans LIKE ?
      OR vt.type LIKE ?
      OR vt.couleur LIKE ?
      OR vt.assurance LIKE ?
      OR CONCAT(t.nom_trans, ' ', t.prenom_trans) LIKE ?
    )";
  $params = [$like, $like, $like, $like, $like, $like];
  $types  = 'ssssss';
}

// ----- TOTAL -----
$sqlCount = "
  SELECT COUNT(*) AS cnt
  FROM vehiculetrans vt
  INNER JOIN transporteur t ON t.id_transporteur = vt.id_transporteur
  WHERE $where
";
if (!$stmt = $connect->prepare($sqlCount)) {
  echo json_encode(['rows' => [], 'total' => 0, 'error' => 'prepare count failed']);
  exit;
}
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res   = $stmt->get_result();
$total = (int)($res->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

// ----- ROWS -----
$sqlRows = "
  SELECT
    t.nom_trans,
    t.prenom_trans,
    vt.id_vehicule_trans,
    vt.matricule,
    vt.nom_vehi_trans,
    vt.type,
    vt.couleur,
    vt.assurance,
    DATE_FORMAT(vt.date_expiration, '%Y-%m-%d')     AS date_expiration,
    vt.image_vehicule_trans,
    DATE_FORMAT(vt.created_at,      '%Y-%m-%d %H:%i') AS created_at
  FROM vehiculetrans vt
  INNER JOIN transporteur t ON t.id_transporteur = vt.id_transporteur
  WHERE $where
  ORDER BY vt.id_vehicule_trans DESC
  LIMIT ? OFFSET ?
";

if (!$stmt = $connect->prepare($sqlRows)) {
  echo json_encode(['rows' => [], 'total' => 0, 'error' => 'prepare rows failed']);
  exit;
}

if ($types) {
  $bindTypes = $types . 'ii';
  $bindVals  = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($bindTypes, ...$bindVals);
} else {
  $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$res  = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();

echo json_encode(
  ['rows' => $rows, 'total' => $total],
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
