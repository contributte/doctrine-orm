<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Traits\TEntityMapping;
use Tests\Toolkit\Tests;

class EntityMappingCompilerExtension extends CompilerExtension
{

	use TEntityMapping;

	public function loadConfiguration(): void
	{
		$this->setEntityMappings([
			'Tests' => Tests::FIXTURES_PATH,
		]);
	}

}
