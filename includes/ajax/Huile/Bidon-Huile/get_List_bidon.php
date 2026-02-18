<?php
// get_List_bidon.php — liste des lignes POUR UN CLIENT en joignant via l’entête de bon
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors','0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function($sev,$msg,$file,$line){ throw new ErrorException($msg,0,$sev,$file,$line); });

require_once '../../../../config/config.ini.php';

try {
  // Inputs
  $client_id = (int)($_GET['client_id'] ?? 0);
  if ($client_id <= 0) {
    echo json_encode(['ok'=>false,'error'=>'client_id manquant']); exit;
  }
  $q            = trim((string)($_GET['q'] ?? ''));
  $page         = max(1, (int)($_GET['page'] ?? 1));
  $perPage      = (int)($_GET['perPage'] ?? 25);
  $perPage      = in_array($perPage, [10,25,50,100], true) ? $perPage : 25;
  $offset       = ($page - 1) * $perPage;
  $date_from    = trim((string)($_GET['date_from'] ?? ''));
  $date_to      = trim((string)($_GET['date_to']   ?? ''));
  $without_sortie = (int)($_GET['without_sortie'] ?? 0); // NOTE: pour ce client, toutes les lignes doivent déjà être rattachées à un bon (id_bon != NULL)

  // WHERE dynamique
  $w    = ["bon.id_client = ?"];
  $args = [$client_id];
  $typs = "i";

  if ($q !== '') {
    $w[]   = "(bdn.nom_bidon LIKE ? OR bdn.type_bidon LIKE ? OR CAST(dbb.prix_unitaire AS CHAR) LIKE ?)";
    $like  = "%{$q}%";
    $typs .= "sss";
    array_push($args, $like, $like, $like);
  }
  if ($date_from !== '') {
    $w[]   = "dbb.created_at >= ?";
    $typs .= "s";
    $args[] = $date_from . " 00:00:00";
  }
  if ($date_to !== '') {
    $w[]   = "dbb.created_at <= ?";
    $typs .= "s";
    $args[] = $date_to . " 23:59:59";
  }

  // IMPORTANT : dans une vue "par client", les lignes sans bon (id_bon IS NULL) ne peuvent pas être reliées au client.
  // On limite donc aux lignes RATTACHÉES à un bon de ce client.
  $w[] = "dbb.id_bon IS NOT NULL";

  $where = "WHERE " . implode(" AND ", $w);

  // COUNT
  $sqlCnt = "
    SELECT COUNT(*) AS cnt
    FROM details_bon_bidon dbb
    JOIN bon_sortie_bidon bon ON bon.id_bon_sortie_bidon = dbb.id_bon
    JOIN bidons bdn           ON bdn.id_bidon = dbb.id_bidon
    $where
  ";
  $stmt = $connect->prepare($sqlCnt);
  $stmt->bind_param($typs, ...$args);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
  $stmt->close();

  // PAGE
  $sql = "
    SELECT
      dbb.id_detail,
      dbb.id_bon,
      CONCAT(c.prenom,' ',c.nom) AS client,
      bdn.nom_bidon,
      bdn.type_bidon,
      dbb.quantite             AS quantite_bidon,   -- alias pour coller au front
      dbb.prix_unitaire        AS prix_bidon,       -- alias pour coller au front
      (dbb.quantite * dbb.prix_unitaire) AS total_ligne,
      dbb.created_at
    FROM details_bon_bidon dbb
    JOIN bon_sortie_bidon bon ON bon.id_bon_sortie_bidon = dbb.id_bon
    JOIN client c             ON c.id = bon.id_client
    JOIN bidons bdn           ON bdn.id_bidon = dbb.id_bidon
    $where
    ORDER BY dbb.created_at DESC, dbb.id_detail DESC
    LIMIT ? OFFSET ?
  ";
  $stmt = $connect->prepare($sql);
  $typsPage = $typs . "ii";
  $argsPage = [...$args, $perPage, $offset];
  $stmt->bind_param($typsPage, ...$argsPage);
  $stmt->execute();
  $res = $stmt->get_result();

  $items = [];
  while ($r = $res->fetch_assoc()) {
    $r['has_sortie'] = true; // puisque id_bon IS NOT NULL garanti par le WHERE
    $items[] = [
      'id_detail'     => (int)$r['id_detail'],
      'id_bon'        => (int)$r['id_bon'],
      'client'        => (string)$r['client'],
      'nom_bidon'     => (string)$r['nom_bidon'],
      'type_bidon'    => (string)$r['type_bidon'],
      'quantite_bidon'=> (float)$r['quantite_bidon'],
      'prix_bidon'    => (float)$r['prix_bidon'],
      'total_ligne'   => (float)$r['total_ligne'],
      'created_at'    => $r['created_at'],
      'has_sortie'    => true
    ];
  }
  $stmt->close();

  echo json_encode([
    'ok'         => true,
    'items'      => $items,
    'page'       => $page,
    'perPage'    => $perPage,
    'total'      => $total,
    'totalPages' => (int)ceil($total / max(1,$perPage)),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error: '.$e->getMessage()]);
  exit;
}
