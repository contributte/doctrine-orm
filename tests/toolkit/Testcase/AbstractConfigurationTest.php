<?php declare(strict_types = 1);

namespace Tests\Toolkit\Testcase;

use Doctrine\ORM\Configuration;
use Nette\DI\Compiler;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

abstract class AbstractConfigurationTest extends TestCase
{

	protected function createConfiguration(?callable $callback = null): Configuration
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler) use ($callback): void {
				if ($callback) {
					$callback($compiler);
				}
			})
			->build();

		return $container->getByType(Configuration::class);
	}

}
