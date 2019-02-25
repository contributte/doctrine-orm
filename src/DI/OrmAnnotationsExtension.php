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
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\ServiceDefinition;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Utils\Validators;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Nettrine\ORM\Mapping\AnnotationDriver;

class OrmAnnotationsExtension extends CompilerExtension
{

	public const DRIVERS = [
		'apc' => ApcCache::class,
		'apcu' => ApcuCache::class,
		'array' => ArrayCache::class,
		'filesystem' => FilesystemCache::class,
		'memcache' => MemcacheCache::class,
		'memcached' => MemcachedCache::class,
		'redis' => RedisCache::class,
		'void' => VoidCache::class,
		'xcache' => XcacheCache::class,
	];

	/** @var mixed[] */
	public $defaults = [
		'paths' => [], //'%appDir%'
		'excludePaths' => [],
		'ignore' => [],
		'defaultCache' => 'filesystem',
		'cache' => null,
		'debug' => false,
	];

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		if ($this->compiler->getExtensions(OrmExtension::class) === []) {
			throw new InvalidStateException(
				sprintf('You should register %s before %s.', self::class, static::class)
			);
		}

		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$reader = $builder->addDefinition($this->prefix('annotationReader'))
			->setType(AnnotationReader::class)
			->setAutowired(false);

		Validators::assertField($config, 'ignore', 'array');

		foreach ($config['ignore'] as $annotationName) {
			$reader->addSetup('addGlobalIgnoredName', [$annotationName]);
			AnnotationReader::addGlobalIgnoredName($annotationName);
		}

		if ($config['cache'] === null && $config['defaultCache'] !== null) {
			$this->getDefaultCache()
				->setAutowired(false);
		} elseif ($config['cache'] !== null) {
			$builder->addDefinition($this->prefix('annotationsCache'))
				->setFactory($config['cache'])
				->setAutowired(false);
		} else {
			throw new InvalidStateException('Cache or defaultCache must be provided');
		}

		$builder->addDefinition($this->prefix('reader'))
			->setType(Reader::class)
			->setFactory(CachedReader::class, [
				$this->prefix('@annotationReader'),
				$this->prefix('@annotationsCache'),
				$config['debug'],
			]);

		$builder->addDefinition($this->prefix('annotationDriver'))
			->setFactory(AnnotationDriver::class, [$this->prefix('@reader'), Helpers::expand($config['paths'], $builder->parameters)])
			->addSetup('addExcludePaths', [Helpers::expand($config['excludePaths'], $builder->parameters)]);

		$builder->getDefinitionByType(Configuration::class)
			->addSetup('setMetadataDriverImpl', [$this->prefix('@annotationDriver')]);

		AnnotationRegistry::registerUniqueLoader('class_exists');
	}

	public function afterCompile(ClassType $classType): void
	{
		$initialize = $classType->getMethod('initialize');
		$original = (string) $initialize->getBody();
		$initialize->setBody('?::registerUniqueLoader("class_exists");' . "\n", [new PhpLiteral(AnnotationRegistry::class)]);
		$initialize->addBody($original);
	}

	protected function getDefaultCache(): ServiceDefinition
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		if (!isset(self::DRIVERS[$config['defaultCache']])) {
			throw new InvalidStateException(sprintf('Unsupported default cache driver "%s"', $config['defaultCache']));
		}

		$driverCache = $builder->addDefinition($this->prefix('annotationsCache'))
			->setFactory(self::DRIVERS[$config['defaultCache']])
			->setAutowired(false);

		if ($config['defaultCache'] === 'filesystem') {
			$driverCache->setArguments([$builder->parameters['tempDir'] . '/cache/Doctrine.Annotations']);
		}

		return $driverCache;
	}

}
