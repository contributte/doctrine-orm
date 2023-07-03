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
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string|NULL $className
	 */
	public function clear(?string $className = null): void
	{
		if ($className === null) {
			$this->instances = [];

			return;
		}

		if (isset($this->instances[$className = trim($className, '\\')])) {
			unset($this->instances[$className]);
		}
	}

	public function register(mixed $object): void
	{
		if (!is_object($object)) {
			throw new InvalidArgumentException(sprintf('An object was expected, but got "%s".', gettype($object)));
		}

		$this->instances[$object::class] = $object;
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	 */
	public function resolve(string $className): object
	{
		/** @var class-string $className */
		$className = trim($className, '\\');

		if (isset($this->instances[$className])) {
			return $this->instances[$className];
		}

		$service = $this->container->getByType($className, false);

		$this->instances[$className] = $service ?: new $className();

		return $this->instances[$className];
	}

}
