<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

final class DummyHydrator extends AbstractHydrator
{

	/**
	 * @return mixed[]
	 */
	protected function hydrateAllData(): array
	{
		return [];
	}

}
