<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Proxy\ProxyFactory;
use Nette\DI\Compiler;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Default
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByType(Configuration::class);

	Assert::equal(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS, $configuration->getAutoGenerateProxyClasses());
});

// Override
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon('
					nettrine.orm:
						configuration:
							autoGenerateProxyClasses: 3
				'));
		})
		->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByType(Configuration::class);

	Assert::equal(ProxyFactory::AUTOGENERATE_EVAL, $configuration->getAutoGenerateProxyClasses());
});

// Statement
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon('
					nettrine.orm:
						configuration:
							autoGenerateProxyClasses: ::constant(Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER)
				'));
		})
		->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByType(Configuration::class);

	Assert::equal(ProxyFactory::AUTOGENERATE_NEVER, $configuration->getAutoGenerateProxyClasses());
});
