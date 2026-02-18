<!doctype html>
<html lang="fr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Meknassa Olive – Connexion</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
  <link rel="icon" type="image/png" href="assets/img/logo_meknassa-no-bg.png" sizes="64x64">

  <!-- Fonts -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
    integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" />

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    crossorigin="anonymous" />

  <!-- OverlayScrollbars -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
    crossorigin="anonymous" />

  <!-- AdminLTE -->
  <link rel="stylesheet" href="css/adminlte.css" />

  <style>
    :root {
      --olive-dark:   #0f5132;
      --olive-mid:    #198754;
      --olive-light:  #d1e7dd;
    }

    html, body {
      height: 100%;
    }

    body.login-page {
      background: linear-gradient(135deg, #0f5132 0%, #1a7a4a 40%, #145a32 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Source Sans 3', sans-serif;
    }

    /* ── Login box ── */
    .login-box {
      width: 100%;
      max-width: 420px;
      padding: 1rem;
    }

    /* ── Card ── */
    .login-card {
      border: none;
      border-radius: 1.25rem;
      box-shadow: 0 20px 60px rgba(0,0,0,0.35);
      overflow: hidden;
      animation: slideUp .5s ease both;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Header strip ── */
    .login-header {
      background: linear-gradient(-45deg, #0f5132, #198754, #16a34a, #0d9488);
      background-size: 400% 400%;
      animation: bgWave 8s ease infinite;
      padding: 2rem 1.5rem 1.5rem;
      text-align: center;
    }

    @keyframes bgWave {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .login-logo {
      width: 90px;
      filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
      transition: transform .6s ease;
    }
    .login-logo:hover { transform: rotate(360deg); }

    .login-header h4 {
      color: #fff;
      font-weight: 700;
      margin-top: .75rem;
      letter-spacing: .5px;
    }

    .login-header p {
      color: rgba(255,255,255,.75);
      font-size: .9rem;
      margin: 0;
    }

    /* ── Body ── */
    .login-body {
      padding: 2rem 2rem 1.5rem;
      background: #fff;
    }

    /* ── Input group ── */
    .input-group .form-control {
      border-radius: .6rem 0 0 .6rem !important;
      border-right: 0;
      padding: .7rem 1rem;
      font-size: .95rem;
      transition: box-shadow .2s;
    }
    .input-group .form-control:focus {
      box-shadow: 0 0 0 .2rem rgba(25,135,84,.25);
      border-color: var(--olive-mid);
      outline: none;
    }
    .input-group-text {
      border-radius: 0 .6rem .6rem 0 !important;
      background: #f8f9fa;
      border-color: #dee2e6;
      color: #6c757d;
      transition: color .2s, background .2s;
    }
    .input-group:focus-within .input-group-text {
      color: var(--olive-mid);
      background: var(--olive-light);
      border-color: var(--olive-mid);
    }

    /* ── Submit button ── */
    .btn-login {
      background: linear-gradient(135deg, #198754, #0f5132);
      border: none;
      border-radius: .75rem;
      padding: .75rem;
      font-size: 1rem;
      font-weight: 600;
      letter-spacing: .5px;
      transition: transform .15s, box-shadow .2s;
      position: relative;
      overflow: hidden;
    }
    .btn-login::after {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(255,255,255,0);
      transition: background .2s;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(15,81,50,.35);
    }
    .btn-login:hover::after { background: rgba(255,255,255,.08); }
    .btn-login:active { transform: translateY(0); }

    /* ── Remember me ── */
    .form-check-input:checked {
      background-color: var(--olive-mid);
      border-color: var(--olive-mid);
    }

    /* ── Footer strip ── */
    .login-footer {
      background: #f8f9fa;
      border-top: 1px solid #e9ecef;
      padding: .9rem 2rem;
      text-align: center;
      font-size: .8rem;
      color: #6c757d;
    }

    /* ── Show/hide password toggle ── */
    .toggle-password {
      cursor: pointer;
      user-select: none;
    }
    .toggle-password:hover { color: var(--olive-mid) !important; }

    /* ── Loading state ── */
    .btn-login.loading {
      pointer-events: none;
      opacity: .85;
    }

    @media (max-width: 480px) {
      .login-body { padding: 1.5rem 1.25rem 1rem; }
    }
  </style>
</head>

<body class="login-page">
  <div class="login-box">
    <div class="card login-card">

      <!-- Header -->
      <div class="login-header">
        <a href="https://www.meknassaolive.com" target="_blank" rel="noopener noreferrer">
          <img src="assets/img/logo_meknassa-no-bg.png" alt="Meknassa Olive" class="login-logo" />
        </a>
        <h4>Meknassa Olive</h4>
        <p>Connectez-vous pour accéder au tableau de bord</p>
      </div>

      <!-- Body -->
      <div class="login-body">
        <form id="loginForm" autocomplete="on" novalidate>

          <!-- Email -->
          <div class="input-group mb-3">
            <input
              type="email"
              name="email"
              id="inputEmail"
              class="form-control"
              placeholder="Adresse e-mail"
              autocomplete="username"
              required
            />
            <span class="input-group-text">
              <i class="bi bi-envelope-fill"></i>
            </span>
          </div>

          <!-- Password -->
          <div class="input-group mb-3">
            <input
              type="password"
              name="password"
              id="inputPassword"
              class="form-control"
              placeholder="Mot de passe"
              autocomplete="current-password"
              required
            />
            <span class="input-group-text toggle-password" id="togglePwd" title="Afficher / masquer">
              <i class="bi bi-eye-fill" id="togglePwdIcon"></i>
            </span>
          </div>

          <!-- Remember me -->
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="rememberMe" />
              <label class="form-check-label text-muted" for="rememberMe">Se souvenir de moi</label>
            </div>
          </div>

          <!-- Submit -->
          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-login text-white" id="btnLogin">
              <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </button>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div class="login-footer">
        &copy; <?= date('Y') ?> Meknassa Olive &mdash; Tous droits réservés
      </div>

    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/adminlte.js"></script>

  <script>
    // ── Toggle show/hide password ──
    document.getElementById('togglePwd').addEventListener('click', function () {
      const input = document.getElementById('inputPassword');
      const icon  = document.getElementById('togglePwdIcon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash-fill';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-fill';
      }
    });

    // ── Login form submit ──
    document.getElementById('loginForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const email    = document.getElementById('inputEmail').value.trim();
      const password = document.getElementById('inputPassword').value.trim();
      const btn      = document.getElementById('btnLogin');

      if (!email || !password) {
        Swal.fire({
          icon: 'warning',
          title: 'Champs requis',
          text: 'Veuillez saisir votre e-mail et mot de passe.',
          confirmButtonColor: '#198754',
          confirmButtonText: 'OK'
        });
        return;
      }

      // Loading state
      btn.classList.add('loading');
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Connexion…';

      const formData = new FormData(this);

      fetch('includes/ajax/login/login.php', {
        method: 'POST',
        body: formData
      })
        .then(function (response) {
          if (!response.ok && response.status !== 400) {
            throw new Error('Erreur réseau (' + response.status + ')');
          }
          return response.json();
        })
        .then(function (data) {
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Connexion réussie !',
              text: data.message,
              showConfirmButton: false,
              timer: 1400,
              timerProgressBar: true
            });
            setTimeout(function () {
              window.location.href = 'index.php';
            }, 1500);
          } else {
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Se connecter';
            Swal.fire({
              icon: 'error',
              title: 'Erreur de connexion',
              text: data.message || 'Identifiants incorrects.',
              confirmButtonColor: '#198754',
              confirmButtonText: 'Réessayer'
            });
          }
        })
        .catch(function (err) {
          console.error(err);
          btn.classList.remove('loading');
          btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Se connecter';
          Swal.fire({
            icon: 'error',
            title: 'Erreur serveur',
            text: 'Impossible de joindre le serveur. Veuillez réessayer.',
            confirmButtonColor: '#198754',
            confirmButtonText: 'Fermer'
          });
        });
    });
  </script>
</body>
</html>
