<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\ServiceCreationException;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\Mapping\AnnotationDriver;
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
					namespaces:
						- App\Model\Entity
					paths:
						- %appDir%
				'));
			})
			->build();

		/** @var MappingDriverChain $driver */
		$driver = $container->getService('nettrine.orm.mappingDriver');

		$this->assertInstanceOf(AnnotationDriver::class, current($driver->getDrivers()));
	}

	public function testNoReader(): void
	{
		$this->expectException(ServiceCreationException::class);
		$this->expectExceptionMessageMatches("#Service 'nettrine.orm.annotations.annotationDriver' .+#");

		ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.annotations:
					namespaces:
						- App\Model\Entity
					paths:
						- %appDir%
				'));
			})
			->build();
	}

}
