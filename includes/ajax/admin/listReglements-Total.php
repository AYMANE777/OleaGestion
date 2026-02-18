<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../config/config.ini.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // === PARAMÈTRES REQUÊTE ===
    $q = trim((string)($_POST['q'] ?? ''));
    $page = max(1, (int)($_POST['page'] ?? 1));
    $perRaw = strtolower(trim((string)($_POST['per_page'] ?? '10')));
    $allowed = ['10', '25', '50', '100', 'all'];
    if (!in_array($perRaw, $allowed, true)) $perRaw = '10';

    // === CONDITIONS DE RECHERCHE ===
    $where = ["r.id_reglement > 0"];
    $types = '';
    $params = [];

    if ($q !== '') {
        $where[] = "(r.ref_reglement LIKE ? OR f.nom_fournisseur LIKE ? OR f.prenom_fournisseur LIKE ?)";
        $like = "%$q%";
        $params = [$like, $like, $like];
        $types = 'sss';
    }
    $whereSql = implode(' AND ', $where);

    // === COMPTE TOTAL ===
    $sqlCount = "
        SELECT COUNT(DISTINCT r.id_reglement) AS total
        FROM reglement_fournisseur r
        LEFT JOIN fournisseur f ON f.id_fournisseur = r.id_fournisseur
        WHERE $whereSql
    ";
    $stmt = $connect->prepare($sqlCount);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // === PAGINATION ===
    if ($perRaw === 'all') {
        $limit = max(1, $total);
        $offset = 0;
        $lastPage = 1;
        $page = 1;
    } else {
        $perPage = (int)$perRaw;
        $limit = $perPage;
        $lastPage = max(1, ceil($total / $perPage));
        if ($page > $lastPage) $page = $lastPage;
        $offset = ($page - 1) * $perPage;
    }
    $from = $total ? ($offset + 1) : 0;
    $to = $total ? min($offset + $limit, $total) : 0;

    // === RÉCUPÉRATION DES LIGNES ===
    $sql = "
        SELECT 
            r.id_reglement,
            r.ref_reglement,
            r.date_ajout,
            r.situation,
            r.prix_total,
            COALESCE(f.nom_fournisseur, '') AS nom_fournisseur,
            COALESCE(f.prenom_fournisseur, '') AS prenom_fournisseur,
            COALESCE(p.payment_modes, '-') AS payment_mode,
            COALESCE(i.img_facture, '') AS img_facture
        FROM reglement_fournisseur r
        LEFT JOIN fournisseur f ON f.id_fournisseur = r.id_fournisseur
        LEFT JOIN (
            SELECT id_reglement, GROUP_CONCAT(DISTINCT payment_mode SEPARATOR ', ') AS payment_modes
            FROM paiement_reglement
            GROUP BY id_reglement
        ) p ON p.id_reglement = r.id_reglement
        LEFT JOIN (
            SELECT id_reglement, MAX(img_facture) AS img_facture
            FROM image_reglement
            GROUP BY id_reglement
        ) i ON i.id_reglement = r.id_reglement
        WHERE $whereSql
        ORDER BY r.date_ajout DESC, r.id_reglement DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $connect->prepare($sql);
    if ($types) {
        $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'id_reglement' => (int)$r['id_reglement'],
            'ref'          => (string)($r['ref_reglement'] ?? ''),
            'date_fmt'     => $r['date_ajout'] ? date('d/m/Y H:i', strtotime($r['date_ajout'])) : '',
            'fournisseur'  => trim(($r['prenom_fournisseur'] ?? '') . ' ' . ($r['nom_fournisseur'] ?? '')),
            'situation'    => (string)($r['situation'] ?? ''),
            'payment_mode' => (string)($r['payment_mode'] ?? ''),
            'prix_total'   => (float)($r['prix_total'] ?? 0),
            'img_facture'  => (string)($r['img_facture'] ?? '')
        ];
    }
    $stmt->close();

    // === CALCUL DES TOTAUX (inclut Avance) ===
    $sqlTotals = "
        SELECT
            ROUND(SUM(CASE 
                WHEN LOWER(TRIM(r.situation)) IN ('payé','paye','payee','payée') THEN r.prix_total ELSE 0 END),2) AS somme_payee,
            ROUND(SUM(CASE 
                WHEN LOWER(TRIM(r.situation)) IN ('non payé','non paye','non payee','non payée') THEN r.prix_total ELSE 0 END),2) AS somme_non_payee,
            ROUND(SUM(CASE 
                WHEN LOWER(TRIM(r.situation)) = 'avance' THEN r.prix_total ELSE 0 END),2) AS somme_avance,
            ROUND(SUM(COALESCE(r.prix_total,0)),2) AS total_general
        FROM reglement_fournisseur r
        LEFT JOIN fournisseur f ON f.id_fournisseur = r.id_fournisseur
        WHERE $whereSql
    ";
    $stmt = $connect->prepare($sqlTotals);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totals = $stmt->get_result()->fetch_assoc() ?: [
        'somme_payee' => 0,
        'somme_non_payee' => 0,
        'somme_avance' => 0,
        'total_general' => 0
    ];
    $stmt->close();

    $somme_payee = (float)$totals['somme_payee'];
    $somme_non_payee = (float)$totals['somme_non_payee'];
    $somme_avance = (float)$totals['somme_avance'];
    $total_general = (float)$totals['total_general'];

    // === POURCENTAGES ===
    $pct_payee = $total_general > 0 ? round(($somme_payee / $total_general) * 100, 2) : 0;
    $pct_non_payee = $total_general > 0 ? round(($somme_non_payee / $total_general) * 100, 2) : 0;
    $pct_avance = $total_general > 0 ? round(($somme_avance / $total_general) * 100, 2) : 0;

    // === SORTIE JSON ===
    echo json_encode([
        'ok' => true,
        'total' => $total,
        'from' => $from,
        'to' => $to,
        'page' => $page,
        'per_page' => $perRaw,
        'last_page' => $lastPage,
        'rows' => $rows,
        'somme_payee' => number_format($somme_payee, 2, '.', ''),
        'somme_non_payee' => number_format($somme_non_payee, 2, '.', ''),
        'somme_avance' => number_format($somme_avance, 2, '.', ''),
        'total_general' => number_format($total_general, 2, '.', ''),
        'pourcentage_payee' => $pct_payee,
        'pourcentage_non_payee' => $pct_non_payee,
        'pourcentage_avance' => $pct_avance
    ]);

    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}
