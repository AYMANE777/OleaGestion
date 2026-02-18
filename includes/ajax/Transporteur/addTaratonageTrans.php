<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];
 try{
           require_once '../../../config/config.ini.php'; // adjust if needed


    // Collect fields
        $transporteur     = trim($_POST['List_transporteur']            ?? '');
        $vehicule    = trim($_POST['Transporteur_Vehicule']            ?? '');
        $poid_Taratone  = trim($_POST['poid_taratone']         ?? '');
        $poid_brut  = trim($_POST['poid_brut']         ?? '');
        $poid_net    = trim($_POST['poid_net']  ?? '');
        $note    = trim($_POST['note']  ?? '');
       
 // Basic server-side validation
    if ($transporteur === '' || $vehicule === '' || $poid_Taratone === '' || $poid_brut === '' || $poid_net === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

    
  




 // Insert using prepared statement
    $sql = "INSERT INTO taratonage_olive (poid_brut, poid_tarat, poid_net, note, id_vehicule_trans)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Préparation SQL a échoué : " . $connect->error);
    }

    // bind_param types: s = string; phone likely string
    $stmt->bind_param(
        "sssss",
        $poid_brut,
        $poid_Taratone,
        $poid_net,
        $note,
        $vehicule,
    );

    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $response['status']  = "success";
    $response['message'] = "Taratonage Olive ajouté avec succès.";
    $response['id']      = $stmt->insert_id;

    $stmt->close();
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>