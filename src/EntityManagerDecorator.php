<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\ORM\Decorator\EntityManagerDecorator as DoctrineEntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;

class EntityManagerDecorator extends DoctrineEntityManagerDecorator
{

	public function __construct(EntityManagerInterface $wrapped)
	{
		parent::__construct($wrapped);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRepository($className)
	{
		return $this->wrapped->getRepository($className);
	}

}
