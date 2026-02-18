<?php
// AJAX: ajoute une avance et renvoie JSON
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../config/config.ini.php'; // ajuste le chemin si besoin

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // Récupération + nettoyage
    $id_ouvrier = (int)($_POST['ouvrier_id'] ?? 0);
    $montant    = trim((string)($_POST['avance'] ?? ''));
    $motif      = trim((string)($_POST['motif'] ?? ''));

    // normaliser montant (virgule -> point)
    $montant = str_replace(',', '.', $montant);
    $montant = (float)$montant;

    // validations
    if ($id_ouvrier <= 0) {
        throw new InvalidArgumentException('Ouvrier invalide.');
    }
    if (!is_numeric($montant) || $montant <= 0) {
        throw new InvalidArgumentException('Montant invalide (doit être > 0).');
    }
    // motif peut être vide

    // Optional: verify that the ouvrier exists
    $chkSql = "SELECT id_ouvrier FROM ouvrier WHERE id_ouvrier = ? LIMIT 1";
    $stmt = $connect->prepare($chkSql);
    if (!$stmt) throw new RuntimeException('Erreur préparation SQL (check ouvrier): ' . $connect->error);
    $stmt->bind_param('i', $id_ouvrier);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || !$res->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Ouvrier introuvable.']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert
    $sql = "INSERT INTO avance (montant, Motif, id_ouvrier, date_ajout)
            VALUES (?, ?, ?, NOW())";
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new RuntimeException('Erreur préparation SQL (insert): ' . $connect->error);
    $stmt->bind_param('dsi', $montant, $motif, $id_ouvrier); // d = double (float), s = string, i = int
    $stmt->execute();

    $insertId = $connect->insert_id;
    $stmt->close();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Avance enregistrée avec succès.',
        'id'      => $insertId,
    ]);
    exit;

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    exit;
}
