<?php

/**
 * @file
 * Common Git hook callback.
 */

call_user_func(function () {
  $rootProjectDir = '';
  $composerExecutable = '';
  $marvinDir = '';

  $gitHook = basename($GLOBALS['argv'][0]);

  echo "BEGIN $gitHook\n";
  register_shutdown_function(function () use ($gitHook) {
    echo "END   $gitHook\n";
  });

  $drushCommand = "marvin:git-hook:$gitHook";
  $extensionDir = getcwd();

  chdir($rootProjectDir);

  $binDir = trim(exec(sprintf('%s config bin-dir', escapeshellcmd($composerExecutable))));

  $cmdPattern = '%s --config=%s --config=%s --include=%s help %s 2>&1';
  $cmdArgs = [
    escapeshellcmd("$binDir/drush"),
    escapeshellarg('drush'),
    escapeshellarg("$marvinDir/Commands"),
    escapeshellarg($marvinDir),
    escapeshellarg($drushCommand),
  ];

  $output = NULL;
  $exitCode = NULL;
  exec(vsprintf($cmdPattern, $cmdArgs), $output, $exitCode);
  if ($exitCode !== 0) {
    echo "There is no corresponding 'drush marvin:git-hook:$gitHook' command.\n";

    exit(0);
  }

  $args = $GLOBALS['argv'];
  array_shift($args);

  $_SERVER['argv'] = $GLOBALS['argv'] = array_merge(
    [
      "$binDir/drush",
      "--define=command.marvin.settings.gitHook=$gitHook",
      '--config=drush',
      "--config=$marvinDir/Commands",
      "--include=$marvinDir",
      $drushCommand,
    ],
    $args,
    [
      $extensionDir,
    ]
  );

  $_SERVER['argc'] = $GLOBALS['argc'] = count($GLOBALS['argv']);

  $vendorDir = trim(exec(sprintf('%s config vendor-dir', escapeshellcmd($composerExecutable))));
  require "$vendorDir/drush/drush/drush.php";
});
