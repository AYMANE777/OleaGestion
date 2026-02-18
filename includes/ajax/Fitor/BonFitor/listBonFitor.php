<?php

require_once __DIR__ . '/../../../../config/config.ini.php'; // defines $connect (mysqli)

function json_response($data, int $status=200){
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$action = $_GET['action'] ?? '';
if ($action === 'list') {
  // Pagination + search
  $page     = max(1, (int)($_GET['page'] ?? 1));
  $perPage  = min(max(1, (int)($_GET['per_page'] ?? 25)), 200);
  $offset   = ($page - 1) * $perPage;
  $q        = trim((string)($_GET['q'] ?? ''));

  $where = '1';
  $params = [];
  $types = '';

  if ($q !== '') {
    // Search across patron/chauffeur/vehicule/matricule/provider
    $where .= " AND (CONCAT(p.nom_patron,' ',p.prenom_patron) LIKE ? 
                OR CONCAT(c.nom,' ',c.prenom) LIKE ?
                OR vh.nom_vehicule LIKE ?
                OR vh.matricule LIKE ?
                OR b.provider LIKE ?)";
    $needle = '%'.$q.'%';
    $params = array_fill(0, 5, $needle);
    $types  = 'sssss';
  }

  // total
  $sqlCount = "
    SELECT COUNT(*) AS cnt
    FROM bon_fitor b
    JOIN taratonage t ON t.id_taratonage = b.id_taratonage
    JOIN vehicule vh  ON vh.id_vehicule = t.id_vehicule_Fitor
    JOIN chauffeur c  ON c.id_Fchauffeur = vh.id_Fchauffeur
    JOIN patron p     ON p.id_patron = c.id_patron
    WHERE $where
  ";
  $stmt = $connect->prepare($sqlCount);
  if ($types) { $stmt->bind_param($types, ...$params); }
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
  $stmt->close();

  // data
  $sql = "
    SELECT 
      b.id_bon AS id,                       -- NOTE: adjust if your PK differs
      IFNULL(DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i'), DATE_FORMAT(t.taratonage_created_at, '%Y-%m-%d %H:%i')) AS created_at,
      b.quantite, b.prix_init, b.prix_net, b.provider,
      t.poid_brut, t.poid_tarat, t.poid_net,
      vh.nom_vehicule, vh.matricule,
      CONCAT(c.nom,' ',c.prenom) AS chauffeur,
      CONCAT(p.nom_patron,' ',p.prenom_patron) AS patron
    FROM bon_fitor b
    JOIN taratonage t ON t.id_taratonage = b.id_taratonage
    JOIN vehicule vh  ON vh.id_vehicule = t.id_vehicule_Fitor
    JOIN chauffeur c  ON c.id_Fchauffeur = vh.id_Fchauffeur
    JOIN patron p     ON p.id_patron = c.id_patron
    WHERE $where
    ORDER BY IFNULL(b.created_at, t.taratonage_created_at) DESC, b.id_bon DESC
    LIMIT ? OFFSET ?
  ";
  $stmt = $connect->prepare($sql);

  if ($types) {
    $types2 = $types . 'ii';
    $params2 = array_merge($params, [ $perPage, $offset ]);
    $stmt->bind_param($types2, ...$params2);
  } else {
    $stmt->bind_param('ii', $perPage, $offset);
  }

  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while ($r = $res->fetch_assoc()) { $rows[] = $r; }
  $stmt->close();

  $from = $total ? ($offset + 1) : 0;
  $to   = min($offset + $perPage, $total);

  json_response(['ok'=>true, 'data'=>$rows, 'total'=>$total, 'from'=>$from, 'to'=>$to]);
}

if ($action === 'detail') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) json_response(['ok'=>false, 'error'=>'ID invalide'], 400);

  $sql = "
    SELECT 
      b.id_bon AS id,                       -- NOTE: adjust if your PK differs
      IFNULL(DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i'), DATE_FORMAT(t.taratonage_created_at, '%Y-%m-%d %H:%i')) AS created_at,
      b.quantite, b.prix_init, b.prix_net, b.provider,
      t.poid_brut, t.poid_tarat, t.poid_net, t.note AS tara_note,
      vh.nom_vehicule, vh.matricule,
      CONCAT(c.nom,' ',c.prenom) AS chauffeur,
      CONCAT(p.nom_patron,' ',p.prenom_patron) AS patron
    FROM bon_fitor b
    JOIN taratonage t ON t.id_taratonage = b.id_taratonage
    JOIN vehicule vh  ON vh.id_vehicule = t.id_vehicule_Fitor
    JOIN chauffeur c  ON c.id_Fchauffeur = vh.id_Fchauffeur
    JOIN patron p     ON p.id_patron = c.id_patron
    WHERE b.id_bon = ?
    LIMIT 1
  ";
  $stmt = $connect->prepare($sql);
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $data = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$data) json_response(['ok'=>false, 'error'=>'Bon introuvable'], 404);
  json_response(['ok'=>true, 'data'=>$data]);
}

json_response(['ok'=>false, 'error'=>'Action inconnue'], 400);


?>