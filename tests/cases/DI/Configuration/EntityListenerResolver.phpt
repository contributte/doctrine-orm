<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Configuration;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Nette\DI\Compiler;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../../bootstrap.php';

// Default
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration();
	Assert::type(ContainerEntityListenerResolver::class, $configuration->getEntityListenerResolver());
});

// Configuration (string)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					entityListenerResolver: Doctrine\ORM\Mapping\DefaultEntityListenerResolver
		'));
	});
	Assert::type(DefaultEntityListenerResolver::class, $configuration->getEntityListenerResolver());
});

// Configuration (statement)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					entityListenerResolver: Doctrine\ORM\Mapping\DefaultEntityListenerResolver()
		'));
	});
	Assert::type(DefaultEntityListenerResolver::class, $configuration->getEntityListenerResolver());
});

// Configuration (reference)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			services:
				reference: Doctrine\ORM\Mapping\DefaultEntityListenerResolver()

			nettrine.orm:
				configuration:
					entityListenerResolver: @reference
		'));
	});
	Assert::type(DefaultEntityListenerResolver::class, $configuration->getEntityListenerResolver());
});
