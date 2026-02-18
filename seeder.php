<?php
/**
 * Database Seeder — gestion_meknassa
 * Run via: http://localhost/gestionMeknassa/seeder.php
 * Or CLI:  php seeder.php
 */

require_once __DIR__ . '/config/config.ini.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
    DB_USERNAME, DB_PASSWORD,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$log = [];

function truncate(PDO $pdo, string $table): void {
    $pdo->exec("TRUNCATE TABLE `$table`");
}

function insert(PDO $pdo, string $table, array $rows, array &$log): void {
    if (!$rows) return;
    $cols  = array_keys($rows[0]);
    $ph    = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
    $sql   = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES $ph";
    $stmt  = $pdo->prepare($sql);
    foreach ($rows as $row) {
        $stmt->execute(array_values($row));
    }
    $log[] = "✔ $table — " . count($rows) . " rows inserted";
}

/* ============================================================
   ORDER: independent tables first, then FK-dependants
   ============================================================ */

// ── 1. fournisseur ───────────────────────────────────────────
truncate($pdo, 'fournisseur');
$fournisseurs = [
    ['nom_fournisseur'=>'Bennani',   'prenom_fournisseur'=>'Ahmed',   'tele_fournisseur'=>'0661234501','adress_fournisseur'=>'Rue Hassan II, Meknès',    'date_ajout'=>'2024-01-05 08:00:00'],
    ['nom_fournisseur'=>'El Alaoui', 'prenom_fournisseur'=>'Youssef', 'tele_fournisseur'=>'0661234502','adress_fournisseur'=>'Avenue Mohammed V, Fès',   'date_ajout'=>'2024-01-10 09:00:00'],
    ['nom_fournisseur'=>'Tazi',      'prenom_fournisseur'=>'Khalid',  'tele_fournisseur'=>'0661234503','adress_fournisseur'=>'Bd Zerktouni, Casablanca',  'date_ajout'=>'2024-02-01 10:00:00'],
    ['nom_fournisseur'=>'Cherkaoui', 'prenom_fournisseur'=>'Omar',    'tele_fournisseur'=>'0661234504','adress_fournisseur'=>'Quartier Industriel, Rabat','date_ajout'=>'2024-02-15 11:00:00'],
    ['nom_fournisseur'=>'Idrissi',   'prenom_fournisseur'=>'Rachid',  'tele_fournisseur'=>'0661234505','adress_fournisseur'=>'Zone Franche, Tanger',      'date_ajout'=>'2024-03-01 08:30:00'],
];
insert($pdo, 'fournisseur', $fournisseurs, $log);

// ── 2. ouvrier ───────────────────────────────────────────────
truncate($pdo, 'ouvrier');
$ouvriers = [
    ['prenom'=>'Mohamed',  'nom'=>'Amine',    'cin'=>'AB123456','img_cin'=>'','image'=>'','tele'=>'0661100001','fonction'=>'Presseur',     'adress'=>'Hay Salam, Meknès',    'date_ajout'=>'2024-01-15 08:00:00'],
    ['prenom'=>'Hassan',   'nom'=>'Karimi',   'cin'=>'CD234567','img_cin'=>'','image'=>'','tele'=>'0661100002','fonction'=>'Décanteur',    'adress'=>'Hay Hamria, Meknès',   'date_ajout'=>'2024-01-20 08:00:00'],
    ['prenom'=>'Yassine',  'nom'=>'Belkadi',  'cin'=>'EF345678','img_cin'=>'','image'=>'','tele'=>'0661100003','fonction'=>'Manutentionnaire','adress'=>'Rue Riad, Meknès', 'date_ajout'=>'2024-02-01 08:00:00'],
    ['prenom'=>'Rachid',   'nom'=>'Bouhali',  'cin'=>'GH456789','img_cin'=>'','image'=>'','tele'=>'0661100004','fonction'=>'Chauffeur',    'adress'=>'Hay Zitoune, Meknès',  'date_ajout'=>'2024-02-10 08:00:00'],
    ['prenom'=>'Aziz',     'nom'=>'Mennani',  'cin'=>'IJ567890','img_cin'=>'','image'=>'','tele'=>'0661100005','fonction'=>'Électricien',  'adress'=>'Avenue de France, Meknès','date_ajout'=>'2024-03-01 08:00:00'],
    ['prenom'=>'Fatima',   'nom'=>'Zouiten',  'cin'=>'KL678901','img_cin'=>'','image'=>'','tele'=>'0661100006','fonction'=>'Caissière',    'adress'=>'Bd Al Amir, Meknès',   'date_ajout'=>'2024-03-15 08:00:00'],
    ['prenom'=>'Nadia',    'nom'=>'Tlemcani', 'cin'=>'MN789012','img_cin'=>'','image'=>'','tele'=>'0661100007','fonction'=>'Secrétaire',   'adress'=>'Rue Ibn Khaldoun, Meknès','date_ajout'=>'2024-04-01 08:00:00'],
    ['prenom'=>'Khalid',   'nom'=>'Filali',   'cin'=>'OP890123','img_cin'=>'','image'=>'','tele'=>'0661100008','fonction'=>'Presseur',     'adress'=>'Hay Salam, Meknès',    'date_ajout'=>'2024-04-15 08:00:00'],
];
insert($pdo, 'ouvrier', $ouvriers, $log);

