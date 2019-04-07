<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{

	protected function setUp(): void
	{
		parent::setUp();

		if (!defined('TEMP_PATH')) {
			define('TEMP_PATH', __DIR__ . '/../tmp');
		}

		if (!defined('FIXTURES_PATH')) {
			define('FIXTURES_PATH', __DIR__ . '/../fixtures');
		}
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}

}
