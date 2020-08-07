<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Configuration;

use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Nette\DI\Compiler;
use Tests\Toolkit\Neon\NeonLoader;
use Tests\Toolkit\Testcase\AbstractConfigurationTest;

final class QuoteStrategyTest extends AbstractConfigurationTest
{

	public function testDefault(): void
	{
		$configuration = $this->createConfiguration();
		$this->assertInstanceOf(DefaultQuoteStrategy::class, $configuration->getQuoteStrategy());
	}

	public function testString(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							quoteStrategy: Doctrine\ORM\Mapping\AnsiQuoteStrategy
				'));
		});
		$this->assertInstanceOf(AnsiQuoteStrategy::class, $configuration->getQuoteStrategy());
	}

	public function testStatement(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					nettrine.orm:
						configuration:
							quoteStrategy: Doctrine\ORM\Mapping\AnsiQuoteStrategy()
				'));
		});
		$this->assertInstanceOf(AnsiQuoteStrategy::class, $configuration->getQuoteStrategy());
	}

	public function testReference(): void
	{
		$configuration = $this->createConfiguration(function (Compiler $compiler): void {
			$compiler->addConfig(NeonLoader::load('
					services:
						reference: Doctrine\ORM\Mapping\AnsiQuoteStrategy()

					nettrine.orm:
						configuration:
							quoteStrategy: @reference
				'));
		});
		$this->assertInstanceOf(AnsiQuoteStrategy::class, $configuration->getQuoteStrategy());
	}

}
