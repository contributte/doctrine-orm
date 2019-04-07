<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Helpers;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\VoidCache;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\ORM\Exception\Logical\InvalidStateException;

/**
 * @mixin CompilerExtension
 */
class CacheBuilder
{

	private const DRIVERS = [
		'apcu' => ApcuCache::class,
		'array' => ArrayCache::class,
		'filesystem' => FilesystemCache::class,
		'memcached' => MemcachedCache::class,
		'redis' => RedisCache::class,
		'void' => VoidCache::class,
	];

	/** @var CompilerExtension */
	private $extension;

	/** @var string */
	private $default = 'filesystem';

	private function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
	}

	public static function of(CompilerExtension $extension): self
	{
		return new self($extension);
	}

	public function withDefault(string $driver): self
	{
		if (!isset(self::DRIVERS[$driver])) {
			throw new InvalidStateException(sprintf('Unsupported default cache driver "%s"', $driver));
		}

		$this->default = $driver;

		return $this;
	}

	public function getDefinition(string $service): ServiceDefinition
	{
		$builder = $this->extension->getContainerBuilder();

		$def = $builder->addDefinition($this->extension->prefix($service))
			->setFactory(self::DRIVERS[$this->default])
			->setAutowired(false);

		if ($this->default === 'filesystem') {
			$def->setArguments([$builder->parameters['tempDir'] . '/cache/nettrine.cache.' . ucfirst($service)]);
		}

		return $def;
	}

}