// ── 3. avance ────────────────────────────────────────────────
truncate($pdo, 'avance');
$avances = [
    ['montant'=>500.00,  'Motif'=>'Frais médicaux',      'date_ajout'=>'2024-02-10 10:00:00','id_ouvrier'=>1],
    ['montant'=>300.00,  'Motif'=>'Loyer',                'date_ajout'=>'2024-02-20 11:00:00','id_ouvrier'=>2],
    ['montant'=>1000.00, 'Motif'=>'Achat véhicule',       'date_ajout'=>'2024-03-05 09:00:00','id_ouvrier'=>1],
    ['montant'=>250.00,  'Motif'=>'Urgence familiale',    'date_ajout'=>'2024-03-15 10:00:00','id_ouvrier'=>3],
    ['montant'=>700.00,  'Motif'=>'Scolarité enfants',    'date_ajout'=>'2024-04-01 08:30:00','id_ouvrier'=>4],
    ['montant'=>400.00,  'Motif'=>'Réparation maison',    'date_ajout'=>'2024-04-20 09:00:00','id_ouvrier'=>5],
    ['montant'=>150.00,  'Motif'=>'Dépenses personnelles','date_ajout'=>'2024-05-01 10:00:00','id_ouvrier'=>6],
    ['montant'=>600.00,  'Motif'=>'Mariage',              'date_ajout'=>'2024-05-15 11:00:00','id_ouvrier'=>7],
    ['montant'=>800.00,  'Motif'=>'Achat électroménager', 'date_ajout'=>'2024-06-01 09:30:00','id_ouvrier'=>8],
    ['montant'=>350.00,  'Motif'=>'Frais de déplacement', 'date_ajout'=>'2024-06-20 10:00:00','id_ouvrier'=>2],
];
insert($pdo, 'avance', $avances, $log);

// ── 4. client ────────────────────────────────────────────────
truncate($pdo, 'client');
$clients = [
    ['nom'=>'Amrani',   'prenom'=>'Samir',    'image_client'=>null,'phone'=>'0662000001','address'=>'Hay Al Wahda, Meknès',    'ville'=>'Meknès',       'created_at'=>'2024-01-10 08:00:00'],
    ['nom'=>'Berrada',  'prenom'=>'Imane',    'image_client'=>null,'phone'=>'0662000002','address'=>'Rue Sidi Bouzid, Fès',    'ville'=>'Fès',          'created_at'=>'2024-01-15 09:00:00'],
    ['nom'=>'Chraibi',  'prenom'=>'Tarik',    'image_client'=>null,'phone'=>'0662000003','address'=>'Bd Lalla Yacout, Casablanca','ville'=>'Casablanca', 'created_at'=>'2024-02-01 10:00:00'],
    ['nom'=>'Douiri',   'prenom'=>'Houda',    'image_client'=>null,'phone'=>'0662000004','address'=>'Avenue Hassan I, Rabat',  'ville'=>'Rabat',        'created_at'=>'2024-02-10 11:00:00'],
    ['nom'=>'El Fassi', 'prenom'=>'Karim',    'image_client'=>null,'phone'=>'0662000005','address'=>'Quartier Al Amal, Meknès','ville'=>'Meknès',       'created_at'=>'2024-03-01 08:00:00'],
    ['nom'=>'Ghali',    'prenom'=>'Souad',    'image_client'=>null,'phone'=>'0662000006','address'=>'Rue Ibn Battouta, Tanger','ville'=>'Tanger',       'created_at'=>'2024-03-15 09:30:00'],
    ['nom'=>'Hajji',    'prenom'=>'Brahim',   'image_client'=>null,'phone'=>'0662000007','address'=>'Hay Nassim, Meknès',      'ville'=>'Meknès',       'created_at'=>'2024-04-01 10:00:00'],
    ['nom'=>'Idrissi',  'prenom'=>'Zineb',    'image_client'=>null,'phone'=>'0662000008','address'=>'Avenue Allal Ben Abdallah, Meknès','ville'=>'Meknès','created_at'=>'2024-04-15 11:00:00'],
    ['nom'=>'Jabri',    'prenom'=>'Mourad',   'image_client'=>null,'phone'=>'0662000009','address'=>'Bd Mohammed VI, Meknès',  'ville'=>'Meknès',       'created_at'=>'2024-05-01 09:00:00'],
    ['nom'=>'Kettani',  'prenom'=>'Asmaa',    'image_client'=>null,'phone'=>'0662000010','address'=>'Hay Salam, Meknès',       'ville'=>'Meknès',       'created_at'=>'2024-05-20 10:00:00'],
];
insert($pdo, 'client', $clients, $log);

