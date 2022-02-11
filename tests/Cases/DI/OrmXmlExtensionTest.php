<?php declare(strict_types = 1);

use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\ORM\DI\OrmXmlExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Standard driver
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
			$compiler->addConfig(Helpers::neon('
				nettrine.orm.xml:
					simple: false
					mapping:
						App\Model\Entity: %appDir%
				'));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.mappingDriver');

	/** @var XmlDriver $xmlDriver */
	$xmlDriver = current($driver->getDrivers());

	Assert::type(XmlDriver::class, $xmlDriver);
	Assert::equal([], $xmlDriver->getAllClassNames());
});

// Simple driver
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
			$compiler->addConfig(Helpers::neon('
				nettrine.orm.xml:
					simple: true
					mapping:
						App\Model\Entity: %appDir%
				'));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.mappingDriver');

	/** @var SimplifiedXmlDriver $xmlDriver */
	$xmlDriver = current($driver->getDrivers());

	Assert::type(SimplifiedXmlDriver::class, $xmlDriver);
	Assert::equal([], $xmlDriver->getAllClassNames());
});

// Error (missing mapping)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
			})
			->build();
	}, InvalidConfigurationException::class, "The mandatory item 'nettrine.orm.xml › mapping' is missing.");
});
