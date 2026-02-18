<?php

define('BASE_PATH', __DIR__);
ini_set('display_errors', 1);

require_once('config/config.ini.php');
require BASE_PATH . '/includes/head.php';

require BASE_PATH . '/includes/sidebare.php';

$dailyToken = function_exists('global_24h_token')
  ? global_24h_token()
  : bin2hex(random_bytes(4));

function getCount($table, $connect)
{
  $res = $connect->query("SELECT COUNT(*) AS total FROM $table");
  return $res ? $res->fetch_assoc()['total'] : 0;
}

function getSum($table, $col, $connect)
{
  $res = $connect->query("SELECT SUM($col) AS total FROM $table");
  if ($res) {
    $row = $res->fetch_assoc();
    $total = $row['total'] ?? 0; // Si NULL → 0
    return number_format((float) $total, 2);
  }
  return '0.00';
}

function getSumWithout($table, $col, $connect)
{
  $res = $connect->query("SELECT SUM($col) AS total FROM $table");
  if ($res) {
    $row = $res->fetch_assoc();
    return number_format((float)($row['total'] ?? 0), 0);
  }
  return 0;
}

// Totaux
$totalClients = getCount('client', $connect);
$totalFournisseurs = getCount('fournisseur', $connect);
$totalOuvriers = getCount('ouvrier', $connect);
$totalChauffeurs = getCount('chauffeur', $connect);
$totalVehicules = getCount('vehicule', $connect);
$totalMachines = getCount('machine', $connect);

$sumVersements = getSum('versement', 'montant', $connect);
$sumAvances = getSum('avance', 'montant', $connect);
$sumHuile = getSum('bon_de_huile', 'prix_net', $connect);
$sumBidons = getSumWithout('bidons', 'quantite_bidon', $connect);
$sumPieces = getSumWithout('stock_piece', 'quantite_piece', $connect);

// === TOTALS RÈGLEMENTS FOURNISSEURS ===
$sqlReg = "
  SELECT
      ROUND(SUM(rf.prix_total), 2) AS total_general,
      ROUND(SUM(
          CASE WHEN LOWER(TRIM(rf.situation)) IN ('payé','paye','payee','payée')
          THEN rf.prix_total ELSE 0 END
      ), 2) AS total_paye,
      ROUND(SUM(
          CASE WHEN LOWER(TRIM(rf.situation)) = 'avance'
          THEN COALESCE(pr.total_verse, 0) ELSE 0 END
      ), 2) AS total_avance,
      ROUND(SUM(
          CASE 
              WHEN LOWER(TRIM(rf.situation)) IN ('non payé','non paye','non payee','non payée')
                  THEN rf.prix_total
              WHEN LOWER(TRIM(rf.situation)) = 'avance'
                  THEN (rf.prix_total - COALESCE(pr.total_verse, 0))
              ELSE 0
          END
      ), 2) AS total_non_paye
  FROM reglement_fournisseur rf
  LEFT JOIN (
      SELECT id_reglement, SUM(montant) AS total_verse 
      FROM paiement_reglement 
      GROUP BY id_reglement
  ) AS pr ON pr.id_reglement = rf.id_reglement
";
$resReg = $connect->query($sqlReg);
$reg = $resReg ? $resReg->fetch_assoc() : ['total_paye' => 0, 'total_non_paye' => 0, 'total_avance' => 0, 'total_general' => 0];

$totalRegPaye = number_format((float) $reg['total_paye'], 2);
$totalRegNonPaye = number_format((float) $reg['total_non_paye'], 2);
$totalRegGeneral = number_format((float) $reg['total_general'], 2);
$totalRegAvance = number_format((float)$reg['total_avance'], 2);

