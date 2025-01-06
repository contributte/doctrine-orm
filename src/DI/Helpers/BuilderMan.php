<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Helpers;

use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\DI\Pass\AbstractPass;
use Nettrine\ORM\Exception\LogicalException;

final class BuilderMan
{

	private AbstractPass $pass;

	private function __construct(AbstractPass $pass)
	{
		$this->pass = $pass;
	}

	public static function of(AbstractPass $pass): self
	{
		return new self($pass);
	}

	public function getConnectionByName(string $connectionName): ServiceDefinition
	{
		$connections = $this->getConnections();

		if (!isset($connections[$connectionName])) {
			throw new LogicalException(sprintf('Connection "%s" not found', $connectionName));
		}

		return $connections[$connectionName];
	}

	/**
	 * @return array<string, ServiceDefinition>
	 */
	public function getConnections(): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		/** @var array{name: string} $tagValue */
		foreach ($builder->findByTag(DbalExtension::CONNECTION_TAG) as $serviceName => $tagValue) {
			$serviceDef = $builder->getDefinition($serviceName);
			assert($serviceDef instanceof ServiceDefinition);

			$definitions[$tagValue['name']] = $serviceDef;
		}

		return $definitions;
	}

	/**
	 * @return array<string, string>
	 */
	public function getConnectionsMap(): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		/** @var array{name: string} $tagValue */
		foreach ($builder->findByTag(DbalExtension::CONNECTION_TAG) as $serviceName => $tagValue) {
			$definitions[$tagValue['name']] = $serviceName;
		}

		return $definitions;
	}

	/**
	 * @return array<string, string>
	 */
	public function getManagersMap(): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		/** @var array{name: string} $tagValue */
		foreach ($builder->findByTag(OrmExtension::MANAGER_TAG) as $serviceName => $tagValue) {
			$definitions[$tagValue['name']] = $serviceName;
		}

		/** @var array{name: string} $tagValue */
		foreach($builder->findByTag(OrmExtension::MANAGER_DECORATOR_TAG) as $serviceName => $tagValue) {
			$definitions[$tagValue['name']] = $serviceName;
		}

		return $definitions;
	}

	/**
	 * @return array<string, Definition>
	 */
	public function getServiceDefinitionsByTag(string $tag): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		foreach ($builder->findByTag($tag) as $serviceName => $tagValue) {
			$definitions[(string) $tagValue] = $builder->getDefinition($serviceName);
		}

		return $definitions;
	}

	/**
	 * @return array<string, string>
	 */
	public function getServiceNamesByTag(string $tag): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		foreach ($builder->findByTag($tag) as $serviceName => $tagValue) {
			$definitions[(string) $tagValue] = $serviceName;
		}

		return $definitions;
	}

}
