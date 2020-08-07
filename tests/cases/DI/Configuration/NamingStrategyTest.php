<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Configuration;

use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Nette\DI\Compiler;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Testcase\AbstractConfigurationTest;

final class NamingStrategyTest extends AbstractConfigurationTest
{

	public function testDefault(): void
	{
		$configuration = $this->createConfiguration();
		$this->assertInstanceOf(UnderscoreNamingStrategy::class, $configuration->getNamingStrategy());
	}

	public function testString(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy
				'));
		});
		$this->assertInstanceOf(UnderscoreNamingStrategy::class, $configuration->getNamingStrategy());
	}

	public function testStatement(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy()
				'));
		});
		$this->assertInstanceOf(UnderscoreNamingStrategy::class, $configuration->getNamingStrategy());
	}

}