// Données pour graphiques
$versements_mois = $connect->query("
  SELECT DATE_FORMAT(date_versement, '%Y-%m-%d') AS jour, SUM(montant) AS total
  FROM versement 
  WHERE date_versement IS NOT NULL
  GROUP BY jour 
  ORDER BY jour ASC
");
$dataVersement = [];
while ($v = $versements_mois->fetch_assoc()) {
  $dataVersement['labels'][] = $v['jour'];
  $dataVersement['data'][] = floatval($v['total']);
}

$bonHuile_jour = $connect->query("
  SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS jour, SUM(prix_net) AS total
  FROM bon_de_huile 
  WHERE created_at IS NOT NULL
  GROUP BY jour 
  ORDER BY jour ASC
");
$dataHuile = [];
while ($b = $bonHuile_jour->fetch_assoc()) {
  $dataHuile['labels'][] = $b['jour'];
  $dataHuile['data'][] = floatval($b['total']);
}

// === MESSAGE SPÉCIFIQUE PAR RÔLE ===
$greetings = [
  'user' => 'Bonjour M. Youssef',
  'admin' => 'Bonjour',
  'yassine' => 'Bonjour M. Yassine'
];
$greetText = $greetings[$userRole] ?? 'Bonjour à vous';
?>
<style>
  /* === ANIMATION BONJOUR / BONSOIR === */
  .greeting-animate {
    display: inline-block;
    font-size: 1.3rem;
    padding: 10px 20px;
    border-radius: 12px;
    color: #fff;
    background: linear-gradient(90deg, #16a34a, #0d9488);
    box-shadow: 0 0 10px rgba(22, 163, 74, 0.3);
    animation: fadeInScale 2s ease forwards, glowWave 3s ease-in-out infinite;
  }

  @keyframes fadeInScale {
    0% { opacity: 0; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1); }
  }

  @keyframes glowWave {
    0%, 100% { box-shadow: 0 0 8px rgba(22, 163, 74, 0.3); }
    50% { box-shadow: 0 0 16px rgba(13, 148, 136, 0.6); }
  }

  /* === ANIMATIONS GÉNÉRALES POUR TOUTES LES CARTES === */
  @keyframes floatCard {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
  }

  @keyframes pulseIcon {
    0%, 100% { 
      transform: scale(1);
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    50% { 
      transform: scale(1.1);
      filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
    }
  }

  @keyframes cardGlow {
    0%, 100% { 
      border-color: transparent;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    50% { 
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
  }

  /* Styles généraux pour toutes les cartes */
  .card {
    cursor: pointer;
    transition: all 0.4s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
  }

  .card-shine {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
  }

  .card:hover .card-shine {
    transform: translateX(100%);
  }

  .card:hover {
    transform: translateY(-8px) scale(1.02);
    animation: cardGlow 2s ease-in-out infinite;
  }

  .card-icon {
    font-size: 2.5rem;
    animation: floatCard 3s ease-in-out infinite;
    margin-bottom: 0.5rem;
  }

  .card:hover .card-icon {
    animation: pulseIcon 1s ease-in-out infinite;
  }

  .card-count {
    font-weight: 700;
    font-size: 2.2rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
  }

  .card:hover .card-count {
    transform: scale(1.1);
  }

  .card-badge {
    font-size: 0.75rem;
    padding: 4px 12px;
    border-radius: 20px;
    animation: pulseIcon 2s ease-in-out infinite;
  }

  /* Couleurs spécifiques pour chaque type de carte */
  .client-card .card-icon { color: #6c757d; }
  .client-card:hover { border-color: #6c757d !important; }
  .client-card .card-badge { background: linear-gradient(45deg, #6c757d, #adb5bd); }

  .fournisseur-card .card-icon { color: #6c757d; }
  .fournisseur-card:hover { border-color: #6c757d !important; }
  .fournisseur-card .card-badge { background: linear-gradient(45deg, #6c757d, #adb5bd); }

  .ouvrier-card .card-icon { color: #6c757d; }
  .ouvrier-card:hover { border-color: #6c757d !important; }
  .ouvrier-card .card-badge { background: linear-gradient(45deg, #6c757d, #adb5bd); }

  .chauffeur-card .card-icon { color: #6c757d; }
  .chauffeur-card:hover { border-color: #6c757d !important; }
  .chauffeur-card .card-badge { background: linear-gradient(45deg, #6c757d, #adb5bd); }

  .vehicule-card .card-icon { color: #6c757d; }
  .vehicule-card:hover { border-color: #6c757d !important; }
  .vehicule-card .card-badge { background: linear-gradient(45deg, #6c757d, #adb5bd); }

  .machine-card .card-icon { color: #0d6efd; }
  .machine-card:hover { border-color: #0d6efd !important; }
  .machine-card .card-badge { background: linear-gradient(45deg, #0d6efd, #0dcaf0); }

  .versement-card .card-icon { color: white; }
  .versement-card:hover { border-color: #0dcaf0 !important; }
  .versement-card .card-badge { background: linear-gradient(45deg, #0dcaf0, #0dcaf0); }

  .avance-card .card-icon { color: white; }
  .avance-card:hover { border-color: #ffc107 !important; }
  .avance-card .card-badge { background: linear-gradient(45deg, #ffc107, #ffca2c); }

  .huile-card .card-icon { color: white; }
  .huile-card:hover { border-color: #198754 !important; }
  .huile-card .card-badge { background: linear-gradient(45deg, #198754, #20c997); }

  .bidon-card .card-icon { color: white; }
  .bidon-card:hover { border-color: #6c757d !important; }
  .bidon-card .card-badge { background: linear-gradient(45deg, #6c757d, #adb5bd); }

  .piece-card .card-icon { color: white; }
  .piece-card:hover { border-color: #dc3545 !important; }
  .piece-card .card-badge { background: linear-gradient(45deg, #dc3545, #e35d6a); }

  .credit-card .card-icon { color: white; }
  .credit-card:hover { border-color: #0d9488 !important; }
  .credit-card .card-badge { background: linear-gradient(45deg, #0d9488, #14b8a6); }

  .transporteur-card .card-icon { color: white; }
  .transporteur-card:hover { border-color: #212529 !important; }
  .transporteur-card .card-badge { background: linear-gradient(45deg, #212529, #495057); }

  .reglement-card .card-icon { color: white; }
  .reglement-card:hover { border-color: #0d6efd !important; }
  .reglement-card .card-badge { background: linear-gradient(45deg, #0d6efd, #0dcaf0); }

  /* Responsive */
  @media (max-width: 768px) {
    .card {
      margin-bottom: 1rem;
    }
    
    .card-icon {
      font-size: 2rem !important;
    }
    
    .card-count {
      font-size: 1.8rem !important;
    }
  }
</style>

<?php if ($userRole === 'admin' || $userRole === 'user'): ?>
  <main class="app-main">
    <div class="container-fluid">
      <div class="app-content-header">
        <h3 class="text-center mb-4">Tableau d'Analyse - Meknassa Olive</h3>

        <div class="text-center mb-4">
          <div class="text-center my-3">
            <span id="greetingDynamic" class="fw-bold greeting-animate"><?= htmlspecialchars($greetText) ?></span>
          </div>

          <span class="d-inline-block bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-3 shadow-sm"
            style="font-size: 1rem; letter-spacing: 1px;">
            <i class="bi bi-shield-check me-2"></i>
            Code du jour :
            <span class="text-dark ms-1"><?= htmlspecialchars($dailyToken) ?></span>
          </span>
        </div>

        <!-- Première ligne de cartes -->
        <div class="row text-center">
          <!-- Clients Huile -->
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow client-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-people-fill"></i>
              </div>
              <h5 class="text-center mb-2">Clients Huile</h5>
              <h2 class="text-center mb-0 card-count"><?= $totalClients ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-person-check me-1"></i>Actifs
                </span>
              </div>
            </div>
          </div>

          <!-- Fournisseurs -->
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow fournisseur-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-truck"></i>
              </div>
              <h5 class="text-center mb-2">Fournisseurs</h5>
              <h2 class="text-center mb-0 card-count"><?= $totalFournisseurs ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-building me-1"></i>Partenaires
                </span>
              </div>
            </div>
          </div>

          <!-- Ouvriers -->
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow ouvrier-card" id="cardOuvrier">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-person-badge"></i>
              </div>
              <h5 class="text-center mb-2">Ouvriers</h5>
              <h2 class="text-center mb-0 card-count"><?= $totalOuvriers ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-tools me-1"></i>Équipe
                </span>
              </div>
            </div>
          </div>

          <!-- Chauffeurs -->
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow chauffeur-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-person-wheelchair"></i>
              </div>
              <h5 class="text-center mb-2">Chauffeurs Fitor</h5>
              <h2 class="text-center mb-0 card-count"><?= $totalChauffeurs ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-steering-wheel me-1"></i>Conducteurs
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Deuxième ligne de cartes -->
        <div class="row text-center mt-3">
          <!-- Véhicules -->
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow vehicule-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-truck-front"></i>
              </div>
              <h5 class="text-center mb-2">Véhicules Fitor</h5>
              <h2 class="text-center mb-0 card-count"><?= $totalVehicules ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-speedometer me-1"></i>Flotte
                </span>
              </div>
            </div>
          </div>

          <!-- Machines -->
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow machine-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-cpu-fill"></i>
              </div>
              <h5 class="text-center mb-2">Machines</h5>
              <h2 class="text-center mb-0 card-count"><?= $totalMachines ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-activity me-1"></i>Actives
                </span>
              </div>
            </div>
          </div>

          <!-- Versements Grignon -->
          <div class="col-md-3">
            <div class="card bg-info text-white p-3 shadow versement-card" id="cardVersementGrignon">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-cash-stack"></i>
              </div>
              <h5 class="text-center mb-2">Versements Grignon</h5>
              <h2 class="text-center mb-0 card-count"><?= $sumVersements ?> MAD</h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-graph-up me-1"></i>Revenus
                </span>
              </div>
            </div>
          </div>

          <!-- Avances Ouvriers -->
          <div class="col-md-3">
            <div class="card bg-warning text-white p-3 shadow avance-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-wallet2"></i>
              </div>
              <h5 class="text-center mb-2">Avances Ouvriers</h5>
              <h2 class="text-center mb-0 card-count"><?= $sumAvances ?> MAD</h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-arrow-up me-1"></i>Dépenses
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Troisième ligne de cartes -->
        <div class="row text-center mt-3">
          <!-- Total Huile -->
          <div class="col-md-4">
            <div class="card bg-success text-white p-3 shadow huile-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-droplet-fill"></i>
              </div>
              <h5 class="text-center mb-2">Total Huile</h5>
              <h2 class="text-center mb-0 card-count"><?= $sumHuile ?> MAD</h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-bar-chart me-1"></i>Ventes
                </span>
              </div>
            </div>
          </div>

          <!-- Bidons -->
          <div class="col-md-4">
            <div class="card bg-secondary text-white p-3 shadow bidon-card" id="cardBidon">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-archive"></i>
              </div>
              <h5 class="text-center mb-2">Total Bidons</h5>
              <h2 class="text-center mb-0 card-count"><?= $sumBidons ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-box-seam me-1"></i>Stock
                </span>
              </div>
            </div>
          </div>

          <!-- Pièces -->
          <div class="col-md-4">
            <div class="card bg-danger text-white p-3 shadow piece-card">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-nut-fill"></i>
              </div>
              <h5 class="text-center mb-2">Stock Pièces</h5>
              <h2 class="text-center mb-0 card-count"><?= $sumPieces ?></h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-gear me-1"></i>Inventaire
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Quatrième ligne de cartes -->
        <div class="row text-center mt-3">
          <!-- Status Client Crédit -->
          <div class="col-md-6">
            <div class="card bg-teal text-white p-3 shadow credit-card" id="cardTaratonageCredit" style="background:#0d9488;">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-credit-card"></i>
              </div>
              <h5 class="text-center mb-2">Status Client Crédit</h5>
              <h2 class="text-center mb-0 card-count" id="taratonageSommeTotal">0.00 MAD</h2>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-clock me-1"></i>En attente
                </span>
              </div>
            </div>
          </div>

          <!-- Transporteurs -->
          <div class="col-md-6">
            <div class="card bg-dark text-white p-3 shadow transporteur-card" id="cardTransporteur">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-truck"></i>
              </div>
              <h5 class="text-center mb-2">Classement Transporteurs</h5>
              <h6 class="text-center mb-2">Poids Total Livré</h6>
              <div class="text-center mt-2">
                <span class="badge card-badge text-white">
                  <i class="bi bi-trophy me-1"></i>Classement
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Carte Règlements Fournisseurs -->
        <div class="row text-center mt-3">
          <div class="col-md-12">
            <div class="card bg-primary text-white p-3 shadow reglement-card" id="cardReglementFournisseur">
              <div class="card-shine"></div>
              <div class="text-center card-icon">
                <i class="bi bi-receipt"></i>
              </div>
              <h5 class="text-center mb-2">Règlements Fournisseurs</h5>
              <h6 class="text-center text-white-50 mb-3">Total Général : <?= $totalRegGeneral ?> MAD</h6>
              <div class="row text-center">
                <div class="col">
                  <strong>Payé :</strong> <?= $totalRegPaye ?> MAD
                </div>
                <div class="col">
                  <strong>Non Payé :</strong> <?= $totalRegNonPaye ?> MAD
                </div>
                <div class="col">
                  <strong>Avances :</strong> <?= $totalRegAvance ?> MAD
                </div>
              </div>
              <small class="text-light fst-italic mt-2 d-block">(Cliquez pour voir le détail des règlements)</small>
            </div>
          </div>
        </div>

        <!-- Remplacez la section des graphiques par ce code amélioré -->

        <hr class="my-4">

        <!-- Graphiques améliorés -->
        <div class="row">
          <!-- Graphique Versements -->
          <div class="col-md-6">
            <div class="card shadow chart-card">
              <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <div class="chart-icon me-2">
                    <i class="bi bi-graph-up-arrow"></i>
                  </div>
                  <span>Évolution des Versements</span>
                </div>
                <div class="chart-badge">
                  <i class="bi bi-cash-coin me-1"></i>Revenus
                </div>
              </div>
              <div class="card-body">
                <canvas id="chartVersements"></canvas>
              </div>
              <div class="card-footer bg-light">
                <small class="text-muted">
                  <i class="bi bi-info-circle me-1"></i>
                  Évolution quotidienne des versements de grignon
                </small>
              </div>
            </div>
          </div>

          <!-- Graphique Huile -->
          <div class="col-md-6">
            <div class="card shadow chart-card">
              <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <div class="chart-icon me-2">
                    <i class="bi bi-bar-chart-line"></i>
                  </div>
                  <span>Évolution des Bons d'Huile</span>
                </div>
                <div class="chart-badge">
                  <i class="bi bi-droplet me-1"></i>Ventes
                </div>
              </div>
              <div class="card-body">
                <canvas id="chartHuile"></canvas>
              </div>
              <div class="card-footer bg-light">
                <small class="text-muted">
                  <i class="bi bi-info-circle me-1"></i>
                  Évolution quotidienne des ventes d'huile d'olive
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
<?php endif; ?>

<!-- Le reste du code PHP et HTML reste inchangé... -->



<?php if ($userRole === 'yassine'): ?>
  <main class="app-main">
    <div class="container-fluid">
      <div class="app-content-header">
        <h3 class="text-center mb-4">Tableau d’Analyse - Meknassa Olive</h3>

        <div class="text-center mb-4">

          <div class="text-center my-3">
            <span id="greetingDynamicYassine" class="fw-bold greeting-animate"><?= htmlspecialchars($greetText) ?></span>
          </div>


          <span class="d-inline-block bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-3 shadow-sm"
            style="font-size: 1rem; letter-spacing: 1px;">
            <i class="bi bi-shield-check me-2"></i>
            Code du jour :
            <span class="text-dark ms-1"><?= htmlspecialchars($dailyToken) ?></span>
          </span>
        </div>
        <div class="row text-center">
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow">
              <h5>Machines</h5>
              <h2><?= $totalMachines ?></h2>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow">
              <h5>Fournisseurs</h5>
              <h2><?= $totalFournisseurs ?></h2>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light p-3 shadow" id="cardOuvrier">
              <h5>Ouvriers</h5>
              <h2><?= $totalOuvriers ?></h2>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-warning text-white p-3 shadow">
              <h5>Avances des Ouvriers</h5>
              <h2><?= $sumAvances ?> MAD</h2>
            </div>
          </div>
        </div>
        <hr class="my-4">

        <div class="row text-center mt-3">

          <div class="col-md-3">
            
          </div>
          <div class="col-md-3">
            
          </div>
          <div class="col-md-3">
            
          </div>
          <div class="col-md-3">
            
          </div>
        </div>

        <div class="row text-center mt-3">
          <div class="col-md-4">
            <div class="card bg-success text-white p-3 shadow">
              <h5>Total Huile</h5>
            <h6>(Cliquez pour voir détail)</h6>
            </div>
          </div>
          <div id="cardBidon" class="col-md-4">
            <div class="card bg-secondary text-white p-3 shadow">
              <h5>Total Bidons</h5>
              <h2><?= $sumBidons ?> </h2>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card bg-danger text-white p-3 shadow">
              <h5>Stock Pièces</h5>
              <h2><?= $sumPieces ?></h2>
            </div>
          </div>
          <div class="row text-center mt-3">
            <div class="col-md-6">
              <div class="card bg-teal text-white p-3 shadow" id="cardTaratonageCredit" style="background:#0d9488;">
                <h5>Status Client Crédit</h5>
                <h6>(Cliquez pour voir détail)</h6>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-dark text-white p-3 shadow" id="cardTransporteur">
                <h5>Classement des Transporteurs (Poids Total Livré)</h5>
                <h6 class="text-white-50">Cliquez pour voir le classement détaillé</h6>
              </div>
            </div>
          </div>
        </div>
        <div class="row text-center mt-3">
          <div class="col-md-12">
            <div class="card bg-primary text-white p-3 shadow" id="cardReglementFournisseur">
              <h5>Règlements Fournisseurs</h5>
              <h6 class="text-white-50">Total Général : <?= $totalRegGeneral ?> MAD</h6>
              
              <small class="text-light fst-italic">(Cliquez pour voir le détail des règlements)</small>
            </div>
          </div>
        </div>

        

        
      </div>
    </div>
  </main>
<?php endif; ?>
<!-- MODAL Bidon — DESIGN AMÉLIORÉ -->
<div class="modal fade" id="modalBidonDetails" tabindex="-1" aria-labelledby="modalBidonLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">

      <!-- En-tête gradient gris/slate -->
      <div class="modal-header py-4" style="background:linear-gradient(135deg,#475569 0%,#94a3b8 100%);">
        <div class="d-flex align-items-center w-100">
          <div class="me-3" style="background:rgba(255,255,255,.2);border-radius:12px;padding:12px;backdrop-filter:blur(10px);">
            <i class="bi bi-archive-fill display-6 text-white"></i>
          </div>
          <div class="flex-grow-1">
            <h4 class="modal-title mb-1 fw-bold text-white" id="modalBidonLabel">Détails des Bidons</h4>
            <p class="mb-0 text-white-50"><i class="bi bi-info-circle me-1"></i>Stock &amp; commandes de bidons</p>
          </div>
          <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <!-- Barre de stats + recherche -->
      <div class="bg-light py-3 px-4 border-bottom">
        <div class="row g-3 align-items-center">
          <div class="col-md-5">
            <div class="input-group input-group-sm" style="border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="searchBidon" class="form-control border-start-0"
                     placeholder="Rechercher bidon, type, client…" style="border-radius:0 8px 8px 0;">
            </div>
          </div>
          <div class="col-md-7">
            <div class="d-flex justify-content-end gap-2 flex-wrap">
              <div class="px-3 py-2 rounded-3" style="background:rgba(71,85,105,.1);border:1px solid rgba(71,85,105,.25);font-size:.82rem;">
                <i class="bi bi-archive me-1 text-secondary"></i>
                Total : <span id="bidonStatTotal" class="fw-bold text-secondary">0</span> unités
              </div>
              <div class="px-3 py-2 rounded-3" style="background:rgba(220,53,69,.1);border:1px solid rgba(220,53,69,.25);font-size:.82rem;">
                <i class="bi bi-box-arrow-right me-1 text-danger"></i>
                Vendus : <span id="bidonStatVendus" class="fw-bold text-danger">0</span>
              </div>
              <div class="px-3 py-2 rounded-3" style="background:rgba(25,135,84,.1);border:1px solid rgba(25,135,84,.25);font-size:.82rem;">
                <i class="bi bi-check-circle me-1 text-success"></i>
                Dispo : <span id="bidonStatDispo" class="fw-bold text-success">0</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Corps -->
      <div class="modal-body p-0">

        <!-- Section 1 : Stock par type -->
        <div class="px-4 pt-3 pb-1">
          <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-layers me-1"></i>Stock par type de bidon</h6>
        </div>
        <div class="table-responsive" style="max-height:38vh;">
          <table class="table table-hover align-middle mb-0" id="tableBidons">
            <thead class="sticky-top" style="top:0;z-index:10;background:#475569;">
              <tr>
                <th class="ps-4 text-white" style="width:5%;">#</th>
                <th class="text-white" style="width:20%;"><i class="bi bi-archive me-1"></i>Bidon</th>
                <th class="text-white" style="width:15%;"><i class="bi bi-tag me-1"></i>Type</th>
                <th class="text-white text-end" style="width:15%;"><i class="bi bi-boxes me-1"></i>Stock Init.</th>
                <th class="text-white text-end" style="width:15%;"><i class="bi bi-box-arrow-right me-1"></i>Vendu</th>
                <th class="text-white text-end" style="width:15%;"><i class="bi bi-check2-circle me-1"></i>Reste</th>
                <th class="text-white text-center" style="width:15%;"><i class="bi bi-activity me-1"></i>Statut</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr><td colspan="7" class="text-center text-muted py-4">
                <div class="spinner-border text-secondary mb-2" role="status"><span class="visually-hidden">…</span></div>
                <p class="mb-0">Chargement…</p>
              </td></tr>
            </tbody>
          </table>
        </div>

        <hr class="my-0">

        <!-- Section 2 : Commandes -->
        <div class="px-4 pt-3 pb-1">
          <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-cart3 me-1"></i>Commandes de bidons</h6>
        </div>
        <div class="table-responsive" style="max-height:38vh;">
          <table class="table table-hover align-middle mb-0" id="tableCommandeBidons">
            <thead class="sticky-top" style="top:0;z-index:10;background:#475569;">
              <tr>
                <th class="ps-4 text-white" style="width:5%;">#</th>
                <th class="text-white" style="width:22%;"><i class="bi bi-person me-1"></i>Client</th>
                <th class="text-white text-end" style="width:14%;"><i class="bi bi-boxes me-1"></i>Qté</th>
                <th class="text-white" style="width:14%;"><i class="bi bi-tag me-1"></i>Type</th>
                <th class="text-white text-end" style="width:18%;"><i class="bi bi-currency-exchange me-1"></i>Montant (MAD)</th>
                <th class="text-white text-center" style="width:15%;"><i class="bi bi-activity me-1"></i>Statut</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr><td colspan="6" class="text-center text-muted py-4">
                <div class="spinner-border text-secondary mb-2" role="status"><span class="visually-hidden">…</span></div>
                <p class="mb-0">Chargement…</p>
              </td></tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div id="emptyStateBidon" class="d-none text-center py-5">
          <i class="bi bi-archive display-1 text-muted opacity-50"></i>
          <h5 class="text-muted mt-3">Aucun résultat</h5>
          <button class="btn btn-secondary mt-2" id="resetSearchBidon">
            <i class="bi bi-arrow-clockwise me-1"></i>Réinitialiser
          </button>
        </div>
      </div>

      <!-- Pied de page avec pagination -->
      <div class="modal-footer bg-light py-3 px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <span class="text-muted small">
            Commandes : <span id="bidonStartIndex" class="fw-bold text-dark">0</span>–<span id="bidonEndIndex" class="fw-bold text-dark">0</span>
            sur <span id="bidonTotalItems" class="fw-bold text-secondary">0</span>
          </span>
          <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 me-1 small text-muted">Afficher :</label>
            <select id="bidonPerPage" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
              <option value="10">10</option>
              <option value="25" selected>25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <div class="btn-group ms-2">
              <button class="btn btn-outline-secondary btn-sm" id="firstBidonPage" disabled><i class="bi bi-chevron-double-left"></i></button>
              <button class="btn btn-outline-secondary btn-sm" id="prevBidonPage" disabled><i class="bi bi-chevron-left"></i></button>
              <span class="px-3 d-flex align-items-center fw-bold text-secondary small" id="bidonPageInfo">1/1</span>
              <button class="btn btn-outline-secondary btn-sm" id="nextBidonPage" disabled><i class="bi bi-chevron-right"></i></button>
              <button class="btn btn-outline-secondary btn-sm" id="lastBidonPage" disabled><i class="bi bi-chevron-double-right"></i></button>
            </div>
            <button class="btn btn-secondary ms-3" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL AVANCES OUVRIERS - DESIGN AMÉLIORÉ -->
<div class="modal fade" id="modalAvanceDetails" tabindex="-1" aria-labelledby="modalAvanceLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <!-- En-tête du modal avec gradient -->
      <div class="modal-header bg-gradient-warning text-dark py-4">
        <div class="d-flex align-items-center w-100">
          <div class="modal-icon-container me-3 bg-warning bg-opacity-25">
            <i class="bi bi-wallet2 display-6 text-dark"></i>
          </div>
          <div class="flex-grow-1">
            <h4 class="modal-title mb-1 fw-bold" id="modalAvanceLabel">
              Liste des Avances des Ouvriers
            </h4>
            <p class="mb-0 opacity-75 text-dark">
              <i class="bi bi-info-circle me-1"></i>
              Gestion des avances versées aux ouvriers
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <!-- Barre d'outils avec statistiques -->
      <div class="toolbar-section bg-light py-3 px-4 border-bottom">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <div class="input-group input-group-sm search-container">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="searchAvance" class="form-control border-start-0 avance-search" 
                       placeholder="Rechercher un ouvrier, CIN ou motif..." style="border-radius: 0 8px 8px 0;">
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end gap-3">
              <div class="stat-badge bg-warning bg-opacity-10 text-dark px-3 py-2 rounded-3">
                <i class="bi bi-cash-coin me-1"></i>
                Total: <span id="avanceTotalAmount" class="fw-bold"><?= $sumAvances ?></span> MAD
              </div>
              <div class="stat-badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-3">
                <i class="bi bi-people me-1"></i>
                <span id="avanceTotalCount" class="fw-bold">0</span> avances
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Corps du modal -->
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 65vh;">
          <table class="table table-hover align-middle mb-0" id="tableAvances">
            <thead class="table-warning sticky-top" style="top: 0;">
              <tr>
                <th class="ps-4" style="width: 5%;">
                  <div class="d-flex align-items-center">
                    <span>#</span>
                    <i class="bi bi-sort-numeric-down text-dark ms-1 small"></i>
                  </div>
                </th>
                <th style="width: 12%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-calendar me-2"></i>
                    Date
                  </div>
                </th>
                <th style="width: 20%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-person-badge me-2"></i>
                    Ouvrier
                  </div>
                </th>
                <th style="width: 12%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card me-2"></i>
                    CIN
                  </div>
                </th>
                <th style="width: 25%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-chat-text me-2"></i>
                    Motif
                  </div>
                </th>
                <th class="text-end pe-4" style="width: 15%;">
                  <div class="d-flex align-items-center justify-content-end">
                    <i class="bi bi-currency-exchange me-2"></i>
                    Montant (MAD)
                  </div>
                </th>
               
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <div class="loading-spinner">
                    <div class="spinner-border text-warning mb-3" role="status">
                      <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mb-0">Chargement des avances...</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- État vide -->
        <div id="emptyStateAvance" class="d-none text-center py-5">
          <div class="empty-icon mb-3">
            <i class="bi bi-wallet2 display-1 text-muted"></i>
          </div>
          <h5 class="text-muted mb-2">Aucune avance trouvée</h5>
          <p class="text-muted mb-3">Aucune avance ne correspond à votre recherche.</p>
          <button class="btn btn-warning" id="resetSearchAvance">
            <i class="bi bi-arrow-clockwise me-1"></i>
            Réinitialiser la recherche
          </button>
        </div>
      </div>

      <!-- Pied de page avec pagination améliorée -->
      <div class="modal-footer bg-light py-3 px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <!-- Informations de pagination -->
          <div class="pagination-info">
            <span class="text-muted small">
              Affichage de 
              <span id="avanceStartIndex" class="fw-bold text-dark">0</span> 
              à 
              <span id="avanceEndIndex" class="fw-bold text-dark">0</span> 
              sur 
              <span id="avanceTotalItems" class="fw-bold text-warning">0</span> 
              avances
            </span>
          </div>

          <!-- Contrôles de pagination -->
          <div class="d-flex align-items-center gap-2">
            <!-- Sélection du nombre d'éléments -->
            <div class="d-flex align-items-center me-3">
              <label class="form-label mb-0 me-2 small text-muted">Afficher :</label>
              <select id="avancePerPage" class="form-select form-select-sm pagination-select">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>

            <!-- Boutons de navigation -->
            <div class="btn-group pagination-buttons" role="group">
              <button class="btn btn-outline-warning btn-sm d-flex align-items-center" id="firstAvancePage" disabled>
                <i class="bi bi-chevron-double-left"></i>
              </button>
              <button class="btn btn-outline-warning btn-sm d-flex align-items-center" id="prevAvancePage" disabled>
                <i class="bi bi-chevron-left"></i>
              </button>
              
              <div class="page-indicator px-3 d-flex align-items-center">
                <span class="fw-bold text-warning" id="avancePageInfo">1/1</span>
              </div>
              
              <button class="btn btn-outline-warning btn-sm d-flex align-items-center" id="nextAvancePage" disabled>
                <i class="bi bi-chevron-right"></i>
              </button>
              <button class="btn btn-outline-warning btn-sm d-flex align-items-center" id="lastAvancePage" disabled>
                <i class="bi bi-chevron-double-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL HUILE -->
<div class="modal fade" id="modalHuileDetails" tabindex="-1" aria-labelledby="modalHuileLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header py-4" style="background:linear-gradient(135deg,#198754 0%,#20c997 100%);">
        <div class="d-flex align-items-center w-100">
          <div class="modal-icon-container me-3" style="background:rgba(255,255,255,.2);border-radius:12px;padding:12px;backdrop-filter:blur(10px);">
            <i class="bi bi-droplet-fill display-6 text-white"></i>
          </div>
          <div class="flex-grow-1">
            <h4 class="modal-title mb-1 fw-bold text-white" id="modalHuileLabel">Liste des Bons d'Huile</h4>
            <p class="mb-0 text-white-50"><i class="bi bi-info-circle me-1"></i>Ventes d'huile d'olive</p>
          </div>
          <a href="https://meknassaolive.com/gestionMeknassa/bon-Huile/solde-client.php"
             class="btn btn-light btn-sm fw-bold text-success me-3 d-flex align-items-center gap-1 shadow-sm"
             target="_blank" style="border-radius:8px;">
            <i class="bi bi-check-circle-fill"></i> Vérifier Statut
          </a>
          <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <div class="bg-light py-3 px-4 border-bottom">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group input-group-sm" style="border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="searchHuile" class="form-control border-start-0"
                     placeholder="Rechercher client, ville ou mode de paiement…" style="border-radius:0 8px 8px 0;">
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end gap-3">
              <div class="px-3 py-2 rounded-3" style="background:rgba(25,135,84,.1);border:1px solid rgba(25,135,84,.25);font-size:.85rem;">
                <i class="bi bi-droplet me-1 text-success"></i>
                Total : <span id="huileTotalCount" class="fw-bold text-success">0</span> bons
              </div>
              <div class="px-3 py-2 rounded-3" style="background:rgba(25,135,84,.1);border:1px solid rgba(25,135,84,.25);font-size:.85rem;">
                <i class="bi bi-cash-coin me-1 text-success"></i>
                <span id="huileSommeTotal" class="fw-bold text-success">0.00</span> MAD
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height:65vh;">
          <table class="table table-hover align-middle mb-0" id="tableHuile">
            <thead class="sticky-top" style="top:0;z-index:10;background:#198754;">
              <tr>
                <th class="ps-4 text-white" style="width:4%;">#</th>
                <th class="text-white" style="width:10%;"><i class="bi bi-calendar me-1"></i>Date</th>
                <th class="text-white" style="width:18%;"><i class="bi bi-person me-1"></i>Client</th>
                <th class="text-white" style="width:12%;"><i class="bi bi-geo-alt me-1"></i>Ville</th>
                <th class="text-white text-end" style="width:9%;"><i class="bi bi-box me-1"></i>Qté</th>
                <th class="text-white text-end" style="width:12%;"><i class="bi bi-tag me-1"></i>P.U (MAD)</th>
                <th class="text-white text-end" style="width:12%;"><i class="bi bi-currency-exchange me-1"></i>Net (MAD)</th>
                <th class="text-white" style="width:12%;"><i class="bi bi-credit-card me-1"></i>Paiement</th>
                <th class="text-white" style="width:11%;"><i class="bi bi-person-check me-1"></i>Réalisé par</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr>
                <td colspan="9" class="text-center text-muted py-5">
                  <div class="spinner-border text-success mb-3" role="status"><span class="visually-hidden">Chargement…</span></div>
                  <p class="mb-0">Chargement des bons d'huile…</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div id="emptyStateHuile" class="d-none text-center py-5">
          <i class="bi bi-droplet display-1 text-muted opacity-50"></i>
          <h5 class="text-muted mt-3">Aucun bon trouvé</h5>
          <button class="btn btn-success mt-2" id="resetSearchHuile"><i class="bi bi-arrow-clockwise me-1"></i>Réinitialiser</button>
        </div>
      </div>

      <div class="modal-footer bg-light py-3 px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <span class="text-muted small">
            Affichage de <span id="huileStartIndex" class="fw-bold text-dark">0</span>
            à <span id="huileEndIndex" class="fw-bold text-dark">0</span>
            sur <span id="huileTotalItems" class="fw-bold text-success">0</span> bons
          </span>
          <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 me-2 small text-muted">Afficher :</label>
            <select id="huilePerPage" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
              <option value="10">10</option>
              <option value="25" selected>25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <div class="btn-group ms-2">
              <button class="btn btn-outline-success btn-sm" id="firstHuilePage" disabled><i class="bi bi-chevron-double-left"></i></button>
              <button class="btn btn-outline-success btn-sm" id="prevHuilePage" disabled><i class="bi bi-chevron-left"></i></button>
              <span class="px-3 d-flex align-items-center fw-bold text-success small" id="huilePageInfo">1/1</span>
              <button class="btn btn-outline-success btn-sm" id="nextHuilePage" disabled><i class="bi bi-chevron-right"></i></button>
              <button class="btn btn-outline-success btn-sm" id="lastHuilePage" disabled><i class="bi bi-chevron-double-right"></i></button>
            </div>
            <button class="btn btn-secondary ms-3" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- MODAL FOURNISSEURS -->
<!-- MODAL FOURNISSEURS - DESIGN AMÉLIORÉ -->
<div class="modal fade" id="modalFournisseurDetails" tabindex="-1" aria-labelledby="modalFournisseurLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <!-- En-tête du modal avec gradient -->
      <div class="modal-header bg-gradient-primary text-white py-4">
        <div class="d-flex align-items-center w-100">
          <div class="modal-icon-container me-3">
            <i class="bi bi-truck display-6"></i>
          </div>
          <div class="flex-grow-1">
            <h4 class="modal-title mb-1 fw-bold" id="modalFournisseurLabel">
              Liste des Fournisseurs
            </h4>
            <p class="mb-0 opacity-75">
              <i class="bi bi-info-circle me-1"></i>
              Gestion de nos fournisseurs
            </p>
          </div>
          <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <!-- Barre d'outils avec statistiques -->
      <div class="toolbar-section bg-light py-3 px-4 border-bottom">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <div class="input-group input-group-sm search-container">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="searchFournisseur" class="form-control border-start-0 fournisseur-search" 
                       placeholder="Rechercher un fournisseur..." style="border-radius: 0 8px 8px 0;">
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end gap-3">
              <div class="stat-badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-3">
                <i class="bi bi-building me-1"></i>
                Total: <span id="fournisseurTotalCount" class="fw-bold">0</span> fournisseurs
              </div>
             
            </div>
          </div>
        </div>
      </div>

      <!-- Corps du modal -->
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 65vh;">
          <table class="table table-hover align-middle mb-0" id="tableFournisseurs">
            <thead class="table-dark sticky-top" style="top: 0;">
              <tr>
                <th class="ps-4" style="width: 8%;">
                  <div class="d-flex align-items-center">
                    <span>#</span>
                    <i class="bi bi-sort-numeric-down text-warning ms-1 small"></i>
                  </div>
                </th>
                <th style="width: 25%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-person-badge me-2"></i>
                    Fournisseur
                  </div>
                </th>
                <th style="width: 15%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-telephone me-2"></i>
                    Téléphone
                  </div>
                </th>
                <th style="width: 25%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-geo-alt me-2"></i>
                    Adresse
                  </div>
                </th>
                <th class="text-center" style="width: 15%;">
                  <div class="d-flex align-items-center justify-content-center">
                    <i class="bi bi-calendar-plus me-2"></i>
                    Date d'ajout
                  </div>
                </th>
                <th class="text-center pe-4" style="width: 12%;">
                  <div class="d-flex align-items-center justify-content-center">
                    <i class="bi bi-lightning-charge me-2"></i>
                    Actions
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr>
                <td colspan="6" class="text-center text-muted py-5">
                  <div class="loading-spinner">
                    <div class="spinner-border text-primary mb-3" role="status">
                      <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mb-0">Chargement des fournisseurs...</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- État vide -->
        <div id="emptyStateFournisseur" class="d-none text-center py-5">
          <div class="empty-icon mb-3">
            <i class="bi bi-truck display-1 text-muted"></i>
          </div>
          <h5 class="text-muted mb-2">Aucun fournisseur trouvé</h5>
          <p class="text-muted mb-3">Aucun fournisseur ne correspond à votre recherche.</p>
          <button class="btn btn-primary" id="resetSearchFournisseur">
            <i class="bi bi-arrow-clockwise me-1"></i>
            Réinitialiser la recherche
          </button>
        </div>
      </div>

      <!-- Pied de page avec pagination améliorée -->
      <div class="modal-footer bg-light py-3 px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <!-- Informations de pagination -->
          <div class="pagination-info">
            <span class="text-muted small">
              Affichage de 
              <span id="fournisseurStartIndex" class="fw-bold text-dark">0</span> 
              à 
              <span id="fournisseurEndIndex" class="fw-bold text-dark">0</span> 
              sur 
              <span id="fournisseurTotalItems" class="fw-bold text-primary">0</span> 
              fournisseurs
            </span>
          </div>

          <!-- Contrôles de pagination -->
          <div class="d-flex align-items-center gap-2">
            <!-- Sélection du nombre d'éléments -->
            <div class="d-flex align-items-center me-3">
              <label class="form-label mb-0 me-2 small text-muted">Afficher :</label>
              <select id="fournisseurPerPage" class="form-select form-select-sm pagination-select">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>

            <!-- Boutons de navigation -->
            <div class="btn-group pagination-buttons" role="group">
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="firstFournisseurPage" disabled>
                <i class="bi bi-chevron-double-left"></i>
              </button>
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="prevFournisseurPage" disabled>
                <i class="bi bi-chevron-left"></i>
              </button>
              
              <div class="page-indicator px-3 d-flex align-items-center">
                <span class="fw-bold text-primary" id="fournisseurPageInfo">1/1</span>
              </div>
              
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="nextFournisseurPage" disabled>
                <i class="bi bi-chevron-right"></i>
              </button>
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="lastFournisseurPage" disabled>
                <i class="bi bi-chevron-double-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CLIENTS -->

<!-- MODAL CLIENTS HUILE - DESIGN AMÉLIORÉ -->
<div class="modal fade" id="modalClientDetails" tabindex="-1" aria-labelledby="modalClientLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <!-- En-tête du modal avec gradient -->
      <div class="modal-header bg-gradient-primary text-white py-4">
        <div class="d-flex align-items-center w-100">
          <div class="modal-icon-container me-3">
            <i class="bi bi-people-fill display-6"></i>
          </div>
          <div class="flex-grow-1">
            <h4 class="modal-title mb-1 fw-bold" id="modalClientLabel">
              Liste des Clients Huile
            </h4>
            <p class="mb-0 opacity-75">
              <i class="bi bi-info-circle me-1"></i>
              Gestion clients
            </p>
          </div>
          <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <!-- Barre d'outils avec statistiques -->
      <div class="toolbar-section bg-light py-3 px-4 border-bottom">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <div class="input-group input-group-sm search-container">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="searchClient" class="form-control border-start-0 client-search" 
                       placeholder="Rechercher un client..." style="border-radius: 0 8px 8px 0;">
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end gap-3">
              <div class="stat-badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-3">
                <i class="bi bi-people me-1"></i>
                Total: <span id="clientTotalCount" class="fw-bold">0</span> clients
              </div>
             
            </div>
          </div>
        </div>
      </div>

      <!-- Corps du modal -->
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 65vh;">
          <table class="table table-hover align-middle mb-0" id="tableClients">
            <thead class="table-dark sticky-top" style="top: 0;">
              <tr>
                <th class="ps-4" style="width: 8%;">
                  <div class="d-flex align-items-center">
                    <span>#</span>
                    <i class="bi bi-sort-numeric-down text-warning ms-1 small"></i>
                  </div>
                </th>
                <th style="width: 22%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-person me-2"></i>
                    Client
                  </div>
                </th>
                <th style="width: 15%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-telephone me-2"></i>
                    Téléphone
                  </div>
                </th>
                <th style="width: 20%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-geo-alt me-2"></i>
                    Adresse
                  </div>
                </th>
                <th style="width: 15%;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-building me-2"></i>
                    Ville
                  </div>
                </th>
                <th class="text-center" style="width: 15%;">
                  <div class="d-flex align-items-center justify-content-center">
                    <i class="bi bi-calendar-event me-2"></i>
                    Date d'inscription
                  </div>
                </th>
                <th class="text-center pe-4" style="width: 5%;">
                  <i class="bi bi-activity"></i>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <div class="loading-spinner">
                    <div class="spinner-border text-primary mb-3" role="status">
                      <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mb-0">Chargement des clients...</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- État vide -->
        <div id="emptyState" class="d-none text-center py-5">
          <div class="empty-icon mb-3">
            <i class="bi bi-people display-1 text-muted"></i>
          </div>
          <h5 class="text-muted mb-2">Aucun client trouvé</h5>
          <p class="text-muted mb-3">Aucun client ne correspond à votre recherche.</p>
          <button class="btn btn-primary" id="resetSearch">
            <i class="bi bi-arrow-clockwise me-1"></i>
            Réinitialiser la recherche
          </button>
        </div>
      </div>

      <!-- Pied de page avec pagination améliorée -->
      <div class="modal-footer bg-light py-3 px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <!-- Informations de pagination -->
          <div class="pagination-info">
            <span class="text-muted small">
              Affichage de 
              <span id="clientStartIndex" class="fw-bold text-dark">0</span> 
              à 
              <span id="clientEndIndex" class="fw-bold text-dark">0</span> 
              sur 
              <span id="clientTotalItems" class="fw-bold text-primary">0</span> 
              clients
            </span>
          </div>

          <!-- Contrôles de pagination -->
          <div class="d-flex align-items-center gap-2">
            <!-- Sélection du nombre d'éléments -->
            <div class="d-flex align-items-center me-3">
              <label class="form-label mb-0 me-2 small text-muted">Afficher :</label>
              <select id="clientPerPage" class="form-select form-select-sm pagination-select">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>

            <!-- Boutons de navigation -->
            <div class="btn-group pagination-buttons" role="group">
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="firstClientPage" disabled>
                <i class="bi bi-chevron-double-left"></i>
              </button>
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="prevClientPage" disabled>
                <i class="bi bi-chevron-left"></i>
              </button>
              
              <div class="page-indicator px-3 d-flex align-items-center">
                <span class="fw-bold text-primary" id="clientPageInfo">1/1</span>
              </div>
              
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="nextClientPage" disabled>
                <i class="bi bi-chevron-right"></i>
              </button>
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center" id="lastClientPage" disabled>
                <i class="bi bi-chevron-double-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>






<!-- MODAL PIÈCES FIXÉE -->
<div class="modal fade" id="modalPieceDetails" tabindex="-1" aria-labelledby="modalPieceLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 96vw;">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-danger text-white sticky-top">
        <h5 class="modal-title">
          <i class="bi bi-nut-fill me-2"></i> Liste des Pièces / Stock et Statuts
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 78vh; overflow-y: auto; overflow-x: auto;">
          <table class="table table-striped table-hover align-middle mb-0 text-nowrap" id="tablePieces">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Référence</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Quantité</th>
                <th>En marche</th>
                <th>En panne</th>
                <th>En maintenance</th>
                <th>Fournisseur</th>
                <th>Date ajout</th>
                <th>Image</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="11" class="text-center text-muted p-4">Chargement...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer bg-light">
        <button class="btn btn-secondary px-4" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
<!-- MODAL REGLEMENTS FOURNISSEURS -->
<div class="modal fade" id="modalReglementFournisseur" tabindex="-1" aria-labelledby="modalReglementFournisseurLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalReglementFournisseurLabel">Détails des Règlements Fournisseurs</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover" id="tableReglementFournisseur">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Référence</th>
                <th>Date</th>
                <th>Fournisseur</th>
                <th>Mode Paiement</th>
                <th>Situation</th>
                <th>Montant (MAD)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="7" class="text-center text-muted">Chargement...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>


<!-- STYLE FIX MODAL -->
<style>

/* === STYLES SPÉCIFIQUES AU MODAL AVANCES === */
#modalAvanceDetails .modal-content {
  border-radius: 16px;
  overflow: hidden;
}

/* En-tête avec gradient */
#modalAvanceDetails .bg-gradient-warning {
  background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%) !important;
}

#modalAvanceDetails .modal-icon-container {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 12px;
  padding: 12px;
  backdrop-filter: blur(10px);
}

/* Barre d'outils */
#modalAvanceDetails .toolbar-section {
  background: linear-gradient(to right, #fefefe, #ffffff);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

#modalAvanceDetails .search-container {
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

#modalAvanceDetails .avance-search {
  border-radius: 0 10px 10px 0 !important;
  border: none;
  box-shadow: none;
}

#modalAvanceDetails .avance-search:focus {
  box-shadow: none;
  border-color: #ffc107;
}

#modalAvanceDetails .stat-badge {
  font-size: 0.85rem;
  font-weight: 500;
  border: 1px solid rgba(255, 193, 7, 0.3);
}

/* Tableau amélioré */
#modalAvanceDetails .table {
  margin-bottom: 0;
}

#modalAvanceDetails .table thead th {
  background: #ffc107;
  color: #212529;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 1rem 0.75rem;
  border: none;
  position: sticky;
  top: 0;
  z-index: 10;
}

#modalAvanceDetails .table tbody tr {
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

#modalAvanceDetails .table tbody tr:hover {
  background: linear-gradient(90deg, #fffbf0, #ffffff);
  border-left-color: #ffc107;
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

#modalAvanceDetails .table tbody td {
  padding: 1rem 0.75rem;
  vertical-align: middle;
  border-color: rgba(0, 0, 0, 0.05);
  font-size: 0.9rem;
}

#modalAvanceDetails .table tbody td:first-child {
  border-radius: 8px 0 0 8px;
}

#modalAvanceDetails .table tbody td:last-child {
  border-radius: 0 8px 8px 0;
}

/* Montant en gras */
#modalAvanceDetails .montant-avance {
  font-weight: 700;
  font-size: 1rem;
  color: #198754;
}

/* Badges et boutons d'action */
#modalAvanceDetails .btn-action {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  border-radius: 6px;
  transition: all 0.3s ease;
  border: none;
}

#modalAvanceDetails .btn-details {
  background: linear-gradient(135deg, #0d6efd, #0dcaf0);
  color: white;
}

#modalAvanceDetails .btn-details:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
}

#modalAvanceDetails .btn-print {
  background: linear-gradient(135deg, #198754, #20c997);
  color: white;
}

#modalAvanceDetails .btn-print:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
}

/* État de chargement */
#modalAvanceDetails .loading-spinner {
  padding: 3rem 1rem;
}

/* État vide */
#modalAvanceDetails .empty-icon {
  opacity: 0.5;
}

/* Pagination améliorée */
#modalAvanceDetails .pagination-select {
  width: auto;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

#modalAvanceDetails .pagination-buttons .btn {
  border-radius: 8px;
  margin: 0 2px;
  transition: all 0.3s ease;
}

#modalAvanceDetails .pagination-buttons .btn:not(:disabled):hover {
  background: #ffc107;
  color: #212529;
  transform: translateY(-1px);
}

#modalAvanceDetails .page-indicator {
  background: rgba(255, 193, 7, 0.1);
  border-radius: 8px;
  min-width: 60px;
  justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
  #modalAvanceDetails .modal-dialog {
    margin: 1rem;
  }
  
  #modalAvanceDetails .toolbar-section .row {
    flex-direction: column;
    gap: 1rem !important;
  }
  
  #modalAvanceDetails .modal-footer > div {
    flex-direction: column;
    gap: 1rem;
  }
  
  #modalAvanceDetails .pagination-buttons {
    order: -1;
  }
  
  #modalAvanceDetails .table thead th:nth-child(4),
  #modalAvanceDetails .table tbody td:nth-child(4) {
    display: none;
  }
}

/* Animation d'apparition des lignes */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

#modalAvanceDetails .table tbody tr {
  animation: fadeInUp 0.5s ease forwards;
}

#modalAvanceDetails .table tbody tr:nth-child(odd) {
  animation-delay: 0.1s;
}

#modalAvanceDetails .table tbody tr:nth-child(even) {
  animation-delay: 0.2s;
}

/* Scrollbar personnalisée */
#modalAvanceDetails .modal-body::-webkit-scrollbar {
  width: 6px;
}

#modalAvanceDetails .modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

#modalAvanceDetails .modal-body::-webkit-scrollbar-thumb {
  background: #ffc107;
  border-radius: 3px;
}

#modalAvanceDetails .modal-body::-webkit-scrollbar-thumb:hover {
  background: #e0a800;
}



/* === STYLES SPÉCIFIQUES AU MODAL FOURNISSEURS === */
#modalFournisseurDetails .modal-content {
  border-radius: 16px;
  overflow: hidden;
}

/* En-tête avec gradient */
#modalFournisseurDetails .bg-gradient-primary {
  background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%) !important;
}

#modalFournisseurDetails .modal-icon-container {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 12px;
  backdrop-filter: blur(10px);
}

/* Barre d'outils */
#modalFournisseurDetails .toolbar-section {
  background: linear-gradient(to right, #f8f9fa, #ffffff);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

#modalFournisseurDetails .search-container {
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

#modalFournisseurDetails .fournisseur-search {
  border-radius: 0 10px 10px 0 !important;
  border: none;
  box-shadow: none;
}

#modalFournisseurDetails .fournisseur-search:focus {
  box-shadow: none;
  border-color: #0d6efd;
}

#modalFournisseurDetails .stat-badge {
  font-size: 0.85rem;
  font-weight: 500;
  border: 1px solid rgba(13, 110, 253, 0.2);
}

/* Tableau amélioré */
#modalFournisseurDetails .table {
  margin-bottom: 0;
}

#modalFournisseurDetails .table thead th {
  background: #2c3e50;
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 1rem 0.75rem;
  border: none;
  position: sticky;
  top: 0;
  z-index: 10;
}

#modalFournisseurDetails .table tbody tr {
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

#modalFournisseurDetails .table tbody tr:hover {
  background: linear-gradient(90deg, #f8f9fa, #ffffff);
  border-left-color: #0d6efd;
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
}

#modalFournisseurDetails .table tbody td {
  padding: 1rem 0.75rem;
  vertical-align: middle;
  border-color: rgba(0, 0, 0, 0.05);
  font-size: 0.9rem;
}

#modalFournisseurDetails .table tbody td:first-child {
  border-radius: 8px 0 0 8px;
}

#modalFournisseurDetails .table tbody td:last-child {
  border-radius: 0 8px 8px 0;
}

/* Badges et boutons d'action */
#modalFournisseurDetails .btn-action {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  border-radius: 6px;
  transition: all 0.3s ease;
}

#modalFournisseurDetails .btn-view {
  background: linear-gradient(135deg, #0d6efd, #0dcaf0);
  border: none;
  color: white;
}

#modalFournisseurDetails .btn-view:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
}

#modalFournisseurDetails .btn-edit {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
  border: none;
  color: white;
}

#modalFournisseurDetails .btn-edit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
}

/* État de chargement */
#modalFournisseurDetails .loading-spinner {
  padding: 3rem 1rem;
}

/* État vide */
#modalFournisseurDetails .empty-icon {
  opacity: 0.5;
}

/* Pagination améliorée */
#modalFournisseurDetails .pagination-select {
  width: auto;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

#modalFournisseurDetails .pagination-buttons .btn {
  border-radius: 8px;
  margin: 0 2px;
  transition: all 0.3s ease;
}

#modalFournisseurDetails .pagination-buttons .btn:not(:disabled):hover {
  background: #0d6efd;
  color: white;
  transform: translateY(-1px);
}

#modalFournisseurDetails .page-indicator {
  background: rgba(13, 110, 253, 0.1);
  border-radius: 8px;
  min-width: 60px;
  justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
  #modalFournisseurDetails .modal-dialog {
    margin: 1rem;
  }
  
  #modalFournisseurDetails .toolbar-section .row {
    flex-direction: column;
    gap: 1rem !important;
  }
  
  #modalFournisseurDetails .modal-footer > div {
    flex-direction: column;
    gap: 1rem;
  }
  
  #modalFournisseurDetails .pagination-buttons {
    order: -1;
  }
  
  #modalFournisseurDetails .table thead th:nth-child(4),
  #modalFournisseurDetails .table tbody td:nth-child(4) {
    display: none;
  }
}

