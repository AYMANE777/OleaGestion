<?php

require_once 'auth/auth_check.php';
require_once __DIR__ . '/../config/config.ini.php';
function auto_desactiver_bons(mysqli $connect) {
    $sql = "
        UPDATE bon_rue
        SET statut = 'DESACTIVE',
            commentaire = CONCAT(
                COALESCE(commentaire, ''), 
                ' | Auto désactivé le ', NOW()
            )
        WHERE duree IS NOT NULL
          AND duree < NOW()
          AND statut != 'DESACTIVE'
    ";
    mysqli_query($connect, $sql);
}

//  exécuter une fois par jour ou par session
$lastCheck = $_SESSION['last_auto_desact'] ?? 0;
if (time() - $lastCheck > 3600) { // 1h
    auto_desactiver_bons($connect);
    $_SESSION['last_auto_desact'] = time();
}

$activeSection = $activeSection ?? '';
$activePage    = $activePage ?? '';

function isActive($key, $activePage)   { return $key === $activePage ? ' active' : ''; }
function isOpen($key, $activeSection)  { return $key === $activeSection ? ' menu-open' : ''; }

  $mouvOpen = in_array($activePage ?? '', [
    'inventaire-mouvements-machines',
    'inventaire-mouvements-statut-pieces',
    'inventaire-mouvements-stock-pieces'
    
  ]);
  $userRole = $_SESSION['status'] ?? 'user';
?>


<style>
/* === NAVBAR ANIMÉE === */
#meknassaNav {
  background: linear-gradient(-45deg, #0f5132, #198754, #86cda2ff, #16a34a);
  background-size: 400% 400%;
  animation: bgWave 10s ease infinite;
  box-shadow: 0 3px 10px rgba(0,0,0,0.15);
  position: relative;
  z-index: 10;
  height: 70px;
}

