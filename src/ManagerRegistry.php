<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Nette\DI\Container;

class ManagerRegistry extends AbstractManagerRegistry
{

	/** @var Container */
	private $container;

	public function __construct(Connection $connection, EntityManagerInterface $em, Container $container)
	{
		$defaultConnection = $container->findByType(get_class($connection))[0];
		$defaultManager = $container->findByType(get_class($em))[0];

		$connections = ['default' => $defaultConnection];
		$managers = ['default' => $defaultManager];

		parent::__construct('ORM', $connections, $managers, 'default', 'default', Proxy::class);
		$this->container = $container;
	}

	/**
	 * @param string $name
	 * @return object&ObjectManager
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	 */
	protected function getService($name)
	{
		return $this->container->getService($name);
	}

	/**
	 * @param string $name
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	protected function resetService($name): void
	{
		$this->container->removeService($name);
	}

	/**
	 * @param string $alias
	 * @throws ORMException
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAliasNamespace($alias): string
	{
		foreach (array_keys($this->getManagers()) as $name) {
			try {
				/** @var EntityManagerInterface $entityManager */
				$entityManager = $this->getManager($name);

				return $entityManager->getConfiguration()->getEntityNamespace($alias);
			} catch (ORMException $e) {
				// Ignore
			}
		}

		throw ORMException::unknownEntityNamespace($alias);
	}

}
