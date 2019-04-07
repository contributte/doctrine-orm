<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\DI\OrmXmlExtension;
use Tests\Toolkit\TestCase;

final class OrmXmlExtensionTest extends TestCase
{

	public function testExtension(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addExtension('orm', new OrmExtension());
			$compiler->addExtension('orm.xml', new OrmXmlExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => TEMP_PATH,
					'appDir' => __DIR__,
				],
			]);
		}, self::class . __METHOD__);

		/** @var Container $container */
		$container = new $class();

		$this->assertInstanceOf(XmlDriver::class, $container->getService('orm.xml.xmlDriver'));
	}

}
