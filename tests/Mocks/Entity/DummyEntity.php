<?php declare(strict_types = 1);

namespace Tests\Mocks\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Tests\Mocks\DummyIdentity;

#[Entity]
class DummyEntity implements DummyIdentity
{

	#[Column(type: 'integer', unique: true, nullable: false)]
	#[GeneratedValue(strategy: 'IDENTITY')]
	#[Id]
	private int $id;

	#[Column(type: 'text', nullable: false)]
	private string $username;

	public function __construct(string $username)
	{
		$this->username = $username;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getUsername(): string
	{
		return $this->username;
	}

}
