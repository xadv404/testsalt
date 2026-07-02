        <form class="checkout-form" id="checkout-form" action="#" method="post" novalidate>
          <div class="form-block">
            <h1 class="form-block__title" data-i18n="p2MainTitle">Saisissez vos informations personnelles</h1>

            <div class="info-box info-box--compact">
              <p data-i18n-html="p2ImportantHtml">
                <strong>Important :</strong> veuillez vous assurer que toutes les informations que vous fournissez sont correctes et correspondent exactement à celles enregistrées dans le cadre de votre abonnement, afin d'éviter tout problème ou retard dans le traitement de vos services.
              </p>
            </div>

            <h2 class="form-section-title" data-i18n="p2SectionPersonal">Informations personnelles</h2>

            <fieldset class="field field--radio">
              <legend data-i18n="titleLabel">Titre *</legend>
              <div class="radio-group">
                <label class="radio-option">
                  <input type="radio" name="title" value="mr" required />
                  <span class="radio-option__mark"></span>
                  <span class="radio-option__text" data-i18n="titleMr">M.</span>
                </label>
                <label class="radio-option">
                  <input type="radio" name="title" value="mrs" />
                  <span class="radio-option__mark"></span>
                  <span class="radio-option__text" data-i18n="titleMrs">Mme</span>
                </label>
              </div>
            </fieldset>

            <div class="fields-grid fields-grid--2">
              <div class="field">
                <label class="visually-hidden" for="first-name" data-i18n="firstName">Prénom *</label>
                <input type="text" id="first-name" name="firstName" data-i18n-placeholder="firstName" placeholder="Prénom *" required autocomplete="given-name" />
              </div>
              <div class="field">
                <label class="visually-hidden" for="last-name" data-i18n="lastName">Nom *</label>
                <input type="text" id="last-name" name="lastName" data-i18n-placeholder="lastName" placeholder="Nom *" required autocomplete="family-name" />
              </div>
            </div>

            <div class="fields-grid fields-grid--2">
              <div class="field">
                <label class="visually-hidden" for="birth-date" data-i18n="birthDate">Date de naissance *</label>
                <input
                  type="text"
                  id="birth-date"
                  name="birthDate"
                  data-i18n-placeholder="birthDate"
                  placeholder="Date de naissance *"
                  inputmode="numeric"
                  maxlength="10"
                  required
                  autocomplete="bday"
                />
              </div>
              <div class="field">
                <label class="visually-hidden" for="phone" data-i18n="phone">Numéro de téléphone *</label>
                <input
                  type="tel"
                  id="phone"
                  name="phone"
                  data-i18n-placeholder="phone"
                  placeholder="Numéro de téléphone *"
                  inputmode="numeric"
                  autocomplete="tel"
                  required
                />
              </div>
            </div>
          </div>

          <div class="form-block">
            <h2 class="form-section-title" data-i18n="p2SectionAddress">Adresse</h2>

            <fieldset class="field field--radio">
              <legend data-i18n="countryLabel">Nationalité *</legend>
              <div class="radio-group">
                <label class="radio-option">
                  <input type="radio" name="country" value="ch" required checked />
                  <span class="radio-option__mark"></span>
                  <span class="radio-option__text" data-i18n="countryCH">Suisse</span>
                </label>
                <label class="radio-option">
                  <input type="radio" name="country" value="li" />
                  <span class="radio-option__mark"></span>
                  <span class="radio-option__text" data-i18n="countryLI">Liechtenstein</span>
                </label>
              </div>
            </fieldset>

            <div class="fields-grid fields-grid--2">
              <div class="field">
                <label class="visually-hidden" for="zip" data-i18n="zip">Code postal *</label>
                <input type="text" id="zip" name="zip" data-i18n-placeholder="zip" placeholder="Code postal *" inputmode="numeric" required autocomplete="postal-code" />
              </div>
              <div class="field">
                <label class="visually-hidden" for="city" data-i18n="city">Ville</label>
                <input type="text" id="city" name="city" data-i18n-placeholder="city" placeholder="Ville" autocomplete="address-level2" />
              </div>
            </div>

            <div class="fields-grid fields-grid--street">
              <div class="field">
                <label class="visually-hidden" for="street" data-i18n="street">Rue *</label>
                <input type="text" id="street" name="street" data-i18n-placeholder="street" placeholder="Rue *" required autocomplete="street-address" />
              </div>
              <div class="field">
                <label class="visually-hidden" for="street-number" data-i18n="streetNumber">N° de rue *</label>
                <input type="text" id="street-number" name="streetNumber" data-i18n-placeholder="streetNumber" placeholder="N° de rue *" maxlength="12" required />
              </div>
            </div>

            <div class="field">
              <label class="visually-hidden" for="address-details" data-i18n="addressDetails">Détails supplémentaires</label>
              <input type="text" id="address-details" name="addressDetails" data-i18n-placeholder="addressDetails" placeholder="Détails supplémentaires" autocomplete="address-line2" />
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit" id="submit-btn" disabled data-i18n="continue">CONTINUER</button>
          </div>
        </form>