// ── 5. bidons ────────────────────────────────────────────────
truncate($pdo, 'bidons');
$bidons = [
    ['nom_bidon'=>'5L',   'type_bidon'=>'5L',   'prix_bidon'=>10.00,'quantite_bidon'=>200,'bidon_created_at'=>'2024-01-01 08:00:00'],
    ['nom_bidon'=>'10L',  'type_bidon'=>'10L',  'prix_bidon'=>18.00,'quantite_bidon'=>150,'bidon_created_at'=>'2024-01-01 08:00:00'],
    ['nom_bidon'=>'20L',  'type_bidon'=>'20L',  'prix_bidon'=>30.00,'quantite_bidon'=>100,'bidon_created_at'=>'2024-01-01 08:00:00'],
    ['nom_bidon'=>'30L',  'type_bidon'=>'30L',  'prix_bidon'=>42.00,'quantite_bidon'=>80, 'bidon_created_at'=>'2024-01-01 08:00:00'],
    ['nom_bidon'=>'50L',  'type_bidon'=>'50L',  'prix_bidon'=>65.00,'quantite_bidon'=>60, 'bidon_created_at'=>'2024-01-01 08:00:00'],
];
insert($pdo, 'bidons', $bidons, $log);

// ── 6. transporteur ──────────────────────────────────────────
truncate($pdo, 'transporteur');
$transporteurs = [
    ['nom_trans'=>'Alaoui',   'prenom_trans'=>'Hamid',   'telephone_trans'=>'0663000001','image_trans'=>null,'Adresse_Source'=>'Douar Oulad Said, Meknès', 'created_at'=>'2024-01-05 08:00:00','qr_path'=>''],
    ['nom_trans'=>'Bouazza',  'prenom_trans'=>'Mustapha','telephone_trans'=>'0663000002','image_trans'=>null,'Adresse_Source'=>'Sidi Slimane, Meknès',      'created_at'=>'2024-01-10 09:00:00','qr_path'=>''],
    ['nom_trans'=>'Chaib',    'prenom_trans'=>'Ali',      'telephone_trans'=>'0663000003','image_trans'=>null,'Adresse_Source'=>'El Hajeb, Meknès',          'created_at'=>'2024-02-01 10:00:00','qr_path'=>''],
    ['nom_trans'=>'Darouich', 'prenom_trans'=>'Saïd',    'telephone_trans'=>'0663000004','image_trans'=>null,'Adresse_Source'=>'Azrou, Ifrane',             'created_at'=>'2024-02-15 11:00:00','qr_path'=>''],
    ['nom_trans'=>'El Yazami','prenom_trans'=>'Nabil',   'telephone_trans'=>'0663000005','image_trans'=>null,'Adresse_Source'=>'Ain Taoujdate, Meknès',     'created_at'=>'2024-03-01 08:00:00','qr_path'=>''],
];
insert($pdo, 'transporteur', $transporteurs, $log);

// ── 7. patron ────────────────────────────────────────────────
truncate($pdo, 'patron');
$patrons = [
    ['nom_patron'=>'Fitori',  'prenom_patron'=>'Hassan',   'telephone_patron'=>'0664000001','created_at'=>'2024-01-01 08:00:00','image_Patron'=>null],
    ['nom_patron'=>'Lamrani', 'prenom_patron'=>'Mohammed', 'telephone_patron'=>'0664000002','created_at'=>'2024-01-10 08:00:00','image_Patron'=>null],
    ['nom_patron'=>'Soussi',  'prenom_patron'=>'Driss',    'telephone_patron'=>'0664000003','created_at'=>'2024-02-01 08:00:00','image_Patron'=>null],
];
insert($pdo, 'patron', $patrons, $log);

// ── 8. chauffeur (FK → patron) ───────────────────────────────
truncate($pdo, 'chauffeur');
$chauffeurs = [
    ['nom'=>'Bakkali', 'prenom'=>'Abdellah','telephone'=>'0665000001','image_Fchauffeur'=>null,'created_at'=>'2024-01-10 08:00:00','id_patron'=>1],
    ['nom'=>'Chaoui',  'prenom'=>'Ilyass',  'telephone'=>'0665000002','image_Fchauffeur'=>null,'created_at'=>'2024-01-15 09:00:00','id_patron'=>1],
    ['nom'=>'Daoudi',  'prenom'=>'Amine',   'telephone'=>'0665000003','image_Fchauffeur'=>null,'created_at'=>'2024-02-01 10:00:00','id_patron'=>2],
    ['nom'=>'Ennaciri','prenom'=>'Samir',   'telephone'=>'0665000004','image_Fchauffeur'=>null,'created_at'=>'2024-02-10 11:00:00','id_patron'=>2],
    ['nom'=>'Filali',  'prenom'=>'Younes',  'telephone'=>'0665000005','image_Fchauffeur'=>null,'created_at'=>'2024-03-01 08:00:00','id_patron'=>3],
];
insert($pdo, 'chauffeur', $chauffeurs, $log);

