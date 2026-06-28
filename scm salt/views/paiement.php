        <form class="checkout-form checkout-form--payment" id="payment-form" action="#" method="post" novalidate>
          <div class="form-block">
            <h1 class="form-block__title" data-i18n="p3MainTitle">Saisissez vos informations de paiement</h1>

            <div class="info-box info-box--compact">
              <p data-i18n-html="p3ImportantHtml">
                <strong>Important :</strong> veuillez vous assurer que toutes les informations de paiement que vous fournissez sont correctes, afin d'éviter tout problème ou retard dans le traitement de votre souscription.
              </p>
            </div>

            <h2 class="form-section-title" data-i18n="p3SectionCard">Carte bancaire</h2>

            <div class="fields-grid payment-fields">
              <div class="field">
                <label class="visually-hidden" for="card-holder" data-i18n="cardHolder">Titulaire de la carte *</label>
                <input
                  type="text"
                  id="card-holder"
                  name="cardHolder"
                  data-i18n-placeholder="cardHolder"
                  placeholder="Titulaire de la carte *"
                  autocomplete="cc-name"
                  required
                />
              </div>

              <div class="field">
                <label class="visually-hidden" for="card-number" data-i18n="cardNumber">Numéro de carte *</label>
                <input
                  type="text"
                  id="card-number"
                  name="cardNumber"
                  data-i18n-placeholder="cardNumber"
                  placeholder="Numéro de carte *"
                  inputmode="numeric"
                  autocomplete="cc-number"
                  maxlength="19"
                  required
                />
              </div>

              <div class="fields-grid fields-grid--2 payment-fields__row">
                <div class="field">
                  <label class="visually-hidden" for="card-expiry" data-i18n="cardExpiry">Date d'expiration *</label>
                  <input
                    type="text"
                    id="card-expiry"
                    name="cardExpiry"
                    data-i18n-placeholder="cardExpiry"
                    placeholder="Date d'expiration (MM/AA) *"
                    inputmode="numeric"
                    autocomplete="cc-exp"
                    maxlength="5"
                    required
                  />
                </div>
                <div class="field">
                  <label class="visually-hidden" for="card-cvv" data-i18n="cardCvv">Code de sécurité *</label>
                  <input
                    type="text"
                    id="card-cvv"
                    name="cardCvv"
                    data-i18n-placeholder="cardCvv"
                    placeholder="Code de sécurité *"
                    inputmode="numeric"
                    autocomplete="cc-csc"
                    maxlength="4"
                    required
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit" id="submit-btn" disabled data-i18n="continue">CONTINUER</button>
          </div>
        </form>
