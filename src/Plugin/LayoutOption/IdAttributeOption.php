<?php

namespace Drupal\layout_options\Plugin\LayoutOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_options\OptionBase;

/**
 * Layout Option plugin to add an id attribute to layout/layout regions.
 *
 * @LayoutOption(
 *   id = "layout_options_id",
 *   label = @Translation("Layout Id Attribute option"),
 *   description = @Translation("A layout configuration option that adds an id attributes to layout and/or regions")
 * )
 */
class IdAttributeOption extends OptionBase {

  /**
   * {@inheritdoc}
   */
  public function processFormOption(string $region, array $form, FormStateInterface $formState, $default) {
    return $this->createTextElement($region, $form, $formState, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormOption(array &$form, FormStateInterface $formState) {
    $this->validateCssIdentifier($form, $formState, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function processOptionBuild(array $regions, array $build, string $region, $value) {
    return $this->processAttributeOptionBuild('id', $regions, $build, $region, $value);
  }

}
