<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\Mapping\AnnotationDriver;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmAnnotationsExtension extends AbstractExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'paths' => Expect::listOf('string'),
			'excludePaths' => Expect::listOf('string'),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		// Validates needed extension
		$this->validate();

		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$builder->addDefinition($this->prefix('annotationDriver'))
			->setFactory(AnnotationDriver::class, [1 => $config->paths])
			->setType(MappingDriver::class)
			->addSetup('addExcludePaths', [$config->excludePaths]);

		$configurationDef = $this->getConfigurationDef();
		$configurationDef->addSetup('setMetadataDriverImpl', [$this->prefix('@annotationDriver')]);
	}

}
