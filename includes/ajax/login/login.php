<?php
header('Content-Type: application/json; charset=utf-8');

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    require_once(__DIR__ . '/../../../config/config.ini.php');

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        throw new Exception("Veuillez saisir l'email et le mot de passe.");
    }

    // Prépare la requête sécurisée
    $sql = "SELECT id, email, password, status FROM user WHERE email = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur SQL : " . $connect->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Utilisateur introuvable.");
    }

    $user = $result->fetch_assoc();

    // Vérification du mot de passe (hashé ou non)
    if ($user['password'] !== $password && !password_verify($password, $user['password'])) {
        throw new Exception("Mot de passe incorrect.");
    }

    // Connexion OK — start session before any output
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['status']  = $user['status'];

    $stmt->close();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Connexion réussie.',
        'role'    => $user['status']
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