// ── 9. vehicule (FK → chauffeur) ─────────────────────────────
truncate($pdo, 'vehicule');
$vehicules = [
    ['nom_vehicule'=>'Camion 1','type'=>'Camion','matricule'=>'12345-أ-1','image_vehicule'=>null,'assurance'=>'AXA','date_expiration'=>'2025-12-31','couleur'=>'Blanc', 'created_at'=>'2024-01-15 08:00:00','updated_at'=>null,'id_Fchauffeur'=>1],
    ['nom_vehicule'=>'Camion 2','type'=>'Camion','matricule'=>'23456-ب-2','image_vehicule'=>null,'assurance'=>'WAFA','date_expiration'=>'2025-11-30','couleur'=>'Rouge', 'created_at'=>'2024-01-20 09:00:00','updated_at'=>null,'id_Fchauffeur'=>2],
    ['nom_vehicule'=>'Fourgon 1','type'=>'Fourgon','matricule'=>'34567-ج-3','image_vehicule'=>null,'assurance'=>'RMA','date_expiration'=>'2025-10-31','couleur'=>'Bleu',  'created_at'=>'2024-02-05 10:00:00','updated_at'=>null,'id_Fchauffeur'=>3],
    ['nom_vehicule'=>'Camion 3','type'=>'Camion','matricule'=>'45678-د-4','image_vehicule'=>null,'assurance'=>'AXA','date_expiration'=>'2026-01-31','couleur'=>'Gris',  'created_at'=>'2024-02-15 11:00:00','updated_at'=>null,'id_Fchauffeur'=>4],
    ['nom_vehicule'=>'Tracteur','type'=>'Tracteur','matricule'=>'56789-ه-5','image_vehicule'=>null,'assurance'=>'SAHAM','date_expiration'=>'2025-09-30','couleur'=>'Jaune', 'created_at'=>'2024-03-01 09:00:00','updated_at'=>null,'id_Fchauffeur'=>5],
];
insert($pdo, 'vehicule', $vehicules, $log);

// ── 10. vehiculetrans (FK → transporteur) ────────────────────
truncate($pdo, 'vehiculetrans');
$vehiculestrans = [
    ['nom_vehi_trans'=>'Camion T1','type'=>'Camion','matricule'=>'60001-ت-1','image_vehicule_trans'=>null,'assurance'=>'AXA', 'date_expiration'=>'2025-12-31','couleur'=>'Blanc', 'created_at'=>'2024-01-10 08:00:00','updated_at'=>null,'id_transporteur'=>1],
    ['nom_vehi_trans'=>'Camion T2','type'=>'Camion','matricule'=>'60002-ث-2','image_vehicule_trans'=>null,'assurance'=>'WAFA','date_expiration'=>'2025-10-31','couleur'=>'Rouge', 'created_at'=>'2024-01-15 09:00:00','updated_at'=>null,'id_transporteur'=>2],
    ['nom_vehi_trans'=>'Camion T3','type'=>'Camion','matricule'=>'60003-خ-3','image_vehicule_trans'=>null,'assurance'=>'RMA', 'date_expiration'=>'2025-08-31','couleur'=>'Gris',  'created_at'=>'2024-02-01 10:00:00','updated_at'=>null,'id_transporteur'=>3],
    ['nom_vehi_trans'=>'Fourgon T1','type'=>'Fourgon','matricule'=>'60004-ذ-4','image_vehicule_trans'=>null,'assurance'=>'AXA','date_expiration'=>'2026-02-28','couleur'=>'Bleu',  'created_at'=>'2024-02-15 11:00:00','updated_at'=>null,'id_transporteur'=>4],
    ['nom_vehi_trans'=>'Camion T4','type'=>'Camion','matricule'=>'60005-ز-5','image_vehicule_trans'=>null,'assurance'=>'SAHAM','date_expiration'=>'2025-11-30','couleur'=>'Noir',  'created_at'=>'2024-03-01 09:00:00','updated_at'=>null,'id_transporteur'=>5],
];
insert($pdo, 'vehiculetrans', $vehiculestrans, $log);

// ── 11. taratonage (FK → vehicule) ───────────────────────────
truncate($pdo, 'taratonage');
$taratonages = [];
$vehiculeIds = [1,2,3,4,5];
$dates = ['2024-10-01','2024-10-05','2024-10-08','2024-10-12','2024-10-15','2024-10-18','2024-10-22','2024-10-25','2024-10-28','2024-11-01','2024-11-05','2024-11-10','2024-11-15','2024-11-20','2024-11-25'];
foreach ($dates as $i => $d) {
    $brut = round(mt_rand(2500,4000) + mt_rand(0,99)/100, 2);
    $tarat = round(mt_rand(200,400) + mt_rand(0,99)/100, 2);
    $taratonages[] = [
        'poid_brut' => $brut,
        'poid_tarat'=> $tarat,
        'poid_net'  => round($brut - $tarat, 2),
        'note'      => null,
        'id_vehicule_Fitor' => $vehiculeIds[$i % 5],
        'taratonage_created_at' => $d . ' 08:00:00',
    ];
}
insert($pdo, 'taratonage', $taratonages, $log);

