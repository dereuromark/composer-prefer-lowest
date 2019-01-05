<?php
namespace ComposerPreferLowest;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;

class Validator {

	const CODE_SUCCESS = 0;
	const CODE_ERROR = 1;

	/**
	 * @param string $path
	 * @return int Returns 0 on success, otherwise error code.
	 */
	public function validate($path) {
		if (!$path) {
			echo 'Path to composer.lock file not found' . PHP_EOL;
			return static::CODE_ERROR;
		}

		$lockFilePath = $path . 'composer.lock';
		if (!file_exists($lockFilePath)) {
			echo 'composer.lock file not found: ' . $lockFilePath . PHP_EOL;
			return static::CODE_ERROR;
		}

		$jsonFilePath = $path . 'composer.json';
		if (!file_exists($jsonFilePath)) {
			echo 'composer.json file not found: ' . $jsonFilePath . PHP_EOL;
			return static::CODE_ERROR;
		}

		return $this->compare($lockFilePath, $jsonFilePath);
	}

	/**
	 * @param string $lockFile
	 * @param string $jsonFile
	 *
	 * @return int
	 */
	protected function compare($lockFile, $jsonFile) {
		$jsonInfo = $this->parseJsonFromFile($jsonFile);
		$lockInfo = $this->parseLockFromFile($lockFile, $jsonInfo);
		if (!$jsonInfo || !$lockInfo) {
			echo 'Make sure composer.json and composer.lock files are valid and that you have at least one dependency in require.';
			return static::CODE_ERROR;
		}

		$warnings = $errors = [];
		foreach ($lockInfo as $package => $version) {
			$constraints = $jsonInfo[$package]['version'];
			// We only need the first
			$constraint = (new MinimumVersionParser())->parseConstraints($constraints);

			$definedMinimum = $this->normalizeVersion($constraint);
			$version = $this->normalizeVersion($version);

			if (Comparator::equalTo($definedMinimum, $version)) {
				continue;
			}

			$message = 'Defined `' . $definedMinimum . '` as minimum, but is `' . $version . '`';
			if ($jsonInfo[$package]['devVersion']) {
				$warnings[$package] = $message;
			} else {
				$errors[$package] = $message;
			}
		}

		if ($errors) {
			$count = count($errors);
			echo $count . ' ' . ($count === 1 ? 'error' : 'errors') . ' (make sure you ran `composer update --prefer-lowest` before):' . PHP_EOL;
		}
		foreach ($errors as $package => $error) {
			echo ' - ' . $package . ': ' . $error . PHP_EOL;
		}

		if ($warnings) {
			$count = count($warnings);
			echo $count . ' ' . ($count === 1 ? 'warning' : 'warnings') . ' (impossible to test minimum version here):' . PHP_EOL;
		}
		foreach ($warnings as $package => $warning) {
			echo ' - ' . $package . ': ' . $warning . PHP_EOL;
		}

		return !$errors ? static::CODE_SUCCESS : static::CODE_ERROR;
	}

	/**
	 * @param string $jsonFile
	 * @return array
	 */
	protected function parseJsonFromFile($jsonFile) {
		$content = file_get_contents($jsonFile);
		$json = json_decode($content, true);

		if (empty($json['require'])) {
			return [];
		}

		$result = [];

		foreach ($json['require'] as $package => $version) {
			if (preg_match('#^ext-#', $package) || in_array($version, ['*', '@stable'])) {
				continue;
			}
			if (preg_match('#^dev-#', $version)) {
				continue;
			}

			$devVersion = null;
			if (isset($json['require-dev'][$package])) {
				$devVersion = $this->stripVersion($json['require-dev'][$package]);
			}

			$result[$package] = [
				'version' => $this->stripVersion($version),
				'devVersion' => $devVersion,
			];
		}

		return $result;
	}

	/**
	 * TODO: improve
	 *
	 * @param string $version
	 *
	 * @return string
	 */
	protected function stripVersion($version) {
		$from = [
			'>=',
			'^',
			'~',
		];

		return str_replace($from, '', $version);
	}

	/**
	 * @param string $lockFile
	 * @param array $jsonInfo
	 * @return array
	 */
	protected function parseLockFromFile($lockFile, array $jsonInfo) {
		$content = file_get_contents($lockFile);
		$json = json_decode($content, true);

		$result = [];

		$packages = $json['packages'];
		foreach ($packages as $package) {
			$name = $package['name'];
			if (!isset($jsonInfo[$name])) {
				continue;
			}

			$result[$name] = $package['version'];
		}

		return $result;
	}

	/**
	 * PHP has the issue of 1.4 !== 1.4.0, which is nonsense.
	 * Also Composer requires normalization.
	 *
	 * @param string $version
	 * @return string
	 */
	protected function normalizeVersion($version) {
		return (new VersionParser())->normalize($version);
	}

}
