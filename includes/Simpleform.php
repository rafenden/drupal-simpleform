<?php

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Implementation of Simpleform.
 */
class Simpleform {

  protected $elements = array();
  protected $label;
  protected $description;
  protected $settings = array();
  protected $confirmationMessage;

  protected $name;
  protected $form;
  protected $stepNames = array();

  public $currentStep = 0;

  /**
   * Loads a form.
   *
   * @param string $simpleform_name
   *   The name of the form to load.
   * @param bool $reset
   *   Whether to force reloading from file.
   *
   * @return \Simpleform | NULL
   *   Simpleform object or NULL if form could not be loaded.
   */
  public static function load($simpleform_name, $reset = FALSE) {
    $simpleform = NULL;
    $cache_key = "simpleform_{$simpleform_name}";
    if ($reset) {
      cache_set($cache_key, '');
    }

    if ($cache = cache_get($cache_key)) {
      $form_definition = $cache->data;
    }

    if (empty($form_definition)) {
      $forms_path = variable_get('simpleform_forms_path', __DIR__ . '/../examples/forms');
      $filepath = "$forms_path/$simpleform_name.yml";

      if (file_exists($filepath)) {
        try {
          $formFileContent = file_get_contents($filepath);
          $form_definition = self::parseFormDefinition($formFileContent);
          cache_set($cache_key, $form_definition);
        }
        catch (ParseException $e) {
          // @todo watchdog exception
        }
      }
    }

    if (!empty($form_definition)) {
      $simpleform = new Simpleform($simpleform_name, $form_definition);
    }

    return $simpleform;
  }

  public static function parseFormDefinition($formFileContent) {
    return Yaml::parse($formFileContent);
  }

  public static function formSource($source) {
    $form_definition = self::parseFormDefinition($source);
    return $simpleform = new Simpleform('simpleform_preview', $form_definition);
  }

  public function getSetting($settingName) {
    return isset($this->settings[$settingName]) ? $this->settings[$settingName] : NULL;
  }

  /**
   * Simpleform constructor.
   *
   * @param $name
   *   Form machine name.
   * @param array $formDefinition
   *   Form definition after parsing.
   */
  public function __construct($name, array $formDefinition) {
    if (isset($formDefinition['elements'])) {
      $this->drupalizeElements($formDefinition['elements']);
    }

    $this->name = $name;
    $this->label = $formDefinition['label'];
    $this->description = !empty($formDefinition['description']) ? $formDefinition['description'] : NULL;
    $this->settings = !empty($formDefinition['settings']) ? $formDefinition['settings'] : NULL;

    if (isset($formDefinition['confirmation_message'])) {
      $this->confirmationMessage = $formDefinition['confirmation_message'];
    }

    $this->elements = $formDefinition['elements'];

    $this->revisitSteps();

    $this->setCurrentStep(0);
  }

  /**
   * After removing or adding steps this method needs to be executed.
   */
  public function revisitSteps() {
    $this->stepNames = array();
    foreach (element_children($this->elements) as $elementKey) {
      $stepElement = $this->elements[$elementKey];
      $elementIsVisible = !isset($stepElement['#access']) || $stepElement['#access'];
      if ($stepElement['#type'] == 'step' && $elementIsVisible) {
        $this->stepNames[] = $elementKey;
      }
    }
  }

  /**
   * Hide step with goven name.
   *
   * @param $stepName
   */
  public function hideStep($stepName) {
    $this->elements[$stepName]['#access'] = FALSE;
    $this->revisitSteps();
  }

  /**
   * Hide step with goven name.
   *
   * @param $stepName
   */
  public function showStep($stepName) {
    $this->elements[$stepName]['#access'] = TRUE;
    $this->revisitSteps();
  }

  /**
   * Get form elements.
   *
   * @return array
   */
  public function getElements() {
    return $this->elements;
  }

  /**
   * Get label of the current step.
   *
   * @param string|null $stepName
   *   Step name, if empty the current step will be used.
   *
   * @return string
   */
  public function getStepLabel($stepName = NULL) {
    if (!$stepName) {
      $stepName = $this->getCurrentStepName();
    }
    return $this->getElements()[$stepName]['#title'];
  }

  /**
   * Get form elements on the givent step.
   *
   * @param string $stepName
   *   Step name, if empty the current step will be used.
   *
   * @return array
   */
  public function getElementsOnStep($stepName = NULL) {
    $stepName = $this->getStepName($stepName);
    if (!$stepName) {
      //@todo watchdog this
      return array();
    }
    $elements = $this->getElements();
    if (!is_array($elements)) {
      //@todo watchdog this
      return array();
    }
    // Return all steps but the current.
    foreach (element_children($elements) as $elementKey) {
      if ($elements[$elementKey]['#type'] == 'step' && $elementKey != $stepName) {
        $elements[$elementKey]['#access'] = FALSE;
      }
      else {
        $elements[$elementKey]['#access'] = TRUE;
      }
    }
    return $elements;
  }