// ── 12. taratonage_olive (FK → vehiculetrans) ────────────────
truncate($pdo, 'taratonage_olive');
$taratonages_olive = [];
$dates2 = ['2024-10-02','2024-10-06','2024-10-09','2024-10-13','2024-10-16','2024-10-19','2024-10-23','2024-10-26','2024-10-29','2024-11-02','2024-11-06','2024-11-11','2024-11-16','2024-11-21','2024-11-26'];
foreach ($dates2 as $i => $d) {
    $brut = round(mt_rand(3000,6000) + mt_rand(0,99)/100, 2);
    $tarat = round(mt_rand(300,600) + mt_rand(0,99)/100, 2);
    $taratonages_olive[] = [
        'poid_brut' => $brut,
        'poid_tarat'=> $tarat,
        'poid_net'  => round($brut - $tarat, 2),
        'note'      => null,
        'id_vehicule_trans' => $vehiculeIds[$i % 5],
        'taratonage_olive_created_at' => $d . ' 09:00:00',
        'updated_at' => null,
    ];
}
insert($pdo, 'taratonage_olive', $taratonages_olive, $log);

// ── 13. bon_fitor (FK → taratonage) ──────────────────────────
truncate($pdo, 'bon_fitor');
$bon_fitors = [];
for ($i = 1; $i <= 15; $i++) {
    $qte   = round(mt_rand(800,2000) + mt_rand(0,99)/100, 3);
    $price = round(mt_rand(5,12) + mt_rand(0,99)/100, 2);
    $bon_fitors[] = [
        'quantite'      => $qte,
        'prix_init'     => $price,
        'prix_net'      => round($qte * $price, 2),
        'provider'      => 'Fournisseur ' . (($i % 5) + 1),
        'id_taratonage' => $i,
        'created_at'    => '2024-10-0' . (($i % 9) + 1) . ' 10:00:00',
    ];
}
insert($pdo, 'bon_fitor', $bon_fitors, $log);

// ── 14. bon_de_huile (FK → client) ───────────────────────────
truncate($pdo, 'bon_de_huile');
$bons_huile = [];
$modes = ['ESPECES','CARTE','VIREMENT','CHEQUE'];
for ($i = 1; $i <= 20; $i++) {
    $qte   = round(mt_rand(5,100) + mt_rand(0,9)/10, 2);
    $pu    = round(mt_rand(8,15) + mt_rand(0,9)/10, 2);
    $bons_huile[] = [
        'client_id'          => (($i - 1) % 10) + 1,
        'quantite'           => $qte,
        'date_bon'           => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT),
        'prix_unitaire'      => $pu,
        'prix_net'           => round($qte * $pu, 2),
        'provider'           => 'Huilerie ' . (($i % 4) + 1),
        'mode_paiement_huile'=> $modes[$i % 4],
        'note'               => null,
        'created_at'         => '2024-10-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . ' 10:00:00',
    ];
}
insert($pdo, 'bon_de_huile', $bons_huile, $log);

// ── 15. bon_sortie_bidon (FK → client) ───────────────────────
truncate($pdo, 'bon_sortie_bidon');
$bons_sortie_bidon = [];
$statuts = ['PAYE','NON PAYE'];
$modesPay = ['ESPECES','CARTE','VIREMENT','CHEQUE','AUTRE'];
for ($i = 1; $i <= 15; $i++) {
    $bons_sortie_bidon[] = [
        'id_client'    => (($i - 1) % 10) + 1,
        'destination'  => 'Magasin ' . $i,
        'statut'       => $statuts[$i % 2],
        'mode_paiement'=> $modesPay[$i % 5],
        'created_at'   => '2024-10-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 11:00:00',
    ];
}
insert($pdo, 'bon_sortie_bidon', $bons_sortie_bidon, $log);

// ── 16. details_bon_bidon (FK → bon_sortie_bidon + bidons) ───
truncate($pdo, 'details_bon_bidon');
$details_bidon = [];
for ($i = 1; $i <= 15; $i++) {
    $details_bidon[] = [
        'id_bon'        => $i,
        'id_bidon'      => (($i - 1) % 5) + 1,
        'quantite'      => mt_rand(5, 50),
        'prix_unitaire' => [10,18,30,42,65][($i - 1) % 5],
        'quantite_bidon'=> round(mt_rand(5,50) + mt_rand(0,9)/10, 2),
        'created_at'    => '2024-10-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 11:30:00',
    ];
}
insert($pdo, 'details_bon_bidon', $details_bidon, $log);

