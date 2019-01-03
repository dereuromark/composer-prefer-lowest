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

		$errors = [];
		foreach ($lockInfo as $package => $version) {
			$definedMinimum = $jsonInfo[$package];

			$definedMinimum = $this->normalizeVersion($definedMinimum);
			$version = $this->normalizeVersion($version);

			if (Comparator::equalTo($definedMinimum, $version)) {
				continue;
			}

			$errors[$package] = 'Defined `' . $definedMinimum . '` as minimum, but is `' . $version . '`';
		}

		if ($errors) {
			echo count($errors) . ' version errors (make sure you ran `composer update --prefer-lowest` before):' . PHP_EOL;
		}
		foreach ($errors as $error) {
			echo ' - ' . $package . ': ' . $error . PHP_EOL;
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

			$result[$package] = $this->stripVersion($version);
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
		return (new VersionParser)->normalize($version);
	}

}
