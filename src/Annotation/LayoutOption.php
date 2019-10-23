<?php

namespace Drupal\layout_options\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a option item annotation object.
 *
 * @Annotation
 */
class LayoutOption extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the option type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the option.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