/* Animation d'apparition des lignes */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

#modalFournisseurDetails .table tbody tr {
  animation: fadeInUp 0.5s ease forwards;
}

#modalFournisseurDetails .table tbody tr:nth-child(odd) {
  animation-delay: 0.1s;
}

#modalFournisseurDetails .table tbody tr:nth-child(even) {
  animation-delay: 0.2s;
}

/* Scrollbar personnalisée */
#modalFournisseurDetails .modal-body::-webkit-scrollbar {
  width: 6px;
}

#modalFournisseurDetails .modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

#modalFournisseurDetails .modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

#modalFournisseurDetails .modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* === STYLES SPÉCIFIQUES AU MODAL CLIENTS === */
#modalClientDetails .modal-content {
  border-radius: 16px;
  overflow: hidden;
}

/* En-tête avec gradient */
#modalClientDetails .bg-gradient-primary {
  background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%) !important;
}

#modalClientDetails .modal-icon-container {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 12px;
  backdrop-filter: blur(10px);
}

/* Barre d'outils */
#modalClientDetails .toolbar-section {
  background: linear-gradient(to right, #f8f9fa, #ffffff);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

#modalClientDetails .search-container {
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

#modalClientDetails .client-search {
  border-radius: 0 10px 10px 0 !important;
  border: none;
  box-shadow: none;
}

#modalClientDetails .client-search:focus {
  box-shadow: none;
  border-color: #0d6efd;
}

#modalClientDetails .stat-badge {
  font-size: 0.85rem;
  font-weight: 500;
  border: 1px solid rgba(25, 135, 84, 0.2);
}

/* Tableau amélioré */
#modalClientDetails .table {
  margin-bottom: 0;
}

#modalClientDetails .table thead th {
  background: #2c3e50;
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 1rem 0.75rem;
  border: none;
  position: sticky;
  top: 0;
  z-index: 10;
}

#modalClientDetails .table tbody tr {
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

#modalClientDetails .table tbody tr:hover {
  background: linear-gradient(90deg, #f8f9fa, #ffffff);
  border-left-color: #0d6efd;
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
}

#modalClientDetails .table tbody td {
  padding: 1rem 0.75rem;
  vertical-align: middle;
  border-color: rgba(0, 0, 0, 0.05);
  font-size: 0.9rem;
}

#modalClientDetails .table tbody td:first-child {
  border-radius: 8px 0 0 8px;
}

#modalClientDetails .table tbody td:last-child {
  border-radius: 0 8px 8px 0;
}

/* Badges de ville */
#modalClientDetails .table .badge {
  font-size: 0.75rem;
  padding: 0.35em 0.65em;
  border-radius: 8px;
}

/* État de chargement */
#modalClientDetails .loading-spinner {
  padding: 3rem 1rem;
}

/* État vide */
#modalClientDetails .empty-icon {
  opacity: 0.5;
}

/* Pagination améliorée */
#modalClientDetails .pagination-select {
  width: auto;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

#modalClientDetails .pagination-buttons .btn {
  border-radius: 8px;
  margin: 0 2px;
  transition: all 0.3s ease;
}

#modalClientDetails .pagination-buttons .btn:not(:disabled):hover {
  background: #0d6efd;
  color: white;
  transform: translateY(-1px);
}

#modalClientDetails .page-indicator {
  background: rgba(13, 110, 253, 0.1);
  border-radius: 8px;
  min-width: 60px;
  justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
  #modalClientDetails .modal-dialog {
    margin: 1rem;
  }
  
  #modalClientDetails .toolbar-section .row {
    flex-direction: column;
    gap: 1rem !important;
  }
  
  #modalClientDetails .modal-footer > div {
    flex-direction: column;
    gap: 1rem;
  }
  
  #modalClientDetails .pagination-buttons {
    order: -1;
  }
}

/* Animation d'apparition des lignes */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

#modalClientDetails .table tbody tr {
  animation: fadeInUp 0.5s ease forwards;
}

#modalClientDetails .table tbody tr:nth-child(odd) {
  animation-delay: 0.1s;
}

#modalClientDetails .table tbody tr:nth-child(even) {
  animation-delay: 0.2s;
}

/* Scrollbar personnalisée */
#modalClientDetails .modal-body::-webkit-scrollbar {
  width: 6px;
}

#modalClientDetails .modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

#modalClientDetails .modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

#modalClientDetails .modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}



/* === STYLES POUR LES CARTES DE GRAPHIQUES === */
.chart-card {
  cursor: pointer;
  transition: all 0.4s ease;
  border: 2px solid transparent;
  position: relative;
  overflow: hidden;
}

.chart-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.chart-card .chart-icon {
  font-size: 1.5rem;
  animation: floatCard 3s ease-in-out infinite;
}

.chart-card:hover .chart-icon {
  animation: pulseIcon 1s ease-in-out infinite;
}

.chart-card .chart-badge {
  background: rgba(255,255,255,0.2);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

/* Animation spécifique pour les cartes de graphiques */
@keyframes chartGlow {
  0%, 100% { 
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  50% { 
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  }
}

.chart-card:hover {
  animation: chartGlow 2s ease-in-out infinite;
}

/* Effet de brillance pour les cartes de graphiques */
.chart-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
  transition: left 0.6s ease;
}

.chart-card:hover::before {
  left: 100%;
}

/* Styles spécifiques pour chaque type de graphique */
.chart-card .card-header.bg-primary {
  background: linear-gradient(135deg, #0d6efd, #0dcaf0) !important;
}

.chart-card .card-header.bg-success {
  background: linear-gradient(135deg, #198754, #20c997) !important;
}

/* Animation des badges */
.chart-badge {
  animation: pulseBadge 2s ease-in-out infinite;
}

@keyframes pulseBadge {
  0%, 100% { 
    transform: scale(1);
    opacity: 0.9;
  }
  50% { 
    transform: scale(1.05);
    opacity: 1;
  }
}

/* Responsive pour les graphiques */
@media (max-width: 768px) {
  .chart-card {
    margin-bottom: 1rem;
  }
  
  .chart-card .card-header {
    padding: 0.75rem;
  }
  
  .chart-icon {
    font-size: 1.2rem !important;
  }
}

/* Amélioration des tooltips des graphiques */
.chartjs-tooltip {
  background: rgba(0, 0, 0, 0.8) !important;
  border-radius: 8px !important;
  border: none !important;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
}

.chartjs-tooltip-key {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-right: 5px;
}
#modalHuileLabel + a.btn-light {
  background-color: #f1fdf5;
  border: 1px solid #16a34a !important;
}
#modalHuileLabel + a.btn-light:hover {
  background-color: #16a34a !important;
  color: #fff !important;
  transform: scale(1.05);
  box-shadow: 0 0 10px rgba(22,163,74,0.4);
}
  #modalPieceDetails .modal-dialog {
    width: 96vw !important;
    max-width: 96vw !important;
  }

  #modalPieceDetails .table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #212529;
    color: white;
  }

  #modalPieceDetails .modal-body {
    padding: 0 !important;
  }

  #modalPieceDetails img {
    object-fit: cover;
    border-radius: 6px;
    cursor: zoom-in;
  }

  /* Curseur cliquable sur toutes les cartes interactives */
  .card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  /* Petit effet visuel pour le survol */
  .card:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
  }
