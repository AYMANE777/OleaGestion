<?php
header('Content-Type: application/json; charset=utf-8');
$response = ['status' => 'error', 'message' => 'Unknown error'];
 try{
      require_once '../../../../config/config.ini.php'; // adjust if needed


    // Collect fields
        $Fchauffeur     = trim($_POST['List_F_chauffeur']            ?? '');
        $Fchauffeur_Vehicule    = trim($_POST['F_chauffeur_Vehicule']            ?? '');
        $poid_Taratone  = trim($_POST['poid_taratone']         ?? '');
        $poid_brut  = trim($_POST['poid_brut']         ?? '');
        $poid_net    = trim($_POST['poid_net']  ?? '');
        $note    = trim($_POST['note']  ?? '');
       
 // Basic server-side validation
    if ($Fchauffeur === '' || $Fchauffeur_Vehicule === '' || $poid_Taratone === '' || $poid_brut === '' || $poid_net === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

    
  




 // Insert using prepared statement
    $sql = "INSERT INTO taratonage (poid_brut, poid_tarat, poid_net, note, id_vehicule_Fitor)
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
        $Fchauffeur_Vehicule,
    );

    if (!$stmt->execute()) {
        throw new Exception("Insertion échouée : " . $stmt->error);
    }

    $response['status']  = "success";
    $response['message'] = "Taratonage ajouté avec succès.";
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