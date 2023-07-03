<?php declare(strict_types = 1);

namespace Nettrine\ORM\Mapping;

use Doctrine\ORM\Mapping\EntityListenerResolver;
use Nette\DI\Container;
use Nettrine\ORM\Exception\Logical\InvalidArgumentException;

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
	public function register(mixed $object): void
	{
		if (!is_object($object)) {
			throw new InvalidArgumentException(sprintf('An object was expected, but got "%s".', gettype($object)));
		}

		$this->instances[$object::class] = $object;
	}

	/**
	 * {@inheritDoc}
	 */
	public function resolve($className): object
	{
		$className = trim($className, '\\');

		if (isset($this->instances[$className])) {
			return $this->instances[$className];
		}

		$service = $this->container->getByType($className, false);

		$this->instances[$className] = $service ?: new $className();

		return $this->instances[$className];
	}

}
