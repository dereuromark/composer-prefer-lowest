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
	public function testValidateBeta() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'beta' . DS;
		$returnCode = $validator->validate($path);
		$this->assertSame($validator::CODE_SUCCESS, $returnCode);
	}

	/**
	 * @return void
	 */
	public function testValidateRanges() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'ranges' . DS;
		$returnCode = $validator->validate($path);
		$this->assertSame($validator::CODE_SUCCESS, $returnCode);
	}

	/**
	 * @return void
	 */
	public function testValidateMinMax() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'minmax' . DS;
		$returnCode = $validator->validate($path);
		$this->assertSame($validator::CODE_SUCCESS, $returnCode);
	}

	/**
	 * @return void
	 */
	public function testValidateWildcard() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'wildcard' . DS;
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
2 errors (make sure you ran `composer update --prefer-lowest` before):
 - composer/semver: Defined `1.4.0.0` as minimum, but is `1.4.2.0`
 - foo-bar/baz: Defined `2.3.1.0` as minimum, but is `3.1.0.0`
1 warning (impossible to test minimum version here or allowed as `--majors-only`/`-m`):
 - my/warning: Defined `1.1.1.0` as minimum, but is `1.2.1.0`

TXT;
		$this->assertSame($expected, $output);
	}

	/**
	 * @return void
	 */
	public function testValidateFailMajorsOnly() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'failure' . DS;

		ob_start();
		$returnCode = $validator->validate($path, ['--' . $validator::MAJORS_ONLY]);

		$output = ob_get_clean();

		$this->assertSame($validator::CODE_ERROR, $returnCode);

		$expected = <<<TXT
1 error (make sure you ran `composer update --prefer-lowest` before):
 - foo-bar/baz: Defined `2.3.1.0` as minimum, but is `3.1.0.0`
2 warnings (impossible to test minimum version here or allowed as `--majors-only`/`-m`):
 - composer/semver: Defined `1.4.0.0` as minimum, but is `1.4.2.0`
 - my/warning: Defined `1.1.1.0` as minimum, but is `1.2.1.0`

TXT;
		$this->assertSame($expected, $output);
	}

	/**
	 * @return void
	 */
	public function testValidateInvalid() {
		$validator = new Validator();

		$path = TESTS . 'files' . DS . 'invalid' . DS;

		ob_start();
		$returnCode = $validator->validate($path);

		$output = ob_get_clean();

		$this->assertSame($validator::CODE_ERROR, $returnCode);

		$expected = 'Make sure composer.json and composer.lock files are valid and that you have at least one dependency in require.';
		$this->assertSame($expected, $output);
	}

}
