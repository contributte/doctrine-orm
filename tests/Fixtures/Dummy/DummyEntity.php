<?php declare(strict_types = 1);

namespace Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class DummyEntity implements DummyIdentity
{

	#[Column(type: 'integer', unique: true, nullable: false)]
	#[GeneratedValue(strategy: 'IDENTITY')]
	#[Id]
	private int $id;

	public function getId(): int
	{
		return $this->id;
	}

}
