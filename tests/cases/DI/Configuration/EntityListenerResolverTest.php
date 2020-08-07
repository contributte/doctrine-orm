<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Configuration;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Nette\DI\Compiler;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Testcase\AbstractConfigurationTest;

final class EntityListenerResolverTest extends AbstractConfigurationTest
{

	public function testDefault(): void
	{
		$configuration = $this->createConfiguration();
		$this->assertInstanceOf(ContainerEntityListenerResolver::class, $configuration->getEntityListenerResolver());
	}

	public function testString(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							entityListenerResolver: Doctrine\ORM\Mapping\DefaultEntityListenerResolver
				'));
		});
		$this->assertInstanceOf(DefaultEntityListenerResolver::class, $configuration->getEntityListenerResolver());
	}

	public function testStatement(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							entityListenerResolver: Doctrine\ORM\Mapping\DefaultEntityListenerResolver()
				'));
		});
		$this->assertInstanceOf(DefaultEntityListenerResolver::class, $configuration->getEntityListenerResolver());
	}

	public function testReference(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					services:
						reference: Doctrine\ORM\Mapping\DefaultEntityListenerResolver()

					nettrine.orm:
						configuration:
							entityListenerResolver: @reference
				'));
		});
		$this->assertInstanceOf(DefaultEntityListenerResolver::class, $configuration->getEntityListenerResolver());
	}

}
