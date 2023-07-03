<?php declare(strict_types = 1);

namespace Nettrine\ORM\Mapping;

use Doctrine\ORM\Mapping\EntityListenerResolver;
use Nette\DI\Container;

class ContainerEntityListenerResolver implements EntityListenerResolver
{

	/** @var object[] */
	protected array $instances = [];

	private Container $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear($className = null): void
	{
		if ($className === null) {
			$this->instances = [];

			return;
		}

		if (isset($this->instances[$className = trim($className, '\\')])) {
			unset($this->instances[$className]);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function register($object): void
	{
		$this->instances[$object::class] = $object;
	}

	/**
	 * {@inheritDoc}
	 */
	public function resolve($className): object
	{
		/** @var class-string<object> $className */
		$className = trim($className, '\\');

		if (isset($this->instances[$className])) {
			return $this->instances[$className];
		}

		$service = $this->container->getByType($className, false);

		$this->instances[$className] = $service ?? new $className();

		return $this->instances[$className];
	}

}
