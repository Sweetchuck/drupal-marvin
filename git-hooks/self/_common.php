<?php

$gitHook = basename($GLOBALS['argv'][0]);
$drushCommand = "marvin:git-hook:$gitHook";

$exitCode = NULL;
exec("bin/drush help $drushCommand", $output, $exitCode);
if ($exitCode !== 0) {
  // There is no corresponding drush command.
  exit(0);
}

$_SERVER['argv'] = $GLOBALS['argv'] = [
  'bin/drush',
  "--define=marvin.settings.gitHook=$gitHook",
  '--config=.',
  '--config=./Commands',
  $drushCommand,
];

$_SERVER['argc'] = $GLOBALS['argc'] = count($GLOBALS['argv']);

require 'vendor/drush/drush/drush.php';
