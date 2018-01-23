<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;
use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Nette\DI\CompilerExtension;
use Nette\DI\ServiceCreationException;
use Nette\DI\Statement;
use Nette\InvalidStateException;

class OrmConsoleExtension extends CompilerExtension
{

	/**
	 * @return void
	 */
	public function loadConfiguration(): void
	{
		if (!$this->compiler->getExtensions(OrmExtension::class)) {
			throw new InvalidStateException(
				sprintf('You should register %s before %s.', OrmExtension::class, get_class($this))
			);
		}

		if (!class_exists('Symfony\Component\Console\Application'))
			throw new ServiceCreationException('Missing Symfony\Component\Console\Application service');

		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli')
			return;

		$builder = $this->getContainerBuilder();
		// Helpers
		$builder->addDefinition($this->prefix('entityManagerHelper'))
			->setClass(EntityManagerHelper::class)
			->setAutowired(FALSE);

		// Commands
		$builder->addDefinition($this->prefix('schemaToolCreateCommand'))
			->setClass(CreateCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('schemaToolUpdateCommand'))
			->setClass(UpdateCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('schemaToolDropCommand'))
			->setClass(DropCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('convertMappingCommand'))
			->setClass(ConvertMappingCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('ensureProductionSettingsCommand'))
			->setClass(EnsureProductionSettingsCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('generateEntitiesCommand'))
			->setClass(GenerateEntitiesCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('generateProxiesCommand'))
			->setClass(GenerateProxiesCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('generateRepositoriesCommand'))
			->setClass(GenerateRepositoriesCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('infoCommand'))
			->setClass(InfoCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('mappingDescribeCommand'))
			->setClass(MappingDescribeCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('runDqlCommand'))
			->setClass(RunDqlCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('validateSchemaCommand'))
			->setClass(ValidateSchemaCommand::class)
			->setAutowired(FALSE);
	}

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforeCompile(): void
	{
		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli')
			return;

		$builder = $this->getContainerBuilder();

		// Lookup for Symfony Console Application
		$application = $builder->getDefinitionByType('Symfony\Component\Console\Application');

		// Register helpers
		$entityManagerHelper = $this->prefix('@entityManagerHelper');
		$application->addSetup(new Statement('$service->getHelperSet()->set(?,?)', [$entityManagerHelper, 'em']));
	}

}
