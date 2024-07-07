<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Nette\DI\ServiceCreationException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
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
			'mapping' => Expect::arrayOf('string')->required(),
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

		$reader = $builder->getByType(Reader::class);

		if ($reader === null) {
			throw new ServiceCreationException(sprintf('Missing "%s" service', Reader::class));
		}

		$driverDef = $builder->addDefinition($this->prefix('annotationDriver'))
			->setFactory(AnnotationDriver::class, [$builder->getDefinition($reader)])
			->setType(AnnotationDriver::class)
			->addSetup('addExcludePaths', [$config->excludePaths])
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriverDef = $this->getMappingDriverDef();

		foreach ($config->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new InvalidStateException(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$driverDef->addSetup('addPaths', [[$path]]);
			$mappingDriverDef->addSetup('addDriver', [$this->prefix('@annotationDriver'), $namespace]);
		}
	}

}
