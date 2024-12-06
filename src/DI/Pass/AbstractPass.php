<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Pass;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\PhpGenerator\ClassType;
use stdClass;

abstract class AbstractPass
{

	protected CompilerExtension $extension;

	public function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
	}

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		// Override in child
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		// Override in child
	}

	/**
	 * Update PHP code
	 */
	public function afterPassCompile(ClassType $class): void
	{
		// Override in child
	}

	public function prefix(string $id): string
	{
		return $this->extension->prefix($id);
	}

	public function getContainerBuilder(): ContainerBuilder
	{
		return $this->extension->getContainerBuilder();
	}

	public function getConfig(): stdclass
	{
		/** @var stdclass $ret */
		$ret = (object) $this->extension->getConfig();

		return $ret;
	}

}