</style>
<!-- MODAL OUVRIERS -->
<div class="modal fade" id="modalOuvrierDetails" tabindex="-1" aria-labelledby="modalOuvrierLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header py-4" style="background: linear-gradient(135deg,#1e293b 0%,#334155 100%);">
        <div class="d-flex align-items-center w-100">
          <div class="modal-icon-container me-3" style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px;backdrop-filter:blur(10px);">
            <i class="bi bi-person-badge display-6 text-white"></i>
          </div>
          <div class="flex-grow-1">
            <h4 class="modal-title mb-1 fw-bold text-white" id="modalOuvrierLabel">Liste des Ouvriers</h4>
            <p class="mb-0 text-white-50"><i class="bi bi-info-circle me-1"></i>Gestion des ouvriers de l'équipe</p>
          </div>
          <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <div class="bg-light py-3 px-4 border-bottom">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group input-group-sm" style="border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="searchOuvrier" class="form-control border-start-0"
                     placeholder="Rechercher un ouvrier, CIN ou fonction…" style="border-radius:0 8px 8px 0;">
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end gap-3">
              <div class="px-3 py-2 rounded-3" style="background:rgba(30,41,59,.08);border:1px solid rgba(30,41,59,.2);font-size:.85rem;">
                <i class="bi bi-people me-1 text-dark"></i>
                Total : <span id="ouvrierTotalCount" class="fw-bold">0</span> ouvriers
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height:65vh;">
          <table class="table table-hover align-middle mb-0" id="tableOuvriers">
            <thead class="sticky-top" style="top:0;z-index:10;background:#1e293b;">
              <tr>
                <th class="ps-4 text-white" style="width:5%;">#</th>
                <th class="text-white" style="width:22%;"><i class="bi bi-person me-1"></i>Nom Complet</th>
                <th class="text-white" style="width:12%;"><i class="bi bi-credit-card me-1"></i>CIN</th>
                <th class="text-white" style="width:13%;"><i class="bi bi-telephone me-1"></i>Téléphone</th>
                <th class="text-white" style="width:15%;"><i class="bi bi-briefcase me-1"></i>Fonction</th>
                <th class="text-white" style="width:18%;"><i class="bi bi-geo-alt me-1"></i>Adresse</th>
                <th class="text-white text-center" style="width:8%;"><i class="bi bi-camera me-1"></i>Photo</th>
                <th class="text-white text-center pe-4" style="width:7%;"><i class="bi bi-card-image me-1"></i>CIN</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr>
                <td colspan="8" class="text-center text-muted py-5">
                  <div class="spinner-border text-dark mb-3" role="status"><span class="visually-hidden">Chargement…</span></div>
                  <p class="mb-0">Chargement des ouvriers…</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div id="emptyStateOuvrier" class="d-none text-center py-5">
          <i class="bi bi-person-badge display-1 text-muted opacity-50"></i>
          <h5 class="text-muted mt-3">Aucun ouvrier trouvé</h5>
          <button class="btn btn-dark mt-2" id="resetSearchOuvrier"><i class="bi bi-arrow-clockwise me-1"></i>Réinitialiser</button>
        </div>
      </div>

      <div class="modal-footer bg-light py-3 px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <span class="text-muted small">
            Affichage de <span id="ouvrierStartIndex" class="fw-bold text-dark">0</span>
            à <span id="ouvrierEndIndex" class="fw-bold text-dark">0</span>
            sur <span id="ouvrierTotalItems" class="fw-bold" style="color:#1e293b;">0</span> ouvriers
          </span>
          <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 me-2 small text-muted">Afficher :</label>
            <select id="ouvrierPerPage" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
              <option value="10">10</option>
              <option value="25" selected>25</option>
              <option value="50">50</option>
            </select>
            <div class="btn-group ms-2">
              <button class="btn btn-outline-secondary btn-sm" id="firstOuvrierPage" disabled><i class="bi bi-chevron-double-left"></i></button>
              <button class="btn btn-outline-secondary btn-sm" id="prevOuvrierPage" disabled><i class="bi bi-chevron-left"></i></button>
              <span class="px-3 d-flex align-items-center fw-bold small" id="ouvrierPageInfo">1/1</span>
              <button class="btn btn-outline-secondary btn-sm" id="nextOuvrierPage" disabled><i class="bi bi-chevron-right"></i></button>
              <button class="btn btn-outline-secondary btn-sm" id="lastOuvrierPage" disabled><i class="bi bi-chevron-double-right"></i></button>
            </div>
            <button class="btn btn-secondary ms-3" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
  <!-- MODAL CLASSEMENT TRANSPORTEURS -->
  <div class="modal fade" id="modalTransporteurDetails" tabindex="-1" aria-labelledby="modalTransporteurLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width:96vw;">
      <div class="modal-content shadow-lg border-0">

        <!-- HEADER -->
        <div class="modal-header py-4 border-0" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);">
          <div class="d-flex align-items-center w-100">
            <div class="me-3" style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px;backdrop-filter:blur(10px);">
              <i class="bi bi-truck display-6 text-white"></i>
            </div>
            <div class="flex-grow-1">
              <h4 class="modal-title mb-1 fw-bold text-white" id="modalTransporteurLabel">Classement des Transporteurs</h4>
              <p class="mb-0 text-white-50"><i class="bi bi-info-circle me-1"></i>Classement par poids net livré</p>
            </div>
            <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
          </div>
        </div>

        <!-- TOOLBAR -->
        <div class="px-4 pt-3 pb-2" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1" style="min-width:220px;max-width:380px;">
              <div class="input-group input-group-sm shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchTransporteur" class="form-control border-start-0 ps-0"
                       placeholder="Rechercher transporteur…" style="border-radius:0 8px 8px 0;">
                <button id="resetSearchTransporteur" class="btn btn-outline-secondary btn-sm" style="border-radius:0 8px 8px 0;display:none;">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background:#1e293b;font-size:.82rem;">
                <i class="bi bi-people me-1"></i><span id="transCount">0</span> transporteurs
              </span>
              <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background:#dc2626;font-size:.82rem;">
                <i class="bi bi-boxes me-1"></i><span id="transTotalPoids">0</span> kg total
              </span>
              <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background:#2563eb;font-size:.82rem;">
                <i class="bi bi-arrow-repeat me-1"></i><span id="transTotalCharg">0</span> chargements
              </span>
            </div>
          </div>
        </div>

        <!-- TABLE -->
        <div class="modal-body p-0">
          <div class="table-responsive" style="max-height:65vh;overflow-y:auto;">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="tableTransporteurs">
              <thead style="position:sticky;top:0;z-index:2;background:linear-gradient(135deg,#1e293b,#334155);">
                <tr>
                  <th class="text-white fw-semibold" style="padding:12px 16px;">#</th>
                  <th class="text-white fw-semibold" style="padding:12px 16px;">Photo</th>
                  <th class="text-white fw-semibold" style="padding:12px 16px;">Nom Complet</th>
                  <th class="text-white fw-semibold" style="padding:12px 16px;">Téléphone</th>
                  <th class="text-white fw-semibold" style="padding:12px 16px;">Adresse Source</th>
                  <th class="text-white fw-semibold" style="padding:12px 16px;">Poids Total (kg)</th>
                  <th class="text-white fw-semibold" style="padding:12px 16px;">Chargements</th>
                </tr>
              </thead>
              <tbody id="tbodyTransporteurs">
                <tr><td colspan="7" class="text-center text-muted p-4">Chargement…</td></tr>
              </tbody>
            </table>
          </div>
          <!-- EMPTY STATE -->
          <div id="emptyStateTransporteur" style="display:none;" class="text-center py-5 px-3">
            <i class="bi bi-truck" style="font-size:3rem;color:#cbd5e1;"></i>
            <p class="mt-3 fw-semibold text-muted">Aucun transporteur trouvé</p>
            <button class="btn btn-outline-secondary btn-sm mt-1" id="resetEmptyTransporteur">
              <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
            </button>
          </div>
        </div>

        <!-- FOOTER / PAGINATION -->
        <div class="modal-footer justify-content-between align-items-center py-2 px-3" style="background:#f8fafc;border-top:1px solid #e2e8f0;">
          <div class="d-flex align-items-center gap-2">
            <small class="text-muted">Afficher</small>
            <select id="transPerPage" class="form-select form-select-sm" style="width:72px;">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <small class="text-muted">par page</small>
          </div>
          <small class="text-muted" id="transPageInfo">— — —</small>
          <div class="d-flex gap-1 align-items-center">
            <button id="firstTransPage"  class="btn btn-outline-secondary btn-sm px-2" title="Première"><i class="bi bi-chevron-double-left"></i></button>
            <button id="prevTransPage"   class="btn btn-outline-secondary btn-sm px-2" title="Précédente"><i class="bi bi-chevron-left"></i></button>
            <button id="nextTransPage"   class="btn btn-outline-secondary btn-sm px-2" title="Suivante"><i class="bi bi-chevron-right"></i></button>
            <button id="lastTransPage"   class="btn btn-outline-secondary btn-sm px-2" title="Dernière"><i class="bi bi-chevron-double-right"></i></button>
            <button class="btn btn-secondary btn-sm px-3 ms-2" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>

      </div>
    </div>
  </div>
<!-- MODAL TARATONAGE CLIENT CREDIT -->
<div class="modal fade" id="modalTaratonageCredit" tabindex="-1" aria-labelledby="modalTaratonageCreditLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header py-4" style="background:linear-gradient(135deg,#0d9488 0%,#14b8a6 100%);">
        <div class="d-flex align-items-center w-100">
          <div class="modal-icon-container me-3" style="background:rgba(255,255,255,.2);border-radius:12px;padding:12px;backdrop-filter:blur(10px);">
            <i class="bi bi-credit-card display-6 text-white"></i>
          </div>
          <div class="flex-grow-1">
            <h4 class="modal-title mb-1 fw-bold text-white" id="modalTaratonageCreditLabel">Liste des Taratonages Client à Crédit</h4>
            <p class="mb-0 text-white-50"><i class="bi bi-info-circle me-1"></i>Suivi des créditeurs olive</p>
          </div>
          <div class="d-flex gap-2 me-3">
            <a href="https://meknassaolive.com/gestionMeknassa/bon-client-crediteur/soldeClientOlive.php"
               class="btn btn-light btn-sm fw-bold text-teal d-flex align-items-center gap-1 shadow-sm"
               target="_blank" style="border-radius:8px;color:#0d9488 !important;">
              <i class="bi bi-check-circle-fill"></i> Statut Créditeurs
            </a>
            <a href="https://meknassaolive.com/gestionMeknassa/reglement-status/status-client-crediteur.php"
               class="btn btn-light btn-sm fw-bold d-flex align-items-center gap-1 shadow-sm"
               target="_blank" style="border-radius:8px;color:#0d9488 !important;">
              <i class="bi bi-journal-text"></i> Journal
            </a>
          </div>
          <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <div class="bg-light py-3 px-4 border-bottom">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group input-group-sm" style="border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="searchTaratonage" class="form-control border-start-0"
                     placeholder="Rechercher client, prénom ou téléphone…" style="border-radius:0 8px 8px 0;">
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end gap-3">
              <div class="px-3 py-2 rounded-3" style="background:rgba(13,148,136,.1);border:1px solid rgba(13,148,136,.25);font-size:.85rem;">
                <i class="bi bi-list-check me-1" style="color:#0d9488;"></i>
                <span id="taratonageTotalCount" class="fw-bold" style="color:#0d9488;">0</span> enregistrements
              </div>
              <div class="px-3 py-2 rounded-3" style="background:rgba(13,148,136,.1);border:1px solid rgba(13,148,136,.25);font-size:.85rem;">
                <i class="bi bi-cash-coin me-1" style="color:#0d9488;"></i>
                Total : <span id="taratonageSommeTotal" class="fw-bold" style="color:#0d9488;">0.00</span> MAD
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height:65vh;">
          <table class="table table-hover align-middle mb-0" id="tableTaratonageCredit">
            <thead class="sticky-top" style="top:0;z-index:10;background:#0d9488;">
              <tr>
                <th class="ps-4 text-white" style="width:5%;">#</th>
                <th class="text-white" style="width:10%;"><i class="bi bi-calendar me-1"></i>Date</th>
                <th class="text-white" style="width:22%;"><i class="bi bi-person me-1"></i>Client</th>
                <th class="text-white text-end" style="width:12%;"><i class="bi bi-box-seam me-1"></i>Poids Net (kg)</th>
                <th class="text-white text-end" style="width:12%;"><i class="bi bi-tag me-1"></i>P.U (MAD)</th>
                <th class="text-white text-end" style="width:14%;"><i class="bi bi-currency-exchange me-1"></i>Montant (MAD)</th>
                <th class="text-white text-center pe-4" style="width:10%;"><i class="bi bi-file-image me-1"></i>Bon Scanné</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <div class="spinner-border mb-3" style="color:#0d9488;" role="status"><span class="visually-hidden">Chargement…</span></div>
                  <p class="mb-0">Chargement des taratonages…</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div id="emptyStateTaratonage" class="d-none text-center py-5">
          <i class="bi bi-credit-card display-1 text-muted opacity-50"></i>
          <h5 class="text-muted mt-3">Aucun taratonage trouvé</h5>
          <button class="btn mt-2" style="background:#0d9488;color:#fff;" id="resetSearchTaratonage">
            <i class="bi bi-arrow-clockwise me-1"></i>Réinitialiser
          </button>
        </div>
      </div>

      <div class="modal-footer bg-light py-3 px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <span class="text-muted small">
            Affichage de <span id="taratonageStartIndex" class="fw-bold text-dark">0</span>
            à <span id="taratonageEndIndex" class="fw-bold text-dark">0</span>
            sur <span id="taratonageTotalItems" class="fw-bold" style="color:#0d9488;">0</span> enregistrements
          </span>
          <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 me-2 small text-muted">Afficher :</label>
            <select id="taratonagePerPage" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
              <option value="10">10</option>
              <option value="20" selected>20</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <div class="btn-group ms-2">
              <button class="btn btn-sm" id="prevPage" style="border:1px solid #0d9488;color:#0d9488;" disabled><i class="bi bi-chevron-left"></i></button>
              <span class="px-3 d-flex align-items-center fw-bold small" id="taratonagePageInfo" style="color:#0d9488;">1/1</span>
              <button class="btn btn-sm" id="nextPage" style="border:1px solid #0d9488;color:#0d9488;" disabled><i class="bi bi-chevron-right"></i></button>
            </div>
            <button class="btn btn-secondary ms-3" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL VERSEMENTS DE GRIGNON -->
<div class="modal fade" id="modalVersementGrignon" tabindex="-1" aria-labelledby="modalVersementGrignonLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalVersementGrignonLabel">Détails des Versements de Grignon</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle" id="tableVersementGrignon">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Date Versement</th>
                <th>Montant (MAD)</th>
                <th>Mode Paiement</th>
                <th>Patron</th>
                <th>Chauffeur</th>
                <th>Note</th>
                <th>Image</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="8" class="text-center text-muted">Chargement...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL MACHINES & PIÈCES ASSOCIÉES -->
<!-- MODAL MACHINES & PIÈCES ASSOCIÉES - STYLE CORRIGÉ -->
<!-- MODAL MACHINES & PIÈCES ASSOCIÉES - AVEC TOGGLE -->
<div class="modal fade" id="modalMachinePieces" tabindex="-1" aria-labelledby="modalMachinePiecesLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:96vw;">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-info text-white sticky-top">
        <h5 class="modal-title"><i class="bi bi-gear-fill me-2"></i> Liste des Machines & Pièces Associées</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height:78vh; overflow-y:auto;">
          <table class="table table-striped table-hover align-middle mb-0" id="tableMachinePieces">
            <thead class="table-dark sticky-top" style="top: 0;">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Machine</th>
                <th style="width: 15%;">Pièce</th>
                <th style="width: 10%;">Référence</th>
                <th style="width: 20%;">Description</th>
                <th style="width: 8%;">Quantité</th>
                <th style="width: 12%;">Statut</th>
                <th style="width: 10%;">Date ajout</th>
                <th style="width: 5%;">Image</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="9" class="text-center text-muted p-4">Chargement...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination améliorée -->
      <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-secondary btn-sm" id="prevMachinePage" disabled>
            <i class="bi bi-chevron-left"></i> Précédent
          </button>
          <span id="machinePageInfo" class="fw-bold text-muted mx-2">Page 1/1</span>
          <button class="btn btn-outline-secondary btn-sm" id="nextMachinePage" disabled>
            Suivant <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small">Afficher :</span>
          <select id="machinePerPage" class="form-select form-select-sm" style="width: auto;">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
        </div>
        <button class="btn btn-secondary px-4" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
<!-- MODAL IMAGES MACHINE + PIÈCES -->
<div class="modal fade" id="modalMachineImages" tabindex="-1" aria-labelledby="modalMachineImagesLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:90vw;">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="modalMachineImagesLabel"><i class="bi bi-images me-2"></i> Images du Machine & Pièces Associées</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <h5 class="text-center fw-bold mb-3" id="machineTitle"></h5>

        <h6 class="text-primary mb-2"><i class="bi bi-cpu me-2"></i> Images du Machine</h6>
        <div id="machineImages" class="row g-3 mb-4"></div>

        <hr>

        <h6 class="text-primary mb-3"><i class="bi bi-tools me-2"></i> Images des Pièces Associées</h6>
        <div id="pieceImages" class="row g-3"></div>
      </div>

      <div class="modal-footer bg-light">
        <button class="btn btn-secondary px-4" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<style>
#modalMachinePieces .modal-dialog { 
  width:96vw !important; 
  max-width:96vw !important; 
}

#modalMachinePieces .table thead th {
  position: sticky; 
  top: 0; 
  z-index: 2;
  background: #212529; 
  color: #fff;
}

/* Style pour l'en-tête de machine - CLICKABLE */
#modalMachinePieces tbody tr.machine-header {
  cursor: pointer;
  transition: all 0.3s ease;
}

#modalMachinePieces tbody tr.machine-header td {
  background: linear-gradient(135deg, #e0f2fe, #bae6fd) !important;
  font-weight: bold;
  color: #0369a1;
  border-top: 3px solid #0ea5e9;
  border-bottom: 2px solid #0ea5e9;
  font-size: 1.1rem;
  padding: 12px 8px;
  position: relative;
}

/* Indicateur de toggle */
.machine-header .toggle-indicator {
  transition: transform 0.3s ease;
  display: inline-block;
  margin-right: 8px;
}

.machine-header.expanded .toggle-indicator {
  transform: rotate(90deg);
}

/* Style pour les lignes de pièces (cachées par défaut) */
#modalMachinePieces tbody tr.piece-row {
  display: none;
  background: #f8fafc;
  border-left: 3px solid #e2e8f0;
  transition: all 0.2s ease;
}

#modalMachinePieces tbody tr.piece-row.visible {
  display: table-row;
}

#modalMachinePieces tbody tr.piece-row:hover td {
  background: #f1f5f9;
  border-left-color: #0ea5e9;
}

/* Indentation visuelle pour les pièces */
#modalMachinePieces tbody tr.piece-row td:nth-child(2) {
  padding-left: 40px;
  font-style: italic;
  color: #64748b;
}

/* Style pour machine sans pièces */
.machine-header.no-pieces {
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0) !important;
  color: #64748b !important;
  border-color: #cbd5e1 !important;
}

.machine-header.no-pieces .toggle-indicator {
  opacity: 0.3;
}

/* Correction de la largeur de la colonne Description */
#modalMachinePieces table td:nth-child(5),
#modalMachinePieces table th:nth-child(5) {
  max-width: 280px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Ajustement du texte coupé au survol */
#modalMachinePieces table td:nth-child(5):hover {
  white-space: normal;
  background: #f8fafc;
  z-index: 2;
  position: relative;
  max-width: 450px;
  word-break: break-word;
  padding: 8px;
  border-radius: 4px;
  box-shadow: 0 0 8px rgba(0,0,0,0.1);
}

/* Style pour le bouton Voir Images */
.btn-view-images {
  transition: all 0.3s ease;
  border: 1px solid #475569 !important;
}

.btn-view-images:hover {
  background: #1e293b !important;
  color: white !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Animation pour l'expansion */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.piece-row.visible {
  animation: slideDown 0.3s ease-out;
}
/* === STYLE DU BOUTON “Vérifier Statut des Créditeurs” === */
#modalTaratonageCredit .modal-header a.btn-light {
  background-color: #f0fdfa;
  border: 1px solid #0d9488 !important;
  color: #0f766e !important;
  font-weight: 600;
  border-radius: 8px;
  padding: 6px 12px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}
#modalTaratonageCredit .modal-header a.btn-light:hover {
  background-color: #0d9488 !important;
  color: #fff !important;
  transform: scale(1.05);
  box-shadow: 0 0 10px rgba(13,148,136,0.4);
}
#modalTaratonageCredit .modal-header a.btn-light i {
  font-size: 1rem;
}
#modalMachineImages img {
  object-fit: cover;
  border-radius: 8px;
  transition: transform 0.2s ease;
  cursor: zoom-in;
}
#modalMachineImages img:hover {
  transform: scale(1.05);
}
</style>

