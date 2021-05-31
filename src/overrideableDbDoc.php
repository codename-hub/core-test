<?php
namespace codename\core\test;

/**
 * Modified DbDoc class
 * that enables editing/setting the model adapters directly
 */
class overrideableDbDoc extends \codename\architect\dbdoc\dbdoc {

  /**
   * [setModelAdapters description]
   * @param array $modeladapters [description]
   */
  public function setModelAdapters(array $modeladapters) {
    $this->adapters = $modeladapters;
  }
}
