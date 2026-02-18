<?php
// getBon_Huile.php — liste paginée de tous les bons d’huile (tous clients)
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors','0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function($sev,$msg,$file,$line){ throw new ErrorException($msg,0,$sev,$file,$line); });

require_once '../../../config/config.ini.php'; // adjust if needed

try {
  // ---- Inputs
  $q         = trim((string)($_GET['q'] ?? ''));
  $page      = max(1, (int)($_GET['page'] ?? 1));
  $perPage   = (int)($_GET['perPage'] ?? 25);
  $perPage   = in_array($perPage, [10,25,50,100], true) ? $perPage : 25;
  $offset    = ($page - 1) * $perPage;
  $date_from = trim((string)($_GET['date_from'] ?? ''));
  $date_to   = trim((string)($_GET['date_to']   ?? ''));

  // ---- Dynamic WHERE
  $w    = ["1=1"];
  $args = [];
  $typs = "";

  if ($q !== '') {
    $w[]   = "(c.nom LIKE ? OR c.prenom LIKE ? OR b.provider LIKE ? OR b.note LIKE ? OR CAST(b.prix_net AS CHAR) LIKE ?)";
    $like  = "%{$q}%";
    $typs .= "sssss";
    array_push($args, $like, $like, $like, $like, $like);
  }
  if ($date_from !== '') {
    $w[]   = "b.date_bon >= ?";
    $typs .= "s";
    $args[] = $date_from;
  }
  if ($date_to !== '') {
    $w[]   = "b.date_bon <= ?";
    $typs .= "s";
    $args[] = $date_to;
  }

  $where = "WHERE " . implode(" AND ", $w);

  // ---- Count total
  $sqlCnt = "
    SELECT COUNT(*) AS cnt
    FROM bon_de_huile b
    JOIN client c ON c.id = b.client_id
    $where
  ";
  $stmt = $connect->prepare($sqlCnt);
  if ($typs !== '') $stmt->bind_param($typs, ...$args);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
  $stmt->close();

  // ---- Main query (page)
  $sql = "
    SELECT
      b.id               AS id_bon,
      CONCAT(c.prenom, ' ', c.nom) AS client,
      c.id               AS id_client,
      c.ville,
      b.quantite,
      b.date_bon,
      b.prix_unitaire,
      b.prix_net,
      b.provider,
      b.mode_paiement_huile,
      b.note,
      b.created_at
    FROM bon_de_huile b
    JOIN client c ON c.id = b.client_id
    $where
    ORDER BY b.created_at DESC, b.id DESC
    LIMIT ? OFFSET ?
  ";
  $stmt = $connect->prepare($sql);
  $typsPage = $typs . "ii";
  $argsPage = [...$args, $perPage, $offset];
  if ($typs !== '') $stmt->bind_param($typsPage, ...$argsPage);
  else $stmt->bind_param("ii", $perPage, $offset);
  $stmt->execute();
  $res = $stmt->get_result();

  $items = [];
  while ($r = $res->fetch_assoc()) {

    // Fetch details of bidons
    $stmt2 = $connect->prepare("
      SELECT 
        d.id_detail,
        d.quantite_bidon,
        bd.nom_bidon,
        bd.type_bidon,
        bd.prix_bidon
      FROM details_bon_huile d
      LEFT JOIN bidons bd ON bd.id_bidon = d.id_bidon
      WHERE d.id_bon = ?
    ");
    $stmt2->bind_param('i', $r['id_bon']);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $details = [];
    while ($d = $res2->fetch_assoc()) {
      $details[] = [
        'id_detail'      => (int)$d['id_detail'],
        'nom_bidon'      => (string)$d['nom_bidon'],
        'type_bidon'     => (string)$d['type_bidon'],
        'quantite_bidon' => (float)$d['quantite_bidon'],
        'prix_bidon'     => (float)$d['prix_bidon']
      ];
    }
    $stmt2->close();

    $items[] = [
      'id_bon'            => (int)$r['id_bon'],
      'id_client'         => (int)$r['id_client'],
      'client'            => (string)$r['client'],
      'ville'             => (string)($r['ville'] ?? ''),
      'quantite'          => (float)$r['quantite'],
      'date_bon'          => $r['date_bon'],
      'prix_unitaire'     => (float)$r['prix_unitaire'],
      'prix_net'          => (float)$r['prix_net'],
      'provider'          => (string)$r['provider'],
      'mode_paiement'     => (string)($r['mode_paiement_huile'] ?? ''),
      'note'              => (string)($r['note'] ?? ''),
      'created_at'        => $r['created_at'],
      'details_bidons'    => $details
    ];
  }
  $stmt->close();

  // ---- Output
  echo json_encode([
    'ok'         => true,
    'items'      => $items,
    'page'       => $page,
    'perPage'    => $perPage,
    'total'      => $total,
    'totalPages' => (int)ceil($total / max(1,$perPage))
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error: '.$e->getMessage()]);
  exit;
}
