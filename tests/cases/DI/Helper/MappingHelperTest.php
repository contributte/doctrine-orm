<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Helper;

use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Tests\Toolkit\TestCase;

final class MappingHelperTest extends TestCase
{

	public function testValidatePath(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectDeprecationMessage('Given mapping path "invalid" does not exist');

		$extension = new class extends CompilerExtension {

			// Empty class

		};

		MappingHelper::of($extension)->addAnnotation('fake', 'invalid');
	}

}
