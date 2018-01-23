<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMException;

class EntityManagerFactory
{

	/**
	 * @param Connection $connection
	 * @param Configuration $configuration
	 * @param EventManager|NULL $eventManager
	 * @param string $class
	 * @return EntityManager
	 * @throws ORMException
	 */
	public static function create(
		Connection $connection,
		Configuration $configuration,
		?EventManager $eventManager = NULL,
		string $class
	): EntityManager
	{
		if (!$configuration->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}
		if ($eventManager !== NULL && $connection->getEventManager() !== $eventManager) {
			throw ORMException::mismatchedEventManager();
		}

		return new $class($connection, $configuration, $connection->getEventManager());
	}

}
