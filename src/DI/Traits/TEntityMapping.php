<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Traits;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\Exception\Logical\InvalidStateException;

/**
 * @mixin CompilerExtension
 */
trait TEntityMapping
{

	/**
	 * @param string[] $mapping
	 */
	public function setEntityMappings(array $mapping): void
	{
		$builder = $this->getContainerBuilder();

		$tagged = $builder->findByTag(OrmAnnotationsExtension::DRIVER_TAG);

		if (!$tagged) {
			throw new InvalidStateException('AnnotationDriver not found');
		}

		/** @var ServiceDefinition $driver */
		$driver = $builder->getDefinition(current(array_keys($tagged)));
		$driver->addSetup('addPaths', [$mapping]);
	}

}
