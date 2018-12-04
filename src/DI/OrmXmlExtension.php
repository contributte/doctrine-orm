<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\ServiceDefinition;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Utils\Validators;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Nettrine\ORM\Mapping\AnnotationDriver;

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
