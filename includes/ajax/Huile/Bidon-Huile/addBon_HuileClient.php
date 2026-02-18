<?php
header('Content-Type: application/json; charset=utf-8');

$response = ['status'=>'error','message'=>'Unknown error'];

try {
    require_once '../../../../config/config.ini.php';
    $connect->set_charset('utf8mb4');


    function type_to_liters($t) {
        if (!$t) return 0.0;
        if (preg_match('/(\d+(?:\.\d+)?)\s*L/i', $t, $m)) return (float)$m[1];
        return 0.0;
    }


    $client_id          = trim($_POST['List_client_name'] ?? '');
    $quantite_litre     = trim($_POST['quantite_en_litre'] ?? '');
    $prix_unitaire      = trim($_POST['prix_unitaire'] ?? '');
    $prix_net_posted    = trim($_POST['prix_net'] ?? '');
    $provider           = trim($_POST['provider_of_price'] ?? '');
    $mode_paiement_huile= strtoupper(trim($_POST['mode_paiement_huile'] ?? ''));
    $note               = trim($_POST['note_bon_huile'] ?? '');
    $statut_commande    = isset($_POST['statut_commande']) ? strtoupper(trim($_POST['statut_commande'])) : '';

 
    if (!in_array($statut_commande, ['PAYE', 'CREDIT', 'NON PAYE'], true)) {
        throw new Exception("Veuillez sélectionner le statut de la commande (Payé ou Crédit).");
    }


    $cin_huil          = trim($_POST['cin'] ?? '');
    $matricule_voiture = trim($_POST['matricule'] ?? '');

  
 $vehicule_img_path = null;

if (isset($_FILES['image_voiture']) && $_FILES['image_voiture']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image_voiture']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erreur upload image véhicule : " . $_FILES['image_voiture']['error']);
    }

    // Définir chemin absolu (local + serveur)
    $uploadDir = __DIR__ . '/../../../../uploads/vehicule-route/';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception("Impossible de créer le dossier : " . $uploadDir);
        }
    }

    $extV = strtolower(pathinfo($_FILES['image_voiture']['name'], PATHINFO_EXTENSION));
    if (!in_array($extV, ['jpg','jpeg','png'])) {
        throw new Exception("Image véhicule: format jpg/png requis.");
    }

    $fnameV = sprintf('vehicule_%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(3)), $extV);
    $fsPathV = $uploadDir . $fnameV;

    // Déplacement du fichier
    if (!move_uploaded_file($_FILES['image_voiture']['tmp_name'], $fsPathV)) {
        throw new Exception("move_uploaded_file échoué → " . $fsPathV);
    }

    // Chemin public
    $vehicule_img_path = '/uploads/vehicule-route/' . $fnameV;
}



    $detail_types  = $_POST['detail_type']  ?? [];
    $detail_counts = $_POST['detail_count'] ?? [];
    if (!is_array($detail_types))  $detail_types = [];
    if (!is_array($detail_counts)) $detail_counts = [];

    $lines = [];
    foreach ($detail_types as $i => $t) {
        $t   = strtoupper(trim($t ?? ''));
        $cnt = (int)($detail_counts[$i] ?? 0);
        if ($t !== '' && $cnt > 0) {
            if (!isset($lines[$t])) $lines[$t] = 0;
            $lines[$t] += $cnt;
        }
    }

    if ($client_id === '' || $provider === '') {
        throw new Exception("Champs obligatoires manquants (client ou validé par).");
    }


    $q_global   = ($quantite_litre === '') ? 0.0 : (float)$quantite_litre;
    $pu         = ($prix_unitaire  === '') ? 0.0 : (float)$prix_unitaire;
    $litres_from_lines = 0.0;
    foreach ($lines as $type => $cnt)
        $litres_from_lines += type_to_liters($type) * $cnt;
    $quantite_final_litres = $q_global > 0 ? $q_global : $litres_from_lines;
    $expected_net_from_oil = round($pu * $quantite_final_litres, 2);


    if ($statut_commande === 'CREDIT' || $statut_commande === 'NON PAYE') {
        $mode_paiement_huile = 'CREDIT';
        $justif_path = null;
    } else {
        if ($mode_paiement_huile === '')
            throw new Exception("Veuillez choisir un mode de paiement pour un bon payé.");

        $modes_justif = ['CHEQUE','VIREMENT','LCN'];
        $justif_path = null;

        if (in_array($mode_paiement_huile, $modes_justif, true)) {
            if (!isset($_FILES['image_justification']) ||
                $_FILES['image_justification']['error'] === UPLOAD_ERR_NO_FILE)
                throw new Exception("Une justification de paiement est obligatoire pour le mode $mode_paiement_huile.");

            $publicDirJ = '/gestionMeknassa/uploads/huil-justif/';
            $fsDirJ = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $publicDirJ;
            if (!is_dir($fsDirJ)) mkdir($fsDirJ, 0755, true);
            $extJ = strtolower(pathinfo($_FILES['image_justification']['name'], PATHINFO_EXTENSION));
            if (!in_array($extJ, ['jpg','jpeg','png']))
                throw new Exception("Justification: format jpg/png requis.");
            $fnameJ = sprintf('justif_%s.%s', date('Ymd_His').'_' . bin2hex(random_bytes(3)), $extJ);
            $fsPathJ = $fsDirJ . $fnameJ;
            if (!move_uploaded_file($_FILES['image_justification']['tmp_name'], $fsPathJ))
                throw new Exception("Échec upload justification.");
            $justif_path = $publicDirJ . $fnameJ;
        }
    }


    $connect->begin_transaction();


    if (!empty($lines)) {
        foreach ($lines as $type => $cntNeeded) {
            $sql = "SELECT COALESCE(SUM(quantite_bidon),0) AS dispo
                    FROM bidons
                    WHERE UPPER(TRIM(type_bidon)) = UPPER(TRIM(?))
                    FOR UPDATE";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('s', $type);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (($row['dispo'] ?? 0) < $cntNeeded)
                throw new Exception("Stock insuffisant pour le type $type");
        }
    }

    $sql_bon = "INSERT INTO bon_de_huile
        (`client_id`, `quantite`, `date_bon`, `prix_unitaire`, `prix_net`, `provider`,
         `mode_paiement_huile`, `justif-paiment`, `note`, `cin-huil`, `matricul-voiture`, `image-voiture`, `created_at`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $connect->prepare($sql_bon);
    $date_bon = date("Y-m-d");
    $stmt->bind_param(
        "sdssdsssssss",
        $client_id,
        $quantite_final_litres,
        $date_bon,
        $pu,
        $expected_net_from_oil,
        $provider,
        $mode_paiement_huile,
        $justif_path,
        $note,
        $cin_huil,
        $matricule_voiture,
        $vehicule_img_path
    );
    $stmt->execute();
    $id_bon = $stmt->insert_id;
    $stmt->close();


    if (!empty($lines)) {
        foreach ($lines as $type => $cnt) {

            $stmtB = $connect->prepare("SELECT id_bidon, prix_bidon FROM bidons WHERE UPPER(TRIM(type_bidon)) = UPPER(TRIM(?)) LIMIT 1");
            $stmtB->bind_param('s', $type);
            $stmtB->execute();
            $bidon = $stmtB->get_result()->fetch_assoc();
            $stmtB->close();

            if (!$bidon) {
                throw new Exception("Type de bidon '$type' introuvable dans la table bidons.");
            }


            $stmtD = $connect->prepare("INSERT INTO details_bon_huile (id_bon, id_bidon, quantite_bidon) VALUES (?, ?, ?)");
            $stmtD->bind_param('iii', $id_bon, $bidon['id_bidon'], $cnt);
            $stmtD->execute();
            $stmtD->close();


        }
    }


    $dailyToken = function_exists('global_24h_token') ? global_24h_token() : bin2hex(random_bytes(4));
$duree_expiration = date('Y-m-d H:i:s', time() + 48 * 3600);


    $commentaire = 'Bon de route généré le ' . date('Y-m-d H:i:s');
    $statut = 'ACTIVE';

    $sql_br = "INSERT INTO bon_rue (ref_bon_huile, ref_bon_bidon, statut, random_daily_value, commentaire, duree, created_at)
               VALUES (?, NULL, ?, ?, ?, ?, NOW())";
    $stmt = $connect->prepare($sql_br);
    $stmt->bind_param('issss', $id_bon, $statut, $dailyToken, $commentaire, $duree_expiration);
    $stmt->execute();
    $id_bon_rue = $stmt->insert_id;
    $stmt->close();


    $domain = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
    $qrContent = $domain . "/gestionMeknassa/bon_rue_status.php?id=" . $id_bon_rue;
    $qrApiUrl  = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($qrContent);
    $publicDir = '/gestionMeknassa/uploads/rue-qr/';
    $fsDir     = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $publicDir;
    if (!is_dir($fsDir)) mkdir($fsDir, 0755, true);
    $fname     = sprintf('bonrue_%d_%s.png', $id_bon_rue, date('Ymd_His'));
    $fsPath    = $fsDir . $fname;
    $publicPath= $publicDir . $fname;
    $img = @file_get_contents($qrApiUrl);
    if ($img && strlen($img) > 50) file_put_contents($fsPath, $img);

 
    $stmt = $connect->prepare("UPDATE bon_rue SET `qr-rue` = ? WHERE id_bon_rue = ?");
    $stmt->bind_param('si', $publicPath, $id_bon_rue);
    $stmt->execute();
    $stmt->close();

    $connect->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Bon ajouté avec succès.',
        'id_bon_huile' => $id_bon,
        'id_bon_rue' => $id_bon_rue,
        'qr_path' => $publicPath,
        'qr_url' => $qrContent,
        'cin' => $cin_huil,
        'matricule' => $matricule_voiture,
        'vehicule_image' => $vehicule_img_path,
        'quantite_finale_litres' => $quantite_final_litres,
        'mode_paiement' => $mode_paiement_huile,
        'justif_path' => $justif_path
    ]);

} catch (Exception $e) {
    if (isset($connect)) @$connect->rollback();
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}