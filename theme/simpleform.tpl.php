<?php
/**
 * @var Simpleform $simpleform
 * @var array $form
 */
?>

<!-- Form progress. -->
<?php if ($simpleform->getStepNames()): ?>
  <div class="simpleform-progress">
    <h3><?php print t('Step @current_step of @total_steps: @current_step_label', array(
        '@current_step' => $simpleform->getCurrentStep() + 1,
        '@total_steps' => count($simpleform->getStepNames()),
        '@current_step_label' => $simpleform->getStepLabel(),
      ));
      ?></h3>
    <ol class="simpleform-steps">
      <?php foreach ($simpleform->getStepNames() as $step_name): ?>
        <?php $step_class = ($step_name == $simpleform->getCurrentStepName())
          ? 'simpleform-step simpleform-step-current'
          : 'simpleform-step'; ?>
        <li class="<?php print $step_class; ?>"><?php print $simpleform->getStepLabel($step_name); ?></li>
      <?php endforeach; ?>
    </ol>
  </div>
<?php endif; ?>

<!-- Form introduction. -->
<?php if ($simpleform->isFirstStep()): ?>
  <div class="simpleform-introduction">
    <?php if ($simpleform->getDescription()): ?>
      <div class="simpleform-description"><?php print $simpleform->getDescription(); ?></div>
    <?php endif; ?>

    <?php if (!empty($simpleform->getSetting('requirements'))): ?>
      <div class="simpleform-requirements">
        <h3><?php print t('What you will need'); ?></h3>
        <ul class="simpleform-requirements-list">
          <?php foreach ($simpleform->getSetting('requirements') as $requirement): ?>
            <li class="simpleform-requirement"><?php print $requirement; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($simpleform->getSetting('eta'))): ?>
      <div class="simpleform-eta">
        <?php print t('It takes about %eta to fill in this form.', array('%eta' => $simpleform->getSetting('eta'))); ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="simpleform-messages">
  <?php print theme('status_messages'); ?>
</div>

<div class="simpleform-form-container">
  <?php print drupal_render_children($form) ?>
</div>
