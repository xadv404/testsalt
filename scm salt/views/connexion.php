        <h1 class="title" data-i18n="welcome">Bienvenue</h1>

        <div class="info-box">
          <p data-i18n="infoText">Accédez à votre compte client Salt Mobile et Salt Home.</p>
        </div>

        <form class="login-form" action="#" method="post" novalidate>
          <p class="required-hint" data-i18n="requiredHint">* Champ obligatoire</p>

          <div class="fields-row">
            <div class="field">
              <label for="email" data-i18n="emailLabel">Adresse email *</label>
              <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                required
              />
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit" id="submit-btn" disabled data-i18n="continue">CONTINUER</button>
          </div>
        </form>
