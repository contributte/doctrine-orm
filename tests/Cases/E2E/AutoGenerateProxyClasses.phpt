<?php declare(strict_types = 1);

namespace Tests\Casesě\E2E;

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\Configuration;
use Nette\DI\Compiler;
use Ninjify\Nunjuck\Toolkit;
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

	Assert::equal(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS, $configuration->getAutoGenerateProxyClasses());
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

	Assert::equal(AbstractProxyFactory::AUTOGENERATE_EVAL, $configuration->getAutoGenerateProxyClasses());
});

// Statement
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon('
					nettrine.orm:
						configuration:
							autoGenerateProxyClasses: ::constant(Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_NEVER)
				'));
		})
		->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByType(Configuration::class);

	Assert::equal(AbstractProxyFactory::AUTOGENERATE_NEVER, $configuration->getAutoGenerateProxyClasses());
});
