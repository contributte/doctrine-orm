<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Traits;

use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
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

		/** @var ServiceDefinition $annotationDriver */
		$annotationDriver = $builder->getDefinition(current(array_keys($tagged)));
		$annotationDriver->addSetup('addPaths', [array_values($mapping)]);

		/** @var ServiceDefinition $chainDriver */
		$chainDriver = $builder->findByType(MappingDriverChain::class);
		$chainDriver = current(array_values($chainDriver));

		foreach (array_keys($mapping) as $namespace) {
			$chainDriver->addSetup('addDriver', [$annotationDriver, $namespace]);
		}
	}

}
