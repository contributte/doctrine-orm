<?php declare(strict_types = 1);

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nette\DI\ServiceCreationException;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Ok
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
			$compiler->addConfig(Helpers::neon('
				nettrine.orm.annotations:
					mapping:
						App\Model\Entity: %appDir%
				'));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.mappingDriver');

	Assert::type(AnnotationDriver::class, current($driver->getDrivers()));

	/** @var AnnotationDriver $annotationDriver */
	$annotationDriver = current($driver->getDrivers());
	Assert::equal([], $annotationDriver->getAllClassNames());
});

// Error (missing mapping)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
			})
			->build();
	}, InvalidConfigurationException::class, "The mandatory item 'nettrine.orm.annotations › mapping' is missing.");
});


// Error (no reader)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
				$compiler->addConfig(Helpers::neon('
				nettrine.orm.annotations:
					mapping:
						App\Model\Entity: %appDir%
				'));
			})
			->build();
	}, ServiceCreationException::class);
});
