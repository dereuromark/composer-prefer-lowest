<?php

namespace ComposerPreferLowest;

use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\VersionParser;

class MinimumVersionParser {

	/**
	 * Parses a constraint string into MultiConstraint and/or Constraint objects.
	 *
	 * @param string $constraints
	 *
	 * @return string
	 */
	public function parseConstraints($constraints) {
		$constraintsObjects = (new VersionParser())->parseConstraints($constraints);
		if ($constraintsObjects instanceof MultiConstraint) {
			$version = $constraintsObjects->getConstraints()[0]->getPrettyString();
		} else {
			$version = $constraintsObjects->getPrettyString();
		}

		if (strpos($version, ' ') === false) {
			return $version;
		}

		preg_match('#([^=]+)#', $version, $matches);

		return trim($matches[1]);
	}

}
