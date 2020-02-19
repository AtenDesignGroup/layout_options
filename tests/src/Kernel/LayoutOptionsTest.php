<?php

namespace Drupal\Tests\layout_options\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_options\Plugin\Layout\LayoutOptions;

/**
 * Tests LayouOptions layout plugin functionality.
 *
 * @coversDefaultClass \Drupal\layout_options\Plugin\Layout\LayoutOptions
 *
 * @group layout_options
 */
class LayoutOptionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system', 'layout_discovery', 'layout_options',
    'layout_test', 'layout_options_test',
  ];

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * A default test schema.
   *
   * @var array
   */
  protected $testSchema = [
    'layout_option_definitions' => [
      'layout_option_id' => [
        'title' => 'Title 1',
        'description' => 'Descripton 1',
        'default' => '',
        'plugin' => 'layout_options_id',
        'layout' => TRUE,
        'regions' => TRUE,
        'weight' => -100,
      ],
      'layout_options_class_select' => [
        'title' => 'Title 2',
        'description' => 'Descripton 2',
        'default' => '',
        'plugin' => 'layout_options_class_select',
        'multi' => FALSE,
        'options' => [
          'option1' => 'Option 1',
          'option2' => 'Option 2',
          'option3' => 'Option 3',
        ],
        'layout' => TRUE,
        'regions' => TRUE,
        'weight' => -100,
      ],
      'layout_options_class_checkboxes' => [
        'title' => 'Title 3',
        'description' => 'Descripton 3',
        'default' => '',
        'plugin' => 'layout_options_class_checkboxes',
        'inline' => TRUE,
        'options' => [
          'option1' => 'Option 1',
          'option2' => 'Option 2',
          'option3' => 'Option 3',
        ],
        'layout' => TRUE,
        'regions' => TRUE,
        'weight' => -100,
      ],
      'layout_options_class_radios' => [
        'title' => 'Title 4',
        'description' => 'Descripton 4',
        'default' => '',
        'plugin' => 'layout_options_class_radios',
        'inline' => TRUE,
        'options' => [
          'option1' => 'Option 1',
          'option2' => 'Option 2',
          'option3' => 'Option 3',
        ],
        'layout' => TRUE,
        'regions' => TRUE,
        'weight' => -100,
      ],
      'layout_options_class_string' => [
        'title' => 'Title 5',
        'description' => 'Descripton 5',
        'default' => '',
        'plugin' => 'layout_options_class_string',
        'layout' => TRUE,
        'regions' => TRUE,
        'weight' => -100,
      ],
    ],
    'layout_options' => [
      'global' => [
        'layout_option_id' => [],
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->layoutPluginManager = $this->container->get('plugin.manager.core.layout');

    $this->container->get('theme_installer')->install(['test_layout_options_theme']);
    $this->config('system.theme')->set('default', 'test_layout_options_theme')->save();

    if ($this->getName() == 'testGetLayoutOptionsSchemaBadYamlFile') {
      $this->container->get('module_installer')->install(['layout_options_test_bad_yaml']);
    }
  }

  /**
   * Create a layoutOptionPlugin object to use in testing.
   *
   * @param string $layoutId
   *   The layout id to use.
   * @param array $configuration
   *   The configuration used by the plugin.
   * @param \Drupal\Core\Layout\LayoutDefinition $definition
   *   The layout Definition.
   *
   * @return \Drupal\layout_options\Plugin\Layout\LayoutOptions
   *   A new initialized LayoutOptions object.
   */
  protected function getLayoutOptionsPlugin(string $layoutId = "test_50_50", array $configuration = [], LayoutDefinition $definition = NULL) {
    if ($definition === NULL) {
      $definition = new LayoutDefinition([
        'theme_hook' => 'layout',
        'library' => 'core/drupal',
        'regions' => [
          'left' => [
            'label' => 'Left',
          ],
          'right' => [
            'label' => 'Right',
          ],
        ],
      ]);
    }
    $definition->set('id', $layoutId);
    return new LayoutOptions(
      $configuration,
      '',
      $definition,
      $this->container->get('module_handler'),
      $this->container->get('theme_handler'),
      $this->container->get('plugin.manager.layout_options')
    );
  }

  /**
   * @covers ::getYamlDiscovery
   */
  public function testYamlDiscovery() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $results = $layoutPlugin->getYamlDiscovery()->findAll();
    $this->assertArrayHasKey('test_layout_options_theme', $results, "Found theme options");
    $this->assertArrayHasKey('layout_options_test', $results, "Found module options");
    $this->assertArrayHasKey('layout_option_definitions', $results['test_layout_options_theme'], "Theme options have definitions");
    $this->assertArrayHasKey('layout_option_definitions', $results['layout_options_test'], "Module options have definitions");
  }

  /**
   * @covers ::getLayoutOptionsSchema
   *
   * Note: Uses test specific setup
   */
  public function testGetLayoutOptionsSchemaBadYamlFile() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $results = $layoutPlugin->getLayoutOptionsSchema();
    $this->assertEmpty($results, "Check if schema is empty");
    $errors = $layoutPlugin->messenger()->messagesByType($layoutPlugin->messenger()::TYPE_ERROR);
    $this->assertNotEmpty($errors, "Checking that error messages created");
  }

  /**
   * @covers ::getLayoutDefinitions
   */
  public function testGetLayoutDefinitions() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $results = $layoutPlugin->getLayoutDefinitions();
    $this->assertArrayHasKey('layout_id_theme', $results, "Has theme defined option");
    $this->assertArrayHasKey('layout_id', $results, "Has module defined option");
    $this->assertArrayHasKey('layout_bg_color', $results, "Has layout bg color option");
    $this->assertEqual($results['layout_bg_color']['title'], 'Theme Background color', "Theme override worked");
  }

  /**
   * @covers ::getLayoutOptions
   */
  public function testGetLayoutOptions() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $results = $layoutPlugin->getLayoutOptions();
    $this->assertArrayHasKey('global', $results, "Missing global section");
    $this->assertArrayHasKey('my_layout_2col_50_50', $results, "Missing module defined layout rules");
    $this->assertArrayHasKey('regions', $results['global']['layout_id'], "Missing theme override");
  }

  /**
   * @covers ::parseLayoutOptions
   */
  public function testParseLayoutOptions() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $results = $layoutPlugin->parseLayoutOptions('my_layout_2col_50_50');
    $keys = array_keys($results);
    sort($keys);
    $expected = [
      'layout_only',
      'regions_only',
      'layout_class_checkboxes',
      'layout_id_theme',
      'layout_design_classes',
      'layout_bg_color',
      'layout_id',
      'left_only',
    ];
    sort($expected);
    $this->assertSame($expected, $keys, "Did not find all layout options.");
    $this->assertFalse($results['layout_bg_color']['regions'], "Module layout override didn't work.");
    $this->assertFalse($results['layout_id']['layout'], "Theme global override didn't work.");

    $results = $layoutPlugin->parseLayoutOptions('layout_only_in_regions');
    $this->assertTrue($results['layout_only']['regions'], "Test if regions can be toggled.");
  }

  /**
   * @covers ::getOptionPlugin
   */
  public function testGetOptionPlugin() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $optionId = 'layout_id';
    $optionDefinition = [
      'title' => 'Id attribute',
      'description' => 'The CSS identifier to use on this layout item.',
      'default' => '',
      'plugin' => 'layout_options_id',
      'layout' => TRUE,
      'regions' => TRUE,
      'weight' => -100,
    ];
    $results = $layoutPlugin->getOptionPlugin($optionId, $optionDefinition);
    $this->assertNotNull($results, "Did not find layout option plugin");
    $this->assertEquals($optionDefinition, $results->getDefinition(), "Plugin definition match test");

    // Test that plug definitions changed when using cached version.
    $optionDefinitionModified = $optionDefinition;
    $optionDefinitionModified['layout'] = FALSE;
    $results = $layoutPlugin->getOptionPlugin($optionId, $optionDefinitionModified);
    $this->assertNotNull($results, "Did not find layout option plugin");
    $this->assertEquals($optionDefinitionModified, $results->getDefinition(), "Plugin definitin match test");

    $optionDefinitionBadPlugin = $optionDefinition;
    $optionDefinitionBadPlugin['plugin'] = "bad_plugin_id";
    $results = $layoutPlugin->getOptionPlugin($optionId, $optionDefinitionBadPlugin);
    $this->assertNotNull($results, "Not using cached plugin");

    $results = $layoutPlugin->getOptionPlugin($optionId, $optionDefinitionBadPlugin, TRUE);
    $this->assertNull($results, "Cache not bypassed");

    // Reload good plugin.
    $results = $layoutPlugin->getOptionPlugin($optionId, $optionDefinition, TRUE);

    $layoutPlugin->clearPluginCache();
    $results = $layoutPlugin->getOptionPlugin($optionId, $optionDefinitionBadPlugin);
    $this->assertNull($results, "Did catch invalid plugin.");
  }

  /**
   * @covers ::validateDefinitions
   */
  public function testValidateDefinitions() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $testData = $this->testSchema;

    $results = $layoutPlugin->validateDefinitions($testData);
    $this->assertTrue($results, "Valid Schema is not valid");

    $testDataBadPlugin = $testData;
    $testDataBadPlugin['layout_option_definitions']['layout_option_id']['plugin'] = 'xxx_layout_options_id';
    $layoutPlugin->clearPluginCache();
    $results = $layoutPlugin->validateDefinitions($testDataBadPlugin);
    $this->assertFalse($results, "Bad plugin not caught.");

    $testDataBadType = $testData;
    $testDataBadType['layout_option_definitions']['layout_option_id']['weight'] = 'xxx';
    $results = $layoutPlugin->validateDefinitions($testDataBadType);
    $this->assertFalse($results, "Bad type not caught.");
  }

  /**
   * @covers ::processConfigurationForm
   */
  public function testProcessConfigurationForm() {
    $form = [];
    $formState = new FormState();
    $formState->addBuildInfo('callback_object', new \stdClass());
    $layoutPlugin = $this->getLayoutOptionsPlugin();

    $results = $layoutPlugin->processConfigurationForm($form, $formState);
    $expectedTopKeys = [
      'layout_only', 'layout_class_checkboxes', 'layout_id_theme',
      'layout_bg_color', 'left', 'right',
    ];
    sort($expectedTopKeys);
    $test = array_keys($results);
    sort($test);
    $this->assertEquals($expectedTopKeys, $test, "Expected top keys not found.");

    $expectedLeftKeys = [
      '#type', '#title', 'regions_only', 'layout_class_checkboxes',
      'layout_id_theme', 'layout_bg_color', 'left_only',
    ];
    sort($expectedLeftKeys);
    $test = array_keys($results['left']);
    sort($test);
    $this->assertEquals($expectedLeftKeys, $test, "Expected left region keys not found.");

    // Test the left only key does not appear in right region.
    $test = array_keys($results['right']);
    sort($test);
    $this->assertArrayNotHasKey('left_only', $test, "Left only key found in right region.");

    $expectedLeftIdKeys = [
      '#title', '#description', '#type', '#default_value', '#weight',
    ];
    sort($expectedLeftIdKeys);
    $test = array_keys($results['left']['layout_id_theme']);
    sort($test);
    $this->assertEquals($expectedLeftIdKeys, $test, "Expected left region layout_id_theme form fields not found.");

    // Check that weight not set.
    $expectedLeftRegionOnlyKeys = [
      '#title', '#description', '#type', '#default_value',
    ];
    sort($expectedLeftRegionOnlyKeys);
    $test = array_keys($results['left']['regions_only']);
    sort($test);
    $this->assertEquals($expectedLeftRegionOnlyKeys, $test, "Expected left region region_only form fields not found.");
  }

  /**
   * Test that rules can override definition settings.
   *
   * @covers ::processConfigurationForm
   */
  public function testProcessConfigurationFormAltLayout() {
    $form = [];
    $formState = new FormState();
    $formState->addBuildInfo('callback_object', new \stdClass());
    $layoutPlugin = $this->getLayoutOptionsPlugin('layout_only_in_regions');

    $results = $layoutPlugin->processConfigurationForm($form, $formState);
    $expectedTopKeys = [
      'layout_only', 'layout_class_checkboxes', 'layout_id_theme',
      'layout_bg_color', 'left', 'right',
    ];
    sort($expectedTopKeys);
    $test = array_keys($results);
    sort($test);
    $this->assertEquals($expectedTopKeys, $test, "Expected top keys not found.");

    // Test that layout_only option shows up in region.
    $expectedLeftKeys = [
      '#type', '#title', 'layout_only', 'regions_only', 'layout_class_checkboxes',
      'layout_id_theme', 'layout_bg_color', 'left_only',
    ];
    sort($expectedLeftKeys);
    $test = array_keys($results['left']);
    sort($test);
    $this->assertEquals($expectedLeftKeys, $test, "Expected left region keys not found.");

    $expectedLeftIdKeys = [
      '#title', '#description', '#type', '#default_value', '#weight',
    ];
    sort($expectedLeftIdKeys);
    $test = array_keys($results['left']['layout_id_theme']);
    sort($test);
    $this->assertEquals($expectedLeftIdKeys, $test, "Expected left region layout_id_theme form fields not found.");

    // Check that weight not set.
    $expectedLeftRegionOnlyKeys = [
      '#title', '#description', '#type', '#default_value',
    ];
    sort($expectedLeftRegionOnlyKeys);
    $test = array_keys($results['left']['layout_only']);
    sort($test);
    $this->assertEquals($expectedLeftRegionOnlyKeys, $test, "Expected left region layout_only form fields not found.");
  }

  /**
   * Test that region configuration field does not show up if empty.
   *
   * @covers ::processConfigurationForm
   */
  public function testProcessConfigurationFormEmptyRegion() {
    $form = [];
    $formState = new FormState();
    $formState->addBuildInfo('callback_object', new \stdClass());
    $layoutPlugin = $this->getLayoutOptionsPlugin('empty_regions');

    $results = $layoutPlugin->processConfigurationForm($form, $formState);
    $expectedTopKeys = [
      'layout_only', 'layout_class_checkboxes', 'layout_id_theme',
      'layout_bg_color',
    ];
    sort($expectedTopKeys);
    $test = array_keys($results);
    sort($test);
    $this->assertEquals($expectedTopKeys, $test, "Expected top keys not found.");
  }

  /**
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationForm() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $layoutPlugin->setConfiguration($layoutPlugin->defaultConfiguration());
    $form = [];
    $formState = new FormState();
    $values = [
      'layout_id_theme' => "test-id",
      'layout_bg_color' => 'bg-info',
      'left' => [
        'layout_id_theme' => 'test-left-id',
        'layout_bg_color' => 'bg-success',
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
        'layout_bg_color' => 'bg-warning',
      ],
    ];
    $formState->setValues($values);
    $layoutPlugin->submitConfigurationForm($form, $formState);

    $results = $layoutPlugin->getConfiguration();
    $expectedConfig = [
      'layout_id_theme' => 'test-id',
      'left' => [
        'layout_id_theme' => 'test-left-id',
        'layout_bg_color' => 'bg-success',
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
        'left_only' => NULL,
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
        'layout_bg_color' => 'bg-warning',
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
      ],
      'layout_bg_color' => 'bg-info',
      'layout_only' => NULL,
      'layout_class_checkboxes' => NULL,
    ];
    // Validate that parent methods were called post Ver 8.7)
    if ($this->getDrupalMajorMinor() > 8.7) {
      $expectedConfig['label'] = NULL;
    }

    $this->assertEquals($expectedConfig, $results);
  }

  /**
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationFormAltLayout() {
    $layoutPlugin = $this->getLayoutOptionsPlugin('layout_only_in_regions');
    $layoutPlugin->setConfiguration($layoutPlugin->defaultConfiguration());
    $form = [];
    $formState = new FormState();
    $values = [
      'layout_id_theme' => "test-id",
      'layout_bg_color' => 'bg-info',
      'left' => [
        'layout_id_theme' => 'test-left-id',
        'layout_bg_color' => 'bg-success',
        'layout_only' => 'layout-only-left-class',
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
        'layout_bg_color' => 'bg-warning',
      ],
    ];
    $formState->setValues($values);
    $layoutPlugin->submitConfigurationForm($form, $formState);

    $results = $layoutPlugin->getConfiguration();
    $expectedConfig = [
      'layout_id_theme' => 'test-id',
      'left' => [
        'layout_id_theme' => 'test-left-id',
        'layout_bg_color' => 'bg-success',
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
        'layout_only' => 'layout-only-left-class',
        'left_only' => NULL,
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
        'layout_bg_color' => 'bg-warning',
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
        'layout_only' => NULL,
      ],
      'layout_bg_color' => 'bg-info',
      'layout_only' => NULL,
      'layout_class_checkboxes' => NULL,
    ];
    // Validate that parent methods were called post Ver 8.7)
    if ($this->getDrupalMajorMinor() > 8.7) {
      $expectedConfig['label'] = NULL;
    }

    $this->assertEquals($expectedConfig, $results);
  }

  /**
   * @covers ::build
   */
  public function testBuild() {
    $layoutPlugin = $this->getLayoutOptionsPlugin();
    $layoutPlugin->setConfiguration($layoutPlugin->defaultConfiguration());

    $form = [];
    $formState = new FormState();
    $values = [
      'layout_id_theme' => "test-id",
      'layout_bg_color' => 'bg-info',
      'layout_class_checkboxes' => ['checkbox1', 'checkbox2'],
      'left' => [
        'layout_id_theme' => 'test-left-id',
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
      ],
    ];
    $formState->setValues($values);
    $layoutPlugin->submitConfigurationForm($form, $formState);

    $regions = [
      'left' => [
        '#markup' => "<p>Left</p>",
        '#view_mode' => 'default',
      ],
      'right' => [
        '#markup' => "<p>Right</p>",
        '#view_mode' => 'default',
      ],
    ];

    $results = $layoutPlugin->build($regions);
    $expectedSettings = [
      'layout_id_theme' => 'test-id',
      'left' => [
        'layout_id_theme' => 'test-left-id',
        'layout_bg_color' => NULL,
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
        'left_only' => NULL,
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
        'layout_bg_color' => NULL,
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
      ],
      'layout_bg_color' => 'bg-info',
      'layout_only' => NULL,
      'layout_class_checkboxes' => ['checkbox1', 'checkbox2'],
    ];
    // Validate that parent methods were called post Ver 8.7)
    if ($this->getDrupalMajorMinor() > 8.7) {
      $expectedSettings['label'] = NULL;
    }
    $this->assertEquals($expectedSettings, $results['#settings'], "Setting did not match");

    $expectedTopAttributes = [
      'id' => ['test-id'],
      'class' => ['checkbox1', 'checkbox2', 'bg-info'],
    ];
    $this->assertEquals($expectedTopAttributes, $results['#attributes']);

    $expectedLeftAttributes = [
      'id' => ['test-left-id'],
    ];
    $this->assertEquals($expectedLeftAttributes, $results['left']['#attributes']);

    $expectedRightAttributes = [
      'id' => ['test-right-id'],
    ];
    $this->assertEquals($expectedRightAttributes, $results['right']['#attributes']);
  }

  /**
   * @covers ::build
   */
  public function testBuildAltLayout() {
    $layoutPlugin = $this->getLayoutOptionsPlugin('layout_only_in_regions');
    $layoutPlugin->setConfiguration($layoutPlugin->defaultConfiguration());

    $form = [];
    $formState = new FormState();
    $values = [
      'layout_only' => 'layout-only-class',
      'layout_bg_color' => 'bg-info',
      'layout_class_checkboxes' => ['checkbox1', 'checkbox2'],
      'left' => [
        'layout_only' => 'layout-only-left-class',
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
      ],
    ];
    $formState->setValues($values);
    $layoutPlugin->submitConfigurationForm($form, $formState);

    $regions = [
      'left' => [
        '#markup' => "<p>Left</p>",
        '#view_mode' => 'default',
      ],
      'right' => [
        '#markup' => "<p>Right</p>",
        '#view_mode' => 'default',
      ],
    ];

    $results = $layoutPlugin->build($regions);
    $expectedSettings = [
      'layout_only' => 'layout-only-class',
      'left' => [
        'layout_id_theme' => NULL,
        'layout_bg_color' => NULL,
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
        'layout_only' => 'layout-only-left-class',
        'left_only' => NULL,
      ],
      'right' => [
        'layout_id_theme' => 'test-right-id',
        'layout_bg_color' => NULL,
        'regions_only' => NULL,
        'layout_class_checkboxes' => NULL,
        'layout_only' => NULL,
      ],
      'layout_bg_color' => 'bg-info',
      'layout_id_theme' => NULL,
      'layout_class_checkboxes' => ['checkbox1', 'checkbox2'],
    ];
    // Validate that parent methods were called post Ver 8.7)
    if ($this->getDrupalMajorMinor() > 8.7) {
      $expectedSettings['label'] = NULL;
    }
    $this->assertEquals($expectedSettings, $results['#settings'], "Setting did not match");

    $expectedTopAttributes = [
      'class' => ['layout-only-class', 'checkbox1', 'checkbox2', 'bg-info'],
    ];
    $this->assertEquals($expectedTopAttributes, $results['#attributes']);

    $expectedLeftAttributes = [
      'class' => ['layout-only-left-class'],
    ];
    $this->assertEquals($expectedLeftAttributes, $results['left']['#attributes']);

    $expectedRightAttributes = [
      'id' => ['test-right-id'],
    ];
    $this->assertEquals($expectedRightAttributes, $results['right']['#attributes']);
  }

  /**
   * Returns the Drupal major and minor version as a float (e.g. 8.8, 8.7, etc).
   *
   * @return float
   *   The Major.Minor parts of the Drupal Version.
   */
  public function getDrupalMajorMinor() {
    $parts = explode('.', \Drupal::VERSION);
    return $parts[0] * 1.0 + $parts[1] * 0.1;
  }

}
