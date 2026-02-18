<?php
require_once '../../../config/config.ini.php';
header('Content-Type: application/json; charset=utf-8');
$id = intval($_GET['id'] ?? 0);
$stmt = $connect->prepare("SELECT * FROM clientolive WHERE id_client_olive = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
echo json_encode($stmt->get_result()->fetch_assoc() ?: []);
?>
