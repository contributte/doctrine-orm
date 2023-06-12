<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry as DoctrineManagerRegistry;

class ManagerProvider implements EntityManagerProvider
{

	public function __construct(
		protected DoctrineManagerRegistry $registry
	)
	{
	}

	public function getDefaultManager(): EntityManagerInterface
	{
		return $this->registry->getManager($this->registry->getDefaultManagerName());
	}

	public function getManager(string $name): EntityManagerInterface
	{
		return $this->registry->getManager($name);
	}

}
