<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Validate path for attribute
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$extension = new class extends CompilerExtension {

			// Empty class

		};

		MappingHelper::of($extension)->addAttribute('fake', 'invalid');
	}, InvalidStateException::class, 'Given mapping path "invalid" does not exist');
});
