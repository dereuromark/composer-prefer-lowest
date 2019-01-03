# Composer Prefer Lowest Validator
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/validate-prefer-lowest/license.svg)](https://packagist.org/packages/dereuromark/validate-prefer-lowest)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

This validator will strictly check your required minimum versions and see if those are actually being tested against.

This is useful for all libraries that want to make sure 
- **the defined minimum of each dependency is actually still being tested** 
- no silent regressions (like using too new methods of depending libraries) sneaked in

For details, see [Why is this useful?](https://evertpot.com/testing-composer-prefer-lowest/).
This is not so useful for projects, as here there is no need to test against anything than latest versions already in use.
Also, if your library has no dependencies, you can skip prefer-lowest checks as well as this validation.

This has been built after Composer didnt have the [motivation](https://github.com/composer/composer/issues/7849) for it.

**A total must have** for
- frameworks
- framework plugins/addons (and testing against the framework minors)
- custom libraries to be used by apps/projects which have at least one dependency to other libraries

It is somewhat important for the involved packages to follow semver here. Otherwise some of the comparison might be problematic.

## Installation
It is recommended to run only for CI and `composer update --prefer-lowest`.
As such, it suffices to add it conditionally here.

E.g. for Travis CI:
```
env:
  global:
    - DEFAULT=1

matrix:
  include:
    - php: 5.6
      env: PREFER_LOWEST=1

before_script:
  - if [[ $PREFER_LOWEST != 1 ]]; then composer install --prefer-source --no-interaction; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then composer update --prefer-dist --no-interaction --prefer-lowest --prefer-stable; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then composer require --dev dereuromark/composer-prefer-lowest:dev-master; fi

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

### PHP version
In general: Use the minimum PHP version for `prefer-lowest` as defined in your composer.json.

This tool requires minimum PHP 5.6, as such make sure your library to test also runs on this (or higher) for the `prefer-lowest` CI job.
At this point, with it being EOL already, you can and should not use any PHP version below 5.6 anyway, or provide support for it.

It is advised to also raise your composer.json entry for the min PHP version here. Use 5.6 or higher:
```
	"require": {
		"php": ">=5.6",
``` 

### TODOS
- Better version handling, especially around special cases like suffixes.

Help is greatly appreciated.