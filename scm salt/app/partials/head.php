<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<?php if (!empty($no_cache)): ?>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<?php endif; ?>
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,700;1,9..40,400&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= asset('css/styles.css') ?>" />
</head>
