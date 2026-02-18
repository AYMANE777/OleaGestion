<?php
/**
 * Meknassa Olive - get_bidon_huile_statut.php
 * Retourne les bons d’huile filtrés par client, date, type (huile / huile+bidon)
 * et statut de paiement (payé / non payé)
 * ➕ Calcule aussi :
 *  - Total bidons
 *  - Total général = huile + bidons
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
    // === PARAMÈTRES D’ENTRÉE ===
    $id_client = (int)($_POST['id_client'] ?? 0);
    $date_debut = trim($_POST['date_debut'] ?? '');
    $date_fin   = trim($_POST['date_fin'] ?? '');
    $type_commande = trim($_POST['type_commande'] ?? 'tous');   // 'tous', 'huile', 'huile_bidon'
    $statut_paiement = trim($_POST['statut_paiement'] ?? 'tous'); // 'tous', 'paye', 'non_paye'

    // === CONDITIONS SQL ===
    $where = ["1=1"];
    $params = [];
    $types = "";

    if ($id_client > 0) {
        $where[] = "b.client_id = ?";
        $params[] = $id_client;
        $types .= "i";
    }

    if (!empty($date_debut) && !empty($date_fin)) {
        $where[] = "DATE(b.date_bon) BETWEEN ? AND ?";
        $params[] = $date_debut;
        $params[] = $date_fin;
        $types .= "ss";
    } elseif (!empty($date_debut)) {
        $where[] = "DATE(b.date_bon) >= ?";
        $params[] = $date_debut;
        $types .= "s";
    } elseif (!empty($date_fin)) {
        $where[] = "DATE(b.date_bon) <= ?";
        $params[] = $date_fin;
        $types .= "s";
    }

    // === FILTRE TYPE COMMANDE ===
    if ($type_commande === 'huile') {
        $where[] = "b.id NOT IN (SELECT DISTINCT d.id_bon FROM details_bon_huile d)";
    } elseif ($type_commande === 'huile_bidon') {
        $where[] = "b.id IN (SELECT DISTINCT d.id_bon FROM details_bon_huile d)";
    }

    // === FILTRE STATUT DE PAIEMENT ===
    if ($statut_paiement === 'paye') {
        $where[] = "(LOWER(b.mode_paiement_huile) IN ('espece','cash','virement','chèque','cheque','payé','paye'))";
    } elseif ($statut_paiement === 'non_paye') {
        $where[] = "(LOWER(b.mode_paiement_huile) IN ('credit','non payé','non paye','à crédit','a credit'))";
    }

    $whereSql = implode(" AND ", $where);

    // === REQUÊTE PRINCIPALE ===
    $sql = "
        SELECT 
            b.id              AS id_bon,
            CONCAT(c.prenom, ' ', c.nom) AS client,
            c.id              AS id_client,
            c.ville,
            b.date_bon,
            b.quantite,
            b.prix_unitaire,
            b.prix_net,
            b.provider,
            b.mode_paiement_huile,
            b.note,
            DATE_FORMAT(b.created_at, '%d/%m/%Y %H:%i') AS date_fmt
        FROM bon_de_huile b
        JOIN client c ON c.id = b.client_id
        WHERE $whereSql
        ORDER BY b.date_bon DESC
    ";

    $stmt = $connect->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $quantite_totale = $somme_totale = $somme_payee = $somme_non_paye = $total_bidon = 0.0;

    while ($r = $res->fetch_assoc()) {
        $id_bon = (int)$r['id_bon'];

        // === Détails des bidons associés ===
        $stmt2 = $connect->prepare("
            SELECT 
                d.id_detail,
                bd.nom_bidon,
                bd.type_bidon,
                d.quantite_bidon,
                bd.prix_bidon
            FROM details_bon_huile d
            LEFT JOIN bidons bd ON bd.id_bidon = d.id_bidon
            WHERE d.id_bon = ?
        ");
        $stmt2->bind_param("i", $id_bon);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $details = $res2->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        // 🔸 Totaux bidons
        $total_bidon_bon = 0;
        $total_prix_bidon_bon = 0;
        foreach ($details as $d) {
            $q = (float)($d['quantite_bidon'] ?? 0);
            $p = (float)($d['prix_bidon'] ?? 0);
            $total_bidon_bon += $q;
            $total_prix_bidon_bon += $q * $p;
        }

        $total_bidon += $total_bidon_bon;

        // 🔸 Totaux huile
        $prix_huile = (float)$r['prix_net'];
        $quantite = (float)$r['quantite'];

        // 🔸 Montant total (huile + bidon)
        $prix_total_bon = $prix_huile + $total_prix_bidon_bon;

        $somme_totale += $prix_total_bon;
        $quantite_totale += $quantite;

        $mode = strtolower(trim($r['mode_paiement_huile'] ?? ''));

// 🔹 On uniformise les accents et espaces
$mode = str_replace(['é', 'è', 'ê'], 'e', $mode);
$mode = preg_replace('/\s+/', ' ', $mode); // nettoie espaces multiples

// 🔹 Liste de mots-clés considérés comme "payé"
$keywords_paye = ['espece', 'en espece', 'cash', 'virement', 'cheque', 'paye', 'payé', 'deja paye', 'deja payé'];

$is_paye = false;
foreach ($keywords_paye as $mot) {
    if (strpos($mode, $mot) !== false) {
        $is_paye = true;
        break;
    }
}

if ($is_paye) {
    $somme_payee += $prix_total_bon;
} else {
    $somme_non_paye += $prix_total_bon;
}


        $rows[] = [
            'id_bon'        => $id_bon,
            'client'        => $r['client'],
            'ville'         => $r['ville'],
            'date_bon'      => $r['date_fmt'],
            'quantite'      => $quantite,
            'prix_unitaire' => (float)$r['prix_unitaire'],
            'prix_net'      => $prix_total_bon, // ✅ total avec bidons
            'provider'      => $r['provider'],
            'mode_paiement' => $r['mode_paiement_huile'],
            'note'          => $r['note'],
            'details_bidons'=> $details ?? [],
            'total_bidon_bon' => $total_bidon_bon,
            'total_prix_bidon_bon' => $total_prix_bidon_bon
        ];
    }
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'rows' => $rows,
        'quantite_totale' => round($quantite_totale, 2),
        'somme_totale' => round($somme_totale, 2),
        'somme_payee' => round($somme_payee, 2),
        'somme_non_paye' => round($somme_non_paye, 2),
        'total_bidon' => round($total_bidon, 2)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}
