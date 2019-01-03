<?php

namespace ComposerPreferLowest\TestCase;

use ComposerPreferLowest\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {

	/**
	 * @return void
	 */
	public function testValidate() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'success' . DS;
		$returnCode = $validator->validate($path);
		$this->assertSame($validator::CODE_SUCCESS, $returnCode);
	}

	/**
	 * @return void
	 */
	public function testValidateFail() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'failure' . DS;

		ob_start();
		$returnCode = $validator->validate($path);

		$output = ob_get_clean();

		$this->assertSame($validator::CODE_ERROR, $returnCode);

		$expected = <<<TXT
1 version errors (make sure you ran `composer update --prefer-lowest` before):
 - composer/semver: Defined `1.4.0.0` as minimum, but is `1.4.2.0`

TXT;
		$this->assertSame($expected, $output);
	}

}