  /**
   * Returns form confirmation URL.
   *
   * @return string
   */
  public function getConfirmationURL() {
    return 'form/' . $this->getName() . '/confirmation';
  }

  /**
   * Returns title of the confirmation page.
   *
   * @return string
   */
  public function getConfirmationPageTitle() {
    return t('Confirmation');
  }

  /**
   * Get form's label.
   *
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Get form's description.
   *
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Get form's name.
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get all step names.
   *
   * @return array
   */
  public function getStepNames() {
    return $this->stepNames;
  }

  /**
   * Get name of the current step.
   *
   * @return string
   */
  public function getCurrentStepName() {
    return $this->getStepName();
  }

  /**
   * Get name of step specified by $step.
   *
   * @param int|NULL $stepIndex
   *   Step index to return name for, if null use current step.
   *
   * @return string | NULL
   *   Step name or NULL if we could not find step name.
   */
  public function getStepName($stepIndex = NULL) {
    if ($stepIndex === NULL) {
      $stepIndex = $this->getCurrentStep();
    }

    $stepName = NULL;
    if (isset($this->stepNames[$stepIndex])) {
      $stepName = $this->stepNames[$stepIndex];
    }

    return $stepName;
  }

  /**
   * Get index of the current step.
   *
   * @return int
   */
  public function getCurrentStep() {
    return $this->currentStep;
  }

  /**
   * Set index of the current step.
   *
   * @param int $step
   */
  protected function setCurrentStep($step) {
    $this->currentStep = $step;
  }

  /**
   * Go to the next step.
   */
  public function goToNextStep() {
    $this->setCurrentStep($this->getCurrentStep() + 1);
  }

  /**
   * Go to the previous step.
   */
  public function goToPrevStep() {
    $this->setCurrentStep($this->getCurrentStep() - 1);
  }

  /**
   * Check if the current step is first.
   *
   * @return bool
   */
  public function isFirstStep() {
    return $this->getCurrentStep() == 0;
  }

  /**
   * Check if the current step is the last one.
   *
   * @return bool
   */
  public function isLastStep() {
    return $this->getCurrentStep() == count($this->getStepNames()) - 1;
  }

  /**
   * Get actions as From API elements used to navigate between form steps.
   */
  public function getActions() {
    $formActions = array();
    if (!$this->isFirstStep()) {
      $formActions['back'] = array(
        '#type' => 'submit',
        '#value' => t('Back'),
        '#name' => 'back',
        '#limit_validation_errors' => array(),
        '#ajax' => array(
          'callback' => 'simpleform_ajax_callback_item',
          'wrapper' => 'simpleform-wrapper',
          'effect' => 'none',
        ),
        '#submit' => array('simpleform_ajax_button_submit'),
        '#access' => !$this->isFirstStep(),
        '#attributes' => array('class' => array('btn-back')),
      );
    }

    if ($this->isLastStep()) {
      $formActions['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
        '#access' => $this->isLastStep(),
        '#submit' => array('simpleform_form_submit'),
        '#attributes' => array('class' => array('btn-submit')),
      );
    }
    else {
      $formActions['next'] = array(
        '#type' => 'submit',
        '#value' => t('Next'),
        '#name' => 'next',
        '#ajax' => array(
          'callback' => 'simpleform_ajax_callback_item',
          'wrapper' => 'simpleform-wrapper',
          'effect' => 'none',
        ),
        '#submit' => array('simpleform_ajax_button_submit'),
        '#access' => !$this->isLastStep(),
        '#attributes' => array('class' => array('btn-next')),
      );
    }

    return $formActions;
  }

  /**
   * Convert dot character to a pound in element properties.
   *
   * @param $element
   */
  public static function drupalizeElements(&$element) {
    foreach (element_children($element) as $elementKey) {
      $el = &$element[$elementKey];
      if ($elementKey[0] == '.') {
        $drupalizedElementKey = $elementKey;
        $drupalizedElementKey[0] = '#';
        $element = self::arrayRenameKey($element, $elementKey, $drupalizedElementKey);
      }
      if (is_array($el)) {
        self::drupalizeElements($el);
      }
    }
  }

  /**
   * Helper function to rename array key and preserve the order.
   *
   * @param $array
   * @param $oldKey
   * @param $newKey
   *
   * @return array
   */
  public static function arrayRenameKey($array, $oldKey, $newKey) {
    $keys = array_keys($array);
    $index = array_search($oldKey, $keys);

    if ($index !== false) {
      $keys[$index] = $newKey;
      $array = array_combine($keys, $array);
    }

    return $array;
  }

}