<style>
#modalMachinePieces .modal-dialog { width:96vw !important; max-width:96vw !important; }
#modalMachinePieces .table thead th {
  position: sticky; top: 0; z-index: 2;
  background: #212529; color: #fff;
}
#modalMachinePieces tbody tr.machine-header td {
  background: #e0f2fe;
  font-weight: bold;
  color: #0369a1;
  border-top: 2px solid #0ea5e9;
}
</style>






<!-- STYLE FIX MODAL OUVRIER & TRANSPORTEUR -->
<style>


 
  /* === STYLE INPUT RECHERCHE TARATONAGE === */
  #searchTaratonage { border: none; box-shadow: none; }
  #searchTaratonage:focus { border-color: #0d9488; box-shadow: none; }

  .greeting-animate {
    display: inline-block;
    font-size: 1.3rem;
    padding: 10px 20px;
    border-radius: 12px;
    color: #fff;
    background: linear-gradient(90deg, #16a34a, #0d9488);
    box-shadow: 0 0 10px rgba(22, 163, 74, 0.3);
    animation: fadeInScale 1.8s ease forwards, glowWave 3s ease-in-out infinite;
    transition: all 0.4s ease;
  }

  /* === Effet au survol === */
  .greeting-animate:hover {
    transform: scale(1.08) rotate(-1deg);
    box-shadow: 0 0 20px rgba(13, 148, 136, 0.6),
      0 0 30px rgba(22, 163, 74, 0.4);
    background: linear-gradient(90deg, #0d9488, #16a34a);
    letter-spacing: 1.5px;
  }

  /* === Apparition douce === */
  @keyframes fadeInScale {
    0% {
      opacity: 0;
      transform: scale(0.7);
    }

    100% {
      opacity: 1;
      transform: scale(1);
    }
  }

  /* === Lueur pulsante en continu === */
  @keyframes glowWave {

    0%,
    100% {
      box-shadow: 0 0 10px rgba(22, 163, 74, 0.3);
    }

    50% {
      box-shadow: 0 0 18px rgba(13, 148, 136, 0.6);
    }
  }

  span.text-success.fw-bold.d-block.text-center {
    background: rgba(22, 163, 74, 0.1);
    border: 1px solid #16a34a;
    border-radius: 8px;
    padding: 8px 16px;
    display: inline-block;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    font-size: 1rem;
    letter-spacing: 1px;
    color: #166534;
    animation: pulseGlow 2.8s ease-in-out infinite;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  span.text-success.fw-bold.d-block.text-center:hover {
    transform: scale(1.05);
    box-shadow: 0 0 15px rgba(22, 163, 74, 0.3);
  }

  /* === Animation Glow Subtile === */
  @keyframes pulseGlow {
    0% {
      box-shadow: 0 0 6px rgba(22, 163, 74, 0.2),
        0 0 12px rgba(22, 163, 74, 0.1);
      background: rgba(22, 163, 74, 0.08);
    }

    50% {
      box-shadow: 0 0 12px rgba(22, 163, 74, 0.45),
        0 0 20px rgba(22, 163, 74, 0.3);
      background: rgba(22, 163, 74, 0.15);
    }

    100% {
      box-shadow: 0 0 6px rgba(22, 163, 74, 0.2),
        0 0 12px rgba(22, 163, 74, 0.1);
      background: rgba(22, 163, 74, 0.08);
    }
  }

  #modalTransporteurDetails .modal-dialog {
    width: 95vw !important;
    max-width: 95vw !important;
  }

  #modalTransporteurDetails .table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #212529;
    color: white;
  }

  #modalTransporteurDetails img {
    object-fit: cover;
    border-radius: 8px;
  }

  #modalOuvrierDetails .modal-dialog {
    width: 96vw !important;
    max-width: 96vw !important;
  }

  #modalOuvrierDetails .table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #212529;
    color: white;
  }

  #modalOuvrierDetails .modal-body {
    padding: 0 !important;
  }

  #modalOuvrierDetails img {
    object-fit: cover;
    border-radius: 6px;
    cursor: zoom-in;
  }

  img {
    cursor: zoom-in;
    transition: transform 0.2s ease;
  }

  img:hover {
    transform: scale(1.05);
  }
  /* === Correction de la largeur de la colonne Description dans Machines & Pièces === */
#modalMachinePieces table td:nth-child(5),
#modalMachinePieces table th:nth-child(5) {
  max-width: 280px;          /* 🔹 limite la largeur */
  white-space: nowrap;       /* pas de retour à la ligne */
  overflow: hidden;          /* cache le texte qui dépasse */
  text-overflow: ellipsis;   /* ajoute “…” */
}

/* Ajustement du texte coupé au survol */
#modalMachinePieces table td:nth-child(5):hover {
  white-space: normal;       /* 🔹 le texte s'affiche complètement au survol */
  background: #f8fafc;       /* petit fond pour la lecture */
  z-index: 2;
  position: relative;
  max-width: 450px;
  word-break: break-word;
  padding: 8px;
  border-radius: 4px;
  box-shadow: 0 0 8px rgba(0,0,0,0.1);
}

/* Style du tooltip natif Bootstrap */
#modalMachinePieces [data-bs-toggle="tooltip"] {
  cursor: help;
}
/* === Global layout du modal Images Machine & Pièces === */
#modalMachineImages .modal-dialog {
  max-width: 95vw;
}

#modalMachineImages .modal-body {
  background-color: #f9fafb;
  border-radius: 8px;
  padding: 20px;
}

/* === TITRES === */
#modalMachineImages h6 {
  font-weight: 600;
  color: #0d6efd;
  margin-top: 1rem;
  border-left: 4px solid #0d6efd;
  padding-left: 8px;
}

/* === IMAGES MACHINE === */
#machineImages img {
  width: 100%;
  height: 220px;
  object-fit: cover;
  border-radius: 10px;
  box-shadow: 0 0 8px rgba(0,0,0,0.15);
  transition: transform 0.2s ease-in-out, box-shadow 0.2s;
  cursor: zoom-in;
}

#machineImages img:hover {
  transform: scale(1.05);
  box-shadow: 0 0 12px rgba(0,0,0,0.25);
}

/* === IMAGES PIÈCES ASSOCIÉES === */
#pieceImages img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 10px;
  box-shadow: 0 0 6px rgba(0,0,0,0.1);
  transition: transform 0.2s ease-in-out, box-shadow 0.2s;
  cursor: zoom-in;
}

#pieceImages img:hover {
  transform: scale(1.04);
  box-shadow: 0 0 10px rgba(0,0,0,0.25);
}

/* === Conteneur image + nom pièce === */
#pieceImages .col-md-3,
#machineImages .col-md-3 {
  padding: 8px;
}

#pieceImages p {
  text-align: center;
  font-size: 0.9rem;
  color: #555;
  margin-top: 6px;
  font-weight: 500;
}

/* === Scroll bar esthétique === */
#modalMachineImages .modal-body::-webkit-scrollbar {
  width: 8px;
}
#modalMachineImages .modal-body::-webkit-scrollbar-thumb {
  background-color: #ccc;
  border-radius: 8px;
}
#modalMachineImages .modal-body::-webkit-scrollbar-thumb:hover {
  background-color: #aaa;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script>

// Animation supplémentaire au clic
document.querySelectorAll('.machine-card').forEach(card => {
  card.addEventListener('click', function() {
    // Effet de pulsation au clic
    this.style.transform = 'scale(0.95)';
    setTimeout(() => {
      this.style.transform = '';
    }, 150);
    
    // Vous pouvez ajouter d'autres effets ici si nécessaire
  });
});

// Effet d'apparition progressive
document.addEventListener('DOMContentLoaded', function() {
  const machineCards = document.querySelectorAll('.machine-card');
  machineCards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
      card.style.transition = 'all 0.6s ease';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, 200 + (index * 100));
  });
});


