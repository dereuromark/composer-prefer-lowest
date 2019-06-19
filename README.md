# Composer Prefer Lowest Validator
[![Build Status](https://api.travis-ci.org/dereuromark/composer-prefer-lowest.svg?branch=master)](https://travis-ci.org/dereuromark/composer-prefer-lowest)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/composer-prefer-lowest/v/stable.svg)](https://packagist.org/packages/dereuromark/composer-prefer-lowest)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/composer-prefer-lowest/license.svg)](https://packagist.org/packages/dereuromark/composer-prefer-lowest)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)
[![Total Downloads](https://poser.pugx.org/dereuromark/composer-prefer-lowest/d/total.svg)](https://packagist.org/packages/dereuromark/composer-prefer-lowest)

This validator will strictly compare the specified minimum versions of your composer.json with the ones actually used by the `prefer-lowest` composer update command option.

This is useful for all libraries that want to make sure 
- **the defined minimum of each dependency is actually still being tested** 
- no silent regressions (like using too new methods of depending libraries) sneaked in

For details, see [Why and when is this useful?](https://www.dereuromark.de/2019/01/04/test-composer-dependencies-with-prefer-lowest).
This has been built after Composer didn't have the [motivation](https://github.com/composer/composer/issues/7849) for it.

**A total must-have** for
- frameworks
- framework plugins/addons (and testing against the framework minors)
- custom libraries to be used by apps/projects which have at least one dependency to other libraries

It is somewhat important for the involved packages to follow semver here. Otherwise some of the comparison might be problematic.

This is not so useful for projects, as here there is no need to test against anything than latest versions already in use.
Also, if your library has no dependencies, you can skip prefer-lowest checks as well as this validation.

## Local Test-Run
You want to give it a quick test-spin for one of your libraries? See what results it yields?
```
composer update --prefer-lowest --prefer-dist --prefer-stable
composer require --dev dereuromark/composer-prefer-lowest
vendor/bin/validate-prefer-lowest
```
If there is no output, that's good. `echo $?` should return `0` (success).

## CI Installation
It is recommended to run only for CI and `composer update --prefer-lowest`.
As such, it suffices to add it conditionally here.

E.g. for Travis CI:
```
php:
  - 5.6
  - 7.3

env:
  global:
    - DEFAULT=1

matrix:
  include:
    - php: 5.6
      env: PREFER_LOWEST=1

before_script:
  - if [[ $PREFER_LOWEST != 1 ]]; then composer install --prefer-source --no-interaction; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then composer update --prefer-lowest --prefer-dist --prefer-stable --no-interaction; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then composer require --dev dereuromark/composer-prefer-lowest; fi

script:
  - if [[ $DEFAULT == 1 ]]; then vendor/bin/phpunit; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then vendor/bin/validate-prefer-lowest; fi
```

You can, of course, also directly include it into `require-dev`.
After manually running `composer update --prefer-lowest` locally, you can also test this on your local computer then:
```
vendor/bin/validate-prefer-lowest
```

It returns the list of errors and exits with error code `1` if any violations are found.
Otherwise it returns with success code `0`.

### Prefer stable
Usually `composer update --prefer-lowest` suffices. 
Make sure you have `"prefer-stable": true` in your composer.json for this to work.
Otherwise you might have to use the longer version as outlined above.

In general it is best to just use all flags for your CI script:
```
composer update --prefer-lowest --prefer-dist --prefer-stable --no-interaction
```

### PHP version
In general: Use the minimum PHP version for `prefer-lowest` as defined in your composer.json.

This tool requires minimum PHP 5.6, as such make sure your library to test also runs on this (or higher) for the `prefer-lowest` CI job.
At this point, with it being EOL already, you can and should not use any PHP version below 5.6 anyway, or provide support for it.

It is advised to also raise your composer.json entry for the min PHP version here. Use 5.6 or higher:
```
    "require": {
        "php": ">=5.6",
``` 

## Local Composer Script Installation
For local testing, when you do not want to modify your composer.json file, you can simple add this composer script:
```
"scripts": {
    ...
    "lowest": " validate-prefer-lowest",
    "lowest-setup": "composer update --prefer-lowest --prefer-stable --prefer-dist --no-interaction && cp composer.json composer.backup && composer require --dev dereuromark/composer-prefer-lowest && mv composer.backup composer.json",
```

Then run `composer lowest-setup` to set up the script and finally `composer lowest` to execute.

## TODOs
- Better version handling, especially around special cases like suffixes.

Help is greatly appreciated.
