<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    // ── CONFIG DB ────────────────────────────────────────────────
    require_once '../../../config/config.ini.php';
    if (!isset($connect) || !$connect instanceof mysqli) {
        throw new Exception("Connexion DB introuvable. Vérifie config.ini.php (\$connect mysqli attendu).");
    }

    // ── RÉCUPÉRATION DES CHAMPS ──────────────────────────────────
    $nom_client     = trim($_POST['nom_transporteur'] ?? '');
    $prenom_client  = trim($_POST['prenom_transporteur'] ?? '');
    $tel_client     = trim($_POST['num_tel_transporteur'] ?? '');
    $address_source = trim($_POST['adresse_source'] ?? '');

    if ($nom_client === '' || $prenom_client === '' || $tel_client === '' || $address_source === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

    // ── UPLOAD IMAGE TRANSPORTEUR ────────────────────────────────
    $imagePathForDb = null;
    if (isset($_FILES['image_transporteur']) && $_FILES['image_transporteur']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image_transporteur']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $origName = $_FILES['image_transporteur']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                throw new Exception("Types autorisés : JPG, JPEG, PNG, GIF, WEBP.");
            }

            $targetDirFs  = __DIR__ . '/../../../uploads/image/Transporteur/';
            $targetDirRel = 'uploads/image/Transporteur/';
            if (!is_dir($targetDirFs) && !mkdir($targetDirFs, 0755, true)) {
                throw new Exception("Impossible de créer le dossier d'upload pour l'image.");
            }

            $base = pathinfo($origName, PATHINFO_FILENAME);
            $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
            $unique = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $newName = $safeBase . '_' . $unique . '.' . $ext;
            $targetFs = $targetDirFs . $newName;

            if (!move_uploaded_file($_FILES['image_transporteur']['tmp_name'], $targetFs)) {
                throw new Exception("Erreur lors du déplacement du fichier uploadé.");
            }

            $imagePathForDb = $targetDirRel . $newName;
        } else {
            throw new Exception("Erreur d’upload (code " . (int)$_FILES['image_transporteur']['error'] . ").");
        }
    }

    // ── INSERT TRANSPORTEUR AVEC qr_path VIDE ─────────────────────
    $sql = "INSERT INTO transporteur 
        (Adresse_Source, nom_trans, prenom_trans, telephone_trans, image_trans, qr_path)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $connect->prepare($sql);
$qr_placeholder = ''; // vide au départ

$stmt->bind_param(
    "ssssss",
    $address_source,
    $nom_client,
    $prenom_client,
    $tel_client,
    $imagePathForDb,
    $qr_placeholder
);


    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $newId = (int)$connect->insert_id;
    $stmt->close();

    // ── GÉNÉRATION DU QR CODE ────────────────────────────────────
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'meknassaolive.com';
    $baseStatusUrl = $scheme . '://' . $host . '/gestionMeknassa/transporteur_status.php?id=';

    $qrDirFs  = __DIR__ . '/../../../uploads/transporteur-qr/';
    $qrDirRel = 'uploads/transporteur-qr/';
    if (!is_dir($qrDirFs) && !mkdir($qrDirFs, 0755, true)) {
        throw new Exception("Impossible de créer le dossier pour QRs : {$qrDirFs}");
    }

    $qrFilename = "qr_{$newId}_" . date('Ymd_His') . ".png";
    $qrFileFs = $qrDirFs . $qrFilename;
    $qrFileRel = $qrDirRel . $qrFilename;
    $content = $baseStatusUrl . $newId;

    $generated = false;
    $lastError = '';

    // Méthode 1: librairie Endroid si dispo
    $vendorAutoload1 = __DIR__ . '/../../../vendor/autoload.php';
    $vendorAutoload2 = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($vendorAutoload1) || file_exists($vendorAutoload2)) {
        $autoload = file_exists($vendorAutoload1) ? $vendorAutoload1 : $vendorAutoload2;
        require_once $autoload;
        try {
            if (class_exists('\Endroid\QrCode\Builder\Builder')) {
                $result = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data($content)
                    ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->margin(10)
                    ->size(350)
                    ->build();

                $result->saveToFile($qrFileFs);
                $generated = true;
            }
        } catch (\Throwable $e) {
            $lastError = 'Endroid error: ' . $e->getMessage();
            $generated = false;
        }
    }

    // Méthode 2: Fallback via API distante
    if (!$generated) {
        $encoded = rawurlencode($content);
        $chartUrl = "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data={$encoded}";

        $img = false;
        if (ini_get('allow_url_fopen')) {
            $opts = ['http' => ['timeout' => 10, 'header' => "User-Agent: PHP\r\n"]];
            $context = stream_context_create($opts);
            $img = @file_get_contents($chartUrl, false, $context);
        }
        if ($img === false) {
            $ch = curl_init($chartUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            $img = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);
            if ($img === false || $httpCode !== 200) {
                throw new Exception("Impossible de générer le QR (HTTP {$httpCode}) : {$curlErr}");
            }
        }
        if (!file_put_contents($qrFileFs, $img)) {
            throw new Exception("Impossible d'enregistrer le QR sur le serveur.");
        }
        $generated = true;
    }

    if (!$generated) {
        throw new Exception("Échec génération QR. {$lastError}");
    }

    // ── UPDATE qr_path ────────────────────────────────────────────
    $update = $connect->prepare("UPDATE transporteur SET qr_path = ? WHERE id_transporteur = ?");
    if (!$update) {
        @unlink($qrFileFs);
        throw new Exception("Préparation SQL (update qr_path) a échoué : " . $connect->error);
    }

    $update->bind_param("si", $qrFileRel, $newId);
    if (!$update->execute()) {
        @unlink($qrFileFs);
        throw new Exception("Mise à jour qr_path échouée : " . $update->error);
    }
    $update->close();

    // ── RÉPONSE SUCCESS ───────────────────────────────────────────
    echo json_encode([
        'status' => 'success',
        'message' => 'Transporteur ajouté et QR généré avec succès.',
        'id' => $newId,
        'qr_path' => $qrFileRel
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
