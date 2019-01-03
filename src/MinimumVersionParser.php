<?php

namespace ComposerPreferLowest;

class MinimumVersionParser {

	/**
	 * Parses a constraint string into MultiConstraint and/or Constraint objects.
	 *
	 * @param string $constraints
	 *
	 * @return string
	 */
	public function parseConstraints($constraints) {
		$orConstraints = preg_split('{\s*\|\|?\s*}', trim($constraints));
		$version = array_shift($orConstraints);

		return $version;
	}

}
