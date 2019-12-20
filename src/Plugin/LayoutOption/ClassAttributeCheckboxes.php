<?php

namespace Drupal\layout_options\Plugin\LayoutOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_options\OptionBase;

/**
 * Layout Option plugin to add one or more classes via a checkboxes.
 *
 * @LayoutOption(
 *   id = "layout_options_class_checkboxes",
 *   label = @Translation("Layout Class attribute option (Checkboxes)"),
 *   description = @Translation("A layout configuration option that adds an class attributes to layout and/or regions")
 * )
 */
class ClassAttributeCheckboxes extends OptionBase {

  /**
   * {@inheritdoc}
   */
  public function validateFormOption(array &$form, FormStateInterface $formState) {
    $this->validateCssIdentifier($form, $formState, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function processFormOption(string $region, array $form, FormStateInterface $formState, $default) {
    return $this->createCheckboxElement($region, $form, $formState, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function processOptionBuild(array $regions, array $build, string $region, $value) {
    return $this->processAttributeOptionBuild('class', $regions, $build, $region, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionAttributes() {
    return array_merge(parent::getDefinitionAttributes(), [
      'inline' => 'boolean',
      'options' => 'array',
    ]);
  }

}
