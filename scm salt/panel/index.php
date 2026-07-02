<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

panel_redirect_if_authenticated();

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="color-scheme" content="dark" />
  <title>Panel — Connexion</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/panel.css" />
</head>
<body class="panel-login">
  <div class="panel-login__card panel-card-interactive" data-accent="#00e676">
    <header class="panel-login__header">
      <p class="panel-login__subtitle">SCAMMA SALT - @IBLISV2</p>
    </header>

    <form id="panel-login-form" novalidate>
      <div class="panel-field">
        <label for="panel-key">Clé d'accès</label>
        <input
          type="password"
          id="panel-key"
          name="key"
          placeholder="Entrez votre clé"
          autocomplete="off"
          required
        />
      </div>

      <button type="submit" class="panel-btn panel-btn--primary" disabled>CONTINUER</button>
      <p class="panel-error" role="alert">Clé invalide.</p>
    </form>
  </div>

  <script src="js/panel.js"></script>
  <script>
    (async function () {
      const form = document.getElementById("panel-login-form");
      panelInitLogin(form);
      panelInitHoverCards(document.body);
    })();
  </script>
</body>
</html>