@keyframes bgWave {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* === Logo à droite === */
.nav-logo {
  height: 70px;
  transition: transform 0.8s ease, filter 0.8s ease;
  filter: drop-shadow(0 0 3px rgba(255,255,255,0.4));
}
.nav-logo:hover {
  transform: rotate(360deg);
  filter: drop-shadow(0 0 8px rgba(255,255,255,0.8));
}

/* === Texte Date & Heure === */
#datetime {
  color: #fff;
  text-align: center;
  letter-spacing: 1px;
  text-shadow: 0 0 6px rgba(0,0,0,0.3);
  background: linear-gradient(90deg, #dcfce7, #bbf7d0, #86efac, #bbf7d0, #dcfce7);
  background-size: 300% 300%;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: textWave 6s ease infinite;
}

@keyframes textWave {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* === SIDEBAR ANIMÉE === */
.app-sidebar {
  background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%) !important;
  box-shadow: 3px 0 15px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
  border-right: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand {
  background: linear-gradient(135deg, #1a252f 0%, #2c3e50 100%);
  border-bottom: 1px solid rgba(255,255,255,0.1);
  padding: 1rem;
  position: relative;
  overflow: hidden;
}

.sidebar-brand::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  transition: left 0.6s ease;
}

.sidebar-brand:hover::before {
  left: 100%;
}

.brand-link {
  color: #ecf0f1 !important;
  font-weight: 600;
  font-size: 1.2rem;
  text-decoration: none;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.brand-link:hover {
  color: #3498db !important;
  transform: translateX(5px);
}

/* === MENU ITEMS ANIMÉS === */
.sidebar-menu {
  padding: 1rem 0;
}

.nav-item {
  position: relative;
  margin: 2px 0;
}

.nav-link {
  color: #bdc3c7 !important;
  padding: 0.75rem 1rem !important;
  border-radius: 8px;
  margin: 2px 8px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-link::before {
  content: '';
  position: absolute;
  left: -100%;
  top: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.2), transparent);
  transition: left 0.5s ease;
}

.nav-link:hover::before {
  left: 100%;
}

.nav-link:hover {
  color: #ecf0f1 !important;
  background: rgba(52, 152, 219, 0.1) !important;
  transform: translateX(5px);
  box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
}

.nav-link.active {
  color: #ffffff !important;
  background: linear-gradient(135deg, #3498db, #2980b9) !important;
  box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
  transform: translateX(8px);
}

.nav-link.active::after {
  content: '';
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 8px;
  height: 8px;
  background: #ffffff;
  border-radius: 50%;
  animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
  0%, 100% { transform: translateY(-50%) scale(1); opacity: 1; }
  50% { transform: translateY(-50%) scale(1.5); opacity: 0.7; }
}

/* === ICÔNES ANIMÉES === */
.nav-icon {
  font-size: 1.1rem;
  transition: all 0.3s ease;
  min-width: 20px;
  text-align: center;
}

.nav-link:hover .nav-icon {
  transform: scale(1.2) rotate(5deg);
  color: #3498db !important;
}

.nav-link.active .nav-icon {
  transform: scale(1.3);
  color: #ffffff !important;
}

/* === SOUS-MENUS ANIMÉS === */
.nav-treeview {
  background: rgba(0,0,0,0.1);
  border-radius: 0 0 8px 8px;
  margin: 0 8px;
  padding: 0.5rem 0;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.nav-treeview .nav-link {
  padding: -0.4rem 1rem 0rem 2.5rem !important;
  margin: 1px 0px;
  font-size: 0.9rem;
  border-left: 2px solid transparent;
}

.nav-treeview .nav-link:hover {
  border-left: 2px solid #3498db;
  background: rgba(52, 152, 219, 0.05) !important;
}

.nav-treeview .nav-link.active {
  border-left: 2px solid #ffffff;
  background: linear-gradient(135deg, rgba(52, 152, 219, 0.8), rgba(41, 128, 185, 0.8)) !important;
}

.nav-treeview .nav-icon {
  font-size: 0.8rem;
}

/* === FLÈCHES ANIMÉES === */
.nav-arrow {
  margin-left: auto;
  transition: transform 0.3s ease;
  font-size: 0.8rem;
}

.menu-open .nav-arrow {
  transform: rotate(90deg);
}

/* === ANIMATION D'APPARITION PROGRESSIVE === */
.sidebar-menu .nav-item {
  opacity: 0;
  transform: translateX(-20px);
  animation: slideInRight 0.5s ease forwards;
}

@keyframes slideInRight {
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Délais d'animation progressifs */
.sidebar-menu .nav-item:nth-child(1) { animation-delay: 0.1s; }
.sidebar-menu .nav-item:nth-child(2) { animation-delay: 0.2s; }
.sidebar-menu .nav-item:nth-child(3) { animation-delay: 0.3s; }
.sidebar-menu .nav-item:nth-child(4) { animation-delay: 0.4s; }
.sidebar-menu .nav-item:nth-child(5) { animation-delay: 0.5s; }
.sidebar-menu .nav-item:nth-child(6) { animation-delay: 0.6s; }
.sidebar-menu .nav-item:nth-child(7) { animation-delay: 0.7s; }
.sidebar-menu .nav-item:nth-child(8) { animation-delay: 0.8s; }
.sidebar-menu .nav-item:nth-child(9) { animation-delay: 0.9s; }

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .app-sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar-open .app-sidebar {
    transform: translateX(0);
  }
  
  .nav-link {
    padding: 1rem !important;
  }
}

/* === SCROLLBAR PERSONNALISÉE === */
.sidebar-wrapper::-webkit-scrollbar {
  width: 6px;
}

.sidebar-wrapper::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.1);
  border-radius: 3px;
}

.sidebar-wrapper::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #3498db, #2980b9);
  border-radius: 3px;
}

.sidebar-wrapper::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #2980b9, #3498db);
}
</style>

<?php if ($userRole === 'admin'): ?>
<nav class="app-header navbar navbar-expand bg-body" id="meknassaNav">
  <div class="container-fluid d-flex justify-content-between align-items-center">

    <!-- Bouton menu gauche -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list fs-4"></i>
        </a>
      </li>
    </ul>

    <!-- Date et Heure centrées -->
    <div id="datetime" class="fw-bold fs-5"></div>

    <!-- Logo à droite -->
    <div class="logo-container d-none d-md-block">
      <img src="<?= defined("APP_BASE") ? APP_BASE : "" ?>/assets/img/logo_meknassa-no-bg.png" alt="Meknassa Olive" class="nav-logo">
    </div>
  </div>
