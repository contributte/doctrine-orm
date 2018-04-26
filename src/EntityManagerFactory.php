<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMException;

class EntityManagerFactory
{

	public static function create(
		Connection $connection,
		Configuration $configuration,
		string $class
	): EntityManager
	{
		if (!$configuration->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}

		return new $class($connection, $configuration, $connection->getEventManager());
	}

}
