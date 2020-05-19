<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nette\DI\ServiceCreationException;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class OrmAnnotationsExtensionTest extends TestCase
{

	public function testOk(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('annotations', new AnnotationsExtension());
				$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.annotations:
					mapping:
						App\Model\Entity: %appDir%
				'));
			})
			->build();

		/** @var MappingDriverChain $driver */
		$driver = $container->getService('nettrine.orm.mappingDriver');

		$this->assertInstanceOf(AnnotationDriver::class, current($driver->getDrivers()));

		/** @var AnnotationDriver $annotationDriver */
		$annotationDriver = current($driver->getDrivers());
		$this->assertEmpty($annotationDriver->getAllClassNames());
	}

	public function testMissingMapping(): void
	{
		$this->expectException(InvalidConfigurationException::class);
		$this->expectDeprecationMessage("The mandatory option 'nettrine.orm.annotations › mapping' is missing.");

		ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
			})
			->build();
	}

	public function testNoReader(): void
	{
		$this->expectException(ServiceCreationException::class);

		ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.annotations:
					mapping:
						App\Model\Entity: %appDir%
				'));
			})
			->build();
	}

}
