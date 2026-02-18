<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');


$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = max(1, intval($_GET['perPage'] ?? 25));
$q       = trim($_GET['q'] ?? '');
$offset  = ($page - 1) * $perPage;

$where   = '';
$params  = [];
$types   = '';

if ($q !== '') {
    $where = "WHERE (c.nom LIKE CONCAT('%', ?, '%') 
              OR c.prenom LIKE CONCAT('%', ?, '%') 
              OR c.telephone LIKE CONCAT('%', ?, '%'))";
    $params = [$q, $q, $q];
    $types = 'sss';
}

// === TOTAL DES ENTRÉES ===
$total = 0;
$countSql = "
    SELECT COUNT(*)
    FROM taratonage_client_olive_credit t
    JOIN clientolive c ON c.id_client_olive = t.id_client_olive
    $where
";
$stmt = $connect->prepare($countSql);
if ($q !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

// === SOMME TOTALE DES MONTANTS ===
$sumTotal = 0;
$sumSql = "
    SELECT SUM(t.montant_total)
    FROM taratonage_client_olive_credit t
    JOIN clientolive c ON c.id_client_olive = t.id_client_olive
    $where
";
$stmt = $connect->prepare($sumSql);
if ($q !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($sumTotal);
$stmt->fetch();
$stmt->close();

// === DONNÉES PAGINÉES ===
$sql = "
    SELECT 
        c.id_client_olive,
        t.id_taratonage,
        t.poid_net, t.prix_unitaire, t.montant_total, t.note, 
        t.scanne_bon,
        t.provider,
        DATE_FORMAT(t.date_taratonage, '%d/%m/%Y %H:%i') AS date_taratonage,
        CONCAT(c.nom, ' ', c.prenom) AS client_nom
    FROM taratonage_client_olive_credit t
    JOIN clientolive c ON c.id_client_olive = t.id_client_olive
    $where
    ORDER BY t.date_taratonage DESC
    LIMIT ?, ?
";
$stmt = $connect->prepare($sql);
if ($q !== '') {
    $params[] = $offset;
    $params[] = $perPage;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$baseUrl = '/gestionMeknassa/uploads/taratonage-Client-Credit/';
$serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";

while ($r = $result->fetch_assoc()) {
    $r['id_client_olive'] = (int)$r['id_client_olive'];
    $r['provider'] = htmlspecialchars($r['provider'] ?? '', ENT_QUOTES, 'UTF-8');
    $r['montant_total'] = number_format((float)$r['montant_total'], 2, '.', ' ');
    $r['prix_unitaire'] = number_format((float)$r['prix_unitaire'], 2, '.', ' ');
    $r['poid_net']      = number_format((float)$r['poid_net'], 2, '.', ' ');
    $r['scanne_bon_url'] = $serverUrl . $baseUrl . $r['scanne_bon'];
    $rows[] = $r;
}

echo json_encode([
    'total'        => $total,
    'somme_totale' => number_format((float)$sumTotal, 2, '.', ' '),
    'rows'         => $rows
], JSON_UNESCAPED_UNICODE);
