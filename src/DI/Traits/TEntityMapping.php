<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Traits;

use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;

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

		/** @var ServiceDefinition $driver */
		$driver = $builder->getDefinitionByType(AnnotationDriver::class);
		$driver->addSetup('addPaths', [$mapping]);
	}

}
