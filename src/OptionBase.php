<?php

namespace Drupal\layout_options;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

abstract class OptionBase extends PluginBase implements OptionInterface {
  use StringTranslationTrait;  // TODO: Can this be injected?

  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::getLabel()
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::getDescription()
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::getDefinition()
   */
  public function getDefinition() {
    return $this->getConfiguration()['definition'];
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::getOptionId()
   */
  public function getOptionId() {
    return $this->getConfiguration()['option_id'];
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::getLayoutPlugin()
   */
  public function getLayoutPlugin() {
    return $this->getConfiguration()['layout_plugin'];
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::addDefaults()
   */
  public function addDefaults(array $configuration) {
    $id = $this->getOptionId();
    $def = $this->getDefinition();
    $default = isset($def['default']) ? $def['default'] : '';

    $regions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    foreach ($regions as $region) {
      if ($this->isAllowed($region)) {
        if ( $region === 'layout') {
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
   *
   * @return boolean
   */
  public function isAllowed( $region ) {
    $def = $this->getDefinition();
    if ($region === 'layout') {
      return $def['layout'];
    }
    else {
      return $def['regions'];
    }
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::buildOption()
   */
  function buildOption(array $regions, array $build) {
    $optionId = $this->getOptionId();
    $layoutRegions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    $configuration = $this->getLayoutPlugin()->getConfiguration();
    // Make the top level layout area look like a region.
    $configuration['layout'][$optionId] = $configuration[$optionId];
    foreach ($layoutRegions as $region) {
      if ($this->isAllowed($region)  && !empty($configuration[$region][$optionId])) {
        $build = $this->processOptionBuild($regions, $build, $region, $configuration[$region][$optionId]);
      }
    }
    return $build;
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::addOptionFormElement()
   */
  function addOptionFormElement(string $region, array $form, FormStateInterface $formState){
    $optionId = $this->getOptionId();
    $config = $this->getLayoutPlugin()->getConfiguration();
    $default = NULL;
    if ($this->isAllowed($region)) {
      if ( $region === 'layout' ) {
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
   * Should handle both 'layout' and layout regions.
   * Simple implementation is to call one of the utility classes and return
   * the results.
   *
   * @see OptionBase::createTextElement
   * @see OptionBase::createSelectElement
   * @see OptionBase::createCheckboxElement
   *
   * @param string $region
   * @param array $form
   * @param FormStateInterface $formState
   * @param mixed $default
   *
   * @return string[]
   * The modified form array
   */
  abstract function processFormOption(string $region, array $form, FormStateInterface $formState, $default);
  /**
   * Modify the build render array for this option.
   *
   * @param string[] $regions  The regions being built
   * @param string[] $build  The render array
   * @param string $region  The region being processed.
   * @param string $value  The configuration value for this option.
   */
  abstract function processOptionBuild($regions, $build, $region, $value);
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::submitFormOption()
   */
  public function submitFormOption(array $configuration, array $form, FormStateInterface $formState) {
    $id = $this->getOptionId();

    $regions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    foreach ($regions as $region) {
      if ($this->isAllowed($region)) {
        $value = $this->getNormalizedValues($this->getFormValue($formState, $region, $id));
        if ( $region === 'layout') {
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
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::validateFormOption()
   */
  public function validateFormOption(array &$form, FormStateInterface $formState) {
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionInterface::validateOptionDefinition()
   */
  public function validateOptionDefinition(array $optionDefinition) {
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\ConfigurableInterface::defaultConfiguration()
   */
  public function defaultConfiguration() {
    return [];
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\ConfigurableInterface::getConfiguration()
   */
  public function getConfiguration() {
    return $this->configuration;
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\ConfigurableInterface::setConfiguration()
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
   * @param array $form
   * @param FormStateInterface $formState
   * @param mixed $default
   *
   * @return array
   * The modified form
   */
  public function createTextElement(string $region, array $form, FormStateInterface $formState, $default) {
    $def = $this->getDefinition();
    $formRenderArray = [
      '#title' => $this->t($def['title']),
      '#description' => $this->t($def['description']),
      '#type' => 'textfield',
      '#default_value' => !empty($default) ? $default : '',
    ];
    $optionId = $this->getOptionId();
    if ( $region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }

  /**
   * Utility function to creates a select field form element from the option def.
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
   * @param array $form
   * @param FormStateInterface $formState
   * @param mixed $default
   *
   * @return array
   * The modified form
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
    $optionId = $this->getOptionId();
    if ( $region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }

  /**
   * Utility function to creates a checkboxes field form element from the option def.
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
   * @param array $form
   * @param FormStateInterface $formState
   * @param mixed $default
   *
   * @return array
   * The modified form
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
    $optionId = $this->getOptionId();
    if ( $region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }
  /**
   * Utility function to creates a checkboxes field form element from the option def.
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
   * @param array $form
   * @param FormStateInterface $formState
   * @param mixed $default
   *
   * @return array
   * The modified form
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
    $optionId = $this->getOptionId();
    if ( $region == 'layout') {
      $form[$optionId] = $formRenderArray;
    }
    else {
      $form[$region][$optionId] = $formRenderArray;
    }
    return $form;
  }

  public function processAttributeOptionBuild(string $attribute, array $regions, array $build, string $region, $value) {
    if ( $region == 'layout') {
      if ( !isset($build['#attributes'])) {
        $build['#attributes'] = [];
      }
      if (is_array($value)) {
        if ( empty($build['#attributes'][$attribute])) {
          $build['#attributes'][$attribute] = $value;
        }
        else {
          $build['#attributes'][$attribute] += $value;
        }
      }
      else {
        $build['#attributes'][$attribute][] = $value;
      }
    }
    elseif (array_key_exists($region, $regions)) {
      if ( !isset($build[$region]['#attributes'])) {
        $build[$region]['#attributes'] = [];
      }
      if (is_array($value)) {
        if ( empty($build[$region]['#attributes'][$attribute])) {
          $build[$region]['#attributes'][$attribute] = $value;
        }
        else {
          $build[$region]['#attributes'][$attribute] += $value;
        }
      }
      else {
        $build[$region]['#attributes'][$attribute][] = $value;
      }
    }
    return $build;
  }
  public function translateOptions($options) {
    $transOptions = [];
    foreach ($options as $key => $label) {
      $transOptions[$key] = $this->t($label);
    }
    return $transOptions;
  }
  public function getLayoutDefinition() {
    return $this->getLayoutPlugin()->getPluginDefinition();
  }
  /**
   * Converts form values to valid option defaults and makes sure they are plain text..
   *
   * @param mixed $values  The form values to normalize
   *
   * @return string|string[]
   * The normalized / sanitized values.
   */
  public function getNormalizedValues($values) {
    if (!$values) {
      return $values;
    }
    if ( is_array($values) ) {
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
   * @param FormStateInterface $formState
   */
  public function validateCssIdentifier(array $form, FormStateInterface $formState, bool $multi=FALSE) {
    $optionId = $this->getOptionId();

    $regions = array_merge(['layout'], $this->getLayoutDefinition()->getRegionNames());
    foreach ($regions as $region) {
      $value = $this->getFOrmValue($formState, $region, $optionId);
      if ($value && !$this->isValidCssIdentifier($value, $multi)) {
\Drupal::logger('layout_options')->debug('validateCssIdentifier called - Invalid CSS identifier found.');
        $formState->setErrorByName($optionId, $this->t("Invalid CSS identifier."));
      }
    }
  }
  /**
   * Utility function to check if the value is a valid CSS identifier or a space separated list of CSS identifiers.
   *
   * @param mixed $value The value or array of values to check
   * @param bool $multi If true, test if a list of space separated valid ids.  Default is false.
   *
   * @return boolean
   * True if valid id(s) or False if not.
   */
  public function isValidCssIdentifier($value, bool $multi=FALSE) {
    if ($multi) {
      $ids = preg_split("/\s+/", $value);
    }
    else {
      $ids = [trim($value)];
    }
    foreach ($ids as $id ) {
      $check = Html::cleanCssIdentifier($id);
      if ($check !== $id) {
        return FALSE;
      }
    }
    return TRUE;
  }
  public function getDefinitionAttributes() {
    return [
      'title' => 'string',
      'description' => 'string',
      'plugin' => 'plugin',
      'default' => 'mixed',
      'layout' => 'boolean',
      'regions' => 'boolean',
    ];
  }
  /**
   * Handle getting a config value from either a ERL form or Layout Builder form.
   *
   * @param FormStateInterface $formState
   * @param string $region
   * @param string $key
   *
   * @return mixed
   * The form value.
   */
  public function getFormValue(FormStateInterface $formState, string $region, string $key) {
    $keyArray = [];
    if ( $formState->hasValue('layout_settings')) {
      $keyArray[] = 'layout_settings';
    }
    if ($region !== 'layout') {
      $keyArray[] = $region;
    }
    $keyArray[] = $key;
    return $formState->getValue($keyArray);
  }
}