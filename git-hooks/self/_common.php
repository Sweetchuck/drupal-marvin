<?php

call_user_func(function () {
  $gitHook = basename($GLOBALS['argv'][0]);
  $drushCommand = "marvin:git-hook:$gitHook";

  $cmdPattern = 'bin/drush --config=%s --config=%s help %s 2>&1';
  $cmdArgs = [
    escapeshellarg('.'),
    escapeshellarg('./Commands'),
    escapeshellarg($drushCommand),
  ];

  $output = NULL;
  $exitCode = NULL;
  exec(vsprintf($cmdPattern, $cmdArgs), $output, $exitCode);
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
});
