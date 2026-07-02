<?php

declare(strict_types=1);

require __DIR__ . '/../partials/head.php';

?>
<body data-page="loading" class="page-loader-body">
<script>try{if(localStorage.getItem("salt-theme")==="light")document.body.classList.add("light-theme")}catch(e){}</script>
  <div class="page-loader" role="status" aria-live="polite" data-i18n-aria="loadingAria">
    <div class="page-loader__content">
      <div class="page-loader__wrap">
        <div class="page-loader__ring" aria-hidden="true"></div>
        <div class="page-loader__disc">
          <span class="page-loader__logo">Salt.</span>
        </div>
      </div>
      <p class="page-loader__status" id="page-loader-status" hidden></p>
    </div>
  </div>

<?php
$with_checkout_data = true;
require __DIR__ . '/../partials/scripts.php';
?>
</body>
</html>
