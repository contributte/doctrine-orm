<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Configuration;

use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Nette\DI\Compiler;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Testcase\AbstractConfigurationTest;

final class RepositoryFactoryTest extends AbstractConfigurationTest
{

	public function testDefault(): void
	{
		$configuration = $this->createConfiguration();
		$this->assertInstanceOf(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
	}

	public function testString(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							repositoryFactory: Doctrine\ORM\Repository\DefaultRepositoryFactory
				'));
		});
		$this->assertInstanceOf(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
	}

	public function testStatement(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							repositoryFactory: Doctrine\ORM\Repository\DefaultRepositoryFactory()
				'));
		});
		$this->assertInstanceOf(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
	}

}
