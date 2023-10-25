<?php declare(strict_types = 1);

namespace Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DummyEntity implements DummyIdentity
{
	#[ORM\Column(type: 'integer', unique: true, nullable: false)]
	#[ORM\GeneratedValue(strategy: 'IDENTITY')]
	#[ORM\Id]
	private int $id;


	public function getId(): int
	{
		return $this->id;
	}
}
