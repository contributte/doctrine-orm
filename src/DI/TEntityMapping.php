<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Nette\DI\ContainerBuilder;

trait TEntityMapping
{

	/**
	 * @param string[] $mapping
	 */
	public function setEntityMappings(array $mapping): void
	{
		/** @var ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();

		$driver = $builder->getDefinitionByType(AnnotationDriver::class);
		$driver->addSetup('addPaths', [$mapping]);
	}

}
