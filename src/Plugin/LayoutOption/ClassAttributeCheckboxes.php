<?php
namespace Drupal\layout_options\Plugin\LayoutOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_options\OptionBase;

/**
 * Layout Option plugin to add one or more classes via a checkboxes set to layout/layout regions.
 *
 * @LayoutOption(
 *   id = "layout_options_class_checkboxes",
 *   label = @Translation("Layout Class attribute option (Checkboxes)"),
 *   description = @Translation("A layout configuration option that adds an class attributes to layout and/or regions")
 * )
 */
Class ClassAttributeCheckboxes extends OptionBase {

  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionBase::validateFormOption()
   */
  public function validateFormOption(array &$form, \Drupal\Core\Form\FormStateInterface $formState) {
    $this->validateCssIdentifier($form, $formState, TRUE);
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionBase::processFormOption()
   */
  public function processFormOption(string $region, array $form, FormStateInterface $formState, $default) {
    return $this->createCheckboxElement($region, $form, $formState, $default);
  }
  /**
   * {@inheritDoc}
   * @see \Drupal\layout_options\OptionBase::processOptionBuild()
   */
  public function processOptionBuild($regions, $build, $region, $value) {
    return $this->processAttributeOptionBuild('class', $regions, $build, $region, $value);
  }

}