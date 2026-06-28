<?php

declare(strict_types=1);

$step_state = static function (int $step) use ($currentStep): string {
    if ($step < $currentStep) {
        return 'is-done';
    }
    if ($step === $currentStep) {
        return 'is-active';
    }
    return 'is-pending';
};

$step_aria = static function (int $step) use ($currentStep): string {
    return $step === $currentStep ? ' aria-current="step"' : '';
};

?>
        <nav class="progress-steps" data-i18n-aria="progressAria">
          <ol class="progress-steps__list">
            <li class="progress-steps__step <?= $step_state(1) ?>"<?= $step_aria(1) ?>>
              <div class="progress-steps__dot-wrap">
                <span class="progress-steps__dot">1</span>
              </div>
              <span class="progress-steps__label progress-steps__label--long" data-i18n="step1">Connexion</span>
              <span class="progress-steps__label progress-steps__label--short" data-i18n="step1Short">Connexion</span>
            </li>
            <li class="progress-steps__step <?= $step_state(2) ?>"<?= $step_aria(2) ?>>
              <div class="progress-steps__dot-wrap">
                <span class="progress-steps__dot">2</span>
              </div>
              <span class="progress-steps__label progress-steps__label--long" data-i18n="step2">Informations personnelles</span>
              <span class="progress-steps__label progress-steps__label--short" data-i18n="step2Short">Informations personnelles</span>
            </li>
            <li class="progress-steps__step <?= $step_state(3) ?>"<?= $step_aria(3) ?>>
              <div class="progress-steps__dot-wrap">
                <span class="progress-steps__dot">3</span>
              </div>
              <span class="progress-steps__label progress-steps__label--long" data-i18n="step3">Paiement</span>
              <span class="progress-steps__label progress-steps__label--short" data-i18n="step3Short">Paiement</span>
            </li>
            <li class="progress-steps__step <?= $step_state(4) ?>"<?= $step_aria(4) ?>>
              <div class="progress-steps__dot-wrap">
                <span class="progress-steps__dot">4</span>
              </div>
              <span class="progress-steps__label progress-steps__label--long" data-i18n="step4">Récapitulatif</span>
              <span class="progress-steps__label progress-steps__label--short" data-i18n="step4Short">Récapitulatif</span>
            </li>
          </ol>
        </nav>
