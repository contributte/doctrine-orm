<?php declare(strict_types = 1);

/**
 * Test: DI\OrmExtension
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\EntityManager;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->addExtension('orm', new OrmExtension());
		$compiler->addConfig([
			'parameters' => [
				'tempDir' => TEMP_DIR,
				'appDir' => __DIR__,
			],
		]);

		$compiler->getContainerBuilder()
			->addDefinition('reader')
			->setClass(AnnotationReader::class);
	}, '1a');

	/** @var Container $container */
	$container = new $class;

	/** @var EntityManager $em */
	$em = $container->getByType(EntityManager::class);
	Assert::type(EntityManager::class, $em);
});
