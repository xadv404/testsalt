<?php

declare(strict_types=1);

require __DIR__ . '/../partials/head.php';

?>
<body data-page="<?= htmlspecialchars($page_id, ENT_QUOTES, 'UTF-8') ?>">
<script>try{if(localStorage.getItem("salt-theme")==="light")document.body.classList.add("light-theme")}catch(e){}</script>
  <div class="page">
<?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="main">
      <div class="content">
<?php if ($progress_step !== null): ?>
<?php $currentStep = $progress_step; require __DIR__ . '/../partials/progress-steps.php'; ?>
<?php endif; ?>
<?php require $view_file; ?>
      </div>
    </main>

<?php
$with_checkout_data = $with_checkout_data ?? true;
require __DIR__ . '/../partials/footer.php';
?>
  </div>
<?php require __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
