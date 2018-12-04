<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nettrine\ORM\Exception\Logical\InvalidStateException;

class OrmXmlExtension extends CompilerExtension
{

	/** @var mixed[] */
	public $defaults = [
		'paths' => [], //'%appDir%'
		'fileExtension' => XmlDriver::DEFAULT_FILE_EXTENSION,
	];

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		if (!$this->compiler->getExtensions(OrmExtension::class)) {
			throw new InvalidStateException(
				sprintf('You should register %s before %s.', self::class, static::class)
			);
		}

		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$builder->addDefinition($this->prefix('xmlDriver'))
			->setFactory(XmlDriver::class, [
				Helpers::expand($config['paths'], $builder->parameters),
				$config['fileExtension']
			]);

		$builder->getDefinitionByType(Configuration::class)
			->addSetup('setMetadataDriverImpl', [$this->prefix('@xmlDriver')]);
	}

}