</nav>

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/index.php" class="brand-link">
          
            <span class="brand-text fw-light">Meknassa Olive</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false">

                <!-- INVENTAIRE -->
                <li class="nav-item<?php echo isOpen('inventaire', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('inventaire', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-box-seam-fill"></i>
                        <p>
                            L'inventaire
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('inventaire', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/ajouter.php"
                                class="nav-link<?php echo isActive('inventaire-ajouter', $activePage); ?>">
                                <i class="nav-icon bi bi-plus-circle"></i>
                                <p>Ajouter</p>
                            </a>
                        </li>

                        <li class="nav-item">
    <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-machine.php"
        class="nav-link<?php echo isActive('inventaire-liste-machine', $activePage); ?>">
        <i class="nav-icon bi bi-cpu"></i>
        <p>Liste des machines</p>
    </a>
</li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-piece.php"
                                class="nav-link<?php echo isActive('inventaire-liste-piece', $activePage); ?>">
                                <i class="nav-icon bi bi-nut"></i>
                                <p>Liste des pieces</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-reglements.php"
                                class="nav-link<?php echo isActive('inventaire-liste-reglements', $activePage); ?>">
                                <i class="nav-icon bi bi-receipt"></i>
                                <p>Liste des règlements</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-fournisseur.php"
                                class="nav-link<?php echo isActive('inventaire-liste-fournisseur', $activePage); ?>">
                                <i class="nav-icon bi bi-truck"></i>
                                <p>Liste des fournisseurs</p>
                            </a>
                        </li>
                        <!-- Mouvements -->
                        <li class="nav-item">
                            <a href="#" class="nav-link<?php echo $mouvOpen ? ' active' : ''; ?>">
                                <i class="nav-icon bi bi-arrow-left-right"></i>
                                <p>
                                    Mouvements
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview"
                                style="<?php echo $mouvOpen ? 'display:block' : 'display:none'; ?>">
                                <li class="nav-item">
                                    <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/mouvements-machines.php"
                                        class="nav-link<?php echo isActive('inventaire-mouvements-machines', $activePage); ?>">
                                        <i class="nav-icon bi bi-gear"></i>
                                        <p>Statut des machines</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/mouvements-statut-pieces.php"
                                        class="nav-link<?php echo isActive('inventaire-mouvements-statut-pieces', $activePage); ?>">
                                        <i class="nav-icon bi bi-tools"></i>
                                        <p>Statut des pièces</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/mouvements-stock-pieces.php"
                                        class="nav-link<?php echo isActive('inventaire-mouvements-stock-pieces', $activePage); ?>">
                                        <i class="nav-icon bi bi-box-seam"></i>
                                        <p>Stock des pièces</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <!-- AVANCES -->
                <li class="nav-item<?php echo isOpen('avances', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('avances', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-cash-stack"></i>
                        <p>
                            Avances ouvriers
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('avances', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/avances/ajouter.php"
                                class="nav-link<?php echo isActive('avances-ajouter', $activePage); ?>">
                                <i class="nav-icon bi bi-plus-circle"></i>
                                <p>Ajouter</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/avances/liste_avances.php"
                                class="nav-link<?php echo isActive('avances-liste_avances', $activePage); ?>">
                                <i class="nav-icon bi bi-list-check"></i>
                                <p>Liste des avances</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/avances/liste_ouvrier.php"
                                class="nav-link<?php echo isActive('avances-liste_ouvrier', $activePage); ?>">
                                <i class="nav-icon bi bi-people"></i>
                                <p>Liste des ouvriers</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- BON DE GRIGNON -->
                <li class="nav-item<?php echo isOpen('bon-Fitor', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('bon-Fitor', $activeSection) ? ' active' : ''; ?>">
                       <i class="nav-icon bi bi-file-earmark-fill"></i>
                        <p>
                            Bon de grignon
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('bon-Fitor', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Fitor/ajouter-Fitor.php"
                                class="nav-link<?php echo isActive('bon-ajouter-Fitor', $activePage); ?>">
                                <i class="nav-icon bi bi-plus-circle"></i>
                                <p>Ajouter</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Fitor/versement-Fitour.php"
                                class="nav-link<?php echo isActive('Versement', $activePage); ?>">
                                <i class="nav-icon bi bi-cash-coin"></i>
                                <p>Versement</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Fitor/taratonage-Fitor.php"
                                class="nav-link<?php echo isActive('Taratonage', $activePage); ?>">
                                <i class="nav-icon bi bi-clipboard-check"></i>
                                <p>Taratonage</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Fitor/generer-bon.php"
                                class="nav-link<?php echo isActive('Generer-bon', $activePage); ?>">
                                <i class="nav-icon bi bi-file-earmark-plus"></i>
                                <p>Générer le Bon</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Fitor/liste-patron.php"
                                class="nav-link<?php echo isActive('Liste des patrons', $activePage); ?>">
                                <i class="nav-icon bi bi-person-badge"></i>
                                <p>Liste des Patron</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Fitor/liste-chauffeur.php"
                                class="nav-link<?php echo isActive('Liste des chauffeurs', $activePage); ?>">
                                <i class="nav-icon bi bi-person-wheelchair"></i>
                                <p>Liste des Chauffeur</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Fitor/liste-vehicule.php"
                                class="nav-link<?php echo isActive('Liste des vehicules', $activePage); ?>">
                                <i class="nav-icon bi bi-truck-front"></i>
                                <p>Liste des Vehicules</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- BON D'HUILE -->
                <li class="nav-item<?php echo isOpen('bon-Huile', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('bon-Huile', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-file-earmark-text-fill"></i>
                        <p>
                            Bon d'huile
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('bon-Huile', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Huile/ajouter.php"
                                class="nav-link<?php echo isActive('bon-huile-ajouter', $activePage); ?>">
                                <i class="nav-icon bi bi-person-plus"></i>
                                <p>Client</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Huile/versement-client.php"
                                class="nav-link<?php echo isActive('bon-huile-versement', $activePage); ?>">
                                <i class="nav-icon bi bi-cash-stack"></i>
                                <p>Versement Client</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Huile/ajouter-bidons.php"
                                class="nav-link<?php echo isActive('add Bidons', $activePage); ?>">
                                <i class="nav-icon bi bi-droplet"></i>
                                <p>Bidons de Huile</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Huile/bon-reception-huile.php"
                                class="nav-link<?php echo isActive('bon reception huile', $activePage); ?>">
                                <i class="nav-icon bi bi-cart-plus"></i>
                                <p>Achat d'huile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Huile/bon-reception-bidon.php"
                                class="nav-link<?php echo isActive('bon reception bidon', $activePage); ?>">
                                <i class="nav-icon bi bi-box-seam"></i>
                                <p>Achat de bidons</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-Huile/solde-client.php"
                                class="nav-link<?php echo isActive('client solde status', $activePage); ?>">
                                <i class="nav-icon bi bi-graph-up"></i>
                                <p>Solde Client</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- TRANSPORTEURS -->
                <li class="nav-item<?php echo isOpen('Transporteur', $activeSection); ?>">
                    <a href="#"
                        class="nav-link<?php echo isOpen('Transporteur', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-truck"></i>
                        <p>Transporteurs
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('Transporteur', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/Transporteur/add-trans.php"
                                class="nav-link<?php echo isActive('Transporteur-ajouter', $activePage); ?>">
                                <i class="nav-icon bi bi-person-plus"></i>
                                <p>Transporteur</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/Transporteur/add-vehicule-trans.php"
                                class="nav-link<?php echo isActive('add-vehicule-transp', $activePage); ?>">
                                <i class="nav-icon bi bi-plus-circle"></i>
                                <p>Vehicule</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/Transporteur/taratonage-Transporteur.php"
                                class="nav-link<?php echo isActive('Taratonage-Trans', $activePage); ?>">
                                <i class="nav-icon bi bi-clipboard-check"></i>
                                <p>Taratonage</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- OUBLIER BON -->
                <li class="nav-item<?php echo isOpen('bon-oublier', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('bon-oublier', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-trash"></i>
                        <p>
                            Oublier Bon
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('bon-oublier', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-oublier/ajouter-Client.php"
                                class="nav-link<?php echo isActive('bon-ajouter-oublier', $activePage); ?>">
                                <i class="nav-icon bi bi-file-earmark-plus"></i>
                                <p>Générer</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- CLIENT CREDITEUR OLIVE -->
                <li class="nav-item<?php echo isOpen('ClientCrediteurOlive', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('ClientCrediteurOlive', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-credit-card"></i>
                        <p>
                            Client Credit Olive
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('ClientCrediteurOlive', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-client-crediteur/add-client-crediteur.php"
                                class="nav-link<?php echo isActive('ClientCrediteurOlive-ajouter', $activePage); ?>">
                                <i class="nav-icon bi bi-plus-circle"></i>
                                <p>Générer</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-client-crediteur/taratonage-cred.php"
                                class="nav-link<?php echo isActive('ClientCrediteurOlive-taratonage', $activePage); ?>">
                                <i class="nav-icon bi bi-clipboard-check"></i>
                                <p>Taratonage</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-client-crediteur/versement-cred.php"
                                class="nav-link<?php echo isActive('ClientCrediteurOlive-versement', $activePage); ?>">
                                <i class="nav-icon bi bi-cash-stack"></i>
                                <p>Versement</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/bon-client-crediteur/soldeClientOlive.php"
                                class="nav-link<?php echo isActive('SoldeClientOlive', $activePage); ?>">
                                <i class="nav-icon bi bi-graph-up"></i>
                                <p>Solde des Clients</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- JOURNAL -->
                <li class="nav-item<?php echo isOpen('journale-status', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('journale-status', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-journals"></i>
                        <p>
                            Journal
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('journale-status', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/fournisseur-status.php"
                                class="nav-link<?php echo isActive('journale-status-reglement', $activePage); ?>">
                                <i class="nav-icon bi bi-receipt"></i>
                                <p>Reglement fournisseurs</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/ouvrier-status.php"
                                class="nav-link<?php echo isActive('journale-status-ouvrier', $activePage); ?>">
                                <i class="nav-icon bi bi-person-check"></i>
                                <p>Réglement des Ouvrier</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/bidon-huile-status.php"
                                class="nav-link<?php echo isActive('journale-status-bidon-huile', $activePage); ?>">
                                <i class="nav-icon bi bi-droplet"></i>
                                <p>Huile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/bidon-status.php"
                                class="nav-link<?php echo isActive('journale-status-bidon', $activePage); ?>">
                                <i class="nav-icon bi bi-box-seam"></i>
                                <p>Bidon</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/status-client-crediteur.php"
                                class="nav-link<?php echo isActive('journale-status-client-credit', $activePage); ?>">
                                <i class="nav-icon bi bi-credit-card"></i>
                                <p>Client Crediteur</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/versement-client-crediteur.php"
                                class="nav-link<?php echo isActive('journale-versement-client-credit', $activePage); ?>">
                                <i class="nav-icon bi bi-cash-coin"></i>
                                <p>Versement Client Crediteur</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/status-taratonage-crediteur.php"
                                class="nav-link<?php echo isActive('journale-status-taratonage-credit', $activePage); ?>">
                                <i class="nav-icon bi bi-clipboard-data"></i>
                                <p>Status Taratonage</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/taratonage-crediteur.php"
                                class="nav-link<?php echo isActive('journale-taratonage-credit', $activePage); ?>">
                                <i class="nav-icon bi bi-clipboard-check"></i>
                                <p>Taratonage</p>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>
<?php elseif ($userRole === 'yassine'): ?>
<nav class="app-header navbar navbar-expand bg-body" id="meknassaNav">
  <div class="container-fluid d-flex justify-content-between align-items-center">

    <!-- Bouton menu gauche -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list fs-4"></i>
        </a>
      </li>
    </ul>

    <!-- Date et Heure centrées -->
    <div id="datetime" class="fw-bold fs-5"></div>

    <!-- Logo à droite -->
    <div class="logo-container d-none d-md-block">
      <img src="<?= defined("APP_BASE") ? APP_BASE : "" ?>/assets/img/logo_meknassa-no-bg.png" alt="Meknassa Olive" class="nav-logo">
    </div>
  </div>
</nav>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/index.php" class="brand-link">
            <span class="brand-text fw-light">Meknassa Olive</span>
        </a>
    </div>
    

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false">
<!-- avances -->
                <li class="nav-item<?php echo isOpen('avances', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('avances', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-cash-stack"></i>
                        <p>
                            Avances ouvriers
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('avances', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/avances/ajouter.php"
                                class="nav-link<?php echo isActive('avances-ajouter', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Ajouter</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/avances/liste_avances.php"
                                class="nav-link<?php echo isActive('avances-liste_avances', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Liste des avances</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/avances/liste_ouvrier.php"
                                class="nav-link<?php echo isActive('avances-liste_ouvrier', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Liste des ouvriers</p>
                            </a>
                        </li>


                    </ul>
                </li>
                <!-- INVENTAIRE -->
                <li class="nav-item<?php echo isOpen('inventaire', $activeSection); ?>">
                    <a href="#" class="nav-link<?php echo isOpen('inventaire', $activeSection) ? ' active' : ''; ?>">
                        <i class="nav-icon bi bi-box-seam-fill"></i>
                        <p>
                            L'inventaire
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('inventaire', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/ajouter.php"
                                class="nav-link<?php echo isActive('inventaire-ajouter', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Ajouter</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-machine.php"
                                class="nav-link<?php echo isActive('inventaire-liste-machine', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Liste des machines</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-piece.php"
                                class="nav-link<?php echo isActive('inventaire-liste-piece', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Liste des pieces</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-reglements.php"
                                class="nav-link<?php echo isActive('inventaire-liste-reglements', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Liste des règlements</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/liste-fournisseur.php"
                                class="nav-link<?php echo isActive('inventaire-liste-fournisseur', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Liste des fournisseurs</p>
                            </a>
                        </li>
                        <!-- Mouvements -->
                        <li class="nav-item">
                            <a href="#" class="nav-link<?php echo $mouvOpen ? ' active' : ''; ?>">
                                <i class="nav-icon bi bi-arrow-left-right"></i>
                                <p>
                                    Mouvements
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview"
                                style="<?php echo $mouvOpen ? 'display:block' : 'display:none'; ?>">
                                <li class="nav-item">
                                    <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/mouvements-machines.php"
                                        class="nav-link<?php echo isActive('inventaire-mouvements-machines', $activePage); ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Statut des machines</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/mouvements-statut-pieces.php"
                                        class="nav-link<?php echo isActive('inventaire-mouvements-statut-pieces', $activePage); ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Statut des pièces</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/inventaire/mouvements-stock-pieces.php"
                                        class="nav-link<?php echo isActive('inventaire-mouvements-stock-pieces', $activePage); ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Stock des pièces</p>
                                    </a>
                                </li>
                            </ul>
                        </li>


                    </ul>
                </li>
                
                <!-- Debut du journal des details -->
                  <li class="nav-item<?php echo isOpen('journale-status', $activeSection); ?>">
                     <a href="#" class="nav-link<?php echo isOpen('journale-status', $activeSection) ? ' active' : ''; ?>">
                           <i class="nav-icon bi bi-journals"></i>
                        <p>
                            Journal
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview"
                        style="<?php echo isOpen('journale-status', $activeSection) ? 'display:block' : 'display:none'; ?>">
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/fournisseur-status.php"
                                class="nav-link<?php echo isActive('journale-status-reglement', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Reglement fournisseurs</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/ouvrier-status.php"
                                class="nav-link<?php echo isActive('journale-status-ouvrier', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Réglement des Ouvrier</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/bidon-huile-status.php"
                                class="nav-link<?php echo isActive('journale-status-bidon-huile', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Bidon et Huile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/bidon-status.php"
                                class="nav-link<?php echo isActive('journale-status-bidon', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Bidon</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/status-client-crediteur.php"
                                class="nav-link<?php echo isActive('journale-status-client-credit', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Client Crediteur</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/reglement-status/versement-client-crediteur.php"
                                class="nav-link<?php echo isActive('journale-versement-client-credit', $activePage); ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Versement Client Crediteur</p>
                            </a>
                        </li>
                        
                    </ul>
                </li>
                <!-- Fin du journal des details -->
 </ul>
        </nav>
    </div>
</aside>


<?php elseif ($userRole === 'user'): ?>
<nav class="app-header navbar navbar-expand bg-body" id="meknassaNav">
  <div class="container-fluid d-flex justify-content-between align-items-center">

    <!-- Bouton menu gauche -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list fs-4"></i>
        </a>
      </li>
    </ul>

    <!-- Date et Heure centrées -->
    <div id="datetime" class="fw-bold fs-5"></div>

    <!-- Logo à droite -->
    <div class="logo-container d-none d-md-block">
      <img src="<?= defined("APP_BASE") ? APP_BASE : "" ?>/assets/img/logo_meknassa-no-bg.png" alt="Meknassa Olive" class="nav-logo">
    </div>
  </div>
</nav>

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-wrapper">
        <div class="text-center p-4 d-flex flex-column justify-content-center align-items-center" style="height: 100%;">
            <img src="<?= defined("APP_BASE") ? APP_BASE : "" ?>/assets/img/logo_meknassa-no-bg.png" alt="Meknassa Olive" width="150" class="mb-3">
            <p class="text-white mb-3">Accès limité à la page d'accueil</p>
            <a href="<?= defined("APP_BASE") ? APP_BASE : "" ?>/index.php" class="btn btn-primary w-100">Aller à l'accueil</a>
        </div>
    </div>
</aside>
<?php endif; ?>
<script>
    // === Horloge dynamique ===
function updateDateTime() {
  const now = new Date();
  const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  const date = now.toLocaleDateString('fr-FR', optionsDate);
  const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  document.getElementById("datetime").innerHTML = `${date} — ${time}`;
}
updateDateTime();
setInterval(updateDateTime, 1000);
</script>