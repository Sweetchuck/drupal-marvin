<?php

namespace Drush\marvin;

use Stringy\Stringy;

class Utils {

  public static function commandClassNameToConfigIdentifier(string $className): string {
    return (string) (new Stringy($className))
      ->regexReplace('^\\\\?Drush\\\\Commands\\\\', '')
      ->regexReplace('Commands$', '')
      ->replace('\\', '.')
      ->underscored()
      ->regexReplace('(?<=\.)((qa\.lint)_)(?=[^\.]+$)', '\\2.');
  }

}
