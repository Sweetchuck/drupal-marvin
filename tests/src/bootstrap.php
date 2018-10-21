<?php

/**
 * @file
 * Phpunit bootstrap.
 */

use PHPUnit\Runner\Version as PHPUnitRunnerVersion;
use PHPUnit\Framework\AssertionFailedError as PHPUnitFrameworkAssertionFailedError;
use PHPUnit\Framework\Constraint\Count as PHPUnitFrameworkConstraintCount;
use PHPUnit\Framework\Error\Error as PHPUnitFrameworkErrorError;
use PHPUnit\Framework\Error\Warning as PHPUnitFrameworkErrorWarning;
use PHPUnit\Framework\ExpectationFailedException as PHPUnitFrameworkExpectationFailedException;
use PHPUnit\Framework\Exception as PHPUnitFrameworkException;
use PHPUnit\Framework\MockObject\Matcher\InvokedRecorder as PHPUnitFrameworkMockObjectMatcherInvokedRecorder;
use PHPUnit\Framework\SkippedTestError as PHPUnitFrameworkSkippedTestError;
use PHPUnit\Framework\TestCase as PHPUnitFrameworkTestCase;
use PHPUnit\Framework\TestResult as PHPUnitFrameworkTestResult;
use PHPUnit\Util\Test as PHPUnitUtilTest;
use PHPUnit\Util\XML as PHPUnitUtilXML;

require_once __DIR__ . '/../../vendor/autoload.php';

call_user_func(function () {
  $phpunitVersion = class_exists('\PHPUnit_Runner_Version') ?
    \PHPUnit_Runner_Version::id()
    : PHPUnitRunnerVersion::id();

  if (version_compare($phpunitVersion, '6.1', '>=')) {
    class_alias(PHPUnitFrameworkAssertionFailedError::class, '\PHPUnit_Framework_AssertionFailedError');
    class_alias(PHPUnitFrameworkConstraintCount::class, '\PHPUnit_Framework_Constraint_Count');
    class_alias(PHPUnitFrameworkErrorError::class, '\PHPUnit_Framework_Error');
    class_alias(PHPUnitFrameworkErrorWarning::class, '\PHPUnit_Framework_Error_Warning');
    class_alias(PHPUnitFrameworkExpectationFailedException::class, '\PHPUnit_Framework_ExpectationFailedException');
    class_alias(PHPUnitFrameworkException::class, '\PHPUnit_Framework_Exception');
    class_alias(PHPUnitFrameworkMockObjectMatcherInvokedRecorder::class, '\PHPUnit_Framework_MockObject_Matcher_InvokedRecorder');
    class_alias(PHPUnitFrameworkSkippedTestError::class, '\PHPUnit_Framework_SkippedTestError');
    class_alias(PHPUnitFrameworkTestCase::class, '\PHPUnit_Framework_TestCase');
    class_alias(PHPUnitFrameworkTestResult::class, '\PHPUnit_Framework_TestResult');
    class_alias(PHPUnitUtilTest::class, '\PHPUnit_Util_Test');
    class_alias(PHPUnitUtilXML::class, '\PHPUnit_Util_XML');
  }
});
