<?php
namespace codename\core\test;

use codename\core\app;

/**
 * Base unit test class for using a core environment
 * @package codename\core
 * @since 2021-03-17
 */
abstract class base extends \PHPUnit\Framework\TestCase {

  /**
   * @inheritDoc
   */
  public static function tearDownAfterClass(): void
  {
    // overrideableApp now allows resetting all stuff
    // with one method call
    overrideableApp::reset();

    // Reset instances to cleanup possible clients
    // e.g. database connections
    // $_REQUEST['instances'] = [];
  }

  /**
   * allows setting the current environment config
   * @param array $config [description]
   */
  protected static function setEnvironmentConfig(array $config) {
    $configInstance = new \codename\core\config($config);
    overrideableApp::__overrideEnvironmentConfig($configInstance);
  }

  /**
   * creates a pseudo app instance
   * @return overrideableApp
   */
  protected static function createApp(): overrideableApp {
    return new overrideableApp();
  }

  /**
   * creates a model and builds it
   * @param  string       $schema [description]
   * @param  string       $model  [description]
   * @param  array        $config [description]
   * @param  callable|null  $initFunction
   * @return void
   */
  protected static function createModel(string $schema, string $model, array $config, ?callable $initFunction = null) {
    static::$models[$model] = [
      'schema'    => $schema,
      'model'     => $model,
      'config'    => $config,
      'initFunction' => $initFunction,
    ];
  }

  /**
   * [getModel description]
   * @param  string $model [description]
   * @return \codename\core\model
   */
  protected static function getModelStatic(string $model): \codename\core\model {
    $modelData = static::$models[$model];
    if($modelData['initFunction'] ?? false) {
      return $modelData['initFunction']($modelData['schema'], $modelData['model'], $modelData['config']);
    } else {
      return new sqlModel($modelData['schema'], $modelData['model'], $modelData['config']);
    }
  }

  /**
   * [getModel description]
   * @param  string               $model [description]
   * @return \codename\core\model        [description]
   */
  protected function getModel(string $model): \codename\core\model {
    return static::getModelStatic($model);
  }


  /**
   * Executes architect steps (building models/data structures)
   * @param  string $app     [description]
   * @param  string $vendor  [description]
   * @param  string $envName [description]
   * @return void
   */
  protected static function architect(string $app, string $vendor, string $envName) {
    $dbDoc = new overrideableDbDoc($app, $vendor);
    $architectEnv = new \codename\architect\config\environment(app::getEnvironment()->get(), $envName);

    $modeladapters = [];
    foreach(static::$models as $model) {
      $modeladapters[] = $dbDoc->getModelAdapter($model['schema'], $model['model'], $model['config'], $architectEnv);
    }

    // NOTE: if dbDoc fails due to misconfigured models,
    // this will fail here, too

    $dbDoc->setModelAdapters($modeladapters);

    $dbDoc->run(true, [ \codename\architect\dbdoc\task::TASK_TYPE_REQUIRED ]);
    $dbDoc->run(true, [ \codename\architect\dbdoc\task::TASK_TYPE_SUGGESTED ]);
  }

  /**
   * models in this environment/test case
   * @var array
   */
  protected static $models = [];

}

/**
 * Class override that allows accessing protected or final methods
 * to emulate different environments or force specific circumstances
 */
class overrideableApp extends \codename\core\app {

  /**
   * @inheritDoc
   */
  public function __CONSTRUCT()
  {
    parent::__CONSTRUCT();

    // TODO
    $this->injectApp([
      'vendor' => 'codename',
      'app' => 'architect',
      'namespace' => '\\codename\\architect'
    ]);

    // prevent real exit
    static::$exitCode = null;
  }

  /**
   * resets the app instance
   */
  public static function reset(): void {
    static::$app = null;
    static::$vendor = null;
    static::$namespace = null;
    static::$homedir = null;
    // static::$instances = [];
    static::$instance = null;
    static::$appstack = null;
    $_REQUEST['instances'] = [];
  }

  /**
   * [resetRequest description]
   */
  public static function resetRequest(): void {
    unset(static::$instances['request']);
  }

  /**
   * [resetResponse description]
   */
  public static function resetResponse(): void {
    unset(static::$instances['response']);
  }

  /**
   * Overrides the current app's name
   * must stick to text_methodname
   * @param string $app [description]
   */
  public static function __setApp(string $app) {
    static::$app = new \codename\core\value\text\methodname($app);
  }

  /**
   * Overrides the current app's vendor
   * must stick to text_methodname
   * @param string $vendor [description]
   */
  public static function __setVendor(string $vendor) {
    static::$vendor = new \codename\core\value\text\methodname($vendor);
  }

  /**
   * Overrides the current app's default namespace.
   * Can also be used for resetting (NULL)
   * @param string|null $namespace [description]
   */
  public static function __setNamespace(?string $namespace) {
    static::$namespace = $namespace;
  }

  /**
   * [__setHomedir description]
   * @param string|null $homedir [description]
   */
  public static function __setHomedir(?string $homedir) {
    static::$homedir = $homedir;
  }

  /**
   * [__injectApp description]
   * @param  array  $injectApp [description]
   * @return void
   */
  public static function __injectApp(array $injectApp): void {
    static::injectApp($injectApp);
  }

  /**
   * [__injectClientInstance description]
   * @param  string $type           [description]
   * @param  string $identifier     [description]
   * @param  mixed $clientInstance [description]
   * @return [type]                 [description]
   */
  public static function __injectClientInstance(string $type, string $identifier, $clientInstance) {
    $simplename = $type . $identifier;
    $_REQUEST['instances'][$simplename] = $clientInstance;
  }

  /**
   * [__setInstance description]
   * @param string $name     [description]
   * @param [type] $instance [description]
   */
  public static function __setInstance(string $name, $instance) {
    static::$instances[$name] = $instance;
  }

  /**
   * Injects a given instance into the available instances
   * @param  string                 $contextName
   * @param  \codename\core\context $contextInstance
   */
  public static function __injectContextInstance(string $contextName, \codename\core\context $contextInstance) {
    $simplename = self::getApp()."_{$contextName}";
    $_REQUEST['instances'][$simplename] = $contextInstance;
  }

  /**
   * Overrides/provides an environment config
   * for usage with custom test cases
   * @param \codename\core\config $config [description]
   */
  public static function __overrideEnvironmentConfig(\codename\core\config $config) {
    static::$environment = $config;
  }

  /**
   * [__overrideJsonConfigPath description]
   * @param  string $path [description]
   */
  public static function __overrideJsonConfigPath(string $path) {
    static::$json_config = $path;
  }
}
