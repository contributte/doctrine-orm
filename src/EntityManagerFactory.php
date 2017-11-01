<?php

namespace Nettrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMException;

class EntityManagerFactory
{

	const EM_CLASS = EntityManager::class;

	/**
	 * @param Connection $connection
	 * @param Configuration $configuration
	 * @param EventManager|NULL $eventManager
	 * @return EntityManager
	 * @throws ORMException
	 */
	public static function create(Connection $connection, Configuration $configuration, EventManager $eventManager = NULL)
	{
		if (!$configuration->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}
		if ($eventManager !== NULL && $connection->getEventManager() !== $eventManager) {
			throw ORMException::mismatchedEventManager();
		}

		$class = self::EM_CLASS;
		return new $class($connection, $configuration, $connection->getEventManager());
	}

}
