<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../../config/config.ini.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect->set_charset('utf8mb4');

    $id = (int)($_POST['id_reglement'] ?? 0);
    $new_situation = trim((string)($_POST['is_paye'] ?? ''));

    if ($id <= 0) throw new Exception("ID invalide.");
    if ($new_situation === '') throw new Exception("Situation requise.");

    // === Récupération du règlement ===
    $stmt = $connect->prepare("SELECT situation, prix_total FROM reglement_fournisseur WHERE id_reglement = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($situation, $prix_total);
    $stmt->fetch();
    $stmt->close();

    if (!$situation) throw new Exception("Règlement introuvable.");

    $current = strtolower(trim($situation));
    if (in_array($current, ['payé', 'paye'])) {
        throw new Exception("Ce règlement est déjà marqué comme 'Payé'.");
    }


    $stmt = $connect->prepare("SELECT COALESCE(SUM(montant),0) FROM paiement_reglement WHERE id_reglement = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($total_paye);
    $stmt->fetch();
    $stmt->close();

    $prix_total = (float)$prix_total;
    $total_paye = (float)$total_paye;
    $reste = $prix_total - $total_paye;

    if ($reste <= 0) throw new Exception("Ce règlement est déjà totalement payé.");


    $uploadDir = __DIR__ . '/../../uploads/justification_paiment/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);


    function savePaiement($connect, $id, $mode, $montant, $fileTmp, $fileName, $fileErr, $fileSize, $uploadDir)
    {
        $img_name = null;
        if (in_array($mode, ['Cheque', 'Virement', 'LCN'], true)) {
            if ($fileErr === UPLOAD_ERR_NO_FILE) throw new Exception("Justificatif obligatoire pour le mode $mode.");
            if ($fileErr !== UPLOAD_ERR_OK) throw new Exception("Erreur d’upload du justificatif ($mode).");
            if ($fileSize > 5 * 1024 * 1024) throw new Exception("Fichier justificatif trop volumineux (max 5 MB).");

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) throw new Exception("Type de fichier non autorisé ($mode).");

            $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
            $final = $safe . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($fileTmp, $uploadDir . $final)) throw new Exception("Échec du transfert justificatif ($mode).");
            $img_name = $final;
        }

        $stmt = $connect->prepare("INSERT INTO paiement_reglement (date_ajout, payment_mode, montant, img_justification, id_reglement)
                                   VALUES (NOW(), ?, ?, ?, ?)");
        $stmt->bind_param('sdsi', $mode, $montant, $img_name, $id);
        $stmt->execute();
        $stmt->close();
    }


    if ($new_situation === 'Payé') {
        $montants = array_map('floatval', $_POST['paiements_paye_montant'] ?? []);
        $modes = $_POST['paiements_paye_mode'] ?? [];
        $justifs = $_FILES['paiements_paye_justif'] ?? ['name'=>[], 'tmp_name'=>[], 'error'=>[], 'size'=>[]];

        if (empty($montants)) throw new Exception("Aucun paiement saisi.");
        $total_saisi = array_sum($montants);


        if (abs($total_saisi - $reste) > 0.001) {
            throw new Exception("Le montant total des paiements ($total_saisi MAD) doit être égal au reste à payer ($reste MAD).");
        }


        foreach ($modes as $i => $mode) {
            $montant = $montants[$i] ?? 0;
            if ($montant <= 0) continue;
            savePaiement($connect, $id, $mode, $montant,
                $justifs['tmp_name'][$i] ?? null,
                $justifs['name'][$i] ?? '',
                $justifs['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                $justifs['size'][$i] ?? 0,
                $uploadDir
            );
        }


        $stmt = $connect->prepare("UPDATE reglement_fournisseur SET situation='Payé' WHERE id_reglement=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'ok'=>true,
            'status'=>'success',
            'message'=>"Paiement complet enregistré. Règlement marqué comme 'Payé'.",
            'auto_paye'=>true
        ]);
        exit;
    }


    if ($new_situation === 'Avance') {
        $montants = array_map('floatval', $_POST['paiements_avance_montant'] ?? []);
        $modes = $_POST['paiements_avance_mode'] ?? [];
        $justifs = $_FILES['paiements_avance_justif'] ?? ['name'=>[], 'tmp_name'=>[], 'error'=>[], 'size'=>[]];

        if (empty($montants)) throw new Exception("Aucune avance saisie.");
        $total_saisi = array_sum($montants);


        if ($total_saisi >= $reste) {
            throw new Exception("Le montant de l’avance ($total_saisi MAD) doit être inférieur au reste à payer ($reste MAD).");
        }


        foreach ($modes as $i => $mode) {
            $montant = $montants[$i] ?? 0;
            if ($montant <= 0) continue;
            savePaiement($connect, $id, $mode, $montant,
                $justifs['tmp_name'][$i] ?? null,
                $justifs['name'][$i] ?? '',
                $justifs['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                $justifs['size'][$i] ?? 0,
                $uploadDir
            );
        }


        $stmt = $connect->prepare("UPDATE reglement_fournisseur SET situation='Avance' WHERE id_reglement=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'ok'=>true,
            'status'=>'success',
            'message'=>"Avance enregistrée avec succès. Situation : Avance.",
            'auto_paye'=>false
        ]);
        exit;
    }

    throw new Exception("Situation non gérée.");

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'=>false,
        'status'=>'error',
        'message'=>$e->getMessage(),
        'trace'=>$e->getFile().':'.$e->getLine()
    ]);
    exit;
}