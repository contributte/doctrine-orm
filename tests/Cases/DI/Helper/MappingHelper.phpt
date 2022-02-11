<?php declare(strict_types = 1);

use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Validate path for annotation
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$extension = new class extends CompilerExtension {

			// Empty class

		};

		MappingHelper::of($extension)->addAnnotation('fake', 'invalid');
	}, InvalidStateException::class, 'Given mapping path "invalid" does not exist');
});


// Validate path for attribute
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$extension = new class extends CompilerExtension {

			// Empty class

		};

		MappingHelper::of($extension)->addAttribute('fake', 'invalid');
	}, InvalidStateException::class, 'Given mapping path "invalid" does not exist');
});
