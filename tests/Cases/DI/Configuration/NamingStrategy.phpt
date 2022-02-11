<?php declare(strict_types = 1);

use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Nette\DI\Compiler;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../../bootstrap.php';

// Default
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration();
	Assert::type(UnderscoreNamingStrategy::class, $configuration->getNamingStrategy());
});

// Configuration (string)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy
		'));
	});
	Assert::type(UnderscoreNamingStrategy::class, $configuration->getNamingStrategy());
});

// Configuration (statement)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy()
		'));
	});
	Assert::type(UnderscoreNamingStrategy::class, $configuration->getNamingStrategy());
});

// Configuration (reference)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			services:
				reference: Doctrine\ORM\Mapping\UnderscoreNamingStrategy()

			nettrine.orm:
				configuration:
					namingStrategy: @reference
		'));
	});
	Assert::type(UnderscoreNamingStrategy::class, $configuration->getNamingStrategy());
});
