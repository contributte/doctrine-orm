<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmAttributesExtension extends AbstractExtension
{

	public const DRIVER_TAG = 'nettrine.orm.attribute.driver';

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

		$builder->addDefinition($this->prefix('attributeDriver'))
			->setFactory(AttributeDriver::class, [array_values($config->mapping)])
			->setType(AttributeDriver::class)
			->addSetup('addExcludePaths', [$config->excludePaths])
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriverDef = $this->getMappingDriverDef();
		foreach ($config->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new InvalidStateException(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$mappingDriverDef->addSetup('addDriver', [$this->prefix('@attributeDriver'), $namespace]);
		}
	}

}
