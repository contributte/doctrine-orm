<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Nette\DI\Compiler;
use Nettrine\ORM\Exception\Logical\InvalidArgumentException;
use Tester\Assert;
use Tests\Fixtures\Dummy\DummyConfiguration;
use Tests\Fixtures\Dummy\DummyEntityManagerDecorator;
use Tests\Fixtures\Dummy\DummyFilter;
use Tests\Toolkit\Container;

require_once __DIR__ . '/../../bootstrap.php';

// Ok
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	Assert::type(EntityManagerDecorator::class, $container->getService('nettrine.orm.entityManagerDecorator'));
});

// Custom entity manager
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig([
				'nettrine.orm' => [
					'entityManagerDecoratorClass' => DummyEntityManagerDecorator::class,
					'configurationClass' => DummyConfiguration::class,
				],
			]);
		})
		->build();

	Assert::type(DummyEntityManagerDecorator::class, $container->getByType(DummyEntityManagerDecorator::class));
	Assert::type(DummyConfiguration::class, $container->getByType(DummyConfiguration::class));
});

// Filters
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig([
				'nettrine.orm' => [
					'configuration' => [
						'filters' => [
							'autoEnabledFilter' => [
								'class' => DummyFilter::class,
								'enabled' => true,
							],
							'autoDisabledFilter' => [
								'class' => DummyFilter::class,
							],
						],
					],
				],
			]);
		})
		->build();
	/** @var EntityManagerDecorator $em */
	$em = $container->getService('nettrine.orm.entityManagerDecorator');
	$filters = $em->getFilters();

	Assert::equal(true, $filters->has('autoEnabledFilter'));
	Assert::equal(true, $filters->isEnabled('autoEnabledFilter'));

	Assert::equal(true, $filters->has('autoDisabledFilter'));
	Assert::equal(false, $filters->isEnabled('autoDisabledFilter'));
});

// Error (configuration subclass)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig([
					'nettrine.orm' => [
						'configurationClass' => stdClass::class,
					],
				]);
			})
			->build();
	}, InvalidArgumentException::class, 'Configuration class must be subclass of Doctrine\ORM\Configuration, stdClass given.');
});
