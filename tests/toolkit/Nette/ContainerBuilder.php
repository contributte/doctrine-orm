<?php declare(strict_types = 1);

namespace Tests\Toolkit\Nette;

use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tests\Toolkit\Tests;

final class ContainerBuilder
{

	/** @var string */
	private $key;

	/** @var callable[] */
	private $onCompile = [];

	public function __construct(string $key)
	{
		$this->key = $key;
	}

	public static function of(?string $key = null): ContainerBuilder
	{
		return new static($key ?? uniqid(random_bytes(16)));
	}

	public function withDefaults(): ContainerBuilder
	{
		$this->withDefaultExtensions();
		$this->withDefaultParameters();

		return $this;
	}

	public function withDefaultExtensions(): ContainerBuilder
	{
		$this->onCompile[] = function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.cache', new CacheExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
		};

		return $this;
	}

	public function withDefaultParameters(): ContainerBuilder
	{
		$this->onCompile[] = function (Compiler $compiler): void {
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
					'appDir' => Tests::APP_PATH,
				],
			]);
		};

		return $this;
	}

	public function withCompiler(callable $cb): ContainerBuilder
	{
		$this->onCompile[] = function (Compiler $compiler) use ($cb): void {
			$cb($compiler);
		};

		return $this;
	}

	public function build(): Container
	{
		$loader = new ContainerLoader(Tests::TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			foreach ($this->onCompile as $cb) {
				$cb($compiler);
			}
		}, $this->key);

		/** @var Container $container */
		$container = new $class();

		return $container;
	}

}
