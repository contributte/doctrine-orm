<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Nette\DI\Compiler;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\Mapping\AnnotationDriver;
use Tests\Fixtures\EntityMappingCompilerExtension;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;
use Tests\Toolkit\Tests;

final class OrmAnnotationsExtensionEntityMappingTest extends TestCase
{

	public function testTrait(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('annotations', new AnnotationsExtension());
				$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
				$compiler->addExtension('tests.mapping', new EntityMappingCompilerExtension());
				$compiler->addConfig(NeonLoader::load('
				nettrine.orm.annotations:
					namespaces:
						- App\Model\Entity
					paths:
						- %appDir%
				'));
			})
			->build();

		/** @var AnnotationDriver $driver */
		$driver = $container->getService('nettrine.orm.annotations.annotationDriver');

		$this->assertEquals([
			Tests::APP_PATH,
			Tests::FIXTURES_PATH,
		], array_values($driver->getPaths()));
	}

}
