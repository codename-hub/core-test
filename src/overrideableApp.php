<?php
namespace codename\core\test;

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
   * [__modifyAppstackEntry description]
   * @param  string     $vendor                [app's vendor to look for]
   * @param  string     $app                   [app name to modify]
   * @param  array|null $newData               [null to delete, otherwise: new data to be used]
   * @param  bool       $replace               [whether to replace the full dataset or merge with newData]
   * @return void
   */
  public static function __modifyAppstackEntry(string $vendor, string $app, ?array $newData, bool $replace = false): void {
    $index = null;
    $stack = static::$appstack->get();
    foreach($stack as $i => $appstackEntry) {
      if(($appstackEntry['vendor'] == $vendor) && ($appstackEntry['app'] == $app)) {
        $index = $i;
        break;
      }
    }
    if($index !== null) {
      if($newData === null) {
        array_splice($stack, $index, 1);
      } else {
        if($replace) {
          $stack[$index] = $newData;
        } else {
          $stack[$index] = array_merge($stack[$index], $newData);
        }
      }

      // replace stack
      self::$appstack = new \codename\core\value\structure\appstack($stack);
    } else {
      // not found, error?
      // print_r(static::$appstack);
      // die();
      // throw new \Exception('__modifyInjectedApp failed');
    }
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
   * Returns the current, full-fledged environment config
   * @return \codename\core\config
   */
  public static function __getEnvironmentConfig(): \codename\core\config {
    return static::$environment;
  }

  /**
   * [__overrideJsonConfigPath description]
   * @param  string $path [description]
   */
  public static function __overrideJsonConfigPath(string $path) {
    static::$json_config = $path;
  }
}
