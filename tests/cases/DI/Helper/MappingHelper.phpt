<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Helper;

use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Validate path
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$extension = new class extends CompilerExtension {

			// Empty class

		};

		MappingHelper::of($extension)->addAnnotation('fake', 'invalid');
	}, InvalidStateException::class, 'Given mapping path "invalid" does not exist');
});
