<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Nette\DI\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\DI\Helpers\CacheBuilder;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Nettrine\ORM\Mapping\AnnotationDriver;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmAnnotationsExtension extends AbstractExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'debug' => Expect::bool(false),
			'cache' => Expect::anyOf(Expect::string(), Expect::null(), Expect::type(Statement::class)),
			'defaultCache' => Expect::string('filesystem')->nullable(),
			'paths' => Expect::listOf('string'),
			'excludePaths' => Expect::listOf('string'),
			'ignore' => Expect::listOf('string'),
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

		$reader = $builder->addDefinition($this->prefix('annotationReader'))
			->setType(AnnotationReader::class)
			->setAutowired(false);

		foreach ($config->ignore as $annotationName) {
			$reader->addSetup('addGlobalIgnoredName', [$annotationName]);
			AnnotationReader::addGlobalIgnoredName($annotationName);
		}

		if ($config->cache === null && $config->defaultCache !== null) {
			CacheBuilder::of($this)
				->withDefault($config->defaultCache)
				->getDefinition('annotationsCache');
		} elseif ($config->cache !== null) {
			$builder->addDefinition($this->prefix('annotationsCache'))
				->setFactory($config->cache)
				->setAutowired(false);
		} else {
			throw new InvalidStateException('Cache or defaultCache must be provided');
		}

		$builder->addDefinition($this->prefix('reader'))
			->setType(Reader::class)
			->setFactory(CachedReader::class, [
				$this->prefix('@annotationReader'),
				$this->prefix('@annotationsCache'),
				$config->debug,
			]);

		$builder->addDefinition($this->prefix('annotationDriver'))
			->setFactory(AnnotationDriver::class, [$this->prefix('@reader'), $config->paths])
			->addSetup('addExcludePaths', [$config->excludePaths]);

		$configurationDef = $this->getConfigurationDef();
		$configurationDef->addSetup('setMetadataDriverImpl', [$this->prefix('@annotationDriver')]);

		// Just for runtime
		AnnotationRegistry::registerUniqueLoader('class_exists');
	}

	public function afterCompile(ClassType $classType): void
	{
		$initialize = $classType->getMethod('initialize');
		$original = (string) $initialize->getBody();
		$initialize->setBody('?::registerUniqueLoader("class_exists");' . "\n", [new PhpLiteral(AnnotationRegistry::class)]);
		$initialize->addBody($original);
	}

}
