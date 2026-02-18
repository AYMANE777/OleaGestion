<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
  $page = max(1, intval($_POST['page'] ?? 1));
  $perPage = max(1, intval($_POST['perPage'] ?? 25));
  $q = trim($_POST['q'] ?? '');
  $date_debut = trim($_POST['date_debut'] ?? '');
  $date_fin = trim($_POST['date_fin'] ?? '');
  $offset = ($page - 1) * $perPage;

  $where = ["1"];
  $params = [];
  $types = "";

  if ($q !== '') {
    $where[] = "(c.nom LIKE CONCAT('%', ?, '%') OR c.prenom LIKE CONCAT('%', ?, '%') OR c.ville LIKE CONCAT('%', ?, '%'))";
    $params = array_merge($params, [$q, $q, $q]);
    $types .= "sss";
  }

  if (!empty($date_debut) && !empty($date_fin)) {
    $where[] = "DATE(v.date_versement) BETWEEN ? AND ?";
    $params[] = $date_debut;
    $params[] = $date_fin;
    $types .= "ss";
  } elseif (!empty($date_debut)) {
    $where[] = "DATE(v.date_versement) >= ?";
    $params[] = $date_debut;
    $types .= "s";
  } elseif (!empty($date_fin)) {
    $where[] = "DATE(v.date_versement) <= ?";
    $params[] = $date_fin;
    $types .= "s";
  }

  $whereSql = "WHERE " . implode(" AND ", $where);

  // === Total des lignes
  $countSql = "SELECT COUNT(*) FROM versement_olive v 
               JOIN clientolive c ON c.id_client_olive = v.id_client_olive
               $whereSql";
  $stmt = $connect->prepare($countSql);
  if ($types) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $stmt->bind_result($total);
  $stmt->fetch();
  $stmt->close();

  // === Données paginées
  $sql = "SELECT v.id_versement,
                 DATE_FORMAT(v.date_versement, '%d/%m/%Y %H:%i') AS date_versement,
                 v.montant, v.mode_paiement, v.note,
                 CONCAT(c.prenom, ' ', c.nom) AS client_nom
          FROM versement_olive v
          JOIN clientolive c ON c.id_client_olive = v.id_client_olive
          $whereSql
          ORDER BY v.date_versement DESC
          LIMIT ?, ?";
  $stmt = $connect->prepare($sql);

  if ($types) {
    $params[] = $offset;
    $params[] = $perPage;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
  } else {
    $stmt->bind_param("ii", $offset, $perPage);
  }

  $stmt->execute();
  $res = $stmt->get_result();

  $rows = [];
  while ($r = $res->fetch_assoc()) $rows[] = $r;

  echo json_encode(['ok' => true, 'total' => $total, 'rows' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