// === OUVERTURE LIGHTBOX POUR LES IMAGES DE PIÈCES ===
document.addEventListener('click', e => {
  const img = e.target.closest('img[data-images]');
  if (!img) return;

  const list = (img.dataset.images || '').split('||').filter(Boolean);
  const nomPiece = img.dataset.piece || 'Pièce';
  if (!list.length) return;

  // Crée la modale temporaire
  const viewer = document.createElement('div');
  viewer.className = 'modal fade modal-piece-images';
  viewer.innerHTML = `
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-0 text-white">
          <h5 class="modal-title"><i class="bi bi-images me-2"></i>${nomPiece}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <div id="carouselPieceImages" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
              ${list.map((url, i) => `
                <div class="carousel-item ${i === 0 ? 'active' : ''}">
                  <img src="uploads/pieces/${url}" class="d-block w-100 rounded" style="max-height:80vh; object-fit:contain;">
                </div>
              `).join('')}
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselPieceImages" data-bs-slide="prev">
              <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselPieceImages" data-bs-slide="next">
              <span class="carousel-control-next-icon"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(viewer);
  const modal = new bootstrap.Modal(viewer);
  modal.show();
  viewer.addEventListener('hidden.bs.modal', () => viewer.remove());
});

// === OUVERTURE MODAL MACHINES ===
// === OUVERTURE MODAL MACHINES (avec pagination) ===




// === OUVERTURE MODAL MACHINES (VERSION SIMPLIFIÉE ET FONCTIONNELLE) ===
document.querySelectorAll('.machine-card').forEach(card => {
  card.addEventListener('click', function () {
    const tbody = document.querySelector('#tableMachinePieces tbody');
    const modalEl = document.getElementById('modalMachinePieces');
    const btnPrev = document.getElementById('prevMachinePage');
    const btnNext = document.getElementById('nextMachinePage');
    const pageInfo = document.getElementById('machinePageInfo');
    const perPageSelect = document.getElementById('machinePerPage');
    
    let currentPage = 1;
    let currentPerPage = parseInt(perPageSelect?.value || 10);
    let grouped = {};
    let expandedMachines = new Set();

    function renderPage(page) {
      console.log(`Rendering page ${page}`);
      
      tbody.innerHTML = '';

      const machines = Object.keys(grouped);
      const totalPages = Math.ceil(machines.length / currentPerPage);
      
      if (page < 1) page = 1;
      if (page > totalPages) page = totalPages;

      const start = (page - 1) * currentPerPage;
      const end = start + currentPerPage;
      const slice = machines.slice(start, end);

      let globalIndex = start + 1;
      
      slice.forEach(machineName => {
        const pieces = grouped[machineName] || [];
        const hasPieces = pieces.length > 0;
        const isExpanded = expandedMachines.has(machineName);

        // === LIGNE EN-TÊTE DE LA MACHINE ===
        const trHead = document.createElement('tr');
        trHead.classList.add('machine-header');
        if (!hasPieces) trHead.classList.add('no-pieces');
        if (isExpanded) trHead.classList.add('expanded');
        
        trHead.innerHTML = `
          <td colspan="9">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="toggle-indicator">
                  <i class="bi bi-chevron-right"></i>
                </span>
                <i class="bi bi-cpu me-2"></i>
                <strong>${machineName}</strong>
                <small class="text-muted ms-2">(${pieces.length} pièce(s))</small>
                ${!hasPieces ? '<span class="badge bg-secondary ms-2">Aucune pièce</span>' : ''}
              </div>
              <div>
                <button class="btn btn-sm btn-outline-dark btn-view-images" data-machine="${machineName}">
                  <i class="bi bi-images me-1"></i>Voir Images
                </button>
              </div>
            </div>
          </td>`;
        tbody.appendChild(trHead);

        // Ajouter l'event de toggle
        trHead.addEventListener('click', function(e) {
          if (e.target.closest('.btn-view-images')) return;
          if (!hasPieces) return;
          
          toggleMachinePieces(machineName, trHead);
        });

        // === LIGNES DES PIÈCES ASSOCIÉES ===
        if (hasPieces && isExpanded) {
          pieces.forEach((p) => {
            const quantite = parseFloat(p.quantite) || 0;
            if (quantite <= 0) return;

            let color = 'secondary';
            const statut = (p.statut || '').toLowerCase();
            if (statut.includes('marche')) color = 'success';
            else if (statut.includes('panne')) color = 'danger';
            else if (statut.includes('maintenance')) color = 'warning';

            const img = p.first_image
              ? `<img src="uploads/pieces/${p.first_image}" width="45" height="45" class="rounded shadow-sm me-1 img-piece" 
                  title="${p.nb_images || 0} image(s)" data-images="${p.images_list || ''}" data-piece="${p.nom_piece}">`
              : '<span class="text-muted">-</span>';

            const tr = document.createElement('tr');
            tr.classList.add('piece-row', 'visible');
            tr.innerHTML = `
              <td>${globalIndex++}</td>
              <td>${p.nom_machine}</td>
              <td><strong>${p.nom_piece}</strong></td>
              <td><code>${p.ref_piece || '-'}</code></td>
              <td>${p.description || '-'}</td>
              <td><span class="badge bg-primary">${quantite}</span></td>
              <td>
                <span class="badge bg-${color} px-3 py-2">
                  ${(p.statut || 'inconnu').replace('_', ' ').toUpperCase()}
                </span>
              </td>
              <td><small class="text-muted">${p.date_fmt || '-'}</small></td>
              <td>${img}</td>
            `;
            tbody.appendChild(tr);
          });
        }
      });

      // Mettre à jour la pagination
      updatePagination(page, totalPages);
      
      // Attacher les événements des boutons
      attachButtonEvents();
    }

    function updatePagination(currentPage, totalPages) {
      pageInfo.textContent = `Page ${currentPage} / ${Math.max(totalPages, 1)}`;
      btnPrev.disabled = currentPage <= 1;
      btnNext.disabled = currentPage >= totalPages;
    }

    function attachButtonEvents() {
      // Boutons d'images
      tbody.querySelectorAll('.btn-view-images').forEach(btn => {
        btn.addEventListener('click', e => {
          e.stopPropagation();
          const machine = e.currentTarget.dataset.machine;
          showMachineImages(machine);
        });
      });

      // Images de pièces
      tbody.querySelectorAll('.img-piece').forEach(img => {
        img.addEventListener('click', e => {
          e.stopPropagation();
          const list = (e.target.dataset.images || '').split('||').filter(Boolean);
          const nomPiece = e.target.dataset.piece;
          if (list.length > 0) {
            showPieceCarousel(list, nomPiece);
          }
        });
      });
    }

    function toggleMachinePieces(machineName, headerRow) {
      if (expandedMachines.has(machineName)) {
        // Cacher les pièces
        expandedMachines.delete(machineName);
        headerRow.classList.remove('expanded');
        
        // Supprimer les lignes de pièces
        const allRows = Array.from(tbody.querySelectorAll('tr'));
        const headerIndex = allRows.indexOf(headerRow);
        
        for (let i = headerIndex + 1; i < allRows.length; i++) {
          if (allRows[i].classList.contains('machine-header')) break;
          allRows[i].remove();
        }
      } else {
        // Afficher les pièces
        expandedMachines.add(machineName);
        headerRow.classList.add('expanded');
        
        // Re-rendre la page pour afficher les pièces
        renderPage(currentPage);
      }
    }

    // Événements de pagination
    btnPrev.onclick = () => { 
      if (currentPage > 1) { 
        currentPage--; 
        renderPage(currentPage); 
      } 
    };
    
    btnNext.onclick = () => { 
      if (currentPage < Math.ceil(Object.keys(grouped).length / currentPerPage)) { 
        currentPage++; 
        renderPage(currentPage); 
      } 
    };

    // Événement changement du nombre par page
    if (perPageSelect) {
      perPageSelect.onchange = (e) => {
        currentPerPage = parseInt(e.target.value);
        currentPage = 1;
        renderPage(currentPage);
      };
    }

    // 🔹 Chargement AJAX initial
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Chargement des machines et pièces...</td></tr>';

    fetch('includes/ajax/admin/get_statut_piece_machine.php', {
      method: 'POST',
      body: new URLSearchParams({ per_page: 'all' })
    })
      .then(res => {
        if (!res.ok) throw new Error(`Erreur HTTP: ${res.status}`);
        return res.json();
      })
      .then(json => {
        console.log('Réponse API:', json);
        
        if (!json || !json.ok) throw new Error(json?.error || 'Format de réponse invalide');
        
        const rows = json.rows || [];
        
        if (!rows.length) {
          tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Aucune machine trouvée.</td></tr>';
          return;
        }

        // Grouper les pièces par machine
        grouped = {};
        rows.forEach(r => {
          if (!r || typeof r !== 'object') return;
          
          const machineName = r.nom_machine;
          if (!machineName) return;
          
          if (!grouped[machineName]) grouped[machineName] = [];
          
          // Validation des données de pièce
          if (r.nom_piece || r.ref_piece) {
            grouped[machineName].push({
              nom_machine: machineName,
              nom_piece: r.nom_piece,
              ref_piece: r.ref_piece,
              description: r.description,
              quantite: r.quantite,
              statut: r.statut,
              date_fmt: r.date_fmt,
              first_image: r.first_image,
              nb_images: r.nb_images,
              images_list: r.images_list
            });
          }
        });

        const machineCount = Object.keys(grouped).length;
        if (machineCount === 0) {
          tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Aucune donnée valide trouvée.</td></tr>';
          return;
        }

        console.log(`Machines chargées: ${machineCount}`);
        
        // Rendre la première page
        renderPage(currentPage);
        new bootstrap.Modal(modalEl).show();
      })
      .catch(err => {
        console.error('Erreur détaillée:', err);
        tbody.innerHTML = `
          <tr>
            <td colspan="9" class="text-danger text-center">
              <div>
                <i class="bi bi-exclamation-triangle me-2"></i>
                Erreur de chargement: ${err.message}
              </div>
            </td>
          </tr>`;
      });
  });
});

// === FONCTIONS AUXILIAIRES POUR LES IMAGES ===
function showMachineImages(machineName) {
  const modal = new bootstrap.Modal(document.getElementById('modalMachineImages'));
  document.getElementById('machineTitle').textContent = machineName;
  const machineDiv = document.getElementById('machineImages');
  const piecesDiv = document.getElementById('pieceImages');

  machineDiv.innerHTML = '<div class="text-center text-muted">Chargement des images...</div>';
  piecesDiv.innerHTML = '';

  fetch('includes/ajax/admin/get_machine_images.php', {
    method: 'POST',
    body: new URLSearchParams({ machine: machineName })
  })
    .then(res => {
      if (!res.ok) throw new Error(`Erreur HTTP: ${res.status}`);
      return res.json();
    })
    .then(json => {
      if (!json || !json.ok) throw new Error(json?.error || 'Format de réponse invalide');

      // Images Machine
      const imgsM = [...new Set((json.machine_images || []).filter(img => img))];
      machineDiv.innerHTML = imgsM.length
        ? imgsM.map(img => `
            <div class="col-md-3 col-sm-4 col-6 mb-3">
              <img src="/gestionMeknassa/uploads/machines/${img}" class="img-fluid shadow-sm rounded" alt="Machine" style="height: 200px; object-fit: cover;">
            </div>`).join('')
        : '<div class="text-center text-muted">Aucune image machine trouvée.</div>';

      // Images Pièces Associées
      const imgsP = json.piece_images || [];
      const renderedPieces = new Set();

      piecesDiv.innerHTML = '';

      imgsP.forEach(piece => {
        if (!piece || !piece.nom_piece || renderedPieces.has(piece.nom_piece)) return;
        renderedPieces.add(piece.nom_piece);

        const uniqueImgs = (piece.images || []).filter(url => url);
        const imagesHTML = uniqueImgs.length
          ? uniqueImgs.map(url => `
              <div class="col-md-3 col-sm-4 col-6 mb-3">
                <img src="/gestionMeknassa/uploads/pieces/${url}" class="img-fluid rounded shadow-sm" alt="${piece.nom_piece}" style="height: 180px; object-fit: cover;">
                <p class="small text-center mt-1">${piece.nom_piece}</p>
              </div>`).join('')
          : '<div class="col text-center text-muted">Aucune image disponible</div>';

        piecesDiv.innerHTML += `
          <div class="col-12 mb-4">
            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>${piece.nom_piece}</h6>
            <div class="row g-2">${imagesHTML}</div>
          </div>`;
      });

      if (!piecesDiv.innerHTML.trim())
        piecesDiv.innerHTML = '<div class="text-center text-muted">Aucune image de pièce associée.</div>';

      modal.show();
    })
    .catch(err => {
      console.error('Erreur images:', err);
      machineDiv.innerHTML = `
        <div class="text-danger text-center">
          <i class="bi bi-exclamation-triangle me-2"></i>
          ${err.message}
        </div>`;
    });
}

function showPieceCarousel(list, nomPiece) {
  if (!list || !list.length) return;

  const modalEl = document.getElementById('modalPieceImagesViewer');
  if (!modalEl) return;
  
  const titleEl = document.getElementById('pieceViewerTitle');
  const carouselInner = modalEl.querySelector('.carousel-inner');

  titleEl.textContent = nomPiece || 'Image';
  carouselInner.innerHTML = list
    .map((url, i) => `
      <div class="carousel-item ${i === 0 ? 'active' : ''}">
        <img src="uploads/pieces/${url}" class="d-block w-100 rounded" style="max-height:80vh; object-fit:contain;">
      </div>
    `)
    .join('');

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

































// TABLE COMMANDE BIDONS — loaded inside cardBidon click handler above


  // === TARATONAGE CLIENT CRÉDIT HANDLER ===
  document.getElementById('cardTaratonageCredit').addEventListener('click', function () {
    const modal       = new bootstrap.Modal(document.getElementById('modalTaratonageCredit'));
    const tbody       = document.querySelector('#tableTaratonageCredit tbody');
    const emptyState  = document.getElementById('emptyStateTaratonage');
    const inputSearch = document.getElementById('searchTaratonage');
    const perPageSel  = document.getElementById('taratonagePerPage');
    const totalCount  = document.getElementById('taratonageTotalCount');
    const totalItems  = document.getElementById('taratonageTotalItems');
    const startIdx    = document.getElementById('taratonageStartIndex');
    const endIdx      = document.getElementById('taratonageEndIndex');
    const pageInfoEl  = document.getElementById('taratonagePageInfo');
    const btnPrev     = document.getElementById('prevPage');
    const btnNext     = document.getElementById('nextPage');
    const resetBtn    = document.getElementById('resetSearchTaratonage');
    let currentPage = 1;

    function getPerPage() { return parseInt(perPageSel?.value) || 20; }

    function loadTaratonages(page = 1, q = '') {
      const pp = getPerPage();
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-5">
        <div class="spinner-border mb-2" style="color:#0d9488;" role="status"></div>
        <p class="mb-0">Chargement…</p></td></tr>`;
      if (emptyState) emptyState.classList.add('d-none');

      fetch(`includes/ajax/admin/get_List_client_crediteur.php?page=${page}&perPage=${pp}&q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(json => {
          const rows  = json.rows || [];
          const total = json.total || rows.length;
          const totalPages = Math.max(1, Math.ceil(total / pp));
          const start = (page - 1) * pp;

          // Update badges
          if (totalCount)  totalCount.textContent  = total;
          document.getElementById('taratonageSommeTotal').textContent = json.somme_totale || '0.00';
          if (totalItems)  totalItems.textContent  = total;
          if (startIdx)    startIdx.textContent    = total ? start + 1 : 0;
          if (endIdx)      endIdx.textContent      = Math.min(start + pp, total);
          if (pageInfoEl)  pageInfoEl.textContent  = `${page}/${totalPages}`;
          if (btnPrev) btnPrev.disabled = page <= 1;
          if (btnNext) btnNext.disabled = (page * pp) >= total;

          tbody.innerHTML = '';
          if (!rows.length) {
            if (emptyState) emptyState.classList.remove('d-none');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Aucun résultat trouvé.</td></tr>';
            return;
          }

          rows.forEach((r, i) => {
            const scan = r.scanne_bon_url
              ? `<img src="${r.scanne_bon_url}" width="46" height="46" class="rounded shadow-sm">`
              : '<span class="text-muted">-</span>';
            const tr = document.createElement('tr');
            tr.style.cssText = 'transition:all .25s ease;border-left:3px solid transparent;';
            tr.onmouseenter = () => { tr.style.background='linear-gradient(90deg,#f0fdfa,#fff)'; tr.style.borderLeftColor='#0d9488'; tr.style.transform='translateX(3px)'; };
            tr.onmouseleave = () => { tr.style.background=''; tr.style.borderLeftColor='transparent'; tr.style.transform=''; };
            tr.innerHTML = `
              <td class="ps-4 text-muted small">${start + i + 1}</td>
              <td>${r.date_taratonage}</td>
              <td class="fw-semibold">${r.client_nom}</td>
              <td class="text-end">${r.poid_net}</td>
              <td class="text-end">${r.prix_unitaire}</td>
              <td class="text-end fw-bold">${r.montant_total}</td>
              <td class="text-center pe-4">${scan}</td>`;
            tbody.appendChild(tr);
          });
        })
        .catch(err => {
          tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-4"><i class="bi bi-exclamation-triangle me-2"></i>${err.message}</td></tr>`;
        });
    }

    btnNext.onclick     = () => { currentPage++; loadTaratonages(currentPage, inputSearch.value); };
    btnPrev.onclick     = () => { if (currentPage > 1) currentPage--; loadTaratonages(currentPage, inputSearch.value); };
    inputSearch.oninput = () => { currentPage = 1; loadTaratonages(1, inputSearch.value); };
    if (perPageSel)  perPageSel.onchange  = () => { currentPage = 1; loadTaratonages(1, inputSearch.value); };
    if (resetBtn)    resetBtn.onclick     = () => { inputSearch.value = ''; currentPage = 1; loadTaratonages(1, ''); };

    loadTaratonages();
    modal.show();
  });




  // === UNIVERSAL IMAGE PREVIEW (ENLARGED VIEW) ===
  document.addEventListener('click', (e) => {
    const target = e.target;
    if (target.tagName === 'IMG' && !target.closest('.modal-image-viewer')) {
      const src = target.getAttribute('src');
      if (!src) return;

      // Create modal dynamically
      const viewer = document.createElement('div');
      viewer.className = 'modal fade modal-image-viewer';
      viewer.innerHTML = `
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-0 shadow-lg">
          <div class="modal-body p-0 text-center">
            <img src="${src}" class="img-fluid rounded" style="max-height:85vh; object-fit:contain;">
          </div>
        </div>
      </div>
    `;
      document.body.appendChild(viewer);
      const modal = new bootstrap.Modal(viewer);
      modal.show();

      // Remove modal on close
      viewer.addEventListener('hidden.bs.modal', () => viewer.remove());
    }
  });
  document.addEventListener('DOMContentLoaded', () => {
    const cardGrignon = document.getElementById('cardVersementGrignon');
    if (!cardGrignon) return;

    cardGrignon.addEventListener('click', function () {
      const tbody = document.querySelector('#tableVersementGrignon tbody');
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Chargement...</td></tr>';

      fetch('includes/ajax/admin/get_List_versement_grignon.php')
        .then(res => res.json())
        .then(json => {
          if (!json.ok) throw new Error(json.error || 'Erreur inconnue');
          const rows = json.rows || [];

          if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Aucun versement trouvé.</td></tr>';
            return;
          }

          tbody.innerHTML = '';
          rows.forEach((r, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${i + 1}</td>
            <td>${r.date_fmt}</td>
            <td><strong>${parseFloat(r.montant).toFixed(2)}</strong></td>
            <td>${r.mode_paiement || '-'}</td>
            <td>${r.nom_patron || ''} ${r.prenom_patron || ''}</td>
            <td>${r.nom_chauffeur || ''} ${r.prenom_chauffeur || ''}</td>
            <td>${r.note || '-'}</td>
            <td>
              ${r.image_versement
                ? `<img src="${r.image_versement}" width="45" height="45" class="rounded shadow-sm" alt="photo">`
                : '<span class="text-muted">-</span>'}
            </td>
          `;
            tbody.appendChild(tr);
          });

          new bootstrap.Modal(document.getElementById('modalVersementGrignon')).show();
        })
        .catch(err => {
          console.error(err);
          tbody.innerHTML = `<tr><td colspan="8" class="text-danger text-center">${err.message}</td></tr>`;
        });
    });
  });

  // === REGLEMENTS FOURNISSEURS HANDLER ===
  document.getElementById('cardReglementFournisseur').addEventListener('click', function () {
    const tbody = document.querySelector('#tableReglementFournisseur tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Chargement...</td></tr>';

    fetch('includes/ajax/admin/listReglements-Total.php', {
      method: 'POST',
      body: new URLSearchParams({ per_page: '100' })
    })
      .then(res => res.json())
      .then(json => {
        if (!json.ok) throw new Error(json.error || 'Erreur inconnue');
        const rows = json.rows || [];
        if (!rows.length) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Aucun règlement trouvé.</td></tr>';
          return;
        }

        tbody.innerHTML = '';
        rows.forEach((r, i) => {
          const tr = document.createElement('tr');
          let color = 'secondary';
          let label = (r.situation || '').trim().toLowerCase();

          if (label.includes('non payé') || label.includes('non paye') || label.includes('non payée') || label.includes('non payee')) {
            color = 'danger';
          } else if (label.includes('payé') || label.includes('payee') || label.includes('payée') || label.includes('paye')) {
            color = 'success';
          }
          

          tr.innerHTML = `
          <td>${i + 1}</td>
          <td>${r.ref}</td>
          <td>${r.date_fmt}</td>
          <td>${r.fournisseur}</td>
          <td>${r.payment_mode || '-'}</td>
          <td><span class="badge bg-${color}">${r.situation}</span></td>
          <td>${r.prix_total.toFixed(2)}</td>
        `;
          tbody.appendChild(tr);
        });

        new bootstrap.Modal(document.getElementById('modalReglementFournisseur')).show();
      })
      .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center">${err.message}</td></tr>`;
      });
  });

  // === CLASSEMENT TRANSPORTEUR HANDLER ===
  document.querySelectorAll('#cardTransporteur').forEach(function(card) {
    card.addEventListener('click', function () {
      const tbody       = document.getElementById('tbodyTransporteurs');
      const emptyState  = document.getElementById('emptyStateTransporteur');
      const searchInp   = document.getElementById('searchTransporteur');
      const resetBtn    = document.getElementById('resetSearchTransporteur');
      const resetEmpty  = document.getElementById('resetEmptyTransporteur');
      const perPageSel  = document.getElementById('transPerPage');
      const pageInfo    = document.getElementById('transPageInfo');
      const countBadge  = document.getElementById('transCount');
      const poidsBadge  = document.getElementById('transTotalPoids');
      const chargBadge  = document.getElementById('transTotalCharg');
      const btnFirst    = document.getElementById('firstTransPage');
      const btnPrev     = document.getElementById('prevTransPage');
      const btnNext     = document.getElementById('nextTransPage');
      const btnLast     = document.getElementById('lastTransPage');

      let allRows = [], filtered = [], currentPage = 1;

      function renderPage() {
        const perPage  = parseInt(perPageSel.value) || 10;
        const total    = filtered.length;
        const lastPg   = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > lastPg) currentPage = lastPg;
        const start = (currentPage - 1) * perPage;
        const end   = Math.min(start + perPage, total);
        const slice = filtered.slice(start, end);

        tbody.style.display = '';
        emptyState.style.display = 'none';

        if (!slice.length) {
          tbody.style.display = 'none';
          emptyState.style.display = '';
          pageInfo.textContent = '0 résultats';
          [btnFirst, btnPrev, btnNext, btnLast].forEach(b => b.disabled = true);
          return;
        }

        tbody.innerHTML = '';
        slice.forEach((r, idx) => {
          const rank = start + idx + 1;
          const medalMap = {1:'🥇', 2:'🥈', 3:'🥉'};
          const medal = medalMap[rank] || `<span class="fw-semibold text-muted">${rank}</span>`;
          const img = r.image
            ? `<img src="${r.image}" width="44" height="44" style="object-fit:cover;border-radius:10px;border:2px solid #e2e8f0;" alt="photo">`
            : `<div style="width:44px;height:44px;border-radius:10px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;"><i class="bi bi-person text-muted"></i></div>`;
          const tr = document.createElement('tr');
          tr.style.transition = 'all .15s';
          tr.onmouseenter = () => { tr.style.background='#f1f5f9'; tr.style.borderLeft='3px solid #334155'; };
          tr.onmouseleave = () => { tr.style.background=''; tr.style.borderLeft=''; };
          tr.innerHTML = `
            <td style="padding:10px 16px;font-size:1.1rem;">${medal}</td>
            <td style="padding:10px 16px;">${img}</td>
            <td style="padding:10px 16px;font-weight:600;">${r.nom_complet}</td>
            <td style="padding:10px 16px;">${r.telephone || '<span class="text-muted">—</span>'}</td>
            <td style="padding:10px 16px;">${r.adresse || '<span class="text-muted">—</span>'}</td>
            <td style="padding:10px 16px;">
              <span class="badge rounded-pill px-3 py-2" style="background:#dc2626;font-size:.85rem;">
                <i class="bi bi-boxes me-1"></i>${parseFloat(r.total_poids).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})} kg
              </span>
            </td>
            <td style="padding:10px 16px;">
              <span class="badge rounded-pill px-3 py-2" style="background:#2563eb;font-size:.85rem;">
                <i class="bi bi-arrow-repeat me-1"></i>${r.nb_chargements}
              </span>
            </td>`;
          tbody.appendChild(tr);
        });

        pageInfo.textContent = `${start + 1}–${end} sur ${total}`;
        btnFirst.disabled = currentPage === 1;
        btnPrev.disabled  = currentPage === 1;
        btnNext.disabled  = currentPage === lastPg;
        btnLast.disabled  = currentPage === lastPg;
      }

      function applyFilter() {
        const q = (searchInp.value || '').toLowerCase().trim();
        resetBtn.style.display = q ? '' : 'none';
        filtered = q
          ? allRows.filter(r =>
              (r.nom_complet||'').toLowerCase().includes(q) ||
              (r.telephone||'').includes(q) ||
              (r.adresse||'').toLowerCase().includes(q))
          : allRows.slice();
        currentPage = 1;
        renderPage();
      }

      // Reset event bindings each open
      searchInp.oninput   = applyFilter;
      resetBtn.onclick    = () => { searchInp.value = ''; applyFilter(); };
      resetEmpty.onclick  = () => { searchInp.value = ''; applyFilter(); };
      perPageSel.onchange = () => { currentPage = 1; renderPage(); };
      btnFirst.onclick    = () => { currentPage = 1; renderPage(); };
      btnPrev.onclick     = () => { if (currentPage > 1) { currentPage--; renderPage(); } };
      btnNext.onclick     = () => { currentPage++; renderPage(); };
      btnLast.onclick     = () => {
        currentPage = Math.max(1, Math.ceil(filtered.length / (parseInt(perPageSel.value) || 10)));
        renderPage();
      };

      // Reset UI
      searchInp.value = '';
      resetBtn.style.display = 'none';
      tbody.style.display = '';
      emptyState.style.display = 'none';
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted p-4"><div class="spinner-border spinner-border-sm me-2"></div>Chargement…</td></tr>';
      countBadge.textContent = '0';
      poidsBadge.textContent = '0';
      chargBadge.textContent = '0';

      fetch('includes/ajax/admin/get_Class_transporteur.php')
        .then(r => r.json())
        .then(json => {
          if (!json.ok) throw new Error(json.error || 'Erreur serveur');
          allRows  = json.rows;
          filtered = allRows.slice();

          const totalPoids = allRows.reduce((s,r) => s + parseFloat(r.total_poids||0), 0);
          const totalCharg = allRows.reduce((s,r) => s + parseInt(r.nb_chargements||0), 0);
          countBadge.textContent = allRows.length;
          poidsBadge.textContent = totalPoids.toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2});
          chargBadge.textContent = totalCharg;

          renderPage();
          new bootstrap.Modal(document.getElementById('modalTransporteurDetails')).show();
        })
        .catch(err => {
          tbody.style.display = '';
          tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center p-4"><i class="bi bi-exclamation-triangle me-2"></i>${err.message}</td></tr>`;
          new bootstrap.Modal(document.getElementById('modalTransporteurDetails')).show();
        });
    });
  });

  // === OUVRIER MODAL HANDLER (with search + pagination) ===
  document.getElementById('cardOuvrier').addEventListener('click', function () {
    const tbody        = document.querySelector('#tableOuvriers tbody');
    const emptyState   = document.getElementById('emptyStateOuvrier');
    const searchInput  = document.getElementById('searchOuvrier');
    const perPageSel   = document.getElementById('ouvrierPerPage');
    const countBadge   = document.getElementById('ouvrierTotalCount');
    const totalItems   = document.getElementById('ouvrierTotalItems');
    const startIdx     = document.getElementById('ouvrierStartIndex');
    const endIdx       = document.getElementById('ouvrierEndIndex');
    const pageInfo     = document.getElementById('ouvrierPageInfo');
    const btnFirst     = document.getElementById('firstOuvrierPage');
    const btnPrev      = document.getElementById('prevOuvrierPage');
    const btnNext      = document.getElementById('nextOuvrierPage');
    const btnLast      = document.getElementById('lastOuvrierPage');
    const resetBtn     = document.getElementById('resetSearchOuvrier');

    let allRows = [], filtered = [], currentPage = 1;

    function getPerPage() { return parseInt(perPageSel.value) || 25; }

    function filterRows(q) {
      const s = q.toLowerCase().trim();
      filtered = s ? allRows.filter(r =>
        (`${r.prenom} ${r.nom}`).toLowerCase().includes(s) ||
        (r.cin || '').toLowerCase().includes(s) ||
        (r.fonction || '').toLowerCase().includes(s) ||
        (r.tele || '').toLowerCase().includes(s)
      ) : [...allRows];
      currentPage = 1;
      render();
    }

    function render() {
      const pp = getPerPage();
      const total = filtered.length;
      const totalPages = Math.max(1, Math.ceil(total / pp));
      if (currentPage > totalPages) currentPage = totalPages;
      const start = (currentPage - 1) * pp;
      const slice = filtered.slice(start, start + pp);

      totalItems.textContent = total;
      startIdx.textContent   = total ? start + 1 : 0;
      endIdx.textContent     = Math.min(start + pp, total);
      pageInfo.textContent   = `${currentPage}/${totalPages}`;
      btnFirst.disabled = btnPrev.disabled = currentPage <= 1;
      btnNext.disabled  = btnLast.disabled = currentPage >= totalPages;

      tbody.innerHTML = '';
      if (!slice.length) {
        emptyState.classList.remove('d-none');
        tbody.closest('.table-responsive').classList.add('d-none');
        return;
      }
      emptyState.classList.add('d-none');
      tbody.closest('.table-responsive').classList.remove('d-none');

      slice.forEach((r, i) => {
        const tr = document.createElement('tr');
        tr.style.cssText = 'transition:all .25s ease;border-left:3px solid transparent;';
        tr.onmouseenter = () => { tr.style.background='linear-gradient(90deg,#f8fafc,#fff)'; tr.style.borderLeftColor='#334155'; tr.style.transform='translateX(3px)'; };
        tr.onmouseleave = () => { tr.style.background=''; tr.style.borderLeftColor='transparent'; tr.style.transform=''; };
        tr.innerHTML = `
          <td class="ps-4 text-muted small">${start + i + 1}</td>
          <td class="fw-semibold">${r.prenom} ${r.nom}</td>
          <td><code>${r.cin || '-'}</code></td>
          <td>${r.tele || '-'}</td>
          <td>${r.fonction ? `<span class="badge bg-secondary">${r.fonction}</span>` : '-'}</td>
          <td class="text-muted small">${r.adress || '-'}</td>
          <td class="text-center">
            ${r.image
              ? `<img src="uploads/photo-ouvrier/${r.image}" width="42" height="42" class="rounded shadow-sm" alt="photo">`
              : '<span class="text-muted">-</span>'}
          </td>
          <td class="text-center pe-4">
            ${r.img_cin
              ? `<img src="uploads/scanne-cin/${r.img_cin}" width="42" height="42" class="rounded shadow-sm" alt="CIN">`
              : '<span class="text-muted">-</span>'}
          </td>`;
        tbody.appendChild(tr);
      });
    }

    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-5">
      <div class="spinner-border text-dark mb-2" role="status"></div><p class="mb-0">Chargement…</p></td></tr>`;

    fetch('includes/ajax/admin/get_List_ouvrier.php', {
      method: 'POST', body: new URLSearchParams({ per_page: 'all' })
    })
    .then(r => r.json())
    .then(json => {
      if (!json.ok) throw new Error(json.error || 'Erreur inconnue');
      allRows  = json.rows || [];
      filtered = [...allRows];
      countBadge.textContent = allRows.length;
      render();
      new bootstrap.Modal(document.getElementById('modalOuvrierDetails')).show();
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="8" class="text-danger text-center py-4"><i class="bi bi-exclamation-triangle me-2"></i>${err.message}</td></tr>`;
    });

    // Controls
    searchInput.oninput      = () => filterRows(searchInput.value);
    resetBtn.onclick         = () => { searchInput.value = ''; filterRows(''); };
    perPageSel.onchange      = () => { currentPage = 1; render(); };
    btnFirst.onclick         = () => { currentPage = 1; render(); };
    btnPrev.onclick          = () => { if (currentPage > 1) { currentPage--; render(); } };
    btnNext.onclick          = () => { currentPage++; render(); };
    btnLast.onclick          = () => { currentPage = Math.ceil(filtered.length / getPerPage()); render(); };
  });

  // === PIÈCES MODAL HANDLER ===
  document.querySelectorAll('.card.bg-danger').forEach(card => {
    if (card.textContent.includes('Pièces')) {
      card.addEventListener('click', function () {
        const tbody = document.querySelector('#tablePieces tbody');
        tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">Chargement...</td></tr>';

        fetch('includes/ajax/admin/get_List_pieces.php')
          .then(res => res.json())
          .then(json => {
            if (!json.ok) throw new Error(json.error || 'Erreur inconnue');
            const items = json.rows || [];

            if (!items.length) {
              tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">Aucune pièce trouvée.</td></tr>';
              return;
            }

            tbody.innerHTML = '';
            items.forEach((r, i) => {
              const tr = document.createElement('tr');
              tr.innerHTML = `
              <td>${i + 1}</td>
              <td>${r.ref_piece}</td>
              <td>${r.nom_piece}</td>
              <td>${r.type_piece}</td>
              <td><span class="badge bg-secondary">${r.quantite}</span></td>
              <td><span class="badge bg-success">${r.statut_marche}</span></td>
              <td><span class="badge bg-danger">${r.statut_panne}</span></td>
              <td><span class="badge bg-warning text-dark">${r.statut_maintenance}</span></td>
              <td>${r.fournisseur_nom || '-'}</td>
              <td>${r.date_ajout ? new Date(r.date_ajout).toLocaleDateString('fr-FR') : ''}</td>
              <td>
                ${r.first_image
                  ? `<img src="uploads/pieces/${r.first_image}" width="45" height="45" class="rounded shadow-sm" alt="image">`
                  : '<span class="text-muted">-</span>'}
              </td>
            `;
              tbody.appendChild(tr);
            });

            new bootstrap.Modal(document.getElementById('modalPieceDetails')).show();
          })
          .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="11" class="text-danger text-center">${err.message}</td></tr>`;
          });
      });
    }
  });
  
  
  // Script amélioré pour le modal Clients
document.addEventListener('DOMContentLoaded', function() {
  const clientModal = document.getElementById('modalClientDetails');
  
  if (clientModal) {
    // Initialisation des événements
    initializeClientModal();
  }
});

function initializeClientModal() {
  const searchInput = document.getElementById('searchClient');
  const resetSearchBtn = document.getElementById('resetSearch');
  const emptyState = document.getElementById('emptyState');
  const tableBody = document.querySelector('#tableClients tbody');
  
  // Recherche en temps réel
  if (searchInput) {
    searchInput.addEventListener('input', debounce(function(e) {
      const searchTerm = e.target.value.trim();
      currentPage = 1;
      loadClientsData(currentPage, searchTerm);
    }, 300));
  }
  
  // Réinitialisation de la recherche
  if (resetSearchBtn) {
    resetSearchBtn.addEventListener('click', function() {
      searchInput.value = '';
      currentPage = 1;
      loadClientsData(currentPage);
    });
  }
  
  // Fonction pour afficher/masquer l'état vide
  function toggleEmptyState(show) {
    if (emptyState && tableBody) {
      if (show) {
        tableBody.classList.add('d-none');
        emptyState.classList.remove('d-none');
      } else {
        tableBody.classList.remove('d-none');
        emptyState.classList.add('d-none');
      }
    }
  }
  
  // Mise à jour du compteur total
  function updateTotalCount(count) {
    const totalCountElement = document.getElementById('clientTotalCount');
    if (totalCountElement) {
      totalCountElement.textContent = count.toLocaleString();
    }
  }
  
  // Fonction debounce pour la recherche
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
}
  
  
  

 // === CLIENTS MODAL HANDLER AVEC PAGINATION (VERSION CORRIGÉE) ===
document.querySelectorAll('.card.bg-light').forEach(card => {
  if (card.textContent.includes('Clients Huile')) {
    card.addEventListener('click', function () {
      const modal = new bootstrap.Modal(document.getElementById('modalClientDetails'));
      const tbody = document.querySelector('#tableClients tbody');
      const prevBtn = document.getElementById('prevClientPage');
      const nextBtn = document.getElementById('nextClientPage');
      const firstBtn = document.getElementById('firstClientPage');
      const lastBtn = document.getElementById('lastClientPage');
      const pageInfo = document.getElementById('clientPageInfo');
      const currentPageSpan = document.getElementById('clientCurrentPage');
      const totalItemsSpan = document.getElementById('clientTotalItems');
      const perPageSelect = document.getElementById('clientPerPage');
      const searchInput = document.getElementById('searchClient');
      const resetSearchBtn = document.getElementById('resetSearch');
      const emptyState = document.getElementById('emptyState');
      const totalCountElement = document.getElementById('clientTotalCount');

      let currentPage = 1;
      let currentPerPage = parseInt(perPageSelect?.value || 25);
      let totalItems = 0;
      let totalPages = 1;
      let currentSearchTerm = '';

      function updatePaginationButtons() {
        if (firstBtn && prevBtn && nextBtn && lastBtn) {
          firstBtn.disabled = currentPage <= 1;
          prevBtn.disabled = currentPage <= 1;
          nextBtn.disabled = currentPage >= totalPages;
          lastBtn.disabled = currentPage >= totalPages;
        }
        
        if (pageInfo) pageInfo.textContent = `${currentPage}/${Math.max(totalPages, 1)}`;
        if (currentPageSpan) currentPageSpan.textContent = currentPage;
        if (totalItemsSpan) totalItemsSpan.textContent = totalItems;
        
        // Mettre à jour les indices de début et fin
        const startIndex = totalItems > 0 ? ((currentPage - 1) * currentPerPage) + 1 : 0;
        const endIndex = Math.min(currentPage * currentPerPage, totalItems);
        const startIndexEl = document.getElementById('clientStartIndex');
        const endIndexEl = document.getElementById('clientEndIndex');
        if (startIndexEl) startIndexEl.textContent = startIndex;
        if (endIndexEl) endIndexEl.textContent = endIndex;
      }

      function toggleEmptyState(show) {
        if (emptyState && tbody) {
          if (show) {
            tbody.classList.add('d-none');
            emptyState.classList.remove('d-none');
          } else {
            tbody.classList.remove('d-none');
            emptyState.classList.add('d-none');
          }
        }
      }

      function updateTotalCount(count) {
        if (totalCountElement) {
          totalCountElement.textContent = count.toLocaleString();
        }
      }

      function loadClientsData(page = 1, searchTerm = '') {
        if (!tbody) return;
        
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5"><div class="loading-spinner"><div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Chargement...</span></div><p class="mb-0">Chargement des clients...</p></div></td></tr>';
        
        const params = new URLSearchParams({
          page: page,
          perPage: currentPerPage,
          q: searchTerm
        });

        fetch('includes/ajax/admin/get_List_clients.php?' + params)
          .then(res => {
            if (!res.ok) throw new Error(`Erreur HTTP: ${res.status}`);
            return res.json();
          })
          .then(json => {
            // Vérification de la structure de réponse
            if (!json.rows && !json.error) {
              throw new Error('Format de réponse invalide');
            }
            
            const rows = json.rows || [];
            totalItems = json.total || 0;
            totalPages = Math.ceil(totalItems / currentPerPage) || 1;

            // Mettre à jour le compteur total
            updateTotalCount(totalItems);

            if (!rows.length) {
              toggleEmptyState(true);
              updatePaginationButtons();
              return;
            }

            toggleEmptyState(false);
            tbody.innerHTML = '';

            rows.forEach((r, index) => {
              const globalIndex = ((page - 1) * currentPerPage) + index + 1;
              const tr = document.createElement('tr');
              
              // Formatage de la date
              const createdDate = r.created_at ? new Date(r.created_at).toLocaleDateString('fr-FR') : '-';
              
              tr.innerHTML = `
                <td class="ps-4">${globalIndex}</td>
                <td><strong>${r.prenom || ''} ${r.nom || ''}</strong></td>
                <td>${r.phone || '-'}</td>
                <td>${r.address || '-'}</td>
                <td><span class="badge bg-info">${r.ville || '-'}</span></td>
                <td class="text-center"><small class="text-muted">${createdDate}</small></td>
                <td class="text-center pe-4">
                  <i class="bi bi-person-check text-success"></i>
                </td>
              `;
              tbody.appendChild(tr);
            });

            updatePaginationButtons();
          })
          .catch(err => {
            console.error('Erreur chargement clients:', err);
            tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-4"><i class="bi bi-exclamation-triangle me-2"></i>${err.message}</td></tr>`;
            toggleEmptyState(true);
            updatePaginationButtons();
          });
      }

      // Fonction debounce pour la recherche
      function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
          const later = () => {
            clearTimeout(timeout);
            func(...args);
          };
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
        };
      }

      // Événements de pagination
      if (firstBtn) {
        firstBtn.addEventListener('click', () => {
          currentPage = 1;
          loadClientsData(currentPage, currentSearchTerm);
        });
      }

      if (prevBtn) {
        prevBtn.addEventListener('click', () => {
          if (currentPage > 1) {
            currentPage--;
            loadClientsData(currentPage, currentSearchTerm);
          }
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', () => {
          if (currentPage < totalPages) {
            currentPage++;
            loadClientsData(currentPage, currentSearchTerm);
          }
        });
      }

      if (lastBtn) {
        lastBtn.addEventListener('click', () => {
          currentPage = totalPages;
          loadClientsData(currentPage, currentSearchTerm);
        });
      }

      // Changement du nombre d'éléments par page
      if (perPageSelect) {
        perPageSelect.addEventListener('change', (e) => {
          currentPerPage = parseInt(e.target.value);
          currentPage = 1;
          loadClientsData(currentPage, currentSearchTerm);
        });
      }

      // Recherche en temps réel
      if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
          currentSearchTerm = e.target.value.trim();
          currentPage = 1;
          loadClientsData(currentPage, currentSearchTerm);
        }, 300));
      }

      // Réinitialisation de la recherche
      if (resetSearchBtn) {
        resetSearchBtn.addEventListener('click', function() {
          if (searchInput) searchInput.value = '';
          currentSearchTerm = '';
          currentPage = 1;
          loadClientsData(currentPage);
        });
      }

      // Chargement initial
      loadClientsData();
      modal.show();
    });
  }
});

  // === FOURNISSEURS MODAL HANDLER ===
  // === FOURNISSEURS MODAL HANDLER AVEC PAGINATION (VERSION CORRIGÉE) ===
