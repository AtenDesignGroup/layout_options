<?php

namespace Drupal\layout_options;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The base class for LayoutOption plugins.
 */
abstract class OptionBase extends PluginBase implements OptionInterface {
  use StringTranslationTrait;

  /**
   * Attribute types used in validation that are skipped.
   *
   * @var array
   */
  protected $noCheckTypes = ['mixed', 'plugin'];

  /**
   * Optional attributes.
   *
   * @var array
   */
  protected $optionalAttributes =
    ['weight', 'allowed_regions'];

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->getConfiguration()['definition'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionId() {
    return $this->getConfiguration()['option_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutPlugin() {
    return $this->getConfiguration()['layout_plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function addDefaults($configuration) {
    $id = $this->getOptionId();
    $def = $this->getDefinition();
    $default = isset($def['default']) ? $def['default'] : '';

    $regions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    foreach ($regions as $region) {
      if ($this->isAllowed($region)) {
        if ($region === 'layout') {
          $configuration[$id] = $default;
        }
        else {
          $configuration[$region][$id] = $default;
        }
      }
    }
    return $configuration;
  }

  /**
   * Verify that this option is allowed to be used in this region.
   *
   * @param string $region
   *   The region to test against.
   *
   * @return bool
   *   TRUE if allowed / FALSE if not.
   */
  public function isAllowed($region) {
    $def = $this->getDefinition();
    if ($region === 'layout') {
      return $def['layout'];
    }
    elseif (isset($def['allowed_regions'])) {
      return (in_array($region, $def['allowed_regions']) && $def['regions']);
    }
    else {
      return $def['regions'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOption(array $regions, array $build) {
    $optionId = $this->getOptionId();
    $layoutRegions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    $configuration = $this->getLayoutPlugin()->getConfiguration();

    // Make the top level layout area look like a region.
    if (isset($configuration[$optionId])) {
      $configuration['layout'][$optionId] = $configuration[$optionId];
    }
    foreach ($layoutRegions as $region) {
      if ($this->isAllowed($region) && !empty($configuration[$region][$optionId])) {
        $build = $this->processOptionBuild($regions, $build, $region, $configuration[$region][$optionId]);
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function addOptionFormElement(string $region, array $form, FormStateInterface $formState) {
    $optionId = $this->getOptionId();
    $config = $this->getLayoutPlugin()->getConfiguration();
    $default = NULL;
    if ($this->isAllowed($region)) {
      if ($region === 'layout') {
        $default = $config[$optionId];
      }
      else {
        $default = $config[$region][$optionId];
      }
      $form = $this->processFormOption($region, $form, $formState, $default);
    }
    return $form;
  }

  /**
   * This actually builds the option form element.
   *
   * Should handle both 'layout' and layout regions.
   *
   * Simple implementation is to call one of the utility classes and return
   * the results.
   *
   * @param string $region
   *   The region being processed.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   * @param mixed $default
   *   The default value to use.
   *
   * @return string[]
   *   The modified form array
   *
   * @see OptionBase::createTextElement
   * @see OptionBase::createSelectElement
   * @see OptionBase::createCheckboxElement
   */
  abstract public function processFormOption(string $region, array $form, FormStateInterface $formState, $default);

  /**
   * Modify the build render array for this option.
   *
   * @param string[] $regions
   *   The regions being built.
   * @param string[] $build
   *   The render array.
   * @param string $region
   *   The region being processed.
   * @param string $value
   *   The configuration value for this option.
   */
  abstract public function processOptionBuild(array $regions, array $build, string $region, $value);

  /**
   * {@inheritdoc}
   */
  public function submitFormOption(array $configuration, array $form, FormStateInterface $formState) {
    $id = $this->getOptionId();

    $regions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    foreach ($regions as $region) {
      if ($this->isAllowed($region)) {
        $value = $this->getNormalizedValues($this->getFormValue($formState, $region, $id));
        if ($region === 'layout') {
          $configuration[$id] = $value;
        }
        else {
          $configuration[$region][$id] = $value;
        }
      }
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormOption(array &$form, FormStateInterface $formState) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionDefinition(array $optionDefinition) {

    $problems = '';
    $attributes = $this->getDefinitionAttributes();
    foreach ($attributes as $key => $type) {
      if (isset($optionDefinition[$key])) {
        if (!in_array($type, $this->getNoCheckTypes())) {
          if (gettype($optionDefinition[$key]) !== $type) {
            if (!empty($problems)) {
              $problems .= ';';
            }
            $problems .= " Attribute {$key}'s value is not {$type} type";
          }
        }
      }
      elseif (!$this->isOptional($key)) {
        if (!empty($problems)) {
          $problems .= ';';
        }
        $problems .= " Missing the {$key} attribute";
      }
    }
    return empty($problems) ? NULL : $problems;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * Utility function to add a text field form element from the option def.
   *
   * YAML definition should contain the following definition settings:
   *
   *     title: 'Option title'
   *     description: 'Option description'
   *     default: 'Option default value' or ''
   *
   * @param string $region
   *   The region to create this form.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   * @param mixed $default
   *   The default value to use.
   *
   * @return array
   *   The modified form
   */
  public function createTextElement(string $region, array $form, FormStateInterface $formState, $default) {
    $def = $this->getDefinition();
    $formRenderArray = [
      '#title' => $this->t($def['title']),
      '#description' => $this->t($def['description']),
      '#type' => 'textfield',
      '#default_value' => !empty($default) ? $default : '',
    ];
    if (isset($def['weight'])) {
      $formRenderArray['#weight'] = $def['weight'];
    }
    $optionId = $this->getOptionId();
    if ($region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }

  /**
   * Creates a select field form element from the option def.
   *
   * YAML definition should contain the following settings:
   *     title: 'Option title'
   *     description: 'Option description'
   *     default: 'Option default value' or ''
   *     multi: false  (Select multiple items if true)
   *     options: {
   *       value1: 'Label 1',
   *       value2: 'Label 2,
   *       value3: 'Label 3,
   *       ...
   *     }
   *
   * @param string $region
   *   The region to create this form.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   * @param mixed $default
   *   The default value to use.
   *
   * @return array
   *   The modified form
   */
  public function createSelectElement(string $region, array $form, FormStateInterface $formState, $default) {
    $def = $this->getDefinition();
    $formRenderArray = [
      '#title' => $this->t($def['title']),
      '#description' => $this->t($def['description']),
      '#type' => 'select',
      '#options' => $this->translateOptions($def['options']),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#default_value' => $default,
    ];
    if ($def['multi']) {
      $formRenderArray['#multiple'] = TRUE;
    }
    if (isset($def['weight'])) {
      $formRenderArray['#weight'] = $def['weight'];
    }
    $optionId = $this->getOptionId();
    if ($region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }

  /**
   * Creates a checkboxes field form element from the option def.
   *
   * YAML definition should contain the following settings:
   *     title: 'Option title'
   *     description: 'Option description'
   *     default: 'Option default value' or ''
   *     inline:  true || false
   *     options: {
   *       value1: 'Label 1',
   *       value2: 'Label 2,
   *       value3: 'Label 3,
   *       ...
   *     }
   *
   * @param string $region
   *   The region to create this form.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   * @param mixed $default
   *   The default value to use.
   *
   * @return array
   *   The modified form
   */
  public function createCheckboxElement(string $region, array $form, FormStateInterface $formState, $default) {
    $def = $this->getDefinition();
    $formRenderArray = [
      '#title' => $this->t($def['title']),
      '#description' => $this->t($def['description']),
      '#type' => 'checkboxes',
      '#options' => $this->translateOptions($def['options']),
      '#default_value' => $default,
    ];
    if ($def['inline']) {
      $formRenderArray['#attributes'] = ['class' => ['container-inline']];
    }
    if (isset($def['weight'])) {
      $formRenderArray['#weight'] = $def['weight'];
    }
    $optionId = $this->getOptionId();
    if ($region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }

  /**
   * Create a checkboxes field form element from the option def.
   *
   * YAML definition should contain the following settings:
   *     title: 'Option title'
   *     description: 'Option description'
   *     default: 'Option default value' or ''
   *     inline:  true || false
   *     options: {
   *       value1: 'Label 1',
   *       value2: 'Label 2,
   *       value3: 'Label 3,
   *       ...
   *     }
   *
   * @param string $region
   *   The region to create this form.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   * @param mixed $default
   *   The default value to use.
   *
   * @return array
   *   The modified form
   */
  public function createRadiosElement(string $region, array $form, FormStateInterface $formState, $default) {
    $def = $this->getDefinition();
    $formRenderArray = [
      '#title' => $this->t($def['title']),
      '#description' => $this->t($def['description']),
      '#type' => 'radios',
      '#options' => $this->translateOptions($def['options']),
      '#default_value' => $default,
    ];
    if ($def['inline']) {
      $formRenderArray['#attributes'] = ['class' => ['container-inline']];
    }
    if (isset($def['weight'])) {
      $formRenderArray['#weight'] = $def['weight'];
    }
    $optionId = $this->getOptionId();
    if ($region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }

  /**
   * Handle adding an attribute to the build render array for this option.
   *
   * @param string $attribute
   *   The attribute to use, e.g. id, class.
   * @param array $regions
   *   The regions to be built.
   * @param array $build
   *   The build render array.
   * @param string $region
   *   The region being processed.
   * @param mixed $value
   *   The value to used for the attribute.
   *
   * @return array
   *   The modified build array.
   */
  public function processAttributeOptionBuild(string $attribute, array $regions, array $build, string $region, $value) {
    if ($region == 'layout') {
      if (!isset($build['#attributes'])) {
        $build['#attributes'] = [];
      }
      if (is_array($value)) {
        if (empty($build['#attributes'][$attribute])) {
          $build['#attributes'][$attribute] = $value;
        }
        else {
          $build['#attributes'][$attribute] = array_merge($build['#attributes'][$attribute], $value);
        }
      }
      else {
        $build['#attributes'][$attribute][] = $value;
      }
    }
    elseif (array_key_exists($region, $regions)) {
      if (!isset($build[$region]['#attributes'])) {
        $build[$region]['#attributes'] = [];
      }
      if (is_array($value)) {
        if (empty($build[$region]['#attributes'][$attribute])) {
          $build[$region]['#attributes'][$attribute] = $value;
        }
        else {
          $build[$region]['#attributes'][$attribute] = array_merge($build[$region]['#attributes'][$attribute], $value);
        }
      }
      else {
        $build[$region]['#attributes'][$attribute][] = $value;
      }
    }
    return $build;
  }

  /**
   * Create a translated version of an options array.
   *
   * @return string[]
   *   The translated options array.
   */
  public function translateOptions($options) {
    $transOptions = [];
    foreach ($options as $key => $label) {
      $transOptions[$key] = $this->t($label);
    }
    return $transOptions;
  }

  /**
   * Get the Layout plugin using this option plug's definition.
   *
   * @return \Drupal\Core\Layout\LayoutDefinition
   *   Layout definition object.
   */
  public function getLayoutDefinition() {
    return $this->getLayoutPlugin()->getPluginDefinition();
  }

  /**
   * Converts form values to valid option defaults.
   *
   * Also makes sure they are plain text.
   *
   * @param mixed $values
   *   The form values to normalize.
   *
   * @return string|string[]
   *   The normalized / sanitized values.
   */
  public function getNormalizedValues($values) {
    if (!$values) {
      return $values;
    }
    if (is_array($values)) {
      $new_values = [];
      foreach ($values as $value) {
        if ($value) {
          $new_values[] = Html::escape($value);
        }
      }
    }
    else {
      $new_values = Html::escape($values);
    }
    return $new_values;
  }

  /**
   * Utility function to validate CSS identifier(s) entered on forms.
   *
   * @param array $form
   *   The form Array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   * @param bool $multi
   *   Flag to indicate that multiple space separated values can be specified.
   */
  public function validateCssIdentifier(array $form, FormStateInterface $formState, bool $multi = FALSE) {
    $optionId = $this->getOptionId();

    $regions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    foreach ($regions as $region) {
      $value = $this->getFormValue($formState, $region, $optionId);
      if ($value && !$this->isValidCssIdentifier($value, $multi)) {
        $formState->setErrorByName($optionId, $this->t("Invalid CSS identifier."));
      }
    }
  }

  /**
   * Validate CSS identifiers in a single item or list of identifiers.
   *
   * @param mixed $value
   *   The value or array of values to check.
   * @param bool $multi
   *   If true, test if a list of space separated valid ids.  Default is false.
   *
   * @return bool
   *   True if valid id(s) or False if not.
   */
  public function isValidCssIdentifier($value, bool $multi = FALSE) {
    if ($multi) {
      $ids = preg_split("/\s+/", $value);
    }
    else {
      $ids = [trim($value)];
    }
    foreach ($ids as $id) {
      $check = Html::cleanCssIdentifier($id);
      if ($check !== $id) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the definition attributes needed by this definition type.
   *
   * @return string[]
   *   Array with key being the attribute and value the type.
   */
  public function getDefinitionAttributes() {
    return [
      'title' => 'string',
      'description' => 'string',
      'plugin' => 'plugin',
      'default' => 'mixed',
      'layout' => 'boolean',
      'regions' => 'boolean',
      'weight' => 'integer',
      'allowed_regions' => 'array',
    ];
  }

  /**
   * Handle getting config value from either a ERL form or Layout Builder form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   * @param string $region
   *   The region to use.
   * @param string $key
   *   The option id key.
   *
   * @return mixed
   *   The form value.
   */
  public function getFormValue(FormStateInterface $formState, string $region, string $key) {
    $keyArray = [];
    if ($formState->hasValue('layout_settings')) {
      $keyArray[] = 'layout_settings';
    }
    if ($region !== 'layout') {
      $keyArray[] = $region;
    }
    $keyArray[] = $key;
    return $formState->getValue($keyArray);
  }

  /**
   * Check if attribute is an optional attribute.
   *
   * @param string $attribute
   *   The attribute to check.
   *
   * @return bool
   *   True if attribute is optional, False if not.
   */
  public function isOptional(string $attribute) {
    return in_array($attribute, $this->getOptionalAttributes());
  }

  /**
   * Get the type not to check in validation.
   *
   * @return array
   *   Array of types not to check.
   */
  public function getNoCheckTypes() {
    return $this->noCheckTypes;
  }

  /**
   * Set the array of validation types that should not be checked.
   *
   * @param array $noCheckTypes
   *   Array of validation types.
   */
  public function setNoCheckTypes(array $noCheckTypes) {
    $this->noCheckTypes = $noCheckTypes;
  }

  /**
   * Get the array of optional attributes.
   *
   * @return array
   *   array of optional attributes
   */
  public function getOptionalAttributes() {
    return $this->optionalAttributes;
  }

  /**
   * Set the array of optional attributes.
   *
   * @param array $optionalAttributes
   *   Array of optional attributes.
   */
  public function setOptionalAttributes(array $optionalAttributes) {
    $this->optionalAttributes = $optionalAttributes;
  }

}
