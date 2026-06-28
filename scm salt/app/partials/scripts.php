  <script>window.SALT_ROUTES = <?= json_encode(SALT_ROUTES, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<?php if (function_exists('antibot_page_token')): ?>
  <script>window.SALT_ANTIBOT = <?= json_encode(['token' => antibot_page_token()], JSON_UNESCAPED_UNICODE) ?>;</script>
<?php endif; ?>
  <script src="<?= asset('js/core/i18n.js') ?>"></script>
<?php if (!empty($with_checkout_data)): ?>
  <script src="<?= asset('js/core/checkout-data.js') ?>"></script>
  <script src="<?= asset('js/core/checkout-pending.js') ?>"></script>
<?php endif; ?>
  <script src="<?= asset('js/core/common.js') ?>"></script>
  <script src="<?= asset('js/core/antibot.js') ?>"></script>
<?php foreach ($core_scripts ?? [] as $script): ?>
  <script src="<?= asset('js/core/' . $script) ?>"></script>
<?php endforeach; ?>
<?php foreach ($scripts as $script): ?>
  <script src="<?= asset('js/pages/' . $script) ?>"></script>
<?php endforeach; ?>
