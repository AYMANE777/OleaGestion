<?php
/**
 * Meknassa Olive - get_statut_piece_machine.php
 * Retourne la liste complète des pièces, leurs statuts, machines associées et images.
 */

header('Content-Type: application/json; charset=utf-8');

try {
  require_once '../../../config/config.ini.php'; 
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');

  // === Paramètres ===
  $q = trim((string)($_POST['q'] ?? ''));
  $page = max(1, (int)($_POST['page'] ?? 1));
  $perRaw = strtolower(trim((string)($_POST['per_page'] ?? '10')));
  $allowed = ['10', '25', '50', '100', 'all'];
  if (!in_array($perRaw, $allowed, true)) $perRaw = '10';

  // === Conditions ===
  $where = ["1=1"];
  $types = '';
  $params = [];

  if ($q !== '') {
    $where[] = "(p.ref_piece LIKE ? OR p.nom_piece LIKE ? OR m.nom_machine LIKE ? OR sp.statut LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
  }

  $whereSql = implode(' AND ', $where);

  // === Requête principale ===
  $sql = "
    SELECT
  m.id_machine,
  m.nom_machine,
  p.id_piece,
  p.ref_piece,
  p.nom_piece,
  p.description_piece,
  p.type_piece,
  p.date_ajout,
  p.id_fournisseur,
  CONCAT(f.prenom_fournisseur, ' ', f.nom_fournisseur) AS fournisseur_nom,

  (
    SELECT COALESCE(SUM(sp.quantite_piece), 0)
    FROM stock_piece sp
    WHERE sp.id_piece = p.id_piece
  ) AS quantite,

  (
    SELECT COALESCE(SUM(st.quantite), 0)
    FROM statut_piece st
    WHERE st.id_piece = p.id_piece AND st.statut = 'en_marche'
  ) AS statut_marche,

  (
    SELECT COALESCE(SUM(st.quantite), 0)
    FROM statut_piece st
    WHERE st.id_piece = p.id_piece AND st.statut = 'en_panne'
  ) AS statut_panne,

  (
    SELECT COALESCE(SUM(st.quantite), 0)
    FROM statut_piece st
    WHERE st.id_piece = p.id_piece AND st.statut = 'en_maintenance'
  ) AS statut_maintenance,

  (
    SELECT ip.image
    FROM image_piece ip
    WHERE ip.id_piece = p.id_piece
    ORDER BY ip.date_ajout ASC, ip.id_image ASC
    LIMIT 1
  ) AS first_image,

  (
    SELECT COUNT(*) FROM image_piece ip2 WHERE ip2.id_piece = p.id_piece
  ) AS nb_images,

  (
    SELECT GROUP_CONCAT(ip3.image ORDER BY ip3.date_ajout ASC, ip3.id_image ASC SEPARATOR '||')
    FROM image_piece ip3
    WHERE ip3.id_piece = p.id_piece
  ) AS images_list,

  sp.statut,
  sp.description,
  sp.quantite AS quantite_statut,
  sp.date_ajout AS date_statut

FROM machine m
LEFT JOIN statut_piece sp ON sp.id_machine = m.id_machine
LEFT JOIN piece p ON p.id_piece = sp.id_piece
LEFT JOIN fournisseur f ON f.id_fournisseur = p.id_fournisseur
WHERE $whereSql  AND sp.statut = 'en_marche'
ORDER BY m.nom_machine ASC, p.date_ajout DESC

  ";

  // === Préparation & exécution ===
  $stmt = $connect->prepare($sql);
  if ($types) {
    $bind = array_merge([$types], $params);
    $refs = [];
    foreach ($bind as $k => $v) { $refs[$k] = &$bind[$k]; }
    call_user_func_array([$stmt, 'bind_param'], $refs);
  }
  $stmt->execute();
  $res = $stmt->get_result();

  // === Formatage des résultats ===
  $rows = [];
  while ($r = $res->fetch_assoc()) {
    $rows[] = [
      'id_piece'        => (int)$r['id_piece'],
      'ref_piece'       => $r['ref_piece'],
      'nom_piece'       => $r['nom_piece'],
      'description'     => $r['description_piece'] ?: $r['description'],
      'type_piece'      => $r['type_piece'],
      'date_fmt'        => $r['date_statut'] ? date('d/m/Y H:i', strtotime($r['date_statut'])) : '',
      'quantite'        => (int)$r['quantite_statut'],
      'statut'          => $r['statut'],
      'nom_machine'     => $r['nom_machine'] ?? '-',
      'fournisseur_nom' => $r['fournisseur_nom'] ?? '-',
      'statut_marche'   => (int)$r['statut_marche'],
      'statut_panne'    => (int)$r['statut_panne'],
      'statut_maintenance' => (int)$r['statut_maintenance'],
      'first_image'     => $r['first_image'] ?? '',
      'nb_images'       => (int)$r['nb_images'],
      'images_list'     => $r['images_list'] ?? '',
    ];
  }

  $stmt->close();

  echo json_encode([
    'ok'   => true,
    'rows' => $rows,
    'total' => count($rows)
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
