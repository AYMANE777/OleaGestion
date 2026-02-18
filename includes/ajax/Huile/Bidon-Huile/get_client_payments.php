<?php
// get_List_payments.php — liste des lignes de bidons via entête de bon (par client OU par bon)
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors','0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function($sev,$msg,$file,$line){ throw new ErrorException($msg,0,$sev,$file,$line); });

require_once __DIR__ . '/../../../../config/config.ini.php';
mysqli_set_charset($connect, 'utf8mb4');

function out($arr, int $code=200) {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

try {
  // -------- Inputs --------
  $client_id  = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
  $bon_id     = isset($_GET['bon_id'])    ? (int)$_GET['bon_id']    : 0; // NEW: filter by a specific bon if provided
  $q          = trim((string)($_GET['q'] ?? ''));
  $page       = max(1, (int)($_GET['page'] ?? 1));
  $perPage    = (int)($_GET['perPage'] ?? 25);
  $perPage    = in_array($perPage, [10,25,50,100], true) ? $perPage : 25;
  $offset     = ($page - 1) * $perPage;
  $date_from  = trim((string)($_GET['date_from'] ?? ''));
  $date_to    = trim((string)($_GET['date_to']   ?? ''));

  // Need at least client_id or bon_id
  if ($client_id <= 0 && $bon_id <= 0) {
    out(['ok'=>false,'error'=>'client_id ou bon_id requis'], 400);
  }

  // -------- WHERE builder --------
  $where = [];
  $types = '';
  $args  = [];

  // Join shape matches your sample; we’ll expose dbb.created_at as created_at.
  // Base scoping
  if ($bon_id > 0) {
    $where[] = 'dbb.id_bon = ?';
    $types  .= 'i';
    $args[]  = $bon_id;
  } else {
    $where[] = 'c.id = ?';
    $types  .= 'i';
    $args[]  = $client_id;
  }

  // Text search on bidon name/type/price
  if ($q !== '') {
    $where[] = '(bd.nom_bidon LIKE ? OR bd.type_bidon LIKE ? OR CAST(dbb.prix_unitaire AS CHAR) LIKE ?)';
    $like = "%{$q}%";
    $types .= 'sss';
    array_push($args, $like, $like, $like);
  }

  // Date range on the line creation date
  if ($date_from !== '') {
    $where[] = 'dbb.created_at >= ?';
    $types .= 's';
    $args[] = $date_from . ' 00:00:00';
  }
  if ($date_to !== '') {
    $where[] = 'dbb.created_at <= ?';
    $types .= 's';
    $args[] = $date_to . ' 23:59:59';
  }

  // Always attached to a bon (your UI assumes has_sortie=true)
  $where[] = 'dbb.id_bon IS NOT NULL';

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  // -------- COUNT --------
  $sqlCnt = "
    SELECT COUNT(*) AS cnt
    FROM Client c
    INNER JOIN Bon_Sortie_Bidon bsb ON c.id = bsb.id_client
    INNER JOIN Details_bon_bidon dbb ON bsb.id_bon_sortie_bidon = dbb.id_bon
    INNER JOIN bidons bd            ON dbb.id_bidon = bd.id_bidon
    $whereSql
  ";
  $stmt = $connect->prepare($sqlCnt);
  if ($types !== '') { $stmt->bind_param($types, ...$args); }
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
  $stmt->close();

  // -------- PAGE --------
  // Align with your example SELECT, but add the fields your UI needs (dbb.id_bon, dbb.created_at)
  $sql = "
    SELECT 
      c.id AS client_id,
      CONCAT(c.prenom, ' ', c.nom) AS client_nom_complet,
      bsb.id_bon_sortie_bidon,
      bsb.destination,
      bsb.statut,
      bsb.mode_paiement,
      bsb.created_at AS date_commande,
      dbb.id_detail,
      dbb.id_bon,                            -- for printing
      dbb.created_at AS created_at,          -- line date for UI filtering/sorting
      bd.nom_bidon,
      bd.type_bidon,
      dbb.quantite,
      dbb.prix_unitaire,
      (dbb.quantite * dbb.prix_unitaire) AS total_ligne
    FROM 
      Client c
      INNER JOIN Bon_Sortie_Bidon bsb ON c.id = bsb.id_client
      INNER JOIN Details_bon_bidon dbb ON bsb.id_bon_sortie_bidon = dbb.id_bon
      INNER JOIN bidons bd ON dbb.id_bidon = bd.id_bidon
    $whereSql
    ORDER BY dbb.created_at DESC, dbb.id_bon DESC, dbb.id_detail DESC
    LIMIT ? OFFSET ?
  ";
  $stmt = $connect->prepare($sql);
  $typesPage = $types . 'ii';
  $argsPage  = [...$args, $perPage, $offset];
  $stmt->bind_param($typesPage, ...$argsPage);
  $stmt->execute();
  $res = $stmt->get_result();

  // -------- Map to UI shape --------
  $items = [];
  while ($r = $res->fetch_assoc()) {
    $items[] = [
      'id_detail'       => (int)$r['id_detail'],
      'id_bon'          => (int)$r['id_bon'],
      'client'          => (string)$r['client_nom_complet'],
      'nom_bidon'       => (string)$r['nom_bidon'],
      'type_bidon'      => (string)$r['type_bidon'],
      'quantite_bidon'  => (float)$r['quantite'],
      'prix_bidon'      => (float)$r['prix_unitaire'],
      'total_ligne'     => (float)$r['total_ligne'],
      'created_at'      => $r['created_at'],
      'has_sortie'      => true,
    ];
  }
  $stmt->close();

  out([
    'ok'         => true,
    'items'      => $items,
    'page'       => $page,
    'perPage'    => $perPage,
    'total'      => $total,
    'totalPages' => (int)ceil($total / max(1,$perPage)),
  ]);

} catch (Throwable $e) {
  out(['ok'=>false,'error'=>'Server error: '.$e->getMessage()], 500);
}
