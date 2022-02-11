<?php declare(strict_types = 1);

use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Nette\DI\Compiler;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../../bootstrap.php';

// Default
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration();
	Assert::type(DefaultQuoteStrategy::class, $configuration->getQuoteStrategy());
});

// Configuration (string)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					quoteStrategy: Doctrine\ORM\Mapping\AnsiQuoteStrategy
		'));
	});
	Assert::type(AnsiQuoteStrategy::class, $configuration->getQuoteStrategy());
});

// Configuration (statement)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			nettrine.orm:
				configuration:
					quoteStrategy: Doctrine\ORM\Mapping\AnsiQuoteStrategy()
		'));
	});
	Assert::type(AnsiQuoteStrategy::class, $configuration->getQuoteStrategy());
});

// Configuration (reference)
Toolkit::test(function (): void {
	$configuration = Helpers::createConfiguration(function (Compiler $compiler): void {
		$compiler->addConfig(Helpers::neon('
			services:
				reference: Doctrine\ORM\Mapping\AnsiQuoteStrategy()

			nettrine.orm:
				configuration:
					quoteStrategy: @reference
		'));
	});
	Assert::type(AnsiQuoteStrategy::class, $configuration->getQuoteStrategy());
});
