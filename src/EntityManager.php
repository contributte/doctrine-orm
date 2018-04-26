<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;

class EntityManager extends DoctrineEntityManager
{

	public function __construct(Connection $connection, Configuration $configuration, EventManager $eventManager)
	{
		parent::__construct($connection, $configuration, $eventManager);
	}

}