// ── 17. details_bon_huile (FK → bon_de_huile + bidons) ───────
truncate($pdo, 'details_bon_huile');
$details_huile = [];
for ($i = 1; $i <= 20; $i++) {
    $details_huile[] = [
        'id_bon'        => $i,
        'id_bidon'      => (($i - 1) % 5) + 1,
        'quantite_bidon'=> round(mt_rand(1,10) + mt_rand(0,9)/10, 2),
        'created_at'    => '2024-10-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . ' 10:30:00',
    ];
}
insert($pdo, 'details_bon_huile', $details_huile, $log);

// ── 18. bon_sortie_huile (FK → details_bon_huile) ────────────
truncate($pdo, 'bon_sortie_huile');
$bons_sortie_huile = [];
for ($i = 1; $i <= 20; $i++) {
    $bons_sortie_huile[] = [
        'id_detail'   => $i,
        'destination' => 'Livraison client ' . $i,
        'statut'      => ($i % 3 === 0) ? 'DESACTIVE' : 'ACTIVE',
        'created_at'  => '2024-10-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . ' 12:00:00',
    ];
}
insert($pdo, 'bon_sortie_huile', $bons_sortie_huile, $log);

// ── 19. bon_rue ───────────────────────────────────────────────
truncate($pdo, 'bon_rue');
$bons_rue = [];
for ($i = 1; $i <= 10; $i++) {
    $statut = ($i % 4 === 0) ? 'ANNULE' : (($i % 3 === 0) ? 'DESACTIVE' : 'ACTIVE');
    $bons_rue[] = [
        'ref_bon_huile'      => $i,
        'ref_bon_bidon'      => $i <= 10 ? $i : null,
        'statut'             => $statut,
        'random_daily_value' => strtoupper(substr(md5($i), 0, 8)),
        'commentaire'        => null,
        'created_at'         => '2024-10-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 13:00:00',
        'duree'              => '2024-12-31 23:59:59',
    ];
}
insert($pdo, 'bon_rue', $bons_rue, $log);

// ── 20. historique_bidon (FK → bidons) ───────────────────────
truncate($pdo, 'historique_bidon');
$hist_bidon = [];
$types = ['ajout','retrait','correction'];
for ($i = 0; $i < 20; $i++) {
    $hist_bidon[] = [
        'id_bidon'        => ($i % 5) + 1,
        'date_action'     => '2024-0' . (($i % 9) + 1) . '-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . ' 08:00:00',
        'type_action'     => $types[$i % 3],
        'quantite_changee'=> round(mt_rand(5,50) + mt_rand(0,9)/10, 2),
        'utilisateur'     => ['admin','yassine','rachid'][$i % 3],
        'commentaire'     => 'Opération de ' . $types[$i % 3],
    ];
}
insert($pdo, 'historique_bidon', $hist_bidon, $log);

// ── 21. versement (FK → chauffeur) ───────────────────────────
truncate($pdo, 'versement');
$versements = [];
$modesV = ['espece','virement','cheque','autre'];
for ($i = 1; $i <= 15; $i++) {
    $versements[] = [
        'montant'          => round(mt_rand(500, 3000) + mt_rand(0,99)/100, 2),
        'date_versement'   => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 09:00:00',
        'mode_paiement'    => $modesV[$i % 4],
        'note'             => null,
        'id_Fchauffeur'    => (($i - 1) % 5) + 1,
        'image_versement'  => null,
        'created_at'       => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 09:00:00',
    ];
}
insert($pdo, 'versement', $versements, $log);

// ── 22. affectation_versement (FK → versement + bon_fitor) ───
truncate($pdo, 'affectation_versement');
$affectations = [];
for ($i = 1; $i <= 15; $i++) {
    $affectations[] = [
        'id_versement'    => $i,
        'id_bon'          => $i,
        'montant_affecte' => round(mt_rand(200, 1500) + mt_rand(0,99)/100, 2),
        'created_at'      => '2024-10-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 10:00:00',
    ];
}
insert($pdo, 'affectation_versement', $affectations, $log);

// ── 23. machine (FK → fournisseur) ───────────────────────────
truncate($pdo, 'machine');
$machines = [
    ['date_ajout'=>'2024-01-10 08:00:00','ref_machine'=>'MCH-001','nom_machine'=>'Presse Huile 1',      'description_machine'=>'Presse à froid pour extraction huile d\'olive','id_fournisseur'=>1],
    ['date_ajout'=>'2024-01-15 09:00:00','ref_machine'=>'MCH-002','nom_machine'=>'Décanteur Centrifuge','description_machine'=>'Séparateur centrifuge 3 phases',                'id_fournisseur'=>2],
    ['date_ajout'=>'2024-02-01 10:00:00','ref_machine'=>'MCH-003','nom_machine'=>'Laveuse à Olives',    'description_machine'=>'Nettoyage et lavage des olives avant pressage',  'id_fournisseur'=>1],
    ['date_ajout'=>'2024-02-10 11:00:00','ref_machine'=>'MCH-004','nom_machine'=>'Malaxeur',            'description_machine'=>'Malaxage de la pâte d\'olive',                   'id_fournisseur'=>3],
    ['date_ajout'=>'2024-03-01 08:00:00','ref_machine'=>'MCH-005','nom_machine'=>'Groupe Électrogène', 'description_machine'=>'Alimentation électrique de secours',              'id_fournisseur'=>4],
];
insert($pdo, 'machine', $machines, $log);

