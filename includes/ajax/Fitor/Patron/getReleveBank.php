<?php
// getReleveBank.php
require_once __DIR__ . '/../../../../config/config.ini.php'; // defines $connect (mysqli)
header('Content-Type: application/json; charset=utf-8');

// Ensure connection charset
if (method_exists($connect, 'set_charset')) {
  $connect->set_charset('utf8mb4');
}

// Params
$id_patron = isset($_GET['id_patron']) ? (int)$_GET['id_patron'] : 0;
$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : null; // 'YYYY-MM-DD'
$to   = isset($_GET['to'])   && $_GET['to']   !== '' ? $_GET['to']   : null;

if ($id_patron <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Paramètre id_patron manquant ou invalide.'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Patron (owner)
$sqlPatron = "SELECT nom_patron, prenom_patron, telephone_patron FROM patron WHERE id_patron = ?";
$stmt = $connect->prepare($sqlPatron);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['error' => 'Erreur préparation SQL patron.'], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param("i", $id_patron);
$stmt->execute();
$res = $stmt->get_result();
$patron = $res ? $res->fetch_assoc() : null;
$stmt->close();

if ($patron) {
  $owner = [
    'title'     => 'M./Mr',
    'name'      => trim(($patron['nom_patron'] ?? '') . ' ' . ($patron['prenom_patron'] ?? '')),
    'telephone' => $patron['telephone_patron'] ?? '',
    'address'   => ['Maroc'],
  ];
} else {
  $owner = [
    'title'     => 'M./Mme',
    'name'      => '—',
    'telephone' => '',
    'address'   => [''],
  ];
}

// Période par défaut (mois courant)
if (!$from || !$to) {
  $from = $from ?: date('Y-m-01');
  $to   = $to   ?: date('Y-m-t');
}
$period = ['from' => $from, 'to' => $to];

// Transactions
$transactions = [];

/*
  We union:
   - bon_fitor (debit): amount = quantite * prix_net
   - versement (credit): amount = v.montant
  We filter BOTH subqueries by id_patron and date range.
*/
$sqlTar = "
(
  SELECT
    DATE(bf.created_at) AS date_op,
    CONCAT('Bon / Tarat. – ', COALESCE(ch.prenom,''), ' ', COALESCE(ch.nom,'')) AS libelle,
    bf.quantite AS quantite,
    bf.prix_init AS prix_init,
    (bf.quantite * bf.prix_init) AS debit,
    NULL AS credit,
    ve.matricule AS matricule
  FROM bon_fitor bf
  JOIN taratonage t  ON t.id_taratonage = bf.id_taratonage
  JOIN vehicule ve   ON ve.id_vehicule   = t.id_vehicule_Fitor
  JOIN chauffeur ch  ON ch.id_Fchauffeur = ve.id_Fchauffeur
  WHERE ch.id_patron = ?

)
UNION ALL
(
  SELECT
    DATE(v.date_versement) AS date_op,
    CONCAT('Versement (', v.mode_paiement, ') – ', COALESCE(ch.prenom,''), ' ', COALESCE(ch.nom,'')) AS libelle,
    NULL AS quantite,
    NULL AS prix_init,
    NULL AS debit,
    v.montant AS credit,
    NULL AS matricule
  FROM versement v
  JOIN chauffeur ch ON ch.id_Fchauffeur = v.id_Fchauffeur
  WHERE ch.id_patron = ?
 
)
ORDER BY date_op ASC
";

$stmt = $connect->prepare($sqlTar);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['error' => 'Erreur préparation SQL transactions.'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Bind: id, from, to, id, from, to
$stmt->bind_param("ii", $id_patron, $id_patron);
$stmt->execute();
$r = $stmt->get_result();

if ($r) {
  while ($row = $r->fetch_assoc()) {
    $transactions[] = [
      'date'      => $row['date_op'],
      'libelle'   => trim($row['libelle']),
      'matricule' => $row['matricule'] ?? '',
      'quantite'  => isset($row['quantite']) ? (float)$row['quantite'] : 0.0,
      'prix_init' => isset($row['prix_init']) ? (float)$row['prix_init'] : 0.0,
      'debit'     => isset($row['debit'])  ? (float)$row['debit']  : 0.0,
      'credit'    => isset($row['credit']) ? (float)$row['credit'] : 0.0,
    ];
  }
}
$stmt->close();

// Soldes & totaux (solde d’ouverture 0 par défaut)
$opening_balance = 0.0;
$balance = $opening_balance;
$total_debit = 0.0;
$total_credit = 0.0;

foreach ($transactions as $t) {
  // Balance increases with credits (versements), decreases with debits (bons)
  $balance += ($t['credit'] - $t['debit']);
  $total_debit  += $t['debit'];
  $total_credit += $t['credit'];
}

echo json_encode([
  'owner'           => $owner,
  'period'          => $period,
  'opening_balance' => $opening_balance,
  'transactions'    => $transactions,
  'totals'          => [
    'debit'           => $total_debit,
    'credit'          => $total_credit,
    'closing_balance' => $balance,
  ],
], JSON_UNESCAPED_UNICODE);
