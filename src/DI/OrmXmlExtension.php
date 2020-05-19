<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmXmlExtension extends AbstractExtension
{

	public const DRIVER_TAG = 'nettrine.orm.xml.driver';

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'mapping' => Expect::arrayOf('string')->required(),
			'fileExtension' => Expect::string(XmlDriver::DEFAULT_FILE_EXTENSION),
			'simple' => Expect::bool(false),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		// Validates needed extension
		$this->validate();

		if ($this->config->simple) {
			$this->loadSimpleConfiguration();
		} else {
			$this->loadStandardConfiguration();
		}
	}

	protected function loadStandardConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$driverDef = $builder->addDefinition($this->prefix('xmlDriver'))
			->setFactory(XmlDriver::class, [
				[],
				$config->fileExtension,
			])
			->setType(XmlDriver::class)
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriverDef = $this->getMappingDriverDef();
		foreach ($config->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new InvalidStateException(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$driverDef->addSetup(new Statement('$service->getLocator()->addPaths([?])', [$path]));
			$mappingDriverDef->addSetup('addDriver', [$this->prefix('@xmlDriver'), $namespace]);
		}
	}

	protected function loadSimpleConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$driverDef = $builder->addDefinition($this->prefix('xmlDriver'))
			->setFactory(SimplifiedXmlDriver::class, [
				[],
				$config->fileExtension,
			])
			->setType(SimplifiedXmlDriver::class)
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriverDef = $this->getMappingDriverDef();
		foreach ($config->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new InvalidStateException(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$driverDef->addSetup(new Statement('$service->getLocator()->addNamespacePrefixes([? => ?])', [$path, $namespace]));
			$mappingDriverDef->addSetup('addDriver', [$this->prefix('@xmlDriver'), $namespace]);
		}
	}

}