document.querySelectorAll('.card.bg-light').forEach(card => {
  if (card.textContent.includes('Fournisseurs')) {
    card.addEventListener('click', function () {
      const modal = new bootstrap.Modal(document.getElementById('modalFournisseurDetails'));
      const tbody = document.querySelector('#tableFournisseurs tbody');
      const prevBtn = document.getElementById('prevFournisseurPage');
      const nextBtn = document.getElementById('nextFournisseurPage');
      const firstBtn = document.getElementById('firstFournisseurPage');
      const lastBtn = document.getElementById('lastFournisseurPage');
      const pageInfo = document.getElementById('fournisseurPageInfo');
      const currentPageSpan = document.getElementById('fournisseurCurrentPage');
      const totalItemsSpan = document.getElementById('fournisseurTotalItems');
      const perPageSelect = document.getElementById('fournisseurPerPage');
      const searchInput = document.getElementById('searchFournisseur');
      const resetSearchBtn = document.getElementById('resetSearchFournisseur');
      const emptyState = document.getElementById('emptyStateFournisseur');
      const totalCountElement = document.getElementById('fournisseurTotalCount');

      let currentPage = 1;
      let currentPerPage = parseInt(perPageSelect?.value || 25);
      let totalItems = 0;
      let totalPages = 1;
      let currentSearchTerm = '';

      function updatePaginationButtons() {
        if (firstBtn && prevBtn && nextBtn && lastBtn) {
          firstBtn.disabled = currentPage <= 1;
          prevBtn.disabled = currentPage <= 1;
          nextBtn.disabled = currentPage >= totalPages;
          lastBtn.disabled = currentPage >= totalPages;
        }
        
        if (pageInfo) pageInfo.textContent = `${currentPage}/${Math.max(totalPages, 1)}`;
        if (currentPageSpan) currentPageSpan.textContent = currentPage;
        if (totalItemsSpan) totalItemsSpan.textContent = totalItems;
        
        // Mettre à jour les indices de début et fin
        const startIndex = totalItems > 0 ? ((currentPage - 1) * currentPerPage) + 1 : 0;
        const endIndex = Math.min(currentPage * currentPerPage, totalItems);
        const startIndexEl = document.getElementById('fournisseurStartIndex');
        const endIndexEl = document.getElementById('fournisseurEndIndex');
        if (startIndexEl) startIndexEl.textContent = startIndex;
        if (endIndexEl) endIndexEl.textContent = endIndex;
      }

      function toggleEmptyState(show) {
        if (emptyState && tbody) {
          if (show) {
            tbody.classList.add('d-none');
            emptyState.classList.remove('d-none');
          } else {
            tbody.classList.remove('d-none');
            emptyState.classList.add('d-none');
          }
        }
      }

      function updateTotalCount(count) {
        if (totalCountElement) {
          totalCountElement.textContent = count.toLocaleString();
        }
      }

      function loadFournisseursData(page = 1, searchTerm = '') {
        if (!tbody) return;
        
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5"><div class="loading-spinner"><div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Chargement...</span></div><p class="mb-0">Chargement des fournisseurs...</p></div></td></tr>';
        
        const params = new URLSearchParams({
          page: page,
          perPage: currentPerPage,
          q: searchTerm
        });

        fetch('includes/ajax/admin/get_List_fournisseurs.php?' + params)
          .then(res => {
            if (!res.ok) throw new Error(`Erreur HTTP: ${res.status}`);
            return res.json();
          })
          .then(json => {
            // Vérification de la structure de réponse
            if (!json.ok) {
              throw new Error(json.error || 'Format de réponse invalide');
            }
            
            const rows = json.rows || [];
            totalItems = json.total || 0;
            totalPages = Math.ceil(totalItems / currentPerPage) || 1;

            // Mettre à jour le compteur total
            updateTotalCount(totalItems);

            if (!rows.length) {
              toggleEmptyState(true);
              updatePaginationButtons();
              return;
            }

            toggleEmptyState(false);
            tbody.innerHTML = '';

            rows.forEach((r, index) => {
              const globalIndex = ((page - 1) * currentPerPage) + index + 1;
              const tr = document.createElement('tr');
              
              // Formatage de la date
              const createdDate = r.date_fmt || (r.created_at ? new Date(r.created_at).toLocaleDateString('fr-FR') : '-');
              
              tr.innerHTML = `
                <td class="ps-4">${globalIndex}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-placeholder bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                      <i class="bi bi-building"></i>
                    </div>
                    <div>
                      <strong class="d-block">${r.prenom || ''} ${r.nom || ''}</strong>
                      <small class="text-muted">ID: ${r.id_fournisseur || 'N/A'}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <i class="bi bi-telephone text-primary me-2"></i>
                    <span>${r.tele || '-'}</span>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <i class="bi bi-geo-alt text-success me-2"></i>
                    <span class="text-truncate" style="max-width: 200px;" title="${r.adress || ''}">
                      ${r.adress || '-'}
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <small class="text-muted">${createdDate}</small>
                </td>
                <td class="text-center pe-4">
                  <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-action btn-view" data-id="${r.id_fournisseur}" title="Voir détails">
                      <i class="bi bi-eye"></i>
                    </button>
                  
                  </div>
                </td>
              `;
              tbody.appendChild(tr);
            });

            // Attacher les événements des boutons d'action
            attachActionEvents();

            updatePaginationButtons();
          })
          .catch(err => {
            console.error('Erreur chargement fournisseurs:', err);
            tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center py-4"><i class="bi bi-exclamation-triangle me-2"></i>${err.message}</td></tr>`;
            toggleEmptyState(true);
            updatePaginationButtons();
          });
      }

      function attachActionEvents() {
        // Bouton Voir détails
        tbody.querySelectorAll('.btn-view').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = e.currentTarget.dataset.id;
            window.location.href = `details_fournisseur.php?id=${id}`;
          });
        });

        // Bouton Modifier
        tbody.querySelectorAll('.btn-edit').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = e.currentTarget.dataset.id;
            window.location.href = `modifier_fournisseur.php?id=${id}`;
          });
        });

        // Clic sur une ligne (navigation vers les détails)
        tbody.querySelectorAll('tr').forEach(tr => {
          tr.addEventListener('click', (e) => {
            if (!e.target.closest('.btn-group')) {
              const id = tr.querySelector('.btn-view')?.dataset.id;
              if (id) {
                window.location.href = `details_fournisseur.php?id=${id}`;
              }
            }
          });
        });
      }

      // Fonction debounce pour la recherche
      function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
          const later = () => {
            clearTimeout(timeout);
            func(...args);
          };
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
        };
      }

      // Événements de pagination
      if (firstBtn) {
        firstBtn.addEventListener('click', () => {
          currentPage = 1;
          loadFournisseursData(currentPage, currentSearchTerm);
        });
      }

      if (prevBtn) {
        prevBtn.addEventListener('click', () => {
          if (currentPage > 1) {
            currentPage--;
            loadFournisseursData(currentPage, currentSearchTerm);
          }
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', () => {
          if (currentPage < totalPages) {
            currentPage++;
            loadFournisseursData(currentPage, currentSearchTerm);
          }
        });
      }

      if (lastBtn) {
        lastBtn.addEventListener('click', () => {
          currentPage = totalPages;
          loadFournisseursData(currentPage, currentSearchTerm);
        });
      }

      // Changement du nombre d'éléments par page
      if (perPageSelect) {
        perPageSelect.addEventListener('change', (e) => {
          currentPerPage = parseInt(e.target.value);
          currentPage = 1;
          loadFournisseursData(currentPage, currentSearchTerm);
        });
      }

      // Recherche en temps réel
      if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
          currentSearchTerm = e.target.value.trim();
          currentPage = 1;
          loadFournisseursData(currentPage, currentSearchTerm);
        }, 300));
      }

      // Réinitialisation de la recherche
      if (resetSearchBtn) {
        resetSearchBtn.addEventListener('click', function() {
          if (searchInput) searchInput.value = '';
          currentSearchTerm = '';
          currentPage = 1;
          loadFournisseursData(currentPage);
        });
      }

      // Chargement initial
      loadFournisseursData();
      modal.show();
    });
  }
});

// === HUILE MODAL HANDLER ===
document.querySelector('.huile-card').addEventListener('click', function () {
    const modal        = new bootstrap.Modal(document.getElementById('modalHuileDetails'));
    const tbody        = document.querySelector('#tableHuile tbody');
    const prevBtn      = document.getElementById('prevHuilePage');
    const nextBtn      = document.getElementById('nextHuilePage');
    const firstBtn     = document.getElementById('firstHuilePage');
    const lastBtn      = document.getElementById('lastHuilePage');
    const pageInfo     = document.getElementById('huilePageInfo');
    const totalItemsSpan = document.getElementById('huileTotalItems');
    const startIndexSpan = document.getElementById('huileStartIndex');
    const endIndexSpan   = document.getElementById('huileEndIndex');
    const perPageSelect  = document.getElementById('huilePerPage');
    const searchInput    = document.getElementById('searchHuile');
    const resetBtn       = document.getElementById('resetSearchHuile');
    const totalCountSpan = document.getElementById('huileTotalCount');
    const sommeTotalSpan = document.getElementById('huileSommeTotal');
    const emptyState     = document.getElementById('emptyStateHuile');

    let currentPage    = 1;
    let currentPerPage = 25;
    let totalItems     = 0;
    let totalPages     = 1;
    let searchQuery    = '';
    let allRows        = [];

    // Reset on open
    searchInput.value = '';
    searchQuery = '';
    totalCountSpan.textContent = '0';
    sommeTotalSpan.textContent = '0.00';

    function updatePagination() {
        firstBtn.disabled = currentPage <= 1;
        prevBtn.disabled  = currentPage <= 1;
        nextBtn.disabled  = currentPage >= totalPages;
        lastBtn.disabled  = currentPage >= totalPages;
        pageInfo.textContent = `${currentPage}/${Math.max(1, totalPages)}`;
        totalItemsSpan.textContent = totalItems;
        const start = totalItems === 0 ? 0 : (currentPage - 1) * currentPerPage + 1;
        const end   = Math.min(currentPage * currentPerPage, totalItems);
        startIndexSpan.textContent = start;
        endIndexSpan.textContent   = end;
    }

    function renderRows(rows, page) {
        emptyState.classList.add('d-none');
        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">Aucun bon d'huile trouvé.</td></tr>`;
            emptyState.classList.remove('d-none');
            updatePagination();
            return;
        }
        tbody.innerHTML = '';
        rows.forEach((row, idx) => {
            const globalIdx = (page - 1) * currentPerPage + idx + 1;
            let badge = 'bg-secondary text-white';
            const mp = (row.mode_paiement || '').toLowerCase();
            if (mp.includes('credit'))  badge = 'bg-warning text-dark';
            else if (mp.includes('esp') || mp.includes('comptant')) badge = 'bg-success text-white';
            const tr = document.createElement('tr');
            tr.style.cssText = 'transition:background .15s;cursor:default;';
            tr.onmouseenter = () => tr.style.background = 'rgba(25,135,84,.06)';
            tr.onmouseleave = () => tr.style.background = '';
            tr.innerHTML = `
                <td class="ps-3 text-muted small">${globalIdx}</td>
                <td>${row.date_bon || row.created_at || '-'}</td>
                <td><strong>${row.client || 'Non spécifié'}</strong></td>
                <td>${row.ville || '-'}</td>
                <td class="text-end"><span class="badge bg-primary">${row.quantite || 0}</span></td>
                <td class="text-end">${parseFloat(row.prix_unitaire || 0).toFixed(2)}</td>
                <td class="text-end"><strong class="text-success">${parseFloat(row.prix_net || 0).toFixed(2)}</strong></td>
                <td><span class="badge ${badge}">${row.mode_paiement || 'CREDIT'}</span></td>
                <td><small class="text-muted">${row.provider || '-'}</small></td>
            `;
            tbody.appendChild(tr);
        });
        updatePagination();
    }

    function applyFilter() {
        const q = searchQuery.toLowerCase();
        const filtered = q
            ? allRows.filter(r =>
                (r.client || '').toLowerCase().includes(q) ||
                (r.ville  || '').toLowerCase().includes(q) ||
                (r.mode_paiement || '').toLowerCase().includes(q) ||
                (r.provider || '').toLowerCase().includes(q)
              )
            : allRows;
        totalItems = filtered.length;
        totalPages = Math.max(1, Math.ceil(totalItems / currentPerPage));
        if (currentPage > totalPages) currentPage = 1;
        const start = (currentPage - 1) * currentPerPage;
        renderRows(filtered.slice(start, start + currentPerPage), currentPage);
    }

    function loadHuileData() {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5">
            <div class="spinner-border text-success mb-2" role="status"></div>
            <p class="mb-0 text-muted">Chargement…</p></td></tr>`;

        fetch('includes/ajax/admin/getBon_Huile.php?page=1&perPage=9999')
            .then(r => r.json())
            .then(json => {
                if (!json.ok) throw new Error(json.error || 'Erreur');
                allRows = json.items || [];
                // Stat badges
                const somme = allRows.reduce((s, r) => s + parseFloat(r.prix_net || 0), 0);
                totalCountSpan.textContent = allRows.length;
                sommeTotalSpan.textContent = somme.toFixed(2);
                currentPage = 1;
                applyFilter();
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="9" class="text-danger text-center py-4">${err.message}</td></tr>`;
            });
    }

    // Search
    searchInput.addEventListener('input', () => {
        searchQuery = searchInput.value.trim();
        currentPage = 1;
        applyFilter();
    });
    if (resetBtn) resetBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchQuery = '';
        currentPage = 1;
        applyFilter();
    });

    // Pagination events
    firstBtn.addEventListener('click', () => { currentPage = 1; applyFilter(); });
    prevBtn.addEventListener('click',  () => { if (currentPage > 1) { currentPage--; applyFilter(); } });
    nextBtn.addEventListener('click',  () => { if (currentPage < totalPages) { currentPage++; applyFilter(); } });
    lastBtn.addEventListener('click',  () => { currentPage = totalPages; applyFilter(); });
    perPageSelect.addEventListener('change', e => {
        currentPerPage = parseInt(e.target.value);
        currentPage = 1;
        applyFilter();
    });

    loadHuileData();
    modal.show();
});


