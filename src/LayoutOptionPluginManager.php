<?php

namespace Drupal\layout_options;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Layout Options plugin manager.
 */
class LayoutOptionPluginManager extends DefaultPluginManager {

  /**
   * Constructs a LayoutOptionPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    // Define the location under the src directory for Layout Option plugins
    // to be defined.
    $pluginSubdir = 'Plugin/LayoutOption';

    // Interface plugins must implement.
    $pluginInterface = 'Drupal\layout_options\OptionInterface';

    // Annotation used to define plugins.
    $pluginAnnotation = 'Drupal\layout_options\Annotation\LayoutOption';

    parent::__construct(
      $pluginSubdir,
      $namespaces,
      $module_handler,
      $pluginInterface,
      $pluginAnnotation
    );

    $this->alterInfo('layout_option_info');
    $this->setCacheBackend($cache_backend, 'layout_options_plugins', []);
  }

}
