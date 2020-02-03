<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Contributte\DI\Extension\CompilerExtension;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use stdClass;

/**
 * @property-read stdClass $config
 */
abstract class AbstractExtension extends CompilerExtension
{

	public function validate(): void
	{
		if ($this->compiler->getExtensions(OrmExtension::class) === []) {
			throw new InvalidStateException(
				sprintf('You should register %s before %s.', OrmExtension::class, static::class)
			);
		}
	}

	protected function getConfigurationDef(): ServiceDefinition
	{
		/** @var ServiceDefinition $def */
		$def = $this->getContainerBuilder()->getDefinitionByType(Configuration::class);

		return $def;
	}

	protected function getMappingDriverDef(): ServiceDefinition
	{
		/** @var ServiceDefinition $def */
		$def = $this->getContainerBuilder()->getDefinitionByType(MappingDriverChain::class);

		return $def;
	}

}
