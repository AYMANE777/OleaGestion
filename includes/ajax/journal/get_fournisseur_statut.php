<?php
/**
 * Meknassa Olive - get_fournisseur_statut.php (version sans pagination)
 * Liste complète filtrable des règlements fournisseurs (sans limite).
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../config/config.ini.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // =======================
    // 🔹 Paramètres d’entrée
    // =======================
    $q = trim((string)($_POST['q'] ?? ''));
    $id_fournisseur = (int)($_POST['id_fournisseur'] ?? 0);
    $date_debut = trim((string)($_POST['date_debut'] ?? ''));
    $date_fin = trim((string)($_POST['date_fin'] ?? ''));

    // =======================
    // 🔹 Conditions SQL dynamiques
    // =======================
    $where = ["r.id_reglement > 0"];
    $types = '';
    $params = [];

    if ($id_fournisseur > 0) {
        $where[] = "r.id_fournisseur = ?";
        $params[] = $id_fournisseur;
        $types .= 'i';
    }

    if ($q !== '') {
        $where[] = "(r.ref_reglement LIKE ? OR f.nom_fournisseur LIKE ? OR f.prenom_fournisseur LIKE ?)";
        $like = "%$q%";
        $params = array_merge($params, [$like, $like, $like]);
        $types .= 'sss';
    }

    if ($date_debut && $date_fin) {
        $where[] = "DATE(r.date_ajout) BETWEEN ? AND ?";
        $params[] = $date_debut;
        $params[] = $date_fin;
        $types .= 'ss';
    } elseif ($date_debut) {
        $where[] = "DATE(r.date_ajout) >= ?";
        $params[] = $date_debut;
        $types .= 's';
    } elseif ($date_fin) {
        $where[] = "DATE(r.date_ajout) <= ?";
        $params[] = $date_fin;
        $types .= 's';
    }

    $whereSql = implode(' AND ', $where);

    // =======================
    // 🔹 Requête principale
    // =======================
    $sql = "
    SELECT 
        r.id_reglement,
        r.ref_reglement,
        r.date_ajout,
        r.situation,
        COALESCE(r.prix_total, 0) AS prix_total,
        COALESCE(f.nom_fournisseur, '') AS nom_fournisseur,
        COALESCE(f.prenom_fournisseur, '') AS prenom_fournisseur,
        (SELECT GROUP_CONCAT(DISTINCT img_facture)
           FROM image_reglement
          WHERE id_reglement = r.id_reglement) AS img_facture,
        (SELECT GROUP_CONCAT(DISTINCT img_justification)
           FROM paiement_reglement
          WHERE id_reglement = r.id_reglement
            AND img_justification IS NOT NULL) AS img_justification,
        (SELECT COALESCE(SUM(montant), 0)
           FROM paiement_reglement
          WHERE id_reglement = r.id_reglement) AS total_paye,
        (COALESCE(r.prix_total, 0) -
         (SELECT COALESCE(SUM(montant), 0)
            FROM paiement_reglement
           WHERE id_reglement = r.id_reglement)) AS reste_payer
    FROM reglement_fournisseur r
    LEFT JOIN fournisseur f ON f.id_fournisseur = r.id_fournisseur
    WHERE $whereSql
    ORDER BY r.date_ajout DESC, r.id_reglement DESC
    ";

    $stmt = $connect->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    // =======================
    // 🔹 Traitement des résultats
    // =======================
    $rows = [];
    $somme_payee = $somme_non_paye = $total_general = 0.0;

    while ($r = $res->fetch_assoc()) {
        $prix_total = (float)$r['prix_total'];
        $total_paye = (float)$r['total_paye'];
        $reste = (float)$r['reste_payer'];

        $total_general += $prix_total;
        $somme_payee += $total_paye;
        $somme_non_paye += $reste;

        $rows[] = [
            'id_reglement'      => (int)$r['id_reglement'],
            'ref'               => (string)($r['ref_reglement'] ?? ''),
            'date_fmt'          => $r['date_ajout'] ? date('d/m/Y H:i', strtotime($r['date_ajout'])) : '',
            'fournisseur'       => trim(($r['prenom_fournisseur'] ?? '') . ' ' . ($r['nom_fournisseur'] ?? '')),
            'situation'         => (string)($r['situation'] ?? ''),
            'prix_total'        => $prix_total,
            'total_paye'        => $total_paye,
            'reste_payer'       => $reste,
            'img_facture'       => (string)($r['img_facture'] ?? ''),
            'img_justification' => (string)($r['img_justification'] ?? '')
        ];
    }

    $stmt->close();

    // =======================
    // 🔹 Réponse JSON
    // =======================
    echo json_encode([
        'ok' => true,
        'total' => count($rows),
        'somme_payee' => round($somme_payee, 2),
        'somme_non_paye' => round($somme_non_paye, 2),
        'total_general' => round($total_general, 2),
        'rows' => $rows
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur : ' . $e->getMessage()]);
    exit;
}
