<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\ORM\DI\OrmXmlExtension;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class OrmXmlExtensionTest extends TestCase
{

	public function testStandardDriver(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.xml:
					simple: false
					mapping:
						App\Model\Entity: %appDir%
				'));
			})
			->build();

		/** @var MappingDriverChain $driver */
		$driver = $container->getService('nettrine.orm.mappingDriver');

		/** @var XmlDriver $xmlDriver */
		$xmlDriver = current($driver->getDrivers());

		$this->assertInstanceOf(XmlDriver::class, $xmlDriver);
		$this->assertEmpty($xmlDriver->getAllClassNames());
	}

	public function testSimpleDriver(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.xml:
					simple: true
					mapping:
						App\Model\Entity: %appDir%
				'));
			})
			->build();

		/** @var MappingDriverChain $driver */
		$driver = $container->getService('nettrine.orm.mappingDriver');

		/** @var SimplifiedXmlDriver $xmlDriver */
		$xmlDriver = current($driver->getDrivers());

		$this->assertInstanceOf(SimplifiedXmlDriver::class, $xmlDriver);
		$this->assertEmpty($xmlDriver->getAllClassNames());
	}

	public function testMissingMapping(): void
	{
		$this->expectException(InvalidConfigurationException::class);
		$this->expectDeprecationMessage("The mandatory option 'nettrine.orm.xml › mapping' is missing.");

		ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
			})
			->build();
	}

}
