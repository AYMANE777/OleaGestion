<?php
require_once(__DIR__ . '/../../../../config/config.ini.php');

if (!isset($_POST['id_taratonage'])) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$id = intval($_POST['id_taratonage']);

$query = $connect->prepare("
    SELECT id_taratonage, poid_brut, poid_tarat, poid_net, note
    FROM taratonage_olive
    WHERE id_taratonage = ?
");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

echo json_encode($data ?: []);
