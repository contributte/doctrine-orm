<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmYamlExtension extends AbstractExtension
{

	public const DRIVER_TAG = 'nettrine.orm.yaml.driver';

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'mapping' => Expect::arrayOf('string')->required(),
			'fileExtension' => Expect::string(SimplifiedYamlDriver::DEFAULT_FILE_EXTENSION),
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

		$driverDef = $builder->addDefinition($this->prefix('yamlDriver'))
			->setFactory(SimplifiedYamlDriver::class, [
				[],
				$config->fileExtension,
			])
			->setType(SimplifiedYamlDriver::class)
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriverDef = $this->getMappingDriverDef();
		foreach ($config->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new InvalidStateException(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$driverDef->addSetup(new Statement('$service->getLocator()->addNamespacePrefixes([? => ?])', [$path, $namespace]));
			$mappingDriverDef->addSetup('addDriver', [$this->prefix('@yamlDriver'), $namespace]);
		}
	}

}
