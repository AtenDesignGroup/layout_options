<?php

namespace Drupal\layout_options;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for layout option plugins.
 */
interface OptionInterface extends PluginInspectionInterface, ConfigurableInterface {

  /**
   * Return the label of the option plugin.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Return the description of the option plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Return the option definition info used to create this instance.
   *
   * @return string
   */
  public function getDefinition();
  /**
   *  Get the option id this plugin instance is defined with.
   *
   *  @return string
   *  The option id.
   */
  public function getOptionId();

  /**
   * Return the Layout Plugin using this Option.
   *
   * @return \Drupal\Core\Layout\LayoutDefault
   * The layout plugin or NULL if it has not been set.
   */
  public function getLayoutPlugin();

  /**
   * Add the default configuration settings for this option.
   *
   * @param mixed $configuration The configuration array.
   *
   * @return array
   * The modified configuration array.
   */
  public function addDefaults(array $configuration);

  /**
   * Modify the build array with this options settings.
   *
   * @param array $regions Array keyed by region containing region render arrays
   * @param array $build The layout render array.
   *
   * @return string[]
   * The modified build array.
   */
  public function buildOption(array $regions, array $build);

  /**
   * Adds the form element(s) needed to configure this option.
   *
   * @param string $region The regions being configured (layout = overall)
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return array
   * The modified form
   */
  public function addOptionFormElement(string $region, array $form, FormStateInterface $formState);

  /**
   * Validates the values from the layout config form.
   *
   * @param string $region The regions being configured (layout = overall)
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   */
  public function validateFormOption(array &$form, FormStateInterface $formState);

  /**
   * Handles form submission for this options part of the layout config form.
   *
   * @param array $configuration The layout plugin configuration.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return string[]
   * Updated configuration array.
   */
  public function submitFormOption(array $configuration, array $form, FormStateInterface $formState);

  /**
   * Validates the values from the yaml definitions for this option.
   *
   * @param array $optionDefinition
   *
   * @return NULL|string
   * NULL if options are valid.  An error string if they are not.
   */
  public function validateOptionDefinition(array $optionDefinition);
}