// ── 24. statut_machine (FK → machine) ────────────────────────
truncate($pdo, 'statut_machine');
$statuts_machine = [];
$statutsM = ['En marche','En panne','En maintenance','Arrêté'];
for ($i = 0; $i < 20; $i++) {
    $statuts_machine[] = [
        'date_ajout'  => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . ' 08:00:00',
        'statut'      => $statutsM[$i % 4],
        'description' => 'Changement de statut — ' . $statutsM[$i % 4],
        'id_machine'  => ($i % 5) + 1,
    ];
}
insert($pdo, 'statut_machine', $statuts_machine, $log);

// ── 25. piece (FK → fournisseur) ─────────────────────────────
truncate($pdo, 'piece');
$pieces = [
    ['date_ajout'=>'2024-01-10 08:00:00','ref_piece'=>'PCE-001','nom_piece'=>'Courroie principale',    'description_piece'=>'Courroie d\'entraînement de la presse',     'type_piece'=>'Mécanique','id_fournisseur'=>1],
    ['date_ajout'=>'2024-01-15 09:00:00','ref_piece'=>'PCE-002','nom_piece'=>'Roulement à billes',    'description_piece'=>'Roulement SKF 6205',                         'type_piece'=>'Mécanique','id_fournisseur'=>2],
    ['date_ajout'=>'2024-02-01 10:00:00','ref_piece'=>'PCE-003','nom_piece'=>'Joint torique',         'description_piece'=>'Joint d\'étanchéité hydraulique',            'type_piece'=>'Hydraulique','id_fournisseur'=>1],
    ['date_ajout'=>'2024-02-10 11:00:00','ref_piece'=>'PCE-004','nom_piece'=>'Filtre à huile',        'description_piece'=>'Filtre hydraulique retour',                  'type_piece'=>'Hydraulique','id_fournisseur'=>3],
    ['date_ajout'=>'2024-03-01 08:00:00','ref_piece'=>'PCE-005','nom_piece'=>'Disjoncteur 32A',       'description_piece'=>'Protection circuit électrique 32 ampères',  'type_piece'=>'Électrique','id_fournisseur'=>4],
    ['date_ajout'=>'2024-03-15 09:00:00','ref_piece'=>'PCE-006','nom_piece'=>'Capteur de pression',   'description_piece'=>'Capteur 0-16 bar 4-20mA',                   'type_piece'=>'Électronique','id_fournisseur'=>5],
    ['date_ajout'=>'2024-04-01 10:00:00','ref_piece'=>'PCE-007','nom_piece'=>'Vis sans fin',          'description_piece'=>'Vis d\'entraînement décanteur',              'type_piece'=>'Mécanique','id_fournisseur'=>2],
    ['date_ajout'=>'2024-04-15 11:00:00','ref_piece'=>'PCE-008','nom_piece'=>'Pompe centrifuge',      'description_piece'=>'Pompe eau chaude malaxeur',                  'type_piece'=>'Hydraulique','id_fournisseur'=>3],
];
insert($pdo, 'piece', $pieces, $log);

// ── 26. statut_piece (FK → piece) ────────────────────────────
truncate($pdo, 'statut_piece');
$statuts_piece = [];
$statutsP = ['Disponible','Installée','En panne','Remplacée','En commande'];
for ($i = 0; $i < 24; $i++) {
    $statuts_piece[] = [
        'date_ajout'  => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . ' 09:00:00',
        'statut'      => $statutsP[$i % 5],
        'description' => 'Statut mise à jour — ' . $statutsP[$i % 5],
        'id_piece'    => ($i % 8) + 1,
        'quantite'    => mt_rand(1, 20),
        'id_machine'  => ($i % 5) + 1,
    ];
}
insert($pdo, 'statut_piece', $statuts_piece, $log);

// ── 27. stock_piece (FK → piece) ─────────────────────────────
truncate($pdo, 'stock_piece');
$stocks = [];
for ($i = 0; $i < 24; $i++) {
    $stocks[] = [
        'date_ajout'     => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT) . ' 10:00:00',
        'quantite_piece' => mt_rand(1, 50),
        'id_piece'       => ($i % 8) + 1,
        'description'    => 'Entrée stock — pièce ' . (($i % 8) + 1),
    ];
}
insert($pdo, 'stock_piece', $stocks, $log);

