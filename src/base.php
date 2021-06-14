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
   * [emulateRequest description]
   * @param string  $method         [description]
   * @param string  $host           [description]
   * @param string  $uri            [description]
   * @param [type]  $payload        [description]
   * @param array   $headers        [description]
   * @param bool    $defaultHeaders [description]
   */
  protected static function emulateRequest(string $method, string $host, string $uri, array $get = [], $payload = null, array $headers = [], bool $defaultHeaders = true): void {

    if(!function_exists('getallheaders')) {
      /**
       * getallheaders polyfill
       * for CLI environment, via $_SERVER['HTTP...'] vars
       * @return array
       */
      function getallheaders() {
        //
        // From https://github.com/ralouphie/getallheaders/blob/develop/src/getallheaders.php
        //
        $headers = array();

        $copy_server = array(
          'CONTENT_TYPE'   => 'Content-Type',
          'CONTENT_LENGTH' => 'Content-Length',
          'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
          if (substr($key, 0, 5) === 'HTTP_') {
            $key = substr($key, 5);
            if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
              $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
              $headers[$key] = $value;
            }
          } elseif (isset($copy_server[$key])) {
            $headers[$copy_server[$key]] = $value;
          }
        }

        if (!isset($headers['Authorization'])) {
          if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
          } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
            $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
            $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
          } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
          }
        }

        return $headers;
      }
    }

    // TODO: restore previous state?
    // or done via PHPUnit automatically?

    if(in_array($method, [ 'HEAD', 'OPTIONS', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ])) {
      $_SERVER['REQUEST_METHOD'] = $method;
    } else {
      throw new \Exception("Invalid method for emulateRequest: '$method'");
    }
    $_SERVER['SERVER_NAME'] = $host;
    $_SERVER['REQUEST_URI'] = $uri;

    if($defaultHeaders) {
      // add default headers
      $headers = array_replace([  ], $headers);
    }

    // header emulation by $_SERVER['HTTP_...'] ?
    foreach($headers as $key => $value) {
      $headerName = strtoupper(str_replace('-', '_', $key));
      $_SERVER['HTTP_'.$headerName] = $value;
    }

    foreach($get as $key => $value) {
      $_GET[$key] = $value;
    }

    // TODO: payload injection? GET? POST? REQUEST array?
    // php://input writing?
  }

  /**
   * models in this environment/test case
   * @var array
   */
  protected static $models = [];

}
