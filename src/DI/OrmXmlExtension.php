<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmXmlExtension extends AbstractExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'namespaces' => Expect::listOf('string')->required(),
			'paths' => Expect::listOf('string')->required(),
			'fileExtension' => Expect::string(XmlDriver::DEFAULT_FILE_EXTENSION),
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

		$builder->addDefinition($this->prefix('xmlDriver'))
			->setFactory(XmlDriver::class, [
				$config->paths,
				$config->fileExtension,
			]);

		$mappingDriverDef = $this->getMappingDriverDef();
		foreach ($config->namespaces as $namespace) {
			$mappingDriverDef->addSetup('addDriver', [$this->prefix('@xmlDriver'), $namespace]);
		}
	}

}
