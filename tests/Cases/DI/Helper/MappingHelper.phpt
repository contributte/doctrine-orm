<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Nette\DI\CompilerExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Nettrine\ORM\Exception\LogicalException;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Validate path for attribute
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$extension = new class extends CompilerExtension {

			// Empty class

		};

		MappingHelper::of($extension)->addAttribute('fake', 'invalid');
	}, LogicalException::class, 'Given mapping path "invalid" does not exist');
});
