<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand;
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
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\ServiceCreationException;
use Symfony\Component\Console\Application;

class OrmConsoleExtension extends AbstractExtension
{

	/** @var bool */
	private $cliMode;

	public function __construct(?bool $cliMode = null)
	{
		$this->cliMode = $cliMode ?? PHP_SAPI === 'cli';
	}

	public function loadConfiguration(): void
	{
		// Validates needed extension
		$this->validate();

		if (!class_exists(Application::class)) {
			throw new ServiceCreationException(sprintf('Missing "%s" service', Application::class));
		}

		// Skip if it's not CLI mode
		if (!$this->cliMode) {
			return;
		}

		$builder = $this->getContainerBuilder();

		// Helpers
		$builder->addDefinition($this->prefix('entityManagerHelper'))
			->setType(EntityManagerHelper::class)
			->setAutowired(false);

		// Commands
		$builder->addDefinition($this->prefix('schemaToolCreateCommand'))
			->setType(CreateCommand::class)
			->addTag('console.command', 'orm:schema-tool:create')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('schemaToolUpdateCommand'))
			->setType(UpdateCommand::class)
			->addTag('console.command', 'orm:schema-tool:update')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('schemaToolDropCommand'))
			->setType(DropCommand::class)
			->addTag('console.command', 'orm:schema-tool:drop')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('convertMappingCommand'))
			->setType(ConvertMappingCommand::class)
			->addTag('console.command', 'orm:convert-mapping')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('ensureProductionSettingsCommand'))
			->setType(EnsureProductionSettingsCommand::class)
			->addTag('console.command', 'orm:ensure-production-settings')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('generateEntitiesCommand'))
			->setType(GenerateEntitiesCommand::class)
			->addTag('console.command', 'orm:generate-entities')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('generateProxiesCommand'))
			->setType(GenerateProxiesCommand::class)
			->addTag('console.command', 'orm:generate-proxies')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('generateRepositoriesCommand'))
			->setType(GenerateRepositoriesCommand::class)
			->addTag('console.command', 'orm:generate-repositories')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('infoCommand'))
			->setType(InfoCommand::class)
			->addTag('console.command', 'orm:info')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('mappingDescribeCommand'))
			->setType(MappingDescribeCommand::class)
			->addTag('console.command', 'orm:mapping:describe')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('runDqlCommand'))
			->setType(RunDqlCommand::class)
			->addTag('console.command', 'orm:run-dql')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('validateSchemaCommand'))
			->setType(ValidateSchemaCommand::class)
			->addTag('console.command', 'orm:validate-schema')
			->setAutowired(false);
		$builder->addDefinition($this->prefix('clearMetadataCacheCommand'))
			->setType(MetadataCommand::class)
			->addTag('console.command', 'orm:clear-cache:metadata')
			->setAutowired(false);
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		// Skip if it's not CLI mode
		if (!$this->cliMode) {
			return;
		}

		$builder = $this->getContainerBuilder();

		// Lookup for Symfony Console Application
		/** @var ServiceDefinition $applicationDef */
		$applicationDef = $builder->getDefinitionByType(Application::class);

		// Register helpers
		$entityManagerHelper = $this->prefix('@entityManagerHelper');
		$applicationDef->addSetup(new Statement('$service->getHelperSet()->set(?,?)', [$entityManagerHelper, 'em']));
	}

}
