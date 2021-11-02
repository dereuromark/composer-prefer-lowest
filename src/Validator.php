<?php

namespace ComposerPreferLowest;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use RuntimeException;

class Validator {

	/**
	 * @var int
	 */
	public const CODE_SUCCESS = 0;

	/**
	 * @var int
	 */
	public const CODE_ERROR = 1;

	/**
	 * @var string
	 */
	public const MAJORS_ONLY = 'majors-only';

	/**
	 * @var string
	 */
	public const MAJORS_ONLY_SHORT = 'm';

	/**
	 * @param string $path
	 * @param array<string> $options
	 * @return int Returns 0 on success, otherwise error code.
	 */
	public function validate(string $path, array $options = []): int {
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

		$compareOptions = $this->parseOptions($options);

		return $this->compare($lockFilePath, $jsonFilePath, $compareOptions);
	}

	/**
	 * @param string $lockFile
	 * @param string $jsonFile
	 * @param array $options
	 *
	 * @return int
	 */
	protected function compare(string $lockFile, string $jsonFile, array $options): int {
		$jsonInfo = $this->parseJsonFromFile($jsonFile);
		$lockInfo = $jsonInfo !== null ? $this->parseLockFromFile($lockFile, $jsonInfo) : null;
		if ($jsonInfo === null || $lockInfo === null) {
			echo 'Make sure composer.json and composer.lock files are valid and that you have at least one dependency in require.';

			return static::CODE_ERROR;
		}

		$warnings = $errors = [];
		foreach ($lockInfo as $package => $version) {
			$definedMinimum = $this->definedMinimum($jsonInfo, $package);
			$version = $this->normalizeVersion($version);
			if ($version === '9999999-dev') {
				continue;
			}

			if (Comparator::equalTo($definedMinimum, $version)) {
				continue;
			}

			$message = 'Defined `' . $definedMinimum . '` as minimum, but is `' . $version . '`';
			if ($jsonInfo[$package]['devVersion'] || $this->isAllowedNonMajor($definedMinimum, $version, $options)) {
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
			$text = 'impossible to test minimum version here or allowed as `--' . static::MAJORS_ONLY . '`/`-' . static::MAJORS_ONLY_SHORT . '`';
			echo $count . ' ' . ($count === 1 ? 'warning' : 'warnings') . ' (' . $text . '):' . PHP_EOL;
		}
		foreach ($warnings as $package => $warning) {
			echo ' - ' . $package . ': ' . $warning . PHP_EOL;
		}

		return !$errors ? static::CODE_SUCCESS : static::CODE_ERROR;
	}

	/**
	 * @param array $jsonInfo
	 * @param string $package
	 *
	 * @return string
	 */
	protected function definedMinimum(array $jsonInfo, string $package): string {
		$constraints = $jsonInfo[$package]['version'];
		// We only need the first
		$constraint = (new MinimumVersionParser())->parseConstraints($constraints);

		return $this->normalizeVersion($constraint);
	}

	/**
	 * @param string $jsonFile
	 * @throws \RuntimeException
	 * @return array|null
	 */
	protected function parseJsonFromFile(string $jsonFile): ?array {
		$content = file_get_contents($jsonFile);
		if ($content === false) {
			throw new RuntimeException('Cannot read file: ' . $jsonFile);
		}
		$json = json_decode($content, true);

		if (!$json || empty($json['require'])) {
			return null;
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
	protected function stripVersion(string $version): string {
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
	 * @throws \RuntimeException
	 * @return array|null
	 */
	protected function parseLockFromFile(string $lockFile, array $jsonInfo): ?array {
		$content = file_get_contents($lockFile);
		if ($content === false) {
			throw new RuntimeException('Cannot read file: ' . $lockFile);
		}
		$json = json_decode($content, true);
		if (!$json) {
			return null;
		}

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
	 * We also strip beta and other tags, as we are mainly focused on
	 * the version numbers for comparison.
	 *
	 * @param string $version
	 * @return string
	 */
	protected function normalizeVersion(string $version): string {
		$version = (new VersionParser())->normalize($version);

		if (strpos($version, '-') !== false) {
			$version = substr($version, 0, strpos($version, '-'));
		}

		return $version;
	}

	/**
	 * @param array<string> $options
	 *
	 * @return array
	 */
	protected function parseOptions(array $options): array {
		$result = [
			static::MAJORS_ONLY => false,
		];
		if (in_array('--' . static::MAJORS_ONLY, $options, true) || in_array('-' . static::MAJORS_ONLY_SHORT, $options, true)) {
			$result[static::MAJORS_ONLY] = true;
		}

		return $result;
	}

	/**
	 * @param string $definedMinimum x.y.z.0
	 * @param string $version x.y.z.0
	 * @param array $options
	 *
	 * @return bool
	 */
	protected function isAllowedNonMajor(string $definedMinimum, string $version, array $options): bool {
		if (!$options[static::MAJORS_ONLY]) {
			return false;
		}

		return Semver::satisfies($version, '^' . $definedMinimum);
	}

}
