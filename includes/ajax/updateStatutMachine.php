<?php
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    require_once __DIR__ . '/../../config/config.ini.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $id   = (int)($_POST['id_machine'] ?? 0);
    $stat = trim($_POST['statut'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($id <= 0) {
        throw new Exception("Identifiant machine invalide.");
    }

    $allowed = ['en_marche','en_panne','en_maintenance'];
    if (!in_array($stat, $allowed, true)) {
        throw new Exception("Statut invalide.");
    }

    $desc = strip_tags($desc);
    if ($desc === '') {
        throw new Exception("La description est obligatoire.");
    }
    if (mb_strlen($desc) > 500) {
        throw new Exception("La description ne doit pas dépasser 500 caractères.");
    }
    $chk = $connect->prepare("SELECT 1 FROM machine WHERE id_machine = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        throw new Exception("Machine introuvable.");
    }
    $chk->close();
    $sql = "INSERT INTO statut_machine (date_ajout, statut, description, id_machine)
            VALUES (NOW(), ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation SQL : " . $connect->error);
    }
    $stmt->bind_param("ssi", $stat, $desc, $id);
    $stmt->execute();

    $response['status']  = 'success';
    $response['message'] = 'Statut enregistré avec succès.';
    $response['payload'] = [
        'id_machine' => $id,
        'statut'     => $stat,
        'description'=> $desc,
        'date_ajout' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