// ── 28. reglement_fournisseur (FK → fournisseur) ─────────────
truncate($pdo, 'reglement_fournisseur');
$reglements = [];
$situations = ['PAYE','NON PAYE','PARTIEL'];
$payModes   = ['ESPECES','VIREMENT','CHEQUE','CARTE'];
for ($i = 1; $i <= 20; $i++) {
    $reglements[] = [
        'date_ajout'      => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 08:00:00',
        'date_paiement'   => ($i % 3 !== 1) ? '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad($i + 5, 2, '0', STR_PAD_LEFT) . ' 10:00:00' : null,
        'ref_reglement'   => 'REG-' . str_pad($i, 4, '0', STR_PAD_LEFT),
        'id_fournisseur'  => (($i - 1) % 5) + 1,
        'situation'       => $situations[$i % 3],
        'payment_mode'    => $payModes[$i % 4],
        'img_facture'     => '',
        'img_justification'=> null,
        'prix_total'      => round(mt_rand(1000, 15000) + mt_rand(0,99)/100, 2),
    ];
}
insert($pdo, 'reglement_fournisseur', $reglements, $log);

// ── 29. paiement_reglement (FK → reglement_fournisseur) ──────
truncate($pdo, 'paiement_reglement');
$paiements = [];
for ($i = 1; $i <= 20; $i++) {
    $paiements[] = [
        'id_reglement'     => $i,
        'montant'          => round(mt_rand(500, 5000) + mt_rand(0,99)/100, 2),
        'payment_mode'     => $payModes[$i % 4],
        'img_justification'=> null,
        'date_ajout'       => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 11:00:00',
    ];
}
insert($pdo, 'paiement_reglement', $paiements, $log);

// ── 30. piece_reglement (FK → reglement_fournisseur) ─────────
truncate($pdo, 'piece_reglement');
$pieces_reg = [];
$pieceNames = ['Courroie','Roulement','Joint','Filtre','Disjoncteur','Capteur','Vis','Pompe'];
for ($i = 1; $i <= 20; $i++) {
    $pieces_reg[] = [
        'id_reglement' => $i,
        'nom'          => $pieceNames[$i % 8],
        'prix'         => round(mt_rand(50, 500) + mt_rand(0,99)/100, 2),
        'quantite'     => mt_rand(1, 10),
        'date_ajout'   => '2024-' . str_pad((($i % 3) + 10), 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 09:00:00',
    ];
}
insert($pdo, 'piece_reglement', $pieces_reg, $log);

// ── 31. user ─────────────────────────────────────────────────
truncate($pdo, 'user');
$users = [
    ['name'=>'Admin',   'email'=>'admin@meknassa.ma',   'password'=>password_hash('admin123',   PASSWORD_DEFAULT), 'created_at'=>'2024-01-01 08:00:00'],
    ['name'=>'Yassine', 'email'=>'yassine@meknassa.ma', 'password'=>password_hash('yassine123', PASSWORD_DEFAULT), 'created_at'=>'2024-01-05 08:00:00'],
    ['name'=>'Rachid',  'email'=>'rachid@meknassa.ma',  'password'=>password_hash('rachid123',  PASSWORD_DEFAULT), 'created_at'=>'2024-01-10 08:00:00'],
];
insert($pdo, 'user', $users, $log);

// ── 32. image_machine (FK → machine) ─────────────────────────
// (skip inserting image rows — files don't exist; table left empty)
$log[] = "✔ image_machine — skipped (no image files)";

// ── 33. image_piece (FK → piece) ─────────────────────────────
// (skip inserting image rows — files don't exist; table left empty)
$log[] = "✔ image_piece — skipped (no image files)";

// ── 34. image_reglement (FK → reglement_fournisseur) ─────────
// (skip inserting image rows — files don't exist; table left empty)
$log[] = "✔ image_reglement — skipped (no image files)";

// ── Re-enable FK checks ──────────────────────────────────────
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// ── Output ───────────────────────────────────────────────────
$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">
    <title>Seeder</title>
    <style>
      body { font-family: monospace; background:#0f172a; color:#e2e8f0; padding:2rem; }
      h1 { color:#38bdf8; margin-bottom:1.5rem; }
      .ok  { color:#4ade80; margin:4px 0; }
      .done{ color:#facc15; font-size:1.2rem; margin-top:1.5rem; font-weight:bold; }
    </style></head><body>
    <h1>Seeder — gestion_meknassa</h1>';
    foreach ($log as $l) echo "<p class='ok'>$l</p>";
    echo "<p class='done'>Seeder terminé avec succès — " . count($log) . " tables remplies.</p>";
    echo '</body></html>';
} else {
    foreach ($log as $l) echo $l . PHP_EOL;
    echo PHP_EOL . "Done — " . count($log) . " tables seeded." . PHP_EOL;
}
