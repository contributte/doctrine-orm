<?php declare(strict_types = 1);

namespace Nettrine\ORM\Entity\Attributes;

/**
 * @deprecated Will be dropped in v0.7.
 */
trait Id
{

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=FALSE)
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 */
	private $id;

	public function getId(): int
	{
		return $this->id;
	}

	public function __clone()
	{
		$this->id = null;
	}

}
