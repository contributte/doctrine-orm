<?php declare(strict_types = 1);

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\DI\OrmXmlExtension;
use Nettrine\ORM\DI\OrmYamlExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\EntityMappingCompilerExtension;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('nettrine.orm.annotations', new OrmAnnotationsExtension());
			$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
			$compiler->addExtension('nettrine.orm.yaml', new OrmYamlExtension());
			$compiler->addExtension('tests.mapping', new EntityMappingCompilerExtension());
			$compiler->addConfig(Helpers::neon('
					nettrine.orm.annotations:
						mapping:
							App\Model\Entity1: %appDir%
					nettrine.orm.xml:
						mapping:
							App\Model\Entity2: %appDir%
					nettrine.orm.yaml:
						mapping:
							App\Model\Entity3: %appDir%
				'));
		})
		->build();

	/** @var AnnotationDriver $annotationDriver */
	$annotationDriver = $container->getService(current(array_keys($container->findByTag(OrmAnnotationsExtension::DRIVER_TAG))));

	Assert::equal([
		Tests::APP_PATH,
		Tests::FIXTURES_PATH,
	], array_values($annotationDriver->getPaths()));

	/** @var XmlDriver $xmlDriver */
	$xmlDriver = $container->getService(current(array_keys($container->findByTag(OrmXmlExtension::DRIVER_TAG))));

	Assert::equal([
		Tests::APP_PATH,
		Tests::FIXTURES_PATH,
	], array_values($xmlDriver->getLocator()->getPaths()));

	/** @var SimplifiedYamlDriver $yamlDriver */
	$yamlDriver = $container->getService(current(array_keys($container->findByTag(OrmYamlExtension::DRIVER_TAG))));

	Assert::equal([
		Tests::APP_PATH,
		Tests::FIXTURES_PATH,
	], array_values($yamlDriver->getLocator()->getPaths()));

	/** @var MappingDriverChain $chainDriver */
	$chainDriver = $container->getService('nettrine.orm.mappingDriver');
	Assert::count(6, $chainDriver->getDrivers());
});
