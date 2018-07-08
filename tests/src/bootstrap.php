<?php

/**
 * @file
 * Phpunit bootstrap.
 */

use PHPUnit\Runner\Version;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/drush/drush/tests/unish.inc';

call_user_func(function () {
  $phpunitVersion = class_exists('\PHPUnit_Runner_Version') ?
    \PHPUnit_Runner_Version::id()
    : Version::id();

  if (version_compare($phpunitVersion, '6.1', '>=')) {
    class_alias('\PHPUnit\Framework\AssertionFailedError', '\PHPUnit_Framework_AssertionFailedError');
    class_alias('\PHPUnit\Framework\Constraint\Count', '\PHPUnit_Framework_Constraint_Count');
    class_alias('\PHPUnit\Framework\Error\Error', '\PHPUnit_Framework_Error');
    class_alias('\PHPUnit\Framework\Error\Warning', '\PHPUnit_Framework_Error_Warning');
    class_alias('\PHPUnit\Framework\ExpectationFailedException', '\PHPUnit_Framework_ExpectationFailedException');
    class_alias('\PHPUnit\Framework\Exception', '\PHPUnit_Framework_Exception');
    class_alias('\PHPUnit\Framework\MockObject\Matcher\InvokedRecorder', '\PHPUnit_Framework_MockObject_Matcher_InvokedRecorder');
    class_alias('\PHPUnit\Framework\SkippedTestError', '\PHPUnit_Framework_SkippedTestError');
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
    class_alias('\PHPUnit\Util\Test', '\PHPUnit_Util_Test');
    class_alias('\PHPUnit\Util\XML', '\PHPUnit_Util_XML');
  }
});
