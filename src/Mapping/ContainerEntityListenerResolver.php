<?php declare(strict_types = 1);

namespace Nettrine\ORM\Mapping;

use Doctrine\ORM\Mapping\EntityListenerResolver;
use Nette\DI\Container;
use Nettrine\ORM\Exception\Logical\InvalidArgumentException;

class ContainerEntityListenerResolver implements EntityListenerResolver
{

	/** @var Container */
	private $container;

	/** @var object[] */
	protected $instances = [];

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string|NULL $className
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
	 * @param object|mixed $object
	 */
	public function register($object): void
	{
		if (!is_object($object)) {
			throw new InvalidArgumentException(sprintf('An object was expected, but got "%s".', gettype($object)));
		}

		$this->instances[get_class($object)] = $object;
	}

	/**
	 * @param string $className
	 * @return object
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	 */
	public function resolve($className)
	{
		/** @var class-string $className */
		$className = trim($className, '\\');

		if (isset($this->instances[$className])) {
			return $this->instances[$className];
		}

		$service = $this->container->getByType($className, false);

		if ($service) {
			$this->instances[$className] = $service;
		} else {
			$this->instances[$className] = new $className();
		}

		return $this->instances[$className];
	}

}
