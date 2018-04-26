<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\Configuration;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\InvalidStateException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Utils\Validators;
use Nettrine\ORM\Mapping\AnnotationDriver;

class OrmAnnotationsExtension extends CompilerExtension
{

	/** @var mixed[] */
	public $defaults = [
		'paths' => [], //'%appDir%'
		'ignore' => [],
		'cache' => FilesystemCache::class,
		'cacheDir' => '%tempDir%/cache/Doctrine.Annotations',
	];

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		if (!$this->compiler->getExtensions(OrmExtension::class)) {
			throw new InvalidStateException(
				sprintf('You should register %s before %s.', OrmExtension::class, get_class($this))
			);
		}

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$reader = $builder->addDefinition($this->prefix('annotationReader'))
			->setClass(AnnotationReader::class)
			->setAutowired(false);

		Validators::assertField($config, 'ignore', 'array');
		foreach ($config['ignore'] as $annotationName) {
			$reader->addSetup('addGlobalIgnoredName', [$annotationName]);
			AnnotationReader::addGlobalIgnoredName($annotationName);
		}

		// Cache
		$builder->addDefinition($this->prefix('annotationsCache'))
			->setFactory($config['cache'], [Helpers::expand($config['cacheDir'], $builder->parameters)]);

		//TODO otestovat predani @...

		$builder->addDefinition($this->prefix('reader'))
			->setClass(Reader::class)
			->setFactory(CachedReader::class, [
				$this->prefix('@annotationReader'),
				$this->prefix('@annotationsCache'),
			]);

		$builder->addDefinition($this->prefix('annotationDriver'))
			->setClass(AnnotationDriver::class, [$this->prefix('@reader'), Helpers::expand($config['paths'], $builder->parameters)]);

		$builder->getDefinitionByType(Configuration::class)
			->addSetup('setMetadataDriverImpl', [$this->prefix('@annotationDriver')]);

		AnnotationRegistry::registerLoader('class_exists');
	}

	public function afterCompile(ClassType $classType): void
	{
		$initialize = $classType->getMethod('initialize');
		$original = (string) $initialize->getBody();
		$initialize->setBody('?::registerLoader("class_exists");' . "\n", [new PhpLiteral(AnnotationRegistry::class)]);
		$initialize->addBody($original);
	}

}
