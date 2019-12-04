#!/usr/bin/php -q
<?php

$options = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
if (!empty($_SERVER['PWD'])) {
    array_unshift($options, $_SERVER['PWD'] . '/vendor/autoload.php');
}

foreach ($options as $file) {
    if (file_exists($file)) {
        define('VALIDATOR_COMPOSER_INSTALL', $file);
        break;
    }
}
require VALIDATOR_COMPOSER_INSTALL;

$validator = new \ComposerPreferLowest\Validator();

$pathToComposerLock = null;
if (!empty($_SERVER['PWD'])) {
	$pathToComposerLock = rtrim($_SERVER['PWD'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

$options = !empty($_SERVER['argv']) ? $_SERVER['argv'] : [];
if ($options) {
	array_shift($options);
}

$result = $validator->validate($pathToComposerLock, $options);
die($result);
