<?php declare(strict_types = 1);

namespace Tests\Nettrine\Migrations;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{

	/**
	 * @return void
	 */
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

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		Mockery::close();
	}

}
