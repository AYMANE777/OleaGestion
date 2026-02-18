<?php
// AJAX endpoint: ajoute un fournisseur et renvoie du JSON
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    // ⚠️ Ajuste le chemin si ton fichier n'est pas dans /inventaire/
    require_once __DIR__ . '/../../config/config.ini.php'; // doit définir $connect = new mysqli(...)

    // (Optionnel mais recommandé)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    // Récupération + normalisation
    $nom    = trim($_POST['nom_fournisseur']     ?? '');
    $prenom = trim($_POST['prenom_fournisseur']  ?? '');
    $tel    = trim($_POST['num_fournisseur']     ?? '');
    $addr   = trim($_POST['adress_fournisseur']  ?? '');

    $nom    = strtoupper($nom);
    $prenom = ucfirst(strtolower($prenom));
    $tel    = preg_replace('/\s+/', '', $tel);

    // Validations serveur
    if ($nom === '' || $prenom === '' || $tel === '' || $addr === '') {
        throw new Exception("Tous les champs sont obligatoires.");
    }
    if (!preg_match('/^[0-9]{10}$/', $tel)) {
        throw new Exception("Le numéro de téléphone doit contenir exactement 10 chiffres.");
    }

    // Insertion
    $sql = "INSERT INTO fournisseur
            (nom_fournisseur, prenom_fournisseur, tele_fournisseur, adress_fournisseur, date_ajout)
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation SQL : " . $connect->error);
    }

    $stmt->bind_param('ssss', $nom, $prenom, $tel, $addr);
    $stmt->execute();

    $response['status']  = 'success';
    $response['message'] = 'Fournisseur ajouté avec succès.';
    $response['id']      = $connect->insert_id;

    $stmt->close();
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
