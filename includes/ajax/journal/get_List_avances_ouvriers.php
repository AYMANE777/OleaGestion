<?php
header('Content-Type: application/json; charset=utf-8');

try {
   require_once __DIR__ . '/../../../config/config.ini.php';
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');

  // Fonction de liaison dynamique
  function stmt_bind_params_array(mysqli_stmt $stmt, string $types, array $params): void {
    $bind = array_merge([$types], $params);
    $refs = [];
    foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
    call_user_func_array([$stmt, 'bind_param'], $refs);
  }

  // === Paramètres reçus ===
  $id_ouvrier = (int)($_POST['id_client'] ?? 0);
  $date_debut = trim((string)($_POST['date_debut'] ?? ''));
  $date_fin   = trim((string)($_POST['date_fin'] ?? ''));
  $q          = trim((string)($_POST['q'] ?? ''));

  $where  = ['1'];
  $types  = '';
  $params = [];

  if ($id_ouvrier > 0) {
    $where[] = 'a.id_ouvrier = ?';
    $params[] = $id_ouvrier;
    $types   .= 'i';
  }

  if ($date_debut !== '' && $date_fin !== '') {
    $where[] = 'DATE(a.date_ajout) BETWEEN ? AND ?';
    $params[] = $date_debut;
    $params[] = $date_fin;
    $types   .= 'ss';
  } elseif ($date_debut !== '') {
    $where[] = 'DATE(a.date_ajout) >= ?';
    $params[] = $date_debut;
    $types   .= 's';
  } elseif ($date_fin !== '') {
    $where[] = 'DATE(a.date_ajout) <= ?';
    $params[] = $date_fin;
    $types   .= 's';
  }

  if ($q !== '') {
    $where[] = '(o.prenom LIKE ? OR o.nom LIKE ? OR o.cin LIKE ? OR a.motif LIKE ? OR CAST(a.montant AS CHAR) LIKE ?)';
    $like = "%{$q}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $types .= 'sssss';
  }

  $whereSql = implode(' AND ', $where);

  // === Requête principale ===
  $sql = "
    SELECT 
      a.id_avance,
      a.date_ajout,
      a.montant,
      a.motif,
      o.id_ouvrier,
      CONCAT(o.prenom, ' ', o.nom) AS ouvrier,
      o.cin
    FROM avance a
    LEFT JOIN ouvrier o ON o.id_ouvrier = a.id_ouvrier
    WHERE $whereSql
    ORDER BY a.date_ajout DESC, a.id_avance DESC
  ";

  $stmt = $connect->prepare($sql);
  if ($types) stmt_bind_params_array($stmt, $types, $params);
  $stmt->execute();
  $res = $stmt->get_result();

  $rows = [];
  $total_paye = 0;
  $total_non_paye = 0;
  $total_general = 0;

  while ($r = $res->fetch_assoc()) {
    $montant = (float)$r['montant'];
    $total_general += $montant;

   
    $rows[] = [
      'id_avance' => (int)$r['id_avance'],
      'date'      => date('d/m/Y', strtotime($r['date_ajout'])),
      'ouvrier'   => $r['ouvrier'],
      'cin'       => $r['cin'],
      'montant'   => number_format($montant, 2),
      'motif'     => $r['motif'],
   
    ];
  }

  echo json_encode([
    'ok' => true,
    'rows' => $rows,
    'somme_totale' => number_format($total_general, 2, '.', ''),
    'somme_payee' => number_format($total_paye, 2, '.', ''),
    'total_avance' => count($rows)
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
