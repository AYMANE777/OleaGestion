<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(fn($s,$m,$f,$l)=>throw new ErrorException($m,0,$s,$f,$l));

try {
  $action = $_POST['action'] ?? '';
  $id_bon = (int)($_POST['id_bon'] ?? 0);
  $id_detail = (int)($_POST['id_detail'] ?? 0);
  $id_bidon = (int)($_POST['id_bidon'] ?? 0);
  $quantite = (float)($_POST['quantite'] ?? 0);

  if ($id_bon <= 0) throw new Exception("ID du bon invalide.");

  $connect->begin_transaction();

  if ($action === 'update') {
    // --- update quantite
    $res = $connect->query("SELECT id_bidon, quantite_bidon FROM Details_bon_huile WHERE id_detail=$id_detail FOR UPDATE");
    if ($res->num_rows===0) throw new Exception("Ligne introuvable.");
    $old = $res->fetch_assoc();
    $id_bidon = (int)$old['id_bidon'];
    $oldQ = (float)$old['quantite_bidon'];
    $diff = $quantite - $oldQ;

    $res2 = $connect->query("SELECT quantite_bidon FROM bidons WHERE id_bidon=$id_bidon FOR UPDATE");
    $stock = (float)$res2->fetch_assoc()['quantite_bidon'];
    if ($diff>0 && $stock<$diff) throw new Exception("Stock insuffisant.");

    // $connect->query("UPDATE bidons SET quantite_bidon=quantite_bidon-$diff WHERE id_bidon=$id_bidon");
    $connect->query("UPDATE Details_bon_huile SET quantite_bidon=$quantite WHERE id_detail=$id_detail");

    $connect->commit();
    echo json_encode(['ok'=>true,'msg'=>'Quantité modifiée.']);
    exit;
  }

  if ($action === 'delete') {
    // --- delete line and restore stock
    $res = $connect->query("SELECT id_bidon, quantite_bidon FROM Details_bon_huile WHERE id_detail=$id_detail FOR UPDATE");
    if ($res->num_rows===0) throw new Exception("Ligne introuvable.");
    $old = $res->fetch_assoc();
    // $connect->query("UPDATE bidons SET quantite_bidon=quantite_bidon+{$old['quantite_bidon']} WHERE id_bidon={$old['id_bidon']}");
    $connect->query("DELETE FROM Details_bon_huile WHERE id_detail=$id_detail");
    $connect->commit();
    echo json_encode(['ok'=>true,'msg'=>'Ligne supprimée.']);
    exit;
  }

  if ($action === 'add') {
    // --- add new bidon type to bon
    if ($id_bidon<=0 || $quantite<=0) throw new Exception("Paramètres invalides.");
    $res = $connect->query("SELECT quantite_bidon FROM bidons WHERE id_bidon=$id_bidon FOR UPDATE");
    if ($res->num_rows===0) throw new Exception("Bidon introuvable.");
    $stock = (float)$res->fetch_assoc()['quantite_bidon'];
    if ($stock<$quantite) throw new Exception("Stock insuffisant.");

    // $connect->query("UPDATE bidons SET quantite_bidon=quantite_bidon-$quantite WHERE id_bidon=$id_bidon");
    $connect->query("INSERT INTO Details_bon_huile (id_bon,id_bidon,quantite_bidon) VALUES ($id_bon,$id_bidon,$quantite)");
    $connect->commit();
    echo json_encode(['ok'=>true,'msg'=>'Ligne ajoutée.']);
    exit;
  }

  throw new Exception("Action inconnue.");

} catch(Throwable $e){
  if(isset($connect)) @$connect->rollback();
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
