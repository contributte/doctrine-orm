<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\DI\Pass\AbstractPass;
use Nettrine\ORM\DI\Pass\ConsolePass;
use Nettrine\ORM\DI\Pass\DoctrinePass;
use Nettrine\ORM\DI\Pass\EventPass;
use Nettrine\ORM\DI\Pass\ManagerPass;
use stdClass;
use Tracy\Debugger;

/**
 * @property-read stdClass $config
 * @phpstan-type TManagerConfig object{
 *     entityManagerDecoratorClass: string,
 *     configurationClass: string,
 *     proxyDir: string|null,
 *     autoGenerateProxyClasses: int|bool|Statement,
 *     proxyNamespace: string|null,
 *     metadataDriverImpl: string,
 *     entityNamespaces: array<string, string>,
 *     resolveTargetEntities: array<string, string>,
 *     customStringFunctions: array<string, string>,
 *     customNumericFunctions: array<string, string>,
 *     customDatetimeFunctions: array<string, string>,
 *     customHydrationModes: array<string, string>,
 *     classMetadataFactoryName: string,
 *     defaultRepositoryClassName: string,
 *     namingStrategy: string|Statement|null,
 *     quoteStrategy: string|Statement|null,
 *     entityListenerResolver: string|Statement|null,
 *     repositoryFactory: string|Statement|null,
 *     defaultQueryHints: array<string, mixed>,
 *     filters: array<string, object{class: string, enabled: bool}>,
 *     mapping: array<string, object{type: 'attributes'|'xml', directories: string[], namespace: string}>,
 *     defaultCache: string|Statement|null,
 *     queryCache: string|Statement|null,
 *     resultCache: string|Statement|null,
 *     hydrationCache: string|Statement|null,
 *     metadataCache: string|Statement|null,
 *     connection: string,
 *     secondLevelCache: object{
 *      enabled: bool,
 *      cache: string|Statement|null,
 *      logger: string|Statement|null,
 *      regions: array<string, object{lifetime: int, lockLifetime: int}>
 *    }
 *  }
 */
final class OrmExtension extends CompilerExtension
{

	public const MAPPING_DRIVER_TAG = 'nettrine.orm.mapping_driver';
	public const MANAGER_TAG = 'nettrine.orm.manager';
	public const MANAGER_DECORATOR_TAG = 'nettrine.orm.manager_decorator';
	public const CONFIGURATION_TAG = 'nettrine.orm.configuration';

	/** @var AbstractPass[] */
	protected array $passes = [];

	public function __construct(
		private ?bool $debugMode = null
	)
	{
		if ($this->debugMode === null) {
			$this->debugMode = class_exists(Debugger::class) && Debugger::$productionMode === false;
		}

		$this->passes[] = new DoctrinePass($this);
		$this->passes[] = new ConsolePass($this);
		$this->passes[] = new EventPass($this);
		$this->passes[] = new ManagerPass($this, $this->debugMode);
	}

	public function getConfigSchema(): Schema
	{
		$parameters = $this->getContainerBuilder()->parameters;
		$proxyDir = isset($parameters['tempDir']) ? $parameters['tempDir'] . '/proxies' : null;
		$autoGenerateProxy = boolval($parameters['debugMode'] ?? true);

		$expectService = Expect::anyOf(
			Expect::string()->required()->assert(fn ($input) => str_starts_with($input, '@') || class_exists($input) || interface_exists($input)),
			Expect::type(Statement::class)->required(),
		);

		return Expect::structure([
			'managers' => Expect::arrayOf(
				Expect::structure([
					'connection' => Expect::string()->required(),
					'entityManagerDecoratorClass' => Expect::string()->assert(fn ($input) => is_a($input, EntityManagerDecorator::class, true), 'EntityManager decorator class must be subclass of ' . EntityManagerDecorator::class),
					'configurationClass' => Expect::string(Configuration::class)->assert(fn ($input) => is_a($input, Configuration::class, true), 'Configuration class must be subclass of ' . Configuration::class),
					'proxyDir' => Expect::string()->default($proxyDir)->before(fn (mixed $v) => $v ?? $proxyDir)->assert(fn (mixed $v) => !($v === null || $v === ''), 'proxyDir must be filled'),
					'autoGenerateProxyClasses' => Expect::anyOf(Expect::int(), Expect::bool(), Expect::type(Statement::class))->default($autoGenerateProxy),
					'proxyNamespace' => Expect::string('Nettrine\Proxy')->nullable(),
					'metadataDriverImpl' => Expect::string(),
					'entityNamespaces' => Expect::array(),
					'resolveTargetEntities' => Expect::array(),
					'customStringFunctions' => Expect::array(),
					'customNumericFunctions' => Expect::array(),
					'customDatetimeFunctions' => Expect::array(),
					'customHydrationModes' => Expect::array(),
					'classMetadataFactoryName' => Expect::string(),
					'defaultRepositoryClassName' => Expect::string(),
					'namingStrategy' => (clone $expectService)->default(UnderscoreNamingStrategy::class),
					'quoteStrategy' => (clone $expectService),
					'entityListenerResolver' => (clone $expectService),
					'repositoryFactory' => (clone $expectService),
					'defaultQueryHints' => Expect::array(),
					'filters' => Expect::arrayOf(
						Expect::structure([
							'class' => Expect::string()->required(),
							'enabled' => Expect::bool(false),
						])
					),
					'mapping' => Expect::arrayOf(
						Expect::structure([
							'type' => Expect::anyOf('attributes', 'xml')->default('attributes'),
							'directories' => Expect::listOf(Expect::string())->min(1)->required(),
							'namespace' => Expect::string()->required(),
						]),
						Expect::string()
					)->required()->assert(fn ($input) => count($input) > 0, 'At least one mapping must be defined'),
					'defaultCache' => (clone $expectService),
					'queryCache' => (clone $expectService),
					'resultCache' => (clone $expectService),
					'hydrationCache' => (clone $expectService),
					'metadataCache' => (clone $expectService),
					'secondLevelCache' => Expect::structure([
						'enabled' => Expect::bool()->default(false),
						'cache' => (clone $expectService),
						'logger' => (clone $expectService),
						'regions' => Expect::arrayOf(
							Expect::structure([
								'lifetime' => Expect::int()->required(),
								'lockLifetime' => Expect::int()->required(),
							]),
							Expect::string()->required()
						),
					]),
				])->required(),
				Expect::string()->required()
			),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->loadPassConfiguration();
		}
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->beforePassCompile();
		}
	}

	public function afterCompile(ClassType $class): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->afterPassCompile($class);
		}
	}

}
