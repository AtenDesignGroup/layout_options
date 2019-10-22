<?php
namespace Drupal\layout_options\Plugin\LayoutOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_options\OptionBase;

/**
 * Layout Option plugin to add one or more classes via select set to layout/layout regions.
 *
 * @LayoutOption(
 *   id = "layout_options_class_select",
 *   label = @Translation("Layout Class attribute option (Select)"),
 *   description = @Translation("A layout configuration option that adds an class attributes to layout and/or regions")
 * )
 */
Class ClassAttributeSelect extends OptionBase {

  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionBase::validateFormOption()
   */
  public function validateFormOption(array &$form, \Drupal\Core\Form\FormStateInterface $formState) {
    $def = $this->getDefinition();
    $this->validateCssIdentifier($form, $formState, $def['multi']);
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionBase::processFormOption()
   */
  public function processFormOption(string $region, array $form, FormStateInterface $formState, $default) {
    return $this->createSelectElement($region, $form, $formState, $default);
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionBase::processOptionBuild()
   */
  public function processOptionBuild($regions, $build, $region, $value) {
    return $this->processAttributeOptionBuild('class', $regions, $build, $region, $value);
  }
}