<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\ORM\Decorator\EntityManagerDecorator as DoctrineEntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class EntityManagerDecorator extends DoctrineEntityManagerDecorator
{

	public function __construct(EntityManagerInterface $wrapped)
	{
		parent::__construct($wrapped);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return EntityRepository<T>
	 */
	public function getRepository($className): EntityRepository
	{
		return $this->wrapped->getRepository($className);
	}

}
