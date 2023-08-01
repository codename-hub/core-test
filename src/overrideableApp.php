<?php

namespace codename\core\test;

use codename\core\app;
use codename\core\config;
use codename\core\context;
use codename\core\exception;
use codename\core\value\structure\appstack;
use codename\core\value\text\methodname;
use ReflectionException;

/**
 * Class override that allows accessing protected or final methods
 * to emulate different environments or force specific circumstances
 */
class overrideableApp extends app
{
    /**
     * state override for ::shouldThrowException
     * @var bool|null
     */
    protected static ?bool $__shouldThrowExceptionState = null;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        // Prevent custom shutdown handler registration
        // as it causes bugs when using process isolation using PHPUnit
        $this->registerShutdownHandler = false;

        parent::__construct();

        // TODO
        static::injectApp([
          'vendor' => 'codename',
          'app' => 'architect',
          'namespace' => '\\codename\\architect',
        ]);

        // prevent real exit
        static::$exitCode = null;
        static::__setShouldThrowException(); // by default: true
    }

    /**
     * [__setShouldThrowException description]
     * @param bool|null $state [description]
     */
    public static function __setShouldThrowException(?bool $state = true): void
    {
        static::$__shouldThrowExceptionState = $state;
    }

    /**
     * resets the app instance
     */
    public static function reset(): void
    {
        static::$config = null; // reset (app) config
        static::$environment = null; // reset (env) config
        // static::$hook // We do not reset this to keep unittest-related global hooks alive
        static::$app = null;
        static::$vendor = null;
        static::$namespace = null;
        static::$homedir = null;
        // static::$instances = [];
        static::$instance = null;
        static::$appstack = null;
        static::$validatorCacheArray = [];
        $_REQUEST['instances'] = [];
    }

    /**
     * [resetRequest description]
     */
    public static function resetRequest(): void
    {
        unset(static::$instances['request']);
    }

    /**
     * [resetResponse description]
     */
    public static function resetResponse(): void
    {
        unset(static::$instances['response']);
    }

    /**
     * Overrides the current app's name
     * must stick to text_methodname
     * @param string $app [description]
     * @throws ReflectionException
     * @throws exception
     */
    public static function __setApp(string $app): void
    {
        static::$app = new methodname($app);
    }

    /**
     * Overrides the current app's vendor
     * must stick to text_methodname
     * @param string $vendor [description]
     * @throws ReflectionException
     * @throws exception
     */
    public static function __setVendor(string $vendor): void
    {
        static::$vendor = new methodname($vendor);
    }

    /**
     * Overrides the current app's default namespace.
     * Can also be used for resetting (NULL)
     * @param string|null $namespace [description]
     */
    public static function __setNamespace(?string $namespace): void
    {
        static::$namespace = $namespace;
    }

    /**
     * [__setHomedir description]
     * @param string|null $homedir [description]
     */
    public static function __setHomedir(?string $homedir): void
    {
        static::$homedir = $homedir;
    }

    /**
     * [__injectApp description]
     * @param array $injectApp
     * @param int|null $injectionMode
     * @return void
     * @throws exception
     */
    public static function __injectApp(array $injectApp, ?int $injectionMode = null): void
    {
        if ($injectionMode === null) {
            static::injectApp($injectApp);
        } else {
            static::injectApp($injectApp, $injectionMode);
        }
    }

    /**
     * [__modifyAppstackEntry description]
     * @param string $vendor [app's vendor to look for]
     * @param string $app [app name to modify]
     * @param array|null $newData [null to delete, otherwise: new data to be used]
     * @param bool $replace [whether to replace the full dataset or merge with newData]
     * @return void
     * @throws ReflectionException
     * @throws exception
     */
    public static function __modifyAppstackEntry(string $vendor, string $app, ?array $newData, bool $replace = false): void
    {
        $stack = static::$appstack->get();
        $newStack = [];
        foreach ($stack as $appstackEntry) {
            if (($appstackEntry['vendor'] == $vendor) && ($appstackEntry['app'] == $app)) {
                if ($newData) {
                    if ($replace) {
                        $newStack[] = $newData;
                    } else {
                        $newStack[] = array_merge($appstackEntry, $newData);
                    }
                } else {
                    // omit.
                }
            } else {
                $newStack[] = $appstackEntry;
            }
        }
        // replace stack
        self::$appstack = new appstack($newStack);
    }

    /**
     * [__injectClientInstance description]
     * @param string $type [description]
     * @param string $identifier [description]
     * @param mixed $clientInstance [description]
     * @return void [type]                 [description]
     */
    public static function __injectClientInstance(string $type, string $identifier, mixed $clientInstance): void
    {
        $simplename = $type . $identifier;
        $_REQUEST['instances'][$simplename] = $clientInstance;
    }

    /**
     * [__setInstance description]
     * @param string $name [description]
     * @param [type] $instance [description]
     */
    public static function __setInstance(string $name, $instance): void
    {
        static::$instances[$name] = $instance;
    }

    /**
     * Injects a given instance into the available instances
     * @param string $contextName
     * @param context $contextInstance
     * @throws ReflectionException
     * @throws exception
     */
    public static function __injectContextInstance(string $contextName, context $contextInstance): void
    {
        $simplename = self::getApp() . "_$contextName";
        $_REQUEST['instances'][$simplename] = $contextInstance;
    }

    /**
     * Overrides/provides an environment config
     * for usage with custom test cases
     * @param config $config [description]
     */
    public static function __overrideEnvironmentConfig(config $config): void
    {
        static::$environment = $config;
    }

    /**
     * Returns the current, full-fledged environment config
     * @return config
     */
    public static function __getEnvironmentConfig(): config
    {
        return static::$environment;
    }

    /**
     * [__overrideJsonConfigPath description]
     * @param string $path [description]
     */
    public static function __overrideJsonConfigPath(string $path): void
    {
        static::$json_config = $path;
    }

    /**
     * @inheritDoc
     */
    protected static function shouldThrowException(): bool
    {
        if (static::$__shouldThrowExceptionState === null) {
            return parent::shouldThrowException();
        } else {
            return static::$__shouldThrowExceptionState;
        }
    }
}
