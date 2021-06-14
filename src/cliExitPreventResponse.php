<?php
namespace codename\core\test;

/**
 * Response that prevents die/flush/exit via pushOutput
 */
class cliExitPreventResponse extends \codename\core\response\cli {
  /**
   * @inheritDoc
   */
  public function pushOutput()
  {
    return;
  }
}
