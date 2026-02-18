<?php
// includes/ajax/Fitor/BonFitor/createBonFitor.php
// JSON API + create Bon Fitor (POST)
// Paths assume project root has /config/config.ini.php
// Adjust the require path if your structure differs.



require_once __DIR__ . '/../../../../config/config.ini.php'; // must define $connect (mysqli)

function json_response($data, int $status = 200) {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function fetch_all(mysqli_stmt $stmt): array {
  $res = $stmt->get_result();
  $rows = [];
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
  $stmt->close();
  return $rows;
}

// ======= Small helpers =======
function validate_relations(mysqli $db, int $id_patron, int $id_chauffeur, int $id_vehicule, int $id_tara): bool {
  $sql = "SELECT 1
          FROM patron p
          JOIN chauffeur c ON c.id_patron = p.id_patron
          JOIN vehicule v  ON v.id_Fchauffeur = c.id_Fchauffeur
          JOIN taratonage t ON t.id_vehicule_Fitor = v.id_vehicule
          WHERE p.id_patron = ? AND c.id_Fchauffeur = ? AND v.id_vehicule = ? AND t.id_taratonage = ?
          LIMIT 1";
  $stmt = $db->prepare($sql);
  $stmt->bind_param('iiii', $id_patron, $id_chauffeur, $id_vehicule, $id_tara);
  $stmt->execute();
  $ok = (bool)$stmt->get_result()->fetch_row();
  $stmt->close();
  return $ok;
}

function patron_totaux(mysqli $db, int $id_patron): array {
  // Total versements
  $sqlV = "SELECT COALESCE(SUM(v.montant),0) AS total_versements
           FROM chauffeur c
           LEFT JOIN versement v ON v.id_Fchauffeur = c.id_Fchauffeur
           WHERE c.id_patron = ?";
  $stmt = $db->prepare($sqlV);
  $stmt->bind_param('i', $id_patron);
  $stmt->execute();
  $tv = (float)($stmt->get_result()->fetch_assoc()['total_versements'] ?? 0);
  $stmt->close();

  // Total bons
  $sqlB = "SELECT COALESCE(SUM(b.prix_net),0) AS total_bons
           FROM chauffeur c
           JOIN vehicule vh   ON vh.id_Fchauffeur = c.id_Fchauffeur
           JOIN taratonage t  ON t.id_vehicule_Fitor = vh.id_vehicule
           JOIN bon_fitor b   ON b.id_taratonage = t.id_taratonage
           WHERE c.id_patron = ?";
  $stmt = $db->prepare($sqlB);
  $stmt->bind_param('i', $id_patron);
  $stmt->execute();
  $tb = (float)($stmt->get_result()->fetch_assoc()['total_bons'] ?? 0);
  $stmt->close();

  return [
    'total_versements' => $tv,
    'total_bons'       => $tb,
    'solde'            => $tv - $tb,
  ];
}

// ======= ROUTER (GET JSON endpoints) =======
if (isset($_GET['action'])) {
  $action = $_GET['action'];

  if ($action === 'get_patrons') {
    $sql = "SELECT id_patron, CONCAT(nom_patron, ' ', prenom_patron) AS label
            FROM patron ORDER BY nom_patron, prenom_patron";
    $rows = [];
    if ($res = $connect->query($sql)) {
      while ($r = $res->fetch_assoc()) { $rows[] = $r; }
      $res->close();
    }
    json_response(['ok'=>true, 'data'=>$rows]);
  }

  if ($action === 'get_chauffeurs') {
    $id_patron = (int)($_GET['id_patron'] ?? 0);
    $stmt = $connect->prepare("SELECT id_Fchauffeur, CONCAT(nom, ' ', prenom) AS label FROM chauffeur WHERE id_patron = ? ORDER BY nom, prenom");
    $stmt->bind_param('i', $id_patron);
    $stmt->execute();
    json_response(['ok'=>true, 'data'=>fetch_all($stmt)]);
  }

  if ($action === 'get_vehicules') {
    $id_chauffeur = (int)($_GET['id_chauffeur'] ?? 0);
    $stmt = $connect->prepare("SELECT id_vehicule, CONCAT(nom_vehicule, ' (', matricule, ')') AS label FROM vehicule WHERE id_Fchauffeur = ? ORDER BY nom_vehicule");
    $stmt->bind_param('i', $id_chauffeur);
    $stmt->execute();
    json_response(['ok'=>true, 'data'=>fetch_all($stmt)]);
  }

  if ($action === 'get_taratonages') {
    $id_vehicule = (int)($_GET['id_vehicule'] ?? 0);
    $stmt = $connect->prepare(
      "SELECT t.id_taratonage,
              CONCAT('Tara#', t.id_taratonage, ' | Brut ', FORMAT(t.poid_brut,2), ' | Tara ', FORMAT(t.poid_tarat,2), ' | Net ', FORMAT(t.poid_net,2)) AS label
       FROM taratonage t
       WHERE t.id_vehicule_Fitor = ?
         AND NOT EXISTS (SELECT 1 FROM bon_fitor b WHERE b.id_taratonage = t.id_taratonage)
       ORDER BY t.taratonage_created_at ASC, t.id_taratonage ASC"
    );
    $stmt->bind_param('i', $id_vehicule);
    $stmt->execute();
    json_response(['ok'=>true, 'data'=>fetch_all($stmt)]);
  }

  if ($action === 'get_taratonage_info') {
    $id_tara = (int)($_GET['id_taratonage'] ?? 0);
    $stmt = $connect->prepare("SELECT id_taratonage, poid_brut, poid_tarat, poid_net, note FROM taratonage WHERE id_taratonage = ? LIMIT 1");
    $stmt->bind_param('i', $id_tara);
    $stmt->execute();
    $rows = fetch_all($stmt);
    json_response(['ok'=> (bool)$rows, 'data'=> $rows ? $rows[0] : null]);
  }

  if ($action === 'get_patron_solde') {
    $id_patron = (int)($_GET['id_patron'] ?? 0);
    $tot = patron_totaux($connect, $id_patron);
    json_response(['ok'=>true, 'data'=>$tot]);
  }

  json_response(['ok'=>false, 'error'=>'Action inconnue'], 400);
}

// ======= POST: Create Bon Fitor =======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Basic CSRF check
   

    $id_patron   = (int)($_POST['id_patron'] ?? 0);
    $id_chauff   = (int)($_POST['id_Fchauffeur'] ?? 0);
    $id_vehicule = (int)($_POST['id_vehicule'] ?? 0);
    $id_tara     = (int)($_POST['id_taratonage'] ?? 0);

    $quantite   = isset($_POST['quantite']) ? (float)$_POST['quantite'] : 0.0;
    $prix_init  = isset($_POST['prix_init']) ? (float)$_POST['prix_init'] : 0.0;
    $provider   = trim((string)($_POST['provider'] ?? ''));
    $use_poid   = (string)($_POST['use_poid_net'] ?? '0') === '1';

    if ($id_patron<=0 || $id_chauff<=0 || $id_vehicule<=0 || $id_tara<=0) {
      throw new Exception("Sélection invalide (Patron/Chauffeur/Véhicule/Taratonage).");
    }
    if ($quantite <= 0 || $prix_init <= 0) {
      throw new Exception("Quantité et Prix initial doivent être > 0.");
    }

    // Check taratonage not used
    $chk = $connect->prepare('SELECT 1 FROM bon_fitor WHERE id_taratonage = ? LIMIT 1');
    $chk->bind_param('i', $id_tara);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
      $chk->close();
      throw new Exception("Ce taratonage est déjà associé à un bon.");
    }
    $chk->close();

    // Validate chain patron -> chauffeur -> vehicule -> taratonage
    if (!validate_relations($connect, $id_patron, $id_chauff, $id_vehicule, $id_tara)) {
      throw new Exception("Incohérence des relations sélectionnées (patron / chauffeur / véhicule / taratonage).");
    }

    // Enforce server poid_net if requested
    if ($use_poid) {
      $taraInfo = $connect->prepare('SELECT poid_net FROM taratonage WHERE id_taratonage = ?');
      $taraInfo->bind_param('i', $id_tara);
      $taraInfo->execute();
      $poid_net_db = (float)($taraInfo->get_result()->fetch_assoc()['poid_net'] ?? 0);
      $taraInfo->close();
     
      if ($quantite <= 0) {
        throw new Exception("poid_net invalide pour ce taratonage.");
      }
    }

    $prix_net = round($quantite * $prix_init, 2);
   

    // INSERT
    $ins = $connect->prepare('INSERT INTO bon_fitor (quantite, prix_init, prix_net, provider, id_taratonage) VALUES (?, ?, ?, ?, ?)');
    $ins->bind_param('dddsi', $quantite, $prix_init, $prix_net, $provider, $id_tara);
    if (!$ins->execute()) {
      throw new Exception('Erreur insertion bon_fitor: '.$connect->error);
    }
    $new_id = $ins->insert_id;
    $ins->close();

    $tot = patron_totaux($connect, $id_patron);

    json_response([
      'ok'      => true,
      'message' => 'Bon Fitor créé avec succès (ID #'.$new_id.')',
      'new_id'  => $new_id,
      'totaux'  => $tot
    ]);
  } catch (Throwable $e) {
    json_response(['ok'=>false, 'error'=>$e->getMessage()], 400);
  }
}

// If we reach here, method not allowed
json_response(['ok'=>false, 'error'=>'Méthode non autorisée'], 405);
