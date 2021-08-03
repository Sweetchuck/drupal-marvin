
# Drupal - Marvin

[![CircleCI](https://circleci.com/gh/Sweetchuck/drupal-marvin/tree/2.x.svg?style=svg)](https://circleci.com/gh/Sweetchuck/drupal-marvin/?branch=2.x)
[![codecov](https://codecov.io/gh/Sweetchuck/drupal-marvin/branch/2.x/graph/badge.svg?token=hKwwzce33I)](https://app.codecov.io/gh/Sweetchuck/drupal-marvin/branch/2.x)


## @todo

* https://packagist.org/packages/dealerdirect/phpcodesniffer-composer-installer
* Circular dependency
* Changelog generator
* *.service.yml Class exists checker
* Run Behat tests parallel
  * Duplicate a site (docroot/sites/*/)
    * HTTP server virtual hosts
    * file.public
    * file.private
    * file.temporary
    * database.default
    * Solr cores
    * Redis (prefix)
    * Memcached (prefix)
    * etc
  * Start CromeDriver/HeadlessCrome instances
  * Split Behat scenarios
