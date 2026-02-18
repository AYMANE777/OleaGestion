<?php
// getSoldeClientGlobal.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
    $id_client = (int)($_GET['id_client'] ?? 0);
    if ($id_client <= 0) {
        throw new Exception("Client invalide.");
    }

    // === 1️⃣ Infos client
    $stmt = $connect->prepare("SELECT id, nom, prenom, phone AS telephone, ville FROM client WHERE id = ?");
    $stmt->bind_param('i', $id_client);
    $stmt->execute();
    $client = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$client) throw new Exception("Client introuvable.");

    // === 2️⃣ Total Huile (prix_net)
    $sql_huile = "SELECT COALESCE(SUM(prix_net),0) AS total_huile FROM bon_de_huile WHERE client_id = ?";
    $stmt = $connect->prepare($sql_huile);
    $stmt->bind_param('i', $id_client);
    $stmt->execute();
    $total_huile = (float)$stmt->get_result()->fetch_assoc()['total_huile'];
    $stmt->close();

    // === 3️⃣ Total Bidon (quantite * prix_unitaire)
    $sql_bidon = "
        SELECT COALESCE(SUM(dbb.quantite * dbb.prix_unitaire),0) AS total_bidon
        FROM bon_sortie_bidon bon
        JOIN details_bon_bidon dbb ON dbb.id_bon = bon.id_bon_sortie_bidon
        WHERE bon.id_client = ?
    ";
    $stmt = $connect->prepare($sql_bidon);
    $stmt->bind_param('i', $id_client);
    $stmt->execute();
    $total_bidon = (float)$stmt->get_result()->fetch_assoc()['total_bidon'];
    $stmt->close();

    // === 4️⃣ Total Versements
    $sql_vers = "SELECT COALESCE(SUM(montant),0) AS total_versement FROM versement_client WHERE id_client = ?";
    $stmt = $connect->prepare($sql_vers);
    $stmt->bind_param('i', $id_client);
    $stmt->execute();
    $total_versement = (float)$stmt->get_result()->fetch_assoc()['total_versement'];
    $stmt->close();

    // === 5️⃣ Solde Global
    $total_credit = $total_huile + $total_bidon;
    $solde = $total_credit - $total_versement;

   // === 6️⃣ Commandes mixtes (Huile + Bidons dans même bon)
$sql_mixte = "
    SELECT
      b.id AS id_bon,
      b.date_bon,
      b.prix_net,
      b.quantite,
      b.prix_unitaire,
      b.provider,
      b.mode_paiement_huile,
      b.note,
      b.created_at,
      COALESCE(SUM(d.quantite_bidon * bd.prix_bidon), 0) AS total_bidon_mixte,
      GROUP_CONCAT(
          CONCAT(
              bd.nom_bidon, '::',
              bd.type_bidon, '::',
              d.quantite_bidon, '::',
              bd.prix_bidon
          ) SEPARATOR '||'
      ) AS bidons_details
    FROM bon_de_huile b
    LEFT JOIN details_bon_huile d ON d.id_bon = b.id
    LEFT JOIN bidons bd ON bd.id_bidon = d.id_bidon
    WHERE b.client_id = ?
    GROUP BY b.id
    ORDER BY b.date_bon DESC
";

$stmt = $connect->prepare($sql_mixte);
$stmt->bind_param('i', $id_client);
$stmt->execute();
$res_mixte = $stmt->get_result();

$commandes_mixte = [];
$total_bidon_mixte_global = 0; // pour la somme globale de tous les bidons mixtes

while ($r = $res_mixte->fetch_assoc()) {
    $bidons = [];
    $total_bidon_mixte = (float)$r['total_bidon_mixte'];
    $total_bidon_mixte_global += $total_bidon_mixte;

    if (!empty($r['bidons_details'])) {
        foreach (explode('||', $r['bidons_details']) as $b) {
            [$nom, $type, $qte, $prix] = explode('::', $b);
            if (trim($nom) !== '') {
                $bidons[] = [
                    'nom_bidon' => $nom,
                    'type_bidon' => $type,
                    'quantite_bidon' => (float)$qte,
                    'prix_bidon' => (float)$prix,
                    'total_bidon' => (float)$qte * (float)$prix
                ];
            }
        }
    }

    $total_general_commande = (float)$r['prix_net'] + $total_bidon_mixte;

    $commandes_mixte[] = [
        'id_bon' => (int)$r['id_bon'],
        'date_bon' => $r['date_bon'],
        'quantite' => (float)$r['quantite'],
        'prix_unitaire' => (float)$r['prix_unitaire'],
        'prix_net' => (float)$r['prix_net'],
        'total_bidon_mixte' => $total_bidon_mixte,
        'total_general' => $total_general_commande,
        'provider' => $r['provider'],
        'mode_paiement' => $r['mode_paiement_huile'],
        'note' => $r['note'],
        'bidons' => $bidons
    ];
}
$stmt->close();



    // === 7️⃣ Détails bidons
    $sql_bidon_details = "
        SELECT bon.id_bon_sortie_bidon, bon.created_at, bdn.nom_bidon, bdn.type_bidon,
               dbb.quantite, dbb.prix_unitaire, (dbb.quantite * dbb.prix_unitaire) AS montant_total,bon.statut
        FROM bon_sortie_bidon bon
        JOIN details_bon_bidon dbb ON dbb.id_bon = bon.id_bon_sortie_bidon
        JOIN bidons bdn ON bdn.id_bidon = dbb.id_bidon
        WHERE bon.id_client = ?
        ORDER BY bon.created_at DESC
    ";
    $stmt = $connect->prepare($sql_bidon_details);
    $stmt->bind_param('i', $id_client);
    $stmt->execute();
    $res_bidon = $stmt->get_result();
    $bidons = [];
    while ($b = $res_bidon->fetch_assoc()) {
        $bidons[] = [
            'date'          => $b['created_at'],
            'nom_bidon'     => $b['nom_bidon'],
            'type_bidon'    => $b['type_bidon'],
            'quantite'      => (float)$b['quantite'],
            'prix_unitaire' => (float)$b['prix_unitaire'],
            'montant_total' => (float)$b['montant_total'],
             'statut'        => $b['statut'] ?? 'NON PAYE'
        ];
    }
    $stmt->close();

    // === 8️⃣ Détails versements
    $sql_v = "
        SELECT id_versement, montant, mode_paiement, note, justificatif, created_at
        FROM versement_client WHERE id_client = ? ORDER BY created_at DESC
    ";
    $stmt = $connect->prepare($sql_v);
    $stmt->bind_param('i', $id_client);
    $stmt->execute();
    $res_v = $stmt->get_result();
    $versements = [];
    while ($v = $res_v->fetch_assoc()) {
        $versements[] = [
            'date_versement' => $v['created_at'],
            'montant'        => (float)$v['montant'],
            'mode_paiement'  => $v['mode_paiement'],
            'note'           => $v['note'],
            'image_versement'=> $v['justificatif']
        ];
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'client' => $client,
        'totaux' => [
            'huile'       => $total_huile,
            'bidon'       => $total_bidon,
            'credit'      => $total_credit,
            'versement'   => $total_versement,
            'solde'       => $solde,
            'total_bidon_mixte_global' => $total_bidon_mixte_global,
        ],
    
        'commandes_mixte' => $commandes_mixte,
        'bons_bidon'   => $bidons,
        'versements'   => $versements
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
