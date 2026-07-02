    <div class="site-header">
      <header class="top-bar">
        <div class="top-bar__left">
          <button type="button" class="icon-btn" id="theme-toggle" data-i18n-aria="themeToggle">
            <svg class="icon-theme icon-theme--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
              <circle cx="12" cy="12" r="4"/>
              <line x1="12" y1="2" x2="12" y2="4"/>
              <line x1="12" y1="20" x2="12" y2="22"/>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
              <line x1="2" y1="12" x2="4" y2="12"/>
              <line x1="20" y1="12" x2="22" y2="12"/>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
            <svg class="icon-theme icon-theme--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
          </button>
          <span class="top-bar__divider" aria-hidden="true"></span>
        </div>
        <div class="lang-dropdown" id="lang-dropdown">
          <button type="button" class="lang-select" id="lang-toggle" aria-haspopup="listbox" aria-expanded="false" data-i18n-aria="langMenu">
            <span id="lang-current">FR</span>
            <svg class="icon-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>
          <ul class="lang-dropdown__menu" id="lang-menu" role="listbox" hidden>
            <li><button type="button" role="option" data-lang="fr">FR</button></li>
            <li><button type="button" role="option" data-lang="de">DE</button></li>
            <li><button type="button" role="option" data-lang="it">IT</button></li>
            <li><button type="button" role="option" data-lang="en">EN</button></li>
          </ul>
        </div>
      </header>

      <div class="brand-bar">
        <a href="<?= page_url('connexion') ?>" class="logo" data-i18n-aria="logoAria">Salt.</a>
      </div>
    </div>
