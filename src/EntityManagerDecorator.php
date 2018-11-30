<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\ORM\Decorator\EntityManagerDecorator as DoctrineEntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;

class EntityManagerDecorator extends DoctrineEntityManagerDecorator
{

	/** @var RepositoryFactory */
	private $repositoryFactory;
	
	public function __construct(EntityManagerInterface $wrapped)
	{
		parent::__construct($wrapped);

		$this->repositoryFactory = $wrapped->getConfiguration()->getRepositoryFactory();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRepository($className)
	{
		return $this->repositoryFactory->getRepository($this, $className);
	}

}
