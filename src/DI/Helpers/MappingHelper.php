<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Helpers;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Exception\LogicalException;

class MappingHelper
{

	private CompilerExtension $extension;

	private function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
	}

	public static function of(CompilerExtension $extension): self
	{
		return new self($extension);
	}

	public function addAttribute(string $connection, string $namespace, string $path): self
	{
		if (!is_dir($path)) {
			throw new LogicalException(sprintf('Given mapping path "%s" does not exist', $path));
		}

		$chainDriver = $this->getChainDriver($connection);
		$chainDriver->addSetup('addDriver', [
			new Statement(AttributeDriver::class, [[$path]]),
			$namespace,
		]);

		return $this;
	}

	public function addXml(string $connection, string $namespace, string $path): self
	{
		if (!is_dir($path)) {
			throw new LogicalException(sprintf('Given mapping path "%s" does not exist', $path));
		}

		$chainDriver = $this->getChainDriver($connection);
		$chainDriver->addSetup('addDriver', [
			new Statement(SimplifiedXmlDriver::class, [[$path => $namespace]]),
			$namespace,
		]);

		return $this;
	}

	private function getChainDriver(string $connection): ServiceDefinition
	{
		$builder = $this->extension->getContainerBuilder();

		/** @var array<string, array{name: string}> $services */
		$services = $builder->findByTag(OrmExtension::MAPPING_DRIVER_TAG);

		if ($services === []) {
			throw new LogicalException('No mapping driver found');
		}

		foreach ($services as $serviceName => $tagValue) {
			if ($tagValue['name'] === $connection) {
				$serviceDef = $builder->getDefinition($serviceName);
				assert($serviceDef instanceof ServiceDefinition);

				return $serviceDef;
			}
		}

		throw new LogicalException(sprintf('No mapping driver found for connection "%s"', $connection));
	}

}
