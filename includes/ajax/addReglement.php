<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.ini.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {

    $MAX_PIECES     = 50;
    $MAX_IMAGES     = 8;
    $MAX_FILE_BYTES = 20 * 1024 * 1024;
    $ALLOWED_MIMES  = ['image/jpeg', 'image/png', 'image/jpg'];

    $DIR_FACTURE = __DIR__ . '/../../uploads/reglements/';
    $DIR_JUSTIF  = __DIR__ . '/../../uploads/justification_paiment/';

   
    $ref            = trim($_POST['reference_facture'] ?? '');
    $id_fournisseur = (int)($_POST['id_fournisseur'] ?? 0);
    $situation      = trim($_POST['is_paye'] ?? 'Non payé');
    $motif          = trim($_POST['motif'] ?? '');

    if ($ref === '') throw new InvalidArgumentException('Référence facture manquante.');
    if ($id_fournisseur <= 0) throw new InvalidArgumentException('Fournisseur invalide.');
    if (!in_array($situation, ['Non payé', 'Payé', 'Avance'], true))
        throw new InvalidArgumentException('Situation invalide.');

    $uploadFile = function(array $file, string $destDir, string $prefix)
    use ($ALLOWED_MIMES, $MAX_FILE_BYTES) {

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK)
            throw new RuntimeException('Erreur upload fichier.');

        if ($file['size'] > $MAX_FILE_BYTES)
            throw new RuntimeException('Fichier trop volumineux (max ' . round($MAX_FILE_BYTES/1048576,1) . ' MB).');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $ALLOWED_MIMES, true))
            throw new RuntimeException('Format non autorisé (JPG/PNG uniquement).');

        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        $ext  = $mime === 'image/png' ? 'png' : 'jpg';
        $name = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $destDir . $name;

        if (!move_uploaded_file($file['tmp_name'], $path))
            throw new RuntimeException('Échec déplacement fichier uploadé.');

        return $name;
    };

    if (empty($_FILES['image_facture']['name'][0]))
        throw new InvalidArgumentException('Image(s) de facture obligatoire(s).');

    $factureFiles = [];
    foreach ($_FILES['image_facture']['name'] as $i => $name) {
        if ($i >= $MAX_IMAGES) break;
        if ($name === '') continue;
        $file = [
            'name'     => $name,
            'type'     => $_FILES['image_facture']['type'][$i],
            'tmp_name' => $_FILES['image_facture']['tmp_name'][$i],
            'error'    => $_FILES['image_facture']['error'][$i],
            'size'     => $_FILES['image_facture']['size'][$i]
        ];
        $factureFiles[] = $uploadFile($file, $DIR_FACTURE, 'facture');
    }


    $pieces = $_POST['pieces'] ?? [];
    if (!is_array($pieces) || count($pieces) === 0)
        throw new InvalidArgumentException('Au moins une pièce est requise.');
    if (count($pieces) > $MAX_PIECES)
        throw new InvalidArgumentException("Nombre de pièces > $MAX_PIECES.");

    $prix_total  = 0.0;
    $cleanPieces = [];
    foreach ($pieces as $idx => $p) {
        $nom   = trim($p['nom'] ?? '');
        $prix  = (float)($p['prix'] ?? 0);
        $qte   = (int)($p['qte'] ?? 1);
        if ($nom === '' || $prix <= 0 || $qte <= 0)
            throw new InvalidArgumentException('Données pièce invalides (ligne #' . ($idx+1) . ').');
        $prix_total += $prix * $qte;
        $cleanPieces[] = ['nom'=>$nom, 'prix'=>$prix, 'qte'=>$qte];
    }

    // === 5️⃣ Paiements (Payé / Avance) ===
    $keySituation = '';
    if ($situation === 'Payé')   $keySituation = 'paye';
    if ($situation === 'Avance') $keySituation = 'avance';

    $paiements_mode    = [];
    $paiements_montant = [];
    $paiements_justif  = null;

    if ($keySituation !== '') {
        $paiements_mode    = $_POST["paiements_{$keySituation}_mode"] ?? [];
        $paiements_montant = $_POST["paiements_{$keySituation}_montant"] ?? [];
        $paiements_justif  = $_FILES["paiements_{$keySituation}_justif"] ?? null;

        if (!is_array($paiements_mode) || count($paiements_mode) === 0)
            throw new InvalidArgumentException($situation === 'Payé' ? 'Aucun paiement ajouté.' : 'Aucune avance ajoutée.');

        if (count($paiements_mode) !== count($paiements_montant))
            throw new InvalidArgumentException('Incohérence des lignes de paiement (mode/montant).');

        $total_paye = 0.0;

        foreach ($paiements_mode as $i => $modeRaw) {
            $mode    = trim($modeRaw ?? '');
            $montant = (float)($paiements_montant[$i] ?? 0);
            if ($mode === '')  throw new InvalidArgumentException("Mode de paiement manquant (#" . ($i + 1) . ").");
            if ($montant <= 0) throw new InvalidArgumentException("Montant invalide (#" . ($i + 1) . ").");

            $total_paye += $montant;

            if (in_array($mode, ['Cheque', 'Virement', 'LCN'], true)) {
                if (
                    !$paiements_justif ||
                    empty($paiements_justif['name'][$i]) ||
                    !isset($paiements_justif['error'][$i]) ||
                    $paiements_justif['error'][$i] !== UPLOAD_ERR_OK
                ) {
                    throw new InvalidArgumentException("Justificatif obligatoire pour le paiement #" . ($i + 1) . " (mode $mode)");
                }
            }
        }


        if ($situation === 'Avance') {
            if ($total_paye > $prix_total) {
                throw new InvalidArgumentException("Vérifiez vos calculs : le total des avances ({$total_paye} DH) dépasse le total général ({$prix_total} DH).");
            }
            if (abs($total_paye - $prix_total) < 0.01) {
                throw new InvalidArgumentException("Ce n’est pas une avance : le total des avances est égal au total général. Marquez cette facture comme 'Payé'.");
            }
        }

        if ($situation === 'Payé') {
            if (abs($total_paye - $prix_total) > 0.01) {
                throw new InvalidArgumentException("Le total des paiements ({$total_paye} DH) est différent du total général ({$prix_total} DH). Vérifiez vos montants.");
            }
        }
    }


    $connect->begin_transaction();

    $stmt = $connect->prepare("
        INSERT INTO reglement_fournisseur (date_ajout, id_fournisseur, ref_reglement, situation, prix_total, motif)
        VALUES (NOW(), ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('issds', $id_fournisseur, $ref, $situation, $prix_total, $motif);
    $stmt->execute();
    $id_reglement = $stmt->insert_id;
    $stmt->close();

    $stmtImg = $connect->prepare("
        INSERT INTO image_reglement (date_ajout, img_facture, id_reglement)
        VALUES (NOW(), ?, ?)
    ");
    foreach ($factureFiles as $img) {
        $stmtImg->bind_param('si', $img, $id_reglement);
        $stmtImg->execute();
    }
    $stmtImg->close();

    if ($keySituation !== '') {
        $stmtPay = $connect->prepare("
            INSERT INTO paiement_reglement (date_ajout, payment_mode, montant, img_justification, id_reglement)
            VALUES (NOW(), ?, ?, ?, ?)
        ");
        foreach ($paiements_mode as $i => $modeRaw) {
            $mode    = trim($modeRaw ?? '');
            $montant = (float)($paiements_montant[$i] ?? 0);
            $justif  = null;

            if (in_array($mode, ['Cheque','Virement','LCN'], true)) {
                $f = [
                    'name'     => $paiements_justif['name'][$i],
                    'type'     => $paiements_justif['type'][$i],
                    'tmp_name' => $paiements_justif['tmp_name'][$i],
                    'error'    => $paiements_justif['error'][$i],
                    'size'     => $paiements_justif['size'][$i],
                ];
                $justif = $uploadFile($f, $DIR_JUSTIF, 'justif');
            }

            $stmtPay->bind_param('sdsi', $mode, $montant, $justif, $id_reglement);
            $stmtPay->execute();
        }
        $stmtPay->close();
    }

    $stmtPiece = $connect->prepare("
        INSERT INTO piece_reglement (date_ajout, nom, prix, quantite, id_reglement)
        VALUES (NOW(), ?, ?, ?, ?)
    ");
    foreach ($cleanPieces as $p) {
        $stmtPiece->bind_param('sdii', $p['nom'], $p['prix'], $p['qte'], $id_reglement);
        $stmtPiece->execute();
    }
    $stmtPiece->close();

    $connect->commit();

    echo json_encode([
        'status'      => 'success',
        'message'     => 'Règlement enregistré avec succès.',
        'id_reglement'=> $id_reglement,
        'prix_total'  => $prix_total
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($connect->errno) $connect->rollback();
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
