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
		/** @var EntityManagerInterface $manager */
		$manager = $this->registry->getManager($this->registry->getDefaultManagerName());

		return $manager;
	}

	public function getManager(string $name): EntityManagerInterface
	{
		/** @var EntityManagerInterface $manager */
		$manager = $this->registry->getManager($name);

		return $manager;
	}

}
