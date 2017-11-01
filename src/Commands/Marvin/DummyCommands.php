<?php

namespace Drush\Commands\Marvin;

use Robo\Contract\TaskInterface;
use Robo\Tasks;

class DummyCommands extends Tasks {

  /**
   * @command marvin:hello
   * @bootstrap none
   */
  public function hello(): TaskInterface {
    return $this->taskExec('echo INNER');
  }

}
