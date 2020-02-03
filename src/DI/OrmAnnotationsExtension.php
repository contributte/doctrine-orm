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

	public const DRIVER_TAG = 'nettrine.orm.annotation.driver';

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'namespaces' => Expect::listOf('string')->required(),
			'paths' => Expect::listOf('string')->required(),
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
			->addSetup('addExcludePaths', [$config->excludePaths])
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriverDef = $this->getMappingDriverDef();
		foreach ($config->namespaces as $namespace) {
			$mappingDriverDef->addSetup('addDriver', [$this->prefix('@annotationDriver'), $namespace]);
		}
	}

}
