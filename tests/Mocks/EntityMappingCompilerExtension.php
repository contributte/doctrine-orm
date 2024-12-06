<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Tests\Toolkit\Tests;

class EntityMappingCompilerExtension extends CompilerExtension
{

	public function beforeCompile(): void
	{
		MappingHelper::of($this)->addXml('Tests2', Tests::FIXTURES_PATH);
	}

}
