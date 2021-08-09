<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Tests\Toolkit\Tests;

class EntityMappingCompilerExtensionForAttributes extends CompilerExtension
{

	public function beforeCompile(): void
	{
		MappingHelper::of($this)->addAttribute('Tests4', Tests::FIXTURES_PATH);
	}

}
