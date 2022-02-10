<?php declare(strict_types = 1);

namespace Tests\Casesě\DI\Configuration;

use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Nette\DI\Compiler;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../../bootstrap.php';

// Default
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration();
	Assert::type(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
});

// Configuration (string)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					repositoryFactory: Doctrine\ORM\Repository\DefaultRepositoryFactory
		'));
	});
	Assert::type(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
});

// Configuration (statement)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					repositoryFactory: Doctrine\ORM\Repository\DefaultRepositoryFactory()
		'));
	});
	Assert::type(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
});

// Configuration (reference)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			services:
				reference: Doctrine\ORM\Repository\DefaultRepositoryFactory()

			nettrine.orm:
				configuration:
					repositoryFactory: @reference
		'));
	});
	Assert::type(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
});
