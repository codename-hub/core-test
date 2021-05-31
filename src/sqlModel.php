<?php
namespace codename\core\test;

/**
 * SQL Base model leveraging the new model servicing modules
 * and enables freely defining and loading model configs
 */
class sqlModel extends \codename\core\model\schematic\sql {

  /**
   * @inheritDoc
   */
  public function __CONSTRUCT(string $schema, string $model, array $config)
  {
    $value = parent::__CONSTRUCT([]);
    $this->config = new \codename\core\config($config);
    $this->setConfig(null, $schema, $model);
  }
}
