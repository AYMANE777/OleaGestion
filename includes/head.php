<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <title>Meknassa</title>

    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="color-scheme" content="light dark" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
    // Compute a root-relative base path that works both locally and on production.
    // e.g. locally:   http://localhost/gestionMeknassa/anything.php → base = '/gestionMeknassa'
    //      cPanel:    https://example.com/gestionMeknassa/sub/page.php → base = '/gestionMeknassa'
    if (!defined('APP_BASE')) {
        // __DIR__ is always .../gestionMeknassa/includes — go one level up to reach project root
        $projectRoot = str_replace('\\', '/', dirname(__DIR__));
        // DOCUMENT_ROOT is the webserver root (e.g. C:/xampp/htdocs)
        $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
        // base = project root relative to document root
        $base = '/' . ltrim(str_replace($docRoot, '', $projectRoot), '/');
        define('APP_BASE', rtrim($base, '/'));
    }
    $base = APP_BASE;
?>
    <link rel="icon" type="image/png" href="<?= $base ?>/assets/img/logo_meknassa-no-bg.png" sizes="64x64">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />

    <link rel="preload" href="<?= $base ?>/css/adminlte.css" as="style" />
    <link rel="stylesheet" href="<?= $base ?>/css/adminlte.css" />
    <!-- cherhce -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

    <style>
    .choices__list--dropdown .choices__input {
        color: #000 !important;
        font-weight: 500;
        background-color: #fff;
    }

    .choices__list--dropdown .choices__input::placeholder {
        color: #555 !important;
    }


      /* Details toggle visibility */
  .details-row { display: none; }
  .details-row.open { display: table-row; }

  /* Details layout */
  .details-wrap { padding: .75rem 0; }
  .details-title { font-weight: 600; margin-bottom: .5rem; }
  .details-grid {
    display: grid;
    gap: .5rem;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  }
  .d-item { background: var(--bs-light, #f7f7f7); border-radius: .5rem; padding: .5rem .75rem; }
  .d-k { font-size: .85rem; color: #6c757d; margin-bottom: .25rem; }
  .d-v { word-break: break-word; }

  /* Images in details */
  .d-images { display: flex; flex-wrap: wrap; gap: .5rem; }
  .d-img-link { display: inline-block; line-height: 0; }
  .d-img-thumb {
    max-width: 120px; max-height: 120px; object-fit: cover;
    border-radius: .5rem; display: block;
  }

  /* Optional: hover affordance on clickable row */
  tr.data-row[role="button"] { cursor: pointer; }
  tr.data-row[aria-expanded="true"] { outline: 2px solid rgba(0,0,0,.08); }
    </style>
    <!-- ..... -->
</head>


<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">

    <div class="app-wrapper">