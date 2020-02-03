<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nettrine\ORM\DI\OrmXmlExtension;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class OrmXmlExtensionTest extends TestCase
{

	public function testOk(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.xml:
					namespaces:
						- App\Model\Entity
					paths:
						- %appDir%
				'));
			})
			->build();

		/** @var MappingDriverChain $driver */
		$driver = $container->getService('nettrine.orm.mappingDriver');

		$this->assertInstanceOf(XmlDriver::class, current($driver->getDrivers()));
	}

}
