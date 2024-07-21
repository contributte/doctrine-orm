<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use Nette\DI\Container;

class ManagerRegistry extends AbstractManagerRegistry
{

	private Container $container;

	public function __construct(Connection $connection, EntityManagerInterface $em, Container $container)
	{
		$defaultConnection = $container->findByType($connection::class)[0];
		$defaultManager = $container->findByType($em::class)[0];

		$connections = ['default' => $defaultConnection];
		$managers = ['default' => $defaultManager];

		parent::__construct('ORM', $connections, $managers, 'default', 'default', Proxy::class);

		$this->container = $container;
	}

	/**
	 * @return object&ObjectManager
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	 */
	protected function getService(string $name)
	{
		return $this->container->getService($name);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	protected function resetService(string $name): void
	{
		$this->container->removeService($name);
	}

}
