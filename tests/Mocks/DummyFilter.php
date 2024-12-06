<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class DummyFilter extends SQLFilter
{

	/**
	 * @inheritDoc
	 */
	public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
	{
		return '';
	}

}