// === BIDONS HANDLER — DESIGN AMÉLIORÉ ===
document.querySelectorAll('#cardBidon').forEach(el => {
  el.addEventListener('click', function () {
    const modal        = new bootstrap.Modal(document.getElementById('modalBidonDetails'));
    const tbodyStock   = document.querySelector('#tableBidons tbody');
    const tbodyCmd     = document.querySelector('#tableCommandeBidons tbody');
    const searchInput  = document.getElementById('searchBidon');
    const resetBtn     = document.getElementById('resetSearchBidon');
    const perPageSel   = document.getElementById('bidonPerPage');
    const firstBtn     = document.getElementById('firstBidonPage');
    const prevBtn      = document.getElementById('prevBidonPage');
    const nextBtn      = document.getElementById('nextBidonPage');
    const lastBtn      = document.getElementById('lastBidonPage');
    const pageInfoEl   = document.getElementById('bidonPageInfo');
    const startIdxEl   = document.getElementById('bidonStartIndex');
    const endIdxEl     = document.getElementById('bidonEndIndex');
    const totalItemsEl = document.getElementById('bidonTotalItems');

    let allStockRows = [], allCmdRows = [];
    let currentPage = 1;
    function getPerPage() { return parseInt(perPageSel?.value) || 25; }

    // --- render stock table (always show all, filtered) ---
    function renderStock(q) {
      const filtered = q
        ? allStockRows.filter(r =>
            (r.nom_bidon||'').toLowerCase().includes(q) ||
            (r.type_bidon||'').toLowerCase().includes(q))
        : allStockRows;

      tbodyStock.innerHTML = '';
      if (!filtered.length) {
        tbodyStock.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Aucun résultat.</td></tr>';
        return;
      }
      filtered.forEach((r, i) => {
        const vendu = (r.vendu_huile||0) + (r.vendu_bidon||0);
        const dispo = r.stock_disponible;
        const statusBadge = dispo > 0
          ? `<span class="badge rounded-pill px-3 py-2" style="background:#198754;">Disponible</span>`
          : `<span class="badge rounded-pill px-3 py-2 bg-danger">Épuisé</span>`;
        const tr = document.createElement('tr');
        tr.style.cssText = 'transition:all .2s ease;border-left:3px solid transparent;';
        tr.onmouseenter = () => { tr.style.background='linear-gradient(90deg,#f1f5f9,#fff)'; tr.style.borderLeftColor='#475569'; tr.style.transform='translateX(3px)'; };
        tr.onmouseleave = () => { tr.style.background=''; tr.style.borderLeftColor='transparent'; tr.style.transform=''; };
        tr.innerHTML = `
          <td class="ps-4 text-muted small">${i+1}</td>
          <td class="fw-semibold">${r.nom_bidon}</td>
          <td>${r.type_bidon}</td>
          <td class="text-end">${r.stock_initial}</td>
          <td class="text-end text-danger fw-semibold">${vendu}</td>
          <td class="text-end fw-bold">${dispo}</td>
          <td class="text-center">${statusBadge}</td>`;
        tbodyStock.appendChild(tr);
      });
    }

    // --- render commandes table with pagination ---
    function renderCmd(q, page) {
      const filtered = q
        ? allCmdRows.filter(r =>
            (r.client||'').toLowerCase().includes(q) ||
            (r.types||'').toLowerCase().includes(q) ||
            (r.statut||'').toLowerCase().includes(q))
        : allCmdRows;

      const pp = getPerPage();
      const total = filtered.length;
      const totalPages = Math.max(1, Math.ceil(total / pp));
      if (page < 1) page = 1;
      if (page > totalPages) page = totalPages;
      currentPage = page;

      const start = (page - 1) * pp;
      const slice = filtered.slice(start, start + pp);

      // Update footer
      if (totalItemsEl) totalItemsEl.textContent = total;
      if (startIdxEl)   startIdxEl.textContent   = total ? start + 1 : 0;
      if (endIdxEl)     endIdxEl.textContent      = Math.min(start + pp, total);
      if (pageInfoEl)   pageInfoEl.textContent    = `${page}/${totalPages}`;
      if (firstBtn) firstBtn.disabled = page <= 1;
      if (prevBtn)  prevBtn.disabled  = page <= 1;
      if (nextBtn)  nextBtn.disabled  = page >= totalPages;
      if (lastBtn)  lastBtn.disabled  = page >= totalPages;

      tbodyCmd.innerHTML = '';
      if (!slice.length) {
        tbodyCmd.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Aucune commande trouvée.</td></tr>';
        return;
      }
      slice.forEach((r, i) => {
        const statut = (r.statut||'').toLowerCase().trim();
        let color = 'secondary';
        if (statut.includes('non pay')) color = 'danger';
        else if (statut.includes('pay')) color = 'success';
        const tr = document.createElement('tr');
        tr.style.cssText = 'transition:all .2s ease;border-left:3px solid transparent;';
        tr.onmouseenter = () => { tr.style.background='linear-gradient(90deg,#f1f5f9,#fff)'; tr.style.borderLeftColor='#475569'; tr.style.transform='translateX(3px)'; };
        tr.onmouseleave = () => { tr.style.background=''; tr.style.borderLeftColor='transparent'; tr.style.transform=''; };
        tr.innerHTML = `
          <td class="ps-4 text-muted small">${start + i + 1}</td>
          <td class="fw-semibold">${r.client}</td>
          <td class="text-end">${r.quantite_totale}</td>
          <td>${r.types}</td>
          <td class="text-end fw-bold">${parseFloat(r.montant_total).toFixed(2)} MAD</td>
          <td class="text-center"><span class="badge rounded-pill px-3 py-2 bg-${color}">${r.statut}</span></td>`;
        tbodyCmd.appendChild(tr);
      });
    }

    // --- wire events ---
    if (searchInput) searchInput.oninput = () => {
      const q = searchInput.value.toLowerCase();
      renderStock(q);
      renderCmd(q, 1);
    };
    if (resetBtn) resetBtn.onclick = () => { searchInput.value = ''; renderStock(''); renderCmd('', 1); };
    if (perPageSel) perPageSel.onchange = () => renderCmd(searchInput?.value?.toLowerCase()||'', 1);
    if (firstBtn) firstBtn.onclick = () => renderCmd(searchInput?.value?.toLowerCase()||'', 1);
    if (prevBtn)  prevBtn.onclick  = () => renderCmd(searchInput?.value?.toLowerCase()||'', currentPage - 1);
    if (nextBtn)  nextBtn.onclick  = () => renderCmd(searchInput?.value?.toLowerCase()||'', currentPage + 1);
    if (lastBtn)  lastBtn.onclick  = () => {
      const q = searchInput?.value?.toLowerCase()||'';
      const total = (q ? allCmdRows.filter(r=>(r.client||'').toLowerCase().includes(q)||(r.types||'').toLowerCase().includes(q)) : allCmdRows).length;
      renderCmd(q, Math.ceil(total / getPerPage()));
    };

    // --- load data ---
    Promise.all([
      fetch('includes/ajax/admin/details_bidon.php').then(r => r.json()),
      fetch('includes/ajax/admin/details_commande_bidon.php').then(r => r.json())
    ]).then(([jsonStock, jsonCmd]) => {
      // Stock
      if (jsonStock.status === 'success') {
        const t = jsonStock.totaux || {};
        if (document.getElementById('bidonStatTotal'))  document.getElementById('bidonStatTotal').textContent  = t.total_bidons || 0;
        if (document.getElementById('bidonStatVendus')) document.getElementById('bidonStatVendus').textContent = t.total_vendus || 0;
        if (document.getElementById('bidonStatDispo'))  document.getElementById('bidonStatDispo').textContent  = t.dispo || 0;
        allStockRows = jsonStock.details || [];
      } else {
        tbodyStock.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-3"><i class="bi bi-exclamation-triangle me-1"></i>${jsonStock.message||'Erreur stock'}</td></tr>`;
      }
      // Commandes
      if (jsonCmd.status === 'success') {
        allCmdRows = jsonCmd.rows || [];
      } else {
        tbodyCmd.innerHTML = `<tr><td colspan="6" class="text-danger text-center py-3"><i class="bi bi-exclamation-triangle me-1"></i>${jsonCmd.message||'Erreur commandes'}</td></tr>`;
      }
      renderStock('');
      renderCmd('', 1);
    }).catch(err => {
      tbodyStock.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-3">${err.message}</td></tr>`;
      tbodyCmd.innerHTML   = `<tr><td colspan="6" class="text-danger text-center py-3">${err.message}</td></tr>`;
    });

    modal.show();
  });
});


// === AVANCES MODAL HANDLER AVEC PAGINATION ===
document.querySelector('.avance-card').addEventListener('click', function () {
  const modal = new bootstrap.Modal(document.getElementById('modalAvanceDetails'));
  const tbody = document.querySelector('#tableAvances tbody');
  const prevBtn = document.getElementById('prevAvancePage');
  const nextBtn = document.getElementById('nextAvancePage');
  const firstBtn = document.getElementById('firstAvancePage');
  const lastBtn = document.getElementById('lastAvancePage');
  const pageInfo = document.getElementById('avancePageInfo');
  const totalItemsSpan = document.getElementById('avanceTotalItems');
  const perPageSelect = document.getElementById('avancePerPage');
  const searchInput = document.getElementById('searchAvance');
  const resetSearchBtn = document.getElementById('resetSearchAvance');
  const emptyState = document.getElementById('emptyStateAvance');
  const totalCountElement = document.getElementById('avanceTotalCount');

  let currentPage = 1;
  let currentPerPage = parseInt(perPageSelect?.value || 25);
  let totalItems = 0;
  let totalPages = 1;
  let currentSearchTerm = '';

  function updatePaginationButtons() {
    if (firstBtn && prevBtn && nextBtn && lastBtn) {
      firstBtn.disabled = currentPage <= 1;
      prevBtn.disabled = currentPage <= 1;
      nextBtn.disabled = currentPage >= totalPages;
      lastBtn.disabled = currentPage >= totalPages;
    }
    
    if (pageInfo) pageInfo.textContent = `${currentPage}/${Math.max(totalPages, 1)}`;
    if (totalItemsSpan) totalItemsSpan.textContent = totalItems;
    
    // Mettre à jour les indices de début et fin
    const startIndex = totalItems > 0 ? ((currentPage - 1) * currentPerPage) + 1 : 0;
    const endIndex = Math.min(currentPage * currentPerPage, totalItems);
    const startIndexEl = document.getElementById('avanceStartIndex');
    const endIndexEl = document.getElementById('avanceEndIndex');
    if (startIndexEl) startIndexEl.textContent = startIndex;
    if (endIndexEl) endIndexEl.textContent = endIndex;
  }

  function toggleEmptyState(show) {
    if (emptyState && tbody) {
      if (show) {
        tbody.classList.add('d-none');
        emptyState.classList.remove('d-none');
      } else {
        tbody.classList.remove('d-none');
        emptyState.classList.add('d-none');
      }
    }
  }

  function updateTotalCount(count) {
    if (totalCountElement) {
      totalCountElement.textContent = count.toLocaleString();
    }
  }

  function loadAvancesData(page = 1, searchTerm = '') {
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5"><div class="loading-spinner"><div class="spinner-border text-warning mb-3" role="status"><span class="visually-hidden">Chargement...</span></div><p class="mb-0">Chargement des avances...</p></div></td></tr>';
    
    const params = new URLSearchParams({
      page: page,
      perPage: currentPerPage,
      q: searchTerm
    });

    fetch('includes/ajax/admin/get_List_avance.php?' + params)
      .then(res => {
        if (!res.ok) throw new Error(`Erreur HTTP: ${res.status}`);
        return res.json();
      })
      .then(json => {
        // Vérification de la structure de réponse
        if (!json.ok) {
          throw new Error(json.error || 'Format de réponse invalide');
        }
        
        const rows = json.rows || [];
        totalItems = json.total || 0;
        totalPages = Math.ceil(totalItems / currentPerPage) || 1;

        // Mettre à jour le compteur total
        updateTotalCount(totalItems);

        if (!rows.length) {
          toggleEmptyState(true);
          updatePaginationButtons();
          return;
        }

        toggleEmptyState(false);
        tbody.innerHTML = '';

        rows.forEach((r, index) => {
          const globalIndex = ((page - 1) * currentPerPage) + index + 1;
          const tr = document.createElement('tr');
          
          // Formatage de la date
          const avanceDate = r.date_fmt || (r.date_avance ? new Date(r.date_avance).toLocaleDateString('fr-FR') : '-');
          
          tr.innerHTML = `
            <td class="ps-4">${globalIndex}</td>
            <td>
              <div class="d-flex align-items-center">
                <i class="bi bi-calendar-check text-primary me-2"></i>
                <small class="text-muted">${avanceDate}</small>
              </div>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar-placeholder bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                  <i class="bi bi-person"></i>
                </div>
                <div>
                  <strong class="d-block">${r.prenom || ''} ${r.nom || ''}</strong>
                  <small class="text-muted">${r.fonction || 'Ouvrier'}</small>
                </div>
              </div>
            </td>
            <td>
              <code class="bg-light px-2 py-1 rounded">${r.cin || '-'}</code>
            </td>
            <td>
              <div class="text-truncate" style="max-width: 250px;" title="${r.Motif || ''}">
                ${r.Motif || 'Non spécifié'}
              </div>
            </td>
            <td class="text-end pe-4">
              <span class="montant-avance">${parseFloat(r.montant || 0).toFixed(2)}</span>
            </td>
            
          `;
          tbody.appendChild(tr);
        });

        // Attacher les événements des boutons d'action
        attachActionEvents();

        updatePaginationButtons();
      })
      .catch(err => {
        console.error('Erreur chargement avances:', err);
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-4"><i class="bi bi-exclamation-triangle me-2"></i>${err.message}</td></tr>`;
        toggleEmptyState(true);
        updatePaginationButtons();
      });
  }

  function attachActionEvents() {
    // Bouton Voir détails
    tbody.querySelectorAll('.btn-details').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = e.currentTarget.dataset.id;
        // Redirection vers la page de détails de l'avance
        window.location.href = `details_avance.php?id=${id}`;
      });
    });

    // Bouton Imprimer
    tbody.querySelectorAll('.btn-print').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = e.currentTarget.dataset.id;
        // Ouverture de la fenêtre d'impression
        window.open(`imprimer_avance.php?id=${id}`, '_blank');
      });
    });

    // Clic sur une ligne (navigation vers les détails)
    tbody.querySelectorAll('tr').forEach(tr => {
      tr.addEventListener('click', (e) => {
        if (!e.target.closest('.btn-group')) {
          const id = tr.querySelector('.btn-details')?.dataset.id;
          if (id) {
            window.location.href = `details_avance.php?id=${id}`;
          }
        }
      });
    });
  }

  // Fonction debounce pour la recherche
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Événements de pagination
  if (firstBtn) {
    firstBtn.addEventListener('click', () => {
      currentPage = 1;
      loadAvancesData(currentPage, currentSearchTerm);
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        loadAvancesData(currentPage, currentSearchTerm);
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (currentPage < totalPages) {
        currentPage++;
        loadAvancesData(currentPage, currentSearchTerm);
      }
    });
  }

  if (lastBtn) {
    lastBtn.addEventListener('click', () => {
      currentPage = totalPages;
      loadAvancesData(currentPage, currentSearchTerm);
    });
  }

  // Changement du nombre d'éléments par page
  if (perPageSelect) {
    perPageSelect.addEventListener('change', (e) => {
      currentPerPage = parseInt(e.target.value);
      currentPage = 1;
      loadAvancesData(currentPage, currentSearchTerm);
    });
  }

  // Recherche en temps réel
  if (searchInput) {
    searchInput.addEventListener('input', debounce(function(e) {
      currentSearchTerm = e.target.value.trim();
      currentPage = 1;
      loadAvancesData(currentPage, currentSearchTerm);
    }, 300));
  }

  // Réinitialisation de la recherche
  if (resetSearchBtn) {
    resetSearchBtn.addEventListener('click', function() {
      if (searchInput) searchInput.value = '';
      currentSearchTerm = '';
      currentPage = 1;
      loadAvancesData(currentPage);
    });
  }

  // Chargement initial
  loadAvancesData();
  modal.show();
});

   // ===== CHARTS AMÉLIORÉS =====
  document.addEventListener("DOMContentLoaded", () => {
    // Configuration commune pour les graphiques
    const commonOptions = {
      responsive: true,
      maintainAspectRatio: true,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      scales: {
        x: {
          type: 'time',
          time: {
            unit: 'day',
            tooltipFormat: 'dd/MM/yyyy',
            displayFormats: { 
              day: 'dd/MM', 
              month: 'MM/yyyy',
              year: 'yyyy'
            }
          },
          title: { 
            display: true, 
            text: 'Date',
            color: '#6c757d',
            font: { weight: 'bold' }
          },
          grid: {
            color: 'rgba(0,0,0,0.1)'
          }
        },
        y: {
          beginAtZero: true,
          title: { 
            display: true, 
            text: 'Montant (MAD)',
            color: '#6c757d',
            font: { weight: 'bold' }
          },
          grid: {
            color: 'rgba(0,0,0,0.1)'
          },
          ticks: {
            callback: function(value) {
              return value.toLocaleString('fr-FR') + ' MAD';
            }
          }
        }
      },
      plugins: { 
        legend: { 
          display: true,
          labels: {
            usePointStyle: true,
            padding: 20
          }
        }, 
        tooltip: { 
          backgroundColor: 'rgba(0,0,0,0.8)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: 'rgba(255,255,255,0.2)',
          borderWidth: 1,
          cornerRadius: 8,
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              if (context.parsed.y !== null) {
                label += new Intl.NumberFormat('fr-FR', { 
                  style: 'currency', 
                  currency: 'MAD' 
                }).format(context.parsed.y);
              }
              return label;
            }
          }
        },
        title: {
          display: false
        }
      },
      animation: {
        duration: 2000,
        easing: 'easeOutQuart'
      },
      elements: {
        line: {
          tension: 0.3
        },
        point: {
          radius: 4,
          hoverRadius: 6,
          backgroundColor: '#fff',
          borderWidth: 2
        }
      }
    };

    // Données pour le graphique des versements
    const versementData = {
      datasets: [{
        label: 'Versements (MAD)',
        data: <?= json_encode(array_map(function ($label, $value) {
          return ['x' => $label, 'y' => (float) $value];
        }, $dataVersement['labels'] ?? [], $dataVersement['data'] ?? [])) ?>,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13, 110, 253, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.3,
        pointBackgroundColor: '#0d6efd',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#0d6efd'
      }]
    };

    // Données pour le graphique des bons d'huile
    const huileData = {
      datasets: [{
        label: 'Bons Huile (MAD)',
        data: <?= json_encode(array_map(function ($label, $value) {
          return ['x' => $label, 'y' => (float) $value];
        }, $dataHuile['labels'] ?? [], $dataHuile['data'] ?? [])) ?>,
        borderColor: '#198754',
        backgroundColor: 'rgba(25, 135, 84, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.3,
        pointBackgroundColor: '#198754',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#198754'
      }]
    };

    // Création des graphiques
    const chartVersements = new Chart(
      document.getElementById('chartVersements'), 
      { 
        type: 'line', 
        data: versementData, 
        options: commonOptions 
      }
    );
    
    const chartHuile = new Chart(
      document.getElementById('chartHuile'), 
      { 
        type: 'line', 
        data: huileData, 
        options: commonOptions 
      }
    );

    // Animation au survol des cartes de graphiques
    document.querySelectorAll('.chart-card').forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
      });
      
      card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
      });
    });

    // Effet de clic sur les cartes de graphiques
    document.querySelectorAll('.chart-card').forEach(card => {
      card.addEventListener('click', function() {
        // Effet de pulsation au clic
        this.style.transform = 'scale(0.98)';
        setTimeout(() => {
          this.style.transform = '';
        }, 150);
      });
    });
  });

</script>



<?php
require BASE_PATH . '/includes/scripts.php';
