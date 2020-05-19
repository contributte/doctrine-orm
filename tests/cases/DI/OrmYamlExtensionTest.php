<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\ORM\DI\OrmYamlExtension;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class OrmYamlExtensionTest extends TestCase
{

	public function testSimpleDriver(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.yaml', new OrmYamlExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.yaml:
					mapping:
						App\Model\Entity: %appDir%
				'));
			})
			->build();

		/** @var MappingDriverChain $driver */
		$driver = $container->getService('nettrine.orm.mappingDriver');

		/** @var SimplifiedYamlDriver $xmlDriver */
		$xmlDriver = current($driver->getDrivers());

		$this->assertInstanceOf(SimplifiedYamlDriver::class, $xmlDriver);
		$this->assertEmpty($xmlDriver->getAllClassNames());
	}

	public function testMissingMapping(): void
	{
		$this->expectException(InvalidConfigurationException::class);
		$this->expectDeprecationMessage("The mandatory option 'nettrine.orm.yaml › mapping' is missing.");

		ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.yaml', new OrmYamlExtension());
			})
			->build();
	}

}
