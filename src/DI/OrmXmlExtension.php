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
			'paths' => Expect::listOf('string'),
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

		$configurationDef = $this->getConfigurationDef();
		$configurationDef->addSetup('setMetadataDriverImpl', [$this->prefix('@xmlDriver')]);
	}

}
