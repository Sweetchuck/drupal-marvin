<?php

/**
 * @file
 * Common Git hook callback.
 */

call_user_func(function () {
  $composerExecutable = 'composer';

  $gitHook = basename($GLOBALS['argv'][0]);
  $drushCommand = "marvin:git-hook:$gitHook";
  $binDir = trim(exec(sprintf('%s config bin-dir', escapeshellcmd($composerExecutable))));

  $cmdPattern = '%s --config=%s --config=%s help %s 2>&1';
  $cmdArgs = [
    escapeshellcmd("$binDir/drush"),
    escapeshellarg('.'),
    escapeshellarg('./Commands'),
    escapeshellarg($drushCommand),
  ];

  $output = NULL;
  $exitCode = NULL;
  exec(vsprintf($cmdPattern, $cmdArgs), $output, $exitCode);
  if ($exitCode !== 0) {
    // There is no corresponding "drush marvin:git-hook:*" command.
    exit(0);
  }

  $args = $GLOBALS['argv'];
  array_shift($args);

  $_SERVER['argv'] = $GLOBALS['argv'] = array_merge(
    [
      "$binDir/drush",
      "--define=command.marvin.settings.gitHook=$gitHook",
      '--config=.',
      '--config=./Commands',
      $drushCommand,
    ],
    $args
  );

  $_SERVER['argc'] = $GLOBALS['argc'] = count($GLOBALS['argv']);

  $vendorDir = trim(exec(sprintf('%s config vendor-dir', escapeshellcmd($composerExecutable))));
  require "$vendorDir/drush/drush/drush.php";
});
