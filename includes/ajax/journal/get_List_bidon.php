<?php
// get_List_bidon.php — Journal détaillé des bidons clients
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function ($sev, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $sev, $file, $line);
});

require_once __DIR__ . '/../../../config/config.ini.php';
$connect->set_charset('utf8mb4');

try {
  // === PARAMÈTRES DE FILTRAGE ===
  $id_client = (int)($_POST['id_client'] ?? $_GET['id_client'] ?? 0);
  $date_debut = trim((string)($_POST['date_debut'] ?? $_GET['date_debut'] ?? ''));
  $date_fin   = trim((string)($_POST['date_fin'] ?? $_GET['date_fin'] ?? ''));
  $statut     = trim((string)($_POST['statut_paiement'] ?? $_GET['statut_paiement'] ?? 'tous'));
  $page       = max(1, (int)($_GET['page'] ?? 1));
  $perPage    = (int)($_GET['perPage'] ?? 100);
  $offset     = ($page - 1) * $perPage;

  // === CONSTRUCTION DU WHERE DYNAMIQUE ===
  $w = ["1=1"];
  $types = "";
  $args = [];

  if ($id_client > 0) {
    $w[] = "bon.id_client = ?";
    $types .= "i";
    $args[] = $id_client;
  }

  if ($date_debut !== '') {
    $w[] = "DATE(bon.created_at) >= ?";
    $types .= "s";
    $args[] = $date_debut;
  }
  if ($date_fin !== '') {
    $w[] = "DATE(bon.created_at) <= ?";
    $types .= "s";
    $args[] = $date_fin;
  }

  if ($statut !== '' && $statut !== 'tous') {
    $w[] = "bon.statut = ?";
    $types .= "s";
    $args[] = $statut;
  }

  $where = "WHERE " . implode(" AND ", $w);

  // === REQUÊTE PRINCIPALE ===
  $sql = "
    SELECT
      bon.id_bon_sortie_bidon     AS id_bon,
      bon.created_at              AS date_bon,
      CONCAT(c.prenom,' ',c.nom)  AS client,
      bdn.nom_bidon,
      bdn.type_bidon,
      dbb.quantite                AS quantite,
      dbb.prix_unitaire           AS prix_unitaire,
      (dbb.quantite * dbb.prix_unitaire) AS prix_total,
      bon.mode_paiement,
      bon.statut,
      bon.destination
    FROM details_bon_bidon dbb
    JOIN bon_sortie_bidon bon ON bon.id_bon_sortie_bidon = dbb.id_bon
    JOIN client c ON c.id = bon.id_client
    JOIN bidons bdn ON bdn.id_bidon = dbb.id_bidon
    $where
    ORDER BY bon.created_at DESC, bon.id_bon_sortie_bidon DESC
    LIMIT ? OFFSET ?
  ";

  $typesPage = $types . "ii";
  $argsPage = [...$args, $perPage, $offset];

  $stmt = $connect->prepare($sql);
  $stmt->bind_param($typesPage, ...$argsPage);
  $stmt->execute();
  $res = $stmt->get_result();

  // === COLLECTE DES LIGNES ===
  $rows = [];
  $quantite_totale = 0;
  $somme_totale = 0;
  $somme_payee = 0;
  $somme_non_paye = 0;

  while ($r = $res->fetch_assoc()) {
    $qte = (float)$r['quantite'];
    $prix_total = (float)$r['prix_total'];

    $quantite_totale += $qte;
    $somme_totale += $prix_total;

    if (strtolower($r['statut']) === 'payé' || strtolower($r['statut']) === 'paye') {
      $somme_payee += $prix_total;
    } else {
      $somme_non_paye += $prix_total;
    }

    $rows[] = [
      'id_bon'       => (int)$r['id_bon'],
      'date_bon'     => $r['date_bon'],
      'client'       => $r['client'],
      'nom_bidon'    => $r['nom_bidon'],
      'type_bidon'   => $r['type_bidon'],
      'quantite'     => $qte,
      'prix_unitaire'=> (float)$r['prix_unitaire'],
      'prix_total'   => $prix_total,
      'mode_paiement'=> $r['mode_paiement'],
      'statut'       => $r['statut'],
      'destination'  => $r['destination']
    ];
  }
  $stmt->close();

  // === COMPTAGE TOTAL POUR PAGINATION ===
  $sqlCount = "
    SELECT COUNT(*) AS total
    FROM details_bon_bidon dbb
    JOIN bon_sortie_bidon bon ON bon.id_bon_sortie_bidon = dbb.id_bon
    JOIN client c ON c.id = bon.id_client
    JOIN bidons bdn ON bdn.id_bidon = dbb.id_bidon
    $where
  ";
  $stmt = $connect->prepare($sqlCount);
  $stmt->bind_param($types, ...$args);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
  $stmt->close();

  echo json_encode([
    'ok' => true,
    'rows' => $rows,
    'quantite_totale' => $quantite_totale,
    'somme_totale' => $somme_totale,
    'somme_payee' => $somme_payee,
    'somme_non_paye' => $somme_non_paye,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => ceil($total / max(1, $perPage))
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Erreur serveur : ' . $e->getMessage()]);
  exit;
}
