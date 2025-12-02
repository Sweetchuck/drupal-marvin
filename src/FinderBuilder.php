<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Symfony\Component\Finder\Finder;

class FinderBuilder {

  /**
   * @param array<string, mixed> $definition
   * @param \Symfony\Component\Finder\Finder $finder
   *
   * @todo PhpStan array shape.
   */
  public function build(array $definition, Finder $finder): static {
    foreach ($definition as $method => $value) {
      if ($value === NULL) {
        continue;
      }

      switch ($method) {
        // Type bool|string|array.
        case 'in':
        case 'depth':
        case 'path':
        case 'notPath':
        case 'name':
        case 'notName':
        case 'ignoreDotFiles':
        case 'ignoreVCS':
          if (is_array($value)) {
            // Dot notation value override can't remove entries in drush.yml
            // files. So, the NULL value means it has to be ignored.
            $value = array_filter($value, fn ($item) => $item !== NULL);
            // @todo What to do with an empty array?
          }
          $finder->{$method}($value);

          break;

        // No argument.
        case 'files':
          $finder->files();
          break;

        // No argument.
        case 'directories':
          $finder->directories();
          break;

        default:
          // @todo Support other methods.
          throw new \LogicException("Method '$method' is not supported.");
      }
    }

    return $this;
  }

}
