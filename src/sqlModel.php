<?php

namespace codename\core\test;

use codename\core\config;
use codename\core\exception;
use codename\core\model\schematic\sql;
use ReflectionException;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class sqlModel extends sql
{
    /**
     * @inheritDoc
     * @param string $schema
     * @param string $model
     * @param array $config
     * @throws ReflectionException
     * @throws exception
     */
    public function __construct(string $schema, string $model, array $config)
    {
        parent::__construct();
        $this->config = new config($config);
        $this->setConfig(null, $schema, $model);
    }
}
